-- =====================================================
-- 修復所有使用 username/email 而非 user_id 的表
-- 將這些表正規化為使用 user_id
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 第一部分：正規化 user_activity 表
-- =====================================================

-- 創建正規化版本
CREATE TABLE IF NOT EXISTS user_activity_normalized (
    user_id INT NOT NULL PRIMARY KEY COMMENT '關聯到 user 表（主鍵）',
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_chat_check TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notification_preferences JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_last_seen (last_seen),
    INDEX idx_last_chat_check (last_chat_check),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的用戶活動表';

-- 遷移數據（僅在原始表存在時）
-- 檢查 user_activity 表是否存在
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables 
    WHERE table_schema = DATABASE() AND table_name = 'user_activity');

SET @sql = IF(@table_exists > 0,
    'INSERT IGNORE INTO user_activity_normalized (
        user_id, last_seen, last_chat_check, notification_preferences, created_at, updated_at
    )
    SELECT 
        u.id AS user_id,
        ua.last_seen,
        ua.last_chat_check,
        ua.notification_preferences,
        ua.created_at,
        ua.updated_at
    FROM user_activity ua
    JOIN user u ON u.username = ua.username
    ON DUPLICATE KEY UPDATE
        last_seen = VALUES(last_seen),
        last_chat_check = VALUES(last_chat_check),
        notification_preferences = VALUES(notification_preferences),
        updated_at = VALUES(updated_at)',
    'SELECT ''user_activity table does not exist, skipping migration'' AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 創建向後兼容視圖
CREATE OR REPLACE VIEW user_activity_view AS
SELECT 
    ua.user_id AS id,  -- 為了兼容
    u.username,
    ua.last_seen,
    ua.last_chat_check,
    ua.notification_preferences,
    ua.created_at,
    ua.updated_at
FROM user_activity_normalized ua
JOIN user u ON ua.user_id = u.id;

-- =====================================================
-- 第二部分：正規化 unread_notifications 表
-- =====================================================

CREATE TABLE IF NOT EXISTS unread_notifications_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '關聯到 user 表',
    notification_type_id INT NOT NULL COMMENT '關聯到 notification_types 表',
    sender_user_id INT NULL COMMENT '關聯到 user 表（發送者）',
    message_preview TEXT,
    chat_url VARCHAR(500),
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE,
    INDEX idx_user_id (user_id),
    INDEX idx_sender_user_id (sender_user_id),
    INDEX idx_sent_at (sent_at),
    INDEX idx_is_read (is_read),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_user_id) REFERENCES user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的未讀通知表';

-- 創建通知類型表（如果不存在）
-- 注意：這個表應該在 complete_normalize_to_3nf.sql 中創建，但這裡也創建以防萬一
CREATE TABLE IF NOT EXISTS notification_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT '類型代碼',
    name VARCHAR(100) NOT NULL COMMENT '類型名稱',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通知類型資料表';

INSERT IGNORE INTO notification_types (code, name) VALUES
('PRIVATE_MESSAGE', '私聊訊息'),
('GROUP_MESSAGE', '群組訊息'),
('SYSTEM_ALERT', '系統通知');

-- 遷移數據（僅在原始表存在時）
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables 
    WHERE table_schema = DATABASE() AND table_name = 'unread_notifications');

SET @sql = IF(@table_exists > 0,
    'INSERT IGNORE INTO unread_notifications_normalized (
        user_id, notification_type_id, sender_user_id, message_preview, chat_url, sent_at, is_read
    )
    SELECT 
        u1.id AS user_id,
        CASE un.notification_type
            WHEN ''private_message'' THEN (SELECT id FROM notification_types WHERE code = ''PRIVATE_MESSAGE'')
            WHEN ''group_message'' THEN (SELECT id FROM notification_types WHERE code = ''GROUP_MESSAGE'')
            WHEN ''system_alert'' THEN (SELECT id FROM notification_types WHERE code = ''SYSTEM_ALERT'')
            ELSE (SELECT id FROM notification_types WHERE code = ''SYSTEM_ALERT'')
        END AS notification_type_id,
        u2.id AS sender_user_id,
        un.message_preview,
        un.chat_url,
        un.sent_at,
        un.is_read
    FROM unread_notifications un
    JOIN user u1 ON u1.username = un.username
    LEFT JOIN user u2 ON u2.username = un.sender_username',
    'SELECT ''unread_notifications table does not exist, skipping migration'' AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 創建視圖
CREATE OR REPLACE VIEW unread_notifications_view AS
SELECT 
    un.id,
    u1.username,
    nt.name AS notification_type,
    u2.username AS sender_username,
    un.message_preview,
    un.chat_url,
    un.sent_at,
    un.is_read
FROM unread_notifications_normalized un
JOIN user u1 ON un.user_id = u1.id
LEFT JOIN user u2 ON un.sender_user_id = u2.id
JOIN notification_types nt ON un.notification_type_id = nt.id;

-- =====================================================
-- 第三部分：正規化 notification_sent_log 表
-- =====================================================

CREATE TABLE IF NOT EXISTS notification_sent_log_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '關聯到 user 表',
    notification_type VARCHAR(100) NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    email_sent BOOLEAN DEFAULT FALSE,
    INDEX idx_user_id (user_id),
    INDEX idx_sent_at (sent_at),
    INDEX idx_notification_type (notification_type),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的通知發送記錄表';

-- 遷移數據（僅在原始表存在時）
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables 
    WHERE table_schema = DATABASE() AND table_name = 'notification_sent_log');

SET @sql = IF(@table_exists > 0,
    'INSERT IGNORE INTO notification_sent_log_normalized (
        user_id, notification_type, sent_at, email_sent
    )
    SELECT 
        u.id AS user_id,
        nsl.notification_type,
        nsl.sent_at,
        nsl.email_sent
    FROM notification_sent_log nsl
    JOIN user u ON u.username = nsl.username',
    'SELECT ''notification_sent_log table does not exist, skipping migration'' AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 創建視圖
CREATE OR REPLACE VIEW notification_sent_log_view AS
SELECT 
    nsl.id,
    u.username,
    nsl.notification_type,
    nsl.sent_at,
    nsl.email_sent
FROM notification_sent_log_normalized nsl
JOIN user u ON nsl.user_id = u.id;

-- =====================================================
-- 第四部分：正規化 message_likes 表（最嚴重）
-- =====================================================

CREATE TABLE IF NOT EXISTS message_likes_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL COMMENT '關聯到 senior_messages 表',
    user_id INT NOT NULL COMMENT '關聯到 user 表',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (message_id, user_id),
    INDEX idx_message_id (message_id),
    INDEX idx_user_id (user_id),
    FOREIGN KEY (message_id) REFERENCES senior_messages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的訊息點讚表';

-- 遷移數據（僅在原始表存在時）
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables 
    WHERE table_schema = DATABASE() AND table_name = 'message_likes');

SET @sql = IF(@table_exists > 0,
    'INSERT IGNORE INTO message_likes_normalized (
        message_id, user_id, created_at
    )
    SELECT 
        ml.message_id,
        u.id AS user_id,
        ml.created_at
    FROM message_likes ml
    LEFT JOIN user u ON u.email = ml.user_email',
    'SELECT ''message_likes table does not exist, skipping migration'' AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 創建視圖
CREATE OR REPLACE VIEW message_likes_view AS
SELECT 
    ml.id,
    ml.message_id,
    u.username,
    u.email AS user_email,
    ml.created_at
FROM message_likes_normalized ml
JOIN user u ON ml.user_id = u.id;

SET FOREIGN_KEY_CHECKS = 1;

SELECT '所有 user 引用已正規化為 user_id' AS message;

