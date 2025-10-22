<?php
/**
 * 簡化的FCM API
 * 用於基本的通知功能
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // 獲取動作參數
    $action = '';
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $action = $data['action'] ?? '';
    }
    
    // 調試信息
    error_log('FCM API調用: ' . json_encode([
        'method' => $_SERVER['REQUEST_METHOD'],
        'action' => $action,
        'get_params' => $_GET,
        'post_data' => file_get_contents('php://input'),
        'timestamp' => date('Y-m-d H:i:s')
    ]));
    
    if (empty($action)) {
        echo json_encode(['success' => false, 'error' => '無效的動作: ' . $action]);
        exit;
    }
    
    switch ($action) {
        case 'register_token':
            registerToken();
            break;
        case 'get_notification_settings':
            getNotificationSettings();
            break;
        case 'update_notification_settings':
            updateNotificationSettings();
            break;
        default:
            echo json_encode(['success' => false, 'error' => '無效的動作: ' . $action]);
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
function registerToken() {
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
    
    // 記錄token註冊
    error_log('FCM Token註冊: ' . json_encode([
        'timestamp' => date('Y-m-d H:i:s'),
        'username' => $username,
        'fcm_token' => $fcmToken,
        'device_type' => $deviceType,
        'device_info' => $deviceInfo
    ]));
    
    echo json_encode([
        'success' => true,
        'message' => 'Token註冊成功',
        'username' => $username
    ]);
}

/**
 * 獲取通知設定
 */
function getNotificationSettings() {
    $username = $_GET['username'] ?? '';
    
    if (empty($username)) {
        echo json_encode(['success' => false, 'error' => '缺少用戶名']);
        return;
    }
    
    // 返回預設設定
    echo json_encode([
        'success' => true,
        'settings' => [
            'chat_notifications' => true,
            'group_notifications' => true,
            'system_notifications' => true,
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00'
        ]
    ]);
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
    
    // 記錄設定更新
    error_log('通知設定更新: ' . json_encode([
        'timestamp' => date('Y-m-d H:i:s'),
        'username' => $username,
        'settings' => $settings
    ]));
    
    echo json_encode([
        'success' => true,
        'message' => '設定已更新'
    ]);
}
?>
