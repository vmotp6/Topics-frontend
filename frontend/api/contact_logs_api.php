<?php
// 載入 session 與資料庫設定
require_once '../session_config.php';
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

function ensureContactLogsTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS enrollment_contact_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        teacher_id INT NOT NULL,
        contact_date DATE NOT NULL,
        method VARCHAR(20) NOT NULL,
        result TEXT NOT NULL,
        follow_up_notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_student_id (student_id),
        INDEX idx_teacher_id (teacher_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
}

try {
    $conn = getDatabaseConnection();
    ensureContactLogsTable($conn);

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        // 僅老師可新增聯絡紀錄
        $isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['role']) && $_SESSION['role'] === '老師' && isset($_SESSION['username']) && !empty($_SESSION['username']);
        if (!$isLoggedIn) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '權限不足']);
            exit;
        }

        // 取得老師 user.id
        $username = $_SESSION['username'];
        $tstmt = $conn->prepare("SELECT u.id FROM user u WHERE u.username = ? AND u.role = '老師'");
        $tstmt->bind_param('s', $username);
        $tstmt->execute();
        $tres = $tstmt->get_result();
        $teacher = $tres->fetch_assoc();
        if (!$teacher) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '找不到老師']);
            exit;
        }
        $teacher_id = (int)$teacher['id'];

        // 讀取輸入
        $input = $_POST;
        if (empty($input)) {
            // 支援 JSON 請求
            $raw = file_get_contents('php://input');
            $json = json_decode($raw, true);
            if (is_array($json)) { $input = $json; }
        }

        $student_id = isset($input['student_id']) ? (int)$input['student_id'] : 0;
        $contact_date = isset($input['contact_date']) ? trim($input['contact_date']) : date('Y-m-d');
        $contact_method = isset($input['contact_method']) ? trim($input['contact_method']) : '';
        $contact_result = isset($input['contact_result']) ? trim($input['contact_result']) : '';
        $follow_up_notes = isset($input['follow_up_notes']) ? trim($input['follow_up_notes']) : null;

        if ($student_id <= 0 || $contact_method === '' || $contact_result === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '缺少必要欄位']);
            exit;
        }

        // 僅能對分配給自己的學生新增紀錄
        $astmt = $conn->prepare("SELECT 1 FROM enrollment_intention WHERE id = ? AND assigned_teacher_id = ?");
        $astmt->bind_param('ii', $student_id, $teacher_id);
        $astmt->execute();
        $ares = $astmt->get_result();
        if ($ares->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '僅能新增分配給您的學生']);
            exit;
        }

        // 寫入資料
        $stmt = $conn->prepare("INSERT INTO enrollment_contact_logs (student_id, teacher_id, contact_date, method, result, follow_up_notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('iissss', $student_id, $teacher_id, $contact_date, $contact_method, $contact_result, $follow_up_notes);
        $ok = $stmt->execute();

        if ($ok) {
            echo json_encode(['success' => true, 'id' => $conn->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '寫入失敗']);
        }
        exit;
    }

    if ($method === 'GET') {
        // 允許老師查詢自己學生的紀錄（選用）
        $isTeacher = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['role']) && $_SESSION['role'] === '老師' && isset($_SESSION['username']);
        if (!$isTeacher) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '權限不足']);
            exit;
        }

        $student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
        if ($student_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '缺少 student_id']);
            exit;
        }

        // 老師僅能看自己的學生
        $username = $_SESSION['username'];
        $tstmt = $conn->prepare("SELECT u.id FROM user u WHERE u.username = ? AND u.role = '老師'");
        $tstmt->bind_param('s', $username);
        $tstmt->execute();
        $tres = $tstmt->get_result();
        $teacher = $tres->fetch_assoc();
        $teacher_id = $teacher ? (int)$teacher['id'] : 0;

        $astmt = $conn->prepare("SELECT 1 FROM enrollment_intention WHERE id = ? AND assigned_teacher_id = ?");
        $astmt->bind_param('ii', $student_id, $teacher_id);
        $astmt->execute();
        $ares = $astmt->get_result();
        if ($ares->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '僅能查詢分配給您的學生']);
            exit;
        }

        $q = $conn->prepare("SELECT id, student_id, teacher_id, contact_date, method, result, follow_up_notes, created_at FROM enrollment_contact_logs WHERE student_id = ? ORDER BY contact_date DESC, id DESC");
        $q->bind_param('i', $student_id);
        $q->execute();
        $res = $q->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'logs' => $rows]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '方法不被允許']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '系統錯誤', 'error' => $e->getMessage()]);
}
?>



