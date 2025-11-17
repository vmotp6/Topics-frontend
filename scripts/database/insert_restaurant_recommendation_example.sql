-- 餐廳推薦功能範例數據
-- 這個 SQL 文件包含一些範例數據，幫助您了解如何使用餐廳推薦功能

USE topics_good;

-- 首先確保表結構存在
-- 如果 senior_messages 表還沒有餐廳相關欄位，需要先執行 add_restaurant_type_to_senior_messages.sql

-- 範例 1: 推薦義式餐廳
INSERT INTO senior_messages (
    title, 
    content, 
    author_name, 
    author_email, 
    author_department, 
    author_grade, 
    author_contact, 
    message_type, 
    author_grade_year,
    restaurant_name,
    restaurant_address,
    restaurant_lat,
    restaurant_lng,
    restaurant_place_id,
    restaurant_rating,
    delivery_rating,
    price_level,
    is_published
) VALUES (
    '推薦內湖超好吃的義式小廚房！',
    '最近在學校附近發現了一間超棒的義式餐廳「冰到 MoonDay 義式小廚房」！

推薦理由：
1. 價格實惠，學生也能負擔得起（平均 $150-200）
2. 義大利麵和披薩都很道地，醬汁濃郁
3. 老闆人很親切，服務態度很好
4. 有提供外送服務，下雨天不想出門很方便

推薦菜色：
- 奶油培根義大利麵（$150）- 醬汁濃郁，培根很香
- 瑪格麗特披薩（$200）- 餅皮薄脆，起司很香
- 每日特餐（$120）- 每天都有不同的特餐，CP值很高

位置就在民權東路六段，從學校走路約 10 分鐘，非常方便！

營業時間：11:30-21:00
電話：02-2793-1234',
    '張小明',
    '11012345@stu.ukn.edu.tw',
    '資訊管理系',
    '一年級',
    'Line: xiaoming123',
    '推薦餐廳',
    110,
    '冰到 MoonDay 義式小廚房',
    '台北市內湖區民權東路六段296巷36號',
    25.0685,
    121.6123,
    'ChIJExample123',
    5,
    4,
    2,
    1
);

-- 範例 2: 推薦日式餐廳
INSERT INTO senior_messages (
    title, 
    content, 
    author_name, 
    author_email, 
    author_department, 
    author_grade, 
    author_contact, 
    message_type, 
    author_grade_year,
    restaurant_name,
    restaurant_address,
    restaurant_lat,
    restaurant_lng,
    restaurant_place_id,
    restaurant_rating,
    delivery_rating,
    price_level,
    is_published
) VALUES (
    '學校附近CP值超高的日式料理！',
    '推薦「酌屋」這間日式餐廳給大家！

優點：
- 價格合理，定食套餐 $180-250
- 食材新鮮，生魚片很新鮮
- 份量足夠，男生也能吃飽
- 環境乾淨，適合聚餐

推薦菜色：
- 綜合生魚片定食（$250）
- 炸豬排定食（$200）
- 親子丼（$180）

位置在金湖路，從學校騎車約 5 分鐘。

小提醒：用餐時間人比較多，建議避開尖峰時段。',
    '李小華',
    '10987654@stu.ukn.edu.tw',
    '商務管理系',
    '二年級',
    'email: lihua@example.com',
    '推薦餐廳',
    109,
    '酌屋',
    '台北市內湖區金湖路51號',
    25.0720,
    121.6085,
    'ChIJExample456',
    4,
    3,
    2,
    1
);

-- 範例 3: 推薦連鎖餐廳（無外送）
INSERT INTO senior_messages (
    title, 
    content, 
    author_name, 
    author_email, 
    author_department, 
    author_grade, 
    author_contact, 
    message_type, 
    author_grade_year,
    restaurant_name,
    restaurant_address,
    restaurant_lat,
    restaurant_lng,
    restaurant_place_id,
    restaurant_rating,
    delivery_rating,
    price_level,
    is_published
) VALUES (
    '樂雅樂餐廳 - 適合聚餐的好地方',
    '推薦「樂雅樂餐廳 內湖店」給需要聚餐的同學們！

這是一間連鎖餐廳，品質穩定，適合：
- 小組聚餐
- 慶生
- 約會
- 家庭聚餐

推薦菜色：
- 漢堡排套餐（$280）
- 牛排套餐（$350）
- 義大利麵套餐（$250）

環境舒適，服務不錯，但價格稍高一些。

注意：這間餐廳沒有外送服務，需要到店用餐。',
    '王小美',
    '11123456@stu.ukn.edu.tw',
    '餐飲管理系',
    '一年級',
    'Line: wangmei456',
    '推薦餐廳',
    111,
    '樂雅樂餐廳 內湖店',
    '台北市內湖區民權東路六段491號',
    25.0750,
    121.6100,
    'ChIJExample789',
    4,
    NULL,
    3,
    1
);

-- 範例評價數據（需要先有上面的餐廳推薦）
-- 注意：message_id 需要根據實際插入的數據調整

-- 為第一個餐廳（冰到 MoonDay）添加評價
SET @message_id_1 = LAST_INSERT_ID();

-- 確保 restaurant_reviews 表存在
CREATE TABLE IF NOT EXISTS restaurant_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_name VARCHAR(255) NOT NULL,
    restaurant_address VARCHAR(500) NOT NULL,
    restaurant_lat DECIMAL(10, 8) DEFAULT NULL,
    restaurant_lng DECIMAL(11, 8) DEFAULT NULL,
    restaurant_place_id VARCHAR(255) DEFAULT NULL,
    rating INT NOT NULL,
    review_content TEXT NOT NULL,
    author_name VARCHAR(100) NOT NULL,
    author_email VARCHAR(255) NOT NULL,
    author_department VARCHAR(100) DEFAULT NULL,
    author_grade VARCHAR(50) DEFAULT NULL,
    author_contact VARCHAR(100) DEFAULT NULL,
    delivery_rating INT DEFAULT NULL,
    price_level INT DEFAULT NULL,
    message_id INT DEFAULT NULL,
    is_published BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    view_count INT DEFAULT 0,
    like_count INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入範例評價
INSERT INTO restaurant_reviews (
    restaurant_name,
    restaurant_address,
    restaurant_lat,
    restaurant_lng,
    restaurant_place_id,
    rating,
    review_content,
    author_name,
    author_email,
    delivery_rating,
    message_id
) VALUES (
    '冰到 MoonDay 義式小廚房',
    '台北市內湖區民權東路六段296巷36號',
    25.0685,
    121.6123,
    'ChIJExample123',
    5,
    '我也去吃過這間！真的很好吃！

優點：
- 義大利麵的醬汁很濃郁，不會太膩
- 披薩的餅皮很薄很脆，起司很香
- 價格真的很實惠，學生也能負擔
- 老闆人很好，會關心用餐體驗

小缺點：
- 用餐時間人比較多，可能要等位
- 外送時間有時候會比較久（約 30-40 分鐘）

整體來說還是很推薦！CP值很高！👍',
    '陳小強',
    '11098765@stu.ukn.edu.tw',
    4,
    @message_id_1
);

-- 插入範例留言（需要先有評價）
SET @review_id_1 = LAST_INSERT_ID();

-- 確保 restaurant_comments 表存在
CREATE TABLE IF NOT EXISTS restaurant_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    comment_content TEXT NOT NULL,
    author_name VARCHAR(100) NOT NULL,
    author_email VARCHAR(255) NOT NULL,
    is_published BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES restaurant_reviews(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入範例留言
INSERT INTO restaurant_comments (
    review_id,
    comment_content,
    author_name,
    author_email
) VALUES (
    @review_id_1,
    '請問外送大概要等多久？我也想試試看外送服務！',
    '林小芳',
    '11111111@stu.ukn.edu.tw'
),
(
    @review_id_1,
    '我上次叫外送大概等了 35 分鐘，建議可以提前訂！',
    '張小明',
    '11012345@stu.ukn.edu.tw'
);

-- 顯示插入的數據
SELECT 
    sm.id,
    sm.title,
    sm.restaurant_name,
    sm.restaurant_address,
    sm.restaurant_rating,
    sm.delivery_rating,
    sm.price_level,
    COUNT(rr.id) as review_count
FROM senior_messages sm
LEFT JOIN restaurant_reviews rr ON sm.id = rr.message_id
WHERE sm.message_type = '推薦餐廳'
GROUP BY sm.id
ORDER BY sm.created_at DESC;

