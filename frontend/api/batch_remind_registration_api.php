<?php
/**
 * 批量提醒報名 API：將多個學生的 remind_registration_done 設為 1
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

$enrollment_ids_json = isset($_POST['enrollment_ids']) ? $_POST['enrollment_ids'] : '';
$enrollment_ids = json_decode($enrollment_ids_json, true);

if (!is_array($enrollment_ids) || empty($enrollment_ids)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '缺少 enrollment_ids 或格式錯誤']);
    exit;
}

// 驗證所有 ID 都是整數
$enrollment_ids = array_filter(array_map('intval', $enrollment_ids));
if (empty($enrollment_ids)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '無效的學生 ID']);
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

    // 獲取用戶的科系（如果是主任）
    $user_department = null;
    if ($isDirector) {
        $table_check = $conn->query("SHOW TABLES LIKE 'director'");
        if ($table_check && $table_check->num_rows > 0) {
            $st = $conn->prepare("SELECT department FROM director WHERE user_id = ?");
            $st->bind_param('i', $user_id);
            $st->execute();
            $res = $st->get_result();
            if ($r = $res->fetch_assoc()) $user_department = trim($r['department'] ?? '');
        }
        if (empty($user_department)) {
            $st = $conn->prepare("SELECT department FROM teacher WHERE user_id = ?");
            $st->bind_param('i', $user_id);
            $st->execute();
            $res = $st->get_result();
            if ($r = $res->fetch_assoc()) $user_department = trim($r['department'] ?? '');
        }
    }

    // 構建 IN 子句的佔位符
    $placeholders = str_repeat('?,', count($enrollment_ids) - 1) . '?';
    
    // 查詢所有學生資料（包含 email / 既有提醒狀態）
    $sql = "SELECT id, name, email, assigned_teacher_id, assigned_department, remind_registration_done, remind_registration_date FROM enrollment_intention WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $types = str_repeat('i', count($enrollment_ids));
    $stmt->bind_param($types, ...$enrollment_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $allowed_ids = [];
    $allowed_students = []; // 儲存允許的學生資料（包含 email 和 name）
    $success_count = 0;
    $email_sent_count = 0;
    $email_failed_count = 0;
    $email_skipped_no_email = 0;
    $skipped_already_reminded = 0;
    $sent_ids = [];
    
    while ($row = $result->fetch_assoc()) {
        $allowed = false;
        
        if ($isDirector) {
            $assigned_dept = trim($row['assigned_department'] ?? '');
            $assigned_teacher_id = $row['assigned_teacher_id'];
            if (!empty($user_department) && strtoupper($assigned_dept) === strtoupper($user_department) && 
                ($assigned_teacher_id == $user_id || $assigned_teacher_id === null)) {
                $allowed = true;
            }
        } else {
            if ((int)$row['assigned_teacher_id'] === $user_id) {
                $allowed = true;
            }
        }
        
        if ($allowed) {
            // 已提醒過就不再寄，避免重複通知
            if ((int)($row['remind_registration_done'] ?? 0) === 1) {
                $skipped_already_reminded++;
                continue;
            }

            $allowed_ids[] = (int)$row['id'];
            $allowed_students[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'] ?? '',
                'email' => trim($row['email'] ?? '')
            ];
        }
    }
    
    if (empty($allowed_ids)) {
        $msg = '沒有可操作的學生（僅能對分配給您的學生操作）';
        if ($skipped_already_reminded > 0) {
            $msg .= "；另有 $skipped_already_reminded 位學生已提醒過";
        }
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }

    // 先發送郵件，成功才寫入「已提醒」註記
    foreach ($allowed_students as $student) {
        if (empty($student['email'])) {
            $email_skipped_no_email++;
            continue;
        }

        $student_name = $student['name'] ?: '同學';
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

        if (sendEmail($student['email'], $subject, $body, $altBody)) {
            $email_sent_count++;
            $sent_ids[] = (int)$student['id'];
        } else {
            $email_failed_count++;
            error_log("批量提醒報名：發送郵件失敗 - student_id={$student['id']}, email={$student['email']}");
        }
    }

    if (!empty($sent_ids)) {
        // 批量更新（僅標記已成功寄出者）
        $sent_placeholders = str_repeat('?,', count($sent_ids) - 1) . '?';
        $update_sql = "UPDATE enrollment_intention SET remind_registration_done = 1, remind_registration_date = CURDATE() WHERE id IN ($sent_placeholders)";
        $update_stmt = $conn->prepare($update_sql);
        $update_types = str_repeat('i', count($sent_ids));
        $update_stmt->bind_param($update_types, ...$sent_ids);

        if ($update_stmt->execute()) {
            $success_count = $update_stmt->affected_rows;
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '郵件已嘗試寄出，但寫入提醒註記失敗']);
            exit;
        }
    }

    $message = "已發送 $email_sent_count 封郵件";
    if ($success_count > 0) $message .= "，並已註記 $success_count 位學生為「已提醒」";
    if ($email_skipped_no_email > 0) $message .= "；$email_skipped_no_email 位學生無 Email 未寄送";
    if ($email_failed_count > 0) $message .= "；$email_failed_count 封郵件發送失敗";
    if ($skipped_already_reminded > 0) $message .= "；$skipped_already_reminded 位學生已提醒過已跳過";

    echo json_encode([
        'success' => true,
        'message' => $message,
        'success_count' => $success_count,
        'email_sent_count' => $email_sent_count,
        'email_failed_count' => $email_failed_count,
        'email_skipped_no_email' => $email_skipped_no_email,
        'skipped_already_reminded' => $skipped_already_reminded,
        'total_count' => count($enrollment_ids)
    ]);
    
    $stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
    $conn->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '系統錯誤', 'error' => $e->getMessage()]);
}
