<?php
require_once 'session_config.php';
require_once 'config.php';

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true &&
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

$allowedRoles = ['老師', 'TEA', 'STA', '學校行政人員', 'AA', 'DI', 'IM'];

if (!$isLoggedIn || !in_array($_SESSION['role'], $allowedRoles, true)) {
    http_response_code(403);
    die('權限不足');
}

$file_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($file_id <= 0) {
    http_response_code(400);
    die('缺少檔案ID');
}

$username = $_SESSION['username'];
$conn = getDatabaseConnection();

$user_stmt = $conn->prepare("SELECT id FROM user WHERE username = ?");
$user_stmt->bind_param("s", $username);
$user_stmt->execute();
$user_res = $user_stmt->get_result();
$user = $user_res->fetch_assoc();
$user_stmt->close();

if (!$user) {
    http_response_code(403);
    die('找不到使用者');
}

$teacher_id = (int)$user['id'];

// 向後相容：若舊表沒有 is_shared/shared_at，動態補欄位
try {
    $conn->query("
        CREATE TABLE IF NOT EXISTS teacher_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_id INT NOT NULL,
            original_filename VARCHAR(255) NOT NULL,
            stored_filename VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size BIGINT NOT NULL,
            file_type VARCHAR(100),
            is_shared TINYINT(1) NOT NULL DEFAULT 0,
            shared_at TIMESTAMP NULL DEFAULT NULL,
            upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_teacher_id (teacher_id),
            INDEX idx_is_shared (is_shared),
            INDEX idx_upload_time (upload_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $c1 = $conn->query("SHOW COLUMNS FROM teacher_files LIKE 'is_shared'");
    if (!$c1 || $c1->num_rows === 0) {
        @$conn->query("ALTER TABLE teacher_files ADD COLUMN is_shared TINYINT(1) NOT NULL DEFAULT 0");
        @$conn->query("ALTER TABLE teacher_files ADD INDEX idx_is_shared (is_shared)");
    }
    $c2 = $conn->query("SHOW COLUMNS FROM teacher_files LIKE 'shared_at'");
    if (!$c2 || $c2->num_rows === 0) {
        @$conn->query("ALTER TABLE teacher_files ADD COLUMN shared_at TIMESTAMP NULL DEFAULT NULL");
    }
} catch (Exception $e) {
    // ignore
}

// 允許：自己的檔案 或 共享檔案
$stmt = $conn->prepare("SELECT original_filename, file_path FROM teacher_files WHERE id = ? AND (teacher_id = ? OR is_shared = 1) LIMIT 1");
$stmt->bind_param("ii", $file_id, $teacher_id);
$stmt->execute();
$res = $stmt->get_result();
$file = $res->fetch_assoc();
$stmt->close();
$conn->close();

if (!$file) {
    http_response_code(404);
    die('找不到檔案或無權限下載');
}

$full_path = __DIR__ . '/' . $file['file_path'];
$download_name = $file['original_filename'];

if (!file_exists($full_path)) {
    http_response_code(404);
    die('檔案不存在');
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . rawurlencode($download_name) . '"');
header('Content-Length: ' . filesize($full_path));
header('Cache-Control: must-revalidate');
header('Pragma: public');

readfile($full_path);
exit;



