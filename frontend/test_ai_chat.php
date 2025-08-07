<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>測試AI聊天記錄</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>測試AI聊天記錄功能</h1>
    
    <div>
        <h3>登入狀態：<?php echo isset($_SESSION['username']) ? '已登入 (' . $_SESSION['username'] . ')' : '未登入'; ?></h3>
        
        <?php if (isset($_SESSION['username'])): ?>
            <button onclick="testSaveMessage()">測試儲存訊息</button>
            <button onclick="testGetHistory()">測試獲取記錄</button>
            <button onclick="testClearHistory()">測試清除記錄</button>
            
            <div id="test-results" style="margin-top: 20px; padding: 10px; border: 1px solid #ccc;">
                <h4>測試結果：</h4>
                <div id="results"></div>
            </div>
        <?php else: ?>
            <p>請先登入才能測試AI聊天記錄功能</p>
        <?php endif; ?>
    </div>

    <script>
    function testSaveMessage() {
        $.ajax({
            url: '../backend/ai_chat_api.php',
            type: 'POST',
            data: {
                action: 'save_message',
                message_type: 'user',
                message_content: '測試訊息 - ' + new Date().toLocaleString()
            },
            dataType: 'json',
            success: function(response) {
                $('#results').html('<p style="color: green;">✅ 儲存成功: ' + JSON.stringify(response) + '</p>');
            },
            error: function(xhr, status, error) {
                $('#results').html('<p style="color: red;">❌ 儲存失敗: ' + error + '</p>');
            }
        });
    }
    
    function testGetHistory() {
        $.ajax({
            url: '../backend/ai_chat_api.php',
            type: 'GET',
            data: { action: 'get_history' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let html = '<p style="color: green;">✅ 獲取記錄成功</p>';
                    html += '<p>記錄數量: ' + response.history.length + '</p>';
                    html += '<ul>';
                    response.history.forEach(function(msg) {
                        html += '<li><strong>' + msg.message_type + ':</strong> ' + msg.message_content + '</li>';
                    });
                    html += '</ul>';
                    $('#results').html(html);
                } else {
                    $('#results').html('<p style="color: red;">❌ 獲取記錄失敗: ' + response.error + '</p>');
                }
            },
            error: function(xhr, status, error) {
                $('#results').html('<p style="color: red;">❌ 獲取記錄失敗: ' + error + '</p>');
            }
        });
    }
    
    function testClearHistory() {
        if (confirm('確定要清除所有聊天記錄嗎？')) {
            $.ajax({
                url: '../backend/ai_chat_api.php',
                type: 'POST',
                data: { action: 'clear_history' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#results').html('<p style="color: green;">✅ 清除記錄成功</p>');
                    } else {
                        $('#results').html('<p style="color: red;">❌ 清除記錄失敗: ' + response.error + '</p>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#results').html('<p style="color: red;">❌ 清除記錄失敗: ' + error + '</p>');
                }
            });
        }
    }
    </script>
</body>
</html> 