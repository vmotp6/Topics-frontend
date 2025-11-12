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
        
        // 獲取所有老師
        $stmt = $pdo->prepare("SELECT t.user_id, t.name, t.department, u.username, '老師' as contact_type
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
        
        if ($currentTeacher && !empty($currentTeacher['department'])) {
            $department = $currentTeacher['department'];
            
            // 獲取同科系的老師
            $stmt = $pdo->prepare("SELECT t.user_id, t.name, t.department, u.username, '老師' as contact_type
                                  FROM teacher t 
                                  JOIN user u ON t.user_id = u.id 
                                  WHERE u.role = '老師' AND t.department = ? AND u.username != ?
                                  ORDER BY t.name");
            $stmt->execute([$department, $username]);
            $sameDeptTeachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $contacts = array_merge($contacts, $sameDeptTeachers);
        } else {
            // 如果當前老師沒有科系資料，獲取所有老師（排除自己）
            $stmt = $pdo->prepare("SELECT t.user_id, t.name, t.department, u.username, '老師' as contact_type
                                  FROM teacher t 
                                  JOIN user u ON t.user_id = u.id 
                                  WHERE u.role = '老師' AND u.username != ?
                                  ORDER BY t.name");
            $stmt->execute([$username]);
            $allTeachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $contacts = array_merge($contacts, $allTeachers);
        }
        
        // 獲取所有學生（無論老師是否有科系資料，都應該能看到所有學生）
        $allStudents = [];
        try {
            // 優先使用 student 表（如果有完整的學生資料）
            $stmt = $pdo->prepare("SELECT s.user_id, s.name, s.department, u.username, '學生' as contact_type, s.grade, s.class_name
                                  FROM student s 
                                  JOIN user u ON s.user_id = u.id 
                                  WHERE u.role = '學生'
                                  ORDER BY s.department, s.grade, s.name");
            $stmt->execute();
            $studentsFromTable = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $allStudents = array_merge($allStudents, $studentsFromTable);
        } catch (PDOException $e) {
            error_log("從 student 表載入學生失敗: " . $e->getMessage());
        }
        
        // 無論如何，也從 user 表載入所有學生（確保不遺漏任何學生，特別是如果 student 表缺少某些學生）
        try {
            $stmt = $pdo->prepare("SELECT u.id as user_id, COALESCE(u.name, u.username) as name, '未設定' as department, u.username, '學生' as contact_type, '未設定' as grade, '未設定' as class_name
                                  FROM user u 
                                  WHERE u.role = '學生'
                                  ORDER BY u.username");
            $stmt->execute();
            $studentsFromUser = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 合併結果，避免重複（根據 username 去重）
            $existingUsernames = array_column($allStudents, 'username');
            foreach ($studentsFromUser as $student) {
                if (!in_array($student['username'], $existingUsernames)) {
                    $allStudents[] = $student;
                }
            }
        } catch (PDOException $e) {
            error_log("從 user 表載入學生失敗: " . $e->getMessage());
        }
        
        $contacts = array_merge($contacts, $allStudents);
        
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
  <style>
    /* 未讀徽章脈動動畫 */
    @keyframes pulse {
      0% {
        transform: scale(1);
        box-shadow: 0 2px 4px rgba(255, 68, 68, 0.4);
      }
      50% {
        transform: scale(1.1);
        box-shadow: 0 2px 8px rgba(255, 68, 68, 0.6);
      }
      100% {
        transform: scale(1);
        box-shadow: 0 2px 4px rgba(255, 68, 68, 0.4);
      }
    }
    
    .unread-badge.pulse {
      animation: pulse 2s ease-in-out infinite;
    }
    
    /* 優化頭像顯示 */
    .user-avatar {
      position: relative;
      transition: transform 0.2s ease;
      overflow: visible !important; /* 確保徽章可以顯示在頭像外 */
    }
    
    .user-item:hover .user-avatar {
      transform: scale(1.05);
    }
    
    /* 聯絡人列表項優化 */
    .user-item {
      transition: background-color 0.2s ease;
    }
    
    .user-item:hover {
      background-color: #f5f5f5;
    }
    
    .user-item.active {
      background-color: #e3f2fd;
    }
    
    /* 聊天訊息區域優化 */
    .chat-messages {
      scroll-behavior: smooth;
    }
    
    /* 發送按鈕優化 */
    .chat-input button {
      transition: background-color 0.2s ease, transform 0.1s ease;
    }
    
    .chat-input button:hover:not(:disabled) {
      transform: translateY(-1px);
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .chat-input button:active:not(:disabled) {
      transform: translateY(0);
    }
  </style>
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
                <div class="user-avatar" style="position: relative;">
                  <?php 
                  // 顯示中文姓名的第一個字符
                  $name = $contact['name'];
                  $firstChar = mb_substr($name, 0, 1, 'UTF-8');
                  echo $firstChar;
                  ?>
                  <span class="unread-badge" data-contact-id="<?php echo $contact['username']; ?>" style="position: absolute; top: -4px; right: -4px;">0</span>
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
        
        <div class="chat-messages" id="chatMessages_student">
          <div class="no-chat-selected">
            請從左側選擇一位老師開始聊天
          </div>
        </div>
        
        <div class="chat-input">
          <input type="text" id="messageInput_student" placeholder="輸入訊息..." disabled>
          <button id="voiceRecordBtn_student" onclick="toggleVoiceRecording()" disabled title="語音輸入">🎤 語音</button>
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
          <div style="margin-top: 10px; position: relative; z-index: 10;">
            <?php if ($role === '老師' || $role === 'teacher'): ?>
            <button id="createGroupBtn" style="margin-right: 5px; padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; position: relative; z-index: 10; pointer-events: auto;">建立群組</button>
            <?php endif; ?>
            <button id="notificationSettingsBtn" style="margin-right: 5px; padding: 8px 16px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer; position: relative; z-index: 10; pointer-events: auto;">🔔 通知設定</button>
            <button id="colorPickerBtn" style="padding: 8px 16px; background: #6f42c1; color: white; border: none; border-radius: 4px; cursor: pointer; position: relative; z-index: 10; pointer-events: auto;">🎨 配色方案</button>
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
                <div class="user-avatar" style="position: relative;">
                  <?php 
                  // 顯示中文姓名的第一個字符
                  $name = $contact['name'];
                  $firstChar = mb_substr($name, 0, 1, 'UTF-8');
                  echo $firstChar;
                  ?>
                  <span class="unread-badge" data-contact-id="<?php echo $contact['username']; ?>" style="position: absolute; top: -4px; right: -4px;">0</span>
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
    
    // 已讀聯絡人列表（已點擊並標記為已讀的聯絡人）
    // 從 localStorage 恢復已讀列表
    function loadReadContactsFromStorage() {
      try {
        const stored = localStorage.getItem(`readContacts_${username}`);
        if (stored) {
          const readList = JSON.parse(stored);
          return new Set(readList);
        }
      } catch (e) {
        console.warn('載入已讀列表失敗:', e);
      }
      return new Set();
    }
    
    // 保存已讀列表到 localStorage（僅用於當前會話期間的臨時狀態）
    function saveReadContactsToStorage() {
      try {
        const readList = Array.from(readContacts);
        // 不再持久化到 localStorage，因為資料庫的實際狀態才是權威來源
        // localStorage.setItem(`readContacts_${username}`, JSON.stringify(readList));
        console.log('已讀列表（僅當前會話）:', readList);
      } catch (e) {
        console.warn('保存已讀列表失敗:', e);
      }
    }
    
    // 不再從 localStorage 載入已讀列表，因為資料庫的實際狀態才是權威來源
    // readContacts 只用於當前會話期間的臨時狀態（例如用戶點擊了頭像但資料庫還沒更新完成）
    let readContacts = new Set(); // 從空 Set 開始，不再從 localStorage 恢復
    
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
        const chatMessages = getChatMessagesElement();
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
      const messageInput = getMessageInputElement();
      if (messageInput) {
        messageInput.disabled = false;
        // 更新按鈕狀態（此時輸入框為空，按鈕應為禁用狀態）
        updateSendButtonState();
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
      const chatMessages = getChatMessagesElement();
      
      // 清空聊天區域
      if (!chatMessages) return;
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
          newUserName: newUserName,
          chatType: currentChatType,
          username: username,
          role: role,
          elementData: {
            userId: this.dataset.userId,
            userName: this.dataset.userName,
            chatType: this.dataset.chatType
          }
        });
        
        // 驗證必要資料
        if (!newUserId || !newUserName) {
          console.error('無法切換聯絡人: 缺少必要資料', {
            newUserId,
            newUserName,
            dataset: this.dataset
          });
          alert('無法選擇此聯絡人，資料不完整');
          return;
        }
        
        // 檢查是否切換到不同的用戶
        if (currentUserId !== newUserId || currentChatType !== 'private') {
          // 重置訊息ID並清空聊天區域
          lastMessageId = 0;
          const chatMessages = getChatMessagesElement();
          if (chatMessages) {
            chatMessages.innerHTML = '';
          }
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
        const userRole = this.querySelector('.user-role')?.textContent || '';
        
        console.log('已更新當前用戶資訊:', {
          currentUserId,
          currentUserName,
          currentChatType,
          userRole,
          username,
          role
        });
        
        // 更新聊天標題
        const chatNameElement = document.querySelector('.current-chat-name');
        const chatRoleElement = document.querySelector('.current-chat-role');
        if (chatNameElement) {
          chatNameElement.textContent = currentUserName;
        } else {
          console.warn('找不到 .current-chat-name 元素');
        }
        if (chatRoleElement) {
          chatRoleElement.textContent = userRole;
        } else {
          console.warn('找不到 .current-chat-role 元素');
        }
        
        // 啟用輸入框
        const messageInput = getMessageInputElement();
        if (messageInput) {
          messageInput.disabled = false;
          console.log('已啟用輸入框:', messageInput.id);
        } else {
          console.error('找不到訊息輸入框元素');
        }
        
        // 更新按鈕狀態（此時輸入框為空，按鈕應為禁用狀態）
        updateSendButtonState();
        updateVoiceButtonState();
        
        // 隱藏提示訊息
        const noChatSelected = document.querySelector('.no-chat-selected');
        if (noChatSelected && noChatSelected.classList) {
          noChatSelected.classList.add('hidden');
        }
        
        // 立即清除該聯絡人的未讀徽章（即時回饋，不等待API響應）
        const contactBadge = document.querySelector(`.unread-badge[data-contact-id="${newUserId}"]`);
        if (contactBadge) {
          // 添加消失動畫
          if (contactBadge.classList.contains('show')) {
          contactBadge.classList.add('hiding');
          contactBadge.classList.remove('pulse');
          
          setTimeout(() => {
            contactBadge.classList.remove('show', 'hiding');
              contactBadge.style.display = 'none'; // 完全隱藏
              contactBadge.style.visibility = 'hidden'; // 完全隱藏
          }, 250);
          } else {
            // 如果徽章沒有顯示，也確保它被完全隱藏
          contactBadge.classList.remove('show', 'pulse', 'hiding');
            contactBadge.style.display = 'none'; // 完全隱藏
            contactBadge.style.visibility = 'hidden'; // 完全隱藏
          }
        }
        
        // 載入聊天記錄（清除快取後重新載入）
        const cacheKey = `${username}-${newUserId}`;
        messageCache.delete(cacheKey);
        loadChatHistory();
        
        // 立即將該聯絡人添加到已讀列表（即使API還未完成）
        readContacts.add(newUserId);
        saveReadContactsToStorage(); // 立即保存到 localStorage
        
        // 在背景標記該聯絡人的所有未讀訊息為已讀（不阻塞UI）
        markContactMessagesAsRead(newUserId).then((success) => {
          if (success) {
            console.log(`✅ ${newUserId} 的未讀訊息已標記為已讀`);
            // 確保在已讀列表中
            readContacts.add(newUserId);
            saveReadContactsToStorage(); // 保存到 localStorage
          } else {
            console.warn(`⚠️ ${newUserId} 的未讀訊息標記可能失敗，但UI已更新`);
            // 即使標記失敗，也保持UI狀態（已點擊過）
            readContacts.add(newUserId);
            saveReadContactsToStorage(); // 保存到 localStorage
          }
        }).catch((error) => {
          console.error('標記聯絡人訊息為已讀時發生錯誤:', error);
          // 即使出錯也繼續，不影響用戶體驗
          readContacts.add(newUserId);
          saveReadContactsToStorage();
        });
          
          // 延遲更新所有聯絡人的未讀計數（確保資料庫已更新）
          setTimeout(() => {
            updateContactUnreadCounts();
          }, 800);
      });
    });

    // 獲取聊天訊息容器（支援不同介面）
    function getChatMessagesElement() {
      return document.getElementById('chatMessages') || document.getElementById('chatMessages_student');
    }
    
    // 獲取訊息輸入框（支援不同介面）
    function getMessageInputElement() {
      return document.getElementById('messageInput') || document.getElementById('messageInput_student');
    }
    
    // 獲取語音按鈕（支援不同介面）
    function getVoiceRecordBtnElement() {
      return document.getElementById('voiceRecordBtn') || document.getElementById('voiceRecordBtn_student');
    }
    
    // 載入聊天記錄
    async function loadChatHistory() {
      const chatMessages = getChatMessagesElement();
      const cacheKey = `${username}-${currentUserId}`;
      
      // 檢查快取
      if (messageCache.has(cacheKey)) {
        const cachedData = messageCache.get(cacheKey);
        displayMessages(cachedData.messages);
        lastMessageId = cachedData.lastMessageId;
        console.log('使用快取聊天記錄:', cacheKey);
        return;
      }
      
      // 檢查元素是否存在
      if (!chatMessages) {
        console.error('找不到聊天訊息容器元素，無法載入聊天記錄');
        return;
      }
      
      // 顯示載入指示器
      chatMessages.innerHTML = '<div style="text-align: center; padding: 20px; color: #666;">載入中...</div>';
      
      try {
        const startTime = performance.now();
        const response = await fetch('load_private_messages.php?from=' + username + '&to=' + currentUserId);
        
        const result = await response.json();
        
        if (result.success && result.messages) {
          // 確保訊息ID是數字
          result.messages = result.messages.map(m => ({
            ...m,
            id: parseInt(m.id) || 0
          }));
          
          displayMessages(result.messages);
          
          if (result.messages.length > 0) {
            lastMessageId = Math.max(...result.messages.map(m => m.id));
          } else {
            lastMessageId = 0;
          }
          
          // 儲存到快取
          messageCache.set(cacheKey, {
            messages: result.messages,
            lastMessageId: lastMessageId,
            timestamp: Date.now()
          });
          console.log('儲存快取:', cacheKey, '訊息數量:', result.messages.length, '最後ID:', lastMessageId);
          
          const loadTime = performance.now() - startTime;
          console.log(`聊天記錄載入完成，耗時: ${loadTime.toFixed(2)}ms`);
        } else {
          console.error('載入聊天記錄失敗:', result.error || result);
          if (chatMessages) {
          chatMessages.innerHTML = '<div style="text-align: center; padding: 20px; color: #999;">載入失敗: ' + (result.error || '未知錯誤') + '</div>';
          }
        }
      } catch (error) {
        console.error('載入聊天記錄失敗:', error);
        if (chatMessages) {
        chatMessages.innerHTML = '<div style="text-align: center; padding: 20px; color: #999;">載入失敗</div>';
        }
      }
    }
    
    // 顯示訊息
    function displayMessages(messages) {
      const chatMessages = getChatMessagesElement();
      
      // 檢查元素是否存在
      if (!chatMessages) {
        console.error('找不到聊天訊息容器元素 (chatMessages 或 chatMessages_student)');
        return;
      }
      
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
        
        // 檢查訊息是否包含圖片URL，並正確處理
        let messageText = message.message || '';
        
        // 如果訊息是圖片URL，檢查並修正路徑
        if (messageText && (messageText.includes('.jpg') || messageText.includes('.png') || messageText.includes('.gif') || messageText.includes('.jpeg') || messageText.includes('.webp'))) {
          let imageUrl = messageText.trim();
          
          // 處理各種圖片路徑格式
          // 優先處理：如果已經是完整 URL，直接使用
          if (imageUrl.startsWith('http://') || imageUrl.startsWith('https://')) {
            // 已經是完整 URL，不需要處理
          } 
          // 處理絕對路徑（以 / 開頭）
          else if (imageUrl.startsWith('/')) {
            // 如果是 /Topics-frontend/frontend/chat/share/ 格式，改為 /Topics-frontend/frontend/share/
            if (imageUrl.includes('/chat/share/')) {
              imageUrl = imageUrl.replace('/chat/share/', '/share/');
            } 
            // 如果是 /Topics-frontend/frontend/chat/share/filename.jpg 格式
            else if (imageUrl.includes('Topics-frontend/frontend/chat/share/')) {
              imageUrl = imageUrl.replace('Topics-frontend/frontend/chat/share/', 'Topics-frontend/frontend/share/');
            }
            // 如果是 /frontend/chat/share/ 格式
            else if (imageUrl.includes('/frontend/chat/share/')) {
              imageUrl = imageUrl.replace('/frontend/chat/share/', '/frontend/share/');
            }
          }
          // 處理相對路徑
          else {
            // 提取文件名（無論路徑格式如何）
            const fileName = imageUrl.split('/').pop();
            
            // 處理各種可能的錯誤路徑格式
            if (imageUrl.includes('chat/share/') || 
                imageUrl.includes('/frontend/chat/share/') || 
                imageUrl.includes('Topics-frontend/') ||
                imageUrl.includes('frontend/chat/share/')) {
              // 從任何包含 chat/share 的路徑中提取文件名
              imageUrl = '../share/' + fileName;
            } else if (imageUrl.includes('/share/')) {
              // 處理包含 "/share/" 但不是 chat/share 的路徑
              const shareIndex = imageUrl.indexOf('/share/');
              imageUrl = '../share/' + imageUrl.substring(shareIndex + 7);
            } else if (imageUrl.startsWith('share/')) {
              // 處理 "share/filename.jpg" 格式
              imageUrl = '../share/' + imageUrl.substring(6);
            } else if (!imageUrl.includes('/')) {
              // 如果只是文件名（如 "EIdROxGXsAE_LSs.jpg"），直接加上 share 路徑
              imageUrl = '../share/' + imageUrl;
            } else {
              // 其他情況，嘗試提取文件名
                imageUrl = '../share/' + fileName;
            }
          }
          
          // 創建圖片元素（無論路徑格式如何）
          const img = document.createElement('img');
          img.src = imageUrl;
          img.alt = '分享的圖片';
          img.style.cssText = 'max-width: 300px; max-height: 300px; border-radius: 8px; margin-top: 5px; cursor: pointer; display: block; object-fit: contain;';
          img.onerror = function() {
            // 如果圖片載入失敗，嘗試其他可能的路徑
            const originalUrl = message.message || messageText;
            const fileName = originalUrl.split('/').pop();
            const alternativePaths = [
              '../share/' + fileName,
              '/Topics-frontend/frontend/share/' + fileName,
              '/frontend/share/' + fileName,
              '/share/' + fileName,
              '../../share/' + fileName
            ];
            
            let tried = 0;
            const tryNext = () => {
              if (tried < alternativePaths.length) {
                this.src = alternativePaths[tried++];
              } else {
                // 所有路徑都失敗，顯示錯誤訊息
                this.style.display = 'none';
                const errorDiv = document.createElement('div');
                errorDiv.textContent = '圖片載入失敗';
                errorDiv.style.cssText = 'color: #999; font-size: 12px; font-style: italic; padding: 5px;';
                contentDiv.appendChild(errorDiv);
                console.warn('圖片載入失敗，原始URL:', message.message, '嘗試的URL:', imageUrl, '替代路徑:', alternativePaths);
              }
            };
            
            this.onerror = tryNext;
            tryNext();
          };
          img.onload = function() {
            console.log('圖片載入成功:', imageUrl);
          };
          img.onclick = function() {
            // 點擊圖片可以查看大圖
            window.open(this.src, '_blank');
          };
          contentDiv.appendChild(img);
        } else {
          // 普通文字訊息
          contentDiv.textContent = messageText;
        }
        
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
        
        // 當顯示接收的訊息後，更新聯絡人未讀計數（因為訊息已讀）
        if (message.from_user !== username) {
          setTimeout(() => {
            updateContactUnreadCounts();
          }, 500);
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
      const input = getMessageInputElement();
      const message = input.value.trim();
      
      if (!message) return;
      
      // 發送時禁用按鈕
      const sendButton = document.querySelector('.chat-input button[onclick="sendMessage()"]');
      if (sendButton) {
        sendButton.disabled = true;
        sendButton.style.opacity = '0.5';
        sendButton.style.cursor = 'not-allowed';
      }
      
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
            // 更新按鈕狀態（輸入框已清空）
            updateSendButtonState();
            // 重新載入群組聊天記錄
            loadGroupChatHistory();
          } else {
            console.error('群組訊息發送失敗:', result.error);
            alert('發送失敗: ' + (result.error || '未知錯誤'));
            // 發送失敗時也更新按鈕狀態
            updateSendButtonState();
          }
        } else {
          // 發送私聊訊息
          if (!currentUserId) {
            console.error('無法發送訊息: currentUserId 為空', {
              currentUserId,
              currentUserName,
              currentChatType,
              username,
              role
            });
            alert('請先選擇一個聯絡人');
            return;
          }
          
          console.log('發送私聊訊息:', {
            from: username,
            to: currentUserId,
            message: message.substring(0, 50) + '...',
            role: role
          });
          
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
            console.log('訊息發送成功:', result);
            
            // 如果有保存的訊息資料，直接添加到顯示中
            if (result.saved_message) {
              const chatMessages = getChatMessagesElement();
              
              // 檢查元素是否存在
              if (!chatMessages) {
                console.error('找不到聊天訊息容器元素，無法顯示新訊息，但訊息已成功發送');
                // 即使找不到元素，也清除快取以便下次載入時顯示
                const cacheKey = `${username}-${currentUserId}`;
                messageCache.delete(cacheKey);
                return;
              }
              
              // 將新訊息添加到顯示中
              const messageDiv = document.createElement('div');
              messageDiv.className = 'message sent';
              messageDiv.dataset.messageId = result.saved_message.id;
              
              const contentDiv = document.createElement('div');
              contentDiv.className = 'message-content';
              contentDiv.textContent = result.saved_message.message;
              
              const timeDiv = document.createElement('div');
              timeDiv.className = 'message-time';
              timeDiv.textContent = new Date(result.saved_message.timestamp).toLocaleString();
              
              // 添加未讀狀態（自己的訊息）
              const readStatusDiv = document.createElement('div');
              readStatusDiv.className = 'read-status';
              readStatusDiv.style.cssText = 'font-size: 11px; margin-top: 4px; text-align: right;';
              readStatusDiv.innerHTML = '<span class="read-indicator unread">⏳ 未讀</span>';
              
              contentDiv.appendChild(readStatusDiv);
              contentDiv.appendChild(timeDiv);
              messageDiv.appendChild(contentDiv);
              
              if (chatMessages) {
              chatMessages.appendChild(messageDiv);
              
              // 滾動到底部
              chatMessages.scrollTop = chatMessages.scrollHeight;
              }
              
              // 更新 lastMessageId
              lastMessageId = Math.max(lastMessageId, result.saved_message.id);
              
              // 清除快取，確保下次載入時獲取最新資料
              const cacheKey = `${username}-${currentUserId}`;
              messageCache.delete(cacheKey);
              
              console.log('訊息已立即顯示，ID:', result.saved_message.id);
            } else {
              // 如果沒有返回訊息資料，清除快取並重新載入
              const cacheKey = `${username}-${currentUserId}`;
              messageCache.delete(cacheKey);
              loadChatHistory();
            }
          } else {
            console.error('發送失敗:', result.error);
            alert('發送失敗: ' + (result.error || '未知錯誤'));
          }
        }
      } catch (error) {
        console.error('發送訊息失敗:', error);
        alert('發送失敗: ' + (error.message || '未知錯誤'));
      } finally {
        // 發送完成後，恢復按鈕狀態
        const sendButton = document.querySelector('.chat-input button[onclick="sendMessage()"]');
        if (sendButton) {
          sendButton.disabled = false;
          sendButton.style.opacity = '1';
          sendButton.style.cursor = 'pointer';
        }
        // 更新按鈕狀態（根據輸入框內容）
        updateSendButtonState();
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
    
    // 標記聯絡人的所有未讀訊息為已讀
    async function markContactMessagesAsRead(contactUsername) {
      try {
        if (!contactUsername || !username) {
          console.error('標記已讀失敗: 缺少必要的用戶名');
          return false;
        }
        
        console.log(`🔍 開始標記 ${contactUsername} 的未讀訊息為已讀...`);
        
        let unreadMessageIds = [];
        
        // 直接從資料庫查詢該聯絡人的所有未讀訊息ID（更可靠）
        try {
        const queryResponse = await fetch(`update_read_status.php?action=get_unread_messages&from=${encodeURIComponent(contactUsername)}&to=${encodeURIComponent(username)}`);
          if (queryResponse && queryResponse.ok) {
        const queryResult = await queryResponse.json();
        if (queryResult.success && queryResult.message_ids && queryResult.message_ids.length > 0) {
          unreadMessageIds = queryResult.message_ids;
          console.log(`📬 從資料庫查詢到 ${unreadMessageIds.length} 條來自 ${contactUsername} 的未讀訊息`);
            }
          }
        } catch (queryError) {
          console.warn('查詢未讀訊息失敗，將從聊天記錄中獲取:', queryError);
        }
        
        // 如果沒有從專門API獲取到，則從聊天記錄中獲取
        if (unreadMessageIds.length === 0) {
          try {
          const response = await fetch(`load_private_messages.php?from=${encodeURIComponent(contactUsername)}&to=${encodeURIComponent(username)}`);
            if (response && response.ok) {
          const result = await response.json();
          
          if (result.success && result.messages) {
            // 找出所有未讀的訊息ID（發送者是聯絡人，接收者是當前用戶）
            unreadMessageIds = result.messages
              .filter(msg => {
                const isFromContact = msg.from_user === contactUsername;
                const isToCurrentUser = msg.to_user === username;
                const isUnread = !msg.is_read || msg.is_read === false || msg.is_read === 0 || msg.is_read === null;
                return isFromContact && isToCurrentUser && isUnread;
              })
              .map(msg => parseInt(msg.id));
              }
            }
          } catch (loadError) {
            console.warn('從聊天記錄中獲取未讀訊息失敗:', loadError);
          }
        }
        
        if (unreadMessageIds.length > 0) {
          console.log(`📬 準備標記 ${unreadMessageIds.length} 條來自 ${contactUsername} 的未讀訊息為已讀...`);
          
          // 批次標記為已讀
          let markResponse;
          let markResult = { success: false };
          
          try {
            markResponse = await fetch('update_read_status.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              action: 'mark_as_read',
              message_ids: unreadMessageIds,
              reader: username
            })
          });
          
            if (markResponse && markResponse.ok) {
              markResult = await markResponse.json();
            }
          } catch (markError) {
            console.error('標記已讀請求失敗:', markError);
            return false;
          }
          
            if (markResult.success) {
            console.log(`✅ 已成功標記 ${unreadMessageIds.length} 條訊息為已讀`);
            
            // 將該聯絡人添加到已讀列表
            readContacts.add(contactUsername);
            saveReadContactsToStorage(); // 保存到 localStorage
            
            // 立即更新該聯絡人的徽章狀態（完全隱藏）
            const contactBadge = document.querySelector(`.unread-badge[data-contact-id="${contactUsername}"]`);
            if (contactBadge) {
              contactBadge.classList.remove('show', 'pulse', 'hiding');
              contactBadge.style.display = 'none'; // 完全隱藏
              contactBadge.style.visibility = 'hidden'; // 完全隱藏
            }
            
            // 清除該聯絡人的聊天記錄快取
            const cacheKey = `${username}-${contactUsername}`;
            messageCache.delete(cacheKey);
            
            // 更新已讀狀態顯示
            unreadMessageIds.forEach(msgId => {
              const msgElement = document.querySelector(`[data-message-id="${msgId}"]`);
              if (msgElement) {
                const readStatusDiv = msgElement.querySelector('.read-status');
                if (readStatusDiv) {
                  readStatusDiv.innerHTML = '<span class="read-indicator read">✓ 已讀</span>';
                }
              }
            });
            
            return true; // 標記成功
          } else {
            console.error('❌ 標記已讀失敗:', markResult.error);
            return false;
          }
        } else {
          console.log(`ℹ️ ${contactUsername} 沒有未讀訊息`);
          
          // 將該聯絡人添加到已讀列表（沒有未讀訊息也算已讀）
          readContacts.add(contactUsername);
          saveReadContactsToStorage(); // 保存到 localStorage
          
          // 即使沒有未讀訊息，也確保徽章被完全隱藏
          const contactBadge = document.querySelector(`.unread-badge[data-contact-id="${contactUsername}"]`);
          if (contactBadge) {
            contactBadge.classList.remove('show', 'pulse', 'hiding');
            contactBadge.style.display = 'none'; // 完全隱藏
            contactBadge.style.visibility = 'hidden'; // 完全隱藏
          }
          return true;
        }
      } catch (error) {
        console.error('❌ 標記聯絡人訊息為已讀失敗:', error);
        return false;
      }
    }
    
    // 獲取未讀訊息數量（總數）
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
    
    // 獲取每個聯絡人的未讀訊息數量
    async function updateContactUnreadCounts() {
      try {
        const response = await fetch(`get_contact_unread_count.php?username=${encodeURIComponent(username)}`);
        const result = await response.json();
        
        console.log('未讀計數API響應:', result);
        
        if (result.success && result.unread_counts) {
          console.log('未讀計數數據:', result.unread_counts);
          console.log('調試信息:', result.debug);
          
          // 詳細檢查未讀計數數據
          const unreadEntries = Object.entries(result.unread_counts);
          console.log(`📊 未讀計數詳情: ${unreadEntries.length} 個聯絡人有未讀訊息`);
          unreadEntries.forEach(([username, count]) => {
            console.log(`  - ${username}: ${count} 條未讀訊息`);
          });
          
          // 檢查是否有任何徽章元素
          const allBadges = document.querySelectorAll('.unread-badge');
          console.log(`找到 ${allBadges.length} 個未讀徽章元素`);
          
          // 檢查每個徽章的元素詳情
          allBadges.forEach((badge, index) => {
            const contactId = badge.getAttribute('data-contact-id');
            console.log(`徽章 ${index + 1}: data-contact-id="${contactId}", classes="${badge.className}", display="${window.getComputedStyle(badge).display}"`);
          });
          
          // 更新每個聯絡人的未讀徽章
          // 先隱藏所有徽章
          allBadges.forEach(badge => {
            badge.classList.remove('show', 'pulse');
          });
          
          // 然後顯示有未讀訊息的聯絡人徽章
          let updatedCount = 0;
          Object.keys(result.unread_counts).forEach(contactUsername => {
            const unreadCount = result.unread_counts[contactUsername];
            
            // 如果當前正在查看該聯絡人的聊天，強制隱藏徽章（因為正在查看，應該已經被標記為已讀）
            if (currentUserId === contactUsername && currentChatType === 'private') {
              const badge = document.querySelector(`.unread-badge[data-contact-id="${contactUsername}"]`);
              if (badge) {
                badge.classList.remove('show', 'pulse', 'hiding');
                badge.style.display = 'none'; // 完全隱藏
                badge.style.visibility = 'hidden'; // 完全隱藏
              }
              // 添加到已讀列表（僅在當前會話期間）
              readContacts.add(contactUsername);
              return;
            }
            
            // 注意：不再根據 readContacts 來跳過顯示徽章
            // 因為資料庫的實際狀態才是權威來源，localStorage 中的 readContacts 
            // 只用於當前會話期間的臨時狀態（例如用戶點擊了頭像但資料庫還沒更新完成）
            
            const badge = document.querySelector(`.unread-badge[data-contact-id="${contactUsername}"]`);
            console.log(`檢查聯絡人 "${contactUsername}": 未讀=${unreadCount}, 徽章=${badge ? '找到' : '未找到'}`);
            
            if (badge) {
              if (unreadCount > 0) {
                badge.textContent = unreadCount > 99 ? '99+' : unreadCount.toString();
                badge.setAttribute('data-count', unreadCount.toString());
                
                // 移除隱藏動畫類（如果存在）
                badge.classList.remove('hiding');
                
                // 如果之前沒有顯示，添加顯示效果
                if (!badge.classList.contains('show')) {
                  badge.classList.add('show', 'pulse');
                  console.log(`🔵 添加 show 類到徽章: ${contactUsername}`);
                } else if (!badge.classList.contains('pulse')) {
                  badge.classList.add('pulse');
                }
                
                // 強制顯示徽章（調試用）
                badge.style.display = 'flex';
                badge.style.visibility = 'visible';
                badge.style.opacity = '1';
                
                // 檢查計算後的樣式
                const computedStyle = window.getComputedStyle(badge);
                console.log(`🔍 徽章樣式檢查 (${contactUsername}):`, {
                  display: computedStyle.display,
                  visibility: computedStyle.visibility,
                  opacity: computedStyle.opacity,
                  classes: badge.className,
                  textContent: badge.textContent
                });
                
                updatedCount++;
                console.log(`✅ 顯示未讀徽章: ${contactUsername} = ${unreadCount}`);
              } else {
                // 未讀數量為0，完全隱藏徽章
                badge.classList.remove('show', 'pulse', 'hiding');
                badge.style.display = 'none'; // 強制隱藏
                badge.style.visibility = 'hidden'; // 強制隱藏
                badge.textContent = '0';
                badge.setAttribute('data-count', '0');
                console.log(`🔴 隱藏徽章（未讀為0）: ${contactUsername}`);
              }
            } else {
              // 如果找不到徽章，嘗試為該聯絡人創建徽章
              // 先嘗試查找所有可能的聯絡人項目（包括被隱藏的）
              let contactItem = document.querySelector(`.user-item[data-user-id="${contactUsername}"]`);
              
              // 如果找不到，可能是被搜尋功能隱藏了，使用更寬鬆的選擇器
              if (!contactItem) {
                // 檢查是否有任何匹配的項目（包括被隱藏的）
                const allItems = document.querySelectorAll('.user-item');
                for (let item of allItems) {
                  if (item.getAttribute('data-user-id') === contactUsername) {
                    contactItem = item;
                    break;
                  }
                }
              }
              
              if (contactItem) {
                const avatar = contactItem.querySelector('.user-avatar');
                if (avatar) {
                  // 檢查是否已有徽章（可能因為某些原因選擇器沒找到）
                  let existingBadge = avatar.querySelector('.unread-badge');
                  
                  // 如果當前正在查看該聯絡人的聊天，不顯示徽章
                  if (currentUserId === contactUsername && currentChatType === 'private') {
                    if (existingBadge) {
                      existingBadge.classList.remove('show', 'pulse', 'hiding');
                      existingBadge.style.display = 'none'; // 完全隱藏
                      existingBadge.style.visibility = 'hidden'; // 完全隱藏
                    }
                    console.log(`ℹ️ 正在查看 "${contactUsername}" 的聊天，不顯示徽章`);
                  } else if (!existingBadge) {
                    // 創建新徽章
                    const newBadge = document.createElement('span');
                    newBadge.className = 'unread-badge';
                    newBadge.setAttribute('data-contact-id', contactUsername);
                    newBadge.style.cssText = 'position: absolute; top: -4px; right: -4px;';
                    newBadge.textContent = unreadCount > 0 ? (unreadCount > 99 ? '99+' : unreadCount.toString()) : '0';
                    newBadge.setAttribute('data-count', unreadCount.toString());
                    
                    if (unreadCount > 0) {
                      newBadge.classList.add('show', 'pulse');
                    }
                    
                    avatar.appendChild(newBadge);
                    updatedCount++;
                    console.log(`✅ 為聯絡人 "${contactUsername}" 創建並顯示未讀徽章: ${unreadCount}`);
                  } else {
                    // 如果徽章已存在，更新它
                    existingBadge.textContent = unreadCount > 0 ? (unreadCount > 99 ? '99+' : unreadCount.toString()) : '0';
                    existingBadge.setAttribute('data-count', unreadCount.toString());
                    
                    if (unreadCount > 0) {
                      existingBadge.classList.add('show', 'pulse');
                      existingBadge.classList.remove('hiding');
                    } else {
                      existingBadge.classList.remove('show', 'pulse', 'hiding');
                    }
                    updatedCount++;
                    console.log(`✅ 更新聯絡人 "${contactUsername}" 的未讀徽章: ${unreadCount}`);
                  }
                } else {
                  console.warn(`⚠️ 未找到聯絡人 "${contactUsername}" 的頭像元素，無法創建徽章`);
                }
              } else {
                // 如果完全找不到聯絡人項目，可能是因為該用戶不在當前角色的聯絡人列表中
                // 如果當前正在查看該聯絡人的聊天，不需要創建徽章
                if (currentUserId === contactUsername && currentChatType === 'private') {
                  console.debug(`ℹ️ 正在查看 "${contactUsername}" 的聊天，但不在當前頁面的聯絡人列表中`);
                } else if (unreadCount > 0) {
                  // 有未讀訊息但聯絡人不在列表中，嘗試在所有聯絡人列表中查找（包括被搜尋隱藏的）
                  // 這可能是因為該聯絡人被搜尋功能隱藏了，或者根本不在當前頁面
                  const allUserItems = document.querySelectorAll('.user-item');
                  let foundItem = null;
                  for (let item of allUserItems) {
                    const userId = item.getAttribute('data-user-id');
                    if (userId === contactUsername) {
                      foundItem = item;
                      break;
                    }
                  }
                  
                  if (foundItem) {
                    const avatar = foundItem.querySelector('.user-avatar');
                    if (avatar) {
                      // 查找是否已有徽章
                      let badge = avatar.querySelector('.unread-badge[data-contact-id="' + contactUsername + '"]');
                      if (!badge) {
                        // 創建新徽章
                        badge = document.createElement('span');
                        badge.className = 'unread-badge';
                        badge.setAttribute('data-contact-id', contactUsername);
                        badge.style.cssText = 'position: absolute; top: -4px; right: -4px;';
                        avatar.appendChild(badge);
                      }
                      
                      // 更新徽章
                      badge.textContent = unreadCount > 99 ? '99+' : unreadCount.toString();
                      badge.setAttribute('data-count', unreadCount.toString());
                      badge.classList.remove('hiding');
                      badge.classList.add('show', 'pulse');
                      updatedCount++;
                      console.log(`✅ 為隱藏的聯絡人 "${contactUsername}" 創建並顯示未讀徽章 = ${unreadCount}`);
                    }
                  } else {
                    // 這種情況是正常的：未讀計數 API 會返回所有有未讀訊息的用戶（包括不在當前角色聯絡人列表中的）
                    // 例如：不同角色之間可能有未讀訊息，但他們不在彼此的聯絡人列表中
                    console.debug(`ℹ️ 聯絡人 "${contactUsername}" 有未讀訊息，但不在當前頁面的聯絡人列表中（這是正常情況，可能因為角色限制或資料問題）`);
                  }
                } else {
                  console.debug(`ℹ️ 聯絡人 "${contactUsername}" 不在當前頁面的聯絡人列表中（可能有未讀訊息但該用戶不在可見列表中）`);
                }
              }
            }
          });
          
          // 確保所有沒有未讀訊息的聯絡人徽章都被隱藏
          // 對於那些不在 unread_counts 中的聯絡人，他們的徽章應該被隱藏
          allBadges.forEach(badge => {
            const contactId = badge.getAttribute('data-contact-id');
            // 如果這個聯絡人不在未讀計數列表中，隱藏其徽章
            if (!result.unread_counts.hasOwnProperty(contactId)) {
              // 如果當前正在查看該聯絡人的聊天，不隱藏（因為已經處理過了）
              if (currentUserId !== contactId || currentChatType !== 'private') {
                badge.classList.remove('show', 'pulse', 'hiding');
                badge.style.display = 'none'; // 強制隱藏
                badge.style.visibility = 'hidden'; // 強制隱藏
                badge.textContent = '0';
                badge.setAttribute('data-count', '0');
              }
            }
          });
          
          console.log(`已更新 ${updatedCount} 個未讀徽章`);
          
          // 更新總未讀數量
          const totalUnread = Object.values(result.unread_counts).reduce((sum, count) => sum + count, 0);
          const unreadBadge = document.getElementById('unreadBadge');
          if (unreadBadge) {
            if (totalUnread > 0) {
              unreadBadge.textContent = totalUnread > 99 ? '99+' : totalUnread.toString();
              unreadBadge.style.display = 'inline';
            } else {
              unreadBadge.style.display = 'none';
            }
          }
        } else {
          console.warn('未讀計數API返回失敗或無數據:', result);
        }
      } catch (error) {
        console.error('獲取聯絡人未讀數量失敗:', error);
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
    
    // 更新發送按鈕狀態
    function updateSendButtonState() {
      const messageInput = getMessageInputElement(); // 使用統一的函數獲取輸入框
      const sendButton = document.querySelector('.chat-input button[onclick="sendMessage()"]');
      
      if (!messageInput || !sendButton) {
        console.warn('updateSendButtonState: 找不到輸入框或發送按鈕', {
          messageInput: !!messageInput,
          sendButton: !!sendButton
        });
        return;
      }
      
      const hasText = messageInput.value.trim().length > 0;
      const isInputEnabled = !messageInput.disabled;
      
      // 只有在輸入框啟用且有文字時，才啟用發送按鈕
      sendButton.disabled = !isInputEnabled || !hasText;
      
      // 更新按鈕樣式提示
      if (sendButton.disabled) {
        sendButton.style.opacity = '0.5';
        sendButton.style.cursor = 'not-allowed';
      } else {
        sendButton.style.opacity = '1';
        sendButton.style.cursor = 'pointer';
      }
      
      console.log('updateSendButtonState:', {
        hasText,
        isInputEnabled,
        buttonDisabled: sendButton.disabled,
        inputId: messageInput.id
      });
    }
    
    // 監聽輸入框變化（支援兩個介面的輸入框）
    const messageInput1 = document.getElementById('messageInput');
    const messageInput2 = document.getElementById('messageInput_student');
    const messageInput = messageInput1 || messageInput2;
    if (messageInput) {
      // 監聽輸入事件（實時更新）
      messageInput.addEventListener('input', updateSendButtonState);
      messageInput.addEventListener('keyup', updateSendButtonState);
      messageInput.addEventListener('paste', function() {
        setTimeout(updateSendButtonState, 10); // 延遲一下以確保內容已貼上
      });
      
      // 按Enter發送訊息
      messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          const sendButton = document.querySelector('.chat-input button[onclick="sendMessage()"]');
          if (sendButton && !sendButton.disabled) {
            sendMessage();
          }
        }
      });
    }
    
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
      const voiceBtn = getVoiceRecordBtnElement();
      const messageInput = getMessageInputElement();
      
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
    console.log('已載入已讀列表（僅當前會話）:', Array.from(readContacts)); // 調試：顯示載入的已讀列表
    updateUserActivity(); // 更新活動時間
    getUnreadCount(); // 獲取未讀數量
    
    // 立即更新聯絡人未讀計數（延遲一點確保DOM已載入）
    // 注意：不再根據 localStorage 中的 readContacts 來隱藏徽章
    // 因為資料庫的實際狀態才是權威來源
    setTimeout(() => {
      // 如果當前正在查看某個聯絡人的聊天，隱藏其徽章
      if (currentUserId && currentChatType === 'private') {
        const badge = document.querySelector(`.unread-badge[data-contact-id="${currentUserId}"]`);
        if (badge) {
          badge.classList.remove('show', 'pulse', 'hiding');
          badge.style.display = 'none'; // 完全隱藏
          badge.style.visibility = 'hidden'; // 完全隱藏
          console.log(`隱藏當前查看的聯絡人徽章: ${currentUserId}`);
        }
      }
      
      // 更新所有聯絡人的未讀計數（根據資料庫實際狀態）
      updateContactUnreadCounts();
    }, 500);
    
    // 初始化FCM推播通知
    initializeFCM();
    
    // 應用配色方案
    applyColorScheme();
    
    // 定期更新未讀數量和活動時間（每30秒）
    setInterval(() => {
      getUnreadCount();
      updateUserActivity();
      updateContactUnreadCounts(); // 更新聯絡人未讀計數
    }, 30000);
    
    // 更頻繁地更新聯絡人未讀計數（每5秒），以便及時看到新訊息
    setInterval(() => {
      updateContactUnreadCounts();
    }, 5000);
    
    // 頁面載入完成後再次更新（確保DOM完全準備好）
    window.addEventListener('load', () => {
      setTimeout(() => {
        console.log('頁面載入完成，更新未讀計數');
        updateContactUnreadCounts();
      }, 1000);
    });
    
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
            const currentMaxId = result.messages.length > 0 ? Math.max(...result.messages.map(m => parseInt(m.id) || 0)) : 0;
            if (currentMaxId > lastMessageId) {
              // 有新訊息，更新顯示
              try {
              displayMessages(result.messages);
              lastMessageId = currentMaxId;
              console.log('發現新訊息，已更新顯示，最後訊息ID:', lastMessageId);
              } catch (displayError) {
                console.error('顯示新訊息時發生錯誤:', displayError);
              }
            }
          } else {
            console.error('輪詢失敗:', result.error || '未知錯誤');
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
