；-- =====================================================
-- 驗證 quick_fix_missing_tables.sql 執行結果
-- 檢查所有表和視圖是否已創建
-- =====================================================

USE topics_good;

-- 1. 檢查 role_types 表
SELECT 'role_types 表' AS 檢查項目,
    CASE 
        WHEN EXISTS(SELECT 1 FROM information_schema.tables 
            WHERE table_schema = DATABASE() AND table_name = 'role_types')
        THEN '✅ 存在'
        ELSE '❌ 不存在'
    END AS 狀態,
    (SELECT COUNT(*) FROM role_types) AS 記錄數;

-- 2. 檢查所有正規化表
SELECT '正規化表' AS 類別,
    table_name AS 表名,
    CASE 
        WHEN EXISTS(SELECT 1 FROM information_schema.tables 
            WHERE table_schema = DATABASE() AND table_name = t.table_name)
        THEN '✅ 存在'
        ELSE '❌ 不存在'
    END AS 狀態
FROM (
    SELECT 'student_normalized' AS table_name
    UNION ALL SELECT 'teacher_normalized'
    UNION ALL SELECT 'chat_groups_normalized'
    UNION ALL SELECT 'group_members_normalized'
    UNION ALL SELECT 'private_chat_history_normalized'
    UNION ALL SELECT 'group_messages_normalized'
    UNION ALL SELECT 'ai_chat_history_normalized'
) AS t;

-- 3. 檢查所有視圖
SELECT '視圖' AS 類別,
    table_name AS 視圖名,
    CASE 
        WHEN EXISTS(SELECT 1 FROM information_schema.views 
            WHERE table_schema = DATABASE() AND table_name = v.table_name)
        THEN '✅ 存在'
        ELSE '❌ 不存在'
    END AS 狀態
FROM (
    SELECT 'student_view' AS table_name
    UNION ALL SELECT 'teacher_view'
    UNION ALL SELECT 'private_chat_history_view'
    UNION ALL SELECT 'group_messages_view'
    UNION ALL SELECT 'chat_groups_view'
    UNION ALL SELECT 'group_members_view'
) AS v;

-- 4. 檢查外鍵約束
SELECT 
    '外鍵約束' AS 類別,
    TABLE_NAME AS 表名,
    CONSTRAINT_NAME AS 外鍵名稱,
    '✅ 已設置' AS 狀態
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = DATABASE()
AND CONSTRAINT_TYPE = 'FOREIGN KEY'
AND CONSTRAINT_NAME IN (
    'fk_enrollment_identity',
    'fk_enrollment_gender',
    'fk_enrollment_grade',
    'fk_enrollment_teacher',
    'fk_cooperation_teacher',
    'fk_cooperation_department'
)
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

-- 5. 統計總結
SELECT 
    '📊 統計總結' AS 標題,
    (SELECT COUNT(*) FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name IN ('role_types', 'student_normalized', 'teacher_normalized', 
                          'chat_groups_normalized', 'group_members_normalized', 
                          'private_chat_history_normalized', 'group_messages_normalized', 
                          'ai_chat_history_normalized')) AS '已創建的表數',
    (SELECT COUNT(*) FROM information_schema.views 
        WHERE table_schema = DATABASE() 
        AND table_name IN ('student_view', 'teacher_view', 'private_chat_history_view', 
                          'group_messages_view', 'chat_groups_view', 'group_members_view')) AS '已創建的視圖數',
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = DATABASE()
        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        AND CONSTRAINT_NAME IN ('fk_enrollment_identity', 'fk_enrollment_gender', 
                               'fk_enrollment_grade', 'fk_enrollment_teacher', 
                               'fk_cooperation_teacher', 'fk_cooperation_department')) AS '已設置的外鍵數';

