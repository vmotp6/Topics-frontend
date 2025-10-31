<?php
/**
 * Gmail 郵件通知服務
 * 用於發送私訊通知郵件
 */

class EmailNotificationService {
    private $smtp_server;
    private $smtp_port;
    private $sender_email;
    private $sender_password;
    private $sender_name;
    private $base_url;
    
    public function __construct() {
        // Gmail SMTP 設定
        $this->smtp_server = "smtp.gmail.com";
        $this->smtp_port = 587;
        
        // 從環境變數或配置檔案讀取郵件設定
        $this->sender_email = getenv('GMAIL_SENDER_EMAIL') ?: 'your-email@gmail.com';
        $this->sender_password = getenv('GMAIL_APP_PASSWORD') ?: 'your-app-password';
        $this->sender_name = getenv('GMAIL_SENDER_NAME') ?: '康寧大學聊天系統';
        
        // 網站基礎URL
        $this->base_url = getenv('BASE_URL') ?: 'http://localhost';
    }
    
    /**
     * 發送私訊通知郵件
     * 
     * @param string $to_email 接收者郵箱
     * @param string $to_name 接收者姓名
     * @param string $from_name 發送者姓名
     * @param string $message_content 訊息內容
     * @param string $chat_url 聊天頁面URL（可選）
     * @return bool 發送是否成功
     */
    public function sendPrivateMessageNotification($to_email, $to_name, $from_name, $message_content, $chat_url = null) {
        try {
            // 如果沒有提供聊天URL，使用預設的聊天頁面
            if (!$chat_url) {
                $chat_url = $this->base_url . '/frontend/chat/chat.php';
            }
            
            // 郵件主題
            $subject = "📩 您有新的私訊來自 " . $from_name;
            
            // 創建HTML內容
            $html_content = $this->createNotificationHTML($to_name, $from_name, $message_content, $chat_url);
            
            // 創建純文字內容
            $text_content = $this->createNotificationText($to_name, $from_name, $message_content, $chat_url);
            
            // 設置郵件標頭
            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: multipart/alternative; boundary="boundary_' . uniqid() . '"',
                'From: ' . $this->sender_name . ' <' . $this->sender_email . '>',
                'Reply-To: ' . $this->sender_email,
                'X-Mailer: PHP/' . phpversion()
            ];
            
            // 創建多部分郵件內容
            $boundary = 'boundary_' . uniqid();
            $headers[1] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
            
            $body = "--$boundary\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $body .= $text_content . "\r\n\r\n";
            
            $body .= "--$boundary\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $body .= $html_content . "\r\n\r\n";
            
            $body .= "--$boundary--\r\n";
            
            // 發送郵件
            $success = mail($to_email, $subject, $body, implode("\r\n", $headers));
            
            if ($success) {
                error_log("郵件發送成功: $to_email");
            } else {
                error_log("郵件發送失敗: $to_email");
            }
            
            return $success;
            
        } catch (Exception $e) {
            error_log("發送郵件時發生錯誤: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 創建HTML格式的郵件內容
     */
    private function createNotificationHTML($to_name, $from_name, $message_content, $chat_url) {
        $current_time = date('Y-m-d H:i:s');
        
        // 截取訊息內容（避免郵件過長）
        $display_message = strlen($message_content) > 200 ? 
            substr($message_content, 0, 200) . '...' : $message_content;
        
        // 轉義HTML特殊字符
        $display_message = htmlspecialchars($display_message, ENT_QUOTES, 'UTF-8');
        $from_name = htmlspecialchars($from_name, ENT_QUOTES, 'UTF-8');
        $to_name = htmlspecialchars($to_name, ENT_QUOTES, 'UTF-8');
        
        return "
        <!DOCTYPE html>
        <html lang='zh-TW'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>新私訊通知</title>
            <style>
                body {
                    font-family: 'Microsoft JhengHei', Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                    background-color: #f5f5f5;
                }
                .container {
                    background-color: white;
                    border-radius: 10px;
                    padding: 30px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .header {
                    text-align: center;
                    border-bottom: 2px solid #4CAF50;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                .header h1 {
                    color: #4CAF50;
                    margin: 0;
                    font-size: 24px;
                }
                .message-box {
                    background-color: #f8f9fa;
                    border-left: 4px solid #4CAF50;
                    padding: 20px;
                    margin: 20px 0;
                    border-radius: 5px;
                }
                .sender-info {
                    font-weight: bold;
                    color: #4CAF50;
                    margin-bottom: 10px;
                }
                .message-content {
                    background-color: white;
                    padding: 15px;
                    border-radius: 5px;
                    border: 1px solid #e0e0e0;
                    white-space: pre-wrap;
                    word-wrap: break-word;
                }
                .action-button {
                    display: inline-block;
                    background-color: #4CAF50;
                    color: white;
                    padding: 12px 30px;
                    text-decoration: none;
                    border-radius: 5px;
                    margin: 20px 0;
                    font-weight: bold;
                    text-align: center;
                }
                .action-button:hover {
                    background-color: #45a049;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #e0e0e0;
                    color: #666;
                    font-size: 12px;
                }
                .timestamp {
                    color: #888;
                    font-size: 12px;
                    text-align: right;
                    margin-top: 10px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📩 您有新的私訊</h1>
                </div>
                
                <p>親愛的 <strong>$to_name</strong>，</p>
                
                <p>您收到了一條新的私訊：</p>
                
                <div class='message-box'>
                    <div class='sender-info'>來自：$from_name</div>
                    <div class='message-content'>$display_message</div>
                    <div class='timestamp'>發送時間：$current_time</div>
                </div>
                
                <div style='text-align: center;'>
                    <a href='$chat_url' class='action-button'>立即回覆</a>
                </div>
                
                <div class='footer'>
                    <p>此郵件由康寧大學聊天系統自動發送</p>
                    <p>如果您不想收到此類通知，請聯繫系統管理員</p>
                    <p>© 2025 康寧大學</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * 發送Discord風格的通知郵件
     * 
     * @param string $to_email 接收者郵箱
     * @param string $to_name 接收者姓名
     * @param string $subject 郵件主題
     * @param string $html_body HTML郵件內容
     * @return bool 發送是否成功
     */
    public function sendDiscordLikeNotification($to_email, $to_name, $subject, $html_body) {
        try {
            // 設置郵件標頭
            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $this->sender_name . ' <' . $this->sender_email . '>',
                'Reply-To: ' . $this->sender_email,
                'X-Mailer: PHP/' . phpversion()
            ];
            
            // 發送郵件
            $success = mail($to_email, $subject, $html_body, implode("\r\n", $headers));
            
            if ($success) {
                error_log("Discord風格通知郵件發送成功: $to_email");
            } else {
                error_log("Discord風格通知郵件發送失敗: $to_email");
            }
            
            return $success;
            
        } catch (Exception $e) {
            error_log("發送Discord風格通知郵件時發生錯誤: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 創建純文字格式的郵件內容
     */
    private function createNotificationText($to_name, $from_name, $message_content, $chat_url) {
        $current_time = date('Y-m-d H:i:s');
        
        // 截取訊息內容
        $display_message = strlen($message_content) > 200 ? 
            substr($message_content, 0, 200) . '...' : $message_content;
        
        return "
親愛的 $to_name,

您收到了一條新的私訊：

發送者：$from_name
發送時間：$current_time

訊息內容：
$display_message

請點擊以下連結回覆：
$chat_url

---
此郵件由康寧大學聊天系統自動發送
如果您不想收到此類通知，請聯繫系統管理員
© 2025 康寧大學
        ";
    }
}

// 如果直接執行此檔案，進行測試
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $emailService = new EmailNotificationService();
    echo "📧 郵件通知服務已載入\n";
    echo "如需測試，請設定Gmail配置後調用測試方法\n";
}
?>