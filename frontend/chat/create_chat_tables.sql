-- 創建私聊訊息表
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
);

-- 創建群組表
CREATE TABLE IF NOT EXISTS chat_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(255) NOT NULL,
    created_by VARCHAR(255) NOT NULL,
    department VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_by (created_by)
);

-- 創建群組成員表
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
);

-- 創建群組訊息表
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
);

