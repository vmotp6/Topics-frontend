-- =====================================================
-- 添加外鍵前的檢查腳本
-- 檢查數據一致性和表結構
-- =====================================================

USE topics_good;

-- 1. 檢查 enrollment_applications_normalized 表的結構
DESCRIBE enrollment_applications_normalized;

-- 2. 檢查 cooperation_applications_normalized 表的結構
DESCRIBE cooperation_applications_normalized;

-- 3. 檢查 teacher_normalized 表的結構
DESCRIBE teacher_normalized;

-- 4. 檢查 enrollment_applications_normalized 中是否有無效的 recommended_teacher_user_id
SELECT 
    'enrollment_applications_normalized 中的無效 teacher_user_id' AS 檢查項目,
    COUNT(*) AS 無效記錄數
FROM enrollment_applications_normalized ean
WHERE ean.recommended_teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized tn 
    WHERE tn.user_id = ean.recommended_teacher_user_id
);

-- 5. 顯示無效的記錄（如果有）
SELECT 
    id,
    recommended_teacher_user_id,
    '此 teacher_user_id 在 teacher_normalized 中不存在' AS 問題
FROM enrollment_applications_normalized ean
WHERE ean.recommended_teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized tn 
    WHERE tn.user_id = ean.recommended_teacher_user_id
)
LIMIT 10;

-- 6. 檢查 cooperation_applications_normalized 中是否有無效的 teacher_user_id
SELECT 
    'cooperation_applications_normalized 中的無效 teacher_user_id' AS 檢查項目,
    COUNT(*) AS 無效記錄數
FROM cooperation_applications_normalized can
WHERE can.teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized tn 
    WHERE tn.user_id = can.teacher_user_id
);

-- 7. 顯示無效的記錄（如果有）
SELECT 
    id,
    teacher_user_id,
    '此 teacher_user_id 在 teacher_normalized 中不存在' AS 問題
FROM cooperation_applications_normalized can
WHERE can.teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized tn 
    WHERE tn.user_id = can.teacher_user_id
)
LIMIT 10;

-- 8. 檢查 identity_id 的有效性
SELECT 
    'enrollment_applications_normalized 中的無效 identity_id' AS 檢查項目,
    COUNT(*) AS 無效記錄數
FROM enrollment_applications_normalized ean
WHERE ean.identity_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM identities i 
    WHERE i.id = ean.identity_id
);

-- 9. 檢查 gender_id 的有效性
SELECT 
    'enrollment_applications_normalized 中的無效 gender_id' AS 檢查項目,
    COUNT(*) AS 無效記錄數
FROM enrollment_applications_normalized ean
WHERE ean.gender_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM genders g 
    WHERE g.id = ean.gender_id
);

-- 10. 檢查 current_grade_id 的有效性
SELECT 
    'enrollment_applications_normalized 中的無效 current_grade_id' AS 檢查項目,
    COUNT(*) AS 無效記錄數
FROM enrollment_applications_normalized ean
WHERE ean.current_grade_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM grades gr 
    WHERE gr.id = ean.current_grade_id
);

-- 11. 檢查 department_id 的有效性（cooperation_applications_normalized）
SELECT 
    'cooperation_applications_normalized 中的無效 department_id' AS 檢查項目,
    COUNT(*) AS 無效記錄數
FROM cooperation_applications_normalized can
WHERE can.department_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM departments d 
    WHERE d.id = can.department_id
);

-- 12. 檢查 company_id 的有效性
SELECT 
    'cooperation_applications_normalized 中的無效 company_id' AS 檢查項目,
    COUNT(*) AS 無效記錄數
FROM cooperation_applications_normalized can
WHERE can.company_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM companies c 
    WHERE c.id = can.company_id
);

