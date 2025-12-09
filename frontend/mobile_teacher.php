
<?php
// 手機版老師活動通知頁面：讓老師群發活動信給各國中負責人

// 載入 session 配置
require_once __DIR__ . '/session_config.php';

// 會用到的設定與工具
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

// 使用 PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 檢查登入狀態和角色（支援角色代碼和中文名稱，包含STA行政人員）
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['username']);
$user_role = $_SESSION['role'] ?? '';
$is_teacher = ($user_role === '老師' || $user_role === 'TEA' || $user_role === 'STA' || $user_role === '學校行政人員' || $user_role === 'DI');

if (!$isLoggedIn || !isset($_SESSION['role']) || !$is_teacher) {
	header("Location: index.php");
	exit;
}

// 建立 PDO 連線
try {
	$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USERNAME, DB_PASSWORD);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	die('資料庫連接失敗: ' . $e->getMessage());
}

// 獲取登入老師的資訊（用於自動填入發送者資訊）
$teacher_info = null;
$default_teacher_name = '';
$default_teacher_email = '';
$current_user_id = null;
try {
	// 從 teacher 表和 user 表獲取老師資訊
	// 注意：name 和 email 欄位在 user 表中，不在 teacher 表中
	$stmt = $pdo->prepare("
		SELECT u.id, u.name, t.department, u.email 
		FROM teacher t
		INNER JOIN user u ON t.user_id = u.id 
		WHERE u.username = ?
	");
	$stmt->execute([$_SESSION['username']]);
	$teacher_info = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if ($teacher_info) {
		$current_user_id = (int)$teacher_info['id'];
		$default_teacher_name = $teacher_info['name'] ?? '';
		// 優先使用 user 表的 email，如果沒有則使用其他來源
		$default_teacher_email = $teacher_info['email'] ?? '';
	}
} catch (PDOException $e) {
	error_log("獲取老師資訊失敗: " . $e->getMessage());
	// 如果查詢失敗，繼續使用空值（不影響主要功能）
}


// 處理表單送出
$result_message = '';
// 只有當不是快速新增聯絡人時，才處理發送通知表單
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['add_contact'])) {
	$teacher_name = isset($_POST['teacher_name']) ? trim($_POST['teacher_name']) : '';
	$teacher_email = isset($_POST['teacher_email']) ? trim($_POST['teacher_email']) : '';
	$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
	$content = isset($_POST['content']) ? trim($_POST['content']) : '';
	$event_date = isset($_POST['event_date']) ? trim($_POST['event_date']) : '';
	$link = isset($_POST['link']) ? trim($_POST['link']) : '';
	$selected_contacts = isset($_POST['contacts']) && is_array($_POST['contacts']) ? $_POST['contacts'] : [];
	$extra_emails = isset($_POST['extra_emails']) ? trim($_POST['extra_emails']) : '';

	// 基本驗證
	if ($subject === '' || $content === '') {
		$result_message = '請填寫主旨與內容';
	} else if (empty($selected_contacts) && $extra_emails === '') {
		$result_message = '請選擇至少一個收件人或輸入 Email 地址';
	} else {
		try {
			$pdo->beginTransaction();
			// 建立通知主檔（包含 user_id）
			$ins = $pdo->prepare("INSERT INTO activity_notifications (user_id, subject, content, event_date, link) VALUES (?, ?, ?, ?, ?)");
			$ins->execute([$current_user_id ?: null, $subject, $content, $event_date ?: null, $link ?: null]);
			$notification_id = (int)$pdo->lastInsertId();

			// 整理收件人清單（來自既有聯絡人 + 額外Email）
			$recipients = [];
			if (!empty($selected_contacts)) {
				// 從 schools_contacts 表查找聯絡人
				$in_placeholders = implode(',', array_fill(0, count($selected_contacts), '?'));
				$stmt = $pdo->prepare("SELECT id, email FROM schools_contacts WHERE id IN ($in_placeholders) AND is_active = 1");
				$stmt->execute($selected_contacts);
				while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
					if (filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
						$recipients[] = ['contact_id' => (int)$row['id'], 'email' => $row['email']];
					}
				}
			}
			if ($extra_emails !== '') {
				$emails = preg_split('/[,\n;\s]+/u', $extra_emails);
				foreach ($emails as $em) {
					$em = trim($em);
					if ($em !== '' && filter_var($em, FILTER_VALIDATE_EMAIL)) {
						$recipients[] = ['contact_id' => null, 'email' => $em];
					}
				}
			}

			// 驗證所有 contact_id 是否有效
			$valid_recipients = [];
			foreach ($recipients as $r) {
				$contact_id = ($r['contact_id'] !== null && $r['contact_id'] > 0) ? (int)$r['contact_id'] : null;
				
				if ($contact_id !== null) {
					// 驗證 contact_id 是否存在於 schools_contacts 表中
					$checkStmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM schools_contacts WHERE id = ? AND is_active = 1");
					$checkStmt->execute([$contact_id]);
					$checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
					
					if ($checkResult && $checkResult['cnt'] > 0) {
						// contact_id 存在，保留
						$valid_recipients[] = ['contact_id' => $contact_id, 'email' => $r['email']];
					} else {
						// contact_id 不存在，設為 NULL（當作額外 Email 處理）
						error_log("Warning: contact_id {$contact_id} not found in schools_contacts, setting to NULL");
						$valid_recipients[] = ['contact_id' => null, 'email' => $r['email']];
					}
				} else {
					// contact_id 為 NULL（額外 Email），直接保留
					$valid_recipients[] = ['contact_id' => null, 'email' => $r['email']];
				}
			}

			// 插入收件人佇列（使用驗證後的收件人清單）
			if (empty($valid_recipients)) {
				throw new Exception("沒有有效的收件人");
			}
			
			$insRec = $pdo->prepare("INSERT INTO activity_recipients (notification_id, contact_id, email) VALUES (?, ?, ?)");
			foreach ($valid_recipients as $r) {
				// 確保 contact_id 格式正確（NULL 或整數）
				$contact_id = ($r['contact_id'] !== null && $r['contact_id'] > 0) ? (int)$r['contact_id'] : null;
				try {
					$insRec->execute([$notification_id, $contact_id, $r['email']]);
				} catch (PDOException $ex) {
					// 如果外鍵錯誤，嘗試將 contact_id 設為 NULL 重試
					if (strpos($ex->getMessage(), 'foreign key constraint') !== false && $contact_id !== null) {
						$insRec->execute([$notification_id, null, $r['email']]);
					} else {
						throw $ex; // 其他錯誤繼續拋出
					}
				}
			}

			$pdo->commit();

			// 寄送郵件
			$mail_errors = [];
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
				$mail->SMTPDebug = 0; // 關閉調試模式（生產環境）
				$mail->Debugoutput = function($str, $level) use (&$mail_errors) {
					$mail_errors[] = $str;
				};
			// 動態寄件者名稱：優先使用老師姓名，其次使用通用名稱
			$fromName = $teacher_name ? $teacher_name : '康寧大學活動通知';
			$mail->setFrom(SMTP_FROM_EMAIL, $fromName);
			// 驗證 email 格式，只有有效的 email 才設置 Reply-To
			if ($teacher_email && filter_var($teacher_email, FILTER_VALIDATE_EMAIL)) {
				$mail->addReplyTo($teacher_email, $teacher_name ?: $teacher_email);
			} elseif ($default_teacher_email && filter_var($default_teacher_email, FILTER_VALIDATE_EMAIL)) {
				// 如果表單中的 email 無效，使用從資料庫查詢到的 email
				$mail->addReplyTo($default_teacher_email, $teacher_name ?: $default_teacher_email);
			}

			// 單封逐一寄送，避免大量收件被 SMTP 限制
			$stmtQ = $pdo->prepare("SELECT id, email FROM activity_recipients WHERE notification_id = ?");
			$stmtQ->execute([$notification_id]);
			$sent = 0; $failed = 0;
			while ($row = $stmtQ->fetch(PDO::FETCH_ASSOC)) {
				try {
					$mail->clearAddresses();
					$mail->addAddress($row['email']);
					$mail->isHTML(true);
					$mail->Subject = $subject;
					
					$escapedContent = nl2br(htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
					$escapedDate = $event_date ? htmlspecialchars($event_date, ENT_QUOTES, 'UTF-8') : '';
					$escapedLink = $link ? htmlspecialchars($link, ENT_QUOTES, 'UTF-8') : '';
					$ctaBtn = $link ? '<a href="' . $escapedLink . '" target="_blank" style="display:inline-block;padding:12px 20px;background:#4caf50;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;">查看活動詳情</a>' : '';
					$metaRows = '';
					if ($event_date) {
						$metaRows .= '<tr><td style="padding:8px 0;font-size:15px;color:#333;"><strong>活動日期：</strong>' . $escapedDate . '</td></tr>';
					}
					if ($link) {
						$metaRows .= '<tr><td style="padding:6px 0;font-size:15px;color:#333;"><strong>活動連結：</strong><a href="' . $escapedLink . '" target="_blank" style="color:#1a73e8;">' . $escapedLink . '</a></td></tr>';
					}
					$signature = '';
					if ($teacher_name || $teacher_email) {
						$signature = '<tr><td style="padding-top:18px;font-size:14px;color:#666;">' .
							($teacher_name ? htmlspecialchars($teacher_name, ENT_QUOTES, 'UTF-8') : '') .
							($teacher_email ? '（' . htmlspecialchars($teacher_email, ENT_QUOTES, 'UTF-8') . '）' : '') .
						'</td></tr>';
					}
					
					$body = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f4f6f8;">'
						. '<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f4f6f8;padding:24px 0;">'
						. '<tr><td align="center">'
						. '<table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 4px 14px rgba(0,0,0,0.08);">'
						. '<tr><td style="background:linear-gradient(90deg,#667eea,#764ba2);padding:18px 24px;color:#fff;font-size:18px;font-weight:700;">' . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . '</td></tr>'
						. '<tr><td style="padding:22px 24px;">'
						. '<h2 style="margin:0 0 12px 0;color:#222;font-size:22px;line-height:1.35;">' . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . '</h2>'
						. '<div style="font-size:15px;color:#333;line-height:1.8;">' . $escapedContent . '</div>'
						. '<table role="presentation" width="100%" style="margin-top:16px;">' . $metaRows . '</table>'
						. ($ctaBtn ? '<div style="margin-top:16px;">' . $ctaBtn . '</div>' : '')
						. $signature
						. '<tr><td style="padding-top:22px;color:#999;font-size:12px;">此郵件由系統發送，請勿直接回覆。</td></tr>'
						. '</td></tr>'
						. '<tr><td style="background:#f8f9fb;padding:14px 24px;color:#98a6ad;font-size:12px;">© 康寧大學</td></tr>'
						. '</table>'
						. '</td></tr></table>'
						. '</body></html>';
					
					$mail->Body = $body;
					$mail->AltBody = $content;
					$mail->send();
					$sent++;
				} catch (Exception $ex) {
					$error_msg = $ex->getMessage();
					error_log("郵件發送失敗 (收件人: {$row['email']}): " . $error_msg);
					$mail_errors[] = "收件人 {$row['email']}: " . $error_msg;
					$failed++;
				}
			}
			} catch (Exception $mail_init_error) {
				// 郵件初始化失敗
				error_log("郵件系統初始化失敗: " . $mail_init_error->getMessage());
				$result_message = '郵件系統初始化失敗：' . $mail_init_error->getMessage() . '。請檢查SMTP設定。';
				if ($pdo->inTransaction()) {
					$pdo->rollBack();
				}
				throw new Exception($result_message);
			}

			// 顯示結果：若無失敗，只顯示成功數量並使用綠色樣式
			if ($failed === 0 && $sent > 0) {
				$result_message = '寄送完成：成功 ' . $sent . ' 封';
				// 成功後清空頁面表單與暫存內容
				echo '<script>
					(function(){
						var form = document.getElementById("notificationForm");
						if (form) {
							form.reset();
							// 取消所有勾選
							var cbs = form.querySelectorAll("input[type=checkbox][name=\\"contacts[]\\"]");
							cbs.forEach(function(cb){ cb.checked = false; });
						}
						try { localStorage.removeItem("teacher_notification_form"); } catch(e){}
						// 移除查詢字串避免重新整理重送
						if (window.history && window.history.replaceState) {
							window.history.replaceState({}, document.title, location.pathname);
						}
					})();
				</script>';
				// 清空伺服端端的 POST 值，避免重新渲染時 value 屬性帶回舊資料
				$__clear_fields = ['teacher_name','teacher_email','subject','content','event_date','link','extra_emails','contacts'];
				foreach ($__clear_fields as $__k) { if (isset($_POST[$__k])) { unset($_POST[$__k]); } }
			} elseif ($sent > 0) {
				$result_message = '寄送完成：成功 ' . $sent . ' 封，失敗 ' . $failed . ' 封';
				if (!empty($mail_errors) && count($mail_errors) <= 5) {
					$result_message .= '。錯誤詳情：' . implode('; ', array_slice($mail_errors, 0, 5));
				}
			} else {
				$result_message = '寄送失敗：沒有成功發送任何郵件。';
				if (!empty($mail_errors)) {
					$result_message .= ' 錯誤：' . implode('; ', array_slice($mail_errors, 0, 3));
				} else {
					$result_message .= ' 請檢查SMTP設定或聯絡系統管理員。';
				}
				error_log("郵件發送完全失敗 - 成功: $sent, 失敗: $failed, 錯誤: " . implode(' | ', $mail_errors));
			}
		} catch (Exception $ex) {
			if ($pdo->inTransaction()) $pdo->rollBack();
			$result_message = '發送失敗：' . $ex->getMessage();
		}
	}
}

// 處理AJAX請求：獲取聯絡人列表
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_contacts') {
	header('Content-Type: application/json; charset=utf-8');
	try {
		$stmt = $pdo->query("
			SELECT 
				sc.id, 
				sc.school_code,
				sd.name as school_name, 
				sc.contact_name, 
				sc.email, 
				sc.phone,
				sc.title,
				sd.city, 
				sd.district
			FROM schools_contacts sc
			LEFT JOIN school_data sd ON sc.school_code = sd.school_code
			WHERE sc.is_active = 1 
			ORDER BY sd.city, sd.district, sd.name, sc.contact_name
		");
		$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
		echo json_encode(['success' => true, 'contacts' => $contacts], JSON_UNESCAPED_UNICODE);
		exit;
	} catch (PDOException $e) {
		error_log("取得聯絡人清單失敗: " . $e->getMessage());
		echo json_encode(['success' => false, 'message' => '取得聯絡人清單失敗'], JSON_UNESCAPED_UNICODE);
		exit;
	}
}

// 取得聯絡人清單供選擇（從 schools_contacts 表，JOIN school_data 顯示學校名稱）
$contacts = [];
try {
	$stmt = $pdo->query("
		SELECT 
			sc.id, 
			sc.school_code,
			sd.name as school_name, 
			sc.contact_name, 
			sc.email, 
			sc.phone,
			sc.title,
			sd.city, 
			sd.district
		FROM schools_contacts sc
		LEFT JOIN school_data sd ON sc.school_code = sd.school_code
		WHERE sc.is_active = 1 
		ORDER BY sd.city, sd.district, sd.name, sc.contact_name
	");
	$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
	$contacts = [];
	error_log("取得聯絡人清單失敗: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>老師活動通知系統 - 康寧大學</title>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
	<link rel="stylesheet" href="assets/csp/admission.css">
	<link rel="stylesheet" href="assets/csp/mobile_teacher.css">
	<style>
		/* 聯絡人維護搜尋樣式 */
		.modern-search-container-contact {
			position: relative;
			width: 100%;
		}
		
		.search-input-wrapper-contact {
			position: relative;
			display: flex;
			align-items: center;
		}
		
		.search-input-wrapper-contact input {
			width: 100%;
			padding: 12px 45px 12px 15px;
			border: 2px solid #e1e8ed;
			border-radius: 8px;
			font-size: 1rem;
			transition: all 0.3s ease;
			box-sizing: border-box;
		}
		
		.search-input-wrapper-contact input:focus {
			outline: none;
			border-color: #667eea;
			box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
		}
		
		.search-icon-contact {
			position: absolute;
			right: 15px;
			color: #6c757d;
			pointer-events: none;
			z-index: 1;
		}
		
		.clear-btn-contact {
			position: absolute;
			right: 40px;
			color: #999;
			cursor: pointer;
			z-index: 2;
			padding: 4px;
		}
		
		.clear-btn-contact:hover {
			color: #333;
		}
		
		.modern-search-results-contact {
			position: absolute;
			top: 100%;
			left: 0;
			right: 0;
			background: white;
			border: 1px solid #e1e8ed;
			border-radius: 8px;
			box-shadow: 0 4px 12px rgba(0,0,0,0.1);
			max-height: 300px;
			overflow-y: auto;
			z-index: 1000;
			display: none;
			margin-top: 2px;
		}
		
		.modern-search-results-contact.show {
			display: block;
		}
		
		.search-result-item-contact {
			padding: 12px 15px;
			cursor: pointer;
			border-bottom: 1px solid #f1f3f4;
			display: flex;
			align-items: center;
			gap: 10px;
			transition: background-color 0.2s ease;
		}
		
		.search-result-item-contact:last-child {
			border-bottom: none;
		}
		
		.search-result-item-contact:hover {
			background-color: #f8f9fa;
		}
		
		.search-result-item-contact i {
			color: #667eea;
			font-size: 0.9rem;
		}
		
		.school-info-contact {
			flex: 1;
			display: flex;
			flex-direction: column;
			gap: 4px;
		}
		
		.school-name-contact {
			font-weight: 600;
			color: #333;
			font-size: 14px;
		}
		
		.school-location-contact {
			font-size: 12px;
			color: #666;
		}
		
		.search-result-item-contact.more-results {
			background-color: #f8f9fa;
			color: #666;
			font-size: 12px;
			cursor: default;
			text-align: center;
			font-style: italic;
		}
		
		.search-result-item-contact.more-results:hover {
			background-color: #f8f9fa;
		}
		
		.help-text-contact {
			margin-top: 8px;
			font-size: 12px;
			color: #666;
			display: flex;
			align-items: center;
			gap: 6px;
		}
		
		.help-text-contact i {
			color: #667eea;
		}
		
		.field-error-contact {
			animation: slideDown 0.3s ease;
		}
		
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
	</style>
</head>
<body>
	<?php include("share/header.php"); ?>
<main>
	<div class="admission-container">
		<div class="header">
			<h1><i class="fas fa-envelope"></i> 老師活動通知系統</h1>
			<div class="subtitle">將活動訊息寄送給各國中負責人</div>
		</div>

		<?php if ($result_message !== ''): ?>
			<div class="message <?php echo (strpos($result_message, '失敗') === false ? 'success' : 'error'); ?>" id="resultMessage">
				<i class="fas fa-<?php echo (strpos($result_message, '失敗') === false ? 'check-circle' : 'exclamation-triangle'); ?>"></i>
				<?php echo htmlspecialchars($result_message, ENT_QUOTES, 'UTF-8'); ?>
			</div>
			<script>
				// 3秒後自動隱藏訊息
				setTimeout(function() {
					var msg = document.getElementById('resultMessage');
					if (msg) {
						msg.style.transition = 'opacity 0.5s ease';
						msg.style.opacity = '0';
						setTimeout(function() {
							msg.style.display = 'none';
						}, 500);
					}
				}, 3000);
			</script>
		<?php endif; ?>

		<div class="form-container">
			<form method="post" id="notificationForm">
				<div class="form-section">
					<h3><i class="fas fa-user"></i> 發送者資訊</h3>
					<div class="form-row">
						<div class="field-group">
							<label>老師姓名（系統自動填入）</label>
							<input type="text" name="teacher_name" id="teacher_name" placeholder="周建宇" value="<?php 
								echo isset($_POST['teacher_name']) && $_POST['teacher_name'] !== '' 
									? htmlspecialchars($_POST['teacher_name'], ENT_QUOTES, 'UTF-8') 
									: htmlspecialchars($default_teacher_name, ENT_QUOTES, 'UTF-8'); 
							?>" readonly style="background-color: #f8f9fa; color: #6c757d; cursor: not-allowed;" />
							<small style="color: #666; font-size: 0.9em;">將作為回覆名稱顯示於郵件中（系統自動填入）</small>
						</div>
						<div class="field-group">
							<label>老師 Email（系統自動填入）</label>
							<input type="email" name="teacher_email" id="teacher_email" placeholder="your.name@example.com" value="<?php 
								echo isset($_POST['teacher_email']) && $_POST['teacher_email'] !== '' 
									? htmlspecialchars($_POST['teacher_email'], ENT_QUOTES, 'UTF-8') 
									: htmlspecialchars($default_teacher_email, ENT_QUOTES, 'UTF-8'); 
							?>" readonly style="background-color: #f8f9fa; color: #6c757d; cursor: not-allowed;" />
							<small style="color: #666; font-size: 0.9em;">收件人回覆將寄至此信箱（系統自動填入）</small>
						</div>
					</div>
				</div>

				<div class="form-section">
					<h3><i class="fas fa-bullhorn"></i> 活動資訊</h3>
					<div class="field-group">
						<label><span class="required">*</span> 活動主旨</label>
						<input type="text" name="subject" id="subject" placeholder="康寧大學活動邀請" required value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject'], ENT_QUOTES, 'UTF-8') : ''; ?>" />
					</div>
					<div class="field-group">
						<label><span class="required">*</span> 活動內容</label>
						<textarea name="content" id="content" placeholder="請輸入活動說明、時間、地點、對象與注意事項..." required style="min-height: 140px;"><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
					</div>
					<div class="form-row">
						<div class="field-group">
							<label><i class="fas fa-calendar-alt" style="color:#667eea;"></i> 活動日期（可選）</label>
							<div class="event-date-wrapper">
								<input type="date" name="event_date" id="event_date" style="padding-right:40px; cursor:pointer;" min="<?php echo date('Y-m-d'); ?>" value="<?php echo isset($_POST['event_date']) ? htmlspecialchars($_POST['event_date'], ENT_QUOTES, 'UTF-8') : ''; ?>" />
								<i class="fas fa-calendar-check date-icon"></i>
							</div>
							<small style="color: #666; font-size: 0.85em; margin-top:6px; display:block;">
								<i class="fas fa-info-circle" style="color:#667eea; margin-right:4px;"></i> 
								<span>選擇活動舉辦日期</span>
							</small>
						</div>
						<div class="field-group">
							<label><i class="fas fa-link" style="color:#667eea;"></i> 活動連結（可選）</label>
							<div class="event-link-wrapper">
								<input type="url" name="link" id="link" placeholder="https://example.com" value="<?php echo isset($_POST['link']) ? htmlspecialchars($_POST['link'], ENT_QUOTES, 'UTF-8') : ''; ?>" />
								<i class="fas fa-globe link-icon"></i>
							</div>
							<small style="color: #666; font-size: 0.85em; margin-top:6px; display:block;">
								<i class="fas fa-info-circle" style="color:#667eea; margin-right:4px;"></i>
								<span>輸入活動相關網址</span>
							</small>
						</div>
					</div>
				</div>

				<div class="form-section">
					<h3><i class="fas fa-users"></i> 選擇收件人（國中負責人）</h3>
					<div id="contactsContainer" style="max-height: 300px; overflow-y: auto; border: 2px solid #e0e0e0; border-radius: 8px; padding: 15px; background: #f9f9f9; margin-bottom: 15px;">
						<?php if (empty($contacts)): ?>
							<p style="color:#999; text-align:center; padding:20px;">目前尚無聯絡人，請先建立聯絡人資料。</p>
						<?php else: ?>
							<div style="margin-bottom:10px; display:flex; gap:10px;">
								<button type="button" onclick="document.querySelectorAll('.recipients-list input[type=checkbox]').forEach(c => c.checked = true)" style="background:#28a745; padding:8px 16px; border:0; border-radius:6px; color:#fff; font-size:14px; cursor:pointer; font-weight:600;">
									<i class="fas fa-check-square"></i> 全選
								</button>
								<button type="button" onclick="document.querySelectorAll('.recipients-list input[type=checkbox]').forEach(c => c.checked = false)" style="background:#6c757d; padding:8px 16px; border:0; border-radius:6px; color:#fff; font-size:14px; cursor:pointer; font-weight:600;">
									<i class="fas fa-square"></i> 全不選
								</button>
								<button type="button" onclick="loadContactsList()" style="background:#667eea; padding:8px 16px; border:0; border-radius:6px; color:#fff; font-size:14px; cursor:pointer; font-weight:600;">
									<i class="fas fa-sync-alt"></i> 重新整理
								</button>
							</div>
							<div class="recipients-list" id="recipientsList" style="display:grid; gap:8px;">
							<?php foreach ($contacts as $c): ?>
								<div class="checkbox-item">
									<input type="checkbox" name="contacts[]" value="<?php echo (int)$c['id']; ?>" id="contact_<?php echo (int)$c['id']; ?>" />
									<label for="contact_<?php echo (int)$c['id']; ?>" style="cursor:pointer; flex:1;">
										<div style="font-weight:600; color:#333;">
											<?php echo htmlspecialchars($c['school_name'] ?? '未知學校', ENT_QUOTES, 'UTF-8'); ?>
										</div>
										<div style="font-size:0.9em; color:#666; margin-top:4px;">
											<?php if ($c['contact_name']): ?>
												<i class="fas fa-user"></i> <?php echo htmlspecialchars($c['contact_name'], ENT_QUOTES, 'UTF-8'); ?>
												<?php if ($c['title']): ?>
													<span style="color:#999;">（<?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?>）</span>
												<?php endif; ?>
												<span style="margin:0 8px;">·</span>
											<?php endif; ?>
											<?php if ($c['city'] || $c['district']): ?>
												<i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars(($c['city'] ?: '') . ($c['district'] ? ' ' . $c['district'] : ''), ENT_QUOTES, 'UTF-8'); ?>
												<span style="margin:0 8px;">·</span>
											<?php endif; ?>
											<i class="fas fa-envelope"></i> <?php echo htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8'); ?>
										</div>
									</label>
								</div>
							<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
					<small style="color: #666; font-size: 0.9em;">可多選；如需臨時收件人，亦可在下方手動輸入 Email</small>
				</div>

				<div class="form-section">
					<h3><i class="fas fa-envelope-open"></i> 額外收件 Email（選填）</h3>
					<div class="field-group">
						<label>額外收件 Email（以逗號、分號或換行分隔）</label>
						<textarea name="extra_emails" id="extra_emails" placeholder="a@school.edu.tw, b@school.edu.tw" style="min-height: 100px;"><?php echo isset($_POST['extra_emails']) ? htmlspecialchars($_POST['extra_emails'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
					</div>
				</div>

				<button type="submit" class="submit-btn">
					<i class="fas fa-paper-plane"></i> 送出活動通知
				</button>
			</form>
		</div>

		<div class="form-container" style="margin-top:20px;">
			<div class="form-section">
				<h3><i class="fas fa-address-book"></i> 聯絡人維護（快速新增）</h3>
				<form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?add_contact=1" id="addContactForm" onsubmit="return submitAddContactForm(event);">
					<div class="form-row">
						<div class="field-group">
							<label><span class="required">*</span> 學校</label>
							<div class="modern-search-container-contact">
								<div class="search-input-wrapper-contact">
									<input type="text" id="contact_school_search" name="contact_school_search" placeholder="請輸入學校名稱..." autocomplete="off" required 
										   value="<?php echo isset($_POST['contact_school_search']) ? htmlspecialchars($_POST['contact_school_search']) : ''; ?>" />
									<input type="hidden" id="contact_school_code" name="school_code" value="<?php echo isset($_POST['school_code']) ? htmlspecialchars($_POST['school_code']) : ''; ?>" />
									<div class="search-icon-contact">
										<i class="fas fa-search"></i>
									</div>
									<div class="clear-btn-contact" id="clearContactSchoolSearch" style="display: none;">
										<i class="fas fa-times"></i>
									</div>
								</div>
								<div id="contactSchoolResults" class="modern-search-results-contact"></div>
							</div>
							<div class="help-text-contact">
								<i class="fas fa-info-circle"></i> 輸入學校名稱即可即時搜尋，請從搜尋結果中選擇學校（不能自行輸入）
							</div>
							<div id="contact_school_error" class="field-error-contact" style="display: none; color: #d32f2f; font-size: 13px; margin-top: 8px; padding: 8px 12px; background-color: #ffebee; border-left: 3px solid #d32f2f; border-radius: 4px;">
								<i class="fas fa-exclamation-circle"></i> <span id="contact_school_error_text">請從系統提供的選項中選擇學校，不能自行輸入</span>
							</div>
						</div>
						<div class="field-group">
							<label>聯絡人姓名</label>
							<input type="text" name="contact_name" placeholder="聯絡人姓名" />
						</div>
					</div>
					<div class="form-row">
						<div class="field-group">
							<label><span class="required">*</span> Email</label>
							<input type="email" name="email" placeholder="email@school.edu.tw" required />
						</div>
						<div class="field-group">
							<label><span class="required">*</span> 電話</label>
							<input type="tel" name="phone" placeholder="例如：02-1234-5678" required />
						</div>
					</div>
					<div class="form-row">
						<div class="field-group">
							<label><span class="required">*</span> 職稱</label>
							<input type="text" name="title" placeholder="例如：校長、主任" required />
						</div>
					</div>
					<button type="submit" class="submit-btn" style="margin-top:15px;">
						<i class="fas fa-plus"></i> 新增聯絡人
					</button>
				</form>
				<?php
				// 快速新增聯絡人處理
				if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['add_contact'])) {
					// 優先從隱藏欄位讀取 school_code
					$school_code = trim($_POST['school_code'] ?? '');
					$contact_school_search = trim($_POST['contact_school_search'] ?? '');
					
					// 如果沒有 school_code，但 contact_school_search 有值，嘗試解析
					if (empty($school_code) && !empty($contact_school_search)) {
						// 檢查格式是否為 "學校名稱 (縣市區)"
						if (preg_match('/^(.+?) \(.+\)$/', $contact_school_search, $matches)) {
							$school_name = trim($matches[1]);
							$checkSchool = $pdo->prepare("SELECT school_code FROM school_data WHERE name = ? LIMIT 1");
							$checkSchool->execute([$school_name]);
							$schoolRow = $checkSchool->fetch(PDO::FETCH_ASSOC);
							if ($schoolRow) {
								$school_code = $schoolRow['school_code'];
							}
						}
					}
					
					$contact_name = trim($_POST['contact_name'] ?? '');
					$email = trim($_POST['email'] ?? '');
					$phone = trim($_POST['phone'] ?? '');
					$title = trim($_POST['title'] ?? '');
					
					if ($school_code === '' || $email === '' || $phone === '' || $title === '') {
						echo '<div class="message error"><i class="fas fa-exclamation-triangle"></i> 請填寫所有必填欄位（學校、Email、電話、職稱）</div>';
					} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
						echo '<div class="message error"><i class="fas fa-exclamation-triangle"></i> 請提供正確的 Email 地址格式</div>';
					} else {
						try {
							// 驗證 school_code 是否存在於 school_data 表
							$checkSchool = $pdo->prepare("SELECT school_code FROM school_data WHERE school_code = ? LIMIT 1");
							$checkSchool->execute([$school_code]);
							if ($checkSchool->rowCount() === 0) {
								throw new Exception("學校代碼不存在，請確認後再試");
							}
							
							$pdo->beginTransaction();
							
							// 檢查是否已存在相同的聯絡人（根據 school_code 和 email）
							$checkStmt = $pdo->prepare("SELECT id FROM schools_contacts WHERE school_code = ? AND email = ? LIMIT 1");
							$checkStmt->execute([$school_code, $email]);
							$existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
							
							if ($existing) {
								// 更新現有記錄
								$stmtA = $pdo->prepare("UPDATE schools_contacts SET contact_name=?, phone=?, title=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
								$stmtA->execute([$contact_name ?: null, $phone, $title, $existing['id']]);
								$rowsAffected = $stmtA->rowCount();
								$action = '更新';
							} else {
								// 插入新記錄
								$stmtA = $pdo->prepare("INSERT INTO schools_contacts (school_code, contact_name, email, phone, title) VALUES (?, ?, ?, ?, ?)");
								$stmtA->execute([$school_code, $contact_name ?: null, $email, $phone, $title]);
								$rowsAffected = $stmtA->rowCount();
								$action = '新增';
							}
							
							$pdo->commit();
							
							if ($rowsAffected > 0) {
								// 獲取學校名稱用於顯示
								$schoolNameStmt = $pdo->prepare("SELECT name FROM school_data WHERE school_code = ? LIMIT 1");
								$schoolNameStmt->execute([$school_code]);
								$schoolNameRow = $schoolNameStmt->fetch(PDO::FETCH_ASSOC);
								$school_name_display = $schoolNameRow['name'] ?? $school_code;
								
								// 獲取新增的聯絡人ID（如果是新增）
								$new_contact_id = null;
								if ($action === '新增') {
									$new_contact_id = (int)$pdo->lastInsertId();
								} else {
									$new_contact_id = (int)$existing['id'];
								}
								
								// 獲取完整的聯絡人資訊用於顯示
								$contactInfoStmt = $pdo->prepare("
									SELECT 
										sc.id, 
										sc.school_code,
										sd.name as school_name, 
										sc.contact_name, 
										sc.email, 
										sc.phone,
										sc.title,
										sd.city, 
										sd.district
									FROM schools_contacts sc
									LEFT JOIN school_data sd ON sc.school_code = sd.school_code
									WHERE sc.id = ? AND sc.is_active = 1
								");
								$contactInfoStmt->execute([$new_contact_id]);
								$new_contact_info = $contactInfoStmt->fetch(PDO::FETCH_ASSOC);
								
								$msg = '已' . $action . '聯絡人（學校：' . htmlspecialchars($school_name_display, ENT_QUOTES, 'UTF-8') . '，Email：' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '）';
								echo '<div id="contactAddSuccessMsg" class="message success"><i class="fas fa-check-circle"></i> ' . $msg . '</div>';
								echo '<script>
									// 3秒後自動隱藏新增聯絡人成功訊息
									setTimeout(function() {
										var msg = document.getElementById("contactAddSuccessMsg");
										if (msg) {
											msg.style.transition = "opacity 0.5s ease";
											msg.style.opacity = "0";
											setTimeout(function() {
												msg.style.display = "none";
											}, 500);
										}
									}, 3000);
								</script>';
								
								// 清空表單並動態更新聯絡人列表
								echo '<script>
									(function() {
										// 清空表單
										var form = document.getElementById("addContactForm");
										if (form) {
											form.reset();
											// 清除隱藏欄位
											var schoolCodeInput = document.getElementById("contact_school_code");
											var schoolSearchInput = document.getElementById("contact_school_search");
											if (schoolCodeInput) schoolCodeInput.value = "";
											if (schoolSearchInput) schoolSearchInput.value = "";
											// 隱藏清除按鈕
											var clearBtn = document.getElementById("clearContactSchoolSearch");
											if (clearBtn) clearBtn.style.display = "none";
											// 隱藏搜尋結果
											var resultsDiv = document.getElementById("contactSchoolResults");
											if (resultsDiv) resultsDiv.classList.remove("show");
										}
										
										// 動態更新聯絡人列表
										if (typeof loadContactsList === "function") {
											loadContactsList();
										} else {
											// 如果函數不存在，重新載入頁面
											setTimeout(function() {
												window.location.reload();
											}, 1000);
										}
										
										// 滾動到成功訊息位置
										setTimeout(function() {
											var successMsg = document.getElementById("contactAddSuccessMsg");
											if (successMsg) {
												successMsg.scrollIntoView({ behavior: "smooth", block: "center" });
											}
										}, 100);
									})();
								</script>';
								
								// 輸出新增的聯絡人資訊（用於動態更新）
								if ($new_contact_info) {
									echo '<script>
										window.newContactInfo = ' . json_encode($new_contact_info, JSON_UNESCAPED_UNICODE) . ';
									</script>';
								}
							} else {
								echo '<div class="message warning"><i class="fas fa-info-circle"></i> 操作完成，但未影響任何記錄（可能資料已存在）</div>';
							}
						} catch (PDOException $ex) {
							if ($pdo->inTransaction()) {
								$pdo->rollBack();
							}
							$errorMsg = $ex->getMessage();
							$errorCode = $ex->getCode();
							
							// 如果是唯一鍵衝突，提供更友好的錯誤信息
							if (strpos($errorMsg, 'Duplicate entry') !== false || strpos($errorMsg, '1062') !== false) {
								$errorMsg = '該 Email 與學校代碼的組合已存在，請使用不同的 Email 或學校代碼';
							}
							
							echo '<div class="message error"><i class="fas fa-exclamation-triangle"></i> 新增失敗：' . htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') . ' (錯誤代碼: ' . $errorCode . ')</div>';
						} catch (Exception $ex) {
							echo '<div class="message error"><i class="fas fa-exclamation-triangle"></i> 錯誤：' . htmlspecialchars($ex->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
						}
					}
				}
				?>
			</div>
		</div>

		<script>
		// 保存表單內容到 localStorage（防止頁面刷新丟失）
		document.addEventListener('DOMContentLoaded', function() {
			// 設置活動日期的最小值為今天
			var eventDateInput = document.getElementById('event_date');
			if (eventDateInput) {
				var today = new Date().toISOString().split('T')[0];
				eventDateInput.setAttribute('min', today);
				
				// 如果當前值小於今天，清空它
				if (eventDateInput.value && eventDateInput.value < today) {
					eventDateInput.value = '';
				}
			}
			
			// 自動隱藏所有成功和錯誤訊息（3秒後）
			var messages = document.querySelectorAll('.message.success, .message.error');
			messages.forEach(function(msg) {
				// 如果訊息還沒有設置自動隱藏
				if (!msg.hasAttribute('data-auto-hide')) {
					msg.setAttribute('data-auto-hide', 'true');
					setTimeout(function() {
						msg.style.transition = 'opacity 0.5s ease';
						msg.style.opacity = '0';
						setTimeout(function() {
							msg.style.display = 'none';
						}, 500);
					}, 3000);
				}
			});
			// 恢復保存的表單內容（但優先使用伺服器端自動填入的值）
			var savedForm = localStorage.getItem('teacher_notification_form');
			if (savedForm) {
				try {
					var formData = JSON.parse(savedForm);
					// 只有在伺服器端沒有自動填入值時，才使用 localStorage 的值
					var teacherNameInput = document.querySelector('input[name="teacher_name"]');
					var teacherEmailInput = document.querySelector('input[name="teacher_email"]');
					if (teacherNameInput && !teacherNameInput.value && formData.teacher_name) {
						teacherNameInput.value = formData.teacher_name;
					}
					if (teacherEmailInput && !teacherEmailInput.value && formData.teacher_email) {
						teacherEmailInput.value = formData.teacher_email;
					}
					if (formData.subject) document.querySelector('input[name="subject"]').value = formData.subject;
					if (formData.content) document.querySelector('textarea[name="content"]').value = formData.content;
					if (formData.event_date) document.querySelector('input[name="event_date"]').value = formData.event_date;
					if (formData.link) document.querySelector('input[name="link"]').value = formData.link;
					if (formData.extra_emails) document.querySelector('textarea[name="extra_emails"]').value = formData.extra_emails;
					
					// 恢復選擇的聯絡人
					if (formData.contacts && Array.isArray(formData.contacts)) {
						formData.contacts.forEach(function(contactId) {
							var checkbox = document.querySelector('input[type="checkbox"][value="' + contactId + '"]');
							if (checkbox) checkbox.checked = true;
						});
					}
				} catch(e) {
					console.log('無法恢復表單內容');
				}
			}
			
			// 監聽表單變化，自動保存
			var form = document.getElementById('notificationForm');
			if (form) {
				var inputs = form.querySelectorAll('input, textarea, select');
				inputs.forEach(function(input) {
					input.addEventListener('input', saveFormData);
					input.addEventListener('change', saveFormData);
				});
				
				// 表單提交成功後清除保存的內容
				form.addEventListener('submit', function() {
					localStorage.removeItem('teacher_notification_form');
				});
			}
		});
		
		function saveFormData() {
			var form = document.getElementById('notificationForm');
			if (!form) return;
			
			var formData = {
				teacher_name: form.querySelector('input[name="teacher_name"]').value || '',
				teacher_email: form.querySelector('input[name="teacher_email"]').value || '',
				subject: form.querySelector('input[name="subject"]').value || '',
				content: form.querySelector('textarea[name="content"]').value || '',
				event_date: form.querySelector('input[name="event_date"]').value || '',
				link: form.querySelector('input[name="link"]').value || '',
				extra_emails: form.querySelector('textarea[name="extra_emails"]').value || '',
				contacts: []
			};
			
			// 保存選擇的聯絡人
			var checkboxes = form.querySelectorAll('input[type="checkbox"][name="contacts[]"]:checked');
			checkboxes.forEach(function(cb) {
				formData.contacts.push(cb.value);
			});
			
			localStorage.setItem('teacher_notification_form', JSON.stringify(formData));
		}
		</script>

		<?php
		// 顯示最近的發送記錄
		try {
			$stmtHistory = $pdo->query("
				SELECT 
					n.id,
					n.subject,
					n.created_at,
					COUNT(r.id) as total_recipients
				FROM activity_notifications n
				LEFT JOIN activity_recipients r ON n.id = r.notification_id
				GROUP BY n.id
				ORDER BY n.created_at DESC
				LIMIT 10
			");
			$history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);
			
			if (!empty($history)):
		?>
		<div class="form-container" style="margin-top:20px;">
			<div class="form-section">
				<button type="button" id="toggleHistoryBtn" onclick="toggleHistory()" style="background:#6c757d; color:#fff; border:0; padding:8px 16px; border-radius:6px; cursor:pointer; font-size:14px; font-weight:600; display:flex; text-align:center; gap:6px;">
						<i class="fas fa-eye-slash" style="margin-top:5px;"></i> <span id="toggleHistoryText">隱藏記錄</span>
				</button>
				<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
					<h3 style="margin:0;"><i class="fas fa-history"></i> 最近的發送記錄</h3>

				</div>
				<div id="historyTableContainer" style="overflow-x:auto;">
					<table style="width:100%; border-collapse:collapse; font-size:14px;">
						<thead>
							<tr style="background:#f8f9fa; border-bottom:2px solid #dee2e6;">
								<th style="padding:12px; text-align:left; color:#667eea;">時間</th>
								<th style="padding:12px; text-align:left; color:#667eea;">主旨</th>
								<th style="padding:12px; text-align:center; color:#667eea;">收件數</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($history as $h): ?>
							<tr style="border-bottom:1px solid #eee;">
								<td style="padding:12px;"><?php echo htmlspecialchars(date('m/d H:i', strtotime($h['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
								<td style="padding:12px;"><?php echo htmlspecialchars(mb_substr($h['subject'], 0, 30), ENT_QUOTES, 'UTF-8'); ?><?php echo mb_strlen($h['subject']) > 30 ? '...' : ''; ?></td>
								<td style="padding:12px; text-align:center; font-weight:600;"><?php echo (int)$h['total_recipients']; ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<script>
		// 控制歷史記錄顯示/隱藏
		function toggleHistory() {
			var container = document.getElementById('historyTableContainer');
			var btn = document.getElementById('toggleHistoryBtn');
			var text = document.getElementById('toggleHistoryText');
			var icon = btn.querySelector('i');
			
			if (container.style.display === 'none') {
				container.style.display = 'block';
				text.textContent = '隱藏記錄';
				icon.className = 'fas fa-eye-slash';
				btn.style.background = '#6c757d';
				localStorage.setItem('historyTableVisible', 'true');
			} else {
				container.style.display = 'none';
				text.textContent = '顯示記錄';
				icon.className = 'fas fa-eye';
				btn.style.background = '#28a745';
				localStorage.setItem('historyTableVisible', 'false');
			}
		}
		
		// 頁面載入時恢復隱藏狀態
		document.addEventListener('DOMContentLoaded', function() {
			var savedState = localStorage.getItem('historyTableVisible');
			var container = document.getElementById('historyTableContainer');
			var btn = document.getElementById('toggleHistoryBtn');
			var text = document.getElementById('toggleHistoryText');
			var icon = btn.querySelector('i');
			
			if (savedState === 'false') {
				container.style.display = 'none';
				text.textContent = '顯示記錄';
				icon.className = 'fas fa-eye';
				btn.style.background = '#28a745';
			}
		});
		</script>
		<?php
			endif;
		} catch (PDOException $e) {
			// 忽略錯誤，不影響主要功能
		}
		?>
	</div>
</main>
    <?php include("share/footer.php"); ?>
	
	<script>
	// 聯絡人維護 - 學校搜尋功能
	document.addEventListener('DOMContentLoaded', function() {
		function initializeContactSchoolSearch() {
			const schoolInput = document.getElementById('contact_school_search');
			const resultsDiv = document.getElementById('contactSchoolResults');
			const clearBtn = document.getElementById('clearContactSchoolSearch');
			
			if (!schoolInput || !resultsDiv) {
				return;
			}
			
			// 防抖函數
			let searchTimeout;
			const debounceSearch = (callback, delay) => {
				clearTimeout(searchTimeout);
				searchTimeout = setTimeout(callback, delay);
			};
			
			// 輸入事件監聽
			schoolInput.addEventListener('input', function() {
				const keyword = this.value.trim();
				
				// 顯示/隱藏清除按鈕
				if (clearBtn) {
					clearBtn.style.display = keyword.length > 0 ? 'block' : 'none';
				}
				
				if (keyword.length === 0) {
					resultsDiv.classList.remove('show');
					clearContactSchoolError();
					return;
				}
				
				// 防抖搜尋
				debounceSearch(() => {
					performContactSchoolSearch(keyword);
				}, 300);
			});
			
			// 失去焦點時立即驗證
			schoolInput.addEventListener('blur', function() {
				clearTimeout(schoolInput.validationTimeout);
				schoolInput.validationTimeout = setTimeout(validateContactSchoolInputImmediate, 200);
			});
			
			// 清除按鈕點擊
			if (clearBtn) {
				clearBtn.addEventListener('click', function(e) {
					e.preventDefault();
					e.stopPropagation();
					clearContactSchoolSearch();
				});
			}
			
			// 鍵盤事件
			schoolInput.addEventListener('keydown', function(e) {
				if (e.key === 'Escape') {
					clearContactSchoolSearch();
				}
			});
			
			// 如果有初始值，顯示清除按鈕
			if (schoolInput.value) {
				if (clearBtn) {
					clearBtn.style.display = 'block';
				}
			}
		}
		
		// 執行學校搜尋
		function performContactSchoolSearch(keyword) {
			const resultsDiv = document.getElementById('contactSchoolResults');
			const schoolInput = document.getElementById('contact_school_search');
			
			if (keyword.length < 2) {
				resultsDiv.innerHTML = '<div class="search-result-item-contact">請輸入至少2個字元</div>';
				resultsDiv.classList.add('show');
				clearContactSchoolError();
				return;
			}
			
			// 顯示載入中
			resultsDiv.innerHTML = '<div class="search-result-item-contact"><i class="fas fa-spinner fa-spin"></i> 搜尋中...</div>';
			resultsDiv.classList.add('show');
			clearContactSchoolError();
			
			// 從API獲取搜尋結果
			fetch(`api/school_data_api.php?action=search&keyword=${encodeURIComponent(keyword)}&v=20241014-4`)
				.then(response => response.json())
				.then(data => {
					if (data.schools && data.schools.length > 0) {
						resultsDiv.innerHTML = data.schools.map(school => {
							let displayName = school.name;
							let additionalInfo = '';
							
							if (school.all_names && school.all_names.length > 1) {
								additionalInfo = `<div class="school-alternative-names">其他名稱: ${school.all_names.join(', ')}</div>`;
							}
							
							return `<div class="search-result-item-contact" onclick="selectContactSchool('${school.school_code || ''}', '${school.name.replace(/'/g, "\\'")}', '${school.city || ''}', '${school.district || ''}')">
								<i class="fas fa-school"></i>
								<div class="school-info-contact">
									<span class="school-name-contact">${displayName}</span>
									<span class="school-location-contact">${school.city || ''} ${school.district || ''}</span>
									${additionalInfo}
								</div>
							</div>`;
						}).join('');
						
						if (data.total > 20) {
							resultsDiv.innerHTML += `<div class="search-result-item-contact more-results">還有 ${data.total - 20} 個結果...</div>`;
						}
						clearContactSchoolError();
					} else {
						resultsDiv.innerHTML = '<div class="search-result-item-contact">找不到匹配的學校</div>';
						clearContactSchoolError();
					}
				})
				.catch(error => {
					console.error('搜尋錯誤:', error);
					resultsDiv.innerHTML = '<div class="search-result-item-contact">搜尋失敗，請稍後再試</div>';
					clearContactSchoolError();
				});
		}
		
		// 清除學校輸入錯誤提示
		function clearContactSchoolError() {
			const errorDiv = document.getElementById('contact_school_error');
			const input = document.getElementById('contact_school_search');
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
		function showContactSchoolError(message) {
			const errorDiv = document.getElementById('contact_school_error');
			const errorText = document.getElementById('contact_school_error_text');
			const input = document.getElementById('contact_school_search');
			
			if (errorDiv && errorText) {
				errorText.textContent = message || '請從系統提供的選項中選擇學校，不能自行輸入';
				errorDiv.style.display = 'block';
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
		function validateContactSchoolInput() {
			const input = document.getElementById('contact_school_search');
			if (!input) return;
			
			const value = input.value.trim();
			const resultsDiv = document.getElementById('contactSchoolResults');
			
			// 如果為空，不顯示錯誤（由required屬性處理）
			if (!value) {
				clearContactSchoolError();
				return;
			}
			
			// 如果下拉選單正在顯示，表示用戶還在選擇中，不顯示錯誤
			if (resultsDiv && resultsDiv.classList.contains('show')) {
				clearContactSchoolError();
				return;
			}
			
			// 檢查格式：優先檢查隱藏欄位的 school_code
			const schoolCodeInput = document.getElementById('contact_school_code');
			const hasValidCode = schoolCodeInput && schoolCodeInput.value.trim().length > 0;
			
			if (hasValidCode) {
				// 如果有 school_code，檢查顯示格式是否為學校名稱 (縣市區)
				const schoolFormatPattern = /^.+ \(.+\)$/;
				if (!schoolFormatPattern.test(value)) {
					showContactSchoolError('請從系統提供的選項中選擇學校，不能自行輸入');
				} else {
					clearContactSchoolError();
				}
			} else {
				// 沒有 school_code，檢查格式是否為學校名稱 (縣市區)
				const schoolFormatPattern = /^.+ \(.+\)$/;
				if (!schoolFormatPattern.test(value)) {
					showContactSchoolError('請從系統提供的選項中選擇學校，不能自行輸入');
				} else {
					clearContactSchoolError();
				}
			}
		}
		
		// 立即驗證（不延遲）- 用於失去焦點時
		function validateContactSchoolInputImmediate() {
			validateContactSchoolInput();
		}
		
		// 清除搜尋
		function clearContactSchoolSearch() {
			const schoolInput = document.getElementById('contact_school_search');
			const schoolCodeInput = document.getElementById('contact_school_code');
			
			if (schoolInput) {
				schoolInput.value = '';
				schoolInput.removeAttribute('data-school-code');
				schoolInput.removeAttribute('data-school-name');
			}
			if (schoolCodeInput) {
				schoolCodeInput.value = '';
			}
			
			document.getElementById('contactSchoolResults').classList.remove('show');
			document.getElementById('clearContactSchoolSearch').style.display = 'none';
			clearContactSchoolError();
		}
		
		// 選擇學校
		function selectContactSchool(schoolCode, schoolName, city, district) {
			const schoolInput = document.getElementById('contact_school_search');
			const schoolCodeInput = document.getElementById('contact_school_code');
			
			// 顯示學校名稱（格式：學校名稱 (縣市區)）
			const displayName = `${schoolName} (${city || ''}${district || ''})`;
			schoolInput.value = displayName;
			
			// 保存 school_code 到隱藏欄位
			if (schoolCodeInput) {
				schoolCodeInput.value = schoolCode;
			}
			
			// 同時保存到 data 屬性作為備份
			schoolInput.setAttribute('data-school-code', schoolCode);
			schoolInput.setAttribute('data-school-name', displayName);
			
			document.getElementById('contactSchoolResults').classList.remove('show');
			const clearBtn = document.getElementById('clearContactSchoolSearch');
			if (clearBtn) {
				clearBtn.style.display = 'block';
			}
			
			// 清除錯誤提示（因為用戶已從系統選項中選擇）
			clearContactSchoolError();
		}
		
		// 將函數暴露到全局作用域
		window.selectContactSchool = selectContactSchool;
		
		// AJAX提交新增聯絡人表單
		window.submitAddContactForm = function(event) {
			event.preventDefault();
			
			const form = document.getElementById('addContactForm');
			if (!form) return false;
			
			const formData = new FormData(form);
			const submitBtn = form.querySelector('button[type="submit"]');
			const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
			
			// 顯示載入狀態
			if (submitBtn) {
				submitBtn.disabled = true;
				submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 處理中...';
			}
			
			// 發送AJAX請求
			fetch(form.action, {
				method: 'POST',
				body: formData
			})
			.then(response => response.text())
			.then(html => {
				// 創建臨時div來解析響應
				const tempDiv = document.createElement('div');
				tempDiv.innerHTML = html;
				
				// 查找成功或錯誤訊息
				const successMsg = tempDiv.querySelector('#contactAddSuccessMsg, .message.success');
				const errorMsg = tempDiv.querySelector('.message.error, .message.warning');
				
				// 移除舊的訊息
				const oldMsg = document.querySelector('#contactAddSuccessMsg, .message.success, .message.error');
				if (oldMsg && oldMsg.closest('.form-container')) {
					oldMsg.remove();
				}
				
				// 顯示新訊息
				if (successMsg) {
					const formContainer = form.closest('.form-container');
					if (formContainer) {
						const clonedMsg = successMsg.cloneNode(true);
						formContainer.insertBefore(clonedMsg, formContainer.firstChild);
						
						// 3秒後自動隱藏訊息
						setTimeout(function() {
							if (clonedMsg) {
								clonedMsg.style.transition = 'opacity 0.5s ease';
								clonedMsg.style.opacity = '0';
								setTimeout(function() {
									clonedMsg.style.display = 'none';
								}, 500);
							}
						}, 3000);
					}
					
					// 清空表單
					form.reset();
					const schoolCodeInput = document.getElementById('contact_school_code');
					const schoolSearchInput = document.getElementById('contact_school_search');
					if (schoolCodeInput) schoolCodeInput.value = '';
					if (schoolSearchInput) schoolSearchInput.value = '';
					const clearBtn = document.getElementById('clearContactSchoolSearch');
					if (clearBtn) clearBtn.style.display = 'none';
					const resultsDiv = document.getElementById('contactSchoolResults');
					if (resultsDiv) resultsDiv.classList.remove('show');
					
					// 動態更新聯絡人列表
					loadContactsList();
					
					// 滾動到成功訊息
					setTimeout(() => {
						const newSuccessMsg = document.querySelector('#contactAddSuccessMsg, .message.success');
						if (newSuccessMsg) {
							newSuccessMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
						}
					}, 100);
				} else if (errorMsg) {
					const formContainer = form.closest('.form-container');
					if (formContainer) {
						formContainer.insertBefore(errorMsg.cloneNode(true), formContainer.firstChild);
					}
				}
				
				// 恢復按鈕狀態
				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.innerHTML = originalBtnText;
				}
			})
			.catch(error => {
				console.error('提交失敗:', error);
				alert('提交失敗，請稍後再試。錯誤：' + error.message);
				
				// 恢復按鈕狀態
				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.innerHTML = originalBtnText;
				}
			});
			
			return false;
		};
		
		// 動態載入聯絡人列表
		window.loadContactsList = function() {
			const recipientsList = document.getElementById('recipientsList');
			const contactsContainer = document.getElementById('contactsContainer');
			
			if (!recipientsList && !contactsContainer) return;
			
			// 顯示載入中
			if (recipientsList) {
				recipientsList.innerHTML = '<div style="text-align:center; padding:20px; color:#999;"><i class="fas fa-spinner fa-spin"></i> 載入中...</div>';
			}
			
			// 使用AJAX獲取最新的聯絡人列表
			fetch('<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?ajax=get_contacts')
				.then(response => response.json())
				.then(data => {
					if (data.success && data.contacts) {
						// 保存當前選中的聯絡人
						const selectedContacts = [];
						if (recipientsList) {
							const checkboxes = recipientsList.querySelectorAll('input[type="checkbox"]:checked');
							checkboxes.forEach(cb => selectedContacts.push(cb.value));
						}
						
						// 更新聯絡人列表
						if (recipientsList) {
							if (data.contacts.length === 0) {
								recipientsList.innerHTML = '<p style="color:#999; text-align:center; padding:20px;">目前尚無聯絡人，請先建立聯絡人資料。</p>';
							} else {
								let html = '';
								data.contacts.forEach(function(c) {
									const isChecked = selectedContacts.includes(String(c.id)) ? 'checked' : '';
									html += `
										<div class="checkbox-item">
											<input type="checkbox" name="contacts[]" value="${c.id}" id="contact_${c.id}" ${isChecked} />
											<label for="contact_${c.id}" style="cursor:pointer; flex:1;">
												<div style="font-weight:600; color:#333;">
													${escapeHtml(c.school_name || '未知學校')}
												</div>
												<div style="font-size:0.9em; color:#666; margin-top:4px;">
													${c.contact_name ? `<i class="fas fa-user"></i> ${escapeHtml(c.contact_name)}${c.title ? ` <span style="color:#999;">（${escapeHtml(c.title)}）</span>` : ''} <span style="margin:0 8px;">·</span>` : ''}
													${(c.city || c.district) ? `<i class="fas fa-map-marker-alt"></i> ${escapeHtml((c.city || '') + (c.district ? ' ' + c.district : ''))} <span style="margin:0 8px;">·</span>` : ''}
													<i class="fas fa-envelope"></i> ${escapeHtml(c.email)}
												</div>
											</label>
										</div>
									`;
								});
								recipientsList.innerHTML = html;
							}
						}
						
						// 更新容器顯示狀態（如果recipientsList不存在）
						if (contactsContainer && !recipientsList) {
							if (data.contacts.length === 0) {
								contactsContainer.innerHTML = '<p style="color:#999; text-align:center; padding:20px;">目前尚無聯絡人，請先建立聯絡人資料。</p>';
							} else {
								const buttonsHtml = `
									<div style="margin-bottom:10px; display:flex; gap:10px;">
										<button type="button" onclick="document.querySelectorAll('.recipients-list input[type=checkbox]').forEach(c => c.checked = true)" style="background:#28a745; padding:8px 16px; border:0; border-radius:6px; color:#fff; font-size:14px; cursor:pointer; font-weight:600;">
											<i class="fas fa-check-square"></i> 全選
										</button>
										<button type="button" onclick="document.querySelectorAll('.recipients-list input[type=checkbox]').forEach(c => c.checked = false)" style="background:#6c757d; padding:8px 16px; border:0; border-radius:6px; color:#fff; font-size:14px; cursor:pointer; font-weight:600;">
											<i class="fas fa-square"></i> 全不選
										</button>
										<button type="button" onclick="loadContactsList()" style="background:#667eea; padding:8px 16px; border:0; border-radius:6px; color:#fff; font-size:14px; cursor:pointer; font-weight:600;">
											<i class="fas fa-sync-alt"></i> 重新整理
										</button>
									</div>
									<div class="recipients-list" id="recipientsList" style="display:grid; gap:8px;">
									</div>
								`;
								contactsContainer.innerHTML = buttonsHtml;
								// 重新獲取recipientsList引用
								const newRecipientsList = document.getElementById('recipientsList');
								if (newRecipientsList && data.contacts.length > 0) {
									let html = '';
									data.contacts.forEach(function(c) {
										const isChecked = selectedContacts.includes(String(c.id)) ? 'checked' : '';
										html += `
											<div class="checkbox-item">
												<input type="checkbox" name="contacts[]" value="${c.id}" id="contact_${c.id}" ${isChecked} />
												<label for="contact_${c.id}" style="cursor:pointer; flex:1;">
													<div style="font-weight:600; color:#333;">
														${escapeHtml(c.school_name || '未知學校')}
													</div>
													<div style="font-size:0.9em; color:#666; margin-top:4px;">
														${c.contact_name ? `<i class="fas fa-user"></i> ${escapeHtml(c.contact_name)}${c.title ? ` <span style="color:#999;">（${escapeHtml(c.title)}）</span>` : ''} <span style="margin:0 8px;">·</span>` : ''}
														${(c.city || c.district) ? `<i class="fas fa-map-marker-alt"></i> ${escapeHtml((c.city || '') + (c.district ? ' ' + c.district : ''))} <span style="margin:0 8px;">·</span>` : ''}
														<i class="fas fa-envelope"></i> ${escapeHtml(c.email)}
													</div>
												</label>
											</div>
										`;
									});
									newRecipientsList.innerHTML = html;
								}
							}
						}
					} else {
						console.error('載入聯絡人列表失敗:', data.message || '未知錯誤');
					}
				})
				.catch(error => {
					console.error('載入聯絡人列表錯誤:', error);
					if (recipientsList) {
						recipientsList.innerHTML = '<div style="text-align:center; padding:20px; color:#d32f2f;"><i class="fas fa-exclamation-triangle"></i> 載入失敗，請重新整理頁面</div>';
					}
				});
		};
		
		// HTML轉義函數
		function escapeHtml(text) {
			if (!text) return '';
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}
		
		// 點擊其他地方隱藏搜尋結果
		document.addEventListener('click', function(e) {
			if (!e.target.closest('.modern-search-container-contact')) {
				const resultsDiv = document.getElementById('contactSchoolResults');
				if (resultsDiv && resultsDiv.classList.contains('show')) {
					resultsDiv.classList.remove('show');
					// 當下拉選單隱藏時，驗證輸入
					setTimeout(validateContactSchoolInput, 100);
				}
			}
		});
		
		// 表單提交驗證
		const addContactForm = document.getElementById('addContactForm');
		if (addContactForm) {
			addContactForm.addEventListener('submit', function(e) {
				const schoolCodeInput = document.getElementById('contact_school_code');
				const schoolInput = document.getElementById('contact_school_search');
				
				if (!schoolCodeInput || !schoolCodeInput.value.trim()) {
					e.preventDefault();
					showContactSchoolError('請從系統提供的選項中選擇學校，不能自行輸入');
					if (schoolInput) {
						schoolInput.focus();
					}
					return false;
				}
				
				// 驗證格式
				validateContactSchoolInput();
				const schoolCode = schoolCodeInput.value.trim();
				if (!schoolCode) {
					e.preventDefault();
					showContactSchoolError('請從系統提供的選項中選擇學校，不能自行輸入');
					if (schoolInput) {
						schoolInput.focus();
					}
					return false;
				}
			});
		}
		
		// 初始化學校搜尋功能
		initializeContactSchoolSearch();
	});
	</script>
</body>
</html>