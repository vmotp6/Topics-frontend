<?php
// 設定時區為台灣時區 (UTC+8)
date_default_timezone_set('Asia/Taipei');

// 國中學校招生申請表單 - 康寧大學招生系統

// 載入配置檔案
require_once __DIR__ . '/config.php';
// 載入 session 配置
require_once __DIR__ . '/session_config.php';
// 雿輻 PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 撱箇? PDO ???
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('鞈?摨恍?憭望?: ' . $e->getMessage());
}

// 撱箄”嚗?銝剖飛?⊥??隢”
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS junior_school_recruitment_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        school_name VARCHAR(100) NOT NULL COMMENT '摮豢?迂',
        city VARCHAR(20) NOT NULL COMMENT '蝮??',
        district VARCHAR(20) NOT NULL COMMENT '?/?撣?,
        school_address VARCHAR(255) DEFAULT NULL COMMENT '摮豢?啣?',
        contact_name VARCHAR(50) NOT NULL COMMENT '?舐窗鈭箏???,
        contact_title VARCHAR(50) DEFAULT NULL COMMENT '?舐窗鈭箄蝔?,
        contact_phone VARCHAR(20) NOT NULL COMMENT '?舐窗?餉店',
        contact_email VARCHAR(120) NOT NULL COMMENT '?舐窗Email',
        preferred_date DATE DEFAULT NULL COMMENT '?????交?',
        preferred_time VARCHAR(50) DEFAULT NULL COMMENT '????嚗?憒?銝?????',
        target_grades VARCHAR(50) DEFAULT NULL COMMENT '?格?撟渡?嚗?憒?銝僑蝝?撟渡?嚗?,
        expected_students INT DEFAULT NULL COMMENT '????摮貊?鈭箸',
        venue_type VARCHAR(50) DEFAULT NULL COMMENT '?游憿?嚗?憒?蝳桀???摰歹?',
        special_requirements TEXT DEFAULT NULL COMMENT '?寞??瘙?,
        remarks TEXT DEFAULT NULL COMMENT '?酉',
        status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending' COMMENT '?唾????,
        admin_comment TEXT DEFAULT NULL COMMENT '蝞∠??∪?閮?,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '?唾???',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '?湔??',
        INDEX idx_school_name (school_name),
        INDEX idx_city (city),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at),
        INDEX idx_contact_email (contact_email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='?葉摮豢???唾?銵?");
} catch (PDOException $e) {
    die('?????”憭望?: ' . $e->getMessage());
}

// ???亥岷?唾?鞈?
$search_email = '';
$application_data = null;
$application_list = [];
$selected_application_id = isset($_GET['application_id']) ? (int)$_GET['application_id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'search' && isset($_GET['email'])) {
    $search_email = trim($_GET['email']);
    if ($search_email !== '') {
        try {
            // 根據 email 查詢資料表
            $stmt = $pdo->prepare("SELECT * FROM junior_school_recruitment_applications WHERE contact_email = ? ORDER BY created_at DESC");
            $stmt->execute([$search_email]);
            $application_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 如果有指定申請 ID，則查詢該申請
            if ($selected_application_id > 0) {
                foreach ($application_list as $app) {
                    if ($app['id'] == $selected_application_id) {
                        $application_data = $app;
                        break;
                    }
                }
            } elseif (count($application_list) > 0) {
                // 如果沒有 ID，則使用第一筆資料作為預設
                $application_data = $application_list[0];
            }
        } catch (PDOException $e) {
            error_log("?亥岷?唾?鞈?憭望?: " . $e->getMessage());
        }
    }
}

// ??銵典?
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

    // ?箸撽?嚗??急????閮?憛恬?
    if ($school_name === '' || $city === '' || $district === '' || 
        $contact_name === '' || $contact_phone === '' || $contact_email === '' ||
        $preferred_date === '' || $preferred_time === '' || $target_grades === '' ||
        $expected_students === '' || $venue_type === '') {
        $result_message = '隢‵撖急???憛急?雿?????賊?鞈?嚗?;
        $result_type = 'error';
    } else if (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $result_message = '隢?靘迤蝣箇? Email ?啣??澆?';
        $result_type = 'error';
    } else if (!is_numeric($expected_students) || (int)$expected_students <= 0) {
        $result_message = '????摮貊?鈭箸敹??臬之??0 ?摮?;
        $result_type = 'error';
    } else {
        try {
            $pdo->beginTransaction();
            
            $expected_students_int = (int)$expected_students;
            
            if ($action_post === 'update' && $application_id > 0) {
                // ?湔?唾?閮?
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
                    // ?湔??敺??啣????ｇ?皜征???雿?                    header('Location: ' . $_SERVER['PHP_SELF'] . '?updated=1&id=' . $application_id);
                    exit;
                } else {
                    $result_message = '?湔憭望?嚗銝閰脩隢??? Email 銝??;
                    $result_type = 'error';
                    $pdo->rollBack();
                }
            } else {
                // ??啁隢???                $ins = $pdo->prepare("INSERT INTO junior_school_recruitment_applications 
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
                
                // ?潮Ⅱ隤隞嗥策?唾?鈭綽???啣???
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
                    $mail->setFrom(SMTP_FROM_EMAIL, '摨瑕祐憭批飛????');
                    $mail->addAddress($contact_email, $contact_name);
                    
                    $mail->isHTML(true);
                    $mail->Subject = '?熒撖批之摮詻??隢歇?嗅 - ' . htmlspecialchars($school_name, ENT_QUOTES, 'UTF-8');
                    
                    $mailBody = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f4f6f8;">'
                        . '<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f4f6f8;padding:24px 0;">'
                        . '<tr><td align="center">'
                        . '<table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 4px 14px rgba(0,0,0,0.08);">'
                        . '<tr><td style="background:linear-gradient(90deg,#667eea,#764ba2);padding:18px 24px;color:#fff;font-size:18px;font-weight:700;">???唾?撌脫??/td></tr>'
                        . '<tr><td style="padding:22px 24px;">'
                        . '<h2 style="margin:0 0 12px 0;color:#222;font-size:22px;line-height:1.35;">???函??唾?</h2>'
                        . '<div style="font-size:15px;color:#333;line-height:1.8;">'
                        . '<p>閬芣???' . htmlspecialchars($contact_name, ENT_QUOTES, 'UTF-8') . ' ?葦嚗?/p>'
                        . '<p>?歇?嗅 <strong>' . htmlspecialchars($school_name, ENT_QUOTES, 'UTF-8') . '</strong> ???隢??唾?蝺刻??綽?<strong>#' . $application_id . '</strong>??/p>'
                        . '<p>??????撠敹怠祟?豢?隢?銝血 3-5 ?極雿予?扯??刻蝜怒?/p>'
                        . '<div style="background:#f8f9fb;padding:16px;border-radius:8px;margin:20px 0;">'
                        . '<h3 style="margin:0 0 12px 0;color:#667eea;font-size:16px;">?唾?鞈???</h3>'
                        . '<table style="width:100%;font-size:14px;color:#333;">'
                        . '<tr><td style="padding:6px 0;"><strong>摮豢?迂嚗?/strong>' . htmlspecialchars($school_name, ENT_QUOTES, 'UTF-8') . '</td></tr>'
                        . '<tr><td style="padding:6px 0;"><strong>?舐窗鈭綽?</strong>' . htmlspecialchars($contact_name, ENT_QUOTES, 'UTF-8') . '</td></tr>'
                        . '<tr><td style="padding:6px 0;"><strong>?舐窗?餉店嚗?/strong>' . htmlspecialchars($contact_phone, ENT_QUOTES, 'UTF-8') . '</td></tr>'
                        . ($preferred_date ? '<tr><td style="padding:6px 0;"><strong>???交?嚗?/strong>' . htmlspecialchars($preferred_date, ENT_QUOTES, 'UTF-8') . '</td></tr>' : '')
                        . ($target_grades ? '<tr><td style="padding:6px 0;"><strong>?格?撟渡?嚗?/strong>' . htmlspecialchars($target_grades, ENT_QUOTES, 'UTF-8') . '</td></tr>' : '')
                        . '</table></div>'
                        . '<p>憒?隞颱???嚗迭餈???蝜怒?/p>'
                        . '</div>'
                        . '<tr><td style="padding-top:22px;color:#999;font-size:12px;">甇日隞嗥蝟餌絞?潮?隢?湔????/td></tr>'
                        . '</td></tr>'
                        . '<tr><td style="background:#f8f9fb;padding:14px 24px;color:#98a6ad;font-size:12px;">穢 摨瑕祐憭批飛??撟喳</td></tr>'
                        . '</table>'
                        . '</td></tr></table>'
                        . '</body></html>';
                    
                    $mail->Body = $mailBody;
                    $mail->AltBody = "???函??唾????歇?嗅 " . $school_name . " ???隢??唾?蝺刻???#" . $application_id . "????????撠敹怨??刻蝜怒?;
                    $mail->send();
                } catch (Exception $e) {
                    // ?萎辣?潮仃??敶梢?唾??漱
                    error_log("?萎辣?潮仃?? " . $e->getMessage());
                }
                
                // 雿輻 POST-Redirect-GET 璅∪??脫迫???漱
                header('Location: ' . $_SERVER['PHP_SELF'] . '?submitted=1&id=' . $application_id);
                exit;
            }
            
        } catch (Exception $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $result_message = '?唾??漱憭望?嚗? . $ex->getMessage();
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
    <title>?葉摮豢???唾? - 摨瑕祐憭批飛</title>
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
            <h1><i class="fas fa-school"></i> ?葉摮豢???唾?</h1>
            <div class="subtitle">甇∟??葉摮豢?唾?摨瑕祐憭批飛????</div>
        </div>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <strong>?唾?隤芣?嚗?/strong>憛怠神?祈”?桀?嚗???????撠 3-5 ?極雿予?扯??刻蝜恬?閮????賊?鈭??銋隞乩蝙??Email ?亥岷?耨?寞?隢???        </div>

        <!-- ?亥岷?唾?鞈??憛?-->
        <div class="form-container" style="margin-bottom: 20px;">
            <div class="form-section">
                <h3><i class="fas fa-search"></i> ?亥岷/靽格?唾?鞈?</h3>
                <form method="get" action="" style="display: flex; gap: 10px; align-items: flex-end;">
                    <input type="hidden" name="action" value="search">
                    <div class="field-group" style="width: 50%;">
                        <label>頛詨 Email ?亥岷?唾?鞈?</label>
                        <input type="email" name="email" placeholder="隢撓?亦隢?雿輻??Email" 
                               value="<?php echo htmlspecialchars($search_email, ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <button type="submit" style="background: #28a745; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; width: 40%;">
                        <i class="fas fa-search"></i> ?亥岷
                    </button>
                </form>
                
                <?php if (count($application_list) > 0): ?>
                    <div style="margin-top: 20px;">
                        <p style="margin: 0 0 15px 0; font-weight: 600; color: #2e7d32; font-size: 16px;">
                            <i class="fas fa-check-circle"></i> ?曉 <?php echo count($application_list); ?> 蝑隢???                        </p>
                        
                        <!-- ?唾?閮??” -->
                        <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px; background: #fff;">
                            <?php foreach ($application_list as $app): 
                                $is_selected = ($application_data && $application_data['id'] == $app['id']);
                                $status_text = [
                                    'pending' => ['text' => '敺祟??, 'color' => '#ff9800'],
                                    'approved' => ['text' => '撌脫??, 'color' => '#28a745'],
                                    'rejected' => ['text' => '撌脫?蝯?, 'color' => '#dc3545'],
                                    'completed' => ['text' => '撌脣???, 'color' => '#17a2b8']
                                ];
                                $status = $status_text[$app['status']] ?? ['text' => $app['status'], 'color' => '#6c757d'];
                            ?>
                                <div style="padding: 15px; border-bottom: 1px solid #eee; <?php echo $is_selected ? 'background: #e3f2fd; border-left: 4px solid #2196f3;' : ''; ?>">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                        <div style="flex: 1;">
                                            <div style="font-weight: 600; color: #333; margin-bottom: 5px;">
                                                ?唾?蝺刻?嚗?<?php echo $app['id']; ?>
                                                <?php if ($is_selected): ?>
                                                    <span style="background: #2196f3; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; margin-left: 8px;">?桀??豢?</span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-size: 14px; color: #666; margin-bottom: 5px;">
                                                <i class="fas fa-building"></i> <?php echo htmlspecialchars($app['school_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                            <div style="font-size: 13px; color: #888;">
                                                <i class="fas fa-calendar"></i> <?php echo date('Y-m-d H:i', strtotime($app['created_at'])); ?>
                                                <?php if ($app['preferred_date']): ?>
                                                    | <i class="fas fa-clock"></i> ???交?嚗??php echo htmlspecialchars($app['preferred_date'], ENT_QUOTES, 'UTF-8'); ?>
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
                                            <i class="fas fa-edit"></i> <?php echo $is_selected ? '撌脤?? : '?豢?甇斤?'; ?>
                                        </button>
                                        <?php if ($is_selected): ?>
                                            <button type="button" onclick="loadApplicationData()" 
                                                    style="background: #28a745; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                                <i class="fas fa-edit"></i> 頛鞈??脰?靽格
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
                            <i class="fas fa-exclamation-triangle"></i> ?曆??啗府 Email ?隢???                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php 
        // 瑼Ｘ?臬??鈭斗???閮
        if (isset($_GET['submitted']) && $_GET['submitted'] == '1' && isset($_GET['id'])) {
            $result_message = '?唾?撌脫???鈭歹??唾?蝺刻?嚗?' . htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8') . '?????∪翰??舐鼠??;
            $result_type = 'success';
            // 皜征?亥岷鞈?嚗Ⅱ靽”?格?雿蝛箇?
            $application_data = null;
            $search_email = '';
        }
        
        // 瑼Ｘ?臬??唳???閮
        if (isset($_GET['updated']) && $_GET['updated'] == '1' && isset($_GET['id'])) {
            $result_message = '?唾?鞈?撌脫???堆??唾?蝺刻?嚗?' . htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8');
            $result_type = 'success';
            // 皜征?亥岷鞈?嚗Ⅱ靽”?格?雿蝛箇?
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
                
                <!-- 摮豢?箸鞈? -->
                <div class="form-section">
                    <h3><i class="fas fa-building"></i> 摮豢?箸鞈?</h3>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span> 摮豢?迂</label>
                            <input type="text" name="school_name" placeholder="隢撓?亙飛?∪?? required 
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
                            <label><span class="required">*</span> 蝮??</label>
                            <input type="text" name="city" placeholder="靘?嚗?????" required 
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
                            <label><span class="required">*</span> ?/?撣?/label>
                            <input type="text" name="district" placeholder="靘?嚗葉甇???璈?" required 
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
                            <label>摮豢?啣?</label>
                            <input type="text" name="school_address" placeholder="摰?飛?∪?嚗憛恬?" 
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

                <!-- ?舐窗鈭箄?閮?-->
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> ?舐窗鈭箄?閮?/h3>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span> ?舐窗鈭箏???/label>
                            <input type="text" name="contact_name" placeholder="隢撓?亥蝯∩犖憪?" required 
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
                            <label>?瑞迂</label>
                            <input type="text" name="contact_title" placeholder="靘?嚗蜓隞颯??? 
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
                            <label><span class="required">*</span> ?舐窗?餉店</label>
                            <input type="tel" name="contact_phone" placeholder="靘?嚗?2-1234-5678" required 
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
                            <label><span class="required">*</span> ?舐窗 Email</label>
                            <input type="email" name="contact_email" placeholder="靘?嚗ontact@school.edu.tw" required 
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

                <!-- ???賊?鞈? -->
                <div class="form-section">
                    <h3><i class="fas fa-calendar-check"></i> ???賊?鞈?</h3>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span><i class="fas fa-calendar-alt" style="color:#667eea;"></i> ?????交?</label>
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
                            <label><span class="required">*</span><i class="fas fa-clock" style="color:#667eea;"></i> ????</label>
                            <select name="preferred_time" required>
                                <option value="">隢??/option>
                                <?php
                                $preferred_time_value = '';
                                if ($application_data) {
                                    $preferred_time_value = $application_data['preferred_time'];
                                } elseif (isset($_POST['preferred_time'])) {
                                    $preferred_time_value = $_POST['preferred_time'];
                                }
                                ?>
                                <option value="銝?" <?php echo ($preferred_time_value === '銝?') ? 'selected' : ''; ?>>銝?</option>
                                <option value="銝?" <?php echo ($preferred_time_value === '銝?') ? 'selected' : ''; ?>>銝?</option>
                                <option value="?典予" <?php echo ($preferred_time_value === '?典予') ? 'selected' : ''; ?>>?典予</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span> ?格?撟渡?</label>
                            <input type="text" name="target_grades" placeholder="靘?嚗?撟渡???撟渡????券" required
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
                            <label><span class="required">*</span> ????摮貊?鈭箸</label>
                            <input type="number" name="expected_students" min="1" placeholder="靘?嚗?00" required
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
                            <label><span class="required">*</span> ?游憿?</label>
                            <select name="venue_type" required>
                                <option value="">隢??/option>
                                <?php
                                $venue_type_value = '';
                                if ($application_data) {
                                    $venue_type_value = $application_data['venue_type'];
                                } elseif (isset($_POST['venue_type'])) {
                                    $venue_type_value = $_POST['venue_type'];
                                }
                                ?>
                                <option value="蝳桀?" <?php echo ($venue_type_value === '蝳桀?') ? 'selected' : ''; ?>>蝳桀?</option>
                                <option value="瘣餃?銝剖?" <?php echo ($venue_type_value === '瘣餃?銝剖?') ? 'selected' : ''; ?>>瘣餃?銝剖?</option>
                                <option value="?恕" <?php echo ($venue_type_value === '?恕') ? 'selected' : ''; ?>>?恕</option>
                                <option value="?嗡?" <?php echo ($venue_type_value === '?嗡?') ? 'selected' : ''; ?>>?嗡?</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-group">
                            <label>?寞??瘙?/label>
                            <textarea name="special_requirements" placeholder="隢牧?遙雿畾?瘙?靘?嚗身??瘙????嗥?"><?php 
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
                            <label>?酉</label>
                            <textarea name="remarks" placeholder="?嗡??閬???鞈?"><?php 
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
                        <input type="text" name="captcha" id="captchaInput" placeholder="請輸入驗證碼" maxlength="6" required autocomplete="off" style="flex: 1; min-width: 150px; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 15px;">
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
                    <i class="fas fa-paper-plane"></i> <span id="submit_btn_text">??唾?</span>
                </button>
            </form>
        </div>
    </div>
</main>
    <?php include("share/footer.php"); ?>
    
<script>
// ?豢??唾?閮?
function selectApplication(applicationId) {
    const email = document.querySelector('input[name="email"]').value;
    if (email) {
        window.location.href = '?action=search&email=' + encodeURIComponent(email) + '&application_id=' + applicationId;
    }
}

// 頛?唾?鞈??啗”??function loadApplicationData() {
    <?php if ($application_data): ?>
    const data = <?php echo json_encode($application_data); ?>;
    
    // 憛怠?銵典甈?
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
    
    // 閮剖??箸?唳芋撘?    document.getElementById('form_action').value = 'update';
    document.getElementById('application_id').value = data.id;
    document.getElementById('submit_btn_text').textContent = '?湔?唾?鞈?';
    
    // 皛曉??啗”??    document.getElementById('recruitmentForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
    
    // 憿舐內?內閮
    alert('?唾?鞈?撌脰??亥”?殷??典隞乩耨?孵?暺???啁隢?????摮?);
    <?php endif; ?>
}

// ?寞?銵典???唳?鈭斗???摮?document.addEventListener('DOMContentLoaded', function() {
    const formAction = document.getElementById('form_action');
    const submitBtnText = document.getElementById('submit_btn_text');
    
    if (formAction && formAction.value === 'update') {
        submitBtnText.textContent = '?湔?唾?鞈?';
    }
    
    // 憒???唳???閮嚗?蝘??芸?蝘駁?亥岷?
    if (window.location.search.includes('updated=1')) {
        setTimeout(function() {
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }, 5000);
    }
    
    // ??銵典?漱嚗甇ａ?銴?鈭?    const form = document.getElementById('recruitmentForm');
    const submitBtn = document.getElementById('submit_btn');
    let isSubmitting = false;
    
    if (form) {
        form.addEventListener('submit', function(e) {
            // 憒?甇??漱銝哨??餅迫???漱
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            
            const action = formAction.value;
            if (action === 'update') {
                if (!confirm('蝣箏?閬?啁隢???嚗?)) {
                    e.preventDefault();
                    return false;
                }
            }
            
            // 璅??箸迤?冽?鈭?            isSubmitting = true;
            
            // 蝳?漱??
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.6';
                submitBtn.style.cursor = 'not-allowed';
                const originalText = submitBtnText.textContent;
                submitBtnText.textContent = '??銝?..';
                
                // 憒?5蝘????漱??嚗敺拇????脫迫蝬脰楝??嚗?                setTimeout(function() {
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

// 驗證碼刷新功能
function refreshCaptcha() {
    const captchaImage = document.getElementById('captchaImage');
    const captchaInput = document.getElementById('captchaInput');
    
    // 清空輸入框
    if (captchaInput) {
        captchaInput.value = '';
    }
    
    // 刷新驗證碼圖片（添加時間戳防止緩存）
    if (captchaImage) {
        captchaImage.src = 'captcha_image.php?t=' + new Date().getTime();
    }
}
</script>
</body>
</html>




