<?php
// 資料庫連線設定
$host = 'localhost';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 創建AI聊天記錄資料表
    $sql = "CREATE TABLE IF NOT EXISTS ai_chat_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message_type ENUM('user', 'ai') NOT NULL,
        message_content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "AI聊天記錄資料表建立成功！\n";
    
    // 檢查資料表是否建立成功
    $stmt = $pdo->query("SHOW TABLES LIKE 'ai_chat_history'");
    if ($stmt->rowCount() > 0) {
        echo "資料表 'ai_chat_history' 已存在並可以使用。\n";
    } else {
        echo "警告：資料表建立可能失敗。\n";
    }
    
} catch(PDOException $e) {
    echo "錯誤: " . $e->getMessage() . "\n";
}
?> 