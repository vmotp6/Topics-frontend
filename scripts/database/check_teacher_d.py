#!/usr/bin/env python3
"""檢查 :D 這位老師的資料"""

import pymysql

conn = pymysql.connect(
    host='localhost',
    user='root',
    password='',
    database='topics_good'
)

cursor = conn.cursor()

print("=" * 60)
print("檢查 :D 這位老師的資料")
print("=" * 60)

# 檢查 user 表中的資料
cursor.execute("SELECT id, username, role, name, email FROM user WHERE username = ':D'")
user_data = cursor.fetchone()

if user_data:
    print("\nuser 表中的資料：")
    print(f"  ID: {user_data[0]}")
    print(f"  用戶名: {user_data[1]}")
    print(f"  角色: {user_data[2]}")
    print(f"  姓名欄位: {user_data[3]}")
    print(f"  電子郵件: {user_data[4]}")
else:
    print("\n❌ 在 user 表中找不到用戶名 ':D'")

# 檢查 teacher 表中的資料
cursor.execute("SELECT id, user_id, name, department FROM teacher WHERE user_id = 15")
teacher_data = cursor.fetchone()

if teacher_data:
    print("\nteacher 表中的資料：")
    print(f"  ID: {teacher_data[0]}")
    print(f"  user_id: {teacher_data[1]}")
    print(f"  姓名: {teacher_data[2]}")
    print(f"  科系: {teacher_data[3]}")
    
    print("\n⚠️  目前的顯示名稱是：'這位老師'")
    print("   這個名稱是我剛才自動創建記錄時設定的")
else:
    print("\n❌ 在 teacher 表中找不到對應記錄")

cursor.close()
conn.close()

