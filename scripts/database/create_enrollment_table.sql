-- 建立康寧大學就讀意願登錄資料表
USE topics_good;

-- 先檢查並刪除舊表（如果存在）
DROP TABLE IF EXISTS enrollment_applications;

-- 創建新的就讀意願登錄資料表
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

-- 插入一些測試資料（可選）
INSERT INTO enrollment_applications (username, name, identity, gender, phone1, email, intention1, system1, department1, junior_high, current_grade, status) VALUES
('test_student1', '張小明', '學生', '男', '0912345678', 'test1@example.com', '資訊管理科', '五專', '資訊管理科', '中正國中', '國三', 'pending'),
('test_parent1', '李媽媽', '家長', '女', '0923456789', 'test2@example.com', '企業管理科', '五專', '企業管理科', '建國國中', '國二', 'contacted'),
('test_student2', '王小華', '學生', '女', '0934567890', 'test3@example.com', '應用外語科', '五專', '應用外語科', '復興國中', '國三', 'enrolled');

-- 顯示建立結果
SELECT 'enrollment_applications 資料表建立成功！' AS message;
SELECT COUNT(*) AS total_records FROM enrollment_applications;
