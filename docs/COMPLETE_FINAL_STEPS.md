# 完成 3NF 正規化的最後步驟

## 當前狀態

根據最新驗證報告：
- ✅ **大部分外鍵已設置**：11/14
- ✅ **數據已遷移**：student 和 teacher 數據已成功遷移
- ⚠️ **還有 3 個外鍵未設置**

## 待完成項目

### 最後 3 個外鍵約束：

1. `enrollment_applications_normalized.recommended_teacher_user_id` → `teacher_normalized(user_id)`
2. `cooperation_applications_normalized.teacher_user_id` → `teacher_normalized(user_id)`
3. `cooperation_applications_normalized.department_id` → `departments(id)`

## 執行步驟

### 添加最後 3 個外鍵約束

執行：
```
scripts/database/add_final_3_foreign_keys.sql
```

這個腳本會：
1. **檢查數據有效性**
   - 找出所有無效的 `recommended_teacher_user_id`
   - 找出所有無效的 `teacher_user_id`
   - 找出所有無效的 `department_id`

2. **清理無效數據**
   - 將無效的外鍵引用設為 NULL（允許 NULL 的欄位）
   - 確保所有數據都滿足外鍵約束

3. **添加外鍵約束**
   - 添加 `fk_enrollment_teacher`
   - 添加 `fk_cooperation_teacher`
   - 添加 `fk_cooperation_department`

4. **驗證結果**
   - 顯示所有已添加的外鍵
   - 確認所有 3 個外鍵都已成功設置

## 執行完成後

### 驗證最終結果

重新運行驗證腳本：
```
http://localhost/scripts/setup/verify_3nf_normalization.php
```

預期結果：
- ✅ **通過率：90%+**
- ✅ **所有外鍵約束：14/14 已設置**
- ✅ **數據一致性：一致**

## 常見問題

### Q: 如果外鍵添加失敗？

**可能原因**：
1. 數據中包含無效的引用值
2. 表結構問題（NOT NULL 但允許 NULL）

**解決方案**：
腳本會自動清理無效數據，但如果仍有問題，可能需要：

```sql
-- 檢查表結構
DESCRIBE enrollment_applications_normalized;
DESCRIBE cooperation_applications_normalized;

-- 如果 teacher_user_id 是 NOT NULL 但有 NULL 值
ALTER TABLE cooperation_applications_normalized
MODIFY COLUMN teacher_user_id INT NULL;
```

### Q: 清理數據後會丟失信息嗎？

**回答**：
- 無效的外鍵引用會被設為 NULL
- 但原始數據仍然保留在原始表中（`enrollment_applications`, `cooperation_applications`）
- 如果需要，可以手動修復這些數據，然後重新遷移

## 其他待處理項目（非緊急）

以下項目不影響 3NF 正規化標準，可以後續處理：

### 使用 username 的表（違反 3NF）

這些表已經有對應的正規化表：
- `chat_history` → 可創建 `chat_history_normalized`
- `enrollment_applications` → 已有 `enrollment_applications_normalized`
- `group_chat_members` → 已有 `group_members_normalized`
- `group_chat_messages` → 已有 `group_messages_normalized`
- `group_info` → 已有 `chat_groups_normalized`
- `private_chat_history` → 已有 `private_chat_history_normalized`

**處理方式**：
- 逐步遷移應用程式代碼使用正規化表和視圖
- 創建對應的正規化表（如果還沒有）
- 使用視圖保持向後兼容

## 完成後的建議

1. **備份資料庫**
   - 正規化完成後，創建完整備份

2. **測試應用程式**
   - 確保所有功能正常運作
   - 測試使用正規化表和視圖的功能

3. **監控性能**
   - 檢查查詢性能
   - 優化必要的索引

4. **考慮清理舊表**（可選）
   - 確認無誤後，可將舊表重命名為 `*_backup`
   - 建議保留至少 1-3 個月以防萬一

