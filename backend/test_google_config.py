#!/usr/bin/env python3
"""
測試 Google OAuth 配置
"""

import os
import sys

# 添加當前目錄到 Python 路徑
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

# 載入環境變數
try:
    from dotenv import load_dotenv
    load_dotenv()
    print("✅ 環境變數載入成功")
except ImportError:
    print("⚠️  python-dotenv未安裝，使用預設配置")

# 載入配置檔案
try:
    from config import *
    print("✅ 配置檔案載入成功")
except ImportError:
    print("⚠️  配置檔案不存在，使用預設配置")

# Google OAuth 配置 - 優先使用環境變數，否則使用config.py
GOOGLE_CLIENT_ID = os.getenv('GOOGLE_CLIENT_ID') or globals().get('GOOGLE_CLIENT_ID', 'your-google-client-id')
GOOGLE_CLIENT_SECRET = os.getenv('GOOGLE_CLIENT_SECRET') or globals().get('GOOGLE_CLIENT_SECRET', 'your-google-client-secret')
GOOGLE_REDIRECT_URI = os.getenv('GOOGLE_REDIRECT_URI') or globals().get('GOOGLE_REDIRECT_URI', 'http://localhost:5000/auth/google/callback')

print("\n🔧 Google OAuth 配置檢查:")
print(f"GOOGLE_CLIENT_ID: {GOOGLE_CLIENT_ID}")
print(f"GOOGLE_CLIENT_SECRET: {GOOGLE_CLIENT_SECRET[:10]}..." if len(GOOGLE_CLIENT_SECRET) > 10 else "未設定")
print(f"GOOGLE_REDIRECT_URI: {GOOGLE_REDIRECT_URI}")

print("\n📋 配置狀態:")
if GOOGLE_CLIENT_ID == 'your-google-client-id':
    print("❌ GOOGLE_CLIENT_ID 未設定")
else:
    print("✅ GOOGLE_CLIENT_ID 已設定")

if GOOGLE_CLIENT_SECRET == 'your-google-client-secret':
    print("❌ GOOGLE_CLIENT_SECRET 未設定")
else:
    print("✅ GOOGLE_CLIENT_SECRET 已設定")

print("\n🔗 測試 Google OAuth URL:")
import requests
import secrets

# 生成 state 參數
state = secrets.token_urlsafe(32)
print(f"State: {state}")

# 構建 Google OAuth URL
google_auth_url = (
    f"https://accounts.google.com/o/oauth2/v2/auth?"
    f"client_id={GOOGLE_CLIENT_ID}&"
    f"redirect_uri={GOOGLE_REDIRECT_URI}&"
    f"scope=openid%20email%20profile&"
    f"response_type=code&"
    f"state={state}"
)

print(f"Google OAuth URL: {google_auth_url}")

print("\n🧪 測試後端連接:")
try:
    response = requests.get("http://localhost:5000/auth/google?format=json", timeout=5)
    if response.status_code == 200:
        print("✅ 後端服務正常運行")
        data = response.json()
        print(f"後端返回的 Google URL: {data.get('auth_url', 'N/A')}")
    else:
        print(f"❌ 後端服務異常: {response.status_code}")
        print(f"響應內容: {response.text}")
except requests.exceptions.ConnectionError:
    print("❌ 無法連接到後端服務 (http://localhost:5000)")
except Exception as e:
    print(f"❌ 後端連接錯誤: {e}")

print("\n📝 建議:")
if GOOGLE_CLIENT_ID == 'your-google-client-id' or GOOGLE_CLIENT_SECRET == 'your-google-client-secret':
    print("1. 請按照 GOOGLE_OAUTH_SETUP.md 的指示設定 Google OAuth 憑證")
    print("2. 創建 .env 文件並填入正確的 GOOGLE_CLIENT_ID 和 GOOGLE_CLIENT_SECRET")
    print("3. 或者直接修改 config.py 文件")
else:
    print("✅ Google OAuth 配置看起來正確")
    print("如果仍有問題，請檢查:")
    print("1. Google Cloud Console 中的重定向 URI 設定")
    print("2. OAuth 同意畫面設定")
    print("3. 後端服務是否正常運行")
