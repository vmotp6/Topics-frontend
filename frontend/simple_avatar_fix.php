<?php
// 簡單的頭像修正方案
// 載入 session 配置
require_once 'session_config.php';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>簡單頭像修正</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .avatar { width: 50px; height: 50px; border-radius: 50%; border: 2px solid #007bff; }
        .test-section { background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>簡單頭像修正測試</h1>
    
    <div class="test-section">
        <h3>測試不同的頭像路徑</h3>
        
        <h4>1. 相對路徑 (./share/)</h4>
        <img src="./share/EIdROxGXsAE_LSs.jpg" alt="相對路徑" class="avatar" onerror="this.style.border='2px solid red';">
        
        <h4>2. 相對路徑 (share/)</h4>
        <img src="share/EIdROxGXsAE_LSs.jpg" alt="相對路徑2" class="avatar" onerror="this.style.border='2px solid red';">
        
        <h4>3. 絕對路徑</h4>
        <img src="/Topics-frontend/frontend/share/EIdROxGXsAE_LSs.jpg" alt="絕對路徑" class="avatar" onerror="this.style.border='2px solid red';">
        
        <h4>4. 完整URL</h4>
        <img src="http://localhost/Topics-frontend/frontend/share/EIdROxGXsAE_LSs.jpg" alt="完整URL" class="avatar" onerror="this.style.border='2px solid red';">
    </div>
    
    <div class="test-section">
        <h3>修正後的Header組件</h3>
        <p>使用相對路徑的頭像：</p>
        <div style="border: 1px solid #ccc; padding: 10px;">
            <?php
            // 模擬header中的頭像顯示
            $avatar_src = './share/EIdROxGXsAE_LSs.jpg'; // 使用相對路徑
            ?>
            <img src="<?php echo $avatar_src; ?>" alt="修正後頭像" class="avatar" onerror="this.style.border='2px solid red';">
        </div>
    </div>
    
    <div class="test-section">
        <h3>建議的修正方案</h3>
        <p>將 header.php 中的頭像路徑改為相對路徑：</p>
        <pre style="background: #e9ecef; padding: 10px; border-radius: 5px;">
$avatar_src = './share/EIdROxGXsAE_LSs.jpg'; // 預設頭像
        </pre>
    </div>
</body>
</html>
