<?php
// 載入 session 配置
require_once 'session_config.php';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>快速頭像測試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-box { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 10px; 
            margin: 20px 0; 
            border: 2px solid #dee2e6;
        }
        .success { border-color: #28a745; background: #d4edda; }
        .error { border-color: #dc3545; background: #f8d7da; }
        .avatar { 
            width: 50px; 
            height: 50px; 
            border-radius: 50%; 
            border: 3px solid #007bff; 
            margin: 10px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <h1>⚡ 快速頭像測試</h1>
    
    <div class="test-box">
        <h3>🔐 登入狀態</h3>
        <?php
        $isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['username']);
        ?>
        <?php if ($isLoggedIn): ?>
            <div class="success">
                <p>✅ <strong>已登入</strong></p>
                <p>用戶名: <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                <p>角色: <?php echo htmlspecialchars($_SESSION['role'] ?? '未知'); ?></p>
            </div>
        <?php else: ?>
            <div class="error">
                <p>❌ <strong>未登入</strong></p>
                <p>這就是為什麼看不到頭像的原因！</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="test-box">
        <h3>🖼️ 頭像測試</h3>
        <?php if ($isLoggedIn): ?>
            <p>測試頭像顯示：</p>
            <img src="./share/EIdROxGXsAE_LSs.jpg" 
                 alt="測試頭像" 
                 class="avatar"
                 onload="console.log('✅ 頭像載入成功')"
                 onerror="console.log('❌ 頭像載入失敗'); this.style.border='3px solid red';">
        <?php else: ?>
            <p>請先登入後再測試頭像</p>
        <?php endif; ?>
    </div>
    
    <div class="test-box">
        <h3>🧭 Header 組件測試</h3>
        <p>測試修正後的導航列：</p>
        <div style="border: 1px solid #ccc; padding: 10px; background: rgba(217, 229, 234, 0.95);">
            <?php include 'share/header.php'; ?>
        </div>
    </div>
    
    <div style="margin-top: 30px; text-align: center;">
        <?php if (!$isLoggedIn): ?>
            <a href="test_google_login.php" style="color: #007bff; text-decoration: none; margin: 0 10px;">🔐 先登入</a>
        <?php endif; ?>
        <a href="index.php" style="color: #007bff; text-decoration: none; margin: 0 10px;">🏠 返回首頁</a>
    </div>

    <script>
        window.onload = function() {
            console.log('⚡ 快速頭像測試頁面載入完成');
            console.log('登入狀態:', <?php echo $isLoggedIn ? 'true' : 'false'; ?>);
        };
    </script>
</body>
</html>
