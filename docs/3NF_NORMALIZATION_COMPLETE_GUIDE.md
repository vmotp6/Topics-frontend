# 完整 3NF 正規化執行指南

## 概述

本指南說明如何將 `topics_good` 資料庫完整正規化至第三正規化（3NF）標準。3NF 正規化可以：

- ✅ 消除數據冗餘
- ✅ 提高數據一致性
- ✅ 改善資料庫維護性
- ✅ 降低數據更新異常風險

## 3NF 正規化原則

### 第一正規化（1NF）
- 每個欄位都是原子值（不可再分割）
- 沒有重複的列
- **範例：** 將 `intention1`, `intention2`, `intention3` 改為 `enrollment_preferences` 明細表

### 第二正規化（2NF）
- 符合 1NF
- 非主鍵欄位完全依賴於主鍵
- **範例：** 所有欄位都直接關聯到主鍵

### 第三正規化（3NF）
- 符合 2NF
- 消除傳遞依賴（非主鍵欄位不能依賴於其他非主鍵欄位）
- **範例：** 將 `department` 字串改為 `department_id` 外鍵引用 `departments` 表

## 正規化改進項目

### 1. 消除字串冗餘
- ❌ **舊結構：** `student.department = '資訊管理科'`（字串重複存儲）
- ✅ **新結構：** `student_normalized.department_id` → `departments.id`

### 2. 解決重複列
- ❌ **舊結構：** `enrollment_applications` 有 `intention1`, `intention2`, `intention3`
- ✅ **新結構：** `enrollment_preferences` 表（一對多關係）

### 3. 獨立公司資訊
- ❌ **舊結構：** 公司資訊直接存在申請表中
- ✅ **新結構：** `companies` 獨立表，申請表引用 `company_id`

### 4. 統一狀態管理
- ❌ **舊結構：** `status ENUM('pending', 'contacted', ...)`
- ✅ **新結構：** `application_statuses` 表，引用 `status_id`

## 正規化後的表結構

### 基礎參考表（9個）

| 表名 | 說明 | 用途 |
|------|------|------|
| `departments` | 科系表 | 存放所有科系資料 |
| `education_systems` | 學制表 | 存放學制資料（五專、四技等） |
| `application_statuses` | 申請狀態表 | 統一管理申請狀態 |
| `identities` | 身分別表 | 存放身分別（學生、家長） |
| `genders` | 性別表 | 存放性別選項 |
| `grades` | 年級表 | 存放年級資料 |
| `companies` | 公司表 | 存放公司資訊 |
| `message_types` | 訊息類型表 | 存放訊息類型 |
| `role_types` | 角色類型表 | 統一管理角色 |

### 正規化主表（11個）

| 表名 | 說明 | 改進 |
|------|------|------|
| `student_normalized` | 正規化學生表 | 移除 `department`, `grade` 字串，改用外鍵 |
| `teacher_normalized` | 正規化老師表 | 移除 `department` 字串，改用外鍵 |
| `enrollment_applications_normalized` | 正規化就讀意願申請表 | 移除所有字串狀態，改用外鍵 |
| `enrollment_preferences` | 就讀意願明細表 | 解決 1NF 問題（1-3 志願） |
| `cooperation_applications_normalized` | 正規化產學合作申請表 | 公司資訊獨立，狀態改用外鍵 |
| `cooperation_application_categories` | 產學合作申請類別明細表 | 解決多值屬性問題 |
| `ip_rights` | 智慧財產權明細表 | 解決智慧財產權多欄位問題 |
| `chat_groups_normalized` | 正規化聊天群組表 | 移除字串欄位，改用外鍵 |
| `group_members_normalized` | 正規化群組成員表 | 移除 `username`，改用 `user_id` |
| `private_chat_history_normalized` | 正規化私聊訊息表 | 移除 `username`，改用 `user_id` |
| `group_messages_normalized` | 正規化群組訊息表 | 移除 `username`，改用 `user_id` |

## 執行步驟

### 方法一：使用 PHP 腳本（推薦）⭐

1. **訪問執行腳本：**
   ```
   http://localhost/scripts/setup/execute_complete_3nf_normalization.php
   ```

2. **確認執行：**
   - 閱讀執行前提醒
   - 點擊「確認執行 3NF 正規化」按鈕

3. **查看結果：**
   - 腳本會自動創建所有表結構
   - 遷移現有數據
   - 顯示執行統計

4. **驗證結果：**
   ```
   http://localhost/scripts/setup/verify_3nf_compliance.php
   ```

### 方法二：使用 SQL 腳本

1. **執行創建表結構腳本：**
   ```bash
   mysql -u root -p topics_good < scripts/database/complete_3nf_normalization.sql
   ```

2. **執行數據遷移腳本：**
   ```bash
   mysql -u root -p topics_good < scripts/database/migrate_data_to_3nf.sql
   ```

3. **驗證結果：**
   - 訪問驗證腳本：`http://localhost/scripts/setup/verify_3nf_compliance.php`
   - 或手動執行驗證查詢

### 方法三：使用 phpMyAdmin

1. 登入 phpMyAdmin
2. 選擇 `topics_good` 資料庫
3. 點擊「SQL」標籤
4. 依次執行：
   - `scripts/database/complete_3nf_normalization.sql`
   - `scripts/database/migrate_data_to_3nf.sql`

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

### 學生數據遷移

```sql
-- 自動匹配科系名稱到 department_id
SELECT id FROM departments WHERE name = student.department

-- 自動匹配年級名稱到 grade_id
SELECT id FROM grades WHERE name = student.grade
```

### 老師數據遷移

```sql
-- 自動匹配科系名稱到 department_id
SELECT id FROM departments WHERE name = teacher.department
```

### 就讀意願申請遷移

```sql
-- 身份轉換
identity = '學生' → identity_id = (SELECT id FROM identities WHERE code = 'STUDENT')

-- 狀態轉換
status = 'pending' → status_id = (SELECT id FROM application_statuses WHERE code = 'PENDING')

-- 志願拆分
intention1, system1, department1 → enrollment_preferences (preference_order = 1)
intention2, system2, department2 → enrollment_preferences (preference_order = 2)
intention3, system3, department3 → enrollment_preferences (preference_order = 3)
```

## 驗證檢查項目

執行驗證腳本會檢查以下項目：

1. ✅ **基礎參考表存在性**（9個表）
2. ✅ **正規化表存在性**（11個表）
3. ✅ **外鍵約束設置**（所有外鍵關係）
4. ✅ **數據一致性**（原始表 vs 正規化表記錄數）
5. ✅ **向後兼容視圖**（6個視圖）

## 預期結果

執行完成後，驗證報告應該顯示：

- ✅ 通過率：**90%+**
- ✅ 所有表：**已創建**
- ✅ 所有外鍵：**已設置**
- ✅ 數據一致性：**一致**
- ✅ 3NF 標準：**完全符合**

## 注意事項

### ⚠️ 執行前

1. **備份資料庫**
   ```bash
   mysqldump -u root -p topics_good > backup_before_3nf.sql
   ```

2. **確認資料庫連接設定**
   - 檢查 `execute_complete_3nf_normalization.php` 中的資料庫設定
   - 確認資料庫使用者有足夠權限

3. **測試環境先執行**
   - 建議先在測試環境執行
   - 確認無誤後再在生產環境執行

### ⚠️ 執行後

1. **檢查 NULL 值**
   - 某些數據可能因為找不到對應記錄而設為 NULL
   - 需要手動檢查並補齊缺失的參考數據

2. **更新應用程式代碼**
   - 逐步將代碼從使用原表改為使用正規化表
   - 或使用視圖保持向後兼容

3. **測試應用功能**
   - 確保所有功能正常運作
   - 檢查性能影響

## 故障排除

### 問題 1: 找不到對應的 department

**解決方案：**
```sql
-- 檢查缺失的科系
SELECT DISTINCT department FROM student 
WHERE department NOT IN (SELECT name FROM departments);

-- 添加缺失的科系
INSERT INTO departments (code, name, available_systems) 
VALUES ('NEW_DEPT', '新科系', '["五專"]');

-- 更新學生記錄
UPDATE student_normalized sn
JOIN student s ON sn.user_id = s.user_id
SET sn.department_id = (SELECT id FROM departments WHERE name = s.department)
WHERE sn.department_id IS NULL;
```

### 問題 2: 外鍵約束添加失敗

**原因：** 數據遷移未完成或存在無效引用

**解決方案：**
```sql
-- 清理無效的外鍵引用
UPDATE enrollment_applications_normalized
SET recommended_teacher_user_id = NULL
WHERE recommended_teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized 
    WHERE user_id = enrollment_applications_normalized.recommended_teacher_user_id
);
```

### 問題 3: 數據遷移失敗

**原因：** 找不到對應的 user 或參考記錄

**解決方案：**
```sql
-- 檢查無法遷移的記錄
SELECT DISTINCT from_user FROM private_chat_history 
WHERE from_user NOT IN (SELECT username FROM user);

-- 創建缺失的用戶或跳過這些記錄
```

## 相關文件

- `scripts/database/complete_3nf_normalization.sql` - 創建正規化表結構
- `scripts/database/migrate_data_to_3nf.sql` - 數據遷移腳本
- `scripts/setup/execute_complete_3nf_normalization.php` - PHP 執行腳本
- `scripts/setup/verify_3nf_compliance.php` - 驗證腳本
- `docs/COMPLETE_3NF_NORMALIZATION_GUIDE.md` - 原有指南（部分表）
- `docs/DATABASE_NORMALIZATION_3NF.md` - 原有正規化文檔

## 執行檢查清單

- [ ] 備份資料庫
- [ ] 確認資料庫連接設定
- [ ] 執行創建表結構腳本
- [ ] 執行數據遷移腳本
- [ ] 驗證遷移統計
- [ ] 檢查 NULL 值記錄
- [ ] 補充缺失的參考數據
- [ ] 測試視圖功能
- [ ] 更新應用程式代碼
- [ ] 測試應用功能
- [ ] 備份正規化後的資料庫

## 後續步驟

1. **更新應用程式代碼**
   - 使用正規化表或視圖
   - 逐步移除對舊表的依賴

2. **性能優化**
   - 根據實際使用情況調整索引
   - 監控查詢性能

3. **清理舊表**（可選）
   - 確認一切正常後，可將舊表重命名為 `*_backup`
   - 建議保留一段時間以防萬一

## 總結

完成 3NF 正規化後，您的資料庫將：

- ✅ 消除數據冗餘
- ✅ 提高數據一致性
- ✅ 改善維護性
- ✅ 符合資料庫正規化最佳實踐

如有任何問題，請參考故障排除章節或檢查相關文檔。

