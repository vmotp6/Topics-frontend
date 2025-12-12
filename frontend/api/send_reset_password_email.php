<?php
/**
 * 發送重設密碼郵件腳本（支援命令行和 HTTP POST 兩種方式）
 */
header('Content-Type: application/json; charset=utf-8');

// 啟用錯誤報告（僅用於調試）
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 獲取腳本所在目錄的父目錄（frontend）
$script_dir = dirname(__DIR__);
$config_file = $script_dir . '/config.php';
$email_functions_file = $script_dir . '/includes/email_functions.php';

if (!file_exists($config_file)) {
    error_log("send_reset_password_email.php: 找不到 config.php: {$config_file}");
    if (php_sapi_name() === 'cli') {
        exit(1);
    } else {
        echo json_encode(['success' => false, 'message' => '配置文件不存在']);
        exit;
    }
}

if (!file_exists($email_functions_file)) {
    error_log("send_reset_password_email.php: 找不到 email_functions.php: {$email_functions_file}");
    if (php_sapi_name() === 'cli') {
        exit(1);
    } else {
        echo json_encode(['success' => false, 'message' => '郵件功能文件不存在']);
        exit;
    }
}

require_once $config_file;
require_once $email_functions_file;

// 判斷是命令行調用還是 HTTP 請求
if (php_sapi_name() === 'cli') {
    // 命令行模式
    if ($argc < 5) {
        error_log("send_reset_password_email.php: 參數不足，需要 4 個參數");
        exit(1);
    }
    $user_id = $argv[1];
    $reset_token = $argv[2];
    $email = $argv[3];
    $name = $argv[4];
} else {
    // HTTP POST 模式
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => '只接受 POST 請求']);
        exit;
    }
    $user_id = intval($_POST['user_id'] ?? 0);
    $reset_token = trim($_POST['token'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    
    if (empty($user_id) || empty($reset_token) || empty($email) || empty($name)) {
        echo json_encode(['success' => false, 'message' => '缺少必要參數']);
        exit;
    }
}

error_log("send_reset_password_email.php: 開始發送郵件 - user_id={$user_id}, email={$email}");

// 構建重設密碼連結
$base_url = defined('BASE_URL') ? BASE_URL : 'http://localhost';
$reset_url = $base_url . '/Topics-frontend/frontend/reset_password.php?token=' . urlencode($reset_token);

$subject = "康寧大學招生平台 - 重設密碼";
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
        .button { display: inline-block; background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; margin: 20px 0; }
        .button:hover { background: #5568d3; }
        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🔐 重設密碼</h1>
        </div>
        <div class='content'>
            <h2>親愛的 " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "，您好！</h2>
            <p>我們收到您要求重設密碼的請求。</p>
            <p>請點擊以下按鈕來重設您的密碼：</p>
            <div style='text-align: center;'>
                <a href='" . htmlspecialchars($reset_url, ENT_QUOTES, 'UTF-8') . "' class='button'>重設密碼</a>
            </div>
            <p>或者複製以下連結到瀏覽器：</p>
            <p style='word-break: break-all; color: #667eea;'>{$reset_url}</p>
            <div class='warning'>
                <p><strong>⚠️ 安全提醒：</strong></p>
                <p>此連結將在 1 小時後過期。</p>
                <p>如果您沒有要求重設密碼，請忽略此郵件，您的密碼將不會被更改。</p>
            </div>
            <div class='footer'>
                <p>此郵件由系統自動發送，請勿直接回覆</p>
                <p><strong>康寧大學招生組</strong></p>
            </div>
        </div>
    </div>
</body>
</html>
";

$altBody = "親愛的 {$name}，您好！\n\n我們收到您要求重設密碼的請求。\n\n請使用以下連結來重設您的密碼：\n\n{$reset_url}\n\n此連結將在 1 小時後過期。\n\n如果您沒有要求重設密碼，請忽略此郵件，您的密碼將不會被更改。\n\n康寧大學招生組";

error_log("send_reset_password_email.php: 準備發送郵件 - SMTP_HOST=" . (defined('SMTP_HOST') ? SMTP_HOST : '未定義'));

$result = sendEmail($email, $subject, $body, $altBody);

if ($result) {
    error_log("send_reset_password_email.php: 郵件發送成功 - email={$email}");
    if (php_sapi_name() === 'cli') {
        exit(0);
    } else {
        echo json_encode(['success' => true, 'message' => '重設密碼郵件已發送']);
        exit;
    }
} else {
    error_log("send_reset_password_email.php: 郵件發送失敗 - email={$email}");
    if (php_sapi_name() === 'cli') {
        exit(1);
    } else {
        echo json_encode(['success' => false, 'message' => '發送重設密碼郵件失敗，請檢查 SMTP 配置']);
        exit;
    }
}
?>
