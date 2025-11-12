# 完成 3NF 正規化的最後步驟

## 當前狀態

✅ **通過率：80.6%**（從 40.3% 大幅提升！）

### 已完成的項目：
- ✅ 所有基礎參考表已創建
- ✅ 所有正規化表已創建（7個）
- ✅ 所有視圖已創建（6個）
- ✅ 大部分外鍵約束已設置（11/14）

### 待完成的項目：
1. **3個外鍵約束未設置**
   - `enrollment_applications_normalized.recommended_teacher_user_id`
   - `cooperation_applications_normalized.teacher_user_id`
   - `cooperation_applications_normalized.department_id`

2. **數據遷移未完成**
   - `student_normalized` 表是空的（原始表有 2 筆）
   - `teacher_normalized` 表是空的（原始表有 11 筆）

## 執行步驟

### 步驟 1: 遷移 student 和 teacher 數據

**重要性：** 這必須先執行，因為後續的外鍵約束需要這些數據存在。

執行：
```
scripts/database/migrate_student_teacher_data.sql
```

這個腳本會：
- 從 `student` 表遷移數據到 `student_normalized`
- 從 `teacher` 表遷移數據到 `teacher_normalized`
- 自動查找對應的 `department_id` 和 `grade_id`
- 驗證遷移結果

### 步驟 2: 修復剩餘的 3 個外鍵約束

執行：
```
scripts/database/fix_remaining_foreign_keys.sql
```

這個腳本會：
- 檢查並清理無效的外鍵引用
- 添加 3 個缺失的外鍵約束
- 驗證結果

### 步驟 3: 驗證最終結果

重新運行驗證腳本：
```
http://localhost/scripts/setup/verify_3nf_normalization.php
```

預期結果：
- ✅ 通過率提升到 **90%+**
- ✅ 所有外鍵約束已設置
- ✅ 數據一致性檢查通過

## 執行順序很重要！

**必須按照以下順序執行：**

1. ✅ 先執行 `migrate_student_teacher_data.sql`（遷移數據）
2. ✅ 再執行 `fix_remaining_foreign_keys.sql`（添加外鍵）

**為什麼？**

因為外鍵約束需要：
- `teacher_normalized` 表有數據
- 外鍵引用的值必須在被引用表中存在

如果先添加外鍵再遷移數據，會導致：
- 外鍵添加失敗（因為被引用的表是空的）
- 或者外鍵添加成功，但數據遷移時會因為外鍵約束而失敗

## 其他待處理項目（非緊急）

以下項目不影響 3NF 正規化標準，可以後續處理：

1. **使用 username 的表**
   - `chat_history`
   - `enrollment_applications`（原始表）
   - `group_chat_members`
   - `group_chat_messages`
   - `group_info`
   - `private_chat_history`（原始表）

   這些表已經有對應的正規化表（`*_normalized`），可以逐步遷移應用程式代碼使用正規化表。

2. **數據一致性**
   - 確保所有應用程式代碼從舊表切換到新表和視圖

## 預期最終結果

執行完成後，驗證報告應該顯示：

- ✅ 通過率：**90%+**
- ✅ 所有外鍵約束：**14/14 已設置**
- ✅ 數據一致性：**一致**
- ✅ 3NF 標準：**完全符合**

## 問題排查

### 如果外鍵添加失敗：

1. **檢查數據是否存在**
   ```sql
   SELECT COUNT(*) FROM teacher_normalized;
   SELECT COUNT(*) FROM student_normalized;
   ```

2. **檢查是否有無效引用**
   ```sql
   -- 檢查 enrollment_applications_normalized
   SELECT * FROM enrollment_applications_normalized
   WHERE recommended_teacher_user_id IS NOT NULL
   AND NOT EXISTS (
       SELECT 1 FROM teacher_normalized 
       WHERE user_id = enrollment_applications_normalized.recommended_teacher_user_id
   );
   ```

3. **檢查表結構**
   ```sql
   DESCRIBE enrollment_applications_normalized;
   DESCRIBE teacher_normalized;
   ```

## 完成後的下一步

1. **測試應用程式功能**
   - 確保所有功能正常運作
   - 使用視圖保持向後兼容

2. **備份資料庫**
   - 正規化完成後，創建完整備份

3. **考慮重命名舊表**
   - 確認無誤後，可將舊表重命名為 `*_backup`
   - 但建議保留一段時間以防萬一

