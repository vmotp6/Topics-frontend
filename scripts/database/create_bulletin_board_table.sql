-- =====================================================
-- 公告欄資料表設置腳本
-- 用於儲存招生公告、考試資訊、面試通知等公告內容
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- 建立公告類型參考表（可選，用於正規化）
CREATE TABLE IF NOT EXISTS bulletin_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT '類型代碼',
    name VARCHAR(100) NOT NULL COMMENT '類型名稱',
    description TEXT NULL COMMENT '類型描述',
    color VARCHAR(50) NULL COMMENT '顯示顏色（CSS 類名或顏色值）',
    display_order INT DEFAULT 0 COMMENT '顯示順序',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告類型參考表';

-- 插入預設公告類型
INSERT INTO bulletin_types (code, name, description, color, display_order) VALUES
('exam', '考試資訊', '入學考試、報名日期等考試相關資訊', 'exam', 1),
('interview', '面試通知', '面試時間、地點等面試相關通知', 'interview', 2),
('result', '錄取結果', '錄取結果、報到通知等結果公告', 'result', 3),
('general', '一般公告', '其他一般性公告與通知', 'general', 4)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 建立公告狀態參考表（可選，用於正規化）
CREATE TABLE IF NOT EXISTS bulletin_statuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT '狀態代碼',
    name VARCHAR(100) NOT NULL COMMENT '狀態名稱',
    description TEXT NULL COMMENT '狀態描述',
    display_order INT DEFAULT 0 COMMENT '顯示順序',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告狀態參考表';

-- 插入預設公告狀態
INSERT INTO bulletin_statuses (code, name, description, display_order) VALUES
('draft', '草稿', '尚未發布的草稿', 1),
('published', '已發布', '已發布的公告', 2),
('archived', '已歸檔', '已歸檔的舊公告', 3)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 建立公告資料表
CREATE TABLE IF NOT EXISTS bulletin_board (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '發布者用戶ID（外鍵關聯到 user 表）',
    title VARCHAR(255) NOT NULL COMMENT '公告標題',
    content TEXT NOT NULL COMMENT '公告內容/描述',
    type_code VARCHAR(50) NOT NULL DEFAULT 'general' COMMENT '公告類型（exam, interview, result, general）',
    status_code VARCHAR(50) NOT NULL DEFAULT 'published' COMMENT '公告狀態（draft, published, archived）',
    source VARCHAR(255) NULL COMMENT '公告來源（例如：招生公告、教務處等）',
    start_date DATE NULL COMMENT '公告開始日期',
    end_date DATE NULL COMMENT '公告結束日期',
    image_url VARCHAR(500) NULL COMMENT '公告圖片URL（可選）',
    link_url VARCHAR(500) NULL COMMENT '相關連結URL（可選）',
    view_count INT DEFAULT 0 COMMENT '瀏覽次數',
    is_pinned BOOLEAN DEFAULT FALSE COMMENT '是否置頂',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    INDEX idx_user_id (user_id),
    INDEX idx_type_code (type_code),
    INDEX idx_status_code (status_code),
    INDEX idx_start_date (start_date),
    INDEX idx_end_date (end_date),
    INDEX idx_created_at (created_at),
    INDEX idx_is_pinned (is_pinned),
    INDEX idx_type_status (type_code, status_code),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (type_code) REFERENCES bulletin_types(code) ON DELETE RESTRICT,
    FOREIGN KEY (status_code) REFERENCES bulletin_statuses(code) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告欄資料表';

-- 建立公告瀏覽記錄表（可選，用於追蹤用戶瀏覽記錄）
CREATE TABLE IF NOT EXISTS bulletin_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bulletin_id INT NOT NULL COMMENT '公告ID（外鍵關聯到 bulletin_board 表）',
    user_id INT NULL COMMENT '瀏覽者用戶ID（外鍵關聯到 user 表，可為NULL表示匿名瀏覽）',
    ip_address VARCHAR(45) NULL COMMENT 'IP地址',
    user_agent VARCHAR(500) NULL COMMENT '用戶代理（瀏覽器資訊）',
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '瀏覽時間',
    INDEX idx_bulletin_id (bulletin_id),
    INDEX idx_user_id (user_id),
    INDEX idx_viewed_at (viewed_at),
    FOREIGN KEY (bulletin_id) REFERENCES bulletin_board(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告瀏覽記錄表';

SET FOREIGN_KEY_CHECKS = 1;
