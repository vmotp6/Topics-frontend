-- =====================================================
-- 修復 3 個缺失的外鍵約束
-- 1. enrollment_applications_normalized.recommended_teacher_user_id
-- 2. cooperation_applications_normalized.teacher_user_id
-- 3. cooperation_applications_normalized.department_id
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 步驟 1: 檢查並添加缺失的欄位
-- =====================================================

-- 檢查並添加 enrollment_applications_normalized.recommended_teacher_user_id
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'enrollment_applications_normalized' 
    AND column_name = 'recommended_teacher_user_id');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE enrollment_applications_normalized ADD COLUMN recommended_teacher_user_id INT NULL COMMENT ''關聯到 teacher_normalized.user_id'' AFTER facebook',
    'SELECT ''Column recommended_teacher_user_id already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加索引（如果不存在）
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'enrollment_applications_normalized' 
    AND index_name = 'idx_recommended_teacher_user_id');
SET @sql = IF(@idx_exists = 0, 
    'ALTER TABLE enrollment_applications_normalized ADD INDEX idx_recommended_teacher_user_id (recommended_teacher_user_id)',
    'SELECT ''Index idx_recommended_teacher_user_id already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 檢查並添加 cooperation_applications_normalized.teacher_user_id
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'cooperation_applications_normalized' 
    AND column_name = 'teacher_user_id');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE cooperation_applications_normalized ADD COLUMN teacher_user_id INT NULL COMMENT ''關聯到 teacher_normalized.user_id'' AFTER id',
    'SELECT ''Column teacher_user_id already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加索引（如果不存在）
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'cooperation_applications_normalized' 
    AND index_name = 'idx_teacher_user_id');
SET @sql = IF(@idx_exists = 0, 
    'ALTER TABLE cooperation_applications_normalized ADD INDEX idx_teacher_user_id (teacher_user_id)',
    'SELECT ''Index idx_teacher_user_id already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 如果欄位存在但為 NULL，且原表有 teacher_id，則嘗試遷移數據
-- 注意：這裡假設可能有舊的 teacher_id 欄位需要遷移
SET @old_col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'cooperation_applications_normalized' 
    AND column_name = 'teacher_id');
SET @new_col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'cooperation_applications_normalized' 
    AND column_name = 'teacher_user_id');
SET @sql = IF(@old_col_exists > 0 AND @new_col_exists > 0,
    'UPDATE cooperation_applications_normalized SET teacher_user_id = teacher_id WHERE teacher_user_id IS NULL AND teacher_id IS NOT NULL',
    'SELECT ''No migration needed''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 步驟 2: 清理無效的數據引用
-- =====================================================

-- 清理 enrollment_applications_normalized 中的無效 recommended_teacher_user_id
-- 只有在欄位存在時才執行
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'enrollment_applications_normalized' 
    AND column_name = 'recommended_teacher_user_id');
SET @sql = IF(@col_exists > 0, 
    'UPDATE enrollment_applications_normalized SET recommended_teacher_user_id = NULL WHERE recommended_teacher_user_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM teacher_normalized WHERE user_id = enrollment_applications_normalized.recommended_teacher_user_id)',
    'SELECT ''Column recommended_teacher_user_id does not exist, skipping cleanup''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 清理 cooperation_applications_normalized 中的無效 teacher_user_id
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'cooperation_applications_normalized' 
    AND column_name = 'teacher_user_id');
SET @sql = IF(@col_exists > 0, 
    'UPDATE cooperation_applications_normalized SET teacher_user_id = NULL WHERE teacher_user_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM teacher_normalized WHERE user_id = cooperation_applications_normalized.teacher_user_id)',
    'SELECT ''Column teacher_user_id does not exist, skipping cleanup''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 清理 cooperation_applications_normalized 中的無效 department_id
UPDATE cooperation_applications_normalized
SET department_id = NULL
WHERE department_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM departments 
    WHERE id = cooperation_applications_normalized.department_id
);

-- =====================================================
-- 步驟 3: 添加外鍵約束
-- =====================================================

-- 外鍵 1: enrollment_applications_normalized.recommended_teacher_user_id
-- 檢查是否已存在
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'enrollment_applications_normalized'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    AND CONSTRAINT_NAME = 'fk_enrollment_teacher');
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE enrollment_applications_normalized ADD CONSTRAINT fk_enrollment_teacher FOREIGN KEY (recommended_teacher_user_id) REFERENCES teacher_normalized(user_id) ON DELETE SET NULL',
    'SELECT ''FK fk_enrollment_teacher already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 外鍵 2: cooperation_applications_normalized.teacher_user_id
-- 檢查是否已存在
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cooperation_applications_normalized'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    AND CONSTRAINT_NAME = 'fk_cooperation_teacher');
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE cooperation_applications_normalized ADD CONSTRAINT fk_cooperation_teacher FOREIGN KEY (teacher_user_id) REFERENCES teacher_normalized(user_id) ON DELETE RESTRICT',
    'SELECT ''FK fk_cooperation_teacher already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 外鍵 3: cooperation_applications_normalized.department_id
-- 檢查是否已存在
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cooperation_applications_normalized'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    AND CONSTRAINT_NAME = 'fk_cooperation_department');
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE cooperation_applications_normalized ADD CONSTRAINT fk_cooperation_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT',
    'SELECT ''FK fk_cooperation_department already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 驗證結果
-- =====================================================

SELECT '✅ 外鍵約束已添加！' AS message;

-- 顯示已添加的外鍵
SELECT 
    CONSTRAINT_NAME AS '外鍵名稱',
    TABLE_NAME AS '表名',
    COLUMN_NAME AS '欄位名',
    REFERENCED_TABLE_NAME AS '引用表',
    REFERENCED_COLUMN_NAME AS '引用欄位'
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME IN ('enrollment_applications_normalized', 'cooperation_applications_normalized')
AND CONSTRAINT_NAME IN ('fk_enrollment_teacher', 'fk_cooperation_teacher', 'fk_cooperation_department')
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

