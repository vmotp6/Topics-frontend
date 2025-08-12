<?php
session_start();
$username = $_SESSION['username'] ?? '匿名';
$role = $_SESSION['role'] ?? '訪客';
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    echo "請先登入後再使用聊天室。";
    exit;
}

// 資料庫連接
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 根據角色獲取不同的資料
    if ($role === '廠商') {
        // 廠商：獲取教師列表，使用username作為ID
        $stmt = $pdo->query("SELECT t2.u_id, t2.name, t2.department, u.username 
                             FROM teacher02 t2 
                             JOIN user u ON t2.u_id = u.id 
                             WHERE u.role = '老師'");
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($role === '老師') {
        // 老師：獲取廠商列表，使用username作為ID
        $stmt = $pdo->query("SELECT DISTINCT username as vendor_id, username as vendor_name 
                             FROM user 
                             WHERE role = '廠商' 
                             ORDER BY username");
        $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 如果沒有廠商，顯示空陣列
        if (empty($vendors)) {
            $vendors = [];
        }
    }
    
} catch(PDOException $e) {
    die("資料庫連接失敗: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>聊天室</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      background: #f5f5f5;
    }
    
    .chat-container {
      display: flex;
      height: 100vh;
    }
    
    .sidebar {
      width: 280px;
      background: white;
      border-right: 1px solid #ddd;
      overflow-y: auto;
    }
    
    .sidebar-header {
      padding: 20px;
      border-bottom: 1px solid #eee;
      background: #f8f9fa;
    }
    
    .sidebar-title {
      font-size: 18px;
      font-weight: bold;
      color: #333;
      margin: 0;
    }
    
    .user-list {
      padding: 0;
      margin: 0;
      list-style: none;
    }
    
    .user-item {
      display: flex;
      align-items: center;
      padding: 15px 20px;
      border-bottom: 1px solid #f0f0f0;
      cursor: pointer;
      transition: background-color 0.2s;
    }
    
    .user-item:hover {
      background-color: #f8f9fa;
    }
    
    .user-item.active {
      background-color: #e3f2fd;
      border-left: 4px solid #2196f3;
    }
    
    .user-avatar {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: #2196f3;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
      font-weight: bold;
      font-size: 18px;
    }
    
    .user-info {
      flex: 1;
    }
    
    .user-name {
      font-weight: bold;
      color: #333;
      margin-bottom: 5px;
    }
    
    .user-role {
      font-size: 12px;
      color: #666;
    }
    
    .chat-main {
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    
    .chat-header {
      padding: 20px;
      background: white;
      border-bottom: 1px solid #ddd;
      display: flex;
      align-items: center;
    }
    
    .current-chat-info {
      flex: 1;
    }
    
    .current-chat-name {
      font-size: 18px;
      font-weight: bold;
      color: #333;
      margin-bottom: 5px;
    }
    
    .current-chat-role {
      font-size: 14px;
      color: #666;
    }
    
    .chat-messages {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
      background: #f8f9fa;
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
      padding: 12px 16px;
      border-radius: 18px;
      word-wrap: break-word;
    }
    
    .message.received .message-content {
      background: white;
      color: #333;
      border: 1px solid #ddd;
    }
    
    .message.sent .message-content {
      background: #2196f3;
      color: white;
    }
    
    .message-time {
      font-size: 11px;
      color: #999;
      margin-top: 5px;
    }
    
    .chat-input {
      padding: 20px;
      background: white;
      border-top: 1px solid #ddd;
      display: flex;
      align-items: center;
    }
    
    .chat-input input {
      flex: 1;
      padding: 12px 16px;
      border: 1px solid #ddd;
      border-radius: 25px;
      margin-right: 10px;
      font-size: 14px;
    }
    
    .chat-input button {
      padding: 12px 24px;
      background: #2196f3;
      color: white;
      border: none;
      border-radius: 25px;
      cursor: pointer;
      font-size: 14px;
      font-weight: bold;
    }
    
    .chat-input button:hover {
      background: #1976d2;
    }
    
    .chat-input button:disabled {
      background: #ccc;
      cursor: not-allowed;
    }
    
    .no-chat-selected {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100%;
      color: #666;
      font-size: 18px;
    }
    
    .hidden {
      display: none;
    }
    
    /* 群聊樣式 */
    .group-chat {
      max-width: 800px;
      margin: 50px auto;
      padding: 20px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    #groupChat {
      width: 100%;
      height: 400px;
      border: 1px solid #ccc;
      overflow-y: scroll;
      padding: 10px;
      margin-bottom: 10px;
      background: #f9f9f9;
    }
    
    .teacher { color: blue; }
    .vendor { color: green; }
    .me { font-weight: bold; }
  </style>
</head>
<body>
  <?php if ($role === '廠商' && !empty($teachers)): ?>
    <!-- 廠商私聊介面 -->
    <div class="chat-container">
      <!-- 左側教師列表 -->
      <div class="sidebar">
        <div class="sidebar-header">
          <h2 class="sidebar-title">教師列表</h2>
        </div>
        <ul class="user-list">
          <?php foreach ($teachers as $teacher): ?>
          <li class="user-item" data-user-id="<?php echo $teacher['username']; ?>" data-user-name="<?php echo htmlspecialchars($teacher['name']); ?>">
            <div class="user-avatar">
              <?php echo strtoupper(substr($teacher['name'], 0, 1)); ?>
            </div>
            <div class="user-info">
              <div class="user-name"><?php echo htmlspecialchars($teacher['name']); ?></div>
              <div class="user-role"><?php echo htmlspecialchars($teacher['department']); ?></div>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      
      <!-- 右側聊天區域 -->
      <div class="chat-main">
        <div class="chat-header">
          <div class="current-chat-info">
            <div class="current-chat-name">選擇教師開始聊天</div>
            <div class="current-chat-role"></div>
          </div>
        </div>
        
        <div class="chat-messages" id="chatMessages">
          <div class="no-chat-selected">
            請從左側選擇一位教師開始聊天
          </div>
        </div>
        
        <div class="chat-input">
          <input type="text" id="messageInput" placeholder="輸入訊息..." disabled>
          <button onclick="sendMessage()" disabled>發送</button>
        </div>
      </div>
    </div>

  <?php elseif ($role === '老師' && !empty($vendors)): ?>
    <!-- 老師私聊介面 -->
    <div class="chat-container">
      <!-- 左側廠商列表 -->
      <div class="sidebar">
        <div class="sidebar-header">
          <h2 class="sidebar-title">廠商列表</h2>
        </div>
        <ul class="user-list">
          <?php foreach ($vendors as $vendor): ?>
          <li class="user-item" data-user-id="<?php echo $vendor['vendor_id']; ?>" data-user-name="<?php echo htmlspecialchars($vendor['vendor_name']); ?>">
            <div class="user-avatar">
              <?php echo strtoupper(substr($vendor['vendor_name'], 0, 1)); ?>
            </div>
            <div class="user-info">
              <div class="user-name"><?php echo htmlspecialchars($vendor['vendor_name']); ?></div>
              <div class="user-role">廠商</div>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      
      <!-- 右側聊天區域 -->
      <div class="chat-main">
        <div class="chat-header">
          <div class="current-chat-info">
            <div class="current-chat-name">選擇廠商查看聊天</div>
            <div class="current-chat-role"></div>
          </div>
        </div>
        
        <div class="chat-messages" id="chatMessages">
          <div class="no-chat-selected">
            請從左側選擇一位廠商查看聊天記錄
          </div>
        </div>
        
        <div class="chat-input">
          <input type="text" id="messageInput" placeholder="輸入訊息..." disabled>
          <button onclick="sendMessage()" disabled>發送</button>
        </div>
      </div>
    </div>

  <?php else: ?>
    <!-- 群聊介面 -->
    <div class="group-chat">
      <h2>群聊聊天室</h2>
      <div id="groupChat"></div>
  <input type="text" id="msg" placeholder="輸入訊息">
  <button onclick="sendMsg()">送出</button>
    </div>
  <?php endif; ?>

  <script>
    const username = "<?php echo $username; ?>";
    const role = "<?php echo $role; ?>";
    
    let currentUserId = null;
    let currentUserName = null;
    let lastMessageId = 0;
    
    <?php if ($role === '廠商' || $role === '老師'): ?>
    // 私聊功能
    document.querySelectorAll('.user-item').forEach(item => {
      item.addEventListener('click', function() {
        // 移除其他項目的active狀態
        document.querySelectorAll('.user-item').forEach(i => i.classList.remove('active'));
        
        // 添加當前項目的active狀態
        this.classList.add('active');
        
        // 獲取用戶資訊
        currentUserId = this.dataset.userId;
        currentUserName = this.dataset.userName;
        const userRole = this.querySelector('.user-role').textContent;
        
        // 更新聊天標題
        document.querySelector('.current-chat-name').textContent = currentUserName;
        document.querySelector('.current-chat-role').textContent = userRole;
        
        // 啟用輸入框
        document.getElementById('messageInput').disabled = false;
        document.querySelector('.chat-input button').disabled = false;
        
        // 隱藏提示訊息
        document.querySelector('.no-chat-selected').classList.add('hidden');
        
        // 載入聊天記錄
        loadChatHistory();
        
        // 清空訊息區域
        document.getElementById('chatMessages').innerHTML = '';
        });
      });

    // 載入聊天記錄
    async function loadChatHistory() {
      try {
        const response = await fetch('load_private_messages.php?from=' + username + '&to=' + currentUserId);
        
        const result = await response.json();
        
        if (result.success && result.messages) {
          displayMessages(result.messages);
          
          if (result.messages.length > 0) {
            lastMessageId = Math.max(...result.messages.map(m => m.id));
          }
        } else {
          console.error('載入聊天記錄失敗:', result.error);
        }
      } catch (error) {
        console.error('載入聊天記錄失敗:', error);
      }
    }
    
    // 顯示訊息
    function displayMessages(messages) {
      const chatMessages = document.getElementById('chatMessages');
      chatMessages.innerHTML = '';
      
      messages.forEach(message => {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${message.from_user === username ? 'sent' : 'received'}`;
        
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
      const input = document.getElementById('messageInput');
      const message = input.value.trim();
      
      if (!message || !currentUserId) return;
      
      const sendButton = document.querySelector('.chat-input button');
      sendButton.disabled = true;
      
      try {
        const response = await fetch('save_private_message.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            from: username,
            to: currentUserId,
            message: message,
            role: role
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
    
    // 按Enter發送訊息
    document.getElementById('messageInput').addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        sendMessage();
      }
    });
    
    // 輪詢新訊息
    setInterval(async () => {
      if (!currentUserId) return;
      
      try {
        const response = await fetch('load_private_messages.php?from=' + username + '&to=' + currentUserId);
        
        const result = await response.json();
        
        if (result.success && result.messages) {
          // 檢查是否有新訊息
          const newMessages = result.messages.filter(m => m.id > lastMessageId);
          if (newMessages.length > 0) {
            displayMessages(result.messages);
            lastMessageId = Math.max(...result.messages.map(m => m.id));
          }
        }
      } catch (error) {
        console.error('檢查新訊息失敗:', error);
      }
    }, 3000);
    
    <?php else: ?>
    // 群聊功能
    const chat = document.getElementById('groupChat');
    let lastMessageId = 0;
    
    // 載入群聊歷史
    async function loadGroupChatHistory() {
      try {
        const response = await fetch('../backend/chat_history.php');
        const messages = await response.json();
        displayGroupMessages(messages);
        
        if (messages.length > 0) {
          lastMessageId = Math.max(...messages.map(m => m.id));
        }
      } catch (error) {
        console.error('載入群聊歷史失敗:', error);
      }
    }
    
    // 顯示群聊訊息
    function displayGroupMessages(messages) {
      chat.innerHTML = '';
      
      messages.forEach(message => {
      const p = document.createElement('p');
        const isMe = (message.username === username);
      let cssClass = '';
        if (message.role === '老師') cssClass = 'teacher';
        else if (message.role === '廠商') cssClass = 'vendor';
      if (isMe) cssClass += ' me';

      p.className = cssClass;
        p.textContent = `[${message.role}] ${message.username}：${message.message}`;
      chat.appendChild(p);
      });
      
      chat.scrollTop = chat.scrollHeight;
    }

    // 發送群聊訊息
    async function sendMsg() {
      const msgInput = document.getElementById('msg');
      const msg = msgInput.value.trim();
      
      if (!msg) return;
      
      try {
        const response = await fetch('../backend/send_message.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
          username: username,
          role: role,
          message: msg
          })
        });
        
        const result = await response.json();
        
        if (result.success) {
        msgInput.value = '';
          loadGroupChatHistory();
        } else {
          alert('發送失敗: ' + (result.error || '未知錯誤'));
        }
      } catch (error) {
        console.error('發送訊息失敗:', error);
        alert('發送失敗: ' + error.message);
      }
    }
    
    // 載入群聊歷史
    loadGroupChatHistory();
    
    // 輪詢新群聊訊息
    setInterval(async () => {
      try {
        const response = await fetch('../backend/chat_history.php');
        const messages = await response.json();
        
        // 檢查是否有新訊息
        const newMessages = messages.filter(m => m.id > lastMessageId);
        if (newMessages.length > 0) {
          displayGroupMessages(messages);
          lastMessageId = Math.max(...messages.map(m => m.id));
        }
      } catch (error) {
        console.error('檢查新群聊訊息失敗:', error);
      }
    }, 3000);
    <?php endif; ?>
  </script>
</body>
</html>
