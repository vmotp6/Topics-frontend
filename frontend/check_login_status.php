<?php
// 載入 session 配置
require_once 'session_config.php';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登入狀態檢查</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .status-box { 
            padding: 20px; 
            border-radius: 10px; 
            margin: 20px 0; 
            border: 2px solid;
        }
        .logged-in { background: #d4edda; border-color: #28a745; color: #155724; }
        .not-logged-in { background: #f8d7da; border-color: #dc3545; color: #721c24; }
        .info { background: #d1ecf1; border-color: #17a2b8; color: #0c5460; }
        .debug { background: #e9ecef; padding: 10px; border-radius: 5px; margin: 10px 0; font-family: monospace; }
    </style>
</head>
<body>
    <h1>🔐 登入狀態檢查</h1>
    
    <div class="status-box <?php echo (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) ? 'logged-in' : 'not-logged-in'; ?>">
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <h2>✅ 您已登入</h2>
            <p><strong>用戶名:</strong> <?php echo htmlspecialchars($_SESSION['username'] ?? '未知'); ?></p>
            <p><strong>角色:</strong> <?php echo htmlspecialchars($_SESSION['role'] ?? '未知'); ?></p>
            <p><strong>登入方式:</strong> <?php echo htmlspecialchars($_SESSION['login_method'] ?? '未知'); ?></p>
            <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
        <?php else: ?>
            <h2>❌ 您未登入</h2>
            <p>這就是為什麼看不到頭像的原因！</p>
            <p>頭像只會在登入狀態下顯示。</p>
        <?php endif; ?>
    </div>
    
    <div class="status-box info">
        <h3>📊 Session 詳細資訊</h3>
        <div class="debug">
            <?php
            echo "Session 狀態: " . session_status() . "\n";
            echo "Session ID: " . session_id() . "\n";
            echo "Session 名稱: " . session_name() . "\n";
            echo "Session 路徑: " . session_save_path() . "\n";
            echo "\nSession 資料:\n";
            print_r($_SESSION);
            ?>
        </div>
    </div>
    
    <div class="status-box info">
        <h3>🔧 解決方案</h3>
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <p>您已經登入，如果頭像還是看不到，可能是以下原因：</p>
            <ul>
                <li>頭像檔案路徑不正確</li>
                <li>CSS 樣式問題</li>
                <li>JavaScript 錯誤</li>
                <li>瀏覽器快取問題</li>
            </ul>
            <p><strong>建議:</strong> 請訪問 <a href="avatar_diagnosis.php">頭像診斷頁面</a> 進行詳細檢查。</p>
        <?php else: ?>
            <p>您需要先登入才能看到頭像！</p>
            <ul>
                <li><a href="test_google_login.php">使用 Google 登入</a></li>
                <li><a href="index.php">返回首頁登入</a></li>
            </ul>
        <?php endif; ?>
    </div>
    
    <div style="margin-top: 30px; text-align: center;">
        <a href="index.php" style="color: #007bff; text-decoration: none; margin: 0 10px;">🏠 返回首頁</a>
        <?php if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']): ?>
            <a href="test_google_login.php" style="color: #007bff; text-decoration: none; margin: 0 10px;">🔐 Google 登入</a>
        <?php else: ?>
            <a href="avatar_diagnosis.php" style="color: #007bff; text-decoration: none; margin: 0 10px;">🔍 頭像診斷</a>
        <?php endif; ?>
    </div>
</body>
</html>
