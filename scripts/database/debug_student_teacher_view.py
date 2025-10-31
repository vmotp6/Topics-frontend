#!/usr/bin/env python3
"""調試學生看到的老師列表"""

import pymysql

conn = pymysql.connect(
    host='localhost',
    user='root',
    password='',
    database='topics_good'
)

cursor = conn.cursor()

print("=" * 60)
print("檢查學生看到的老師列表")
print("=" * 60)

# 1. 檢查所有老師用戶
print("\n1. 所有 user 表中 role='老師' 的用戶：")
cursor.execute("SELECT id, username, role FROM user WHERE role = '老師' ORDER BY username")
teacher_users = cursor.fetchall()
print(f"   找到 {len(teacher_users)} 位老師用戶：")
for user in teacher_users:
    print(f"   - ID: {user[0]}, 用戶名: {user[1]}, 角色: {user[2]}")

# 2. 檢查所有 teacher 表記錄
print("\n2. teacher 表中的所有記錄：")
cursor.execute("SELECT id, user_id, name, department FROM teacher ORDER BY name")
teacher_records = cursor.fetchall()
print(f"   找到 {len(teacher_records)} 筆老師記錄：")
for teacher in teacher_records:
    print(f"   - ID: {teacher[0]}, user_id: {teacher[1]}, 姓名: {teacher[2]}, 科系: {teacher[3]}")

# 3. 執行學生視角的查詢（與 chat.php 相同）
print("\n3. 學生看到的老師列表（使用 JOIN 查詢）：")
cursor.execute("""
    SELECT t.user_id, t.name, t.department, u.username, '老師' as contact_type
    FROM teacher t 
    JOIN user u ON t.user_id = u.id 
    WHERE u.role = '老師'
    ORDER BY t.name
""")
student_view_teachers = cursor.fetchall()
print(f"   找到 {len(student_view_teachers)} 位老師：")
for teacher in student_view_teachers:
    print(f"   - {teacher[1]:<15} | 科系: {teacher[2] or '未設定':<20} | 帳號: {teacher[3]}")

# 4. 檢查哪些老師用戶沒有對應的 teacher 記錄
print("\n4. 檢查缺失的老師記錄：")
cursor.execute("""
    SELECT u.id, u.username 
    FROM user u 
    WHERE u.role = '老師' 
    AND u.id NOT IN (SELECT user_id FROM teacher WHERE user_id IS NOT NULL)
""")
missing_teachers = cursor.fetchall()
if missing_teachers:
    print(f"   ⚠️  發現 {len(missing_teachers)} 位老師用戶沒有對應的 teacher 記錄：")
    for teacher in missing_teachers:
        print(f"   - ID: {teacher[0]}, 用戶名: {teacher[1]}")
else:
    print("   ✅ 所有老師用戶都有對應的 teacher 記錄")

# 5. 檢查是否有 teacher 記錄對應不存在的 user
print("\n5. 檢查孤立的 teacher 記錄：")
cursor.execute("""
    SELECT t.id, t.user_id, t.name 
    FROM teacher t 
    LEFT JOIN user u ON t.user_id = u.id 
    WHERE u.id IS NULL OR u.role != '老師'
""")
orphan_teachers = cursor.fetchall()
if orphan_teachers:
    print(f"   ⚠️  發現 {len(orphan_teachers)} 筆孤立的 teacher 記錄：")
    for teacher in orphan_teachers:
        print(f"   - ID: {teacher[0]}, user_id: {teacher[1]}, 姓名: {teacher[2]}")
else:
    print("   ✅ 所有 teacher 記錄都有對應的有效用戶")

cursor.close()
conn.close()

