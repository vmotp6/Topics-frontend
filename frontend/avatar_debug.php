<?php
// 載入 session 配置
require_once 'session_config.php';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>頭像調試頁面</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .warning { background: #fff3cd; color: #856404; }
        .info { background: #d1ecf1; color: #0c5460; }
        .avatar-test { width: 100px; height: 100px; border-radius: 50%; border: 3px solid #007bff; margin: 10px; }
        .avatar-small { width: 40px; height: 40px; border-radius: 50%; border: 3px solid white; margin: 5px; }
        .path-test { background: #e9ecef; padding: 10px; border-radius: 5px; margin: 5px 0; font-family: monospace; }
    </style>
</head>
<body>
    <h1>頭像調試頁面</h1>
    
    <div class="debug-section">
        <h3>當前登入狀態</h3>
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <p class="success">✅ 已登入</p>
            <p><strong>用戶名:</strong> <?php echo $_SESSION['username'] ?? '未設定'; ?></p>
            <p><strong>角色:</strong> <?php echo $_SESSION['role'] ?? '未設定'; ?></p>
        <?php else: ?>
            <p class="error">❌ 未登入</p>
        <?php endif; ?>
    </div>
    
    <div class="debug-section">
        <h3>路徑測試</h3>
        <?php
        $base_path = '/Topics-frontend/frontend/share/';
        $default_avatar = 'EIdROxGXsAE_LSs.jpg';
        $full_path = $base_path . $default_avatar;
        ?>
        <div class="path-test">
            <strong>基礎路徑:</strong> <?php echo $base_path; ?><br>
            <strong>預設頭像檔名:</strong> <?php echo $default_avatar; ?><br>
            <strong>完整路徑:</strong> <?php echo $full_path; ?><br>
            <strong>實際檔案路徑:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] . $full_path; ?>
        </div>
    </div>
    
    <div class="debug-section">
        <h3>檔案存在性檢查</h3>
        <?php
        $file_path = $_SERVER['DOCUMENT_ROOT'] . $full_path;
        if (file_exists($file_path)) {
            echo '<p class="success">✅ 預設頭像檔案存在</p>';
            echo '<p><strong>檔案大小:</strong> ' . filesize($file_path) . ' bytes</p>';
            echo '<p><strong>最後修改時間:</strong> ' . date('Y-m-d H:i:s', filemtime($file_path)) . '</p>';
        } else {
            echo '<p class="error">❌ 預設頭像檔案不存在</p>';
            echo '<p><strong>尋找路徑:</strong> ' . $file_path . '</p>';
        }
        ?>
    </div>
    
    <div class="debug-section">
        <h3>資料庫頭像查詢</h3>
        <?php
        $avatar_src = $full_path; // 預設頭像
        $db_avatar = null;
        
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
                        $db_avatar = $row['profile_picture'];
                        echo '<p class="info">📊 資料庫查詢結果:</p>';
                        echo '<p><strong>profile_picture 欄位值:</strong> ' . ($db_avatar ?: 'NULL/空值') . '</p>';
                        
                        if (!empty($db_avatar)) {
                            if (filter_var($db_avatar, FILTER_VALIDATE_URL)) {
                                $avatar_src = $db_avatar;
                                echo '<p class="success">✅ 使用完整URL頭像</p>';
                            } else {
                                $avatar_src = $base_path . $db_avatar;
                                echo '<p class="info">ℹ️ 使用相對路徑頭像</p>';
                            }
                        } else {
                            echo '<p class="warning">⚠️ 資料庫中沒有頭像，使用預設頭像</p>';
                        }
                    } else {
                        echo '<p class="error">❌ 找不到用戶資料</p>';
                    }
                    $conn->close();
                } else {
                    echo '<p class="error">❌ 資料庫連接失敗</p>';
                }
            } catch (Exception $e) {
                echo '<p class="error">❌ 資料庫查詢錯誤: ' . $e->getMessage() . '</p>';
            }
        } else {
            echo '<p class="warning">⚠️ 未登入，使用預設頭像</p>';
        }
        ?>
    </div>
    
    <div class="debug-section">
        <h3>頭像顯示測試</h3>
        <p><strong>最終使用的頭像路徑:</strong></p>
        <div class="path-test"><?php echo htmlspecialchars($avatar_src); ?></div>
        
        <h4>大尺寸頭像測試 (100x100px):</h4>
        <img src="<?php echo htmlspecialchars($avatar_src); ?>" 
             alt="大頭像測試" 
             class="avatar-test"
             onerror="this.style.border='3px solid red'; this.alt='載入失敗'; console.log('大頭像載入失敗:', this.src);">
        
        <h4>小尺寸頭像測試 (40x40px - 與導航列相同):</h4>
        <img src="<?php echo htmlspecialchars($avatar_src); ?>" 
             alt="小頭像測試" 
             class="avatar-small"
             onerror="this.style.border='3px solid red'; this.alt='載入失敗'; console.log('小頭像載入失敗:', this.src);">
        
        <h4>原始尺寸顯示:</h4>
        <img src="<?php echo htmlspecialchars($avatar_src); ?>" 
             alt="原始尺寸測試"
             onerror="this.style.border='3px solid red'; this.alt='載入失敗'; console.log('原始頭像載入失敗:', this.src);">
    </div>
    
    <div class="debug-section">
        <h3>HTTP 狀態檢查</h3>
        <div id="httpStatus"></div>
        <button onclick="checkHttpStatus()">檢查HTTP狀態</button>
    </div>
    
    <div class="debug-section">
        <h3>替代路徑測試</h3>
        <?php
        $alternative_paths = [
            'http://localhost' . $full_path,
            'http://localhost/Topics-frontend/frontend/share/EIdROxGXsAE_LSs.jpg',
            './share/EIdROxGXsAE_LSs.jpg',
            'share/EIdROxGXsAE_LSs.jpg'
        ];
        
        foreach ($alternative_paths as $i => $path) {
            echo '<h4>路徑 ' . ($i + 1) . ':</h4>';
            echo '<div class="path-test">' . htmlspecialchars($path) . '</div>';
            echo '<img src="' . htmlspecialchars($path) . '" alt="測試' . ($i + 1) . '" class="avatar-small" onerror="this.style.border=\'3px solid red\'; this.alt=\'載入失敗\'; console.log(\'路徑' . ($i + 1) . '載入失敗:\', this.src);">';
        }
        ?>
    </div>
    
    <div class="debug-section info">
        <h3>調試說明</h3>
        <p>1. 檢查檔案是否存在於正確路徑</p>
        <p>2. 檢查資料庫中的頭像設定</p>
        <p>3. 測試不同路徑格式</p>
        <p>4. 檢查HTTP狀態碼</p>
        <p>5. 查看瀏覽器控制台的錯誤訊息</p>
    </div>

    <script>
        function checkHttpStatus() {
            const avatarSrc = '<?php echo $avatar_src; ?>';
            const statusDiv = document.getElementById('httpStatus');
            
            statusDiv.innerHTML = '<p>正在檢查HTTP狀態...</p>';
            
            fetch(avatarSrc, { method: 'HEAD' })
                .then(response => {
                    statusDiv.innerHTML = `
                        <p><strong>HTTP狀態碼:</strong> ${response.status}</p>
                        <p><strong>狀態文字:</strong> ${response.statusText}</p>
                        <p><strong>Content-Type:</strong> ${response.headers.get('content-type') || '未知'}</p>
                        <p><strong>Content-Length:</strong> ${response.headers.get('content-length') || '未知'}</p>
                    `;
                })
                .catch(error => {
                    statusDiv.innerHTML = `<p class="error">❌ 檢查失敗: ${error.message}</p>`;
                });
        }
        
        // 頁面載入時自動檢查
        window.onload = function() {
            console.log('頭像調試頁面載入完成');
            console.log('最終頭像路徑:', '<?php echo $avatar_src; ?>');
            
            // 檢查所有圖片載入狀態
            const images = document.querySelectorAll('img');
            images.forEach((img, index) => {
                img.addEventListener('load', () => {
                    console.log(`圖片 ${index + 1} 載入成功:`, img.src);
                });
                img.addEventListener('error', () => {
                    console.log(`圖片 ${index + 1} 載入失敗:`, img.src);
                });
            });
        };
    </script>
</body>
</html>
