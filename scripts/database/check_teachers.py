#!/usr/bin/env python3
"""檢查所有老師資料"""

import pymysql

conn = pymysql.connect(
    host='localhost',
    user='root',
    password='',
    database='topics_good'
)

cursor = conn.cursor()

print("=" * 60)
print("所有老師列表")
print("=" * 60)

try:
    cursor.execute("""
        SELECT t.name, t.department, u.username, u.role 
        FROM teacher t 
        JOIN user u ON t.user_id = u.id 
        WHERE u.role = '老師'
        ORDER BY t.department, t.name
    """)
    
    teachers = cursor.fetchall()
    if teachers:
        print(f"\n總共找到 {len(teachers)} 位老師：\n")
        for teacher in teachers:
            print(f"  - {teacher[0]:<15} | 科系: {teacher[1] or '未設定':<20} | 帳號: {teacher[2]}")
    else:
        print("\n未找到任何老師資料")
        
    # 檢查科系分佈
    print("\n" + "=" * 60)
    print("科系分佈統計")
    print("=" * 60)
    cursor.execute("""
        SELECT t.department, COUNT(*) as count
        FROM teacher t 
        JOIN user u ON t.user_id = u.id 
        WHERE u.role = '老師'
        GROUP BY t.department
        ORDER BY count DESC
    """)
    
    dept_stats = cursor.fetchall()
    for dept, count in dept_stats:
        print(f"  {dept or '未設定'}: {count} 位老師")

except Exception as e:
    print(f"錯誤: {e}")

finally:
    cursor.close()
    conn.close()

