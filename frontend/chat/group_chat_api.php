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
    
    // 檢查是否需要建立群聊資料表
    $stmt = $pdo->query("SHOW TABLES LIKE 'group_chats'");
    if ($stmt->rowCount() == 0) {
        // 建立群聊資料表
        $sql = "CREATE TABLE group_chats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_name VARCHAR(255) NOT NULL,
            created_by VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created_by (created_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $pdo->exec($sql);
        
        // 建立群聊成員資料表
        $sql = "CREATE TABLE group_chat_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            member_username VARCHAR(255) NOT NULL,
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES group_chats(id) ON DELETE CASCADE,
            INDEX idx_group_id (group_id),
            INDEX idx_member_username (member_username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $pdo->exec($sql);
        
        // 建立群聊訊息資料表
        $sql = "CREATE TABLE group_chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            from_user VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            role VARCHAR(50) NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES group_chats(id) ON DELETE CASCADE,
            INDEX idx_group_id (group_id),
            INDEX idx_timestamp (timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $pdo->exec($sql);
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            echo json_encode(['error' => '無效的資料格式']);
            exit;
        }
        
        $action = $data['action'] ?? '';
        
                 switch ($action) {
             case 'create_group':
                 createGroup($pdo, $data);
                 break;
             case 'send_group_message':
                 sendGroupMessage($pdo, $data);
                 break;
             case 'get_group_messages':
                 getGroupMessages($pdo, $data);
                 break;
             case 'update_group_name':
                 updateGroupName($pdo, $data);
                 break;
             default:
                 echo json_encode(['error' => '未知的操作']);
         }
    } else {
        echo json_encode(['error' => '只支援POST請求']);
    }
    
} catch(PDOException $e) {
    echo json_encode(['error' => '資料庫錯誤: ' . $e->getMessage()]);
}

function createGroup($pdo, $data) {
    $groupName = $data['group_name'] ?? '群聊';
    $createdBy = $data['created_by'] ?? '';
    $members = $data['members'] ?? [];
    
    if (empty($createdBy)) {
        echo json_encode(['error' => '缺少建立者資訊']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // 建立群聊
        $stmt = $pdo->prepare("INSERT INTO group_chats (group_name, created_by) VALUES (?, ?)");
        $stmt->execute([$groupName, $createdBy]);
        $groupId = $pdo->lastInsertId();
        
        // 添加建立者為成員
        $stmt = $pdo->prepare("INSERT INTO group_chat_members (group_id, member_username) VALUES (?, ?)");
        $stmt->execute([$groupId, $createdBy]);
        
        // 添加其他成員
        foreach ($members as $member) {
            $stmt->execute([$groupId, $member]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'group_id' => $groupId,
            'message' => '群聊建立成功'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        echo json_encode(['error' => '建立群聊失敗: ' . $e->getMessage()]);
    }
}

function sendGroupMessage($pdo, $data) {
    $groupId = $data['group_id'] ?? 0;
    $fromUser = $data['from_user'] ?? '';
    $message = $data['message'] ?? '';
    $role = $data['role'] ?? '用戶';
    
    if (empty($groupId) || empty($fromUser) || empty($message)) {
        echo json_encode(['error' => '缺少必要參數']);
        return;
    }
    
    try {
        // 檢查用戶是否為群組成員
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_chat_members WHERE group_id = ? AND member_username COLLATE utf8mb4_unicode_ci = ? COLLATE utf8mb4_unicode_ci");
        $stmt->execute([$groupId, $fromUser]);
        
        if ($stmt->fetchColumn() == 0) {
            echo json_encode(['error' => '您不是此群組的成員']);
            return;
        }
        
        // 儲存訊息
        $stmt = $pdo->prepare("INSERT INTO group_chat_messages (group_id, from_user, message, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$groupId, $fromUser, $message, $role]);
        
        echo json_encode([
            'success' => true,
            'message' => '訊息已發送',
            'id' => $pdo->lastInsertId()
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => '發送訊息失敗: ' . $e->getMessage()]);
    }
}

function getGroupMessages($pdo, $data) {
    $groupId = $data['group_id'] ?? 0;
    $fromUser = $data['from_user'] ?? '';
    
    if (empty($groupId) || empty($fromUser)) {
        echo json_encode(['error' => '缺少必要參數']);
        return;
    }
    
    try {
        // 檢查用戶是否為群組成員
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_chat_members WHERE group_id = ? AND member_username COLLATE utf8mb4_unicode_ci = ? COLLATE utf8mb4_unicode_ci");
        $stmt->execute([$groupId, $fromUser]);
        
        if ($stmt->fetchColumn() == 0) {
            echo json_encode(['error' => '您不是此群組的成員']);
            return;
        }
        
        // 獲取群組訊息
        $stmt = $pdo->prepare("SELECT * FROM group_chat_messages WHERE group_id = ? ORDER BY timestamp ASC");
        $stmt->execute([$groupId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'messages' => $messages
        ]);
        
         } catch (Exception $e) {
         echo json_encode(['error' => '獲取訊息失敗: ' . $e->getMessage()]);
     }
 }
 
 function updateGroupName($pdo, $data) {
     $groupId = $data['group_id'] ?? 0;
     $newName = $data['new_name'] ?? '';
     $updatedBy = $data['updated_by'] ?? '';
     
     if (empty($groupId) || empty($newName) || empty($updatedBy)) {
         echo json_encode(['error' => '缺少必要參數']);
         return;
     }
     
     try {
         // 檢查用戶是否為群組建立者
         $stmt = $pdo->prepare("SELECT created_by FROM group_chats WHERE id = ?");
         $stmt->execute([$groupId]);
         $group = $stmt->fetch(PDO::FETCH_ASSOC);
         
         if (!$group) {
             echo json_encode(['error' => '群組不存在']);
             return;
         }
         
         if ($group['created_by'] !== $updatedBy) {
             echo json_encode(['error' => '只有群組建立者可以修改群組名稱']);
             return;
         }
         
         // 更新群組名稱
         $stmt = $pdo->prepare("UPDATE group_chats SET group_name = ? WHERE id = ?");
         $stmt->execute([$newName, $groupId]);
         
         echo json_encode([
             'success' => true,
             'message' => '群組名稱更新成功'
         ]);
         
     } catch (Exception $e) {
         echo json_encode(['error' => '更新群組名稱失敗: ' . $e->getMessage()]);
     }
 }
 ?>
