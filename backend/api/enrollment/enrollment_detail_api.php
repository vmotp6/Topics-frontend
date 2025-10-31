<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => '只支援GET請求']);
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => '缺少申請編號']);
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
    
    $sql = "SELECT * FROM enrollment_applications WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $_GET['id']]);
    
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($application) {
        echo json_encode(['success' => true, 'application' => $application]);
    } else {
        echo json_encode(['success' => false, 'message' => '找不到該申請資料']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料庫錯誤: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '系統錯誤: ' . $e->getMessage()]);
}
?>
