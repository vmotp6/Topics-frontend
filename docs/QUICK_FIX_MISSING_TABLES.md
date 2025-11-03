# 快速修復缺失的正規化表

## 問題說明

根據驗證報告，資料庫缺少以下內容：
- `role_types` 表
- 多個正規化表（student_normalized, teacher_normalized 等）
- 多個視圖（student_view, teacher_view 等）
- 部分外鍵約束

## 解決方案

### 方法一：使用 SQL 腳本（推薦）

1. **打開 phpMyAdmin**
   - 訪問 `http://localhost/phpmyadmin`
   - 選擇資料庫 `topics_good`

2. **執行 SQL 腳本**
   - 點擊「SQL」標籤
   - 複製 `scripts/database/quick_fix_missing_tables.sql` 的內容
   - 貼上並執行

3. **執行數據遷移**
   - 執行 `scripts/database/migrate_all_data_to_3nf.sql`
   - 將原始表的數據遷移到正規化表

4. **驗證結果**
   - 訪問 `http://localhost/scripts/setup/verify_3nf_normalization.php`
   - 檢查通過率是否提升

### 方法二：使用 PHP 腳本

1. **訪問修復腳本**
   ```
   http://localhost/scripts/setup/fix_missing_normalized_tables.php
   ```

2. **查看執行結果**
   - 腳本會自動創建所有缺失的表和視圖
   - 顯示執行進度和結果

## 創建的內容

### 表
- ✅ `role_types` - 角色類型表
- ✅ `student_normalized` - 正規化學生表
- ✅ `teacher_normalized` - 正規化老師表
- ✅ `chat_groups_normalized` - 正規化聊天群組表
- ✅ `group_members_normalized` - 正規化群組成員表
- ✅ `private_chat_history_normalized` - 正規化私聊訊息表
- ✅ `group_messages_normalized` - 正規化群組訊息表
- ✅ `ai_chat_history_normalized` - 正規化AI聊天記錄表

### 視圖
- ✅ `student_view` - 學生視圖
- ✅ `teacher_view` - 老師視圖
- ✅ `private_chat_history_view` - 私聊訊息視圖
- ✅ `group_messages_view` - 群組訊息視圖
- ✅ `chat_groups_view` - 聊天群組視圖
- ✅ `group_members_view` - 群組成員視圖

### 外鍵約束
- ✅ `enrollment_applications_normalized` 的所有外鍵
- ✅ `cooperation_applications_normalized` 的所有外鍵

## 注意事項

1. **備份資料庫**：執行前務必備份
2. **外鍵檢查**：腳本會暫時關閉外鍵檢查，執行完後重新開啟
3. **錯誤處理**：如果表已存在，會自動忽略錯誤
4. **數據遷移**：創建表後，需要執行數據遷移腳本

## 預期結果

執行完成後，驗證報告應該顯示：
- ✅ 所有基礎參考表存在
- ✅ 所有正規化表存在
- ✅ 所有視圖存在
- ✅ 外鍵關係正確設置
- 📊 通過率從 40.3% 提升到 80%+

