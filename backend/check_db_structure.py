#!/usr/bin/env python3
"""
檢查資料庫結構
"""

import pymysql

def check_database_structure():
    """檢查資料庫結構"""
    try:
        conn = pymysql.connect(
            host='100.79.58.120',
            user='root',
            password='',
            database='topics_good'
        )
        
        cursor = conn.cursor()
        
        # 檢查用戶表結構
        print("📋 用戶表結構:")
        cursor.execute("DESCRIBE user")
        columns = cursor.fetchall()
        
        for col in columns:
            print(f"  - {col[0]}: {col[1]} {'(必填)' if col[2] == 'NO' else '(選填)'}")
        
        # 檢查是否有Google相關欄位
        column_names = [col[0] for col in columns]
        has_google_id = 'google_id' in column_names
        has_profile_picture = 'profile_picture' in column_names
        
        print(f"\n🔍 Google登入支援:")
        print(f"  - google_id 欄位: {'✅ 存在' if has_google_id else '❌ 缺失'}")
        print(f"  - profile_picture 欄位: {'✅ 存在' if has_profile_picture else '❌ 缺失'}")
        
        if not has_google_id or not has_profile_picture:
            print("\n⚠️  需要修復資料庫結構以支援Google登入")
            return False
        else:
            print("\n✅ 資料庫結構完整，支援Google登入")
            return True
        
        conn.close()
        
    except Exception as e:
        print(f"❌ 檢查失敗: {e}")
        return False

if __name__ == "__main__":
    check_database_structure()
