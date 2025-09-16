-- 康寧大學就讀意願登錄資料表建立腳本
-- 請在 phpMyAdmin 中執行此腳本

USE topics_good;

-- 刪除舊表（如果存在）
DROP TABLE IF EXISTS enrollment_applications;

-- 創建新表
CREATE TABLE enrollment_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    identity ENUM('學生', '家長') NOT NULL,
    gender ENUM('男', '女') NULL,
    phone1 VARCHAR(50) NOT NULL,
    phone2 VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    intention1 VARCHAR(255) DEFAULT '無特定',
    system1 VARCHAR(50) NULL,
    department1 VARCHAR(255) NULL,
    intention2 VARCHAR(255) DEFAULT '無特定',
    system2 VARCHAR(50) NULL,
    department2 VARCHAR(255) NULL,
    intention3 VARCHAR(255) DEFAULT '無特定',
    system3 VARCHAR(50) NULL,
    department3 VARCHAR(255) NULL,
    junior_high VARCHAR(255) NULL,
    current_grade VARCHAR(50) NULL,
    line_id VARCHAR(255) NULL,
    facebook VARCHAR(255) NULL,
    remarks TEXT NULL,
    status ENUM('pending', 'contacted', 'enrolled') DEFAULT 'pending',
    admin_comment TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_identity (identity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
