<?php
// 資料庫連接配置檔案
// 與 backend/app.py 保持一致的連線設定

define('DB_HOST', 'localhost');    // 本地資料庫
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
define('SMTP_PASSWORD', 'sulvmlfyysjdhrcp');                     // 您的 Gmail 應用程序密碼 (請填入)
define('SMTP_FROM_EMAIL', 'vichuang2005@gmail.com');                   // 發送者郵件地址 (請填入)
define('SMTP_FROM_NAME', '康寧大學招生系統');  // 發送者名稱
define('SMTP_SECURE', 'tls');                    // 加密類型 (tls 或 ssl)

// Google Maps API 設定
define('GOOGLE_MAPS_API_KEY', 'AIzaSyCvjwdPOQe2OgPiGxTmSkzzGP2cb_fLJ3I');

/**
 * 將參與者代碼轉換為顯示名稱
 * @param string $codes_str 逗號分隔的代碼字符串（如：JHS_7,JHS_8,SHS_1）
 * @param mysqli $conn 資料庫連接（可選，如果不提供則會創建新連接）
 * @return string 轉換後的顯示名稱字符串
 */
function convertParticipantCodesToNames($codes_str, $conn = null) {
    if (empty($codes_str)) {
        return '';
    }
    
    // 如果沒有提供連接，創建一個
    $need_close = false;
    if ($conn === null) {
        $conn = getDatabaseConnection();
        $need_close = true;
    }
    
    $codes = array_map('trim', explode(',', $codes_str));
    $names = [];
    
    // 提取純代碼（去除 OTHER: 前綴）用於查詢
    $query_codes = [];
    foreach ($codes as $code) {
        if (strpos($code, 'OTHER:') === 0) {
            $query_codes[] = 'OTHER';
        } else {
            $query_codes[] = $code;
        }
    }
    $query_codes = array_unique($query_codes);
    
    // 查詢 participant_options 表
    if (!empty($query_codes)) {
        $code_placeholders = str_repeat('?,', count($query_codes) - 1) . '?';
        $sql = "SELECT code, name FROM participant_options WHERE code IN ($code_placeholders) AND is_active = 1";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $types = str_repeat('s', count($query_codes));
            $stmt->bind_param($types, ...$query_codes);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // 創建代碼到名稱的映射
            $code_to_name = [];
            while ($row = $result->fetch_assoc()) {
                $code_to_name[$row['code']] = $row['name'];
            }
            $stmt->close();
            
            // 轉換代碼為名稱
            foreach ($codes as $code) {
                // 處理「其他」選項的自定義文字（格式：OTHER:自定義文字）
                if (strpos($code, 'OTHER:') === 0) {
                    $other_text = substr($code, 6);
                    $names[] = '其他: ' . htmlspecialchars($other_text);
                } elseif (isset($code_to_name[$code])) {
                    $names[] = $code_to_name[$code];
                } else {
                    // 如果找不到對應的名稱，保留原代碼（向後兼容）
                    $names[] = $code;
                }
            }
        } else {
            // 如果查詢失敗，返回原始字符串（向後兼容）
            $names = $codes;
        }
    } else {
        // 如果沒有代碼，返回空
        $names = [];
    }
    
    if ($need_close) {
        $conn->close();
    }
    
    return implode(', ', $names);
}

/**
 * 將活動類型代碼轉換為顯示名稱
 * @param string $code 活動類型代碼（如：TYPE_SCHOOL_VISIT）
 * @param mysqli $conn 資料庫連接（可選，如果不提供則會創建新連接）
 * @return string 轉換後的顯示名稱
 */
function convertActivityTypeCodeToName($code, $conn = null) {
    if (empty($code)) {
        return '';
    }
    
    // 如果沒有提供連接，創建一個
    $need_close = false;
    if ($conn === null) {
        $conn = getDatabaseConnection();
        $need_close = true;
    }
    
    // 查詢 activity_type_options 表
    $sql = "SELECT name FROM activity_type_options WHERE code = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $name = $row['name'];
            $stmt->close();
            
            if ($need_close) {
                $conn->close();
            }
            
            return $name;
        }
        $stmt->close();
    }
    
    if ($need_close) {
        $conn->close();
    }
    
    // 如果找不到對應的名稱，返回原代碼（向後兼容）
    return $code;
}
?>
