-- =====================================================
-- 正規化 senior_messages 表
-- 將 author_department, author_grade 改為外鍵
-- 將 author_email 改為 user_id
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- 創建正規化版本
CREATE TABLE IF NOT EXISTS senior_messages_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL COMMENT '留言標題',
    content TEXT NOT NULL COMMENT '留言內容',
    author_user_id INT NULL COMMENT '關聯到 user 表（學長姐）',
    author_name VARCHAR(100) NOT NULL COMMENT '學長姐姓名（備用）',
    author_email VARCHAR(255) NULL COMMENT '學長姐Email（備用）',
    author_department_id INT NULL COMMENT '關聯到 departments 表',
    author_grade_id INT NULL COMMENT '關聯到 grades 表',
    author_contact VARCHAR(100) DEFAULT NULL COMMENT '聯絡方式',
    message_type_id INT NOT NULL DEFAULT 1 COMMENT '關聯到 message_types 表',
    is_published BOOLEAN DEFAULT TRUE COMMENT '是否發布',
    author_grade_year INT DEFAULT NULL COMMENT '入學年份（用於權限控制）',
    view_count INT DEFAULT 0 COMMENT '瀏覽次數',
    like_count INT DEFAULT 0 COMMENT '點讚次數',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '創建時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    INDEX idx_author_user_id (author_user_id),
    INDEX idx_department_id (author_department_id),
    INDEX idx_grade_id (author_grade_id),
    INDEX idx_message_type_id (message_type_id),
    INDEX idx_is_published (is_published),
    FOREIGN KEY (author_user_id) REFERENCES user(id) ON DELETE SET NULL,
    FOREIGN KEY (author_department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (author_grade_id) REFERENCES grades(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的學長姐留言表';

-- 確保 departments 和 grades 表存在（應該在 complete_normalize_to_3nf.sql 中創建）
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT '科系代碼',
    name VARCHAR(255) NOT NULL COMMENT '科系名稱',
    available_systems JSON NOT NULL COMMENT '可用學制',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE COMMENT '年級代碼',
    name VARCHAR(50) NOT NULL COMMENT '年級名稱',
    level INT NOT NULL COMMENT '年級層級',
    education_level ENUM('國中', '高中', '專科', '大學') DEFAULT '專科',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 確保 message_types 表存在（應該在 complete_normalize_to_3nf.sql 中創建）
CREATE TABLE IF NOT EXISTS message_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT '類型代碼',
    name VARCHAR(100) NOT NULL COMMENT '類型名稱',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='訊息類型資料表';

-- 創建留言類型參考表（擴展原有的 message_types）
-- 如果不存在對應的類型，創建它們
INSERT IGNORE INTO message_types (code, name) VALUES
('TEXT', '文字訊息'),
('IMAGE', '圖片'),
('FILE', '檔案'),
('SYSTEM', '系統訊息'),
('EXPERIENCE_SHARE', '經驗分享'),
('STUDY_ADVICE', '學習建議'),
('LIFE_GUIDE', '生活指南'),
('CAREER_INFO', '就業資訊'),
('OTHER', '其他');

-- 遷移數據
INSERT IGNORE INTO senior_messages_normalized (
    id, title, content, author_user_id, author_name, author_email,
    author_department_id, author_grade_id, author_contact, message_type_id,
    is_published, author_grade_year, view_count, like_count, created_at, updated_at
)
SELECT 
    sm.id,
    sm.title,
    sm.content,
    COALESCE(
        (SELECT id FROM user WHERE email = sm.author_email LIMIT 1),
        NULL
    ) AS author_user_id,
    sm.author_name,
    sm.author_email,
    COALESCE(
        (SELECT id FROM departments WHERE name = sm.author_department LIMIT 1),
        (SELECT id FROM departments WHERE name LIKE CONCAT('%', sm.author_department, '%') LIMIT 1),
        NULL
    ) AS author_department_id,
    COALESCE(
        (SELECT id FROM grades WHERE name = sm.author_grade LIMIT 1),
        (SELECT id FROM grades WHERE name LIKE CONCAT('%', sm.author_grade, '%') LIMIT 1),
        NULL
    ) AS author_grade_id,
    sm.author_contact,
    CASE sm.message_type
        WHEN '經驗分享' THEN (SELECT id FROM message_types WHERE code = 'EXPERIENCE_SHARE')
        WHEN '學習建議' THEN (SELECT id FROM message_types WHERE code = 'STUDY_ADVICE')
        WHEN '生活指南' THEN (SELECT id FROM message_types WHERE code = 'LIFE_GUIDE')
        WHEN '就業資訊' THEN (SELECT id FROM message_types WHERE code = 'CAREER_INFO')
        ELSE (SELECT id FROM message_types WHERE code = 'OTHER')
    END AS message_type_id,
    sm.is_published,
    sm.author_grade_year,
    sm.view_count,
    sm.like_count,
    sm.created_at,
    sm.updated_at
FROM senior_messages sm;

-- 創建向後兼容視圖
CREATE OR REPLACE VIEW senior_messages_view AS
SELECT 
    sm.id,
    sm.title,
    sm.content,
    sm.author_name,
    sm.author_email,
    COALESCE(d.name, '') AS author_department,
    COALESCE(g.name, '') AS author_grade,
    sm.author_contact,
    mt.name AS message_type,
    sm.is_published,
    sm.author_grade_year,
    sm.view_count,
    sm.like_count,
    sm.created_at,
    sm.updated_at
FROM senior_messages_normalized sm
LEFT JOIN departments d ON sm.author_department_id = d.id
LEFT JOIN grades g ON sm.author_grade_id = g.id
LEFT JOIN message_types mt ON sm.message_type_id = mt.id;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'senior_messages 表已正規化' AS message;

