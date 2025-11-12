-- =====================================================
-- 完整資料庫第三正規化（3NF）數據遷移腳本
-- 將現有數據遷移到正規化表
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 第一部分：遷移 student 表數據
-- =====================================================

INSERT IGNORE INTO student_normalized (
    user_id, name, student_id, department_id, grade_id, class_name, email, phone, created_at, updated_at
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    student_id = VALUES(student_id),
    department_id = VALUES(department_id),
    grade_id = VALUES(grade_id),
    class_name = VALUES(class_name),
    email = VALUES(email),
    phone = VALUES(phone),
    updated_at = VALUES(updated_at)
SELECT 
    s.user_id,
    s.name,
    s.student_id,
    COALESCE(
        (SELECT id FROM departments WHERE name = s.department LIMIT 1),
        (SELECT id FROM departments WHERE name LIKE CONCAT('%', s.department, '%') LIMIT 1),
        NULL
    ) AS department_id,
    COALESCE(
        (SELECT id FROM grades WHERE name = s.grade LIMIT 1),
        (SELECT id FROM grades WHERE name LIKE CONCAT('%', s.grade, '%') LIMIT 1),
        NULL
    ) AS grade_id,
    s.class_name,
    s.email,
    s.phone,
    s.created_at,
    s.updated_at
FROM student s
WHERE NOT EXISTS (
    SELECT 1 FROM student_normalized sn 
    WHERE sn.user_id = s.user_id
);

-- =====================================================
-- 第二部分：遷移 teacher 表數據
-- =====================================================

INSERT IGNORE INTO teacher_normalized (
    user_id, name, department_id, phone, created_at, updated_at
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    department_id = VALUES(department_id),
    phone = VALUES(phone),
    updated_at = VALUES(updated_at)
SELECT 
    t.user_id,
    t.name,
    COALESCE(
        (SELECT id FROM departments WHERE name = t.department LIMIT 1),
        (SELECT id FROM departments WHERE name LIKE CONCAT('%', t.department, '%') LIMIT 1),
        NULL
    ) AS department_id,
    t.phone,
    t.created_at,
    COALESCE(t.updated_at, t.created_at) AS updated_at
FROM teacher t
WHERE NOT EXISTS (
    SELECT 1 FROM teacher_normalized tn 
    WHERE tn.user_id = t.user_id
);

-- =====================================================
-- 第三部分：遷移聊天系統數據
-- =====================================================

-- 3.1 遷移聊天群組數據
INSERT IGNORE INTO chat_groups_normalized (
    id, group_name, created_by_user_id, department_id, created_at
)
SELECT 
    cg.id,
    cg.group_name,
    COALESCE(
        (SELECT id FROM user WHERE username = cg.created_by LIMIT 1),
        NULL
    ) AS created_by_user_id,
    COALESCE(
        (SELECT id FROM departments WHERE name = cg.department LIMIT 1),
        (SELECT id FROM departments WHERE name LIKE CONCAT('%', cg.department, '%') LIMIT 1),
        NULL
    ) AS department_id,
    cg.created_at
FROM chat_groups cg
WHERE NOT EXISTS (
    SELECT 1 FROM chat_groups_normalized cgn 
    WHERE cgn.id = cg.id
)
AND EXISTS (
    SELECT 1 FROM user WHERE username = cg.created_by
);

-- 3.2 遷移群組成員數據
INSERT IGNORE INTO group_members_normalized (
    group_id, user_id, role_type_id, joined_at
)
SELECT 
    gm.group_id,
    COALESCE(
        (SELECT id FROM user WHERE username = gm.username LIMIT 1),
        NULL
    ) AS user_id,
    COALESCE(
        (SELECT id FROM role_types WHERE code = 
            CASE gm.role
                WHEN '老師' THEN 'TEACHER'
                WHEN '學生' THEN 'STUDENT'
                WHEN '管理員' THEN 'ADMIN'
                ELSE 'MEMBER'
            END
        ),
        (SELECT id FROM role_types WHERE code = 'MEMBER')
    ) AS role_type_id,
    gm.joined_at
FROM group_members gm
WHERE NOT EXISTS (
    SELECT 1 FROM group_members_normalized gmn 
    WHERE gmn.group_id = gm.group_id 
    AND gmn.user_id = (SELECT id FROM user WHERE username = gm.username LIMIT 1)
)
AND EXISTS (
    SELECT 1 FROM user WHERE username = gm.username
)
AND EXISTS (
    SELECT 1 FROM chat_groups_normalized WHERE id = gm.group_id
);

-- 3.3 遷移私聊訊息數據
INSERT IGNORE INTO private_chat_history_normalized (
    from_user_id, to_user_id, message, message_type_id, created_at
)
SELECT 
    COALESCE((SELECT id FROM user WHERE username = pch.from_user LIMIT 1), NULL) AS from_user_id,
    COALESCE((SELECT id FROM user WHERE username = pch.to_user LIMIT 1), NULL) AS to_user_id,
    pch.message,
    (SELECT id FROM message_types WHERE code = 'TEXT') AS message_type_id,
    pch.timestamp AS created_at
FROM private_chat_history pch
WHERE NOT EXISTS (
    SELECT 1 FROM private_chat_history_normalized pchn 
    WHERE pchn.from_user_id = (SELECT id FROM user WHERE username = pch.from_user LIMIT 1)
    AND pchn.to_user_id = (SELECT id FROM user WHERE username = pch.to_user LIMIT 1)
    AND pchn.created_at = pch.timestamp
)
AND EXISTS (
    SELECT 1 FROM user WHERE username = pch.from_user
)
AND EXISTS (
    SELECT 1 FROM user WHERE username = pch.to_user
);

-- 3.4 遷移群組訊息數據
INSERT IGNORE INTO group_messages_normalized (
    group_id, from_user_id, message, message_type_id, created_at
)
SELECT 
    gmm.group_id,
    COALESCE((SELECT id FROM user WHERE username = gmm.from_user LIMIT 1), NULL) AS from_user_id,
    gmm.message,
    (SELECT id FROM message_types WHERE code = 'TEXT') AS message_type_id,
    gmm.timestamp AS created_at
FROM group_messages gmm
WHERE NOT EXISTS (
    SELECT 1 FROM group_messages_normalized gmmn 
    WHERE gmmn.group_id = gmm.group_id
    AND gmmn.from_user_id = (SELECT id FROM user WHERE username = gmm.from_user LIMIT 1)
    AND gmmn.created_at = gmm.timestamp
)
AND EXISTS (
    SELECT 1 FROM user WHERE username = gmm.from_user
)
AND EXISTS (
    SELECT 1 FROM chat_groups_normalized WHERE id = gmm.group_id
);

-- =====================================================
-- 第四部分：遷移 AI 聊天記錄（如果表存在）
-- =====================================================

INSERT IGNORE INTO ai_chat_history_normalized (
    user_id, message_type_id, message_content, created_at
)
SELECT 
    ach.user_id,
    COALESCE(
        (SELECT id FROM message_types WHERE code = 
            CASE ach.message_type
                WHEN 'user' THEN 'TEXT'
                WHEN 'ai' THEN 'TEXT'
                ELSE 'TEXT'
            END
        ),
        (SELECT id FROM message_types WHERE code = 'TEXT')
    ) AS message_type_id,
    ach.message_content,
    ach.created_at
FROM ai_chat_history ach
WHERE NOT EXISTS (
    SELECT 1 FROM ai_chat_history_normalized achn 
    WHERE achn.user_id = ach.user_id
    AND achn.created_at = ach.created_at
);

-- =====================================================
-- 第五部分：遷移就讀意願和產學合作數據（補充原有腳本）
-- =====================================================

-- 5.1 遷移就讀意願申請數據（如果 enrollment_applications 表存在）
INSERT IGNORE INTO enrollment_applications_normalized (
    username, name, identity_id, gender_id, phone1, phone2, email,
    junior_high_school_id, current_grade_id, line_id, facebook, remarks,
    status_id, admin_comment, created_at, updated_at
)
SELECT 
    ea.username,
    ea.name,
    CASE ea.identity 
        WHEN '學生' THEN (SELECT id FROM identities WHERE code = 'STUDENT')
        WHEN '家長' THEN (SELECT id FROM identities WHERE code = 'PARENT')
        ELSE (SELECT id FROM identities WHERE code = 'STUDENT')
    END AS identity_id,
    CASE ea.gender
        WHEN '男' THEN (SELECT id FROM genders WHERE code = 'MALE')
        WHEN '女' THEN (SELECT id FROM genders WHERE code = 'FEMALE')
        ELSE NULL
    END AS gender_id,
    ea.phone1,
    ea.phone2,
    ea.email,
    (SELECT id FROM schools WHERE name = ea.junior_high LIMIT 1) AS junior_high_school_id,
    CASE ea.current_grade
        WHEN '國一' THEN (SELECT id FROM grades WHERE code = 'JUNIOR_1')
        WHEN '國二' THEN (SELECT id FROM grades WHERE code = 'JUNIOR_2')
        WHEN '國三' THEN (SELECT id FROM grades WHERE code = 'JUNIOR_3')
        WHEN '已畢業' THEN (SELECT id FROM grades WHERE code = 'JUNIOR_GRADUATED')
        ELSE NULL
    END AS current_grade_id,
    ea.line_id,
    ea.facebook,
    ea.remarks,
    CASE ea.status
        WHEN 'pending' THEN (SELECT id FROM application_statuses WHERE code = 'PENDING')
        WHEN 'contacted' THEN (SELECT id FROM application_statuses WHERE code = 'CONTACTED')
        WHEN 'enrolled' THEN (SELECT id FROM application_statuses WHERE code = 'ENROLLED')
        ELSE (SELECT id FROM application_statuses WHERE code = 'PENDING')
    END AS status_id,
    ea.admin_comment,
    ea.created_at,
    ea.updated_at
FROM enrollment_applications ea
WHERE NOT EXISTS (
    SELECT 1 FROM enrollment_applications_normalized ean 
    WHERE ean.username = ea.username 
    AND ABS(TIMESTAMPDIFF(SECOND, ean.created_at, ea.created_at)) < 5
);

-- 5.2 遷移就讀意願明細數據（補充，使用更靈活的匹配）
INSERT IGNORE INTO enrollment_preferences (
    enrollment_application_id, preference_order, department_id, education_system_id
)
SELECT 
    ean.id,
    1 AS preference_order,
    COALESCE(
        (SELECT id FROM departments WHERE name = ea.intention1 LIMIT 1),
        (SELECT id FROM departments WHERE name LIKE CONCAT('%', ea.intention1, '%') LIMIT 1),
        NULL
    ) AS department_id,
    COALESCE(
        (SELECT id FROM education_systems WHERE name = ea.system1 LIMIT 1),
        (SELECT id FROM education_systems WHERE name LIKE CONCAT('%', ea.system1, '%') LIMIT 1)
    ) AS education_system_id
FROM enrollment_applications ea
JOIN enrollment_applications_normalized ean ON ean.username = ea.username 
    AND ABS(TIMESTAMPDIFF(SECOND, ean.created_at, ea.created_at)) < 5
WHERE ea.intention1 IS NOT NULL AND ea.intention1 != '無特定' AND ea.intention1 != ''
    AND ea.system1 IS NOT NULL AND ea.system1 != ''
    AND NOT EXISTS (
        SELECT 1 FROM enrollment_preferences ep 
        WHERE ep.enrollment_application_id = ean.id AND ep.preference_order = 1
    )
UNION ALL
SELECT 
    ean.id,
    2 AS preference_order,
    COALESCE(
        (SELECT id FROM departments WHERE name = ea.intention2 LIMIT 1),
        (SELECT id FROM departments WHERE name LIKE CONCAT('%', ea.intention2, '%') LIMIT 1),
        NULL
    ) AS department_id,
    COALESCE(
        (SELECT id FROM education_systems WHERE name = ea.system2 LIMIT 1),
        (SELECT id FROM education_systems WHERE name LIKE CONCAT('%', ea.system2, '%') LIMIT 1)
    ) AS education_system_id
FROM enrollment_applications ea
JOIN enrollment_applications_normalized ean ON ean.username = ea.username 
    AND ABS(TIMESTAMPDIFF(SECOND, ean.created_at, ea.created_at)) < 5
WHERE ea.intention2 IS NOT NULL AND ea.intention2 != '無特定' AND ea.intention2 != ''
    AND ea.system2 IS NOT NULL AND ea.system2 != ''
    AND NOT EXISTS (
        SELECT 1 FROM enrollment_preferences ep 
        WHERE ep.enrollment_application_id = ean.id AND ep.preference_order = 2
    )
UNION ALL
SELECT 
    ean.id,
    3 AS preference_order,
    COALESCE(
        (SELECT id FROM departments WHERE name = ea.intention3 LIMIT 1),
        (SELECT id FROM departments WHERE name LIKE CONCAT('%', ea.intention3, '%') LIMIT 1),
        NULL
    ) AS department_id,
    COALESCE(
        (SELECT id FROM education_systems WHERE name = ea.system3 LIMIT 1),
        (SELECT id FROM education_systems WHERE name LIKE CONCAT('%', ea.system3, '%') LIMIT 1)
    ) AS education_system_id
FROM enrollment_applications ea
JOIN enrollment_applications_normalized ean ON ean.username = ea.username 
    AND ABS(TIMESTAMPDIFF(SECOND, ean.created_at, ea.created_at)) < 5
WHERE ea.intention3 IS NOT NULL AND ea.intention3 != '無特定' AND ea.intention3 != ''
    AND ea.system3 IS NOT NULL AND ea.system3 != ''
    AND NOT EXISTS (
        SELECT 1 FROM enrollment_preferences ep 
        WHERE ep.enrollment_application_id = ean.id AND ep.preference_order = 3
    );

-- 5.3 遷移產學合作申請數據（如果 cooperation_applications 表存在）
INSERT IGNORE INTO companies (name, contact_person, phone, email, address)
SELECT DISTINCT
    ca.company_name,
    ca.company_contact,
    ca.company_phone,
    NULL AS email,
    NULL AS address
FROM cooperation_applications ca
WHERE NOT EXISTS (
    SELECT 1 FROM companies c 
    WHERE c.name = ca.company_name 
    AND c.contact_person = ca.company_contact
);

INSERT IGNORE INTO cooperation_applications_normalized (
    teacher_user_id, department_id, company_id, application_date, approval_number,
    principal_investigator, regulations_read, project_amount, admin_fee_percentage,
    project_title, expected_outcomes, project_timeline, has_intellectual_property,
    future_tech_transfer, tech_transfer_amount, has_derived_benefits, benefits_amount,
    use_university_venue, venue_fees_in_proposal, employ_disadvantaged_students,
    use_standard_contract, contract_file_path, proposal_file_path, status_id,
    admin_comment, review_date, created_at, updated_at
)
SELECT 
    COALESCE(
        (SELECT tn.user_id FROM teacher_normalized tn 
         JOIN user u ON tn.user_id = u.id 
         WHERE u.username = ca.teacher_username LIMIT 1),
        NULL
    ) AS teacher_user_id,
    COALESCE(
        (SELECT id FROM departments WHERE name = ca.department LIMIT 1),
        (SELECT id FROM departments WHERE name LIKE CONCAT('%', ca.department, '%') LIMIT 1),
        NULL
    ) AS department_id,
    (SELECT id FROM companies WHERE name = ca.company_name AND contact_person = ca.company_contact LIMIT 1) AS company_id,
    ca.application_date,
    ca.approval_number,
    ca.principal_investigator,
    CASE WHEN ca.regulations_read = 'yes' THEN TRUE ELSE FALSE END,
    ca.project_amount,
    ca.admin_fee_percentage,
    ca.project_title,
    ca.expected_outcomes,
    ca.project_timeline,
    CASE WHEN ca.has_intellectual_property = 'yes' THEN TRUE ELSE FALSE END,
    CASE WHEN ca.future_tech_transfer = 'yes' THEN TRUE ELSE FALSE END,
    ca.tech_transfer_amount,
    CASE WHEN ca.has_derived_benefits = 'yes' THEN TRUE ELSE FALSE END,
    ca.benefits_amount,
    ca.use_university_venue,
    ca.venue_fees_in_proposal,
    ca.employ_disadvantaged_students,
    ca.use_standard_contract,
    ca.contract_file_path,
    ca.proposal_file_path,
    CASE ca.status
        WHEN 'pending' THEN (SELECT id FROM application_statuses WHERE code = 'PENDING')
        WHEN 'approved' THEN (SELECT id FROM application_statuses WHERE code = 'APPROVED')
        WHEN 'rejected' THEN (SELECT id FROM application_statuses WHERE code = 'REJECTED')
        ELSE (SELECT id FROM application_statuses WHERE code = 'PENDING')
    END AS status_id,
    ca.admin_comment,
    ca.review_date,
    ca.created_at,
    ca.updated_at
FROM cooperation_applications ca
WHERE NOT EXISTS (
    SELECT 1 FROM cooperation_applications_normalized can 
    WHERE can.contract_file_path = ca.contract_file_path
    AND ABS(TIMESTAMPDIFF(SECOND, can.created_at, ca.created_at)) < 5
)
AND EXISTS (
    SELECT 1 FROM teacher_normalized tn 
    JOIN user u ON tn.user_id = u.id 
    WHERE u.username = ca.teacher_username
);

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 驗證遷移結果
-- =====================================================

SELECT '數據遷移完成！' AS message;
SELECT '請檢查以下統計數據確認遷移是否成功：' AS next_step;

-- 顯示遷移統計
SELECT 
    'student' AS table_name,
    (SELECT COUNT(*) FROM student) AS original_count,
    (SELECT COUNT(*) FROM student_normalized) AS normalized_count
UNION ALL
SELECT 
    'teacher' AS table_name,
    (SELECT COUNT(*) FROM teacher) AS original_count,
    (SELECT COUNT(*) FROM teacher_normalized) AS normalized_count
UNION ALL
SELECT 
    'chat_groups' AS table_name,
    (SELECT COUNT(*) FROM chat_groups) AS original_count,
    (SELECT COUNT(*) FROM chat_groups_normalized) AS normalized_count
UNION ALL
SELECT 
    'private_chat_history' AS table_name,
    (SELECT COUNT(*) FROM private_chat_history) AS original_count,
    (SELECT COUNT(*) FROM private_chat_history_normalized) AS normalized_count
UNION ALL
SELECT 
    'group_messages' AS table_name,
    (SELECT COUNT(*) FROM group_messages) AS original_count,
    (SELECT COUNT(*) FROM group_messages_normalized) AS normalized_count;

