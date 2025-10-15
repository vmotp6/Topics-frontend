#!/usr/bin/env python3
"""
測試資料庫連接
"""

import pymysql
import sys
import os

# 添加當前目錄到 Python 路徑
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

# 載入配置
try:
    from config import *
    print("✅ 配置檔案載入成功")
except ImportError:
    print("⚠️  配置檔案不存在，使用預設配置")

# 資料庫連接配置
DB_CONFIG = {
    'host': '100.79.58.120',
    'user': 'root',
    'password': '',
    'database': 'topics_good',
    'charset': 'utf8mb4'
}

def test_db_connection():
    """測試資料庫連接"""
    try:
        print("🔗 測試資料庫連接...")
        print(f"Host: {DB_CONFIG['host']}")
        print(f"Database: {DB_CONFIG['database']}")
        print(f"User: {DB_CONFIG['user']}")
        
        conn = pymysql.connect(**DB_CONFIG)
        print("✅ 資料庫連接成功")
        
        with conn.cursor() as cursor:
            # 測試基本查詢
            cursor.execute("SELECT VERSION()")
            version = cursor.fetchone()
            print(f"MySQL 版本: {version[0]}")
            
            # 檢查 user 表是否存在
            cursor.execute("SHOW TABLES LIKE 'user'")
            if cursor.fetchone():
                print("✅ user 表存在")
                
                # 檢查 user 表結構
                cursor.execute("DESCRIBE user")
                columns = cursor.fetchall()
                print("📋 user 表結構:")
                for col in columns:
                    print(f"  - {col[0]}: {col[1]}")
                
                # 檢查用戶數量
                cursor.execute("SELECT COUNT(*) FROM user")
                count = cursor.fetchone()[0]
                print(f"📊 用戶總數: {count}")
                
                # 顯示最近的用戶
                cursor.execute("SELECT id, username, email, role, created_at FROM user ORDER BY created_at DESC LIMIT 5")
                users = cursor.fetchall()
                print("👥 最近的用戶:")
                for user in users:
                    print(f"  - ID: {user[0]}, 用戶名: {user[1]}, 郵箱: {user[2]}, 角色: {user[3]}, 創建時間: {user[4]}")
                    
            else:
                print("❌ user 表不存在")
                
        conn.close()
        print("✅ 資料庫連接測試完成")
        
    except Exception as e:
        print(f"❌ 資料庫連接失敗: {e}")
        return False
    
    return True

if __name__ == "__main__":
    test_db_connection()
