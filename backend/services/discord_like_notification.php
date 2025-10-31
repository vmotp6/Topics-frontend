<?php
/**
 * Discord-like 長時間未查看通知系統
 * 當用戶長時間沒有查看聊天系統時，發送Gmail通知提醒
 */

require_once __DIR__ . '/../config/email_config.php';
require_once __DIR__ . '/email_notification.php';

class DiscordLikeNotificationService {
    private $db;
    private $emailService;
    private $config;
    
    // 通知設定
    private $notificationIntervals = [
        '1_hour' => 3600,      // 1小時
        '6_hours' => 21600,    // 6小時
        '24_hours' => 86400,   // 24小時
        '3_days' => 259200,    // 3天
        '1_week' => 604800     // 1週
    ];
    
    public function __construct() {
        $this->connectDatabase();
        $this->emailService = new EmailNotificationService();
        $this->config = getEmailConfig();
    }
    
    private function connectDatabase() {
        $host = 'localhost';
        $dbname = 'topics_good';
        $username = 'root';
        $password = '';
        
        try {
            $this->db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            error_log("資料庫連接失敗: " . $e->getMessage());
            throw new Exception("資料庫連接失敗");
        }
    }
    
    /**
     * 更新用戶活動時間
     */
    public function updateUserActivity($username, $activityType = 'chat_check') {
        try {
            $sql = "INSERT INTO user_activity (username, last_chat_check) 
                    VALUES (?, NOW()) 
                    ON DUPLICATE KEY UPDATE 
                    last_chat_check = NOW(), 
                    last_seen = NOW()";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username]);
            
            return true;
        } catch(PDOException $e) {
            error_log("更新用戶活動失敗: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 檢查需要發送通知的用戶
     */
    public function checkUsersForNotification() {
        $notificationsSent = 0;
        
        foreach ($this->notificationIntervals as $intervalName => $seconds) {
            $users = $this->getUsersNeedingNotification($seconds, $intervalName);
            
            foreach ($users as $user) {
                if ($this->shouldSendNotification($user['username'], $intervalName)) {
                    $this->sendDiscordLikeNotification($user, $intervalName);
                    $notificationsSent++;
                }
            }
        }
        
        return $notificationsSent;
    }
    
    /**
     * 獲取需要通知的用戶列表
     */
    private function getUsersNeedingNotification($seconds, $intervalName) {
        try {
            $sql = "SELECT DISTINCT u.username, u.email, u.name,
                           ua.last_chat_check,
                           COUNT(pch.id) as unread_count,
                           MAX(pch.timestamp) as latest_message_time
                    FROM user u
                    LEFT JOIN user_activity ua ON u.username = ua.username
                    LEFT JOIN private_chat_history pch ON u.username = pch.to_user 
                        AND pch.timestamp > COALESCE(ua.last_chat_check, '1970-01-01')
                    WHERE u.email IS NOT NULL 
                        AND u.email != ''
                        AND (ua.last_chat_check IS NULL 
                             OR ua.last_chat_check < DATE_SUB(NOW(), INTERVAL ? SECOND))
                    GROUP BY u.username, u.email, u.name, ua.last_chat_check
                    HAVING unread_count > 0";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$seconds]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("查詢需要通知的用戶失敗: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 檢查是否應該發送通知（避免重複發送）
     */
    private function shouldSendNotification($username, $intervalName) {
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM notification_sent_log 
                    WHERE username = ? 
                        AND notification_type = ? 
                        AND sent_at > DATE_SUB(NOW(), INTERVAL 1 DAY)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username, $intervalName]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] == 0;
        } catch(PDOException $e) {
            error_log("檢查通知發送記錄失敗: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 發送Discord風格的通知
     */
    private function sendDiscordLikeNotification($user, $intervalName) {
        try {
            $unreadMessages = $this->getUnreadMessages($user['username']);
            $timeAgo = $this->getTimeAgoText($intervalName);
            
            $subject = "🔔 您有未讀消息 - 康寧大學聊天系統";
            $body = $this->generateDiscordLikeEmailBody($user, $unreadMessages, $timeAgo);
            
            $success = $this->emailService->sendDiscordLikeNotification(
                $user['email'],
                $user['name'],
                $subject,
                $body
            );
            
            if ($success) {
                $this->logNotificationSent($user['username'], $intervalName);
                $this->createUnreadNotification($user['username'], $unreadMessages);
            }
            
            return $success;
        } catch(Exception $e) {
            error_log("發送通知失敗: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 獲取用戶的未讀消息
     */
    private function getUnreadMessages($username) {
        try {
            $sql = "SELECT pch.from_user, pch.message, pch.timestamp,
                           u.name as sender_name
                    FROM private_chat_history pch
                    JOIN user u ON pch.from_user = u.username
                    LEFT JOIN user_activity ua ON pch.to_user = ua.username
                    WHERE pch.to_user = ?
                        AND pch.timestamp > COALESCE(ua.last_chat_check, '1970-01-01')
                    ORDER BY pch.timestamp DESC
                    LIMIT 5";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("獲取未讀消息失敗: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 生成Discord風格的郵件內容
     */
    private function generateDiscordLikeEmailBody($user, $unreadMessages, $timeAgo) {
        $chatUrl = $this->config['chat_url'];
        $unreadCount = count($unreadMessages);
        
        $messagesHtml = '';
        foreach ($unreadMessages as $message) {
            $time = date('H:i', strtotime($message['timestamp']));
            $preview = mb_substr($message['message'], 0, 100) . (mb_strlen($message['message']) > 100 ? '...' : '');
            
            $messagesHtml .= "
                <div class='message-item'>
                    <div class='message-header'>
                        <strong>{$message['sender_name']}</strong>
                        <span class='time'>{$time}</span>
                    </div>
                    <div class='message-content'>{$preview}</div>
                </div>";
        }
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; background: #f8f9fa; }
                .header { background: linear-gradient(135deg, #5865F2 0%, #7289DA 100%); color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; }
                .header p { margin: 10px 0 0 0; opacity: 0.9; }
                .content { background: white; padding: 30px; }
                .notification-box { background: #f0f8ff; border-left: 4px solid #5865F2; padding: 20px; margin: 20px 0; border-radius: 0 8px 8px 0; }
                .unread-count { background: #ff4757; color: white; padding: 5px 10px; border-radius: 20px; font-size: 14px; font-weight: bold; }
                .message-item { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 3px solid #5865F2; }
                .message-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
                .message-header strong { color: #5865F2; }
                .time { color: #666; font-size: 12px; }
                .message-content { color: #333; }
                .cta-button { display: inline-block; background: #5865F2; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 20px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px; }
                .discord-style { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
            </style>
        </head>
        <body>
            <div class='container discord-style'>
                <div class='header'>
                    <h1>🔔 您有未讀消息</h1>
                    <p>康寧大學聊天系統</p>
                </div>
                
                <div class='content'>
                    <h2>親愛的 {$user['name']}，</h2>
                    
                    <div class='notification-box'>
                        <p>您已經 <strong>{$timeAgo}</strong> 沒有查看聊天系統了！</p>
                        <p>您有 <span class='unread-count'>{$unreadCount}</span> 條未讀消息等待您的回覆。</p>
                    </div>
                    
                    <h3>📨 未讀消息預覽：</h3>
                    {$messagesHtml}
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$chatUrl}' class='cta-button'>立即查看消息</a>
                    </div>
                    
                    <p style='color: #666; font-size: 14px;'>
                        如果您不希望收到這些通知，請在聊天系統中調整您的通知設定。
                    </p>
                </div>
                
                <div class='footer'>
                    <p>此郵件由康寧大學聊天系統自動發送</p>
                    <p>如有問題，請聯繫系統管理員</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * 獲取時間間隔的文字描述
     */
    private function getTimeAgoText($intervalName) {
        $timeTexts = [
            '1_hour' => '1小時',
            '6_hours' => '6小時',
            '24_hours' => '1天',
            '3_days' => '3天',
            '1_week' => '1週'
        ];
        
        return $timeTexts[$intervalName] ?? $intervalName;
    }
    
    /**
     * 記錄通知發送
     */
    private function logNotificationSent($username, $intervalName) {
        try {
            $sql = "INSERT INTO notification_sent_log (username, notification_type, email_sent) 
                    VALUES (?, ?, 1)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username, $intervalName]);
        } catch(PDOException $e) {
            error_log("記錄通知發送失敗: " . $e->getMessage());
        }
    }
    
    /**
     * 創建未讀通知記錄
     */
    private function createUnreadNotification($username, $unreadMessages) {
        try {
            foreach ($unreadMessages as $message) {
                $sql = "INSERT INTO unread_notifications 
                        (username, notification_type, sender_username, message_preview, chat_url) 
                        VALUES (?, 'private_message', ?, ?, ?)";
                
                $stmt = $this->db->prepare($sql);
                $preview = mb_substr($message['message'], 0, 200);
                $chatUrl = $this->config['chat_url'];
                
                $stmt->execute([$username, $message['from_user'], $preview, $chatUrl]);
            }
        } catch(PDOException $e) {
            error_log("創建未讀通知記錄失敗: " . $e->db->getMessage());
        }
    }
}

// 如果直接執行此腳本，則運行檢查
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $service = new DiscordLikeNotificationService();
        $notificationsSent = $service->checkUsersForNotification();
        
        echo "檢查完成，共發送 {$notificationsSent} 個通知\n";
        error_log("Discord-like通知檢查完成，共發送 {$notificationsSent} 個通知");
    } catch(Exception $e) {
        echo "錯誤: " . $e->getMessage() . "\n";
        error_log("Discord-like通知檢查失敗: " . $e->getMessage());
    }
}
?>
