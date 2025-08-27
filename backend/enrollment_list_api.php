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

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 檢查資料表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'enrollment_applications'");
    if ($stmt->rowCount() == 0) {
        echo json_encode([]);
        exit;
    }
    
    // 建立查詢條件
    $where_conditions = [];
    $params = [];
    
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $where_conditions[] = "status = :status";
        $params[':status'] = $_GET['status'];
    }
    
    if (isset($_GET['identity']) && !empty($_GET['identity'])) {
        $where_conditions[] = "identity = :identity";
        $params[':identity'] = $_GET['identity'];
    }
    
    if (isset($_GET['date']) && !empty($_GET['date'])) {
        $where_conditions[] = "DATE(created_at) = :date";
        $params[':date'] = $_GET['date'];
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    $sql = "SELECT * FROM enrollment_applications $where_clause ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($applications);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料庫錯誤: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '系統錯誤: ' . $e->getMessage()]);
}
?>
