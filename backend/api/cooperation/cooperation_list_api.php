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
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 建立查詢條件
    $where_conditions = [];
    $params = [];
    
    // 狀態篩選
    if (!empty($_GET['status'])) {
        $where_conditions[] = "status = :status";
        $params[':status'] = $_GET['status'];
    }
    
    // 科系篩選
    if (!empty($_GET['department'])) {
        $where_conditions[] = "department = :department";
        $params[':department'] = $_GET['department'];
    }
    
    // 日期篩選
    if (!empty($_GET['date'])) {
        $where_conditions[] = "DATE(created_at) = :date";
        $params[':date'] = $_GET['date'];
    }
    
    // 組合SQL語句
    $sql = "SELECT id, teacher_username, department, project_title, 
                   company_name, project_amount as budget_amount, status, created_at 
            FROM cooperation_applications";
    
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(" AND ", $where_conditions);
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($applications);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料庫錯誤: ' . $e->getMessage()]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => '系統錯誤: ' . $e->getMessage()]);
}
?>
