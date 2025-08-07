<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['username']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>測試AI視窗拖動</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f0f0f0;
        }
        .test-info {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-info h3 {
            color: #333;
            margin-top: 0;
        }
        .test-info ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .test-info li {
            margin: 5px 0;
        }
        .highlight {
            background: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="test-info">
        <h3>🎯 AI視窗拖動測試</h3>
        <p>請測試以下拖動行為：</p>
        <ul>
            <li><span class="highlight">左邊緣拖動</span>：拖動左邊緣時，左邊緣應該跟隨滑鼠移動</li>
            <li><span class="highlight">右邊緣拖動</span>：拖動右邊緣時，右邊緣應該跟隨滑鼠移動</li>
            <li><span class="highlight">上邊緣拖動</span>：拖動上邊緣時，上邊緣應該跟隨滑鼠移動</li>
            <li><span class="highlight">下邊緣拖動</span>：拖動下邊緣時，下邊緣應該跟隨滑鼠移動</li>
            <li><span class="highlight">四個角落</span>：拖動角落時，對應的邊緣應該跟隨滑鼠移動</li>
        </ul>
        <p><strong>測試步驟：</strong></p>
        <ol>
            <li>點擊右下角的AI按鈕打開AI視窗</li>
            <li>將滑鼠移到AI視窗邊緣，會看到橙色的拖動控制點</li>
            <li>拖動左邊緣，觀察左邊緣是否跟隨滑鼠移動</li>
            <li>打開Console（F12）查看拖動調試信息</li>
        </ol>
    </div>

    <!-- 包含AI Widget -->
    <?php include("share/ai_widget.php"); ?>

    <script>
    $(document).ready(function() {
        console.log('=== AI拖動測試頁面載入完成 ===');
        console.log('請點擊AI按鈕並測試拖動功能');
        
        // 監聽拖動開始
        $(document).on('mousedown', '.resize-handle-bl, .resize-handle-br, .resize-handle-t, .resize-handle-b, .resize-handle-l, .resize-handle-r', function() {
            console.log('=== 開始拖動 ===');
            console.log('拖動控制點:', $(this).attr('class'));
        });
        
        // 監聽AI按鈕點擊
        $(document).on('click', '#ai-float-btn', function() {
            console.log('=== AI按鈕被點擊 ===');
            setTimeout(function() {
                console.log('AI視窗是否可見:', $('#ai-box').is(':visible'));
                console.log('AI視窗位置:', {
                    right: $('#ai-box').css('right'),
                    bottom: $('#ai-box').css('bottom'),
                    width: $('#ai-box').width(),
                    height: $('#ai-box').height()
                });
            }, 100);
        });
    });
    </script>
</body>
</html> 