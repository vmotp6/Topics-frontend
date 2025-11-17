-- 為 senior_messages 表添加推薦餐廳類型支持
-- 如果表已存在，需要修改 message_type 欄位

-- 方法1: 如果使用 ENUM，需要先刪除再重新創建（注意：這會丟失數據，請先備份）
-- ALTER TABLE senior_messages MODIFY COLUMN message_type ENUM('經驗分享', '學習建議', '生活指南', '就業資訊', '推薦餐廳', '其他') DEFAULT '經驗分享';

-- 方法2: 更安全的方式 - 先檢查現有結構，然後使用 VARCHAR 替代 ENUM
ALTER TABLE senior_messages MODIFY COLUMN message_type VARCHAR(50) DEFAULT '經驗分享';

-- 添加餐廳相關欄位到 senior_messages 表（如果選擇將餐廳推薦作為留言類型）
ALTER TABLE senior_messages 
ADD COLUMN IF NOT EXISTS restaurant_name VARCHAR(255) DEFAULT NULL COMMENT '餐廳名稱（僅用於推薦餐廳類型）',
ADD COLUMN IF NOT EXISTS restaurant_address VARCHAR(500) DEFAULT NULL COMMENT '餐廳地址',
ADD COLUMN IF NOT EXISTS restaurant_lat DECIMAL(10, 8) DEFAULT NULL COMMENT '餐廳緯度',
ADD COLUMN IF NOT EXISTS restaurant_lng DECIMAL(11, 8) DEFAULT NULL COMMENT '餐廳經度',
ADD COLUMN IF NOT EXISTS restaurant_place_id VARCHAR(255) DEFAULT NULL COMMENT 'Google Places ID',
ADD COLUMN IF NOT EXISTS restaurant_rating INT DEFAULT NULL COMMENT '餐廳評分 (1-5)',
ADD COLUMN IF NOT EXISTS delivery_rating INT DEFAULT NULL COMMENT '外送評分 (1-5)',
ADD COLUMN IF NOT EXISTS price_level INT DEFAULT NULL COMMENT '價格等級 (1-4)';

