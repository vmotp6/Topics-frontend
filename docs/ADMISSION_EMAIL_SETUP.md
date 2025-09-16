# 康寧大學五專入學說明會系統設置指南

## 系統概述

此系統包含：
1. 入學說明會報名頁面 (`frontend/admission.php`)
2. 資料庫表格 (`admission_applications`)
3. 郵件提醒功能 (`scripts/maintenance/send_admission_reminders.php`)

## 安裝步驟

### 1. 建立資料庫表格

執行以下命令來建立所需的資料庫表格：

```bash
cd scripts/setup
php setup_admission_table.php
```

或者直接執行 SQL 文件：

```sql
mysql -u root -p topics_good < scripts/database/create_admission_table.sql
```

### 2. 配置郵件設定

編輯 `scripts/maintenance/send_admission_reminders.php` 中的郵件配置：

```php
$mail_config = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'your-email@gmail.com', // 更換為實際郵箱
    'smtp_password' => 'your-app-password',    // 更換為應用程式密碼
    'from_email' => 'your-email@gmail.com',
    'from_name' => '康寧大學招生中心'
];
```

### 3. 設置定期執行任務 (Cron Job)

#### Linux/Mac 系統：

編輯 crontab：
```bash
crontab -e
```

添加以下行（每天上午 9:00 執行）：
```bash
0 9 * * * /usr/bin/php /path/to/your/project/scripts/maintenance/send_admission_reminders.php
```

#### Windows 系統：

使用工作排程器（Task Scheduler）：

1. 開啟「工作排程器」
2. 點擊「建立基本工作」
3. 設定名稱：「康寧大學郵件提醒」
4. 觸發程序：每日
5. 時間：上午 9:00
6. 動作：啟動程式
7. 程式：`php.exe`
8. 引數：`C:\path\to\your\project\scripts\maintenance\send_admission_reminders.php`

### 4. 測試系統

#### 測試報名功能：
1. 開啟 `http://your-domain/frontend/admission.php`
2. 填寫測試資料並提交
3. 檢查資料庫是否正確儲存資料

#### 測試郵件功能：
```bash
cd scripts/maintenance
php send_admission_reminders.php
```

## 使用說明

### 報名流程
1. 學生/家長填寫報名表單
2. 資料儲存到 `admission_applications` 資料表
3. 系統在活動前 1-3 天自動發送提醒郵件

### 活動場次管理

目前支援的場次：
- `114.05.29（四) 1900-2030 （線上)`
- `114.06.21（六)1300-1600`

如需修改或新增場次，請編輯：
1. `frontend/admission.php` - 更新表單選項
2. `scripts/maintenance/send_admission_reminders.php` - 更新 `$session_dates` 陣列

### 資料庫結構

`admission_applications` 資料表包含以下欄位：

| 欄位名稱 | 資料類型 | 說明 |
|---------|---------|------|
| id | INT AUTO_INCREMENT | 主鍵 |
| email | VARCHAR(255) | 電子郵件 |
| school_name | VARCHAR(255) | 學校名稱 |
| student_name | VARCHAR(100) | 學生姓名 |
| grade | VARCHAR(50) | 就讀年級 |
| parent_name | VARCHAR(100) | 家長姓名 |
| contact_phone | VARCHAR(20) | 聯絡電話 |
| line_id | VARCHAR(100) | LINE ID |
| session_choice | VARCHAR(255) | 參加場次 |
| experience_course | TEXT | 體驗課程 |
| receive_info | ENUM | 是否願意收到升學訊息 |
| created_at | TIMESTAMP | 建立時間 |
| email_sent | TINYINT(1) | 是否已發送提醒郵件 |

## 維護與監控

### 檢查郵件發送狀況
```sql
SELECT session_choice, 
       COUNT(*) as total_applications,
       SUM(email_sent) as emails_sent,
       COUNT(*) - SUM(email_sent) as pending_emails
FROM admission_applications 
GROUP BY session_choice;
```

### 查看錯誤日誌
```bash
tail -f scripts/maintenance/email_reminder_errors.log
```

### 手動重置郵件發送狀態
如需重新發送郵件，可執行：
```sql
UPDATE admission_applications SET email_sent = 0 WHERE session_choice = '場次名稱';
```

## 疑難排解

### 常見問題

**1. 郵件無法發送**
- 檢查 SMTP 設定是否正確
- 確認 Gmail 應用程式密碼設定
- 檢查防火牆設定

**2. 資料庫連接錯誤**
- 確認資料庫服務運行正常
- 檢查 `config.php` 中的連接設定
- 驗證資料庫權限

**3. Cron 工作未執行**
- 檢查 PHP 路徑是否正確
- 確認檔案權限設定
- 查看系統日誌

### 聯絡資訊

如有技術問題，請聯絡系統管理員。

---

© 2025 康寧大學招生系統
