# MySQL 啟動問題排查指南

## 快速診斷

### 1. 運行診斷腳本
```bash
php scripts/maintenance/check_mysql_status.php
```

### 2. 運行修復腳本（需要管理員權限）
```bash
# 右鍵點擊，選擇「以系統管理員身份執行」
scripts/maintenance/fix_mysql_startup.bat
```

## 常見問題和解決方案

### 問題 1: MySQL 服務無法啟動

#### 解決方法 A: 使用服務管理器
1. 按 `Win + R`，輸入 `services.msc`，按 Enter
2. 找到 MySQL 服務（可能是 `MySQL`、`MySQL80` 或 `MySQL57`）
3. 右鍵點擊，選擇「啟動」
4. 如果啟動失敗，查看「屬性」→「登入」標籤，確保服務帳戶有足夠權限

#### 解決方法 B: 使用命令提示字元（管理員）
```cmd
# 啟動服務
net start MySQL80
# 或
net start MySQL
# 或
net start MySQL57

# 停止服務
net stop MySQL80
```

### 問題 2: 端口 3306 被占用

#### 檢查端口占用
```cmd
netstat -ano | findstr :3306
```

#### 查看占用端口的程序
```cmd
tasklist | findstr [PID]
```
將 `[PID]` 替換為上面命令顯示的 PID 號碼

#### 解決方法
- 停止占用端口的程序
- 或修改 MySQL 配置文件中的端口號

### 問題 3: 配置文件錯誤

#### 找到配置文件
常見位置：
- `C:\Program Files\MySQL\MySQL Server 8.0\my.ini`
- `C:\ProgramData\MySQL\MySQL Server 8.0\my.ini`
- `C:\xampp\mysql\bin\my.ini`
- `C:\wamp64\bin\mysql\mysql8.0.xx\my.ini`

#### 檢查配置
確保以下配置正確：
```ini
[mysqld]
port=3306
datadir=C:/ProgramData/MySQL/MySQL Server 8.0/Data
```

### 問題 4: 數據目錄損壞

#### 檢查錯誤日誌
常見位置：
- `C:\ProgramData\MySQL\MySQL Server 8.0\Data\*.err`
- `C:\xampp\mysql\data\*.err`

#### 解決方法
1. 備份數據目錄
2. 嘗試修復：
```cmd
mysqld --console --skip-grant-tables
```

### 問題 5: 權限問題

#### 檢查服務帳戶
1. 打開服務管理器 (`services.msc`)
2. 找到 MySQL 服務
3. 右鍵 → 屬性 → 登入標籤
4. 確保帳戶有足夠權限訪問數據目錄

### 問題 6: 重新安裝 MySQL 服務

如果以上方法都無效，可以嘗試重新安裝服務：

```cmd
# 以管理員身份運行
cd "C:\Program Files\MySQL\MySQL Server 8.0\bin"

# 移除服務
mysqld --remove MySQL80

# 安裝服務
mysqld --install MySQL80

# 啟動服務
net start MySQL80
```

## 檢查 MySQL 是否正常運行

### 方法 1: 使用 PHP 測試
```php
<?php
$conn = mysqli_connect('localhost', 'root', '', '', 3306);
if ($conn) {
    echo "✅ MySQL 連接成功";
    mysqli_close($conn);
} else {
    echo "❌ 連接失敗: " . mysqli_connect_error();
}
?>
```

### 方法 2: 使用命令列
```cmd
mysql -u root -p
```

### 方法 3: 使用 MySQL Workbench
連接到 `localhost:3306`

## 常見錯誤訊息

### Error 2003: Can't connect to MySQL server
- **原因**: MySQL 服務沒有運行
- **解決**: 啟動 MySQL 服務

### Error 1045: Access denied
- **原因**: 用戶名或密碼錯誤
- **解決**: 檢查配置文件中的用戶名和密碼

### Error 1064: Syntax error
- **原因**: SQL 語法錯誤
- **解決**: 檢查 SQL 語句

### Port already in use
- **原因**: 端口被其他程序占用
- **解決**: 停止占用端口的程序或更改 MySQL 端口

## 預防措施

1. **定期備份數據**
2. **監控磁碟空間**（MySQL 需要足夠空間）
3. **檢查錯誤日誌**定期檢查
4. **保持 MySQL 更新**

## 需要更多幫助？

如果以上方法都無法解決問題，請：
1. 檢查 MySQL 錯誤日誌
2. 記錄完整的錯誤訊息
3. 檢查系統事件日誌（`eventvwr.msc`）











