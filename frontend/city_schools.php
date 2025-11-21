<?php
// 載入 session 配置
require_once 'session_config.php';

// 資料庫連接
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("資料庫連接失敗: " . $e->getMessage());
}

// 獲取城市列表
try {
    $stmt = $pdo->query("SELECT city, COUNT(*) as count FROM school_data WHERE type = '國民中學' AND is_active = 1 GROUP BY city ORDER BY count DESC");
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $cities = [];
}

// 獲取選定城市的學校
$selectedCity = $_GET['city'] ?? '';
$schools = [];

if ($selectedCity) {
    try {
        $stmt = $pdo->prepare("SELECT name, city, district, type, school_code FROM school_data WHERE city = ? AND type = '國民中學' AND is_active = 1 ORDER BY district, name");
        $stmt->execute([$selectedCity]);
        $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $schools = [];
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>城市學校瀏覽 - 康寧大學</title>
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
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
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
        .city-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .city-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }
        .city-card:hover {
            background: #e9ecef;
            border-color: #28a745;
            transform: translateY(-2px);
        }
        .city-card.selected {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }
        .city-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .city-count {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .city-card.selected .city-count {
            color: rgba(255,255,255,0.8);
        }
        .schools-section {
            margin-top: 30px;
        }
        .schools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }
        .school-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        .school-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .school-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        .school-location {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        .school-type {
            color: #28a745;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .stats {
            background: #e8f5e8;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
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
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include("share/header.php"); ?>
    
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-map-marker-alt"></i> 城市學校瀏覽</h1>
            <p>按城市查看全台灣國民中學</p>
        </div>
        
        <div class="content">
            <div class="stats">
                <h3>📊 統計資訊</h3>
                <p>共涵蓋 <strong><?php echo count($cities); ?></strong> 個城市，總計 <strong><?php echo array_sum(array_column($cities, 'count')); ?></strong> 所國民中學</p>
            </div>
            
            <h3><i class="fas fa-city"></i> 選擇城市</h3>
            <div class="city-grid">
                <?php foreach ($cities as $city): ?>
                <a href="?city=<?php echo urlencode($city['city']); ?>" 
                   class="city-card <?php echo $selectedCity === $city['city'] ? 'selected' : ''; ?>">
                    <div class="city-name"><?php echo htmlspecialchars($city['city']); ?></div>
                    <div class="city-count"><?php echo $city['count']; ?> 所學校</div>
                </a>
                <?php endforeach; ?>
            </div>
            
            <?php if ($selectedCity): ?>
            <div class="schools-section">
                <h3><i class="fas fa-school"></i> <?php echo htmlspecialchars($selectedCity); ?> 的國民中學</h3>
                
                <?php if (!empty($schools)): ?>
                <div class="stats">
                    <p>共找到 <strong><?php echo count($schools); ?></strong> 所國民中學</p>
                </div>
                
                <div class="schools-grid">
                    <?php foreach ($schools as $school): ?>
                    <div class="school-card">
                        <div class="school-name">
                            <i class="fas fa-school"></i> <?php echo htmlspecialchars($school['name']); ?>
                        </div>
                        <div class="school-location">
                            📍 <?php echo htmlspecialchars($school['city'] . ' ' . $school['district']); ?>
                        </div>
                        <div class="school-type">
                            <?php echo htmlspecialchars($school['type']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #ffc107; margin-bottom: 20px;"></i>
                    <h3>沒有找到學校</h3>
                    <p><?php echo htmlspecialchars($selectedCity); ?> 目前沒有國民中學資料</p>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="no-data">
                <i class="fas fa-hand-pointer" style="font-size: 3rem; color: #28a745; margin-bottom: 20px;"></i>
                <h3>請選擇城市</h3>
                <p>點擊上方城市卡片來查看該城市的國民中學</p>
            </div>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="test_school_api.php" class="btn">
                    <i class="fas fa-search"></i> 搜尋功能測試
                </a>
                <a href="admin_school_data.php" class="btn btn-secondary">
                    <i class="fas fa-cogs"></i> 管理介面
                </a>
                <a href="cooperation_upload.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> 返回就讀意願登錄
                </a>
            </div>
        </div>
    </div>
</body>
</html>
