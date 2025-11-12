<?php
/**
 * 驗證碼生成API
 * 統一使用 $_SESSION['captcha_code'] 作為 session key
 */

// 載入 session 配置
require_once 'session_config.php';

// 生成4位隨機數字驗證碼
function generateCaptcha() {
    return sprintf("%04d", rand(1000, 9999));
}

// 獲取當前驗證碼（用於在頁面中顯示）
function getCurrentCaptcha() {
    if (!isset($_SESSION['captcha_code'])) {
        $_SESSION['captcha_code'] = generateCaptcha();
    }
    return $_SESSION['captcha_code'];
}

// 只有在直接訪問此文件時才輸出 JSON（作為 API）
if (basename($_SERVER['PHP_SELF']) === 'generate_captcha.php') {
    // 設定回應為 JSON
    header('Content-Type: application/json; charset=utf-8');
    
    // 如果是 AJAX 請求，返回新的驗證碼
    if (isset($_GET['action']) && $_GET['action'] === 'refresh') {
        $captcha = generateCaptcha();
        $_SESSION['captcha_code'] = $captcha;  // 統一使用 captcha_code
        
        echo json_encode(['success' => true, 'captcha' => $captcha]);
        exit;
    }
    
    // 初始化驗證碼（如果 session 中沒有）
    if (!isset($_SESSION['captcha_code'])) {
        $_SESSION['captcha_code'] = generateCaptcha();
    }
    
    // 返回當前驗證碼
    echo json_encode([
        'success' => true, 
        'captcha' => $_SESSION['captcha_code']
    ]);
    exit;
}

// 如果被其他文件引入，只初始化驗證碼（如果需要的話）
if (!isset($_SESSION['captcha_code'])) {
    $_SESSION['captcha_code'] = generateCaptcha();
}
?>
