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

$stmt = $conn->prepare("SELECT original_filename, file_path FROM teacher_files WHERE id = ? AND teacher_id = ?");
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

