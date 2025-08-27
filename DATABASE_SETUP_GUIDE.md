# 就讀意願登錄資料庫設定指南

## 步驟 1: 登入 phpMyAdmin

1. 開啟瀏覽器，前往：http://100.79.58.120/phpmyadmin/index.php
2. 使用你的資料庫帳號密碼登入

## 步驟 2: 選擇資料庫

1. 在左側選單中選擇 `topics_good` 資料庫
2. 確認你已經選中正確的資料庫

## 步驟 3: 執行 SQL 腳本

### 方法一：使用 SQL 標籤頁

1. 點擊上方的 `SQL` 標籤頁
2. 複製以下 SQL 語句並貼上：

```sql
-- 康寧大學就讀意願登錄資料表建立腳本
USE topics_good;

-- 刪除舊表（如果存在）
DROP TABLE IF EXISTS enrollment_applications;

-- 創建新表
CREATE TABLE enrollment_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    identity ENUM('學生', '家長') NOT NULL,
    gender ENUM('男', '女') NULL,
    phone1 VARCHAR(50) NOT NULL,
    phone2 VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    intention1 VARCHAR(255) DEFAULT '無特定',
    system1 VARCHAR(50) NULL,
    department1 VARCHAR(255) NULL,
    intention2 VARCHAR(255) DEFAULT '無特定',
    system2 VARCHAR(50) NULL,
    department2 VARCHAR(255) NULL,
    intention3 VARCHAR(255) DEFAULT '無特定',
    system3 VARCHAR(50) NULL,
    department3 VARCHAR(255) NULL,
    junior_high VARCHAR(255) NULL,
    current_grade VARCHAR(50) NULL,
    line_id VARCHAR(255) NULL,
    facebook VARCHAR(255) NULL,
    remarks TEXT NULL,
    status ENUM('pending', 'contacted', 'enrolled') DEFAULT 'pending',
    admin_comment TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_identity (identity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

3. 點擊 `執行` 按鈕

### 方法二：使用匯入功能

1. 點擊上方的 `匯入` 標籤頁
2. 選擇檔案 `backend/simple_enrollment_setup.sql`
3. 點擊 `執行`

## 步驟 4: 驗證資料表建立成功

1. 在左側選單中應該能看到新的 `enrollment_applications` 資料表
2. 點擊資料表名稱查看結構
3. 確認所有欄位都已正確建立

## 步驟 5: 插入測試資料（可選）

如果你想要插入一些測試資料，可以執行以下 SQL：

```sql
-- 插入測試資料
INSERT INTO enrollment_applications (username, name, identity, gender, phone1, email, intention1, system1, department1, junior_high, current_grade, status) VALUES
('test_student1', '張小明', '學生', '男', '0912345678', 'test1@example.com', '資訊工程學系', '大學部', '資訊工程學系', '中正國中', '國三', 'pending'),
('test_parent1', '李媽媽', '家長', '女', '0923456789', 'test2@example.com', '企業管理學系', '大學部', '企業管理學系', '建國國中', '國二', 'contacted'),
('test_student2', '王小華', '學生', '女', '0934567890', 'test3@example.com', '外國語文學系', '大學部', '外國語文學系', '復興國中', '國三', 'enrolled');
```

## 資料表結構說明

### 主要欄位：
- `id`: 自動遞增的主鍵
- `username`: 用戶帳號
- `name`: 姓名
- `identity`: 身分別（學生/家長）
- `gender`: 性別（男/女）
- `phone1`: 主要聯絡電話（必填）
- `phone2`: 次要聯絡電話
- `email`: 電子郵件
- `intention1/2/3`: 三個就讀意願
- `system1/2/3`: 對應的學制
- `department1/2/3`: 對應的科系
- `junior_high`: 就讀或畢業國中
- `current_grade`: 目前年級
- `line_id`: Line ID
- `facebook`: Facebook 帳號
- `remarks`: 備註
- `status`: 狀態（pending/contacted/enrolled）
- `admin_comment`: 管理員備註
- `created_at`: 建立時間
- `updated_at`: 更新時間

### 索引：
- `idx_username`: 用戶帳號索引
- `idx_status`: 狀態索引
- `idx_created_at`: 建立時間索引
- `idx_identity`: 身分別索引

## 完成後

資料表建立完成後，你的就讀意願登錄系統就可以正常運作了！

- 學生/家長可以透過表單提交申請
- 管理員可以透過管理介面查看和管理申請
- 所有資料都會儲存在這個資料表中
