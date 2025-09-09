# reCAPTCHA 設定指南

## 目前狀況

您的就學意願表已經整合了 reCAPTCHA 驗證系統，目前使用的是 Google 提供的測試用金鑰。這些測試金鑰可以讓系統正常運作，但在正式環境中建議使用您自己申請的金鑰。

## 如何取得正式的 reCAPTCHA 金鑰

### 步驟 1：前往 Google reCAPTCHA 管理頁面

1. 開啟瀏覽器，前往：https://www.google.com/recaptcha/admin/create
2. 使用您的 Google 帳號登入

### 步驟 2：建立新的網站

1. 點擊右上角的 "+" 按鈕
2. 填寫以下資訊：
   - **Label（標籤）**：輸入您的網站名稱，例如 "康寧大學就學意願表"
   - **reCAPTCHA Type（類型）**：選擇 "reCAPTCHA v2" > "I'm not a robot Checkbox"
   - **Domains（網域）**：輸入您的網站網域，例如：
     - `yourdomain.com`
     - `localhost`（如果是本地測試）
     - `127.0.0.1`（如果是本地測試）

### 步驟 3：取得金鑰

1. 點擊 "Submit" 按鈕
2. 系統會顯示您的 **Site Key** 和 **Secret Key**
3. 複製這兩個金鑰

### 步驟 4：更新設定檔案

1. 開啟 `backend/recaptcha_config.php` 檔案
2. 將測試用的金鑰替換為您申請的金鑰：

```php
// 將這行
define('RECAPTCHA_SITE_KEY', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI');

// 替換為您的 Site Key
define('RECAPTCHA_SITE_KEY', '您的_Site_Key');

// 將這行
define('RECAPTCHA_SECRET_KEY', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe');

// 替換為您的 Secret Key
define('RECAPTCHA_SECRET_KEY', '您的_Secret_Key');
```

## 測試 reCAPTCHA 功能

### 方法 1：使用測試頁面

1. 在瀏覽器中開啟：`frontend/test_recaptcha.php`
2. 填寫表單並完成 reCAPTCHA 驗證
3. 提交表單測試功能

### 方法 2：測試就學意願表

1. 登入學生帳號
2. 前往就學意願登錄頁面
3. 填寫表單並完成 reCAPTCHA 驗證
4. 提交表單

## 檔案說明

### 主要檔案

- `backend/recaptcha_config.php` - reCAPTCHA 設定檔案
- `backend/enrollment_api.php` - 就學意願表後端 API（已整合 reCAPTCHA 驗證）
- `frontend/cooperation_upload.php` - 就學意願表前端頁面（已整合 reCAPTCHA）

### 測試檔案

- `frontend/test_recaptcha.php` - reCAPTCHA 測試頁面
- `backend/test_recaptcha_api.php` - reCAPTCHA 測試 API

## 功能特色

### 前端功能

- ✅ 顯示 reCAPTCHA 驗證框
- ✅ 驗證使用者是否完成 reCAPTCHA
- ✅ 顯示錯誤訊息
- ✅ 自動重置 reCAPTCHA（提交後）

### 後端功能

- ✅ 驗證 reCAPTCHA 回應
- ✅ 與 Google 伺服器通訊驗證
- ✅ 拒絕未通過驗證的提交
- ✅ 提供詳細的錯誤訊息

## 常見問題

### Q: reCAPTCHA 驗證框沒有顯示？

A: 檢查以下項目：
1. 確認 Site Key 正確
2. 確認網域設定正確
3. 檢查網路連線
4. 查看瀏覽器控制台是否有錯誤訊息

### Q: 驗證通過但後端仍然拒絕？

A: 檢查以下項目：
1. 確認 Secret Key 正確
2. 確認後端可以連接到 Google 伺服器
3. 檢查 PHP 的 `file_get_contents` 函數是否可用

### Q: 如何停用 reCAPTCHA？

A: 在 `backend/recaptcha_config.php` 中設定：

```php
// 停用 reCAPTCHA 驗證（僅供開發測試）
define('RECAPTCHA_ENABLED', false);
```

然後在 API 檔案中加入條件判斷。

## 安全性注意事項

1. **不要將 Secret Key 暴露在前端程式碼中**
2. **定期更新 reCAPTCHA 金鑰**
3. **監控 reCAPTCHA 的使用情況**
4. **設定適當的網域限制**

## 技術支援

如果您在設定過程中遇到問題，可以：

1. 查看瀏覽器控制台的錯誤訊息
2. 檢查 PHP 錯誤日誌
3. 使用測試頁面進行除錯
4. 參考 Google reCAPTCHA 官方文件

---

**注意**：目前使用的測試金鑰可以讓系統正常運作，但建議在正式環境中使用您自己申請的金鑰以獲得更好的安全性和控制權。