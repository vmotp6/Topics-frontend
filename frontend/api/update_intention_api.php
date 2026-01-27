<?php
/**
 * 更新學生意願 API
 */
session_name('KANGNING_SESSION');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config.php';

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

if (!$isLoggedIn || !in_array($user_role, ['TEA', 'DI', '老師', '主任'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '權限不足']);
    exit;
}

$enrollment_id = isset($_POST['enrollment_id']) ? (int)$_POST['enrollment_id'] : 0;
$intention_level = isset($_POST['intention_level']) ? trim((string)$_POST['intention_level']) : '';
$decline_reason = isset($_POST['decline_reason']) ? trim((string)$_POST['decline_reason']) : '';
$decline_reason_other = isset($_POST['decline_reason_other']) ? trim((string)$_POST['decline_reason_other']) : '';
$need_follow_up = isset($_POST['need_follow_up']) ? trim((string)$_POST['need_follow_up']) : '';

if ($enrollment_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '缺少 enrollment_id']);
    exit;
}

if (!in_array($intention_level, ['high', 'medium', 'low', 'none'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '無效的意願等級']);
    exit;
}

// 如果選擇「無意願」，必須填寫「不來原因」
if ($intention_level === 'none' && empty($decline_reason)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '選擇「無意願」時，必須填寫「不來原因」']);
    exit;
}

try {
    $conn = getDatabaseConnection();

    // 檢查權限：僅能更新分配給自己的學生
    $isDirector = ($user_role === 'DI' || $user_role === '主任');
    if ($isDirector) {
        // 主任：檢查是否為自己科系的學生
        $table_check = $conn->query("SHOW TABLES LIKE 'director'");
        $has_director_table = $table_check && $table_check->num_rows > 0;
        if ($has_director_table) {
            $dept_stmt = $conn->prepare("SELECT department FROM director WHERE user_id = ?");
        } else {
            $dept_stmt = $conn->prepare("SELECT department FROM teacher WHERE user_id = ?");
        }
        $dept_stmt->bind_param('i', $user_id);
        $dept_stmt->execute();
        $dept_res = $dept_stmt->get_result();
        $dept_row = $dept_res->fetch_assoc();
        $director_department = $dept_row ? trim($dept_row['department'] ?? '') : '';
        
        $check_stmt = $conn->prepare("SELECT assigned_department FROM enrollment_intention WHERE id = ?");
        $check_stmt->bind_param('i', $enrollment_id);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result();
        $enrollment = $check_res->fetch_assoc();
        if (!$enrollment || strtoupper(trim($enrollment['assigned_department'] ?? '')) !== strtoupper($director_department)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '僅能更新自己科系的學生']);
            exit;
        }
    } else {
        // 老師：必須 assigned_teacher_id = 自己的ID
        $check_stmt = $conn->prepare("SELECT 1 FROM enrollment_intention WHERE id = ? AND assigned_teacher_id = ?");
        $check_stmt->bind_param('ii', $enrollment_id, $user_id);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result();
        if ($check_res->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '僅能更新分配給您的學生']);
            exit;
        }
    }

    // 確保 decline_reason_final 欄位存在
    $col_check = $conn->query("SHOW COLUMNS FROM enrollment_intention LIKE 'decline_reason_final'");
    $has_decline_reason_final = $col_check && $col_check->num_rows > 0;
    
    // 準備「不來原因」字串（如果有選擇「其他」，則合併其他說明）
    $decline_reason_final = '';
    if ($intention_level === 'none' && !empty($decline_reason)) {
        $decline_reason_final = $decline_reason;
        if ($decline_reason === 'other' && !empty($decline_reason_other)) {
            $decline_reason_final = 'other:' . $decline_reason_other;
        }
    }
    
    // 更新意願和追蹤狀態
    if ($intention_level === 'none') {
        // 無意願：根據 need_follow_up 決定追蹤狀態
        if ($need_follow_up === 'no') {
            // 不要（結案）：更新為結案狀態
            if ($has_decline_reason_final && !empty($decline_reason_final)) {
                $up = $conn->prepare("UPDATE enrollment_intention SET intention_level = ?, follow_up_status = 'closed_declined', case_closed = 1, decline_reason_final = ? WHERE id = ?");
                $up->bind_param('ssi', $intention_level, $decline_reason_final, $enrollment_id);
            } else {
                $up = $conn->prepare("UPDATE enrollment_intention SET intention_level = ?, follow_up_status = 'closed_declined', case_closed = 1 WHERE id = ?");
                $up->bind_param('si', $intention_level, $enrollment_id);
            }
        } else {
            // 要或之後再說：更新為追蹤狀態
            if ($has_decline_reason_final && !empty($decline_reason_final)) {
                $up = $conn->prepare("UPDATE enrollment_intention SET intention_level = ?, follow_up_status = 'decline_follow_up', decline_reason_final = ? WHERE id = ?");
                $up->bind_param('ssi', $intention_level, $decline_reason_final, $enrollment_id);
            } else {
                $up = $conn->prepare("UPDATE enrollment_intention SET intention_level = ?, follow_up_status = 'decline_follow_up' WHERE id = ?");
                $up->bind_param('si', $intention_level, $enrollment_id);
            }
        }
    } else {
        // 有意願：更新為追蹤狀態
        $up = $conn->prepare("UPDATE enrollment_intention SET intention_level = ?, follow_up_status = 'tracking' WHERE id = ?");
        $up->bind_param('si', $intention_level, $enrollment_id);
    }
    
    if (!$up->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '更新失敗']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => '意願已更新']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '系統錯誤', 'error' => $e->getMessage()]);
}
