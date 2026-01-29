<?php
/**
 * 結案 API：主任可將學生結案（僅當最近 3 筆聯絡紀錄皆為「聯絡不到」時）
 * 結案後該學生會顯示在歷史紀錄。
 */
session_name('KANGNING_SESSION');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

// 僅接受 POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '僅接受 POST']);
    exit;
}

$isLoggedIn = (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) ||
              (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true);
$user_role = $_SESSION['role'] ?? '';
$user_id = (int)($_SESSION['user_id'] ?? 0);

if (!$isLoggedIn || $user_role !== 'DI') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '僅主任可執行結案']);
    exit;
}

$enrollment_id = isset($_POST['enrollment_id']) ? (int)$_POST['enrollment_id'] : 
                 (isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0);
if ($enrollment_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '缺少 enrollment_id']);
    exit;
}

try {
    $conn = getDatabaseConnection();

    // 確保 case_closed、follow_up_status 欄位存在
    $r = @$conn->query("SHOW COLUMNS FROM enrollment_intention LIKE 'case_closed'");
    if (!$r || $r->num_rows === 0) {
        @$conn->query("ALTER TABLE enrollment_intention ADD COLUMN case_closed TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=否,1=是'");
    }
    $fs = @$conn->query("SHOW COLUMNS FROM enrollment_intention LIKE 'follow_up_status'");
    if (!$fs || $fs->num_rows === 0) {
        @$conn->query("ALTER TABLE enrollment_intention ADD COLUMN follow_up_status VARCHAR(30) DEFAULT 'tracking' COMMENT 'tracking/decline_follow_up/closed_unreachable/closed_declined'");
    }

    // 主任科系
    $director_dept = null;
    $chk = $conn->query("SHOW TABLES LIKE 'director'");
    if ($chk && $chk->num_rows > 0) {
        $st = $conn->prepare("SELECT department FROM director WHERE user_id = ?");
        $st->bind_param('i', $user_id);
        $st->execute();
        $res = $st->get_result();
        if ($row = $res->fetch_assoc()) $director_dept = trim($row['department'] ?? '');
    }
    if (empty($director_dept)) {
        $st = $conn->prepare("SELECT department FROM teacher WHERE user_id = ?");
        $st->bind_param('i', $user_id);
        $st->execute();
        $res = $st->get_result();
        if ($row = $res->fetch_assoc()) $director_dept = trim($row['department'] ?? '');
    }

    if (empty($director_dept)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => '無法取得主任科系']);
        exit;
    }

    // 學生：必須為主任科系、未結案
    $st = $conn->prepare("SELECT id, assigned_department, case_closed FROM enrollment_intention WHERE id = ?");
    $st->bind_param('i', $enrollment_id);
    $st->execute();
    $res = $st->get_result();
    $ei = $res->fetch_assoc();
    if (!$ei) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => '找不到該學生']);
        exit;
    }
    if (strtoupper(trim($ei['assigned_department'] ?? '')) !== strtoupper($director_dept)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => '僅能結案自己科系的學生']);
        exit;
    }
    if ((int)($ei['case_closed'] ?? 0) === 1) {
        echo json_encode(['success' => true, 'message' => '該生已結案']);
        exit;
    }

    // 檢查最近 3 筆聯絡紀錄是否皆為「聯絡不到」
    $col = @$conn->query("SHOW COLUMNS FROM enrollment_contact_logs LIKE 'contact_result'");
    if (!$col || $col->num_rows === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '系統尚未支援聯絡結果，請先執行資料庫更新']);
        exit;
    }

    $st = $conn->prepare("
        SELECT contact_result FROM enrollment_contact_logs 
        WHERE enrollment_id = ? 
        ORDER BY contact_date DESC, id DESC 
        LIMIT 3
    ");
    $st->bind_param('i', $enrollment_id);
    $st->execute();
    $res = $st->get_result();
    $last3 = $res->fetch_all(MYSQLI_ASSOC);

    if (count($last3) < 3) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '需至少 3 筆聯絡紀錄且最近 3 筆皆為「聯絡不到」才能結案']);
        exit;
    }
    foreach ($last3 as $r) {
        if (($r['contact_result'] ?? '') !== 'unreachable') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '最近 3 筆聯絡紀錄須皆為「聯絡不到」才能結案']);
            exit;
        }
    }

    // 執行結案（聯絡不到），並設 follow_up_status=closed_unreachable
    $up = $conn->prepare("UPDATE enrollment_intention SET case_closed = 1, follow_up_status = 'closed_unreachable' WHERE id = ?");
    $up->bind_param('i', $enrollment_id);
    if (!$up->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '更新失敗']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => '已結案，該生將顯示於歷史紀錄']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '系統錯誤', 'error' => $e->getMessage()]);
}
