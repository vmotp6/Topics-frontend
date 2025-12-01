<?php
/**
 * 檢查登入狀態 API
 * 用於前端 JavaScript 檢查用戶是否仍然登入
 */
require_once dirname(__DIR__) . '/session_config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// 檢查登入狀態
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

// 如果 session 資料不完整，清除登入狀態
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (!isset($_SESSION['username']) || empty($_SESSION['username']) || 
        !isset($_SESSION['role']) || empty($_SESSION['role'])) {
        $_SESSION['logged_in'] = false;
        $isLoggedIn = false;
    }
}

// 返回登入狀態
echo json_encode([
    'logged_in' => $isLoggedIn,
    'username' => $isLoggedIn ? ($_SESSION['username'] ?? '') : '',
    'role' => $isLoggedIn ? ($_SESSION['role'] ?? '') : '',
    'session_id' => session_id()
], JSON_UNESCAPED_UNICODE);
?>



