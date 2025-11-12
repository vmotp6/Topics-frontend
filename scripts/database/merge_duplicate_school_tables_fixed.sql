-- =====================================================
-- 合併重複的學校表（修復版本）
-- 將 school_data 合併到 schools 表
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- 擴展 schools 表結構（添加 school_data 的額外欄位）
-- 注意：這些語句會在欄位已存在時失敗，但可以在 PHP 中忽略錯誤

-- 1. 添加 school_code（如果不存在）
ALTER TABLE schools ADD COLUMN school_code VARCHAR(20) DEFAULT NULL COMMENT '學校代碼' AFTER type;

-- 2. 添加 principal（如果不存在）
ALTER TABLE schools ADD COLUMN principal VARCHAR(50) DEFAULT NULL COMMENT '校長姓名' AFTER website;

-- 3. 添加 student_count（如果不存在）
ALTER TABLE schools ADD COLUMN student_count INT DEFAULT 0 COMMENT '學生人數' AFTER principal;

-- 4. 添加 teacher_count（如果不存在）
ALTER TABLE schools ADD COLUMN teacher_count INT DEFAULT 0 COMMENT '教師人數' AFTER student_count;

-- 5. 添加 established_year（如果不存在）
ALTER TABLE schools ADD COLUMN established_year YEAR DEFAULT NULL COMMENT '創校年份' AFTER teacher_count;

-- 6. 添加 is_active（如果不存在）
ALTER TABLE schools ADD COLUMN is_active TINYINT(1) DEFAULT 1 COMMENT '是否營運中' AFTER established_year;

-- 7. 添加 data_source（如果不存在）
ALTER TABLE schools ADD COLUMN data_source VARCHAR(100) DEFAULT '教育部開放資料' COMMENT '資料來源' AFTER is_active;

-- 8. 添加 last_updated（如果不存在）
ALTER TABLE schools ADD COLUMN last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最後更新時間' AFTER data_source;

-- 添加索引（如果不存在會失敗，但可以忽略）
ALTER TABLE schools ADD INDEX idx_school_code (school_code);
ALTER TABLE schools ADD INDEX idx_active (is_active);

-- 合併 school_data 的數據到 schools（只合併不存在的記錄）
INSERT INTO schools (
    name, city, district, address, phone, website, type,
    school_code, principal, student_count, teacher_count,
    established_year, is_active, data_source, last_updated
)
SELECT DISTINCT
    sd.name,
    sd.city,
    sd.district,
    COALESCE(sd.address, '') AS address,
    sd.phone,
    sd.website,
    CASE 
        WHEN sd.type LIKE '%公立%' THEN '公立'
        WHEN sd.type LIKE '%私立%' THEN '私立'
        ELSE '公立'
    END AS type,
    sd.school_code,
    sd.principal,
    sd.student_count,
    sd.teacher_count,
    sd.established_year,
    sd.is_active,
    sd.data_source,
    sd.last_updated
FROM school_data sd
WHERE NOT EXISTS (
    SELECT 1 FROM schools s 
    WHERE s.name = sd.name 
    AND s.city = sd.city
)
ON DUPLICATE KEY UPDATE
    school_code = COALESCE(VALUES(school_code), schools.school_code),
    principal = COALESCE(VALUES(principal), schools.principal),
    student_count = COALESCE(VALUES(student_count), schools.student_count),
    teacher_count = COALESCE(VALUES(teacher_count), schools.teacher_count),
    established_year = COALESCE(VALUES(established_year), schools.established_year),
    is_active = COALESCE(VALUES(is_active), schools.is_active),
    data_source = COALESCE(VALUES(data_source), schools.data_source),
    last_updated = GREATEST(VALUES(last_updated), schools.last_updated);

-- 更新現有 schools 記錄（從 school_data 補齊資料）
UPDATE schools s
JOIN school_data sd ON s.name = sd.name AND s.city = sd.city
SET 
    s.school_code = COALESCE(sd.school_code, s.school_code),
    s.principal = COALESCE(sd.principal, s.principal),
    s.student_count = COALESCE(sd.student_count, s.student_count),
    s.teacher_count = COALESCE(sd.teacher_count, s.teacher_count),
    s.established_year = COALESCE(sd.established_year, s.established_year),
    s.is_active = COALESCE(sd.is_active, s.is_active),
    s.data_source = COALESCE(sd.data_source, s.data_source),
    s.last_updated = GREATEST(s.last_updated, COALESCE(sd.last_updated, s.last_updated));

SET FOREIGN_KEY_CHECKS = 1;

SELECT '學校表已合併' AS message;

