-- 更新產學合作申請表結構以支援康寧大學申請表格式
USE topics_good;

-- 先刪除舊表（如果存在）
DROP TABLE IF EXISTS cooperation_applications;

-- 創建新的表結構
CREATE TABLE cooperation_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- 基本申請資訊
    teacher_username VARCHAR(255) NOT NULL,
    application_date DATE NOT NULL,
    approval_number VARCHAR(100) NULL,
    department VARCHAR(255) NOT NULL,
    principal_investigator VARCHAR(255) NOT NULL,
    regulations_read ENUM('yes', 'no') NOT NULL,
    
    -- 申請類別
    application_categories TEXT NOT NULL,
    
    -- 計畫詳細資訊
    project_amount DECIMAL(15,2) NOT NULL,
    admin_fee_percentage DECIMAL(5,2) DEFAULT 10.00,
    outcome_university BOOLEAN DEFAULT FALSE,
    outcome_company BOOLEAN DEFAULT FALSE,
    university_percentage DECIMAL(5,2) DEFAULT 0,
    company_percentage DECIMAL(5,2) DEFAULT 0,
    
    -- 合作廠商資訊
    company_name VARCHAR(255) NOT NULL,
    company_contact VARCHAR(255) NOT NULL,
    company_phone VARCHAR(50) NOT NULL,
    
    -- 計畫內容
    project_title VARCHAR(500) NOT NULL,
    expected_outcomes TEXT NOT NULL,
    project_timeline TEXT NOT NULL,
    
    -- 智慧財產權
    has_intellectual_property ENUM('yes', 'no') NOT NULL,
    university_ip_percentage DECIMAL(5,2) DEFAULT 0,
    company_ip_percentage DECIMAL(5,2) DEFAULT 0,
    investigator_ip_percentage DECIMAL(5,2) DEFAULT 0,
    
    -- 智慧財產權詳細資訊
    university_patent VARCHAR(255) NULL,
    company_patent VARCHAR(255) NULL,
    investigator_patent VARCHAR(255) NULL,
    university_trademark VARCHAR(255) NULL,
    company_trademark VARCHAR(255) NULL,
    investigator_trademark VARCHAR(255) NULL,
    university_copyright VARCHAR(255) NULL,
    company_copyright VARCHAR(255) NULL,
    investigator_copyright VARCHAR(255) NULL,
    university_trade_secret VARCHAR(255) NULL,
    company_trade_secret VARCHAR(255) NULL,
    investigator_trade_secret VARCHAR(255) NULL,
    
    -- 其他問題
    future_tech_transfer ENUM('yes', 'no') NULL,
    tech_transfer_amount DECIMAL(15,2) DEFAULT 0,
    has_derived_benefits ENUM('yes', 'no') NULL,
    benefits_amount DECIMAL(15,2) DEFAULT 0,
    use_university_venue BOOLEAN DEFAULT FALSE,
    venue_fees_in_proposal BOOLEAN DEFAULT FALSE,
    employ_disadvantaged_students BOOLEAN DEFAULT FALSE,
    use_standard_contract BOOLEAN DEFAULT FALSE,
    
    -- 檔案路徑
    contract_file_path VARCHAR(500) NOT NULL,
    proposal_file_path VARCHAR(500) NOT NULL,
    
    -- 審核狀態
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_username VARCHAR(255) NULL,
    admin_comment TEXT NULL,
    review_date TIMESTAMP NULL,
    
    -- 時間戳記
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- 索引
    INDEX idx_teacher_username (teacher_username),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_department (department)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 顯示表結構確認
DESCRIBE cooperation_applications;
