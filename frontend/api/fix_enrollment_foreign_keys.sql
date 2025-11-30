-- 修复 enrollment_intention 表的外键约束
-- 问题：错误的外键约束设置在显示字段上，应该设置在关联代码字段上

USE topics_good;

-- 1. 删除错误的外键约束
ALTER TABLE `enrollment_intention` 
  DROP FOREIGN KEY IF EXISTS `enrollment_intention_ibfk_4`,
  DROP FOREIGN KEY IF EXISTS `enrollment_intention_ibfk_5`;

-- 2. 添加正确的外键约束（如果字段存在且数据有效）
-- 注意：只有在 junior_high_school_code 和 current_grade_code 字段存在时才执行

-- 检查并添加 junior_high_school_code 的外键约束
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'topics_good' 
    AND TABLE_NAME = 'enrollment_intention' 
    AND COLUMN_NAME = 'junior_high_school_code'
);

SET @sql = IF(@col_exists > 0,
    'ALTER TABLE `enrollment_intention` 
     ADD CONSTRAINT `enrollment_intention_ibfk_4` 
     FOREIGN KEY (`junior_high_school_code`) 
     REFERENCES `school_data` (`school_code`) 
     ON DELETE SET NULL 
     ON UPDATE CASCADE',
    'SELECT "junior_high_school_code 字段不存在，跳过外键约束" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 current_grade_code 的外键约束
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'topics_good' 
    AND TABLE_NAME = 'enrollment_intention' 
    AND COLUMN_NAME = 'current_grade_code'
);

SET @sql = IF(@col_exists > 0,
    'ALTER TABLE `enrollment_intention` 
     ADD CONSTRAINT `enrollment_intention_ibfk_5` 
     FOREIGN KEY (`current_grade_code`) 
     REFERENCES `identity_options` (`code`) 
     ON DELETE SET NULL 
     ON UPDATE CASCADE',
    'SELECT "current_grade_code 字段不存在，跳过外键约束" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 显示当前的外键约束
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM 
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE 
    TABLE_SCHEMA = 'topics_good'
    AND TABLE_NAME = 'enrollment_intention'
    AND REFERENCED_TABLE_NAME IS NOT NULL;

