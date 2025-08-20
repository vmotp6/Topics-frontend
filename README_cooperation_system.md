# 產學合作案表單系統使用說明

## 系統概述

這是一個完整的產學合作案申請表單系統，允許老師上傳申請表，行政人員進行審核管理。

## 功能特色

### 老師功能
- 📝 **申請表上傳**: 填寫詳細的產學合作案申請資訊
- 📋 **申請狀態查詢**: 查看所有申請的審核狀態
- 📊 **統計資訊**: 查看待審核、已通過、已拒絕的申請數量
- 📄 **檔案管理**: 上傳PDF格式的申請表檔案

### 行政人員功能
- 🔍 **申請列表管理**: 查看所有老師的申請表
- 📊 **篩選功能**: 依狀態、科系、日期篩選申請
- ✅ **審核功能**: 通過或拒絕申請，並可添加審核意見
- 📋 **詳細檢視**: 查看申請表的完整資訊

## 系統架構

### 資料庫表結構
```sql
cooperation_applications (
    id,                    -- 申請編號
    teacher_username,      -- 老師帳號
    teacher_name,          -- 老師姓名
    department,            -- 科系
    project_title,         -- 專案名稱
    project_description,   -- 專案描述
    company_name,          -- 企業名稱
    company_contact,       -- 企業聯絡人
    company_phone,         -- 企業電話
    company_email,         -- 企業信箱
    project_start_date,    -- 專案開始日期
    project_end_date,      -- 專案結束日期
    budget_amount,         -- 預算金額
    expected_outcomes,     -- 預期成果
    application_file_path, -- 申請表檔案路徑
    status,                -- 審核狀態 (pending/approved/rejected)
    admin_username,        -- 審核人員帳號
    admin_comment,         -- 審核意見
    review_date,           -- 審核日期
    created_at,            -- 建立時間
    updated_at             -- 更新時間
)
```

### 檔案結構
```
frontend/
├── cooperation_upload.php              # 老師上傳申請表頁面
├── teacher_cooperation_status.php      # 老師查看申請狀態頁面
├── admin_cooperation_review.php        # 行政人員審核頁面
└── teacher.php                         # 老師主頁面 (已更新)

backend/
├── cooperation_upload_api.php          # 處理申請表上傳
├── cooperation_list_api.php            # 獲取申請表列表
├── cooperation_detail_api.php          # 獲取申請表詳細資料
├── cooperation_review_api.php          # 處理審核結果
├── cooperation_teacher_list_api.php    # 老師專用申請列表
├── create_cooperation_table.sql        # 資料表建立SQL
└── setup_cooperation_table.php         # 資料表設定腳本

uploads/
└── cooperation/                        # 申請表檔案儲存目錄
```

## 安裝與設定

### 1. 建立資料庫表
執行以下命令建立資料表：
```bash
cd backend
php setup_cooperation_table.php
```

### 2. 設定檔案權限
確保上傳目錄具有寫入權限：
```bash
chmod 755 uploads/cooperation/
```

### 3. 檢查資料庫連線
確認 `backend/` 目錄下的所有API檔案中的資料庫連線設定正確。

## 使用流程

### 老師使用流程
1. **登入系統**: 使用老師帳號登入
2. **上傳申請表**: 
   - 點擊「📝 上傳申請表」
   - 填寫申請人資訊、專案資訊、企業資訊
   - 上傳PDF格式的申請表檔案
   - 提交申請
3. **查看申請狀態**:
   - 點擊「📋 查看申請狀態」
   - 查看統計資訊和申請列表
   - 點擊「查看」按鈕查看詳細資料

### 行政人員使用流程
1. **登入系統**: 使用行政人員帳號登入
2. **進入審核頁面**: 直接訪問 `admin_cooperation_review.php`
3. **篩選申請**: 使用狀態、科系、日期篩選功能
4. **審核申請**:
   - 點擊「查看」按鈕查看詳細資料
   - 填寫審核意見（選填）
   - 點擊「通過」或「拒絕」按鈕

## API 端點說明

### 申請表上傳
- **URL**: `backend/cooperation_upload_api.php`
- **方法**: POST
- **功能**: 處理申請表上傳和資料儲存

### 申請表列表 (行政人員)
- **URL**: `backend/cooperation_list_api.php`
- **方法**: GET
- **參數**: `status`, `department`, `date` (選填)

### 申請表列表 (老師)
- **URL**: `backend/cooperation_teacher_list_api.php`
- **方法**: GET
- **參數**: `teacher_username`

### 申請表詳細資料
- **URL**: `backend/cooperation_detail_api.php`
- **方法**: GET
- **參數**: `id`

### 審核處理
- **URL**: `backend/cooperation_review_api.php`
- **方法**: POST
- **參數**: `application_id`, `status`, `comment`, `admin_username`

## 安全性考量

1. **身份驗證**: 所有頁面都檢查用戶登入狀態和角色權限
2. **檔案上傳**: 限制檔案類型為PDF，大小限制10MB
3. **SQL注入防護**: 使用參數化查詢
4. **XSS防護**: 輸出時進行適當的轉義

## 注意事項

1. **檔案上傳**: 確保伺服器有足夠的儲存空間
2. **資料庫備份**: 定期備份 `cooperation_applications` 資料表
3. **權限管理**: 只有老師可以上傳申請，只有行政人員可以審核
4. **檔案清理**: 定期清理過期的申請表檔案

## 故障排除

### 常見問題
1. **檔案上傳失敗**: 檢查上傳目錄權限和磁碟空間
2. **資料庫連線錯誤**: 確認資料庫連線設定
3. **頁面無法載入**: 檢查PHP錯誤日誌

### 錯誤代碼
- 400: 請求參數錯誤
- 401: 未授權存取
- 404: 資源不存在
- 500: 伺服器內部錯誤

## 更新日誌

### v1.0.0 (2024-01-XX)
- 初始版本發布
- 支援基本的申請表上傳和審核功能
- 完整的用戶介面和API設計
