<?php
// 載入 session 配置
require_once 'session_config.php';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>逐步頭像調試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .step { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 10px; 
            margin: 20px 0; 
            border-left: 5px solid #007bff;
        }
        .success { border-left-color: #28a745; background: #d4edda; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .warning { border-left-color: #ffc107; background: #fff3cd; }
        .info { border-left-color: #17a2b8; background: #d1ecf1; }
        .avatar { 
            width: 50px; 
            height: 50px; 
            border-radius: 50%; 
            border: 3px solid #007bff; 
            margin: 10px;
            object-fit: cover;
        }
        .debug { 
            background: #e9ecef; 
            padding: 10px; 
            border-radius: 5px; 
            margin: 10px 0; 
            font-family: monospace; 
            font-size: 12px;
            white-space: pre-wrap;
        }
        .test-container {
            border: 2px solid #dee2e6;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <h1>🔍 逐步頭像調試</h1>
    
    <div class="step">
        <h3>步驟 1: 檢查登入狀態</h3>
        <?php
        $isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['username']);
        ?>
        <div class="debug">
登入狀態檢查:
- $_SESSION['logged_in']: <?php echo isset($_SESSION['logged_in']) ? ($_SESSION['logged_in'] ? 'true' : 'false') : '未設定'; ?>
- $_SESSION['username']: <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : '未設定'; ?>
- $isLoggedIn: <?php echo $isLoggedIn ? 'true' : 'false'; ?>
        </div>
        
        <?php if ($isLoggedIn): ?>
            <div class="success">
                <p>✅ 登入狀態正常</p>
                <p>用戶名: <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            </div>
        <?php else: ?>
            <div class="error">
                <p>❌ 登入狀態異常</p>
                <p>這就是頭像不顯示的原因！</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="step">
        <h3>步驟 2: 檢查檔案存在性</h3>
        <?php
        $avatar_file = __DIR__ . '/share/EIdROxGXsAE_LSs.jpg';
        $file_exists = file_exists($avatar_file);
        ?>
        <div class="debug">
檔案檢查:
- 檔案路徑: <?php echo $avatar_file; ?>
- 檔案存在: <?php echo $file_exists ? '是' : '否'; ?>
<?php if ($file_exists): ?>
- 檔案大小: <?php echo filesize($avatar_file); ?> bytes
- 最後修改: <?php echo date('Y-m-d H:i:s', filemtime($avatar_file)); ?>
<?php endif; ?>
        </div>
        
        <?php if ($file_exists): ?>
            <div class="success">
                <p>✅ 預設頭像檔案存在</p>
            </div>
        <?php else: ?>
            <div class="error">
                <p>❌ 預設頭像檔案不存在</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="step">
        <h3>步驟 3: 測試不同路徑</h3>
        <div class="test-container">
            <h4>路徑測試結果:</h4>
            <?php
            $test_paths = [
                './share/EIdROxGXsAE_LSs.jpg',
                'share/EIdROxGXsAE_LSs.jpg',
                '/Topics-frontend/frontend/share/EIdROxGXsAE_LSs.jpg',
                'http://localhost/Topics-frontend/frontend/share/EIdROxGXsAE_LSs.jpg'
            ];
            
            foreach ($test_paths as $i => $path) {
                echo '<div style="margin: 10px 0; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">';
                echo '<strong>路徑 ' . ($i + 1) . ':</strong> ' . htmlspecialchars($path) . '<br>';
                echo '<img src="' . htmlspecialchars($path) . '" alt="測試' . ($i + 1) . '" class="avatar" ';
                echo 'onload="console.log(\'路徑' . ($i + 1) . '載入成功\')" ';
                echo 'onerror="console.log(\'路徑' . ($i + 1) . '載入失敗\'); this.style.border=\'3px solid red\';" ';
                echo 'style="margin: 5px;">';
                echo '</div>';
            }
            ?>
        </div>
    </div>
    
    <div class="step">
        <h3>步驟 4: 模擬 Header 邏輯</h3>
        <?php if ($isLoggedIn): ?>
            <?php
            // 模擬 header.php 中的頭像邏輯
            $avatar_src = './share/EIdROxGXsAE_LSs.jpg'; // 預設頭像
            if (isset($_SESSION['username'])) {
                try {
                    $configPath = dirname(__DIR__) . '/config.php';
                    if (file_exists($configPath)) {
                        require_once $configPath;
                        $conn = getDatabaseConnection();
                    } else {
                        $conn = null;
                    }
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
                                    $avatar_src = './share/' . $row['profile_picture'];
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
            <div class="debug">
Header 邏輯模擬:
- 最終頭像路徑: <?php echo $avatar_src; ?>
- 用戶名: <?php echo $_SESSION['username']; ?>
            </div>
            
            <div class="test-container">
                <h4>Header 模擬結果:</h4>
                <div style="background: rgba(217, 229, 234, 0.95); padding: 15px; border-radius: 8px; display: flex; align-items: center; justify-content: space-between;">
                    <div style="font-weight: bold; color: #2c3e50;">康寧大學招生平台</div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="color: #2c3e50;">歡迎，<?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <div style="position: relative;">
                            <img src="<?php echo htmlspecialchars($avatar_src); ?>" 
                                 alt="用戶頭像" 
                                 class="avatar"
                                 onload="console.log('Header模擬頭像載入成功')"
                                 onerror="console.log('Header模擬頭像載入失敗'); this.style.border='3px solid red';">
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="warning">
                <p>⚠️ 未登入，無法模擬 Header 邏輯</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="step">
        <h3>步驟 5: 實際 Header 組件測試</h3>
        <?php if ($isLoggedIn): ?>
            <div class="test-container">
                <h4>實際 Header 組件:</h4>
                <div style="border: 2px solid #007bff; padding: 10px; border-radius: 8px;">
                    <?php include 'share/header.php'; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="warning">
                <p>⚠️ 未登入，Header 組件不會顯示頭像區域</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="step">
        <h3>步驟 6: 瀏覽器檢查</h3>
        <div class="info">
            <p>請在瀏覽器中執行以下檢查：</p>
            <ol>
                <li>按 F12 打開開發者工具</li>
                <li>查看 Console 標籤是否有錯誤訊息</li>
                <li>查看 Network 標籤，重新載入頁面，檢查是否有 404 錯誤</li>
                <li>查看 Elements 標籤，檢查 img 標籤是否存在</li>
            </ol>
        </div>
    </div>
    
    <div style="margin-top: 30px; text-align: center;">
        <a href="index.php" style="color: #007bff; text-decoration: none; margin: 0 10px;">🏠 返回首頁</a>
        <a href="check_login_status.php" style="color: #007bff; text-decoration: none; margin: 0 10px;">🔐 檢查登入</a>
    </div>

    <script>
        window.onload = function() {
            console.log('🔍 逐步頭像調試頁面載入完成');
            
            // 檢查所有圖片
            const images = document.querySelectorAll('img');
            console.log('找到 ' + images.length + ' 個圖片元素');
            
            images.forEach((img, index) => {
                img.addEventListener('load', () => {
                    console.log(`✅ 圖片 ${index + 1} 載入成功:`, img.src);
                });
                
                img.addEventListener('error', () => {
                    console.log(`❌ 圖片 ${index + 1} 載入失敗:`, img.src);
                });
            });
        };
    </script>
</body>
</html>
