<?php
/**
 * 快速測試Gmail郵件發送
 */

echo "<h1>快速Gmail測試</h1>";

// 處理測試郵件發送
if (isset($_POST['send_test'])) {
    $to_email = $_POST['to_email'];
    $to_name = $_POST['to_name'] ?: '測試用戶';
    $from_name = $_POST['from_name'] ?: '系統測試';
    $message = $_POST['message'] ?: '這是一封測試郵件，用於驗證Gmail通知功能。';
    
    echo "<h2>發送測試郵件...</h2>";
    echo "<p>發送到: " . htmlspecialchars($to_email) . "</p>";
    echo "<p>接收者: " . htmlspecialchars($to_name) . "</p>";
    echo "<p>發送者: " . htmlspecialchars($from_name) . "</p>";
    echo "<p>訊息: " . htmlspecialchars($message) . "</p>";
    
    // 創建HTML郵件內容
    $subject = "📩 測試郵件 - " . $from_name;
    
    $html_content = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
            .message-box { background: white; padding: 15px; border-left: 4px solid #4CAF50; margin: 15px 0; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>📩 康寧大學聊天系統</h1>
                <p>您有新的私訊</p>
            </div>
            <div class='content'>
                <h2>親愛的 " . htmlspecialchars($to_name) . "，</h2>
                <p>您收到來自 <strong>" . htmlspecialchars($from_name) . "</strong> 的新訊息：</p>
                <div class='message-box'>
                    <p><strong>訊息內容：</strong></p>
                    <p>" . nl2br(htmlspecialchars($message)) . "</p>
                </div>
                <p style='text-align: center; margin: 20px 0;'>
                    <a href='http://100.79.58.120/frontend/chat/chat.php' 
                       style='background: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;'>
                        📱 前往聊天室
                    </a>
                </p>
                <div class='footer'>
                    <p>此郵件由康寧大學聊天系統自動發送</p>
                    <p>發送時間: " . date('Y-m-d H:i:s') . "</p>
                </div>
            </div>
        </div>
    </body>
    </html>";
    
    // 純文字版本
    $text_content = "
康寧大學聊天系統 - 新私訊通知

親愛的 " . $to_name . "，

您收到來自 " . $from_name . " 的新訊息：

訊息內容：
" . $message . "

前往聊天室: http://100.79.58.120/frontend/chat/chat.php

此郵件由康寧大學聊天系統自動發送
發送時間: " . date('Y-m-d H:i:s');
    
    // 設置郵件標頭
    $boundary = 'boundary_' . uniqid();
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        'From: 康寧大學聊天系統 <noreply@kangning.edu.tw>',
        'Reply-To: noreply@kangning.edu.tw',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // 創建多部分郵件內容
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
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
        echo "<h3>✅ 郵件發送成功！</h3>";
        echo "<p>測試郵件已發送到 <strong>" . htmlspecialchars($to_email) . "</strong></p>";
        echo "<p>請檢查您的郵箱（包括垃圾郵件夾）</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
        echo "<h3>❌ 郵件發送失敗</h3>";
        echo "<p>可能的原因：</p>";
        echo "<ul>";
        echo "<li>PHP mail() 函數未正確配置</li>";
        echo "<li>SMTP 服務器設置問題</li>";
        echo "<li>郵箱地址無效</li>";
        echo "<li>服務器防火牆阻擋</li>";
        echo "</ul>";
        echo "</div>";
    }
    
    echo "<hr>";
}

// 顯示測試表單
?>

<h2>發送測試郵件</h2>
<form method="POST" style="background: #f8f9fa; padding: 20px; border-radius: 8px; max-width: 600px;">
    <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">接收者郵箱地址：</label>
        <input type="email" name="to_email" required 
               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
               placeholder="example@gmail.com">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">接收者姓名：</label>
        <input type="text" name="to_name" 
               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
               placeholder="測試用戶" value="測試用戶">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">發送者姓名：</label>
        <input type="text" name="from_name" 
               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
               placeholder="系統測試" value="系統測試">
    </div>
    
    <div style="margin-bottom: 20px;">
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">測試訊息：</label>
        <textarea name="message" rows="4" 
                  style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                  placeholder="輸入測試訊息內容...">這是一封測試郵件，用於驗證Gmail通知功能是否正常工作。如果您收到這封郵件，表示系統配置正確！</textarea>
    </div>
    
    <button type="submit" name="send_test" 
            style="background: #4CAF50; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
        📧 發送測試郵件
    </button>
</form>

<h2>系統檢查</h2>
<div style="background: #e9ecef; padding: 15px; border-radius: 4px; margin: 20px 0;">
    <h3>PHP郵件配置狀態：</h3>
    <ul>
        <li><strong>mail()函數：</strong> <?php echo function_exists('mail') ? '✅ 可用' : '❌ 不可用'; ?></li>
        <li><strong>SMTP：</strong> <?php echo ini_get('SMTP') ?: '未設置'; ?></li>
        <li><strong>smtp_port：</strong> <?php echo ini_get('smtp_port') ?: '未設置'; ?></li>
        <li><strong>sendmail_from：</strong> <?php echo ini_get('sendmail_from') ?: '未設置'; ?></li>
    </ul>
</div>

<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 4px; margin: 20px 0;">
    <h3>💡 測試建議：</h3>
    <ol>
        <li>使用您自己的Gmail地址進行測試</li>
        <li>檢查垃圾郵件夾</li>
        <li>如果失敗，可能需要配置SMTP服務器</li>
        <li>確保服務器允許發送郵件</li>
    </ol>
</div>

<p><a href="chat.php">← 返回聊天室</a></p>












