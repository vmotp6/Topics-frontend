# 🔐 Google登入 + 📧 郵件通知系統

## 🎯 功能概述

本系統提供完整的Google登入功能和私訊郵件通知服務：

- ✅ **Google OAuth 登入** - 支援Google帳號登入
- ✅ **用戶資料管理** - 自動創建和更新用戶資料
- ✅ **私訊郵件通知** - 新私訊自動發送Gmail通知
- ✅ **美觀郵件模板** - HTML格式的郵件通知
- ✅ **安全配置管理** - 分離敏感配置檔案

## 📁 檔案結構

```
backend/
├── app.py                                    # Flask後端主應用
├── services/
│   └── email_notification.php               # 郵件通知服務
├── config/
│   ├── email_config.php                     # 郵件配置檔案
│   └── email_config_local.php.example       # 配置範例
├── fix_database_for_google.py               # 資料庫修復腳本
└── test_system.py                           # 系統測試腳本

frontend/chat/
└── save_private_message.php                 # 私訊儲存（含郵件通知）
```

## 🚀 快速開始

### 1. 修復資料庫結構

```bash
cd backend
python fix_database_for_google.py
```

### 2. 設定Gmail配置

```bash
# 複製配置範例
cp backend/config/email_config_local.php.example backend/config/email_config_local.php

# 編輯配置檔案，填入你的Gmail設定
```

### 3. 設定Google OAuth

在 `backend/app.py` 中設定：
```python
GOOGLE_CLIENT_ID = 'your-google-client-id'
GOOGLE_CLIENT_SECRET = 'your-google-client-secret'
GOOGLE_REDIRECT_URI = 'http://localhost:5000/auth/google/callback'
```

### 4. 啟動後端服務

```bash
cd backend
python app.py
```

### 5. 測試系統

```bash
python test_system.py
```

## 📧 Gmail設定步驟

### 1. 啟用2步驟驗證
1. 登入Google帳戶
2. 前往「安全性」設定
3. 啟用「2步驟驗證」

### 2. 生成應用程式密碼
1. 在「2步驟驗證」頁面底部
2. 點擊「應用程式密碼」
3. 選擇「郵件」和您的裝置
4. 複製16位元密碼

### 3. 配置郵件設定
編輯 `backend/config/email_config_local.php`：
```php
$email_config = array_merge($email_config, [
    'smtp_username' => 'your-email@gmail.com',
    'smtp_password' => 'your-16-digit-app-password',
    'sender_email' => 'your-email@gmail.com',
    'sender_name' => '康寧大學聊天系統',
    'base_url' => 'http://100.79.58.120',
    'enable_notifications' => true,
]);
```

## 🔧 API端點

### Google登入
- `GET /auth/google` - 開始Google登入流程
- `GET /auth/google/callback` - Google登入回調處理

### 用戶管理
- `GET /user/profile?username=xxx` - 獲取用戶資料
- `POST /user/select-role` - Gmail用戶選擇角色

### 系統監控
- `GET /health` - 健康檢查

## 📧 郵件通知功能

### 自動觸發
當用戶發送私訊時，系統會：
1. 儲存訊息到資料庫
2. 查詢接收者郵箱
3. 發送Gmail通知
4. 記錄發送狀態

### 郵件內容
- 發送者姓名
- 訊息內容（前200字）
- 發送時間
- 直接回覆連結
- 美觀的HTML格式

## 🐛 疑難排解

### 常見問題

**Q: Google登入失敗**
- 檢查Google OAuth設定
- 確認重定向URI正確
- 檢查Client ID和Secret

**Q: 郵件發送失敗**
- 檢查Gmail應用程式密碼
- 確認2步驟驗證已啟用
- 檢查SMTP設定

**Q: 資料庫錯誤**
- 執行資料庫修復腳本
- 檢查用戶表結構
- 確認欄位完整性

### 檢查日誌

```bash
# 查看PHP錯誤日誌
tail -f /var/log/apache2/error.log

# 查看Flask應用日誌
# 在終端中查看python app.py的輸出
```

## 🔒 安全注意事項

1. **保護敏感資訊**：
   - 將 `email_config_local.php` 加入 `.gitignore`
   - 不要提交Google OAuth密鑰
   - 使用環境變數管理敏感配置

2. **權限設定**：
   - 設定適當的檔案權限
   - 限制配置檔案讀取權限

3. **監控使用**：
   - 定期檢查郵件發送日誌
   - 監控異常登入活動

## 📊 系統監控

### 檢查用戶資料
```sql
SELECT username, name, email, google_id, role 
FROM user 
WHERE google_id IS NOT NULL;
```

### 檢查私訊記錄
```sql
SELECT from_user, to_user, message, timestamp 
FROM private_chat_history 
ORDER BY timestamp DESC 
LIMIT 10;
```

## 🚀 進階功能

### 自訂郵件模板
修改 `backend/services/email_notification.php` 中的模板方法

### 批量郵件設定
- 使用郵件佇列系統
- 設定發送頻率限制
- 監控郵件發送狀態

### 多語言支援
- 根據用戶語言偏好選擇模板
- 使用國際化函數
- 支援不同時區

## 📞 技術支援

如果遇到問題：
1. 檢查錯誤日誌
2. 執行測試腳本
3. 確認配置設定
4. 檢查網路連接

---

## 📝 更新日誌

- **2025-01-27**: 初始版本發布
  - ✅ Google OAuth 登入功能
  - ✅ 私訊郵件通知系統
  - ✅ 資料庫結構修復
  - ✅ 配置檔案管理
  - ✅ 系統測試腳本

---

© 2025 康寧大學聊天系統
