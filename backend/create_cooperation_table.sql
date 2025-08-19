-- 創建產學合作案申請表資料表
USE topics_good;

CREATE TABLE IF NOT EXISTS cooperation_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_username VARCHAR(255) NOT NULL,
    teacher_name VARCHAR(255) NOT NULL,
    department VARCHAR(255) NOT NULL,
    project_title VARCHAR(500) NOT NULL,
    project_description TEXT NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    company_contact VARCHAR(255) NOT NULL,
    company_phone VARCHAR(50) NOT NULL,
    company_email VARCHAR(255) NOT NULL,
    project_start_date DATE NOT NULL,
    project_end_date DATE NOT NULL,
    budget_amount DECIMAL(15,2) NOT NULL,
    expected_outcomes TEXT NOT NULL,
    application_file_path VARCHAR(500) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_username VARCHAR(255) NULL,
    admin_comment TEXT NULL,
    review_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_teacher_username (teacher_username),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
