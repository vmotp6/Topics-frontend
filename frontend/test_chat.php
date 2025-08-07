<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>聊天室功能測試</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
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
        .feature-list {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .feature-list h3 {
            color: #007bff;
            margin-top: 0;
        }
        .feature-list ul {
            margin: 0;
            padding-left: 20px;
        }
        .feature-list li {
            margin: 8px 0;
        }
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>💬 聊天室功能測試頁面</h1>
        
        <div class="feature-list">
            <h3>✅ 已實現的功能：</h3>
            <ul>
                <li><strong>浮動聊天按鈕：</strong>在右下角顯示，點擊可開啟聊天室</li>
                <li><strong>登入狀態檢查：</strong>未登入用戶會看到登入提示</li>
                <li><strong>網站功能介紹：</strong>為登入用戶顯示詳細的網站功能</li>
                <li><strong>聊天功能：</strong>支援發送訊息和自動回覆</li>
                <li><strong>關閉功能：</strong>✖ 按鈕可關閉聊天視窗</li>
                <li><strong>永久關閉：</strong>🚫 按鈕可永久隱藏聊天按鈕</li>
                <li><strong>響應式設計：</strong>在手機上也能正常顯示</li>
                <li><strong>Cookie 記憶：</strong>用戶的隱藏設置會被記住</li>
            </ul>
        </div>

        <div class="feature-list">
            <h3>🧪 測試步驟：</h3>
            <ol>
                <li>查看右下角是否有聊天按鈕 💬</li>
                <li>點擊聊天按鈕開啟聊天室</li>
                <li>測試登入/未登入狀態的不同顯示</li>
                <li>嘗試發送訊息（如果已登入）</li>
                <li>點擊 ✖ 關閉聊天視窗</li>
                <li>點擊 🚫 永久隱藏聊天按鈕</li>
                <li>前往 <a href="chat_settings.php">聊天設置頁面</a> 重新啟用</li>
            </ol>
        </div>

        <div style="margin-top: 30px;">
            <a href="one.php" class="btn">返回首頁</a>
            <a href="chat_settings.php" class="btn">聊天設置</a>
            <a href="QA.php" class="btn">認識產學合作</a>
        </div>
    </div>

    <!-- 引入通用聊天室組件 -->
    <?php include("share/chat_widget.php"); ?>
    
    <?php include("share/footer.php"); ?>
    
    <?php include("share/ai_widget.php"); ?>
</body>
</html> 