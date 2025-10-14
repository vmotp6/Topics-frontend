<?php
// 郵件通知配置檔案

// SMTP 郵件設定 (使用現有的 config.php 設定)
require_once dirname(__DIR__) . '/config.php';

// 引入 PHPMailer
require_once dirname(__DIR__) . '/PHPMailer/src/PHPMailer.php';
require_once dirname(__DIR__) . '/PHPMailer/src/SMTP.php';
require_once dirname(__DIR__) . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 郵件模板配置
$email_templates = [
    'recommendation_success' => [
        'subject' => '【康寧大學】推薦申請提交成功通知',
        'template' => 'recommendation_success_template'
    ],
    'approval_notification' => [
        'subject' => '【康寧大學】推薦審核通過通知',
        'template' => 'approval_notification_template'
    ],
    'enrollment_notification' => [
        'subject' => '【康寧大學】入學確認通知',
        'template' => 'enrollment_notification_template'
    ]
];

// 郵件模板內容
function getEmailTemplate($template_name, $data = []) {
    $templates = [
        'recommendation_success_template' => '
            <!DOCTYPE html>
            <html lang="zh-Hant">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>推薦申請提交成功通知</title>
                <style>
                    body { font-family: "Microsoft JhengHei", Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                    .highlight { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
                    .info-box { background: white; border: 1px solid #dee2e6; padding: 20px; border-radius: 5px; margin: 15px 0; }
                    .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>✅ 推薦申請提交成功</h1>
                        <p>康寧大學招生推薦系統</p>
                    </div>
                    <div class="content">
                        <h2>親愛的 {recommender_name} 同學，您好！</h2>
                        
                        <div class="highlight">
                            <h3>🎉 推薦申請已成功提交！</h3>
                            <p>感謝您為康寧大學推薦優秀學生！您的推薦申請已成功提交，我們會盡快處理。</p>
                        </div>
                        
                        <div class="info-box">
                            <h3>📋 推薦資訊</h3>
                            <p><strong>推薦人：</strong>{recommender_name} ({recommender_student_id})</p>
                            <p><strong>推薦人科系：</strong>{recommender_department}</p>
                            <p><strong>被推薦學生：</strong>{student_name}</p>
                            <p><strong>被推薦學生學校：</strong>{student_school}</p>
                            <p><strong>被推薦學生年級：</strong>{student_grade}</p>
                            <p><strong>提交時間：</strong>{submission_time}</p>
                        </div>
                        
                        <div class="info-box">
                            <h3>📚 後續流程</h3>
                            <p>1. 我們會盡快審核您的推薦申請</p>
                            <p>2. 審核結果將通過郵件通知您</p>
                            <p>3. 如有任何問題，請聯繫招生辦公室</p>
                        </div>
                        
                        <p>再次感謝您為康寧大學推薦優秀學生！</p>
                        
                        <div class="footer">
                            <p>此郵件由系統自動發送，請勿直接回覆</p>
                            <p>康寧大學招生辦公室 | 電話：(02) 2632-1181</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
        ',
        
        'approval_notification_template' => '
            <!DOCTYPE html>
            <html lang="zh-Hant">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>推薦審核通過通知</title>
                <style>
                    body { font-family: "Microsoft JhengHei", Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                    .highlight { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
                    .info-box { background: white; border: 1px solid #dee2e6; padding: 20px; border-radius: 5px; margin: 15px 0; }
                    .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>🎉 推薦審核通過通知</h1>
                        <p>康寧大學招生推薦系統</p>
                    </div>
                    <div class="content">
                        <h2>親愛的 {recommender_name} 同學，您好！</h2>
                        
                        <div class="highlight">
                            <h3>✅ 恭喜！您推薦的學生申請已通過審核</h3>
                            <p>我們很高興地通知您，您推薦的 <strong>{student_name}</strong> 同學的入學申請已經通過審核。</p>
                        </div>
                        
                        <div class="info-box">
                            <h3>📋 申請資訊</h3>
                            <p><strong>被推薦學生：</strong>{student_name}</p>
                            <p><strong>被推薦學生學校：</strong>{student_school}</p>
                            <p><strong>被推薦學生年級：</strong>{student_grade}</p>
                            <p><strong>推薦人：</strong>{recommender_name} ({recommender_student_id})</p>
                            <p><strong>推薦人科系：</strong>{recommender_department}</p>
                            <p><strong>審核通過時間：</strong>{approval_time}</p>
                        </div>
                        
                        <div class="info-box">
                            <h3>📚 後續步驟</h3>
                            <p>1. 被推薦學生將收到入學相關文件</p>
                            <p>2. 請協助被推薦學生關注康寧大學官方網站的最新消息</p>
                            <p>3. 如有任何問題，請聯繫招生辦公室</p>
                        </div>
                        
                        <p>感謝您為康寧大學推薦優秀學生，期待被推薦學生的加入！</p>
                        
                        <div class="footer">
                            <p>此郵件由系統自動發送，請勿直接回覆</p>
                            <p>康寧大學招生辦公室 | 電話：(02) 2632-1181</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
        ',
        
        'enrollment_notification_template' => '
            <!DOCTYPE html>
            <html lang="zh-Hant">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>入學確認通知</title>
                <style>
                    body { font-family: "Microsoft JhengHei", Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                    .highlight { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0; }
                    .info-box { background: white; border: 1px solid #dee2e6; padding: 20px; border-radius: 5px; margin: 15px 0; }
                    .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>🎓 入學確認通知</h1>
                        <p>康寧大學招生推薦系統</p>
                    </div>
                    <div class="content">
                        <h2>親愛的 {recommender_name} 同學，您好！</h2>
                        
                        <div class="highlight">
                            <h3>🎉 恭喜！您推薦的學生已正式入學！</h3>
                            <p>我們很高興地通知您，您推薦的 <strong>{student_name}</strong> 同學已成功完成入學手續，正式成為康寧大學的學生。</p>
                        </div>
                        
                        <div class="info-box">
                            <h3>📋 入學資訊</h3>
                            <p><strong>被推薦學生：</strong>{student_name}</p>
                            <p><strong>被推薦學生學校：</strong>{student_school}</p>
                            <p><strong>被推薦學生年級：</strong>{student_grade}</p>
                            <p><strong>推薦人：</strong>{recommender_name} ({recommender_student_id})</p>
                            <p><strong>推薦人科系：</strong>{recommender_department}</p>
                            <p><strong>入學確認時間：</strong>{enrollment_time}</p>
                        </div>
                        
                        <div class="info-box">
                            <h3>📚 重要提醒</h3>
                            <p>1. 被推薦學生將收到新生說明會通知</p>
                            <p>2. 被推薦學生需要完成學籍註冊手續</p>
                            <p>3. 被推薦學生將領取學生證和相關文件</p>
                            <p>4. 請協助被推薦學生關注開學時間和課程安排</p>
                        </div>
                        
                        <p>感謝您為康寧大學推薦優秀學生！被推薦學生已正式加入康寧大學大家庭。</p>
                        
                        <div class="footer">
                            <p>此郵件由系統自動發送，請勿直接回覆</p>
                            <p>康寧大學招生辦公室 | 電話：(02) 2632-1181</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
        '
    ];
    
    $template = $templates[$template_name] ?? '';
    
    // 替換模板變數
    foreach ($data as $key => $value) {
        $template = str_replace('{' . $key . '}', $value, $template);
    }
    
    return $template;
}

// 發送郵件函數
function sendNotificationEmail($to_email, $to_name, $template_name, $data = []) {
    try {
        $mail = new PHPMailer(true);
        
        // SMTP 設定
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // 發件人設定
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        // 收件人設定
        $mail->addAddress($to_email, $to_name);
        
        // 郵件內容
        $mail->isHTML(true);
        $mail->Subject = $GLOBALS['email_templates'][$template_name]['subject'] ?? '康寧大學通知';
        $mail->Body = getEmailTemplate($GLOBALS['email_templates'][$template_name]['template'], $data);
        
        // 發送郵件
        $result = $mail->send();
        
        if ($result) {
            error_log("郵件發送成功: {$to_email} - {$template_name}");
            return true;
        } else {
            error_log("郵件發送失敗: {$to_email} - {$template_name}");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("郵件發送錯誤: " . $e->getMessage());
        return false;
    }
}

// 記錄通知發送記錄
function logNotification($recommendation_id, $notification_type, $email, $status) {
    try {
        $conn = getDatabaseConnection();
        $sql = "INSERT INTO notification_logs (recommendation_id, notification_type, email, status, sent_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $recommendation_id, $notification_type, $email, $status);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        error_log("記錄通知日誌失敗: " . $e->getMessage());
    }
}
?>