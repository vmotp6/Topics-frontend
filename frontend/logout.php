<?php
session_start();
session_unset();
session_destroy();

// 防止快取
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

header("Location: one.php"); // 登出後導回首頁
exit;
?>