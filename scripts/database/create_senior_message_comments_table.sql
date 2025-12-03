-- =====================================================
-- 創建 senior_message_comments 表（留言表）
-- 用於儲存對 senior_messages 的留言
-- 符合正規化設計，使用外鍵關聯
-- =====================================================

USE topics_good;

-- 確保外鍵檢查已啟用
SET FOREIGN_KEY_CHECKS = 1;

-- 如果表已存在，先刪除（可選，用於重新創建）
-- DROP TABLE IF EXISTS senior_message_comments;

-- 創建留言表（符合 3NF 正規化）
CREATE TABLE IF NOT EXISTS senior_message_comments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY COMMENT '留言ID',
    message_id INT(11) NOT NULL COMMENT '被留言的訊息ID（外鍵關聯到 senior_messages）',
    user_id INT(11) NOT NULL COMMENT '留言的使用者ID（外鍵關聯到 user）',
    content TEXT NOT NULL COMMENT '留言內容',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '創建時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    
    -- 索引優化
    INDEX idx_message_id (message_id) COMMENT '訊息ID索引（用於快速查詢某個留言的所有回覆）',
    INDEX idx_user_id (user_id) COMMENT '用戶ID索引（用於快速查詢某個用戶的所有留言）',
    INDEX idx_created_at (created_at) COMMENT '創建時間索引（用於時間排序）',
    INDEX idx_message_user (message_id, user_id) COMMENT '複合索引（用於查詢某個用戶對某個留言的留言）',
    
    -- 外鍵約束（確保資料完整性）
    CONSTRAINT fk_comment_message 
        FOREIGN KEY (message_id) 
        REFERENCES senior_messages(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE 
        COMMENT '當留言被刪除時，相關的留言也會被刪除',
    
    CONSTRAINT fk_comment_user 
        FOREIGN KEY (user_id) 
        REFERENCES user(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE 
        COMMENT '當用戶被刪除時，相關的留言也會被刪除',
    
    -- 確保不會有重複的留言（同一用戶對同一留言只能留一次）
    -- 如果需要允許同一用戶多次留言，請移除此唯一約束
    -- UNIQUE KEY uk_message_user (message_id, user_id) COMMENT '確保同一用戶對同一留言只能留一次'
    
) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci 
  COMMENT='學長姐留言的留言表（正規化設計）';


