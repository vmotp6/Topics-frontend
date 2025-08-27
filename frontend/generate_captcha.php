<?php
// 檢查 session 是否已啟動，避免重複啟動
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 生成4位隨機數字驗證碼
function generateCaptcha() {
    return sprintf("%04d", rand(1000, 9999));
}

// 如果是 AJAX 請求，返回新的驗證碼
if (isset($_GET['action']) && $_GET['action'] === 'refresh') {
    $captcha = generateCaptcha();
    $_SESSION['captcha'] = $captcha;
    
    header('Content-Type: application/json');
    echo json_encode(['captcha' => $captcha]);
    exit;
}

// 初始化驗證碼（如果 session 中沒有）
if (!isset($_SESSION['captcha'])) {
    $_SESSION['captcha'] = generateCaptcha();
}

// 返回當前驗證碼
function getCurrentCaptcha() {
    return isset($_SESSION['captcha']) ? $_SESSION['captcha'] : generateCaptcha();
}
?>
