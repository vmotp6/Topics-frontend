<?php
// 載入 session 配置
require_once 'session_config.php';

// 記錄登出前的狀態（用於調試）
$debug_logout = isset($_GET['debug']) ? true : false;
if ($debug_logout) {
    error_log("登出前 Session 狀態: " . print_r($_SESSION, true));
}

// 清除所有 session 資料
$_SESSION = array();

// 清除所有可能的 session 變數
unset($_SESSION['logged_in']);
unset($_SESSION['username']);
unset($_SESSION['role']);
unset($_SESSION['user_id']);
unset($_SESSION['id']);
unset($_SESSION['login_method']);
unset($_SESSION['last_activity']);
unset($_SESSION['expire_time']);
unset($_SESSION['initiated']);

// 清除 session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 銷毀 session
session_destroy();

// 重新啟動一個乾淨的 session
session_start();
session_regenerate_id(true);

// 設定新的 session 為未登入狀態
$_SESSION['logged_in'] = false;

// 防止快取
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// 記錄登出後的狀態（用於調試）
if ($debug_logout) {
    error_log("登出後 Session 狀態: " . print_r($_SESSION, true));
}

// 登出後導回首頁
header("Location: index.php");
exit;
?>