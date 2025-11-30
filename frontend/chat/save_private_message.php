<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => '只支援POST請求']);
    exit;
}

// 引入郵件通知服務
require_once '../../backend/services/email_notification.php';

// 資料庫連接
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // 添加調試日誌
    error_log("收到消息保存請求: " . json_encode($data));
    
    if (!$data || !isset($data['from']) || !isset($data['to']) || !isset($data['message'])) {
        error_log("消息格式錯誤: " . json_encode($data));
        echo json_encode(['error' => '無效的資料格式']);
        exit;
    }
    
    // 根據實際表結構：from_user 和 to_user 是 INT 類型（外鍵到 user.id），沒有 role 欄位
    // 先將 username 轉換為 user.id
    $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
    $stmt->execute([$data['from']]);
    $fromUser = $stmt->fetch(PDO::FETCH_ASSOC);
    $fromUserId = $fromUser ? $fromUser['id'] : null;
    
    $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
    $stmt->execute([$data['to']]);
    $toUser = $stmt->fetch(PDO::FETCH_ASSOC);
    $toUserId = $toUser ? $toUser['id'] : null;
    
    if (!$fromUserId || !$toUserId) {
        error_log("找不到用戶: from={$data['from']} (id={$fromUserId}), to={$data['to']} (id={$toUserId})");
        echo json_encode(['error' => '找不到指定的用戶', 'debug' => [
            'from' => $data['from'],
            'fromUserId' => $fromUserId,
            'to' => $data['to'],
            'toUserId' => $toUserId
        ]]);
        exit;
    }
    
    error_log("用戶ID轉換成功: from={$data['from']} -> {$fromUserId}, to={$data['to']} -> {$toUserId}");
    
    // 檢查表是否有 from_user_id, to_user_id 或 from_user, to_user
    $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                        WHERE TABLE_SCHEMA = 'topics_good' 
                        AND TABLE_NAME = 'private_chat_history' 
                        AND COLUMN_NAME IN ('from_user', 'to_user', 'from_user_id', 'to_user_id')");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasFromUserId = in_array('from_user_id', $columns);
    $hasFromUser = in_array('from_user', $columns);
    
    // 插入訊息（不包含 role 欄位）
    if ($hasFromUserId) {
        // 使用 from_user_id, to_user_id
        $sql = "INSERT INTO private_chat_history (from_user_id, to_user_id, message) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fromUserId, $toUserId, $data['message']]);
        error_log("使用 from_user_id/to_user_id 保存消息: from_user_id={$fromUserId}, to_user_id={$toUserId}");
    } elseif ($hasFromUser) {
        // 使用 from_user, to_user (INT 類型)
        $sql = "INSERT INTO private_chat_history (from_user, to_user, message) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fromUserId, $toUserId, $data['message']]);
        error_log("使用 from_user/to_user (INT) 保存消息: from_user={$fromUserId}, to_user={$toUserId}");
    } else {
        error_log("表結構異常: 找不到用戶欄位");
        echo json_encode(['error' => 'private_chat_history 表結構異常，找不到用戶欄位']);
        exit;
    }
    
    $message_id = $pdo->lastInsertId();
    error_log("消息保存成功，ID: {$message_id}");
    
    // 發送FCM推播通知
    $notification_sent = false;
    try {
        // 引入FCM服務
        require_once 'fcm_service.php';
        $fcmService = new FCMService();
        
        // 發送FCM推播通知
        $fcmResult = $fcmService->sendChatNotification(
            $data['to'],      // 接收者
            $data['from'],    // 發送者
            $data['message'], // 訊息內容
            $message_id       // 訊息ID
        );
        
        if ($fcmResult && isset($fcmResult['success']) && $fcmResult['success'] > 0) {
            $notification_sent = true;
            error_log("FCM推播通知發送成功: 發送者={$data['from']}, 接收者={$data['to']}");
        } else {
            error_log("FCM推播通知發送失敗: " . json_encode($fcmResult));
        }
        
    } catch (Exception $notification_error) {
        error_log("發送FCM推播通知時發生錯誤: " . $notification_error->getMessage());
    }
    
    // 返回訊息詳情（用於前端立即顯示）
    $returnData = [
        'success' => true,
        'message' => '私聊訊息已儲存',
        'id' => (int)$message_id,
        'fcm_notification' => $notification_sent ? 'sent' : 'failed'
    ];
    
    // 返回完整的訊息資料（使用 JOIN 獲取 username）
    $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                        WHERE TABLE_SCHEMA = 'topics_good' 
                        AND TABLE_NAME = 'private_chat_history' 
                        AND COLUMN_NAME IN ('from_user_id', 'to_user_id', 'from_user', 'to_user')");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasFromUserId = in_array('from_user_id', $columns);
    
    if ($hasFromUserId) {
        $stmt = $pdo->prepare("SELECT pch.*, u1.username as from_username, u2.username as to_username 
                              FROM private_chat_history pch
                              LEFT JOIN user u1 ON pch.from_user_id = u1.id
                              LEFT JOIN user u2 ON pch.to_user_id = u2.id
                              WHERE pch.id = ?");
    } else {
        $stmt = $pdo->prepare("SELECT pch.*, u1.username as from_username, u2.username as to_username 
                              FROM private_chat_history pch
                              LEFT JOIN user u1 ON pch.from_user = u1.id
                              LEFT JOIN user u2 ON pch.to_user = u2.id
                              WHERE pch.id = ?");
    }
    $stmt->execute([$message_id]);
    $savedMessage = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($savedMessage) {
        $returnData['saved_message'] = [
            'id' => (int)$savedMessage['id'],
            'from_user' => $savedMessage['from_username'],
            'to_user' => $savedMessage['to_username'],
            'message' => $savedMessage['message'],
            'timestamp' => $savedMessage['timestamp']
        ];
    }
    
    echo json_encode($returnData);
    
} catch(PDOException $e) {
    echo json_encode(['error' => '儲存私聊訊息失敗: ' . $e->getMessage()]);
}
?>