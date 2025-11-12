-- =====================================================
-- 安全地修復缺失的外鍵約束
-- 先清理無效數據，再添加外鍵
-- =====================================================

USE topics_good;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 步驟 1: 清理無效數據
-- =====================================================

-- 將無效的 recommended_teacher_user_id 設為 NULL
UPDATE enrollment_applications_normalized ean
SET ean.recommended_teacher_user_id = NULL
WHERE ean.recommended_teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized tn 
    WHERE tn.user_id = ean.recommended_teacher_user_id
);

-- 將無效的 teacher_user_id 設為 NULL（注意：這可能需要根據業務邏輯調整）
-- 如果 teacher_user_id 是必填的，您可能需要先添加對應的 teacher 記錄
UPDATE cooperation_applications_normalized can
SET can.teacher_user_id = NULL
WHERE can.teacher_user_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM teacher_normalized tn 
    WHERE tn.user_id = can.teacher_user_id
);

-- 將無效的 identity_id 設為 NULL（如果允許）
UPDATE enrollment_applications_normalized ean
SET ean.identity_id = NULL
WHERE ean.identity_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM identities i 
    WHERE i.id = ean.identity_id
);

-- 將無效的 gender_id 設為 NULL
UPDATE enrollment_applications_normalized ean
SET ean.gender_id = NULL
WHERE ean.gender_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM genders g 
    WHERE g.id = ean.gender_id
);

-- 將無效的 current_grade_id 設為 NULL
UPDATE enrollment_applications_normalized ean
SET ean.current_grade_id = NULL
WHERE ean.current_grade_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM grades gr 
    WHERE gr.id = ean.current_grade_id
);

-- 將無效的 department_id 設為 NULL
UPDATE cooperation_applications_normalized can
SET can.department_id = NULL
WHERE can.department_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM departments d 
    WHERE d.id = can.department_id
);

-- 將無效的 company_id 設為 NULL
UPDATE cooperation_applications_normalized can
SET can.company_id = NULL
WHERE can.company_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM companies c 
    WHERE c.id = can.company_id
);

-- =====================================================
-- 步驟 2: 添加外鍵約束
-- =====================================================

-- enrollment_applications_normalized 的外鍵

-- 身份別外鍵（identity_id 可能允許 NULL）
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

-- 推薦老師外鍵（允許 NULL，因為已經清理了無效數據）
ALTER TABLE enrollment_applications_normalized
ADD CONSTRAINT fk_enrollment_teacher 
FOREIGN KEY (recommended_teacher_user_id) REFERENCES teacher_normalized(user_id) ON DELETE SET NULL;

-- =====================================================
-- cooperation_applications_normalized 的外鍵
-- =====================================================

-- 注意：如果 teacher_user_id 或 department_id 是必填的，確保沒有 NULL 值

-- 老師外鍵（如果允許 NULL，否則確保所有記錄都有有效的 teacher_user_id）
-- 如果表結構中 teacher_user_id 是 NOT NULL，但現在有 NULL 值，這會失敗
-- 先檢查：SELECT COUNT(*) FROM cooperation_applications_normalized WHERE teacher_user_id IS NULL;

ALTER TABLE cooperation_applications_normalized
ADD CONSTRAINT fk_cooperation_teacher 
FOREIGN KEY (teacher_user_id) REFERENCES teacher_normalized(user_id) ON DELETE RESTRICT;

-- 科系外鍵（如果允許 NULL）
ALTER TABLE cooperation_applications_normalized
ADD CONSTRAINT fk_cooperation_department 
FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 步驟 3: 驗證結果
-- =====================================================

SELECT 
    '✅ 外鍵約束添加完成！' AS 結果,
    COUNT(*) AS 已設置的外鍵數
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
);

-- 顯示所有成功添加的外鍵
SELECT 
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

