-- =====================================================
-- 公告相關連結和檔案資料表擴充腳本
-- 用於儲存多個相關連結URL和上傳的檔案
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- 建立公告相關連結表（支援多個URL）
CREATE TABLE IF NOT EXISTS bulletin_urls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bulletin_id INT NOT NULL COMMENT '公告ID（外鍵關聯到 bulletin_board 表）',
    url VARCHAR(500) NOT NULL COMMENT '連結URL',
    title VARCHAR(255) NULL COMMENT '連結標題（可選）',
    display_order INT DEFAULT 0 COMMENT '顯示順序',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    INDEX idx_bulletin_id (bulletin_id),
    INDEX idx_display_order (display_order),
    FOREIGN KEY (bulletin_id) REFERENCES bulletin_board(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告相關連結表';

-- 建立公告檔案表（支援多個檔案）
CREATE TABLE IF NOT EXISTS bulletin_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bulletin_id INT NOT NULL COMMENT '公告ID（外鍵關聯到 bulletin_board 表）',
    file_path VARCHAR(500) NOT NULL COMMENT '檔案路徑',
    original_filename VARCHAR(255) NOT NULL COMMENT '原始檔案名稱',
    file_size INT NULL COMMENT '檔案大小（位元組）',
    file_type VARCHAR(100) NULL COMMENT '檔案類型（MIME type）',
    display_order INT DEFAULT 0 COMMENT '顯示順序',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    INDEX idx_bulletin_id (bulletin_id),
    INDEX idx_display_order (display_order),
    FOREIGN KEY (bulletin_id) REFERENCES bulletin_board(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告檔案表';

SET FOREIGN_KEY_CHECKS = 1;
