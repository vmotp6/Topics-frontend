<?php
// 載入 session 配置
require_once 'session_config.php';

// 顯示調試信息
echo "<h1>Google 登入調試頁面</h1>";

echo "<h2>Session 狀態</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>GET 參數</h2>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

echo "<h2>登入狀態檢查</h2>";
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

echo "登入狀態: " . ($isLoggedIn ? "已登入" : "未登入") . "<br>";
echo "logged_in: " . (isset($_SESSION['logged_in']) ? ($_SESSION['logged_in'] ? 'true' : 'false') : '未設定') . "<br>";
echo "username: " . (isset($_SESSION['username']) ? $_SESSION['username'] : '未設定') . "<br>";
echo "role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : '未設定') . "<br>";
echo "login_method: " . (isset($_SESSION['login_method']) ? $_SESSION['login_method'] : '未設定') . "<br>";

echo "<h2>處理 Google 登入回調</h2>";
if (isset($_GET['google_login']) && $_GET['google_login'] === 'success') {
    echo "檢測到 Google 登入回調<br>";
    if (isset($_GET['username']) && isset($_GET['role'])) {
        echo "設定 Session...<br>";
        // 設定Session
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $_GET['username'];
        $_SESSION['role'] = $_GET['role'];
        $_SESSION['login_method'] = 'google';
        
        echo "Session 設定完成<br>";
        echo "重定向到相應頁面...<br>";
        
        // 重定向到相應頁面（避免URL參數顯示）
        $redirect_url = 'index.php';
        if ($_GET['role'] === '管理員') {
            $redirect_url = 'admin_admission.php';
        } elseif ($_GET['role'] === '老師') {
            $redirect_url = 'teacher.php';
        } elseif ($_GET['role'] === '學生') {
            $redirect_url = 'student.php';
        }
        
        echo "重定向到: $redirect_url<br>";
        echo "<script>setTimeout(function(){ window.location.href = '$redirect_url'; }, 2000);</script>";
    } else {
        echo "缺少必要參數: username 或 role<br>";
    }
} else {
    echo "未檢測到 Google 登入回調<br>";
}

echo "<h2>測試連結</h2>";
echo "<a href='index.php'>返回首頁</a><br>";
echo "<a href='student.php'>學生頁面</a><br>";
echo "<a href='teacher.php'>老師頁面</a><br>";
echo "<a href='admin_admission.php'>管理員頁面</a><br>";
?>
