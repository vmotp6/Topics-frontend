#!/usr/bin/env python3
"""為缺少 teacher 記錄的老師用戶創建記錄"""

import pymysql

conn = pymysql.connect(
    host='localhost',
    user='root',
    password='',
    database='topics_good'
)

cursor = conn.cursor()

print("=" * 60)
print("為缺少 teacher 記錄的老師創建記錄")
print("=" * 60)

# 找出所有沒有 teacher 記錄的老師用戶
cursor.execute("""
    SELECT u.id, u.username 
    FROM user u 
    WHERE u.role = '老師' 
    AND u.id NOT IN (SELECT user_id FROM teacher WHERE user_id IS NOT NULL)
""")
missing_teachers = cursor.fetchall()

if not missing_teachers:
    print("\n✅ 所有老師用戶都已有對應的 teacher 記錄！")
else:
    print(f"\n發現 {len(missing_teachers)} 位老師缺少 teacher 記錄：")
    for teacher in missing_teachers:
        print(f"   - ID: {teacher[0]}, 用戶名: {teacher[1]}")
    
    print("\n開始創建記錄...")
    
    # 預設科系列表
    departments = ['資訊管理科', '企業管理科', '護理科', '嬰幼兒保育科', '應用外語科', '視光科', '數位影視動畫科']
    
    created_count = 0
    for index, teacher in enumerate(missing_teachers):
        user_id, username = teacher
        
        # 根據用戶名判斷科系，或使用預設
        department = '資訊管理科'  # 預設科系
        
        # 特殊處理 :D 這位老師
        if username == ':D':
            department = '應用外語科'  # 可以根據實際情況調整
            display_name = '這位老師'
        else:
            display_name = username
        
        try:
            # 檢查是否已存在（防止重複）
            cursor.execute("SELECT id FROM teacher WHERE user_id = %s", (user_id,))
            if cursor.fetchone():
                print(f"   ⚠️  {username} (ID: {user_id}) 已有記錄，跳過")
                continue
            
            # 插入新記錄
            cursor.execute("""
                INSERT INTO teacher (user_id, name, department) 
                VALUES (%s, %s, %s)
            """, (user_id, display_name, department))
            
            conn.commit()
            print(f"   ✅ 為 {username} (ID: {user_id}) 創建記錄 - 姓名: {display_name}, 科系: {department}")
            created_count += 1
            
        except Exception as e:
            print(f"   ❌ 為 {username} (ID: {user_id}) 創建記錄失敗: {e}")
            conn.rollback()
    
    print(f"\n✅ 完成！共創建 {created_count} 筆記錄")
    
    # 驗證結果
    print("\n驗證結果：")
    cursor.execute("""
        SELECT t.user_id, t.name, t.department, u.username, '老師' as contact_type
        FROM teacher t 
        JOIN user u ON t.user_id = u.id 
        WHERE u.role = '老師'
        ORDER BY t.name
    """)
    all_teachers = cursor.fetchall()
    print(f"   現在共有 {len(all_teachers)} 位老師可以顯示：")
    for teacher in all_teachers:
        print(f"   - {teacher[1]:<15} | 科系: {teacher[2] or '未設定':<20} | 帳號: {teacher[3]}")

cursor.close()
conn.close()

