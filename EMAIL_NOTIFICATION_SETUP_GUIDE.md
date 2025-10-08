# 郵件通知系統設定指南

## 概述
本指南將幫助您設定康寧大學招生推薦系統的郵件通知功能，包括審核通過通知和入學確認通知。

## 功能特色

### 📧 自動郵件通知
- **審核通過通知**：當推薦狀態更新為「已報名」時，自動發送通知給被推薦學生
- **入學確認通知**：當入學狀態更新為「已入學」時，自動發送通知給被推薦學生
- **美觀的HTML郵件模板**：使用響應式設計，支援各種郵件客戶端

### 🎨 郵件模板特色
- 現代化的設計風格
- 康寧大學品牌色彩
- 響應式布局
- 清晰的資訊結構
- 自動變數替換

## 設定步驟

### 1. 資料庫設定
首先需要創建通知日誌表：

```sql
-- 執行以下SQL腳本
source scripts/database/create_notification_logs_table.sql
```

### 2. SMTP 郵件設定
確保 `frontend/config.php` 中的SMTP設定正確：

```php
// SMTP 郵件設定
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('SMTP_FROM_NAME', '康寧大學五專入學說明會');
define('SMTP_SECURE', 'tls');
```

### 3. Gmail 應用程式密碼設定
1. 前往 [Google 帳戶設定](https://myaccount.google.com/)
2. 選擇「安全性」
3. 啟用「兩步驟驗證」
4. 生成「應用程式密碼」
5. 將應用程式密碼設定到 `SMTP_PASSWORD`

## 使用方式

### 1. 管理員操作
1. 登入系統（需要管理員權限）
2. 前往「推薦報名」頁面
3. 搜尋推薦記錄
4. 使用操作按鈕更新狀態：
   - ✅ **審核通過**：將狀態更新為「已報名」，發送審核通過通知
   - ❌ **審核拒絕**：將狀態更新為「已拒絕」
   - 🎓 **確認入學**：將入學狀態更新為「已入學」，發送入學確認通知

### 2. 自動通知流程
```
推薦申請提交 → 管理員審核 → 狀態更新 → 自動發送郵件通知
```

## 郵件模板

### 審核通過通知
- **觸發條件**：推薦狀態從其他狀態更新為「已報名」
- **收件人**：被推薦學生的電子郵件
- **內容**：包含推薦人資訊、審核通過時間、後續步驟等

### 入學確認通知
- **觸發條件**：入學狀態更新為「已入學」
- **收件人**：被推薦學生的電子郵件
- **內容**：包含入學確認資訊、重要提醒事項等

## 測試功能

### 1. 郵件通知測試
訪問 `frontend/test_email_notification.php` 進行測試：
- 輸入測試郵件地址
- 選擇通知類型
- 發送測試郵件
- 檢查收件匣

### 2. 功能測試步驟
1. 提交測試推薦申請
2. 使用管理員帳號登入
3. 搜尋推薦記錄
4. 更新狀態並觀察通知

## 故障排除

### 常見問題

#### 1. 郵件發送失敗
**可能原因：**
- SMTP 設定錯誤
- Gmail 應用程式密碼未設定
- 網路連線問題
- 收件人郵件地址無效

**解決方法：**
- 檢查 SMTP 設定
- 確認 Gmail 應用程式密碼
- 測試網路連線
- 驗證郵件地址格式

#### 2. 郵件進入垃圾郵件匣
**解決方法：**
- 設定 SPF 記錄
- 使用專用郵件伺服器
- 避免使用敏感詞彙
- 建議收件人將發件人加入白名單

#### 3. 郵件格式顯示異常
**可能原因：**
- 郵件客戶端不支援HTML
- CSS 樣式被過濾
- 字體不支援

**解決方法：**
- 使用純文字版本
- 簡化CSS樣式
- 使用網頁字體

### 錯誤日誌
系統會自動記錄郵件發送日誌到 `notification_logs` 表：
- 發送成功/失敗狀態
- 發送時間
- 錯誤訊息（如有）

## 安全考量

### 1. 郵件安全
- 使用 TLS 加密連線
- 定期更換應用程式密碼
- 限制發送頻率
- 監控異常發送

### 2. 資料保護
- 不記錄郵件內容
- 只記錄發送狀態
- 定期清理舊日誌
- 遵守個資法規定

## 進階設定

### 1. 自訂郵件模板
可以修改 `frontend/config/email_notification_config.php` 中的模板：

```php
// 自訂郵件模板
function getEmailTemplate($template_name, $data = []) {
    // 修改模板內容
}
```

### 2. 添加更多通知類型
在 `$email_templates` 陣列中添加新的通知類型：

```php
$email_templates = [
    'new_notification' => [
        'subject' => '新通知主題',
        'template' => 'new_template'
    ]
];
```

### 3. 設定發送條件
可以修改 `api/update_recommendation_status.php` 中的發送邏輯：

```php
// 自訂發送條件
if ($new_status === 'custom_status') {
    // 發送自訂通知
}
```

## 監控與維護

### 1. 定期檢查
- 檢查郵件發送成功率
- 監控SMTP配額使用情況
- 查看錯誤日誌
- 測試郵件功能

### 2. 效能優化
- 使用郵件佇列（如Redis）
- 批量發送郵件
- 快取郵件模板
- 優化資料庫查詢

## 支援

如有問題，請參考：
- [PHPMailer 文件](https://github.com/PHPMailer/PHPMailer)
- [Gmail SMTP 設定](https://support.google.com/mail/answer/7126229)
- [HTML 郵件最佳實踐](https://www.campaignmonitor.com/dev-resources/guides/coding-html-emails/)

## 更新日誌

### v1.0.0 (2024-01-XX)
- 初始版本發布
- 支援審核通過通知
- 支援入學確認通知
- 基本的錯誤處理和日誌記錄
