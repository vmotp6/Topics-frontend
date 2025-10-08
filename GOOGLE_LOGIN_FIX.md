# Google 登入問題修正說明

## 問題描述
Google 登入成功後跳轉回首頁時又自動登出，無法維持登入狀態。

## 問題原因分析

### 1. 主要問題
- `frontend/index.php` 缺少 Google 登入回調處理邏輯
- `frontend/admin_admission.php` 權限檢查使用錯誤的 session 變數名稱
- Session 配置不完整，可能導致 session 過期或丟失

### 2. 次要問題
- CORS 設定不完整
- Session 生命週期設定不當
- 缺少 session 安全設定

## 修正內容

### 1. 新增 Session 配置檔案
**檔案**: `frontend/session_config.php`
- 設定 session 生命週期為 24 小時
- 啟用 session 安全設定
- 防止 session 固定攻擊
- 自動處理 session 過期

### 2. 修正 index.php
**檔案**: `frontend/index.php`
- 新增 Google 登入回調處理邏輯
- 載入 session 配置檔案
- 正確設定 session 變數

### 3. 修正 admin_admission.php
**檔案**: `frontend/admin_admission.php`
- 修正權限檢查邏輯
- 從 `$_SESSION['user_type']` 改為 `$_SESSION['role']`
- 從 `'admin'` 改為 `'管理員'`

### 4. 更新後端 CORS 設定
**檔案**: `backend/app.py`
- 啟用 credentials 支援
- 設定正確的 origins
- 新增重定向 URL 的調試輸出

### 5. 新增調試工具
**檔案**: `frontend/debug_session.php`
- Session 狀態檢查
- 詳細的 session 資訊顯示
- 調試功能

**檔案**: `frontend/test_google_login.php`
- Google 登入測試頁面
- Session 檢查功能
- 測試操作按鈕

## 修正後的登入流程

1. 用戶點擊 Google 登入
2. 跳轉到 Google 授權頁面
3. 用戶授權後，Google 回調到後端
4. 後端處理用戶資料並重定向到前端
5. 前端接收回調參數並設定 session
6. 重定向到相應頁面（避免 URL 參數顯示）
7. 頁面載入時檢查 session 狀態

## 測試方法

### 1. 基本測試
1. 訪問 `http://localhost/Topics-frontend/frontend/test_google_login.php`
2. 點擊「測試 Google 登入」
3. 完成 Google 授權
4. 檢查是否成功登入並維持狀態

### 2. 調試測試
1. 訪問 `http://localhost/Topics-frontend/frontend/debug_session.php`
2. 查看 session 狀態和資料
3. 使用 `?debug_session=1` 參數查看詳細資訊

### 3. 功能測試
1. 登入後訪問不同頁面
2. 檢查是否維持登入狀態
3. 測試登出功能

## 注意事項

1. **Session 安全**: 生產環境需要設定 `session.cookie_secure = 1`
2. **CORS 設定**: 需要根據實際域名調整 origins
3. **資料庫權限**: 確保用戶角色設定正確
4. **Google OAuth**: 確保 redirect URI 設定正確

## 常見問題

### Q: 登入後還是會登出
A: 檢查 session 配置和瀏覽器 cookie 設定

### Q: 權限檢查失敗
A: 確認資料庫中的用戶角色設定正確

### Q: CORS 錯誤
A: 檢查後端 CORS 設定和前端請求 URL

## 相關檔案

- `frontend/session_config.php` - Session 配置
- `frontend/index.php` - 首頁登入處理
- `frontend/admin_admission.php` - 管理員頁面
- `frontend/debug_session.php` - 調試工具
- `frontend/test_google_login.php` - 測試頁面
- `backend/app.py` - 後端 API
