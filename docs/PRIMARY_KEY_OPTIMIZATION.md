# 主鍵優化說明

## 問題

在原始的 3NF 正規化設計中，`teacher_normalized` 和 `student_normalized` 表都有兩個欄位：
- `id` - 自動遞增的主鍵
- `user_id` - 關聯到 `user` 表的外鍵

## 優化方案

由於 `teacher` 和 `user` 之間是一對一關係（一個 user 對應一個 teacher），以及 `student` 和 `user` 之間也是一對一關係，我們可以：

**使用 `user_id` 作為主鍵，移除多餘的 `id` 欄位**

## 優點

1. **更簡潔**：減少一個不必要的欄位
2. **更直觀**：主鍵直接就是外鍵，關係更清晰
3. **更高效**：減少一個索引的維護
4. **符合設計原則**：一對一關係時，通常使用被引用表的主鍵作為主鍵

## 修改後的表結構

### teacher_normalized（優化後）

```sql
CREATE TABLE teacher_normalized (
    user_id INT NOT NULL PRIMARY KEY,  -- 直接作為主鍵
    name VARCHAR(255) NOT NULL,
    department_id INT NULL,
    phone VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);
```

### student_normalized（優化後）

```sql
CREATE TABLE student_normalized (
    user_id INT NOT NULL PRIMARY KEY,  -- 直接作為主鍵
    name VARCHAR(255) NOT NULL,
    student_id VARCHAR(50) UNIQUE,
    department_id INT NULL,
    grade_id INT NULL,
    class_name VARCHAR(100) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (grade_id) REFERENCES grades(id) ON DELETE SET NULL
);
```

## 需要修改的引用

### 1. enrollment_applications_normalized

**修改前**：
```sql
recommended_teacher_id INT NULL COMMENT '關聯到 teacher_normalized 表',
FOREIGN KEY (recommended_teacher_id) REFERENCES teacher_normalized(id) ...
```

**修改後**：
```sql
recommended_teacher_user_id INT NULL COMMENT '關聯到 teacher_normalized.user_id',
FOREIGN KEY (recommended_teacher_user_id) REFERENCES teacher_normalized(user_id) ...
```

### 2. cooperation_applications_normalized

**修改前**：
```sql
teacher_id INT NOT NULL COMMENT '關聯到 teacher_normalized 表',
FOREIGN KEY (teacher_id) REFERENCES teacher_normalized(id) ...
```

**修改後**：
```sql
teacher_user_id INT NOT NULL COMMENT '關聯到 teacher_normalized.user_id',
FOREIGN KEY (teacher_user_id) REFERENCES teacher_normalized(user_id) ...
```

## 向後兼容性

為了保持與舊代碼的兼容性，視圖中會將 `user_id` 顯示為 `id`：

```sql
CREATE OR REPLACE VIEW teacher_view AS
SELECT 
    t.user_id AS id,  -- 將 user_id 別名為 id，保持兼容
    t.user_id,
    t.name,
    ...
FROM teacher_normalized t
...
```

這樣，使用視圖的代碼不需要修改，因為視圖會自動將 `user_id` 映射為 `id`。

## 注意事項

1. **一對一關係**：這種設計假設一個 user 只能有一個 teacher 或 student 記錄
2. **未來擴展**：如果將來需要支持一個 user 對應多個 teacher/student 記錄，需要重新設計
3. **數據遷移**：遷移時需要注意主鍵衝突，使用 `ON DUPLICATE KEY UPDATE` 處理重複

## 查詢範例

### 查詢老師資料

```sql
-- 使用正規化表（新方式）
SELECT * FROM teacher_normalized WHERE user_id = 1;

-- 使用視圖（向後兼容）
SELECT * FROM teacher_view WHERE id = 1;  -- id 實際上是 user_id
```

### 關聯查詢

```sql
-- 查詢產學合作申請及其老師
SELECT 
    ca.id,
    ca.project_title,
    t.name AS teacher_name,
    d.name AS department_name
FROM cooperation_applications_normalized ca
JOIN teacher_normalized t ON ca.teacher_user_id = t.user_id
JOIN departments d ON t.department_id = d.id;
```

