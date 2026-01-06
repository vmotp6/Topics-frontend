<?php
// API：新增學生聯絡資訊
header('Content-Type: application/json; charset=utf-8');

require_once '../session_config.php';
require_once '../config.php';

$allowed_roles = ['老師', 'TEA', 'STA', '學校行政人員', 'AA', 'DI', 'IM'];
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true &&
              isset($_SESSION['role']) && in_array($_SESSION['role'], $allowed_roles, true);

if (!$isLoggedIn) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '未登入或權限不足']);
    exit;
}

function ensureStudentContactTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS student_contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        junior_high VARCHAR(150) DEFAULT NULL,
        current_grade VARCHAR(50) DEFAULT NULL,
        interest_department VARCHAR(150) DEFAULT NULL,
        activity_source VARCHAR(150) DEFAULT NULL,
        contact_teacher VARCHAR(150) DEFAULT NULL,
        status VARCHAR(100) DEFAULT NULL,
        contact_method VARCHAR(50) DEFAULT NULL,
        contact_method_value VARCHAR(255) DEFAULT NULL,
        contact_note VARCHAR(255) DEFAULT NULL,
        contact_date DATE DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_by_username VARCHAR(150) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_name (name),
        INDEX idx_junior_high (junior_high),
        INDEX idx_current_grade (current_grade),
        INDEX idx_interest_department (interest_department),
        INDEX idx_status (status),
        INDEX idx_created_by (created_by),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new Exception('請以 JSON 傳送資料');
    }

    $name = trim($input['name'] ?? '');
    $junior_high = trim($input['junior_high'] ?? '');
    $current_grade = trim($input['current_grade'] ?? '');
    $interest_department = trim($input['interest_department'] ?? '');
    $activity_source = trim($input['activity_source'] ?? '');
    $contact_teacher = trim($input['contact_teacher'] ?? '');
    $status = trim($input['status'] ?? '');
    $contact_method = trim($input['contact_method'] ?? '');
    $contact_method_value = trim($input['contact_method_value'] ?? '');
    $contact_note = trim($input['contact_note'] ?? '');
    $contact_date = trim($input['contact_date'] ?? '');

    if ($name === '') {
        throw new Exception('姓名為必填');
    }

    $conn = getDatabaseConnection();
    ensureStudentContactTable($conn);

    // 確保欄位存在（向後兼容）
    $conn->query("ALTER TABLE student_contacts ADD COLUMN IF NOT EXISTS current_grade VARCHAR(50) DEFAULT NULL");
    $conn->query("ALTER TABLE student_contacts ADD COLUMN IF NOT EXISTS contact_method VARCHAR(50) DEFAULT NULL");
    $conn->query("ALTER TABLE student_contacts ADD COLUMN IF NOT EXISTS contact_method_value VARCHAR(255) DEFAULT NULL");
    $conn->query("ALTER TABLE student_contacts ADD COLUMN IF NOT EXISTS contact_note VARCHAR(255) DEFAULT NULL");
    $conn->query("ALTER TABLE student_contacts ADD COLUMN IF NOT EXISTS contact_date DATE DEFAULT NULL");
    $conn->query("ALTER TABLE student_contacts ADD COLUMN IF NOT EXISTS created_by INT DEFAULT NULL");
    $conn->query("ALTER TABLE student_contacts ADD COLUMN IF NOT EXISTS created_by_username VARCHAR(150) DEFAULT NULL");

    $created_by = null;
    $created_by_username = $_SESSION['username'] ?? null;
    if ($created_by_username) {
        $stmtUser = $conn->prepare("SELECT id FROM user WHERE username = ? LIMIT 1");
        if ($stmtUser) {
            $stmtUser->bind_param("s", $created_by_username);
            if ($stmtUser->execute()) {
                $stmtUser->bind_result($uid);
                if ($stmtUser->fetch()) {
                    $created_by = $uid;
                }
            }
            $stmtUser->close();
        }
    }

    $stmt = $conn->prepare("INSERT INTO student_contacts (name, junior_high, current_grade, interest_department, activity_source, contact_teacher, status, contact_method, contact_method_value, contact_note, contact_date, created_by, created_by_username) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssssis", $name, $junior_high, $current_grade, $interest_department, $activity_source, $contact_teacher, $status, $contact_method, $contact_method_value, $contact_note, $contact_date, $created_by, $created_by_username);
    if (!$stmt->execute()) {
        throw new Exception('寫入資料庫失敗，請稍後再試');
    }
    $stmt->close();
    $conn->close();

    echo json_encode(['success' => true, 'message' => '新增成功']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

