-- =====================================================
-- 修復 enrollment_applications_normalized 表結構
-- 確保表有 recommended_teacher_user_id 欄位
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- 檢查並添加 recommended_teacher_user_id 欄位（如果不存在）
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

SET FOREIGN_KEY_CHECKS = 1;

SELECT '✅ enrollment_applications_normalized 表結構已修復！' AS message;

