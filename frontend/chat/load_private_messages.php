<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 資料庫連接
$host = 'localhost';  // 使用本機資料庫
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 獲取GET參數
    $from = $_GET['from'] ?? '';
    $to = $_GET['to'] ?? '';
    
    if (empty($from) || empty($to)) {
        echo json_encode(['error' => '缺少必要參數']);
        exit;
    }
    
    // 獲取兩個用戶之間的私聊訊息
    $sql = "SELECT * FROM private_chat_history 
            WHERE (from_user = ? AND to_user = ?) 
            OR (from_user = ? AND to_user = ?) 
            ORDER BY timestamp ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$from, $to, $to, $from]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => '載入私聊訊息失敗: ' . $e->getMessage()
    ]);
}
?>
