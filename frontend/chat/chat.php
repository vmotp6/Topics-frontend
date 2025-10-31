<?php
// 載入 session 配置
require_once '../session_config.php';

// 檢查登入狀態（與 header.php 保持一致）
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

// 如果未登入，重定向到 Google 登入頁面
if (!$isLoggedIn) {
    header("Location: google_chat_integration.php");
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

// 載入資料庫配置
require_once '../config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 根據角色獲取不同的資料
    if ($role === '學生' || $role === 'student') {
        // 學生：獲取所有教師列表
        $contacts = [];
        
        // 獲取所有老師（包含頭像）
        $stmt = $pdo->prepare("SELECT t.user_id, t.name, t.department, u.username, u.profile_picture, '老師' as contact_type
                              FROM teacher t 
                              JOIN user u ON t.user_id = u.id 
                              WHERE u.role = '老師'
                              ORDER BY t.name");
        $stmt->execute();
        $allTeachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $contacts = array_merge($contacts, $allTeachers);
        
        // 如果沒有聯絡人，顯示空陣列
        if (empty($contacts)) {
            $contacts = [];
        }
    } elseif ($role === '老師' || $role === 'teacher') {
        // 老師：獲取同科系老師和所有學生
        $contacts = [];
        
        // 先獲取當前老師的科系
        $stmt = $pdo->prepare("SELECT t.department FROM teacher t 
                              JOIN user u ON t.user_id = u.id 
                              WHERE u.username = ? AND u.role = '老師'");
        $stmt->execute([$username]);
        $currentTeacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($currentTeacher) {
            $department = $currentTeacher['department'];
            
            // 獲取同科系的老師（包含頭像）
            $stmt = $pdo->prepare("SELECT t.user_id, t.name, t.department, u.username, u.profile_picture, '老師' as contact_type
                                  FROM teacher t 
                                  JOIN user u ON t.user_id = u.id 
                                  WHERE u.role = '老師' AND t.department = ? AND u.username != ?
                                  ORDER BY t.name");
            $stmt->execute([$department, $username]);
            $sameDeptTeachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $contacts = array_merge($contacts, $sameDeptTeachers);
            
            // 獲取所有學生（包含頭像）
            try {
                $stmt = $pdo->prepare("SELECT s.user_id, s.name, s.department, u.username, u.profile_picture, '學生' as contact_type, s.grade, s.class_name
                                      FROM student s 
                                      JOIN user u ON s.user_id = u.id 
                                      WHERE u.role = '學生'
                                      ORDER BY s.department, s.grade, s.name");
                $stmt->execute();
                $allStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $contacts = array_merge($contacts, $allStudents);
            } catch (PDOException $e) {
                // 如果學生表不存在或結構不同，使用用戶表（包含頭像）
                $stmt = $pdo->prepare("SELECT u.id as user_id, u.username as name, '未設定' as department, u.username, u.profile_picture, '學生' as contact_type, '未設定' as grade, '未設定' as class_name
                                      FROM user u 
                                      WHERE u.role = '學生'
                                      ORDER BY u.username");
                $stmt->execute();
                $allStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $contacts = array_merge($contacts, $allStudents);
            }
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
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <link rel="stylesheet" href="css_cache_buster.php?file=chat&v=<?php echo time(); ?>">
  <link rel="stylesheet" href="css_cache_buster.php?file=color_schemes&v=<?php echo time(); ?>">
  <link rel="stylesheet" href="css_cache_buster.php?file=voice_styles&v=<?php echo time(); ?>">
  <title>聊天室</title>
  <script src="fcm_client.js"></script>
  <script src="voice_recorder.js"></script>
</head>
<body>
<?php include("../share/header.php"); ?>
<main>
  <?php if ($role === '學生' || $role === 'student'): ?>
    <!-- 學生聊天介面 -->
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
                  <?php 
                  // 從資料庫獲取頭像
                  $avatar_src = './share/EIdROxGXsAE_LSs.jpg'; // 預設頭像
                  if (!empty($contact['profile_picture'])) {
                      // 檢查是否為完整URL或相對路徑
                      if (filter_var($contact['profile_picture'], FILTER_VALIDATE_URL)) {
                          $avatar_src = $contact['profile_picture'];
                      } else {
                          $avatar_src = './share/' . $contact['profile_picture'];
                      }
                      // 顯示頭像圖片
                      echo '<img src="' . htmlspecialchars($avatar_src) . '" alt="' . htmlspecialchars($contact['name']) . '" class="avatar-img" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'inline-block\';">';
                      // 如果圖片載入失敗，顯示文字備用
                      echo '<span class="avatar-text" style="display:none;">' . mb_substr($contact['name'], 0, 1, 'UTF-8') . '</span>';
                  } else {
                      // 沒有頭像時顯示中文姓名的第一個字符
                      $name = $contact['name'];
                      $firstChar = mb_substr($name, 0, 1, 'UTF-8');
                      echo '<span class="avatar-text">' . $firstChar . '</span>';
                  }
                  ?>
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
            <div class="current-chat-name">選擇老師開始聊天</div>
            <div class="current-chat-role"></div>
          </div>
        </div>
        
        <div class="chat-messages" id="chatMessages">
          <div class="no-chat-selected">
            請從左側選擇一位老師開始聊天
          </div>
        </div>
        
        <div class="chat-input">
          <input type="text" id="messageInput" placeholder="輸入訊息..." disabled>
          <button id="voiceRecordBtn" onclick="toggleVoiceRecording()" disabled title="語音輸入">🎤 語音</button>
          <button onclick="sendMessage()" disabled>發送</button>
        </div>
      </div>
    </div>

  <?php elseif ($role === '老師' || $role === 'teacher' || $role === '學生' || $role === 'student'): ?>
    <!-- 老師和學生聊天介面 -->
    <div class="chat-container">
      <!-- 左側聯絡人列表 -->
      <div class="sidebar">
        <div class="sidebar-header">
          <h2 class="sidebar-title">聯絡人列表 <span id="unreadBadge" style="background: #ff4444; color: white; border-radius: 50%; padding: 2px 6px; font-size: 12px; display: none;">0</span></h2>
          <div style="margin-top: 10px;">
            <?php if ($role === '老師' || $role === 'teacher'): ?>
            <button id="createGroupBtn" style="margin-right: 5px; padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">建立群組</button>
            <?php endif; ?>
            <button id="notificationSettingsBtn" style="margin-right: 5px; padding: 8px 16px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer;">🔔 通知設定</button>
            <button id="colorPickerBtn" style="padding: 8px 16px; background: #6f42c1; color: white; border: none; border-radius: 4px; cursor: pointer;">🎨 配色方案</button>
          </div>
        </div>
        
        <!-- 搜尋框 -->
        <?php if ($role === '老師' || $role === 'teacher'): ?>
        <div class="search-container" style="padding: 10px; border-bottom: 1px solid #eee;">
          <input type="text" id="studentSearch" placeholder="搜尋學生姓名、學號或科系..." 
                 style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
        </div>
        <?php endif; ?>
        
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
              <li class="user-item" data-user-id="<?php echo $contact['username']; ?>" data-user-name="<?php echo htmlspecialchars($contact['name']); ?>" data-chat-type="private" data-contact-type="<?php echo $contact['contact_type']; ?>" data-department="<?php echo htmlspecialchars($contact['department'] ?? ''); ?>" data-grade="<?php echo htmlspecialchars($contact['grade'] ?? ''); ?>" data-class="<?php echo htmlspecialchars($contact['class_name'] ?? ''); ?>">
                <div class="user-avatar">
                  <?php 
                  // 從資料庫獲取頭像
                  $avatar_src = './share/EIdROxGXsAE_LSs.jpg'; // 預設頭像
                  if (!empty($contact['profile_picture'])) {
                      // 檢查是否為完整URL或相對路徑
                      if (filter_var($contact['profile_picture'], FILTER_VALIDATE_URL)) {
                          $avatar_src = $contact['profile_picture'];
                      } else {
                          $avatar_src = './share/' . $contact['profile_picture'];
                      }
                      // 顯示頭像圖片
                      echo '<img src="' . htmlspecialchars($avatar_src) . '" alt="' . htmlspecialchars($contact['name']) . '" class="avatar-img" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'inline-block\';">';
                      // 如果圖片載入失敗，顯示文字備用
                      echo '<span class="avatar-text" style="display:none;">' . mb_substr($contact['name'], 0, 1, 'UTF-8') . '</span>';
                  } else {
                      // 沒有頭像時顯示中文姓名的第一個字符
                      $name = $contact['name'];
                      $firstChar = mb_substr($name, 0, 1, 'UTF-8');
                      echo '<span class="avatar-text">' . $firstChar . '</span>';
                  }
                  ?>
                </div>
                <div class="user-info">
                  <div class="user-name"><?php echo htmlspecialchars($contact['name']); ?></div>
                  <div class="user-role"><?php echo htmlspecialchars($contact['department'] ?? ''); ?></div>
                  <?php if ($contact['contact_type'] === '學生' && isset($contact['grade'])): ?>
                  <div class="student-info" style="font-size: 12px; color: #666;">
                    <?php echo htmlspecialchars($contact['grade']); ?>
                    <?php if (isset($contact['class_name'])): ?>
                    - <?php echo htmlspecialchars($contact['class_name']); ?>
                    <?php endif; ?>
                  </div>
                  <?php endif; ?>
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
          <button id="voiceRecordBtn" onclick="toggleVoiceRecording()" disabled title="語音輸入">🎤 語音</button>
          <button onclick="sendMessage()" disabled>發送</button>
        </div>
        
        <!-- 語音錄製指示器 -->
        <div id="recordingIndicator" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(255,0,0,0.8); color: white; padding: 20px; border-radius: 10px; z-index: 1000;">
          <div style="text-align: center;">
            <div style="font-size: 24px; margin-bottom: 10px;">🎤</div>
            <div>正在錄製語音...</div>
            <div id="recordingTimer" style="font-size: 18px; margin-top: 5px;"></div>
          </div>
        </div>
        
        <!-- 處理中指示器 -->
        <div id="processingIndicator" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 20px; border-radius: 10px; z-index: 1000;">
          <div style="text-align: center;">
            <div style="font-size: 24px; margin-bottom: 10px;">⏳</div>
            <div>正在轉換語音為文字...</div>
          </div>
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
    
    // 清除所有快取
    function clearMessageCache() {
        messageCache.clear();
        console.log('清除所有聊天記錄快取');
    }
    
    <?php if ($role === '學生' || $role === 'student' || $role === '老師' || $role === 'teacher'): ?>
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
                <div class="user-name" id="group-name-${group.id}">${group.group_name}</div>
                <div class="user-role">${group.member_count} 位成員</div>
                <div class="contact-type">群組</div>
                <button class="edit-group-btn" onclick="editGroupName('${group.id}', '${group.group_name}')">編輯</button>
              </div>
            `;
            
            groupItem.addEventListener('click', function(e) {
              // 如果點擊的是編輯按鈕，不觸發群組選擇
              if (e.target && e.target.classList && e.target.classList.contains('edit-group-btn')) {
                return;
              }
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
      console.log('選擇群組:', groupId, groupName);
      
      // 移除其他項目的active狀態
      document.querySelectorAll('.user-item').forEach(i => {
        if (i && i.classList) {
          i.classList.remove('active');
        }
      });
      
      // 添加當前項目的active狀態
      const currentElement = document.querySelector(`[data-group-id="${groupId}"]`);
      if (currentElement && currentElement.classList) {
        currentElement.classList.add('active');
      }
      
      // 檢查是否切換到不同的群組
      if (currentGroupId !== groupId || currentChatType !== 'group') {
        // 重置訊息ID並清空聊天區域
        lastMessageId = 0;
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
          chatMessages.innerHTML = '';
        }
      }
      
      // 設置當前聊天類型
      currentChatType = 'group';
      currentGroupId = groupId;
      currentUserId = null;
      currentUserName = groupName;
      
      // 更新聊天標題
      const chatNameElement = document.querySelector('.current-chat-name');
      const chatRoleElement = document.querySelector('.current-chat-role');
      if (chatNameElement) {
        chatNameElement.textContent = groupName;
      }
      if (chatRoleElement) {
        chatRoleElement.textContent = '群組聊天';
      }
      
      // 啟用輸入框
      const messageInput = document.getElementById('messageInput');
      const sendButton = document.querySelector('.chat-input button');
      if (messageInput) {
        messageInput.disabled = false;
      }
      if (sendButton) {
        sendButton.disabled = false;
      }
      
      // 隱藏提示訊息
      const noChatSelected = document.querySelector('.no-chat-selected');
      if (noChatSelected && noChatSelected.classList) {
        noChatSelected.classList.add('hidden');
      }
      
      // 載入群組聊天記錄
      loadGroupChatHistory();
    }
    
    // 編輯群組名稱
    async function editGroupName(groupId, currentName) {
      const newName = prompt('請輸入新的群組名稱:', currentName);
      if (newName && newName.trim() && newName !== currentName) {
        try {
          const formData = new FormData();
          formData.append('action', 'update_group_name');
          formData.append('group_id', groupId);
          formData.append('new_name', newName.trim());
          formData.append('username', username);
          
          const response = await fetch('group_management.php', {
            method: 'POST',
            body: formData
          });
          
          const result = await response.json();
          
          if (result.success) {
            // 更新顯示的群組名稱
            const nameElement = document.getElementById(`group-name-${groupId}`);
            if (nameElement) {
              nameElement.textContent = newName.trim();
            }
            
            // 更新群組項目的data屬性
            const groupItem = document.querySelector(`[data-group-id="${groupId}"]`);
            if (groupItem) {
              groupItem.dataset.groupName = newName.trim();
            }
            
            // 如果當前選中的是這個群組，也要更新聊天標題
            if (currentGroupId === groupId) {
              document.querySelector('.current-chat-name').textContent = newName.trim();
              currentUserName = newName.trim();
            }
            
            alert('群組名稱更新成功！');
            
            // 重新載入群組列表以確保數據同步
            loadGroups();
          } else {
            alert('更新失敗: ' + result.error);
          }
        } catch (error) {
          console.error('更新群組名稱失敗:', error);
          alert('更新群組名稱失敗');
        }
      }
    }
    
    // 載入群組聊天記錄
    async function loadGroupChatHistory() {
      console.log('載入群組聊天記錄，群組ID:', currentGroupId);
      try {
        const response = await fetch('group_management.php?action=get_group_messages&group_id=' + currentGroupId);
        console.log('群組訊息API響應:', response);
        
        const result = await response.json();
        console.log('群組訊息API結果:', result);
        
        if (result.success && result.messages) {
          console.log('群組訊息數量:', result.messages.length);
          displayGroupMessages(result.messages);
          
          if (result.messages.length > 0) {
            lastMessageId = Math.max(...result.messages.map(m => m.id));
            console.log('最後訊息ID:', lastMessageId);
          }
        } else {
          console.log('群組訊息載入失敗或沒有訊息');
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
    
    // 通知設定按鈕事件
    const notificationSettingsBtn = document.getElementById('notificationSettingsBtn');
    if (notificationSettingsBtn) {
      notificationSettingsBtn.addEventListener('click', function() {
        showNotificationSettings();
      });
    }
    
    // 配色方案按鈕事件
    const colorPickerBtn = document.getElementById('colorPickerBtn');
    if (colorPickerBtn) {
      colorPickerBtn.addEventListener('click', function() {
        window.open('color_picker.php', '_blank', 'width=800,height=600');
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
        const displayName = contact.name;
        const role = contact.contact_type;
        const additionalInfo = contact.contact_type === '學生' && contact.grade ? ` - ${contact.grade}` : '';
        html += `
          <div style="margin: 5px 0;">
            <input type="checkbox" id="member_${contact.username}" value="${contact.username}" data-role="${role}">
            <label for="member_${contact.username}">${displayName} (${role})${additionalInfo}</label>
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
    
    // 顯示通知設定
    function showNotificationSettings() {
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
          <h3>🔔 通知設定</h3>
          
          <div style="margin: 15px 0;">
            <label style="display: flex; align-items: center; margin-bottom: 10px;">
              <input type="checkbox" id="chatNotifications" checked style="margin-right: 10px;">
              聊天訊息通知
            </label>
            
            <label style="display: flex; align-items: center; margin-bottom: 10px;">
              <input type="checkbox" id="groupNotifications" checked style="margin-right: 10px;">
              群組訊息通知
            </label>
            
            <label style="display: flex; align-items: center; margin-bottom: 15px;">
              <input type="checkbox" id="systemNotifications" checked style="margin-right: 10px;">
              系統通知
            </label>
          </div>
          
          <div style="margin: 15px 0;">
            <label>安靜時間：</label>
            <div style="display: flex; align-items: center; margin-top: 5px;">
              <input type="time" id="quietStart" value="22:00" style="margin-right: 10px;">
              <span>到</span>
              <input type="time" id="quietEnd" value="08:00" style="margin-left: 10px;">
            </div>
          </div>
          
          <div style="margin: 15px 0;">
            <label>通知權限狀態：</label>
            <div id="notificationStatus" style="margin-top: 5px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
              ${Notification.permission === 'granted' ? 
                '<span style="color: green;">✅ 已啟用推播通知</span>' : 
                '<span style="color: orange;">⚠️ 推播通知未啟用</span>'}
            </div>
          </div>
          
          <div style="text-align: right; margin-top: 20px;">
            <button onclick="closeNotificationSettings()" style="margin-right: 10px; padding: 8px 16px;">取消</button>
            <button onclick="saveNotificationSettings()" style="padding: 8px 16px; background: #2196F3; color: white; border: none; border-radius: 4px;">儲存</button>
          </div>
        </div>
      `;
      
      document.body.appendChild(modal);
      
      // 載入現有設定
      loadNotificationSettings();
    }
    
    // 載入通知設定
    async function loadNotificationSettings() {
      try {
        const response = await fetch('simple_fcm_api.php?action=get_notification_settings&username=' + username);
        const result = await response.json();
        
        if (result.success && result.settings) {
          const settings = result.settings;
          document.getElementById('chatNotifications').checked = settings.chat_notifications;
          document.getElementById('groupNotifications').checked = settings.group_notifications;
          document.getElementById('systemNotifications').checked = settings.system_notifications;
          document.getElementById('quietStart').value = settings.quiet_hours_start || '22:00';
          document.getElementById('quietEnd').value = settings.quiet_hours_end || '08:00';
        }
      } catch (error) {
        console.error('載入通知設定失敗:', error);
      }
    }
    
    // 儲存通知設定
    async function saveNotificationSettings() {
      try {
        const settings = {
          chat_notifications: document.getElementById('chatNotifications').checked,
          group_notifications: document.getElementById('groupNotifications').checked,
          system_notifications: document.getElementById('systemNotifications').checked,
          quiet_hours_start: document.getElementById('quietStart').value,
          quiet_hours_end: document.getElementById('quietEnd').value
        };
        
        const response = await fetch('simple_fcm_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            action: 'update_notification_settings',
            username: username,
            settings: settings
          })
        });
        
        const result = await response.json();
        
        if (result.success) {
          alert('通知設定已儲存！');
          closeNotificationSettings();
        } else {
          alert('儲存失敗: ' + result.error);
        }
        
      } catch (error) {
        console.error('儲存通知設定失敗:', error);
        alert('儲存失敗: ' + error.message);
      }
    }
    
    // 關閉通知設定
    function closeNotificationSettings() {
      const modal = document.querySelector('div[style*="position: fixed"]');
      if (modal) {
        modal.remove();
      }
    }
    
    // 應用配色方案
    function applyColorScheme() {
      // 從PHP獲取配色方案
      const colorScheme = '<?php echo $_SESSION["chat_color_scheme"] ?? "white"; ?>';
      
      // 移除現有的配色方案類
      document.body.classList.remove('color-scheme-white', 'color-scheme-warm', 'color-scheme-mint', 'color-scheme-pink', 'color-scheme-gray', 'color-scheme-blue');
      
      // 添加新的配色方案類
      document.body.classList.add(`color-scheme-${colorScheme}`);
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
        document.querySelectorAll('.user-item').forEach(i => {
          if (i && i.classList) {
            i.classList.remove('active');
          }
        });
        
        // 添加當前項目的active狀態
        if (this && this.classList) {
          this.classList.add('active');
        }
        
        // 獲取用戶資訊
        const newUserId = this.dataset.userId;
        const newUserName = this.dataset.userName;
        
        console.log('切換聯絡人:', {
          from: currentUserId,
          to: newUserId,
          chatType: currentChatType
        });
        
        // 檢查是否切換到不同的用戶
        if (currentUserId !== newUserId || currentChatType !== 'private') {
          // 重置訊息ID並清空聊天區域
          lastMessageId = 0;
          document.getElementById('chatMessages').innerHTML = '';
          // 清除快取，強制重新載入
          const oldCacheKey = `${username}-${currentUserId}`;
          messageCache.delete(oldCacheKey);
          console.log('清除舊快取:', oldCacheKey);
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
        updateVoiceButtonState();
        
        // 隱藏提示訊息
        const noChatSelected = document.querySelector('.no-chat-selected');
        if (noChatSelected && noChatSelected.classList) {
          noChatSelected.classList.add('hidden');
        }
        
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
        console.log('使用快取聊天記錄:', cacheKey);
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
          console.log('儲存快取:', cacheKey, '訊息數量:', result.messages.length);
          
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
        messageDiv.dataset.messageId = message.id;
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        contentDiv.textContent = message.message;
        
        const timeDiv = document.createElement('div');
        timeDiv.className = 'message-time';
        timeDiv.textContent = new Date(message.timestamp).toLocaleString();
        
        // 添加已讀狀態
        if (message.from_user === username) {
          // 自己發送的訊息，顯示已讀狀態
          const readStatusDiv = document.createElement('div');
          readStatusDiv.className = 'read-status';
          readStatusDiv.style.cssText = `
            font-size: 11px; 
            margin-top: 4px; 
            text-align: right;
            font-weight: 500;
          `;
          
          if (message.is_read && message.read_at) {
            readStatusDiv.innerHTML = `
              <span class="read-indicator read">✓ 已讀</span>
              <span class="read-time">${new Date(message.read_at).toLocaleTimeString()}</span>
            `;
          } else {
            readStatusDiv.innerHTML = '<span class="read-indicator unread">⏳ 未讀</span>';
          }
          
          contentDiv.appendChild(readStatusDiv);
        } else {
          // 接收的訊息，標記為已讀
          markMessageAsRead(message.id);
        }
        
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
          console.log('發送群組訊息:', {
            groupId: currentGroupId,
            fromUser: username,
            message: message,
            role: role
          });
          
          // 發送群組訊息
          const formData = new FormData();
          formData.append('action', 'send_group_message');
          formData.append('group_id', currentGroupId);
          formData.append('from_user', username);
          formData.append('message', message);
          formData.append('role', role);
          
          console.log('發送請求到:', 'group_management.php');
          
          const response = await fetch('group_management.php', {
            method: 'POST',
            body: formData
          });
          
          console.log('收到響應:', response);
          
          const result = await response.json();
          console.log('響應結果:', result);
          
          if (result.success) {
            input.value = '';
            console.log('群組訊息發送成功，重新載入聊天記錄');
            // 重新載入群組聊天記錄
            loadGroupChatHistory();
          } else {
            console.error('群組訊息發送失敗:', result.error);
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
    
    // 標記訊息為已讀
    async function markMessageAsRead(messageId) {
      try {
        const response = await fetch('update_read_status.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            action: 'mark_as_read',
            message_ids: [messageId],
            reader: username
          })
        });
        
        const result = await response.json();
        if (result.success) {
          console.log('訊息已標記為已讀:', messageId);
        }
      } catch (error) {
        console.error('標記已讀失敗:', error);
      }
    }
    
    // 獲取未讀訊息數量
    async function getUnreadCount() {
      try {
        const response = await fetch(`update_read_status.php?action=get_unread_count&username=${username}`);
        const result = await response.json();
        
        if (result.success) {
          // 更新未讀數量顯示
          const unreadBadge = document.getElementById('unreadBadge');
          if (unreadBadge) {
            if (result.unread_count > 0) {
              unreadBadge.textContent = result.unread_count;
              unreadBadge.style.display = 'inline';
            } else {
              unreadBadge.style.display = 'none';
            }
          }
        }
      } catch (error) {
        console.error('獲取未讀數量失敗:', error);
      }
    }
    
    // 更新用戶活動時間
    async function updateUserActivity() {
      try {
        const response = await fetch('update_read_status.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            action: 'update_activity',
            username: username,
            is_online: true
          })
        });
        
        const result = await response.json();
        if (result.success) {
          console.log('活動時間已更新');
        }
      } catch (error) {
        console.error('更新活動時間失敗:', error);
      }
    }
    
    // 初始化FCM推播通知
    async function initializeFCM() {
      try {
        // 檢查瀏覽器是否支援通知
        if (!('Notification' in window)) {
          console.log('此瀏覽器不支援推播通知');
          return;
        }
        
        // 請求通知權限
        if (Notification.permission === 'default') {
          const permission = await Notification.requestPermission();
          if (permission !== 'granted') {
            console.log('用戶拒絕了通知權限');
            return;
          }
        }
        
        if (Notification.permission === 'granted') {
          // 註冊FCM token（模擬）
          const fcmToken = 'web-token-' + username + '-' + Date.now();
          await registerFCMToken(fcmToken);
          
          // 設置通知點擊處理
          setupNotificationHandlers();
          
          console.log('FCM推播通知已啟用');
        }
        
      } catch (error) {
        console.error('FCM初始化失敗:', error);
      }
    }
    
    // 註冊FCM token
    async function registerFCMToken(token) {
      try {
        const response = await fetch('simple_fcm_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            action: 'register_token',
            username: username,
            fcm_token: token,
            device_type: 'web',
            device_info: JSON.stringify({
              userAgent: navigator.userAgent,
              platform: navigator.platform,
              language: navigator.language,
              timestamp: new Date().toISOString()
            })
          })
        });
        
        const result = await response.json();
        if (result.success) {
          console.log('FCM token註冊成功');
        } else {
          console.error('FCM token註冊失敗:', result.error);
        }
        
      } catch (error) {
        console.error('註冊FCM token時發生錯誤:', error);
      }
    }
    
    // 設置通知處理器
    function setupNotificationHandlers() {
      // 監聽頁面可見性變化
      document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
          // 頁面隱藏時，可以發送推播通知
          console.log('頁面已隱藏，推播通知已啟用');
        } else {
          // 頁面可見時，更新活動狀態
          updateUserActivity();
        }
      });
      
      // 監聽頁面關閉事件
      window.addEventListener('beforeunload', function() {
        // 標記用戶為離線
        navigator.sendBeacon('update_read_status.php', JSON.stringify({
          action: 'update_activity',
          username: username,
          is_online: false
        }));
      });
    }
    
    // 顯示本地通知
    function showLocalNotification(title, body, data = {}) {
      if (Notification.permission === 'granted') {
        const options = {
          body: body,
          icon: '../assets/icon-192x192.svg',
          badge: '../assets/badge-72x72.svg',
          tag: 'chat-notification',
          data: data,
          requireInteraction: false,
          silent: false
        };
        
        const notification = new Notification(title, options);
        
        notification.onclick = function(event) {
          event.preventDefault();
          window.focus();
          
          if (data.chat_url) {
            window.open(data.chat_url, '_blank');
          }
          
          notification.close();
        };
        
        // 自動關閉通知
        setTimeout(() => {
          notification.close();
        }, 5000);
        
        return notification;
      }
    }
    
    // 按Enter發送訊息
    document.getElementById('messageInput').addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        sendMessage();
      }
    });
    
    // 語音錄製功能 - 類似LINE的開關模式
    function toggleVoiceRecording() {
      if (!voiceRecorder) {
        alert('語音錄製功能尚未初始化');
        return;
      }
      
      if (voiceRecorder.isCurrentlyRecording()) {
        // 如果正在錄製，停止錄製
        voiceRecorder.stopRecording();
      } else {
        // 如果沒有在錄製，開始錄製
        voiceRecorder.startRecording();
      }
    }
    
    // 更新語音按鈕狀態
    function updateVoiceButtonState() {
      const voiceBtn = document.getElementById('voiceRecordBtn');
      const messageInput = document.getElementById('messageInput');
      
      if (voiceBtn && messageInput) {
        // 當輸入框啟用時，語音按鈕也啟用
        voiceBtn.disabled = messageInput.disabled;
      }
    }
    
    // 學生搜尋功能
    <?php if ($role === '老師' || $role === 'teacher'): ?>
    const studentSearchInput = document.getElementById('studentSearch');
    if (studentSearchInput) {
      studentSearchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const userItems = document.querySelectorAll('.user-item[data-contact-type="學生"]');
        
        userItems.forEach(item => {
          const name = item.dataset.userName.toLowerCase();
          const department = (item.dataset.department || '').toLowerCase();
          const grade = (item.dataset.grade || '').toLowerCase();
          const className = (item.dataset.class || '').toLowerCase();
          
          const matches = name.includes(searchTerm) || 
                         department.includes(searchTerm) || 
                         grade.includes(searchTerm) || 
                         className.includes(searchTerm);
          
          if (matches || searchTerm === '') {
            item.style.display = 'flex';
          } else {
            item.style.display = 'none';
          }
        });
        
        // 更新聯絡人計數
        updateContactCount();
      });
    }
    
    // 更新聯絡人計數
    function updateContactCount() {
      const visibleStudents = document.querySelectorAll('.user-item[data-contact-type="學生"]:not([style*="display: none"])');
      const visibleTeachers = document.querySelectorAll('.user-item[data-contact-type="老師"]:not([style*="display: none"])');
      
      const contactListHeader = document.querySelector('#contactList h3');
      if (contactListHeader) {
        const searchTerm = studentSearchInput ? studentSearchInput.value.trim() : '';
        if (searchTerm) {
          contactListHeader.textContent = `聯絡人 (學生: ${visibleStudents.length}, 老師: ${visibleTeachers.length})`;
        } else {
          contactListHeader.textContent = '聯絡人';
        }
      }
    }
    <?php endif; ?>
    
    // 初始化群組列表
    if (role === '老師' || role === 'teacher' || role === '學生' || role === 'student') {
      loadGroups();
    }
    
    // 初始化已讀功能
    updateUserActivity(); // 更新活動時間
    getUnreadCount(); // 獲取未讀數量
    
    // 初始化FCM推播通知
    initializeFCM();
    
    // 應用配色方案
    applyColorScheme();
    
    // 定期更新未讀數量和活動時間（每30秒）
    setInterval(() => {
      getUnreadCount();
      updateUserActivity();
    }, 30000);
    
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
</main>
<?php include("../share/footer.php"); ?>
</body>
</html>
