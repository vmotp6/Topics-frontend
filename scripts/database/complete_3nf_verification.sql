-- =====================================================
-- 完整的 3NF 正規化驗證腳本
-- 基於驗證報告檢查所有項目
-- =====================================================

USE topics_good;

-- =====================================================
-- 1. 基礎參考表檢查
-- =====================================================

SELECT '📊 1. 基礎參考表檢查' AS 章節;

SELECT 
    table_name AS 表名,
    CASE 
        WHEN EXISTS(SELECT 1 FROM information_schema.tables 
            WHERE table_schema = DATABASE() AND table_name = t.table_name)
        THEN '✅ 存在'
        ELSE '❌ 不存在'
    END AS 狀態,
    CASE 
        WHEN table_name = 'departments' THEN (SELECT COUNT(*) FROM departments)
        WHEN table_name = 'education_systems' THEN (SELECT COUNT(*) FROM education_systems)
        WHEN table_name = 'application_statuses' THEN (SELECT COUNT(*) FROM application_statuses)
        WHEN table_name = 'identities' THEN (SELECT COUNT(*) FROM identities)
        WHEN table_name = 'genders' THEN (SELECT COUNT(*) FROM genders)
        WHEN table_name = 'grades' THEN (SELECT COUNT(*) FROM grades)
        WHEN table_name = 'companies' THEN (SELECT COUNT(*) FROM companies)
        WHEN table_name = 'message_types' THEN (SELECT COUNT(*) FROM message_types)
        WHEN table_name = 'role_types' THEN (SELECT COUNT(*) FROM role_types)
        WHEN table_name = 'notification_types' THEN (SELECT COUNT(*) FROM notification_types)
        ELSE 0
    END AS 記錄數
FROM (
    SELECT 'departments' AS table_name
    UNION ALL SELECT 'education_systems'
    UNION ALL SELECT 'application_statuses'
    UNION ALL SELECT 'identities'
    UNION ALL SELECT 'genders'
    UNION ALL SELECT 'grades'
    UNION ALL SELECT 'companies'
    UNION ALL SELECT 'message_types'
    UNION ALL SELECT 'role_types'
    UNION ALL SELECT 'notification_types'
) AS t;

-- =====================================================
-- 2. 正規化表檢查
-- =====================================================

SELECT '📊 2. 正規化表檢查' AS 章節;

SELECT 
    normalized_table AS 正規化表,
    original_table AS 原始表,
    CASE 
        WHEN EXISTS(SELECT 1 FROM information_schema.tables 
            WHERE table_schema = DATABASE() AND table_name = t.normalized_table)
        THEN '✅ 存在'
        ELSE '❌ 不存在'
    END AS 狀態,
    CASE 
        WHEN normalized_table = 'student_normalized' THEN (SELECT COUNT(*) FROM student_normalized)
        WHEN normalized_table = 'teacher_normalized' THEN (SELECT COUNT(*) FROM teacher_normalized)
        WHEN normalized_table = 'chat_groups_normalized' THEN (SELECT COUNT(*) FROM chat_groups_normalized)
        WHEN normalized_table = 'group_members_normalized' THEN (SELECT COUNT(*) FROM group_members_normalized)
        WHEN normalized_table = 'private_chat_history_normalized' THEN (SELECT COUNT(*) FROM private_chat_history_normalized)
        WHEN normalized_table = 'group_messages_normalized' THEN (SELECT COUNT(*) FROM group_messages_normalized)
        WHEN normalized_table = 'enrollment_applications_normalized' THEN (SELECT COUNT(*) FROM enrollment_applications_normalized)
        WHEN normalized_table = 'enrollment_preferences' THEN (SELECT COUNT(*) FROM enrollment_preferences)
        WHEN normalized_table = 'cooperation_applications_normalized' THEN (SELECT COUNT(*) FROM cooperation_applications_normalized)
        WHEN normalized_table = 'ai_chat_history_normalized' THEN (SELECT COUNT(*) FROM ai_chat_history_normalized)
        WHEN normalized_table = 'user_activity_normalized' THEN (SELECT COUNT(*) FROM user_activity_normalized)
        WHEN normalized_table = 'unread_notifications_normalized' THEN (SELECT COUNT(*) FROM unread_notifications_normalized)
        WHEN normalized_table = 'notification_sent_log_normalized' THEN (SELECT COUNT(*) FROM notification_sent_log_normalized)
        WHEN normalized_table = 'message_likes_normalized' THEN (SELECT COUNT(*) FROM message_likes_normalized)
        WHEN normalized_table = 'senior_messages_normalized' THEN (SELECT COUNT(*) FROM senior_messages_normalized)
        ELSE 0
    END AS 記錄數,
    CASE 
        WHEN original_table = 'student' THEN (SELECT COUNT(*) FROM student)
        WHEN original_table = 'teacher' THEN (SELECT COUNT(*) FROM teacher)
        WHEN original_table = 'private_chat_history' THEN (SELECT COUNT(*) FROM private_chat_history)
        WHEN original_table = 'enrollment_applications' THEN (SELECT COUNT(*) FROM enrollment_applications)
        WHEN original_table = 'ai_chat_history' THEN (SELECT COUNT(*) FROM ai_chat_history)
        WHEN original_table = 'senior_messages' THEN (SELECT COUNT(*) FROM senior_messages)
        ELSE NULL
    END AS 原始表記錄數
FROM (
    SELECT 'student_normalized' AS normalized_table, 'student' AS original_table
    UNION ALL SELECT 'teacher_normalized', 'teacher'
    UNION ALL SELECT 'chat_groups_normalized', 'chat_groups'
    UNION ALL SELECT 'group_members_normalized', 'group_members'
    UNION ALL SELECT 'private_chat_history_normalized', 'private_chat_history'
    UNION ALL SELECT 'group_messages_normalized', 'group_messages'
    UNION ALL SELECT 'enrollment_applications_normalized', 'enrollment_applications'
    UNION ALL SELECT 'enrollment_preferences', '-'
    UNION ALL SELECT 'cooperation_applications_normalized', 'cooperation_applications'
    UNION ALL SELECT 'ai_chat_history_normalized', 'ai_chat_history'
    UNION ALL SELECT 'user_activity_normalized', 'user_activity'
    UNION ALL SELECT 'unread_notifications_normalized', 'unread_notifications'
    UNION ALL SELECT 'notification_sent_log_normalized', 'notification_sent_log'
    UNION ALL SELECT 'message_likes_normalized', 'message_likes'
    UNION ALL SELECT 'senior_messages_normalized', 'senior_messages'
) AS t;

-- =====================================================
-- 3. 向後兼容視圖檢查
-- =====================================================

SELECT '📊 3. 向後兼容視圖檢查' AS 章節;

SELECT 
    view_name AS 視圖名稱,
    CASE 
        WHEN EXISTS(SELECT 1 FROM information_schema.views 
            WHERE table_schema = DATABASE() AND table_name = v.view_name)
        THEN '✅ 存在'
        ELSE '❌ 不存在'
    END AS 狀態,
    CASE 
        WHEN view_name = 'student_view' THEN (SELECT COUNT(*) FROM student_view)
        WHEN view_name = 'teacher_view' THEN (SELECT COUNT(*) FROM teacher_view)
        WHEN view_name = 'private_chat_history_view' THEN (SELECT COUNT(*) FROM private_chat_history_view)
        WHEN view_name = 'group_messages_view' THEN (SELECT COUNT(*) FROM group_messages_view)
        WHEN view_name = 'chat_groups_view' THEN (SELECT COUNT(*) FROM chat_groups_view)
        WHEN view_name = 'group_members_view' THEN (SELECT COUNT(*) FROM group_members_view)
        WHEN view_name = 'user_activity_view' THEN (SELECT COUNT(*) FROM user_activity_view)
        WHEN view_name = 'unread_notifications_view' THEN (SELECT COUNT(*) FROM unread_notifications_view)
        WHEN view_name = 'notification_sent_log_view' THEN (SELECT COUNT(*) FROM notification_sent_log_view)
        WHEN view_name = 'message_likes_view' THEN (SELECT COUNT(*) FROM message_likes_view)
        WHEN view_name = 'senior_messages_view' THEN (SELECT COUNT(*) FROM senior_messages_view)
        ELSE 0
    END AS 記錄數
FROM (
    SELECT 'student_view' AS view_name
    UNION ALL SELECT 'teacher_view'
    UNION ALL SELECT 'private_chat_history_view'
    UNION ALL SELECT 'group_messages_view'
    UNION ALL SELECT 'chat_groups_view'
    UNION ALL SELECT 'group_members_view'
    UNION ALL SELECT 'user_activity_view'
    UNION ALL SELECT 'unread_notifications_view'
    UNION ALL SELECT 'notification_sent_log_view'
    UNION ALL SELECT 'message_likes_view'
    UNION ALL SELECT 'senior_messages_view'
) AS v;

-- =====================================================
-- 4. 外鍵關係檢查
-- =====================================================

SELECT '📊 4. 外鍵關係檢查' AS 章節;

SELECT 
    TABLE_NAME AS 表名,
    COLUMN_NAME AS 欄位,
    REFERENCED_TABLE_NAME AS 引用表,
    REFERENCED_COLUMN_NAME AS 引用欄位,
    CASE 
        WHEN CONSTRAINT_NAME IS NOT NULL THEN '✅ 已設置'
        ELSE '⚠️ 未設置'
    END AS 狀態
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
AND (
    (TABLE_NAME = 'student_normalized' AND COLUMN_NAME = 'user_id' AND REFERENCED_TABLE_NAME = 'user')
    OR (TABLE_NAME = 'student_normalized' AND COLUMN_NAME = 'department_id' AND REFERENCED_TABLE_NAME = 'departments')
    OR (TABLE_NAME = 'student_normalized' AND COLUMN_NAME = 'grade_id' AND REFERENCED_TABLE_NAME = 'grades')
    OR (TABLE_NAME = 'teacher_normalized' AND COLUMN_NAME = 'user_id' AND REFERENCED_TABLE_NAME = 'user')
    OR (TABLE_NAME = 'teacher_normalized' AND COLUMN_NAME = 'department_id' AND REFERENCED_TABLE_NAME = 'departments')
    OR (TABLE_NAME = 'enrollment_applications_normalized' AND COLUMN_NAME = 'status_id' AND REFERENCED_TABLE_NAME = 'application_statuses')
    OR (TABLE_NAME = 'enrollment_applications_normalized' AND COLUMN_NAME = 'identity_id' AND REFERENCED_TABLE_NAME = 'identities')
    OR (TABLE_NAME = 'enrollment_applications_normalized' AND COLUMN_NAME = 'gender_id' AND REFERENCED_TABLE_NAME = 'genders')
    OR (TABLE_NAME = 'enrollment_applications_normalized' AND COLUMN_NAME = 'current_grade_id' AND REFERENCED_TABLE_NAME = 'grades')
    OR (TABLE_NAME = 'enrollment_applications_normalized' AND COLUMN_NAME = 'recommended_teacher_user_id' AND REFERENCED_TABLE_NAME = 'teacher_normalized')
    OR (TABLE_NAME = 'cooperation_applications_normalized' AND COLUMN_NAME = 'teacher_user_id' AND REFERENCED_TABLE_NAME = 'teacher_normalized')
    OR (TABLE_NAME = 'cooperation_applications_normalized' AND COLUMN_NAME = 'department_id' AND REFERENCED_TABLE_NAME = 'departments')
    OR (TABLE_NAME = 'cooperation_applications_normalized' AND COLUMN_NAME = 'company_id' AND REFERENCED_TABLE_NAME = 'companies')
    OR (TABLE_NAME = 'cooperation_applications_normalized' AND COLUMN_NAME = 'status_id' AND REFERENCED_TABLE_NAME = 'application_statuses')
)
ORDER BY TABLE_NAME, COLUMN_NAME;

-- 檢查缺失的外鍵
SELECT 
    '缺失的外鍵檢查' AS 檢查項目,
    'enrollment_applications_normalized.recommended_teacher_user_id → teacher_normalized(user_id)' AS 外鍵,
    CASE 
        WHEN EXISTS(
            SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'enrollment_applications_normalized'
            AND COLUMN_NAME = 'recommended_teacher_user_id'
            AND REFERENCED_TABLE_NAME = 'teacher_normalized'
        ) THEN '✅ 已設置'
        ELSE '⚠️ 未設置'
    END AS 狀態
UNION ALL
SELECT 
    '缺失的外鍵檢查',
    'cooperation_applications_normalized.teacher_user_id → teacher_normalized(user_id)',
    CASE 
        WHEN EXISTS(
            SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'cooperation_applications_normalized'
            AND COLUMN_NAME = 'teacher_user_id'
            AND REFERENCED_TABLE_NAME = 'teacher_normalized'
        ) THEN '✅ 已設置'
        ELSE '⚠️ 未設置'
    END
UNION ALL
SELECT 
    '缺失的外鍵檢查',
    'cooperation_applications_normalized.department_id → departments(id)',
    CASE 
        WHEN EXISTS(
            SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'cooperation_applications_normalized'
            AND COLUMN_NAME = 'department_id'
            AND REFERENCED_TABLE_NAME = 'departments'
        ) THEN '✅ 已設置'
        ELSE '⚠️ 未設置'
    END;

-- =====================================================
-- 5. 數據一致性檢查
-- =====================================================

SELECT '📊 5. 數據一致性檢查' AS 章節;

SELECT 
    'student 數據一致性' AS 檢查項目,
    (SELECT COUNT(*) FROM student WHERE user_id IS NOT NULL) AS 原始表記錄數,
    (SELECT COUNT(*) FROM student_normalized) AS 正規化表記錄數,
    CASE 
        WHEN (SELECT COUNT(*) FROM student WHERE user_id IS NOT NULL) = 
             (SELECT COUNT(*) FROM student_normalized)
        THEN '✅ 一致'
        ELSE '⚠️ 不一致'
    END AS 狀態
UNION ALL
SELECT 
    'teacher 數據一致性',
    (SELECT COUNT(*) FROM teacher WHERE user_id IS NOT NULL),
    (SELECT COUNT(*) FROM teacher_normalized),
    CASE 
        WHEN (SELECT COUNT(*) FROM teacher WHERE user_id IS NOT NULL) = 
             (SELECT COUNT(*) FROM teacher_normalized)
        THEN '✅ 一致'
        ELSE '⚠️ 不一致'
    END;

-- =====================================================
-- 6. 驗證總結
-- =====================================================

SELECT '📊 6. 驗證總結' AS 章節;

SELECT 
    '總檢查項' AS 檢查項目,
    (
        (SELECT COUNT(*) FROM (
            SELECT 'departments' UNION ALL SELECT 'education_systems' UNION ALL SELECT 'application_statuses'
            UNION ALL SELECT 'identities' UNION ALL SELECT 'genders' UNION ALL SELECT 'grades'
            UNION ALL SELECT 'companies' UNION ALL SELECT 'message_types' UNION ALL SELECT 'role_types'
            UNION ALL SELECT 'notification_types'
        ) AS ref_tables) +
        (SELECT COUNT(*) FROM (
            SELECT 'student_normalized' UNION ALL SELECT 'teacher_normalized' UNION ALL SELECT 'chat_groups_normalized'
            UNION ALL SELECT 'group_members_normalized' UNION ALL SELECT 'private_chat_history_normalized'
            UNION ALL SELECT 'group_messages_normalized' UNION ALL SELECT 'enrollment_applications_normalized'
            UNION ALL SELECT 'enrollment_preferences' UNION ALL SELECT 'cooperation_applications_normalized'
            UNION ALL SELECT 'ai_chat_history_normalized' UNION ALL SELECT 'user_activity_normalized'
            UNION ALL SELECT 'unread_notifications_normalized' UNION ALL SELECT 'notification_sent_log_normalized'
            UNION ALL SELECT 'message_likes_normalized' UNION ALL SELECT 'senior_messages_normalized'
        ) AS norm_tables) +
        (SELECT COUNT(*) FROM (
            SELECT 'student_view' UNION ALL SELECT 'teacher_view' UNION ALL SELECT 'private_chat_history_view'
            UNION ALL SELECT 'group_messages_view' UNION ALL SELECT 'chat_groups_view'
            UNION ALL SELECT 'group_members_view' UNION ALL SELECT 'user_activity_view'
            UNION ALL SELECT 'unread_notifications_view' UNION ALL SELECT 'notification_sent_log_view'
            UNION ALL SELECT 'message_likes_view' UNION ALL SELECT 'senior_messages_view'
        ) AS views) +
        14 +  -- 外鍵檢查項目數
        2      -- 數據一致性檢查項目數
    ) AS 數量;

SELECT 
    '✅ 通過' AS 檢查項目,
    (
        (SELECT COUNT(*) FROM information_schema.tables 
         WHERE table_schema = DATABASE() 
         AND table_name IN ('departments', 'education_systems', 'application_statuses', 'identities', 
                           'genders', 'grades', 'companies', 'message_types', 'role_types', 'notification_types')) +
        (SELECT COUNT(*) FROM information_schema.tables 
         WHERE table_schema = DATABASE() 
         AND table_name LIKE '%_normalized' OR table_name = 'enrollment_preferences') +
        (SELECT COUNT(*) FROM information_schema.views 
         WHERE table_schema = DATABASE() 
         AND table_name LIKE '%_view') +
        (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = DATABASE()
         AND REFERENCED_TABLE_NAME IS NOT NULL
         AND ((TABLE_NAME = 'student_normalized' AND COLUMN_NAME IN ('user_id', 'department_id', 'grade_id'))
              OR (TABLE_NAME = 'teacher_normalized' AND COLUMN_NAME IN ('user_id', 'department_id'))
              OR (TABLE_NAME = 'enrollment_applications_normalized' AND COLUMN_NAME IN ('status_id', 'identity_id', 'gender_id', 'current_grade_id', 'recommended_teacher_user_id'))
              OR (TABLE_NAME = 'cooperation_applications_normalized' AND COLUMN_NAME IN ('teacher_user_id', 'department_id', 'company_id', 'status_id')))) +
        (CASE WHEN (SELECT COUNT(*) FROM student WHERE user_id IS NOT NULL) = 
                   (SELECT COUNT(*) FROM student_normalized) THEN 1 ELSE 0 END) +
        (CASE WHEN (SELECT COUNT(*) FROM teacher WHERE user_id IS NOT NULL) = 
                   (SELECT COUNT(*) FROM teacher_normalized) THEN 1 ELSE 0 END)
    ) AS 數量;

SELECT 
    '⚠️ 警告' AS 檢查項目,
    (
        (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = DATABASE()
         AND ((TABLE_NAME = 'enrollment_applications_normalized' AND COLUMN_NAME = 'recommended_teacher_user_id' AND REFERENCED_TABLE_NAME IS NULL)
              OR (TABLE_NAME = 'cooperation_applications_normalized' AND COLUMN_NAME = 'teacher_user_id' AND REFERENCED_TABLE_NAME IS NULL)
              OR (TABLE_NAME = 'cooperation_applications_normalized' AND COLUMN_NAME = 'department_id' AND REFERENCED_TABLE_NAME IS NULL))) +
        (CASE WHEN (SELECT COUNT(*) FROM teacher WHERE user_id IS NOT NULL) != 
                   (SELECT COUNT(*) FROM teacher_normalized) THEN 1 ELSE 0 END)
    ) AS 數量;

SELECT 
    '通過率' AS 檢查項目,
    CONCAT(
        ROUND(
            (
                (SELECT COUNT(*) FROM information_schema.tables 
                 WHERE table_schema = DATABASE() 
                 AND table_name IN ('departments', 'education_systems', 'application_statuses', 'identities', 
                                   'genders', 'grades', 'companies', 'message_types', 'role_types', 'notification_types')) +
                (SELECT COUNT(*) FROM information_schema.tables 
                 WHERE table_schema = DATABASE() 
                 AND (table_name LIKE '%_normalized' OR table_name = 'enrollment_preferences')) +
                (SELECT COUNT(*) FROM information_schema.views 
                 WHERE table_schema = DATABASE() 
                 AND table_name LIKE '%_view') +
                (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                 AND REFERENCED_TABLE_NAME IS NOT NULL) +
                (CASE WHEN (SELECT COUNT(*) FROM student WHERE user_id IS NOT NULL) = 
                           (SELECT COUNT(*) FROM student_normalized) THEN 1 ELSE 0 END) +
                (CASE WHEN (SELECT COUNT(*) FROM teacher WHERE user_id IS NOT NULL) = 
                           (SELECT COUNT(*) FROM teacher_normalized) THEN 1 ELSE 0 END)
            ) / 62 * 100,
            1
        ),
        '%'
    ) AS 數量;

SELECT '✅ 驗證完成！' AS message;

