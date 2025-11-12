#!/usr/bin/env python3
"""更新 :D 這位老師的名稱"""

import pymysql

conn = pymysql.connect(
    host='localhost',
    user='root',
    password='',
    database='topics_good'
)

cursor = conn.cursor()

print("更新 :D 這位老師的顯示名稱...")

# 從 user 表獲取原始名稱
cursor.execute("SELECT name FROM user WHERE username = ':D' AND role = '老師'")
user_name = cursor.fetchone()

if user_name:
    original_name = user_name[0] if user_name[0] else ':D'
    print(f"  從 user 表取得名稱: {original_name}")
    
    # 更新 teacher 表的名稱
    cursor.execute("UPDATE teacher SET name = %s WHERE user_id = 15", (original_name,))
    conn.commit()
    
    print(f"  ✅ 已更新 teacher 表中的名稱為: {original_name}")
    
    # 驗證更新結果
    cursor.execute("SELECT name FROM teacher WHERE user_id = 15")
    updated_name = cursor.fetchone()
    print(f"  驗證: teacher 表中的名稱現在是: {updated_name[0]}")
else:
    print("  ❌ 找不到該用戶")

cursor.close()
conn.close()

