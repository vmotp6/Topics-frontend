<?php
/**
 * 最終驗證碼測試頁面
 */

session_start();

// 生成初始驗證碼
if (!isset($_SESSION['captcha_code'])) {
    $captcha_code = '';
    for ($i = 0; $i < 4; $i++) {
        $captcha_code .= rand(0, 9);
    }
    $_SESSION['captcha_code'] = $captcha_code;
}

echo "<!DOCTYPE html>
<html lang='zh-Hant'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>最終驗證碼測試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .captcha-container { display: flex; align-items: center; gap: 10px; margin: 10px 0; flex-wrap: wrap; }
        .captcha-container input { width: 100px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; text-align: center; font-size: 16px; font-weight: bold; letter-spacing: 2px; height: 40px; box-sizing: border-box; }
        .captcha-display { height: 40px; width: 120px; border: 2px solid #ccc; border-radius: 4px; display: flex; align-items: center; justify-content: center; background: linear-gradient(45deg, #f0f0f0, #e0e0e0); font-size: 20px; font-weight: bold; color: #333; font-family: 'Courier New', monospace; letter-spacing: 3px; text-shadow: 1px 1px 2px rgba(0,0,0,0.1); box-shadow: inset 0 1px 3px rgba(0,0,0,0.1); cursor: pointer; user-select: none; position: relative; overflow: hidden; }
        .captcha-display:hover { background: linear-gradient(45deg, #e8e8e8, #d8d8d8); border-color: #999; }
        .captcha-display::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent); animation: shine 2s infinite; }
        @keyframes shine { 0% { left: -100%; } 100% { left: 100%; } }
        .captcha-display::after { content: ''; position: absolute; top: 50%; left: 10%; right: 10%; height: 1px; background: rgba(0,0,0,0.1); transform: rotate(-15deg); }
        .refresh-btn { background: #007bff; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; height: 40px; }
        .refresh-btn:hover { background: #0056b3; }
        .result { margin-top: 10px; padding: 10px; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .btn { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #1e7e34; }
    </style>
</head>
<body>";

echo "<div class='container'>";
echo "<h1>🔐 最終驗證碼系統測試</h1>";

// 測試表單
echo "<div class='test-section'>";
echo "<h3>內嵌驗證碼測試</h3>";
echo "<div class='captcha-container'>";
echo "<label>驗證碼:</label>";
echo "<input type='text' id='captcha' placeholder='請輸入驗證碼' maxlength='4'>";
echo "<div id='captcha-display' class='captcha-display' onclick='refreshCaptcha()'>" . $_SESSION['captcha_code'] . "</div>";
echo "<button type='button' onclick='refreshCaptcha()' class='refresh-btn'>刷新</button>";
echo "</div>";
echo "<button onclick='testCaptcha()' class='btn'>測試驗證</button>";
echo "<div id='result' class='result' style='display:none;'></div>";
echo "</div>";

// 系統信息
echo "<div class='test-section'>";
echo "<h3>系統信息</h3>";
echo "<div class='info'>";
echo "<p><strong>PHP版本:</strong> " . phpversion() . "</p>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>當前驗證碼:</strong> " . $_SESSION['captcha_code'] . "</p>";
echo "<p><strong>驗證碼長度:</strong> " . strlen($_SESSION['captcha_code']) . "</p>";
echo "</div>";
echo "</div>";

echo "<p><a href='cooperation_upload.php' class='btn'>返回正式表單</a></p>";
echo "</div>";

echo "
<script>
function refreshCaptcha() {
    // 生成新的4位數字驗證碼
    let newCaptcha = '';
    for (let i = 0; i < 4; i++) {
        newCaptcha += Math.floor(Math.random() * 10);
    }
    
    // 更新顯示
    document.getElementById('captcha-display').textContent = newCaptcha;
    document.getElementById('captcha').value = '';
    document.getElementById('result').style.display = 'none';
    
    // 發送AJAX請求更新後端驗證碼
    fetch('update_captcha.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'captcha=' + encodeURIComponent(newCaptcha)
    }).then(response => response.json())
      .then(data => {
          console.log('驗證碼更新結果:', data);
      })
      .catch(error => {
          console.log('更新驗證碼失敗:', error);
      });
}

function testCaptcha() {
    const input = document.getElementById('captcha').value;
    const result = document.getElementById('result');
    
    if (!input) {
        result.className = 'result error';
        result.textContent = '請輸入驗證碼';
        result.style.display = 'block';
        return;
    }
    
    if (input.length !== 4) {
        result.className = 'result error';
        result.textContent = '驗證碼必須是4位數字';
        result.style.display = 'block';
        return;
    }
    
    // 發送驗證請求
    fetch('verify_captcha.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'captcha=' + encodeURIComponent(input)
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              result.className = 'result success';
              result.textContent = '✅ 驗證碼正確！';
          } else {
              result.className = 'result error';
              result.textContent = '❌ ' + data.message;
          }
          result.style.display = 'block';
      })
      .catch(error => {
          result.className = 'result error';
          result.textContent = '❌ 驗證失敗: ' + error.message;
          result.style.display = 'block';
      });
}
</script>
</body>
</html>";
?>

