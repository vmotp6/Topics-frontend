<?php
// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 測試登入功能...\n\n";
    
    // 測試帳號
    $test_username = 'teacher1';
    $test_password = '123456';
    
    echo "測試帳號：$test_username\n";
    echo "測試密碼：$test_password\n\n";
    
    // 查詢用戶
    $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ?");
    $stmt->execute([$test_username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ 找到用戶：\n";
        echo "- 用戶名：{$user['username']}\n";
        echo "- 角色：{$user['role']}\n";
        echo "- 密碼：{$user['password']}\n";
        echo "- 狀態：{$user['status']}\n";
        
        // 檢查密碼是否匹配
        if ($user['password'] === $test_password) {
            echo "\n✅ 密碼匹配成功！\n";
        } else {
            echo "\n❌ 密碼不匹配！\n";
            echo "資料庫中的密碼：{$user['password']}\n";
            echo "輸入的密碼：$test_password\n";
        }
    } else {
        echo "❌ 找不到用戶：$test_username\n";
    }
    
    echo "\n📋 所有用戶列表：\n";
    $stmt = $pdo->query("SELECT username, password, role, status FROM user ORDER BY role, username");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        echo "- {$user['username']} (密碼: {$user['password']}, 角色: {$user['role']}, 狀態: {$user['status']})\n";
    }
    
} catch(PDOException $e) {
    echo "❌ 資料庫錯誤: " . $e->getMessage() . "\n";
} catch(Exception $e) {
    echo "❌ 系統錯誤: " . $e->getMessage() . "\n";
}
?>
