<?php
// 載入 session 配置
require_once 'session_config.php';

// 清除所有 session 資料
session_unset();
session_destroy();

// 重新啟動 session 以確保完全清除
session_start();
session_regenerate_id(true);

// 防止快取
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// 登出後導回首頁
header("Location: index.php");
exit;
?>