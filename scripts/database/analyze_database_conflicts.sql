-- =====================================================
-- 資料庫矛盾分析腳本
-- 用於檢測所有表之間的衝突和不一致
-- =====================================================

USE topics_good;

-- =====================================================
-- 第一部分：檢測重複的表（功能重疊）
-- =====================================================

-- 1. 學校相關表
SELECT '重複表檢測：學校表' AS check_type,
       'schools' AS table1,
       'school_data' AS table2,
       '功能重疊：都是存儲學校資料' AS issue,
       '建議：合併為單一 schools 表' AS recommendation;

-- 檢查兩個表的結構差異
SELECT 'schools 表結構' AS info;
SHOW COLUMNS FROM schools;

SELECT 'school_data 表結構' AS info;
SHOW COLUMNS FROM school_data;

-- 2. 就讀意願相關表
SELECT '重複表檢測：就讀意願表' AS check_type,
       'enrollment_applications' AS table1,
       'enrollment_intention' AS table2,
       '功能重疊：都是存儲就讀意願資料' AS issue,
       '建議：合併為 enrollment_applications_normalized' AS recommendation;

-- =====================================================
-- 第二部分：檢測使用 username 而非 user_id 的表
-- =====================================================

-- 1. user_activity 表
SELECT 'user_activity 表使用 username 而非 user_id' AS issue,
       '應該改為使用 user_id 作為外鍵' AS recommendation;

-- 2. unread_notifications 表
SELECT 'unread_notifications 表使用 username 和 sender_username' AS issue,
       '應該改為使用 user_id 和 sender_user_id' AS recommendation;

-- 3. notification_sent_log 表
SELECT 'notification_sent_log 表使用 username' AS issue,
       '應該改為使用 user_id' AS recommendation;

-- 4. message_likes 表
SELECT 'message_likes 表使用 user_email' AS issue,
       '應該改為使用 user_id（最糟糕，使用 email 而非 username 或 user_id）' AS recommendation;

-- =====================================================
-- 第三部分：檢測缺少外鍵的表
-- =====================================================

-- 1. senior_messages 表缺少正規化
SELECT 'senior_messages 表問題：' AS issue,
       'author_department 和 author_grade 應該是外鍵引用' AS problem1,
       'author_email 應該關聯到 user 表' AS problem2,
       'message_type 應該引用參考表' AS problem3;

-- 2. notification_logs 表
SELECT 'notification_logs 表引用 admission_recommendations 表' AS issue,
       '但 admission_recommendations 表可能不存在或未定義' AS problem;

-- 檢查 admission_recommendations 表是否存在
SELECT 
    CASE 
        WHEN EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'topics_good' AND table_name = 'admission_recommendations')
        THEN 'admission_recommendations 表存在'
        ELSE 'admission_recommendations 表不存在（外鍵會失敗）'
    END AS status;

-- =====================================================
-- 第四部分：檢測命名不一致
-- =====================================================

-- 1. teacher_id vs teacher_user_id
SELECT '命名不一致' AS issue,
       '有些表使用 teacher_id，有些使用 teacher_user_id' AS problem,
       '建議：統一使用 teacher_user_id（指向 teacher_normalized.user_id）' AS recommendation;

-- 2. 學校表引用不一致
SELECT '學校表引用不一致' AS issue,
       '有些表引用 schools 表，有些可能引用 school_data 表' AS problem,
       '建議：統一使用 schools 表' AS recommendation;

-- =====================================================
-- 第五部分：統計所有問題
-- =====================================================

SELECT '=== 資料庫矛盾總結 ===' AS summary;

SELECT 
    '使用 username 而非 user_id 的表' AS category,
    COUNT(*) AS count,
    GROUP_CONCAT(table_name SEPARATOR ', ') AS tables
FROM information_schema.columns
WHERE table_schema = 'topics_good'
    AND column_name = 'username'
    AND table_name NOT IN ('user')  -- 排除 user 表本身
GROUP BY category

UNION ALL

SELECT 
    '使用 email 作為外鍵的表' AS category,
    COUNT(*) AS count,
    GROUP_CONCAT(table_name SEPARATOR ', ') AS tables
FROM information_schema.columns
WHERE table_schema = 'topics_good'
    AND column_name LIKE '%email%'
    AND table_name NOT IN ('user', 'companies', 'schools_contacts')
    AND column_name != 'email'  -- 排除本身就是 email 的欄位
GROUP BY category;

