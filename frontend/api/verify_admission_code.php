<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../session_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '只接受 POST 請求']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$code = trim($_POST['code'] ?? '');

if (empty($email) || empty($code)) {
    echo json_encode(['success' => false, 'message' => '請提供 email 與驗證碼']);
    exit;
}

if (!isset($_SESSION['admission_verification'])) {
    echo json_encode(['success' => false, 'message' => '尚未發送驗證碼或已過期']);
    exit;
}

$v = $_SESSION['admission_verification'];
if ($v['email'] !== $email) {
    echo json_encode(['success' => false, 'message' => '驗證信箱與先前不符']);
    exit;
}

if (time() > intval($v['expires'])) {
    unset($_SESSION['admission_verification']);
    echo json_encode(['success' => false, 'message' => '驗證碼已過期，請重新發送']);
    exit;
}

if ($v['code'] !== $code) {
    echo json_encode(['success' => false, 'message' => '驗證碼錯誤']);
    exit;
}

// 驗證成功
$_SESSION['admission_verified_email'] = $email;
unset($_SESSION['admission_verification']);
echo json_encode(['success' => true, 'message' => '驗證成功']);
exit;

?>
