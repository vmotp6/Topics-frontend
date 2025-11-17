-- 創建餐廳評價和留言表
CREATE TABLE IF NOT EXISTS restaurant_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_name VARCHAR(255) NOT NULL COMMENT '餐廳名稱',
    restaurant_address VARCHAR(500) NOT NULL COMMENT '餐廳地址',
    restaurant_lat DECIMAL(10, 8) DEFAULT NULL COMMENT '餐廳緯度',
    restaurant_lng DECIMAL(11, 8) DEFAULT NULL COMMENT '餐廳經度',
    restaurant_place_id VARCHAR(255) DEFAULT NULL COMMENT 'Google Places ID',
    rating INT NOT NULL COMMENT '評分 (1-5)',
    review_content TEXT NOT NULL COMMENT '評價內容',
    author_name VARCHAR(100) NOT NULL COMMENT '評價者姓名',
    author_email VARCHAR(255) NOT NULL COMMENT '評價者Email',
    author_department VARCHAR(100) DEFAULT NULL COMMENT '評價者科系',
    author_grade VARCHAR(50) DEFAULT NULL COMMENT '評價者年級',
    author_contact VARCHAR(100) DEFAULT NULL COMMENT '聯絡方式',
    delivery_rating INT DEFAULT NULL COMMENT '外送評分 (1-5)',
    price_level INT DEFAULT NULL COMMENT '價格等級 (1-4)',
    is_published BOOLEAN DEFAULT TRUE COMMENT '是否發布',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '創建時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    view_count INT DEFAULT 0 COMMENT '瀏覽次數',
    like_count INT DEFAULT 0 COMMENT '點讚次數',
    INDEX idx_restaurant_name (restaurant_name),
    INDEX idx_author_email (author_email),
    INDEX idx_is_published (is_published),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='餐廳評價表';

-- 創建餐廳評價點讚表
CREATE TABLE IF NOT EXISTS restaurant_review_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL COMMENT '評價ID',
    user_email VARCHAR(255) NOT NULL COMMENT '點讚用戶Email',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '創建時間',
    UNIQUE KEY unique_like (review_id, user_email),
    FOREIGN KEY (review_id) REFERENCES restaurant_reviews(id) ON DELETE CASCADE,
    INDEX idx_review_id (review_id),
    INDEX idx_user_email (user_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='餐廳評價點讚表';

-- 創建餐廳留言表（針對特定餐廳的留言）
CREATE TABLE IF NOT EXISTS restaurant_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL COMMENT '關聯的評價ID',
    comment_content TEXT NOT NULL COMMENT '留言內容',
    author_name VARCHAR(100) NOT NULL COMMENT '留言者姓名',
    author_email VARCHAR(255) NOT NULL COMMENT '留言者Email',
    is_published BOOLEAN DEFAULT TRUE COMMENT '是否發布',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '創建時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    FOREIGN KEY (review_id) REFERENCES restaurant_reviews(id) ON DELETE CASCADE,
    INDEX idx_review_id (review_id),
    INDEX idx_author_email (author_email),
    INDEX idx_is_published (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='餐廳留言表';

