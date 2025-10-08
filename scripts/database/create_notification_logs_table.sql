-- 創建通知日誌表
CREATE TABLE IF NOT EXISTS notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recommendation_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL,
    status ENUM('sent', 'failed') NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    error_message TEXT,
    INDEX idx_recommendation_id (recommendation_id),
    INDEX idx_notification_type (notification_type),
    INDEX idx_sent_at (sent_at),
    FOREIGN KEY (recommendation_id) REFERENCES admission_recommendations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入測試數據
INSERT INTO notification_logs (recommendation_id, notification_type, email, status, sent_at) VALUES
(1, 'approval_notification', 'test@example.com', 'sent', NOW()),
(2, 'enrollment_notification', 'test2@example.com', 'sent', NOW());
