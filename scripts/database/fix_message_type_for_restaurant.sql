-- 修復 message_type 欄位以支援「推薦餐廳」分類
-- 如果 message_type 是 ENUM 類型，需要先改為 VARCHAR

USE topics_good;

-- 檢查 message_type 欄位的類型
-- 如果是 ENUM，需要先改為 VARCHAR 以支援新的分類

-- 方法 1: 如果欄位是 ENUM，改為 VARCHAR
ALTER TABLE senior_messages 
MODIFY COLUMN message_type VARCHAR(50) DEFAULT '經驗分享' 
COMMENT '留言類型：經驗分享、學習建議、生活指南、就業資訊、推薦餐廳、其他';

-- 方法 2: 更新現有數據（如果有 message_type 為 NULL 的記錄）
UPDATE senior_messages 
SET message_type = '其他' 
WHERE message_type IS NULL OR message_type = '';

-- 方法 3: 確保所有現有記錄都有 message_type
UPDATE senior_messages 
SET message_type = COALESCE(message_type, '其他')
WHERE message_type IS NULL;

-- 顯示更新後的數據統計
SELECT 
    message_type,
    COUNT(*) as count
FROM senior_messages
GROUP BY message_type
ORDER BY count DESC;

