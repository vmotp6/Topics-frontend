-- =====================================================
-- 修復 teacher 數據遷移
-- 解決步驟 2 的問題
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 步驟 1: 查看要遷移的數據
-- =====================================================

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
        ELSE '❌ department 不匹配'
    END AS department_檢查
FROM teacher t
WHERE t.user_id IS NOT NULL
ORDER BY t.id;

-- =====================================================
-- 步驟 2: 遷移所有數據
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
    -- 嘗試匹配 department（精確匹配優先，然後模糊匹配）
    COALESCE(
        (SELECT id FROM departments d WHERE d.name = t.department LIMIT 1),
        (SELECT id FROM departments d WHERE d.name LIKE CONCAT('%', t.department, '%') LIMIT 1),
        (SELECT id FROM departments d WHERE t.department LIKE CONCAT('%', d.name, '%') LIMIT 1),
        NULL
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

-- =====================================================
-- 步驟 3: 檢查遷移結果
-- =====================================================

SELECT 
    '遷移結果' AS 檢查項目,
    (SELECT COUNT(*) FROM teacher WHERE user_id IS NOT NULL) AS 原始表記錄數,
    (SELECT COUNT(*) FROM teacher_normalized) AS 正規化表記錄數,
    CASE 
        WHEN (SELECT COUNT(*) FROM teacher WHERE user_id IS NOT NULL) = (SELECT COUNT(*) FROM teacher_normalized)
        THEN '✅ 遷移成功'
        ELSE CONCAT('⚠️ 有 ', 
            (SELECT COUNT(*) FROM teacher WHERE user_id IS NOT NULL) - 
            (SELECT COUNT(*) FROM teacher_normalized),
            ' 筆記錄未遷移')
    END AS 狀態;

-- 顯示遷移後的數據樣本
SELECT 
    '遷移後的數據樣本' AS 說明,
    tn.user_id,
    tn.name,
    tn.department_id,
    d.name AS department_name,
    tn.phone
FROM teacher_normalized tn
LEFT JOIN departments d ON d.id = tn.department_id
LIMIT 5;

SET FOREIGN_KEY_CHECKS = 1;

SELECT '✅ teacher 數據遷移完成！' AS message;

