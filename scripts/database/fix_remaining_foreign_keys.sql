-- =====================================================
-- 修復剩餘的 3 個外鍵約束
-- 專門處理無法添加的外鍵問題
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 問題分析：
-- 1. enrollment_applications_normalized.recommended_teacher_user_id
-- 2. cooperation_applications_normalized.teacher_user_id  
-- 3. cooperation_applications_normalized.department_id
-- =====================================================

-- 步驟 1: 檢查是否有無效數據
SELECT '檢查無效數據' AS 步驟;

-- 檢查無效的 recommended_teacher_user_id
SELECT 
    'enrollment_applications_normalized 中無效的 recommended_teacher_user_id' AS 檢查項目,
    COUNT(*) AS 無效記錄數
FROM enrollment_applications_normalized
WHERE recommended_teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized 
    WHERE user_id = enrollment_applications_normalized.recommended_teacher_user_id
);

-- 檢查無效的 teacher_user_id（cooperation_applications_normalized）
SELECT 
    'cooperation_applications_normalized 中無效的 teacher_user_id' AS 檢查項目,
    COUNT(*) AS 無效記錄數
FROM cooperation_applications_normalized
WHERE teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized 
    WHERE user_id = cooperation_applications_normalized.teacher_user_id
);

-- 檢查無效的 department_id
SELECT 
    'cooperation_applications_normalized 中無效的 department_id' AS 檢查項目,
    COUNT(*) AS 無效記錄數
FROM cooperation_applications_normalized
WHERE department_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM departments 
    WHERE id = cooperation_applications_normalized.department_id
);

-- 步驟 2: 清理無效數據（設為 NULL）
UPDATE enrollment_applications_normalized
SET recommended_teacher_user_id = NULL
WHERE recommended_teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized 
    WHERE user_id = enrollment_applications_normalized.recommended_teacher_user_id
);

UPDATE cooperation_applications_normalized
SET teacher_user_id = NULL
WHERE teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized 
    WHERE user_id = cooperation_applications_normalized.teacher_user_id
);

UPDATE cooperation_applications_normalized
SET department_id = NULL
WHERE department_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM departments 
    WHERE id = cooperation_applications_normalized.department_id
);

-- 步驟 3: 添加外鍵約束

-- 外鍵 1: enrollment_applications_normalized.recommended_teacher_user_id
ALTER TABLE enrollment_applications_normalized
ADD CONSTRAINT fk_enrollment_teacher 
FOREIGN KEY (recommended_teacher_user_id) 
REFERENCES teacher_normalized(user_id) 
ON DELETE SET NULL;

-- 外鍵 2: cooperation_applications_normalized.teacher_user_id
-- 注意：如果這個欄位是 NOT NULL，但現在有 NULL 值，需要先修改表結構
ALTER TABLE cooperation_applications_normalized
ADD CONSTRAINT fk_cooperation_teacher 
FOREIGN KEY (teacher_user_id) 
REFERENCES teacher_normalized(user_id) 
ON DELETE RESTRICT;

-- 外鍵 3: cooperation_applications_normalized.department_id
ALTER TABLE cooperation_applications_normalized
ADD CONSTRAINT fk_cooperation_department 
FOREIGN KEY (department_id) 
REFERENCES departments(id) 
ON DELETE RESTRICT;

SET FOREIGN_KEY_CHECKS = 1;

-- 步驟 4: 驗證結果
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

SELECT '✅ 剩餘的外鍵約束已修復完成！' AS message;

