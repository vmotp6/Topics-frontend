<?php
// 立即設定錯誤處理和輸出緩衝，確保只輸出JSON
ob_start(); // 開始輸出緩衝
error_reporting(E_ALL);
ini_set('display_errors', 0); // 不顯示錯誤，記錄到日誌
ini_set('log_errors', 1);

// 設定回應為 JSON（必須在任何輸出之前）
header('Content-Type: application/json; charset=utf-8');

// 自定義錯誤處理函數，確保錯誤時返回JSON
function handleError($errno, $errstr, $errfile, $errline) {
    // 只處理嚴重的錯誤，忽略警告和通知
    if (!(error_reporting() & $errno)) {
        return false; // 錯誤被錯誤報告級別忽略
    }
    
    // 忽略警告（如 ob_clean 在空緩衝區時的警告）
    if ($errno === E_WARNING || $errno === E_NOTICE || $errno === E_DEPRECATED) {
        // 只記錄到日誌，不中斷執行
        error_log("PHP Warning/Notice [$errno]: $errstr in $errfile on line $errline");
        return true; // 告訴PHP我們已經處理了這個錯誤
    }
    
    // 只處理致命錯誤
    if ($errno === E_ERROR || $errno === E_PARSE || $errno === E_CORE_ERROR || $errno === E_COMPILE_ERROR) {
        error_log("PHP Fatal Error [$errno]: $errstr in $errfile on line $errline");
        if (ob_get_level() > 0) {
            @ob_clean(); // 使用 @ 抑制可能的警告
        }
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => '系統錯誤，請稍後再試'
        ]);
        if (ob_get_level() > 0) {
            @ob_end_flush();
        }
        exit;
    }
    
    return false; // 繼續執行標準錯誤處理
}

// 自定義異常處理函數
function handleException($exception) {
    error_log("Uncaught Exception: " . $exception->getMessage());
    error_log("異常堆疊: " . $exception->getTraceAsString());
    if (ob_get_level() > 0) {
        @ob_clean(); // 使用 @ 抑制可能的警告
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '系統錯誤，請稍後再試'
    ]);
    if (ob_get_level() > 0) {
        @ob_end_flush();
    }
    exit;
}

set_error_handler('handleError');
set_exception_handler('handleException');

try {
    // 載入 session 配置
    require_once '../session_config.php';
    
    // 載入 Gmail 郵件配置
    require_once '../../backend/config/email_config.php';
    
    // 清除緩衝區中的任何意外輸出（使用 @ 抑制警告）
    @ob_clean();
} catch (Exception $e) {
    handleException($e);
}

/**
 * 發送 Gmail 通知
 */
function sendEnrollmentNotification($data) {
    $config = getEmailConfig();
    
    // 檢查是否啟用郵件通知
    if (!$config['enable_notifications']) {
        return false;
    }
    
    // 檢查是否有填寫電子郵件
    if (empty($data['email'])) {
        return false;
    }
    
    // 設定收件人（發送給提交表單的人）
    $to_email = $data['email']; // 使用表單中填寫的電子郵件
    $to_name = $data['name']; // 使用表單中填寫的姓名
    
    // 郵件主題
    $subject = '【康寧大學】就讀意願登錄確認通知';
    
    // 郵件內容
    $message = "
    <html>
    <head>
        <meta charset='utf-8'>
        <style>
            body { font-family: 'Microsoft JhengHei', Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f8f9fa; }
            .field { margin: 10px 0; padding: 10px; background: white; border-left: 4px solid #667eea; }
            .label { font-weight: bold; color: #667eea; }
            .footer { background: #2c3e50; color: white; padding: 15px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h2>🎓 康寧大學就讀意願登錄確認</h2>
        </div>
        
        <div class='content'>
            <p>親愛的 {$data['name']}，您好！</p>
            <p>感謝您對康寧大學的關注！我們已收到您的就讀意願登錄，詳細資訊如下：</p>
            
            <div class='field'>
                <span class='label'>姓名：</span>{$data['name']}
            </div>
            
            <div class='field'>
                <span class='label'>身分別：</span>{$data['identity']}
            </div>
            
            <div class='field'>
                <span class='label'>性別：</span>{$data['gender']}
            </div>
            
            <div class='field'>
                <span class='label'>聯絡電話1：</span>{$data['phone1']}
            </div>
            
            <div class='field'>
                <span class='label'>聯絡電話2：</span>{$data['phone2']}
            </div>
            
            <div class='field'>
                <span class='label'>電子郵件：</span>{$data['email']}
            </div>
            
            <div class='field'>
                <span class='label'>就讀意願一：</span>{$data['intention1']} - {$data['system1']}
            </div>
            
            <div class='field'>
                <span class='label'>就讀意願二：</span>{$data['intention2']} - {$data['system2']}
            </div>
            
            <div class='field'>
                <span class='label'>就讀意願三：</span>{$data['intention3']} - {$data['system3']}
            </div>
            
            <div class='field'>
                <span class='label'>就讀或畢業國中：</span>{$data['junior_high']}
            </div>
            
            <div class='field'>
                <span class='label'>目前年級：</span>{$data['current_grade']}
            </div>
            
            <div class='field'>
                <span class='label'>LineID：</span>{$data['line_id']}
            </div>
            
            <div class='field'>
                <span class='label'>Facebook：</span>{$data['facebook']}
            </div>
            
            <div class='field'>
                <span class='label'>推薦老師：</span>{$data['recommended_teacher']}
            </div>
            
            <div class='field'>
                <span class='label'>備註：</span>{$data['remarks']}
            </div>
            
            <div class='field'>
                <span class='label'>提交時間：</span>" . date('Y-m-d H:i:s') . "
            </div>
            
            <div style='margin-top: 30px; padding: 20px; background: #e8f4fd; border-radius: 8px; border-left: 4px solid #667eea;'>
                <h3 style='color: #667eea; margin-top: 0;'>📞 後續聯絡</h3>
                <p>我們的招生組將在收到您的資料後，儘快與您聯絡，請保持電話暢通。</p>
                <p>如有任何疑問，歡迎隨時與我們聯繫：</p>
                <ul>
                    <li>📧 招生組信箱：admissions@knu.edu.tw</li>
                    <li>📞 招生組電話：(02) 2632-1181</li>
                    <li>🌐 官方網站：https://www.knu.edu.tw</li>
                </ul>
            </div>
        </div>
        
        <div class='footer'>
            <p>此郵件由康寧大學招生平台自動發送，請勿直接回覆。</p>
            <p>如有疑問，請透過上述聯絡方式與我們聯繫。</p>
        </div>
    </body>
    </html>
    ";
    
    // 郵件標頭
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . $config['sender_name'] . ' <' . $config['sender_email'] . '>',
        'Reply-To: ' . $config['sender_email'],
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // 發送郵件
    return mail($to_email, $subject, $message, implode("\r\n", $headers));
}

// 資料庫連接
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 檢查並創建 enrollment_intention 表（如果不存在）
    $stmt = $pdo->query("SHOW TABLES LIKE 'enrollment_intention'");
    if ($stmt->rowCount() == 0) {
        // 表不存在，創建表
        $createTableSQL = "CREATE TABLE enrollment_intention (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL COMMENT '姓名',
            identity ENUM('學生', '家長') NOT NULL COMMENT '身分別',
            gender ENUM('男', '女') DEFAULT NULL COMMENT '性別',
            phone1 VARCHAR(20) NOT NULL COMMENT '聯絡電話1',
            phone2 VARCHAR(20) DEFAULT NULL COMMENT '聯絡電話2',
            email VARCHAR(100) DEFAULT NULL COMMENT '電子郵件',
            intention1 VARCHAR(50) DEFAULT NULL COMMENT '就讀意願一',
            intention2 VARCHAR(50) DEFAULT NULL COMMENT '就讀意願二',
            intention3 VARCHAR(50) DEFAULT NULL COMMENT '就讀意願三',
            system1 VARCHAR(20) DEFAULT NULL COMMENT '學制一',
            system2 VARCHAR(20) DEFAULT NULL COMMENT '學制二',
            system3 VARCHAR(20) DEFAULT NULL COMMENT '學制三',
            junior_high VARCHAR(200) DEFAULT NULL COMMENT '就讀或畢業國中',
            current_grade VARCHAR(20) DEFAULT NULL COMMENT '目前年級',
            line_id VARCHAR(100) DEFAULT NULL COMMENT 'LineID',
            facebook VARCHAR(200) DEFAULT NULL COMMENT 'Facebook',
            recommended_teacher VARCHAR(100) DEFAULT NULL COMMENT '推薦老師',
            remarks TEXT DEFAULT NULL COMMENT '備註',
            captcha VARCHAR(10) DEFAULT NULL COMMENT '驗證碼',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='就讀意願登錄表'";
        
        $pdo->exec($createTableSQL);
        error_log("已自動創建 enrollment_intention 資料表");
    }
} catch (PDOException $e) {
    error_log("資料庫連接錯誤: " . $e->getMessage());
    if (ob_get_level() > 0) {
        @ob_clean();
    }
    echo json_encode([
        'success' => false,
        'message' => '資料庫連接失敗'
    ]);
    if (ob_get_level() > 0) {
        @ob_end_flush();
    }
    exit;
}

// 檢查是否為 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (ob_get_level() > 0) {
        @ob_clean();
    }
    echo json_encode([
        'success' => false,
        'message' => '請使用 POST 方法提交'
    ]);
    if (ob_get_level() > 0) {
        @ob_end_flush();
    }
    exit;
}

// 獲取表單數據
$name = $_POST['name'] ?? '';
$identity = $_POST['identity'] ?? '';
$gender = $_POST['gender'] ?? '';
$phone1 = $_POST['phone1'] ?? '';
$phone2 = $_POST['phone2'] ?? '';
$email = $_POST['email'] ?? '';
$intention1 = $_POST['intention1'] ?? '';
$intention2 = $_POST['intention2'] ?? '';
$intention3 = $_POST['intention3'] ?? '';
$system1 = $_POST['system1'] ?? '';
$system2 = $_POST['system2'] ?? '';
$system3 = $_POST['system3'] ?? '';
$junior_high = $_POST['junior_high'] ?? '';
$current_grade = $_POST['current_grade'] ?? '';
$line_id = $_POST['line_id'] ?? '';
$facebook = $_POST['facebook'] ?? '';
$recommended_teacher = $_POST['recommended_teacher'] ?? '';
$remarks = $_POST['remarks'] ?? '';
$captcha = $_POST['captcha'] ?? '';

// 基本驗證
if (empty($name) || empty($identity) || empty($phone1)) {
    if (ob_get_level() > 0) {
        @ob_clean();
    }
    echo json_encode([
        'success' => false,
        'message' => '請填寫必填欄位'
    ]);
    if (ob_get_level() > 0) {
        @ob_end_flush();
    }
    exit;
}

// 驗證電子郵件（必填）
if (empty($email)) {
    if (ob_get_level() > 0) {
        @ob_clean();
    }
    echo json_encode([
        'success' => false,
        'message' => '請填寫電子郵件信箱'
    ]);
    if (ob_get_level() > 0) {
        @ob_end_flush();
    }
    exit;
}

// 驗證電子郵件格式
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    if (ob_get_level() > 0) {
        @ob_clean();
    }
    echo json_encode([
        'success' => false,
        'message' => '請輸入有效的電子郵件格式'
    ]);
    if (ob_get_level() > 0) {
        @ob_end_flush();
    }
    exit;
}

// 驗證至少一個就讀意願（不能全部是「無特定」）
$hasIntention = false;
if (!empty($intention1) && $intention1 !== '無特定') {
    $hasIntention = true;
} elseif (!empty($intention2) && $intention2 !== '無特定') {
    $hasIntention = true;
} elseif (!empty($intention3) && $intention3 !== '無特定') {
    $hasIntention = true;
}

if (!$hasIntention) {
    if (ob_get_level() > 0) {
        @ob_clean();
    }
    echo json_encode([
        'success' => false,
        'message' => '請至少選擇一個就讀意願（不能全部選擇「無特定」）'
    ]);
    if (ob_get_level() > 0) {
        @ob_end_flush();
    }
    exit;
}

// 驗證就讀或畢業國中（必填）
if (empty($junior_high)) {
    if (ob_get_level() > 0) {
        @ob_clean();
    }
    echo json_encode([
        'success' => false,
        'message' => '請填寫就讀或畢業國中資訊'
    ]);
    if (ob_get_level() > 0) {
        @ob_end_flush();
    }
    exit;
}

// 驗證碼檢查 - 必須與session中的驗證碼匹配
if (empty($captcha)) {
    if (ob_get_level() > 0) {
        @ob_clean();
    }
    echo json_encode([
        'success' => false,
        'message' => '請輸入驗證碼'
    ]);
    if (ob_get_level() > 0) {
        @ob_end_flush();
    }
    exit;
}

// 檢查session中的驗證碼
$captcha_session = $_SESSION['captcha_code'] ?? '';

// 調試信息
error_log("=== 驗證碼檢查開始 ===");
error_log("Session ID: " . session_id());
error_log("Session 狀態: " . session_status());
error_log("Session 中有驗證碼: " . (!empty($captcha_session) ? 'YES (' . strlen($captcha_session) . ' 字符): ' . $captcha_session : 'NO'));
error_log("Session 所有數據: " . print_r($_SESSION, true));

// 如果驗證碼長度不是4，記錄警告
if (!empty($captcha_session) && strlen($captcha_session) !== 4) {
    error_log("警告: Session中的驗證碼長度不是4，實際長度: " . strlen($captcha_session));
}

if (empty($captcha_session)) {
    // 檢查是否是 session 問題
    $session_keys = array_keys($_SESSION);
    error_log("Session 中的所有鍵: " . implode(', ', $session_keys));
    
    if (ob_get_level() > 0) {
        @ob_clean();
    }
    echo json_encode([
        'success' => false,
        'message' => '驗證碼已過期，請刷新驗證碼後再試',
        'debug_info' => 'Session ID: ' . session_id() . ', Session keys: ' . implode(', ', $session_keys)
    ]);
    if (ob_get_level() > 0) {
        @ob_end_flush();
    }
    exit;
}

// 獲取原始值（用於調試）
$captcha_raw = $_POST['captcha'] ?? '';
$session_raw = $_SESSION['captcha_code'] ?? '';

// 最簡單的驗證方式：只轉大寫和去空格（不移除任何字符）
$captcha_normalized = trim(strtoupper($captcha));
$session_normalized = trim(strtoupper($captcha_session));

// 調試信息（記錄到日誌和響應）
$debug_info = [
    'input_original' => $captcha_raw,
    'input_normalized' => $captcha_normalized,
    'session_original' => $session_raw,
    'session_normalized' => $session_normalized,
    'input_length' => strlen($captcha_normalized),
    'session_length' => strlen($session_normalized),
    'session_id' => session_id(),
    'session_keys' => array_keys($_SESSION)
];

error_log("=== 驗證碼驗證開始 ===");
error_log("原始輸入: '" . $captcha_raw . "' (長度:" . strlen($captcha_raw) . ")");
error_log("標準化輸入: '" . $captcha_normalized . "' (長度:" . strlen($captcha_normalized) . ")");
error_log("原始Session: '" . $session_raw . "' (長度:" . strlen($session_raw) . ")");
error_log("標準化Session: '" . $session_normalized . "' (長度:" . strlen($session_normalized) . ")");
error_log("Session ID: " . session_id());

// 驗證驗證碼是否匹配（不區分大小寫，只去空格）
$match = false;

// 方法1: 直接比較標準化後的值
if ($captcha_normalized === $session_normalized && !empty($captcha_normalized) && !empty($session_normalized)) {
    $match = true;
    error_log("驗證碼匹配成功（標準化比較）");
} else {
    // 方法2: 移除所有非字母數字字符後比較
    $captcha_clean = preg_replace('/[^A-Z0-9]/', '', $captcha_normalized);
    $session_clean = preg_replace('/[^A-Z0-9]/', '', $session_normalized);
    
    if ($captcha_clean === $session_clean && !empty($captcha_clean) && !empty($session_clean)) {
        $match = true;
        error_log("驗證碼匹配成功（清理後比較）");
    } else {
        // 方法3: 逐字符比較（最寬鬆）
        $captcha_chars = array_values(array_filter(str_split($captcha_normalized), function($c) {
            return ctype_alnum($c);
        }));
        $session_chars = array_values(array_filter(str_split($session_normalized), function($c) {
            return ctype_alnum($c);
        }));
        
        if (count($captcha_chars) === count($session_chars) && count($captcha_chars) > 0) {
            $char_match = true;
            for ($i = 0; $i < count($captcha_chars); $i++) {
                if (strtoupper($captcha_chars[$i]) !== strtoupper($session_chars[$i])) {
                    $char_match = false;
                    break;
                }
            }
            if ($char_match) {
                $match = true;
                error_log("驗證碼匹配成功（逐字符比較）");
            } else {
                error_log("驗證碼匹配失敗（所有方法都失敗）");
                error_log("逐字符比較: 輸入=[" . implode(',', $captcha_chars) . "], session=[" . implode(',', $session_chars) . "]");
            }
        } else {
            error_log("驗證碼匹配失敗: 字符數量不匹配 (輸入:" . count($captcha_chars) . ", session:" . count($session_chars) . ")");
        }
    }
}

if (!$match) {
    error_log("=== 驗證碼驗證失敗 ===");
    error_log("詳細比較: 輸入標準化='" . $captcha_normalized . "' (長度:" . strlen($captcha_normalized) . "), Session標準化='" . $session_normalized . "' (長度:" . strlen($session_normalized) . ")");
    error_log("輸入十六進制: " . bin2hex($captcha_normalized));
    error_log("Session十六進制: " . bin2hex($session_normalized));
    
    if (ob_get_level() > 0) {
        @ob_clean();
    }
    
    // 在開發環境中返回調試信息，生產環境中隱藏
    $is_dev = (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false));
    $is_debug = isset($_GET['debug']) || isset($_POST['debug']);
    
    $response = [
        'success' => false,
        'message' => '驗證碼錯誤，請重新輸入。如果確認輸入正確，請刷新驗證碼後再試。'
    ];
    
    if ($is_dev || $is_debug) {
        $response['debug'] = $debug_info;
    }
    
    echo json_encode($response);
    if (ob_get_level() > 0) {
        @ob_end_flush();
    }
    exit;
}

error_log("=== 驗證碼驗證成功 ===");

// 驗證成功後，清除session中的驗證碼（防止重複使用）
unset($_SESSION['captcha_code']);

try {
    // 插入資料到資料庫
    $sql = "INSERT INTO enrollment_intention (
        name, identity, gender, phone1, phone2, email,
        intention1, intention2, intention3,
        system1, system2, system3,
        junior_high, current_grade,
        line_id, facebook, recommended_teacher, remarks,
        captcha, created_at
    ) VALUES (
        :name, :identity, :gender, :phone1, :phone2, :email,
        :intention1, :intention2, :intention3,
        :system1, :system2, :system3,
        :junior_high, :current_grade,
        :line_id, :facebook, :recommended_teacher, :remarks,
        :captcha, NOW()
    )";

    $stmt = $pdo->prepare($sql);
    if ($stmt === false) {
        $errorInfo = $pdo->errorInfo();
        error_log("SQL準備失敗: " . print_r($errorInfo, true));
        throw new Exception("SQL準備失敗: " . ($errorInfo[2] ?? '未知錯誤'));
    }
    
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':identity', $identity);
    $stmt->bindParam(':gender', $gender);
    $stmt->bindParam(':phone1', $phone1);
    $stmt->bindParam(':phone2', $phone2);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':intention1', $intention1);
    $stmt->bindParam(':intention2', $intention2);
    $stmt->bindParam(':intention3', $intention3);
    $stmt->bindParam(':system1', $system1);
    $stmt->bindParam(':system2', $system2);
    $stmt->bindParam(':system3', $system3);
    $stmt->bindParam(':junior_high', $junior_high);
    $stmt->bindParam(':current_grade', $current_grade);
    $stmt->bindParam(':line_id', $line_id);
    $stmt->bindParam(':facebook', $facebook);
    $stmt->bindParam(':recommended_teacher', $recommended_teacher);
    $stmt->bindParam(':remarks', $remarks);
    $stmt->bindParam(':captcha', $captcha);

    if ($stmt->execute()) {
        // 準備郵件資料
        $emailData = [
            'name' => $name,
            'identity' => $identity,
            'gender' => $gender,
            'phone1' => $phone1,
            'phone2' => $phone2,
            'email' => $email,
            'intention1' => $intention1,
            'intention2' => $intention2,
            'intention3' => $intention3,
            'system1' => $system1,
            'system2' => $system2,
            'system3' => $system3,
            'junior_high' => $junior_high,
            'current_grade' => $current_grade,
            'line_id' => $line_id,
            'facebook' => $facebook,
            'recommended_teacher' => $recommended_teacher,
            'remarks' => $remarks
        ];
        
        // 發送 Gmail 通知（失敗不影響提交成功）
        try {
            $emailSent = sendEnrollmentNotification($emailData);
        } catch (Exception $emailError) {
            // 郵件發送失敗不影響提交成功
            error_log("郵件發送失敗: " . $emailError->getMessage());
            $emailSent = false;
        }
        
        $message = '就讀意願提交成功！我們將儘快與您聯絡。';
        if ($emailSent && !empty($email)) {
            $message .= ' 確認通知已發送至您的電子郵件信箱。';
        }
        
        if (ob_get_level() > 0) {
            @ob_clean();
        }
        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
    } else {
        if (ob_get_level() > 0) {
            @ob_clean();
        }
        echo json_encode([
            'success' => false,
            'message' => '提交失敗，請稍後再試'
        ]);
    }

} catch (PDOException $e) {
    // 記錄詳細錯誤信息
    $errorMessage = $e->getMessage();
    $errorCode = $e->getCode();
    error_log("就讀意願提交資料庫錯誤 [Code: $errorCode]: " . $errorMessage);
    error_log("SQL State: " . $e->errorInfo[0] . ", Driver Code: " . ($e->errorInfo[1] ?? 'N/A'));
    
    // 清除任何意外輸出（使用 @ 抑制警告）
    if (ob_get_level() > 0) {
        @ob_clean();
    }
    
    // 根據錯誤類型提供更友好的錯誤訊息
    $userMessage = '系統錯誤，請稍後再試';
    if (strpos($errorMessage, 'Table') !== false && strpos($errorMessage, "doesn't exist") !== false) {
        $userMessage = '資料表不存在，請聯繫系統管理員';
    } elseif (strpos($errorMessage, 'Duplicate entry') !== false) {
        $userMessage = '資料已存在，請勿重複提交';
    } elseif (strpos($errorMessage, 'SQLSTATE') !== false) {
        $userMessage = '資料庫操作失敗，請檢查資料格式';
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $userMessage
    ]);
} catch (Exception $e) {
    // 記錄一般錯誤
    error_log("就讀意願提交一般錯誤: " . $e->getMessage());
    error_log("錯誤堆疊: " . $e->getTraceAsString());
    
    // 清除任何意外輸出（使用 @ 抑制警告）
    if (ob_get_level() > 0) {
        @ob_clean();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '系統錯誤，請稍後再試'
    ]);
} finally {
    // 確保輸出緩衝區被正確處理（使用 @ 抑制警告）
    if (ob_get_level() > 0) {
        @ob_end_flush();
    }
}
?>
