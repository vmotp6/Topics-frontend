-- =====================================================
-- 第三正規化遷移回滾腳本
-- 如果遷移出現問題，可以使用此腳本回滾
-- =====================================================

USE topics_good;

-- 刪除視圖
DROP VIEW IF EXISTS enrollment_applications_view;
DROP VIEW IF EXISTS cooperation_applications_view;

-- 刪除新建立的正規化表（注意：這會刪除所有資料！）
DROP TABLE IF EXISTS enrollment_preferences;
DROP TABLE IF EXISTS cooperation_application_categories;
DROP TABLE IF EXISTS ip_rights;
DROP TABLE IF EXISTS enrollment_applications_normalized;
DROP TABLE IF EXISTS cooperation_applications_normalized;
DROP TABLE IF EXISTS companies;
DROP TABLE IF EXISTS grades;
DROP TABLE IF EXISTS genders;
DROP TABLE IF EXISTS identities;
DROP TABLE IF EXISTS education_systems;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS application_statuses;

SELECT '回滾完成！所有正規化表已刪除。' AS message;

