# 康寧大學產學合作申請表提交失敗 - 故障排除指南

## 問題描述
當您嘗試提交康寧大學產學合作申請表時，系統顯示「提交失敗，請稍後再試」的錯誤訊息。

## 可能原因與解決方案

### 1. 資料表結構不匹配
**問題**：資料庫中的 `cooperation_applications` 資料表結構與API期望的欄位不匹配。

**解決方案**：
1. 執行修復腳本：
   ```
   訪問：http://your-domain/backend/fix_cooperation_system.php
   ```
2. 或手動執行SQL：
   ```sql
   -- 刪除舊表
   DROP TABLE IF EXISTS cooperation_applications;
   
   -- 執行 update_cooperation_table.sql 中的完整建表語句
   ```

### 2. 資料庫連線問題
**問題**：無法連接到資料庫伺服器。

**檢查項目**：
- 資料庫主機：`100.79.58.120`
- 資料庫名稱：`topics_good`
- 使用者：`root`
- 密碼：空

**解決方案**：
1. 確認資料庫伺服器正在運行
2. 檢查網路連線
3. 確認資料庫連線設定正確

### 3. 檔案上傳權限問題
**問題**：上傳目錄沒有適當的寫入權限。

**解決方案**：
```bash
# 建立上傳目錄
mkdir -p uploads/cooperation/

# 設定權限
chmod 755 uploads/cooperation/
```

### 4. PHP設定問題
**問題**：PHP設定限制檔案上傳或執行時間。

**檢查項目**：
- `upload_max_filesize`：檔案上傳最大大小
- `post_max_size`：POST請求最大大小
- `max_execution_time`：最大執行時間

**解決方案**：
在 `php.ini` 中調整設定：
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
```

## 診斷工具

### 1. 系統診斷
訪問診斷頁面來檢查系統狀態：
```
http://your-domain/backend/diagnose_cooperation_system.php
```

### 2. 自動修復
執行自動修復腳本：
```
http://your-domain/backend/fix_cooperation_system.php
```

## 常見錯誤訊息對應

| 錯誤訊息 | 可能原因 | 解決方案 |
|---------|---------|---------|
| 「提交失敗，請稍後再試」 | 資料庫連線或資料表結構問題 | 執行修復腳本 |
| 「檔案上傳失敗」 | 上傳目錄權限或磁碟空間不足 | 檢查目錄權限和磁碟空間 |
| 「缺少必要欄位」 | 表單未完整填寫 | 檢查所有必填欄位 |
| 「資料庫錯誤」 | 資料庫連線或SQL語法錯誤 | 檢查資料庫設定和連線 |

## 預防措施

### 1. 定期備份
```sql
-- 備份資料表
mysqldump -h 100.79.58.120 -u root topics_good cooperation_applications > backup.sql
```

### 2. 監控系統
- 定期檢查資料庫連線狀態
- 監控上傳目錄的磁碟空間
- 檢查PHP錯誤日誌

### 3. 測試流程
1. 定期測試申請表上傳功能
2. 檢查檔案上傳是否正常
3. 驗證資料是否正確儲存

## 聯絡支援

如果問題持續存在，請提供以下資訊：
1. 錯誤訊息的完整內容
2. 瀏覽器的開發者工具中的網路請求詳情
3. 伺服器的錯誤日誌
4. 系統診斷報告的結果

## 更新日誌

- **2024-01-XX**：建立故障排除指南
- **2024-01-XX**：新增診斷和修復工具
- **2024-01-XX**：更新資料表結構以支援康寧大學格式
