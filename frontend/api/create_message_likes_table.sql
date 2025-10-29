-- 創建 message_likes 表來記錄用戶對留言的點讚狀態
CREATE TABLE IF NOT EXISTS message_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    user_email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (message_id, user_email),
    FOREIGN KEY (message_id) REFERENCES senior_messages(id) ON DELETE CASCADE,
    INDEX idx_message_id (message_id),
    INDEX idx_user_email (user_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

