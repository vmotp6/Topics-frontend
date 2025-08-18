<?php
header('Content-Type: application/json');

// 資料庫連接
$host = '100.79.58.120';  // 使用本機資料庫
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 獲取POST數據
    $input = json_decode(file_get_contents('php://input'), true);
    $vendorUsername = $input['vendor_username'] ?? '';
    
    if (empty($vendorUsername)) {
        echo json_encode(['success' => false, 'error' => '缺少廠商帳號']);
        exit;
    }
    
    // 查詢廠商參與的群聊
    $stmt = $pdo->prepare("SELECT gc.id, gc.group_name, gc.created_by, gc.created_at, '群聊' as contact_type
                          FROM group_chats gc 
                          JOIN group_chat_members gcm ON gc.id = gcm.group_id 
                          WHERE gcm.member_username = ?
                          ORDER BY gc.created_at DESC");
    $stmt->execute([$vendorUsername]);
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'vendor_username' => $vendorUsername,
        'groups' => $groups,
        'count' => count($groups)
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => '資料庫錯誤: ' . $e->getMessage()]);
}
?>

