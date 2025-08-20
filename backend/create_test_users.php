<?php
// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "📋 建立測試用戶...\n";
    
    // 檢查user表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'user'");
    if ($stmt->rowCount() == 0) {
        echo "❌ user表不存在，正在建立...\n";
        
        // 建立user表
        $sql = "CREATE TABLE user (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('老師', '學校行政人員', '學生') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "✅ user表建立成功\n";
    }
    
    // 建立測試用戶
    $test_users = [
        ['username' => 'teacher1', 'password' => '123456', 'role' => '老師'],
        ['username' => 'teacher2', 'password' => '123456', 'role' => '老師'],
        ['username' => 'admin1', 'password' => '123456', 'role' => '學校行政人員'],
        ['username' => 'admin2', 'password' => '123456', 'role' => '學校行政人員']
    ];
    
    foreach ($test_users as $user) {
        // 檢查用戶是否已存在
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$user['username']]);
        
        if ($stmt->rowCount() == 0) {
            // 建立新用戶
            $stmt = $pdo->prepare("INSERT INTO user (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$user['username'], $user['password'], $user['role']]);
            echo "✅ 建立用戶: {$user['username']} ({$user['role']})\n";
        } else {
            echo "⚠️  用戶已存在: {$user['username']}\n";
        }
    }
    
    echo "\n🎉 測試用戶建立完成！\n";
    echo "\n📋 測試帳號：\n";
    echo "老師帳號：\n";
    echo "- 帳號：teacher1，密碼：123456\n";
    echo "- 帳號：teacher2，密碼：123456\n";
    echo "\n行政人員帳號：\n";
    echo "- 帳號：admin1，密碼：123456\n";
    echo "- 帳號：admin2，密碼：123456\n";
    
} catch(PDOException $e) {
    echo "❌ 資料庫錯誤: " . $e->getMessage() . "\n";
} catch(Exception $e) {
    echo "❌ 系統錯誤: " . $e->getMessage() . "\n";
}
?>
