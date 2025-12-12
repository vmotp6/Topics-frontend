<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/email_functions.php';
require_once __DIR__ . '/../session_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '只接受 POST 請求']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$name = trim($_POST['name'] ?? '查詢者');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => '請提供有效的電子郵件地址']);
    exit;
}

// 產生 4 位數驗證碼
$verification_code = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
$expires = time() + 3600; // 1 小時

// 存入 session
$_SESSION['admission_verification'] = [
    'email' => $email,
    'code' => $verification_code,
    'expires' => $expires
];

// 組合郵件內容
$subject = "康寧大學招生平台 - Email 驗證碼";
$body = "<!DOCTYPE html>\n<html lang='zh-TW'>\n<head>\n<meta charset='UTF-8'>\n<meta name='viewport' content='width=device-width,initial-scale=1.0'>\n<style>\n    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans TC', 'Microsoft JhengHei', sans-serif; background:#f6f8fb; margin:0; padding:0; }\n    .container { max-width:680px; margin:30px auto; background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 8px 30px rgba(38,53,78,0.08);}\n    .header { background: linear-gradient(90deg,#7ac9c7 0%,#956dbd 100%); padding:38px 30px; text-align:center; color:#fff;}\n    .header h1 { margin:0; font-size:22px; letter-spacing:1px; }\n    .content { padding:34px 36px; color:#333; }\n    .greeting { font-size:18px; font-weight:700; margin:0 0 10px 0; }\n    .intro { margin:0 0 22px 0; color:#555; line-height:1.6; }\n    .code-box { border:2px solid #667eea; border-radius:8px; padding:22px; text-align:center; background:#fff; margin:18px 0; }\n    .code { font-size:48px; font-weight:700; color:#667eea; letter-spacing:10px; }\n    .note { color:#666; font-size:13px; line-height:1.6; margin-top:8px; }\n    .footer { text-align:center; padding:18px 20px; color:#9aa3b2; font-size:13px; }\n    @media only screen and (max-width:480px){ .code { font-size:36px; letter-spacing:6px; } .container{margin:12px;} .content{padding:20px;} }\n</style>\n</head>\n<body>\n<div class='container'>\n    <div class='header'>\n        <h1>📧 Email 驗證</h1>\n    </div>\n    <div class='content'>\n        <p class='greeting'>親愛的 " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "，您好！</p>\n        <p class='intro'>感謝您使用康寧大學招生平台！請使用以下驗證碼完成 Email 驗證：</p>\n        <div class='code-box'>\n            <div class='code'>" . htmlspecialchars($verification_code, ENT_QUOTES, 'UTF-8') . "</div>\n        </div>\n        <p class='note'>此驗證碼將在 1 小時後過期。若您未要求此驗證，請忽略此郵件。</p>\n    </div>\n    <div class='footer'>此郵件由系統自動發送，請勿直接回覆<br>康寧大學招生組</div>\n</div>\n</body>\n</html>";
$alt = "親愛的 {$name}，您的驗證碼為: {$verification_code} (1小時內有效)";

$sent = sendEmail($email, $subject, $body, $alt);

if ($sent) {
    echo json_encode(['success' => true, 'message' => '驗證碼已發送']);
} else {
    echo json_encode(['success' => false, 'message' => '發送驗證碼失敗，請稍後再試']);
}

?>
