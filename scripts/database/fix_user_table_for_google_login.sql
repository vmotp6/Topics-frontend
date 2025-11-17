-- 修復 user 表以支援 Google 登入
-- 添加 google_id 和 profile_picture 欄位

USE topics_good;

-- 檢查並添加 google_id 欄位
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'topics_good' 
    AND TABLE_NAME = 'user' 
    AND COLUMN_NAME = 'google_id'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE user ADD COLUMN google_id VARCHAR(255) NULL UNIQUE COMMENT "Google 用戶 ID" AFTER email',
    'SELECT "google_id 欄位已存在" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 檢查並添加 profile_picture 欄位
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'topics_good' 
    AND TABLE_NAME = 'user' 
    AND COLUMN_NAME = 'profile_picture'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE user ADD COLUMN profile_picture TEXT NULL COMMENT "用戶頭像 URL" AFTER google_id',
    'SELECT "profile_picture 欄位已存在" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 顯示結果
SELECT '✅ user 表已修復，支援 Google 登入' AS result;

