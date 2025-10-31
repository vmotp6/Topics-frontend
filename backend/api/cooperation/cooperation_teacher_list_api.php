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

// 檢查必要參數
if (!isset($_GET['teacher_username']) || empty($_GET['teacher_username'])) {
    echo json_encode(['success' => false, 'message' => '缺少老師帳號']);
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
    
    // 查詢該老師的所有申請表
    $sql = "SELECT id, project_title, company_name, budget_amount, status, created_at 
            FROM cooperation_applications 
            WHERE teacher_username = :teacher_username 
            ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':teacher_username' => $_GET['teacher_username']]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($applications);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料庫錯誤: ' . $e->getMessage()]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => '系統錯誤: ' . $e->getMessage()]);
}
?>
