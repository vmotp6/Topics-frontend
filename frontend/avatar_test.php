<?php
// 載入 session 配置
require_once 'session_config.php';

// 獲取當前域名和端口
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . '://' . $host;
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>頭像測試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; text-align: center; }
        .avatar-test { 
            width: 100px; 
            height: 100px; 
            border-radius: 50%; 
            border: 3px solid #007bff; 
            margin: 20px;
            object-fit: cover;
        }
        .avatar-nav { 
            width: 40px; 
            height: 40px; 
            border-radius: 50%; 
            border: 3px solid white; 
            margin: 10px;
            object-fit: cover;
        }
        .test-section { 
            background: #f5f5f5; 
            padding: 20px; 
            border-radius: 10px; 
            margin: 20px 0; 
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        .path-info { 
            background: #e9ecef; 
            padding: 10px; 
            border-radius: 5px; 
            margin: 10px 0; 
            font-family: monospace; 
            word-break: break-all;
        }
    </style>
</head>
<body>
    <h1>頭像顯示測試</h1>
    
    <div class="test-section">
        <h3>路徑資訊</h3>
        <div class="path-info">
            <strong>基礎URL:</strong> <?php echo $base_url; ?><br>
            <strong>完整頭像路徑:</strong> <?php echo $base_url . '/Topics-frontend/frontend/share/EIdROxGXsAE_LSs.jpg'; ?>
        </div>
    </div>
    
    <div class="test-section">
        <h3>登入狀態</h3>
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <p class="success">✅ 已登入 - <?php echo $_SESSION['username']; ?></p>
        <?php else: ?>
            <p class="error">❌ 未登入</p>
        <?php endif; ?>
    </div>
    
    <div class="test-section">
        <h3>頭像顯示測試</h3>
        
        <h4>1. 預設頭像 (100x100px)</h4>
        <img src="<?php echo $base_url; ?>/Topics-frontend/frontend/share/EIdROxGXsAE_LSs.jpg" 
             alt="預設頭像" 
             class="avatar-test"
             onload="console.log('預設頭像載入成功')"
             onerror="console.log('預設頭像載入失敗'); this.style.border='3px solid red';">
        
        <h4>2. 導航列尺寸頭像 (40x40px)</h4>
        <img src="<?php echo $base_url; ?>/Topics-frontend/frontend/share/EIdROxGXsAE_LSs.jpg" 
             alt="導航列頭像" 
             class="avatar-nav"
             onload="console.log('導航列頭像載入成功')"
             onerror="console.log('導航列頭像載入失敗'); this.style.border='3px solid red';">
        
        <h4>3. 其他可用頭像</h4>
        <img src="<?php echo $base_url; ?>/Topics-frontend/frontend/share/EMdrrheUEAAGkC4.jpg" 
             alt="頭像2" 
             class="avatar-test"
             onload="console.log('頭像2載入成功')"
             onerror="console.log('頭像2載入失敗'); this.style.border='3px solid red';">
        
        <img src="<?php echo $base_url; ?>/Topics-frontend/frontend/share/ESmOf3yU8AA12sp.jpg" 
             alt="頭像3" 
             class="avatar-test"
             onload="console.log('頭像3載入成功')"
             onerror="console.log('頭像3載入失敗'); this.style.border='3px solid red';">
    </div>
    
    <div class="test-section">
        <h3>Header組件測試</h3>
        <p>測試導航列中的頭像顯示：</p>
        <div style="border: 2px solid #ccc; padding: 20px; margin: 20px 0;">
            <?php include 'share/header.php'; ?>
        </div>
    </div>
    
    <div class="test-section info">
        <h3>測試說明</h3>
        <p>1. 如果頭像正常顯示，表示路徑配置正確</p>
        <p>2. 如果頭像無法顯示，請檢查瀏覽器控制台的錯誤訊息</p>
        <p>3. 導航列中的頭像應該與測試圖片相同</p>
        <p>4. 如果所有圖片都無法顯示，可能是伺服器配置問題</p>
    </div>
    
    <div style="margin-top: 30px;">
        <a href="index.php" style="color: #007bff; text-decoration: none;">← 返回首頁</a> | 
        <a href="avatar_debug.php" style="color: #007bff; text-decoration: none;">詳細調試</a>
    </div>

    <script>
        // 頁面載入時檢查所有圖片
        window.onload = function() {
            console.log('頭像測試頁面載入完成');
            console.log('基礎URL:', '<?php echo $base_url; ?>');
            
            const images = document.querySelectorAll('img');
            let loadedCount = 0;
            let failedCount = 0;
            
            images.forEach((img, index) => {
                img.addEventListener('load', () => {
                    loadedCount++;
                    console.log(`圖片 ${index + 1} 載入成功:`, img.src);
                });
                
                img.addEventListener('error', () => {
                    failedCount++;
                    console.log(`圖片 ${index + 1} 載入失敗:`, img.src);
                });
            });
            
            // 3秒後顯示統計
            setTimeout(() => {
                console.log(`圖片載入統計: 成功 ${loadedCount} 個, 失敗 ${failedCount} 個`);
            }, 3000);
        };
    </script>
</body>
</html>
