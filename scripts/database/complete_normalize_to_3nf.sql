-- =====================================================
-- 完整資料庫第三正規化（3NF）遷移腳本
-- 目標：將整個資料庫正規化至第三正規化（3NF）
-- 適用於：topics_good 資料庫
-- 日期：2025
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 第一部分：創建基礎參考表（消除數據冗餘）
-- =====================================================

-- 1. 科系表（已存在則跳過）
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT '科系代碼',
    name VARCHAR(255) NOT NULL COMMENT '科系名稱',
    available_systems JSON NOT NULL COMMENT '可用學制 ["五專", "四技"]',
    description TEXT NULL COMMENT '科系描述',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='科系資料表';

-- 插入預設科系資料
INSERT INTO departments (code, name, available_systems) VALUES
('NURSING', '護理科', '["五專"]'),
('CHILDCARE', '嬰幼兒保育科', '["五專", "四技"]'),
('OPTOMETRY', '視光科', '["五專"]'),
('DIGITAL_MEDIA', '數位影視動畫科', '["五專"]'),
('IM', '資訊管理科', '["五專"]'),
('BA', '企業管理科', '["五專", "四技"]'),
('FOREIGN_LANG', '應用外語科', '["五專"]'),
('LONG_TERM_CARE', '長期照護學系', '["四技"]')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 2. 學制表
CREATE TABLE IF NOT EXISTS education_systems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE COMMENT '學制代碼',
    name VARCHAR(50) NOT NULL COMMENT '學制名稱',
    years INT NOT NULL COMMENT '修業年數',
    description TEXT NULL COMMENT '學制描述',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='學制資料表';

INSERT INTO education_systems (code, name, years) VALUES
('FIVE_YEAR', '五專', 5),
('FOUR_YEAR', '四技', 4),
('THREE_YEAR', '三專', 3)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 3. 申請狀態表
CREATE TABLE IF NOT EXISTS application_statuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT '狀態代碼',
    name VARCHAR(100) NOT NULL COMMENT '狀態名稱',
    description TEXT NULL COMMENT '狀態描述',
    display_order INT DEFAULT 0 COMMENT '顯示順序',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='申請狀態資料表';

INSERT INTO application_statuses (code, name, display_order) VALUES
('PENDING', '待處理', 1),
('CONTACTED', '已聯絡', 2),
('ENROLLED', '已入學', 3),
('APPROVED', '已核准', 4),
('REJECTED', '已拒絕', 5)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 4. 身分別表
CREATE TABLE IF NOT EXISTS identities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT '身份代碼',
    name VARCHAR(100) NOT NULL COMMENT '身份名稱',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='身分別資料表';

INSERT INTO identities (code, name) VALUES
('STUDENT', '學生'),
('PARENT', '家長')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 5. 性別表
CREATE TABLE IF NOT EXISTS genders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE COMMENT '性別代碼',
    name VARCHAR(50) NOT NULL COMMENT '性別名稱',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='性別資料表';

INSERT INTO genders (code, name) VALUES
('MALE', '男'),
('FEMALE', '女'),
('OTHER', '其他')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 6. 年級表（擴展以支援更多年級）
CREATE TABLE IF NOT EXISTS grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE COMMENT '年級代碼',
    name VARCHAR(50) NOT NULL COMMENT '年級名稱',
    level INT NOT NULL COMMENT '年級層級 (1=國一/一年級, 2=國二/二年級, ...)',
    education_level ENUM('國中', '高中', '專科', '大學') DEFAULT '專科' COMMENT '教育層級',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='年級資料表';

INSERT INTO grades (code, name, level, education_level) VALUES
-- 國中年級
('JUNIOR_1', '國一', 1, '國中'),
('JUNIOR_2', '國二', 2, '國中'),
('JUNIOR_3', '國三', 3, '國中'),
('JUNIOR_GRADUATED', '國中已畢業', 4, '國中'),
-- 專科年級
('YEAR_1', '一年級', 1, '專科'),
('YEAR_2', '二年級', 2, '專科'),
('YEAR_3', '三年級', 3, '專科'),
('YEAR_4', '四年級', 4, '專科'),
('YEAR_5', '五年級', 5, '專科'),
-- 大學年級
('UNI_YEAR_1', '大一', 1, '大學'),
('UNI_YEAR_2', '大二', 2, '大學'),
('UNI_YEAR_3', '大三', 3, '大學'),
('UNI_YEAR_4', '大四', 4, '大學')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 7. 公司表
CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT '公司名稱',
    contact_person VARCHAR(255) NOT NULL COMMENT '聯絡人',
    phone VARCHAR(50) NOT NULL COMMENT '電話',
    email VARCHAR(255) NULL COMMENT '電子郵件',
    address TEXT NULL COMMENT '地址',
    tax_id VARCHAR(50) NULL COMMENT '統一編號',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_company_tax (tax_id),
    INDEX idx_name (name),
    INDEX idx_contact_person (contact_person)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公司資料表';

-- 8. 訊息類型表（用於聊天系統）
CREATE TABLE IF NOT EXISTS message_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT '類型代碼',
    name VARCHAR(100) NOT NULL COMMENT '類型名稱',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='訊息類型資料表';

INSERT INTO message_types (code, name) VALUES
('TEXT', '文字訊息'),
('IMAGE', '圖片'),
('FILE', '檔案'),
('SYSTEM', '系統訊息')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 9. 角色類型表（統一管理角色）
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
('MEMBER', '成員');

-- =====================================================
-- 第二部分：正規化用戶相關表
-- =====================================================

-- 10. 正規化 student 表（移除冗余的 department 和 grade 字串）
-- 使用 user_id 作為主鍵，因為一個 user 對應一個 student（一對一關係）
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

-- 11. 正規化 teacher 表（移除冗余的 department 字串）
-- 使用 user_id 作為主鍵，因為一個 user 對應一個 teacher（一對一關係）
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
-- 第三部分：正規化聊天系統表
-- =====================================================

-- 12. 正規化聊天群組表（移除冗余的 department 字串，改用 user_id）
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

-- 13. 正規化群組成員表（改用 user_id 替代 username）
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

-- 14. 正規化私聊訊息表（改用 user_id）
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

-- 15. 正規化群組訊息表（改用 user_id）
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
-- 第四部分：正規化就讀意願和產學合作表（補充原有腳本）
-- =====================================================

-- 16. 正規化後的就讀意願申請表
CREATE TABLE IF NOT EXISTS enrollment_applications_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL COMMENT '關聯到 user 表',
    username VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    identity_id INT NOT NULL COMMENT '關聯到 identities 表',
    gender_id INT NULL COMMENT '關聯到 genders 表',
    phone1 VARCHAR(50) NOT NULL,
    phone2 VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    junior_high_school_id INT NULL COMMENT '關聯到 schools 表',
    current_grade_id INT NULL COMMENT '關聯到 grades 表',
    line_id VARCHAR(255) NULL,
    facebook VARCHAR(255) NULL,
    recommended_teacher_user_id INT NULL COMMENT '關聯到 teacher_normalized.user_id',
    remarks TEXT NULL,
    status_id INT NOT NULL DEFAULT 1 COMMENT '關聯到 application_statuses 表',
    admin_id INT NULL COMMENT '處理的管理員ID',
    admin_comment TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_username (username),
    INDEX idx_status_id (status_id),
    INDEX idx_created_at (created_at),
    INDEX idx_identity_id (identity_id),
    INDEX idx_junior_high_school_id (junior_high_school_id),
    FOREIGN KEY (status_id) REFERENCES application_statuses(id) ON DELETE RESTRICT,
    FOREIGN KEY (identity_id) REFERENCES identities(id) ON DELETE RESTRICT,
    FOREIGN KEY (gender_id) REFERENCES genders(id) ON DELETE SET NULL,
    FOREIGN KEY (junior_high_school_id) REFERENCES schools(id) ON DELETE SET NULL,
    FOREIGN KEY (current_grade_id) REFERENCES grades(id) ON DELETE SET NULL,
    FOREIGN KEY (recommended_teacher_user_id) REFERENCES teacher_normalized(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的就讀意願申請表';

-- 17. 就讀意願明細表（解決第一正規化問題：1-3 志願）
CREATE TABLE IF NOT EXISTS enrollment_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_application_id INT NOT NULL COMMENT '關聯到 enrollment_applications_normalized',
    preference_order INT NOT NULL COMMENT '志願順序 (1, 2, 3)',
    department_id INT NOT NULL COMMENT '關聯到 departments 表',
    education_system_id INT NOT NULL COMMENT '關聯到 education_systems 表',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_application_preference (enrollment_application_id, preference_order),
    INDEX idx_enrollment_application_id (enrollment_application_id),
    INDEX idx_department_id (department_id),
    INDEX idx_education_system_id (education_system_id),
    FOREIGN KEY (enrollment_application_id) REFERENCES enrollment_applications_normalized(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (education_system_id) REFERENCES education_systems(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='就讀意願明細表';

-- 18. 正規化後的產學合作申請表
CREATE TABLE IF NOT EXISTS cooperation_applications_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_user_id INT NOT NULL COMMENT '關聯到 teacher_normalized.user_id',
    department_id INT NOT NULL COMMENT '關聯到 departments 表',
    company_id INT NOT NULL COMMENT '關聯到 companies 表',
    application_date DATE NOT NULL,
    approval_number VARCHAR(100) NULL,
    principal_investigator VARCHAR(255) NOT NULL,
    regulations_read BOOLEAN NOT NULL DEFAULT FALSE,
    project_amount DECIMAL(15,2) NOT NULL,
    admin_fee_percentage DECIMAL(5,2) DEFAULT 10.00,
    project_title VARCHAR(500) NOT NULL,
    expected_outcomes TEXT NOT NULL,
    project_timeline TEXT NOT NULL,
    has_intellectual_property BOOLEAN NOT NULL DEFAULT FALSE,
    future_tech_transfer BOOLEAN NULL,
    tech_transfer_amount DECIMAL(15,2) DEFAULT 0,
    has_derived_benefits BOOLEAN NULL,
    benefits_amount DECIMAL(15,2) DEFAULT 0,
    use_university_venue BOOLEAN DEFAULT FALSE,
    venue_fees_in_proposal BOOLEAN DEFAULT FALSE,
    employ_disadvantaged_students BOOLEAN DEFAULT FALSE,
    use_standard_contract BOOLEAN DEFAULT FALSE,
    contract_file_path VARCHAR(500) NOT NULL,
    proposal_file_path VARCHAR(500) NOT NULL,
    status_id INT NOT NULL DEFAULT 1 COMMENT '關聯到 application_statuses 表',
    admin_id INT NULL COMMENT '審核管理員ID',
    admin_comment TEXT NULL,
    review_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_teacher_user_id (teacher_user_id),
    INDEX idx_company_id (company_id),
    INDEX idx_status_id (status_id),
    INDEX idx_created_at (created_at),
    INDEX idx_department_id (department_id),
    FOREIGN KEY (status_id) REFERENCES application_statuses(id) ON DELETE RESTRICT,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (teacher_user_id) REFERENCES teacher_normalized(user_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的產學合作申請表';

-- 19. 產學合作申請類別明細表（解決多值屬性問題）
CREATE TABLE IF NOT EXISTS cooperation_application_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cooperation_application_id INT NOT NULL COMMENT '關聯到 cooperation_applications_normalized',
    category_code VARCHAR(100) NOT NULL COMMENT '申請類別代碼',
    category_name VARCHAR(255) NOT NULL COMMENT '申請類別名稱',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cooperation_application_id (cooperation_application_id),
    INDEX idx_category_code (category_code),
    FOREIGN KEY (cooperation_application_id) REFERENCES cooperation_applications_normalized(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='產學合作申請類別明細表';

-- 20. 智慧財產權明細表
CREATE TABLE IF NOT EXISTS ip_rights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cooperation_application_id INT NOT NULL COMMENT '關聯到 cooperation_applications_normalized',
    ip_type ENUM('patent', 'trademark', 'copyright', 'trade_secret') NOT NULL COMMENT 'IP類型',
    university_percentage DECIMAL(5,2) DEFAULT 0 COMMENT '大學比例',
    company_percentage DECIMAL(5,2) DEFAULT 0 COMMENT '公司比例',
    investigator_percentage DECIMAL(5,2) DEFAULT 0 COMMENT '研究員比例',
    description TEXT NULL COMMENT '描述',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cooperation_application_id (cooperation_application_id),
    INDEX idx_ip_type (ip_type),
    FOREIGN KEY (cooperation_application_id) REFERENCES cooperation_applications_normalized(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='智慧財產權明細表';

-- =====================================================
-- 第五部分：正規化 AI 聊天表
-- =====================================================

-- 21. 正規化 AI 聊天記錄表（已使用 user_id，只需添加 message_type）
-- 注意：MySQL 不支援 IF NOT EXISTS，需要手動檢查
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'ai_chat_history' 
    AND column_name = 'message_type_id');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE ai_chat_history ADD COLUMN message_type_id INT NULL COMMENT ''關聯到 message_types 表'' AFTER message_type',
    'SELECT ''Column message_type_id already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 如果表不存在則創建
CREATE TABLE IF NOT EXISTS ai_chat_history_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '關聯到 user 表',
    message_type_id INT NOT NULL DEFAULT 1 COMMENT '關聯到 message_types 表',
    message_content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_message_type_id (message_type_id),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (message_type_id) REFERENCES message_types(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的AI聊天記錄表';

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 第六部分：創建向後兼容的視圖
-- =====================================================

-- 22. student 視圖（保持與舊表兼容）
-- 注意：使用 user_id 作為 id，因為 user_id 現在是主鍵
CREATE OR REPLACE VIEW student_view AS
SELECT 
    s.user_id AS id,  -- user_id 作為主鍵，對外顯示為 id
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

-- 23. teacher 視圖（保持與舊表兼容）
-- 注意：使用 user_id 作為 id，因為 user_id 現在是主鍵
CREATE OR REPLACE VIEW teacher_view AS
SELECT 
    t.user_id AS id,  -- user_id 作為主鍵，對外顯示為 id
    t.user_id,
    t.name,
    COALESCE(d.name, '') AS department,
    t.phone,
    t.created_at,
    t.updated_at
FROM teacher_normalized t
LEFT JOIN departments d ON t.department_id = d.id;

-- 24. 私聊訊息視圖
CREATE OR REPLACE VIEW private_chat_history_view AS
SELECT 
    p.id,
    u1.username AS from_user,
    u2.username AS to_user,
    p.message,
    rt.name AS role,
    p.created_at AS timestamp
FROM private_chat_history_normalized p
JOIN user u1 ON p.from_user_id = u1.id
JOIN user u2 ON p.to_user_id = u2.id
LEFT JOIN user u ON u.id = p.from_user_id
LEFT JOIN role_types rt ON rt.id = (
    SELECT 
        CASE 
            WHEN EXISTS(SELECT 1 FROM teacher_normalized WHERE user_id = p.from_user_id) THEN (SELECT id FROM role_types WHERE code = 'TEACHER')
            WHEN EXISTS(SELECT 1 FROM student_normalized WHERE user_id = p.from_user_id) THEN (SELECT id FROM role_types WHERE code = 'STUDENT')
            ELSE (SELECT id FROM role_types WHERE code = 'MEMBER')
        END
);

-- 25. 群組訊息視圖
CREATE OR REPLACE VIEW group_messages_view AS
SELECT 
    gm.id,
    gm.group_id,
    u.username AS from_user,
    gm.message,
    rt.name AS role,
    gm.created_at AS timestamp
FROM group_messages_normalized gm
JOIN user u ON gm.from_user_id = u.id
LEFT JOIN role_types rt ON rt.id = (
    SELECT 
        CASE 
            WHEN EXISTS(SELECT 1 FROM teacher_normalized WHERE user_id = gm.from_user_id) THEN (SELECT id FROM role_types WHERE code = 'TEACHER')
            WHEN EXISTS(SELECT 1 FROM student_normalized WHERE user_id = gm.from_user_id) THEN (SELECT id FROM role_types WHERE code = 'STUDENT')
            ELSE (SELECT id FROM role_types WHERE code = 'MEMBER')
        END
);

-- 26. 群組視圖
CREATE OR REPLACE VIEW chat_groups_view AS
SELECT 
    cg.id,
    cg.group_name,
    u.username AS created_by,
    d.name AS department,
    cg.created_at
FROM chat_groups_normalized cg
JOIN user u ON cg.created_by_user_id = u.id
LEFT JOIN departments d ON cg.department_id = d.id;

-- 27. 群組成員視圖
CREATE OR REPLACE VIEW group_members_view AS
SELECT 
    gm.id,
    gm.group_id,
    u.username AS username,
    rt.name AS role,
    gm.joined_at
FROM group_members_normalized gm
JOIN user u ON gm.user_id = u.id
JOIN role_types rt ON gm.role_type_id = rt.id;

-- =====================================================
-- 完成
-- =====================================================

SELECT '第三正規化表結構創建完成！' AS message;
SELECT '下一步：執行數據遷移腳本 migrate_all_data_to_3nf.sql' AS next_step;

