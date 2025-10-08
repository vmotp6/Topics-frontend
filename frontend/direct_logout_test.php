<?php
// 直接測試登出功能
echo "<h1>直接登出測試</h1>";

// 載入 session 配置
require_once 'session_config.php';

echo "<h2>登出前狀態：</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>登入狀態: " . (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] ? '已登入' : '未登入') . "</p>";
echo "<p>用戶名: " . ($_SESSION['username'] ?? '無') . "</p>";

// 執行登出
echo "<h2>執行登出...</h2>";
session_unset();
session_destroy();

// 重新啟動 session
session_start();
session_regenerate_id(true);

echo "<h2>登出後狀態：</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>登入狀態: " . (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] ? '已登入' : '未登入') . "</p>";
echo "<p>用戶名: " . ($_SESSION['username'] ?? '無') . "</p>";

echo "<h2>測試完成</h2>";
echo "<p><a href='index.php'>返回首頁</a></p>";
?>
