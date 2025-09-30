# Google OAuth 設定指南

## 🚀 快速設定Google登入

### 1. 創建Google Cloud專案

1. 前往 [Google Cloud Console](https://console.cloud.google.com/)
2. 創建新專案或選擇現有專案
3. 啟用 Google+ API

### 2. 設定OAuth同意畫面

1. 在左側選單選擇「APIs & Services」→「OAuth consent screen」
2. 選擇「External」用戶類型
3. 填寫必要資訊：
   - 應用程式名稱：康寧大學聊天系統
   - 用戶支援電子郵件：你的郵箱
   - 開發人員聯絡資訊：你的郵箱

### 3. 創建OAuth 2.0憑證

1. 在左側選單選擇「APIs & Services」→「Credentials」
2. 點擊「Create Credentials」→「OAuth 2.0 Client IDs」
3. 應用程式類型選擇「Web application」
4. 名稱：康寧大學聊天系統
5. 授權的重新導向URI：
   ```
   http://localhost:5000/auth/google/callback
   ```

### 4. 設定環境變數

創建 `backend/.env` 檔案：

```env
# Google OAuth 配置
GOOGLE_CLIENT_ID=你的Google_Client_ID
GOOGLE_CLIENT_SECRET=你的Google_Client_Secret
GOOGLE_REDIRECT_URI=http://localhost:5000/auth/google/callback

# Gmail 郵件配置
GMAIL_SENDER_EMAIL=your-email@gmail.com
GMAIL_APP_PASSWORD=your-app-password
GMAIL_SENDER_NAME=康寧大學聊天系統

# 網站基礎URL
BASE_URL=http://localhost
```

### 5. 安裝python-dotenv

```bash
pip install python-dotenv
```

### 6. 修改app.py載入環境變數

在 `backend/app.py` 開頭添加：

```python
from dotenv import load_dotenv
load_dotenv()
```

### 7. 測試Google登入

1. 重新啟動後端服務
2. 點擊「使用 Google 登入」按鈕
3. 應該會跳轉到Google授權頁面
4. 選擇Google帳號後會自動註冊/登入

## 🔧 故障排除

### 常見問題

**1. 400錯誤：無效的redirect_uri**
- 檢查Google Console中的重定向URI設定
- 確保URI完全匹配：`http://localhost:5000/auth/google/callback`

**2. 403錯誤：存取被拒絕**
- 檢查OAuth同意畫面設定
- 確保應用程式已發布或添加測試用戶

**3. 登入後沒有跳轉**
- 檢查後端日誌是否有錯誤
- 確認資料庫連接正常

### 測試步驟

1. 檢查環境變數是否正確載入
2. 測試Google OAuth URL是否可訪問
3. 檢查資料庫用戶表結構
4. 查看後端日誌輸出

## 📝 注意事項

- 開發環境使用 `localhost:5000`
- 生產環境需要更新重定向URI
- 定期更新OAuth憑證
- 保護Client Secret安全
