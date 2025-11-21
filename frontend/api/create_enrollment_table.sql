-- 創建就讀意願登錄表
CREATE TABLE IF NOT EXISTS enrollment_intention (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '姓名',
    identity ENUM('學生', '家長') NOT NULL COMMENT '身分別',
    gender ENUM('男', '女') DEFAULT NULL COMMENT '性別',
    phone1 VARCHAR(20) NOT NULL COMMENT '聯絡電話1',
    phone2 VARCHAR(20) DEFAULT NULL COMMENT '聯絡電話2',
    email VARCHAR(100) DEFAULT NULL COMMENT '電子郵件',
    intention1 VARCHAR(50) DEFAULT NULL COMMENT '就讀意願一',
    intention2 VARCHAR(50) DEFAULT NULL COMMENT '就讀意願二',
    intention3 VARCHAR(50) DEFAULT NULL COMMENT '就讀意願三',
    system1 VARCHAR(20) DEFAULT NULL COMMENT '學制一',
    system2 VARCHAR(20) DEFAULT NULL COMMENT '學制二',
    system3 VARCHAR(20) DEFAULT NULL COMMENT '學制三',
    junior_high VARCHAR(200) DEFAULT NULL COMMENT '就讀或畢業國中',
    current_grade VARCHAR(20) DEFAULT NULL COMMENT '目前年級',
    line_id VARCHAR(100) DEFAULT NULL COMMENT 'LineID',
    facebook VARCHAR(200) DEFAULT NULL COMMENT 'Facebook',
    recommended_teacher VARCHAR(100) DEFAULT NULL COMMENT '推薦老師',
    remarks TEXT DEFAULT NULL COMMENT '備註',
    captcha VARCHAR(10) DEFAULT NULL COMMENT '驗證碼',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='就讀意願登錄表';



























