# 修復外鍵約束錯誤指南

## 問題說明

在添加外鍵約束時，特別是 `fk_enrollment_teacher` 時出現錯誤，通常原因如下：

### 常見錯誤原因

1. **數據不一致**
   - `enrollment_applications_normalized.recommended_teacher_user_id` 中有值，但對應的 `teacher_normalized.user_id` 不存在
   - `cooperation_applications_normalized.teacher_user_id` 中有值，但對應的 `teacher_normalized.user_id` 不存在

2. **表結構問題**
   - 欄位類型不匹配
   - 欄位為 NOT NULL 但數據為 NULL

3. **外鍵已存在**
   - 可能已有不同名稱的外鍵約束

## 解決步驟

### 步驟 1: 檢查數據一致性

執行檢查腳本：
```
scripts/database/check_before_add_foreign_keys.sql
```

這個腳本會顯示：
- 所有無效的 `teacher_user_id` 記錄
- 所有無效的 `identity_id`、`gender_id`、`grade_id` 等記錄
- 表結構信息

### 步驟 2: 清理無效數據

**選項 A：使用安全修復腳本（推薦）**

執行：
```
scripts/database/fix_missing_foreign_keys_safe.sql
```

這個腳本會：
1. 自動清理所有無效的外鍵引用（設為 NULL）
2. 然後添加所有外鍵約束
3. 最後驗證結果

**選項 B：手動清理**

如果發現無效數據，手動執行：

```sql
-- 清理無效的 recommended_teacher_user_id
UPDATE enrollment_applications_normalized 
SET recommended_teacher_user_id = NULL
WHERE recommended_teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized 
    WHERE user_id = enrollment_applications_normalized.recommended_teacher_user_id
);

-- 清理無效的 teacher_user_id（cooperation_applications_normalized）
-- 注意：如果這個欄位是必填的，可能需要先創建對應的 teacher 記錄
UPDATE cooperation_applications_normalized 
SET teacher_user_id = NULL
WHERE teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized 
    WHERE user_id = cooperation_applications_normalized.teacher_user_id
);
```

### 步驟 3: 添加外鍵約束

清理數據後，執行：
```
scripts/database/fix_missing_foreign_keys.sql
```

## 特殊情況處理

### 情況 1: teacher_user_id 是必填欄位

如果 `cooperation_applications_normalized.teacher_user_id` 是 `NOT NULL`，但有無效數據：

**選項 A：創建對應的 teacher 記錄**

```sql
-- 查看需要創建的 teacher 記錄
SELECT DISTINCT can.teacher_user_id
FROM cooperation_applications_normalized can
WHERE can.teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized tn 
    WHERE tn.user_id = can.teacher_user_id
);

-- 然後手動創建這些 teacher 記錄
-- 例如：
INSERT INTO teacher_normalized (user_id, name)
SELECT DISTINCT can.teacher_user_id, '待補全' AS name
FROM cooperation_applications_normalized can
WHERE can.teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized tn 
    WHERE tn.user_id = can.teacher_user_id
);
```

**選項 B：修改表結構允許 NULL**

```sql
ALTER TABLE cooperation_applications_normalized
MODIFY COLUMN teacher_user_id INT NULL;
```

### 情況 2: 數據遷移問題

如果數據是從舊表遷移來的，可能需要重新執行遷移腳本：
```
scripts/database/migrate_all_data_to_3nf.sql
```

## 驗證

執行完成後，驗證外鍵是否成功添加：

```sql
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    '✅ 已設置' AS 狀態
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = 'topics_good'
AND CONSTRAINT_TYPE = 'FOREIGN KEY'
AND CONSTRAINT_NAME LIKE 'fk_%';
```

或運行完整驗證：
```
http://localhost/scripts/setup/verify_3nf_normalization.php
```

## 預防措施

1. **數據遷移前檢查**
   - 確保所有引用表的數據都已遷移
   - 確保所有外鍵值都有效

2. **分步執行**
   - 先遷移基礎表（user, departments, grades 等）
   - 再遷移依賴表（teacher_normalized, student_normalized）
   - 最後遷移應用表（enrollment_applications_normalized 等）

3. **數據驗證**
   - 遷移後立即檢查數據完整性
   - 添加外鍵前檢查所有引用是否有效

