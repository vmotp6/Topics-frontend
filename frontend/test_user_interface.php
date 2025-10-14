<?php
// 載入 session 配置
require_once 'session_config.php';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用戶介面測試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        .avatar-test { width: 100px; height: 100px; border-radius: 50%; border: 3px solid #007bff; }
        button { padding: 10px 20px; margin: 5px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .logout-btn { background: #dc3545; }
        .logout-btn:hover { background: #c82333; }
    </style>
</head>
<body>
    <h1>用戶介面測試頁面</h1>
    
    <div class="test-section">
        <h3>當前登入狀態</h3>
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <p class="success">✅ 已登入</p>
            <p><strong>用戶名:</strong> <?php echo $_SESSION['username'] ?? '未設定'; ?></p>
            <p><strong>角色:</strong> <?php echo $_SESSION['role'] ?? '未設定'; ?></p>
            <p><strong>登入方式:</strong> <?php echo $_SESSION['login_method'] ?? '未設定'; ?></p>
            <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
        <?php else: ?>
            <p class="error">❌ 未登入</p>
        <?php endif; ?>
    </div>
    
    <div class="test-section">
        <h3>頭像測試</h3>
        <?php
        // 測試頭像載入
        $avatar_src = '/Topics-frontend/frontend/share/EIdROxGXsAE_LSs.jpg'; // 預設頭像
        if (isset($_SESSION['username'])) {
            try {
                require_once 'config.php';
                $conn = getDatabaseConnection();
                if ($conn) {
                    $stmt = $conn->prepare("SELECT profile_picture FROM user WHERE username = ?");
                    $stmt->bind_param("s", $_SESSION['username']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        if (!empty($row['profile_picture'])) {
                            if (filter_var($row['profile_picture'], FILTER_VALIDATE_URL)) {
                                $avatar_src = $row['profile_picture'];
                            } else {
                                $avatar_src = '/Topics-frontend/frontend/share/' . $row['profile_picture'];
                            }
                        }
                    }
                    $conn->close();
                }
            } catch (Exception $e) {
                error_log("頭像載入錯誤: " . $e->getMessage());
            }
        }
        ?>
        <p><strong>頭像路徑:</strong> <?php echo htmlspecialchars($avatar_src); ?></p>
        <img src="<?php echo htmlspecialchars($avatar_src); ?>" alt="測試頭像" class="avatar-test" onerror="this.src='/Topics-frontend/frontend/share/EIdROxGXsAE_LSs.jpg'">
        <p><em>如果頭像無法顯示，會自動使用預設頭像</em></p>
    </div>
    
    <div class="test-section">
        <h3>功能測試</h3>
        <button onclick="testLogout()">測試登出功能</button>
        <button onclick="testAvatar()">測試頭像載入</button>
        <button onclick="goToIndex()">前往首頁</button>
        <button onclick="goToHeader()">測試Header組件</button>
    </div>
    
    <div class="test-section">
        <h3>登出測試</h3>
        <p>點擊下方按鈕測試登出功能：</p>
        <a href="logout.php" class="logout-btn" style="display: inline-block; padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px;">登出</a>
    </div>
    
    <div class="test-section info">
        <h3>測試說明</h3>
        <p>1. <strong>登出測試</strong>：點擊登出按鈕，應該會清除session並跳轉到首頁</p>
        <p>2. <strong>頭像測試</strong>：檢查頭像是否能正確顯示</p>
        <p>3. <strong>Header測試</strong>：檢查導航列中的用戶下拉選單是否正常</p>
    </div>
    
    <div class="test-section">
        <h3>測試結果</h3>
        <div id="testResult"></div>
    </div>

    <script>
        function testLogout() {
            document.getElementById('testResult').innerHTML = '<p class="info">正在測試登出功能...</p>';
            fetch('logout.php')
                .then(response => {
                    if (response.redirected) {
                        document.getElementById('testResult').innerHTML = '<p class="success">✅ 登出功能正常，已重定向</p>';
                    } else {
                        document.getElementById('testResult').innerHTML = '<p class="error">❌ 登出功能異常</p>';
                    }
                })
                .catch(error => {
                    document.getElementById('testResult').innerHTML = '<p class="error">❌ 登出測試失敗: ' + error + '</p>';
                });
        }
        
        function testAvatar() {
            const avatar = document.querySelector('.avatar-test');
            if (avatar.complete && avatar.naturalHeight !== 0) {
                document.getElementById('testResult').innerHTML = '<p class="success">✅ 頭像載入成功</p>';
            } else {
                document.getElementById('testResult').innerHTML = '<p class="error">❌ 頭像載入失敗</p>';
            }
        }
        
        function goToIndex() {
            window.location.href = 'index.php';
        }
        
        function goToHeader() {
            // 創建一個測試頁面來檢查header組件
            const testWindow = window.open('', '_blank');
            testWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head><title>Header測試</title></head>
                <body>
                    <h1>Header組件測試</h1>
                    <div id="header-container"></div>
                    <script>
                        fetch('share/header.php')
                            .then(response => response.text())
                            .then(data => {
                                document.getElementById('header-container').innerHTML = data;
                            });
                    </script>
                </body>
                </html>
            `);
        }
        
        // 頁面載入時自動檢查
        window.onload = function() {
            console.log('頁面載入完成');
            console.log('Session ID:', '<?php echo session_id(); ?>');
            console.log('登入狀態:', <?php echo isset($_SESSION['logged_in']) && $_SESSION['logged_in'] ? 'true' : 'false'; ?>);
        };
    </script>
</body>
</html>
