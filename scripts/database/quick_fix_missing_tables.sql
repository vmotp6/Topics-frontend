-- =====================================================
-- 快速修復缺失的正規化表和視圖
-- 可在 phpMyAdmin 中直接執行
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. 創建缺失的 role_types 表
-- =====================================================
CREATE TABLE IF NOT EXISTS role_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT '角色代碼',
    name VARCHAR(100) NOT NULL COMMENT '角色名稱',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色類型資料表';

INSERT IGNORE INTO role_types (code, name) VALUES
('TEACHER', '老師'),
('STUDENT', '學生'),
('ADMIN', '管理員'),
('STAFF', '行政人員'),
('VENDOR', '廠商'),
('MEMBER', '成員');

-- =====================================================
-- 2. 創建 student_normalized 表
-- =====================================================
CREATE TABLE IF NOT EXISTS student_normalized (
    user_id INT NOT NULL PRIMARY KEY COMMENT '關聯到 user 表（主鍵）',
    name VARCHAR(255) NOT NULL,
    student_id VARCHAR(50) UNIQUE,
    department_id INT NULL COMMENT '關聯到 departments 表',
    grade_id INT NULL COMMENT '關聯到 grades 表',
    class_name VARCHAR(100) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student_id (student_id),
    INDEX idx_department_id (department_id),
    INDEX idx_grade_id (grade_id),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (grade_id) REFERENCES grades(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的學生表';

-- =====================================================
-- 3. 創建 teacher_normalized 表
-- =====================================================
CREATE TABLE IF NOT EXISTS teacher_normalized (
    user_id INT NOT NULL PRIMARY KEY COMMENT '關聯到 user 表（主鍵）',
    name VARCHAR(255) NOT NULL,
    department_id INT NULL COMMENT '關聯到 departments 表',
    phone VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_department_id (department_id),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的老師表';

-- =====================================================
-- 4. 創建 chat_groups_normalized 表
-- =====================================================
CREATE TABLE IF NOT EXISTS chat_groups_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(255) NOT NULL,
    created_by_user_id INT NOT NULL COMMENT '關聯到 user 表',
    department_id INT NULL COMMENT '關聯到 departments 表',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_created_by (created_by_user_id),
    INDEX idx_department_id (department_id),
    FOREIGN KEY (created_by_user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的聊天群組表';

-- =====================================================
-- 5. 創建 group_members_normalized 表
-- =====================================================
CREATE TABLE IF NOT EXISTS group_members_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL COMMENT '關聯到 chat_groups_normalized',
    user_id INT NOT NULL COMMENT '關聯到 user 表',
    role_type_id INT NOT NULL DEFAULT 6 COMMENT '關聯到 role_types 表（預設為成員）',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_group_id (group_id),
    INDEX idx_user_id (user_id),
    UNIQUE KEY unique_group_member (group_id, user_id),
    FOREIGN KEY (group_id) REFERENCES chat_groups_normalized(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (role_type_id) REFERENCES role_types(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的群組成員表';

-- =====================================================
-- 6. 創建 private_chat_history_normalized 表
-- =====================================================
CREATE TABLE IF NOT EXISTS private_chat_history_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_user_id INT NOT NULL COMMENT '關聯到 user 表',
    to_user_id INT NOT NULL COMMENT '關聯到 user 表',
    message TEXT NOT NULL,
    message_type_id INT NOT NULL DEFAULT 1 COMMENT '關聯到 message_types 表',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_from_user (from_user_id),
    INDEX idx_to_user (to_user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_message_type (message_type_id),
    FOREIGN KEY (from_user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (to_user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (message_type_id) REFERENCES message_types(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的私聊訊息表';

-- =====================================================
-- 7. 創建 group_messages_normalized 表
-- =====================================================
CREATE TABLE IF NOT EXISTS group_messages_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL COMMENT '關聯到 chat_groups_normalized',
    from_user_id INT NOT NULL COMMENT '關聯到 user 表',
    message TEXT NOT NULL,
    message_type_id INT NOT NULL DEFAULT 1 COMMENT '關聯到 message_types 表',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_group_id (group_id),
    INDEX idx_from_user (from_user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_message_type (message_type_id),
    FOREIGN KEY (group_id) REFERENCES chat_groups_normalized(id) ON DELETE CASCADE,
    FOREIGN KEY (from_user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (message_type_id) REFERENCES message_types(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的群組訊息表';

-- =====================================================
-- 8. 創建 ai_chat_history_normalized 表
-- =====================================================
CREATE TABLE IF NOT EXISTS ai_chat_history_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '關聯到 user 表',
    question TEXT NOT NULL,
    answer TEXT NULL,
    message_type_id INT NOT NULL DEFAULT 1 COMMENT '關聯到 message_types 表',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_message_type (message_type_id),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (message_type_id) REFERENCES message_types(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的AI聊天記錄表';

-- =====================================================
-- 9. 創建視圖 - student_view
-- =====================================================
CREATE OR REPLACE VIEW student_view AS
SELECT 
    s.user_id AS id,
    s.user_id,
    s.name,
    s.student_id,
    COALESCE(d.name, '') AS department,
    COALESCE(g.name, '') AS grade,
    s.class_name,
    s.email,
    s.phone,
    s.created_at,
    s.updated_at
FROM student_normalized s
LEFT JOIN departments d ON s.department_id = d.id
LEFT JOIN grades g ON s.grade_id = g.id;

-- =====================================================
-- 10. 創建視圖 - teacher_view
-- =====================================================
CREATE OR REPLACE VIEW teacher_view AS
SELECT 
    t.user_id AS id,
    t.user_id,
    t.name,
    COALESCE(d.name, '') AS department,
    t.phone,
    t.created_at,
    t.updated_at
FROM teacher_normalized t
LEFT JOIN departments d ON t.department_id = d.id;

-- =====================================================
-- 11. 創建視圖 - private_chat_history_view
-- =====================================================
CREATE OR REPLACE VIEW private_chat_history_view AS
SELECT 
    p.id,
    u1.username AS from_user,
    u2.username AS to_user,
    p.message,
    COALESCE(rt.name, '用戶') AS role,
    p.created_at AS timestamp
FROM private_chat_history_normalized p
JOIN user u1 ON p.from_user_id = u1.id
JOIN user u2 ON p.to_user_id = u2.id
LEFT JOIN role_types rt ON rt.id = (
    SELECT 
        CASE 
            WHEN EXISTS(SELECT 1 FROM teacher_normalized t WHERE t.user_id = p.from_user_id) 
                THEN (SELECT id FROM role_types WHERE code = 'TEACHER' LIMIT 1)
            WHEN EXISTS(SELECT 1 FROM student_normalized s WHERE s.user_id = p.from_user_id) 
                THEN (SELECT id FROM role_types WHERE code = 'STUDENT' LIMIT 1)
            ELSE (SELECT id FROM role_types WHERE code = 'MEMBER' LIMIT 1)
        END
);

-- =====================================================
-- 12. 創建視圖 - group_messages_view
-- =====================================================
CREATE OR REPLACE VIEW group_messages_view AS
SELECT 
    gm.id,
    gm.group_id,
    u.username AS from_user,
    gm.message,
    '用戶' AS role,
    gm.created_at AS timestamp
FROM group_messages_normalized gm
JOIN user u ON gm.from_user_id = u.id;

-- =====================================================
-- 13. 創建視圖 - chat_groups_view
-- =====================================================
CREATE OR REPLACE VIEW chat_groups_view AS
SELECT 
    cg.id,
    cg.group_name,
    u.username AS created_by,
    COALESCE(d.name, '') AS department,
    cg.created_at
FROM chat_groups_normalized cg
JOIN user u ON cg.created_by_user_id = u.id
LEFT JOIN departments d ON cg.department_id = d.id;

-- =====================================================
-- 14. 創建視圖 - group_members_view
-- =====================================================
CREATE OR REPLACE VIEW group_members_view AS
SELECT 
    gm.id,
    gm.group_id,
    u.username AS username,
    COALESCE(rt.name, '成員') AS role,
    gm.joined_at
FROM group_members_normalized gm
JOIN user u ON gm.user_id = u.id
LEFT JOIN role_types rt ON gm.role_type_id = rt.id;

-- =====================================================
-- 15. 添加缺失的外鍵約束
-- 注意：如果外鍵已存在，會顯示錯誤但可以忽略
-- 如果需要避免錯誤，請使用 quick_fix_missing_tables_safe.sql
-- =====================================================

-- enrollment_applications_normalized 的外鍵
-- 如果外鍵已存在，這些語句會報錯，但不影響其他表的創建

-- 身份別外鍵
ALTER TABLE enrollment_applications_normalized
ADD CONSTRAINT fk_enrollment_identity 
FOREIGN KEY (identity_id) REFERENCES identities(id) ON DELETE RESTRICT;

-- 性別外鍵
ALTER TABLE enrollment_applications_normalized
ADD CONSTRAINT fk_enrollment_gender 
FOREIGN KEY (gender_id) REFERENCES genders(id) ON DELETE SET NULL;

-- 年級外鍵
ALTER TABLE enrollment_applications_normalized
ADD CONSTRAINT fk_enrollment_grade 
FOREIGN KEY (current_grade_id) REFERENCES grades(id) ON DELETE SET NULL;

-- 推薦老師外鍵
ALTER TABLE enrollment_applications_normalized
ADD CONSTRAINT fk_enrollment_teacher 
FOREIGN KEY (recommended_teacher_user_id) REFERENCES teacher_normalized(user_id) ON DELETE SET NULL;

-- =====================================================
-- cooperation_applications_normalized 的外鍵
-- =====================================================

-- 老師外鍵
ALTER TABLE cooperation_applications_normalized
ADD CONSTRAINT fk_cooperation_teacher 
FOREIGN KEY (teacher_user_id) REFERENCES teacher_normalized(user_id) ON DELETE RESTRICT;

-- 科系外鍵
ALTER TABLE cooperation_applications_normalized
ADD CONSTRAINT fk_cooperation_department 
FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT;

SET FOREIGN_KEY_CHECKS = 1;

SELECT '所有缺失的表和視圖已創建！' AS message;
SELECT '請執行數據遷移腳本 migrate_all_data_to_3nf.sql 來遷移數據' AS next_step;

