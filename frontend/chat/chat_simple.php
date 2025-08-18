<?php
session_start();
$username = $_SESSION['username'] ?? '匿名';
$role = $_SESSION['role'] ?? '訪客';
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    echo "請先登入後再使用聊天室。";
    exit;
}

// 資料庫連接
$host = '100.79.58.120';  // 使用本機資料庫
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 獲取教師列表
    $stmt = $pdo->query("SELECT u_id, name, department FROM teacher02");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("資料庫連接失敗: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>廠商私聊系統</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
        
        .container {
            display: flex;
            height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: white;
            border-right: 1px solid #ddd;
            overflow-y: auto;
        }
        
        .teacher-list {
            padding: 20px;
        }
        
        .teacher-item {
            display: flex;
            align-items: center;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .teacher-item:hover {
            background: #f0f0f0;
        }
        
        .teacher-item.active {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #2196f3;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }
        
        .teacher-info {
            flex: 1;
        }
        
        .teacher-name {
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .teacher-dept {
            font-size: 12px;
            color: #666;
        }
        
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            background: white;
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
        }
        
        .chat-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #2196f3;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: white;
        }
        
        .message {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }
        
        .message.sent {
            justify-content: flex-end;
        }
        
        .message-content {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 18px;
            word-wrap: break-word;
        }
        
        .message.sent .message-content {
            background: #2196f3;
            color: white;
        }
        
        .message.received .message-content {
            background: #f1f1f1;
            color: #333;
        }
        
        .message-time {
            font-size: 11px;
            color: #999;
            margin-top: 5px;
        }
        
        .chat-input {
            background: white;
            padding: 20px;
            border-top: 1px solid #ddd;
            display: flex;
            align-items: center;
        }
        
        .message-input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 20px;
            margin-right: 10px;
            outline: none;
        }
        
        .send-button {
            padding: 12px 20px;
            background: #2196f3;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .send-button:hover {
            background: #1976d2;
        }
        
        .send-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .no-chat-selected {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 左側教師列表 -->
        <div class="sidebar">
            <div class="teacher-list">
                <h3>教師列表</h3>
                <?php foreach ($teachers as $teacher): ?>
                <div class="teacher-item" onclick="selectTeacher(<?php echo $teacher['u_id']; ?>, '<?php echo htmlspecialchars($teacher['name']); ?>', '<?php echo htmlspecialchars($teacher['department']); ?>')">
                    <div class="avatar">
                        <?php echo strtoupper(substr($teacher['name'], 0, 1)); ?>
                    </div>
                    <div class="teacher-info">
                        <div class="teacher-name"><?php echo htmlspecialchars($teacher['name']); ?></div>
                        <div class="teacher-dept"><?php echo htmlspecialchars($teacher['department']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- 右側聊天區域 -->
        <div class="chat-area">
            <div id="no-chat-selected" class="no-chat-selected">
                請選擇一位教師開始聊天
            </div>
            
            <div id="chat-interface" style="display: none;">
                <div class="chat-header">
                    <div class="chat-avatar" id="chat-avatar"></div>
                    <div>
                        <div id="chat-name"></div>
                        <div style="font-size: 12px; color: #666;" id="chat-dept"></div>
                    </div>
                </div>
                
                <div class="chat-messages" id="chat-messages"></div>
                
                <div class="chat-input">
                    <input type="text" class="message-input" id="message-input" placeholder="輸入訊息..." onkeypress="handleKeyPress(event)">
                    <button class="send-button" onclick="sendMessage()" id="send-button">發送</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentTeacher = null;
        let currentTeacherName = '';
        let currentTeacherDept = '';
        let lastMessageId = 0;
        
        // 選擇教師
        function selectTeacher(teacherId, teacherName, teacherDept) {
            currentTeacher = teacherId;
            currentTeacherName = teacherName;
            currentTeacherDept = teacherDept;
            
            // 更新UI
            document.getElementById('no-chat-selected').style.display = 'none';
            document.getElementById('chat-interface').style.display = 'flex';
            document.getElementById('chat-interface').style.flexDirection = 'column';
            
            document.getElementById('chat-avatar').textContent = teacherName.charAt(0).toUpperCase();
            document.getElementById('chat-name').textContent = teacherName;
            document.getElementById('chat-dept').textContent = teacherDept;
            
            // 更新選中狀態
            document.querySelectorAll('.teacher-item').forEach(item => {
                item.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            // 載入聊天記錄
            loadChatHistory();
            
            // 開始輪詢新訊息
            startPolling();
        }
        
        // 載入聊天記錄
        async function loadChatHistory() {
            try {
                const response = await fetch('../backend/load_private_messages.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        from: 'vendor_1', // 假設廠商ID
                        to: currentTeacher
                    })
                });
                
                const messages = await response.json();
                displayMessages(messages);
                
                if (messages.length > 0) {
                    lastMessageId = Math.max(...messages.map(m => m.id));
                }
            } catch (error) {
                console.error('載入聊天記錄失敗:', error);
            }
        }
        
        // 顯示訊息
        function displayMessages(messages) {
            const chatMessages = document.getElementById('chat-messages');
            chatMessages.innerHTML = '';
            
            messages.forEach(message => {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${message.from_user === 'vendor_1' ? 'sent' : 'received'}`;
                
                const contentDiv = document.createElement('div');
                contentDiv.className = 'message-content';
                contentDiv.textContent = message.message;
                
                const timeDiv = document.createElement('div');
                timeDiv.className = 'message-time';
                timeDiv.textContent = new Date(message.timestamp).toLocaleString();
                
                contentDiv.appendChild(timeDiv);
                messageDiv.appendChild(contentDiv);
                chatMessages.appendChild(messageDiv);
            });
            
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // 發送訊息
        async function sendMessage() {
            const input = document.getElementById('message-input');
            const message = input.value.trim();
            
            if (!message || !currentTeacher) return;
            
            const sendButton = document.getElementById('send-button');
            sendButton.disabled = true;
            
            try {
                const response = await fetch('../backend/send_private_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        from: 'vendor_1',
                        to: currentTeacher,
                        message: message,
                        role: '廠商'
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    input.value = '';
                    // 重新載入聊天記錄以顯示新訊息
                    loadChatHistory();
                } else {
                    alert('發送失敗: ' + (result.error || '未知錯誤'));
                }
            } catch (error) {
                console.error('發送訊息失敗:', error);
                alert('發送失敗: ' + error.message);
            } finally {
                sendButton.disabled = false;
            }
        }
        
        // 處理按鍵事件
        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }
        
        // 輪詢新訊息
        function startPolling() {
            setInterval(async () => {
                if (!currentTeacher) return;
                
                try {
                    const response = await fetch('../backend/load_private_messages.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            from: 'vendor_1',
                            to: currentTeacher
                        })
                    });
                    
                    const messages = await response.json();
                    
                    // 檢查是否有新訊息
                    const newMessages = messages.filter(m => m.id > lastMessageId);
                    if (newMessages.length > 0) {
                        displayMessages(messages);
                        lastMessageId = Math.max(...messages.map(m => m.id));
                    }
                } catch (error) {
                    console.error('檢查新訊息失敗:', error);
                }
            }, 3000); // 每3秒檢查一次
        }
        
        // 載入群聊歷史
        async function loadChatHistory() {
            try {
                const response = await fetch('../backend/chat_history.php');
                const messages = await response.json();
                displayMessages(messages);
            } catch (error) {
                console.error('載入聊天記錄失敗:', error);
            }
        }
        
        // 檢查新訊息
        function checkNewMessages() {
            setInterval(async () => {
                try {
                    const response = await fetch('../backend/chat_history.php');
                    const messages = await response.json();
                    displayMessages(messages);
                } catch (error) {
                    console.error('檢查新訊息失敗:', error);
                }
            }, 3000);
        }
        
        // 發送群聊訊息
        async function sendMsg() {
            const input = document.getElementById('message-input');
            const message = input.value.trim();
            
            if (!message) return;
            
            const sendButton = document.getElementById('send-button');
            sendButton.disabled = true;
            
            try {
                const response = await fetch('../backend/send_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        username: '廠商用戶',
                        role: '廠商',
                        message: message
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    input.value = '';
                    loadChatHistory();
                } else {
                    alert('發送失敗: ' + (result.error || '未知錯誤'));
                }
            } catch (error) {
                console.error('發送訊息失敗:', error);
                alert('發送失敗: ' + error.message);
            } finally {
                sendButton.disabled = false;
            }
        }
    </script>
</body>
</html>
