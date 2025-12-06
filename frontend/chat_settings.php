<?php
// 載入 session 配置
require_once 'session_config.php';
$chatHidden = isset($_COOKIE['chat_hidden']);
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>AI助手設置</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Microsoft JhengHei', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 700px;
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }
        
        .status {
            padding: 25px;
            border-radius: 15px;
            margin: 25px 0;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .status.hidden {
            background: linear-gradient(90deg, rgba(122, 201, 199, 0.1) 0%, rgba(149, 109, 189, 0.1) 100%);
            border-color: #e17055;
            color: #2d3436;
        }
        
        .status.visible {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            border-color: #00b894;
            color: white;
        }
        
        .status h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .status p {
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .action-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin: 25px 0;
            text-align: center;
        }
        
        .action-section h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.4rem;
            font-weight: 600;
        }
        
        .btn {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            margin: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            min-width: 180px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn.danger {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }
        
        .btn.danger:hover {
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
        }
        
        .btn.success {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            box-shadow: 0 4px 15px rgba(0, 184, 148, 0.3);
        }
        
        .btn.success:hover {
            box-shadow: 0 6px 20px rgba(0, 184, 148, 0.4);
        }
        
        .info-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin: 25px 0;
        }
        
        .info-section h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.4rem;
            font-weight: 600;
        }
        
        .info-section ul {
            list-style: none;
            padding: 0;
        }
        
        .info-section li {
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
            color: #495057;
            font-size: 1rem;
            position: relative;
            padding-left: 25px;
        }
        
        .info-section li:last-child {
            border-bottom: none;
        }
        
        .info-section li:before {
            content: "✨";
            position: absolute;
            left: 0;
            top: 10px;
        }
        
        .back-section {
            text-align: center;
            margin-top: 30px;
        }
        
        .back-section .btn {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            box-shadow: 0 4px 15px rgba(116, 185, 255, 0.3);
        }
        
        .back-section .btn:hover {
            box-shadow: 0 6px 20px rgba(116, 185, 255, 0.4);
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .container {
                padding: 25px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .btn {
                min-width: 150px;
                padding: 12px 25px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🤖 AI助手設置</h1>
            <p>管理您的AI助手顯示設定</p>
        </div>
        
        <div class="status <?php echo $chatHidden ? 'hidden' : 'visible'; ?>">
            <h3>當前狀態</h3>
            <?php if ($chatHidden): ?>
                <p>❌ AI助手目前已被隱藏</p>
            <?php else: ?>
                <p>✅ AI助手目前可見</p>
            <?php endif; ?>
        </div>

        <div class="action-section">
            <h3>操作選項</h3>
            <?php if ($chatHidden): ?>
                <button class="btn success" onclick="showChat()">
                    <i class="fas fa-eye"></i> 重新啟用AI助手
                </button>
            <?php else: ?>
                <button class="btn danger" onclick="hideChat()">
                    <i class="fas fa-eye-slash"></i> 隱藏AI助手
                </button>
            <?php endif; ?>
        </div>

        <div class="info-section">
            <h3>功能說明</h3>
            <ul>
                <li>AI助手會在網站的所有頁面右下角顯示</li>
                <li>您可以隨時隱藏或重新啟用AI助手</li>
                <li>設置會保存在您的瀏覽器中</li>
                <li>如果AI助手被隱藏，您可以在任何頁面點擊 🚫 按鈕來永久關閉</li>
                <li>AI助手可以幫助您解答問題和提供資訊</li>
            </ul>
        </div>

        <div class="back-section">
            <a href="<?php 
                // 根據登入狀態和角色決定導向頁面
                if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['role'])) {
                    // 所有角色統一返回 index.php（學校行政人員除外）
                    if ($_SESSION['role'] === '學校行政人員') {
                        echo 'admin.php';
                    } else {
                        echo 'index.php';
                    }
                } else {
                    echo 'index.php';
                }
            ?>" class="btn">
                <i class="fas fa-home"></i> <?php 
                    // 根據登入狀態顯示不同的按鈕文字
                    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['role'])) {
                        switch ($_SESSION['role']) {
                            case '學生':
                                echo '返回學生首頁';
                                break;
                            case '老師':
                                echo '返回老師首頁';
                                break;
                            case '學校行政人員':
                                echo '返回管理首頁';
                                break;
                            default:
                                echo '返回首頁';
                                break;
                        }
                    } else {
                        echo '返回首頁';
                    }
                ?>
            </a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>
        function hideChat() {
            const button = event.target;
            const originalText = button.innerHTML;
            
            // 顯示載入狀態
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 處理中...';
            button.disabled = true;
            
            $.post('hide_chat.php', {hide_chat: true}, function(data) {
                if (data.success) {
                    // 顯示成功訊息
                    button.innerHTML = '<i class="fas fa-check"></i> 已隱藏';
                    button.style.background = 'linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%)';
                    
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    // 恢復按鈕狀態
                    button.innerHTML = originalText;
                    button.disabled = false;
                    alert('操作失敗，請稍後再試');
                }
            }, 'json').fail(function() {
                // 恢復按鈕狀態
                button.innerHTML = originalText;
                button.disabled = false;
                alert('網路錯誤，請檢查連線後再試');
            });
        }

        function showChat() {
            const button = event.target;
            const originalText = button.innerHTML;
            
            // 顯示載入狀態
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 處理中...';
            button.disabled = true;
            
            $.post('hide_chat.php', {show_chat: true}, function(data) {
                if (data.success) {
                    // 顯示成功訊息
                    button.innerHTML = '<i class="fas fa-check"></i> 已啟用';
                    button.style.background = 'linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%)';
                    
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    // 恢復按鈕狀態
                    button.innerHTML = originalText;
                    button.disabled = false;
                    alert('操作失敗，請稍後再試');
                }
            }, 'json').fail(function() {
                // 恢復按鈕狀態
                button.innerHTML = originalText;
                button.disabled = false;
                alert('網路錯誤，請檢查連線後再試');
            });
        }
        
        // 添加頁面載入動畫
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.6s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
    
    <?php include("share/footer.php"); ?>
    
    <?php include("share/ai_widget.php"); ?>
</body>
</html> 