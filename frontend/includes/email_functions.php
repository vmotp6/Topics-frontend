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
function sendEmailWithPHPMailer($to, $subject, $body, $altBody = '', $attachments = []) {
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
        
        // 啟用詳細錯誤日誌（用於調試）
        $mail->SMTPDebug = 0; // 0=關閉, 2=詳細
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug (Level $level): $str");
        };
        
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
        
        if (!empty($attachments) && is_array($attachments)) {
            foreach ($attachments as $att) {
                $path = isset($att['path']) ? (string)$att['path'] : '';
                if ($path === '' || !file_exists($path)) continue;
                $name = isset($att['name']) ? (string)$att['name'] : '';
                if ($name !== '') {
                    $mail->addAttachment($path, $name);
                } else {
                    $mail->addAttachment($path);
                }
            }
        }

        $result = $mail->send();
        if ($result) {
            error_log("✅ PHPMailer 郵件發送成功: 收件人=$to, 主題=$subject");
        } else {
            error_log("❌ PHPMailer 郵件發送失敗: 收件人=$to, 主題=$subject");
        }
        return $result;
        
    } catch (Exception $e) {
        error_log("❌ PHPMailer 異常: 收件人=$to, 錯誤=" . $e->getMessage());
        error_log("PHPMailer 異常堆疊: " . $e->getTraceAsString());
        return false;
    }
}

/**
 * 使用內建 mail() 函數發送郵件（備用方案）
 */
function sendEmailWithBuiltIn($to, $subject, $body, $altBody = '', $attachments = []) {
    // 設定郵件頭
    $headers = array();
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>';
    $headers[] = 'Reply-To: ' . SMTP_FROM_EMAIL;
    $headers[] = 'X-Mailer: PHP/' . phpversion();

    $has_attachments = (!empty($attachments) && is_array($attachments));
    if (!$has_attachments) {
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $header_string = implode("\r\n", $headers);
        return mail($to, $subject, $body, $header_string);
    }

    $boundary = 'b1_' . md5(uniqid((string)microtime(true), true));
    $alt_boundary = 'b2_' . md5(uniqid('alt', true));
    $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
    $header_string = implode("\r\n", $headers);

    $message = '';
    $message .= '--' . $boundary . "\r\n";
    $message .= 'Content-Type: multipart/alternative; boundary="' . $alt_boundary . '"' . "\r\n\r\n";

    if ($altBody !== '') {
        $message .= '--' . $alt_boundary . "\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($altBody)) . "\r\n";
    }

    $message .= '--' . $alt_boundary . "\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message .= chunk_split(base64_encode($body)) . "\r\n";
    $message .= '--' . $alt_boundary . "--\r\n";

    foreach ($attachments as $att) {
        $path = isset($att['path']) ? (string)$att['path'] : '';
        if ($path === '' || !file_exists($path)) continue;
        $name = isset($att['name']) ? (string)$att['name'] : basename($path);
        $mime = isset($att['mime']) ? (string)$att['mime'] : 'application/octet-stream';
        $data = @file_get_contents($path);
        if ($data === false) continue;
        $message .= '--' . $boundary . "\r\n";
        $message .= 'Content-Type: ' . $mime . '; name="' . $name . '"' . "\r\n";
        $message .= 'Content-Transfer-Encoding: base64' . "\r\n";
        $message .= 'Content-Disposition: attachment; filename="' . $name . '"' . "\r\n\r\n";
        $message .= chunk_split(base64_encode($data)) . "\r\n";
    }
    $message .= '--' . $boundary . "--\r\n";

    return mail($to, $subject, $message, $header_string);
}

/**
 * 通用郵件發送函數
 */
function sendEmail($to, $subject, $body, $altBody = '', $attachments = []) {
    global $phpmailer_available;
    
    error_log("開始發送郵件: 收件人=$to, 主題=$subject");
    
    // 檢查 SMTP 設定是否已配置
    if (empty(SMTP_USERNAME) || empty(SMTP_PASSWORD) || empty(SMTP_FROM_EMAIL)) {
        error_log("❌ SMTP 設定未完成，請在 config.php 中填入相關資訊");
        error_log("SMTP_USERNAME=" . (defined('SMTP_USERNAME') ? (empty(SMTP_USERNAME) ? '空' : '已設置') : '未定義'));
        error_log("SMTP_PASSWORD=" . (defined('SMTP_PASSWORD') ? (empty(SMTP_PASSWORD) ? '空' : '已設置') : '未定義'));
        error_log("SMTP_FROM_EMAIL=" . (defined('SMTP_FROM_EMAIL') ? (empty(SMTP_FROM_EMAIL) ? '空' : SMTP_FROM_EMAIL) : '未定義'));
        return false;
    }
    
    // 優先使用 PHPMailer
    if ($phpmailer_available) {
        error_log("使用 PHPMailer 發送郵件");
        return sendEmailWithPHPMailer($to, $subject, $body, $altBody, $attachments);
    } else {
        // 備用方案：使用內建 mail() 函數
        error_log("⚠️ PHPMailer 未安裝，使用內建 mail() 函數");
        $result = sendEmailWithBuiltIn($to, $subject, $body, $altBody, $attachments);
        if ($result) {
            error_log("✅ 內建 mail() 函數發送成功: 收件人=$to");
        } else {
            error_log("❌ 內建 mail() 函數發送失敗: 收件人=$to");
        }
        return $result;
    }
}

/**
 * 發送歡迎郵件
 */
function sendWelcomeEmail($email, $studentName, $parentName, $sessionName, $courseText, $sessionType = '實體', $location = '', $onlineLink = '') {
    $subject = "康寧大學五專入學說明會 - 報名確認通知";
    
    // 根據場次類型生成不同的內容
    // 線上場次：只顯示連結，不顯示地點
    // 實體場次：只顯示地點，不顯示連結
    $sessionInfo = '';
    $sessionType = trim($sessionType);
    if ($sessionType === '線上') {
        // 線上場次：顯示線上會議連結
        if (!empty($onlineLink)) {
            $sessionInfo = "<p><strong>線上會議連結：</strong><a href='{$onlineLink}' style='color: #667eea; text-decoration: underline;'>{$onlineLink}</a></p>";
        } else {
            $sessionInfo = "<p><strong>線上會議連結：</strong>將於活動前另行通知</p>";
        }
    } else {
        // 實體場次：顯示活動地點
        if (!empty($location)) {
            $sessionInfo = "<p><strong>活動地點：</strong>{$location}</p>";
        } else {
            $sessionInfo = "<p><strong>活動地點：</strong>將於活動前另行通知</p>";
        }
    }
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
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
                    <p><strong>場次類型：</strong>{$sessionType}</p>
                    {$sessionInfo}
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
                        <li>我們會在活動前再次發送提醒郵件</li>";
    
    if ($sessionType === '線上') {
        $body .= "
                        <li>請提前測試您的網路連線和視訊設備</li>
                        <li>建議提前 5 分鐘進入線上會議室</li>";
    } else {
        $body .= "
                        <li>請記得在活動當天攜帶學生證或相關證件</li>
                        <li>建議提前 15 分鐘到達會場</li>";
    }
    
    $body .= "
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
    // 線上場次：只顯示連結，不顯示地點
    // 實體場次：只顯示地點，不顯示連結
    $sessionInfoText = '';
    if ($sessionType === '線上') {
        // 線上場次：顯示線上會議連結
        if (!empty($onlineLink)) {
            $sessionInfoText = "線上會議連結：{$onlineLink}";
        } else {
            $sessionInfoText = "線上會議連結：將於活動前另行通知";
        }
    } else {
        // 實體場次：顯示活動地點
        if (!empty($location)) {
            $sessionInfoText = "活動地點：{$location}";
        } else {
            $sessionInfoText = "活動地點：將於活動前另行通知";
        }
    }
    
    $altBody = "
康寧大學五專入學說明會 - 報名確認通知

親愛的 {$parentName} ，您好！

感謝您為 {$studentName} 同學報名參加康寧大學五專入學說明會。

報名資訊確認：
- 學生姓名：{$studentName}
- 姓名：{$parentName}
- 參加場次：{$sessionName}
- 場次類型：{$sessionType}
- {$sessionInfoText}
- 體驗課程：{$courseText}

重要提醒：
- 我們會在活動前再次發送提醒郵件";
    
    if ($sessionType === '線上') {
        $altBody .= "
- 請提前測試您的網路連線和視訊設備
- 建議提前 5 分鐘進入線上會議室";
    } else {
        $altBody .= "
- 請記得在活動當天攜帶學生證或相關證件
- 建議提前 15 分鐘到達會場";
    }
    
    $altBody .= "

如有任何問題，歡迎與我們聯繫。

康寧大學招生組
    ";
    
    return sendEmail($email, $subject, $body, $altBody);
}

/**
 * 發送提醒郵件
 */
function sendReminderEmail($email, $studentName, $parentName, $sessionName, $sessionDate, $sessionType = '實體', $location = '', $onlineLink = '') {
    $subject = "康寧大學五專入學說明會 - 活動提醒通知";
    
    // 根據場次類型生成不同的內容
    // 線上場次：只顯示連結，不顯示地點
    // 實體場次：只顯示地點，不顯示連結
    $sessionInfo = '';
    $sessionType = trim($sessionType);
    if ($sessionType === '線上') {
        // 線上場次：顯示線上會議連結
        if (!empty($onlineLink)) {
            $sessionInfo = "<p><strong>線上會議連結：</strong><a href='{$onlineLink}' style='color: #667eea; text-decoration: underline; font-weight: bold;'>{$onlineLink}</a></p>";
        } else {
            $sessionInfo = "<p><strong>線上會議連結：</strong>將於活動前另行通知</p>";
        }
    } else {
        // 實體場次：顯示活動地點
        if (!empty($location)) {
            $sessionInfo = "<p><strong>活動地點：</strong>{$location}</p>";
        } else {
            $sessionInfo = "<p><strong>活動地點：</strong>將於活動前另行通知</p>";
        }
    }
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
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
                    <p><strong>場次類型：</strong>{$sessionType}</p>
                    {$sessionInfo}
                </div>
                
                <h3>📝 出席準備事項</h3>
                <ul>";
    
    if ($sessionType === '線上') {
        $body .= "
                    <li>請提前測試您的網路連線和視訊設備</li>
                    <li>建議提前 5 分鐘進入線上會議室</li>
                    <li>建議準備筆記本，記錄重要資訊</li>
                    <li>如有疑問，歡迎在會議中提問</li>";
    } else {
        $body .= "
                    <li>請攜帶學生證或相關身份證明</li>
                    <li>建議攜帶筆記本，記錄重要資訊</li>
                    <li>如有疑問，歡迎現場提問</li>
                    <li>建議提前 15 分鐘到達會場</li>";
    }
    
    $body .= "
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

/**
 * 發送修改確認郵件
 */
function sendModifyConfirmationEmail($email, $studentName, $parentName, $sessionName, $courseText, $sessionType = '實體', $location = '', $onlineLink = '') {
    $subject = "康寧大學五專入學說明會 - 報名修改確認";
    
    // 根據場次類型生成不同的內容
    // 線上場次：只顯示連結，不顯示地點
    // 實體場次：只顯示地點，不顯示連結
    $sessionInfo = '';
    $sessionType = trim($sessionType);
    if ($sessionType === '線上') {
        // 線上場次：顯示線上會議連結
        if (!empty($onlineLink)) {
            $sessionInfo = "<tr>
                        <td style='padding: 8px 0; font-weight: bold; color: #555;'>線上會議連結：</td>
                        <td style='padding: 8px 0; color: #333;'><a href='{$onlineLink}' style='color: #667eea; text-decoration: underline;'>{$onlineLink}</a></td>
                    </tr>";
        } else {
            $sessionInfo = "<tr>
                        <td style='padding: 8px 0; font-weight: bold; color: #555;'>線上會議連結：</td>
                        <td style='padding: 8px 0; color: #333;'>將於活動前另行通知</td>
                    </tr>";
        }
    } else {
        // 實體場次：顯示活動地點
        if (!empty($location)) {
            $sessionInfo = "<tr>
                        <td style='padding: 8px 0; font-weight: bold; color: #555;'>活動地點：</td>
                        <td style='padding: 8px 0; color: #333;'>{$location}</td>
                    </tr>";
        } else {
            $sessionInfo = "<tr>
                        <td style='padding: 8px 0; font-weight: bold; color: #555;'>活動地點：</td>
                        <td style='padding: 8px 0; color: #333;'>將於活動前另行通知</td>
                    </tr>";
        }
    }
    
    $body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
        <div style='background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%); color: white; padding: 30px; border-radius: 15px 15px 0 0; text-align: center;'>
            <h1 style='margin: 0; font-size: 24px;'>康寧大學五專入學說明會</h1>
            <p style='margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;'>報名修改確認通知</p>
        </div>
        
        <div style='background: white; padding: 30px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 15px 15px;'>
            <h2 style='color: #667eea; margin-bottom: 20px;'>親愛的 {$parentName} 您好：</h2>
            
            <p style='font-size: 16px; line-height: 1.6; color: #333; margin-bottom: 20px;'>
                感謝您對康寧大學五專入學說明會的關注！我們已收到您的報名修改申請，並已成功更新您的報名資料。
            </p>
            
            <div style='background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                <h3 style='color: #667eea; margin-top: 0; margin-bottom: 15px;'>修改後的報名資訊：</h3>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold; color: #555; width: 120px;'>學生姓名：</td>
                        <td style='padding: 8px 0; color: #333;'>{$studentName}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold; color: #555;'>參加場次：</td>
                        <td style='padding: 8px 0; color: #333;'>{$sessionName}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold; color: #555;'>場次類型：</td>
                        <td style='padding: 8px 0; color: #333;'>{$sessionType}</td>
                    </tr>
                    {$sessionInfo}
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold; color: #555;'>體驗課程：</td>
                        <td style='padding: 8px 0; color: #333;'>{$courseText}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: bold; color: #555;'>修改時間：</td>
                        <td style='padding: 8px 0; color: #333;'>" . date('Y-m-d H:i:s') . "</td>
                    </tr>
                </table>
            </div>
            
            <div style='background: #e8f4fd; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #667eea;'>
                <h4 style='color: #667eea; margin-top: 0; margin-bottom: 10px;'>📋 重要提醒：</h4>
                <ul style='margin: 0; padding-left: 20px; color: #333;'>
                    <li>請確認修改後的場次時間";
    
    if ($sessionType === '線上') {
        $body .= "和線上會議連結";
    } else {
        $body .= "和地點";
    }
    
    $body .= "</li>
                    <li>活動前一天我們會再次發送提醒郵件</li>
                    <li>如有任何疑問，請聯繫招生諮詢老師</li>
                    <li>您可隨時透過電子郵件查詢您的報名狀態</li>
                </ul>
            </div>
            
            <p style='font-size: 16px; line-height: 1.6; color: #333; margin-bottom: 20px;'>
                再次感謝您選擇康寧大學！我們期待在說明會上與您見面，為您詳細介紹我們的五專課程特色和未來發展機會。
            </p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <p style='color: #667eea; font-size: 18px; font-weight: bold; margin: 0;'>
                    選擇康寧 • 人生雙贏 • 未來罩您
                </p>
            </div>
            
            <hr style='border: none; border-top: 1px solid #e0e0e0; margin: 30px 0;'>
            
            <div style='text-align: center; color: #666; font-size: 14px;'>
                <p>此郵件由系統自動發送，請勿直接回覆。</p>
                <p>如有疑問，請聯繫康寧大學招生處。</p>
                <p>© " . date('Y') . " 康寧大學 版權所有</p>
            </div>
        </div>
    </div>
    ";
    
    return sendEmail($email, $subject, $body);
}
?>
