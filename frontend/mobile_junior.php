<?php
// ===============================================
// 康寧大學 - 國中學校招生申請系統
// ===============================================

// 設定時區為台灣
date_default_timezone_set('Asia/Taipei');

// 啟用輸出緩衝，避免 header() 錯誤
ob_start();

// 載入設定檔與 Session 設定
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session_config.php';

// 載入 PHPMailer
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --------------------------------------------------
// 建立 PDO 資料庫連線
// --------------------------------------------------
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USERNAME,
        DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('資料庫連接失敗: ' . htmlspecialchars($e->getMessage()));
}

// --------------------------------------------------
// 建立資料表（若不存在）
// --------------------------------------------------
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS junior_school_recruitment_applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            school_name VARCHAR(100) NOT NULL COMMENT '學校名稱',
            city VARCHAR(20) NOT NULL COMMENT '縣市',
            district VARCHAR(20) NOT NULL COMMENT '區/鄉鎮市',
            school_address VARCHAR(255) DEFAULT NULL COMMENT '學校地址',
            contact_name VARCHAR(50) NOT NULL COMMENT '聯絡人姓名',
            contact_title VARCHAR(50) DEFAULT NULL COMMENT '聯絡人職稱',
            contact_phone VARCHAR(20) NOT NULL COMMENT '聯絡電話',
            contact_email VARCHAR(120) NOT NULL COMMENT '聯絡Email',
            preferred_date DATE NOT NULL COMMENT '期望招生日期',
            preferred_time VARCHAR(50) NOT NULL COMMENT '期望時間',
            target_grades VARCHAR(50) NOT NULL COMMENT '目標年級',
            expected_students INT NOT NULL COMMENT '預期學生數',
            venue_type VARCHAR(50) DEFAULT NULL COMMENT '場地類型',
            special_requirements TEXT DEFAULT NULL COMMENT '特殊需求',
            remarks TEXT DEFAULT NULL COMMENT '備註',
            `status` ENUM('pending','approved','rejected','completed','cancelled') DEFAULT 'pending' COMMENT '申請狀態',
            admin_comment TEXT DEFAULT NULL COMMENT '管理員備註',
            admin_id INT DEFAULT NULL COMMENT '處理的管理員ID',
            processed_at TIMESTAMP NULL DEFAULT NULL COMMENT '處理時間',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
            INDEX idx_school_name (school_name),
            INDEX idx_city (city),
            INDEX idx_district (district),
            INDEX idx_status (`status`),
            INDEX idx_created_at (created_at),
            INDEX idx_contact_email (contact_email),
            INDEX idx_preferred_date (preferred_date),
            INDEX idx_admin_id (admin_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='國中學校招生申請表單';
    ");
} catch (PDOException $e) {
    die('建立資料表失敗: ' . htmlspecialchars($e->getMessage()));
}

// --------------------------------------------------
// 初始化變數
// --------------------------------------------------
$search_email = '';
$application_data = null;
$application_list = [];
$selected_application_id = 0;
$action = '';
$result_message = '';
$result_type = '';

// --------------------------------------------------
// 處理 GET 搜尋邏輯
// --------------------------------------------------
if (isset($_GET['application_id'])) {
    $selected_application_id = (int)$_GET['application_id'];
}
if (isset($_GET['action'])) {
    $action = $_GET['action'];
}

if ($action === 'search' && isset($_GET['email'])) {
    $search_email = trim($_GET['email']);
    if ($search_email !== '') {
        try {
            $stmt = $pdo->prepare("SELECT * FROM junior_school_recruitment_applications WHERE contact_email = ? ORDER BY created_at DESC");
            $stmt->execute([$search_email]);
            $application_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($selected_application_id > 0) {
                foreach ($application_list as $app) {
                    if ($app['id'] == $selected_application_id) {
                        $application_data = $app;
                        break;
                    }
                }
            } elseif (count($application_list) > 0) {
                $application_data = $application_list[0];
            }
        } catch (PDOException $e) {
            error_log("搜尋申請資料失敗: " . $e->getMessage());
        }
    }
}

// --------------------------------------------------
// 處理 POST 表單提交邏輯
// --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_post = $_POST['action'] ?? 'submit';
    $application_id = isset($_POST['application_id']) ? (int)$_POST['application_id'] : 0;

    $school_name = trim($_POST['school_name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $school_address = trim($_POST['school_address'] ?? '');
    $contact_name = trim($_POST['contact_name'] ?? '');
    $contact_title = trim($_POST['contact_title'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $preferred_date = trim($_POST['preferred_date'] ?? '');
    $preferred_time = trim($_POST['preferred_time'] ?? '');
    $target_grades = trim($_POST['target_grades'] ?? '');
    $expected_students = trim($_POST['expected_students'] ?? '');
    $venue_type = trim($_POST['venue_type'] ?? '');
    $special_requirements = trim($_POST['special_requirements'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $captcha = trim($_POST['captcha'] ?? '');

    // 表單驗證
    if ($school_name === '' || $city === '' || $district === '' || $contact_name === '' ||
        $contact_phone === '' || $contact_email === '' || $preferred_date === '' ||
        $preferred_time === '' || $target_grades === '' || $expected_students === '') {
        $result_message = '請填寫所有必填欄位。';
        $result_type = 'error';
    } elseif (!empty($school_name) && !preg_match('/^.+ \(.+\)$/', $school_name)) {
        // 驗證就讀國中格式（必須從系統選項中選擇）
        $result_message = '請從系統提供的選項中選擇學校，不能自行輸入';
        $result_type = 'error';
    } elseif ($captcha === '') {
        $result_message = '請輸入驗證碼。';
        $result_type = 'error';
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $result_message = '請輸入有效的 Email 格式。';
        $result_type = 'error';
    } elseif (!is_numeric($expected_students) || (int)$expected_students <= 0) {
        $result_message = '預期學生數必須為大於 0 的數字。';
        $result_type = 'error';
    } else {
        // 驗證碼檢查
        $captcha_session = $_SESSION['captcha_code'] ?? '';
        if (empty($captcha_session)) {
            $result_message = '驗證碼已過期，請重新載入。';
            $result_type = 'error';
        } elseif (strtoupper($captcha) !== strtoupper($captcha_session)) {
            $result_message = '驗證碼錯誤。';
            $result_type = 'error';
        } else {
            unset($_SESSION['captcha_code']); // 驗證成功後清除

            try {
                $pdo->beginTransaction();
                $expected_students_int = (int)$expected_students;

                if ($action_post === 'update' && $application_id > 0) {
                    // 更新申請資料
                    $upd = $pdo->prepare("UPDATE junior_school_recruitment_applications SET
                        school_name=?, city=?, district=?, school_address=?, contact_name=?, contact_title=?,
                        contact_phone=?, contact_email=?, preferred_date=?, preferred_time=?, target_grades=?,
                        expected_students=?, venue_type=?, special_requirements=?, remarks=?, updated_at=CURRENT_TIMESTAMP
                        WHERE id=? AND contact_email=?");

                    $upd->execute([
                        $school_name, $city, $district, $school_address ?: null,
                        $contact_name, $contact_title ?: null, $contact_phone, $contact_email,
                        $preferred_date, $preferred_time, $target_grades,
                        $expected_students_int, $venue_type ?: null, $special_requirements ?: null, $remarks ?: null,
                        $application_id, $contact_email
                    ]);

                    if ($upd->rowCount() > 0) {
                        $pdo->commit();
                        header('Location: ' . $_SERVER['PHP_SELF'] . '?updated=1&id=' . $application_id);
                        exit;
                    } else {
                        $result_message = '更新失敗，請確認申請資料。';
                        $result_type = 'error';
                        $pdo->rollBack();
                    }
                } else {
                    // 新增申請資料
                    $ins = $pdo->prepare("INSERT INTO junior_school_recruitment_applications
                        (school_name, city, district, school_address, contact_name, contact_title,
                         contact_phone, contact_email, preferred_date, preferred_time, target_grades,
                         expected_students, venue_type, special_requirements, remarks)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                    $ins->execute([
                        $school_name, $city, $district, $school_address ?: null,
                        $contact_name, $contact_title ?: null, $contact_phone, $contact_email,
                        $preferred_date, $preferred_time, $target_grades,
                        $expected_students_int, $venue_type ?: null, $special_requirements ?: null, $remarks ?: null
                    ]);

                    $application_id = (int)$pdo->lastInsertId();
                    $pdo->commit();

                    // 發送確認信
                    try {
                        $mail = new PHPMailer(true);
                        $mail->isSMTP();
                        $mail->Host = SMTP_HOST;
                        $mail->SMTPAuth = true;
                        $mail->Username = SMTP_USERNAME;
                        $mail->Password = SMTP_PASSWORD;
                        $mail->SMTPSecure = SMTP_SECURE;
                        $mail->Port = SMTP_PORT;
                        $mail->CharSet = 'UTF-8';
                        $mail->setFrom(SMTP_FROM_EMAIL, '康寧大學招生系統');
                        $mail->addAddress($contact_email, $contact_name);
                        $mail->isHTML(true);
                        $mail->Subject = '國中學校招生申請已收到 - ' . htmlspecialchars($school_name, ENT_QUOTES, 'UTF-8');

                        $mailBody = "
                            <html><body style='font-family:Arial,sans-serif'>
                            <h2>感謝您的申請</h2>
                            <p>親愛的 {$contact_name} 您好，</p>
                            <p>貴校 <strong>{$school_name}</strong> 的招生申請已收到，申請編號為：<strong>#{$application_id}</strong>。</p>
                            <p>我們將於 3-5 個工作天內與您聯繫。</p>
                            <hr>
                            <p style='font-size:12px;color:#777;'>此為系統自動發送郵件，請勿直接回覆。</p>
                            </body></html>";

                        $mail->Body = $mailBody;
                        $mail->AltBody = "感謝您的申請。貴校 {$school_name} 的招生申請已收到，申請編號 #{$application_id}。";
                        $mail->send();
                    } catch (Exception $e) {
                        error_log("郵件發送失敗: " . $e->getMessage());
                    }

                    // 重導向避免重複提交
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?submitted=1&id=' . $application_id);
                    exit;
                }
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                
                // 優化錯誤提示
                $error_message = $ex->getMessage();
                $error_code = $ex->getCode();
                
                // 記錄詳細錯誤到日誌
                error_log("資料庫錯誤 [Code: $error_code]: " . $error_message);
                
                // 根據錯誤類型提供友好的中文提示
                if ($error_code == 1644) {
                    // MySQL SIGNAL 錯誤（自定義錯誤）
                    if (strpos($error_message, '期望招生日期不能是過去的日期') !== false) {
                        $result_message = '期望招生日期不能是過去的日期，請選擇今天或未來的日期。';
                    } else {
                        // 提取自定義錯誤訊息（去除 SQLSTATE 前綴）
                        $result_message = '資料驗證失敗：' . preg_replace('/^SQLSTATE\[45000\]:.*?:\s*/', '', $error_message);
                    }
                } elseif (strpos($error_message, 'Duplicate entry') !== false) {
                    $result_message = '此申請資料已存在，請勿重複提交。';
                } elseif (strpos($error_message, 'foreign key constraint') !== false) {
                    $result_message = '資料關聯錯誤，請檢查輸入的資料是否正確。';
                } elseif (strpos($error_message, 'cannot be null') !== false) {
                    $result_message = '必填欄位未填寫完整，請檢查所有標示 * 的欄位。';
                } else {
                    // 其他資料庫錯誤，提供通用提示
                    $result_message = '申請提交失敗，請檢查輸入的資料是否正確。如問題持續，請聯繫系統管理員。';
                }
                
                $result_type = 'error';
            } catch (Exception $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                
                // 記錄一般錯誤
                error_log("申請提交錯誤: " . $ex->getMessage());
                
                $result_message = '申請提交失敗，請稍後再試。如問題持續，請聯繫系統管理員。';
                $result_type = 'error';
            }
        }
    }
}

// 處理提交成功後的顯示
if (isset($_GET['submitted']) && $_GET['submitted'] == '1' && isset($_GET['id'])) {
    $result_message = '申請已成功提交！申請編號：' . htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8') . '。我們將盡快處理您的申請。';
    $result_type = 'success';
    // 清除搜尋結果，避免顯示舊資料
    $application_data = null;
    $search_email = '';
}

// 處理更新成功後的顯示
if (isset($_GET['updated']) && $_GET['updated'] == '1' && isset($_GET['id'])) {
    $result_message = '申請資料已更新！申請編號：' . htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8');
    $result_type = 'success';
    // 清除搜尋結果，避免顯示舊資料
    $application_data = null;
    $search_email = '';
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>國中學校招生申請 - 康寧大學</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/csp/mobile_junior.css">
    <link rel="stylesheet" href="assets/css/maps.css">
    <style>
        /* 錯誤提示動畫 */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* 學校地址相關欄位樣式 - 與 admission_recommend.php 一致 */
        .field-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .field-group input[name="city"],
        .field-group input[name="district"],
        .field-group input[name="school_address"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
            background: white;
            font-family: 'Microsoft JhengHei', sans-serif;
        }
        
        .field-group input[name="city"]:focus,
        .field-group input[name="district"]:focus,
        .field-group input[name="school_address"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .field-error {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .field-error i {
            font-size: 14px;
        }
    </style>
    <style>
        .recruitment-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #1976d2;
        }
        
        .info-box i {
            color: #1976d2;
            margin-right: 8px;
        }
        
        .field-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .field-group label .required {
            color: #e74c3c;
            margin-right: 4px;
        }
        
        .field-group input,
        .field-group select,
        .field-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: inherit;
            background-color: #ffffff;
            color: #333;
        }
        
        /* 可用狀態 - 明顯的視覺提示 */
        .field-group input:not(:disabled),
        .field-group select:not(:disabled),
        .field-group textarea:not(:disabled) {
            background-color: #ffffff;
            border-color: #d0d0d0;
            box-shadow: 0 0 0 1px rgba(102, 126, 234, 0.1);
        }
        
        .field-group input:not(:disabled):hover,
        .field-group select:not(:disabled):hover,
        .field-group textarea:not(:disabled):hover {
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.15);
        }
        
        .field-group input:focus,
        .field-group select:focus,
        .field-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
            background-color: #ffffff;
        }
        
        /* 禁用狀態 - 明顯的灰色提示 */
        .field-group input:disabled,
        .field-group select:disabled,
        .field-group textarea:disabled {
            background-color: #f5f5f5;
            border-color: #d0d0d0;
            color: #999;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .field-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .submit-btn {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-top: 20px;
        }
        
        /* 啟用狀態 - 藍色 */
        .submit-btn:not(:disabled) {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            color: white;
            cursor: pointer;
            opacity: 1;
        }
        
        .submit-btn:not(:disabled):hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        /* 禁用狀態 - 灰色 */
        .submit-btn:disabled {
            background: #cccccc;
            color: #999999;
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .submit-btn:disabled:hover {
            transform: none;
            box-shadow: none;
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .recruitment-container .header {
            background: #667eea !important;
            border-radius: 10px !important;
            color: white !important;
            padding: 40px !important;
            text-align: center !important;
            margin-bottom: 30px !important;
            box-shadow: none !important;
        }
        
        .recruitment-container .header h1 {
            margin: 0 !important;
            font-size: 2.5rem !important;
            font-weight: 700 !important;
            color: white !important;
            text-shadow: none !important;
        }
        
        .recruitment-container .header h1 i {
            margin-right: 10px !important;
            color: white !important;
        }
        
        .recruitment-container .header .subtitle {
            margin: 10px 0 0 0 !important;
            font-size: 1.1rem !important;
            opacity: 0.9 !important;
            color: white !important;
        }
        
    </style>
</head>
<body>
	<?php include("share/header.php"); ?>
<main>
    <div class="recruitment-container">
        <div class="header" style="background: #667eea !important; color: white !important; padding: 40px !important; border-radius: 10px !important; text-align: center !important; margin-bottom: 30px !important;">
            <h1 style="color: white !important; font-size: 2.5rem !important; font-weight: 700 !important; margin: 0 !important;"><i class="fas fa-graduation-cap" style="color: white !important; margin-right: 10px !important;"></i> 康寧大學就讀意願登錄</h1>
            <div class="subtitle" style="color: white !important; font-size: 1.1rem !important; margin: 10px 0 0 0 !important; opacity: 0.9 !important;">填寫您的就讀意願，我們將儘快與您聯絡</div>
        </div>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <strong>申請須知：</strong>請填寫完整資料，我們將在收到申請後 3-5 個工作天內與您聯繫。您也可以使用申請時填寫的 Email 搜尋查詢您的申請狀態。
        </div>

        <!-- 搜尋申請資料功能 -->
        <div class="form-container" style="margin-bottom: 20px;">
            <div class="form-section">
                <h3><i class="fas fa-search"></i> 搜尋/查詢申請資料</h3>
                <form method="get" action="" style="display: flex; gap: 10px; align-items: flex-end;">
                    <input type="hidden" name="action" value="search">
                    <div class="field-group" style="width: 50%;">
                        <label>請輸入 Email 搜尋申請資料</label>
                        <input type="email" name="email" placeholder="請輸入您申請時使用的 Email" 
                               value="<?php echo htmlspecialchars($search_email, ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <button type="submit" style="background: #28a745; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; width: 40%; margin-bottom: 1px">
                        <i class="fas fa-search"></i> 搜尋
                    </button>
                </form>
                
                <?php if (count($application_list) > 0): ?>
                    <div style="margin-top: 20px;">
                        <p style="margin: 0 0 15px 0; font-weight: 600; color: #2e7d32; font-size: 16px;">
                            <i class="fas fa-check-circle"></i> 找到 <?php echo count($application_list); ?> 筆申請資料
                        </p>
                        
                        <!-- 申請資料列表 -->
                        <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px; background: #fff;">
                            <?php foreach ($application_list as $app): 
                                $is_selected = ($application_data && $application_data['id'] == $app['id']);
                                $status_text = [
                                    'pending' => ['text' => '審核中', 'color' => '#ff9800'],
                                    'approved' => ['text' => '已通過', 'color' => '#28a745'],
                                    'rejected' => ['text' => '已拒絕', 'color' => '#dc3545'],
                                    'completed' => ['text' => '已完成', 'color' => '#17a2b8']
                                ];
                                $status = $status_text[$app['status']] ?? ['text' => $app['status'], 'color' => '#6c757d'];
                            ?>
                                <div style="padding: 15px; border-bottom: 1px solid #eee; <?php echo $is_selected ? 'background: #e3f2fd; border-left: 4px solid #2196f3;' : ''; ?>">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                        <div style="flex: 1;">
                                            <div style="font-weight: 600; color: #333; margin-bottom: 5px;">
                                                申請編號：<?php echo $app['id']; ?>
                                                <?php if ($is_selected): ?>
                                                    <span style="background: #2196f3; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; margin-left: 8px;">已選取</span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-size: 14px; color: #666; margin-bottom: 5px;">
                                                <i class="fas fa-building"></i> <?php echo htmlspecialchars($app['school_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                            <div style="font-size: 13px; color: #888;">
                                                <i class="fas fa-calendar"></i> <?php echo date('Y-m-d H:i', strtotime($app['created_at'])); ?>
                                                <?php if ($app['preferred_date']): ?>
                                                    | <i class="fas fa-clock"></i> 首選日期：<?php echo htmlspecialchars($app['preferred_date'], ENT_QUOTES, 'UTF-8'); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <span style="background: <?php echo $status['color']; ?>; color: white; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                                <?php echo $status['text']; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                                        <button type="button" onclick="selectApplication(<?php echo $app['id']; ?>)" 
                                                style="background: #667eea; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                            <i class="fas fa-edit"></i> <?php echo $is_selected ? '已選取' : '選取此筆'; ?>
                                        </button>
                                        <?php if ($is_selected): ?>
                                            <button type="button" onclick="loadApplicationData()" 
                                                    style="background: #28a745; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                                <i class="fas fa-edit"></i> 載入資料到表單
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php elseif ($search_email !== ''): ?>
                    <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
                        <p style="margin: 0; color: #856404;">
                            <i class="fas fa-exclamation-triangle"></i> 找不到該 Email 的申請資料
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($result_message !== ''): ?>
            <div class="message <?php echo $result_type; ?>">
                <i class="fas fa-<?php echo ($result_type === 'success') ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($result_message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="post" id="recruitmentForm" onsubmit="return validateFormBeforeSubmit(event)">
                <input type="hidden" name="action" id="form_action" value="submit">
                <input type="hidden" name="application_id" id="application_id" value="<?php echo $application_data ? $application_data['id'] : '0'; ?>">
                
                <!-- 學校基本資料 -->
                <div class="form-section">
                    <h3><i class="fas fa-building"></i> 學校基本資料</h3>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span> 學校名稱：</label>
                            <div class="modern-search-container">
                                <div class="search-input-wrapper">
                                    <input type="text" id="school_name" name="school_name" placeholder="請輸入學校名稱..." autocomplete="off" required 
                                           value="<?php 
                                           $school_name_value = '';
                                           if ($application_data) {
                                               $school_name_value = htmlspecialchars($application_data['school_name'], ENT_QUOTES, 'UTF-8');
                                           } elseif (isset($_POST['school_name'])) {
                                               $school_name_value = htmlspecialchars($_POST['school_name'], ENT_QUOTES, 'UTF-8');
                                           }
                                           echo $school_name_value;
                                           ?>" />
                                    <div class="search-icon">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <div class="clear-btn" id="clearSchoolSearch" style="display: none;">
                                        <i class="fas fa-times"></i>
                                    </div>
                                </div>
                                <div id="schoolResults" class="modern-search-results"></div>
                            </div>
                            <div class="help-text">
                                <i class="fas fa-info-circle"></i> 輸入學校名稱即可即時搜尋，請從搜尋結果中選擇學校（不能自行輸入）
                            </div>
                            <div id="school_name_error" class="field-error" style="display: none; color: #d32f2f; font-size: 13px; margin-top: 8px; padding: 8px 12px; background-color: #ffebee; border-left: 3px solid #d32f2f; border-radius: 4px; animation: slideDown 0.3s ease;">
                                <i class="fas fa-exclamation-circle"></i> <span id="school_name_error_text">請從系統提供的選項中選擇學校，不能自行輸入</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span> 縣市</label>
                            <input type="text" name="city" placeholder="例如：台北市、新北市" required 
                                   value="<?php 
                                   $city_value = '';
                                   if ($application_data) {
                                       $city_value = htmlspecialchars($application_data['city'], ENT_QUOTES, 'UTF-8');
                                   } elseif (isset($_POST['city'])) {
                                       $city_value = htmlspecialchars($_POST['city'], ENT_QUOTES, 'UTF-8');
                                   }
                                   echo $city_value;
                                   ?>" />
                        </div>
                        <div class="field-group">
                            <label><span class="required">*</span> 區/鄉鎮市</label>
                            <input type="text" name="district" placeholder="例如：中正區、板橋區" required 
                                   value="<?php 
                                   $district_value = '';
                                   if ($application_data) {
                                       $district_value = htmlspecialchars($application_data['district'], ENT_QUOTES, 'UTF-8');
                                   } elseif (isset($_POST['district'])) {
                                       $district_value = htmlspecialchars($_POST['district'], ENT_QUOTES, 'UTF-8');
                                   }
                                   echo $district_value;
                                   ?>" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-group">
                            <label>學校地址</label>
                            <input type="text" name="school_address" placeholder="請輸入完整地址（選填）" 
                                   value="<?php 
                                   $school_address_value = '';
                                   if ($application_data) {
                                       $school_address_value = htmlspecialchars($application_data['school_address'] ?? '', ENT_QUOTES, 'UTF-8');
                                   } elseif (isset($_POST['school_address'])) {
                                       $school_address_value = htmlspecialchars($_POST['school_address'], ENT_QUOTES, 'UTF-8');
                                   }
                                   echo $school_address_value;
                                   ?>" />
                        </div>
                    </div>
                </div>

                <!-- 聯絡人資訊 -->
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> 聯絡人資訊</h3>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span> 聯絡人姓名</label>
                            <input type="text" name="contact_name" placeholder="請輸入聯絡人姓名" required 
                                   value="<?php 
                                   $contact_name_value = '';
                                   if ($application_data) {
                                       $contact_name_value = htmlspecialchars($application_data['contact_name'], ENT_QUOTES, 'UTF-8');
                                   } elseif (isset($_POST['contact_name'])) {
                                       $contact_name_value = htmlspecialchars($_POST['contact_name'], ENT_QUOTES, 'UTF-8');
                                   }
                                   echo $contact_name_value;
                                   ?>" />
                        </div>
                        <div class="field-group">
                            <label>職稱</label>
                            <input type="text" name="contact_title" placeholder="例如：教務主任、輔導主任" 
                                   value="<?php 
                                   $contact_title_value = '';
                                   if ($application_data) {
                                       $contact_title_value = htmlspecialchars($application_data['contact_title'] ?? '', ENT_QUOTES, 'UTF-8');
                                   } elseif (isset($_POST['contact_title'])) {
                                       $contact_title_value = htmlspecialchars($_POST['contact_title'], ENT_QUOTES, 'UTF-8');
                                   }
                                   echo $contact_title_value;
                                   ?>" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span> 聯絡人電話</label>
                            <input type="tel" name="contact_phone" placeholder="例如：02-1234-5678" required 
                                   value="<?php 
                                   $contact_phone_value = '';
                                   if ($application_data) {
                                       $contact_phone_value = htmlspecialchars($application_data['contact_phone'], ENT_QUOTES, 'UTF-8');
                                   } elseif (isset($_POST['contact_phone'])) {
                                       $contact_phone_value = htmlspecialchars($_POST['contact_phone'], ENT_QUOTES, 'UTF-8');
                                   }
                                   echo $contact_phone_value;
                                   ?>" />
                        </div>
                        <div class="field-group">
                            <label><span class="required">*</span> 聯絡人 Email</label>
                            <input type="email" name="contact_email" placeholder="例如：contact@school.edu.tw" required 
                                   value="<?php 
                                   $contact_email_value = '';
                                   if ($application_data) {
                                       $contact_email_value = htmlspecialchars($application_data['contact_email'], ENT_QUOTES, 'UTF-8');
                                   } elseif (isset($_POST['contact_email'])) {
                                       $contact_email_value = htmlspecialchars($_POST['contact_email'], ENT_QUOTES, 'UTF-8');
                                   }
                                   echo $contact_email_value;
                                   ?>" />
                        </div>
                    </div>
                </div>

                <!-- 招生安排資料 -->
                <div class="form-section">
                    <h3><i class="fas fa-calendar-check"></i> 招生安排資料</h3>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span><i class="fas fa-calendar-alt" style="color:#667eea;"></i> 首選日期</label>
                            <input type="date" name="preferred_date" required min="<?php echo date('Y-m-d'); ?>"
                                   value="<?php 
                                   $preferred_date_value = '';
                                   if ($application_data) {
                                       $preferred_date_value = $application_data['preferred_date'];
                                   } elseif (isset($_POST['preferred_date'])) {
                                       $preferred_date_value = htmlspecialchars($_POST['preferred_date'], ENT_QUOTES, 'UTF-8');
                                   }
                                   echo $preferred_date_value;
                                   ?>" />
                            <small style="color: #666; margin-top: 5px; display: block;">
                                <i class="fas fa-info-circle"></i> 請選擇今天或未來的日期
                            </small>
                        </div>
                        <div class="field-group">
                            <label><span class="required">*</span><i class="fas fa-clock" style="color:#667eea;"></i> 首選時段</label>
                            <select name="preferred_time" required>
                                <option value="">請選擇</option>
                                <?php
                                $preferred_time_value = '';
                                if ($application_data) {
                                    $preferred_time_value = $application_data['preferred_time'];
                                } elseif (isset($_POST['preferred_time'])) {
                                    $preferred_time_value = $_POST['preferred_time'];
                                }
                                ?>
                                <option value="上午" <?php echo ($preferred_time_value === '上午') ? 'selected' : ''; ?>>上午</option>
                                <option value="下午" <?php echo ($preferred_time_value === '下午') ? 'selected' : ''; ?>>下午</option>
                                <option value="晚上" <?php echo ($preferred_time_value === '晚上') ? 'selected' : ''; ?>>晚上</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span> 目標年級</label>
                            <input type="text" name="target_grades" placeholder="例如：七年級、八年級、九年級" required
                                   value="<?php 
                                   $target_grades_value = '';
                                   if ($application_data) {
                                       $target_grades_value = htmlspecialchars($application_data['target_grades'], ENT_QUOTES, 'UTF-8');
                                   } elseif (isset($_POST['target_grades'])) {
                                       $target_grades_value = htmlspecialchars($_POST['target_grades'], ENT_QUOTES, 'UTF-8');
                                   }
                                   echo $target_grades_value;
                                   ?>" />
                        </div>
                        <div class="field-group">
                            <label><span class="required">*</span> 預期參與學生數</label>
                            <input type="number" name="expected_students" min="1" placeholder="例如：100" required
                                   value="<?php 
                                   $expected_students_value = '';
                                   if ($application_data) {
                                       $expected_students_value = $application_data['expected_students'];
                                   } elseif (isset($_POST['expected_students'])) {
                                       $expected_students_value = htmlspecialchars($_POST['expected_students'], ENT_QUOTES, 'UTF-8');
                                   }
                                   echo $expected_students_value;
                                   ?>" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-group">
                            <label>場地類型</label>
                            <select name="venue_type">
                                <option value="">請選擇（選填）</option>
                                <?php
                                $venue_type_value = '';
                                if ($application_data) {
                                    $venue_type_value = $application_data['venue_type'];
                                } elseif (isset($_POST['venue_type'])) {
                                    $venue_type_value = $_POST['venue_type'];
                                }
                                ?>
                                <option value="校內" <?php echo ($venue_type_value === '校內') ? 'selected' : ''; ?>>校內</option>
                                <option value="校外" <?php echo ($venue_type_value === '校外') ? 'selected' : ''; ?>>校外</option>
                                <option value="線上" <?php echo ($venue_type_value === '線上') ? 'selected' : ''; ?>>線上</option>
                                <option value="其他" <?php echo ($venue_type_value === '其他') ? 'selected' : ''; ?>>其他</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-group">
                            <label>特殊需求</label>
                            <textarea name="special_requirements" placeholder="請描述任何特殊需求或注意事項（選填）"><?php 
                            $special_requirements_value = '';
                            if ($application_data) {
                                $special_requirements_value = htmlspecialchars($application_data['special_requirements'] ?? '', ENT_QUOTES, 'UTF-8');
                            } elseif (isset($_POST['special_requirements'])) {
                                $special_requirements_value = htmlspecialchars($_POST['special_requirements'], ENT_QUOTES, 'UTF-8');
                            }
                            echo $special_requirements_value;
                            ?></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-group">
                            <label>備註</label>
                            <textarea name="remarks" placeholder="其他需要補充的資訊（選填）"><?php 
                            $remarks_value = '';
                            if ($application_data) {
                                $remarks_value = htmlspecialchars($application_data['remarks'] ?? '', ENT_QUOTES, 'UTF-8');
                            } elseif (isset($_POST['remarks'])) {
                                $remarks_value = htmlspecialchars($_POST['remarks'], ENT_QUOTES, 'UTF-8');
                            }
                            echo $remarks_value;
                            ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- 驗證碼 -->
                <div class="form-section">
                    <h3><i class="fas fa-shield-alt"></i> 驗證碼 <span class="required">*</span></h3>
                    <div class="captcha-section" style="display: flex; align-items: center; gap: 10px; margin: 15px 0; flex-wrap: wrap;">
                        <input type="text" name="captcha" id="captchaInput" placeholder="請輸入驗證碼" maxlength="6" required autocomplete="off" style="flex: 1; min-width: 150px; padding: 12px; border: 2px solid #d0d0d0; border-radius: 8px; font-size: 15px; background-color: #ffffff; color: #333; transition: all 0.3s; text-transform: uppercase;" oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '')">
                        <img src="captcha_image.php" id="captchaImage" alt="驗證碼" onclick="refreshCaptcha()" style="height: 50px; width: 150px; border: 2px solid #e0e0e0; border-radius: 8px; cursor: pointer;" title="點擊刷新驗證碼" onerror="this.onerror=null; this.src='captcha_image.php?t='+Date.now();">
                        <button type="button" onclick="refreshCaptcha()" style="padding: 12px 20px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                            <i class="fas fa-sync-alt"></i> 刷新
                        </button>
                    </div>
                    <small style="color: #666; margin-top: 8px; display: block;">
                        <i class="fas fa-info-circle"></i> 請輸入圖片中顯示的字母和數字（不區分大小寫）
                    </small>
                </div>

                <button type="submit" class="submit-btn" id="submit_btn">
                    <i class="fas fa-paper-plane"></i> <span id="submit_btn_text">提交申請</span>
                </button>
            </form>
        </div>
    </div>
</main>
    <?php include("share/footer.php"); ?>
    
<script>
// 檢查必填欄位並更新提交按鈕狀態（不再禁用按鈕）
function checkRequiredFields() {
    // 此函數保留用於其他用途，但不再禁用提交按鈕
    // 提交按鈕將始終保持可用狀態
    const submitBtn = document.getElementById('submit_btn');
    if (submitBtn) {
        submitBtn.disabled = false;
    }
}

// 頁面載入時初始化輸入框視覺效果和學校搜尋
document.addEventListener('DOMContentLoaded', function() {
    // 綁定學校搜尋事件
    const schoolSearchInput = document.getElementById('school_name');
    const clearSchoolBtn = document.getElementById('clearSchoolSearch');

    if (schoolSearchInput) {
        // 輸入事件（即時搜尋）
        schoolSearchInput.addEventListener('input', function() {
            performSchoolSearch();
            // 當下拉選單顯示時，不進行驗證（用戶還在輸入和選擇中）
        });

        // 失去焦點時立即驗證
        schoolSearchInput.addEventListener('blur', function() {
            clearTimeout(schoolSearchInput.validationTimeout);
            // 延遲一點驗證，讓點擊下拉選單項目的時間完成
            schoolSearchInput.validationTimeout = setTimeout(validateSchoolInputImmediate, 200);
        });
        
        // 當輸入框獲得焦點時，如果已有錯誤且下拉選單未顯示，保持顯示
        schoolSearchInput.addEventListener('focus', function() {
            const resultsDiv = document.getElementById('schoolResults');
            const value = this.value.trim();
            // 只有在下拉選單未顯示時才檢查錯誤
            if (value && !/^.+ \(.+\)$/.test(value) && 
                (!resultsDiv || !resultsDiv.classList.contains('show'))) {
                validateSchoolInput();
            }
        });

        // 清除按鈕事件
        if (clearSchoolBtn) {
            clearSchoolBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                clearSchoolSearch();
            });
        }

        // 鍵盤事件
        schoolSearchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                clearSchoolSearch();
            }
        });
        
        // 如果有初始值，顯示清除按鈕
        if (schoolSearchInput.value) {
            if (clearSchoolBtn) {
                clearSchoolBtn.style.display = 'block';
            }
        }
    }

    // 點擊其他地方隱藏搜尋結果
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.modern-search-container')) {
            const resultsDiv = document.getElementById('schoolResults');
            if (resultsDiv && resultsDiv.classList.contains('show')) {
                resultsDiv.classList.remove('show');
                // 當下拉選單隱藏時，驗證輸入
                setTimeout(validateSchoolInput, 100);
            }
        }
    });
    
    // 表單提交前驗證
    function validateFormBeforeSubmit(e) {
        const schoolNameInput = document.getElementById('school_name');
        if (schoolNameInput) {
            const schoolName = schoolNameInput.value.trim();
            if (schoolName) {
                // 檢查格式是否為：學校名稱 (縣市區)
                const schoolFormatPattern = /^.+ \(.+\)$/;
                if (!schoolFormatPattern.test(schoolName)) {
                    e.preventDefault();
                    showSchoolError('請從系統提供的選項中選擇學校，不能自行輸入');
                    schoolNameInput.focus();
                    schoolNameInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }
            }
        }
        return true;
    }
    
    // 為驗證碼輸入框添加視覺反饋
    const captchaInput = document.getElementById('captchaInput');
    if (captchaInput) {
        // 添加 hover 效果
        captchaInput.addEventListener('mouseenter', function() {
            if (!this.disabled) {
                this.style.borderColor = '#667eea';
                this.style.boxShadow = '0 0 0 2px rgba(102, 126, 234, 0.15)';
            }
        });
        captchaInput.addEventListener('mouseleave', function() {
            if (!this.disabled && document.activeElement !== this) {
                this.style.borderColor = '#d0d0d0';
                this.style.boxShadow = 'none';
            }
        });
        // 添加 focus 效果
        captchaInput.addEventListener('focus', function() {
            if (!this.disabled) {
                this.style.borderColor = '#667eea';
                this.style.boxShadow = '0 0 0 3px rgba(102, 126, 234, 0.2)';
            }
        });
        captchaInput.addEventListener('blur', function() {
            if (!this.disabled) {
                this.style.borderColor = '#d0d0d0';
                this.style.boxShadow = 'none';
            }
        });
    }
    
    // 確保提交按鈕始終可用（不再禁用）
    const submitBtn = document.getElementById('submit_btn');
    if (submitBtn) {
        submitBtn.disabled = false;
    }
    
    // 監聽所有必填欄位的變化（保留用於其他用途，但不再禁用按鈕）
    const form = document.getElementById('recruitmentForm');
    if (form) {
        // 監聽 input、select 和 textarea 的變化
        form.addEventListener('input', checkRequiredFields);
        form.addEventListener('change', checkRequiredFields);
        
        // 初始檢查一次
        checkRequiredFields();
    }
    
    // 處理更新成功後的顯示，5秒後清除URL參數避免重新整理時重複顯示
    if (window.location.search.includes('updated=1')) {
        setTimeout(function() {
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }, 5000);
    }
    
    // 處理表單提交
    let isSubmitting = false;
    const formAction = document.getElementById('form_action');
    const submitBtnText = document.getElementById('submit_btn_text');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            // 防止重複提交
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            
            // 驗證日期不能是過去的日期
            const preferredDateInput = form.querySelector('input[name="preferred_date"]');
            if (preferredDateInput && preferredDateInput.value) {
                const selectedDate = new Date(preferredDateInput.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0); // 設定為今天的開始時間
                selectedDate.setHours(0, 0, 0, 0);
                
                if (selectedDate < today) {
                    e.preventDefault();
                    alert('期望招生日期不能是過去的日期，請選擇今天或未來的日期。');
                    preferredDateInput.focus();
                    isSubmitting = false;
                    return false;
                }
            }
            
            if (formAction) {
                const action = formAction.value;
                if (action === 'update') {
                    if (!confirm('確定要更新申請資料嗎？')) {
                        e.preventDefault();
                        isSubmitting = false;
                        return false;
                    }
                }
            }
            
            // 設定提交狀態
            isSubmitting = true;
            
            // 更新按鈕文字（但不禁用按鈕）
            if (submitBtn && submitBtnText) {
                const originalText = submitBtnText.textContent;
                submitBtnText.textContent = '處理中...';
                
                // 如果5秒後仍在提交，恢復按鈕文字
                setTimeout(function() {
                    if (isSubmitting) {
                        isSubmitting = false;
                        submitBtnText.textContent = originalText;
                    }
                }, 5000);
            }
        });
    }
    
    // 處理表單初始化
    if (formAction && formAction.value === 'update') {
        if (submitBtnText) {
            submitBtnText.textContent = '更新申請資料';
        }
        // 如果是更新模式，檢查必填欄位並更新按鈕狀態
        setTimeout(checkRequiredFields, 100);
    }
});

// 選取申請資料
function selectApplication(applicationId) {
    // 優先從搜索框獲取 email
    let email = '';
    const emailInput = document.querySelector('input[name="email"]');
    if (emailInput) {
        email = emailInput.value.trim();
    }
    
    // 如果搜索框沒有值，嘗試從 URL 參數獲取
    if (!email) {
        const urlParams = new URLSearchParams(window.location.search);
        email = urlParams.get('email') || '';
    }
    
    // 如果還是沒有，使用 PHP 傳入的搜索 email
    if (!email) {
        email = '<?php echo htmlspecialchars($search_email ?? "", ENT_QUOTES, "UTF-8"); ?>';
    }
    
    if (email) {
        // 構建新的 URL
        const baseUrl = window.location.pathname;
        window.location.href = baseUrl + '?action=search&email=' + encodeURIComponent(email) + '&application_id=' + applicationId;
    } else {
        alert('請先輸入 Email 進行搜尋');
        if (emailInput) {
            emailInput.focus();
        }
    }
}

// 學校搜尋功能
function performSchoolSearch() {
    const keyword = document.getElementById('school_name').value.trim();
    const resultsDiv = document.getElementById('schoolResults');
    const clearBtn = document.getElementById('clearSchoolSearch');

    // 顯示/隱藏清除按鈕
    if (keyword.length > 0) {
        clearBtn.style.display = 'block';
    } else {
        clearBtn.style.display = 'none';
        resultsDiv.classList.remove('show');
        // 當搜尋結果隱藏時，清除錯誤提示
        clearSchoolError();
        return;
    }

    if (keyword.length < 2) {
        resultsDiv.innerHTML = '<div class="search-result-item">請輸入至少2個字元</div>';
        resultsDiv.classList.add('show');
        // 當下拉選單顯示時，清除錯誤提示（用戶還在輸入中）
        clearSchoolError();
        return;
    }

    // 顯示載入中
    resultsDiv.innerHTML = '<div class="search-result-item"><i class="fas fa-spinner fa-spin"></i> 搜尋中...</div>';
    resultsDiv.classList.add('show');
    // 當下拉選單顯示時，清除錯誤提示（用戶還在選擇中）
    clearSchoolError();

    // 從API獲取搜尋結果
    fetch(`api/school_data_api.php?action=search&keyword=${encodeURIComponent(keyword)}&v=20241014-4`)
        .then(response => response.json())
        .then(data => {
            console.log('搜尋結果:', data);
            if (data.schools && data.schools.length > 0) {
                resultsDiv.innerHTML = data.schools.map(school => {
                    let displayName = school.name;
                    let additionalInfo = '';
                    
                    if (school.all_names && school.all_names.length > 1) {
                        additionalInfo = `<div class="school-alternative-names">其他名稱: ${school.all_names.join(', ')}</div>`;
                    }
                    
                    return `<div class="search-result-item" onclick="selectSchool('${school.name}', '${school.city}', '${school.district}')">
                        <i class="fas fa-school"></i>
                        <div class="school-info">
                            <span class="school-name">${displayName}</span>
                            <span class="school-location">${school.city} ${school.district}</span>
                            ${additionalInfo}
                        </div>
                    </div>`;
                }).join('');

                if (data.total > 20) {
                    resultsDiv.innerHTML += `<div class="search-result-item more-results">還有 ${data.total - 20} 個結果...</div>`;
                }
                // 當下拉選單顯示時，清除錯誤提示
                clearSchoolError();
            } else {
                resultsDiv.innerHTML = '<div class="search-result-item">找不到匹配的學校</div>';
                // 即使找不到結果，下拉選單仍然顯示，所以清除錯誤提示
                clearSchoolError();
            }
        })
        .catch(error => {
            console.error('搜尋錯誤:', error);
            resultsDiv.innerHTML = '<div class="search-result-item">搜尋失敗，請稍後再試</div>';
            // 即使搜尋失敗，下拉選單仍然顯示，所以清除錯誤提示
            clearSchoolError();
        });
}

// 清除學校輸入錯誤提示
function clearSchoolError() {
    const errorDiv = document.getElementById('school_name_error');
    const input = document.getElementById('school_name');
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
    if (input) {
        input.style.borderColor = '';
        input.style.borderWidth = '';
        input.style.boxShadow = '';
    }
}

// 顯示學校輸入錯誤提示
function showSchoolError(message) {
    const errorDiv = document.getElementById('school_name_error');
    const errorText = document.getElementById('school_name_error_text');
    const input = document.getElementById('school_name');
    
    if (errorDiv && errorText) {
        errorText.textContent = message || '請從系統提供的選項中選擇學校，不能自行輸入';
        errorDiv.style.display = 'block';
        // 添加動畫效果
        errorDiv.style.animation = 'none';
        setTimeout(() => {
            errorDiv.style.animation = 'slideDown 0.3s ease';
        }, 10);
    }
    
    if (input) {
        input.style.borderColor = '#d32f2f';
        input.style.borderWidth = '2px';
        input.style.boxShadow = '0 0 0 3px rgba(211, 47, 47, 0.1)';
    }
}

// 驗證學校輸入格式
function validateSchoolInput() {
    const input = document.getElementById('school_name');
    if (!input) return;
    
    const value = input.value.trim();
    const resultsDiv = document.getElementById('schoolResults');
    
    // 如果為空，不顯示錯誤（由required屬性處理）
    if (!value) {
        clearSchoolError();
        return;
    }
    
    // 如果下拉選單正在顯示，表示用戶還在選擇中，不顯示錯誤
    if (resultsDiv && resultsDiv.classList.contains('show')) {
        clearSchoolError();
        return;
    }
    
    // 檢查格式是否為：學校名稱 (縣市區)
    const schoolFormatPattern = /^.+ \(.+\)$/;
    if (!schoolFormatPattern.test(value)) {
        // 只有在下拉選單隱藏時才顯示錯誤
        showSchoolError('請從系統提供的選項中選擇學校，不能自行輸入');
    } else {
        clearSchoolError();
    }
}

// 立即驗證（不延遲）- 用於失去焦點時
function validateSchoolInputImmediate() {
    validateSchoolInput();
}

// 清除搜尋
function clearSchoolSearch() {
    document.getElementById('school_name').value = '';
    document.getElementById('schoolResults').classList.remove('show');
    document.getElementById('clearSchoolSearch').style.display = 'none';
    clearSchoolError();
}

// 選擇學校
function selectSchool(schoolName, city, district) {
    const fullSchoolName = `${schoolName} (${city}${district})`;
    const schoolInput = document.getElementById('school_name');
    schoolInput.value = fullSchoolName;
    
    // 自動填入縣市和區/鄉鎮市
    const cityInput = document.querySelector('input[name="city"]');
    const districtInput = document.querySelector('input[name="district"]');
    if (cityInput) cityInput.value = city;
    if (districtInput) districtInput.value = district;
    
    document.getElementById('schoolResults').classList.remove('show');
    document.getElementById('clearSchoolSearch').style.display = 'block';
    
    // 清除錯誤提示（因為用戶已從系統選項中選擇）
    clearSchoolError();
    
    // 觸發必填欄位檢查
    if (typeof checkRequiredFields === 'function') {
        checkRequiredFields();
    }
}

// 載入申請資料到表單
function loadApplicationData() {
    <?php if ($application_data): ?>
    const data = <?php echo json_encode($application_data); ?>;
    
    // 填入表單資料
    const schoolNameInput = document.getElementById('school_name');
    if (schoolNameInput) {
        schoolNameInput.value = data.school_name || '';
    }
    document.querySelector('input[name="city"]').value = data.city || '';
    document.querySelector('input[name="district"]').value = data.district || '';
    document.querySelector('input[name="school_address"]').value = data.school_address || '';
    document.querySelector('input[name="contact_name"]').value = data.contact_name || '';
    document.querySelector('input[name="contact_title"]').value = data.contact_title || '';
    document.querySelector('input[name="contact_phone"]').value = data.contact_phone || '';
    document.querySelector('input[name="contact_email"]').value = data.contact_email || '';
    document.querySelector('input[name="preferred_date"]').value = data.preferred_date || '';
    document.querySelector('select[name="preferred_time"]').value = data.preferred_time || '';
    document.querySelector('input[name="target_grades"]').value = data.target_grades || '';
    document.querySelector('input[name="expected_students"]').value = data.expected_students || '';
    document.querySelector('select[name="venue_type"]').value = data.venue_type || '';
    document.querySelector('textarea[name="special_requirements"]').value = data.special_requirements || '';
    document.querySelector('textarea[name="remarks"]').value = data.remarks || '';
    
    // 如果有學校名稱，顯示清除按鈕
    if (schoolNameInput && schoolNameInput.value) {
        const clearBtn = document.getElementById('clearSchoolSearch');
        if (clearBtn) {
            clearBtn.style.display = 'block';
        }
    }
    
    // 設定為更新模式
    document.getElementById('form_action').value = 'update';
    document.getElementById('application_id').value = data.id;
    document.getElementById('submit_btn_text').textContent = '更新申請資料';
    
    // 檢查必填欄位並更新按鈕狀態
    checkRequiredFields();
    
    // 滾動到表單
    document.getElementById('recruitmentForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
    
    // 顯示提示訊息
    alert('申請資料已載入到表單，您可以修改後重新提交。');
    <?php endif; ?>
}

// 驗證碼刷新功能
function refreshCaptcha() {
    const captchaImage = document.getElementById('captchaImage');
    const captchaInput = document.getElementById('captchaInput');
    
    // 清空輸入框
    if (captchaInput) {
        captchaInput.value = '';
        // 清空後檢查必填欄位並更新按鈕狀態
        checkRequiredFields();
    }
    
    // 刷新驗證碼圖片（添加時間戳防止緩存）
    if (captchaImage) {
        captchaImage.src = 'captcha_image.php?t=' + new Date().getTime();
    }
}

</script>
</body>
</html>