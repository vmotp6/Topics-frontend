-- =====================================================
-- 清理無效數據並添加最後 3 個外鍵約束
-- 一步到位完成所有操作
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 步驟 1: 清理無效數據
-- =====================================================

-- 清理 enrollment_applications_normalized 中的無效 recommended_teacher_user_id
UPDATE enrollment_applications_normalized
SET recommended_teacher_user_id = NULL
WHERE recommended_teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized 
    WHERE user_id = enrollment_applications_normalized.recommended_teacher_user_id
);

SELECT CONCAT('✅ 已清理 enrollment_applications_normalized 中 ', ROW_COUNT(), ' 筆無效的 recommended_teacher_user_id') AS 清理結果;

-- 清理 cooperation_applications_normalized 中的無效 teacher_user_id
UPDATE cooperation_applications_normalized
SET teacher_user_id = NULL
WHERE teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized 
    WHERE user_id = cooperation_applications_normalized.teacher_user_id
);

SELECT CONCAT('✅ 已清理 cooperation_applications_normalized 中 ', ROW_COUNT(), ' 筆無效的 teacher_user_id') AS 清理結果;

-- 清理 cooperation_applications_normalized 中的無效 department_id
UPDATE cooperation_applications_normalized
SET department_id = NULL
WHERE department_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM departments 
    WHERE id = cooperation_applications_normalized.department_id
);

SELECT CONCAT('✅ 已清理 cooperation_applications_normalized 中 ', ROW_COUNT(), ' 筆無效的 department_id') AS 清理結果;

-- =====================================================
-- 步驟 2: 添加外鍵約束
-- =====================================================

-- 外鍵 1: enrollment_applications_normalized.recommended_teacher_user_id
ALTER TABLE enrollment_applications_normalized
ADD CONSTRAINT fk_enrollment_teacher 
FOREIGN KEY (recommended_teacher_user_id) 
REFERENCES teacher_normalized(user_id) 
ON DELETE SET NULL;

SELECT '✅ fk_enrollment_teacher 外鍵已添加' AS 結果;

-- 外鍵 2: cooperation_applications_normalized.teacher_user_id
ALTER TABLE cooperation_applications_normalized
ADD CONSTRAINT fk_cooperation_teacher 
FOREIGN KEY (teacher_user_id) 
REFERENCES teacher_normalized(user_id) 
ON DELETE RESTRICT;

SELECT '✅ fk_cooperation_teacher 外鍵已添加' AS 結果;

-- 外鍵 3: cooperation_applications_normalized.department_id
ALTER TABLE cooperation_applications_normalized
ADD CONSTRAINT fk_cooperation_department 
FOREIGN KEY (department_id) 
REFERENCES departments(id) 
ON DELETE RESTRICT;

SELECT '✅ fk_cooperation_department 外鍵已添加' AS 結果;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 步驟 3: 最終驗證
-- =====================================================

SELECT 
    '✅ 外鍵驗證' AS 檢查項目,
    CONSTRAINT_NAME AS 外鍵名稱,
    TABLE_NAME AS 表名,
    '✅ 已設置' AS 狀態
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = DATABASE()
AND CONSTRAINT_TYPE = 'FOREIGN KEY'
AND CONSTRAINT_NAME IN (
    'fk_enrollment_teacher',
    'fk_cooperation_teacher',
    'fk_cooperation_department'
)
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

SELECT '🎉 所有外鍵約束添加完成！' AS message;
SELECT '📝 請重新運行驗證腳本：verify_3nf_normalization.php' AS next_step;

