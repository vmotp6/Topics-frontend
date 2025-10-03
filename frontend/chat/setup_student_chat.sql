-- 設置學生聊天系統的資料庫腳本
USE topics_good;

-- 1. 確保學生表存在
CREATE TABLE IF NOT EXISTS student (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    student_id VARCHAR(50) UNIQUE,
    department VARCHAR(255),
    grade VARCHAR(50),
    class_name VARCHAR(100),
    email VARCHAR(255),
    phone VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_student_id (student_id),
    INDEX idx_department (department),
    INDEX idx_name (name),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. 更新 user 表，確保學生角色存在
ALTER TABLE user MODIFY COLUMN role ENUM('老師', '學校行政人員', '學生', '廠商') NOT NULL;

-- 3. 插入測試學生用戶（如果不存在）
INSERT IGNORE INTO user (username, password, role) VALUES
('student1', '123456', '學生'),
('student2', '123456', '學生'),
('student3', '123456', '學生'),
('student4', '123456', '學生'),
('student5', '123456', '學生'),
('student6', '123456', '學生'),
('student7', '123456', '學生'),
('student8', '123456', '學生');

-- 4. 插入學生詳細資料（如果不存在）
INSERT IGNORE INTO student (user_id, name, student_id, department, grade, class_name, email, phone) VALUES
((SELECT id FROM user WHERE username = 'student1'), '張小明', 'S001', '資訊工程學系', '一年級', '資工一甲', 'student1@example.com', '0912345678'),
((SELECT id FROM user WHERE username = 'student2'), '李美華', 'S002', '資訊工程學系', '一年級', '資工一乙', 'student2@example.com', '0923456789'),
((SELECT id FROM user WHERE username = 'student3'), '王大偉', 'S003', '企業管理學系', '二年級', '企管二甲', 'student3@example.com', '0934567890'),
((SELECT id FROM user WHERE username = 'student4'), '陳小芳', 'S004', '外國語文學系', '三年級', '外文三甲', 'student4@example.com', '0945678901'),
((SELECT id FROM user WHERE username = 'student5'), '林志強', 'S005', '資訊工程學系', '二年級', '資工二甲', 'student5@example.com', '0956789012'),
((SELECT id FROM user WHERE username = 'student6'), '黃雅婷', 'S006', '企業管理學系', '一年級', '企管一甲', 'student6@example.com', '0967890123'),
((SELECT id FROM user WHERE username = 'student7'), '劉建國', 'S007', '外國語文學系', '四年級', '外文四甲', 'student7@example.com', '0978901234'),
((SELECT id FROM user WHERE username = 'student8'), '吳淑芬', 'S008', '資訊工程學系', '三年級', '資工三甲', 'student8@example.com', '0989012345');

-- 5. 確保聊天相關表存在
CREATE TABLE IF NOT EXISTS private_chat_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_user VARCHAR(255) NOT NULL,
    to_user VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    role VARCHAR(50) DEFAULT '用戶',
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_from_user (from_user),
    INDEX idx_to_user (to_user),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(255) NOT NULL,
    created_by VARCHAR(255) NOT NULL,
    department VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    username VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT '成員',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES chat_groups(id) ON DELETE CASCADE,
    INDEX idx_group_id (group_id),
    INDEX idx_username (username),
    UNIQUE KEY unique_group_member (group_id, username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS group_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    from_user VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    role VARCHAR(50) DEFAULT '用戶',
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES chat_groups(id) ON DELETE CASCADE,
    INDEX idx_group_id (group_id),
    INDEX idx_from_user (from_user),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. 顯示設置結果
SELECT '學生聊天系統設置完成！' as message;
SELECT COUNT(*) as student_count FROM student;
SELECT COUNT(*) as user_student_count FROM user WHERE role = '學生';
