<?php
header('Content-Type: application/json');

if (isset($_POST['hide_ai']) && $_POST['hide_ai'] == true) {
    // 設置cookie，有效期30天
    setcookie('ai_hidden', 'true', time() + (30 * 24 * 60 * 60), '/');
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?> 