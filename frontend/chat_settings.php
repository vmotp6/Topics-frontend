<?php
// 載入 session 配置
require_once 'session_config.php';
$chatHidden = isset($_COOKIE['chat_hidden']);
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>AI設置</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 600px;ping 100.79.58.120

            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .status.hidden {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        .status.visible {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn.danger {
            background: #dc3545;
        }
        .btn.danger:hover {
            background: #c82333;
        }
        .btn.success {
            background: #28a745;
        }
        .btn.success:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>💬 AI設置</h1>
        
        <div class="status <?php echo $chatHidden ? 'hidden' : 'visible'; ?>">
            <h3>當前狀態</h3>
            <?php if ($chatHidden): ?>
                <p>❌ AI按鈕目前已被隱藏</p>
            <?php else: ?>
                <p>✅ AI按鈕目前可見</p>
            <?php endif; ?>
        </div>

        <div>
            <h3>操作選項</h3>
            <?php if ($chatHidden): ?>
                <button class="btn success" onclick="showChat()">重新啟用AI按鈕</button>
            <?php else: ?>
                <button class="btn danger" onclick="hideChat()">隱藏AI按鈕</button>
            <?php endif; ?>
        </div>

        <div style="margin-top: 30px;">
            <h3>說明</h3>
            <ul>
                <li>AI會在網站的所有頁面右下角顯示</li>
                <li>您可以隨時隱藏或重新啟用AI</li>
                <li>設置會保存在您的瀏覽器中</li>
                <li>如果AI被隱藏，您可以在任何頁面的AI中點擊 🚫 按鈕來永久關閉</li>
            </ul>
        </div>

        <div style="margin-top: 20px;">
            <a href="index.php" class="btn">返回首頁</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>
        function hideChat() {
            $.post('hide_chat.php', {hide_chat: true}, function(data) {
                if (data.success) {
                    location.reload();
                }
            }, 'json');
        }

        function showChat() {
            $.post('hide_chat.php', {show_chat: true}, function(data) {
                if (data.success) {
                    location.reload();
                }
            }, 'json');
        }
    </script>
    
    <?php include("share/footer.php"); ?>
    
    <?php include("share/ai_widget.php"); ?>
</body>
</html> 