<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => '只支援POST請求']);
    exit;
}

// 資料庫連接
$host = 'localhost';  // 使用本機資料庫
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['username']) || !isset($data['message'])) {
        echo json_encode(['error' => '無效的資料格式']);
        exit;
    }
    
    $sql = "INSERT INTO chat_history (username, role, message) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['username'], $data['role'] ?? '用戶', $data['message']]);
    
    echo json_encode([
        'success' => true,
        'message' => '訊息已儲存',
        'id' => $pdo->lastInsertId()
    ]);
} catch(PDOException $e) {
    echo json_encode(['error' => '儲存訊息失敗: ' . $e->getMessage()]);
}
?>
