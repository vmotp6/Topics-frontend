# SMTP 郵件系統設置指南

## 🚀 快速設置步驟

### 1. 配置 SMTP 參數

請編輯 `config.php` 文件，填入以下 SMTP 設定：

```php
// SMTP 郵件設定
define('SMTP_HOST', 'smtp.gmail.com');           // Gmail SMTP 伺服器
define('SMTP_PORT', 587);                        // Gmail SMTP 端口 (TLS)
define('SMTP_USERNAME', 'your-email@gmail.com'); // 您的 Gmail 地址
define('SMTP_PASSWORD', 'your-app-password');    // 您的 Gmail 應用程序密碼
define('SMTP_FROM_EMAIL', 'your-email@gmail.com'); // 發送者郵件地址
define('SMTP_FROM_NAME', '康寧大學五專入學說明會');  // 發送者名稱
define('SMTP_SECURE', 'tls');                    // 加密類型
```

### 2. 安裝 PHPMailer（推薦方式）

#### 方式一：使用 Composer（推薦）

```bash
# 在 frontend 目錄下執行
cd C:\Topics2\Topics-frontend\frontend
composer require phpmailer/phpmailer
```

#### 方式二：手動下載

1. 前往 [PHPMailer GitHub](https://github.com/PHPMailer/PHPMailer)
2. 下載最新版本的 ZIP 文件
3. 解壓縮到 `frontend/PHPMailer/` 目錄
4. 確保目錄結構如下：
   ```
   frontend/
   ├── PHPMailer/
   │   └── src/
   │       ├── PHPMailer.php
   │       ├── SMTP.php
   │       └── Exception.php
   ├── includes/
   │   └── email_functions.php
   └── config.php
   ```

### 3. Gmail 應用程序密碼設置

1. 登入您的 Gmail 帳戶
2. 前往 [Google 帳戶設定](https://myaccount.google.com/)
3. 選擇「安全性」
4. 在「登入 Google」部分，選擇「應用程式密碼」
5. 選擇「郵件」和您的設備
6. 複製生成的 16 位應用程序密碼
7. 將此密碼填入 `config.php` 中的 `SMTP_PASSWORD`

### 4. 測試郵件功能

建立並運行測試文件：

```php
<?php
require_once 'config.php';
require_once 'includes/email_functions.php';

// 測試郵件發送
$testResult = testEmailFunction('your-test-email@example.com');

if ($testResult) {
    echo "✅ 郵件發送測試成功！";
} else {
    echo "❌ 郵件發送測試失敗，請檢查設定。";
}
?>
```

## 🔧 備用方案

如果無法安裝 PHPMailer，系統會自動使用 PHP 內建的 `mail()` 函數作為備用方案。但這需要您的伺服器支援郵件發送功能。

## 📝 注意事項

1. **安全性**：請不要將真實的密碼提交到版本控制系統
2. **防火牆**：確保伺服器可以連接到 Gmail SMTP 伺服器（端口 587）
3. **錯誤日誌**：郵件發送錯誤會記錄在 PHP 錯誤日誌中
4. **測試**：建議先在測試環境中驗證配置

## 🚨 常見問題

### Q: 收到「SMTP connect() failed」錯誤
A: 檢查網路連接和防火牆設定，確保可以連接到 smtp.gmail.com:587

### Q: 收到「Invalid address」錯誤
A: 檢查 `SMTP_FROM_EMAIL` 是否正確填寫

### Q: 收到「Authentication failed」錯誤
A: 檢查 Gmail 應用程序密碼是否正確，並確保已啟用兩步驟驗證

### Q: 郵件發送成功但收不到
A: 檢查垃圾郵件文件夾，或嘗試發送到不同的郵件地址

## 📧 支援

如有任何問題，請檢查 PHP 錯誤日誌或聯繫系統管理員。
