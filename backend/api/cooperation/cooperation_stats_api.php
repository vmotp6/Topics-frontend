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
    
    // 獲取統計數據
    $stats = [];
    
    // 總申請數
    $sql_total = "SELECT COUNT(*) as total FROM cooperation_applications";
    $stmt = $pdo->query($sql_total);
    $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 待審核申請數
    $sql_pending = "SELECT COUNT(*) as pending FROM cooperation_applications WHERE status = 'pending'";
    $stmt = $pdo->query($sql_pending);
    $stats['pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];
    
    // 已通過申請數
    $sql_approved = "SELECT COUNT(*) as approved FROM cooperation_applications WHERE status = 'approved'";
    $stmt = $pdo->query($sql_approved);
    $stats['approved'] = $stmt->fetch(PDO::FETCH_ASSOC)['approved'];
    
    // 已拒絕申請數
    $sql_rejected = "SELECT COUNT(*) as rejected FROM cooperation_applications WHERE status = 'rejected'";
    $stmt = $pdo->query($sql_rejected);
    $stats['rejected'] = $stmt->fetch(PDO::FETCH_ASSOC)['rejected'];
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料庫錯誤：' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '系統錯誤：' . $e->getMessage()]);
}
?>
