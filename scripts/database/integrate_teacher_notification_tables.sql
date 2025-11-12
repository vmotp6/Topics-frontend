-- =====================================================
-- 老師活動通知系統資料表整合與正規化腳本
-- 目標：整合並正規化 teacher_activity_notifications, 
--       teacher_activity_recipients, schools_contacts 三個表
-- 正規化程度：第三正規化（3NF）
-- =====================================================

USE topics_good;

-- =====================================================
-- 第一部分：創建基礎表（如果不存在）
-- =====================================================

-- 1. 創建 schools_contacts 表（學校聯絡人表）
CREATE TABLE IF NOT EXISTS schools_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '聯絡人ID',
    school_id INT DEFAULT NULL COMMENT '學校ID（可關聯到 schools 或 school_data 表）',
    name VARCHAR(100) NOT NULL COMMENT '聯絡人姓名',
    position VARCHAR(100) DEFAULT NULL COMMENT '職稱',
    email VARCHAR(120) NOT NULL COMMENT 'Email地址',
    phone VARCHAR(50) DEFAULT NULL COMMENT '電話',
    department VARCHAR(100) DEFAULT NULL COMMENT '部門',
    is_active TINYINT(1) DEFAULT 1 COMMENT '是否啟用',
    notes TEXT DEFAULT NULL COMMENT '備註',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    UNIQUE KEY uk_email (email),
    INDEX idx_school_id (school_id),
    INDEX idx_email (email),
    INDEX idx_name (name),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='學校聯絡人資料表';

-- 2. 創建正規化後的 teacher_activity_notifications 表
--    移除冗余的 teacher_name 和 teacher_email，改為關聯到 user 表
CREATE TABLE IF NOT EXISTS teacher_activity_notifications_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '通知ID',
    user_id INT DEFAULT NULL COMMENT '老師用戶ID（外鍵關聯到 user 表，可為NULL）',
    teacher_email VARCHAR(120) DEFAULT NULL COMMENT '老師Email（備用，用於找不到user時）',
    subject VARCHAR(200) NOT NULL COMMENT '郵件主旨',
    content TEXT NOT NULL COMMENT '郵件內容',
    event_date DATE DEFAULT NULL COMMENT '活動日期',
    link VARCHAR(300) DEFAULT NULL COMMENT '活動連結',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    INDEX idx_user_id (user_id),
    INDEX idx_teacher_email (teacher_email),
    INDEX idx_event_date (event_date),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='老師活動通知主表（正規化）';

-- 3. 創建正規化後的 teacher_activity_recipients 表
--    移除冗余的 email 字段，改為只引用 contact_id
CREATE TABLE IF NOT EXISTS teacher_activity_recipients_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '記錄ID',
    notification_id INT NOT NULL COMMENT '通知ID（外鍵關聯到 teacher_activity_notifications_normalized）',
    contact_id INT DEFAULT NULL COMMENT '聯絡人ID（外鍵關聯到 schools_contacts）',
    status ENUM('queued','sent','failed') DEFAULT 'queued' COMMENT '發送狀態',
    sent_at DATETIME DEFAULT NULL COMMENT '發送時間',
    error_message VARCHAR(500) DEFAULT NULL COMMENT '錯誤訊息',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    INDEX idx_notification (notification_id),
    INDEX idx_contact (contact_id),
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at),
    FOREIGN KEY (notification_id) REFERENCES teacher_activity_notifications_normalized(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES schools_contacts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='收件人記錄表（正規化）';

-- =====================================================
-- 第二部分：遷移現有數據
-- =====================================================

-- 1. 遷移 schools_contacts 數據（從現有數據中提取）
--    如果 schools_contacts 表已有數據，跳過此步驟
--    如果沒有數據，從 teacher_activity_recipients 中提取 email 創建聯絡人記錄

-- 注意：此操作需要 teacher_activity_recipients 表存在
-- 如果表不存在，此語句會失敗，但不會影響其他表的創建
INSERT IGNORE INTO schools_contacts (email, name, is_active, created_at)
SELECT DISTINCT 
    email,
    CONCAT('聯絡人-', SUBSTRING_INDEX(email, '@', 1)) as name,
    1 as is_active,
    MIN(created_at) as created_at
FROM teacher_activity_recipients
WHERE email IS NOT NULL 
  AND email != ''
GROUP BY email;

-- 2. 遷移 teacher_activity_notifications 數據到正規化表
--    根據 teacher_email 查找對應的 user_id
--    注意：此操作需要 teacher_activity_notifications 表存在

INSERT IGNORE INTO teacher_activity_notifications_normalized (
    id,
    user_id,
    teacher_email,
    subject,
    content,
    event_date,
    link,
    created_at
)
SELECT 
    tan.id,
    u.id as user_id,  -- 如果找不到對應的user，設為NULL
    tan.teacher_email as teacher_email,  -- 保留email作為備用
    tan.subject,
    tan.content,
    tan.event_date,
    tan.link,
    tan.created_at
FROM teacher_activity_notifications tan
LEFT JOIN user u ON u.email = tan.teacher_email;

-- 3. 遷移 teacher_activity_recipients 數據到正規化表
--    根據 email 查找對應的 contact_id
--    注意：此操作需要 teacher_activity_recipients 表存在

INSERT IGNORE INTO teacher_activity_recipients_normalized (
    id,
    notification_id,
    contact_id,
    status,
    sent_at,
    error_message,
    created_at
)
SELECT 
    tar.id,
    tar.notification_id,
    sc.id as contact_id,
    tar.status,
    tar.sent_at,
    tar.error_message,
    tar.created_at
FROM teacher_activity_recipients tar
LEFT JOIN schools_contacts sc ON sc.email = tar.email;

-- =====================================================
-- 第三部分：處理數據完整性
-- =====================================================

-- 1. 處理找不到對應user的通知（可能需要創建臨時user或標記）
--    先檢查是否有未對應的通知

-- 2. 創建視圖以保持向後兼容性（可選）
CREATE OR REPLACE VIEW teacher_activity_notifications_view AS
SELECT 
    tan.id,
    COALESCE(u.username, CONCAT('用戶-', tan.teacher_email)) as teacher_name,
    COALESCE(u.email, tan.teacher_email) as teacher_email,
    tan.subject,
    tan.content,
    tan.event_date,
    tan.link,
    tan.created_at
FROM teacher_activity_notifications_normalized tan
LEFT JOIN user u ON tan.user_id = u.id;

CREATE OR REPLACE VIEW teacher_activity_recipients_view AS
SELECT 
    tar.id,
    tar.notification_id,
    tar.contact_id,
    sc.email,
    tar.status,
    tar.sent_at,
    tar.error_message,
    tar.created_at
FROM teacher_activity_recipients_normalized tar
LEFT JOIN schools_contacts sc ON tar.contact_id = sc.id;

-- =====================================================
-- 第四部分：創建輔助索引和優化
-- =====================================================

-- 為正規化表添加複合索引以提升查詢性能
-- 注意：如果索引已存在，這些語句會失敗，但這是預期的行為
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'teacher_activity_recipients_normalized' 
               AND index_name = 'idx_notification_status');

SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE teacher_activity_recipients_normalized ADD INDEX idx_notification_status (notification_id, status)',
    'SELECT ''Index idx_notification_status already exists''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'teacher_activity_notifications_normalized' 
               AND index_name = 'idx_user_date');

SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE teacher_activity_notifications_normalized ADD INDEX idx_user_date (user_id, event_date)',
    'SELECT ''Index idx_user_date already exists''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 完成
-- =====================================================

