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
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['from']) || !isset($data['to']) || !isset($data['message'])) {
        echo json_encode(['error' => '無效的資料格式']);
        exit;
    }
    
    // 儲存私聊訊息
    $sql = "INSERT INTO private_chat_history (from_user, to_user, message, role) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['from'], $data['to'], $data['message'], $data['role'] ?? '用戶']);
    
    $message_id = $pdo->lastInsertId();
    
    // 發送郵件通知
    $email_sent = false;
    try {
        // 獲取接收者的郵箱和姓名
        $user_sql = "SELECT name, email FROM user WHERE username = ?";
        $user_stmt = $pdo->prepare($user_sql);
        $user_stmt->execute([$data['to']]);
        $receiver_info = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        // 獲取發送者的姓名
        $sender_stmt = $pdo->prepare($user_sql);
        $sender_stmt->execute([$data['from']]);
        $sender_info = $sender_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($receiver_info && $sender_info && !empty($receiver_info['email'])) {
            // 創建郵件通知服務實例
            $emailService = new EmailNotificationService();
            
            // 發送郵件通知
            $email_sent = $emailService->sendPrivateMessageNotification(
                $receiver_info['email'],
                $receiver_info['name'] ?: $data['to'],
                $sender_info['name'] ?: $data['from'],
                $data['message'],
                'http://100.79.58.120/frontend/chat/chat.php'
            );
            
            if ($email_sent) {
                error_log("私訊通知郵件發送成功: {$data['to']} -> {$receiver_info['email']}");
            } else {
                error_log("私訊通知郵件發送失敗: {$data['to']} -> {$receiver_info['email']}");
            }
        } else {
            error_log("無法獲取用戶資訊或郵箱地址: {$data['to']}");
        }
        
    } catch (Exception $email_error) {
        error_log("發送郵件通知時發生錯誤: " . $email_error->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => '私聊訊息已儲存',
        'id' => $message_id,
        'email_notification' => $email_sent ? 'sent' : 'failed'
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['error' => '儲存私聊訊息失敗: ' . $e->getMessage()]);
}
?>