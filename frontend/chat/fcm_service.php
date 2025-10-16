<?php
/**
 * Firebase Cloud Messaging 服務類
 */

class FCMService {
    private $serverKey;
    private $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
    private $pdo;
    
    public function __construct() {
        // 從環境變數或配置檔案讀取Firebase Server Key
        $this->serverKey = getenv('FIREBASE_SERVER_KEY') ?: 'your-firebase-server-key';
        
        // 資料庫連接
        $host = '100.79.58.120';
        $dbname = 'topics_good';
        $db_username = 'root';
        $db_password = '';
        
        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            error_log("FCM資料庫連接失敗: " . $e->getMessage());
            throw new Exception("資料庫連接失敗");
        }
    }
    
    /**
     * 發送推播通知
     */
    public function sendNotification($tokens, $title, $body, $data = []) {
        if (empty($tokens)) {
            return ['success' => false, 'error' => '沒有有效的FCM tokens'];
        }
        
        $fields = [
            'registration_ids' => is_array($tokens) ? $tokens : [$tokens],
            'notification' => [
                'title' => $title,
                'body' => $body,
                'icon' => '/assets/icon-192x192.png',
                'sound' => 'default',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ],
            'data' => array_merge($data, [
                'timestamp' => time(),
                'app_name' => '康寧大學聊天系統'
            ]),
            'priority' => 'high',
            'time_to_live' => 3600 // 1小時
        ];
        
        $headers = [
            'Authorization: key=' . $this->serverKey,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->fcmUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'error' => 'CURL錯誤: ' . $error];
        }
        
        if ($httpCode !== 200) {
            return ['success' => false, 'error' => 'HTTP錯誤: ' . $httpCode];
        }
        
        $response = json_decode($result, true);
        
        // 記錄推播結果
        $this->logNotification($tokens, $title, $body, $data, $response);
        
        return $response;
    }
    
    /**
     * 發送聊天訊息通知
     */
    public function sendChatNotification($toUser, $fromUser, $message, $messageId = null) {
        // 檢查用戶通知設定
        if (!$this->shouldSendNotification($toUser)) {
            return ['success' => false, 'error' => '用戶已關閉通知'];
        }
        
        // 獲取用戶的FCM tokens
        $tokens = $this->getUserFCMTokens($toUser);
        
        if (empty($tokens)) {
            return ['success' => false, 'error' => '用戶沒有註冊FCM token'];
        }
        
        $title = "📩 新訊息來自 " . $fromUser;
        $body = $this->truncateMessage($message, 100);
        
        $data = [
            'type' => 'chat_message',
            'from_user' => $fromUser,
            'to_user' => $toUser,
            'message' => $message,
            'message_id' => $messageId,
            'chat_url' => 'http://100.79.58.120/frontend/chat/chat.php',
            'action' => 'open_chat'
        ];
        
        return $this->sendNotification($tokens, $title, $body, $data);
    }
    
    /**
     * 發送群組訊息通知
     */
    public function sendGroupNotification($groupMembers, $fromUser, $groupName, $message, $groupId = null) {
        $activeTokens = [];
        
        foreach ($groupMembers as $member) {
            if ($member !== $fromUser && $this->shouldSendNotification($member)) {
                $tokens = $this->getUserFCMTokens($member);
                $activeTokens = array_merge($activeTokens, $tokens);
            }
        }
        
        if (empty($activeTokens)) {
            return ['success' => false, 'error' => '沒有活躍的群組成員'];
        }
        
        $title = "👥 群組訊息: " . $groupName;
        $body = $fromUser . ": " . $this->truncateMessage($message, 80);
        
        $data = [
            'type' => 'group_message',
            'from_user' => $fromUser,
            'group_name' => $groupName,
            'group_id' => $groupId,
            'message' => $message,
            'chat_url' => 'http://100.79.58.120/frontend/chat/chat.php',
            'action' => 'open_group_chat'
        ];
        
        return $this->sendNotification($activeTokens, $title, $body, $data);
    }
    
    /**
     * 註冊用戶FCM token
     */
    public function registerUserToken($username, $fcmToken, $deviceType = 'web', $deviceInfo = null) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO user_fcm_tokens (username, fcm_token, device_type, device_info) 
                                       VALUES (?, ?, ?, ?) 
                                       ON DUPLICATE KEY UPDATE 
                                       device_type = VALUES(device_type),
                                       device_info = VALUES(device_info),
                                       is_active = TRUE,
                                       updated_at = CURRENT_TIMESTAMP");
            
            $stmt->execute([$username, $fcmToken, $deviceType, $deviceInfo]);
            
            return ['success' => true, 'message' => 'FCM token註冊成功'];
            
        } catch (PDOException $e) {
            error_log("註冊FCM token失敗: " . $e->getMessage());
            return ['success' => false, 'error' => '註冊失敗: ' . $e->getMessage()];
        }
    }
    
    /**
     * 獲取用戶的FCM tokens
     */
    private function getUserFCMTokens($username) {
        try {
            $stmt = $this->pdo->prepare("SELECT fcm_token FROM user_fcm_tokens WHERE username = ? AND is_active = TRUE");
            $stmt->execute([$username]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
            
        } catch (PDOException $e) {
            error_log("獲取FCM tokens失敗: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 檢查是否應該發送通知
     */
    private function shouldSendNotification($username) {
        try {
            $stmt = $this->pdo->prepare("SELECT chat_notifications, quiet_hours_start, quiet_hours_end FROM user_notification_settings WHERE username = ?");
            $stmt->execute([$username]);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$settings || !$settings['chat_notifications']) {
                return false;
            }
            
            // 檢查安靜時間
            $currentTime = date('H:i:s');
            $quietStart = $settings['quiet_hours_start'];
            $quietEnd = $settings['quiet_hours_end'];
            
            if ($quietStart && $quietEnd) {
                if ($quietStart <= $quietEnd) {
                    // 同一天內的安靜時間
                    if ($currentTime >= $quietStart && $currentTime <= $quietEnd) {
                        return false;
                    }
                } else {
                    // 跨天的安靜時間
                    if ($currentTime >= $quietStart || $currentTime <= $quietEnd) {
                        return false;
                    }
                }
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log("檢查通知設定失敗: " . $e->getMessage());
            return true; // 預設允許發送
        }
    }
    
    /**
     * 記錄推播通知
     */
    private function logNotification($tokens, $title, $body, $data, $response) {
        try {
            $tokens = is_array($tokens) ? $tokens : [$tokens];
            
            foreach ($tokens as $token) {
                $stmt = $this->pdo->prepare("INSERT INTO push_notification_log 
                                           (username, fcm_token, title, body, data, status, response_data) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                $username = $this->getUsernameByToken($token);
                $status = isset($response['success']) && $response['success'] > 0 ? 'sent' : 'failed';
                
                $stmt->execute([
                    $username,
                    $token,
                    $title,
                    $body,
                    json_encode($data),
                    $status,
                    json_encode($response)
                ]);
            }
            
        } catch (PDOException $e) {
            error_log("記錄推播通知失敗: " . $e->getMessage());
        }
    }
    
    /**
     * 根據token獲取用戶名
     */
    private function getUsernameByToken($token) {
        try {
            $stmt = $this->pdo->prepare("SELECT username FROM user_fcm_tokens WHERE fcm_token = ? LIMIT 1");
            $stmt->execute([$token]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['username'] : 'unknown';
            
        } catch (PDOException $e) {
            return 'unknown';
        }
    }
    
    /**
     * 截斷訊息長度
     */
    private function truncateMessage($message, $length) {
        if (strlen($message) <= $length) {
            return $message;
        }
        return substr($message, 0, $length) . '...';
    }
    
    /**
     * 獲取推播統計
     */
    public function getNotificationStats($username = null) {
        try {
            $whereClause = $username ? "WHERE username = ?" : "";
            $params = $username ? [$username] : [];
            
            $stmt = $this->pdo->prepare("SELECT 
                                        COUNT(*) as total,
                                        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                                        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
                                        FROM push_notification_log $whereClause");
            $stmt->execute($params);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("獲取推播統計失敗: " . $e->getMessage());
            return ['total' => 0, 'sent' => 0, 'failed' => 0, 'pending' => 0];
        }
    }
}
?>














