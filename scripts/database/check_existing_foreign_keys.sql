-- =====================================================
-- 檢查所有現有的外鍵約束
-- 查看 enrollment_applications_normalized 和 cooperation_applications_normalized 表的外鍵
-- =====================================================

USE topics_good;

-- 1. 檢查 enrollment_applications_normalized 的所有外鍵
SELECT 
    'enrollment_applications_normalized' AS 表名,
    CONSTRAINT_NAME AS 外鍵名稱,
    COLUMN_NAME AS 欄位名稱,
    REFERENCED_TABLE_NAME AS 引用表,
    REFERENCED_COLUMN_NAME AS 引用欄位
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'enrollment_applications_normalized'
AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY CONSTRAINT_NAME;

-- 2. 檢查 cooperation_applications_normalized 的所有外鍵
SELECT 
    'cooperation_applications_normalized' AS 表名,
    CONSTRAINT_NAME AS 外鍵名稱,
    COLUMN_NAME AS 欄位名稱,
    REFERENCED_TABLE_NAME AS 引用表,
    REFERENCED_COLUMN_NAME AS 引用欄位
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'cooperation_applications_normalized'
AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY CONSTRAINT_NAME;

-- 3. 檢查這兩個表的所有約束（包括外鍵）
SELECT 
    TABLE_NAME AS 表名,
    CONSTRAINT_NAME AS 約束名稱,
    CONSTRAINT_TYPE AS 約束類型
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME IN ('enrollment_applications_normalized', 'cooperation_applications_normalized')
ORDER BY TABLE_NAME, CONSTRAINT_TYPE, CONSTRAINT_NAME;

-- 4. 檢查特定外鍵是否存在
SELECT 
    CASE 
        WHEN EXISTS(
            SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND CONSTRAINT_NAME = 'fk_enrollment_identity'
        ) THEN '✅ 存在'
        ELSE '❌ 不存在'
    END AS fk_enrollment_identity,
    CASE 
        WHEN EXISTS(
            SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND CONSTRAINT_NAME = 'fk_enrollment_gender'
        ) THEN '✅ 存在'
        ELSE '❌ 不存在'
    END AS fk_enrollment_gender,
    CASE 
        WHEN EXISTS(
            SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND CONSTRAINT_NAME = 'fk_enrollment_grade'
        ) THEN '✅ 存在'
        ELSE '❌ 不存在'
    END AS fk_enrollment_grade,
    CASE 
        WHEN EXISTS(
            SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND CONSTRAINT_NAME = 'fk_enrollment_teacher'
        ) THEN '✅ 存在'
        ELSE '❌ 不存在'
    END AS fk_enrollment_teacher,
    CASE 
        WHEN EXISTS(
            SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND CONSTRAINT_NAME = 'fk_cooperation_teacher'
        ) THEN '✅ 存在'
        ELSE '❌ 不存在'
    END AS fk_cooperation_teacher,
    CASE 
        WHEN EXISTS(
            SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND CONSTRAINT_NAME = 'fk_cooperation_department'
        ) THEN '✅ 存在'
        ELSE '❌ 不存在'
    END AS fk_cooperation_department;

