<?php
/**
 * 測試驗證碼圖片是否正常顯示
 */
require_once 'session_config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>驗證碼測試</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 600px;
            margin: 0 auto;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .captcha-test {
            margin: 20px 0;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
        }
        img {
            border: 2px solid #ccc;
            padding: 5px;
            background: white;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>驗證碼圖片測試</h1>
        
        <div class="info">
            <h3>系統資訊：</h3>
            <ul>
                <li>GD 擴展：<?php echo extension_loaded('gd') ? '<span style="color:green">✓ 已啟用</span>' : '<span style="color:red">✗ 未啟用</span>'; ?></li>
                <li>Session ID：<?php echo session_id(); ?></li>
                <li>Session 狀態：<?php echo session_status() === PHP_SESSION_ACTIVE ? '活動中' : '未啟動'; ?></li>
                <li>當前驗證碼（Session）：<?php echo isset($_SESSION['captcha_code']) ? $_SESSION['captcha_code'] : '未生成'; ?></li>
            </ul>
        </div>

        <div class="captcha-test">
            <h3>驗證碼圖片：</h3>
            <img src="captcha_image.php?t=<?php echo time(); ?>" alt="驗證碼" style="height: 50px; width: 150px;">
            <br><br>
            <button onclick="location.reload()" style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer;">
                刷新頁面
            </button>
        </div>

        <div class="info">
            <h3>測試說明：</h3>
            <ol>
                <li>如果看到驗證碼圖片，說明系統正常</li>
                <li>如果圖片無法顯示，請檢查：
                    <ul>
                        <li>GD 擴展是否已啟用</li>
                        <li>Session 是否正常工作</li>
                        <li>檔案路徑是否正確</li>
                        <li>瀏覽器控制台是否有錯誤訊息</li>
                    </ul>
                </li>
            </ol>
        </div>
    </div>
</body>
</html>

