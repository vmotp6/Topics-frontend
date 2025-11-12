# 資料庫第三正規化（3NF）文檔

## 概述

本文檔說明如何將資料庫正規化到第三正規化（3NF）的過程和結構。

## 正規化目標

第三正規化（3NF）要求：
1. **第一正規化（1NF）**：消除重複列，每個欄位都是原子值
2. **第二正規化（2NF）**：非主鍵欄位完全依賴於主鍵
3. **第三正規化（3NF）**：消除傳遞依賴，非主鍵欄位不能依賴於其他非主鍵欄位

## 主要改進

### 1. 就讀意願申請表（enrollment_applications）

#### 問題識別
- **違反 1NF**：有重複的志願欄位（intention1-3, system1-3, department1-3）
- **違反 3NF**：直接存儲科系名稱、學制名稱等，造成重複

#### 解決方案
- 創建 `departments` 表：存放科系資料
- 創建 `education_systems` 表：存放學制資料
- 創建 `enrollment_preferences` 表：存放志願明細（一對多關係）
- 創建 `identities` 表：存放身分別
- 創建 `genders` 表：存放性別
- 創建 `grades` 表：存放年級
- 創建 `application_statuses` 表：存放申請狀態

#### 新表結構

```
enrollment_applications_normalized
├── id (PK)
├── user_id (FK -> user)
├── identity_id (FK -> identities)
├── gender_id (FK -> genders)
├── junior_high_school_id (FK -> schools)
├── current_grade_id (FK -> grades)
├── status_id (FK -> application_statuses)
└── ... (其他欄位)

enrollment_preferences
├── id (PK)
├── enrollment_application_id (FK -> enrollment_applications_normalized)
├── preference_order (1, 2, 3)
├── department_id (FK -> departments)
└── education_system_id (FK -> education_systems)
```

### 2. 產學合作申請表（cooperation_applications）

#### 問題識別
- **違反 3NF**：公司資訊直接存在申請表中，造成重複
- **違反 1NF**：智慧財產權有多個欄位（patent, trademark, copyright, trade_secret）
- **違反 1NF**：申請類別是 TEXT 欄位，包含多個值

#### 解決方案
- 創建 `companies` 表：獨立存放公司資訊
- 創建 `ip_rights` 表：存放智慧財產權明細
- 創建 `cooperation_application_categories` 表：存放申請類別明細

#### 新表結構

```
cooperation_applications_normalized
├── id (PK)
├── teacher_id (FK -> teacher)
├── department_id (FK -> departments)
├── company_id (FK -> companies)
├── status_id (FK -> application_statuses)
└── ... (其他欄位)

companies
├── id (PK)
├── name
├── contact_person
└── phone

ip_rights
├── id (PK)
├── cooperation_application_id (FK)
├── ip_type (patent, trademark, copyright, trade_secret)
└── ... (比例欄位)

cooperation_application_categories
├── id (PK)
├── cooperation_application_id (FK)
└── category_code, category_name
```

## 資料遷移

### 執行順序

1. **創建新表結構**
   ```sql
   SOURCE scripts/database/normalize_to_3nf.sql;
   ```

2. **驗證資料遷移**
   執行腳本中的驗證查詢，確認資料正確遷移

3. **測試應用程式**
   使用視圖（`enrollment_applications_view`、`cooperation_applications_view`）測試應用程式是否正常運作

4. **回滾（如需要）**
   ```sql
   SOURCE scripts/database/normalize_to_3nf_rollback.sql;
   ```

## 向後兼容性

為了保持向後兼容，創建了以下視圖：

### enrollment_applications_view
提供與舊 `enrollment_applications` 表相同的欄位結構，應用程式可以無縫切換。

### cooperation_applications_view
提供與舊 `cooperation_applications` 表相同的欄位結構。

## 優點

1. **消除資料冗餘**：科系、學制、狀態等資訊只存儲一次
2. **資料一致性**：通過外鍵約束保證資料完整性
3. **易於維護**：修改科系名稱只需更新一個地方
4. **擴展性**：新增科系、學制等只需在對應表中添加記錄
5. **查詢效率**：通過索引和外鍵關係提高查詢效率

## 注意事項

1. **備份資料**：執行遷移前務必備份資料庫
2. **測試環境**：先在測試環境執行，確認無誤後再在生產環境執行
3. **應用程式更新**：逐步更新應用程式代碼以使用新表結構
4. **監控**：遷移後監控應用程式運行狀況，確保無異常

## 表關聯圖

```
user
  └── enrollment_applications_normalized
        ├── identities
        ├── genders
        ├── schools
        ├── grades
        ├── application_statuses
        └── enrollment_preferences
              ├── departments
              └── education_systems

teacher
  └── cooperation_applications_normalized
        ├── departments
        ├── companies
        ├── application_statuses
        ├── cooperation_application_categories
        └── ip_rights
```

## 後續建議

1. **更新應用程式代碼**：直接使用正規化後的表結構
2. **移除舊表**：確認遷移成功後，可以將舊表重命名為 `*_backup`
3. **性能優化**：根據實際使用情況調整索引策略
4. **文檔更新**：更新 API 文檔和開發文檔

