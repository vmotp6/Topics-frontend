<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['username']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>詳細調試AI Widget</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>詳細調試AI Widget</h1>
    
    <div>
        <h3>登入狀態：<?php echo $isLoggedIn ? '已登入 (' . $_SESSION['username'] . ')' : '未登入'; ?></h3>
        
        <div id="debug-info" style="margin: 20px 0; padding: 10px; background: #f0f0f0;">
            <h4>調試資訊：</h4>
            <div id="debug-content"></div>
        </div>
        
        <button onclick="testAISave()">測試AI儲存</button>
        <button onclick="testAILoad()">測試AI載入</button>
        <button onclick="testAIClick()">測試AI按鈕點擊</button>
        <button onclick="checkConsole()">檢查Console錯誤</button>
    </div>

    <!-- 包含AI Widget -->
    <?php include("share/ai_widget.php"); ?>

    <script>
    // 詳細的調試函數
    function testAISave() {
        console.log('=== 開始測試AI儲存 ===');
        $('#debug-content').html('<p>正在測試AI儲存功能...</p>');
        
        let testMessage = '測試訊息 - ' + new Date().toLocaleString();
        console.log('測試訊息:', testMessage);
        
        $.ajax({
            url: '../backend/ai_chat_api.php',
            type: 'POST',
            data: {
                action: 'save_message',
                message_type: 'user',
                message_content: testMessage
            },
            dataType: 'json',
            success: function(response) {
                console.log('AI儲存成功響應:', response);
                if (response.success) {
                    $('#debug-content').html('<p style="color: green;">✅ AI儲存成功: ' + JSON.stringify(response) + '</p>');
                } else {
                    $('#debug-content').html('<p style="color: red;">❌ AI儲存失敗: ' + response.error + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.log('AI儲存錯誤:', {xhr, status, error});
                $('#debug-content').html('<p style="color: red;">❌ AI儲存錯誤: ' + error + '</p>');
            }
        });
    }
    
    function testAILoad() {
        console.log('=== 開始測試AI載入 ===');
        $('#debug-content').html('<p>正在測試AI載入功能...</p>');
        
        $.ajax({
            url: '../backend/ai_chat_api.php',
            type: 'GET',
            data: { action: 'get_history' },
            dataType: 'json',
            success: function(response) {
                console.log('AI載入成功響應:', response);
                if (response.success) {
                    let html = '<p style="color: green;">✅ AI載入成功</p>';
                    html += '<p>記錄數量: ' + response.history.length + '</p>';
                    html += '<ul>';
                    response.history.forEach(function(msg) {
                        html += '<li><strong>' + msg.message_type + ':</strong> ' + msg.message_content + '</li>';
                    });
                    html += '</ul>';
                    $('#debug-content').html(html);
                } else {
                    $('#debug-content').html('<p style="color: red;">❌ AI載入失敗: ' + response.error + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.log('AI載入錯誤:', {xhr, status, error});
                $('#debug-content').html('<p style="color: red;">❌ AI載入錯誤: ' + error + '</p>');
            }
        });
    }
    
    function testAIClick() {
        console.log('=== 測試AI按鈕點擊 ===');
        $('#debug-content').html('<p>正在測試AI按鈕點擊...</p>');
        
        // 檢查AI按鈕是否存在
        if ($('#ai-float-btn').length > 0) {
            console.log('AI按鈕存在');
            $('#ai-float-btn').click();
            $('#debug-content').html('<p style="color: green;">✅ AI按鈕點擊成功</p>');
        } else {
            console.log('AI按鈕不存在');
            $('#debug-content').html('<p style="color: red;">❌ AI按鈕不存在</p>');
        }
    }
    
    function checkConsole() {
        $('#debug-content').html('<p>請檢查瀏覽器的Console（按F12）查看是否有錯誤訊息</p>');
    }
    
    // 詳細的事件監聽
    $(document).ready(function() {
        console.log('=== 頁面載入完成 ===');
        console.log('jQuery版本:', $.fn.jquery);
        console.log('AI按鈕數量:', $('#ai-float-btn').length);
        console.log('AI視窗數量:', $('#ai-box').length);
        console.log('AI發送按鈕數量:', $('#ai-send-msg').length);
        console.log('AI輸入框數量:', $('#ai-input-field').length);
        
        // 監聽AI發送按鈕點擊
        $(document).on('click', '#ai-send-msg', function() {
            console.log('=== AI發送按鈕被點擊 ===');
            let msg = $('#ai-input-field').val().trim();
            console.log('輸入訊息:', msg);
        });
        
        // 監聽AI輸入框
        $(document).on('keypress', '#ai-input-field', function(e) {
            if (e.which == 13) {
                console.log('=== AI輸入框Enter被按下 ===');
            }
        });
        
        // 監聽AI浮動按鈕
        $(document).on('click', '#ai-float-btn', function() {
            console.log('=== AI浮動按鈕被點擊 ===');
        });
        
        // 監聽AI視窗顯示/隱藏
        $(document).on('show hide', '#ai-box', function() {
            console.log('=== AI視窗狀態改變 ===', $(this).is(':visible'));
        });
    });
    </script>
</body>
</html> 