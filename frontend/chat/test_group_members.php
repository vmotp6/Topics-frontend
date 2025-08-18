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
    $groupId = $input['group_id'] ?? '';
    
    if (empty($groupId)) {
        echo json_encode(['success' => false, 'error' => '缺少群聊ID']);
        exit;
    }
    
    // 查詢群聊成員
    $stmt = $pdo->prepare("SELECT gcm.member_username, gcm.joined_at, gc.group_name, gc.created_by
                          FROM group_chat_members gcm
                          JOIN group_chats gc ON gcm.group_id = gc.id
                          WHERE gcm.group_id = ?
                          ORDER BY gcm.joined_at ASC");
    $stmt->execute([$groupId]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 查詢群聊基本信息
    $stmt = $pdo->prepare("SELECT id, group_name, created_by, created_at FROM group_chats WHERE id = ?");
    $stmt->execute([$groupId]);
    $groupInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'group_id' => $groupId,
        'group_info' => $groupInfo,
        'members' => $members,
        'member_count' => count($members)
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => '資料庫錯誤: ' . $e->getMessage()]);
}
?>

