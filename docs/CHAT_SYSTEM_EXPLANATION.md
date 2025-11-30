# 聊天訊息系統運作說明

## 📋 概述

這個系統支持多種聊天類型：
1. **私聊** (Private Chat) - 兩人之間的私人對話
2. **群組聊天** (Group Chat) - 多人群組聊天
3. **AI 聊天** (AI Chat) - 與 AI 助手的對話

---

## 💬 聊天訊息如何存儲到資料庫

### 1. 私聊訊息 (Private Chat)

**資料表：`private_chat_history`**

當用戶 A 向用戶 B 發送私聊訊息時：

```php
// 發送流程 (save_private_message.php)
1. 前端發送：POST /save_private_message.php
   {
     "from": "用戶A",
     "to": "用戶B",
     "message": "訊息內容",
     "role": "老師" 或 "學生"
   }

2. 後端處理：
   - 檢查表結構（支持正規化和非正規化版本）
   - 如果是正規化版本：將 username 轉換為 user_id
   - 插入資料庫：
     INSERT INTO private_chat_history 
     (from_user, to_user, message, role, timestamp) 
     VALUES (?, ?, ?, ?, NOW())
   
3. 資料庫存儲結構：
   - id: 訊息ID (自動遞增)
   - from_user: 發送者用戶名 (或 from_user_id)
   - to_user: 接收者用戶名 (或 to_user_id)
   - message: 訊息內容 (TEXT)
   - role: 發送者角色
   - timestamp: 發送時間
```

**讀取訊息 (load_private_messages.php)：**

```php
// 查詢邏輯
SELECT * FROM private_chat_history 
WHERE (from_user = '用戶A' AND to_user = '用戶B') 
   OR (from_user = '用戶B' AND to_user = '用戶A')
ORDER BY timestamp ASC
```

**特點：**
- 雙向查詢：只要是用戶 A 和 B 之間的對話，無論誰發給誰都會顯示
- 支援增量載入：可以使用 `lastMessageId` 參數只載入新訊息
- 支援正規化版本：如果資料表使用 `from_user_id` 和 `to_user_id`，會自動 JOIN `user` 表獲取用戶名

---

### 2. 群組訊息 (Group Chat)

**資料表結構：**

群組系統使用三個主要資料表：

#### a. `group_info` - 群組基本資訊
```sql
CREATE TABLE group_info (
    group_id VARCHAR(255) PRIMARY KEY,  -- 群組ID (格式: timestamp_random)
    group_name VARCHAR(255) NOT NULL,   -- 群組名稱
    created_by VARCHAR(255) NOT NULL,   -- 創建者
    department VARCHAR(255),            -- 所屬科系
    created_at TIMESTAMP                -- 創建時間
)
```

#### b. `group_chat_members` - 群組成員
```sql
CREATE TABLE group_chat_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id VARCHAR(255) NOT NULL,     -- 群組ID
    member_username VARCHAR(255) NOT NULL, -- 成員用戶名
    joined_at TIMESTAMP,                -- 加入時間
    UNIQUE KEY (group_id, member_username) -- 防止重複加入
)
```

#### c. `group_chat_messages` - 群組訊息
```sql
CREATE TABLE group_chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id VARCHAR(255) NOT NULL,     -- 群組ID
    from_user VARCHAR(255) NOT NULL,    -- 發送者 (或 user_id)
    message TEXT NOT NULL,              -- 訊息內容
    role VARCHAR(50) DEFAULT '用戶',    -- 發送者角色
    timestamp TIMESTAMP                 -- 發送時間
)
```

**發送群組訊息流程：**

```php
// 1. 創建群組 (group_management.php?action=create_group)
   - 生成群組ID: time() . '_' . rand(1000, 9999)
   - 插入 group_info 表
   - 將創建者和所有成員加入 group_chat_members 表

// 2. 發送訊息 (group_management.php?action=send_group_message)
   - 檢查用戶是否為群組成員
   - 插入 group_chat_messages 表
   INSERT INTO group_chat_messages 
   (group_id, from_user, message, role, timestamp) 
   VALUES (?, ?, ?, ?, NOW())

// 3. 讀取訊息 (group_management.php?action=get_group_messages)
   SELECT * FROM group_chat_messages 
   WHERE group_id = ? 
   ORDER BY timestamp ASC
```

---

### 3. AI 聊天訊息 (AI Chat)

**資料表：`ai_chat_history`**

```sql
CREATE TABLE ai_chat_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,      -- 用戶ID (或 username)
    message_type ENUM('user', 'ai'),    -- 訊息類型：用戶或AI
    message_content TEXT NOT NULL,      -- 訊息內容
    created_at TIMESTAMP                -- 創建時間
)
```

**存儲方式：**

```php
// 儲存用戶訊息
INSERT INTO ai_chat_history 
(user_id, message_type, message_content) 
VALUES (?, 'user', ?)

// 儲存AI回覆
INSERT INTO ai_chat_history 
(user_id, message_type, message_content) 
VALUES (?, 'ai', ?)

// 讀取歷史記錄（按時間順序）
SELECT * FROM ai_chat_history 
WHERE user_id = ? 
ORDER BY created_at ASC
```

---

## 👥 群組是如何組織的

### 群組的創建流程

```php
// 步驟 1: 創建群組資訊
$groupId = time() . '_' . rand(1000, 9999);  // 例如: 1735123456_5678
INSERT INTO group_info (group_id, group_name, created_by, department)
VALUES (?, ?, ?, ?)

// 步驟 2: 添加創建者為成員
INSERT INTO group_chat_members (group_id, member_username)
VALUES (?, '創建者用戶名')

// 步驟 3: 添加其他成員
foreach ($members as $member) {
    INSERT INTO group_chat_members (group_id, member_username)
    VALUES (?, $member['username'])
}
```

### 群組的查詢方式

**獲取用戶的所有群組：**

```sql
SELECT 
    gm.group_id as id,
    gi.group_name,
    COUNT(gm2.id) as member_count,
    gi.created_by,
    gi.department
FROM group_chat_members gm 
JOIN group_chat_members gm2 ON gm.group_id = gm2.group_id
LEFT JOIN group_info gi ON gm.group_id = gi.group_id
WHERE gm.member_username = '當前用戶名'
GROUP BY gm.group_id
ORDER BY gm.joined_at DESC
```

**特點：**
- 使用 `group_chat_members` 表來查找用戶參與的所有群組
- 通過 JOIN `group_info` 表獲取群組的詳細資訊
- 通過 JOIN 另一個 `group_chat_members` 實例來計算成員數量

### 群組成員管理

**檢查用戶是否為群組成員：**

```sql
SELECT COUNT(*) FROM group_chat_members 
WHERE group_id = ? AND member_username = ?
```

**添加成員：**

```sql
INSERT INTO group_chat_members (group_id, member_username, joined_at)
VALUES (?, ?, NOW())
-- 注意：UNIQUE KEY 防止重複加入
```

---

## 🔄 訊息讀取機制

### 1. 輪詢機制 (Polling)

系統使用輪詢方式來獲取新訊息：

```javascript
// 前端每 3 秒輪詢一次
setInterval(function() {
    loadPrivateMessages(from, to, lastMessageId);
}, 3000);
```

### 2. 增量載入

只載入比 `lastMessageId` 更新的訊息：

```sql
SELECT * FROM private_chat_history 
WHERE ((from_user = ? AND to_user = ?) 
    OR (from_user = ? AND to_user = ?))
  AND id > ?
ORDER BY timestamp ASC
```

### 3. Socket.IO (可選)

系統中也有 Flask-SocketIO 服務 (`chat.py`)，但主要用於即時廣播：

```python
@socketio.on('chat_message')
def handle_chat_message(data):
    insert_message(sender, receiver, role, message)
    emit('chat_message', data, broadcast=True)  # 廣播給所有連接的用戶
```

---

## 📊 資料表關係圖

```
┌─────────────┐
│    user     │  (用戶表)
└─────────────┘
      │
      ├───┐
      │   │
      ▼   ▼
┌─────────────────────────┐    ┌─────────────────────┐
│ private_chat_history    │    │   group_chat_       │
│ - from_user_id          │    │   members           │
│ - to_user_id            │    │ - user_id           │
│ - message               │    │ - group_id          │
└─────────────────────────┘    └─────────────────────┘
                                        │
                                        │
                              ┌─────────┴──────────┐
                              │                    │
                              ▼                    ▼
                    ┌─────────────────┐  ┌────────────────────┐
                    │   group_info    │  │ group_chat_messages│
                    │ - group_id (PK) │  │ - group_id (FK)    │
                    │ - group_name    │  │ - from_user        │
                    │ - created_by    │  │ - message          │
                    └─────────────────┘  └────────────────────┘
```

---

## 🎯 關鍵特點總結

### 私聊訊息
- ✅ 使用 `private_chat_history` 表
- ✅ 支援雙向查詢（A→B 和 B→A 都顯示）
- ✅ 支援正規化和非正規化兩種表結構
- ✅ 支援增量載入（只載入新訊息）

### 群組訊息
- ✅ 三個表協同工作：`group_info`、`group_chat_members`、`group_chat_messages`
- ✅ 使用字串格式的 `group_id`（時間戳+隨機數）
- ✅ 自動檢查成員權限（只有成員可以發送訊息）
- ✅ 支援動態創建群組和添加成員

### 資料庫設計
- ✅ 使用索引優化查詢性能（`idx_from_user`, `idx_group_id`, `idx_timestamp`）
- ✅ 支援外鍵約束（正規化版本）
- ✅ 自動時間戳記錄（`CURRENT_TIMESTAMP`）
- ✅ 支援 Unicode（`utf8mb4` 字符集）

---

## 📝 程式碼位置

- **私聊訊息儲存：** `Topics-frontend/frontend/chat/save_private_message.php`
- **私聊訊息載入：** `Topics-frontend/frontend/chat/load_private_messages.php`
- **群組管理：** `Topics-frontend/frontend/chat/group_management.php`
- **AI 聊天：** `Topics-frontend/backend/api/chat/ai_chat_api.php`
- **Socket.IO 服務：** `Topics-frontend/backend/services/chat.py`
- **資料表創建 SQL：** `Topics-frontend/frontend/chat/create_chat_tables.sql`


