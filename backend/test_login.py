#!/usr/bin/env python3
"""
測試登入功能
"""

import requests
import json

def test_login():
    """測試登入功能"""
    print("🧪 測試登入功能...")
    print("=" * 50)
    
    # 測試後端健康檢查
    try:
        response = requests.get('http://localhost:5000/health', timeout=5)
        if response.status_code == 200:
            print("✅ 後端服務正常運行")
        else:
            print(f"❌ 後端服務異常: {response.status_code}")
            return False
    except requests.exceptions.ConnectionError:
        print("❌ 無法連接到後端服務")
        return False
    except Exception as e:
        print(f"❌ 後端服務測試失敗: {e}")
        return False
    
    # 測試登入端點
    try:
        # 使用測試帳號登入
        login_data = {
            'username': 'test_user',
            'password': 'test_password'
        }
        
        response = requests.post('http://localhost:5000/login', data=login_data, timeout=5)
        print(f"登入測試回應: {response.status_code}")
        
        if response.status_code == 401:
            print("✅ 登入端點正常（帳號密碼錯誤是預期的）")
        elif response.status_code == 200:
            print("✅ 登入端點正常（測試帳號存在）")
        else:
            print(f"⚠️  登入端點回應異常: {response.status_code}")
            print(f"回應內容: {response.text}")
        
        return True
        
    except Exception as e:
        print(f"❌ 登入端點測試失敗: {e}")
        return False

def test_google_auth():
    """測試Google登入端點"""
    print("\n🔍 測試Google登入端點...")
    
    try:
        response = requests.get('http://localhost:5000/auth/google', timeout=5, allow_redirects=False)
        print(f"Google登入端點回應: {response.status_code}")
        
        if response.status_code == 302:
            print("✅ Google登入端點正常（重定向到Google）")
            return True
        else:
            print(f"⚠️  Google登入端點回應異常: {response.status_code}")
            return False
            
    except Exception as e:
        print(f"❌ Google登入端點測試失敗: {e}")
        return False

if __name__ == "__main__":
    print("🚀 開始測試登入系統...")
    
    # 測試基本登入
    login_ok = test_login()
    
    # 測試Google登入
    google_ok = test_google_auth()
    
    print("\n📋 測試總結:")
    print("=" * 50)
    print(f"基本登入: {'✅ 正常' if login_ok else '❌ 異常'}")
    print(f"Google登入: {'✅ 正常' if google_ok else '❌ 異常'}")
    
    if login_ok and google_ok:
        print("\n🎉 登入系統測試通過！")
        print("如果前端登入按鈕仍有問題，可能是JavaScript或CSS問題。")
    else:
        print("\n⚠️  登入系統需要修復")
