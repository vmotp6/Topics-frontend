<?php
// 資料庫連接配置檔案
// 與 backend/app.py 保持一致的連線設定

define('DB_HOST', '100.79.58.120');    // 組員電腦的IP地址
define('DB_USERNAME', 'root');          // 資料庫使用者名稱
define('DB_PASSWORD', '');              // 資料庫密碼
define('DB_NAME', 'topics_good');       // 資料庫名稱
define('DB_CHARSET', 'utf8mb4');        // 字符集

// 資料庫連接函數
function getDatabaseConnection() {
    $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // 檢查連接
    if ($conn->connect_error) {
        die("資料庫連接失敗: " . $conn->connect_error);
    }
    
    // 設定字符集
    $conn->set_charset(DB_CHARSET);
    
    return $conn;
}

// 檔案上傳設定
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'zip', 'rar']);

// 驗證碼選項
define('CAPTCHA_CODES', ['5897', '3642', '8159', '7234', '9876']);

// SMTP 郵件設定
define('SMTP_HOST', 'smtp.gmail.com');           // Gmail SMTP 伺服器
define('SMTP_PORT', 587);                        // Gmail SMTP 端口 (TLS)
define('SMTP_USERNAME', 'vichuang2005@gmail.com');                     // 您的 Gmail 地址 (請填入)
define('SMTP_PASSWORD', 'sulv mlfy ysjd hrcp');                     // 您的 Gmail 應用程序密碼 (請填入)
define('SMTP_FROM_EMAIL', 'vichuang2005@gmail.com');                   // 發送者郵件地址 (請填入)
define('SMTP_FROM_NAME', '康寧大學五專入學說明會');  // 發送者名稱
define('SMTP_SECURE', 'tls');                    // 加密類型 (tls 或 ssl)
?>
