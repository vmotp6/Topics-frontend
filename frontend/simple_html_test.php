<?php
// 載入 session 配置
require_once 'session_config.php';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>簡單HTML測試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .avatar { 
            width: 50px; 
            height: 50px; 
            border-radius: 50%; 
            border: 3px solid #007bff; 
            margin: 10px;
            object-fit: cover;
        }
        .test-box { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 10px; 
            margin: 20px 0; 
            border: 2px solid #dee2e6;
        }
    </style>
</head>
<body>
    <h1>🧪 簡單HTML測試</h1>
    
    <div class="test-box">
        <h3>登入狀態</h3>
        <?php
        $isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['username']);
        echo $isLoggedIn ? '✅ 已登入' : '❌ 未登入';
        if ($isLoggedIn) {
            echo '<br>用戶名: ' . htmlspecialchars($_SESSION['username']);
        }
        ?>
    </div>
    
    <div class="test-box">
        <h3>直接HTML測試</h3>
        <p>如果這個頭像能顯示，說明路徑沒問題：</p>
        <img src="./share/EIdROxGXsAE_LSs.jpg" alt="直接測試" class="avatar">
    </div>
    
    <?php if ($isLoggedIn): ?>
    <div class="test-box">
        <h3>模擬Header結構</h3>
        <p>完全模擬header.php的HTML結構：</p>
        <div style="background: rgba(217, 229, 234, 0.95); padding: 15px; border-radius: 8px; display: flex; align-items: center; justify-content: space-between;">
            <div style="font-weight: bold; color: #2c3e50;">康寧大學招生平台</div>
            <div class="user-dropdown" style="position: relative;">
                <div class="avatar-btn" style="cursor: pointer; display: flex; align-items: center; justify-content: center; position: relative; padding: 0; width: 50px; height: 50px; border-radius: 50%; transition: all 0.3s ease;">
                    <img src="./share/EIdROxGXsAE_LSs.jpg" alt="頭像" class="avatar-img" style="width: 40px; height: 40px; border-radius: 50%; border: 3px solid white; background-color: #ffffff; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); object-fit: cover;">
                </div>
            </div>
        </div>
    </div>
    
    <div class="test-box">
        <h3>實際Header組件</h3>
        <p>包含實際的header.php：</p>
        <div style="border: 2px solid #007bff; padding: 10px; border-radius: 8px;">
            <?php include 'share/header.php'; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="test-box">
        <h3>⚠️ 需要登入</h3>
        <p>請先登入後再測試頭像顯示</p>
        <a href="test_google_login.php" style="color: #007bff;">🔐 Google 登入</a>
    </div>
    <?php endif; ?>
    
    <div style="margin-top: 30px; text-align: center;">
        <a href="index.php" style="color: #007bff; text-decoration: none; margin: 0 10px;">🏠 返回首頁</a>
    </div>

    <script>
        window.onload = function() {
            console.log('🧪 簡單HTML測試頁面載入完成');
            
            // 檢查所有圖片
            const images = document.querySelectorAll('img');
            console.log('找到 ' + images.length + ' 個圖片元素');
            
            images.forEach((img, index) => {
                console.log(`圖片 ${index + 1}:`, img.src);
                
                img.addEventListener('load', () => {
                    console.log(`✅ 圖片 ${index + 1} 載入成功`);
                });
                
                img.addEventListener('error', () => {
                    console.log(`❌ 圖片 ${index + 1} 載入失敗`);
                });
            });
        };
    </script>
</body>
</html>
