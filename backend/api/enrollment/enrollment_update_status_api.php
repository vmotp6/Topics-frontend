<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '只支援POST請求']);
    exit;
}

// 讀取POST資料
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'message' => '缺少必要參數']);
    exit;
}

$id = $input['id'];
$status = $input['status'];

// 驗證狀態值
$valid_statuses = ['pending', 'contacted', 'enrolled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => '無效的狀態值']);
    exit;
}

// 資料庫連接
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 檢查資料表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'enrollment_applications'");
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => '資料表不存在']);
        exit;
    }
    
    // 更新狀態
    $sql = "UPDATE enrollment_applications SET status = :status WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([':status' => $status, ':id' => $id]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => '狀態更新成功']);
    } else {
        echo json_encode(['success' => false, 'message' => '找不到該申請資料或更新失敗']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料庫錯誤: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '系統錯誤: ' . $e->getMessage()]);
}
?>
