#!/usr/bin/env python3
"""
測試系統功能
"""

import pymysql
import requests
import json

def test_database_connection():
    """測試資料庫連接"""
    print("🔍 測試資料庫連接...")
    try:
        conn = pymysql.connect(
            host='100.79.58.120',
            user='root',
            password='',
            database='topics_good'
        )
        
        cursor = conn.cursor()
        cursor.execute("SELECT COUNT(*) FROM user")
        user_count = cursor.fetchone()[0]
        print(f"✅ 資料庫連接成功，用戶總數: {user_count}")
        
        # 檢查Google登入支援
        cursor.execute("DESCRIBE user")
        columns = [row[0] for row in cursor.fetchall()]
        has_google_id = 'google_id' in columns
        has_profile_picture = 'profile_picture' in columns
        
        print(f"✅ Google登入支援: {'完整' if has_google_id and has_profile_picture else '不完整'}")
        
        conn.close()
        return True
        
    except Exception as e:
        print(f"❌ 資料庫連接失敗: {e}")
        return False

def test_backend_api():
    """測試後端API"""
    print("\n🔍 測試後端API...")
    try:
        # 測試健康檢查端點
        response = requests.get('http://localhost:5000/health', timeout=5)
        if response.status_code == 200:
            print("✅ 後端API健康檢查通過")
            return True
        else:
            print(f"❌ 後端API健康檢查失敗: {response.status_code}")
            return False
            
    except requests.exceptions.ConnectionError:
        print("⚠️  後端API未啟動，請先啟動Flask應用")
        return False
    except Exception as e:
        print(f"❌ 後端API測試失敗: {e}")
        return False

def test_email_notification():
    """測試郵件通知功能"""
    print("\n🔍 測試郵件通知功能...")
    try:
        # 檢查郵件服務檔案是否存在
        import os
        email_service_path = 'services/email_notification.php'
        if os.path.exists(email_service_path):
            print("✅ 郵件通知服務檔案存在")
            return True
        else:
            print("❌ 郵件通知服務檔案不存在")
            return False
            
    except Exception as e:
        print(f"❌ 郵件通知測試失敗: {e}")
        return False

def main():
    """主測試函數"""
    print("🧪 開始系統功能測試...")
    print("=" * 50)
    
    # 測試資料庫
    db_ok = test_database_connection()
    
    # 測試後端API
    api_ok = test_backend_api()
    
    # 測試郵件通知
    email_ok = test_email_notification()
    
    # 總結
    print("\n📋 測試總結:")
    print("=" * 50)
    print(f"資料庫連接: {'✅ 正常' if db_ok else '❌ 異常'}")
    print(f"後端API: {'✅ 正常' if api_ok else '❌ 異常'}")
    print(f"郵件通知: {'✅ 正常' if email_ok else '❌ 異常'}")
    
    if db_ok and email_ok:
        print("\n🎉 系統基本功能正常！")
        print("📝 下一步:")
        print("1. 設定Gmail配置檔案")
        print("2. 啟動後端API服務")
        print("3. 測試Google登入功能")
        print("4. 測試私訊郵件通知")
    else:
        print("\n⚠️  系統需要修復，請檢查上述問題")

if __name__ == "__main__":
    main()
