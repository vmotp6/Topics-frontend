-- =====================================================
-- 遷移 student 和 teacher 數據到正規化表（修復版）
-- 處理可能的錯誤情況
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. 先檢查數據
-- =====================================================

SELECT '檢查原始數據' AS 步驟;

-- 檢查 student 表
SELECT 
    'student 表' AS 表名,
    COUNT(*) AS 總記錄數,
    COUNT(user_id) AS 有 user_id 的記錄數,
    COUNT(DISTINCT user_id) AS 唯一 user_id 數
FROM student;

-- 檢查 teacher 表
SELECT 
    'teacher 表' AS 表名,
    COUNT(*) AS 總記錄數,
    COUNT(user_id) AS 有 user_id 的記錄數,
    COUNT(DISTINCT user_id) AS 唯一 user_id 數
FROM teacher;

-- 檢查 departments 表
SELECT 
    'departments 表' AS 表名,
    id,
    name
FROM departments;

-- 檢查 grades 表
SELECT 
    'grades 表' AS 表名,
    id,
    name
FROM grades;

-- =====================================================
-- 2. 遷移 student 數據到 student_normalized
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
    -- 查找 department_id（支持模糊匹配）
    COALESCE(
        (SELECT id FROM departments d 
         WHERE d.name = s.department 
         LIMIT 1),
        (SELECT id FROM departments d 
         WHERE d.name LIKE CONCAT('%', s.department, '%')
         LIMIT 1),
        NULL
    ) AS department_id,
    -- 查找 grade_id（支持模糊匹配）
    COALESCE(
        (SELECT id FROM grades g 
         WHERE g.name = s.grade 
         LIMIT 1),
        (SELECT id FROM grades g 
         WHERE g.name LIKE CONCAT('%', s.grade, '%')
         LIMIT 1),
        NULL
    ) AS grade_id,
    s.class_name,
    s.email,
    s.phone,
    COALESCE(s.created_at, NOW()) AS created_at,
    COALESCE(s.updated_at, NOW()) AS updated_at
FROM student s
WHERE s.user_id IS NOT NULL
AND EXISTS (
    -- 確保對應的 user 存在
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
-- 3. 遷移 teacher 數據到 teacher_normalized
-- =====================================================

INSERT INTO teacher_normalized (
    user_id,
    name,
    department_id,
    phone,
    created_at,
    updated_at
)
SELECT 
    t.user_id,
    COALESCE(t.name, '') AS name,
    -- 查找 department_id（支持模糊匹配）
    COALESCE(
        (SELECT id FROM departments d 
         WHERE d.name = t.department 
         LIMIT 1),
        (SELECT id FROM departments d 
         WHERE d.name LIKE CONCAT('%', t.department, '%')
         LIMIT 1),
        NULL
    ) AS department_id,
    t.phone,
    COALESCE(t.created_at, NOW()) AS created_at,
    COALESCE(t.updated_at, NOW()) AS updated_at
FROM teacher t
WHERE t.user_id IS NOT NULL
AND EXISTS (
    -- 確保對應的 user 存在
    SELECT 1 FROM user u WHERE u.id = t.user_id
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    department_id = VALUES(department_id),
    phone = VALUES(phone),
    updated_at = VALUES(updated_at);

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 4. 驗證遷移結果
-- =====================================================

SELECT 
    'student 數據遷移' AS 檢查項目,
    (SELECT COUNT(*) FROM student WHERE user_id IS NOT NULL) AS 原始表有效記錄數,
    (SELECT COUNT(*) FROM student_normalized) AS 正規化表記錄數,
    CASE 
        WHEN (SELECT COUNT(*) FROM student WHERE user_id IS NOT NULL) = (SELECT COUNT(*) FROM student_normalized) 
        THEN '✅ 一致'
        ELSE '⚠️ 不一致'
    END AS 狀態;

SELECT 
    'teacher 數據遷移' AS 檢查項目,
    (SELECT COUNT(*) FROM teacher WHERE user_id IS NOT NULL) AS 原始表有效記錄數,
    (SELECT COUNT(*) FROM teacher_normalized) AS 正規化表記錄數,
    CASE 
        WHEN (SELECT COUNT(*) FROM teacher WHERE user_id IS NOT NULL) = (SELECT COUNT(*) FROM teacher_normalized) 
        THEN '✅ 一致'
        ELSE '⚠️ 不一致'
    END AS 狀態;

-- 顯示未遷移的記錄（如果有）
SELECT 
    '⚠️ 未遷移的 student 記錄' AS 檢查項目,
    s.id,
    s.name,
    s.user_id,
    CASE 
        WHEN s.user_id IS NULL THEN 'user_id 為 NULL'
        WHEN NOT EXISTS (SELECT 1 FROM user u WHERE u.id = s.user_id) THEN '找不到對應的 user'
        ELSE '其他原因'
    END AS 原因
FROM student s
WHERE s.user_id IS NULL
OR NOT EXISTS (SELECT 1 FROM user u WHERE u.id = s.user_id)
LIMIT 10;

SELECT 
    '⚠️ 未遷移的 teacher 記錄' AS 檢查項目,
    t.id,
    t.name,
    t.user_id,
    CASE 
        WHEN t.user_id IS NULL THEN 'user_id 為 NULL'
        WHEN NOT EXISTS (SELECT 1 FROM user u WHERE u.id = t.user_id) THEN '找不到對應的 user'
        ELSE '其他原因'
    END AS 原因
FROM teacher t
WHERE t.user_id IS NULL
OR NOT EXISTS (SELECT 1 FROM user u WHERE u.id = t.user_id)
LIMIT 10;

-- 顯示 department 匹配情況
SELECT 
    'department 匹配情況' AS 檢查項目,
    s.department AS 原始 department,
    sn.department_id AS 匹配的 department_id,
    d.name AS 匹配的 department 名稱
FROM student s
LEFT JOIN student_normalized sn ON sn.user_id = s.user_id
LEFT JOIN departments d ON d.id = sn.department_id
WHERE s.department IS NOT NULL
LIMIT 10;

SELECT '✅ 數據遷移完成！' AS message;

