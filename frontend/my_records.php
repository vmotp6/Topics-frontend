<?php
// 載入 session 配置
require_once 'session_config.php';

// 引入配置檔案
require_once 'config.php';

// 檢查登入狀態（AA權限與TEA一致）
$user_role = $_SESSION['role'] ?? '';
$is_teacher = ($user_role === '老師' || $user_role === 'TEA' || $user_role === 'STA');
if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || !$is_teacher) {
    header("Location: login.php");
    exit();
}

// 建立資料庫連接
$conn = getDatabaseConnection();

// 獲取登入教師的資訊
$teacher_id = $_SESSION['id'];
$teacher_info = null;
$records = [];

// 從 teacher 表獲取教師詳細資訊
$teacher_sql = "SELECT * FROM teacher WHERE user_id = ?";
$teacher_stmt = $conn->prepare($teacher_sql);
$teacher_stmt->bind_param("i", $teacher_id);
$teacher_stmt->execute();
$teacher_result = $teacher_stmt->get_result();

if ($teacher_result->num_rows > 0) {
    $teacher_info = $teacher_result->fetch_assoc();
}
$teacher_stmt->close();

// 查詢該教師的所有活動紀錄（通過 JOIN 獲取 teacher 名稱）
$records_sql = "SELECT ar.*, t.name AS teacher_name_display, t.department AS teacher_department_display
                FROM activity_records ar
                LEFT JOIN teacher t ON ar.teacher_id = t.user_id
                WHERE ar.teacher_id = ? 
                ORDER BY ar.created_at DESC";
$records_stmt = $conn->prepare($records_sql);
$records_stmt->bind_param("i", $teacher_id);
$records_stmt->execute();
$records_result = $records_stmt->get_result();

if ($records_result->num_rows > 0) {
    while ($row = $records_result->fetch_assoc()) {
        // teacher_name 字段存儲的是代碼，teacher_name_display 是從 teacher 表 JOIN 來的名稱
        $records[] = $row;
    }
}
$records_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的活動紀錄</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/csp/records.css">
    <style>
        .stats-container {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .action-buttons {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .action-btn {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 25px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 0 10px;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
            text-decoration: none;
            color: white;
        }
        
        .records-grid {
            display: grid;
            gap: 20px;
        }
        
        .record-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border-left: 4px solid #6c7aed;
        }
        
        .record-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .record-date {
            font-size: 1.2em;
            font-weight: 600;
            color: #6c7aed;
        }
        
        .record-school {
            color: #6c757d;
            font-style: italic;
        }
        
        .record-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .detail-item {
            background: #f8f9fa;
            padding: 12px 15px;
            border-radius: 8px;
        }
        
        .detail-label {
            font-weight: 600;
            color: #495057;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        
        .detail-value {
            color: #6c757d;
            line-height: 1.4;
        }
        
        .no-records {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .no-records i {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .record-details {
                grid-template-columns: 1fr;
            }
            
            .action-btn {
                width: 100%;
                margin: 5px 0;
                justify-content: center;
            }
        }
    </style>
</head>

<body class="page-body">
    <?php include("share/header.php"); ?>
    
    <div class="main-content">
        <div class="records-form-container">
            <div class="form-header">
                <h1><i class="fas fa-clipboard-list"></i> 我的活動紀錄</h1>
                <p>查看您填寫的所有活動紀錄</p>
            </div>
            
            <div class="form-content">
                <!-- 教師資訊和統計 -->
                <?php if ($teacher_info): ?>
                    <div class="stats-container">
                        <h3><i class="fas fa-user"></i> 歡迎回來，<?php echo htmlspecialchars($teacher_info['teacher_name'] ?? $teacher_info['name'] ?? '老師'); ?>！</h3>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo count($records); ?></span>
                                <span class="stat-label">填報次數</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo htmlspecialchars($teacher_info['user_id']); ?></span>
                                <span class="stat-label">教師ID</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo htmlspecialchars($teacher_info['department'] ?? $teacher_info['teacher_unit'] ?? '未設定'); ?></span>
                                <span class="stat-label">所屬單位</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">
                                    <?php 
                                    $this_month = date('Y-m');
                                    $this_month_count = 0;
                                    foreach ($records as $record) {
                                        if (isset($record['created_at']) && strpos($record['created_at'], $this_month) === 0) {
                                            $this_month_count++;
                                        }
                                    }
                                    echo $this_month_count;
                                    ?>
                                </span>
                                <span class="stat-label">本月填報</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- 操作按鈕 -->
                <div class="action-buttons">
                    <a href="records.php" class="action-btn">
                        <i class="fas fa-plus"></i> 新增活動紀錄
                    </a>
                </div>
                
                <!-- 活動紀錄列表 -->
                <?php if (!empty($records)): ?>
                    <div class="records-grid">
                        <?php foreach ($records as $record): ?>
                            <div class="record-card">
                                <div class="record-header">
                                    <div class="record-date">
                                        <i class="fas fa-calendar"></i> 
                                        <?php echo date('Y年n月j日', strtotime($record['activity_date'])); ?>
                                    </div>
                                    <div class="record-school">
                                        <i class="fas fa-school"></i> 
                                        <?php echo htmlspecialchars($record['school_name']); ?>
                                    </div>
                                </div>
                                
                                <div class="record-details">
                                    <div class="detail-item">
                                        <div class="detail-label">活動性質</div>
                                        <div class="detail-value"><?php echo htmlspecialchars(convertActivityTypeCodeToName($record['activity_type'], $conn)); ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">活動時間</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($record['activity_time']); ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">聯絡窗口</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($record['contact_person'] ?: '無'); ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">聯絡電話</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($record['contact_phone'] ?: '無'); ?></div>
                                    </div>
                                    <?php if ($record['participants']): ?>
                                        <div class="detail-item">
                                            <div class="detail-label">參與對象</div>
                                            <div class="detail-value"><?php echo convertParticipantCodesToNames($record['participants']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($record['activity_feedback']): ?>
                                        <div class="detail-item">
                                            <div class="detail-label">活動紀錄</div>
                                            <div class="detail-value"><?php echo htmlspecialchars($record['activity_feedback']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($record['suggestion']): ?>
                                        <div class="detail-item" style="grid-column: 1 / -1;">
                                            <div class="detail-label">建議事項</div>
                                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($record['suggestion'])); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="detail-item">
                                        <div class="detail-label">填報時間</div>
                                        <div class="detail-value">
                                            <?php 
                                            echo isset($record['created_at']) 
                                                ? date('Y年n月j日 H:i', strtotime($record['created_at']))
                                                : '未知';
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-records">
                        <i class="fas fa-inbox"></i>
                        <h3>尚無活動紀錄</h3>
                        <p>您還沒有填寫任何活動紀錄，點擊上方按鈕開始填寫吧！</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include("share/footer.php"); ?>
    
    <!-- 浮動助手組件 -->
    <?php include("share/chat_widget.php"); ?>
</body>
</html>
