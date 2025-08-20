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
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 獲取動作類型
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_my_groups':
            getMyGroups($pdo);
            break;
        case 'create_group':
            createGroup($pdo);
            break;
        case 'get_group_messages':
            getGroupMessages($pdo);
            break;
        case 'send_group_message':
            sendGroupMessage($pdo);
            break;
        case 'update_group_name':
            updateGroupName($pdo);
            break;
        default:
            echo json_encode(['success' => false, 'error' => '無效的動作']);
    }
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => '資料庫連接失敗: ' . $e->getMessage()
    ]);
}

// 獲取我的群組
function getMyGroups($pdo) {
    $username = $_GET['username'] ?? '';
    
    if (empty($username)) {
        echo json_encode(['success' => false, 'error' => '缺少用戶名參數']);
        return;
    }
    
    try {
        // 獲取用戶參與的群組 - 使用 group_chat_members 表和 group_info 表
        $sql = "SELECT gm.group_id as id, 
                       COALESCE(gi.group_name, gm.group_id) as group_name, 
                       COUNT(gm2.id) as member_count,
                       COALESCE(gi.created_by, gm.member_username) as created_by,
                       gi.department
                FROM group_chat_members gm 
                JOIN group_chat_members gm2 ON gm.group_id = gm2.group_id 
                LEFT JOIN group_info gi ON gm.group_id = gi.group_id
                WHERE gm.member_username = ? 
                GROUP BY gm.group_id 
                ORDER BY gm.joined_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username]);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'groups' => $groups
        ]);
        
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => '獲取群組失敗: ' . $e->getMessage()
        ]);
    }
}

// 創建群組
function createGroup($pdo) {
    $groupName = $_POST['group_name'] ?? '';
    $createdBy = $_POST['created_by'] ?? '';
    $department = $_POST['department'] ?? '';
    $members = json_decode($_POST['members'] ?? '[]', true);
    
    if (empty($groupName) || empty($createdBy)) {
        echo json_encode(['success' => false, 'error' => '缺少必要參數']);
        return;
    }
    
    try {
        // 創建群組資訊表（如果不存在）- 在事務外執行
        try {
            $sql = "CREATE TABLE IF NOT EXISTS group_info (
                group_id VARCHAR(255) PRIMARY KEY,
                group_name VARCHAR(255) NOT NULL,
                created_by VARCHAR(255) NOT NULL,
                department VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // 表可能已存在，忽略錯誤
        }
        
        // 開始事務
        $pdo->beginTransaction();
        
        // 創建群組 - 使用簡單的群組ID
        $groupId = time() . '_' . rand(1000, 9999); // 生成唯一的群組ID
        
        // 保存群組資訊
        $sql = "INSERT INTO group_info (group_id, group_name, created_by, department, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId, $groupName, $createdBy, $department]);
        
        // 添加創建者為成員
        $sql = "INSERT INTO group_chat_members (group_id, member_username, joined_at) VALUES (?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId, $createdBy]);
        
        // 添加其他成員
        foreach ($members as $member) {
            $sql = "INSERT INTO group_chat_members (group_id, member_username, joined_at) VALUES (?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$groupId, $member['username']]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => '群組創建成功',
            'group_id' => $groupId
        ]);
        
    } catch(PDOException $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'error' => '創建群組失敗: ' . $e->getMessage()
        ]);
    }
}

// 獲取群組訊息
function getGroupMessages($pdo) {
    $groupId = $_GET['group_id'] ?? '';
    
    if (empty($groupId)) {
        echo json_encode(['success' => false, 'error' => '缺少群組ID參數']);
        return;
    }
    
    try {
        $sql = "SELECT gm.id, gm.group_id, gm.from_user, gm.message, gm.role, gm.timestamp, u.role as user_role 
                FROM group_chat_messages gm 
                LEFT JOIN user u ON gm.from_user = u.username 
                WHERE gm.group_id = ? 
                ORDER BY gm.timestamp ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'messages' => $messages
        ]);
        
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => '獲取群組訊息失敗: ' . $e->getMessage()
        ]);
    }
}

// 發送群組訊息
function sendGroupMessage($pdo) {
    $groupId = $_POST['group_id'] ?? '';
    $fromUser = $_POST['from_user'] ?? '';
    $message = $_POST['message'] ?? '';
    $role = $_POST['role'] ?? '';
    
    if (empty($groupId) || empty($fromUser) || empty($message)) {
        echo json_encode(['success' => false, 'error' => '缺少必要參數']);
        return;
    }
    
    try {
        // 檢查用戶是否為群組成員
        $sql = "SELECT COUNT(*) FROM group_chat_members WHERE group_id = ? AND member_username = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId, $fromUser]);
        $isMember = $stmt->fetchColumn() > 0;
        
        if (!$isMember) {
            echo json_encode(['success' => false, 'error' => '您不是該群組的成員']);
            return;
        }
        
        // 儲存群組訊息
        $sql = "INSERT INTO group_chat_messages (group_id, from_user, message, role, timestamp) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId, $fromUser, $message, $role]);
        
        echo json_encode([
            'success' => true,
            'message' => '訊息發送成功',
            'id' => $pdo->lastInsertId()
        ]);
        
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => '發送群組訊息失敗: ' . $e->getMessage()
        ]);
    }
}

// 更新群組名稱
function updateGroupName($pdo) {
    $groupId = $_POST['group_id'] ?? '';
    $newName = $_POST['new_name'] ?? '';
    $username = $_POST['username'] ?? '';
    
    if (empty($groupId) || empty($newName) || empty($username)) {
        echo json_encode(['success' => false, 'error' => '缺少必要參數']);
        return;
    }
    
    try {
        // 檢查用戶是否為群組成員
        $sql = "SELECT COUNT(*) FROM group_chat_members WHERE group_id = ? AND member_username = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId, $username]);
        $isMember = $stmt->fetchColumn() > 0;
        
        if (!$isMember) {
            echo json_encode(['success' => false, 'error' => '您不是該群組的成員']);
            return;
        }
        
        // 檢查群組資訊是否存在，如果不存在則創建
        $sql = "SELECT COUNT(*) FROM group_info WHERE group_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId]);
        $exists = $stmt->fetchColumn() > 0;
        
        if ($exists) {
            // 更新現有群組資訊
            $sql = "UPDATE group_info SET group_name = ? WHERE group_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$newName, $groupId]);
        } else {
            // 創建新的群組資訊記錄
            $sql = "INSERT INTO group_info (group_id, group_name, created_by, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$groupId, $newName, $username]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => '群組名稱更新成功'
        ]);
        
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => '更新群組名稱失敗: ' . $e->getMessage()
        ]);
    }
}
?>

