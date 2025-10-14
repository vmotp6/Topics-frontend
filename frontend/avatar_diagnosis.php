<?php
// 載入 session 配置
require_once 'session_config.php';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>頭像診斷</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .diagnosis-section { 
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
        .avatar-test { 
            width: 50px; 
            height: 50px; 
            border-radius: 50%; 
            border: 3px solid #007bff; 
            margin: 10px;
            object-fit: cover;
        }
        .debug-info { 
            background: #e9ecef; 
            padding: 10px; 
            border-radius: 5px; 
            margin: 10px 0; 
            font-family: monospace; 
            font-size: 12px;
        }
        .header-preview {
            background: rgba(217, 229, 234, 0.95);
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <h1>🔍 頭像診斷工具</h1>
    
    <div class="diagnosis-section">
        <h3>📊 登入狀態檢查</h3>
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <div class="success">
                <p>✅ <strong>已登入</strong></p>
                <p><strong>用戶名:</strong> <?php echo htmlspecialchars($_SESSION['username'] ?? '未知'); ?></p>
                <p><strong>角色:</strong> <?php echo htmlspecialchars($_SESSION['role'] ?? '未知'); ?></p>
                <p><strong>登入方式:</strong> <?php echo htmlspecialchars($_SESSION['login_method'] ?? '未知'); ?></p>
            </div>
        <?php else: ?>
            <div class="error">
                <p>❌ <strong>未登入</strong></p>
                <p>如果未登入，頭像區域不會顯示。請先登入後再檢查頭像。</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="diagnosis-section">
        <h3>🖼️ 頭像路徑檢查</h3>
        <?php
        $avatar_src = './share/EIdROxGXsAE_LSs.jpg';
        $file_exists = file_exists(__DIR__ . '/share/EIdROxGXsAE_LSs.jpg');
        ?>
        <div class="debug-info">
            <strong>頭像路徑:</strong> <?php echo $avatar_src; ?><br>
            <strong>實際檔案路徑:</strong> <?php echo __DIR__ . '/share/EIdROxGXsAE_LSs.jpg'; ?><br>
            <strong>檔案存在:</strong> <?php echo $file_exists ? '✅ 是' : '❌ 否'; ?>
        </div>
        
        <?php if ($file_exists): ?>
            <div class="success">
                <p>✅ 預設頭像檔案存在</p>
                <img src="<?php echo $avatar_src; ?>" alt="預設頭像" class="avatar-test">
            </div>
        <?php else: ?>
            <div class="error">
                <p>❌ 預設頭像檔案不存在</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="diagnosis-section">
        <h3>🎯 頭像顯示測試</h3>
        <p>測試不同路徑的頭像：</p>
        
        <div style="display: flex; gap: 20px; margin: 20px 0;">
            <div>
                <h4>相對路徑</h4>
                <img src="./share/EIdROxGXsAE_LSs.jpg" alt="相對路徑" class="avatar-test" 
                     onload="console.log('相對路徑頭像載入成功')"
                     onerror="console.log('相對路徑頭像載入失敗'); this.style.border='3px solid red';">
            </div>
            
            <div>
                <h4>絕對路徑</h4>
                <img src="/Topics-frontend/frontend/share/EIdROxGXsAE_LSs.jpg" alt="絕對路徑" class="avatar-test"
                     onload="console.log('絕對路徑頭像載入成功')"
                     onerror="console.log('絕對路徑頭像載入失敗'); this.style.border='3px solid red';">
            </div>
            
            <div>
                <h4>完整URL</h4>
                <img src="http://localhost/Topics-frontend/frontend/share/EIdROxGXsAE_LSs.jpg" alt="完整URL" class="avatar-test"
                     onload="console.log('完整URL頭像載入成功')"
                     onerror="console.log('完整URL頭像載入失敗'); this.style.border='3px solid red';">
            </div>
        </div>
    </div>
    
    <div class="diagnosis-section">
        <h3>🧭 導航列預覽</h3>
        <p>模擬實際的導航列頭像顯示：</p>
        
        <div class="header-preview">
            <div style="font-weight: bold; color: #2c3e50;">康寧大學招生平台</div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                    <span style="color: #2c3e50;">歡迎，<?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <div style="position: relative;">
                        <img src="<?php echo $avatar_src; ?>" 
                             alt="用戶頭像" 
                             class="avatar-test"
                             onload="console.log('導航列頭像載入成功')"
                             onerror="console.log('導航列頭像載入失敗'); this.style.border='3px solid red';">
                    </div>
                <?php else: ?>
                    <span style="color: #6c757d;">未登入</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="diagnosis-section">
        <h3>🔧 資料庫頭像查詢</h3>
        <?php if (isset($_SESSION['username'])): ?>
            <?php
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
                        echo '<div class="info">';
                        echo '<p>📊 資料庫查詢結果:</p>';
                        echo '<p><strong>profile_picture 欄位值:</strong> ' . ($db_avatar ?: 'NULL/空值') . '</p>';
                        
                        if (!empty($db_avatar)) {
                            if (filter_var($db_avatar, FILTER_VALIDATE_URL)) {
                                echo '<p>✅ 使用完整URL頭像</p>';
                                echo '<img src="' . htmlspecialchars($db_avatar) . '" alt="資料庫頭像" class="avatar-test">';
                            } else {
                                echo '<p>ℹ️ 使用相對路徑頭像</p>';
                                echo '<img src="./share/' . htmlspecialchars($db_avatar) . '" alt="資料庫頭像" class="avatar-test">';
                            }
                        } else {
                            echo '<p>⚠️ 資料庫中沒有頭像，使用預設頭像</p>';
                        }
                        echo '</div>';
                    } else {
                        echo '<div class="error"><p>❌ 找不到用戶資料</p></div>';
                    }
                    $conn->close();
                } else {
                    echo '<div class="error"><p>❌ 資料庫連接失敗</p></div>';
                }
            } catch (Exception $e) {
                echo '<div class="error"><p>❌ 資料庫查詢錯誤: ' . $e->getMessage() . '</p></div>';
            }
            ?>
        <?php else: ?>
            <div class="warning">
                <p>⚠️ 未登入，無法查詢資料庫頭像</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="diagnosis-section">
        <h3>📋 診斷結果</h3>
        <div id="diagnosisResult">
            <p>正在診斷中...</p>
        </div>
    </div>
    
    <div style="margin-top: 30px; text-align: center;">
        <a href="index.php" style="color: #007bff; text-decoration: none; margin: 0 10px;">🏠 返回首頁</a>
        <a href="test_google_login.php" style="color: #007bff; text-decoration: none; margin: 0 10px;">🔐 測試登入</a>
    </div>

    <script>
        window.onload = function() {
            console.log('🔍 頭像診斷頁面載入完成');
            
            // 檢查所有圖片載入狀態
            const images = document.querySelectorAll('img');
            let loadedCount = 0;
            let failedCount = 0;
            
            images.forEach((img, index) => {
                img.addEventListener('load', () => {
                    loadedCount++;
                    console.log(`✅ 圖片 ${index + 1} 載入成功:`, img.src);
                });
                
                img.addEventListener('error', () => {
                    failedCount++;
                    console.log(`❌ 圖片 ${index + 1} 載入失敗:`, img.src);
                });
            });
            
            // 3秒後顯示診斷結果
            setTimeout(() => {
                const resultDiv = document.getElementById('diagnosisResult');
                let result = '';
                
                if (failedCount === 0) {
                    result = '<div class="success"><p>🎉 所有頭像測試通過！頭像應該能正常顯示。</p></div>';
                } else if (loadedCount > 0) {
                    result = '<div class="warning"><p>⚠️ 部分頭像載入失敗。請檢查路徑配置。</p></div>';
                } else {
                    result = '<div class="error"><p>❌ 所有頭像載入失敗。請檢查檔案路徑和伺服器配置。</p></div>';
                }
                
                result += `<p><strong>統計:</strong> 成功 ${loadedCount} 個, 失敗 ${failedCount} 個</p>`;
                resultDiv.innerHTML = result;
                
                console.log(`📊 診斷完成: 成功 ${loadedCount}/${images.length}, 失敗 ${failedCount}/${images.length}`);
            }, 3000);
        };
    </script>
</body>
</html>
