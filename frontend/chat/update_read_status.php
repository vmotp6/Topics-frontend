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
$host = '100.79.58.120';
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
    
    // 獲取發送給該用戶的未讀訊息數量
    $stmt = $pdo->prepare("SELECT COUNT(*) as unread_count 
                          FROM private_chat_history 
                          WHERE to_user = ? AND is_read = FALSE");
    $stmt->execute([$username]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'unread_count' => (int)$result['unread_count']
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
    
    // 獲取兩個用戶之間的訊息已讀狀態
    $stmt = $pdo->prepare("SELECT id, from_user, to_user, message, is_read, read_at, timestamp
                          FROM private_chat_history 
                          WHERE (from_user = ? AND to_user = ?) OR (from_user = ? AND to_user = ?)
                          ORDER BY timestamp ASC");
    $stmt->execute([$username, $otherUser, $otherUser, $username]);
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
    
    // 更新或插入用戶活動記錄
    $stmt = $pdo->prepare("INSERT INTO user_activity (username, last_chat_check, is_online) 
                          VALUES (?, NOW(), ?) 
                          ON DUPLICATE KEY UPDATE 
                          last_chat_check = NOW(), 
                          is_online = VALUES(is_online)");
    $stmt->execute([$username, $isOnline]);
    
    echo json_encode([
        'success' => true,
        'message' => '活動時間已更新'
    ]);
}
?>
