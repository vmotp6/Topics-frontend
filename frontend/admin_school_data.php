<?php
// 載入 session 配置
require_once 'session_config.php';

// 檢查管理員權限
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== '管理員') {
    header('Location: index.php');
    exit;
}

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("資料庫連接失敗: " . $e->getMessage());
}

// 處理更新請求
if ($_POST['action'] ?? '' === 'update_school_data') {
    try {
        // 執行更新腳本
        $output = shell_exec('php ../scripts/fetch_real_school_data.php 2>&1');
        $success = true;
        $message = "全台灣國民中學資料更新完成";
    } catch (Exception $e) {
        $success = false;
        $message = "更新失敗: " . $e->getMessage();
    }
}

// 獲取統計資料
try {
    $stats = [];
    
    // 總學校數
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM school_data WHERE is_active = 1");
    $stats['total'] = $stmt->fetch()['total'];
    
    // 按類型統計
    $stmt = $pdo->query("SELECT type, COUNT(*) as count FROM school_data WHERE is_active = 1 GROUP BY type");
    $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 按城市統計
    $stmt = $pdo->query("SELECT city, COUNT(*) as count FROM school_data WHERE is_active = 1 GROUP BY city ORDER BY count DESC LIMIT 10");
    $stats['by_city'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 最後更新時間
    $stmt = $pdo->query("SELECT MAX(last_updated) as last_update FROM school_data");
    $stats['last_update'] = $stmt->fetch()['last_update'];
    
} catch (PDOException $e) {
    $stats = [];
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>學校資料管理 - 康寧大學</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Microsoft JhengHei', sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2rem;
        }
        .content {
            padding: 30px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
        }
        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: #28a745;
        }
        .action-section {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background: #218838;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .loading i {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php include("share/header.php"); ?>
    
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-school"></i> 學校資料管理</h1>
            <p>管理台灣教育部開放資料的學校資訊</p>
        </div>
        
        <div class="content">
            <?php if (isset($message)): ?>
                <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?>">
                    <i class="fas fa-<?php echo $success ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- 統計資料 -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>總學校數</h3>
                    <div class="number"><?php echo $stats['total'] ?? 0; ?></div>
                </div>
                <div class="stat-card">
                    <h3>最後更新</h3>
                    <div class="number" style="font-size: 1rem;">
                        <?php echo $stats['last_update'] ? date('Y-m-d H:i', strtotime($stats['last_update'])) : '未更新'; ?>
                    </div>
                </div>
                <div class="stat-card">
                    <h3>資料來源</h3>
                    <div class="number" style="font-size: 1rem;">教育部開放資料</div>
                </div>
            </div>
            
            <!-- 操作區域 -->
            <div class="action-section">
                <h3><i class="fas fa-cogs"></i> 資料管理操作</h3>
                <p>點擊下方按鈕來更新學校資料。系統會從台灣教育部開放資料平台獲取全台灣國民中學的最新資訊，包含中崙國中等所有學校。</p>
                
                <form method="POST" id="updateForm">
                    <input type="hidden" name="action" value="update_school_data">
                    <button type="submit" class="btn" id="updateBtn">
                        <i class="fas fa-sync-alt"></i> 更新學校資料
                    </button>
                </form>
                
                <div class="loading" id="loading">
                    <i class="fas fa-spinner"></i> 正在更新資料，請稍候...
                </div>
            </div>
            
            <!-- 按類型統計 -->
            <?php if (!empty($stats['by_type'])): ?>
            <div class="action-section">
                <h3><i class="fas fa-chart-pie"></i> 按學校類型統計</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>學校類型</th>
                            <th>數量</th>
                            <th>百分比</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['by_type'] as $type): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($type['type']); ?></td>
                            <td><?php echo $type['count']; ?></td>
                            <td><?php echo round(($type['count'] / $stats['total']) * 100, 1); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- 按城市統計 -->
            <?php if (!empty($stats['by_city'])): ?>
            <div class="action-section">
                <h3><i class="fas fa-map-marker-alt"></i> 按城市統計 (前10名)</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>城市</th>
                            <th>學校數量</th>
                            <th>百分比</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['by_city'] as $city): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($city['city']); ?></td>
                            <td><?php echo $city['count']; ?></td>
                            <td><?php echo round(($city['count'] / $stats['total']) * 100, 1); ?>%</td>
                            <td>
                                <a href="test_school_api.php?city=<?php echo urlencode($city['city']); ?>" class="btn" style="padding: 5px 10px; font-size: 0.8rem;">
                                    <i class="fas fa-eye"></i> 查看學校
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="city_schools.php" class="btn">
                    <i class="fas fa-map-marker-alt"></i> 城市學校瀏覽
                </a>
                <a href="test_school_api.php" class="btn">
                    <i class="fas fa-search"></i> API測試
                </a>
                <a href="cooperation_upload.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> 返回就讀意願登錄
                </a>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('updateForm').addEventListener('submit', function(e) {
            const updateBtn = document.getElementById('updateBtn');
            const loading = document.getElementById('loading');
            
            updateBtn.style.display = 'none';
            loading.style.display = 'block';
        });
    </script>
</body>
</html>
