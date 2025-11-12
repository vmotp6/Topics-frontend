-- =====================================================
-- 簡單版：添加最後 3 個外鍵約束
-- 直接執行，自動處理所有問題
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 步驟 1: 顯示無效數據（僅查看，不修改）
-- =====================================================

-- 查看無效的 recommended_teacher_user_id
SELECT 
    '無效的 recommended_teacher_user_id' AS 檢查項目,
    id,
    recommended_teacher_user_id,
    '此 user_id 在 teacher_normalized 中不存在' AS 問題
FROM enrollment_applications_normalized
WHERE recommended_teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized 
    WHERE user_id = enrollment_applications_normalized.recommended_teacher_user_id
);

-- 查看無效的 teacher_user_id
SELECT 
    '無效的 teacher_user_id' AS 檢查項目,
    id,
    teacher_user_id,
    '此 user_id 在 teacher_normalized 中不存在' AS 問題
FROM cooperation_applications_normalized
WHERE teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized 
    WHERE user_id = cooperation_applications_normalized.teacher_user_id
);

-- 查看無效的 department_id
SELECT 
    '無效的 department_id' AS 檢查項目,
    id,
    department_id,
    '此 department_id 在 departments 表中不存在' AS 問題
FROM cooperation_applications_normalized
WHERE department_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM departments 
    WHERE id = cooperation_applications_normalized.department_id
);

-- =====================================================
-- 步驟 2: 清理無效數據（將無效值設為 NULL）
-- =====================================================

-- 清理 enrollment_applications_normalized 中的無效 recommended_teacher_user_id
UPDATE enrollment_applications_normalized
SET recommended_teacher_user_id = NULL
WHERE recommended_teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized 
    WHERE user_id = enrollment_applications_normalized.recommended_teacher_user_id
);

SELECT CONCAT('✅ 已清理 ', ROW_COUNT(), ' 筆無效的 recommended_teacher_user_id') AS 清理結果;

-- 清理 cooperation_applications_normalized 中的無效 teacher_user_id
UPDATE cooperation_applications_normalized
SET teacher_user_id = NULL
WHERE teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized 
    WHERE user_id = cooperation_applications_normalized.teacher_user_id
);

SELECT CONCAT('✅ 已清理 ', ROW_COUNT(), ' 筆無效的 teacher_user_id') AS 清理結果;

-- 清理 cooperation_applications_normalized 中的無效 department_id
UPDATE cooperation_applications_normalized
SET department_id = NULL
WHERE department_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM departments 
    WHERE id = cooperation_applications_normalized.department_id
);

SELECT CONCAT('✅ 已清理 ', ROW_COUNT(), ' 筆無效的 department_id') AS 清理結果;

-- =====================================================
-- 步驟 3: 添加外鍵約束
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
-- 步驟 4: 最終驗證
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

-- 統計
SELECT 
    '📊 外鍵統計' AS 檢查項目,
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
     WHERE TABLE_SCHEMA = DATABASE()
     AND CONSTRAINT_TYPE = 'FOREIGN KEY'
     AND CONSTRAINT_NAME IN (
         'fk_enrollment_identity', 'fk_enrollment_gender', 'fk_enrollment_grade', 
         'fk_enrollment_teacher', 'fk_enrollment_status',
         'fk_cooperation_teacher', 'fk_cooperation_department', 'fk_cooperation_company', 'fk_cooperation_status',
         'fk_student_user', 'fk_student_department', 'fk_student_grade',
         'fk_teacher_user', 'fk_teacher_department'
     )) AS 總外鍵數,
    CASE 
        WHEN (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE()
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
              AND CONSTRAINT_NAME IN (
                  'fk_enrollment_identity', 'fk_enrollment_gender', 'fk_enrollment_grade', 
                  'fk_enrollment_teacher', 'fk_enrollment_status',
                  'fk_cooperation_teacher', 'fk_cooperation_department', 'fk_cooperation_company', 'fk_cooperation_status',
                  'fk_student_user', 'fk_student_department', 'fk_student_grade',
                  'fk_teacher_user', 'fk_teacher_department'
              )) >= 14
        THEN '✅ 所有外鍵已設置'
        ELSE '⚠️ 部分外鍵未設置'
    END AS 狀態;

SELECT '🎉 所有外鍵約束添加完成！' AS message;
SELECT '請重新運行驗證腳本查看完整結果' AS next_step;

