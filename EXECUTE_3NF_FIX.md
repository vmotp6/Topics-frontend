# 執行 3NF 正規化修復腳本指南

## 方式 1：使用 PHP 網頁介面（推薦）⭐

### 步驟：
1. 確保您的 Web 服務器正在運行（Apache/XAMPP）
2. 在瀏覽器中訪問：
   ```
   http://localhost/scripts/setup/execute_fix_3nf_issues.php
   ```
3. 點擊「確認執行修復腳本」按鈕
4. 查看執行結果

---

## 方式 2：使用 MySQL 命令行

### 步驟：
1. 打開命令提示字元（CMD）或 PowerShell
2. 切換到 MySQL bin 目錄（通常在 XAMPP 中）：
   ```powershell
   cd C:\xampp\mysql\bin
   ```
3. 登入 MySQL：
   ```bash
   mysql -u root -p topics_good
   ```
   （如果沒有密碼，直接按 Enter）

4. 執行修復腳本：
   ```sql
   source C:/110534225/project/code/Topics-frontend/scripts/database/fix_3nf_remaining_issues.sql;
   ```

5. 執行驗證腳本：
   ```sql
   source C:/110534225/project/code/Topics-frontend/scripts/database/complete_3nf_verification.sql;
   ```

---

## 方式 3：使用 phpMyAdmin

### 步驟：
1. 打開 phpMyAdmin：
   ```
   http://localhost/phpmyadmin
   ```
2. 選擇資料庫 `topics_good`
3. 點擊「SQL」標籤
4. 點擊「選擇檔案」
5. 選擇檔案：`scripts/database/fix_3nf_remaining_issues.sql`
6. 點擊「執行」

---

## 方式 4：使用 Python 腳本

如果您的系統有 Python 環境：

### 步驟：
1. 打開命令提示字元或 PowerShell
2. 切換到專案目錄：
   ```powershell
   cd C:\110534225\project\code\Topics-frontend
   ```
3. 執行 Python 腳本（如果有）：
   ```bash
   python scripts/database/execute_3nf_migration.py
   ```

---

## 執行驗證腳本

修復完成後，執行驗證腳本查看結果：

### PHP 方式：
```
http://localhost/scripts/setup/verify_3nf_normalization.php
```

### MySQL 命令行：
```sql
source C:/110534225/project/code/Topics-frontend/scripts/database/complete_3nf_verification.sql;
```

### phpMyAdmin：
1. 選擇資料庫 `topics_good`
2. 點擊「SQL」標籤
3. 上傳並執行 `scripts/database/complete_3nf_verification.sql`

---

## 注意事項

⚠️ **重要提醒：**
- 執行前請備份資料庫！
- 建議先在測試環境執行
- 如果外鍵已存在，腳本可能會報錯（可以忽略）

---

## 執行順序

1. **先執行修復腳本**：`fix_3nf_remaining_issues.sql`
   - 遷移 teacher 數據
   - 添加外鍵約束

2. **再執行驗證腳本**：`complete_3nf_verification.sql`
   - 檢查所有項目
   - 查看通過率

---

## 常見問題

### Q: 執行時出現「外鍵已存在」錯誤？
**A:** 這是正常的，表示外鍵已經設置好了，可以忽略這個錯誤。

### Q: Teacher 數據遷移失敗？
**A:** 檢查 `teacher` 表中的記錄是否有對應的 `user_id`，並且在 `user` 表中存在。

### Q: 如何備份資料庫？
**A:** 在 phpMyAdmin 中：
1. 選擇資料庫 `topics_good`
2. 點擊「匯出」
3. 選擇「快速」或「自訂」
4. 點擊「執行」

或在命令行：
```bash
mysqldump -u root -p topics_good > backup_$(date +%Y%m%d).sql
```

