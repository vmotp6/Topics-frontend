# 資料庫矛盾分析報告

## 概述

本報告分析 `topics_good` 資料庫中所有表的設計矛盾和不一致問題。

## 🔴 嚴重問題

### 1. 重複功能的表（功能重疊）

#### 問題：學校表重複
- **`schools`** - 國中學校資料表
- **`school_data`** - 學校基本資料表

**問題分析**：
- 兩個表都存儲學校資料，但欄位結構略有不同
- `schools` 表更簡潔（name, city, district, address, phone, website, type）
- `school_data` 表更詳細（包含 principal, student_count, teacher_count 等）

**建議**：
- 合併為單一的 `schools` 表
- 將 `school_data` 的額外欄位添加到 `schools` 表
- 統一所有引用為 `schools` 表

#### 問題：就讀意願表重複
- **`enrollment_applications`** - 就讀意願申請表
- **`enrollment_intention`** - 就讀意願登錄表

**問題分析**：
- 兩個表功能完全相同，只是欄位略有差異
- 造成數據分散，難以管理

**建議**：
- 統一使用 `enrollment_applications_normalized`（正規化版本）
- 刪除 `enrollment_intention` 表或遷移數據後刪除

### 2. 使用 username/email 而非 user_id（違反 3NF）

#### 問題表列表：

1. **`user_activity`** 
   - ❌ 使用 `username VARCHAR(255)`
   - ✅ 應該使用 `user_id INT` 並添加外鍵

2. **`unread_notifications`**
   - ❌ 使用 `username VARCHAR(255)` 和 `sender_username VARCHAR(255)`
   - ✅ 應該使用 `user_id INT` 和 `sender_user_id INT`

3. **`notification_sent_log`**
   - ❌ 使用 `username VARCHAR(255)`
   - ✅ 應該使用 `user_id INT`

4. **`message_likes`** ⚠️ **最嚴重**
   - ❌ 使用 `user_email VARCHAR(255)`
   - ✅ 應該使用 `user_id INT`
   - 問題：email 可能改變，無法保證唯一性

### 3. 缺少外鍵定義

#### `senior_messages` 表
**問題**：
- `author_department VARCHAR(100)` - 應該是 `department_id INT`（外鍵）
- `author_grade VARCHAR(50)` - 應該是 `grade_id INT`（外鍵）
- `author_email VARCHAR(255)` - 應該關聯到 `user` 表
- `message_type ENUM(...)` - 應該引用 `message_types` 參考表

#### `notification_logs` 表
**問題**：
- 引用 `admission_recommendations(id)`，但該表可能不存在
- 需要確認 `admission_recommendations` 表是否定義

## 🟡 中等問題

### 4. 命名不一致

#### teacher 引用命名
- 有些表使用 `teacher_id`
- 有些表使用 `teacher_user_id`
- **建議**：統一使用 `teacher_user_id`（明確指向 `teacher_normalized.user_id`）

#### 學校引用命名
- 有些使用 `junior_high_school_id`（引用 `schools` 表）
- 可能有些地方直接存儲學校名稱字串
- **建議**：統一使用 `school_id` 並引用 `schools` 表

### 5. 缺少索引

某些經常查詢的欄位可能缺少索引：
- `user_activity.username` - 有索引 ✅
- `unread_notifications.username` - 有索引 ✅
- `message_likes.user_email` - 有索引 ✅

但使用 `user_id` 後需要添加對應索引。

## 🟢 輕微問題

### 6. 未正規化的 ENUM

多個表使用 ENUM 存儲狀態值：
- `enrollment_applications.status`
- `cooperation_applications.status`
- `senior_messages.message_type`

**建議**：這些都應該改為引用參考表（已完成在正規化腳本中）

## 📋 修復優先級

### 高優先級（必須修復）

1. ✅ **合併重複的學校表**
   - 創建統一的 `schools` 表
   - 遷移 `school_data` 的數據
   - 更新所有引用

2. ✅ **修復 user_activity 等表使用 username 的問題**
   - 改為使用 `user_id`
   - 添加外鍵約束

3. ✅ **修復 message_likes 使用 user_email 的問題**
   - 改為使用 `user_id`
   - 這是數據完整性的嚴重問題

### 中優先級（建議修復）

4. ✅ **正規化 senior_messages 表**
   - 將 `author_department`、`author_grade` 改為外鍵
   - 將 `author_email` 改為 `user_id`

5. ✅ **統一命名規範**
   - 統一 teacher 引用為 `teacher_user_id`
   - 統一學校引用為 `school_id`

### 低優先級（可選修復）

6. ✅ **合併 enrollment 相關表**
   - 統一使用正規化版本

7. ✅ **確認並修復 notification_logs 的外鍵**
   - 確認 `admission_recommendations` 表是否存在
   - 如果不存在，需要創建或移除外鍵

## 🔧 修復腳本

所有修復方案已包含在 `complete_normalize_to_3nf.sql` 中，但需要額外創建：

1. `fix_user_references_to_user_id.sql` - 修復所有使用 username/email 的表
2. `merge_duplicate_school_tables.sql` - 合併重複的學校表
3. `normalize_senior_messages.sql` - 正規化 senior_messages 表

