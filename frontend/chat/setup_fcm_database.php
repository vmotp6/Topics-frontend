<?php
/**
 * 設置Firebase Cloud Messaging資料庫
 */

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>設置Firebase Cloud Messaging資料庫</h1>";
    
    // 1. 創建用戶FCM tokens表
    echo "<h2>1. 創建用戶FCM tokens表</h2>";
    $sql = "CREATE TABLE IF NOT EXISTS user_fcm_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        fcm_token VARCHAR(500) NOT NULL,
        device_type VARCHAR(50) DEFAULT 'web',
        device_info TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_fcm_token (fcm_token),
        INDEX idx_is_active (is_active),
        UNIQUE KEY unique_user_token (username, fcm_token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ 創建 user_fcm_tokens 表成功<br>";
    
    // 2. 創建推播通知記錄表
    echo "<h2>2. 創建推播通知記錄表</h2>";
    $sql = "CREATE TABLE IF NOT EXISTS push_notification_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        fcm_token VARCHAR(500) NOT NULL,
        title VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        data JSON,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
        response_data JSON,
        INDEX idx_username (username),
        INDEX idx_sent_at (sent_at),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ 創建 push_notification_log 表成功<br>";
    
    // 3. 創建通知設定表
    echo "<h2>3. 創建通知設定表</h2>";
    $sql = "CREATE TABLE IF NOT EXISTS user_notification_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        chat_notifications BOOLEAN DEFAULT TRUE,
        group_notifications BOOLEAN DEFAULT TRUE,
        system_notifications BOOLEAN DEFAULT TRUE,
        quiet_hours_start TIME DEFAULT '22:00:00',
        quiet_hours_end TIME DEFAULT '08:00:00',
        timezone VARCHAR(50) DEFAULT 'Asia/Taipei',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_settings (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ 創建 user_notification_settings 表成功<br>";
    
    // 4. 檢查表結構
    echo "<h2>4. 檢查表結構</h2>";
    $tables = ['user_fcm_tokens', 'push_notification_log', 'user_notification_settings'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ $table 表已創建<br>";
            
            // 顯示表結構
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
            echo "<tr><th>欄位</th><th>類型</th><th>允許NULL</th><th>鍵</th><th>預設值</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "<td>{$column['Default']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "❌ $table 表創建失敗<br>";
        }
    }
    
    // 5. 插入測試數據
    echo "<h2>5. 插入測試數據</h2>";
    
    // 為現有用戶創建通知設定
    $stmt = $pdo->query("SELECT username FROM user WHERE role IN ('學生', '老師')");
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($users as $username) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO user_notification_settings (username) VALUES (?)");
        $stmt->execute([$username]);
    }
    
    echo "✅ 為 " . count($users) . " 個用戶創建通知設定<br>";
    
    // 6. 顯示統計
    echo "<h2>6. 統計信息</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_fcm_tokens");
    $tokenCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "FCM tokens數量: $tokenCount<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM push_notification_log");
    $logCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "推播記錄數量: $logCount<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_notification_settings");
    $settingsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "通知設定數量: $settingsCount<br>";
    
    echo "<h2>✅ FCM資料庫設置完成！</h2>";
    echo "<p><a href='fcm_service.php'>前往FCM服務</a></p>";
    echo "<p><a href='test_fcm.php'>測試FCM功能</a></p>";
    echo "<p><a href='chat.php'>返回聊天室</a></p>";
    
} catch(PDOException $e) {
    echo "<h1>❌ 錯誤</h1>";
    echo "<p>資料庫錯誤: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>























