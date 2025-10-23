<?php
/**
 * Google 聊天系統測試頁面
 * 測試 Google 登入整合和聊天功能
 */

// 載入 session 配置
require_once '../session_config.php';

$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? null;
$loginMethod = $_SESSION['login_method'] ?? null;
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google 聊天系統測試</title>
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .status {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
            transition: background-color 0.3s;
        }
        
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        
        .test-section {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        
        .session-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Google 聊天系統測試</h1>
        
        <!-- 登入狀態檢查 -->
        <div class="test-section">
            <h2>📊 登入狀態檢查</h2>
            
            <?php if ($isLoggedIn): ?>
                <div class="status success">
                    <h3>✅ 已登入</h3>
                    <p><strong>用戶名：</strong><?php echo htmlspecialchars($username); ?></p>
                    <p><strong>角色：</strong><?php echo htmlspecialchars($role); ?></p>
                    <p><strong>登入方式：</strong><?php echo htmlspecialchars($loginMethod); ?></p>
                </div>
                
                <div style="text-align: center; margin: 20px 0;">
                    <a href="chat.php" class="btn btn-success">🚀 進入聊天系統</a>
                    <a href="?logout=1" class="btn btn-danger">登出</a>
                </div>
            <?php else: ?>
                <div class="status error">
                    <h3>❌ 未登入</h3>
                    <p>請先使用 Google 帳號登入</p>
                </div>
                
                <div style="text-align: center; margin: 20px 0;">
                    <a href="google_chat_integration.php" class="btn">🔐 Google 登入</a>
                    <a href="test_chat_system.php" class="btn">🧪 測試登入</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Session 資訊 -->
        <div class="test-section">
            <h2>🔍 Session 資訊</h2>
            <div class="session-info">
                <h4>Session 基本資訊：</h4>
                <p><strong>Session ID：</strong><?php echo session_id(); ?></p>
                <p><strong>Session 狀態：</strong><?php echo session_status(); ?></p>
                <p><strong>Session 名稱：</strong><?php echo session_name(); ?></p>
            </div>
            
            <h4>Session 資料：</h4>
            <pre><?php print_r($_SESSION); ?></pre>
        </div>
        
        <!-- 功能測試 -->
        <div class="test-section">
            <h2>⚙️ 功能測試</h2>
            
            <div class="status info">
                <h4>測試步驟：</h4>
                <ol>
                    <li>點擊「Google 登入」按鈕</li>
                    <li>完成 Google 授權</li>
                    <li>確認登入狀態</li>
                    <li>進入聊天系統</li>
                    <li>測試聊天功能</li>
                </ol>
            </div>
            
            <div style="text-align: center; margin: 20px 0;">
                <a href="google_chat_integration.php" class="btn">🔐 Google 登入測試</a>
                <a href="test_chat_system.php" class="btn">🧪 系統測試</a>
                <a href="../index.php" class="btn">🏠 返回首頁</a>
            </div>
        </div>
        
        <!-- 系統資訊 -->
        <div class="test-section">
            <h2>📋 系統資訊</h2>
            
            <div class="status info">
                <h4>Google 登入整合狀態：</h4>
                <ul>
                    <li>✅ Session 配置已載入</li>
                    <li>✅ Google 登入回調處理</li>
                    <li>✅ 聊天系統登入檢查</li>
                    <li>✅ 重定向邏輯設定</li>
                </ul>
            </div>
            
            <div class="status warning">
                <h4>⚠️ 注意事項：</h4>
                <ul>
                    <li>確保後端服務 (localhost:5000) 正在運行</li>
                    <li>確保 Google OAuth 設定正確</li>
                    <li>確保資料庫連接正常</li>
                    <li>測試時請使用真實的 Google 帳號</li>
                </ul>
            </div>
        </div>
    </div>
    
    <?php
    // 處理登出
    if (isset($_GET['logout'])) {
        session_destroy();
        header('Location: test_google_chat.php');
        exit;
    }
    ?>
</body>
</html>





