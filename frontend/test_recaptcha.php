<?php
// 載入 reCAPTCHA 設定
require_once '../backend/recaptcha_config.php';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>reCAPTCHA 測試頁面</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .recaptcha-section {
            margin: 20px 0;
            text-align: center;
        }
        .recaptcha-error {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
        }
        .submit-btn {
            background: #007cba;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .submit-btn:hover {
            background: #005a87;
        }
        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .message {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-box h3 {
            margin-top: 0;
            color: #0066cc;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>reCAPTCHA 測試頁面</h1>
        
        <div class="info-box">
            <h3>目前使用的 reCAPTCHA 設定：</h3>
            <p><strong>Site Key:</strong> <?php echo RECAPTCHA_SITE_KEY; ?></p>
            <p><strong>Secret Key:</strong> <?php echo substr(RECAPTCHA_SECRET_KEY, 0, 10) . '...'; ?></p>
            <p><em>注意：目前使用的是 Google 提供的測試用金鑰，僅供開發測試使用。</em></p>
        </div>
        
        <div id="message-display"></div>
        
        <form id="testForm">
            <div class="form-group">
                <label for="name">姓名:</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="email">電子郵件:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="message">訊息:</label>
                <textarea id="message" name="message" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" required></textarea>
            </div>
            
            <div class="recaptcha-section">
                <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>" data-callback="onRecaptchaSuccess" data-expired-callback="onRecaptchaExpired"></div>
                <div id="recaptcha-error" class="recaptcha-error" style="display: none;">
                    請完成「我不是機器人」驗證
                </div>
            </div>
            
            <button type="submit" class="submit-btn" id="submitBtn">
                提交測試
            </button>
        </form>
    </div>

    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
        // reCAPTCHA 狀態追蹤
        let recaptchaCompleted = false;
        
        // reCAPTCHA 成功回調
        function onRecaptchaSuccess(token) {
            recaptchaCompleted = true;
            document.getElementById('recaptcha-error').style.display = 'none';
            console.log('reCAPTCHA 驗證成功，Token:', token);
        }
        
        // reCAPTCHA 過期回調
        function onRecaptchaExpired() {
            recaptchaCompleted = false;
            console.log('reCAPTCHA 驗證已過期');
        }
        
        // 表單提交處理
        document.getElementById('testForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const messageDiv = document.getElementById('message-display');
            
            // 檢查必填欄位
            const requiredFields = ['name', 'email', 'message'];
            const missingFields = [];
            
            requiredFields.forEach(field => {
                const element = document.getElementById(field);
                if (element && !element.value.trim()) {
                    missingFields.push(field);
                }
            });
            
            if (missingFields.length > 0) {
                messageDiv.className = 'message error';
                messageDiv.textContent = '請填寫必填欄位: ' + missingFields.join(', ');
                return;
            }
            
            // 檢查reCAPTCHA
            const recaptchaResponse = grecaptcha.getResponse();
            if (!recaptchaResponse || !recaptchaCompleted) {
                messageDiv.className = 'message error';
                messageDiv.textContent = '請完成「我不是機器人」驗證';
                document.getElementById('recaptcha-error').style.display = 'block';
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = '提交中...';
            
            // 收集表單資料
            const formData = new FormData(this);
            formData.append('g-recaptcha-response', recaptchaResponse);
            
            // 提交到後端API
            fetch('../backend/test_recaptcha_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.className = 'message success';
                    messageDiv.textContent = data.message;
                    document.getElementById('testForm').reset();
                    if (typeof grecaptcha !== 'undefined') {
                        grecaptcha.reset();
                        recaptchaCompleted = false;
                    }
                } else {
                    messageDiv.className = 'message error';
                    messageDiv.textContent = '提交失敗: ' + data.message;
                }
            })
            .catch(error => {
                console.error('提交錯誤:', error);
                messageDiv.className = 'message error';
                messageDiv.textContent = '提交失敗，請稍後再試。錯誤詳情: ' + error.message;
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = '提交測試';
            });
        });
    </script>
</body>
</html>