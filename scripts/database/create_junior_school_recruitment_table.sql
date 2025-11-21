-- =====================================================
-- 國中學校招生申請表建立腳本
-- 改進版本 - 包含資料驗證和約束
-- =====================================================

USE topics_good;

-- 如果表已存在，先刪除（謹慎使用）
-- DROP TABLE IF EXISTS `junior_school_recruitment_applications`;

-- =====================================================
-- 建立國中學校招生申請表
-- =====================================================

CREATE TABLE IF NOT EXISTS `junior_school_recruitment_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '申請編號',
  
  -- 學校基本資訊
  `school_name` varchar(100) NOT NULL COMMENT '學校名稱',
  `city` varchar(20) NOT NULL COMMENT '縣市',
  `district` varchar(20) NOT NULL COMMENT '區/鄉鎮市',
  `school_address` varchar(255) DEFAULT NULL COMMENT '學校地址',
  
  -- 聯絡人資訊
  `contact_name` varchar(50) NOT NULL COMMENT '聯絡人姓名',
  `contact_title` varchar(50) DEFAULT NULL COMMENT '聯絡人職稱',
  `contact_phone` varchar(20) NOT NULL COMMENT '聯絡電話',
  `contact_email` varchar(120) NOT NULL COMMENT '聯絡Email',
  
  -- 招生活動資訊
  `preferred_date` date NOT NULL COMMENT '期望招生日期',
  `preferred_time` varchar(50) NOT NULL COMMENT '期望時間（例如：上午、下午、全天）',
  `target_grades` varchar(50) NOT NULL COMMENT '目標年級（例如：三年級、二年級）',
  `expected_students` int(11) NOT NULL COMMENT '預期參與學生人數',
  `venue_type` varchar(50) DEFAULT NULL COMMENT '場地類型（例如：禮堂、活動中心、教室）',
  
  -- 其他資訊
  `special_requirements` text DEFAULT NULL COMMENT '特殊需求',
  `remarks` text DEFAULT NULL COMMENT '備註',
  
  -- 狀態管理
  `status` enum('pending','approved','rejected','completed','cancelled') DEFAULT 'pending' COMMENT '申請狀態：pending=待審核, approved=已核准, rejected=已拒絕, completed=已完成, cancelled=已取消',
  `admin_comment` text DEFAULT NULL COMMENT '管理員備註',
  `admin_id` int(11) DEFAULT NULL COMMENT '處理的管理員ID',
  `processed_at` timestamp NULL DEFAULT NULL COMMENT '處理時間',
  
  -- 時間戳記
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '申請時間',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間',
  
  PRIMARY KEY (`id`),
  
  -- 索引
  KEY `idx_school_name` (`school_name`),
  KEY `idx_city` (`city`),
  KEY `idx_district` (`district`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_contact_email` (`contact_email`),
  KEY `idx_preferred_date` (`preferred_date`),
  KEY `idx_admin_id` (`admin_id`),
  
  -- 唯一性約束：避免同一個學校、同一個聯絡人在同一天重複申請
  UNIQUE KEY `unique_school_contact_date` (`school_name`, `contact_email`, `preferred_date`)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='國中學校招生申請表';

-- =====================================================
-- 添加資料驗證觸發器
-- =====================================================

DELIMITER $$

-- 驗證 Email 格式
CREATE TRIGGER `validate_email_before_insert`
BEFORE INSERT ON `junior_school_recruitment_applications`
FOR EACH ROW
BEGIN
    IF NEW.contact_email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Email 格式不正確';
    END IF;
END$$

CREATE TRIGGER `validate_email_before_update`
BEFORE UPDATE ON `junior_school_recruitment_applications`
FOR EACH ROW
BEGIN
    IF NEW.contact_email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Email 格式不正確';
    END IF;
END$$

-- 驗證電話號碼格式
CREATE TRIGGER `validate_phone_before_insert`
BEFORE INSERT ON `junior_school_recruitment_applications`
FOR EACH ROW
BEGIN
    IF NEW.contact_phone NOT REGEXP '^[0-9-+() ]+$' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = '電話號碼格式不正確，只能包含數字、連字號、加號、括號和空格';
    END IF;
END$$

CREATE TRIGGER `validate_phone_before_update`
BEFORE UPDATE ON `junior_school_recruitment_applications`
FOR EACH ROW
BEGIN
    IF NEW.contact_phone NOT REGEXP '^[0-9-+() ]+$' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = '電話號碼格式不正確，只能包含數字、連字號、加號、括號和空格';
    END IF;
END$$

-- 驗證預期學生人數
CREATE TRIGGER `validate_students_before_insert`
BEFORE INSERT ON `junior_school_recruitment_applications`
FOR EACH ROW
BEGIN
    IF NEW.expected_students IS NOT NULL AND NEW.expected_students <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = '預期參與學生人數必須大於 0';
    END IF;
END$$

CREATE TRIGGER `validate_students_before_update`
BEFORE UPDATE ON `junior_school_recruitment_applications`
FOR EACH ROW
BEGIN
    IF NEW.expected_students IS NOT NULL AND NEW.expected_students <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = '預期參與學生人數必須大於 0';
    END IF;
END$$

-- 驗證日期不能是過去
CREATE TRIGGER `validate_date_before_insert`
BEFORE INSERT ON `junior_school_recruitment_applications`
FOR EACH ROW
BEGIN
    IF NEW.preferred_date < CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = '期望招生日期不能是過去的日期';
    END IF;
END$$

CREATE TRIGGER `validate_date_before_update`
BEFORE UPDATE ON `junior_school_recruitment_applications`
FOR EACH ROW
BEGIN
    IF NEW.preferred_date < CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = '期望招生日期不能是過去的日期';
    END IF;
END$$

-- 狀態變更時自動記錄處理時間
CREATE TRIGGER `update_processed_at_on_status_change`
BEFORE UPDATE ON `junior_school_recruitment_applications`
FOR EACH ROW
BEGIN
    IF OLD.status = 'pending' AND NEW.status IN ('approved', 'rejected', 'completed') THEN
        SET NEW.processed_at = CURRENT_TIMESTAMP;
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- 插入範例資料（可選）
-- =====================================================

-- 如果需要插入測試資料，取消下面的註解
/*
INSERT INTO `junior_school_recruitment_applications` 
(`school_name`, `city`, `district`, `school_address`, `contact_name`, `contact_title`, `contact_phone`, `contact_email`, `preferred_date`, `preferred_time`, `target_grades`, `expected_students`, `venue_type`, `status`) 
VALUES
('中正國中', '台北市', '中正區', '台北市中正區重慶南路一段', '王主任', '教務主任', '02-23456789', 'wang@school.edu.tw', DATE_ADD(CURDATE(), INTERVAL 30 DAY), '上午', '三年級', 50, '禮堂', 'pending'),
('建國國中', '新北市', '板橋區', '新北市板橋區建國路123號', '李主任', '學務主任', '02-34567890', 'lee@school.edu.tw', DATE_ADD(CURDATE(), INTERVAL 45 DAY), '下午', '二年級', 30, '活動中心', 'pending');
*/

-- =====================================================
-- 建立視圖（方便查詢）
-- =====================================================

-- 申請統計視圖
CREATE OR REPLACE VIEW `v_recruitment_applications_stats` AS
SELECT 
    status,
    COUNT(*) as count,
    COUNT(CASE WHEN preferred_date >= CURDATE() THEN 1 END) as upcoming_count,
    COUNT(CASE WHEN preferred_date < CURDATE() THEN 1 END) as past_count
FROM `junior_school_recruitment_applications`
GROUP BY status;

-- 最近申請視圖
CREATE OR REPLACE VIEW `v_recent_applications` AS
SELECT 
    id,
    school_name,
    city,
    district,
    contact_name,
    contact_email,
    preferred_date,
    preferred_time,
    target_grades,
    expected_students,
    status,
    created_at
FROM `junior_school_recruitment_applications`
ORDER BY created_at DESC
LIMIT 50;

-- =====================================================
-- 完成訊息
-- =====================================================

SELECT '✅ 國中學校招生申請表建立完成！' AS message;
SELECT '📋 表結構已建立，包含資料驗證觸發器' AS info;
SELECT '🔍 可以使用以下查詢檢查表結構：' AS info;
SELECT '   SHOW CREATE TABLE junior_school_recruitment_applications;' AS query;







