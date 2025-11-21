<?php
/**
 * Google 登入整合到聊天系統
 * 提供 Google 登入按鈕和重定向處理
 */

// 載入 session 配置
require_once '../session_config.php';

// 處理 Google 登入回調
if (isset($_GET['google_login']) && $_GET['google_login'] === 'success') {
    if (isset($_GET['username']) && isset($_GET['role'])) {
        // 設定Session
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $_GET['username'];
        $_SESSION['role'] = $_GET['role'];
        $_SESSION['login_method'] = 'google';
        
        // 重定向到聊天系統
        header("Location: chat.php");
        exit();
    }
}

// 檢查是否已登入
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

// 如果已登入，直接重定向到聊天系統
if ($isLoggedIn) {
    header("Location: chat.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google 登入 - 聊天系統</title>
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        
        .logo {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .title {
            color: #333;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
        }
        
        .google-btn {
            background: #4285f4;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            margin: 10px;
        }
        
        .google-btn:hover {
            background: #3367d6;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(66, 133, 244, 0.3);
        }
        
        .google-icon {
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            color: #4285f4;
        }
        
        .features {
            margin-top: 30px;
            text-align: left;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin: 15px 0;
            color: #666;
        }
        
        .feature-icon {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            font-size: 16px;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link:hover {
            color: #333;
        }
        
        .loading {
            display: none;
            margin-top: 20px;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #4285f4;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">💬</div>
        <h1 class="title">康寧大學聊天系統</h1>
        <p class="subtitle">使用 Google 帳號登入開始聊天</p>
        
        <a href="http://localhost:5000/auth/google" class="google-btn" onclick="showLoading()">
            <div class="google-icon">G</div>
            使用 Google 登入
        </a>
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>正在登入中...</p>
        </div>
        
        <div class="features">
            <h3 style="text-align: center; margin-bottom: 20px; color: #333;">聊天功能特色</h3>
            <div class="feature-item">
                <span class="feature-icon">👥</span>
                <span>與老師和同學即時聊天</span>
            </div>
            <div class="feature-item">
                <span class="feature-icon">🔒</span>
                <span>安全的 Google 帳號登入</span>
            </div>
            <div class="feature-item">
                <span class="feature-icon">📱</span>
                <span>支援手機和電腦使用</span>
            </div>
            <div class="feature-item">
                <span class="feature-icon">⚡</span>
                <span>即時訊息同步</span>
            </div>
        </div>
        
        <a href="../index.php" class="back-link">← 返回首頁</a>
    </div>
    
    <script>
        function showLoading() {
            document.getElementById('loading').style.display = 'block';
        }
        
        // 檢查是否已經登入
        <?php if ($isLoggedIn): ?>
        // 如果已經登入，直接重定向
        window.location.href = 'chat.php';
        <?php endif; ?>
        
        // 處理登入狀態檢查
        function checkLoginStatus() {
            // 這裡可以添加 AJAX 檢查登入狀態的邏輯
        }
        
        // 頁面載入時檢查登入狀態
        document.addEventListener('DOMContentLoaded', function() {
            checkLoginStatus();
        });
    </script>
</body>
</html>






















