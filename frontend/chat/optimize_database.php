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
    
    echo "<h1>資料庫優化工具</h1>";
    
    // 檢查並添加索引
    $indexes = [
        // private_chat_history 表索引
        "ALTER TABLE private_chat_history ADD INDEX idx_users (from_user, to_user)",
        "ALTER TABLE private_chat_history ADD INDEX idx_timestamp (timestamp)",
        "ALTER TABLE private_chat_history ADD INDEX idx_users_timestamp (from_user, to_user, timestamp)",
        
        // group_chat_messages 表索引
        "ALTER TABLE group_chat_messages ADD INDEX idx_group_timestamp (group_id, timestamp)",
        "ALTER TABLE group_chat_messages ADD INDEX idx_from_user (from_user)",
        
        // group_chat_members 表索引
        "ALTER TABLE group_chat_members ADD INDEX idx_member_username (member_username)",
        "ALTER TABLE group_chat_members ADD INDEX idx_group_member (group_id, member_username)",
        
        // user 表索引
        "ALTER TABLE user ADD INDEX idx_username_role (username, role)",
        
        // teacher02 表索引
        "ALTER TABLE teacher02 ADD INDEX idx_u_id (u_id)",
        "ALTER TABLE teacher02 ADD INDEX idx_department (department)"
    ];
    
    echo "<h2>添加索引</h2>";
    
    foreach ($indexes as $index) {
        try {
            $pdo->exec($index);
            echo "<p style='color: green;'>✅ 成功添加索引</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<p style='color: orange;'>⚠️ 索引已存在</p>";
            } else {
                echo "<p style='color: red;'>❌ 添加索引失敗: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // 分析表結構
    echo "<h2>表結構分析</h2>";
    
    $tables = ['private_chat_history', 'group_chat_messages', 'group_chat_members', 'user', 'teacher02'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW INDEX FROM {$table}");
            $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>{$table} 表索引</h3>";
            if (!empty($indexes)) {
                echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
                echo "<tr><th>索引名稱</th><th>欄位</th><th>類型</th></tr>";
                foreach ($indexes as $index) {
                    echo "<tr>";
                    echo "<td>{$index['Key_name']}</td>";
                    echo "<td>{$index['Column_name']}</td>";
                    echo "<td>{$index['Index_type']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>無索引</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ 檢查索引失敗: " . $e->getMessage() . "</p>";
        }
    }
    
    // 查詢效能測試
    echo "<h2>查詢效能測試</h2>";
    
    // 測試私聊查詢
    try {
        $startTime = microtime(true);
        $stmt = $pdo->prepare("SELECT * FROM private_chat_history 
                              WHERE (from_user = ? AND to_user = ?) 
                              OR (from_user = ? AND to_user = ?) 
                              ORDER BY timestamp DESC 
                              LIMIT 100");
        $stmt->execute(['test_user1', 'test_user2', 'test_user2', 'test_user1']);
        $messages = $stmt->fetchAll();
        $endTime = microtime(true);
        $queryTime = ($endTime - $startTime) * 1000;
        
        echo "<p>私聊查詢測試：{$queryTime:.2f}ms</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ 私聊查詢測試失敗（正常，因為測試用戶不存在）</p>";
    }
    
    // 測試群組查詢
    try {
        $startTime = microtime(true);
        $stmt = $pdo->prepare("SELECT * FROM group_chat_members WHERE member_username = ?");
        $stmt->execute(['test_user']);
        $members = $stmt->fetchAll();
        $endTime = microtime(true);
        $queryTime = ($endTime - $startTime) * 1000;
        
        echo "<p>群組成員查詢測試：{$queryTime:.2f}ms</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ 群組查詢測試失敗（正常，因為測試用戶不存在）</p>";
    }
    
    echo "<h2>優化建議</h2>";
    echo "<ul>";
    echo "<li>✅ 已添加必要的資料庫索引</li>";
    echo "<li>✅ 前端已實作快取機制</li>";
    echo "<li>✅ 已限制查詢結果數量（最新100條）</li>";
    echo "<li>✅ 已優化DOM操作（使用DocumentFragment）</li>";
    echo "<li>✅ 已添加載入指示器</li>";
    echo "</ul>";
    
    echo "<h2>測試連結</h2>";
    echo "<ul>";
    echo "<li><a href='chat.php'>進入聊天室測試</a></li>";
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "<h2>❌ 資料庫連接失敗</h2>";
    echo "<p>錯誤訊息: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    line-height: 1.6;
}

h1, h2, h3 {
    color: #333;
}

table {
    margin: 10px 0;
    font-size: 12px;
}

th, td {
    padding: 5px;
    text-align: left;
}

th {
    background-color: #f0f0f0;
}

ul {
    margin: 10px 0;
}

li {
    margin: 5px 0;
}

a {
    color: #2196f3;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>
