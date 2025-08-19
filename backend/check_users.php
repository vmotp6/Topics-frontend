<?php
// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "📋 檢查用戶資料表...\n";
    
    // 檢查user表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'user'");
    if ($stmt->rowCount() > 0) {
        echo "✅ user表存在\n";
        
        // 顯示user表結構
        echo "\n📋 user表結構：\n";
        $stmt = $pdo->query("DESCRIBE user");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo "- {$column['Field']}: {$column['Type']} " . 
                 ($column['Null'] === 'NO' ? '(必填)' : '(選填)') . "\n";
        }
        
        // 顯示現有用戶
        echo "\n👥 現有用戶：\n";
        $stmt = $pdo->query("SELECT username, role FROM user ORDER BY role, username");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($users) > 0) {
            foreach ($users as $user) {
                echo "- {$user['username']} ({$user['role']})\n";
            }
        } else {
            echo "❌ 沒有找到任何用戶\n";
        }
        
    } else {
        echo "❌ user表不存在\n";
    }
    
} catch(PDOException $e) {
    echo "❌ 資料庫錯誤: " . $e->getMessage() . "\n";
} catch(Exception $e) {
    echo "❌ 系統錯誤: " . $e->getMessage() . "\n";
}
?>
