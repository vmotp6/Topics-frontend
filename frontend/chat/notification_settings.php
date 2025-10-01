<?php
session_start();

// 檢查用戶是否已登入
if (!isset($_SESSION['username'])) {
    header('Location: ../index.php');
    exit;
}

$username = $_SESSION['username'];

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

// 處理設置更新
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notificationSettings = [
        'enable_notifications' => isset($_POST['enable_notifications']) ? 1 : 0,
        'notification_intervals' => $_POST['notification_intervals'] ?? [],
        'quiet_hours_start' => $_POST['quiet_hours_start'] ?? '22:00',
        'quiet_hours_end' => $_POST['quiet_hours_end'] ?? '08:00',
        'email_frequency' => $_POST['email_frequency'] ?? 'daily'
    ];
    
    try {
        // 更新或插入用戶通知設置
        $sql = "INSERT INTO user_activity (username, notification_preferences) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE 
                notification_preferences = ?";
        
        $stmt = $pdo->prepare($sql);
        $jsonSettings = json_encode($notificationSettings);
        $stmt->execute([$username, $jsonSettings, $jsonSettings]);
        
        $successMessage = "通知設置已更新！";
    } catch(PDOException $e) {
        $errorMessage = "更新設置失敗: " . $e->getMessage();
    }
}

// 獲取當前設置
$currentSettings = [
    'enable_notifications' => true,
    'notification_intervals' => ['1_hour', '6_hours', '24_hours'],
    'quiet_hours_start' => '22:00',
    'quiet_hours_end' => '08:00',
    'email_frequency' => 'daily'
];

try {
    $sql = "SELECT notification_preferences FROM user_activity WHERE username = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['notification_preferences']) {
        $savedSettings = json_decode($result['notification_preferences'], true);
        if ($savedSettings) {
            $currentSettings = array_merge($currentSettings, $savedSettings);
        }
    }
} catch(PDOException $e) {
    // 使用默認設置
}

// 獲取用戶統計信息
try {
    $sql = "SELECT COUNT(*) as unread_count 
            FROM private_chat_history pch 
            LEFT JOIN user_activity ua ON pch.to_user = ua.username 
            WHERE pch.to_user = ? 
                AND pch.timestamp > COALESCE(ua.last_chat_check, '1970-01-01')";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    $unreadCount = $stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
} catch(PDOException $e) {
    $unreadCount = 0;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>通知設置 - 康寧大學聊天系統</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #5865F2 0%, #7289DA 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 16px;
        }
        
        .content {
            padding: 40px;
        }
        
        .stats-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid #5865F2;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #5865F2;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .form-section {
            margin-bottom: 40px;
        }
        
        .form-section h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .checkbox-item:hover {
            border-color: #5865F2;
            background: #f0f8ff;
        }
        
        .checkbox-item input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.2);
        }
        
        .checkbox-item input[type="checkbox"]:checked + label {
            color: #5865F2;
            font-weight: 600;
        }
        
        .time-inputs {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .time-inputs input[type="time"] {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .time-inputs input[type="time"]:focus {
            outline: none;
            border-color: #5865F2;
        }
        
        .select-wrapper {
            position: relative;
        }
        
        .select-wrapper select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            background: white;
            transition: border-color 0.3s ease;
        }
        
        .select-wrapper select:focus {
            outline: none;
            border-color: #5865F2;
        }
        
        .save-button {
            background: linear-gradient(135deg, #5865F2 0%, #7289DA 100%);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
            width: 100%;
        }
        
        .save-button:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .back-button {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: background 0.3s ease;
        }
        
        .back-button:hover {
            background: #5a6268;
        }
        
        .help-text {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔔 通知設置</h1>
            <p>自定義您的聊天通知偏好</p>
        </div>
        
        <div class="content">
            <a href="chat.php" class="back-button">← 返回聊天</a>
            
            <?php if (isset($successMessage)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($errorMessage)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>
            
            <div class="stats-card">
                <h3>📊 您的統計信息</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $unreadCount; ?></div>
                        <div class="stat-label">未讀消息</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $currentSettings['enable_notifications'] ? '開啟' : '關閉'; ?></div>
                        <div class="stat-label">通知狀態</div>
                    </div>
                </div>
            </div>
            
            <form method="POST">
                <div class="form-section">
                    <h3>🔔 基本通知設置</h3>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="enable_notifications" 
                                   <?php echo $currentSettings['enable_notifications'] ? 'checked' : ''; ?>>
                            啟用Gmail通知
                        </label>
                        <div class="help-text">當您長時間未查看聊天系統時，會收到Gmail通知提醒</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>⏰ 通知時間間隔</h3>
                    <div class="form-group">
                        <label>選擇您希望收到通知的時間間隔：</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" name="notification_intervals[]" value="1_hour"
                                       <?php echo in_array('1_hour', $currentSettings['notification_intervals']) ? 'checked' : ''; ?>>
                                <label>1小時</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="notification_intervals[]" value="6_hours"
                                       <?php echo in_array('6_hours', $currentSettings['notification_intervals']) ? 'checked' : ''; ?>>
                                <label>6小時</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="notification_intervals[]" value="24_hours"
                                       <?php echo in_array('24_hours', $currentSettings['notification_intervals']) ? 'checked' : ''; ?>>
                                <label>24小時</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="notification_intervals[]" value="3_days"
                                       <?php echo in_array('3_days', $currentSettings['notification_intervals']) ? 'checked' : ''; ?>>
                                <label>3天</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="notification_intervals[]" value="1_week"
                                       <?php echo in_array('1_week', $currentSettings['notification_intervals']) ? 'checked' : ''; ?>>
                                <label>1週</label>
                            </div>
                        </div>
                        <div class="help-text">選擇多個時間間隔，系統會在這些時間點檢查並發送通知</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>🌙 安靜時間</h3>
                    <div class="form-group">
                        <label>設置安靜時間（在此期間不會發送通知）：</label>
                        <div class="time-inputs">
                            <input type="time" name="quiet_hours_start" 
                                   value="<?php echo htmlspecialchars($currentSettings['quiet_hours_start']); ?>">
                            <span>至</span>
                            <input type="time" name="quiet_hours_end" 
                                   value="<?php echo htmlspecialchars($currentSettings['quiet_hours_end']); ?>">
                        </div>
                        <div class="help-text">例如：22:00 至 08:00 表示晚上10點到早上8點不會發送通知</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>📧 郵件頻率</h3>
                    <div class="form-group">
                        <label>郵件通知頻率：</label>
                        <div class="select-wrapper">
                            <select name="email_frequency">
                                <option value="immediate" <?php echo $currentSettings['email_frequency'] === 'immediate' ? 'selected' : ''; ?>>
                                    立即通知
                                </option>
                                <option value="hourly" <?php echo $currentSettings['email_frequency'] === 'hourly' ? 'selected' : ''; ?>>
                                    每小時
                                </option>
                                <option value="daily" <?php echo $currentSettings['email_frequency'] === 'daily' ? 'selected' : ''; ?>>
                                    每日摘要
                                </option>
                                <option value="weekly" <?php echo $currentSettings['email_frequency'] === 'weekly' ? 'selected' : ''; ?>>
                                    每週摘要
                                </option>
                            </select>
                        </div>
                        <div class="help-text">選擇您希望收到郵件通知的頻率</div>
                    </div>
                </div>
                
                <button type="submit" class="save-button">💾 保存設置</button>
            </form>
        </div>
    </div>
</body>
</html>
