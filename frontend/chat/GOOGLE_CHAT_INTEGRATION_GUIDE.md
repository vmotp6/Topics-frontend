# Google 登入聊天系統整合指南

## 概述
本指南說明如何在私訊聊天系統中應用 Google 登入功能，提供無縫的用戶體驗。

## 系統架構

### 1. 登入流程
```
用戶點擊 Google 登入 
    ↓
跳轉到 Google 授權頁面
    ↓
用戶授權後回調到後端
    ↓
後端處理用戶資料並重定向
    ↓
前端接收回調並設定 Session
    ↓
重定向到聊天系統
```

### 2. 檔案結構
```
frontend/chat/
├── google_chat_integration.php    # Google 登入頁面
├── chat.php                      # 主要聊天系統
├── test_google_chat.php          # 測試頁面
└── GOOGLE_CHAT_INTEGRATION_GUIDE.md  # 本指南
```

## 功能特色

### ✅ 已實現功能
- **Google OAuth 2.0 登入**：使用 Google 帳號安全登入
- **Session 管理**：24小時 session 生命週期
- **角色權限**：支援學生、老師、管理員角色
- **自動重定向**：登入後自動進入聊天系統
- **安全驗證**：完整的登入狀態檢查

### 🎯 聊天功能
- **私聊功能**：學生與老師一對一聊天
- **群組聊天**：老師可創建群組
- **即時訊息**：每3秒自動更新
- **現代化界面**：響應式設計

## 使用步驟

### 1. 啟動系統
```bash
# 啟動後端服務
cd backend
python app.py

# 啟動前端服務
cd frontend
php -S localhost:8000
```

### 2. 測試 Google 登入
1. 訪問：`http://localhost:8000/chat/test_google_chat.php`
2. 點擊「Google 登入測試」
3. 完成 Google 授權
4. 確認登入狀態
5. 進入聊天系統

### 3. 正常使用流程
1. 訪問：`http://localhost:8000/`
2. 點擊「私訊聊天室」
3. 使用 Google 帳號登入
4. 開始聊天

## 技術細節

### 1. Session 配置
```php
// session_config.php
ini_set('session.cookie_lifetime', 86400); // 24小時
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_httponly', 1);     // 防止XSS
ini_set('session.use_strict_mode', 1);     // 嚴格模式
```

### 2. 登入檢查
```php
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);
```

### 3. Google 回調處理
```php
if (isset($_GET['google_login']) && $_GET['google_login'] === 'success') {
    $_SESSION['logged_in'] = true;
    $_SESSION['username'] = $_GET['username'];
    $_SESSION['role'] = $_GET['role'];
    $_SESSION['login_method'] = 'google';
    header("Location: chat.php");
}
```

## 配置要求

### 1. 後端配置
- Python Flask 應用運行在 `localhost:5000`
- Google OAuth 2.0 設定正確
- 資料庫連接正常

### 2. 前端配置
- PHP 7.4+ 支援
- Session 功能啟用
- 正確的檔案權限

### 3. Google OAuth 設定
- 客戶端 ID 和密鑰設定
- 重定向 URI 設定為 `http://localhost:5000/auth/google/callback`
- 授權域名設定

## 故障排除

### 常見問題

#### 1. 登入後自動登出
**原因**：Session 配置問題
**解決**：檢查 `session_config.php` 設定

#### 2. Google 授權失敗
**原因**：OAuth 設定錯誤
**解決**：檢查 Google Console 設定

#### 3. 重定向錯誤
**原因**：URL 路徑錯誤
**解決**：檢查後端重定向 URL

#### 4. 聊天功能無法使用
**原因**：資料庫連接問題
**解決**：檢查資料庫設定和權限

### 調試方法

#### 1. 檢查 Session 狀態
```php
// 在任意頁面加入
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
```

#### 2. 檢查登入狀態
訪問：`http://localhost:8000/chat/test_google_chat.php`

#### 3. 檢查後端日誌
查看後端控制台輸出的錯誤訊息

## 安全注意事項

### 1. Session 安全
- 使用 HTTPS（生產環境）
- 設定適當的 Session 過期時間
- 防止 Session 固定攻擊

### 2. OAuth 安全
- 驗證 state 參數
- 檢查授權範圍
- 處理錯誤情況

### 3. 資料保護
- 加密敏感資料
- 驗證用戶輸入
- 防止 SQL 注入

## 進階功能

### 1. 自動登入
- 記住用戶選擇
- 自動重定向到聊天系統

### 2. 角色管理
- 根據角色顯示不同功能
- 權限控制

### 3. 用戶體驗
- 載入動畫
- 錯誤提示
- 響應式設計

## 測試清單

### ✅ 基本功能測試
- [ ] Google 登入按鈕顯示
- [ ] Google 授權流程
- [ ] Session 設定正確
- [ ] 重定向到聊天系統
- [ ] 聊天功能正常

### ✅ 安全測試
- [ ] Session 過期處理
- [ ] 未授權訪問阻擋
- [ ] 登出功能正常
- [ ] 資料驗證

### ✅ 用戶體驗測試
- [ ] 載入速度
- [ ] 錯誤提示
- [ ] 響應式設計
- [ ] 跨瀏覽器相容性

## 部署建議

### 1. 生產環境設定
- 使用 HTTPS
- 設定正確的域名
- 更新 Google OAuth 設定
- 設定適當的 Session 參數

### 2. 監控和日誌
- 記錄登入活動
- 監控錯誤率
- 設定警報

### 3. 備份和恢復
- 定期備份資料庫
- 測試恢復流程
- 設定災難恢復計劃

## 支援和維護

### 1. 定期檢查
- 檢查 Google OAuth 設定
- 更新依賴套件
- 監控系統性能

### 2. 用戶支援
- 提供使用說明
- 收集用戶反饋
- 持續改進功能

### 3. 技術支援
- 文檔更新
- 問題追蹤
- 版本控制

---

**最後更新**：2024年12月
**版本**：1.0.0
**維護者**：康寧大學開發團隊






