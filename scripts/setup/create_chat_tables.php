<?php
/**
 * 創建聊天表
 */

$host = '100.79.58.120';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "📋 檢查聊天表...\n";
    
    // 檢查 private_chat_history 表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'private_chat_history'");
    if ($stmt->rowCount() == 0) {
        echo "❌ private_chat_history 表不存在，正在創建...\n";
        
        // 讀取並執行 SQL 文件
        $sql = file_get_contents('frontend/chat/create_chat_tables.sql');
        $statements = explode(';', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        echo "✅ 聊天表創建成功！\n";
    } else {
        echo "✅ private_chat_history 表已存在\n";
    }
    
    // 檢查其他聊天表
    $tables = ['chat_groups', 'group_members', 'group_messages'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ $table 表已存在\n";
        } else {
            echo "❌ $table 表不存在\n";
        }
    }
    
    echo "\n🎉 聊天表檢查完成！\n";
    
} catch (Exception $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
}
?>











