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

    </script>

    <?php include("share/footer.php"); ?>
    
    <!-- 浮動助手組件 -->
    <?php include("share/chat_widget.php"); ?>
    <?php include("share/ai_widget.php"); ?>
</body>
</html>
