<?php
// 載入 session 配置
require_once 'session_config.php';

// 獲取當前頁面信息
$current_page = basename($_SERVER['PHP_SELF']);
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '直接訪問';

?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session 診斷工具</title>
    <style>
        body {
            font-family: 'Microsoft JhengHei', sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }
        .section h3 {
            color: #2c3e50;
            margin-top: 0;
            border-bottom: 2px solid #28a745;
            padding-bottom: 10px;
        }
        .status {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #e9ecef;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        .btn:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-bug"></i> Session 診斷工具</h1>
        
        <div class="section">
            <h3>📊 基本資訊</h3>
            <div class="status info">
                <strong>當前頁面：</strong> <?php echo htmlspecialchars($current_page); ?><br>
                <strong>來源頁面：</strong> <?php echo htmlspecialchars($referrer); ?><br>
                <strong>當前時間：</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
                <strong>Session ID：</strong> <?php echo session_id(); ?>
            </div>
        </div>

        <div class="section">
            <h3>🔐 Session 狀態</h3>
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                <div class="status success">
                    <strong>✅ 登入狀態：</strong> 已登入
                </div>
                <div class="status info">
                    <strong>用戶名：</strong> <?php echo htmlspecialchars($_SESSION['username'] ?? '未設定'); ?><br>
                    <strong>角色：</strong> <?php echo htmlspecialchars($_SESSION['role'] ?? '未設定'); ?><br>
                    <strong>登入方式：</strong> <?php echo htmlspecialchars($_SESSION['login_method'] ?? '未設定'); ?><br>
                    <strong>最後活動：</strong> <?php echo isset($_SESSION['last_activity']) ? date('Y-m-d H:i:s', $_SESSION['last_activity']) : '未設定'; ?><br>
                    <strong>過期時間：</strong> <?php echo isset($_SESSION['expire_time']) ? date('Y-m-d H:i:s', $_SESSION['expire_time']) : '未設定'; ?>
                </div>
            <?php else: ?>
                <div class="status error">
                    <strong>❌ 登入狀態：</strong> 未登入
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>🍪 Cookie 資訊</h3>
            <div class="status info">
                <strong>Session Cookie 名稱：</strong> <?php echo session_name(); ?><br>
                <strong>Session Cookie 值：</strong> <?php echo isset($_COOKIE[session_name()]) ? $_COOKIE[session_name()] : '未設定'; ?><br>
                <strong>Cookie 存活時間：</strong> <?php echo ini_get('session.cookie_lifetime'); ?> 秒<br>
                <strong>Cookie 路徑：</strong> <?php echo ini_get('session.cookie_path'); ?><br>
                <strong>Cookie 域名：</strong> <?php echo ini_get('session.cookie_domain'); ?>
            </div>
        </div>

        <div class="section">
            <h3>⚙️ Session 設定</h3>
            <div class="status info">
                <strong>Session 存活時間：</strong> <?php echo ini_get('session.gc_maxlifetime'); ?> 秒<br>
                <strong>Session 儲存路徑：</strong> <?php echo ini_get('session.save_path'); ?><br>
                <strong>Session 狀態：</strong> <?php echo session_status(); ?> (1=已啟動, 2=已關閉, 0=未啟動)<br>
                <strong>嚴格模式：</strong> <?php echo ini_get('session.use_strict_mode') ? '啟用' : '停用'; ?><br>
                <strong>HTTP Only：</strong> <?php echo ini_get('session.cookie_httponly') ? '啟用' : '停用'; ?><br>
                <strong>Secure：</strong> <?php echo ini_get('session.cookie_secure') ? '啟用' : '停用'; ?>
            </div>
        </div>

        <div class="section">
            <h3>📋 完整 Session 資料</h3>
            <pre><?php print_r($_SESSION); ?></pre>
        </div>

        <div class="section">
            <h3>🔧 測試功能</h3>
            <a href="?test_login=1" class="btn">模擬登入</a>
            <a href="?test_logout=1" class="btn">模擬登出</a>
            <a href="?clear_session=1" class="btn">清除 Session</a>
            <a href="cooperation_upload.php" class="btn">前往就讀意願登錄</a>
            <a href="index.php" class="btn">返回首頁</a>
        </div>

        <?php
        // 測試功能
        if (isset($_GET['test_login'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = 'test_user';
            $_SESSION['role'] = '學生';
            $_SESSION['login_method'] = 'test';
            $_SESSION['last_activity'] = time();
            $_SESSION['expire_time'] = time() + 86400;
            echo '<div class="status success">✅ 已模擬登入</div>';
        }

        if (isset($_GET['test_logout'])) {
            session_unset();
            session_destroy();
            session_start();
            echo '<div class="status warning">⚠️ 已模擬登出</div>';
        }

        if (isset($_GET['clear_session'])) {
            session_unset();
            echo '<div class="status warning">⚠️ 已清除 Session 資料</div>';
        }
        ?>
    </div>
</body>
</html>