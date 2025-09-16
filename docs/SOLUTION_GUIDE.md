# 康寧大學產學合作申請表提交失敗 - 完整解決方案

## 🚨 問題診斷

根據開發者工具截圖，問題的根本原因是：
1. **404 錯誤**：`backend/cooperation_upload_api.php` 檔案找不到
2. **JSON 解析錯誤**：因為收到 HTML 錯誤頁面而不是 JSON 回應

## 🔧 解決方案

### 步驟 1：執行快速修復
```
訪問：http://your-domain/backend/quick_fix.php
```
這個腳本會：
- ✅ 檢查資料庫連線
- ✅ 重建資料表結構
- ✅ 建立上傳目錄
- ✅ 設定適當權限
- ✅ 測試基本功能

### 步驟 2：測試 API 存取
```
訪問：http://your-domain/backend/test_api_access.php
```
這個頁面會：
- ✅ 檢查所有必要檔案是否存在
- ✅ 測試 API 回應
- ✅ 顯示伺服器資訊
- ✅ 提供路徑測試

### 步驟 3：使用測試頁面
```
訪問：http://your-domain/frontend/test_cooperation_upload.php
```
這個簡化的測試頁面會：
- ✅ 顯示詳細的錯誤訊息
- ✅ 記錄所有請求資訊
- ✅ 提供調試資料

### 步驟 4：如果仍有問題

#### 選項 A：使用新的簡化 API
我已經創建了 `simple_cooperation_api.php`，前端已經更新為使用這個 API。

#### 選項 B：檢查路徑問題
如果路徑問題持續，請嘗試以下路徑：
- `backend/simple_cooperation_api.php`
- `/backend/simple_cooperation_api.php`
- `../backend/simple_cooperation_api.php`

#### 選項 C：手動修復
1. 確保資料庫連線正常
2. 執行資料表重建
3. 檢查檔案權限
4. 驗證 PHP 設定

## 📋 我創建的修復工具

### 診斷工具
1. **`test_api_access.php`** - API 存取測試
2. **`debug_cooperation_submission.php`** - 詳細調試工具
3. **`diagnose_cooperation_system.php`** - 系統狀態檢查

### 修復工具
1. **`quick_fix.php`** - 快速修復腳本
2. **`fix_cooperation_system.php`** - 完整修復工具
3. **`simple_cooperation_api.php`** - 簡化的 API

### 測試工具
1. **`test_cooperation_upload.php`** - 簡化的測試頁面
2. **`test_cooperation_api.php`** - 改進的測試 API

## 🎯 預期結果

執行這些步驟後，您應該能夠：
- ✅ 成功提交產學合作申請表
- ✅ 看到成功訊息而不是錯誤
- ✅ 檔案正常上傳到伺服器
- ✅ 資料正確儲存到資料庫

## 📞 如果問題持續

請提供以下資訊：
1. 快速修復腳本的執行結果
2. API 存取測試的結果
3. 測試頁面顯示的錯誤訊息
4. 瀏覽器開發者工具中的網路請求詳情
5. 任何 PHP 錯誤日誌

## 🔍 常見問題

### Q: 為什麼會出現 404 錯誤？
A: 通常是路徑問題或伺服器設定問題。使用 `test_api_access.php` 來診斷。

### Q: 如何檢查資料庫連線？
A: 執行 `quick_fix.php` 或 `diagnose_cooperation_system.php`。

### Q: 檔案上傳失敗怎麼辦？
A: 檢查上傳目錄權限和 PHP 設定，使用 `debug_cooperation_submission.php` 診斷。

### Q: JSON 解析錯誤如何解決？
A: 這通常是因為 API 返回 HTML 錯誤頁面而不是 JSON。檢查 API 檔案是否存在且可存取。

## 📝 更新日誌

- **2024-01-XX**：識別 404 錯誤問題
- **2024-01-XX**：創建簡化 API 解決方案
- **2024-01-XX**：更新前端使用新 API
- **2024-01-XX**：創建完整的診斷和修復工具集
