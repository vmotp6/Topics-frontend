-- =====================================================
-- 簡化版：遷移 student 數據（分步驟執行）
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 步驟 1: 查看要遷移的數據
-- =====================================================

SELECT 
    '將要遷移的 student 記錄' AS 說明,
    s.id,
    s.user_id,
    s.name,
    s.department,
    s.grade,
    -- 檢查是否能找到對應的 user
    CASE 
        WHEN EXISTS (SELECT 1 FROM user u WHERE u.id = s.user_id) 
        THEN '✅ user 存在'
        ELSE '❌ user 不存在'
    END AS user_檢查,
    -- 檢查是否能找到對應的 department
    (SELECT id FROM departments d WHERE d.name = s.department LIMIT 1) AS department_id,
    CASE 
        WHEN EXISTS (SELECT 1 FROM departments d WHERE d.name = s.department) 
        THEN '✅ department 匹配'
        ELSE '❌ department 不匹配'
    END AS department_檢查,
    -- 檢查是否能找到對應的 grade
    (SELECT id FROM grades g WHERE g.name = s.grade LIMIT 1) AS grade_id,
    CASE 
        WHEN EXISTS (SELECT 1 FROM grades g WHERE g.name = s.grade) 
        THEN '✅ grade 匹配'
        ELSE '❌ grade 不匹配'
    END AS grade_檢查
FROM student s
WHERE s.user_id IS NOT NULL
ORDER BY s.id;

-- =====================================================
-- 步驟 2: 遷移所有數據
-- =====================================================

INSERT INTO student_normalized (
    user_id,
    name,
    student_id,
    department_id,
    grade_id,
    class_name,
    email,
    phone,
    created_at,
    updated_at
)
SELECT 
    s.user_id,
    COALESCE(s.name, '') AS name,
    s.student_id,
    -- 查找 department_id
    (SELECT id FROM departments d WHERE d.name = s.department LIMIT 1) AS department_id,
    -- 查找 grade_id
    (SELECT id FROM grades g WHERE g.name = s.grade LIMIT 1) AS grade_id,
    s.class_name,
    s.email,
    s.phone,
    COALESCE(s.created_at, NOW()) AS created_at,
    COALESCE(s.updated_at, NOW()) AS updated_at
FROM student s
WHERE s.user_id IS NOT NULL
AND EXISTS (
    -- 只遷移有對應 user 的記錄
    SELECT 1 FROM user u WHERE u.id = s.user_id
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    student_id = VALUES(student_id),
    department_id = VALUES(department_id),
    grade_id = VALUES(grade_id),
    class_name = VALUES(class_name),
    email = VALUES(email),
    phone = VALUES(phone),
    updated_at = VALUES(updated_at);

-- =====================================================
-- 步驟 3: 檢查遷移結果
-- =====================================================

SELECT 
    '遷移結果' AS 檢查項目,
    (SELECT COUNT(*) FROM student WHERE user_id IS NOT NULL) AS 原始表記錄數,
    (SELECT COUNT(*) FROM student_normalized) AS 正規化表記錄數,
    CASE 
        WHEN (SELECT COUNT(*) FROM student WHERE user_id IS NOT NULL) = (SELECT COUNT(*) FROM student_normalized)
        THEN '✅ 遷移成功'
        ELSE CONCAT('⚠️ 有 ', 
            (SELECT COUNT(*) FROM student WHERE user_id IS NOT NULL) - 
            (SELECT COUNT(*) FROM student_normalized),
            ' 筆記錄未遷移')
    END AS 狀態;

-- 顯示遷移後的數據樣本
SELECT 
    '遷移後的數據樣本' AS 說明,
    sn.user_id,
    sn.name,
    sn.department_id,
    d.name AS department_name,
    sn.grade_id,
    g.name AS grade_name
FROM student_normalized sn
LEFT JOIN departments d ON d.id = sn.department_id
LEFT JOIN grades g ON g.id = sn.grade_id
LIMIT 5;

SET FOREIGN_KEY_CHECKS = 1;

SELECT '✅ student 數據遷移完成！' AS message;

