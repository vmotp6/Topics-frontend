<?php
// 資料庫字符集修復腳本
header('Content-Type: text/html; charset=utf-8');

// 資料庫連接
$host = '100.79.58.120';  // 使用本機資料庫
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>資料庫字符集修復工具</h2>";
    
    // 修復 user 資料表
    echo "<h3>修復 user 資料表...</h3>";
    $sql = "ALTER TABLE user CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "✅ user 資料表修復完成<br>";
    
    // 修復 teacher02 資料表
    echo "<h3>修復 teacher02 資料表...</h3>";
    $sql = "ALTER TABLE teacher02 CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "✅ teacher02 資料表修復完成<br>";
    
    // 修復 private_chat_history 資料表
    echo "<h3>修復 private_chat_history 資料表...</h3>";
    $sql = "ALTER TABLE private_chat_history CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "✅ private_chat_history 資料表修復完成<br>";
    
    // 檢查並修復群聊相關資料表
    $stmt = $pdo->query("SHOW TABLES LIKE 'group_chats'");
    if ($stmt->rowCount() > 0) {
        echo "<h3>修復 group_chats 資料表...</h3>";
        $sql = "ALTER TABLE group_chats CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        $pdo->exec($sql);
        echo "✅ group_chats 資料表修復完成<br>";
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'group_chat_members'");
    if ($stmt->rowCount() > 0) {
        echo "<h3>修復 group_chat_members 資料表...</h3>";
        $sql = "ALTER TABLE group_chat_members CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        $pdo->exec($sql);
        echo "✅ group_chat_members 資料表修復完成<br>";
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'group_chat_messages'");
    if ($stmt->rowCount() > 0) {
        echo "<h3>修復 group_chat_messages 資料表...</h3>";
        $sql = "ALTER TABLE group_chat_messages CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        $pdo->exec($sql);
        echo "✅ group_chat_messages 資料表修復完成<br>";
    }
    
    // 檢查其他可能的聊天相關資料表
    $stmt = $pdo->query("SHOW TABLES LIKE '%chat%'");
    $chatTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($chatTables as $table) {
        if (!in_array($table, ['group_chats', 'group_chat_members', 'group_chat_messages', 'private_chat_history'])) {
            echo "<h3>修復 {$table} 資料表...</h3>";
            $sql = "ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            $pdo->exec($sql);
            echo "✅ {$table} 資料表修復完成<br>";
        }
    }
    
    echo "<h3>✅ 所有資料表字符集修復完成！</h3>";
    echo "<p>現在可以正常使用聊天功能了。</p>";
    
} catch(PDOException $e) {
    echo "<h3>❌ 修復失敗</h3>";
    echo "<p>錯誤訊息: " . $e->getMessage() . "</p>";
}
?>

