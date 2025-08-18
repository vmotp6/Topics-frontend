<?php
header('Content-Type: text/html; charset=utf-8');

// 資料庫連接
$host = '100.79.58.120';  // 使用本機資料庫
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>資料庫測試</h2>";
    
    // 檢查聊天相關的資料表
    echo "<h3>1. 檢查聊天相關資料表</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE '%chat%'");
    $chatTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($chatTables)) {
        echo "<p style='color: red;'>❌ 沒有找到聊天相關的資料表！</p>";
        echo "<p>需要建立以下資料表：</p>";
        echo "<ul>";
        echo "<li>private_chat_history</li>";
        echo "<li>group_chats</li>";
        echo "<li>group_chat_members</li>";
        echo "<li>group_chat_messages</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: green;'>✅ 找到以下聊天資料表：</p>";
        echo "<ul>";
        foreach ($chatTables as $table) {
            echo "<li>{$table}</li>";
        }
        echo "</ul>";
    }
    
    // 檢查 private_chat_history 表結構
    echo "<h3>2. 檢查 private_chat_history 表結構</h3>";
    try {
        $stmt = $pdo->query("DESCRIBE private_chat_history");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>欄位名</th><th>類型</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ private_chat_history 表不存在或無法訪問：{$e->getMessage()}</p>";
    }
    
    // 檢查是否有聊天記錄
    echo "<h3>3. 檢查聊天記錄</h3>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM private_chat_history");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>總共有 {$result['count']} 條私聊記錄</p>";
        
        if ($result['count'] > 0) {
            $stmt = $pdo->query("SELECT * FROM private_chat_history ORDER BY timestamp DESC LIMIT 5");
            $recentMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h4>最近的聊天記錄：</h4>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>發送者</th><th>接收者</th><th>訊息</th><th>角色</th><th>時間</th></tr>";
            foreach ($recentMessages as $message) {
                echo "<tr>";
                echo "<td>{$message['id']}</td>";
                echo "<td>{$message['from_user']}</td>";
                echo "<td>{$message['to_user']}</td>";
                echo "<td>{$message['message']}</td>";
                echo "<td>{$message['role']}</td>";
                echo "<td>{$message['timestamp']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ 無法檢查聊天記錄：{$e->getMessage()}</p>";
    }
    
    // 檢查用戶資料
    echo "<h3>4. 檢查用戶資料</h3>";
    try {
        $stmt = $pdo->query("SELECT username, role FROM user WHERE role IN ('老師', '廠商') LIMIT 10");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>用戶列表：</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>用戶名</th><th>角色</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ 無法檢查用戶資料：{$e->getMessage()}</p>";
    }
    
} catch(PDOException $e) {
    echo "<h3>❌ 資料庫連接失敗</h3>";
    echo "<p>錯誤訊息: " . $e->getMessage() . "</p>";
}
?>

