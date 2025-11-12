-- =====================================================
-- 修復缺失的外鍵約束
-- 專門用於添加 enrollment_applications_normalized 和 cooperation_applications_normalized 的外鍵
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- enrollment_applications_normalized 的外鍵
-- =====================================================

-- 先檢查並刪除可能存在的同名外鍵（如果有的話）
-- 注意：這些語句可能會報錯，但不影響後續執行

-- 刪除現有的外鍵（如果存在）
ALTER TABLE enrollment_applications_normalized
DROP FOREIGN KEY IF EXISTS fk_enrollment_identity;

ALTER TABLE enrollment_applications_normalized
DROP FOREIGN KEY IF EXISTS fk_enrollment_gender;

ALTER TABLE enrollment_applications_normalized
DROP FOREIGN KEY IF EXISTS fk_enrollment_grade;

ALTER TABLE enrollment_applications_normalized
DROP FOREIGN KEY IF EXISTS fk_enrollment_teacher;

-- 添加外鍵
-- 身份別外鍵
ALTER TABLE enrollment_applications_normalized
ADD CONSTRAINT fk_enrollment_identity 
FOREIGN KEY (identity_id) REFERENCES identities(id) ON DELETE RESTRICT;

-- 性別外鍵
ALTER TABLE enrollment_applications_normalized
ADD CONSTRAINT fk_enrollment_gender 
FOREIGN KEY (gender_id) REFERENCES genders(id) ON DELETE SET NULL;

-- 年級外鍵
ALTER TABLE enrollment_applications_normalized
ADD CONSTRAINT fk_enrollment_grade 
FOREIGN KEY (current_grade_id) REFERENCES grades(id) ON DELETE SET NULL;

-- 推薦老師外鍵
ALTER TABLE enrollment_applications_normalized
ADD CONSTRAINT fk_enrollment_teacher 
FOREIGN KEY (recommended_teacher_user_id) REFERENCES teacher_normalized(user_id) ON DELETE SET NULL;

-- =====================================================
-- cooperation_applications_normalized 的外鍵
-- =====================================================

-- 刪除現有的外鍵（如果存在）
ALTER TABLE cooperation_applications_normalized
DROP FOREIGN KEY IF EXISTS fk_cooperation_teacher;

ALTER TABLE cooperation_applications_normalized
DROP FOREIGN KEY IF EXISTS fk_cooperation_department;

-- 添加外鍵
-- 老師外鍵
ALTER TABLE cooperation_applications_normalized
ADD CONSTRAINT fk_cooperation_teacher 
FOREIGN KEY (teacher_user_id) REFERENCES teacher_normalized(user_id) ON DELETE RESTRICT;

-- 科系外鍵
ALTER TABLE cooperation_applications_normalized
ADD CONSTRAINT fk_cooperation_department 
FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT;

SET FOREIGN_KEY_CHECKS = 1;

-- 驗證外鍵是否創建成功
SELECT 
    '外鍵驗證' AS 檢查項目,
    TABLE_NAME AS 表名,
    CONSTRAINT_NAME AS 外鍵名稱,
    '✅ 已設置' AS 狀態
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = DATABASE()
AND CONSTRAINT_TYPE = 'FOREIGN KEY'
AND CONSTRAINT_NAME IN (
    'fk_enrollment_identity',
    'fk_enrollment_gender',
    'fk_enrollment_grade',
    'fk_enrollment_teacher',
    'fk_cooperation_teacher',
    'fk_cooperation_department'
)
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

SELECT '✅ 外鍵約束修復完成！' AS message;

