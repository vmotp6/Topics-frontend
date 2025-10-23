<?php
/**
 * 檢查學長姐留言表是否存在
 */

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>🔍 檢查學長姐留言表</h1>";
    
    // 檢查表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'senior_messages'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ 資料表 'senior_messages' 存在</p>";
        
        // 檢查表結構
        $stmt = $pdo->query("DESCRIBE senior_messages");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h2>資料表欄位：</h2>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} ({$column['Type']}) - {$column['Null']} - {$column['Key']}</li>";
        }
        echo "</ul>";
        
        // 檢查資料數量
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM senior_messages");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>總留言數：{$count['total']}</p>";
        
    } else {
        echo "<p style='color: red;'>❌ 資料表 'senior_messages' 不存在</p>";
        echo "<p><a href='create_senior_messages_table.php'>創建資料表</a></p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>資料庫連接失敗: " . $e->getMessage() . "</p>";
}
?>
