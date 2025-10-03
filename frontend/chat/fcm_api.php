<?php
/**
 * Firebase Cloud Messaging API端點
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 引入FCM服務
require_once 'fcm_service.php';

try {
    $fcmService = new FCMService();
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'register_token':
            registerToken($fcmService);
            break;
        case 'send_chat_notification':
            sendChatNotification($fcmService);
            break;
        case 'send_group_notification':
            sendGroupNotification($fcmService);
            break;
        case 'get_stats':
            getNotificationStats($fcmService);
            break;
        case 'test_notification':
            testNotification($fcmService);
            break;
        case 'get_notification_settings':
            getNotificationSettings();
            break;
        case 'update_notification_settings':
            updateNotificationSettings();
            break;
        default:
            echo json_encode(['success' => false, 'error' => '無效的動作']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'FCM服務錯誤: ' . $e->getMessage()
    ]);
}

/**
 * 註冊FCM token
 */
function registerToken($fcmService) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['username']) || !isset($data['fcm_token'])) {
        echo json_encode(['success' => false, 'error' => '缺少必要參數']);
        return;
    }
    
    $username = $data['username'];
    $fcmToken = $data['fcm_token'];
    $deviceType = $data['device_type'] ?? 'web';
    $deviceInfo = $data['device_info'] ?? null;
    
    $result = $fcmService->registerUserToken($username, $fcmToken, $deviceType, $deviceInfo);
    echo json_encode($result);
}

/**
 * 發送聊天通知
 */
function sendChatNotification($fcmService) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['to_user']) || !isset($data['from_user']) || !isset($data['message'])) {
        echo json_encode(['success' => false, 'error' => '缺少必要參數']);
        return;
    }
    
    $toUser = $data['to_user'];
    $fromUser = $data['from_user'];
    $message = $data['message'];
    $messageId = $data['message_id'] ?? null;
    
    $result = $fcmService->sendChatNotification($toUser, $fromUser, $message, $messageId);
    echo json_encode($result);
}

/**
 * 發送群組通知
 */
function sendGroupNotification($fcmService) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['group_members']) || !isset($data['from_user']) || !isset($data['group_name']) || !isset($data['message'])) {
        echo json_encode(['success' => false, 'error' => '缺少必要參數']);
        return;
    }
    
    $groupMembers = $data['group_members'];
    $fromUser = $data['from_user'];
    $groupName = $data['group_name'];
    $message = $data['message'];
    $groupId = $data['group_id'] ?? null;
    
    $result = $fcmService->sendGroupNotification($groupMembers, $fromUser, $groupName, $message, $groupId);
    echo json_encode($result);
}

/**
 * 獲取通知統計
 */
function getNotificationStats($fcmService) {
    $username = $_GET['username'] ?? null;
    $stats = $fcmService->getNotificationStats($username);
    echo json_encode(['success' => true, 'stats' => $stats]);
}

/**
 * 測試通知
 */
function testNotification($fcmService) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['to_user']) || !isset($data['title']) || !isset($data['body'])) {
        echo json_encode(['success' => false, 'error' => '缺少必要參數']);
        return;
    }
    
    $toUser = $data['to_user'];
    $title = $data['title'];
    $body = $data['body'];
    $customData = $data['data'] ?? [];
    
    // 獲取用戶的FCM tokens
    $host = '100.79.58.120';
    $dbname = 'topics_good';
    $db_username = 'root';
    $db_password = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT fcm_token FROM user_fcm_tokens WHERE username = ? AND is_active = TRUE");
        $stmt->execute([$toUser]);
        $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tokens)) {
            echo json_encode(['success' => false, 'error' => '用戶沒有註冊FCM token']);
            return;
        }
        
        $result = $fcmService->sendNotification($tokens, $title, $body, $customData);
        echo json_encode($result);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => '資料庫錯誤: ' . $e->getMessage()]);
    }
}

/**
 * 獲取通知設定
 */
function getNotificationSettings() {
    $username = $_GET['username'] ?? '';
    
    if (!$username) {
        echo json_encode(['success' => false, 'error' => '缺少用戶名']);
        return;
    }
    
    $host = '100.79.58.120';
    $dbname = 'topics_good';
    $db_username = 'root';
    $db_password = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT * FROM user_notification_settings WHERE username = ?");
        $stmt->execute([$username]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($settings) {
            echo json_encode(['success' => true, 'settings' => $settings]);
        } else {
            // 創建預設設定
            $stmt = $pdo->prepare("INSERT INTO user_notification_settings (username) VALUES (?)");
            $stmt->execute([$username]);
            
            $stmt = $pdo->prepare("SELECT * FROM user_notification_settings WHERE username = ?");
            $stmt->execute([$username]);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'settings' => $settings]);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => '資料庫錯誤: ' . $e->getMessage()]);
    }
}

/**
 * 更新通知設定
 */
function updateNotificationSettings() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['username']) || !isset($data['settings'])) {
        echo json_encode(['success' => false, 'error' => '缺少必要參數']);
        return;
    }
    
    $username = $data['username'];
    $settings = $data['settings'];
    
    $host = '100.79.58.120';
    $dbname = 'topics_good';
    $db_username = 'root';
    $db_password = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("UPDATE user_notification_settings SET 
                              chat_notifications = ?,
                              group_notifications = ?,
                              system_notifications = ?,
                              quiet_hours_start = ?,
                              quiet_hours_end = ?,
                              updated_at = CURRENT_TIMESTAMP
                              WHERE username = ?");
        
        $stmt->execute([
            $settings['chat_notifications'] ? 1 : 0,
            $settings['group_notifications'] ? 1 : 0,
            $settings['system_notifications'] ? 1 : 0,
            $settings['quiet_hours_start'],
            $settings['quiet_hours_end'],
            $username
        ]);
        
        echo json_encode(['success' => true, 'message' => '通知設定已更新']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => '資料庫錯誤: ' . $e->getMessage()]);
    }
}
?>
