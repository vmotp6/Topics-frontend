# Discord風格通知系統設置指南

## 概述
這個系統實現了類似Discord的長時間未查看通知功能。當用戶長時間沒有查看聊天系統時，會自動發送Gmail通知提醒。

## 功能特性

### 🔔 智能通知
- **多時間間隔**：1小時、6小時、24小時、3天、1週
- **避免重複**：同一時間間隔內不會重複發送通知
- **安靜時間**：可設置免打擾時間段
- **頻率控制**：可自定義通知頻率

### 📧 Discord風格郵件
- **美觀設計**：採用Discord風格的藍色主題
- **消息預覽**：顯示未讀消息的預覽內容
- **直接連結**：一鍵跳轉到聊天頁面
- **統計信息**：顯示未讀消息數量

### ⚙️ 用戶自定義
- **通知開關**：可完全關閉通知
- **時間間隔選擇**：自定義通知時間點
- **安靜時間設置**：設置免打擾時段
- **郵件頻率**：選擇通知頻率

## 安裝步驟

### 1. 創建資料庫表
執行以下SQL腳本創建必要的資料庫表：

```bash
mysql -u root -p topics_good < scripts/database/create_user_activity_table.sql
```

### 2. 配置Gmail設置
在 `.env` 文件中添加以下配置：

```env
# Gmail通知設置
GMAIL_SENDER_EMAIL=your-email@gmail.com
GMAIL_APP_PASSWORD=your-16-digit-app-password
GMAIL_SENDER_NAME=康寧大學聊天系統
BASE_URL=http://100.79.58.120
```

### 3. 設置Gmail應用程式密碼
1. 登入您的Google帳戶
2. 前往「安全性」設定
3. 啟用「2步驟驗證」
4. 生成「應用程式密碼」
5. 將16位元密碼填入配置

### 4. 設置定時任務
在crontab中添加以下任務：

```bash
# 每小時檢查一次
0 * * * * /usr/bin/php /path/to/scripts/maintenance/discord_notification_cron.php

# 或者每30分鐘檢查一次
*/30 * * * * /usr/bin/php /path/to/scripts/maintenance/discord_notification_cron.php
```

## 使用方法

### 1. 用戶設置
用戶可以通過以下方式設置通知偏好：
- 訪問 `frontend/chat/notification_settings.php`
- 自定義通知時間間隔
- 設置安靜時間
- 選擇郵件頻率

### 2. 手動測試
執行測試腳本驗證系統：

```bash
php scripts/test/discord_notification_test.php
```

### 3. 手動觸發檢查
```bash
php backend/services/discord_like_notification.php
```

## 系統架構

### 資料庫表結構

#### user_activity
- `username`: 用戶名
- `last_seen`: 最後活動時間
- `last_chat_check`: 最後查看聊天時間
- `notification_preferences`: 通知偏好設置（JSON）

#### unread_notifications
- `username`: 用戶名
- `notification_type`: 通知類型
- `sender_username`: 發送者
- `message_preview`: 消息預覽
- `sent_at`: 發送時間

#### notification_sent_log
- `username`: 用戶名
- `notification_type`: 通知類型
- `sent_at`: 發送時間
- `email_sent`: 是否發送成功

### 核心類別

#### DiscordLikeNotificationService
- `updateUserActivity()`: 更新用戶活動
- `checkUsersForNotification()`: 檢查需要通知的用戶
- `sendDiscordLikeNotification()`: 發送通知

#### EmailNotificationService
- `sendDiscordLikeNotification()`: 發送Discord風格郵件
- `sendPrivateMessageNotification()`: 發送私訊通知

## 通知邏輯

### 觸發條件
1. 用戶有未讀消息
2. 用戶超過設定的時間間隔未查看聊天
3. 不在安靜時間內
4. 該時間間隔內未發送過通知

### 通知內容
- 未讀消息數量
- 消息預覽（最多5條）
- 發送者信息
- 直接回覆連結
- 美觀的HTML格式

## 故障排除

### 常見問題

**Q: 通知沒有發送**
- 檢查Gmail配置是否正確
- 確認定時任務是否運行
- 查看錯誤日誌

**Q: 重複發送通知**
- 檢查 `notification_sent_log` 表
- 確認時間間隔設置

**Q: 郵件格式錯誤**
- 檢查HTML模板
- 確認字符編碼設置

### 日誌檢查
```bash
# 查看PHP錯誤日誌
tail -f /var/log/apache2/error.log

# 查看定時任務日誌
tail -f /var/log/cron.log
```

## 安全注意事項

1. **保護Gmail憑證**：
   - 使用應用程式密碼
   - 不要將憑證提交到版本控制

2. **限制通知頻率**：
   - 避免過度發送通知
   - 尊重用戶的安靜時間設置

3. **數據隱私**：
   - 只發送必要的消息預覽
   - 不記錄敏感信息

## 自定義設置

### 修改通知間隔
在 `DiscordLikeNotificationService` 中修改：

```php
private $notificationIntervals = [
    '1_hour' => 3600,
    '6_hours' => 21600,
    '24_hours' => 86400,
    '3_days' => 259200,
    '1_week' => 604800
];
```

### 自定義郵件模板
修改 `generateDiscordLikeEmailBody()` 方法中的HTML模板。

### 添加新的通知類型
1. 在資料庫中添加新的 `notification_type`
2. 修改檢查邏輯
3. 創建對應的郵件模板

## 監控和維護

### 定期檢查
- 監控通知發送成功率
- 檢查用戶反饋
- 優化通知頻率

### 性能優化
- 使用資料庫索引
- 限制查詢範圍
- 緩存用戶設置

## 更新日誌

### v1.0.0 (2025-01-XX)
- 初始版本發布
- 實現基本通知功能
- 支持多時間間隔
- Discord風格郵件模板
- 用戶自定義設置

---

如有問題或建議，請聯繫系統管理員。
