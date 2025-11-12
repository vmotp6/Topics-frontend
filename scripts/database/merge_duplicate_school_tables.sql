-- =====================================================
-- 合併重複的學校表
-- 將 school_data 合併到 schools 表
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- 擴展 schools 表結構（添加 school_data 的額外欄位）
-- 使用簡單的方式，如果欄位已存在會報錯但可以忽略
-- 注意：這裡使用簡單的 ALTER TABLE，如果欄位已存在會失敗，但可以在 PHP 中忽略

SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'schools' AND column_name = 'principal');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE schools ADD COLUMN principal VARCHAR(50) DEFAULT NULL COMMENT ''校長姓名'' AFTER website',
    'SELECT ''Column principal already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'schools' AND column_name = 'student_count');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE schools ADD COLUMN student_count INT DEFAULT 0 COMMENT ''學生人數'' AFTER principal',
    'SELECT ''Column student_count already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'schools' AND column_name = 'teacher_count');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE schools ADD COLUMN teacher_count INT DEFAULT 0 COMMENT ''教師人數'' AFTER student_count',
    'SELECT ''Column teacher_count already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'schools' AND column_name = 'established_year');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE schools ADD COLUMN established_year YEAR DEFAULT NULL COMMENT ''創校年份'' AFTER teacher_count',
    'SELECT ''Column established_year already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'schools' AND column_name = 'is_active');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE schools ADD COLUMN is_active TINYINT(1) DEFAULT 1 COMMENT ''是否營運中'' AFTER established_year',
    'SELECT ''Column is_active already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'schools' AND column_name = 'data_source');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE schools ADD COLUMN data_source VARCHAR(100) DEFAULT ''教育部開放資料'' COMMENT ''資料來源'' AFTER is_active',
    'SELECT ''Column data_source already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'schools' AND column_name = 'last_updated');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE schools ADD COLUMN last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT ''最後更新時間'' AFTER data_source',
    'SELECT ''Column last_updated already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加索引（如果不存在）
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() AND table_name = 'schools' AND index_name = 'idx_school_code');
SET @sql = IF(@idx_exists = 0, 
    'ALTER TABLE schools ADD INDEX idx_school_code (school_code)',
    'SELECT ''Index idx_school_code already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() AND table_name = 'schools' AND index_name = 'idx_active');
SET @sql = IF(@idx_exists = 0, 
    'ALTER TABLE schools ADD INDEX idx_active (is_active)',
    'SELECT ''Index idx_active already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 合併 school_data 的數據到 schools
-- 根據名稱和城市匹配
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
    s.last_updated = GREATEST(s.last_updated, sd.last_updated);

SET FOREIGN_KEY_CHECKS = 1;

SELECT '學校表已合併' AS message;
SELECT '建議：確認數據正確後，可以將 school_data 表重命名為 school_data_backup' AS next_step;

