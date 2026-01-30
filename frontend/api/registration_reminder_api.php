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
        'registration_stage' => "VARCHAR(20) DEFAULT NULL COMMENT 'priority_exam/joint_exam/continued_recruitment 當前報名階段'",
        'priority_exam_reminded' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '優先免試是否已提醒'",
        'priority_exam_registered' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '優先免試是否已報名'",
        'joint_exam_reminded' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '聯合免試是否已提醒'",
        'joint_exam_registered' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '聯合免試是否已報名'",
        'continued_recruitment_reminded' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '續招是否已提醒'",
        'continued_recruitment_registered' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '續招是否已報名'",
        'is_registered' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否已報名（任一階段）'"
    ];
    foreach ($cols as $name => $def) {
        $r = @$conn->query("SHOW COLUMNS FROM enrollment_intention LIKE '$name'");
        if (!$r || $r->num_rows === 0) {
            @$conn->query("ALTER TABLE enrollment_intention ADD COLUMN $name $def");
        }
    }
}

// 判斷當前報名階段
function getCurrentRegistrationStage() {
    $current_month = (int)date('m');
    if ($current_month >= 1 && $current_month < 2) {
        return 'priority_exam'; // 5月：優先免試
    } elseif ($current_month >= 6 && $current_month < 8) {
        return 'joint_exam'; // 6-7月：聯合免試
    } elseif ($current_month >= 8) {
        return 'continued_recruitment'; // 8月以後：續招
    }
    return null; // 非報名期間
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
        
        $stage = getCurrentRegistrationStage();
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
        
        $stage = getCurrentRegistrationStage();
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
        // 同時標記該階段已報名，以及整體已報名狀態
        $stmt = $conn->prepare("UPDATE enrollment_intention SET $registered_col = 1, is_registered = 1 WHERE id = ?");
        $stmt->bind_param("i", $enrollment_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => '已標記為已報名']);
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
