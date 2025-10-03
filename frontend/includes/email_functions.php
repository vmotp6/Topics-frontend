<?php
// 郵件發送功能模組
require_once __DIR__ . '/../config.php';

// 檢查是否安裝了 PHPMailer，如果沒有則使用內建的 mail() 函數
$phpmailer_available = false;

// 嘗試加載 PHPMailer（如果通過 Composer 安裝）
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $phpmailer_available = class_exists('PHPMailer\PHPMailer\PHPMailer');
}

// 如果沒有 Composer，嘗試手動加載 PHPMailer
if (!$phpmailer_available && file_exists(__DIR__ . '/../PHPMailer/src/PHPMailer.php')) {
    require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
    require_once __DIR__ . '/../PHPMailer/src/Exception.php';
    $phpmailer_available = class_exists('PHPMailer\PHPMailer\PHPMailer');
}

/**
 * 使用 PHPMailer 發送 SMTP 郵件
 */
function sendEmailWithPHPMailer($to, $subject, $body, $altBody = '') {
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // SMTP 設定
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        
        // 編碼設定
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        // 發送者資訊
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        // 收件者
        $mail->addAddress($to);
        
        // 郵件內容
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        if ($altBody) {
            $mail->AltBody = $altBody;
        }
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("PHPMailer 錯誤: " . $e->getMessage());
        return false;
    }
}

/**
 * 使用內建 mail() 函數發送郵件（備用方案）
 */
function sendEmailWithBuiltIn($to, $subject, $body) {
    // 設定郵件頭
    $headers = array();
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>';
    $headers[] = 'Reply-To: ' . SMTP_FROM_EMAIL;
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    
    $header_string = implode("\r\n", $headers);
    
    // 發送郵件
    return mail($to, $subject, $body, $header_string);
}

/**
 * 通用郵件發送函數
 */
function sendEmail($to, $subject, $body, $altBody = '') {
    global $phpmailer_available;
    
    // 檢查 SMTP 設定是否已配置
    if (empty(SMTP_USERNAME) || empty(SMTP_PASSWORD) || empty(SMTP_FROM_EMAIL)) {
        error_log("SMTP 設定未完成，請在 config.php 中填入相關資訊");
        return false;
    }
    
    // 優先使用 PHPMailer
    if ($phpmailer_available) {
        return sendEmailWithPHPMailer($to, $subject, $body, $altBody);
    } else {
        // 備用方案：使用內建 mail() 函數
        error_log("PHPMailer 未安裝，使用內建 mail() 函數");
        return sendEmailWithBuiltIn($to, $subject, $body);
    }
}

/**
 * 發送歡迎郵件
 */
function sendWelcomeEmail($email, $studentName, $parentName, $sessionName, $courseText) {
    $subject = "康寧大學五專入學說明會 - 報名確認通知";
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .info-box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #667eea; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            .highlight { color: #667eea; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎓 康寧大學五專入學說明會</h1>
                <p>報名成功確認通知</p>
            </div>
            <div class='content'>
                <h2>親愛的 {$parentName} ，您好！</h2>
                
                <p>感謝您為 <span class='highlight'>{$studentName}</span> 同學報名參加康寧大學五專入學說明會。我們已成功收到您的報名資料。</p>
                
                <div class='info-box'>
                    <h3>📋 報名資訊確認</h3>
                    <p><strong>學生姓名：</strong>{$studentName}</p>
                    <p><strong>姓名：</strong>{$parentName}</p>
                    <p><strong>參加場次：</strong>{$sessionName}</p>
                    <p><strong>體驗課程：</strong>{$courseText}</p>
                </div>
                
                <div class='info-box'>
                    <h3>📞 聯絡資訊</h3>
                    <p>如有任何問題，歡迎與我們聯繫：</p>
                    <p><strong>招生諮詢專線：</strong>請洽學校總機</p>
                    <p><strong>電子郵件：</strong>" . SMTP_FROM_EMAIL . "</p>
                </div>
                
                <div class='info-box'>
                    <h3>⏰ 重要提醒</h3>
                    <ul>
                        <li>我們會在活動前再次發送提醒郵件</li>
                        <li>請記得在活動當天攜帶學生證或相關證件</li>
                        <li>建議提前 15 分鐘到達會場</li>
                        <li>如需要取消或變更，請提前通知我們</li>
                    </ul>
                </div>
                
                <p>我們期待與您和 {$studentName} 同學在說明會中見面！</p>
                
                <div class='footer'>
                    <p>此郵件由系統自動發送，請勿直接回覆</p>
                    <p><strong>康寧大學招生組</strong></p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // 純文字版本（備用）
    $altBody = "
康寧大學五專入學說明會 - 報名確認通知

親愛的 {$parentName} ，您好！

感謝您為 {$studentName} 同學報名參加康寧大學五專入學說明會。

報名資訊確認：
- 學生姓名：{$studentName}
- 姓名：{$parentName}
- 參加場次：{$sessionName}
- 體驗課程：{$courseText}

重要提醒：
- 我們會在活動前再次發送提醒郵件
- 請記得在活動當天攜帶學生證或相關證件
- 建議提前 15 分鐘到達會場

如有任何問題，歡迎與我們聯繫。

康寧大學招生組
    ";
    
    return sendEmail($email, $subject, $body, $altBody);
}

/**
 * 發送提醒郵件
 */
function sendReminderEmail($email, $studentName, $parentName, $sessionName, $sessionDate) {
    $subject = "康寧大學五專入學說明會 - 活動提醒通知";
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .reminder-box { background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; margin: 20px 0; border-radius: 8px; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>⏰ 康寧大學五專入學說明會</h1>
                <p>活動提醒通知</p>
            </div>
            <div class='content'>
                <h2>親愛的 {$parentName} ，您好！</h2>
                
                <div class='reminder-box'>
                    <h3>📅 活動即將開始</h3>
                    <p>提醒您，{$studentName} 同學報名的「{$sessionName}」即將舉行。</p>
                    <p><strong>活動時間：</strong>{$sessionDate}</p>
                </div>
                
                <h3>📝 出席準備事項</h3>
                <ul>
                    <li>請攜帶學生證或相關身份證明</li>
                    <li>建議攜帶筆記本，記錄重要資訊</li>
                    <li>如有疑問，歡迎現場提問</li>
                    <li>建議提前 15 分鐘到達會場</li>
                </ul>
                
                <p>我們期待您的蒞臨！</p>
                
                <div class='footer'>
                    <p>康寧大學招生組</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body);
}

// 檢查 PHPMailer 是否可用的函數
function checkPHPMailerAvailability() {
    global $phpmailer_available;
    return $phpmailer_available;
}

// 測試郵件發送功能
function testEmailFunction($testEmail = null) {
    if (empty($testEmail)) {
        $testEmail = SMTP_FROM_EMAIL;
    }
    
    $subject = "康寧大學系統 - 郵件功能測試";
    $body = "
    <h2>郵件功能測試成功！</h2>
    <p>這是一封測試郵件，用於確認 SMTP 配置是否正確。</p>
    <p>發送時間：" . date('Y-m-d H:i:s') . "</p>
    <p>如果您收到這封郵件，表示郵件系統配置成功。</p>
    ";
    
    return sendEmail($testEmail, $subject, $body);
}
?>
