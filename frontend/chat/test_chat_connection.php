<?php
// 測試聊天系統的數據庫連接和功能

echo "<h2>聊天系統測試</h2>";

// 資料庫連接測試
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✓ 數據庫連接成功</p>";
    
    // 檢查必要的表是否存在
    $tables = ['private_chat_history', 'chat_groups', 'group_members', 'group_messages', 'user', 'teacher'];
    $missingTables = [];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() == 0) {
                $missingTables[] = $table;
            } else {
                echo "<p style='color: green;'>✓ 表 '$table' 存在</p>";
            }
        } catch (Exception $e) {
            $missingTables[] = $table;
        }
    }
    
    if (!empty($missingTables)) {
        echo "<p style='color: red;'>✗ 缺少以下表: " . implode(', ', $missingTables) . "</p>";
        echo "<p>請執行 create_chat_tables.sql 來創建必要的表</p>";
    }
    
    // 測試用戶表數據
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM user");
        $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>用戶表中有 $userCount 個用戶</p>";
        
        // 顯示一些用戶示例
        $stmt = $pdo->query("SELECT username, role FROM user LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>用戶示例:</p><ul>";
        foreach ($users as $user) {
            echo "<li>{$user['username']} ({$user['role']})</li>";
        }
        echo "</ul>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ 無法讀取用戶表: " . $e->getMessage() . "</p>";
    }
    
    // 測試教師表數據
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM teacher");
        $teacherCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>教師表中有 $teacherCount 個教師</p>";
        
        // 顯示一些教師示例
        $stmt = $pdo->query("SELECT t.name, t.department, u.username FROM teacher t JOIN user u ON t.user_id = u.id LIMIT 5");
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>教師示例:</p><ul>";
        foreach ($teachers as $teacher) {
            echo "<li>{$teacher['name']} ({$teacher['department']}) - {$teacher['username']}</li>";
        }
        echo "</ul>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ 無法讀取教師表: " . $e->getMessage() . "</p>";
    }
    
    // 測試私聊訊息表
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM private_chat_history");
        $messageCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>私聊訊息表中有 $messageCount 條訊息</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ 無法讀取私聊訊息表: " . $e->getMessage() . "</p>";
    }
    
    // 測試群組表
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM chat_groups");
        $groupCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>群組表中有 $groupCount 個群組</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ 無法讀取群組表: " . $e->getMessage() . "</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>✗ 數據庫連接失敗: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>測試建議</h3>";
echo "<ol>";
echo "<li>確保數據庫服務器 100.79.58.120 可以正常連接</li>";
echo "<li>確保數據庫 topics_good 存在</li>";
echo "<li>確保用戶 root 有適當的權限</li>";
echo "<li>執行 create_chat_tables.sql 創建必要的表</li>";
        echo "<li>確保 user 和 teacher 表中有數據</li>";
echo "</ol>";

echo "<p><a href='chat.php'>前往聊天室</a></p>";
?>
