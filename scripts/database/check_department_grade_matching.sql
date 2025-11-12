-- =====================================================
-- 檢查 department 和 grade 匹配問題
-- =====================================================

USE topics_good;

-- 1. 查看 departments 表中的所有值
SELECT 
    'departments 表數據' AS 說明,
    id,
    name,
    code
FROM departments
ORDER BY id;

-- 2. 查看 grades 表中的所有值
SELECT 
    'grades 表數據' AS 說明,
    id,
    name,
    code
FROM grades
ORDER BY id;

-- 3. 查看 student 表中的 department 值
SELECT 
    'student 表中的 department 值' AS 說明,
    DISTINCT department,
    COUNT(*) AS 記錄數
FROM student
WHERE department IS NOT NULL
GROUP BY department;

-- 4. 查看 student 表中的 grade 值
SELECT 
    'student 表中的 grade 值' AS 說明,
    DISTINCT grade,
    COUNT(*) AS 記錄數
FROM student
WHERE grade IS NOT NULL
GROUP BY grade;

-- 5. 查看 teacher 表中的 department 值
SELECT 
    'teacher 表中的 department 值' AS 說明,
    DISTINCT department,
    COUNT(*) AS 記錄數
FROM teacher
WHERE department IS NOT NULL
GROUP BY department;

-- 6. 嘗試模糊匹配
SELECT 
    'department 模糊匹配測試' AS 說明,
    s.department AS student_department,
    d.name AS departments_table_name,
    d.id AS department_id,
    CASE 
        WHEN d.id IS NOT NULL THEN '✅ 可匹配'
        ELSE '❌ 無法匹配'
    END AS 匹配狀態
FROM (
    SELECT DISTINCT department 
    FROM student 
    WHERE department IS NOT NULL
) AS s
LEFT JOIN departments d ON (
    d.name = s.department 
    OR d.name LIKE CONCAT('%', s.department, '%')
    OR s.department LIKE CONCAT('%', d.name, '%')
);

-- 7. 嘗試模糊匹配 grade
SELECT 
    'grade 模糊匹配測試' AS 說明,
    s.grade AS student_grade,
    g.name AS grades_table_name,
    g.id AS grade_id,
    CASE 
        WHEN g.id IS NOT NULL THEN '✅ 可匹配'
        ELSE '❌ 無法匹配'
    END AS 匹配狀態
FROM (
    SELECT DISTINCT grade 
    FROM student 
    WHERE grade IS NOT NULL
) AS s
LEFT JOIN grades g ON (
    g.name = s.grade 
    OR g.name LIKE CONCAT('%', s.grade, '%')
    OR s.grade LIKE CONCAT('%', g.name, '%')
);

