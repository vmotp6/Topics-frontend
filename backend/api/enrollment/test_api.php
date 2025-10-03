<?php
// 簡單的測試 API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 測試資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo json_encode([
        'success' => true,
        'message' => '資料庫連接成功',
        'method' => $_SERVER['REQUEST_METHOD'],
        'post_data' => $_POST
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '資料庫連接失敗: ' . $e->getMessage(),
        'host' => $host,
        'dbname' => $dbname
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '系統錯誤: ' . $e->getMessage()
    ]);
}
?>
