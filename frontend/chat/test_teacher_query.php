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
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 模擬老師查詢（使用測試用戶名）
    $username = 'test_teacher';
    
    // 先獲取當前老師的科系
    $stmt = $pdo->prepare("SELECT t2.department FROM teacher02 t2 
                          JOIN user u ON t2.u_id = u.id 
                          WHERE u.username = ? AND u.role = '老師'");
    $stmt->execute([$username]);
    $currentTeacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $contacts = [];
    
    if ($currentTeacher) {
        $department = $currentTeacher['department'];
        
        // 獲取同科系的老師
        $stmt = $pdo->prepare("SELECT t2.u_id, t2.name, t2.department, u.username, '老師' as contact_type
                              FROM teacher02 t2 
                              JOIN user u ON t2.u_id = u.id 
                              WHERE u.role = '老師' AND t2.department = ? AND u.username != ?
                              ORDER BY t2.name");
        $stmt->execute([$department, $username]);
        $sameDeptTeachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $contacts = array_merge($contacts, $sameDeptTeachers);
        
        // 獲取有私訊的廠商
        $stmt = $pdo->prepare("SELECT DISTINCT u.username as vendor_id, u.username as vendor_name, '廠商' as contact_type
                              FROM user u 
                              JOIN private_chat_history pch ON (u.username COLLATE utf8mb4_unicode_ci = pch.from_user COLLATE utf8mb4_unicode_ci OR u.username COLLATE utf8mb4_unicode_ci = pch.to_user COLLATE utf8mb4_unicode_ci)
                              WHERE u.role = '廠商' 
                              AND (pch.from_user = ? OR pch.to_user = ?)
                              AND u.username != ?
                              ORDER BY u.username");
        $stmt->execute([$username, $username, $username]);
        $vendorsWithChat = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $contacts = array_merge($contacts, $vendorsWithChat);
    }
    
    echo json_encode([
        'success' => true,
        'message' => '老師查詢測試成功',
        'contacts_count' => count($contacts),
        'contacts' => $contacts
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => '資料庫錯誤: ' . $e->getMessage()
    ]);
}
?>

