<?php
// 測試資料庫連線
$host = 'localhost';
$dbname = 'topics_good';
$username = 'root';
$password = '';

echo "正在測試資料庫連線...\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ 資料庫連線成功！\n";
    
    // 檢查現有資料表
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "現有資料表：\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    // 檢查ai_chat_history資料表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'ai_chat_history'");
    if ($stmt->rowCount() > 0) {
        echo "✅ ai_chat_history 資料表已存在\n";
    } else {
        echo "❌ ai_chat_history 資料表不存在，正在建立...\n";
        
        // 建立資料表
        $sql = "CREATE TABLE IF NOT EXISTS ai_chat_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(255) NOT NULL,
            message_type ENUM('user', 'ai') NOT NULL,
            message_content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "✅ ai_chat_history 資料表建立成功！\n";
    }
    
} catch(PDOException $e) {
    echo "❌ 資料庫連線失敗: " . $e->getMessage() . "\n";
}
?> 