<?php
/**
 * 驗證碼驗證API
 */

session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $captcha_input = $_POST['captcha'] ?? '';
    $captcha_session = $_SESSION['captcha_code'] ?? '';
    
    if (empty($captcha_input)) {
        echo json_encode(['success' => false, 'message' => '請輸入驗證碼']);
        exit;
    }
    
    if (empty($captcha_session)) {
        echo json_encode(['success' => false, 'message' => '驗證碼已過期，請刷新']);
        exit;
    }
    
    if ($captcha_input === $captcha_session) {
        echo json_encode(['success' => true, 'message' => '驗證碼正確']);
    } else {
        echo json_encode(['success' => false, 'message' => '驗證碼錯誤']);
    }
} else {
    echo json_encode(['success' => false, 'message' => '只支援POST請求']);
}
?>













