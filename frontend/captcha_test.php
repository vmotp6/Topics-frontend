<?php
/**
 * 驗證碼測試頁面
 */

session_start();

echo "<!DOCTYPE html>
<html lang='zh-Hant'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>驗證碼測試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .captcha-container { display: flex; align-items: center; gap: 10px; margin: 10px 0; flex-wrap: wrap; }
        .captcha-container input { width: 100px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; text-align: center; font-size: 16px; font-weight: bold; letter-spacing: 2px; height: 40px; box-sizing: border-box; }
        .captcha-container img { height: 40px; width: 120px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; }
        .refresh-btn { background: #007bff; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; height: 40px; }
        .refresh-btn:hover { background: #0056b3; }
        .result { margin-top: 10px; padding: 10px; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    </style>
</head>
<body>";

echo "<div class='container'>";
echo "<h1>🔐 驗證碼系統測試</h1>";

// 測試1: 原始驗證碼
echo "<div class='test-section'>";
echo "<h3>測試1: 原始驗證碼 (captcha.php)</h3>";
echo "<div class='captcha-container'>";
echo "<label>驗證碼:</label>";
echo "<input type='text' id='captcha1' placeholder='請輸入驗證碼' maxlength='4'>";
echo "<img src='captcha.php' id='captcha-image1' alt='驗證碼' onclick='refreshCaptcha1()'>";
echo "<button type='button' onclick='refreshCaptcha1()' class='refresh-btn'>刷新</button>";
echo "</div>";
echo "<button onclick='testCaptcha1()'>測試驗證</button>";
echo "<div id='result1' class='result' style='display:none;'></div>";
echo "</div>";

// 測試2: 簡單驗證碼
echo "<div class='test-section'>";
echo "<h3>測試2: 簡單驗證碼 (simple_captcha.php)</h3>";
echo "<div class='captcha-container'>";
echo "<label>驗證碼:</label>";
echo "<input type='text' id='captcha2' placeholder='請輸入驗證碼' maxlength='4'>";
echo "<img src='simple_captcha.php' id='captcha-image2' alt='驗證碼' onclick='refreshCaptcha2()'>";
echo "<button type='button' onclick='refreshCaptcha2()' class='refresh-btn'>刷新</button>";
echo "</div>";
echo "<button onclick='testCaptcha2()'>測試驗證</button>";
echo "<div id='result2' class='result' style='display:none;'></div>";
echo "</div>";

// 系統信息
echo "<div class='test-section'>";
echo "<h3>系統信息</h3>";
echo "<div class='info'>";
echo "<p><strong>PHP版本:</strong> " . phpversion() . "</p>";
echo "<p><strong>GD擴展:</strong> " . (extension_loaded('gd') ? '✅ 已啟用' : '❌ 未啟用') . "</p>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>當前驗證碼:</strong> " . ($_SESSION['captcha_code'] ?? '無') . "</p>";
echo "</div>";
echo "</div>";

echo "<p><a href='cooperation_upload.php'>返回表單</a></p>";
echo "</div>";

echo "
<script>
function refreshCaptcha1() {
    const captchaImage = document.getElementById('captcha-image1');
    captchaImage.src = 'captcha.php?' + Math.random();
    document.getElementById('captcha1').value = '';
    document.getElementById('result1').style.display = 'none';
}

function refreshCaptcha2() {
    const captchaImage = document.getElementById('captcha-image2');
    captchaImage.src = 'simple_captcha.php?' + Math.random();
    document.getElementById('captcha2').value = '';
    document.getElementById('result2').style.display = 'none';
}

function testCaptcha1() {
    const input = document.getElementById('captcha1').value;
    const result = document.getElementById('result1');
    
    if (!input) {
        result.className = 'result error';
        result.textContent = '請輸入驗證碼';
        result.style.display = 'block';
        return;
    }
    
    // 這裡應該發送AJAX請求到後端驗證
    result.className = 'result info';
    result.textContent = '輸入的驗證碼: ' + input + ' (需要後端驗證)';
    result.style.display = 'block';
}

function testCaptcha2() {
    const input = document.getElementById('captcha2').value;
    const result = document.getElementById('result2');
    
    if (!input) {
        result.className = 'result error';
        result.textContent = '請輸入驗證碼';
        result.style.display = 'block';
        return;
    }
    
    // 這裡應該發送AJAX請求到後端驗證
    result.className = 'result info';
    result.textContent = '輸入的驗證碼: ' + input + ' (需要後端驗證)';
    result.style.display = 'block';
}
</script>
</body>
</html>";
?>
