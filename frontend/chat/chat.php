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
    
    // 根據角色獲取不同的資料
    if ($role === '廠商') {
        // 廠商：獲取所有教師列表
        $contacts = [];
        
        // 獲取所有老師
        $stmt = $pdo->prepare("SELECT t2.u_id, t2.name, t2.department, u.username, '老師' as contact_type
                              FROM teacher02 t2 
                              JOIN user u ON t2.u_id = u.id 
                              WHERE u.role = '老師'
                              ORDER BY t2.name");
        $stmt->execute();
        $allTeachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $contacts = array_merge($contacts, $allTeachers);
        
        // 如果沒有聯絡人，顯示空陣列
        if (empty($contacts)) {
            $contacts = [];
        }
    } elseif ($role === '老師') {
        // 老師：獲取同科系老師和所有廠商
        $contacts = [];
        
        // 先獲取當前老師的科系
        $stmt = $pdo->prepare("SELECT t2.department FROM teacher02 t2 
                              JOIN user u ON t2.u_id = u.id 
                              WHERE u.username = ? AND u.role = '老師'");
        $stmt->execute([$username]);
        $currentTeacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($currentTeacher) {
            $department = $currentTeacher['department'];
            
            // 獲取同科系的老師
            $stmt = $pdo->prepare("SELECT t2.u_id, t2.name, t2.department, u.username, '老師' as contact_type
                                  FROM teacher02 t2 
                                  JOIN user u ON t2.u_id = u.id 
                                  WHERE u.role = '老師' AND t2.department = ? AND u.username != ?
                                  ORDER BY t2.name");
            $stmt->execute([$department, $username]);
            $sameDeptTeachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $contacts = array_merge($contacts, $sameDeptTeachers);
            
            // 獲取所有廠商
            $stmt = $pdo->prepare("SELECT u.username as vendor_id, u.username as vendor_name, '廠商' as contact_type
                                  FROM user u 
                                  WHERE u.role = '廠商'
                                  ORDER BY u.username");
            $stmt->execute();
            $allVendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $contacts = array_merge($contacts, $allVendors);
        }
        
        // 如果沒有聯絡人，顯示空陣列
        if (empty($contacts)) {
            $contacts = [];
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
    
    .contact-type {
      font-size: 10px;
      color: #999;
      margin-top: 2px;
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
  <?php if ($role === '廠商'): ?>
    <!-- 廠商聊天介面 -->
    <div class="chat-container">
      <!-- 左側聯絡人列表 -->
      <div class="sidebar">
        <div class="sidebar-header">
          <h2 class="sidebar-title">聯絡人列表</h2>
        </div>
        
        <!-- 群組列表 -->
        <div id="groupList" style="margin-bottom: 20px;">
          <h3 style="margin: 10px 0; color: #666; font-size: 14px;">我的群組</h3>
          <div id="groupsContainer"></div>
        </div>
        
        <!-- 聯絡人列表 -->
        <div id="contactList">
          <h3 style="margin: 10px 0; color: #666; font-size: 14px;">聯絡人</h3>
          <ul class="user-list">
            <?php if (!empty($contacts)): ?>
              <?php foreach ($contacts as $contact): ?>
              <li class="user-item" data-user-id="<?php echo $contact['username']; ?>" data-user-name="<?php echo htmlspecialchars($contact['name']); ?>" data-chat-type="private">
                <div class="user-avatar">
                  <?php echo strtoupper(substr($contact['name'], 0, 1)); ?>
                </div>
                <div class="user-info">
                  <div class="user-name"><?php echo htmlspecialchars($contact['name']); ?></div>
                  <div class="user-role"><?php echo htmlspecialchars($contact['department']); ?></div>
                  <div class="contact-type"><?php echo $contact['contact_type']; ?></div>
                </div>
              </li>
              <?php endforeach; ?>
            <?php else: ?>
              <li style="padding: 20px; text-align: center; color: #999;">暫無聯絡人</li>
            <?php endif; ?>
          </ul>
        </div>
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

  <?php elseif ($role === '老師' || $role === '廠商'): ?>
    <!-- 老師和廠商聊天介面 -->
    <div class="chat-container">
      <!-- 左側聯絡人列表 -->
      <div class="sidebar">
        <div class="sidebar-header">
          <h2 class="sidebar-title">聯絡人列表</h2>
          <?php if ($role === '老師'): ?>
          <button id="createGroupBtn" style="margin-top: 10px; padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">建立群組</button>
          <?php endif; ?>
        </div>
        
        <!-- 群組列表 -->
        <div id="groupList" style="margin-bottom: 20px;">
          <h3 style="margin: 10px 0; color: #666; font-size: 14px;">我的群組</h3>
          <div id="groupsContainer"></div>
        </div>
        
        <!-- 聯絡人列表 -->
        <div id="contactList">
          <h3 style="margin: 10px 0; color: #666; font-size: 14px;">聯絡人</h3>
          <ul class="user-list">
            <?php if (!empty($contacts)): ?>
              <?php foreach ($contacts as $contact): ?>
              <li class="user-item" data-user-id="<?php echo $contact['username'] ?? $contact['vendor_id']; ?>" data-user-name="<?php echo htmlspecialchars($contact['name'] ?? $contact['vendor_name']); ?>" data-chat-type="private">
                <div class="user-avatar">
                  <?php echo strtoupper(substr($contact['name'] ?? $contact['vendor_name'], 0, 1)); ?>
                </div>
                <div class="user-info">
                  <div class="user-name"><?php echo htmlspecialchars($contact['name'] ?? $contact['vendor_name']); ?></div>
                  <div class="user-role"><?php echo htmlspecialchars($contact['department'] ?? '廠商'); ?></div>
                  <div class="contact-type"><?php echo $contact['contact_type']; ?></div>
                </div>
              </li>
              <?php endforeach; ?>
            <?php else: ?>
              <li style="padding: 20px; text-align: center; color: #999;">暫無聯絡人</li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
      
      <!-- 右側聊天區域 -->
      <div class="chat-main">
        <div class="chat-header">
          <div class="current-chat-info">
            <div class="current-chat-name">選擇聯絡人開始聊天</div>
            <div class="current-chat-role"></div>
          </div>
        </div>
        
        <div class="chat-messages" id="chatMessages">
          <div class="no-chat-selected">
            請從左側選擇一位聯絡人開始聊天
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
    let currentChatType = 'private'; // 'private' 或 'group'
    let currentGroupId = null;
    let lastMessageId = 0;
    let messageCache = new Map(); // 快取聊天記錄
    
    <?php if ($role === '廠商' || $role === '老師'): ?>
    // 載入群組列表
    async function loadGroups() {
      try {
        const response = await fetch('group_management.php?action=get_my_groups&username=' + username);
        const result = await response.json();
        
        if (result.success && result.groups) {
          const groupsContainer = document.getElementById('groupsContainer');
          groupsContainer.innerHTML = '';
          
          result.groups.forEach(group => {
            const groupItem = document.createElement('div');
            groupItem.className = 'user-item';
            groupItem.dataset.groupId = group.id;
            groupItem.dataset.groupName = group.group_name;
            groupItem.dataset.chatType = 'group';
            
            groupItem.innerHTML = `
              <div class="user-avatar" style="background: #4CAF50;">
                <i class="fas fa-users" style="font-size: 16px;">👥</i>
              </div>
              <div class="user-info">
                <div class="user-name">${group.group_name}</div>
                <div class="user-role">${group.member_count} 位成員</div>
                <div class="contact-type">群組</div>
              </div>
            `;
            
            groupItem.addEventListener('click', function() {
              selectGroup(group.id, group.group_name);
            });
            
            groupsContainer.appendChild(groupItem);
          });
        }
      } catch (error) {
        console.error('載入群組失敗:', error);
      }
    }
    
    // 選擇群組
    function selectGroup(groupId, groupName) {
      // 移除其他項目的active狀態
      document.querySelectorAll('.user-item').forEach(i => i.classList.remove('active'));
      
      // 添加當前項目的active狀態
      event.currentTarget.classList.add('active');
      
      // 檢查是否切換到不同的群組
      if (currentGroupId !== groupId || currentChatType !== 'group') {
        // 重置訊息ID並清空聊天區域
        lastMessageId = 0;
        document.getElementById('chatMessages').innerHTML = '';
      }
      
      // 設置當前聊天類型
      currentChatType = 'group';
      currentGroupId = groupId;
      currentUserId = null;
      currentUserName = groupName;
      
      // 更新聊天標題
      document.querySelector('.current-chat-name').textContent = groupName;
      document.querySelector('.current-chat-role').textContent = '群組聊天';
      
      // 啟用輸入框
      document.getElementById('messageInput').disabled = false;
      document.querySelector('.chat-input button').disabled = false;
      
      // 隱藏提示訊息
      document.querySelector('.no-chat-selected').classList.add('hidden');
      
      // 載入群組聊天記錄
      loadGroupChatHistory();
    }
    
    // 載入群組聊天記錄
    async function loadGroupChatHistory() {
      try {
        const response = await fetch('group_management.php?action=get_group_messages&group_id=' + currentGroupId);
        const result = await response.json();
        
        if (result.success && result.messages) {
          displayGroupMessages(result.messages);
          
          if (result.messages.length > 0) {
            lastMessageId = Math.max(...result.messages.map(m => m.id));
          }
        }
      } catch (error) {
        console.error('載入群組聊天記錄失敗:', error);
      }
    }
    
    // 顯示群組訊息
    function displayGroupMessages(messages) {
      const chatMessages = document.getElementById('chatMessages');
      
      // 清空聊天區域
      chatMessages.innerHTML = '';
      
      // 檢查是否有訊息
      if (!messages || messages.length === 0) {
        // 顯示無訊息的提示
        const noMessageDiv = document.createElement('div');
        noMessageDiv.style.cssText = `
          text-align: center;
          padding: 40px 20px;
          color: #999;
          font-size: 14px;
          background: #f8f9fa;
          border-radius: 8px;
          margin: 20px;
          border: 1px dashed #ddd;
        `;
        noMessageDiv.innerHTML = `
          <div style="font-size: 24px; margin-bottom: 10px;">👥</div>
          <div>群組還沒有任何訊息</div>
          <div style="font-size: 12px; margin-top: 5px;">開始發送訊息吧！</div>
        `;
        chatMessages.appendChild(noMessageDiv);
        return;
      }
      
      messages.forEach(message => {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${message.from_user === username ? 'sent' : 'received'}`;
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        contentDiv.innerHTML = `
          <div style="font-size: 12px; color: #666; margin-bottom: 4px;">
            ${message.from_user} (${message.role})
          </div>
          <div>${message.message}</div>
        `;
        
        const timeDiv = document.createElement('div');
        timeDiv.className = 'message-time';
        timeDiv.textContent = new Date(message.timestamp).toLocaleString();
        
        contentDiv.appendChild(timeDiv);
        messageDiv.appendChild(contentDiv);
        chatMessages.appendChild(messageDiv);
      });
      
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // 建立群組按鈕事件
    const createGroupBtn = document.getElementById('createGroupBtn');
    if (createGroupBtn) {
      createGroupBtn.addEventListener('click', function() {
        showCreateGroupModal();
      });
    }
    
    // 顯示建立群組模態框
    function showCreateGroupModal() {
      const modal = document.createElement('div');
      modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
      `;
      
      modal.innerHTML = `
        <div style="background: white; padding: 20px; border-radius: 8px; width: 400px; max-height: 80vh; overflow-y: auto;">
          <h3>建立群組</h3>
          <div style="margin: 15px 0;">
            <label>群組名稱：</label>
            <input type="text" id="groupName" style="width: 100%; padding: 8px; margin-top: 5px;">
          </div>
          <div style="margin: 15px 0;">
            <label>選擇成員：</label>
            <div id="memberSelection" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-top: 5px;">
              ${generateMemberCheckboxes()}
            </div>
          </div>
          <div style="text-align: right; margin-top: 20px;">
            <button onclick="closeCreateGroupModal()" style="margin-right: 10px; padding: 8px 16px;">取消</button>
            <button onclick="createGroup()" style="padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px;">建立</button>
          </div>
        </div>
      `;
      
      document.body.appendChild(modal);
    }
    
    // 生成成員選擇框
    function generateMemberCheckboxes() {
      const contacts = <?php echo json_encode($contacts ?? []); ?>;
      let html = '';
      
      contacts.forEach(contact => {
        const displayName = contact.name || contact.vendor_name;
        const role = contact.contact_type;
        html += `
          <div style="margin: 5px 0;">
            <input type="checkbox" id="member_${contact.username || contact.vendor_id}" value="${contact.username || contact.vendor_id}" data-role="${role}">
            <label for="member_${contact.username || contact.vendor_id}">${displayName} (${role})</label>
          </div>
        `;
      });
      
      return html || '<p style="color: #999;">暫無聯絡人可選擇</p>';
    }
    
    // 關閉建立群組模態框
    function closeCreateGroupModal() {
      const modal = document.querySelector('div[style*="position: fixed"]');
      if (modal) {
        modal.remove();
      }
    }
    
    // 建立群組
    async function createGroup() {
      const groupName = document.getElementById('groupName').value.trim();
      if (!groupName) {
        alert('請輸入群組名稱');
        return;
      }
      
      const selectedMembers = [];
      document.querySelectorAll('#memberSelection input[type="checkbox"]:checked').forEach(checkbox => {
        selectedMembers.push({
          username: checkbox.value,
          role: checkbox.dataset.role
        });
      });
      
      try {
        const formData = new FormData();
        formData.append('action', 'create_group');
        formData.append('group_name', groupName);
        formData.append('created_by', username);
        formData.append('department', '<?php echo $currentTeacher['department'] ?? ''; ?>');
        formData.append('members', JSON.stringify(selectedMembers));
        
        const response = await fetch('group_management.php', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          alert('群組建立成功！');
          closeCreateGroupModal();
          loadGroups(); // 重新載入群組列表
        } else {
          alert('建立群組失敗: ' + (result.error || '未知錯誤'));
        }
      } catch (error) {
        console.error('建立群組失敗:', error);
        alert('建立群組失敗: ' + error.message);
      }
    }
    
    // 私聊功能
    document.querySelectorAll('.user-item[data-chat-type="private"]').forEach(item => {
      item.addEventListener('click', function() {
        // 移除其他項目的active狀態
        document.querySelectorAll('.user-item').forEach(i => i.classList.remove('active'));
        
        // 添加當前項目的active狀態
        this.classList.add('active');
        
        // 獲取用戶資訊
        const newUserId = this.dataset.userId;
        const newUserName = this.dataset.userName;
        
        // 檢查是否切換到不同的用戶
        if (currentUserId !== newUserId || currentChatType !== 'private') {
          // 重置訊息ID並清空聊天區域
          lastMessageId = 0;
          document.getElementById('chatMessages').innerHTML = '';
        }
        
        // 更新當前用戶資訊
        currentUserId = newUserId;
        currentUserName = newUserName;
        currentChatType = 'private';
        currentGroupId = null;
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
      });
    });

    // 載入聊天記錄
    async function loadChatHistory() {
      const chatMessages = document.getElementById('chatMessages');
      const cacheKey = `${username}-${currentUserId}`;
      
      // 檢查快取
      if (messageCache.has(cacheKey)) {
        const cachedData = messageCache.get(cacheKey);
        displayMessages(cachedData.messages);
        lastMessageId = cachedData.lastMessageId;
        console.log('使用快取聊天記錄');
        return;
      }
      
      // 顯示載入指示器
      chatMessages.innerHTML = '<div style="text-align: center; padding: 20px; color: #666;">載入中...</div>';
      
      try {
        const startTime = performance.now();
        const response = await fetch('load_private_messages.php?from=' + username + '&to=' + currentUserId);
        
        const result = await response.json();
        
        if (result.success && result.messages) {
          displayMessages(result.messages);
          
          if (result.messages.length > 0) {
            lastMessageId = Math.max(...result.messages.map(m => m.id));
          }
          
          // 儲存到快取
          messageCache.set(cacheKey, {
            messages: result.messages,
            lastMessageId: lastMessageId,
            timestamp: Date.now()
          });
          
          const loadTime = performance.now() - startTime;
          console.log(`聊天記錄載入完成，耗時: ${loadTime.toFixed(2)}ms`);
        } else {
          console.error('載入聊天記錄失敗:', result.error);
          chatMessages.innerHTML = '<div style="text-align: center; padding: 20px; color: #999;">載入失敗</div>';
        }
      } catch (error) {
        console.error('載入聊天記錄失敗:', error);
        chatMessages.innerHTML = '<div style="text-align: center; padding: 20px; color: #999;">載入失敗</div>';
      }
    }
    
    // 顯示訊息
    function displayMessages(messages) {
      const chatMessages = document.getElementById('chatMessages');
      
      // 清空聊天區域
      chatMessages.innerHTML = '';
      
      // 檢查是否有訊息
      if (!messages || messages.length === 0) {
        // 顯示無訊息的提示
        const noMessageDiv = document.createElement('div');
        noMessageDiv.style.cssText = `
          text-align: center;
          padding: 40px 20px;
          color: #999;
          font-size: 14px;
          background: #f8f9fa;
          border-radius: 8px;
          margin: 20px;
          border: 1px dashed #ddd;
        `;
        noMessageDiv.innerHTML = `
          <div style="font-size: 24px; margin-bottom: 10px;">💬</div>
          <div>還沒有任何訊息</div>
          <div style="font-size: 12px; margin-top: 5px;">開始發送訊息吧！</div>
        `;
        chatMessages.appendChild(noMessageDiv);
        return;
      }
      
      // 使用 DocumentFragment 優化DOM操作
      const fragment = document.createDocumentFragment();
      
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
        fragment.appendChild(messageDiv);
      });
      
      // 一次性更新DOM
      chatMessages.appendChild(fragment);
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // 發送訊息
    async function sendMessage() {
      const input = document.getElementById('messageInput');
      const message = input.value.trim();
      
      if (!message) return;
      
      const sendButton = document.querySelector('.chat-input button');
      sendButton.disabled = true;
      
      try {
        if (currentChatType === 'group') {
          // 發送群組訊息
          const formData = new FormData();
          formData.append('action', 'send_group_message');
          formData.append('group_id', currentGroupId);
          formData.append('from_user', username);
          formData.append('message', message);
          formData.append('role', role);
          
          const response = await fetch('group_management.php', {
            method: 'POST',
            body: formData
          });
          
          const result = await response.json();
          
          if (result.success) {
            input.value = '';
            // 重新載入群組聊天記錄
            loadGroupChatHistory();
          } else {
            alert('發送失敗: ' + (result.error || '未知錯誤'));
          }
        } else {
          // 發送私聊訊息
          if (!currentUserId) return;
          
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
            // 清除快取，強制重新載入
            const cacheKey = `${username}-${currentUserId}`;
            messageCache.delete(cacheKey);
            // 重新載入聊天記錄以顯示新訊息
            loadChatHistory();
          } else {
            alert('發送失敗: ' + (result.error || '未知錯誤'));
          }
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
    
    // 初始化群組列表
    if (role === '老師' || role === '廠商') {
      loadGroups();
    }
    
    // 定期清理快取（每5分鐘清理一次）
    setInterval(() => {
      const now = Date.now();
      const maxAge = 5 * 60 * 1000; // 5分鐘
      
      for (const [key, data] of messageCache.entries()) {
        if (now - data.timestamp > maxAge) {
          messageCache.delete(key);
        }
      }
      console.log('快取清理完成');
    }, 5 * 60 * 1000);
    
    // 輪詢新訊息
    setInterval(async () => {
      if (currentChatType === 'group' && currentGroupId) {
        // 檢查群組新訊息
        try {
          const response = await fetch('group_management.php?action=get_group_messages&group_id=' + currentGroupId);
          const result = await response.json();
          
          if (result.success && result.messages) {
            const newMessages = result.messages.filter(m => m.id > lastMessageId);
            if (newMessages.length > 0) {
              displayGroupMessages(result.messages);
              lastMessageId = Math.max(...result.messages.map(m => m.id));
            }
          }
        } catch (error) {
          console.error('檢查群組新訊息失敗:', error);
        }
      } else if (currentChatType === 'private' && currentUserId) {
        // 檢查私聊新訊息
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
      }
    }, 3000);
    <?php endif; ?>
  </script>
</body>
</html>
