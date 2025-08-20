<?php
// 遠端資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔧 更新遠端資料庫用戶密碼...\n\n";
    
    // 更新teacher1的密碼
    $stmt = $pdo->prepare("UPDATE user SET password = ? WHERE username = ?");
    $stmt->execute(['123456', 'teacher1']);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ teacher1 密碼已更新為：123456\n";
    } else {
        echo "⚠️  teacher1 密碼更新失敗或無變化\n";
    }
    
    // 更新teacher2的密碼
    $stmt = $pdo->prepare("UPDATE user SET password = ? WHERE username = ?");
    $stmt->execute(['123456', 'teacher2']);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ teacher2 密碼已更新為：123456\n";
    } else {
        echo "⚠️  teacher2 密碼更新失敗或無變化\n";
    }
    
    // 更新admin1的密碼
    $stmt = $pdo->prepare("UPDATE user SET password = ? WHERE username = ?");
    $stmt->execute(['123456', 'admin1']);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ admin1 密碼已更新為：123456\n";
    } else {
        echo "⚠️  admin1 密碼更新失敗或無變化\n";
    }
    
    // 更新admin2的密碼
    $stmt = $pdo->prepare("UPDATE user SET password = ? WHERE username = ?");
    $stmt->execute(['123456', 'admin2']);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ admin2 密碼已更新為：123456\n";
    } else {
        echo "⚠️  admin2 密碼更新失敗或無變化\n";
    }
    
    echo "\n🎉 遠端資料庫密碼更新完成！\n";
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
