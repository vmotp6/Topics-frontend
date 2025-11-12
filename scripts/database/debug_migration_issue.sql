-- =====================================================
-- 診斷遷移問題
-- 檢查為什麼 teacher 數據遷移失敗
-- =====================================================

USE topics_good;

-- 1. 檢查 teacher 表結構
DESCRIBE teacher;

-- 2. 檢查 teacher_normalized 表結構
DESCRIBE teacher_normalized;

-- 3. 檢查兩表的欄位是否匹配
SELECT 
    'teacher 表欄位' AS 來源,
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'teacher'
ORDER BY ORDINAL_POSITION;

SELECT 
    'teacher_normalized 表欄位' AS 來源,
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'teacher_normalized'
ORDER BY ORDINAL_POSITION;

-- 4. 檢查 teacher 表的數據樣本
SELECT 
    'teacher 表數據樣本' AS 說明,
    id,
    user_id,
    name,
    department,
    phone,
    created_at,
    updated_at
FROM teacher
LIMIT 5;

-- 5. 檢查是否有 user_id 為 NULL 的記錄
SELECT 
    'user_id 為 NULL 的記錄' AS 問題,
    COUNT(*) AS 記錄數
FROM teacher
WHERE user_id IS NULL;

-- 6. 檢查是否有 user_id 不存在於 user 表的記錄
SELECT 
    'user_id 無效的記錄' AS 問題,
    COUNT(*) AS 記錄數
FROM teacher t
WHERE t.user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM user u WHERE u.id = t.user_id
);

-- 7. 檢查 departments 表中的數據
SELECT 
    'departments 表數據' AS 說明,
    id,
    name,
    code
FROM departments;

-- 8. 檢查 teacher 表中的 department 值
SELECT 
    'teacher 表中的 department 值' AS 說明,
    department,
    COUNT(*) AS 記錄數
FROM teacher
WHERE department IS NOT NULL
GROUP BY department;

-- 9. 檢查 department 匹配情況
SELECT 
    t.id,
    t.name,
    t.department AS 原始 department,
    d.id AS 匹配的 department_id,
    d.name AS 匹配的 department 名稱,
    CASE 
        WHEN d.id IS NULL THEN '❌ 無法匹配'
        ELSE '✅ 可以匹配'
    END AS 匹配狀態
FROM teacher t
LEFT JOIN departments d ON d.name = t.department
LIMIT 10;

-- 10. 測試插入語句（只選取一條記錄測試）
SELECT 
    '測試數據' AS 說明,
    t.user_id,
    t.name,
    (SELECT id FROM departments d 
     WHERE d.name = t.department 
     LIMIT 1) AS department_id,
    t.phone,
    t.created_at,
    t.updated_at
FROM teacher t
WHERE t.user_id IS NOT NULL
AND EXISTS (SELECT 1 FROM user u WHERE u.id = t.user_id)
LIMIT 1;

