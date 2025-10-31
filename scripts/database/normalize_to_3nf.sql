-- =====================================================
-- 資料庫第三正規化（3NF）遷移腳本
-- 目標：消除資料冗餘和傳遞依賴，達到第三正規化
-- =====================================================

USE topics_good;

-- =====================================================
-- 第一部分：創建新的正規化表結構
-- =====================================================

-- 1. 創建科系表（消除 enrollment_applications 中的重複科系資料）
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

-- 2. 創建學制表（消除重複的學制資料）
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

-- 插入預設學制資料
INSERT INTO education_systems (code, name, years) VALUES
('FIVE_YEAR', '五專', 5),
('FOUR_YEAR', '四技', 4)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 3. 創建申請狀態表（消除狀態字串的重複）
CREATE TABLE IF NOT EXISTS application_statuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT '狀態代碼',
    name VARCHAR(100) NOT NULL COMMENT '狀態名稱',
    description TEXT NULL COMMENT '狀態描述',
    display_order INT DEFAULT 0 COMMENT '顯示順序',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='申請狀態資料表';

-- 插入預設狀態資料
INSERT INTO application_statuses (code, name, display_order) VALUES
('PENDING', '待處理', 1),
('CONTACTED', '已聯絡', 2),
('ENROLLED', '已入學', 3),
('APPROVED', '已核准', 4),
('REJECTED', '已拒絕', 5)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 4. 正規化後的就讀意願申請表
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
    recommended_teacher_id INT NULL COMMENT '關聯到 teacher 表',
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
    FOREIGN KEY (junior_high_school_id) REFERENCES schools(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的就讀意願申請表';

-- 5. 創建就讀意願明細表（解決第一正規化問題：1-3 志願）
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

-- 6. 創建身分別表（消除身份字串重複）
CREATE TABLE IF NOT EXISTS identities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT '身份代碼',
    name VARCHAR(100) NOT NULL COMMENT '身份名稱',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='身分別資料表';

INSERT INTO identities (code, name) VALUES
('STUDENT', '學生'),
('PARENT', '家長')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 7. 創建性別表（消除性別字串重複）
CREATE TABLE IF NOT EXISTS genders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE COMMENT '性別代碼',
    name VARCHAR(50) NOT NULL COMMENT '性別名稱',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='性別資料表';

INSERT INTO genders (code, name) VALUES
('MALE', '男'),
('FEMALE', '女')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 8. 創建年級表（消除年級字串重複）
CREATE TABLE IF NOT EXISTS grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE COMMENT '年級代碼',
    name VARCHAR(50) NOT NULL COMMENT '年級名稱',
    level INT NOT NULL COMMENT '年級層級 (1=國一, 2=國二, 3=國三)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='年級資料表';

INSERT INTO grades (code, name, level) VALUES
('GRADE_1', '國一', 1),
('GRADE_2', '國二', 2),
('GRADE_3', '國三', 3),
('GRADUATED', '已畢業', 4)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 9. 正規化產學合作申請表 - 公司資訊分離
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
    UNIQUE KEY uk_company_name (name),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公司資料表';

-- 10. 正規化後的產學合作申請表（必須在 ip_rights 之前創建）
CREATE TABLE IF NOT EXISTS cooperation_applications_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL COMMENT '關聯到 teacher 表',
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
    INDEX idx_teacher_id (teacher_id),
    INDEX idx_company_id (company_id),
    INDEX idx_status_id (status_id),
    INDEX idx_created_at (created_at),
    INDEX idx_department_id (department_id),
    FOREIGN KEY (status_id) REFERENCES application_statuses(id) ON DELETE RESTRICT,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的產學合作申請表';

-- 12. 產學合作申請類別明細表（解決多值屬性問題）
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

-- 13. 正規化產學合作申請表 - 智慧財產權明細表（必須在 cooperation_applications_normalized 之後創建）
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
-- 第二部分：創建向後兼容的視圖
-- =====================================================

-- 1. 創建就讀意願申請視圖（保持與舊表結構兼容）
CREATE OR REPLACE VIEW enrollment_applications_view AS
SELECT 
    ea.id,
    ea.username,
    ea.name,
    i.name AS identity,
    g.name AS gender,
    ea.phone1,
    ea.phone2,
    ea.email,
    -- 志願一
    MAX(CASE WHEN ep.preference_order = 1 THEN d.name END) AS intention1,
    MAX(CASE WHEN ep.preference_order = 1 THEN es.name END) AS system1,
    MAX(CASE WHEN ep.preference_order = 1 THEN d.name END) AS department1,
    -- 志願二
    MAX(CASE WHEN ep.preference_order = 2 THEN d.name END) AS intention2,
    MAX(CASE WHEN ep.preference_order = 2 THEN es.name END) AS system2,
    MAX(CASE WHEN ep.preference_order = 2 THEN d.name END) AS department2,
    -- 志願三
    MAX(CASE WHEN ep.preference_order = 3 THEN d.name END) AS intention3,
    MAX(CASE WHEN ep.preference_order = 3 THEN es.name END) AS system3,
    MAX(CASE WHEN ep.preference_order = 3 THEN d.name END) AS department3,
    COALESCE(s.name, '') AS junior_high,
    gr.name AS current_grade,
    ea.line_id,
    ea.facebook,
    ea.remarks,
    ast.name AS status,
    ea.admin_comment,
    ea.created_at,
    ea.updated_at
FROM enrollment_applications_normalized ea
LEFT JOIN identities i ON ea.identity_id = i.id
LEFT JOIN genders g ON ea.gender_id = g.id
LEFT JOIN schools s ON ea.junior_high_school_id = s.id
LEFT JOIN grades gr ON ea.current_grade_id = gr.id
LEFT JOIN application_statuses ast ON ea.status_id = ast.id
LEFT JOIN enrollment_preferences ep ON ea.id = ep.enrollment_application_id
LEFT JOIN departments d ON ep.department_id = d.id
LEFT JOIN education_systems es ON ep.education_system_id = es.id
GROUP BY ea.id;

-- 2. 創建產學合作申請視圖（保持與舊表結構兼容）
CREATE OR REPLACE VIEW cooperation_applications_view AS
SELECT 
    ca.id,
    ca.teacher_id,
    COALESCE(
        (SELECT username FROM user u JOIN teacher t ON u.id = t.user_id WHERE t.user_id = ca.teacher_id LIMIT 1),
        (SELECT username FROM user WHERE id = ca.teacher_id LIMIT 1),
        ''
    ) AS teacher_username,
    ca.application_date,
    ca.approval_number,
    d.name AS department,
    ca.principal_investigator,
    CASE WHEN ca.regulations_read THEN 'yes' ELSE 'no' END AS regulations_read,
    GROUP_CONCAT(cac.category_name SEPARATOR ', ') AS application_categories,
    ca.project_amount,
    ca.admin_fee_percentage,
    c.name AS company_name,
    c.contact_person AS company_contact,
    c.phone AS company_phone,
    ca.project_title,
    ca.expected_outcomes,
    ca.project_timeline,
    CASE WHEN ca.has_intellectual_property THEN 'yes' ELSE 'no' END AS has_intellectual_property,
    ca.contract_file_path,
    ca.proposal_file_path,
    ast.name AS status,
    ca.admin_comment,
    ca.review_date,
    ca.created_at,
    ca.updated_at
FROM cooperation_applications_normalized ca
LEFT JOIN companies c ON ca.company_id = c.id
LEFT JOIN departments d ON ca.department_id = d.id
LEFT JOIN application_statuses ast ON ca.status_id = ast.id
LEFT JOIN cooperation_application_categories cac ON ca.id = cac.cooperation_application_id
LEFT JOIN teacher t ON ca.teacher_id = t.user_id
GROUP BY ca.id;

-- =====================================================
-- 第三部分：資料遷移腳本（將舊資料遷移到新結構）
-- =====================================================

-- 注意：執行此部分前，請先備份資料庫！

-- 1. 遷移就讀意願申請資料
INSERT INTO enrollment_applications_normalized (
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
        ELSE 1
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
        WHEN '國一' THEN (SELECT id FROM grades WHERE code = 'GRADE_1')
        WHEN '國二' THEN (SELECT id FROM grades WHERE code = 'GRADE_2')
        WHEN '國三' THEN (SELECT id FROM grades WHERE code = 'GRADE_3')
        WHEN '已畢業' THEN (SELECT id FROM grades WHERE code = 'GRADUATED')
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
    WHERE ean.username = ea.username AND ean.created_at = ea.created_at
);

-- 2. 遷移就讀意願明細資料（使用更靈活的科系名稱匹配）
INSERT INTO enrollment_preferences (
    enrollment_application_id, preference_order, department_id, education_system_id
)
SELECT 
    ean.id,
    1 AS preference_order,
    COALESCE(
        (SELECT id FROM departments WHERE name = ea.intention1 LIMIT 1),
        (SELECT id FROM departments WHERE name LIKE CONCAT('%', ea.intention1, '%') LIMIT 1),
        (SELECT id FROM departments WHERE ea.intention1 LIKE CONCAT('%', name, '%') LIMIT 1)
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
    AND COALESCE(
        (SELECT id FROM departments WHERE name = ea.intention1 LIMIT 1),
        (SELECT id FROM departments WHERE name LIKE CONCAT('%', ea.intention1, '%') LIMIT 1),
        NULL
    ) IS NOT NULL
UNION ALL
SELECT 
    ean.id,
    2 AS preference_order,
    COALESCE(
        (SELECT id FROM departments WHERE name = ea.intention2 LIMIT 1),
        (SELECT id FROM departments WHERE name LIKE CONCAT('%', ea.intention2, '%') LIMIT 1),
        (SELECT id FROM departments WHERE ea.intention2 LIKE CONCAT('%', name, '%') LIMIT 1)
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
    AND COALESCE(
        (SELECT id FROM departments WHERE name = ea.intention2 LIMIT 1),
        (SELECT id FROM departments WHERE name LIKE CONCAT('%', ea.intention2, '%') LIMIT 1),
        NULL
    ) IS NOT NULL
UNION ALL
SELECT 
    ean.id,
    3 AS preference_order,
    COALESCE(
        (SELECT id FROM departments WHERE name = ea.intention3 LIMIT 1),
        (SELECT id FROM departments WHERE name LIKE CONCAT('%', ea.intention3, '%') LIMIT 1),
        (SELECT id FROM departments WHERE ea.intention3 LIKE CONCAT('%', name, '%') LIMIT 1)
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
    AND COALESCE(
        (SELECT id FROM departments WHERE name = ea.intention3 LIMIT 1),
        (SELECT id FROM departments WHERE name LIKE CONCAT('%', ea.intention3, '%') LIMIT 1),
        NULL
    ) IS NOT NULL;

-- 3. 遷移產學合作申請資料 - 公司資訊
INSERT INTO companies (name, contact_person, phone)
SELECT DISTINCT
    ca.company_name,
    ca.company_contact,
    ca.company_phone
FROM cooperation_applications ca
WHERE NOT EXISTS (
    SELECT 1 FROM companies c 
    WHERE c.name = ca.company_name 
    AND c.contact_person = ca.company_contact
);

-- 4. 遷移產學合作申請資料
INSERT INTO cooperation_applications_normalized (
    teacher_id, department_id, company_id, application_date, approval_number,
    principal_investigator, regulations_read, project_amount, admin_fee_percentage,
    project_title, expected_outcomes, project_timeline, has_intellectual_property,
    future_tech_transfer, tech_transfer_amount, has_derived_benefits, benefits_amount,
    use_university_venue, venue_fees_in_proposal, employ_disadvantaged_students,
    use_standard_contract, contract_file_path, proposal_file_path, status_id,
    admin_comment, review_date, created_at, updated_at
)
SELECT 
    (SELECT user_id FROM teacher WHERE username = ca.teacher_username LIMIT 1) AS teacher_id,
    (SELECT id FROM departments WHERE name = ca.department LIMIT 1) AS department_id,
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
    AND can.created_at = ca.created_at
);

-- =====================================================
-- 第四部分：驗證腳本
-- =====================================================

-- 驗證資料遷移是否成功
SELECT '資料遷移驗證' AS verification_step;

-- 1. 檢查就讀意願申請資料數量
SELECT 
    'enrollment_applications' AS table_name,
    COUNT(*) AS original_count
FROM enrollment_applications
UNION ALL
SELECT 
    'enrollment_applications_normalized' AS table_name,
    COUNT(*) AS normalized_count
FROM enrollment_applications_normalized;

-- 2. 檢查產學合作申請資料數量
SELECT 
    'cooperation_applications' AS table_name,
    COUNT(*) AS original_count
FROM cooperation_applications
UNION ALL
SELECT 
    'cooperation_applications_normalized' AS table_name,
    COUNT(*) AS normalized_count
FROM cooperation_applications_normalized;

-- 3. 檢查視圖是否正常工作
SELECT COUNT(*) AS view_record_count FROM enrollment_applications_view;
SELECT COUNT(*) AS view_record_count FROM cooperation_applications_view;

-- =====================================================
-- 完成訊息
-- =====================================================
SELECT '第三正規化遷移完成！' AS message;
SELECT '請檢查上述驗證結果，確認資料遷移是否成功。' AS next_step;
SELECT '建議：在確認無誤後，可以將舊表重命名為 *_backup 作為備份。' AS suggestion;

