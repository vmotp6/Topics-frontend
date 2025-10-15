<?php
/**
 * Firebase Cloud Messaging 整合範例
 * 用於即時推播通知
 */

class FirebaseNotificationService {
    private $serverKey;
    private $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
    
    public function __construct() {
        // 從環境變數或配置檔案讀取Firebase Server Key
        $this->serverKey = getenv('FIREBASE_SERVER_KEY') ?: 'your-firebase-server-key';
    }
    
    /**
     * 發送推播通知
     */
    public function sendNotification($tokens, $title, $body, $data = []) {
        $fields = [
            'registration_ids' => is_array($tokens) ? $tokens : [$tokens],
            'notification' => [
                'title' => $title,
                'body' => $body,
                'icon' => 'ic_notification',
                'sound' => 'default',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ],
            'data' => $data,
            'priority' => 'high'
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
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($result, true);
    }
    
    /**
     * 發送聊天訊息通知
     */
    public function sendChatNotification($toUser, $fromUser, $message, $chatUrl = null) {
        // 從資料庫獲取用戶的FCM token
        $tokens = $this->getUserFCMTokens($toUser);
        
        if (empty($tokens)) {
            return false;
        }
        
        $title = "📩 新訊息來自 " . $fromUser;
        $body = substr($message, 0, 100) . (strlen($message) > 100 ? '...' : '');
        
        $data = [
            'type' => 'chat_message',
            'from_user' => $fromUser,
            'to_user' => $toUser,
            'message' => $message,
            'chat_url' => $chatUrl ?: 'http://100.79.58.120/frontend/chat/chat.php',
            'timestamp' => time()
        ];
        
        return $this->sendNotification($tokens, $title, $body, $data);
    }
    
    /**
     * 獲取用戶的FCM tokens
     */
    private function getUserFCMTokens($username) {
        // 這裡需要創建一個表來儲存用戶的FCM tokens
        // CREATE TABLE user_fcm_tokens (
        //     id INT AUTO_INCREMENT PRIMARY KEY,
        //     username VARCHAR(255) NOT NULL,
        //     fcm_token VARCHAR(500) NOT NULL,
        //     device_type VARCHAR(50),
        //     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        //     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        //     INDEX idx_username (username)
        // );
        
        $host = '100.79.58.120';
        $dbname = 'topics_good';
        $db_username = 'root';
        $db_password = '';
        
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->prepare("SELECT fcm_token FROM user_fcm_tokens WHERE username = ?");
            $stmt->execute([$username]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
            
        } catch(PDOException $e) {
            error_log("獲取FCM tokens失敗: " . $e->getMessage());
            return [];
        }
    }
}

// 使用範例
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_notification'])) {
    $firebase = new FirebaseNotificationService();
    
    $result = $firebase->sendChatNotification(
        $_POST['to_user'],
        $_POST['from_user'],
        $_POST['message']
    );
    
    echo "<h2>推播通知測試結果</h2>";
    if ($result && isset($result['success']) && $result['success'] > 0) {
        echo "<p style='color: green;'>✅ 推播通知發送成功！</p>";
        echo "<p>成功發送: {$result['success']} 個設備</p>";
        if (isset($result['failure']) && $result['failure'] > 0) {
            echo "<p>失敗: {$result['failure']} 個設備</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ 推播通知發送失敗</p>";
        echo "<pre>" . print_r($result, true) . "</pre>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Firebase 推播通知測試</title>
    <meta charset="UTF-8">
</head>
<body>
    <h1>Firebase Cloud Messaging 整合測試</h1>
    
    <h2>設置說明</h2>
    <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 20px 0;">
        <h3>1. Firebase 設置</h3>
        <ol>
            <li>前往 <a href="https://console.firebase.google.com/" target="_blank">Firebase Console</a></li>
            <li>創建新專案或選擇現有專案</li>
            <li>啟用 Cloud Messaging</li>
            <li>獲取 Server Key</li>
            <li>設置環境變數 FIREBASE_SERVER_KEY</li>
        </ol>
        
        <h3>2. 資料庫設置</h3>
        <p>執行以下SQL創建FCM tokens表：</p>
        <pre style="background: #e9ecef; padding: 10px; border-radius: 4px;">
CREATE TABLE user_fcm_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    fcm_token VARCHAR(500) NOT NULL,
    device_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username)
);
        </pre>
    </div>
    
    <h2>測試推播通知</h2>
    <form method="POST" style="background: #f8f9fa; padding: 20px; border-radius: 8px; max-width: 600px;">
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">接收者用戶名：</label>
            <input type="text" name="to_user" required 
                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                   placeholder="接收者的用戶名">
        </div>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">發送者用戶名：</label>
            <input type="text" name="from_user" required 
                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                   placeholder="發送者的用戶名">
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">測試訊息：</label>
            <textarea name="message" rows="4" required
                      style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                      placeholder="輸入測試訊息內容...">這是一條測試推播通知訊息！</textarea>
        </div>
        
        <button type="submit" name="test_notification" 
                style="background: #4285f4; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
            📱 發送推播通知
        </button>
    </form>
    
    <h2>其他 Google Cloud 服務整合建議</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;">
        
        <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
            <h3>🤖 Vertex AI</h3>
            <p>AI聊天助手，24/7回答學生問題</p>
            <ul>
                <li>智能問答</li>
                <li>學習建議</li>
                <li>多語言支援</li>
            </ul>
        </div>
        
        <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
            <h3>🌐 Translation API</h3>
            <p>即時翻譯功能</p>
            <ul>
                <li>中英文翻譯</li>
                <li>自動語言檢測</li>
                <li>多語言聊天</li>
            </ul>
        </div>
        
        <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
            <h3>📊 BigQuery</h3>
            <p>學習數據分析</p>
            <ul>
                <li>參與度分析</li>
                <li>學習進度追蹤</li>
                <li>生成報告</li>
            </ul>
        </div>
        
        <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
            <h3>💾 Cloud Storage</h3>
            <p>檔案分享功能</p>
            <ul>
                <li>作業上傳</li>
                <li>圖片分享</li>
                <li>自動備份</li>
            </ul>
        </div>
        
    </div>
    
    <p><a href="chat.php">← 返回聊天室</a></p>
</body>
</html>













