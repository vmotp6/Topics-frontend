<?php
// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 讀取SQL檔案
    $sql = file_get_contents('create_enrollment_table.sql');
    
    // 執行SQL
    $pdo->exec($sql);
    
    echo "資料表創建成功！";
    
} catch (PDOException $e) {
    echo "錯誤: " . $e->getMessage();
}
?>







