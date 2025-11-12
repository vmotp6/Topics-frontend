# 完整資料庫第三正規化（3NF）指南

## 概述

本指南說明如何將整個 `topics_good` 資料庫正規化至第三正規化（3NF）標準。正規化後的資料庫將消除數據冗餘，提高數據一致性和維護性。

## 正規化目標

### 主要改進：

1. **消除重複數據**
   - 科系、學制、狀態等字串值改為引用表
   - 角色、性別、年級等字串值正規化

2. **移除冗餘字段**
   - 學生/老師表中的 `department` 字串 → 引用 `departments` 表
   - 聊天表中的 `username` 字串 → 引用 `user` 表
   - 各種狀態字串 → 引用對應狀態表

3. **解決傳遞依賴**
   - 公司資訊獨立為 `companies` 表
   - 智慧財產權獨立為 `ip_rights` 表
   - 申請類別獨立為明細表

## 正規化後的表結構

### 基礎參考表

#### 1. departments（科系表）
```sql
id, code, name, available_systems, description
```

#### 2. education_systems（學制表）
```sql
id, code, name, years, description
```

#### 3. application_statuses（申請狀態表）
```sql
id, code, name, description, display_order
```

#### 4. genders（性別表）
```sql
id, code, name
```

#### 5. grades（年級表）
```sql
id, code, name, level, education_level
```

#### 6. role_types（角色類型表）
```sql
id, code, name, description
```

#### 7. message_types（訊息類型表）
```sql
id, code, name, description
```

### 正規化的主要表

#### student_normalized（正規化後的學生表）
- 移除：`department`（字串）→ `department_id`（外鍵）
- 移除：`grade`（字串）→ `grade_id`（外鍵）

#### teacher_normalized（正規化後的老師表）
- 移除：`department`（字串）→ `department_id`（外鍵）

#### private_chat_history_normalized（正規化後的私聊表）
- 移除：`from_user`（字串）→ `from_user_id`（外鍵）
- 移除：`to_user`（字串）→ `to_user_id`（外鍵）
- 移除：`role`（字串）→ 自動從 user 關聯判斷

#### chat_groups_normalized（正規化後的群組表）
- 移除：`created_by`（字串）→ `created_by_user_id`（外鍵）
- 移除：`department`（字串）→ `department_id`（外鍵）

#### group_members_normalized（正規化後的群組成員表）
- 移除：`username`（字串）→ `user_id`（外鍵）
- 移除：`role`（字串）→ `role_type_id`（外鍵）

#### enrollment_applications_normalized（正規化後的就讀意願表）
- 移除所有重複的狀態、性別、身份字串
- 改為引用對應的參考表

## 執行步驟

### 方法一：使用 PHP 腳本（推薦）

1. 訪問執行腳本：
   ```
   http://localhost/scripts/setup/execute_complete_3nf_normalization.php
   ```

2. 腳本會自動：
   - 創建所有正規化表結構
   - 遷移現有數據
   - 顯示遷移統計
   - 創建向後兼容視圖

### 方法二：使用 SQL 腳本

1. **創建正規化表結構**：
   ```bash
   mysql -u root -p topics_good < scripts/database/complete_normalize_to_3nf.sql
   ```

2. **遷移數據**：
   ```bash
   mysql -u root -p topics_good < scripts/database/migrate_all_data_to_3nf.sql
   ```

### 方法三：使用 phpMyAdmin

1. 登入 phpMyAdmin
2. 選擇 `topics_good` 資料庫
3. 點擊「SQL」標籤
4. 依次執行：
   - `scripts/database/complete_normalize_to_3nf.sql`
   - `scripts/database/migrate_all_data_to_3nf.sql`

## 向後兼容性

為了保持與現有代碼的兼容性，創建了以下視圖：

### 視圖列表

1. **student_view** - 替代 `student` 表
   ```sql
   SELECT * FROM student_view;
   ```

2. **teacher_view** - 替代 `teacher` 表
   ```sql
   SELECT * FROM teacher_view;
   ```

3. **private_chat_history_view** - 替代 `private_chat_history` 表
   ```sql
   SELECT * FROM private_chat_history_view;
   ```

4. **group_messages_view** - 替代 `group_messages` 表
   ```sql
   SELECT * FROM group_messages_view;
   ```

5. **chat_groups_view** - 替代 `chat_groups` 表
   ```sql
   SELECT * FROM chat_groups_view;
   ```

6. **group_members_view** - 替代 `group_members` 表
   ```sql
   SELECT * FROM group_members_view;
   ```

## 數據遷移邏輯

### 1. 學生數據遷移
- 根據 `department` 字串查找 `departments` 表對應的 ID
- 根據 `grade` 字串查找 `grades` 表對應的 ID
- 如果找不到對應記錄，`department_id` 和 `grade_id` 設為 NULL

### 2. 老師數據遷移
- 根據 `department` 字串查找 `departments` 表對應的 ID
- 如果找不到，設為 NULL

### 3. 聊天數據遷移
- 根據 `username` 查找 `user` 表對應的 `user_id`
- 如果找不到對應的 user，跳過該記錄

### 4. 科系匹配策略
- 首先嘗試精確匹配
- 如果失敗，嘗試模糊匹配（LIKE）
- 如果仍失敗，設為 NULL

## 驗證遷移

執行遷移後，檢查以下統計：

```sql
-- 檢查學生數據
SELECT 
    (SELECT COUNT(*) FROM student) AS original,
    (SELECT COUNT(*) FROM student_normalized) AS normalized;

-- 檢查老師數據
SELECT 
    (SELECT COUNT(*) FROM teacher) AS original,
    (SELECT COUNT(*) FROM teacher_normalized) AS normalized;

-- 檢查聊天數據
SELECT 
    (SELECT COUNT(*) FROM private_chat_history) AS original,
    (SELECT COUNT(*) FROM private_chat_history_normalized) AS normalized;
```

## 注意事項

1. **備份資料庫**
   - 執行正規化前務必備份整個資料庫

2. **外鍵約束**
   - 遷移過程中暫時關閉外鍵檢查
   - 完成後重新開啟

3. **數據完整性**
   - 如果找不到對應的參考記錄，相關外鍵會設為 NULL
   - 需要手動檢查並補齊缺失的參考數據

4. **原有表保留**
   - 正規化後保留原有表不刪除
   - 可以通過視圖繼續使用舊表結構
   - 確認無誤後再刪除或重命名

## 後續步驟

### 1. 更新應用程式代碼

逐步將應用程式代碼從使用原表改為使用正規化表：

```php
// 舊代碼
$stmt = $pdo->query("SELECT * FROM student WHERE department = '資訊管理科'");

// 新代碼（使用正規化表）
$stmt = $pdo->query("
    SELECT s.*, d.name as department_name 
    FROM student_normalized s 
    LEFT JOIN departments d ON s.department_id = d.id 
    WHERE d.name = '資訊管理科'
");

// 或使用視圖（向後兼容）
$stmt = $pdo->query("SELECT * FROM student_view WHERE department = '資訊管理科'");
```

### 2. 處理 NULL 值

正規化過程中，某些數據可能因為找不到對應記錄而設為 NULL。需要：

1. 檢查 NULL 值記錄
2. 補充缺失的參考數據
3. 更新 NULL 值為正確的外鍵

### 3. 測試應用功能

- 測試所有使用正規化表的功能
- 確認視圖正常工作
- 檢查性能影響

### 4. 清理舊表

確認一切正常後：
```sql
-- 重命名舊表作為備份
RENAME TABLE student TO student_backup;
RENAME TABLE teacher TO teacher_backup;
-- ... 其他表
```

## 故障排除

### 問題：找不到對應的 department

**解決方案**：
```sql
-- 檢查缺失的科系
SELECT DISTINCT department FROM student WHERE department NOT IN (SELECT name FROM departments);

-- 添加缺失的科系
INSERT INTO departments (code, name, available_systems) 
VALUES ('NEW_DEPT', '新科系', '["五專"]');

-- 更新學生記錄
UPDATE student_normalized sn
JOIN student s ON sn.user_id = s.user_id
SET sn.department_id = (SELECT id FROM departments WHERE name = s.department)
WHERE sn.department_id IS NULL;
```

### 問題：聊天記錄遷移失敗

**原因**：找不到對應的 user

**解決方案**：
```sql
-- 檢查無法遷移的記錄
SELECT DISTINCT from_user FROM private_chat_history 
WHERE from_user NOT IN (SELECT username FROM user);

-- 創建缺失的用戶或跳過這些記錄
```

## 相關文件

- `scripts/database/complete_normalize_to_3nf.sql` - 創建正規化表結構
- `scripts/database/migrate_all_data_to_3nf.sql` - 數據遷移腳本
- `scripts/setup/execute_complete_3nf_normalization.php` - PHP 執行腳本
- `docs/DATABASE_NORMALIZATION_3NF.md` - 原有正規化文檔（部分表）

## 正規化檢查清單

- [ ] 備份資料庫
- [ ] 執行創建表腳本
- [ ] 執行數據遷移腳本
- [ ] 驗證遷移統計
- [ ] 測試視圖功能
- [ ] 檢查 NULL 值記錄
- [ ] 補充缺失的參考數據
- [ ] 更新應用程式代碼
- [ ] 測試應用功能
- [ ] 備份舊表
- [ ] 清理舊表（可選）

