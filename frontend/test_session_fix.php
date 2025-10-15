<?php
// 載入 session 配置
require_once 'session_config.php';

echo "<h1>Session 修復測試</h1>";

// 檢查當前 Session 狀態
echo "<h2>當前 Session 狀態</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// 檢查登入狀態邏輯
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

echo "<h2>登入狀態檢查</h2>";
echo "登入狀態: " . ($isLoggedIn ? "已登入" : "未登入") . "<br>";
echo "logged_in: " . (isset($_SESSION['logged_in']) ? ($_SESSION['logged_in'] ? 'true' : 'false') : '未設定') . "<br>";
echo "username: " . (isset($_SESSION['username']) ? $_SESSION['username'] : '未設定') . "<br>";
echo "role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : '未設定') . "<br>";

// 模擬 Google 登入回調
if (isset($_GET['test']) && $_GET['test'] === 'gmail') {
    echo "<h2>模擬 Gmail 登入</h2>";
    
    // 模擬設定 Session
    $_SESSION['logged_in'] = true;
    $_SESSION['username'] = 'test@gmail.com';
    $_SESSION['role'] = '學生';
    $_SESSION['login_method'] = 'google';
    
    echo "Session 已設定<br>";
    echo "<a href='student.php'>前往學生頁面</a><br>";
    echo "<a href='?'>重新檢查 Session</a><br>";
}

if (isset($_GET['test']) && $_GET['test'] === 'school') {
    echo "<h2>模擬學校帳號登入</h2>";
    
    // 模擬設定 Session
    $_SESSION['logged_in'] = true;
    $_SESSION['username'] = 'teacher@ukn.edu.tw';
    $_SESSION['role'] = '老師';
    $_SESSION['login_method'] = 'google';
    
    echo "Session 已設定<br>";
    echo "<a href='teacher.php'>前往老師頁面</a><br>";
    echo "<a href='?'>重新檢查 Session</a><br>";
}

if (isset($_GET['test']) && $_GET['test'] === 'clear') {
    echo "<h2>清除 Session</h2>";
    session_unset();
    session_destroy();
    session_start();
    echo "Session 已清除<br>";
    echo "<a href='?'>重新檢查 Session</a><br>";
}

echo "<h2>測試選項</h2>";
echo "<a href='?test=gmail'>模擬 Gmail 登入</a><br>";
echo "<a href='?test=school'>模擬學校帳號登入</a><br>";
echo "<a href='?test=clear'>清除 Session</a><br>";

echo "<h2>頁面連結</h2>";
echo "<a href='index.php'>首頁</a><br>";
echo "<a href='student.php'>學生頁面</a><br>";
echo "<a href='teacher.php'>老師頁面</a><br>";
echo "<a href='admin_admission.php'>管理員頁面</a><br>";

echo "<h2>真實登入測試</h2>";
echo "<a href='http://localhost:5000/auth/google'>Google 登入</a><br>";
?>
