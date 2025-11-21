<?php
// Session 配置檔案
// 解決Google登入後自動登出的問題

// 設定 session 參數
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 86400); // 24小時
    ini_set('session.gc_maxlifetime', 86400);  // 24小時
    ini_set('session.cookie_httponly', 1);     // 防止XSS攻擊
    ini_set('session.cookie_secure', 0);       // 本地開發設為0，生產環境設為1
    ini_set('session.use_strict_mode', 1);     // 嚴格模式
    ini_set('session.cookie_path', '/');       // 設定cookie路徑為根目錄
    ini_set('session.cookie_domain', '');      // 清空域名設定，讓瀏覽器自動處理
    
    // 設定 session 名稱
    session_name('KANGNING_SESSION');
}

// 設定 session 儲存路徑（可選）
// session_save_path('/tmp/sessions');

// 啟動 session（避免重複啟動）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 更新 session 最後活動時間
if (isset($_SESSION['last_activity'])) {
    // 如果超過30分鐘沒有活動，則重新生成 session ID
    if (time() - $_SESSION['last_activity'] > 1800) {
        session_regenerate_id(true);
    }
}
$_SESSION['last_activity'] = time();

// 設定 session 過期時間
if (!isset($_SESSION['expire_time'])) {
    $_SESSION['expire_time'] = time() + 86400; // 24小時後過期
}

// 檢查 session 是否過期
if (isset($_SESSION['expire_time']) && time() > $_SESSION['expire_time']) {
    session_unset();
    session_destroy();
    session_start();
}

// 防止 session 固定攻擊（但不要清除驗證碼）
if (!isset($_SESSION['initiated'])) {
    // 保存驗證碼（如果存在）
    $captcha_backup = $_SESSION['captcha_code'] ?? null;
    
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
    
    // 恢復驗證碼（如果存在）
    if ($captcha_backup !== null) {
        $_SESSION['captcha_code'] = $captcha_backup;
    }
}

// 設定 session 變數的預設值
if (!isset($_SESSION['logged_in'])) {
    $_SESSION['logged_in'] = false;
}

if (!isset($_SESSION['login_method'])) {
    $_SESSION['login_method'] = 'none';
}

// 調試用：顯示 session 狀態
if (isset($_GET['debug_session'])) {
    echo "<pre>";
    echo "Session ID: " . session_id() . "\n";
    echo "Session Status: " . session_status() . "\n";
    echo "Session Data:\n";
    print_r($_SESSION);
    echo "</pre>";
    exit;
}
?>
