<?php
// 載入 session 配置
require_once 'session_config.php';

// 引入配置檔案
require_once 'config.php';

// 檢查登入狀態
$debug_mode = true; // 設為 false 可關閉調試模式

if ($debug_mode) {
    // 調試模式：顯示詳細資訊
   if (
    (!isset($_SESSION['user_id']) && !isset($_SESSION['id']) && !isset($_SESSION['username'])) ||
    !isset($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['老師', '學校行政人員'])
) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; margin: 20px; border-radius: 5px; border: 1px solid #f5c6cb;'>";
        echo "<h3>⚠️ 登入驗證失敗</h3>";
        echo "<p><strong>原因分析：</strong>您需要以教師身分登入才能使用此功能</p>";
        echo "<div style='margin-top: 15px;'>";
        echo "<a href='login.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;'>前往登入頁面</a>";
        echo "</div>";
        echo "</div>";
        exit();
    }
} else {
    // 正常模式：直接跳轉
    if ((!isset($_SESSION['user_id']) && !isset($_SESSION['id']) && !isset($_SESSION['username'])) || !isset($_SESSION['role']) || $_SESSION['role'] !== '老師') {
        header("Location: login.php");
        exit();
    }
}

// 建立資料庫連接
$conn = getDatabaseConnection();

// 獲取登入教師的資訊
$teacher_id = null;
$teacher_info = null;

// 從 teacher 表獲取教師詳細資訊
if (isset($_SESSION['user_id'])) {
    $teacher_id = $_SESSION['user_id'];
    $teacher_sql = "SELECT * FROM teacher WHERE user_id = ?";
    $teacher_stmt = $conn->prepare($teacher_sql);
    if ($teacher_stmt) {
        $teacher_stmt->bind_param("i", $teacher_id);
    }
} elseif (isset($_SESSION['id'])) {
    $teacher_id = $_SESSION['id'];
    $teacher_sql = "SELECT * FROM teacher WHERE user_id = ?";
    $teacher_stmt = $conn->prepare($teacher_sql);
    if ($teacher_stmt) {
        $teacher_stmt->bind_param("i", $teacher_id);
    }
} elseif (isset($_SESSION['username'])) {
    $teacher_sql = "SELECT t.* FROM teacher t 
                    INNER JOIN user u ON t.user_id = u.id 
                    WHERE u.username = ?";
    $teacher_stmt = $conn->prepare($teacher_sql);
    if ($teacher_stmt) {
        $teacher_stmt->bind_param("s", $_SESSION['username']);
    }
}

if (isset($teacher_stmt) && $teacher_stmt !== false) {
    $teacher_stmt->execute();
    $teacher_result = $teacher_stmt->get_result();

    if ($teacher_result && $teacher_result->num_rows > 0) {
        $teacher_info = $teacher_result->fetch_assoc();
        if (!$teacher_id && $teacher_info) {
            $teacher_id = $teacher_info['user_id'];
        }
    }
    $teacher_stmt->close();
}

 // 處理記錄操作 (編輯)
 $message = "";
 $messageType = "";
 
 if ($_POST) {
     if (isset($_POST['action'])) {
         switch ($_POST['action']) {
             case 'update':
                if (isset($_POST['record_id']) && is_numeric($_POST['record_id'])) {
                    $record_id = $_POST['record_id'];
                    $update_sql = "UPDATE activity_records SET 
                                   activity_date = ?, 
                                   school_name = ?, 
                                   activity_type = ?, 
                                   activity_time = ?,
                                   suggestion = ?
                                   WHERE id = ? AND teacher_id = ?";
                    
                    $update_stmt = $conn->prepare($update_sql);
                    if ($update_stmt) {
                        $update_stmt->bind_param("sssssii", 
                            $_POST['activity_date'],
                            $_POST['school_name'],
                            $_POST['activity_type'],
                            $_POST['activity_time'],
                            $_POST['suggestion'],
                            $record_id,
                            $teacher_id
                        );
                        
                        if ($update_stmt->execute()) {
                            $message = "記錄已成功更新！";
                            $messageType = "success";
                        } else {
                            $message = "更新失敗：" . $update_stmt->error;
                            $messageType = "error";
                        }
                        $update_stmt->close();
                    }
                }
                break;
        }
    }
}

// 查詢該教師的活動記錄
// 查詢活動記錄
$activity_records = [];

if (isset($_SESSION['role']) && $_SESSION['role'] === '學校行政人員') {
    // 🔹 若是招生中心 → 查看所有老師紀錄
    $records_sql = "SELECT ar.*, t.name AS teacher_name, t.department AS teacher_department
                    FROM activity_records ar
                    LEFT JOIN teacher t ON ar.teacher_id = t.user_id
                    WHERE 1 ";

                     // 篩選參數
    $params = [];
    $types = '';

    if (!empty($_GET['teacher_name'])) {
        $records_sql .= " AND t.name LIKE ? ";
        $params[] = "%" . $_GET['teacher_name'] . "%";
        $types .= 's';
    }

    if (!empty($_GET['department'])) {
        $records_sql .= " AND t.department = ? ";
        $params[] = $_GET['department'];
        $types .= 's';
    }

    $records_sql .= " ORDER BY ar.activity_date DESC, ar.id DESC";

    $records_stmt = $conn->prepare($records_sql);
    if ($records_stmt) {
        if (!empty($params)) {
            $records_stmt->bind_param($types, ...$params);
        }
        $records_stmt->execute();
        $records_result = $records_stmt->get_result();

        if ($records_result) {
            while ($row = $records_result->fetch_assoc()) {
                $activity_records[] = $row;
            }
        }
        $records_stmt->close();
    }
                    



} elseif ($teacher_id) {
    // 🔹 若是一般老師 → 只看自己的，並包含所屬系所
    $records_sql = "
        SELECT 
            ar.*, 
            t.name AS teacher_name, 
            t.department AS teacher_department
        FROM activity_records ar
        LEFT JOIN teacher t ON ar.teacher_id = t.user_id
        WHERE ar.teacher_id = ?
        ORDER BY ar.activity_date DESC, ar.id DESC
    ";

    $records_stmt = $conn->prepare($records_sql);
    if ($records_stmt) {
        $records_stmt->bind_param("i", $teacher_id);
        $records_stmt->execute();
        $records_result = $records_stmt->get_result();
        
        if ($records_result) {
            while ($row = $records_result->fetch_assoc()) {
                $activity_records[] = $row;
            }
        }
        $records_stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>活動記錄管理系統</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/csp/records.css">
<<<<<<< HEAD
    <script src="https://cdn.jsdelivr.net/npm/chart.js?v=2.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2?v=2.0"></script>
    <style>
         .management-container {
             max-width: 1200px;
             margin: 0 auto;
             padding: 20px;
             background: #f8f9fa;
             min-height: auto;
         }
        
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
         .stats-grid {
             display: grid;
             grid-template-columns: repeat(4, 1fr);
             gap: 20px;
             margin-bottom: 30px;
         }
         
         @media (max-width: 768px) {
             .stats-grid {
                 grid-template-columns: repeat(2, 1fr);
             }
         }
         
         @media (max-width: 480px) {
             .stats-grid {
                 grid-template-columns: 1fr;
             }
         }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #6c757d;
            font-weight: 500;
        }
        
         .records-table-container {
             background: white;
             border-radius: 15px;
             padding: 30px;
             box-shadow: 0 4px 15px rgba(0,0,0,0.1);
             margin-bottom: 50px;
         }
        
        .records-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .records-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 10px;
            text-align: left;
            font-weight: 600;
            border: none;
        }
        
        .records-table td {
            padding: 15px 10px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        
        .records-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85em;
            margin: 2px;
            transition: all 0.3s ease;
        }
        
         .btn-edit {
             background: #28a745;
             color: white;
         }
         
         .btn-view {
             background: #007bff;
             color: white;
         }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .search-tools {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .tools-grid {
            display: grid;
            grid-template-columns: 1fr 1fr auto auto;
            gap: 15px;
            align-items: end;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
        }
        
        .form-group input,
        .form-group select {
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 0.9em;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover,
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 80%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        
        .close:hover {
            color: #000;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .back-btn {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
         .back-btn:hover {
             transform: translateY(-2px);
             box-shadow: 0 4px 12px rgba(0,0,0,0.2);
             text-decoration: none;
             color: white;
         }
         
         body.page-body {
             padding-bottom: 0;
             margin-bottom: 0;
             display: block !important;
             min-height: auto !important;
         }
         
         .page-body .management-container {
             position: relative;
             z-index: 1;
             margin-bottom: 200px;
         }
         
         /* 頁面包裝器 */
         .page-wrapper {
             min-height: 100vh;
             padding-bottom: 150px;
             box-sizing: border-box;
         }
         
         /* 確保footer不會遮擋內容 */
         .footer {
             position: relative !important;
             margin-top: 50px !important;
             clear: both;
         }
         
         /* 圖表容器樣式 */
         .chart-container {
             position: relative;
             height: 450px;
             margin: 20px 0;
             padding: 20px;
         }
         
         .chart-grid {
             display: grid;
             grid-template-columns: 1fr 1fr;
             gap: 30px;
             margin: 20px 0;
         }
         
         @media (max-width: 768px) {
             .chart-grid {
                 grid-template-columns: 1fr;
             }
         }
         
         .chart-card {
             background: white;
             padding: 20px;
             border-radius: 10px;
             box-shadow: 0 2px 10px rgba(0,0,0,0.1);
         }
         
         .chart-title {
             font-size: 1.2em;
             font-weight: bold;
             color: #333;
             margin-bottom: 15px;
             text-align: center;
         }
    </style>
=======
    <link rel="stylesheet" href="assets/csp/activity_records_management.css">
>>>>>>> 9e216731cdb08f4b37bc5fa33d9b45a117cad662
</head>

<?php include("share/header.php"); ?>

<body class="page-body">
    <div class="page-wrapper">
        <div class="management-container">
        <!-- 返回按鈕 -->
        <a href="records.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            返回活動記錄填報
        </a>
        
        <!-- 頁面標題 -->
        <div class="header-section">
            <h1><i class="fas fa-chart-line"></i> 活動記錄管理系統</h1>
            <p>管理您的所有活動記錄 | 編輯、查看、統計分析</p>
            <?php if ($teacher_info): ?>
                <div style="margin-top: 15px; font-size: 1.1em;">
                    <i class="fas fa-user"></i> 
                    <?php echo htmlspecialchars($teacher_info['name'] ?? '未設定'); ?> - 
                    <?php echo htmlspecialchars($teacher_info['department'] ?? '未設定'); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 訊息顯示 -->
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- 統計資訊 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($activity_records); ?></div>
                <div class="stat-label">
                    <i class="fas fa-list"></i> 總記錄數
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number">
                    <?php
                    $recent_count = 0;
                    $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
                    foreach ($activity_records as $record) {
                        if ($record['activity_date'] >= $thirty_days_ago) {
                            $recent_count++;
                        }
                    }
                    echo $recent_count;
                    ?>
                </div>
                <div class="stat-label">
                    <i class="fas fa-calendar-week"></i> 近30天記錄
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number">
                    <?php
                    $schools = [];
                    foreach ($activity_records as $record) {
                        $schools[$record['school_name']] = true;
                    }
                    echo count($schools);
                    ?>
                </div>
                <div class="stat-label">
                    <i class="fas fa-school"></i> 合作學校數
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number">
                    <?php
                    $types = [];
                    foreach ($activity_records as $record) {
                        $types[$record['activity_type']] = true;
                    }
                    echo count($types);
                    ?>
                </div>
                <div class="stat-label">
                    <i class="fas fa-tags"></i> 活動類型數
                </div>
            </div>
        </div>

        <!-- 搜索和篩選工具 -->
        <div class="search-tools">
            <div class="tools-grid">
                <div class="form-group">
                    <label><i class="fas fa-search"></i> 搜索記錄</label>
                    <input type="text" id="searchRecords" placeholder="輸入學校名稱或活動類型..." onkeyup="filterRecords()">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> 活動日期</label>
                    <input type="date" id="filterActivityDate" onchange="filterRecords()">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-filter"></i> 活動類型</label>
                    <select id="filterActivityType" onchange="filterRecords()">
                        <option value="">全部類型</option>
                        <option value="來校體驗">來校體驗</option>
                        <option value="校外參訪">校外參訪</option>
                        <option value="講座分享">講座分享</option>
                    </select>
                </div>
                
                <button type="button" class="btn-secondary" onclick="resetRecordsFilter()">
                    <i class="fas fa-undo"></i> 重置
                </button>
                
                <button type="button" class="btn-primary" onclick="window.location.href='records.php'">
                    <i class="fas fa-plus"></i> 新增記錄
                </button>
            </div>
            
            <div id="filterStats" style="margin-top: 15px; font-size: 0.9em; color: #6c757d;">
                <i class="fas fa-info-circle"></i> 顯示全部 <?php echo count($activity_records); ?> 筆記錄
            </div>
        </div>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === '學校行政人員'): ?>
        <!-- 統計報表功能 (僅行政人員可見) -->
        <div class="analytics-section" style="background: white; border-radius: 15px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 30px;">
            <h3 style="color: #667eea; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-chart-bar"></i> 招生活動統計分析
            </h3>
            
            <!-- 統計報表按鈕組 -->
            <div style="display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap;">
                <button class="btn-primary" onclick="showTeacherStats()" style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-users"></i> 教師活動統計
                </button>
                <button class="btn-primary" onclick="showActivityTypeStats()" style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-chart-pie"></i> 活動類型分析
                </button>
                <button class="btn-primary" onclick="showTimeStats()" style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-calendar-alt"></i> 時間分布分析
                </button>
                <button class="btn-primary" onclick="showSchoolStats()" style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-school"></i> 合作學校統計
                </button>
            </div>
            
            <!-- 統計內容區域 -->
            <div id="analyticsContent" style="min-height: 200px;">
                <div style="text-align: center; padding: 40px; color: #6c757d;">
                    <i class="fas fa-chart-line" style="font-size: 3em; margin-bottom: 15px; color: #dee2e6;"></i>
                    <h4>選擇上方的統計類型來查看詳細分析</h4>
                    <p>提供教師活動參與度、活動類型分布、時間趨勢等多維度統計</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 記錄列表 -->
        <div class="records-table-container">
            <h3><i class="fas fa-table"></i> 活動記錄列表</h3>
            <div class="filter-bar" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
    <form method="GET" action="activity_records_management.php" style="display: flex; gap: 10px;">
        <input type="text" name="teacher_name" placeholder="搜尋教師姓名"
               value="<?php echo htmlspecialchars($_GET['teacher_name'] ?? ''); ?>"
               style="padding: 5px 10px; border-radius: 6px; border: 1px solid #ccc;">

        <select name="department" style="padding: 5px 10px; border-radius: 6px; border: 1px solid #ccc;">
            <option value="">全部科系</option>
            <option value="資訊管理科" <?php if(($_GET['department'] ?? '') == '資訊管理科') echo 'selected'; ?>>資訊管理科</option>
            <option value="企業管理科" <?php if(($_GET['department'] ?? '') == '企業管理科') echo 'selected'; ?>>企業管理科</option>
            <option value="應用外語科" <?php if(($_GET['department'] ?? '') == '應用外語科') echo 'selected'; ?>>應用外語科</option>
            <!-- 可依實際資料庫科系補上更多選項 -->
        </select>

        <button type="submit" style="padding: 5px 15px; border: none; border-radius: 6px; background-color: #4CAF50; color: white; cursor: pointer;">
            🔍 篩選
        </button>
        <a href="activity_records_management.php" style="padding: 5px 15px; border: none; border-radius: 6px; background-color: #888; color: white; text-decoration: none;">
            重置
        </a>
    </form>

    
</div>
            <?php if (!empty($activity_records)): ?>
                <table class="records-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-calendar"></i> 活動日期</th>
                            <th><i class="fas fa-user"></i> 教師姓名</th>
                            <th><i class="fas fa-building"></i> 所屬系所</th>
                            <th><i class="fas fa-school"></i> 學校名稱</th>
                            <th><i class="fas fa-tag"></i> 活動類型</th>
                            <th><i class="fas fa-clock"></i> 活動時間</th>
                            <th><i class="fas fa-calendar-plus"></i> 提交時間</th>
                            <th><i class="fas fa-cogs"></i> 操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activity_records as $record): ?>
                            <tr class="record-row">
                                <td><?php echo htmlspecialchars($record['activity_date']); ?></td>
                                <td><?php echo htmlspecialchars($record['teacher_name'] ?? '—'); ?></td>
                                 <td><?php echo htmlspecialchars($record['teacher_department'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($record['school_name']); ?></td>
                                <td>
                                    <span class="activity-type"><?php echo htmlspecialchars($record['activity_type']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($record['activity_time']); ?></td>
                                <td><?php echo date('Y/m/d H:i', strtotime($record['created_at'] ?? $record['activity_date'])); ?></td>
                                <td>
                                    <button class="action-btn btn-view" onclick="viewRecord(<?php echo $record['id']; ?>)">
                                        <i class="fas fa-eye"></i> 查看
                                    </button>
                                    <button class="action-btn btn-edit" onclick="editRecord(<?php echo $record['id']; ?>)">
                                        <i class="fas fa-edit"></i> 編輯
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 60px; color: #6c757d;">
                    <i class="fas fa-inbox" style="font-size: 4em; margin-bottom: 20px; color: #dee2e6;"></i>
                    <h3>尚無活動記錄</h3>
                    <p>您還沒有任何活動記錄，<a href="records.php" style="color: #667eea;">點此新增第一筆記錄</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>

    <!-- 查看記錄模態框 -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-eye"></i> 記錄詳細資訊</h3>
                <span class="close" onclick="closeModal('viewModal')">&times;</span>
            </div>
            <div id="viewModalBody">
                <!-- 內容將由JavaScript動態填入 -->
            </div>
        </div>
    </div>

    <!-- 編輯記錄模態框 -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> 編輯記錄</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <div id="editModalBody">
                <!-- 內容將由JavaScript動態填入 -->
            </div>
        </div>
    </div>

    <script>
        // 記錄資料 (轉為JavaScript可用格式)
        const activityRecords = <?php echo json_encode($activity_records); ?>;
        
        // 篩選記錄功能
        function filterRecords() {
            const searchValue = document.getElementById('searchRecords').value.toLowerCase();
            const filterType = document.getElementById('filterActivityType').value;
            const filterDate = document.getElementById('filterActivityDate').value;
            const rows = document.querySelectorAll('.record-row');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const schoolName = row.cells[1].textContent.toLowerCase();
                const activityType = row.cells[2].textContent.toLowerCase();
                
                const matchesSearch = searchValue === '' || 
                                    schoolName.includes(searchValue) || 
                                    activityType.includes(searchValue);
                                    
                const matchesType = filterType === '' || activityType.includes(filterType.toLowerCase());
                const activityDate = row.cells[0].textContent.trim(); // 假設活動日期是第0欄
                const matchesDate = filterDate === '' || activityDate === filterDate;

                if (matchesSearch && matchesType && matchesDate) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // 更新篩選統計
            const filterStats = document.getElementById('filterStats');
            const totalCount = rows.length;
            if (visibleCount === totalCount) {
                filterStats.innerHTML = `<i class="fas fa-info-circle"></i> 顯示全部 ${totalCount} 筆記錄`;
            } else {
                filterStats.innerHTML = `<i class="fas fa-filter"></i> 篩選顯示 ${visibleCount} / ${totalCount} 筆記錄`;
            }
        }
        
        // 重置篩選
        function resetRecordsFilter() {
            document.getElementById('searchRecords').value = '';
            document.getElementById('filterActivityType').value = '';
            document.getElementById('filterActivityDate').value = '';
            filterRecords();
        }
        
        // 查看記錄詳細資訊
        function viewRecord(recordId) {
            const record = activityRecords.find(r => r.id == recordId);
            if (!record) return;
            
            const modalBody = document.getElementById('viewModalBody');
            modalBody.innerHTML = `
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div><strong>活動日期:</strong><br>${record.activity_date}</div>
                    <div><strong>教師單位:</strong><br>${record.teacher_department}</div>
                    <div><strong>教師姓名:</strong><br>${record.teacher_name}</div>
                    <div><strong>學校名稱:</strong><br>${record.school_name}</div>
                    <div><strong>聯絡窗口:</strong><br>${record.contact_person || '未填寫'}</div>
                    <div><strong>聯絡電話:</strong><br>${record.contact_phone || '未填寫'}</div>
                    <div><strong>活動性質:</strong><br>${record.activity_type}</div>
                    <div><strong>活動時間:</strong><br>${record.activity_time}</div>
                </div>
                ${record.participants ? `<div style="margin-top: 15px;"><strong>參與對象:</strong><br>${record.participants}</div>` : ''}
                ${record.activity_feedback ? `<div style="margin-top: 15px;"><strong>活動紀錄:</strong><br>${record.activity_feedback}</div>` : ''}
                ${record.suggestion ? `<div style="margin-top: 15px;"><strong>檢討與建議:</strong><br>${record.suggestion}</div>` : ''}
            `;
            
            document.getElementById('viewModal').style.display = 'block';
        }
        
        // 編輯記錄
        function editRecord(recordId) {
            const record = activityRecords.find(r => r.id == recordId);
            if (!record) return;
            
            const modalBody = document.getElementById('editModalBody');
            modalBody.innerHTML = `
                <form method="post" action="">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="record_id" value="${record.id}">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label><strong>活動日期:</strong></label>
                            <input type="date" name="activity_date" value="${record.activity_date}" required style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 5px;">
                        </div>
                        <div>
                            <label><strong>學校名稱:</strong></label>
                            <input type="text" name="school_name" value="${record.school_name}" required style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 5px;">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label><strong>活動性質:</strong></label>
                            <select name="activity_type" required style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 5px;">
                                <option value="來校體驗" ${record.activity_type === '來校體驗' ? 'selected' : ''}>來校體驗</option>
                                <option value="校外參訪" ${record.activity_type === '校外參訪' ? 'selected' : ''}>校外參訪</option>
                                <option value="講座分享" ${record.activity_type === '講座分享' ? 'selected' : ''}>講座分享</option>
                            </select>
                        </div>
                        <div>
                            <label><strong>活動時間:</strong></label>
                            <select name="activity_time" required style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 5px;">
                                <option value="上班日" ${record.activity_time === '上班日' ? 'selected' : ''}>上班日</option>
                                <option value="假日" ${record.activity_time === '假日' ? 'selected' : ''}>假日</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label><strong>檢討與建議:</strong></label>
                        <textarea name="suggestion" rows="4" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 5px;">${record.suggestion || ''}</textarea>
                    </div>
                    
                    <div style="text-align: right;">
                        <button type="button" class="btn-secondary" onclick="closeModal('editModal')" style="margin-right: 10px;">取消</button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> 儲存變更
                        </button>
                    </div>
                </form>
            `;
            
            document.getElementById('editModal').style.display = 'block';
        }
        
        
        // 關閉模態框
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // 點擊模態框外部關閉
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // 清除特定圖表實例
        function clearSpecificChart(chartName) {
            if (window[chartName]) {
                window[chartName].destroy();
                window[chartName] = null;
            }
        }
        
        // 統計分析功能 (僅行政人員)
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === '學校行政人員'): ?>
        
        // 教師活動統計
        function showTeacherStats() {
            // 清除相關圖表
            clearSpecificChart('departmentChart');
            
            const teacherStats = {};
            
            // 統計每位教師的活動
            activityRecords.forEach(record => {
                const teacherName = record.teacher_name || '未知教師';
                const department = record.teacher_department || '未知科系';
                
                if (!teacherStats[teacherName]) {
                    teacherStats[teacherName] = {
                        name: teacherName,
                        department: department,
                        totalActivities: 0,
                        activityTypes: {},
                        schools: new Set(),
                        recentActivities: 0
                    };
                }
                
                teacherStats[teacherName].totalActivities++;
                
                // 統計活動類型
                const activityType = record.activity_type;
                if (!teacherStats[teacherName].activityTypes[activityType]) {
                    teacherStats[teacherName].activityTypes[activityType] = 0;
                }
                teacherStats[teacherName].activityTypes[activityType]++;
                
                // 統計合作學校
                teacherStats[teacherName].schools.add(record.school_name);
                
                // 統計近30天活動
                const activityDate = new Date(record.activity_date);
                const thirtyDaysAgo = new Date();
                thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
                if (activityDate >= thirtyDaysAgo) {
                    teacherStats[teacherName].recentActivities++;
                }
            });
            
            // 轉換為陣列並排序
            const teacherStatsArray = Object.values(teacherStats).map(teacher => ({
                ...teacher,
                schoolCount: teacher.schools.size,
                schools: Array.from(teacher.schools)
            })).sort((a, b) => b.totalActivities - a.totalActivities);
            
            // 按科系分組教師
            const departmentGroups = {};
            teacherStatsArray.forEach(teacher => {
                if (!departmentGroups[teacher.department]) {
                    departmentGroups[teacher.department] = [];
                }
                departmentGroups[teacher.department].push(teacher);
            });
            
            // 調試：輸出科系分組信息
            console.log('Department Groups:', departmentGroups);
            console.log('Total departments:', Object.keys(departmentGroups).length);
            Object.entries(departmentGroups).forEach(([dept, teachers]) => {
                console.log(`${dept}: ${teachers.length} teachers`, teachers.map(t => t.name));
            });

            const content = `
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #667eea; margin-bottom: 15px;">
                        <i class="fas fa-users"></i> 教師活動參與統計
                    </h4>
                    
                    <div class="chart-card" style="margin-bottom: 20px;">
                        <div class="chart-title">科系活動分布</div>
                        <div class="chart-container">
                            <canvas id="departmentActivityChart"></canvas>
                        </div>
                    </div>
                    
                    <div id="departmentCharts">
                        ${Object.entries(departmentGroups).map(([department, teachers]) => {
                            const safeDepartmentName = department.replace(/[^a-zA-Z0-9\u4e00-\u9fff]/g, '_');
                            return `
                            <div class="chart-card" style="margin-bottom: 20px;">
                                <div class="chart-title">${department} - 教師活動統計</div>
                                <div class="chart-container">
                                    <canvas id="department_${safeDepartmentName}_Chart"></canvas>
                                </div>
                                ${teachers.length === 0 ? '<div style="text-align: center; padding: 20px; color: #666;">此科系暫無教師活動記錄</div>' : ''}
                            </div>
                        `;
                        }).join('')}
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 20px;">
                        <h5 style="color: #333; margin-bottom: 15px;">教師詳細統計</h5>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                            ${teacherStatsArray.map(teacher => `
                                <div style="background: white; padding: 20px; border-radius: 10px; border-left: 4px solid #667eea;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                        <h5 style="margin: 0; color: #333;">${teacher.name}</h5>
                                        <span style="background: #667eea; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.8em;">
                                            ${teacher.department}
                                        </span>
                                    </div>
                                    
                                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 15px;">
                                        <div style="text-align: center; background: #f8f9fa; padding: 10px; border-radius: 8px;">
                                            <div style="font-size: 1.5em; font-weight: bold; color: #667eea;">${teacher.totalActivities}</div>
                                            <div style="font-size: 0.8em; color: #666;">總活動數</div>
                                        </div>
                                        <div style="text-align: center; background: #f8f9fa; padding: 10px; border-radius: 8px;">
                                            <div style="font-size: 1.5em; font-weight: bold; color: #28a745;">${teacher.schoolCount}</div>
                                            <div style="font-size: 0.8em; color: #666;">合作學校</div>
                                        </div>
                                    </div>
                                    
                                    <div style="margin-bottom: 10px;">
                                        <div style="font-size: 0.9em; color: #666; margin-bottom: 5px;">活動類型分布：</div>
                                        ${Object.entries(teacher.activityTypes).map(([type, count]) => `
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 3px; font-size: 0.85em;">
                                                <span>${type}</span>
                                                <span style="color: #667eea; font-weight: bold;">${count}次</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                    
                                    <div style="font-size: 0.8em; color: #666;">
                                        <i class="fas fa-calendar-week"></i> 近30天活動：${teacher.recentActivities}次
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('analyticsContent').innerHTML = content;
            
                // 創建科系分布圓餅圖（帶數字顯示）
                setTimeout(() => {
                    const departmentStats = {};
                    teacherStatsArray.forEach(teacher => {
                        if (!departmentStats[teacher.department]) {
                            departmentStats[teacher.department] = 0;
                        }
                        departmentStats[teacher.department] += teacher.totalActivities;
                    });
                    
            // 調試：輸出圓餅圖數據
            console.log('Pie Chart Data:', departmentStats);
            console.log('Creating department chart with data:', Object.keys(departmentStats), Object.values(departmentStats));
            
            // 調試：檢查原始活動記錄數據
            console.log('All activity records:', activityRecords);
            console.log('Records with teacher info:', activityRecords.filter(r => r.teacher_name && r.teacher_department));
                    
                    const canvasElement = document.getElementById('departmentActivityChart');
                    if (!canvasElement) {
                        console.error('Canvas element not found: departmentActivityChart');
                        return;
                    }
                    
                    // 清除之前的圖表實例
                    if (window.departmentChart) {
                        window.departmentChart.destroy();
                        window.departmentChart = null;
                    }
                    
                    const ctx1 = canvasElement.getContext('2d');
                    
                    window.departmentChart = new Chart(ctx1, {
                        type: 'doughnut',
                        data: {
                            labels: Object.keys(departmentStats),
                            datasets: [{
                                data: Object.values(departmentStats),
                                backgroundColor: [
                                    '#667eea',
                                    '#28a745',
                                    '#ffc107',
                                    '#dc3545',
                                    '#17a2b8',
                                    '#6f42c1'
                                ],
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: {
                                animateRotate: true,
                                animateScale: true,
                                duration: 1000
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 15,
                                        usePointStyle: true,
                                        font: {
                                            size: 16
                                        }
                                    },
                                    onClick: function(e, legendItem, legend) {
                                        // 阻止點擊事件，不讓圖表區塊變動
                                        return false;
                                    }
                                },
                                tooltip: {
                                    enabled: true,
                                    titleFont: {
                                        size: 16
                                    },
                                    bodyFont: {
                                        size: 16
                                    },
                                    callbacks: {
                                        title: function(context) {
                                            return context[0].label;
                                        },
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.parsed;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = ((value / total) * 100).toFixed(1);
                                            return `${label}: ${value}次 (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                    
                
                // 為每個科系創建教師活動長條圖
                Object.entries(departmentGroups).forEach(([department, teachers]) => {
                    // 生成安全的 Canvas ID
                    const safeDepartmentName = department.replace(/[^a-zA-Z0-9\u4e00-\u9fff]/g, '_');
                    const canvasId = `department_${safeDepartmentName}_Chart`;
                    const canvasElement = document.getElementById(canvasId);
                    
                    // 調試：檢查元素是否存在
                    if (!canvasElement) {
                        console.log(`Canvas element not found: ${canvasId}`);
                        console.log('Available canvas elements:', document.querySelectorAll('canvas'));
                        return;
                    }
                    
                    console.log(`Creating chart for ${department} with ${teachers.length} teachers`);
                    
                    // 檢查是否有教師數據
                    if (teachers.length === 0) {
                        console.log(`No teachers found for ${department}`);
                        return;
                    }
                    
                    // 清除之前的圖表實例（如果存在）
                    const existingChart = Chart.getChart(canvasElement);
                    if (existingChart) {
                        console.log(`Destroying existing chart for ${department}`);
                        existingChart.destroy();
                    }
                    
                    const ctx = canvasElement.getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: teachers.map(teacher => teacher.name),
                            datasets: [{
                                label: '總活動數',
                                data: teachers.map(teacher => teacher.totalActivities),
                                backgroundColor: '#667eea',
                                borderColor: '#5a6fd8',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                },
                                x: {
                                    ticks: {
                                        maxRotation: 0,
                                        minRotation: 0,
                                        font: {
                                            size: 16,
                                            weight: 'normal'
                                        },
                                        color: '#dc3545'
                                    }
                                }
                            }
                        }
                    });
                });
            }, 100);
        }
        
        // 活動類型統計
        function showActivityTypeStats() {
            // 清除相關圖表
            clearSpecificChart('activityTypeChart');
            
            const typeStats = {};
            const monthlyStats = {};
            
            activityRecords.forEach(record => {
                const type = record.activity_type;
                const month = record.activity_date.substring(0, 7); // YYYY-MM
                
                if (!typeStats[type]) {
                    typeStats[type] = 0;
                }
                typeStats[type]++;
                
                if (!monthlyStats[month]) {
                    monthlyStats[month] = {};
                }
                if (!monthlyStats[month][type]) {
                    monthlyStats[month][type] = 0;
                }
                monthlyStats[month][type]++;
            });
            
            const totalActivities = activityRecords.length;
            const typeStatsArray = Object.entries(typeStats).map(([type, count]) => ({
                type,
                count,
                percentage: ((count / totalActivities) * 100).toFixed(1)
            })).sort((a, b) => b.count - a.count);
            
            const content = `
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #667eea; margin-bottom: 15px;">
                        <i class="fas fa-chart-pie"></i> 活動類型分布分析
                    </h4>
                    
                    <div class="chart-card">
                        <div class="chart-title">活動類型圓餅圖</div>
                        <div class="chart-container">
                            <canvas id="activityTypePieChart"></canvas>
                        </div>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 20px;">
                        <h5 style="color: #333; margin-bottom: 15px;">詳細統計數據</h5>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                            ${typeStatsArray.map((item, index) => {
                                const colors = ['#667eea', '#28a745', '#ffc107', '#dc3545', '#17a2b8'];
                                const color = colors[index % colors.length];
                                return `
                                    <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid ${color};">
                                        <div style="font-weight: bold; color: #333; margin-bottom: 5px;">${item.type}</div>
                                        <div style="font-size: 1.5em; font-weight: bold; color: ${color};">${item.count}次</div>
                                        <div style="font-size: 0.9em; color: #666;">${item.percentage}%</div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('analyticsContent').innerHTML = content;
            
            // 創建圓餅圖（帶數字顯示）
            setTimeout(() => {
                const canvasElement = document.getElementById('activityTypePieChart');
                if (!canvasElement) {
                    console.error('Canvas element not found: activityTypePieChart');
                    return;
                }
                
                // 清除之前的圖表實例
                if (window.activityTypeChart) {
                    window.activityTypeChart.destroy();
                }
                
                const ctx1 = canvasElement.getContext('2d');
                window.activityTypeChart = new Chart(ctx1, {
                    type: 'pie',
                    data: {
                        labels: typeStatsArray.map(item => item.type),
                        datasets: [{
                            data: typeStatsArray.map(item => item.count),
                            backgroundColor: [
                                '#667eea',
                                '#28a745', 
                                '#ffc107',
                                '#dc3545',
                                '#17a2b8'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 20,
                                        usePointStyle: true,
                                        font: {
                                            size: 16
                                        }
                                    },
                                    onClick: function(e, legendItem, legend) {
                                        // 阻止點擊事件，不讓圖表區塊變動
                                        return false;
                                    }
                                },
                                tooltip: {
                                    enabled: true,
                                    titleFont: {
                                        size: 16
                                    },
                                    bodyFont: {
                                        size: 16
                                    },
                                    callbacks: {
                                        title: function(context) {
                                            return context[0].label;
                                        },
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.parsed;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = ((value / total) * 100).toFixed(1);
                                            return `${label}: ${value}次 (${percentage}%)`;
                                        }
                                    }
                                }
                        }
                    },
                });
            }, 100);
        }
        
        // 時間分布統計
        function showTimeStats() {
            // 清除相關圖表
            clearSpecificChart('timeTypeChart');
            
            const timeStats = {
                byMonth: {},
                byWeekday: {},
                byTimeType: {}
            };
            
            activityRecords.forEach(record => {
                const date = new Date(record.activity_date);
                const month = record.activity_date.substring(0, 7);
                const weekday = ['週日', '週一', '週二', '週三', '週四', '週五', '週六'][date.getDay()];
                const timeType = record.activity_time;
                
                // 月度統計
                if (!timeStats.byMonth[month]) {
                    timeStats.byMonth[month] = 0;
                }
                timeStats.byMonth[month]++;
                
                // 週日統計
                if (!timeStats.byWeekday[weekday]) {
                    timeStats.byWeekday[weekday] = 0;
                }
                timeStats.byWeekday[weekday]++;
                
                // 時間類型統計
                if (!timeStats.byTimeType[timeType]) {
                    timeStats.byTimeType[timeType] = 0;
                }
                timeStats.byTimeType[timeType]++;
            });
            
            const content = `
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #667eea; margin-bottom: 15px;">
                        <i class="fas fa-calendar-alt"></i> 時間分布分析
                    </h4>
                    
                    <div class="chart-grid">
                        <div class="chart-card">
                            <div class="chart-title">月度活動統計</div>
                            <div class="chart-container">
                                <canvas id="monthlyTrendChart"></canvas>
                            </div>
                        </div>
                        
                        <div class="chart-card">
                            <div class="chart-title">週日分布</div>
                            <div class="chart-container">
                                <canvas id="weekdayChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="chart-card" style="margin-top: 20px;">
                        <div class="chart-title">時間類型分布</div>
                        <div class="chart-container">
                            <canvas id="timeTypeChart"></canvas>
                        </div>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 20px;">
                        <h5 style="color: #333; margin-bottom: 15px;">詳細時間統計</h5>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                            <div>
                                <h6 style="color: #667eea; margin-bottom: 10px;">月度統計</h6>
                                ${Object.entries(timeStats.byMonth).sort().reverse().map(([month, count]) => `
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: white; margin-bottom: 5px; border-radius: 4px;">
                                        <span style="font-weight: bold;">${month}</span>
                                        <span style="color: #667eea; font-weight: bold;">${count}次</span>
                                    </div>
                                `).join('')}
                            </div>
                            
                            <div>
                                <h6 style="color: #667eea; margin-bottom: 10px;">週日分布</h6>
                                ${Object.entries(timeStats.byWeekday).map(([weekday, count]) => `
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: white; margin-bottom: 5px; border-radius: 4px;">
                                        <span>${weekday}</span>
                                        <span style="color: #667eea; font-weight: bold;">${count}次</span>
                                    </div>
                                `).join('')}
                            </div>
                            
                            <div>
                                <h6 style="color: #667eea; margin-bottom: 10px;">時間類型</h6>
                                ${Object.entries(timeStats.byTimeType).map(([timeType, count]) => `
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: white; margin-bottom: 5px; border-radius: 4px;">
                                        <span>${timeType}</span>
                                        <span style="color: #667eea; font-weight: bold;">${count}次</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('analyticsContent').innerHTML = content;
            
            // 創建圖表
            setTimeout(() => {
                // 月度統計長條圖
                const monthlyData = Object.entries(timeStats.byMonth).sort();
                const ctx1 = document.getElementById('monthlyTrendChart').getContext('2d');
                new Chart(ctx1, {
                    type: 'bar',
                    data: {
                        labels: monthlyData.map(([month]) => month),
                        datasets: [{
                            label: '活動數量',
                            data: monthlyData.map(([month, count]) => count),
                            backgroundColor: '#667eea',
                            borderColor: '#5a6fd8',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
                
                // 週日分布長條圖
                const weekdayOrder = ['週一', '週二', '週三', '週四', '週五', '週六', '週日'];
                const weekdayData = weekdayOrder.map(day => timeStats.byWeekday[day] || 0);
                const ctx2 = document.getElementById('weekdayChart').getContext('2d');
                new Chart(ctx2, {
                    type: 'bar',
                    data: {
                        labels: weekdayOrder,
                        datasets: [{
                            label: '活動數量',
                            data: weekdayData,
                            backgroundColor: '#28a745',
                            borderColor: '#218838',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
                
                // 時間類型圓餅圖（帶數字顯示）
                const canvasElement3 = document.getElementById('timeTypeChart');
                if (!canvasElement3) {
                    console.error('Canvas element not found: timeTypeChart');
                    return;
                }
                const ctx3 = canvasElement3.getContext('2d');
                new Chart(ctx3, {
                    type: 'pie',
                    data: {
                        labels: Object.keys(timeStats.byTimeType),
                        datasets: [{
                            data: Object.values(timeStats.byTimeType),
                            backgroundColor: [
                                '#667eea',
                                '#28a745'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 20,
                                        usePointStyle: true,
                                        font: {
                                            size: 16
                                        }
                                    },
                                    onClick: function(e, legendItem, legend) {
                                        // 阻止點擊事件，不讓圖表區塊變動
                                        return false;
                                    }
                                },
                                tooltip: {
                                    enabled: true,
                                    titleFont: {
                                        size: 16
                                    },
                                    bodyFont: {
                                        size: 16
                                    },
                                    callbacks: {
                                        title: function(context) {
                                            return context[0].label;
                                        },
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.parsed;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = ((value / total) * 100).toFixed(1);
                                            return `${label}: ${value}次 (${percentage}%)`;
                                        }
                                    }
                                }
                        }
                    },
                });
            }, 100);
        }
        
        // 合作學校統計
        function showSchoolStats() {
            // 清除相關圖表
            clearSpecificChart('schoolTypeChart');
            
            const schoolStats = {};
            
            activityRecords.forEach(record => {
                const schoolName = record.school_name;
                if (!schoolStats[schoolName]) {
                    schoolStats[schoolName] = {
                        name: schoolName,
                        totalActivities: 0,
                        activityTypes: {},
                        teachers: new Set(),
                        recentActivities: 0
                    };
                }
                
                schoolStats[schoolName].totalActivities++;
                schoolStats[schoolName].teachers.add(record.teacher_name || '未知教師');
                
                const activityType = record.activity_type;
                if (!schoolStats[schoolName].activityTypes[activityType]) {
                    schoolStats[schoolName].activityTypes[activityType] = 0;
                }
                schoolStats[schoolName].activityTypes[activityType]++;
                
                // 統計近30天活動
                const activityDate = new Date(record.activity_date);
                const thirtyDaysAgo = new Date();
                thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
                if (activityDate >= thirtyDaysAgo) {
                    schoolStats[schoolName].recentActivities++;
                }
            });
            
            const schoolStatsArray = Object.values(schoolStats).map(school => ({
                ...school,
                teacherCount: school.teachers.size,
                teachers: Array.from(school.teachers)
            })).sort((a, b) => b.totalActivities - a.totalActivities);
            
            const content = `
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #667eea; margin-bottom: 15px;">
                        <i class="fas fa-school"></i> 合作學校統計分析
                    </h4>
                    
                    <div class="chart-grid">
                        <div class="chart-card">
                            <div class="chart-title">學校活動參與度</div>
                            <div class="chart-container">
                                <canvas id="schoolActivityChart"></canvas>
                            </div>
                        </div>
                        
                        <div class="chart-card">
                            <div class="chart-title">學校合作類型分布</div>
                            <div class="chart-container">
                                <canvas id="schoolTypeChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 20px;">
                        <h5 style="color: #333; margin-bottom: 15px; font-size: 1.3em;">學校詳細統計</h5>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px;">
                            ${schoolStatsArray.map(school => `
                                <div style="background: white; padding: 20px; border-radius: 10px; border: 1px solid #dee2e6;">
                                    <div style="margin-bottom: 15px;">
                                        <h5 style="margin: 0; color: #333; font-size: 1.2em;">${school.name}</h5>
                                    </div>
                                    
                                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 15px;">
                                        <div style="text-align: center; background: #f8f9fa; padding: 10px; border-radius: 8px;">
                                            <div style="font-size: 1.5em; font-weight: bold; color: #28a745;">${school.totalActivities}</div>
                                            <div style="font-size: 0.9em; color: #666;">總活動數</div>
                                        </div>
                                        <div style="text-align: center; background: #f8f9fa; padding: 10px; border-radius: 8px;">
                                            <div style="font-size: 1.5em; font-weight: bold; color: #667eea;">${school.teacherCount}</div>
                                            <div style="font-size: 0.9em; color: #666;">參與教師</div>
                                        </div>
                                    </div>
                                    
                                    <div style="margin-bottom: 15px;">
                                        <div style="font-size: 1em; color: #666; margin-bottom: 8px; font-weight: bold;">活動類型：</div>
                                        ${Object.entries(school.activityTypes).map(([type, count]) => `
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 0.95em;">
                                                <span>${type}</span>
                                                <span style="color: #28a745; font-weight: bold;">${count}次</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                    
                                    <div style="font-size: 0.95em; color: #666; line-height: 1.4;">
                                        <div style="font-weight: bold; margin-bottom: 5px;">參與教師：</div>
                                        <div style="color: #dc3545;">${school.teachers.join('、')}</div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('analyticsContent').innerHTML = content;
            
            // 創建學校活動長條圖
            setTimeout(() => {
                const ctx1 = document.getElementById('schoolActivityChart').getContext('2d');
                new Chart(ctx1, {
                    type: 'bar',
                    data: {
                        labels: schoolStatsArray.map(school => school.name),
                        datasets: [{
                            label: '總活動數',
                            data: schoolStatsArray.map(school => school.totalActivities),
                            backgroundColor: '#28a745',
                            borderColor: '#218838',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            },
                            x: {
                                ticks: {
                                    maxRotation: 0,
                                    minRotation: 0,
                                    font: {
                                        size: 12,
                                        weight: 'normal'
                                    }
                                }
                            }
                        }
                    }
                });
                
                // 創建學校活動類型分布圓餅圖（帶數字顯示）
                const allActivityTypes = {};
                schoolStatsArray.forEach(school => {
                    Object.entries(school.activityTypes).forEach(([type, count]) => {
                        if (!allActivityTypes[type]) {
                            allActivityTypes[type] = 0;
                        }
                        allActivityTypes[type] += count;
                    });
                });
                
                const canvasElement2 = document.getElementById('schoolTypeChart');
                if (!canvasElement2) {
                    console.error('Canvas element not found: schoolTypeChart');
                    return;
                }
                const ctx2 = canvasElement2.getContext('2d');
                new Chart(ctx2, {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(allActivityTypes),
                        datasets: [{
                            data: Object.values(allActivityTypes),
                            backgroundColor: [
                                '#28a745',
                                '#667eea',
                                '#ffc107',
                                '#dc3545'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            animateRotate: true,
                            animateScale: true,
                            duration: 1000,
                            onComplete: function() {
                                // 動畫完成後強制重繪標籤
                                this.chart.update('none');
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    usePointStyle: true
                                }
                            },
                                tooltip: {
                                    enabled: true,
                                    titleFont: {
                                        size: 16
                                    },
                                    bodyFont: {
                                        size: 16
                                    },
                                    callbacks: {
                                        title: function(context) {
                                            return context[0].label;
                                        },
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.parsed;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = ((value / total) * 100).toFixed(1);
                                            return `${label}: ${value}次 (${percentage}%)`;
                                        }
                                    }
                                }
                        }
                    },
                });
            }, 100);
        }
        
        <?php endif; ?>
    </script>

    <?php include("share/footer.php"); ?>
</body>
</html>
