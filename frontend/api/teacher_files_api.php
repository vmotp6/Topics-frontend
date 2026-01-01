<?php
// API：教師檔案上傳 / 列表 / 刪除
header('Content-Type: application/json; charset=utf-8');

require_once '../session_config.php';
require_once '../config.php';

// 登入與角色檢查
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true &&
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

if (!$isLoggedIn) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '未登入']);
    exit;
}

$role = $_SESSION['role'];
$allowedTeacherRoles = ['老師', 'TEA', 'STA', '學校行政人員', 'AA', 'DI', 'IM'];
if (!in_array($role, $allowedTeacherRoles, true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '權限不足']);
    exit;
}

// 取得使用者
$username = $_SESSION['username'];
$conn = getDatabaseConnection();

$user_stmt = $conn->prepare("SELECT id, username, role FROM user WHERE username = ?");
$user_stmt->bind_param("s", $username);
$user_stmt->execute();
$user_res = $user_stmt->get_result();
$user = $user_res->fetch_assoc();
$user_stmt->close();

if (!$user) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '找不到使用者']);
    exit;
}

$teacher_id = (int)$user['id'];

// 建表（若不存在）
$conn->query("
CREATE TABLE IF NOT EXISTS teacher_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT NOT NULL,
    file_type VARCHAR(100),
    upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_teacher_id (teacher_id),
    INDEX idx_upload_time (upload_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$method = $_SERVER['REQUEST_METHOD'];

// 上傳
if ($method === 'POST') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '檔案上傳失敗']);
        exit;
    }

    $file = $_FILES['file'];
    $original_filename = $file['name'];
    $file_size = $file['size'];
    $file_type = $file['type'];
    $tmp_name = $file['tmp_name'];

    // 單檔 50GB
    $max_file_size = 50 * 1024 * 1024 * 1024;
    if ($file_size > $max_file_size) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '檔案大小超過 50GB 限制']);
        exit;
    }

    $allowed_extensions = ['ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx', 'pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'zip', 'rar', '7z', 'txt', 'csv'];
    $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_extensions, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '不支援的檔案類型']);
        exit;
    }

    // 目錄
    $upload_dir = __DIR__ . '/../uploads/teacher_files/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // 唯一檔名
    $stored_filename = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $original_filename);
    $file_path_full = $upload_dir . $stored_filename;

    if (!move_uploaded_file($tmp_name, $file_path_full)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '檔案儲存失敗']);
        exit;
    }

    $relative_path = 'uploads/teacher_files/' . $stored_filename;

    $insert = $conn->prepare("INSERT INTO teacher_files (teacher_id, original_filename, stored_filename, file_path, file_size, file_type) VALUES (?, ?, ?, ?, ?, ?)");
    $insert->bind_param("isssis", $teacher_id, $original_filename, $stored_filename, $relative_path, $file_size, $file_type);
    if ($insert->execute()) {
        echo json_encode([
            'success' => true,
            'file_id' => $conn->insert_id,
            'filename' => $original_filename,
            'file_size' => $file_size,
            'upload_time' => date('Y-m-d H:i:s')
        ]);
    } else {
        unlink($file_path_full);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '資料寫入失敗']);
    }
    $insert->close();
    exit;
}

// 刪除
if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $file_id = isset($input['file_id']) ? (int)$input['file_id'] : 0;
    if ($file_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '缺少檔案ID']);
        exit;
    }

    $check = $conn->prepare("SELECT file_path FROM teacher_files WHERE id = ? AND teacher_id = ?");
    $check->bind_param("ii", $file_id, $teacher_id);
    $check->execute();
    $res = $check->get_result();
    $file_data = $res->fetch_assoc();
    $check->close();

    if (!$file_data) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => '找不到檔案或無權限刪除']);
        exit;
    }

    $full_path = __DIR__ . '/../' . $file_data['file_path'];
    if (file_exists($full_path)) {
        unlink($full_path);
    }

    $del = $conn->prepare("DELETE FROM teacher_files WHERE id = ? AND teacher_id = ?");
    $del->bind_param("ii", $file_id, $teacher_id);
    $ok = $del->execute();
    $del->close();

    echo json_encode(['success' => $ok, 'message' => $ok ? '檔案已刪除' : '刪除失敗']);
    exit;
}

// 列表
if ($method === 'GET') {
    $files_stmt = $conn->prepare("SELECT id, original_filename, file_size, file_type, upload_time FROM teacher_files WHERE teacher_id = ? ORDER BY upload_time DESC, id DESC");
    $files_stmt->bind_param("i", $teacher_id);
    $files_stmt->execute();
    $res = $files_stmt->get_result();
    $files = [];
    while ($row = $res->fetch_assoc()) {
        $row['file_size_formatted'] = formatSize($row['file_size']);
        $files[] = $row;
    }
    $files_stmt->close();

    echo json_encode(['success' => true, 'files' => $files]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => '方法不被允許']);

function formatSize($bytes) {
    if ($bytes >= 1024 * 1024 * 1024) {
        return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
    } elseif ($bytes >= 1024 * 1024) {
        return round($bytes / (1024 * 1024), 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
}

