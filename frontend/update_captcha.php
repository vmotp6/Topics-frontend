<?php
/**
 * 更新驗證碼API
 */

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $captcha = $_POST['captcha'] ?? '';
    
    if (strlen($captcha) === 4 && ctype_digit($captcha)) {
        $_SESSION['captcha_code'] = $captcha;
        echo json_encode(['success' => true, 'message' => '驗證碼已更新']);
    } else {
        echo json_encode(['success' => false, 'message' => '無效的驗證碼格式']);
    }
} else {
    echo json_encode(['success' => false, 'message' => '只支援POST請求']);
}
?>






