#!/usr/bin/env python3
"""
修復資料庫以支援Google登入
"""

import pymysql

def fix_database():
    """修復資料庫結構"""
    print("🔧 開始修復資料庫結構...")
    
    try:
        conn = pymysql.connect(
            host='100.79.58.120',
            user='root',
            password='',
            database='topics_good'
        )
        
        cursor = conn.cursor()
        
        # 檢查現有欄位
        cursor.execute("DESCRIBE user")
        existing_columns = [row[0] for row in cursor.fetchall()]
        print(f"現有欄位: {existing_columns}")
        
        # 添加 google_id 欄位
        if 'google_id' not in existing_columns:
            print("添加 google_id 欄位...")
            cursor.execute("ALTER TABLE user ADD COLUMN google_id VARCHAR(255) UNIQUE")
            print("✅ google_id 欄位添加成功")
        else:
            print("✅ google_id 欄位已存在")
        
        # 添加 profile_picture 欄位
        if 'profile_picture' not in existing_columns:
            print("添加 profile_picture 欄位...")
            cursor.execute("ALTER TABLE user ADD COLUMN profile_picture TEXT")
            print("✅ profile_picture 欄位添加成功")
        else:
            print("✅ profile_picture 欄位已存在")
        
        # 添加索引
        try:
            cursor.execute("CREATE INDEX idx_google_id ON user(google_id)")
            print("✅ google_id 索引添加成功")
        except:
            print("⚠️  google_id 索引可能已存在")
        
        try:
            cursor.execute("CREATE INDEX idx_email ON user(email)")
            print("✅ email 索引添加成功")
        except:
            print("⚠️  email 索引可能已存在")
        
        conn.commit()
        conn.close()
        
        print("🎉 資料庫修復完成！")
        return True
        
    except Exception as e:
        print(f"❌ 修復失敗: {e}")
        return False

if __name__ == "__main__":
    fix_database()
