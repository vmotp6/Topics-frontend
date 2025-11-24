-- =====================================================
-- 修復缺失的 3NF 正規化表
-- 此腳本專門用於創建缺失的基礎表和正規化表
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 創建缺失的基礎參考表
-- =====================================================

-- 1. message_types 表（如果不存在）
CREATE TABLE IF NOT EXISTS message_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT '類型代碼',
    name VARCHAR(100) NOT NULL COMMENT '類型名稱',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='訊息類型資料表';

INSERT IGNORE INTO message_types (code, name) VALUES
('TEXT', '文字訊息'),
('IMAGE', '圖片'),
('FILE', '檔案'),
('SYSTEM', '系統訊息');

-- 2. role_types 表（如果不存在）
CREATE TABLE IF NOT EXISTS role_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT '角色代碼',
    name VARCHAR(100) NOT NULL COMMENT '角色名稱',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色類型資料表';

INSERT IGNORE INTO role_types (code, name) VALUES
('TEACHER', '老師'),
('STUDENT', '學生'),
('ADMIN', '管理員'),
('STAFF', '行政人員'),
('MEMBER', '成員');

-- =====================================================
-- 創建缺失的正規化表
-- =====================================================

-- 3. student_normalized 表
CREATE TABLE IF NOT EXISTS student_normalized (
    user_id INT NOT NULL PRIMARY KEY COMMENT '關聯到 user 表（主鍵）',
    name VARCHAR(255) NOT NULL,
    student_id VARCHAR(50) UNIQUE,
    department_id INT NULL COMMENT '關聯到 departments 表',
    grade_id INT NULL COMMENT '關聯到 grades 表',
    class_name VARCHAR(100) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student_id (student_id),
    INDEX idx_department_id (department_id),
    INDEX idx_grade_id (grade_id),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (grade_id) REFERENCES grades(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的學生表';

-- 4. teacher_normalized 表
CREATE TABLE IF NOT EXISTS teacher_normalized (
    user_id INT NOT NULL PRIMARY KEY COMMENT '關聯到 user 表（主鍵）',
    name VARCHAR(255) NOT NULL,
    department_id INT NULL COMMENT '關聯到 departments 表',
    phone VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_department_id (department_id),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的老師表';

-- 5. chat_groups_normalized 表
CREATE TABLE IF NOT EXISTS chat_groups_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(255) NOT NULL,
    created_by_user_id INT NOT NULL COMMENT '關聯到 user 表',
    department_id INT NULL COMMENT '關聯到 departments 表',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_created_by (created_by_user_id),
    INDEX idx_department_id (department_id),
    FOREIGN KEY (created_by_user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的聊天群組表';

-- 6. group_members_normalized 表
CREATE TABLE IF NOT EXISTS group_members_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL COMMENT '關聯到 chat_groups_normalized',
    user_id INT NOT NULL COMMENT '關聯到 user 表',
    role_type_id INT NOT NULL DEFAULT 6 COMMENT '關聯到 role_types 表（預設為成員）',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_group_id (group_id),
    INDEX idx_user_id (user_id),
    UNIQUE KEY unique_group_member (group_id, user_id),
    FOREIGN KEY (group_id) REFERENCES chat_groups_normalized(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (role_type_id) REFERENCES role_types(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的群組成員表';

-- 7. private_chat_history_normalized 表
CREATE TABLE IF NOT EXISTS private_chat_history_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_user_id INT NOT NULL COMMENT '關聯到 user 表',
    to_user_id INT NOT NULL COMMENT '關聯到 user 表',
    message TEXT NOT NULL,
    message_type_id INT NOT NULL DEFAULT 1 COMMENT '關聯到 message_types 表',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_from_user (from_user_id),
    INDEX idx_to_user (to_user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_message_type (message_type_id),
    FOREIGN KEY (from_user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (to_user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (message_type_id) REFERENCES message_types(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的私聊訊息表';

-- 8. group_messages_normalized 表
CREATE TABLE IF NOT EXISTS group_messages_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL COMMENT '關聯到 chat_groups_normalized',
    from_user_id INT NOT NULL COMMENT '關聯到 user 表',
    message TEXT NOT NULL,
    message_type_id INT NOT NULL DEFAULT 1 COMMENT '關聯到 message_types 表',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_group_id (group_id),
    INDEX idx_from_user (from_user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_message_type (message_type_id),
    FOREIGN KEY (group_id) REFERENCES chat_groups_normalized(id) ON DELETE CASCADE,
    FOREIGN KEY (from_user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (message_type_id) REFERENCES message_types(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的群組訊息表';

-- =====================================================
-- 添加缺失的外鍵約束
-- =====================================================

-- 檢查並添加 enrollment_applications_normalized 的外鍵
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'enrollment_applications_normalized'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    AND CONSTRAINT_NAME = 'fk_enrollment_identity');
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE enrollment_applications_normalized ADD CONSTRAINT fk_enrollment_identity FOREIGN KEY (identity_id) REFERENCES identities(id) ON DELETE RESTRICT',
    'SELECT ''FK fk_enrollment_identity already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'enrollment_applications_normalized'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    AND CONSTRAINT_NAME = 'fk_enrollment_gender');
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE enrollment_applications_normalized ADD CONSTRAINT fk_enrollment_gender FOREIGN KEY (gender_id) REFERENCES genders(id) ON DELETE SET NULL',
    'SELECT ''FK fk_enrollment_gender already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'enrollment_applications_normalized'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    AND CONSTRAINT_NAME = 'fk_enrollment_grade');
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE enrollment_applications_normalized ADD CONSTRAINT fk_enrollment_grade FOREIGN KEY (current_grade_id) REFERENCES grades(id) ON DELETE SET NULL',
    'SELECT ''FK fk_enrollment_grade already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'enrollment_applications_normalized'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    AND CONSTRAINT_NAME = 'fk_enrollment_teacher');
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE enrollment_applications_normalized ADD CONSTRAINT fk_enrollment_teacher FOREIGN KEY (recommended_teacher_user_id) REFERENCES teacher_normalized(user_id) ON DELETE SET NULL',
    'SELECT ''FK fk_enrollment_teacher already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 檢查並添加 cooperation_applications_normalized 的外鍵
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cooperation_applications_normalized'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    AND CONSTRAINT_NAME = 'fk_cooperation_teacher');
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE cooperation_applications_normalized ADD CONSTRAINT fk_cooperation_teacher FOREIGN KEY (teacher_user_id) REFERENCES teacher_normalized(user_id) ON DELETE RESTRICT',
    'SELECT ''FK fk_cooperation_teacher already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cooperation_applications_normalized'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    AND CONSTRAINT_NAME = 'fk_cooperation_department');
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE cooperation_applications_normalized ADD CONSTRAINT fk_cooperation_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT',
    'SELECT ''FK fk_cooperation_department already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

SELECT '✅ 缺失的表和約束已修復！' AS message;

