<?php
/**
 * Gmail 郵件服務配置檔案
 */

// 預設配置
$email_config = [
    // Gmail SMTP 設定
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'your-email@gmail.com',
    'smtp_password' => 'your-app-password',
    'sender_email' => 'your-email@gmail.com',
    'sender_name' => '康寧大學聊天系統',
    
    // 網站設定
    'base_url' => 'http://localhost',
    'chat_url' => 'http://localhost/frontend/chat/chat.php',
    
    // 郵件設定
    'enable_notifications' => true,
    'max_message_length' => 200,
    
    // 測試設定
    'test_mode' => false,
    'test_email' => 'test@example.com'
];

// 嘗試載入本地配置檔案
$local_config_file = __DIR__ . '/email_config_local.php';
if (file_exists($local_config_file)) {
    require_once $local_config_file;
}

// 從環境變數覆蓋配置
if (getenv('GMAIL_SENDER_EMAIL')) {
    $email_config['sender_email'] = getenv('GMAIL_SENDER_EMAIL');
    $email_config['smtp_username'] = getenv('GMAIL_SENDER_EMAIL');
}

if (getenv('GMAIL_APP_PASSWORD')) {
    $email_config['smtp_password'] = getenv('GMAIL_APP_PASSWORD');
}

if (getenv('GMAIL_SENDER_NAME')) {
    $email_config['sender_name'] = getenv('GMAIL_SENDER_NAME');
}

if (getenv('BASE_URL')) {
    $email_config['base_url'] = getenv('BASE_URL');
    $email_config['chat_url'] = getenv('BASE_URL') . '/frontend/chat/chat.php';
}

/**
 * 獲取郵件配置
 */
function getEmailConfig($key = null) {
    global $email_config;
    
    if ($key === null) {
        return $email_config;
    }
    
    return isset($email_config[$key]) ? $email_config[$key] : null;
}

/**
 * 檢查郵件配置是否完整
 */
function validateEmailConfig() {
    global $email_config;
    
    $errors = [];
    $warnings = [];
    
    // 檢查必要的配置
    if (empty($email_config['sender_email']) || $email_config['sender_email'] === 'your-email@gmail.com') {
        $errors[] = 'sender_email 未設定或使用預設值';
    }
    
    if (empty($email_config['smtp_password']) || $email_config['smtp_password'] === 'your-app-password') {
        $errors[] = 'smtp_password 未設定或使用預設值';
    }
    
    // 檢查郵箱格式
    if (!filter_var($email_config['sender_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'sender_email 格式不正確';
    }
    
    // 檢查URL格式
    if (!filter_var($email_config['base_url'], FILTER_VALIDATE_URL)) {
        $warnings[] = 'base_url 格式可能不正確';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings
    ];
}

/**
 * 顯示配置狀態
 */
function displayEmailConfigStatus() {
    $validation = validateEmailConfig();
    
    echo "📧 郵件服務配置狀態\n";
    echo "=" . str_repeat("=", 50) . "\n";
    
    if ($validation['valid']) {
        echo "✅ 配置完整，郵件服務可以正常使用\n";
    } else {
        echo "❌ 配置不完整，請檢查以下問題：\n";
        foreach ($validation['errors'] as $error) {
            echo "   - $error\n";
        }
    }
    
    if (!empty($validation['warnings'])) {
        echo "\n⚠️  警告：\n";
        foreach ($validation['warnings'] as $warning) {
            echo "   - $warning\n";
        }
    }
    
    echo "\n📋 當前配置：\n";
    echo "   - 發送者郵箱: " . getEmailConfig('sender_email') . "\n";
    echo "   - 發送者名稱: " . getEmailConfig('sender_name') . "\n";
    echo "   - 基礎URL: " . getEmailConfig('base_url') . "\n";
    echo "   - 通知功能: " . (getEmailConfig('enable_notifications') ? '啟用' : '停用') . "\n";
}

// 如果直接執行此檔案，顯示配置狀態
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    displayEmailConfigStatus();
}
?>
