<?php
/**
 * 添加已讀/未讀功能到聊天系統
 */

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>添加已讀/未讀功能</h1>";
    
    // 1. 檢查現有表結構
    echo "<h2>1. 檢查現有表結構</h2>";
    $stmt = $pdo->query("DESCRIBE private_chat_history");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "private_chat_history 表欄位: " . implode(', ', $columns) . "<br>";
    
    // 2. 添加已讀狀態欄位
    echo "<h2>2. 添加已讀狀態欄位</h2>";
    
    if (!in_array('is_read', $columns)) {
        $pdo->exec("ALTER TABLE private_chat_history ADD COLUMN is_read BOOLEAN DEFAULT FALSE AFTER timestamp");
        echo "✅ 添加 is_read 欄位<br>";
    } else {
        echo "ℹ️ is_read 欄位已存在<br>";
    }
    
    if (!in_array('read_at', $columns)) {
        $pdo->exec("ALTER TABLE private_chat_history ADD COLUMN read_at TIMESTAMP NULL AFTER is_read");
        echo "✅ 添加 read_at 欄位<br>";
    } else {
        echo "ℹ️ read_at 欄位已存在<br>";
    }
    
    // 3. 創建已讀記錄表（用於追蹤誰讀了什麼）
    echo "<h2>3. 創建已讀記錄表</h2>";
    $sql = "CREATE TABLE IF NOT EXISTS message_read_status (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_id INT NOT NULL,
        reader_username VARCHAR(255) NOT NULL,
        read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_message_id (message_id),
        INDEX idx_reader (reader_username),
        UNIQUE KEY unique_reader_message (message_id, reader_username),
        FOREIGN KEY (message_id) REFERENCES private_chat_history(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ 創建 message_read_status 表<br>";
    
    // 4. 創建用戶活動表（用於追蹤最後查看時間）
    echo "<h2>4. 創建用戶活動表</h2>";
    $sql = "CREATE TABLE IF NOT EXISTS user_activity (
        username VARCHAR(255) PRIMARY KEY,
        last_chat_check TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_online BOOLEAN DEFAULT FALSE,
        INDEX idx_last_seen (last_seen),
        INDEX idx_is_online (is_online)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ 創建 user_activity 表<br>";
    
    // 5. 檢查結果
    echo "<h2>5. 檢查結果</h2>";
    
    // 檢查 private_chat_history 表
    $stmt = $pdo->query("DESCRIBE private_chat_history");
    $newColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "更新後的 private_chat_history 表欄位: " . implode(', ', $newColumns) . "<br>";
    
    // 檢查新表
    $tables = ['message_read_status', 'user_activity'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ $table 表已創建<br>";
        } else {
            echo "❌ $table 表創建失敗<br>";
        }
    }
    
    // 6. 顯示測試數據
    echo "<h2>6. 測試數據</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM private_chat_history");
    $messageCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "現有訊息數量: $messageCount<br>";
    
    if ($messageCount > 0) {
        $stmt = $pdo->query("SELECT id, from_user, to_user, message, is_read, read_at, timestamp 
                            FROM private_chat_history 
                            ORDER BY timestamp DESC 
                            LIMIT 5");
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>最近5條訊息:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>發送者</th><th>接收者</th><th>訊息</th><th>已讀</th><th>讀取時間</th><th>發送時間</th></tr>";
        foreach ($messages as $message) {
            echo "<tr>";
            echo "<td>{$message['id']}</td>";
            echo "<td>" . htmlspecialchars($message['from_user']) . "</td>";
            echo "<td>" . htmlspecialchars($message['to_user']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($message['message'], 0, 30)) . "...</td>";
            echo "<td>" . ($message['is_read'] ? '✅ 已讀' : '❌ 未讀') . "</td>";
            echo "<td>" . ($message['read_at'] ?: '未讀取') . "</td>";
            echo "<td>{$message['timestamp']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>✅ 已讀/未讀功能設置完成！</h2>";
    echo "<p><a href='chat.php'>前往聊天室測試</a></p>";
    echo "<p><a href='test_read_status.php'>測試已讀功能</a></p>";
    
} catch(PDOException $e) {
    echo "<h1>❌ 錯誤</h1>";
    echo "<p>資料庫錯誤: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>






