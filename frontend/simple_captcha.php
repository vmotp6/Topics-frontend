<?php
/**
 * 簡單HTML驗證碼生成器
 */

session_start();

// 生成隨機驗證碼
$captcha_code = '';
for ($i = 0; $i < 4; $i++) {
    $captcha_code .= rand(0, 9);
}

// 將驗證碼存儲到session中
$_SESSION['captcha_code'] = $captcha_code;

// 返回HTML驗證碼
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        .captcha-display {
            width: 120px;
            height: 40px;
            background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
            border: 2px solid #ccc;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
            color: #333;
            font-family: 'Courier New', monospace;
            letter-spacing: 3px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .captcha-display::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: shine 2s infinite;
        }
        
        @keyframes shine {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .captcha-display::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 10%;
            right: 10%;
            height: 1px;
            background: rgba(0,0,0,0.1);
            transform: rotate(-15deg);
        }
    </style>
</head>
<body style="margin: 0; padding: 0;">
    <div class="captcha-display"><?php echo $captcha_code; ?></div>
</body>
</html>



















