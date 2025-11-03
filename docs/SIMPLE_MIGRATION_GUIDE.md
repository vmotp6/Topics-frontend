# 簡單數據遷移指南

## 執行順序

### 第一步：遷移 student 數據

執行：
```
scripts/database/migrate_student_data_simple.sql
```

這個腳本會：
1. 先顯示要遷移的數據（包括匹配檢查）
2. 執行遷移
3. 顯示遷移結果

### 第二步：遷移 teacher 數據

執行：
```
scripts/database/migrate_teacher_data_simple.sql
```

這個腳本會：
1. 先顯示要遷移的數據（包括匹配檢查）
2. 可以選擇先測試插入一條記錄（取消註釋）
3. 執行完整遷移
4. 顯示遷移結果

## 查看數據樣本

在執行遷移前，您已經運行了診斷腳本查看 `teacher` 表的數據樣本。

如果看到了數據，請檢查：

1. **user_id 是否為 NULL？**
   - 如果為 NULL，這些記錄無法遷移（因為 user_id 是主鍵）

2. **department 是否能在 departments 表中找到？**
   - 查看 `departments` 表的數據
   - 確認名稱是否完全匹配（區分大小寫）

3. **created_at 和 updated_at 是否有值？**
   - 如果為 NULL，腳本會使用 NOW() 作為默認值

## 常見問題

### Q: 看到 "user 不存在" 的記錄

**原因**：`teacher.user_id` 在 `user` 表中找不到對應記錄

**解決方案**：
- 這些記錄無法遷移（外鍵約束要求）
- 需要先在 `user` 表中創建對應的用戶，或者跳過這些記錄

### Q: 看到 "department 不匹配" 的記錄

**原因**：`teacher.department` 的值在 `departments` 表中找不到

**解決方案**：
1. 查看 `departments` 表中的實際名稱
2. 手動更新 `teacher.department` 的值使其匹配
3. 或者在 `departments` 表中添加缺少的科系

### Q: 遷移時出現錯誤

**檢查步驟**：
1. 確認 `teacher_normalized` 表已創建
2. 確認 `user_id` 不為 NULL
3. 確認 `user_id` 存在於 `user` 表
4. 查看具體錯誤訊息

## 手動檢查命令

如果自動遷移失敗，可以使用以下命令手動檢查：

```sql
-- 1. 查看 teacher 表的數據
SELECT * FROM teacher LIMIT 5;

-- 2. 查看 departments 表的數據
SELECT * FROM departments;

-- 3. 檢查匹配情況
SELECT 
    t.department AS teacher_department,
    d.name AS department_table_name,
    d.id AS department_id
FROM teacher t
LEFT JOIN departments d ON d.name = t.department
WHERE t.department IS NOT NULL;

-- 4. 檢查 user_id 是否存在
SELECT 
    t.user_id,
    CASE 
        WHEN EXISTS (SELECT 1 FROM user u WHERE u.id = t.user_id)
        THEN '✅ 存在'
        ELSE '❌ 不存在'
    END AS user_exists
FROM teacher t
WHERE t.user_id IS NOT NULL;
```

## 完成後的驗證

遷移完成後，執行：

```sql
-- 檢查遷移結果
SELECT 
    (SELECT COUNT(*) FROM teacher WHERE user_id IS NOT NULL) AS 原始記錄數,
    (SELECT COUNT(*) FROM teacher_normalized) AS 遷移記錄數;
```

應該看到兩個數字相同（或接近）。

然後重新運行完整驗證：
```
http://localhost/scripts/setup/verify_3nf_normalization.php
```

