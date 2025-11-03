# 老師活動通知系統資料表正規化指南

## 概述

本指南說明如何整合並正規化以下三個資料表：
1. `teacher_activity_notifications` - 老師活動通知主表
2. `teacher_activity_recipients` - 收件人記錄表
3. `schools_contacts` - 學校聯絡人表（需要創建）

## 正規化目標

將資料表正規化至第三正規化（3NF），消除以下問題：
- **數據冗餘**: 移除 `teacher_activity_notifications` 中的 `teacher_name` 和 `teacher_email`，改為關聯到 `user` 表
- **數據冗餘**: 移除 `teacher_activity_recipients` 中的 `email` 字段，改為關聯到 `schools_contacts` 表
- **數據一致性**: 確保聯絡人資料統一管理

## 正規化後的表結構

### 1. schools_contacts（學校聯絡人表）

```sql
CREATE TABLE schools_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    position VARCHAR(100) DEFAULT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    phone VARCHAR(50) DEFAULT NULL,
    department VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**說明**: 統一管理所有聯絡人資料，避免 email 重複。

### 2. teacher_activity_notifications_normalized（正規化後的通知表）

```sql
CREATE TABLE teacher_activity_notifications_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,  -- 關聯到 user 表
    teacher_email VARCHAR(120) DEFAULT NULL,  -- 備用字段
    subject VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    event_date DATE DEFAULT NULL,
    link VARCHAR(300) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
);
```

**說明**: 
- 移除冗余的 `teacher_name` 和 `teacher_email` 字段
- 通過 `user_id` 關聯到 `user` 表獲取老師資料
- 保留 `teacher_email` 作為備用，用於找不到對應 user 的情況

### 3. teacher_activity_recipients_normalized（正規化後的收件人表）

```sql
CREATE TABLE teacher_activity_recipients_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    contact_id INT DEFAULT NULL,  -- 關聯到 schools_contacts 表
    status ENUM('queued','sent','failed') DEFAULT 'queued',
    sent_at DATETIME DEFAULT NULL,
    error_message VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES teacher_activity_notifications_normalized(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES schools_contacts(id) ON DELETE SET NULL
);
```

**說明**:
- 移除冗余的 `email` 字段
- 通過 `contact_id` 關聯到 `schools_contacts` 表獲取聯絡人資料

## 執行步驟

### 方法一：使用 PHP 腳本（推薦）

1. 訪問：`http://your-domain/scripts/setup/integrate_teacher_notifications.php`

2. 腳本會自動：
   - 創建正規化表結構
   - 遷移現有數據
   - 顯示遷移統計

### 方法二：使用 SQL 腳本

1. 執行 SQL 腳本創建表結構：
   ```bash
   mysql -u root -p topics_good < scripts/database/integrate_teacher_notification_tables.sql
   ```

2. 使用 Python 腳本遷移數據：
   ```bash
   python3 scripts/database/migrate_teacher_notifications.py
   ```

### 方法三：使用 phpMyAdmin

1. 登入 phpMyAdmin
2. 選擇 `topics_good` 資料庫
3. 點擊「SQL」標籤
4. 複製並執行 `scripts/database/integrate_teacher_notification_tables.sql` 的內容

## 數據遷移邏輯

### 1. 聯絡人數據遷移

從 `teacher_activity_recipients` 表中提取所有唯一的 email，創建對應的聯絡人記錄。

### 2. 通知數據遷移

- 根據 `teacher_email` 在 `user` 表中查找對應的 `user_id`
- 如果找不到對應的 user，`user_id` 設為 NULL，但保留 `teacher_email` 作為備用

### 3. 收件人數據遷移

- 根據 `email` 在 `schools_contacts` 表中查找對應的 `contact_id`
- 建立關聯關係

## 向後兼容性

為了保持向後兼容，創建了以下視圖：

### teacher_activity_notifications_view

```sql
SELECT 
    tan.id,
    COALESCE(u.username, CONCAT('用戶-', tan.teacher_email)) as teacher_name,
    COALESCE(u.email, tan.teacher_email) as teacher_email,
    tan.subject,
    tan.content,
    tan.event_date,
    tan.link,
    tan.created_at
FROM teacher_activity_notifications_normalized tan
LEFT JOIN user u ON tan.user_id = u.id;
```

### teacher_activity_recipients_view

```sql
SELECT 
    tar.id,
    tar.notification_id,
    tar.contact_id,
    sc.email,
    tar.status,
    tar.sent_at,
    tar.error_message,
    tar.created_at
FROM teacher_activity_recipients_normalized tar
LEFT JOIN schools_contacts sc ON tar.contact_id = sc.id;
```

## 注意事項

1. **外鍵約束**: 遷移過程中暫時關閉外鍵檢查，完成後重新開啟
2. **數據完整性**: 如果找不到對應的 user 或 contact，相關字段會設為 NULL
3. **原有表**: 正規化後保留原有表不刪除，以便需要時回滾
4. **索引優化**: 已為常用查詢字段建立索引

## 查詢範例

### 查詢老師的通知及其收件人

```sql
SELECT 
    tan.id as notification_id,
    u.username as teacher_name,
    u.email as teacher_email,
    tan.subject,
    COUNT(tar.id) as recipient_count
FROM teacher_activity_notifications_normalized tan
LEFT JOIN user u ON tan.user_id = u.id
LEFT JOIN teacher_activity_recipients_normalized tar ON tar.notification_id = tan.id
GROUP BY tan.id, u.username, u.email, tan.subject;
```

### 查詢特定聯絡人的通知記錄

```sql
SELECT 
    tar.id,
    tan.subject,
    sc.email as recipient_email,
    sc.name as recipient_name,
    tar.status,
    tar.sent_at
FROM teacher_activity_recipients_normalized tar
JOIN teacher_activity_notifications_normalized tan ON tar.notification_id = tan.id
JOIN schools_contacts sc ON tar.contact_id = sc.id
WHERE sc.email = 'example@school.edu.tw';
```

## 故障排除

### 問題：找不到對應的 user

**解決方案**:
1. 檢查 `user` 表中的 email 是否與通知中的 `teacher_email` 一致
2. 如果 email 不匹配，手動更新 `user` 表或通知表中的 email
3. 或者為該老師創建對應的 user 記錄

### 問題：找不到對應的聯絡人

**解決方案**:
1. 檢查 `schools_contacts` 表中是否存在該 email
2. 如果不存在，手動創建聯絡人記錄
3. 更新收件人記錄的 `contact_id`

## 回滾方案

如果需要回滾到原有表結構：

1. 使用原有的 `teacher_activity_notifications` 和 `teacher_activity_recipients` 表
2. 刪除正規化表：
   ```sql
   DROP TABLE IF EXISTS teacher_activity_recipients_normalized;
   DROP TABLE IF EXISTS teacher_activity_notifications_normalized;
   DROP TABLE IF EXISTS schools_contacts;
   ```
3. 刪除視圖：
   ```sql
   DROP VIEW IF EXISTS teacher_activity_recipients_view;
   DROP VIEW IF EXISTS teacher_activity_notifications_view;
   ```

## 相關文件

- `scripts/database/integrate_teacher_notification_tables.sql` - SQL 創建腳本
- `scripts/database/migrate_teacher_notifications.py` - Python 遷移腳本
- `scripts/setup/integrate_teacher_notifications.php` - PHP 整合腳本

