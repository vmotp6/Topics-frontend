<?php
/**
 * 已叮嚀報名 API：將學生的 remind_registration_done 設為 1
 * 權限：老師僅能對分配給自己的學生操作；主任僅能對自己科系且自行聯絡的學生操作
 */
session_name('KANGNING_SESSION');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config.php';
require_once '../includes/email_functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '僅接受 POST']);
    exit;
}

$isLoggedIn = (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) ||
              (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true);
$user_role = $_SESSION['role'] ?? '';
$user_id = (int)($_SESSION['user_id'] ?? 0);

// 招生中心不能操作
if (in_array($user_role, ['ADM', 'STA'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '招生中心不能執行此操作']);
    exit;
}

$isDirector = ($user_role === 'DI');
$isTeacher = in_array($user_role, ['TEA', '老師', 'STA', 'AA', '學校行政人員']);

if (!$isLoggedIn || (!$isTeacher && !$isDirector)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '權限不足']);
    exit;
}

$enrollment_id = isset($_POST['enrollment_id']) ? (int)$_POST['enrollment_id'] : 0;
if ($enrollment_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '缺少 enrollment_id']);
    exit;
}

try {
    $conn = getDatabaseConnection();

    // 確保提醒欄位存在
    $r = @$conn->query("SHOW COLUMNS FROM enrollment_intention LIKE 'remind_registration_done'");
    if (!$r || $r->num_rows === 0) {
        @$conn->query("ALTER TABLE enrollment_intention ADD COLUMN remind_registration_done TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=否,1=是'");
    }
    $r2 = @$conn->query("SHOW COLUMNS FROM enrollment_intention LIKE 'remind_registration_date'");
    if (!$r2 || $r2->num_rows === 0) {
        @$conn->query("ALTER TABLE enrollment_intention ADD COLUMN remind_registration_date DATE DEFAULT NULL");
    }

    $ei = $conn->query("SELECT id, name, email, assigned_teacher_id, assigned_department, follow_up_status, remind_registration_done, remind_registration_date FROM enrollment_intention WHERE id = " . (int)$enrollment_id);
    $row = $ei ? $ei->fetch_assoc() : null;
    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => '找不到該學生']);
        exit;
    }

    // 已提醒過：直接回傳訊息（避免重複寄信）
    if ((int)($row['remind_registration_done'] ?? 0) === 1) {
        $d = $row['remind_registration_date'] ?? '';
        $msg = '此學生已提醒過';
        if (!empty($d)) $msg .= "（日期：$d）";
        echo json_encode(['success' => true, 'message' => $msg, 'already_reminded' => true]);
        exit;
    }

    $allowed = false;
    if ($isDirector) {
        $table_check = $conn->query("SHOW TABLES LIKE 'director'");
        $dept = null;
        if ($table_check && $table_check->num_rows > 0) {
            $st = $conn->prepare("SELECT department FROM director WHERE user_id = ?");
            $st->bind_param('i', $user_id);
            $st->execute();
            $res = $st->get_result();
            if ($r = $res->fetch_assoc()) $dept = trim($r['department'] ?? '');
        }
        if (empty($dept)) {
            $st = $conn->prepare("SELECT department FROM teacher WHERE user_id = ?");
            $st->bind_param('i', $user_id);
            $st->execute();
            $res = $st->get_result();
            if ($r = $res->fetch_assoc()) $dept = trim($r['department'] ?? '');
        }
        $assigned_dept = trim($row['assigned_department'] ?? '');
        $assigned_teacher_id = $row['assigned_teacher_id'];
        if (!empty($dept) && strtoupper($assigned_dept) === strtoupper($dept) && ($assigned_teacher_id == $user_id || $assigned_teacher_id === null)) {
            $allowed = true;
        }
    } else {
        if ((int)$row['assigned_teacher_id'] === $user_id) {
            $allowed = true;
        }
    }

    if (!$allowed) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => '僅能對分配給您的學生操作']);
        exit;
    }

    $email = trim((string)($row['email'] ?? ''));
    if ($email === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '此學生未填寫 Email，無法發送 Gmail 通知']);
        exit;
    }

    $student_name = ($row['name'] ?? '') ?: '同學';
    $subject = '康寧大學五專入學報名提醒';
    $body = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, 'Microsoft JhengHei', sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>📧 入學報名提醒</h1>
            </div>
            <div class='content'>
                <p>親愛的 <strong>{$student_name}</strong>，您好！</p>
                <p>提醒您，康寧大學五專入學報名即將開始，請記得完成報名手續。</p>
                <p>如有任何問題，歡迎與我們聯絡。</p>
                <div style='text-align: center;'>
                    <span class='button' style='color: white; text-decoration: none;'>請依學校通知方式完成報名</span>
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
    $altBody = "親愛的 {$student_name}，您好！\n\n提醒您，康寧大學五專入學報名即將開始，請記得完成報名手續。\n\n如有任何問題，歡迎與我們聯絡。\n\n康寧大學招生組";

    if (!sendEmail($email, $subject, $body, $altBody)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '郵件發送失敗，請檢查 SMTP 設定']);
        exit;
    }

    $up = $conn->prepare("UPDATE enrollment_intention SET remind_registration_done = 1, remind_registration_date = CURDATE() WHERE id = ?");
    $up->bind_param('i', $enrollment_id);
    if (!$up->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '已寄出郵件，但寫入提醒註記失敗']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => '已寄出提醒郵件，並註記為已提醒']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '系統錯誤', 'error' => $e->getMessage()]);
}
