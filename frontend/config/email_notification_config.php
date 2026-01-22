<?php
// 郵件通知配置檔案

// 設定時區為台灣時區 (UTC+8)
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Asia/Taipei');
} else {
    // 如果已設定時區，確保是台灣時區
    $current_timezone = date_default_timezone_get();
    if ($current_timezone !== 'Asia/Taipei') {
        date_default_timezone_set('Asia/Taipei');
    }
}

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
    'rejection_notification' => [
        'subject' => '【康寧大學】推薦審核未通過通知',
        'template' => 'rejection_notification_template'
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
                    .header { background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
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
                            <p><strong>提交時間：</strong>{submission_time} (台灣時間 UTC+8)</p>
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
                    .header { background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
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
                            <h3>💰 獎金資訊</h3>
                            <p>您本次推薦獲得的獎金金額為：<strong>{bonus_amount}</strong> 元</p>
                            <p style="color:#666; font-size:14px; margin-top:8px;">※ 若同一位被推薦學生有多人通過，獎金將依規則分攤。</p>
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

        'rejection_notification_template' => '
            <!DOCTYPE html>
            <html lang="zh-Hant">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>推薦審核未通過通知</title>
                <style>
                    body { font-family: "Microsoft JhengHei", Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                    .highlight { background: #fff2f0; border: 1px solid #ffccc7; color: #a8071a; padding: 15px; border-radius: 5px; margin: 20px 0; }
                    .info-box { background: white; border: 1px solid #dee2e6; padding: 20px; border-radius: 5px; margin: 15px 0; }
                    .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>📌 推薦審核未通過通知</h1>
                        <p>康寧大學招生推薦系統</p>
                    </div>
                    <div class="content">
                        <h2>親愛的 {recommender_name} 同學，您好！</h2>

                        <div class="highlight">
                            <h3>❌ 很抱歉，您推薦的學生未通過審核</h3>
                            <p>我們通知您，您推薦的 <strong>{student_name}</strong> 同學之推薦申請未通過審核。</p>
                        </div>

                        <div class="info-box">
                            <h3>📋 申請資訊</h3>
                            <p><strong>被推薦學生：</strong>{student_name}</p>
                            <p><strong>被推薦學生學校：</strong>{student_school}</p>
                            <p><strong>被推薦學生年級：</strong>{student_grade}</p>
                            <p><strong>推薦人：</strong>{recommender_name} ({recommender_student_id})</p>
                            <p><strong>推薦人科系：</strong>{recommender_department}</p>
                            <p><strong>通知時間：</strong>{review_time}</p>
                        </div>

                        <div class="info-box">
                            <h3>📞 如有疑問</h3>
                            <p>若您對審核結果有任何疑問，請聯繫招生辦公室協助確認。</p>
                        </div>

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
                    .header { background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
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
        // 如果是時間相關的欄位，確保格式正確
        if (strpos($key, 'time') !== false || strpos($key, 'date') !== false) {
            // 如果已經是正確格式，直接使用；否則嘗試轉換
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
                // 嘗試解析並格式化為台灣時區
                try {
                    $date = new DateTime($value);
                    // 如果時間是 UTC，轉換為台灣時區
                    if ($date->getTimezone()->getName() === 'UTC' || 
                        strpos($value, 'UTC') !== false || 
                        strpos($value, '+00:00') !== false) {
                        $date->setTimezone(new DateTimeZone('Asia/Taipei'));
                    }
                    $value = $date->format('Y-m-d H:i:s');
                } catch (Exception $e) {
                    // 如果轉換失敗，使用原始值
                }
            }
        }
        $template = str_replace('{' . $key . '}', htmlspecialchars($value), $template);
    }
    
    return $template;
}

// 發送郵件函數
function sendNotificationEmail($to_email, $to_name, $template_name, $data = []) {
    try {
        // 確保使用台灣時區
        date_default_timezone_set('Asia/Taipei');
        
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
        
        // 設定郵件日期為台灣時區
        $mail->MessageDate = date('D, d M Y H:i:s +0800');
        
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
            $error_info = $mail->ErrorInfo;
            error_log("郵件發送失敗: {$to_email} - {$template_name} - 錯誤: {$error_info}");
            error_log("SMTP配置: Host=" . SMTP_HOST . ", Port=" . SMTP_PORT . ", From=" . SMTP_FROM_EMAIL);
            return false;
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("郵件發送錯誤: {$to_email} - {$template_name} - " . $error_message);
        error_log("SMTP配置: Host=" . (defined('SMTP_HOST') ? SMTP_HOST : '未定義') . ", Port=" . (defined('SMTP_PORT') ? SMTP_PORT : '未定義') . ", From=" . (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : '未定義'));
        return false;
    }
}

// 記錄通知發送記錄（已移除 notification_logs，改用 enrollment_contact_logs）
// 此函數已廢棄，聯絡紀錄應使用 enrollment_contact_logs 表
function logNotification($recommendation_id, $notification_type, $email, $status) {
    // 已移除 notification_logs 資料表建立程式
    // 聯絡紀錄應使用 enrollment_contact_logs 表
    error_log("logNotification 函數已廢棄，請使用 enrollment_contact_logs 表記錄聯絡紀錄");
    return false;
}
?>