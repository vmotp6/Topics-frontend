<?php
// 國中學校招生申請頁面：讓國中學校申請康寧大學前來招生

// 會用到的設定與工具
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

// 使用 PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 建立 PDO 連線
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('資料庫連接失敗: ' . $e->getMessage());
}

// 建表：國中學校招生申請表
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS junior_school_recruitment_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        school_name VARCHAR(100) NOT NULL COMMENT '學校名稱',
        city VARCHAR(20) NOT NULL COMMENT '縣市',
        district VARCHAR(20) NOT NULL COMMENT '區/鄉鎮市',
        school_address VARCHAR(255) DEFAULT NULL COMMENT '學校地址',
        contact_name VARCHAR(50) NOT NULL COMMENT '聯絡人姓名',
        contact_title VARCHAR(50) DEFAULT NULL COMMENT '聯絡人職稱',
        contact_phone VARCHAR(20) NOT NULL COMMENT '聯絡電話',
        contact_email VARCHAR(120) NOT NULL COMMENT '聯絡Email',
        preferred_date DATE DEFAULT NULL COMMENT '期望招生日期',
        preferred_time VARCHAR(50) DEFAULT NULL COMMENT '期望時間（例如：上午、下午）',
        target_grades VARCHAR(50) DEFAULT NULL COMMENT '目標年級（例如：三年級、二年級）',
        expected_students INT DEFAULT NULL COMMENT '預期參與學生人數',
        venue_type VARCHAR(50) DEFAULT NULL COMMENT '場地類型（例如：禮堂、教室）',
        special_requirements TEXT DEFAULT NULL COMMENT '特殊需求',
        remarks TEXT DEFAULT NULL COMMENT '備註',
        status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending' COMMENT '申請狀態',
        admin_comment TEXT DEFAULT NULL COMMENT '管理員備註',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '申請時間',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
        INDEX idx_school_name (school_name),
        INDEX idx_city (city),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at),
        INDEX idx_contact_email (contact_email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='國中學校招生申請表'");
} catch (PDOException $e) {
    die('初始化資料表失敗: ' . $e->getMessage());
}

// 處理查詢申請資料
$search_email = '';
$application_data = null;
$application_list = [];
$selected_application_id = isset($_GET['application_id']) ? (int)$_GET['application_id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'search' && isset($_GET['email'])) {
    $search_email = trim($_GET['email']);
    if ($search_email !== '') {
        try {
            // 查詢該 email 的所有申請記錄
            $stmt = $pdo->prepare("SELECT * FROM junior_school_recruitment_applications WHERE contact_email = ? ORDER BY created_at DESC");
            $stmt->execute([$search_email]);
            $application_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 如果有選擇特定的申請 ID，載入該筆資料
            if ($selected_application_id > 0) {
                foreach ($application_list as $app) {
                    if ($app['id'] == $selected_application_id) {
                        $application_data = $app;
                        break;
                    }
                }
            } elseif (count($application_list) > 0) {
                // 如果沒有指定 ID，預設載入最新的一筆
                $application_data = $application_list[0];
            }
        } catch (PDOException $e) {
            error_log("查詢申請資料失敗: " . $e->getMessage());
        }
    }
}

// 處理表單送出
$result_message = '';
$result_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_post = isset($_POST['action']) ? $_POST['action'] : 'submit';
    $application_id = isset($_POST['application_id']) ? (int)$_POST['application_id'] : 0;
    
    $school_name = isset($_POST['school_name']) ? trim($_POST['school_name']) : '';
    $city = isset($_POST['city']) ? trim($_POST['city']) : '';
    $district = isset($_POST['district']) ? trim($_POST['district']) : '';
    $school_address = isset($_POST['school_address']) ? trim($_POST['school_address']) : '';
    $contact_name = isset($_POST['contact_name']) ? trim($_POST['contact_name']) : '';
    $contact_title = isset($_POST['contact_title']) ? trim($_POST['contact_title']) : '';
    $contact_phone = isset($_POST['contact_phone']) ? trim($_POST['contact_phone']) : '';
    $contact_email = isset($_POST['contact_email']) ? trim($_POST['contact_email']) : '';
    $preferred_date = isset($_POST['preferred_date']) ? trim($_POST['preferred_date']) : '';
    $preferred_time = isset($_POST['preferred_time']) ? trim($_POST['preferred_time']) : '';
    $target_grades = isset($_POST['target_grades']) ? trim($_POST['target_grades']) : '';
    $expected_students = isset($_POST['expected_students']) ? trim($_POST['expected_students']) : '';
    $venue_type = isset($_POST['venue_type']) ? trim($_POST['venue_type']) : '';
    $special_requirements = isset($_POST['special_requirements']) ? trim($_POST['special_requirements']) : '';
    $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';

    // 基本驗證（包含招生相關資訊必填）
    if ($school_name === '' || $city === '' || $district === '' || 
        $contact_name === '' || $contact_phone === '' || $contact_email === '' ||
        $preferred_date === '' || $preferred_time === '' || $target_grades === '' ||
        $expected_students === '' || $venue_type === '') {
        $result_message = '請填寫所有必填欄位（包含招生相關資訊）';
        $result_type = 'error';
    } else if (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $result_message = '請提供正確的 Email 地址格式';
        $result_type = 'error';
    } else if (!is_numeric($expected_students) || (int)$expected_students <= 0) {
        $result_message = '預期參與學生人數必須是大於 0 的數字';
        $result_type = 'error';
    } else {
        try {
            $pdo->beginTransaction();
            
            $expected_students_int = (int)$expected_students;
            
            if ($action_post === 'update' && $application_id > 0) {
                // 更新申請記錄
                $upd = $pdo->prepare("UPDATE junior_school_recruitment_applications SET 
                    school_name = ?, city = ?, district = ?, school_address = ?, 
                    contact_name = ?, contact_title = ?, contact_phone = ?, contact_email = ?,
                    preferred_date = ?, preferred_time = ?, target_grades = ?, 
                    expected_students = ?, venue_type = ?, special_requirements = ?, remarks = ?,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND contact_email = ?");
                
                $upd->execute([
                    $school_name, $city, $district, $school_address ?: null, 
                    $contact_name, $contact_title ?: null, $contact_phone, $contact_email,
                    $preferred_date, $preferred_time, $target_grades,
                    $expected_students_int, $venue_type, $special_requirements ?: null, $remarks ?: null,
                    $application_id, $contact_email
                ]);
                
                if ($upd->rowCount() > 0) {
                    $pdo->commit();
                    // 更新成功後重新導向頁面，清空所有欄位
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?updated=1&id=' . $application_id);
                    exit;
                } else {
                    $result_message = '更新失敗：找不到該申請記錄或 Email 不匹配';
                    $result_type = 'error';
                    $pdo->rollBack();
                }
            } else {
                // 插入新申請記錄
                $ins = $pdo->prepare("INSERT INTO junior_school_recruitment_applications 
                    (school_name, city, district, school_address, contact_name, contact_title, 
                     contact_phone, contact_email, preferred_date, preferred_time, target_grades, 
                     expected_students, venue_type, special_requirements, remarks) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $ins->execute([
                    $school_name, $city, $district, $school_address ?: null, 
                    $contact_name, $contact_title ?: null, $contact_phone, $contact_email,
                    $preferred_date, $preferred_time, $target_grades,
                    $expected_students_int, $venue_type, $special_requirements ?: null, $remarks ?: null
                ]);
                
                $application_id = (int)$pdo->lastInsertId();
                $pdo->commit();
                
                // 發送確認郵件給申請人（僅在新增時）
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
                    $mail->setFrom(SMTP_FROM_EMAIL, '康寧大學招生服務');
                    $mail->addAddress($contact_email, $contact_name);
                    
                    $mail->isHTML(true);
                    $mail->Subject = '【康寧大學】招生申請已收到 - ' . htmlspecialchars($school_name, ENT_QUOTES, 'UTF-8');
                    
                    $mailBody = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f4f6f8;">'
                        . '<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f4f6f8;padding:24px 0;">'
                        . '<tr><td align="center">'
                        . '<table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 4px 14px rgba(0,0,0,0.08);">'
                        . '<tr><td style="background:linear-gradient(90deg,#667eea,#764ba2);padding:18px 24px;color:#fff;font-size:18px;font-weight:700;">招生申請已收到</td></tr>'
                        . '<tr><td style="padding:22px 24px;">'
                        . '<h2 style="margin:0 0 12px 0;color:#222;font-size:22px;line-height:1.35;">感謝您的申請</h2>'
                        . '<div style="font-size:15px;color:#333;line-height:1.8;">'
                        . '<p>親愛的 ' . htmlspecialchars($contact_name, ENT_QUOTES, 'UTF-8') . ' 老師，</p>'
                        . '<p>我們已收到 <strong>' . htmlspecialchars($school_name, ENT_QUOTES, 'UTF-8') . '</strong> 的招生申請，申請編號為：<strong>#' . $application_id . '</strong>。</p>'
                        . '<p>我們的招生團隊將盡快審核您的申請，並在 3-5 個工作天內與您聯繫。</p>'
                        . '<div style="background:#f8f9fb;padding:16px;border-radius:8px;margin:20px 0;">'
                        . '<h3 style="margin:0 0 12px 0;color:#667eea;font-size:16px;">申請資訊摘要</h3>'
                        . '<table style="width:100%;font-size:14px;color:#333;">'
                        . '<tr><td style="padding:6px 0;"><strong>學校名稱：</strong>' . htmlspecialchars($school_name, ENT_QUOTES, 'UTF-8') . '</td></tr>'
                        . '<tr><td style="padding:6px 0;"><strong>聯絡人：</strong>' . htmlspecialchars($contact_name, ENT_QUOTES, 'UTF-8') . '</td></tr>'
                        . '<tr><td style="padding:6px 0;"><strong>聯絡電話：</strong>' . htmlspecialchars($contact_phone, ENT_QUOTES, 'UTF-8') . '</td></tr>'
                        . ($preferred_date ? '<tr><td style="padding:6px 0;"><strong>期望日期：</strong>' . htmlspecialchars($preferred_date, ENT_QUOTES, 'UTF-8') . '</td></tr>' : '')
                        . ($target_grades ? '<tr><td style="padding:6px 0;"><strong>目標年級：</strong>' . htmlspecialchars($target_grades, ENT_QUOTES, 'UTF-8') . '</td></tr>' : '')
                        . '</table></div>'
                        . '<p>如有任何問題，歡迎隨時與我們聯繫。</p>'
                        . '</div>'
                        . '<tr><td style="padding-top:22px;color:#999;font-size:12px;">此郵件由系統發送，請勿直接回覆。</td></tr>'
                        . '</td></tr>'
                        . '<tr><td style="background:#f8f9fb;padding:14px 24px;color:#98a6ad;font-size:12px;">© 康寧大學招生平台</td></tr>'
                        . '</table>'
                        . '</td></tr></table>'
                        . '</body></html>';
                    
                    $mail->Body = $mailBody;
                    $mail->AltBody = "感謝您的申請。我們已收到 " . $school_name . " 的招生申請，申請編號為 #" . $application_id . "。我們的招生團隊將盡快與您聯繫。";
                    $mail->send();
                } catch (Exception $e) {
                    // 郵件發送失敗不影響申請提交
                    error_log("郵件發送失敗: " . $e->getMessage());
                }
                
                // 使用 POST-Redirect-GET 模式防止重複提交
                header('Location: ' . $_SERVER['PHP_SELF'] . '?submitted=1&id=' . $application_id);
                exit;
            }
            
        } catch (Exception $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $result_message = '申請提交失敗：' . $ex->getMessage();
            $result_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>國中學校招生申請 - 康寧大學</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/csp/admission.css">
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
            transition: border-color 0.3s;
            font-family: inherit;
        }
        
        .field-group input:focus,
        .field-group select:focus,
        .field-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .field-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
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
    </style>
</head>
<body>
	<?php include("share/header.php"); ?>
<main>
    <div class="recruitment-container">
        <div class="header">
            <h1><i class="fas fa-school"></i> 國中學校招生申請</h1>
            <div class="subtitle">歡迎國中學校申請康寧大學前來招生</div>
        </div>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <strong>申請說明：</strong>填寫本表單後，我們的招生團隊將在 3-5 個工作天內與您聯繫，討論招生相關事宜。您也可以使用 Email 查詢和修改您的申請資料。
        </div>

        <!-- 查詢申請資料區塊 -->
        <div class="form-container" style="margin-bottom: 20px;">
            <div class="form-section">
                <h3><i class="fas fa-search"></i> 查詢/修改申請資料</h3>
                <form method="get" action="" style="display: flex; gap: 10px; align-items: flex-end;">
                    <input type="hidden" name="action" value="search">
                    <div class="field-group" style="width: 50%;">
                        <label>輸入 Email 查詢申請資料</label>
                        <input type="email" name="email" placeholder="請輸入申請時使用的 Email" 
                               value="<?php echo htmlspecialchars($search_email, ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <button type="submit" style="background: #28a745; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; width: 40%;">
                        <i class="fas fa-search"></i> 查詢
                    </button>
                </form>
                
                <?php if (count($application_list) > 0): ?>
                    <div style="margin-top: 20px;">
                        <p style="margin: 0 0 15px 0; font-weight: 600; color: #2e7d32; font-size: 16px;">
                            <i class="fas fa-check-circle"></i> 找到 <?php echo count($application_list); ?> 筆申請記錄
                        </p>
                        
                        <!-- 申請記錄列表 -->
                        <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px; background: #fff;">
                            <?php foreach ($application_list as $app): 
                                $is_selected = ($application_data && $application_data['id'] == $app['id']);
                                $status_text = [
                                    'pending' => ['text' => '待審核', 'color' => '#ff9800'],
                                    'approved' => ['text' => '已核准', 'color' => '#28a745'],
                                    'rejected' => ['text' => '已拒絕', 'color' => '#dc3545'],
                                    'completed' => ['text' => '已完成', 'color' => '#17a2b8']
                                ];
                                $status = $status_text[$app['status']] ?? ['text' => $app['status'], 'color' => '#6c757d'];
                            ?>
                                <div style="padding: 15px; border-bottom: 1px solid #eee; <?php echo $is_selected ? 'background: #e3f2fd; border-left: 4px solid #2196f3;' : ''; ?>">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                        <div style="flex: 1;">
                                            <div style="font-weight: 600; color: #333; margin-bottom: 5px;">
                                                申請編號：#<?php echo $app['id']; ?>
                                                <?php if ($is_selected): ?>
                                                    <span style="background: #2196f3; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; margin-left: 8px;">目前選擇</span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-size: 14px; color: #666; margin-bottom: 5px;">
                                                <i class="fas fa-building"></i> <?php echo htmlspecialchars($app['school_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                            <div style="font-size: 13px; color: #888;">
                                                <i class="fas fa-calendar"></i> <?php echo date('Y-m-d H:i', strtotime($app['created_at'])); ?>
                                                <?php if ($app['preferred_date']): ?>
                                                    | <i class="fas fa-clock"></i> 期望日期：<?php echo htmlspecialchars($app['preferred_date'], ENT_QUOTES, 'UTF-8'); ?>
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
                                            <i class="fas fa-edit"></i> <?php echo $is_selected ? '已選擇' : '選擇此筆'; ?>
                                        </button>
                                        <?php if ($is_selected): ?>
                                            <button type="button" onclick="loadApplicationData()" 
                                                    style="background: #28a745; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                                <i class="fas fa-edit"></i> 載入資料進行修改
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
                            <i class="fas fa-exclamation-triangle"></i> 找不到該 Email 的申請記錄
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php 
        // 檢查是否有提交成功的訊息
        if (isset($_GET['submitted']) && $_GET['submitted'] == '1' && isset($_GET['id'])) {
            $result_message = '申請已成功提交！申請編號：#' . htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8') . '。我們將盡快與您聯繫。';
            $result_type = 'success';
            // 清空查詢資料，確保表單欄位是空的
            $application_data = null;
            $search_email = '';
        }
        
        // 檢查是否有更新成功的訊息
        if (isset($_GET['updated']) && $_GET['updated'] == '1' && isset($_GET['id'])) {
            $result_message = '申請資料已成功更新！申請編號：#' . htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8');
            $result_type = 'success';
            // 清空查詢資料，確保表單欄位是空的
            $application_data = null;
            $search_email = '';
        }
        ?>
        
        <?php if ($result_message !== ''): ?>
            <div class="message <?php echo $result_type; ?>">
                <i class="fas fa-<?php echo ($result_type === 'success') ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($result_message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="post" id="recruitmentForm">
                <input type="hidden" name="action" id="form_action" value="submit">
                <input type="hidden" name="application_id" id="application_id" value="<?php echo $application_data ? $application_data['id'] : '0'; ?>">
                
                <!-- 學校基本資訊 -->
                <div class="form-section">
                    <h3><i class="fas fa-building"></i> 學校基本資訊</h3>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span> 學校名稱</label>
                            <input type="text" name="school_name" placeholder="請輸入學校全名" required 
                                   value="<?php 
                                   $school_name_value = '';
                                   if ($application_data) {
                                       $school_name_value = htmlspecialchars($application_data['school_name'], ENT_QUOTES, 'UTF-8');
                                   } elseif (isset($_POST['school_name'])) {
                                       $school_name_value = htmlspecialchars($_POST['school_name'], ENT_QUOTES, 'UTF-8');
                                   }
                                   echo $school_name_value;
                                   ?>" />
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
                            <input type="text" name="school_address" placeholder="完整的學校地址（選填）" 
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
                            <input type="text" name="contact_title" placeholder="例如：主任、組長" 
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
                            <label><span class="required">*</span> 聯絡電話</label>
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
                            <label><span class="required">*</span> 聯絡 Email</label>
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

                <!-- 招生相關資訊 -->
                <div class="form-section">
                    <h3><i class="fas fa-calendar-check"></i> 招生相關資訊</h3>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span><i class="fas fa-calendar-alt" style="color:#667eea;"></i> 期望招生日期</label>
                            <input type="date" name="preferred_date" required
                                   value="<?php 
                                   $preferred_date_value = '';
                                   if ($application_data) {
                                       $preferred_date_value = $application_data['preferred_date'];
                                   } elseif (isset($_POST['preferred_date'])) {
                                       $preferred_date_value = htmlspecialchars($_POST['preferred_date'], ENT_QUOTES, 'UTF-8');
                                   }
                                   echo $preferred_date_value;
                                   ?>" />
                        </div>
                        <div class="field-group">
                            <label><span class="required">*</span><i class="fas fa-clock" style="color:#667eea;"></i> 期望時間</label>
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
                                <option value="全天" <?php echo ($preferred_time_value === '全天') ? 'selected' : ''; ?>>全天</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span> 目標年級</label>
                            <input type="text" name="target_grades" placeholder="例如：三年級、二年級、或全部" required
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
                            <label><span class="required">*</span> 預期參與學生人數</label>
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
                            <label><span class="required">*</span> 場地類型</label>
                            <select name="venue_type" required>
                                <option value="">請選擇</option>
                                <?php
                                $venue_type_value = '';
                                if ($application_data) {
                                    $venue_type_value = $application_data['venue_type'];
                                } elseif (isset($_POST['venue_type'])) {
                                    $venue_type_value = $_POST['venue_type'];
                                }
                                ?>
                                <option value="禮堂" <?php echo ($venue_type_value === '禮堂') ? 'selected' : ''; ?>>禮堂</option>
                                <option value="活動中心" <?php echo ($venue_type_value === '活動中心') ? 'selected' : ''; ?>>活動中心</option>
                                <option value="教室" <?php echo ($venue_type_value === '教室') ? 'selected' : ''; ?>>教室</option>
                                <option value="其他" <?php echo ($venue_type_value === '其他') ? 'selected' : ''; ?>>其他</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-group">
                            <label>特殊需求</label>
                            <textarea name="special_requirements" placeholder="請說明任何特殊需求，例如：設備需求、時間限制等"><?php 
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
                            <textarea name="remarks" placeholder="其他需要補充的資訊"><?php 
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

                <button type="submit" class="submit-btn" id="submit_btn">
                    <i class="fas fa-paper-plane"></i> <span id="submit_btn_text">送出申請</span>
                </button>
            </form>
        </div>
    </div>
</main>
    <?php include("share/footer.php"); ?>
    
<script>
// 選擇申請記錄
function selectApplication(applicationId) {
    const email = document.querySelector('input[name="email"]').value;
    if (email) {
        window.location.href = '?action=search&email=' + encodeURIComponent(email) + '&application_id=' + applicationId;
    }
}

// 載入申請資料到表單
function loadApplicationData() {
    <?php if ($application_data): ?>
    const data = <?php echo json_encode($application_data); ?>;
    
    // 填充表單欄位
    document.querySelector('input[name="school_name"]').value = data.school_name || '';
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
    
    // 設定為更新模式
    document.getElementById('form_action').value = 'update';
    document.getElementById('application_id').value = data.id;
    document.getElementById('submit_btn_text').textContent = '更新申請資料';
    
    // 滾動到表單
    document.getElementById('recruitmentForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
    
    // 顯示提示訊息
    alert('申請資料已載入表單，您可以修改後點擊「更新申請資料」按鈕保存。');
    <?php endif; ?>
}

// 根據表單狀態更新提交按鈕文字
document.addEventListener('DOMContentLoaded', function() {
    const formAction = document.getElementById('form_action');
    const submitBtnText = document.getElementById('submit_btn_text');
    
    if (formAction && formAction.value === 'update') {
        submitBtnText.textContent = '更新申請資料';
    }
    
    // 如果有更新成功的訊息，3秒後自動移除查詢參數
    if (window.location.search.includes('updated=1')) {
        setTimeout(function() {
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }, 5000);
    }
    
    // 監聽表單提交，防止重複提交
    const form = document.getElementById('recruitmentForm');
    const submitBtn = document.getElementById('submit_btn');
    let isSubmitting = false;
    
    if (form) {
        form.addEventListener('submit', function(e) {
            // 如果正在提交中，阻止重複提交
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            
            const action = formAction.value;
            if (action === 'update') {
                if (!confirm('確定要更新申請資料嗎？')) {
                    e.preventDefault();
                    return false;
                }
            }
            
            // 標記為正在提交
            isSubmitting = true;
            
            // 禁用提交按鈕
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.6';
                submitBtn.style.cursor = 'not-allowed';
                const originalText = submitBtnText.textContent;
                submitBtnText.textContent = '處理中...';
                
                // 如果5秒後還沒提交成功，恢復按鈕（防止網路問題）
                setTimeout(function() {
                    if (isSubmitting) {
                        isSubmitting = false;
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';
                        submitBtn.style.cursor = 'pointer';
                        submitBtnText.textContent = originalText;
                    }
                }, 5000);
            }
        });
    }
});
</script>
</body>
</html>
