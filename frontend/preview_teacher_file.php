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

// 向後相容：確保 teacher_files 有 is_shared 欄位
try {
    $c1 = $conn->query("SHOW COLUMNS FROM teacher_files LIKE 'is_shared'");
    if (!$c1 || $c1->num_rows === 0) {
        @$conn->query("ALTER TABLE teacher_files ADD COLUMN is_shared TINYINT(1) NOT NULL DEFAULT 0");
        @$conn->query("ALTER TABLE teacher_files ADD INDEX idx_is_shared (is_shared)");
    }
} catch (Exception $e) {
    // ignore
}

$user_stmt = $conn->prepare("SELECT id FROM user WHERE username = ? LIMIT 1");
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

// 允許：自己的檔案 或 共享檔案
$stmt = $conn->prepare("SELECT original_filename, file_path, file_type FROM teacher_files WHERE id = ? AND (teacher_id = ? OR is_shared = 1) LIMIT 1");
$stmt->bind_param("ii", $file_id, $teacher_id);
$stmt->execute();
$res = $stmt->get_result();
$file = $res->fetch_assoc();
$stmt->close();
$conn->close();

if (!$file) {
    http_response_code(404);
    die('找不到檔案或無權限預覽');
}

$full_path = __DIR__ . '/' . $file['file_path'];
$filename = (string)$file['original_filename'];
$mime = trim((string)($file['file_type'] ?? ''));

if (!file_exists($full_path)) {
    http_response_code(404);
    die('檔案不存在');
}

// 若沒有 mime，嘗試用副檔名推斷
if ($mime === '') {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $map = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'txt' => 'text/plain; charset=utf-8',
        'csv' => 'text/csv; charset=utf-8',
    ];
    $mime = $map[$ext] ?? 'application/octet-stream';
}

// 以 inline 方式預覽（瀏覽器支援的檔案才會直接顯示）
header('Content-Description: File Preview');
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . rawurlencode($filename) . '"');
header('Content-Length: ' . filesize($full_path));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
header('X-Content-Type-Options: nosniff');

readfile($full_path);
exit;


