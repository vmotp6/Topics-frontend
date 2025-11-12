# 第三正規化（3NF）遷移執行指南

## 快速開始

### 步驟 1：備份資料庫
```bash
mysqldump -u root -p topics_good > backup_before_3nf_$(date +%Y%m%d_%H%M%S).sql
```

### 步驟 2：執行遷移腳本
```bash
mysql -u root -p topics_good < scripts/database/normalize_to_3nf.sql
```

或在 MySQL 客戶端中執行：
```sql
USE topics_good;
SOURCE scripts/database/normalize_to_3nf.sql;
```

### 步驟 3：驗證遷移結果
檢查輸出中的驗證查詢結果，確認：
- 舊表和新表的記錄數量一致
- 視圖可以正常查詢
- 外鍵關係正確

### 步驟 4：測試應用程式
使用視圖測試應用程式是否正常運作：
```sql
SELECT * FROM enrollment_applications_view LIMIT 10;
SELECT * FROM cooperation_applications_view LIMIT 10;
```

## 如果出現問題

### 回滾遷移
```bash
mysql -u root -p topics_good < scripts/database/normalize_to_3nf_rollback.sql
```

然後從備份恢復：
```bash
mysql -u root -p topics_good < backup_before_3nf_*.sql
```

## 遷移後建議

1. **保留舊表**：先不要刪除舊表，重命名為 `*_backup`：
   ```sql
   RENAME TABLE enrollment_applications TO enrollment_applications_backup;
   RENAME TABLE cooperation_applications TO cooperation_applications_backup;
   ```

2. **更新應用程式代碼**：逐步更新代碼以使用新表結構

3. **監控性能**：觀察應用程式性能，必要時調整索引

## 常見問題

### Q: 遷移需要多長時間？
A: 取決於資料量，一般幾分鐘內完成。

### Q: 應用程式需要立即更新嗎？
A: 不需要，視圖提供向後兼容，可以逐步更新。

### Q: 舊表可以刪除嗎？
A: 建議保留至少一個月作為備份，確認無誤後再刪除。

