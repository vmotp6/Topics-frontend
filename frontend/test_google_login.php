<?php
// 載入 session 配置
require_once 'session_config.php';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google 登入測試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        button { padding: 10px 20px; margin: 5px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>Google 登入測試頁面</h1>
    
    <div class="test-section">
        <h3>當前 Session 狀態</h3>
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <p class="success">✅ 已登入</p>
            <p><strong>用戶名:</strong> <?php echo $_SESSION['username'] ?? '未設定'; ?></p>
            <p><strong>角色:</strong> <?php echo $_SESSION['role'] ?? '未設定'; ?></p>
            <p><strong>登入方式:</strong> <?php echo $_SESSION['login_method'] ?? '未設定'; ?></p>
        <?php else: ?>
            <p class="error">❌ 未登入</p>
        <?php endif; ?>
    </div>
    
    <div class="test-section">
        <h3>測試操作</h3>
        <button onclick="testGoogleLogin()">測試 Google 登入</button>
        <button onclick="checkSession()">檢查 Session</button>
        <button onclick="clearSession()">清除 Session</button>
        <button onclick="goToIndex()">前往首頁</button>
    </div>
    
    <div class="test-section">
        <h3>測試結果</h3>
        <div id="testResult"></div>
    </div>
    
    <div class="test-section info">
        <h3>說明</h3>
        <p>1. 點擊「測試 Google 登入」會跳轉到 Google 授權頁面</p>
        <p>2. 授權成功後會回到此頁面並顯示登入狀態</p>
        <p>3. 如果登入後又自動登出，請檢查 Session 設定</p>
    </div>

    <script>
        function testGoogleLogin() {
            window.location.href = 'http://localhost:5000/auth/google';
        }
        
        function checkSession() {
            fetch('debug_session.php')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('testResult').innerHTML = '<h4>Session 檢查結果:</h4>' + data;
                })
                .catch(error => {
                    document.getElementById('testResult').innerHTML = '<p class="error">檢查失敗: ' + error + '</p>';
                });
        }
        
        function clearSession() {
            if (confirm('確定要清除 Session 嗎？')) {
                window.location.href = 'logout.php';
            }
        }
        
        function goToIndex() {
            window.location.href = 'index.php';
        }
        
        // 頁面載入時自動檢查 Session
        window.onload = function() {
            console.log('頁面載入完成，當前 Session ID:', '<?php echo session_id(); ?>');
        };
    </script>
</body>
</html>
