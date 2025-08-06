<?php
header('Content-Type: application/json');

// 設置 cookie 來隱藏聊天按鈕
if (isset($_POST['hide_chat'])) {
    setcookie('chat_hidden', '1', time() + (365 * 24 * 60 * 60), '/'); // 1年有效期
    echo json_encode(['success' => true]);
    exit;
}

// 顯示聊天按鈕
if (isset($_POST['show_chat'])) {
    setcookie('chat_hidden', '', time() - 3600, '/'); // 刪除 cookie
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => '無效的請求']);
?> 