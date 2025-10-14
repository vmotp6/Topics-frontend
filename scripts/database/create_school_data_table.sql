-- 創建學校資料表
CREATE TABLE IF NOT EXISTS school_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '學校名稱',
    city VARCHAR(20) NOT NULL COMMENT '縣市',
    district VARCHAR(20) NOT NULL COMMENT '區/鄉鎮市',
    type VARCHAR(20) NOT NULL COMMENT '學校類型',
    school_code VARCHAR(20) DEFAULT NULL COMMENT '學校代碼',
    address VARCHAR(200) DEFAULT NULL COMMENT '學校地址',
    phone VARCHAR(20) DEFAULT NULL COMMENT '聯絡電話',
    website VARCHAR(200) DEFAULT NULL COMMENT '學校網站',
    principal VARCHAR(50) DEFAULT NULL COMMENT '校長姓名',
    student_count INT DEFAULT 0 COMMENT '學生人數',
    teacher_count INT DEFAULT 0 COMMENT '教師人數',
    established_year YEAR DEFAULT NULL COMMENT '創校年份',
    is_active TINYINT(1) DEFAULT 1 COMMENT '是否營運中',
    data_source VARCHAR(100) DEFAULT '教育部開放資料' COMMENT '資料來源',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最後更新時間',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    INDEX idx_name (name),
    INDEX idx_city (city),
    INDEX idx_type (type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='學校基本資料表';

-- 插入一些測試資料
INSERT INTO school_data (name, city, district, type, school_code, is_active) VALUES
('中正國中', '台北市', '中正區', '國民中學', 'TP001', 1),
('西松國中', '台北市', '松山區', '國民中學', 'TP002', 1),
('永吉國中', '台北市', '信義區', '國民中學', 'TP003', 1),
('信義國中', '台北市', '信義區', '國民中學', 'TP004', 1),
('松山國中', '台北市', '松山區', '國民中學', 'TP005', 1),
('板橋國中', '新北市', '板橋區', '國民中學', 'NT001', 1),
('海山國中', '新北市', '板橋區', '國民中學', 'NT002', 1),
('新莊國中', '新北市', '新莊區', '國民中學', 'NT003', 1),
('桃園國中', '桃園市', '桃園區', '國民中學', 'TY001', 1),
('中壢國中', '桃園市', '中壢區', '國民中學', 'TY002', 1),
('基隆國中', '基隆市', '中正區', '國民中學', 'KL001', 1),
('新竹國中', '新竹市', '東區', '國民中學', 'HSC001', 1),
('竹北國中', '新竹縣', '竹北市', '國民中學', 'HSH001', 1);
