# 聊天室功能更新說明

## 更新內容

本次更新實現了以下三個主要功能：

### 1. 點擊頭像更新歷史紀錄 ✅

**問題解決：**
- 修復了點擊不同用戶頭像時，歷史紀錄不會清空的問題
- 現在每次點擊頭像都會重置 `lastMessageId` 並清空聊天區域
- 確保每次切換用戶時都能正確載入對應的聊天記錄

**技術實現：**
```javascript
// 在用戶點擊事件中添加
lastMessageId = 0; // 重置訊息ID
document.getElementById('chatMessages').innerHTML = '';
loadChatHistory();
```

### 2. 老師帳號登入顯示優化 ✅

**功能改進：**
- 老師登入後，聯絡人列表現在會顯示：
  - **同科系的老師**：自動篩選出與當前老師相同科系的其他老師
  - **有私訊的廠商**：只顯示與老師有過私訊往來的廠商帳號

**資料庫查詢邏輯：**
```sql
-- 獲取同科系老師
SELECT t2.u_id, t2.name, t2.department, u.username, '老師' as contact_type
FROM teacher02 t2 
JOIN user u ON t2.u_id = u.id 
WHERE u.role = '老師' AND t2.department = ? AND u.username != ?

-- 獲取有私訊的廠商
SELECT DISTINCT u.username as vendor_id, u.username as vendor_name, '廠商' as contact_type
FROM user u 
JOIN private_chat_history pch ON (u.username = pch.from_user OR u.username = pch.to_user)
WHERE u.role = '廠商' 
AND (pch.from_user = ? OR pch.to_user = ?)
AND u.username != ?
```

**UI 改進：**
- 聯絡人列表顯示聯絡人類型（老師/廠商）
- 更清晰的視覺區分不同類型的聯絡人

### 3. 老師群組聊天功能 ✅

**新增功能：**
- 老師可以建立群組聊天
- 可以邀請同科系老師和有私訊的廠商加入群聊
- 完整的群聊訊息系統

**群聊功能特點：**
- **建立群聊**：點擊「群聊」按鈕開啟邀請模態框
- **選擇成員**：勾選要邀請的聯絡人
- **群聊介面**：專用的群聊聊天介面
- **即時更新**：群聊訊息即時同步

**資料庫結構：**
```sql
-- 群聊資料表
CREATE TABLE group_chats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(255) NOT NULL,
    created_by VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 群聊成員資料表
CREATE TABLE group_chat_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    member_username VARCHAR(255) NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES group_chats(id) ON DELETE CASCADE
);

-- 群聊訊息資料表
CREATE TABLE group_chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    from_user VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    role VARCHAR(50) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES group_chats(id) ON DELETE CASCADE
);
```

## 檔案結構

```
frontend/chat/
├── chat.php                    # 主要聊天介面（已更新）
├── group_chat_api.php          # 群聊API（新增）
├── load_private_messages.php   # 私聊訊息載入API
├── save_private_message.php    # 私聊訊息儲存API
└── test_chat.html             # 功能測試頁面（新增）
```

## API 端點

### 群聊API (`group_chat_api.php`)

**建立群聊：**
```javascript
POST /group_chat_api.php
{
    "action": "create_group",
    "group_name": "群聊名稱",
    "created_by": "建立者用戶名",
    "members": ["成員1", "成員2", ...]
}
```

**發送群聊訊息：**
```javascript
POST /group_chat_api.php
{
    "action": "send_group_message",
    "group_id": 群組ID,
    "from_user": "發送者用戶名",
    "message": "訊息內容",
    "role": "用戶角色"
}
```

**獲取群聊訊息：**
```javascript
POST /group_chat_api.php
{
    "action": "get_group_messages",
    "group_id": 群組ID,
    "from_user": "請求者用戶名"
}
```

## 使用方式

### 廠商用戶
1. 登入後會看到教師列表
2. 點擊教師頭像開始私聊
3. 聊天記錄會即時更新

### 老師用戶
1. 登入後會看到聯絡人列表（同科系老師 + 有私訊的廠商）
2. 點擊聯絡人頭像開始私聊
3. 點擊「群聊」按鈕建立群組聊天
4. 在模態框中選擇要邀請的聯絡人
5. 建立群聊後可以進行群組聊天

## 技術特點

### 前端改進
- **響應式設計**：適配不同螢幕尺寸
- **即時更新**：3秒輪詢檢查新訊息
- **狀態管理**：正確處理私聊和群聊狀態切換
- **錯誤處理**：完善的錯誤提示機制

### 後端改進
- **資料庫優化**：使用外鍵約束確保資料完整性
- **API 設計**：統一的 JSON 回應格式
- **安全性**：檢查用戶權限和群組成員資格
- **效能**：適當的索引設計

### 使用者體驗
- **視覺回饋**：聊天類型指示器（私聊/群聊）
- **狀態指示**：聯絡人類型標籤
- **操作簡化**：一鍵建立群聊
- **訊息同步**：即時訊息更新

## 測試

可以使用 `test_chat.html` 頁面測試各項功能：
1. 群聊API功能測試
2. 私聊API功能測試
3. 資料庫連接測試

## 注意事項

1. 確保資料庫中有 `teacher02` 和 `user` 資料表
2. 確保 `private_chat_history` 資料表存在
3. 群聊相關資料表會在首次使用時自動建立
4. 需要正確的資料庫連接設定

## 未來改進方向

1. **群聊管理**：允許群主管理群組成員
2. **訊息通知**：未讀訊息提醒功能
3. **檔案分享**：支援圖片和檔案傳送
4. **表情符號**：豐富的表情符號支援
5. **訊息搜尋**：聊天記錄搜尋功能

