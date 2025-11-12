-- =====================================================
-- 正規化其他聊天相關表
-- 處理 chat_history, group_chat_members, group_chat_messages, group_info
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. 正規化 chat_history 表（如果存在）
-- =====================================================

-- 創建正規化版本
CREATE TABLE IF NOT EXISTS chat_history_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '關聯到 user 表',
    message TEXT NOT NULL,
    message_type_id INT NOT NULL DEFAULT 1 COMMENT '關聯到 message_types 表',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_message_type (message_type_id),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (message_type_id) REFERENCES message_types(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正規化後的聊天記錄表';

-- 遷移數據（如果原始表存在）
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables 
    WHERE table_schema = DATABASE() AND table_name = 'chat_history');

SET @sql = IF(@table_exists > 0,
    'INSERT IGNORE INTO chat_history_normalized (
        user_id, message, message_type_id, created_at
    )
    SELECT 
        u.id AS user_id,
        ch.message,
        (SELECT id FROM message_types WHERE code = ''TEXT'') AS message_type_id,
        COALESCE(ch.created_at, ch.timestamp, NOW()) AS created_at
    FROM chat_history ch
    LEFT JOIN user u ON u.username = ch.username',
    'SELECT ''chat_history table does not exist, skipping migration'' AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 創建視圖
CREATE OR REPLACE VIEW chat_history_view AS
SELECT 
    ch.id,
    u.username,
    ch.message,
    mt.name AS message_type,
    ch.created_at
FROM chat_history_normalized ch
JOIN user u ON ch.user_id = u.id
LEFT JOIN message_types mt ON ch.message_type_id = mt.id;

-- =====================================================
-- 2. 正規化 group_chat_members 表（如果存在）
--    注意：這個表應該對應到 group_members_normalized
-- =====================================================

-- 檢查 group_chat_members 是否存在
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables 
    WHERE table_schema = DATABASE() AND table_name = 'group_chat_members');

SET @sql = IF(@table_exists > 0,
    'INSERT IGNORE INTO group_members_normalized (
        group_id, user_id, role_type_id, joined_at
    )
    SELECT 
        -- 如果 group_chat_members 使用 VARCHAR group_id，需要轉換
        CASE 
            WHEN gcm.group_id REGEXP ''^[0-9]+$'' THEN CAST(gcm.group_id AS UNSIGNED)
            ELSE NULL  -- 如果 group_id 不是數字，無法直接關聯，需要額外處理
        END AS group_id,
        u.id AS user_id,
        (SELECT id FROM role_types WHERE code = ''MEMBER'') AS role_type_id,
        COALESCE(gcm.joined_at, NOW()) AS joined_at
    FROM group_chat_members gcm
    LEFT JOIN user u ON u.username = gcm.member_username
    WHERE gcm.group_id REGEXP ''^[0-9]+$''  -- 只遷移數字格式的 group_id
    AND u.id IS NOT NULL',
    'SELECT ''group_chat_members table does not exist, skipping migration'' AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 3. 正規化 group_chat_messages 表（如果存在）
--    這個表應該對應到 group_messages_normalized
-- =====================================================

SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables 
    WHERE table_schema = DATABASE() AND table_name = 'group_chat_messages');

SET @sql = IF(@table_exists > 0,
    'INSERT IGNORE INTO group_messages_normalized (
        group_id, from_user_id, message, message_type_id, created_at
    )
    SELECT 
        CASE 
            WHEN gcm.group_id REGEXP ''^[0-9]+$'' THEN CAST(gcm.group_id AS UNSIGNED)
            ELSE NULL
        END AS group_id,
        u.id AS from_user_id,
        gcm.message,
        (SELECT id FROM message_types WHERE code = ''TEXT'') AS message_type_id,
        COALESCE(gcm.timestamp, gcm.created_at, NOW()) AS created_at
    FROM group_chat_messages gcm
    LEFT JOIN user u ON u.username = gcm.from_user
    WHERE gcm.group_id REGEXP ''^[0-9]+$''
    AND u.id IS NOT NULL',
    'SELECT ''group_chat_messages table does not exist, skipping migration'' AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 4. 正規化 group_info 表（如果存在）
--    這個表應該對應到 chat_groups_normalized
-- =====================================================

SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables 
    WHERE table_schema = DATABASE() AND table_name = 'group_info');

SET @sql = IF(@table_exists > 0,
    'INSERT IGNORE INTO chat_groups_normalized (
        id, group_name, created_by_user_id, department_id, created_at
    )
    SELECT 
        CASE 
            WHEN gi.group_id REGEXP ''^[0-9]+$'' THEN CAST(gi.group_id AS UNSIGNED)
            ELSE NULL
        END AS id,
        gi.group_name,
        u.id AS created_by_user_id,
        COALESCE(
            (SELECT id FROM departments WHERE name = gi.department LIMIT 1),
            NULL
        ) AS department_id,
        COALESCE(gi.created_at, NOW()) AS created_at
    FROM group_info gi
    LEFT JOIN user u ON u.username = gi.created_by
    WHERE gi.group_id REGEXP ''^[0-9]+$''
    AND u.id IS NOT NULL',
    'SELECT ''group_info table does not exist, skipping migration'' AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

SELECT '其他聊天表已正規化' AS message;

