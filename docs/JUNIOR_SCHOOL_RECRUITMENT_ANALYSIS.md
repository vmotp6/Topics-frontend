# 國中學校招生申請表分析報告

## 資料表概況

**表名：** `junior_school_recruitment_applications`  
**用途：** 記錄國中學校向康寧大學申請招生活動的資料

## 發現的問題

### 1. 資料不完整
從提供的資料來看：
- **第一筆資料（id=1）**：缺少 `preferred_date`, `preferred_time`, `target_grades`, `expected_students`, `venue_type` 等重要欄位
- 這些欄位雖然允許 NULL，但對於招生申請來說應該是必填的

### 2. 重複申請
- **永吉國中** 出現了多次（id=1, 2, 3）
- 可能是：
  - 測試資料
  - 用戶重複提交
  - 缺少唯一性約束

### 3. 資料驗證不足
- Email 格式：`110534201@stu.uk.edu.tw` 和 `110534201@stu.ukn.edu.tw` 不一致
- 電話號碼：都是 `0900000000`（可能是測試資料）
- 學校地址：都是 `0000`（明顯是測試資料）

### 4. 設計問題
- **學校資訊未正規化**：`school_name`, `city`, `district` 直接存在申請表中
  - 建議：應該關聯到 `schools` 表
- **缺少申請編號規則**：目前使用自動遞增 ID
  - 建議：可以添加格式化的申請編號（如：JR-2025-001）

## 改進建議

### 1. 資料驗證
```sql
-- 添加必填欄位約束
ALTER TABLE `junior_school_recruitment_applications`
  MODIFY `preferred_date` date NOT NULL,
  MODIFY `preferred_time` varchar(50) NOT NULL,
  MODIFY `target_grades` varchar(50) NOT NULL,
  MODIFY `expected_students` int(11) NOT NULL;
```

### 2. 唯一性約束
```sql
-- 避免同一個學校在同一天重複申請
ALTER TABLE `junior_school_recruitment_applications`
  ADD UNIQUE KEY `unique_school_date` (`school_name`, `contact_email`, `preferred_date`);
```

### 3. 正規化設計
建議創建關聯表：
```sql
-- 學校表（如果還沒有）
CREATE TABLE `schools` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `city` VARCHAR(20) NOT NULL,
  `district` VARCHAR(20) NOT NULL,
  `address` VARCHAR(255),
  UNIQUE KEY `unique_school` (`name`, `city`, `district`)
);

-- 修改申請表，使用外鍵
ALTER TABLE `junior_school_recruitment_applications`
  ADD COLUMN `school_id` INT,
  ADD FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`);
```

### 4. 申請編號生成
```sql
-- 添加申請編號欄位
ALTER TABLE `junior_school_recruitment_applications`
  ADD COLUMN `application_number` VARCHAR(20) UNIQUE;

-- 觸發器自動生成申請編號
DELIMITER $$
CREATE TRIGGER `generate_application_number`
BEFORE INSERT ON `junior_school_recruitment_applications`
FOR EACH ROW
BEGIN
    IF NEW.application_number IS NULL THEN
        SET NEW.application_number = CONCAT('JR-', YEAR(NOW()), '-', LPAD(NEW.id, 4, '0'));
    END IF;
END$$
DELIMITER ;
```

## 資料清理建議

### 清理測試資料
```sql
-- 刪除明顯的測試資料
DELETE FROM `junior_school_recruitment_applications` 
WHERE 
    school_address = '0000' 
    OR contact_phone = '0900000000'
    OR contact_email LIKE '%@stu.uk.edu.tw';  -- 注意：這個 email 格式可能有誤
```

### 補齊缺失資料
對於 id=1 的記錄，應該：
1. 要求用戶補齊 `preferred_date`, `preferred_time` 等欄位
2. 或者將狀態設為 `rejected`（資料不完整）

## 前端表單改進

### 1. 必填欄位驗證
確保以下欄位在前端和後端都進行驗證：
- `preferred_date` - 期望招生日期
- `preferred_time` - 期望時間
- `target_grades` - 目標年級
- `expected_students` - 預期參與學生人數

### 2. 防止重複提交
- 添加提交後禁用按鈕
- 檢查是否已有相同申請（同學校、同日期、同聯絡人）

### 3. 資料預設值
- 如果可能，提供合理的預設值
- 使用下拉選單而非自由輸入（如 `preferred_time`, `venue_type`）

## 狀態管理建議

目前的狀態：
- `pending` - 待審核
- `approved` - 已核准
- `rejected` - 已拒絕
- `completed` - 已完成

建議添加：
- `draft` - 草稿（未完成填寫）
- `cancelled` - 已取消
- `in_progress` - 進行中

## 總結

這個資料表的基本結構是合理的，但需要：
1. ✅ 加強資料驗證
2. ✅ 添加唯一性約束
3. ✅ 清理測試資料
4. ✅ 考慮正規化設計
5. ✅ 改進前端表單驗證

建議先執行資料清理，然後逐步實施改進措施。







