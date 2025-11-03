<?php
/**
 * 更新訊息已讀狀態的API
 */

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
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'mark_as_read':
            markAsRead($pdo);
            break;
        case 'get_unread_count':
            getUnreadCount($pdo);
            break;
        case 'get_read_status':
            getReadStatus($pdo);
            break;
        case 'get_unread_messages':
            getUnreadMessages($pdo);
            break;
        case 'update_activity':
            updateActivity($pdo);
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

/**
 * 標記訊息為已讀
 */
function markAsRead($pdo) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['message_ids']) || !isset($data['reader'])) {
        echo json_encode(['success' => false, 'error' => '缺少必要參數']);
        return;
    }
    
    $messageIds = $data['message_ids'];
    $reader = $data['reader'];
    
    if (!is_array($messageIds)) {
        $messageIds = [$messageIds];
    }
    
    $pdo->beginTransaction();
    
    try {
        foreach ($messageIds as $messageId) {
            // 更新 private_chat_history 表
            $stmt = $pdo->prepare("UPDATE private_chat_history SET is_read = TRUE, read_at = NOW() WHERE id = ?");
            $stmt->execute([$messageId]);
            
            // 插入到 message_read_status 表
            $stmt = $pdo->prepare("INSERT IGNORE INTO message_read_status (message_id, reader_username) VALUES (?, ?)");
            $stmt->execute([$messageId, $reader]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => '已標記為已讀',
            'count' => count($messageIds)
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'error' => '更新失敗: ' . $e->getMessage()
        ]);
    }
}

/**
 * 獲取未讀訊息數量
 */
function getUnreadCount($pdo) {
    $username = $_GET['username'] ?? '';
    
    if (empty($username)) {
        echo json_encode(['success' => false, 'error' => '缺少用戶名']);
        return;
    }
    
    // 檢查表結構，自動適配
    $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                        WHERE TABLE_SCHEMA = 'topics_good' 
                        AND TABLE_NAME = 'private_chat_history' 
                        AND COLUMN_NAME IN ('to_user', 'to_user_id')");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('to_user_id', $columns)) {
        // 使用正規化版本
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $userId = $user ? $user['id'] : null;
        
        if (!$userId) {
            echo json_encode(['success' => true, 'unread_count' => 0]);
            return;
        }
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as unread_count 
                              FROM private_chat_history 
                              WHERE to_user_id = ? AND is_read = FALSE");
        $stmt->execute([$userId]);
    } else {
        // 使用舊版本
        $stmt = $pdo->prepare("SELECT COUNT(*) as unread_count 
                              FROM private_chat_history 
                              WHERE to_user = ? AND is_read = FALSE");
        $stmt->execute([$username]);
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'unread_count' => (int)$result['unread_count']
    ]);
}

/**
 * 獲取特定聯絡人的未讀訊息ID列表
 */
function getUnreadMessages($pdo) {
    $from = $_GET['from'] ?? '';
    $to = $_GET['to'] ?? '';
    
    if (empty($from) || empty($to)) {
        echo json_encode(['success' => false, 'error' => '缺少必要參數']);
        return;
    }
    
    // 檢查表結構，自動適配
    $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                        WHERE TABLE_SCHEMA = 'topics_good' 
                        AND TABLE_NAME = 'private_chat_history' 
                        AND COLUMN_NAME IN ('from_user', 'to_user', 'from_user_id', 'to_user_id')");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $useUserId = in_array('from_user_id', $columns) && in_array('to_user_id', $columns);
    
    if ($useUserId) {
        // 使用正規化版本
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$from]);
        $fromUser = $stmt->fetch(PDO::FETCH_ASSOC);
        $fromUserId = $fromUser ? $fromUser['id'] : null;
        
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$to]);
        $toUser = $stmt->fetch(PDO::FETCH_ASSOC);
        $toUserId = $toUser ? $toUser['id'] : null;
        
        if (!$fromUserId || !$toUserId) {
            echo json_encode(['success' => true, 'message_ids' => []]);
            return;
        }
        
        // 檢查是否有 is_read 欄位
        $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                            WHERE TABLE_SCHEMA = 'topics_good' 
                            AND TABLE_NAME = 'private_chat_history' 
                            AND COLUMN_NAME = 'is_read'");
        $hasIsRead = $stmt->rowCount() > 0;
        
        if ($hasIsRead) {
            $stmt = $pdo->prepare("SELECT id FROM private_chat_history 
                                  WHERE from_user_id = ? AND to_user_id = ? 
                                  AND (is_read = FALSE OR is_read IS NULL OR is_read = 0)
                                  ORDER BY id ASC");
        } else {
            // 如果沒有 is_read 欄位，返回所有訊息
            $stmt = $pdo->prepare("SELECT id FROM private_chat_history 
                                  WHERE from_user_id = ? AND to_user_id = ? 
                                  ORDER BY id ASC");
        }
        $stmt->execute([$fromUserId, $toUserId]);
    } else {
        // 使用舊版本
        $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                            WHERE TABLE_SCHEMA = 'topics_good' 
                            AND TABLE_NAME = 'private_chat_history' 
                            AND COLUMN_NAME = 'is_read'");
        $hasIsRead = $stmt->rowCount() > 0;
        
        if ($hasIsRead) {
            $stmt = $pdo->prepare("SELECT id FROM private_chat_history 
                                  WHERE from_user = ? AND to_user = ? 
                                  AND (is_read = FALSE OR is_read IS NULL OR is_read = 0)
                                  ORDER BY id ASC");
        } else {
            // 如果沒有 is_read 欄位，返回所有訊息
            $stmt = $pdo->prepare("SELECT id FROM private_chat_history 
                                  WHERE from_user = ? AND to_user = ? 
                                  ORDER BY id ASC");
        }
        $stmt->execute([$from, $to]);
    }
    
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $messageIds = array_map('intval', $results);
    
    echo json_encode([
        'success' => true,
        'message_ids' => $messageIds,
        'count' => count($messageIds)
    ]);
}

/**
 * 獲取訊息的已讀狀態
 */
function getReadStatus($pdo) {
    $username = $_GET['username'] ?? '';
    $otherUser = $_GET['other_user'] ?? '';
    
    if (empty($username) || empty($otherUser)) {
        echo json_encode(['success' => false, 'error' => '缺少必要參數']);
        return;
    }
    
    // 檢查表結構，自動適配
    $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                        WHERE TABLE_SCHEMA = 'topics_good' 
                        AND TABLE_NAME = 'private_chat_history' 
                        AND COLUMN_NAME IN ('from_user', 'to_user', 'from_user_id', 'to_user_id')");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('from_user_id', $columns) && in_array('to_user_id', $columns)) {
        // 使用正規化版本
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$username]);
        $user1 = $stmt->fetch(PDO::FETCH_ASSOC);
        $userId1 = $user1 ? $user1['id'] : null;
        
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$otherUser]);
        $user2 = $stmt->fetch(PDO::FETCH_ASSOC);
        $userId2 = $user2 ? $user2['id'] : null;
        
        if (!$userId1 || !$userId2) {
            echo json_encode(['success' => true, 'messages' => []]);
            return;
        }
        
        $stmt = $pdo->prepare("SELECT pch.id, u1.username as from_user, u2.username as to_user, 
                              pch.message, pch.is_read, pch.read_at, pch.timestamp
                              FROM private_chat_history pch
                              LEFT JOIN user u1 ON pch.from_user_id = u1.id
                              LEFT JOIN user u2 ON pch.to_user_id = u2.id
                              WHERE (pch.from_user_id = ? AND pch.to_user_id = ?) 
                              OR (pch.from_user_id = ? AND pch.to_user_id = ?)
                              ORDER BY pch.timestamp ASC");
        $stmt->execute([$userId1, $userId2, $userId2, $userId1]);
    } else {
        // 使用舊版本
        $stmt = $pdo->prepare("SELECT id, from_user, to_user, message, is_read, read_at, timestamp
                              FROM private_chat_history 
                              WHERE (from_user = ? AND to_user = ?) OR (from_user = ? AND to_user = ?)
                              ORDER BY timestamp ASC");
        $stmt->execute([$username, $otherUser, $otherUser, $username]);
    }
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
}

/**
 * 更新用戶活動時間
 */
function updateActivity($pdo) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['username'])) {
        echo json_encode(['success' => false, 'error' => '缺少用戶名']);
        return;
    }
    
    $username = $data['username'];
    $isOnline = $data['is_online'] ?? true;
    
    // 檢查 user_activity 表結構，自動適配
    $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                        WHERE TABLE_SCHEMA = 'topics_good' 
                        AND TABLE_NAME = 'user_activity' 
                        AND COLUMN_NAME IN ('username', 'user_id')");
    $uaColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('user_id', $uaColumns)) {
        // 使用正規化版本
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $userId = $user ? $user['id'] : null;
        
        if (!$userId) {
            echo json_encode(['success' => false, 'error' => '找不到指定的用戶']);
            return;
        }
        
        $stmt = $pdo->prepare("INSERT INTO user_activity (user_id, last_chat_check, is_online) 
                              VALUES (?, NOW(), ?) 
                              ON DUPLICATE KEY UPDATE 
                              last_chat_check = NOW(), 
                              is_online = VALUES(is_online)");
        $stmt->execute([$userId, $isOnline]);
    } else {
        // 使用舊版本
        $stmt = $pdo->prepare("INSERT INTO user_activity (username, last_chat_check, is_online) 
                              VALUES (?, NOW(), ?) 
                              ON DUPLICATE KEY UPDATE 
                              last_chat_check = NOW(), 
                              is_online = VALUES(is_online)");
        $stmt->execute([$username, $isOnline]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => '活動時間已更新'
    ]);
}
?>























