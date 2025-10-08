<?php
// 載入 session 配置
require_once 'session_config.php';

// 處理Google登入回調
if (isset($_GET['google_login']) && $_GET['google_login'] === 'success') {
    if (isset($_GET['username']) && isset($_GET['role'])) {
        // 設定Session
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $_GET['username'];
        $_SESSION['role'] = $_GET['role'];
        $_SESSION['login_method'] = 'google';
        
        echo "<h2>Session 設定成功！</h2>";
        echo "<p>用戶名: " . $_SESSION['username'] . "</p>";
        echo "<p>角色: " . $_SESSION['role'] . "</p>";
        echo "<p>登入方式: " . $_SESSION['login_method'] . "</p>";
        echo "<p>Session ID: " . session_id() . "</p>";
        echo "<p><a href='index.php'>前往首頁</a></p>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session 調試頁面</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-info { background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .warning { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <h1>Session 調試頁面</h1>
    
    <div class="debug-info">
        <h3>Session 狀態</h3>
        <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
        <p><strong>Session 狀態:</strong> <?php echo session_status(); ?></p>
        <p><strong>Session 名稱:</strong> <?php echo session_name(); ?></p>
        <p><strong>Session 路徑:</strong> <?php echo session_save_path(); ?></p>
    </div>
    
    <div class="debug-info">
        <h3>Session 資料</h3>
        <?php if (empty($_SESSION)): ?>
            <p class="warning">Session 為空</p>
        <?php else: ?>
            <pre><?php print_r($_SESSION); ?></pre>
        <?php endif; ?>
    </div>
    
    <div class="debug-info">
        <h3>登入狀態</h3>
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <p class="success">✅ 已登入</p>
            <p><strong>用戶名:</strong> <?php echo $_SESSION['username'] ?? '未設定'; ?></p>
            <p><strong>角色:</strong> <?php echo $_SESSION['role'] ?? '未設定'; ?></p>
            <p><strong>登入方式:</strong> <?php echo $_SESSION['login_method'] ?? '未設定'; ?></p>
        <?php else: ?>
            <p class="error">❌ 未登入</p>
        <?php endif; ?>
    </div>
    
    <div class="debug-info">
        <h3>測試連結</h3>
        <p><a href="index.php">前往首頁</a></p>
        <p><a href="logout.php">登出</a></p>
        <p><a href="?debug_session=1">顯示詳細Session資訊</a></p>
    </div>
    
    <div class="debug-info">
        <h3>Google 登入測試</h3>
        <p><a href="http://localhost:5000/auth/google">測試 Google 登入</a></p>
    </div>
</body>
</html>
