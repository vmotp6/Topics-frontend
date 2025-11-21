# 國中學校招生申請表相關檔案清單

## 📋 資料庫相關檔案

### SQL 腳本
1. **`scripts/database/create_junior_school_recruitment_table.sql`** ⭐
   - 改進版的資料表建立腳本
   - 包含資料驗證觸發器
   - 包含唯一性約束
   - 包含視圖定義

2. **`scripts/database/analyze_junior_school_recruitment.sql`**
   - 資料表分析與改進建議
   - 資料清理查詢
   - 約束添加腳本

### PHP 執行腳本
3. **`scripts/setup/create_junior_school_recruitment_table.php`**
   - 自動執行 SQL 腳本的 PHP 工具
   - 顯示執行結果和表結構
   - 驗證觸發器

## 📄 文檔檔案

4. **`docs/JUNIOR_SCHOOL_RECRUITMENT_ANALYSIS.md`**
   - 資料表分析報告
   - 問題發現與改進建議
   - 資料清理建議
   - 前端表單改進建議

## ⚠️ 缺少的檔案

根據搜尋結果，目前**沒有找到**以下檔案：

### 前端表單
- ❌ 國中學校招生申請表單頁面（如：`junior_school_recruitment_form.php`）
- ❌ 申請表單提交處理頁面

### API 檔案
- ❌ 提交申請的 API（如：`api/submit_junior_school_recruitment.php`）
- ❌ 查詢申請列表的 API
- ❌ 管理申請狀態的 API

### 管理介面
- ❌ 管理員審核申請的頁面
- ❌ 申請列表管理頁面

## 🔍 相關但不同的表

以下檔案是關於**其他申請表**，不是 `junior_school_recruitment_applications`：

- `frontend/cooperation_upload.php` - 就讀意願登錄表單（使用 `enrollment_applications` 表）
- `frontend/continued_admission.php` - 繼續入學申請（使用其他表）
- `frontend/admission.php` - 入學申請（使用 `admission_applications` 表）
- `backend/api/enrollment/enrollment_api.php` - 就讀意願 API

## 📝 建議建立的檔案

### 1. 前端表單
```
frontend/junior_school_recruitment_form.php
```
- 國中學校填寫招生申請的表單
- 包含所有必要欄位
- 表單驗證
- 草稿系統整合

### 2. API 檔案
```
frontend/api/submit_junior_school_recruitment.php
```
- 處理表單提交
- 資料驗證
- 插入資料庫

```
frontend/api/junior_school_recruitment_list_api.php
```
- 查詢申請列表
- 支援篩選和分頁

### 3. 管理介面
```
frontend/admin_junior_school_recruitment.php
```
- 管理員查看所有申請
- 審核申請（批准/拒絕）
- 更新申請狀態
- 添加管理員備註

## 🗂️ 檔案結構建議

```
frontend/
├── junior_school_recruitment_form.php          # 申請表單頁面
├── api/
│   ├── submit_junior_school_recruitment.php   # 提交申請 API
│   ├── junior_school_recruitment_list_api.php # 查詢列表 API
│   └── update_recruitment_status_api.php      # 更新狀態 API
└── admin/
    └── admin_junior_school_recruitment.php    # 管理介面

scripts/
└── database/
    ├── create_junior_school_recruitment_table.sql  # ✅ 已存在
    └── analyze_junior_school_recruitment.sql      # ✅ 已存在

docs/
├── JUNIOR_SCHOOL_RECRUITMENT_ANALYSIS.md      # ✅ 已存在
└── JUNIOR_SCHOOL_RECRUITMENT_FILES.md         # ✅ 本檔案
```

## 📊 資料表欄位對照

| 資料表欄位 | 說明 | 前端表單欄位建議 |
|-----------|------|----------------|
| `school_name` | 學校名稱 | 文字輸入 + 學校搜尋 |
| `city` | 縣市 | 下拉選單 |
| `district` | 區/鄉鎮市 | 下拉選單（依縣市動態載入）|
| `school_address` | 學校地址 | 文字輸入 |
| `contact_name` | 聯絡人姓名 | 文字輸入（必填）|
| `contact_title` | 聯絡人職稱 | 文字輸入 |
| `contact_phone` | 聯絡電話 | 文字輸入（必填，格式驗證）|
| `contact_email` | 聯絡Email | Email 輸入（必填，格式驗證）|
| `preferred_date` | 期望招生日期 | 日期選擇器（必填，不能是過去）|
| `preferred_time` | 期望時間 | 下拉選單（上午/下午/全天）|
| `target_grades` | 目標年級 | 多選 checkbox（二年級/三年級）|
| `expected_students` | 預期參與學生人數 | 數字輸入（必填，>0）|
| `venue_type` | 場地類型 | 下拉選單（禮堂/活動中心/教室）|
| `special_requirements` | 特殊需求 | 文字區域 |
| `remarks` | 備註 | 文字區域 |

## 🚀 下一步行動

1. ✅ **已完成**：建立資料表結構
2. ⏳ **待建立**：前端申請表單
3. ⏳ **待建立**：提交申請 API
4. ⏳ **待建立**：管理員審核介面
5. ⏳ **待建立**：申請列表查詢 API

## 📌 注意事項

- 資料表已存在於資料庫中（從 SQL dump 可見）
- 目前有 4 筆測試資料（id: 1-4）
- 需要建立完整的前後端系統來使用這個資料表
- 建議先清理測試資料再正式使用









