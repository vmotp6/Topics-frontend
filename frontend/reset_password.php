<?php
// 只載入必要的配置，不顯示 header
require_once 'session_config.php';

// 路徑配置
if (!isset($config)) {
    $config = [
        'base_url' => '/Topics-frontend/frontend/',
        'share_url' => '/Topics-frontend/frontend/share/'
    ];
}

// 路徑生成函數
if (!function_exists('getCorrectPath')) {
    function getCorrectPath($targetFile) {
        global $config;
        return $config['base_url'] . $targetFile;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>重設密碼 - 康寧大學</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: rgba(0, 0, 0, 0.5);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            margin: 0;
        }
        
        .reset-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            position: relative;
            text-align: center;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }
        
        .reset-container h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            text-align: center;
            font-size: 28px;
            font-weight: 600;
        }
        
        .input-field {
            position: relative;
            border-bottom: 2px solid rgba(102, 126, 234, 0.3);
            margin: 20px 0;
            text-align: left;
            transition: all 0.3s ease;
            padding-top: 14px;
            box-sizing: border-box;
        }
        
        .input-field:focus-within {
            border-bottom-color: #667eea;
        }
        
        .input-field label {
            position: absolute;
            top: 0;
            left: 0;
            transform: none;
            color: #2c3e50;
            font-size: 14px;
            transition: 0.3s ease;
            pointer-events: none;
        }
        
        .input-field input {
            width: 100%;
            height: 40px;
            padding-top: 6px;
            background: transparent;
            border: none;
            font-size: 16px;
            outline: none;
            color: #2c3e50;
        }
        
        .input-field input:focus ~ label,
        .input-field input:valid ~ label {
            font-size: 12px;
            top: -10px;
            transform: none;
            color: #667eea;
        }
        
        button {
            background: linear-gradient(#4e7d7cad 0%);
            color: white;
            font-weight: 600;
            border: none;
            padding: 12px 20px;
            cursor: pointer;
            border-radius: 25px;
            font-size: 16px;
            margin-bottom: 5px;
            margin-top: 10px;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .message {
            margin-top: 15px;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            font-size: 14px;
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
        
        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .loading {
            text-align: center;
            color: #7f8c8d;
            margin: 20px 0;
            font-size: 14px;
        }
        
        .helper-text {
            margin-top: 15px;
            text-align: center;
            color: #7f8c8d;
        }
        
        .helper-text a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .helper-text a:hover {
            color: #5a6fd8;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h1>重設密碼</h1>
        <div id="loadingMessage" class="loading">驗證連結中...</div>
        <div id="errorMessage" class="message error" style="display: none;"></div>
        <div id="successMessage" class="message success" style="display: none;"></div>
        
        <form id="resetPasswordForm" style="display: none;">
            <div class="input-field">
                <input type="password" id="newPassword" name="new_password" required>
                <label>新密碼</label>
            </div>
            <div class="input-field">
                <input type="password" id="confirmPassword" name="confirm_password" required>
                <label>確認新密碼</label>
            </div>
            <button type="submit" id="submitBtn">重設密碼</button>
        </form>
        
        <div class="helper-text" id="backLink" style="display: none;">
            <a href="<?php echo getCorrectPath('index.php'); ?>">返回首頁</a>
        </div>
    </div>

    <script>
        // 從 URL 獲取 token
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token');
        
        if (!token) {
            showError('無效的重設連結');
            document.getElementById('loadingMessage').style.display = 'none';
        } else {
            // 驗證 token
            verifyToken(token);
        }
        
        function verifyToken(token) {
            fetch(`http://localhost:5000/verify-reset-token?token=${token}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('loadingMessage').style.display = 'none';
                    
                    if (data.valid) {
                        // Token 有效，顯示表單
                        document.getElementById('resetPasswordForm').style.display = 'block';
                        if (data.username) {
                            document.querySelector('h1').textContent = `重設密碼 - ${data.username}`;
                        }
                    } else {
                        // Token 無效
                        showError(data.message || '無效的重設連結');
                        document.getElementById('backLink').style.display = 'block';
                    }
                })
                .catch(err => {
                    document.getElementById('loadingMessage').style.display = 'none';
                    showError('驗證失敗，請稍後再試');
                    document.getElementById('backLink').style.display = 'block';
                });
        }
        
        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }
        
        function showSuccess(message) {
            const successDiv = document.getElementById('successMessage');
            successDiv.textContent = message;
            successDiv.style.display = 'block';
        }
        
        // 處理表單提交
        document.getElementById('resetPasswordForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const submitBtn = document.getElementById('submitBtn');
            const errorDiv = document.getElementById('errorMessage');
            const successDiv = document.getElementById('successMessage');
            
            // 隱藏之前的訊息
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';
            
            // 驗證密碼
            if (newPassword.length < 6) {
                showError('密碼長度至少需要 6 個字元');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                showError('兩次輸入的密碼不一致');
                return;
            }
            
            // 提交重設密碼請求
            submitBtn.disabled = true;
            submitBtn.textContent = '處理中...';
            
            fetch('http://localhost:5000/reset-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    token: token,
                    new_password: newPassword
                })
            })
            .then(async res => {
                const data = await res.json();
                if (res.ok) {
                    showSuccess(data.message || '密碼重設成功！');
                    document.getElementById('resetPasswordForm').style.display = 'none';
                    document.getElementById('backLink').style.display = 'block';
                    
                    // 3秒後跳轉到登入頁面
                    setTimeout(() => {
                        window.location.href = '<?php echo getCorrectPath("index.php"); ?>';
                    }, 3000);
                } else {
                    showError(data.message || '重設密碼失敗，請稍後再試');
                    submitBtn.disabled = false;
                    submitBtn.textContent = '重設密碼';
                }
            })
            .catch(err => {
                showError('重設密碼失敗，請稍後再試');
                submitBtn.disabled = false;
                submitBtn.textContent = '重設密碼';
            });
        });
    </script>
</body>
</html>

