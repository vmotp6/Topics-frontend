-- 創建學生表
-- 用於聊天系統的學生資料

USE topics_good;

-- 創建學生表
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

-- 更新 user 表，添加學生角色（如果不存在）
ALTER TABLE user MODIFY COLUMN role ENUM('老師', '學校行政人員', '學生') NOT NULL;

-- 插入一些測試學生資料
INSERT IGNORE INTO user (username, password, role) VALUES
('student1', '123456', '學生'),
('student2', '123456', '學生'),
('student3', '123456', '學生'),
('student4', '123456', '學生'),
('student5', '123456', '學生'),
('student6', '123456', '學生');

-- 插入學生詳細資料
INSERT IGNORE INTO student (user_id, name, student_id, department, grade, class_name, email, phone) VALUES
((SELECT id FROM user WHERE username = 'student1'), '張小明', 'S001', '資訊管理科', '一年級', '資管一甲', 'student1@example.com', '0912345678'),
((SELECT id FROM user WHERE username = 'student2'), '李美華', 'S002', '資訊管理科', '一年級', '資管一乙', 'student2@example.com', '0923456789'),
((SELECT id FROM user WHERE username = 'student3'), '王大偉', 'S003', '企業管理科', '二年級', '企管二甲', 'student3@example.com', '0934567890'),
((SELECT id FROM user WHERE username = 'student4'), '陳小芳', 'S004', '應用外語科', '三年級', '應外三甲', 'student4@example.com', '0945678901'),
((SELECT id FROM user WHERE username = 'student5'), '林志強', 'S005', '護理科', '二年級', '護理二甲', 'student5@example.com', '0956789012'),
((SELECT id FROM user WHERE username = 'student6'), '黃小華', 'S006', '動畫科', '一年級', '動畫一甲', 'student6@example.com', '0967890123');

