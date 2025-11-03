-- =====================================================
-- 更新 student_normalized 的 department_id 和 grade_id
-- 使用模糊匹配來修復 NULL 值
-- =====================================================

USE topics_good;

-- =====================================================
-- 步驟 1: 更新 department_id（使用模糊匹配）
-- =====================================================

UPDATE student_normalized sn
INNER JOIN student s ON s.user_id = sn.user_id
SET sn.department_id = (
    SELECT id FROM departments d 
    WHERE d.name = s.department 
    OR d.name LIKE CONCAT('%', s.department, '%')
    OR s.department LIKE CONCAT('%', d.name, '%')
    LIMIT 1
)
WHERE sn.department_id IS NULL
AND s.department IS NOT NULL;

-- =====================================================
-- 步驟 2: 更新 grade_id（使用模糊匹配）
-- =====================================================

UPDATE student_normalized sn
INNER JOIN student s ON s.user_id = sn.user_id
SET sn.grade_id = (
    SELECT id FROM grades g 
    WHERE g.name = s.grade 
    OR g.name LIKE CONCAT('%', s.grade, '%')
    OR s.grade LIKE CONCAT('%', g.name, '%')
    LIMIT 1
)
WHERE sn.grade_id IS NULL
AND s.grade IS NOT NULL;

-- =====================================================
-- 步驟 3: 驗證更新結果
-- =====================================================

SELECT 
    '更新後的數據' AS 說明,
    sn.user_id,
    sn.name,
    s.department AS 原始_department,
    sn.department_id,
    d.name AS 匹配的_department,
    s.grade AS 原始_grade,
    sn.grade_id,
    g.name AS 匹配的_grade
FROM student_normalized sn
INNER JOIN student s ON s.user_id = sn.user_id
LEFT JOIN departments d ON d.id = sn.department_id
LEFT JOIN grades g ON g.id = sn.grade_id;

SELECT '✅ department_id 和 grade_id 更新完成！' AS message;

