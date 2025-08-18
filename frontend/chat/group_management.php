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
$host = '100.79.58.120';  // 使用本機資料庫
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 使用現有的群組資料表
    // group_chats, group_chat_members, group_chat_messages
    
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'create_group':
            // 建立群組
            $group_name = $_POST['group_name'] ?? '';
            $created_by = $_POST['created_by'] ?? '';
            $department = $_POST['department'] ?? '';
            $members = json_decode($_POST['members'] ?? '[]', true);
            
            if (empty($group_name) || empty($created_by) || empty($department)) {
                echo json_encode(['error' => '缺少必要參數']);
                exit;
            }
            
            $pdo->beginTransaction();
            
            try {
                // 建立群組
                $stmt = $pdo->prepare("INSERT INTO group_chats (group_name, created_by) VALUES (?, ?)");
                $stmt->execute([$group_name, $created_by]);
                $group_id = $pdo->lastInsertId();
                
                // 添加建立者為成員
                $stmt = $pdo->prepare("INSERT INTO group_chat_members (group_id, member_username) VALUES (?, ?)");
                $stmt->execute([$group_id, $created_by]);
                
                // 添加其他成員
                foreach ($members as $member) {
                    $stmt = $pdo->prepare("INSERT INTO group_chat_members (group_id, member_username) VALUES (?, ?)");
                    $stmt->execute([$group_id, $member['username']]);
                }
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => '群組建立成功',
                    'group_id' => $group_id
                ]);
                
            } catch (Exception $e) {
                $pdo->rollback();
                echo json_encode(['error' => '建立群組失敗: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_my_groups':
            // 獲取我的群組
            $username = $_GET['username'] ?? '';
            
            if (empty($username)) {
                echo json_encode(['error' => '缺少用戶名參數']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                SELECT DISTINCT gc.*, COUNT(gcm.member_username) as member_count
                FROM group_chats gc
                JOIN group_chat_members gcm ON gc.id = gcm.group_id
                WHERE gcm.member_username = ?
                GROUP BY gc.id
                ORDER BY gc.created_at DESC
            ");
            $stmt->execute([$username]);
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'groups' => $groups
            ]);
            break;
            
        case 'get_group_members':
            // 獲取群組成員
            $group_id = $_GET['group_id'] ?? '';
            
            if (empty($group_id)) {
                echo json_encode(['error' => '缺少群組ID參數']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                SELECT gcm.*, 
                       CASE 
                           WHEN u.role = '老師' THEN t2.name
                           ELSE gcm.member_username
                       END as display_name,
                       CASE 
                           WHEN u.role = '老師' THEN t2.department
                           ELSE '廠商'
                       END as department,
                       u.role as member_role
                FROM group_chat_members gcm
                LEFT JOIN user u ON gcm.member_username = u.username
                LEFT JOIN teacher02 t2 ON u.id = t2.u_id
                WHERE gcm.group_id = ?
                ORDER BY gcm.joined_at ASC
            ");
            $stmt->execute([$group_id]);
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'members' => $members
            ]);
            break;
            
        case 'get_group_messages':
            // 獲取群組訊息
            $group_id = $_GET['group_id'] ?? '';
            
            if (empty($group_id)) {
                echo json_encode(['error' => '缺少群組ID參數']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                SELECT * FROM group_chat_messages 
                WHERE group_id = ? 
                ORDER BY timestamp ASC
            ");
            $stmt->execute([$group_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'messages' => $messages
            ]);
            break;
            
        case 'send_group_message':
            // 發送群組訊息
            $group_id = $_POST['group_id'] ?? '';
            $from_user = $_POST['from_user'] ?? '';
            $message = $_POST['message'] ?? '';
            $role = $_POST['role'] ?? '';
            
            if (empty($group_id) || empty($from_user) || empty($message)) {
                echo json_encode(['error' => '缺少必要參數']);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO group_chat_messages (group_id, from_user, message, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$group_id, $from_user, $message, $role]);
            
            echo json_encode([
                'success' => true,
                'message' => '訊息發送成功',
                'id' => $pdo->lastInsertId()
            ]);
            break;
            
        default:
            echo json_encode(['error' => '無效的操作']);
            break;
    }
    
} catch(PDOException $e) {
    echo json_encode([
        'error' => '資料庫錯誤: ' . $e->getMessage()
    ]);
}
?>
