-- =====================================================
-- 遷移 student 和 teacher 數據到正規化表
-- 根據驗證報告，student_normalized 和 teacher_normalized 都是空的
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. 遷移 student 數據到 student_normalized
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
    s.name,
    s.student_id,
    -- 查找 department_id
    (SELECT id FROM departments d 
     WHERE d.name = s.department 
     LIMIT 1) AS department_id,
    -- 查找 grade_id
    (SELECT id FROM grades g 
     WHERE g.name = s.grade 
     LIMIT 1) AS grade_id,
    s.class_name,
    s.email,
    s.phone,
    s.created_at,
    s.updated_at
FROM student s
WHERE s.user_id IS NOT NULL
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
-- 2. 遷移 teacher 數據到 teacher_normalized
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
    t.name,
    -- 查找 department_id
    (SELECT id FROM departments d 
     WHERE d.name = t.department 
     LIMIT 1) AS department_id,
    t.phone,
    t.created_at,
    t.updated_at
FROM teacher t
WHERE t.user_id IS NOT NULL
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    department_id = VALUES(department_id),
    phone = VALUES(phone),
    updated_at = VALUES(updated_at);

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 3. 驗證遷移結果
-- =====================================================

SELECT 
    'student 數據遷移' AS 檢查項目,
    (SELECT COUNT(*) FROM student) AS 原始表記錄數,
    (SELECT COUNT(*) FROM student_normalized) AS 正規化表記錄數,
    CASE 
        WHEN (SELECT COUNT(*) FROM student) = (SELECT COUNT(*) FROM student_normalized) 
        THEN '✅ 一致'
        ELSE '⚠️ 不一致'
    END AS 狀態;

SELECT 
    'teacher 數據遷移' AS 檢查項目,
    (SELECT COUNT(*) FROM teacher) AS 原始表記錄數,
    (SELECT COUNT(*) FROM teacher_normalized) AS 正規化表記錄數,
    CASE 
        WHEN (SELECT COUNT(*) FROM teacher) = (SELECT COUNT(*) FROM teacher_normalized) 
        THEN '✅ 一致'
        ELSE '⚠️ 不一致'
    END AS 狀態;

-- 顯示未遷移的記錄（如果有）
SELECT 
    '未遷移的 student 記錄' AS 檢查項目,
    s.id,
    s.name,
    s.user_id,
    'user_id 為 NULL 或找不到對應的 user' AS 原因
FROM student s
WHERE s.user_id IS NULL
OR NOT EXISTS (
    SELECT 1 FROM user u WHERE u.id = s.user_id
)
LIMIT 10;

SELECT 
    '未遷移的 teacher 記錄' AS 檢查項目,
    t.id,
    t.name,
    t.user_id,
    'user_id 為 NULL 或找不到對應的 user' AS 原因
FROM teacher t
WHERE t.user_id IS NULL
OR NOT EXISTS (
    SELECT 1 FROM user u WHERE u.id = t.user_id
)
LIMIT 10;

SELECT '✅ 數據遷移完成！' AS message;

