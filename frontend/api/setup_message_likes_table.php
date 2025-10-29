<?php
// 創建 message_likes 表的腳本
require_once 'session_config.php';

// 檢查是否為管理員或有權限的用戶
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']);

if (!$isLoggedIn) {
    die("請先登入");
}

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 創建 message_likes 表
    $sql = "
    CREATE TABLE IF NOT EXISTS message_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_id INT NOT NULL,
        user_email VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_like (message_id, user_email),
        FOREIGN KEY (message_id) REFERENCES senior_messages(id) ON DELETE CASCADE,
        INDEX idx_message_id (message_id),
        INDEX idx_user_email (user_email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql);
    echo "✅ message_likes 表創建成功！<br>";
    
    // 檢查表是否創建成功
    $stmt = $pdo->query("SHOW TABLES LIKE 'message_likes'");
    if ($stmt->rowCount() > 0) {
        echo "✅ 表已存在並可以使用<br>";
    } else {
        echo "❌ 表創建失敗<br>";
    }
    
} catch(PDOException $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "<br>";
}

echo "<br><a href='senior_messages.php'>返回留言板</a>";
?>

