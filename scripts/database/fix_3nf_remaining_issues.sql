-- =====================================================
-- 修復 3NF 正規化驗證報告中的剩餘問題
-- 1. 遷移 teacher 數據（11 筆 → teacher_normalized）
-- 2. 添加 3 個缺失的外鍵約束
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 步驟 1: 檢查並遷移 teacher 數據
-- =====================================================

SELECT '📊 步驟 1: 檢查 teacher 數據遷移狀態' AS 步驟;

-- 檢查當前狀態
SELECT 
    '當前狀態檢查' AS 檢查項目,
    (SELECT COUNT(*) FROM teacher WHERE user_id IS NOT NULL) AS 原始表記錄數,
    (SELECT COUNT(*) FROM teacher_normalized) AS 正規化表記錄數,
    CASE 
        WHEN (SELECT COUNT(*) FROM teacher WHERE user_id IS NOT NULL) = 
             (SELECT COUNT(*) FROM teacher_normalized)
        THEN '✅ 數據已同步'
        ELSE CONCAT('⚠️ 需要遷移 ', 
            (SELECT COUNT(*) FROM teacher WHERE user_id IS NOT NULL) - 
            (SELECT COUNT(*) FROM teacher_normalized),
            ' 筆記錄')
    END AS 狀態;

-- 檢查要遷移的數據
SELECT 
    '將要遷移的 teacher 記錄' AS 說明,
    t.id,
    t.user_id,
    t.name,
    t.department,
    t.phone,
    -- 檢查是否能找到對應的 user
    CASE 
        WHEN EXISTS (SELECT 1 FROM user u WHERE u.id = t.user_id) 
        THEN '✅ user 存在'
        ELSE '❌ user 不存在'
    END AS user_檢查,
    -- 檢查是否能找到對應的 department
    (SELECT id FROM departments d WHERE d.name = t.department LIMIT 1) AS department_id,
    CASE 
        WHEN EXISTS (SELECT 1 FROM departments d WHERE d.name = t.department) 
        THEN '✅ department 匹配'
        ELSE '⚠️ department 不匹配'
    END AS department_檢查
FROM teacher t
WHERE t.user_id IS NOT NULL
ORDER BY t.id;

-- 遷移 teacher 數據到 teacher_normalized
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
    -- 嘗試匹配 department（精確匹配優先）
    COALESCE(
        (SELECT id FROM departments d WHERE d.name = t.department LIMIT 1),
        NULL  -- 如果找不到匹配的 department，設為 NULL
    ) AS department_id,
    t.phone,
    COALESCE(t.created_at, NOW()) AS created_at,
    COALESCE(t.updated_at, NOW()) AS updated_at
FROM teacher t
WHERE t.user_id IS NOT NULL
AND EXISTS (
    -- 只遷移有對應 user 的記錄
    SELECT 1 FROM user u WHERE u.id = t.user_id
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    department_id = VALUES(department_id),
    phone = VALUES(phone),
    updated_at = VALUES(updated_at);

-- 檢查遷移結果
SELECT 
    '遷移結果檢查' AS 檢查項目,
    (SELECT COUNT(*) FROM teacher WHERE user_id IS NOT NULL) AS 原始表記錄數,
    (SELECT COUNT(*) FROM teacher_normalized) AS 正規化表記錄數,
    CASE 
        WHEN (SELECT COUNT(*) FROM teacher WHERE user_id IS NOT NULL) = 
             (SELECT COUNT(*) FROM teacher_normalized)
        THEN '✅ 遷移成功'
        ELSE CONCAT('⚠️ 有 ', 
            (SELECT COUNT(*) FROM teacher WHERE user_id IS NOT NULL) - 
            (SELECT COUNT(*) FROM teacher_normalized),
            ' 筆記錄未遷移')
    END AS 狀態;

SELECT '✅ 步驟 1 完成：teacher 數據遷移' AS 狀態;

-- =====================================================
-- 步驟 2: 檢查並清理無效的外鍵引用數據
-- =====================================================

SELECT '📊 步驟 2: 檢查數據有效性' AS 步驟;

-- 檢查 enrollment_applications_normalized 中的無效 recommended_teacher_user_id
SELECT 
    'enrollment_applications_normalized 無效的 recommended_teacher_user_id' AS 檢查項目,
    COUNT(*) AS 無效記錄數,
    GROUP_CONCAT(DISTINCT recommended_teacher_user_id SEPARATOR ', ') AS 無效的_user_id
FROM enrollment_applications_normalized
WHERE recommended_teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized 
    WHERE user_id = enrollment_applications_normalized.recommended_teacher_user_id
);

-- 檢查 cooperation_applications_normalized 中的無效 teacher_user_id
-- 注意：如果表使用 teacher_id 而非 teacher_user_id，此查詢會失敗，但可以忽略
-- 先用動態 SQL 檢查欄位是否存在
SET @col_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cooperation_applications_normalized'
    AND COLUMN_NAME = 'teacher_user_id'
);

SET @check_sql = IF(
    @col_exists > 0,
    'SELECT 
        ''cooperation_applications_normalized 無效的 teacher_user_id'' AS 檢查項目,
        COUNT(*) AS 無效記錄數,
        GROUP_CONCAT(DISTINCT teacher_user_id SEPARATOR '', '') AS 無效的_user_id
    FROM cooperation_applications_normalized
    WHERE teacher_user_id IS NOT NULL
    AND NOT EXISTS (
        SELECT 1 FROM teacher_normalized 
        WHERE user_id = cooperation_applications_normalized.teacher_user_id
    )',
    'SELECT ''cooperation_applications_normalized 表使用 teacher_id 欄位，跳過 teacher_user_id 檢查'' AS 檢查項目'
);

PREPARE stmt FROM @check_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 檢查 cooperation_applications_normalized 中的無效 department_id
SELECT 
    'cooperation_applications_normalized 無效的 department_id' AS 檢查項目,
    COUNT(*) AS 無效記錄數,
    GROUP_CONCAT(DISTINCT department_id SEPARATOR ', ') AS 無效的_department_id
FROM cooperation_applications_normalized
WHERE department_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM departments 
    WHERE id = cooperation_applications_normalized.department_id
);

-- 清理無效數據（設為 NULL）
UPDATE enrollment_applications_normalized
SET recommended_teacher_user_id = NULL
WHERE recommended_teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized 
    WHERE user_id = enrollment_applications_normalized.recommended_teacher_user_id
);

-- 清理 cooperation_applications_normalized 中的無效 teacher_user_id（如果欄位存在）
SET @update_sql = IF(
    @col_exists > 0,
    'UPDATE cooperation_applications_normalized
    SET teacher_user_id = NULL
    WHERE teacher_user_id IS NOT NULL
    AND NOT EXISTS (
        SELECT 1 FROM teacher_normalized 
        WHERE user_id = cooperation_applications_normalized.teacher_user_id
    )',
    'SELECT ''跳過更新 teacher_user_id（欄位不存在）'' AS message'
);

PREPARE stmt FROM @update_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE cooperation_applications_normalized
SET department_id = NULL
WHERE department_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM departments 
    WHERE id = cooperation_applications_normalized.department_id
);

SELECT '✅ 無效數據已清理' AS 狀態;

-- =====================================================
-- 步驟 3: 添加缺失的外鍵約束
-- =====================================================

SELECT '📊 步驟 3: 添加外鍵約束' AS 步驟;

-- 檢查現有外鍵
SELECT 
    '現有外鍵檢查' AS 檢查項目,
    CONSTRAINT_NAME AS 外鍵名稱,
    TABLE_NAME AS 表名,
    '✅ 已存在' AS 狀態
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = DATABASE()
AND CONSTRAINT_TYPE = 'FOREIGN KEY'
AND CONSTRAINT_NAME IN (
    'fk_enrollment_teacher',
    'fk_cooperation_teacher',
    'fk_cooperation_department'
)
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

-- 外鍵 1: enrollment_applications_normalized.recommended_teacher_user_id
ALTER TABLE enrollment_applications_normalized
ADD CONSTRAINT fk_enrollment_teacher 
FOREIGN KEY (recommended_teacher_user_id) 
REFERENCES teacher_normalized(user_id) 
ON DELETE SET NULL;

SELECT '✅ fk_enrollment_teacher 外鍵已添加' AS 結果;

-- 外鍵 2: cooperation_applications_normalized.teacher_user_id（只有當欄位存在時）
SET @fk2_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cooperation_applications_normalized'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    AND CONSTRAINT_NAME = 'fk_cooperation_teacher'
);

SET @add_fk2_sql = IF(
    @fk2_exists = 0 AND @col_exists > 0,
    'ALTER TABLE cooperation_applications_normalized
    ADD CONSTRAINT fk_cooperation_teacher 
    FOREIGN KEY (teacher_user_id) 
    REFERENCES teacher_normalized(user_id) 
    ON DELETE RESTRICT',
    'SELECT ''跳過 fk_cooperation_teacher（外鍵已存在或欄位不存在）'' AS message'
);

PREPARE stmt FROM @add_fk2_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT '✅ fk_cooperation_teacher 外鍵已處理' AS 結果;

-- 外鍵 3: cooperation_applications_normalized.department_id
ALTER TABLE cooperation_applications_normalized
ADD CONSTRAINT fk_cooperation_department 
FOREIGN KEY (department_id) 
REFERENCES departments(id) 
ON DELETE RESTRICT;

SELECT '✅ fk_cooperation_department 外鍵已添加' AS 結果;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 步驟 4: 驗證結果
-- =====================================================

SELECT '📊 步驟 4: 驗證修復結果' AS 步驟;

-- 驗證 teacher 數據遷移
SELECT 
    'teacher 數據遷移驗證' AS 檢查項目,
    (SELECT COUNT(*) FROM teacher WHERE user_id IS NOT NULL) AS 原始表記錄數,
    (SELECT COUNT(*) FROM teacher_normalized) AS 正規化表記錄數,
    CASE 
        WHEN (SELECT COUNT(*) FROM teacher WHERE user_id IS NOT NULL) = 
             (SELECT COUNT(*) FROM teacher_normalized)
        THEN '✅ 一致'
        ELSE '⚠️ 不一致'
    END AS 狀態;

-- 驗證外鍵約束
SELECT 
    '✅ 外鍵約束驗證' AS 檢查項目,
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

-- 統計所有外鍵
SELECT 
    '📊 外鍵統計' AS 檢查項目,
    COUNT(*) AS 已設置的外鍵數,
    CASE 
        WHEN COUNT(*) = 3 THEN '✅ 全部添加成功'
        ELSE CONCAT('⚠️ 還有 ', 3 - COUNT(*), ' 個未設置')
    END AS 狀態
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = DATABASE()
AND CONSTRAINT_TYPE = 'FOREIGN KEY'
AND CONSTRAINT_NAME IN (
    'fk_enrollment_teacher',
    'fk_cooperation_teacher',
    'fk_cooperation_department'
);

-- 最終總結
SELECT 
    '📊 修復總結' AS 標題,
    'teacher 數據遷移' AS 項目1,
    CASE 
        WHEN (SELECT COUNT(*) FROM teacher WHERE user_id IS NOT NULL) = 
             (SELECT COUNT(*) FROM teacher_normalized)
        THEN '✅ 完成'
        ELSE '⚠️ 未完成'
    END AS 狀態1,
    '外鍵約束添加' AS 項目2,
    CASE 
        WHEN (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE()
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
              AND CONSTRAINT_NAME IN ('fk_enrollment_teacher', 'fk_cooperation_teacher', 'fk_cooperation_department')) = 3
        THEN '✅ 完成'
        ELSE '⚠️ 未完成'
    END AS 狀態2;

SELECT '✅ 修復腳本執行完成！' AS message;
SELECT '請重新運行驗證腳本查看完整結果' AS next_step;

