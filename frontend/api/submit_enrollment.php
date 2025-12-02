<?php
// 立即設定錯誤處理和輸出緩衝，確保只輸出JSON
ob_start(); // 開始輸出緩衝
error_reporting(E_ALL);
ini_set('display_errors', 0); // 不顯示錯誤，記錄到日誌
ini_set('log_errors', 1);

// 設定錯誤日誌文件位置（如果未設定）
if (ini_get('error_log') == '') {
    // 嘗試設定錯誤日誌到當前目錄
    $log_file = __DIR__ . '/enrollment_errors.log';
    ini_set('error_log', $log_file);
}

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
    $errorMessage = $exception->getMessage();
    $errorTrace = $exception->getTraceAsString();
    
    error_log("=== 未捕獲的異常 ===");
    error_log("異常訊息: " . $errorMessage);
    error_log("異常堆疊: " . $errorTrace);
    error_log("異常類型: " . get_class($exception));
    error_log("檔案: " . $exception->getFile() . " 行號: " . $exception->getLine());
    
    if (ob_get_level() > 0) {
        @ob_clean(); // 使用 @ 抑制可能的警告
    }
    
    http_response_code(500);
    
    // 在開發環境中提供更詳細的錯誤信息
    $is_dev = (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false));
    $is_debug = isset($_GET['debug']) || isset($_POST['debug']);
    
    $response = [
        'success' => false,
        'message' => '系統錯誤，請稍後再試'
    ];
    
    if ($is_dev || $is_debug) {
        $response['debug'] = [
            'error_message' => $errorMessage,
            'error_type' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ];
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    if (ob_get_level() > 0) {
        @ob_end_flush();
    }
    exit;
}

set_error_handler('handleError');
set_exception_handler('handleException');

try {
    // 載入 session 配置
    $session_config_path = __DIR__ . '/../session_config.php';
    if (!file_exists($session_config_path)) {
        throw new Exception("找不到 session_config.php 檔案: $session_config_path");
    }
    require_once $session_config_path;
    
    // 載入 Gmail 郵件配置（可選，如果不存在不影響）
    $email_config_path = __DIR__ . '/../../backend/config/email_config.php';
    if (file_exists($email_config_path)) {
        require_once $email_config_path;
    } else {
        error_log("警告: 找不到 email_config.php 檔案: $email_config_path，郵件功能可能無法使用");
    }
    
    // 清除緩衝區中的任何意外輸出（使用 @ 抑制警告）
    @ob_clean();
} catch (Exception $e) {
    error_log("載入配置檔案時發生錯誤: " . $e->getMessage());
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
            .header { background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%); color: white; padding: 20px; text-align: center; }
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
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_username, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    // 檢查並創建 enrollment_intention 表（如果不存在）
    $stmt = $pdo->query("SHOW TABLES LIKE 'enrollment_intention'");
    if ($stmt->rowCount() == 0) {
        // 表不存在，創建表
        $createTableSQL = "CREATE TABLE enrollment_intention (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL COMMENT '姓名',
            identity TINYINT(1) NOT NULL COMMENT '1=學生 , 2=家長',
            gender TINYINT(1) DEFAULT NULL COMMENT '1=男 , 2=女',
            phone1 VARCHAR(20) NOT NULL COMMENT '聯絡電話1',
            phone2 VARCHAR(20) DEFAULT NULL COMMENT '聯絡電話2',
            email VARCHAR(100) DEFAULT NULL COMMENT '電子郵件',
            junior_high VARCHAR(20) DEFAULT NULL COMMENT '就讀或畢業國中，關聯school_data.school_code',
            current_grade VARCHAR(20) DEFAULT NULL COMMENT '目前年級，關聯identity_options.code',
            line_id VARCHAR(100) DEFAULT NULL COMMENT 'LineID',
            facebook VARCHAR(200) DEFAULT NULL COMMENT 'Facebook',
            recommended_teacher INT(11) DEFAULT NULL COMMENT '推薦老師關聯user.id',
            remarks TEXT DEFAULT NULL COMMENT '備註',
            assigned_department VARCHAR(50) DEFAULT NULL,
            assigned_teacher_id INT(11) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
            INDEX idx_recommended_teacher (recommended_teacher),
            INDEX idx_assigned_teacher_id (assigned_teacher_id),
            INDEX idx_junior_high (junior_high),
            INDEX idx_current_grade (current_grade)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='就讀意願登錄表'";
        
        $pdo->exec($createTableSQL);
        error_log("已自動創建 enrollment_intention 資料表");
    } else {
        // 表已存在，不需要添加额外字段
        // junior_high 和 current_grade 字段直接存储关联代码
        error_log("enrollment_intention 表已存在，使用现有结构");
    }
    
    // 檢查並創建 enrollment_choices 表（如果不存在）
    $stmt = $pdo->query("SHOW TABLES LIKE 'enrollment_choices'");
    if ($stmt->rowCount() == 0) {
        // 表不存在，創建表（先不添加外鍵約束，避免依賴問題）
        try {
            $createChoicesTableSQL = "CREATE TABLE enrollment_choices (
                enrollment_id INT(11) NOT NULL COMMENT '就讀意願記錄ID',
                choice_order TINYINT(1) NOT NULL COMMENT '志願123',
                department_code VARCHAR(50) DEFAULT NULL COMMENT '科系代碼關聯departments.code',
                system_code VARCHAR(20) DEFAULT NULL COMMENT '學制代碼關聯education_systems.code',
                PRIMARY KEY (enrollment_id, choice_order),
                INDEX idx_enrollment_id (enrollment_id),
                INDEX idx_department_code (department_code),
                INDEX idx_system_code (system_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='就讀意願選項列表'";
            
            $pdo->exec($createChoicesTableSQL);
            error_log("已自動創建 enrollment_choices 資料表");
            
            // 嘗試添加外鍵約束（如果相關表存在）
            try {
                // 檢查 enrollment_intention 表是否存在
                $check_table = $pdo->query("SHOW TABLES LIKE 'enrollment_intention'");
                if ($check_table->rowCount() > 0) {
                    $pdo->exec("ALTER TABLE enrollment_choices ADD CONSTRAINT fk_enrollment_id FOREIGN KEY (enrollment_id) REFERENCES enrollment_intention(id) ON DELETE CASCADE");
                }
            } catch (PDOException $e) {
                error_log("添加 enrollment_id 外鍵約束失敗（可能已存在）: " . $e->getMessage());
            }
            
            try {
                // 檢查 departments 表是否存在
                $check_table = $pdo->query("SHOW TABLES LIKE 'departments'");
                if ($check_table->rowCount() > 0) {
                    $pdo->exec("ALTER TABLE enrollment_choices ADD CONSTRAINT fk_department_code FOREIGN KEY (department_code) REFERENCES departments(code) ON DELETE SET NULL");
                }
            } catch (PDOException $e) {
                error_log("添加 department_code 外鍵約束失敗（可能已存在）: " . $e->getMessage());
            }
            
            try {
                // 檢查 education_systems 表是否存在
                $check_table = $pdo->query("SHOW TABLES LIKE 'education_systems'");
                if ($check_table->rowCount() > 0) {
                    $pdo->exec("ALTER TABLE enrollment_choices ADD CONSTRAINT fk_system_code FOREIGN KEY (system_code) REFERENCES education_systems(code) ON DELETE SET NULL");
                }
            } catch (PDOException $e) {
                error_log("添加 system_code 外鍵約束失敗（可能已存在）: " . $e->getMessage());
            }
        } catch (PDOException $e) {
            error_log("創建 enrollment_choices 表時發生錯誤: " . $e->getMessage());
            // 不中斷執行，繼續處理
        }
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

// 驗證學制選擇（如果科系有學制選項，必須選擇學制）
$validateSystemForDepartment = function($department_name, $system_value, $choice_number) use ($pdo) {
    if (empty($department_name) || $department_name === '無特定') {
        return null; // 無特定不需要驗證
    }
    
    // 查詢科系的可用學制
    try {
        $stmt = $pdo->prepare("SELECT available_systems FROM departments WHERE name = ? LIMIT 1");
        $stmt->execute([$department_name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && !empty($result['available_systems'])) {
            $available_systems = json_decode($result['available_systems'], true);
            
            // 如果科系有學制選項，必須選擇學制
            if (is_array($available_systems) && count($available_systems) > 0) {
                if (empty($system_value)) {
                    return "請選擇「就讀意願{$choice_number}」的學制";
                }
                
                // 驗證選擇的學制是否在可用學制列表中
                if (!in_array($system_value, $available_systems)) {
                    return "「就讀意願{$choice_number}」選擇的學制無效";
                }
            }
        }
    } catch (PDOException $e) {
        error_log("查詢科系學制失敗: " . $e->getMessage());
        // 查詢失敗時不阻擋提交，但記錄錯誤
    }
    
    return null; // 驗證通過
};

// 檢查每個就讀意願的學制（system1, system2, system3 已在前面獲取）
$system_error = null;
if (!empty($intention1) && $intention1 !== '無特定') {
    $system_error = $validateSystemForDepartment($intention1, $system1, '一');
    if ($system_error) {
        if (ob_get_level() > 0) {
            @ob_clean();
        }
        echo json_encode([
            'success' => false,
            'message' => $system_error
        ]);
        if (ob_get_level() > 0) {
            @ob_end_flush();
        }
        exit;
    }
}

if (!empty($intention2) && $intention2 !== '無特定') {
    $system_error = $validateSystemForDepartment($intention2, $system2, '二');
    if ($system_error) {
        if (ob_get_level() > 0) {
            @ob_clean();
        }
        echo json_encode([
            'success' => false,
            'message' => $system_error
        ]);
        if (ob_get_level() > 0) {
            @ob_end_flush();
        }
        exit;
    }
}

if (!empty($intention3) && $intention3 !== '無特定') {
    $system_error = $validateSystemForDepartment($intention3, $system3, '三');
    if ($system_error) {
        if (ob_get_level() > 0) {
            @ob_clean();
        }
        echo json_encode([
            'success' => false,
            'message' => $system_error
        ]);
        if (ob_get_level() > 0) {
            @ob_end_flush();
        }
        exit;
    }
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

// 驗證是否從系統選項中選擇學校
// junior_high 現在直接存儲 school_code，需要驗證 school_code 是否存在於資料庫中
if (!empty($junior_high)) {
    $verify_school_stmt = $pdo->prepare("SELECT school_code FROM school_data WHERE school_code = ? AND is_active = 1 LIMIT 1");
    $verify_school_stmt->execute([$junior_high]);
    $verify_school_result = $verify_school_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$verify_school_result) {
        // school_code 不存在於資料庫中，可能是用戶自行輸入的無效值
        if (ob_get_level() > 0) {
            @ob_clean();
        }
        echo json_encode([
            'success' => false,
            'message' => '請從系統提供的選項中選擇學校，不能自行輸入'
        ]);
        if (ob_get_level() > 0) {
            @ob_end_flush();
        }
        exit;
    }
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
    error_log("=== 開始處理表單數據 ===");
    error_log("接收到的POST數據: " . print_r($_POST, true));
    
    // 轉換 identity：'學生'=1, '家長'=2
    $identity_num = ($identity === '學生') ? 1 : (($identity === '家長') ? 2 : null);
    if ($identity_num === null) {
        error_log("身分別格式錯誤: identity=" . ($identity ?? 'NULL'));
        throw new Exception("身分別格式錯誤: " . ($identity ?? 'NULL'));
    }
    
    // 轉換 gender：'男'=1, '女'=2
    $gender_num = null;
    if (!empty($gender)) {
        $gender_num = ($gender === '男') ? 1 : (($gender === '女') ? 2 : null);
    }
    
    // 轉換推薦老師名字為 user.id
    $recommended_teacher_id = null;
    if (!empty($recommended_teacher)) {
        // 先檢查是否已經是數字 ID
        if (is_numeric($recommended_teacher)) {
            // 驗證 ID 是否存在於 user 表中，且角色為老師（TEA）
            $verify_teacher_stmt = $pdo->prepare("SELECT u.id FROM user u 
                                                  JOIN teacher t ON u.id = t.user_id 
                                                  WHERE u.id = ? AND u.role = 'TEA' LIMIT 1");
            $verify_teacher_stmt->execute([$recommended_teacher]);
            $verify_teacher_result = $verify_teacher_stmt->fetch(PDO::FETCH_ASSOC);
            if ($verify_teacher_result) {
                $recommended_teacher_id = (int)$verify_teacher_result['id'];
                error_log("推薦老師驗證成功: ID=" . $recommended_teacher_id);
            } else {
                error_log("推薦老師驗證失敗: ID=" . $recommended_teacher . " 不存在或角色不是 TEA");
            }
        } else {
            // 從 user 表查詢，通過 name 找到對應的 id（因為 teacher 表沒有 name 欄位）
            $teacher_stmt = $pdo->prepare("SELECT u.id FROM user u 
                                          JOIN teacher t ON u.id = t.user_id 
                                          WHERE u.name = ? AND u.role = 'TEA' LIMIT 1");
            $teacher_stmt->execute([$recommended_teacher]);
            $teacher_result = $teacher_stmt->fetch(PDO::FETCH_ASSOC);
            if ($teacher_result) {
                $recommended_teacher_id = (int)$teacher_result['id'];
                error_log("推薦老師驗證成功: 名稱=" . $recommended_teacher . ", ID=" . $recommended_teacher_id);
            } else {
                error_log("推薦老師驗證失敗: 名稱=" . $recommended_teacher . " 不存在或角色不是 TEA");
            }
        }
    }
    
    // junior_high 字段直接存储 school_code（用于外键关联）
    // 前端已经传递了 school_code，直接验证并使用
    $junior_high_code = null;
    
    if (!empty($junior_high)) {
        // 验证 school_code 是否存在于数据库中
        $verify_stmt = $pdo->prepare("SELECT school_code FROM school_data WHERE school_code = ? AND is_active = 1 LIMIT 1");
        $verify_stmt->execute([$junior_high]);
        $verify_result = $verify_stmt->fetch(PDO::FETCH_ASSOC);
        if ($verify_result && !empty($verify_result['school_code'])) {
            $junior_high_code = $verify_result['school_code'];
        } else {
            // 如果验证失败，可能是旧格式（学校名称），尝试转换
            if (preg_match('/^(.+?)\s*\(/', $junior_high, $matches)) {
                $school_name = trim($matches[1]);
                $school_stmt = $pdo->prepare("SELECT school_code FROM school_data WHERE name = ? AND is_active = 1 LIMIT 1");
                $school_stmt->execute([$school_name]);
                $school_result = $school_stmt->fetch(PDO::FETCH_ASSOC);
                if ($school_result && !empty($school_result['school_code'])) {
                    $junior_high_code = $school_result['school_code'];
                }
            }
        }
    }
    
    // 如果无法获取有效的 school_code，抛出错误
    if (empty($junior_high_code)) {
        throw new Exception("無法找到有效的學校代碼，請從系統選項中選擇學校");
    }
    
    // current_grade 字段直接存储 identity_options.code（如 J1, J2, J3）
    // 前端已经传递了 code，直接验证并使用
    $current_grade_code = null;
    
    if (!empty($current_grade)) {
        // 先檢查是否已經是 code（J1, J2, J3）
        if (preg_match('/^J[1-3]$/', $current_grade)) {
            // 驗證 code 是否存在於 identity_options 表中
            $verify_grade_stmt = $pdo->prepare("SELECT code FROM identity_options WHERE code = ? LIMIT 1");
            $verify_grade_stmt->execute([$current_grade]);
            $verify_grade_result = $verify_grade_stmt->fetch(PDO::FETCH_ASSOC);
            if ($verify_grade_result) {
                $current_grade_code = $current_grade;
            }
        } else {
            // 如果是顯示名稱，查詢對應的 code
            $grade_stmt = $pdo->prepare("SELECT code FROM identity_options WHERE name = ? LIMIT 1");
            $grade_stmt->execute([$current_grade]);
            $grade_result = $grade_stmt->fetch(PDO::FETCH_ASSOC);
            if ($grade_result) {
                $current_grade_code = $grade_result['code'];
            }
        }
    }
    
    // 如果 current_grade 有值但无法获取有效的 code，设为 NULL（允许为空）
    // 但不抛出错误，因为这是可选字段
    
    // 轉換科系名稱為 departments.code
    $getDepartmentCode = function($department_name) use ($pdo) {
        if (empty($department_name) || $department_name === '無特定') {
            return null;
        }
        $stmt = $pdo->prepare("SELECT code FROM departments WHERE name = ? LIMIT 1");
        $stmt->execute([$department_name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['code'] : null;
    };
    
    // 轉換學制名稱為 education_systems.code
    $getSystemCode = function($system_name) use ($pdo) {
        if (empty($system_name)) {
            return null;
        }
        $stmt = $pdo->prepare("SELECT code FROM education_systems WHERE name = ? LIMIT 1");
        $stmt->execute([$system_name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['code'] : null;
    };
    
    // 插入資料到 enrollment_intention 表
    // junior_high 直接存储 school_code，current_grade 直接存储 code
    $sql = "INSERT INTO enrollment_intention (
        name, identity, gender, phone1, phone2, email,
        junior_high, current_grade,
        line_id, facebook, recommended_teacher, remarks,
        created_at
    ) VALUES (
        :name, :identity, :gender, :phone1, :phone2, :email,
        :junior_high, :current_grade,
        :line_id, :facebook, :recommended_teacher, :remarks,
        NOW()
    )";

    // 記錄 SQL 語句和參數（用於調試）
    error_log("=== 準備執行 SQL ===");
    error_log("SQL語句: " . $sql);
    error_log("基本參數: name=$name, identity=$identity_num, gender=" . ($gender_num ?? 'NULL') . ", phone1=$phone1, email=$email");
    error_log("關聯字段: junior_high(school_code)=" . ($junior_high_code ?? 'NULL') . ", current_grade(code)=" . ($current_grade_code ?? 'NULL'));
    error_log("推薦老師處理: 原始值=" . ($recommended_teacher ?? 'NULL') . ", 轉換後ID=" . ($recommended_teacher_id ?? 'NULL'));
    
    $stmt = $pdo->prepare($sql);
    if ($stmt === false) {
        $errorInfo = $pdo->errorInfo();
        error_log("SQL準備失敗: " . print_r($errorInfo, true));
        error_log("SQL語句: " . $sql);
        
        // 檢查資料表結構
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM enrollment_intention")->fetchAll(PDO::FETCH_COLUMN);
            error_log("enrollment_intention 表現有欄位: " . implode(', ', $cols));
        } catch (Exception $e) {
            error_log("無法檢查表結構: " . $e->getMessage());
        }
        
        throw new Exception("SQL準備失敗: " . ($errorInfo[2] ?? '未知錯誤'));
    }
    
    // 使用 bindValue 而不是 bindParam，特別是對於可能為 NULL 的值
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':identity', $identity_num, PDO::PARAM_INT);
    
    // gender 可能為 NULL
    if ($gender_num !== null) {
        $stmt->bindValue(':gender', $gender_num, PDO::PARAM_INT);
    } else {
        $stmt->bindValue(':gender', null, PDO::PARAM_NULL);
    }
    
    $stmt->bindValue(':phone1', $phone1);
    $stmt->bindValue(':phone2', $phone2 ? $phone2 : null, $phone2 ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(':email', $email);
    // junior_high 直接存储 school_code
    $stmt->bindValue(':junior_high', $junior_high_code ? $junior_high_code : null, $junior_high_code ? PDO::PARAM_STR : PDO::PARAM_NULL);
    // current_grade 直接存储 code
    $stmt->bindValue(':current_grade', $current_grade_code ? $current_grade_code : null, $current_grade_code ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(':line_id', $line_id ? $line_id : null, $line_id ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(':facebook', $facebook ? $facebook : null, $facebook ? PDO::PARAM_STR : PDO::PARAM_NULL);
    
    // recommended_teacher 可能為 NULL
    if ($recommended_teacher_id !== null) {
        $stmt->bindValue(':recommended_teacher', $recommended_teacher_id, PDO::PARAM_INT);
    } else {
        $stmt->bindValue(':recommended_teacher', null, PDO::PARAM_NULL);
    }
    
    $stmt->bindValue(':remarks', $remarks ? $remarks : null, $remarks ? PDO::PARAM_STR : PDO::PARAM_NULL);

    try {
        $executeResult = $stmt->execute();
    } catch (PDOException $e) {
        error_log("SQL執行異常: " . $e->getMessage());
        error_log("SQL語句: " . $sql);
        // 從 PDOStatement 獲取錯誤信息（如果可用）
        $errorInfo = $stmt->errorInfo();
        if ($errorInfo && $errorInfo[0] !== '00000') {
            error_log("錯誤信息: " . print_r($errorInfo, true));
        } else {
            error_log("錯誤信息: " . $e->getMessage());
        }
        error_log("錯誤代碼: " . $e->getCode());
        throw $e; // 重新拋出異常，讓外層的catch處理
    }
    
    if ($executeResult) {
        // 獲取剛插入的 enrollment_intention id
        $enrollment_id = $pdo->lastInsertId();
        
        if (!$enrollment_id) {
            error_log("警告: 插入成功但無法獲取 lastInsertId");
            throw new Exception("無法獲取插入記錄的ID");
        }
        
        // 插入志願資料到 enrollment_choices 表
        $choices = [
            ['order' => 1, 'department' => $intention1, 'system' => $system1],
            ['order' => 2, 'department' => $intention2, 'system' => $system2],
            ['order' => 3, 'department' => $intention3, 'system' => $system3]
        ];
        
        $choice_sql = "INSERT INTO enrollment_choices (enrollment_id, choice_order, department_code, system_code) VALUES (:enrollment_id, :choice_order, :department_code, :system_code)";
        $choice_stmt = $pdo->prepare($choice_sql);
        
        foreach ($choices as $choice) {
            if (!empty($choice['department']) && $choice['department'] !== '無特定') {
                $dept_code = $getDepartmentCode($choice['department']);
                $sys_code = $getSystemCode($choice['system']);
                
                if ($dept_code) {
                    try {
                        $choice_stmt->bindValue(':enrollment_id', $enrollment_id, PDO::PARAM_INT);
                        $choice_stmt->bindValue(':choice_order', $choice['order'], PDO::PARAM_INT);
                        $choice_stmt->bindValue(':department_code', $dept_code, PDO::PARAM_STR);
                        $choice_stmt->bindValue(':system_code', $sys_code ? $sys_code : null, $sys_code ? PDO::PARAM_STR : PDO::PARAM_NULL);
                        $choice_stmt->execute();
                    } catch (PDOException $e) {
                        // 記錄錯誤但不中斷流程
                        error_log("插入志願資料失敗: " . $e->getMessage() . " - 志願: " . $choice['order']);
                    }
                }
            }
        }
        // 準備郵件資料（轉換回文字格式用於顯示）
        $emailData = [
            'name' => $name,
            'identity' => $identity, // 保持原始文字格式
            'gender' => $gender, // 保持原始文字格式
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
            'recommended_teacher' => $recommended_teacher, // 保持原始名字格式
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
        // 執行失敗，獲取錯誤信息
        $errorInfo = $stmt->errorInfo();
        error_log("SQL執行失敗: " . print_r($errorInfo, true));
        error_log("SQL語句: " . $sql);
        error_log("綁定參數: name=$name, identity=$identity_num, gender=" . ($gender_num ?? 'NULL'));
        
        if (ob_get_level() > 0) {
            @ob_clean();
        }
        
        $errorMsg = '提交失敗，請稍後再試';
        $is_dev = (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false));
        $response = [
            'success' => false,
            'message' => $errorMsg
        ];
        
        if ($is_dev && !empty($errorInfo[2])) {
            $response['debug'] = [
                'sql_error' => $errorInfo[2],
                'sql_state' => $errorInfo[0] ?? 'N/A',
                'driver_code' => $errorInfo[1] ?? 'N/A'
            ];
        }
        
        echo json_encode($response);
    }

} catch (PDOException $e) {
    // 記錄詳細錯誤信息
    $errorMessage = $e->getMessage();
    $errorCode = $e->getCode();
    
    // PDOException 的 errorInfo 是受保護的屬性，需要通過反射訪問
    // 或者直接從異常消息中提取信息
    $errorInfo = ['N/A', 'N/A', $errorMessage];
    try {
        $reflection = new ReflectionClass($e);
        if ($reflection->hasProperty('errorInfo')) {
            $property = $reflection->getProperty('errorInfo');
            $property->setAccessible(true);
            $errorInfoValue = $property->getValue($e);
            if (is_array($errorInfoValue) && count($errorInfoValue) >= 3) {
                $errorInfo = $errorInfoValue;
            }
        }
    } catch (Exception $reflectionError) {
        // 如果反射失敗，使用異常消息
        error_log("無法通過反射獲取 errorInfo，使用異常消息");
    }
    
    error_log("就讀意願提交資料庫錯誤 [Code: $errorCode]: " . $errorMessage);
    error_log("SQL State: " . ($errorInfo[0] ?? 'N/A') . ", Driver Code: " . ($errorInfo[1] ?? 'N/A') . ", Driver Message: " . ($errorInfo[2] ?? 'N/A'));
    error_log("錯誤堆疊: " . $e->getTraceAsString());
    
    // 清除任何意外輸出（使用 @ 抑制警告）
    if (ob_get_level() > 0) {
        @ob_clean();
    }
    
    // 根據錯誤類型提供更友好的錯誤訊息
    $userMessage = '系統錯誤，請稍後再試';
    $debugInfo = [];
    $is_dev = (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false));
    $is_debug = isset($_GET['debug']) || isset($_POST['debug']);
    
    if (strpos($errorMessage, 'Table') !== false && strpos($errorMessage, "doesn't exist") !== false) {
        $userMessage = '資料表不存在，請聯繫系統管理員';
    } elseif (strpos($errorMessage, 'Duplicate entry') !== false) {
        $userMessage = '資料已存在，請勿重複提交';
    } elseif (strpos($errorMessage, 'SQLSTATE') !== false || strpos($errorMessage, 'SQL') !== false || strpos($errorMessage, 'column') !== false) {
        $userMessage = '資料庫操作失敗，請檢查資料格式';
    }
    
    // 在開發環境或調試模式下提供詳細錯誤信息
    if ($is_dev || $is_debug) {
        $debugInfo = [
            'error_message' => $errorMessage,
            'error_code' => $errorCode,
            'sql_state' => $errorInfo[0] ?? 'N/A',
            'driver_code' => $errorInfo[1] ?? 'N/A',
            'driver_message' => $errorInfo[2] ?? 'N/A'
        ];
    }
    
    http_response_code(500);
    $response = [
        'success' => false,
        'message' => $userMessage
    ];
    if (!empty($debugInfo)) {
        $response['debug'] = $debugInfo;
    }
    echo json_encode($response);
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
