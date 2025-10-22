<?php
/**
 * 測試已讀/未讀功能
 */

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>已讀/未讀功能測試</h1>";
    
    // 1. 檢查表結構
    echo "<h2>1. 檢查表結構</h2>";
    
    $tables = ['private_chat_history', 'message_read_status', 'user_activity'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ $table 表存在<br>";
            
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
            echo "❌ $table 表不存在<br>";
        }
    }
    
    // 2. 檢查訊息數據
    echo "<h2>2. 檢查訊息數據</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM private_chat_history");
    $messageCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "總訊息數量: $messageCount<br>";
    
    if ($messageCount > 0) {
        // 顯示最近10條訊息
        $stmt = $pdo->query("SELECT id, from_user, to_user, message, is_read, read_at, timestamp 
                            FROM private_chat_history 
                            ORDER BY timestamp DESC 
                            LIMIT 10");
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>最近10條訊息:</h3>";
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
        
        // 統計已讀/未讀
        $stmt = $pdo->query("SELECT 
                            SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_count,
                            SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count
                            FROM private_chat_history");
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>已讀訊息: {$stats['read_count']} | 未讀訊息: {$stats['unread_count']}</p>";
    }
    
    // 3. 檢查已讀記錄
    echo "<h2>3. 檢查已讀記錄</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM message_read_status");
    $readStatusCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "已讀記錄數量: $readStatusCount<br>";
    
    if ($readStatusCount > 0) {
        $stmt = $pdo->query("SELECT mrs.*, pch.from_user, pch.to_user, pch.message 
                            FROM message_read_status mrs 
                            JOIN private_chat_history pch ON mrs.message_id = pch.id 
                            ORDER BY mrs.read_at DESC 
                            LIMIT 5");
        $readRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>最近5條已讀記錄:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>訊息ID</th><th>讀取者</th><th>發送者</th><th>接收者</th><th>訊息</th><th>讀取時間</th></tr>";
        foreach ($readRecords as $record) {
            echo "<tr>";
            echo "<td>{$record['message_id']}</td>";
            echo "<td>" . htmlspecialchars($record['reader_username']) . "</td>";
            echo "<td>" . htmlspecialchars($record['from_user']) . "</td>";
            echo "<td>" . htmlspecialchars($record['to_user']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($record['message'], 0, 20)) . "...</td>";
            echo "<td>{$record['read_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. 檢查用戶活動
    echo "<h2>4. 檢查用戶活動</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_activity");
    $activityCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "用戶活動記錄數量: $activityCount<br>";
    
    if ($activityCount > 0) {
        $stmt = $pdo->query("SELECT username, last_chat_check, last_seen, is_online 
                            FROM user_activity 
                            ORDER BY last_seen DESC 
                            LIMIT 10");
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>最近10個用戶活動:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>用戶名</th><th>最後查看聊天</th><th>最後上線</th><th>在線狀態</th></tr>";
        foreach ($activities as $activity) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($activity['username']) . "</td>";
            echo "<td>" . ($activity['last_chat_check'] ?: '從未查看') . "</td>";
            echo "<td>{$activity['last_seen']}</td>";
            echo "<td>" . ($activity['is_online'] ? '🟢 在線' : '🔴 離線') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 5. 測試API功能
    echo "<h2>5. 測試API功能</h2>";
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
    echo "<h3>API測試</h3>";
    echo "<p><a href='update_read_status.php?action=get_unread_count&username=test' target='_blank'>測試獲取未讀數量</a></p>";
    echo "<p><a href='update_read_status.php?action=get_read_status&username=test&other_user=test2' target='_blank'>測試獲取已讀狀態</a></p>";
    echo "</div>";
    
    // 6. 模擬測試
    echo "<h2>6. 模擬測試</h2>";
    
    if (isset($_GET['test_mark_read'])) {
        $messageId = $_GET['test_mark_read'];
        $reader = $_GET['reader'] ?? 'test_user';
        
        echo "<h3>模擬標記訊息為已讀</h3>";
        echo "<p>訊息ID: $messageId</p>";
        echo "<p>讀取者: $reader</p>";
        
        try {
            // 更新 private_chat_history 表
            $stmt = $pdo->prepare("UPDATE private_chat_history SET is_read = TRUE, read_at = NOW() WHERE id = ?");
            $stmt->execute([$messageId]);
            
            // 插入到 message_read_status 表
            $stmt = $pdo->prepare("INSERT IGNORE INTO message_read_status (message_id, reader_username) VALUES (?, ?)");
            $stmt->execute([$messageId, $reader]);
            
            echo "<p style='color: green;'>✅ 成功標記訊息為已讀</p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ 標記失敗: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // 提供測試連結
    if ($messageCount > 0) {
        echo "<h3>快速測試</h3>";
        $stmt = $pdo->query("SELECT id FROM private_chat_history WHERE is_read = FALSE LIMIT 1");
        $unreadMessage = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($unreadMessage) {
            echo "<p><a href='?test_mark_read={$unreadMessage['id']}&reader=test_user'>標記第一條未讀訊息為已讀</a></p>";
        } else {
            echo "<p>沒有未讀訊息可以測試</p>";
        }
    }
    
    echo "<h2>✅ 測試完成</h2>";
    echo "<p><a href='chat.php'>前往聊天室</a></p>";
    
} catch(PDOException $e) {
    echo "<h1>❌ 錯誤</h1>";
    echo "<p>資料庫錯誤: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>


















