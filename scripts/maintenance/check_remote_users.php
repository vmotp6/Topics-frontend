<?php
// 遠端資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "📋 檢查遠端資料庫用戶資料...\n\n";
    
    // 檢查teacher1用戶
    $stmt = $pdo->prepare("SELECT username, password, role, status FROM user WHERE username = ?");
    $stmt->execute(['teacher1']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ 找到teacher1用戶：\n";
        echo "- 用戶名：{$user['username']}\n";
        echo "- 密碼：{$user['password']}\n";
        echo "- 角色：{$user['role']}\n";
        echo "- 狀態：{$user['status']}\n\n";
    } else {
        echo "❌ 找不到teacher1用戶\n\n";
    }
    
    // 檢查teacher2用戶
    $stmt = $pdo->prepare("SELECT username, password, role, status FROM user WHERE username = ?");
    $stmt->execute(['teacher2']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ 找到teacher2用戶：\n";
        echo "- 用戶名：{$user['username']}\n";
        echo "- 密碼：{$user['password']}\n";
        echo "- 角色：{$user['role']}\n";
        echo "- 狀態：{$user['status']}\n\n";
    } else {
        echo "❌ 找不到teacher2用戶\n\n";
    }
    
    // 顯示所有老師用戶
    echo "📋 所有老師用戶：\n";
    $stmt = $pdo->query("SELECT username, password, role, status FROM user WHERE role = '老師' ORDER BY username");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        echo "- {$user['username']} (密碼: {$user['password']}, 狀態: {$user['status']})\n";
    }
    
} catch(PDOException $e) {
    echo "❌ 資料庫錯誤: " . $e->getMessage() . "\n";
} catch(Exception $e) {
    echo "❌ 系統錯誤: " . $e->getMessage() . "\n";
}
?>
