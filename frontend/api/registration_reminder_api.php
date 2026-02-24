<?php
// 報名提醒 API：處理「已提醒」和「已報名」狀態更新
session_name('KANGNING_SESSION');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config.php';

// 檢查登入
if (empty($_SESSION['user_id']) && !empty($_SESSION['username'])) {
    try {
        $conn_temp = getDatabaseConnection();
        $user_stmt = $conn_temp->prepare("SELECT id FROM user WHERE username = ? LIMIT 1");
        $user_stmt->bind_param("s", $_SESSION['username']);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        if ($user_row = $user_result->fetch_assoc()) {
            $_SESSION['user_id'] = (int)$user_row['id'];
        }
        $user_stmt->close();
        $conn_temp->close();
    } catch (Exception $e) {
        error_log('registration_reminder_api.php: 無法從 username 查找 user_id: ' . $e->getMessage());
    }
}

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '未登入']);
    exit;
}

// 確保報名提醒相關欄位存在
function ensureRegistrationColumns($conn) {
    $cols = [
        'registration_stage' => "VARCHAR(20) DEFAULT NULL COMMENT 'full_exempt/priority_exam/joint_exam/continued_recruitment 當前報名階段'",
        'full_exempt_reminded' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '完全免試是否已提醒'",
        'full_exempt_registered' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '完全免試是否已報名'",
        'full_exempt_declined' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '完全免試本階段不報'",
        'full_exempt_decline_reason' => "TEXT DEFAULT NULL COMMENT '完全免試本階段不報原因'",
        'priority_exam_reminded' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '優先免試是否已提醒'",
        'priority_exam_registered' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '優先免試是否已報名'",
        'priority_exam_declined' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '優先免試本階段不報'",
        'joint_exam_reminded' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '聯合免試是否已提醒'",
        'joint_exam_registered' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '聯合免試是否已報名'",
        'joint_exam_declined' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '聯合免試本階段不報'",
        'continued_recruitment_reminded' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '續招是否已提醒'",
        'continued_recruitment_registered' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '續招是否已報名'",
        'continued_recruitment_declined' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '續招本階段不報'",
        'priority_exam_decline_reason' => "TEXT DEFAULT NULL COMMENT '優先免試本階段不報原因'",
        'joint_exam_decline_reason' => "TEXT DEFAULT NULL COMMENT '聯合免試本階段不報原因'",
        'continued_recruitment_decline_reason' => "TEXT DEFAULT NULL COMMENT '續招本階段不報原因'",
        'is_registered' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否已報名（任一階段）'",
        'check_in_status' => "VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '報到流程: pending=待報到, reminded=已提醒報到, completed=已完成報到, declined=放棄報到'",
        'check_in_decline_reason' => "TEXT DEFAULT NULL COMMENT '放棄報到原因'"
    ];
    foreach ($cols as $name => $def) {
        $r = @$conn->query("SHOW COLUMNS FROM enrollment_intention LIKE '$name'");
        if (!$r || $r->num_rows === 0) {
            @$conn->query("ALTER TABLE enrollment_intention ADD COLUMN $name $def");
        }
    }
}

/**
 * 從 department_quotas 取得續招報名時間區間
 */
function getContinuedRecruitmentTimeRange($conn) {
    $sql = "SELECT MIN(register_start) AS min_start, MAX(register_end) AS max_end 
            FROM department_quotas 
            WHERE is_active = 1 AND register_start IS NOT NULL AND register_end IS NOT NULL";
    $result = @$conn->query($sql);
    if (!$result || $result->num_rows === 0) {
        return null;
    }
    $row = $result->fetch_assoc();
    if (empty($row['min_start']) || empty($row['max_end'])) {
        return null;
    }
    return ['start' => $row['min_start'], 'end' => $row['max_end']];
}

/**
 * 判斷當前報名階段
 * 完全免試(4月)/優先免試(5月)/聯合免試(6-7月)依月份；續招依「科系名額管理」設定的報名時間區間。
 */
function getCurrentRegistrationStage($conn) {
    $now = time();
    $current_month = (int)date('m');
    $continued_range = getContinuedRecruitmentTimeRange($conn);
    if ($continued_range) {
        $start_ts = strtotime($continued_range['start']);
        $end_ts = strtotime($continued_range['end']);
        if ($start_ts !== false && $end_ts !== false && $now >= $start_ts && $now <= $end_ts) {
            return 'continued_recruitment';
        }
    }
    if ($current_month >= 4 && $current_month < 5) {
        return 'full_exempt'; // 4月：完全免試
    }
    if ($current_month >= 5 && $current_month < 6) {
        return 'priority_exam';
    }
    if ($current_month >= 6 && $current_month < 8) {
        return 'joint_exam';
    }
    return null;
}

try {
    $conn = getDatabaseConnection();
    ensureRegistrationColumns($conn);
    
    // 角色與權限檢查
    $user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    // 招生中心/行政人員角色（不得進行報名提醒）
    $admissionCenterRoles = ['ADM', 'STA', 'STAM', '行政人員', '招生中心組員'];
    $isAdmissionCenter = in_array($user_role, $admissionCenterRoles, true);
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    if ($method === 'POST' && $action === 'remind') {
        // 標記為「已提醒」
        $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);
        if ($enrollment_id <= 0) {
            echo json_encode(['success' => false, 'message' => '無效的學生ID']);
            exit;
        }
        if ($isAdmissionCenter) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '招生中心不可進行報名提醒']);
            exit;
        }
        
        $stage = getCurrentRegistrationStage($conn);
        if (!$stage) {
            echo json_encode(['success' => false, 'message' => '目前非報名期間']);
            exit;
        }
        
        // 僅允許老師/主任對自己名單的學生操作
        $check = $conn->prepare("SELECT assigned_teacher_id FROM enrollment_intention WHERE id = ?");
        $check->bind_param("i", $enrollment_id);
        $check->execute();
        $res = $check->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $check->close();
        $assigned_teacher_id = $row ? (int)$row['assigned_teacher_id'] : 0;
        
        $canOperate = false;
        if ($user_role === 'TEA' && $assigned_teacher_id === $user_id) {
            $canOperate = true;
        } elseif ($user_role === 'DI' && $assigned_teacher_id === $user_id) {
            $canOperate = true;
        }
        if (!$canOperate) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '僅能對自己名單的學生進行報名提醒']);
            exit;
        }
        
        $reminded_col = $stage . '_reminded';
        $stmt = $conn->prepare("UPDATE enrollment_intention SET $reminded_col = 1, registration_stage = ? WHERE id = ?");
        $stmt->bind_param("si", $stage, $enrollment_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => '已標記為已提醒']);
        } else {
            echo json_encode(['success' => false, 'message' => '更新失敗：' . $stmt->error]);
        }
        $stmt->close();
        
    } elseif ($method === 'POST' && $action === 'register') {
        // 標記為「已報名」
        $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);
        if ($enrollment_id <= 0) {
            echo json_encode(['success' => false, 'message' => '無效的學生ID']);
            exit;
        }
        if ($isAdmissionCenter) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '招生中心不可變更報名狀態']);
            exit;
        }
        
        $stage = getCurrentRegistrationStage($conn);
        if (!$stage) {
            echo json_encode(['success' => false, 'message' => '目前非報名期間']);
            exit;
        }
        
        // 僅允許老師/主任對自己名單的學生操作
        $check = $conn->prepare("SELECT assigned_teacher_id FROM enrollment_intention WHERE id = ?");
        $check->bind_param("i", $enrollment_id);
        $check->execute();
        $res = $check->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $check->close();
        $assigned_teacher_id = $row ? (int)$row['assigned_teacher_id'] : 0;
        
        $canOperate = false;
        if ($user_role === 'TEA' && $assigned_teacher_id === $user_id) {
            $canOperate = true;
        } elseif ($user_role === 'DI' && $assigned_teacher_id === $user_id) {
            $canOperate = true;
        }
        if (!$canOperate) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '僅能對自己名單的學生變更報名狀態']);
            exit;
        }
        
        $registered_col = $stage . '_registered';
        // 同時標記該階段已報名、整體已報名狀態，並啟動報到流程（待報到）
        $stmt = $conn->prepare("UPDATE enrollment_intention SET $registered_col = 1, is_registered = 1, check_in_status = 'pending' WHERE id = ?");
        $stmt->bind_param("i", $enrollment_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => '已標記為已報名']);
        } else {
            echo json_encode(['success' => false, 'message' => '更新失敗：' . $stmt->error]);
        }
        $stmt->close();
        
    } elseif ($method === 'POST' && $action === 'decline_stage') {
        // 本階段不報：招生流程狀態回復為「持續聯絡追蹤」，學生仍留當年度招生名單，下一階段可再提醒
        $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);
        if ($enrollment_id <= 0) {
            echo json_encode(['success' => false, 'message' => '無效的學生ID']);
            exit;
        }
        if ($isAdmissionCenter) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '招生中心不可變更報名狀態']);
            exit;
        }

        // 優先使用前端傳入的階段（與名單頁顯示一致），避免名單有顯示階段但 API 依伺服器時間判定為非報名期間
        $valid_stages = ['full_exempt', 'priority_exam', 'joint_exam', 'continued_recruitment'];
        $stage = null;
        if (!empty($_POST['stage']) && in_array($_POST['stage'], $valid_stages, true)) {
            $stage = $_POST['stage'];
        }
        if (!$stage) {
            $stage = getCurrentRegistrationStage($conn);
        }
        if (!$stage) {
            echo json_encode(['success' => false, 'message' => '目前非報名期間']);
            exit;
        }
        
        $check = $conn->prepare("SELECT assigned_teacher_id FROM enrollment_intention WHERE id = ?");
        $check->bind_param("i", $enrollment_id);
        $check->execute();
        $res = $check->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $check->close();
        $assigned_teacher_id = $row ? (int)$row['assigned_teacher_id'] : 0;
        
        $canOperate = false;
        if ($user_role === 'TEA' && $assigned_teacher_id === $user_id) {
            $canOperate = true;
        } elseif ($user_role === 'DI' && $assigned_teacher_id === $user_id) {
            $canOperate = true;
        }
        if (!$canOperate) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '僅能對自己名單的學生操作']);
            exit;
        }
        
        $declined_col = $stage . '_declined';
        $reason_col = $stage . '_decline_reason';
        $r = @$conn->query("SHOW COLUMNS FROM enrollment_intention LIKE '$declined_col'");
        if (!$r || $r->num_rows === 0) {
            @$conn->query("ALTER TABLE enrollment_intention ADD COLUMN `$declined_col` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '本階段不報'");
        }
        $r2 = @$conn->query("SHOW COLUMNS FROM enrollment_intention LIKE '$reason_col'");
        if (!$r2 || $r2->num_rows === 0) {
            @$conn->query("ALTER TABLE enrollment_intention ADD COLUMN `$reason_col` TEXT DEFAULT NULL COMMENT '本階段不報原因'");
        }

        $decline_reason = isset($_POST['decline_reason']) ? trim((string)$_POST['decline_reason']) : '';
        if ($decline_reason !== '') {
            $stmt = $conn->prepare("UPDATE enrollment_intention SET `$declined_col` = 1, `$reason_col` = ? WHERE id = ?");
            $stmt->bind_param("si", $decline_reason, $enrollment_id);
        } else {
            $stmt = $conn->prepare("UPDATE enrollment_intention SET `$declined_col` = 1 WHERE id = ?");
            $stmt->bind_param("i", $enrollment_id);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => '已記錄本階段不報，學生將於下一招生階段可再次提醒報名']);
        } else {
            echo json_encode(['success' => false, 'message' => '更新失敗：' . $stmt->error]);
        }
        $stmt->close();
        
    } elseif ($method === 'POST' && in_array($action, ['check_in_remind', 'check_in_complete', 'check_in_decline'], true)) {
        // 報到流程：已提醒報到、已完成報到、放棄報到（僅影響報到流程，不回到招生追蹤）
        $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);
        if ($enrollment_id <= 0) {
            echo json_encode(['success' => false, 'message' => '無效的學生ID']);
            exit;
        }
        
        $status_map = [
            'check_in_remind' => 'reminded',
            'check_in_complete' => 'completed',
            'check_in_decline' => 'declined'
        ];
        $new_status = $status_map[$action];
        
        // 僅允許老師/主任對自己名單的學生操作；招生中心也可操作報到狀態
        $check = $conn->prepare("SELECT assigned_teacher_id, is_registered FROM enrollment_intention WHERE id = ?");
        $check->bind_param("i", $enrollment_id);
        $check->execute();
        $res = $check->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $check->close();
        $assigned_teacher_id = $row ? (int)$row['assigned_teacher_id'] : 0;
        $is_reg = (int)($row['is_registered'] ?? 0) === 1;
        
        if (!$is_reg) {
            echo json_encode(['success' => false, 'message' => '該學生尚未標記為已報名']);
            exit;
        }
        
        $canOperate = false;
        if ($user_role === 'TEA' && $assigned_teacher_id === $user_id) {
            $canOperate = true;
        } elseif ($user_role === 'DI' && $assigned_teacher_id === $user_id) {
            $canOperate = true;
        } elseif ($isAdmissionCenter) {
            $canOperate = true;
        }
        if (!$canOperate) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '僅能對自己名單的學生或由招生中心操作報到狀態']);
            exit;
        }
        
        $r = @$conn->query("SHOW COLUMNS FROM enrollment_intention LIKE 'check_in_status'");
        if (!$r || $r->num_rows === 0) {
            @$conn->query("ALTER TABLE enrollment_intention ADD COLUMN check_in_status VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '報到流程'");
        }
        $r2 = @$conn->query("SHOW COLUMNS FROM enrollment_intention LIKE 'check_in_decline_reason'");
        if (!$r2 || $r2->num_rows === 0) {
            @$conn->query("ALTER TABLE enrollment_intention ADD COLUMN check_in_decline_reason TEXT DEFAULT NULL COMMENT '放棄報到原因'");
        }

        $check_in_decline_reason = '';
        if ($action === 'check_in_decline') {
            $check_in_decline_reason = isset($_POST['check_in_decline_reason']) ? trim((string)$_POST['check_in_decline_reason']) : '';
        }
        if ($new_status === 'declined') {
            $stmt = $conn->prepare("UPDATE enrollment_intention SET check_in_status = ?, check_in_decline_reason = ? WHERE id = ?");
            $stmt->bind_param("ssi", $new_status, $check_in_decline_reason, $enrollment_id);
        } else {
            $stmt = $conn->prepare("UPDATE enrollment_intention SET check_in_status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $enrollment_id);
        }
        
        $msg = [
            'reminded' => '已標記為已提醒報到',
            'completed' => '已標記為已完成報到',
            'declined' => '已標記為放棄報到（僅影響報到流程）'
        ];
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => $msg[$new_status]]);
        } else {
            echo json_encode(['success' => false, 'message' => '更新失敗：' . $stmt->error]);
        }
        $stmt->close();
        
    } else {
        echo json_encode(['success' => false, 'message' => '無效的請求']);
    }
    
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '錯誤：' . $e->getMessage()]);
}
