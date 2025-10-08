<?php
// 載入 session 配置
require_once 'session_config.php';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>簡單登出測試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; text-align: center; }
        .status { padding: 20px; margin: 20px 0; border-radius: 10px; }
        .logged-in { background: #d4edda; color: #155724; }
        .logged-out { background: #f8d7da; color: #721c24; }
        .logout-btn { 
            display: inline-block; 
            padding: 15px 30px; 
            background: #dc3545; 
            color: white; 
            text-decoration: none; 
            border-radius: 10px; 
            font-size: 18px;
            margin: 10px;
        }
        .logout-btn:hover { background: #c82333; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 10px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>登出功能測試</h1>
    
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
        <div class="status logged-in">
            <h2>✅ 您已登入</h2>
            <p><strong>用戶名:</strong> <?php echo htmlspecialchars($_SESSION['username'] ?? '未知'); ?></p>
            <p><strong>角色:</strong> <?php echo htmlspecialchars($_SESSION['role'] ?? '未知'); ?></p>
            <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
        </div>
        
        <div class="info">
            <h3>測試登出功能</h3>
            <p>點擊下方按鈕測試登出功能：</p>
            <a href="logout.php" class="logout-btn">🚪 登出</a>
        </div>
        
    <?php else: ?>
        <div class="status logged-out">
            <h2>❌ 您未登入</h2>
            <p>請先登入後再測試登出功能</p>
        </div>
        
        <div class="info">
            <h3>登入選項</h3>
            <p>請選擇登入方式：</p>
            <a href="test_google_login.php" class="logout-btn" style="background: #007bff;">🔐 Google 登入</a>
            <a href="index.php" class="logout-btn" style="background: #28a745;">🏠 返回首頁</a>
        </div>
    <?php endif; ?>
    
    <div class="info">
        <h3>測試說明</h3>
        <p>1. 如果您已登入，點擊「登出」按鈕應該會清除session並跳轉到首頁</p>
        <p>2. 如果您未登入，請先使用Google登入功能</p>
        <p>3. 登出後應該會看到「您未登入」的狀態</p>
    </div>
    
    <div style="margin-top: 30px;">
        <a href="index.php" style="color: #007bff; text-decoration: none;">← 返回首頁</a> | 
        <a href="test_user_interface.php" style="color: #007bff; text-decoration: none;">完整測試頁面</a>
    </div>
</body>
</html>
