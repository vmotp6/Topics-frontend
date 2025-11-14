-- =====================================================
-- 國中學校招生申請表分析與改進建議
-- =====================================================

-- 問題分析：
-- 1. 第一筆資料很多欄位是 NULL（preferred_date, preferred_time 等）
-- 2. 有重複的學校名稱（永吉國中出現多次）
-- 3. 資料不完整
-- 4. 缺少資料驗證和約束

-- =====================================================
-- 建議改進方案
-- =====================================================

-- 1. 添加資料驗證約束
ALTER TABLE `junior_school_recruitment_applications`
  ADD CONSTRAINT `chk_email_format` CHECK (`contact_email` REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'),
  ADD CONSTRAINT `chk_phone_format` CHECK (`contact_phone` REGEXP '^[0-9-+() ]+$'),
  ADD CONSTRAINT `chk_expected_students` CHECK (`expected_students` IS NULL OR `expected_students` > 0);

-- 2. 添加唯一約束（避免重複申請）
-- 如果同一個學校、同一個聯絡人在同一天申請，視為重複
ALTER TABLE `junior_school_recruitment_applications`
  ADD UNIQUE KEY `unique_school_contact_date` (`school_name`, `contact_email`, `preferred_date`);

-- 3. 添加外鍵約束（如果 schools 表存在）
-- ALTER TABLE `junior_school_recruitment_applications`
--   ADD CONSTRAINT `fk_school` FOREIGN KEY (`school_name`, `city`, `district`) 
--   REFERENCES `schools` (`name`, `city`, `district`) ON DELETE RESTRICT;

-- 4. 添加觸發器自動更新 updated_at（如果還沒有的話）
DELIMITER $$
CREATE TRIGGER `update_junior_school_recruitment_timestamp`
BEFORE UPDATE ON `junior_school_recruitment_applications`
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END$$
DELIMITER ;

-- =====================================================
-- 資料清理建議
-- =====================================================

-- 1. 找出重複的申請
SELECT 
    school_name, 
    contact_email, 
    preferred_date,
    COUNT(*) as duplicate_count,
    GROUP_CONCAT(id) as ids
FROM `junior_school_recruitment_applications`
WHERE preferred_date IS NOT NULL
GROUP BY school_name, contact_email, preferred_date
HAVING COUNT(*) > 1;

-- 2. 找出資料不完整的申請
SELECT 
    id,
    school_name,
    contact_name,
    contact_email,
    preferred_date,
    preferred_time,
    target_grades,
    expected_students
FROM `junior_school_recruitment_applications`
WHERE 
    preferred_date IS NULL 
    OR preferred_time IS NULL 
    OR target_grades IS NULL 
    OR expected_students IS NULL;

-- 3. 清理測試資料（可選）
-- DELETE FROM `junior_school_recruitment_applications` WHERE id IN (1, 2, 3, 4);

-- =====================================================
-- 改進後的表結構建議
-- =====================================================

-- 如果重新設計，建議：
-- 1. 將學校資訊正規化到獨立的 schools 表
-- 2. 添加申請編號生成規則
-- 3. 添加申請狀態變更記錄表
-- 4. 添加申請審核流程表



