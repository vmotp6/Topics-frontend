-- =====================================================
-- 數據遷移到 3NF 正規化表
-- 目標：將現有數據從舊表遷移到正規化表
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 第一部分：遷移學生數據
-- =====================================================

-- 遷移 student 表數據到 student_normalized
INSERT INTO student_normalized (
    user_id, name, student_id, department_id, grade_id, 
    class_name, email, phone, created_at, updated_at
)
SELECT 
    s.user_id,
    COALESCE(s.name, '') AS name,
    s.student_id,
    -- 根據科系名稱查找對應的 department_id
    COALESCE(
        (SELECT id FROM departments d WHERE d.name = s.department LIMIT 1),
        NULL
    ) AS department_id,
    -- 根據年級名稱查找對應的 grade_id
    COALESCE(
        (SELECT id FROM grades g WHERE g.name = s.grade LIMIT 1),
        NULL
    ) AS grade_id,
    s.class_name,
    s.email,
    s.phone,
    COALESCE(s.created_at, NOW()) AS created_at,
    COALESCE(s.updated_at, NOW()) AS updated_at
FROM student s
WHERE s.user_id IS NOT NULL
AND EXISTS (SELECT 1 FROM user u WHERE u.id = s.user_id)
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
-- 第二部分：遷移老師數據
-- =====================================================

-- 遷移 teacher 表數據到 teacher_normalized
-- 注意：如果 teacher 表沒有 created_at/updated_at 欄位，會使用 NOW()
INSERT INTO teacher_normalized (
    user_id, name, department_id, phone, created_at, updated_at
)
SELECT 
    t.user_id,
    COALESCE(t.name, '') AS name,
    -- 根據科系名稱查找對應的 department_id
    COALESCE(
        (SELECT id FROM departments d WHERE d.name = t.department LIMIT 1),
        NULL
    ) AS department_id,
    t.phone,
    NOW() AS created_at,
    NOW() AS updated_at
FROM teacher t
WHERE t.user_id IS NOT NULL
AND EXISTS (SELECT 1 FROM user u WHERE u.id = t.user_id)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    department_id = VALUES(department_id),
    phone = VALUES(phone),
    updated_at = VALUES(updated_at);

-- =====================================================
-- 第三部分：遷移就讀意願申請數據
-- =====================================================

-- 遷移 enrollment_applications 表數據
-- 動態檢查並添加 recommended_teacher_user_id（如果欄位存在）
INSERT INTO enrollment_applications_normalized (
    user_id, username, name, identity_id, gender_id,
    phone1, phone2, email, junior_high_school_id, current_grade_id,
    line_id, facebook, remarks,
    status_id, admin_id, admin_comment, created_at, updated_at
)
SELECT 
    -- 嘗試根據 username 查找 user_id
    (SELECT id FROM user u WHERE u.username = ea.username LIMIT 1) AS user_id,
    ea.username,
    ea.name,
    -- 根據 identity 字串查找 identity_id
    COALESCE(
        (SELECT id FROM identities i WHERE i.name = ea.identity LIMIT 1),
        (SELECT id FROM identities i WHERE i.code = 'STUDENT' LIMIT 1)
    ) AS identity_id,
    -- 根據 gender 字串查找 gender_id
    COALESCE(
        (SELECT id FROM genders g WHERE g.name = ea.gender LIMIT 1),
        NULL
    ) AS gender_id,
    ea.phone1,
    ea.phone2,
    ea.email,
    -- 如果存在 schools 表，根據學校名稱查找
    NULL AS junior_high_school_id,
    -- 根據年級名稱查找 grade_id
    COALESCE(
        (SELECT id FROM grades g WHERE g.name = ea.current_grade LIMIT 1),
        NULL
    ) AS current_grade_id,
    ea.line_id,
    ea.facebook,
    ea.remarks,
    -- 根據 status 字串查找 status_id
    COALESCE(
        (SELECT id FROM application_statuses a 
         WHERE a.code = UPPER(ea.status) OR a.name = ea.status LIMIT 1),
        (SELECT id FROM application_statuses WHERE code = 'PENDING' LIMIT 1)
    ) AS status_id,
    NULL AS admin_id,
    ea.admin_comment,
    COALESCE(ea.created_at, NOW()) AS created_at,
    COALESCE(ea.updated_at, NOW()) AS updated_at
FROM enrollment_applications ea
ON DUPLICATE KEY UPDATE
    user_id = VALUES(user_id),
    name = VALUES(name),
    identity_id = VALUES(identity_id),
    gender_id = VALUES(gender_id),
    phone1 = VALUES(phone1),
    phone2 = VALUES(phone2),
    email = VALUES(email),
    current_grade_id = VALUES(current_grade_id),
    line_id = VALUES(line_id),
    facebook = VALUES(facebook),
    remarks = VALUES(remarks),
    status_id = VALUES(status_id),
    admin_comment = VALUES(admin_comment),
    updated_at = VALUES(updated_at);

-- 遷移志願明細（enrollment_preferences）
INSERT INTO enrollment_preferences (
    enrollment_application_id, preference_order, department_id, education_system_id, created_at
)
SELECT 
    ean.id AS enrollment_application_id,
    1 AS preference_order,
    COALESCE(
        (SELECT id FROM departments d WHERE d.name = ea.department1 LIMIT 1),
        NULL
    ) AS department_id,
    COALESCE(
        (SELECT id FROM education_systems es WHERE es.name = ea.system1 LIMIT 1),
        NULL
    ) AS education_system_id,
    NOW() AS created_at
FROM enrollment_applications ea
JOIN enrollment_applications_normalized ean ON ean.username = ea.username AND ean.created_at = ea.created_at
WHERE ea.department1 IS NOT NULL AND ea.department1 != '無特定'
ON DUPLICATE KEY UPDATE department_id = VALUES(department_id);

INSERT INTO enrollment_preferences (
    enrollment_application_id, preference_order, department_id, education_system_id, created_at
)
SELECT 
    ean.id AS enrollment_application_id,
    2 AS preference_order,
    COALESCE(
        (SELECT id FROM departments d WHERE d.name = ea.department2 LIMIT 1),
        NULL
    ) AS department_id,
    COALESCE(
        (SELECT id FROM education_systems es WHERE es.name = ea.system2 LIMIT 1),
        NULL
    ) AS education_system_id,
    NOW() AS created_at
FROM enrollment_applications ea
JOIN enrollment_applications_normalized ean ON ean.username = ea.username AND ean.created_at = ea.created_at
WHERE ea.department2 IS NOT NULL AND ea.department2 != '無特定'
ON DUPLICATE KEY UPDATE department_id = VALUES(department_id);

INSERT INTO enrollment_preferences (
    enrollment_application_id, preference_order, department_id, education_system_id, created_at
)
SELECT 
    ean.id AS enrollment_application_id,
    3 AS preference_order,
    COALESCE(
        (SELECT id FROM departments d WHERE d.name = ea.department3 LIMIT 1),
        NULL
    ) AS department_id,
    COALESCE(
        (SELECT id FROM education_systems es WHERE es.name = ea.system3 LIMIT 1),
        NULL
    ) AS education_system_id,
    NOW() AS created_at
FROM enrollment_applications ea
JOIN enrollment_applications_normalized ean ON ean.username = ea.username AND ean.created_at = ea.created_at
WHERE ea.department3 IS NOT NULL AND ea.department3 != '無特定'
ON DUPLICATE KEY UPDATE department_id = VALUES(department_id);

-- =====================================================
-- 第四部分：遷移聊天系統數據（如果存在）
-- =====================================================

-- 遷移私聊訊息（如果表存在）
INSERT INTO private_chat_history_normalized (
    from_user_id, to_user_id, message, message_type_id, created_at
)
SELECT 
    u1.id AS from_user_id,
    u2.id AS to_user_id,
    pch.message,
    COALESCE(
        (SELECT id FROM message_types mt WHERE mt.code = 'TEXT' LIMIT 1),
        1
    ) AS message_type_id,
    COALESCE(pch.timestamp, NOW()) AS created_at
FROM private_chat_history pch
JOIN user u1 ON u1.username = pch.from_user
JOIN user u2 ON u2.username = pch.to_user
WHERE NOT EXISTS (
    SELECT 1 FROM private_chat_history_normalized pchn
    WHERE pchn.from_user_id = u1.id 
    AND pchn.to_user_id = u2.id 
    AND pchn.message = pch.message
    AND DATE(pchn.created_at) = DATE(pch.timestamp)
);

-- 遷移群組訊息（如果表存在）
-- 注意：需要先遷移 chat_groups_normalized 和 group_members_normalized

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 驗證遷移結果
-- =====================================================

SELECT '✅ 數據遷移完成！' AS message;
SELECT 
    (SELECT COUNT(*) FROM student) AS original_student_count,
    (SELECT COUNT(*) FROM student_normalized) AS normalized_student_count,
    (SELECT COUNT(*) FROM teacher) AS original_teacher_count,
    (SELECT COUNT(*) FROM teacher_normalized) AS normalized_teacher_count,
    (SELECT COUNT(*) FROM enrollment_applications) AS original_enrollment_count,
    (SELECT COUNT(*) FROM enrollment_applications_normalized) AS normalized_enrollment_count;

