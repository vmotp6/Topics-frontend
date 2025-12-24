#!/usr/bin/env python3
"""
Flask 後端應用程式
包含Google登入、用戶管理和郵件通知功能
"""

from flask import Flask, request, jsonify, redirect, session
from flask_cors import CORS
import pymysql
import requests
import os
import json
import secrets
import hashlib
from datetime import datetime
try:
    import bcrypt
    BCrypt_AVAILABLE = True
except ImportError:
    BCrypt_AVAILABLE = False
    print("⚠️  bcrypt 未安裝，將無法驗證雜湊密碼")

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

app = Flask(__name__)
# CORS 配置 - 允許所有來源（開發環境），生產環境請限制特定域名
CORS(app, 
     supports_credentials=True, 
     origins=['http://localhost', 'http://localhost:80', 'http://127.0.0.1', 'http://localhost:5000'],
     methods=['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
     allow_headers=['Content-Type', 'Authorization', 'X-Requested-With'])
app.secret_key = 'your-secret-key-here'  # 請更改為安全的密鑰

# 資料庫連接配置 - 優先使用環境變數，否則使用預設值
DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'port': int(os.getenv('DB_PORT', '3306')),  # MySQL 默認端口
    'user': os.getenv('DB_USER', 'root'),
    'password': os.getenv('DB_PASSWORD', ''),
    'database': os.getenv('DB_NAME', 'topics_good'),
    'charset': 'utf8mb4',
    'connect_timeout': 10,  # 連接超時時間（秒）
    'read_timeout': 10,     # 讀取超時時間（秒）
    'write_timeout': 10     # 寫入超時時間（秒）
}

# Google OAuth 配置 - 優先使用環境變數，否則使用config.py
GOOGLE_CLIENT_ID = os.getenv('GOOGLE_CLIENT_ID') or globals().get('GOOGLE_CLIENT_ID', 'your-google-client-id')
GOOGLE_CLIENT_SECRET = os.getenv('GOOGLE_CLIENT_SECRET') or globals().get('GOOGLE_CLIENT_SECRET', 'your-google-client-secret')
GOOGLE_REDIRECT_URI = os.getenv('GOOGLE_REDIRECT_URI') or globals().get('GOOGLE_REDIRECT_URI', 'http://localhost:5000/auth/google/callback')

# 存儲 state 參數（生產環境應使用 Redis 或資料庫）
google_states = {}

def get_db_connection(retries=3, retry_delay=1):
    """
    獲取資料庫連接，帶重試機制
    
    Args:
        retries: 重試次數，預設3次
        retry_delay: 重試延遲時間（秒），預設1秒，每次重試會翻倍
    
    Returns:
        pymysql.connections.Connection 或 None
    """
    last_error = None
    
    for attempt in range(retries):
        try:
            conn = pymysql.connect(**DB_CONFIG)
            print(f"✅ 資料庫連接成功 (嘗試 {attempt + 1}/{retries})")
            return conn
        except pymysql.Error as e:
            last_error = e
            error_msg = str(e)
            
            if attempt < retries - 1:
                print(f"⚠️  資料庫連接失敗 (嘗試 {attempt + 1}/{retries}): {error_msg}")
                print(f"   將在 {retry_delay} 秒後重試...")
                import time
                time.sleep(retry_delay)
                retry_delay *= 2  # 指數退避
            else:
                print(f"❌ 資料庫連接最終失敗 (已嘗試 {retries} 次): {error_msg}")
                print(f"   請檢查：")
                print(f"   1. 資料庫伺服器 {DB_CONFIG['host']} 是否可訪問")
                print(f"   2. 網路連線是否正常")
                print(f"   3. 防火牆設定是否允許連接")
                print(f"   4. 資料庫服務是否正在運行")
        
        except Exception as e:
            last_error = e
            print(f"❌ 資料庫連接發生未知錯誤: {e}")
            break
    
    return None

# 全局錯誤處理器
@app.errorhandler(Exception)
def handle_exception(e):
    """處理所有未捕獲的異常"""
    print(f"❌ 未捕獲的異常: {e}")
    import traceback
    traceback.print_exc()
    
    error_response = {
        "message": "伺服器發生錯誤，請稍後再試",
        "error": "server_error",
        "details": str(e) if app.debug else None
    }
    
    if app.debug:
        error_response["traceback"] = traceback.format_exc()
    
    return jsonify(error_response), 500

@app.route('/')
def home():
    """首頁"""
    return jsonify({
        "message": "康寧大學聊天系統後端API", 
        "status": "running",
        "database": {
            "host": DB_CONFIG['host'],
            "port": DB_CONFIG['port'],
            "database": DB_CONFIG['database']
        }
    })

@app.route('/auth/google', methods=['GET'])
def google_auth():
    """Google 登入授權"""
    # 生成隨機 state 參數防止 CSRF 攻擊
    state = secrets.token_urlsafe(32)
    google_states[state] = {
        'timestamp': datetime.now(),
        'used': False
    }
    
    # 構建 Google OAuth URL
    google_auth_url = (
        f"https://accounts.google.com/o/oauth2/v2/auth?"
        f"client_id={GOOGLE_CLIENT_ID}&"
        f"redirect_uri={GOOGLE_REDIRECT_URI}&"
        f"scope=openid%20email%20profile&"
        f"response_type=code&"
        f"state={state}"
    )
    
    # 檢查是否為 AJAX 請求
    if request.headers.get('Content-Type') == 'application/json' or request.args.get('format') == 'json':
        return jsonify({"auth_url": google_auth_url})
    else:
        return redirect(google_auth_url)

@app.route('/auth/google/callback', methods=['GET'])
def google_callback():
    """處理 Google 登入回調"""
    code = request.args.get('code')
    state = request.args.get('state')
    error = request.args.get('error')
    
    if error:
        return jsonify({"error": f"Google 授權失敗: {error}"}), 400
    
    if not code or not state:
        return jsonify({"error": "缺少必要參數"}), 400
    
    # 驗證 state 參數
    if state not in google_states:
        return jsonify({"error": "無效的 state 參數"}), 400
    
    if google_states[state]['used']:
        return jsonify({"error": "state 參數已使用"}), 400
    
    try:
        # 交換授權碼獲取 access token
        token_url = "https://oauth2.googleapis.com/token"
        token_data = {
            'client_id': GOOGLE_CLIENT_ID,
            'client_secret': GOOGLE_CLIENT_SECRET,
            'code': code,
            'grant_type': 'authorization_code',
            'redirect_uri': GOOGLE_REDIRECT_URI
        }
        
        token_response = requests.post(token_url, data=token_data)
        token_response.raise_for_status()
        token_info = token_response.json()
        
        access_token = token_info.get('access_token')
        if not access_token:
            return jsonify({"error": "無法獲取 access token"}), 500
        
        # 使用 access token 獲取用戶資訊
        user_info_url = "https://www.googleapis.com/oauth2/v2/userinfo"
        headers = {'Authorization': f'Bearer {access_token}'}
        user_response = requests.get(user_info_url, headers=headers)
        user_response.raise_for_status()
        user_info = user_response.json()
        
        # 處理用戶資料
        email = user_info.get('email')
        name = user_info.get('name')
        google_id = user_info.get('id')
        picture = user_info.get('picture')
        
        if not email:
            return jsonify({"error": "無法獲取用戶郵箱"}), 400
        
        # 儲存用戶資訊到資料庫
        conn = get_db_connection()
        if not conn:
            return jsonify({"error": "資料庫連接失敗"}), 500
        
        try:
            with conn.cursor() as cursor:
                # 檢查用戶是否已存在
                cursor.execute(
                    "SELECT id, username, role FROM user WHERE email = %s OR google_id = %s",
                    (email, google_id)
                )
                existing_user = cursor.fetchone()
                
                if existing_user:
                    user_id, username, role_code = existing_user
                    
                    # 驗證現有角色代碼是否有效（存在於 role_types 表中）
                    cursor.execute("SELECT code FROM role_types WHERE code = %s", (role_code,))
                    valid_role = cursor.fetchone()
                    
                    # 調試：列出所有可用的角色代碼
                    cursor.execute("SELECT code, name FROM role_types")
                    all_roles = cursor.fetchall()
                    print(f"📋 資料庫中所有可用的角色: {[f'{r[0]} ({r[1]})' for r in all_roles]}")
                    print(f"📋 當前用戶的角色代碼: {role_code}")
                    
                    # 如果角色代碼無效，修正為有效的角色代碼
                    if not valid_role:
                        print(f"⚠️  發現無效的角色代碼 '{role_code}'，嘗試修正")
                        # 優先順序：STUDENT -> MEMBER -> 第一個可用的角色
                        fallback_codes = ['STU', 'MEMBER', 'TEACHER', 'ADMIN', 'STAFF']
                        new_role_code = None
                        for code in fallback_codes:
                            cursor.execute("SELECT code FROM role_types WHERE code = %s", (code,))
                            if cursor.fetchone():
                                new_role_code = code
                                print(f"✅ 修正為角色代碼: {new_role_code}")
                                break
                        
                        # 如果還是找不到，查詢第一個可用的角色
                        if not new_role_code:
                            cursor.execute("SELECT code FROM role_types LIMIT 1")
                            result = cursor.fetchone()
                            if result:
                                new_role_code = result[0]
                                print(f"✅ 使用第一個可用的角色代碼: {new_role_code}")
                            else:
                                # 如果 role_types 表是空的，這是一個嚴重的資料庫問題
                                print(f"❌ 錯誤：role_types 表為空，無法更新用戶")
                                return jsonify({"error": "系統錯誤：角色類型表為空，請聯繫管理員"}), 500
                        
                        # 再次確認 new_role_code 不是 None 且有效
                        if not new_role_code:
                            print(f"❌ 錯誤：無法找到有效的角色代碼來修正用戶 {user_id}")
                            return jsonify({"error": "系統錯誤：無法確定用戶角色，請聯繫管理員"}), 500
                        
                        # 最後一次驗證 new_role_code 是否存在
                        cursor.execute("SELECT code FROM role_types WHERE code = %s", (new_role_code,))
                        final_check = cursor.fetchone()
                        if not final_check:
                            print(f"❌ 錯誤：new_role_code '{new_role_code}' 在更新前驗證失敗")
                            return jsonify({"error": f"系統錯誤：角色代碼 '{new_role_code}' 無效，請聯繫管理員"}), 500
                        
                        print(f"✅ 準備更新用戶角色: user_id={user_id}, old_role={role_code}, new_role={new_role_code}")
                        cursor.execute(
                            "UPDATE user SET role = %s WHERE id = %s",
                            (new_role_code, user_id)
                        )
                        role_code = new_role_code
                        print(f"✅ 用戶角色更新成功: user_id={user_id}, role={role_code}")
                    
                    # 檢查現有頭像是否為本地上傳的
                    cursor.execute(
                        "SELECT profile_picture FROM user WHERE id = %s",
                        (user_id,)
                    )
                    current_picture = cursor.fetchone()
                    current_picture_path = current_picture[0] if current_picture and current_picture[0] else None
                    
                    # 如果已經有本地上傳的頭像（以 uploads/ 開頭），不要覆蓋
                    # 只有在沒有頭像或頭像是 Google URL 的情況下，才更新為新的 Google 頭像
                    # Google 登入自動設 email_verified = 1
                    if current_picture_path and current_picture_path.startswith('uploads/'):
                        # 保留本地上傳的頭像，只更新其他資訊
                        cursor.execute(
                            "UPDATE user SET google_id = %s, email = %s, email_verified = 1 WHERE id = %s",
                            (google_id, email, user_id)
                        )
                        print(f"更新現有用戶（保留本地上傳頭像）: {username}")
                    else:
                        # 沒有頭像或頭像是 Google URL，更新為新的 Google 頭像
                        cursor.execute(
                            "UPDATE user SET google_id = %s, profile_picture = %s, email = %s, email_verified = 1 WHERE id = %s",
                            (google_id, picture, email, user_id)
                        )
                        print(f"更新現有用戶（更新 Google 頭像）: {username}")
                    
                    # 從 role_types 表查詢角色名稱
                    cursor.execute("SELECT name FROM role_types WHERE code = %s", (role_code,))
                    role_result = cursor.fetchone()
                    role = role_result[0] if role_result else role_code  # 如果查不到，使用代碼作為後備
                else:
                    # 創建新用戶
                    username = name or email.split('@')[0]
                    
                    # 所有新註冊用戶都設為學生（使用角色代碼 'STUDENT'）
                    role_code = 'STUDENT'
                    print(f"新用戶預設設為學生: {email}")
                    
                    # 驗證 role_code 是否存在於 role_types 表中
                    cursor.execute("SELECT code FROM role_types WHERE code = %s", (role_code,))
                    valid_role = cursor.fetchone()
                    
                    # 調試：列出所有可用的角色代碼
                    cursor.execute("SELECT code, name FROM role_types")
                    all_roles = cursor.fetchall()
                    print(f"📋 資料庫中所有可用的角色: {[f'{r[0]} ({r[1]})' for r in all_roles]}")
                    print(f"📋 準備使用的角色代碼: {role_code}")
                    
                    # 如果 'STUDENT' 不存在，嘗試查找其他可用的角色代碼
                    if not valid_role:
                        print(f"⚠️  角色代碼 '{role_code}' 不存在於 role_types 表中，嘗試查找可用的角色")
                        # 優先順序：STUDENT -> MEMBER -> 第一個可用的角色
                        fallback_codes = ['STU', 'MEMBER', 'TEACHER', 'ADMIN', 'STAFF']
                        role_code = None
                        for code in fallback_codes:
                            cursor.execute("SELECT code FROM role_types WHERE code = %s", (code,))
                            if cursor.fetchone():
                                role_code = code
                                print(f"✅ 使用角色代碼: {role_code}")
                                break
                        
                        # 如果還是找不到，查詢第一個可用的角色
                        if not role_code:
                            cursor.execute("SELECT code FROM role_types LIMIT 1")
                            result = cursor.fetchone()
                            if result:
                                role_code = result[0]
                                print(f"✅ 使用第一個可用的角色代碼: {role_code}")
                            else:
                                # 如果 role_types 表是空的，這是一個嚴重的資料庫問題
                                print(f"❌ 錯誤：role_types 表為空，無法創建用戶")
                                return jsonify({"error": "系統錯誤：角色類型表為空，請聯繫管理員"}), 500
                    
                    # 確保用戶名唯一
                    original_username = username
                    counter = 1
                    while True:
                        cursor.execute("SELECT COUNT(*) FROM user WHERE username = %s", (username,))
                        if cursor.fetchone()[0] == 0:
                            break
                        username = f"{original_username}_{counter}"
                        counter += 1
                    
                    # 再次確認 role_code 不是 None 且有效
                    if not role_code:
                        print(f"❌ 錯誤：role_code 為 None，無法創建用戶")
                        return jsonify({"error": "系統錯誤：無法確定用戶角色，請聯繫管理員"}), 500
                    
                    # 最後一次驗證 role_code 是否存在
                    cursor.execute("SELECT code FROM role_types WHERE code = %s", (role_code,))
                    final_check = cursor.fetchone()
                    if not final_check:
                        print(f"❌ 錯誤：role_code '{role_code}' 在插入前驗證失敗")
                        return jsonify({"error": f"系統錯誤：角色代碼 '{role_code}' 無效，請聯繫管理員"}), 500
                    
                    print(f"✅ 準備插入新用戶: username={username}, role_code={role_code}")
                    cursor.execute(
                        """INSERT INTO user (username, name, email, google_id, role, password, profile_picture, email_verified) 
                           VALUES (%s, %s, %s, %s, %s, '', %s, 1)""",
                        (username, name, email, google_id, role_code, picture)
                    )
                    user_id = cursor.lastrowid
                    print(f"✅ 創建新用戶成功: {username}, ID: {user_id}, role: {role_code}")
                    
                    # 同步插入 student_normalized 表（根據正規化結構）
                    try:
                        cursor.execute(
                            """INSERT INTO student_normalized (user_id, name, email) 
                               VALUES (%s, %s, %s)""",
                            (user_id, name or username, email)
                        )
                        print(f"✅ 已同步創建學生資料: user_id={user_id}")
                    except Exception as student_error:
                        # 如果 student_normalized 表插入失敗，記錄錯誤但不影響註冊流程
                        print(f"⚠️  插入 student_normalized 表失敗（但用戶已創建）: {student_error}")
                    
                    # 獲取角色名稱以便重定向（從 role_types 表查詢）
                    cursor.execute("SELECT name FROM role_types WHERE code = %s", (role_code,))
                    role_result = cursor.fetchone()
                    role = role_result[0] if role_result else '學生'
                
                conn.commit()
                print(f"用戶資料保存成功: {username}")
                
                # 清理 state
                del google_states[state]
                
                # 重定向到前端頁面
                if role == '管理員':
                    redirect_url = f"http://localhost/Topics-frontend/frontend/admin_admission.php?google_login=success&username={username}&role={role}"
                elif role == '老師' or role == '學校行政人員':
                    # 老師和行政人員都跳轉到 teacher_profile.php
                    redirect_url = f"http://localhost/Topics-frontend/frontend/index.php?google_login=success&username={username}&role={role}"
                elif role == '學生':
                    redirect_url = f"http://localhost/Topics-frontend/frontend/index.php?google_login=success&username={username}&role={role}"
                else:
                    # 預設重定向到聊天系統登入頁面
                    redirect_url = f"http://localhost/Topics-frontend/frontend/chat/google_chat_integration.php?google_login=success&username={username}&role={role}"
                
                print(f"重定向到: {redirect_url}")
                return redirect(redirect_url)
                
        except Exception as e:
            import traceback
            error_details = traceback.format_exc()
            print(f"❌ 資料庫操作錯誤: {e}")
            print(f"詳細錯誤資訊:\n{error_details}")
            
            # 檢查是否為外鍵約束錯誤
            error_str = str(e)
            if 'foreign key' in error_str.lower() or 'role_types' in error_str.lower():
                return jsonify({
                    "error": f"用戶資料處理失敗: 角色設定錯誤。請確認 role_types 表中有有效的角色代碼。詳細錯誤: {error_str}"
                }), 500
            else:
                return jsonify({
                    "error": f"用戶資料處理失敗: {error_str}"
                }), 500
        finally:
            conn.close()
            
    except Exception as e:
        print(f"Google 回調錯誤: {e}")
        return jsonify({"error": f"登入失敗: {str(e)}"}), 500

@app.route('/user/profile', methods=['GET'])
def get_user_profile():
    """獲取用戶資料"""
    username = request.args.get('username')
    if not username:
        return jsonify({"error": "缺少用戶名參數"}), 400
    
    conn = get_db_connection()
    if not conn:
        return jsonify({"error": "資料庫連接失敗"}), 500
    
    try:
        with conn.cursor() as cursor:
            cursor.execute(
                "SELECT username, name, email, role, profile_picture FROM user WHERE username = %s",
                (username,)
            )
            user = cursor.fetchone()
            
            if user:
                return jsonify({
                    "username": user[0],
                    "name": user[1],
                    "email": user[2],
                    "role": user[3],
                    "profile_picture": user[4]
                }), 200
            else:
                return jsonify({"error": "用戶不存在"}), 404
                
    except Exception as e:
        print(f"查詢用戶資料錯誤: {e}")
        return jsonify({"error": "查詢失敗"}), 500
    finally:
        if conn:
            conn.close()

@app.route('/login', methods=['POST'])
def login():
    """一般登入功能 - 支持 JSON 和 FormData"""
    conn = None
    try:
        # 支持 JSON 和 FormData 兩種格式
        if request.is_json:
            data = request.get_json()
            username = data.get('username') if data else None
            password = data.get('password') if data else None
        else:
            username = request.form.get('username')
            password = request.form.get('password')
        
        print(f"🔐 登入嘗試 - 用戶名: {username}, Content-Type: {request.content_type}")
        print(f"   請求方法: {request.method}, URL: {request.url}")
        
        if not username or not password:
            print("❌ 缺少用戶名或密碼")
            return jsonify({
                "message": "請填寫帳號和密碼",
                "error": "missing_credentials"
            }), 400
        
        # 清理輸入（移除前後空格）
        username = username.strip()
        password = password.strip()
        
        print(f"📡 嘗試連接資料庫: {DB_CONFIG['host']}:{DB_CONFIG['port']}")
        conn = get_db_connection()
        if not conn:
            print("❌ 資料庫連接失敗")
            error_response = {
                "message": "資料庫連接失敗，請稍後再試或聯繫管理員",
                "error": "database_connection_failed",
                "host": DB_CONFIG['host'],
                "port": DB_CONFIG['port'],
                "database": DB_CONFIG['database']
            }
            if app.debug:
                error_response["debug"] = "請檢查資料庫伺服器是否運行，網路是否正常"
            return jsonify(error_response), 500
        
        print("✅ 資料庫連接成功，開始查詢用戶")
        
        try:
            with conn.cursor() as cursor:
                # 先查詢用戶是否存在（不檢查密碼）
                print(f"   查詢用戶: {username}")
                cursor.execute(
                    "SELECT username, role, status, password, email, email_verified FROM user WHERE username = %s",
                    (username,)
                )
                user = cursor.fetchone()
                
                if not user:
                    print(f"❌ 用戶不存在: {username}")
                    return jsonify({
                        "message": "帳號或密碼錯誤",
                        "error": "invalid_credentials"
                    }), 401
                
                print(f"   找到用戶: {user[0]}, 角色: {user[1]}, 狀態: {user[2]}")
                
                # 檢查密碼（支援雜湊密碼和明文密碼）
                db_password = user[3] if user[3] else ''
                password_valid = False
                
                # 檢查是否為雜湊密碼（PHP password_hash 格式：$2y$... 或 $2b$...）
                if db_password.startswith('$2y$') or db_password.startswith('$2b$') or db_password.startswith('$2a$'):
                    # 使用 bcrypt 驗證雜湊密碼
                    if BCrypt_AVAILABLE:
                        try:
                            # 將 PHP 的 $2y$ 格式轉換為 bcrypt 可接受的格式（如果需要）
                            bcrypt_hash = db_password.replace('$2y$', '$2b$') if db_password.startswith('$2y$') else db_password
                            password_valid = bcrypt.checkpw(password.encode('utf-8'), bcrypt_hash.encode('utf-8'))
                            print(f"   bcrypt 驗證結果: {password_valid}")
                        except Exception as e:
                            print(f"❌ bcrypt 驗證錯誤: {e}")
                            password_valid = False
                    else:
                        print("❌ bcrypt 模組未安裝")
                        return jsonify({
                            "message": "系統錯誤：bcrypt 模組未安裝",
                            "error": "system_error"
                        }), 500
                else:
                    # 明文密碼比較（向後兼容）
                    password_valid = (password == db_password)
                    print(f"   明文密碼比較結果: {password_valid}")
                
                if not password_valid:
                    print(f"❌ 密碼錯誤: {username} (輸入: {password[:3]}..., DB: {db_password[:3] if db_password else 'None'}...)")
                    return jsonify({
                        "message": "帳號或密碼錯誤",
                        "error": "invalid_credentials"
                    }), 401
                
                # 檢查帳號是否被停用
                # status 欄位：None/1 表示啟用，0 表示停用
                status = user[2]
                if status is None:
                    # 如果 status 為 NULL，預設為啟用
                    print(f"   狀態為 NULL，預設為啟用")
                    status = 1
                
                if status == 0:  # status = 0 表示停用
                    print(f"❌ 帳號被停用: {username}")
                    return jsonify({
                        "message": "您的帳號已被停用，請聯繫管理員。",
                        "error": "account_disabled"
                    }), 403
                
                # 檢查 email_verified 狀態（從查詢結果中獲取）
                email = user[4]     
                email_verified = user[5] if len(user) > 4 else 0
                
                if email_verified == 0:
                    print(f"❌ Email 未驗證: {username}, email: {email}")
                    return jsonify({
                        "message": "您的 Email 尚未驗證，請檢查您的郵箱並輸入驗證碼以完成註冊。",
                        "error": "email_not_verified",
                        "requires_verification": True,
                        "username": username,
                        "email": email
                    }), 403
                
                # 檢查 role，允許 STU、TEA 和 STA 登入
                user_role = user[1]  # role 欄位
                allowed_roles = ['STU', 'TEA', 'STA','DI', 'STA', 'STUDENT', 'TEACHER', '學校行政人員']  # 支援多種格式，AA權限與TEA一致
                
                # 檢查 role 是否在允許列表中
                if user_role not in allowed_roles:
                    print(f"❌ 角色不允許登入: {username}, 角色: {user_role}")
                    return jsonify({
                        "message": "您的帳號角色不允許登入此系統，僅限學生、老師和行政人員使用。",
                        "error": "role_not_allowed",
                        "role": user_role
                    }), 403
                
                print(f"✅ 登入成功: {username}, 角色: {user[1]}")
                return jsonify({
                    "message": "登入成功",
                    "username": user[0],
                    "role": user[1],
                    "success": True
                }), 200
                
        except pymysql.Error as e:
            print(f"❌ 登入資料庫錯誤: {e}")
            import traceback
            traceback.print_exc()
            error_response = {
                "message": "資料庫操作失敗，請稍後再試",
                "error": "database_error",
                "details": str(e)
            }
            if app.debug:
                error_response["traceback"] = traceback.format_exc()
            return jsonify(error_response), 500
        finally:
            if conn:
                conn.close()
                print("   資料庫連接已關閉")
                
    except Exception as e:
        print(f"❌ 登入發生未知錯誤: {e}")
        import traceback
        traceback.print_exc()
        error_response = {
            "message": "登入失敗，請稍後再試",
            "error": "unknown_error",
            "details": str(e)
        }
        if app.debug:
            error_response["traceback"] = traceback.format_exc()
        return jsonify(error_response), 500

@app.route('/sign', methods=['POST'])
def register():
    """註冊功能"""
    # 同時支援 form 與 JSON
    data = request.form if request.form else (request.get_json(silent=True) or {})
    username = data.get('username')
    password = data.get('password')
    confirm_password = data.get('confirm_password')
    email = data.get('email')
    name = data.get('name')
    
    if not all([username, password, email, name]):
        return jsonify({"message": "請填寫所有必填欄位"}), 400
    if confirm_password is not None and password != confirm_password:
        return jsonify({"message": "兩次密碼輸入不一致"}), 400
    if len(password) < 6:
        return jsonify({"message": "密碼長度至少需 6 碼"}), 400
    # 驗證密碼必須包含至少一個英文字母和一個數字
    import re
    if not re.search(r'[a-zA-Z]', password):
        return jsonify({"message": "密碼必須包含至少一個英文字母"}), 400
    if not re.search(r'[0-9]', password):
        return jsonify({"message": "密碼必須包含至少一個數字"}), 400
    
    conn = get_db_connection()
    if not conn:
        return jsonify({"message": "資料庫連接失敗"}), 500
    
    try:
        with conn.cursor() as cursor:
            # 檢查用戶名是否已存在
            cursor.execute("SELECT COUNT(*) FROM user WHERE username = %s", (username,))
            if cursor.fetchone()[0] > 0:
                conn.rollback()
                return jsonify({"message": "用戶名已被使用"}), 400

            # 檢查 Email 是否已存在（若資料表有唯一約束，可避免 500 錯誤）
            cursor.execute("SELECT COUNT(*) FROM user WHERE email = %s", (email,))
            if cursor.fetchone()[0] > 0:
                conn.rollback()
                return jsonify({"message": "電子郵件已被使用過"}), 400
            
            # 雜湊密碼
            hashed_password = password
            if BCrypt_AVAILABLE:
                hashed_password = bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
            else:
                # 如果沒有 bcrypt，至少使用簡單的雜湊（不推薦，但向後兼容）
                import hashlib
                hashed_password = hashlib.sha256(password.encode('utf-8')).hexdigest()
            
            # 插入新用戶（角色預設為學生，使用角色代碼 'STU'，email_verified 設為 0）
            print(f"📝 開始註冊用戶: username={username}, email={email}, name={name}")
            cursor.execute(
                "INSERT INTO user (username, password, email, name, role, email_verified) VALUES (%s, %s, %s, %s, 'STU', 0)",
                (username, hashed_password, email, name)
            )
            user_id = cursor.lastrowid
            print(f"✅ 已插入 user 表: user_id={user_id}")
            
            # 生成驗證碼
            import random
            verification_code = str(random.randint(1000, 9999)).zfill(4)
            expires_at = datetime.now().replace(microsecond=0)
            from datetime import timedelta
            expires_at = expires_at + timedelta(hours=1)  # 1小時後過期
            
            # 確保 email_verification_codes 表存在
            try:
                cursor.execute("""
                    CREATE TABLE IF NOT EXISTS email_verification_codes (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        code VARCHAR(4) NOT NULL,
                        expires_at DATETIME NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_user_id (user_id),
                        INDEX idx_code (code),
                        INDEX idx_expires_at (expires_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                """)
                print("✅ email_verification_codes 表已創建或已存在")
            except Exception as table_error:
                print(f"⚠️  創建驗證碼表時發生錯誤（可能已存在）: {table_error}")
            
            # 插入驗證碼
            cursor.execute(
                "INSERT INTO email_verification_codes (user_id, code, expires_at) VALUES (%s, %s, %s)",
                (user_id, verification_code, expires_at)
            )
            print(f"✅ 已生成驗證碼: {verification_code}")
            
            # 同步插入 student_normalized 表（根據正規化結構）
            try:
                cursor.execute(
                    """INSERT INTO student_normalized (user_id, name, email) 
                       VALUES (%s, %s, %s)""",
                    (user_id, name, email)
                )
                print(f"✅ 已同步創建學生資料: user_id={user_id}")
            except Exception as student_error:
                # 如果 student_normalized 表插入失敗，記錄錯誤但不影響註冊流程
                print(f"⚠️  插入 student_normalized 表失敗（但用戶已創建）: {student_error}")
            
            # 提交事務
            conn.commit()
            print(f"✅ 註冊成功並已提交: user_id={user_id}, username={username}")
            
            # 發送驗證碼郵件（使用 HTTP 請求調用 PHP API）
            try:
                print(f"📧 準備發送驗證碼郵件到: {email}")
                print(f"   驗證碼: {verification_code}")
                
                # 使用 HTTP 請求調用 PHP API（更可靠，不依賴 PHP 在 PATH 中）
                api_url = "http://localhost/Topics-frontend/frontend/api/send_verification_email.php"
                payload = {
                    'user_id': user_id,
                    'code': verification_code,
                    'email': email,
                    'name': name
                }
                
                try:
                    response = requests.post(api_url, data=payload, timeout=15)
                    print(f"   API 回應狀態碼: {response.status_code}")
                    
                    if response.status_code == 200:
                        try:
                            result_data = response.json()
                            if result_data.get('success'):
                                print(f"✅ 驗證碼郵件已發送到: {email}")
                            else:
                                print(f"⚠️  發送驗證碼郵件失敗: {result_data.get('message', '未知錯誤')}")
                        except json.JSONDecodeError:
                            print(f"⚠️  API 回應不是有效的 JSON: {response.text[:200]}")
                    else:
                        print(f"⚠️  發送驗證碼郵件 API 回應錯誤: HTTP {response.status_code}")
                        print(f"   回應內容: {response.text[:200]}")
                except requests.exceptions.ConnectionError:
                    print(f"⚠️  無法連接到 PHP API，可能 Web 伺服器未運行")
                    print(f"   請確保 Apache/XAMPP 正在運行，並可以訪問: {api_url}")
                except requests.exceptions.Timeout:
                    print(f"⚠️  發送郵件 API 請求超時（超過 15 秒）")
                except requests.exceptions.RequestException as req_error:
                    print(f"⚠️  發送驗證碼郵件 API 請求失敗: {req_error}")
            except Exception as email_error:
                print(f"❌ 發送驗證碼郵件時發生錯誤: {email_error}")
                import traceback
                traceback.print_exc()
            
            print(f"📤 返回註冊成功回應: requires_verification=True, username={username}, email={email}")
            return jsonify({
                "message": "註冊成功！請檢查您的 Email 並輸入驗證碼以完成註冊。",
                "requires_verification": True,
                "username": username,
                "email": email
            }), 200
            
    except pymysql.err.IntegrityError as e:
        # 可能的唯一鍵衝突或外鍵約束失敗
        if conn:
            conn.rollback()
        print(f"❌ 註冊資料庫約束錯誤: {e}")
        import traceback
        traceback.print_exc()
        error_msg = str(e).lower()
        if 'username' in error_msg or 'user.username' in error_msg:
            return jsonify({"message": "用戶名已被使用"}), 400
        elif 'email' in error_msg or 'user.email' in error_msg:
            return jsonify({"message": "電子郵件已被使用過"}), 400
        elif 'foreign key' in error_msg or 'role_types' in error_msg:
            return jsonify({"message": "註冊失敗：無效的角色設定"}), 400
        else:
            return jsonify({"message": "帳號或電子郵件已被使用"}), 400
    except Exception as e:
        # 發生任何異常時都要 rollback
        if conn:
            conn.rollback()
        print(f"❌ 註冊錯誤: {e}")
        import traceback
        traceback.print_exc()
        return jsonify({"message": f"註冊失敗，請稍後再試。錯誤: {str(e)}"}), 500
    finally:
        if conn:
            conn.close()

@app.route('/user/select-role', methods=['POST'])
def select_role():
    """Gmail用戶選擇角色"""
    data = request.get_json()
    if not data:
        return jsonify({"error": "缺少必要參數"}), 400
    
    username = data.get('username')
    role = data.get('role')
    
    if not username or not role:
        return jsonify({"error": "缺少必要參數"}), 400
    
    conn = get_db_connection()
    if not conn:
        return jsonify({"error": "資料庫連接失敗"}), 500
    
    try:
        with conn.cursor() as cursor:
            cursor.execute(
                "UPDATE user SET role = %s WHERE username = %s",
                (role, username)
            )
            conn.commit()
            
            return jsonify({
                "success": True,
                "message": f"身分設定成功，您現在是{role}",
                "username": username,
                "role": role
            }), 200
            
    except Exception as e:
        print(f"更新角色錯誤: {e}")
        return jsonify({"error": "更新失敗"}), 500
    finally:
        if conn:
            conn.close()

@app.route('/health', methods=['GET'])
def health_check():
    """健康檢查端點"""
    return jsonify({"status": "healthy", "timestamp": datetime.now().isoformat()}), 200

@app.route('/test/db', methods=['GET'])
def test_db_connection():
    """測試資料庫連接"""
    try:
        conn = get_db_connection()
        if conn:
            with conn.cursor() as cursor:
                cursor.execute("SELECT 1")
                cursor.fetchone()
            conn.close()
            return jsonify({
                "status": "success",
                "message": "資料庫連接正常",
                "host": DB_CONFIG['host'],
                "database": DB_CONFIG['database']
            }), 200
        else:
            return jsonify({
                "status": "failed",
                "message": "資料庫連接失敗",
                "host": DB_CONFIG['host'],
                "database": DB_CONFIG['database']
            }), 500
    except Exception as e:
        return jsonify({
            "status": "error",
            "message": f"資料庫測試失敗: {str(e)}",
            "host": DB_CONFIG['host'],
            "database": DB_CONFIG['database']
        }), 500

@app.route('/enrollment/submit', methods=['POST'])
def submit_enrollment():
    """提交就讀意願表單"""
    try:
        # 獲取表單資料
        data = request.form.to_dict()
        
        # 連接資料庫
        connection = get_db_connection()
        if not connection:
            return jsonify({
                'success': False,
                'message': '資料庫連接失敗，請稍後再試'
            }), 500
        
        with connection.cursor() as cursor:
            # 檢查資料表是否存在，如果不存在則創建
            cursor.execute("""
                CREATE TABLE IF NOT EXISTS enrollment_applications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(255) NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    identity ENUM('學生', '家長') NOT NULL,
                    gender ENUM('男', '女') NULL,
                    phone1 VARCHAR(50) NOT NULL,
                    phone2 VARCHAR(50) NULL,
                    email VARCHAR(255) NULL,
                    intention1 VARCHAR(255) DEFAULT '無特定',
                    system1 VARCHAR(50) NULL,
                    department1 VARCHAR(255) NULL,
                    intention2 VARCHAR(255) DEFAULT '無特定',
                    system2 VARCHAR(50) NULL,
                    department2 VARCHAR(255) NULL,
                    intention3 VARCHAR(255) DEFAULT '無特定',
                    system3 VARCHAR(50) NULL,
                    department3 VARCHAR(255) NULL,
                    junior_high VARCHAR(255) NULL,
                    current_grade VARCHAR(50) NULL,
                    line_id VARCHAR(255) NULL,
                    facebook VARCHAR(255) NULL,
                    recommended_teacher VARCHAR(255) NULL,
                    remarks TEXT NULL,
                    status ENUM('pending', 'contacted', 'enrolled') DEFAULT 'pending',
                    admin_comment TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_username (username),
                    INDEX idx_status (status),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            """)
            
            # 插入資料
            sql = """
                INSERT INTO enrollment_applications (
                    username, name, identity, gender, phone1, phone2, email,
                    intention1, system1, department1,
                    intention2, system2, department2,
                    intention3, system3, department3,
                    junior_high, current_grade, line_id, facebook, recommended_teacher, remarks
                ) VALUES (
                    %s, %s, %s, %s, %s, %s, %s,
                    %s, %s, %s,
                    %s, %s, %s,
                    %s, %s, %s,
                    %s, %s, %s, %s, %s, %s
                )
            """
            
            values = (
                data.get('username', '訪客'),
                data.get('name', ''),
                data.get('identity', ''),
                data.get('gender', ''),
                data.get('phone1', ''),
                data.get('phone2', ''),
                data.get('email', ''),
                data.get('intention1', '無特定'),
                data.get('system1', ''),
                data.get('department1', ''),
                data.get('intention2', '無特定'),
                data.get('system2', ''),
                data.get('department2', ''),
                data.get('intention3', '無特定'),
                data.get('system3', ''),
                data.get('department3', ''),
                data.get('junior_high', ''),
                data.get('current_grade', ''),
                data.get('line_id', ''),
                data.get('facebook', ''),
                data.get('recommended_teacher', ''),
                data.get('remarks', '')
            )
            
            cursor.execute(sql, values)
            application_id = cursor.lastrowid
            connection.commit()
            
        return jsonify({
            'success': True,
            'message': '就讀意願登錄成功！康寧大學將儘快與您聯絡。',
            'application_id': application_id
        })
        
    except Exception as e:
        print(f"提交就讀意願表單錯誤: {e}")
        return jsonify({
            'success': False,
            'message': f'提交失敗: {str(e)}'
        }), 500
    finally:
        if 'connection' in locals() and connection:
            connection.close()

# ✅ 獲取老師個人資料
@app.route('/teacher/profile/<username>', methods=['GET'])
def get_teacher_profile(username):
    try:
        conn = get_db_connection()
        if not conn:
            return jsonify({"message": "資料庫連接失敗"}), 500
            
        with conn.cursor() as cursor:
            # 先獲取user的id
            sql_get_user_id = "SELECT id FROM user WHERE username = %s"
            cursor.execute(sql_get_user_id, (username,))
            user_result = cursor.fetchone()
            
            if not user_result:
                return jsonify({"message": "使用者不存在"}), 404
            
            user_id = user_result[0]
            
            # 查詢老師個人資料
            sql_get_profile = "SELECT department, phone FROM teacher WHERE user_id = %s"
            cursor.execute(sql_get_profile, (user_id,))
            profile = cursor.fetchone()
            
            if profile:
                # 如果有科系代碼，從 departments 表查詢科系名稱
                department_name = None
                if profile[0]:  # department code
                    cursor.execute("SELECT name FROM departments WHERE code = %s", (profile[0],))
                    dept_result = cursor.fetchone()
                    department_name = dept_result[0] if dept_result else profile[0]
                
                return jsonify({
                    "department": profile[0],  # 返回代碼
                    "department_name": department_name if department_name else '',  # 返回名稱
                    "phone": profile[1]
                }), 200
            else:
                return jsonify({"message": "尚未填寫個人資料"}), 404

    except pymysql.Error as e:
        print(f"資料庫查詢錯誤：{e}")
        return jsonify({"message": "獲取個人資料失敗，請稍後再試。"}), 500
    except Exception as e:
        print(f"未知錯誤：{e}")
        return jsonify({"message": "獲取個人資料失敗，發生未知錯誤。"}), 500
    finally:
        if conn:
            conn.close()

# ✅ 保存老師個人資料
@app.route('/teacher/profile', methods=['POST'])
def save_teacher_profile():
    username = request.form.get('username')
    department = request.form.get('department')
    name = request.form.get('name')  # 新增：獲取 name 欄位
    phone = request.form.get('phone')

    try:
        conn = get_db_connection()
        if not conn:
            return jsonify({"message": "資料庫連接失敗"}), 500
            
        with conn.cursor() as cursor:
            # 先獲取user的id
            sql_get_user_id = "SELECT id FROM user WHERE username = %s"
            cursor.execute(sql_get_user_id, (username,))
            user_result = cursor.fetchone()
            
            if not user_result:
                return jsonify({"message": "使用者不存在"}), 404
            
            user_id = user_result[0]
            
            # 更新 user 表的 name（如果提供）
            if name:
                cursor.execute("UPDATE user SET name = %s WHERE id = %s", (name, user_id))
            
            # 如果 department 是中文名稱，轉換為代碼
            department_code = None
            if department:
                # 先檢查是否已經是代碼
                cursor.execute("SELECT code FROM departments WHERE code = %s", (department,))
                dept_result = cursor.fetchone()
                if dept_result:
                    department_code = dept_result[0]
                else:
                    # 如果不是代碼，嘗試用名稱查詢
                    cursor.execute("SELECT code FROM departments WHERE name = %s", (department,))
                    dept_result = cursor.fetchone()
                    if dept_result:
                        department_code = dept_result[0]
                    else:
                        return jsonify({"message": f"無效的科系：{department}"}), 400
            
            # 檢查是否已有個人資料
            sql_check = "SELECT COUNT(*) FROM teacher WHERE user_id = %s"
            cursor.execute(sql_check, (user_id,))
            exists = cursor.fetchone()[0] > 0
            
            if exists:
                # 更新現有資料（teacher 表沒有 name 欄位，name 在 user 表中）
                sql_update = "UPDATE teacher SET department = %s, phone = %s WHERE user_id = %s"
                cursor.execute(sql_update, (department_code if department_code else None, phone, user_id))
            else:
                # 新增資料（teacher 表沒有 name 欄位）
                sql_insert = "INSERT INTO teacher (user_id, department, phone) VALUES (%s, %s, %s)"
                cursor.execute(sql_insert, (user_id, department_code if department_code else None, phone))
            
            conn.commit()
            return jsonify({"message": "個人資料保存成功"}), 200

    except pymysql.Error as e:
        if conn:
            conn.rollback()
        print(f"資料庫寫入錯誤：{e}")
        return jsonify({"message": "保存失敗，請稍後再試。原因：資料庫錯誤"}), 500
    except Exception as e:
        if conn:
            conn.rollback()
        print(f"未知錯誤：{e}")
        return jsonify({"message": "保存失敗，發生未知錯誤。"}), 500
    finally:
        if conn:
            conn.close()

# ✅ 更新老師帳號和密碼
@app.route('/teacher/update-credentials', methods=['POST'])
def update_teacher_credentials():
    """更新老師的帳號和/或密碼"""
    old_username = request.form.get('old_username')
    new_username = request.form.get('new_username')
    new_password = request.form.get('new_password')
    current_password = request.form.get('current_password')  # 用於驗證身份
    
    if not old_username:
        return jsonify({"message": "缺少必要參數"}), 400
    
    if not new_password:
        return jsonify({"message": "新密碼不能為空"}), 400
    
    try:
        conn = get_db_connection()
        if not conn:
            return jsonify({"message": "資料庫連接失敗"}), 500
        
        with conn.cursor() as cursor:
            # 先驗證當前密碼
            # 使用 COALESCE 處理 username_changed 欄位可能不存在的情況
            sql_get_user = "SELECT id, password, COALESCE(username_changed, 0) as username_changed FROM user WHERE username = %s"
            cursor.execute(sql_get_user, (old_username,))
            user_result = cursor.fetchone()
            
            if not user_result:
                return jsonify({"message": "使用者不存在"}), 404
            
            user_id = user_result[0]
            db_password = user_result[1] if user_result[1] else ''
            # 安全地獲取 username_changed 欄位（如果欄位不存在，COALESCE 會返回 0）
            username_changed = user_result[2] if len(user_result) > 2 and user_result[2] is not None else 0
            
            # 驗證當前密碼
            password_valid = False
            if db_password.startswith('$2y$') or db_password.startswith('$2b$') or db_password.startswith('$2a$'):
                # 使用 bcrypt 驗證雜湊密碼
                if BCrypt_AVAILABLE:
                    try:
                        bcrypt_hash = db_password.replace('$2y$', '$2b$') if db_password.startswith('$2y$') else db_password
                        password_valid = bcrypt.checkpw(current_password.encode('utf-8'), bcrypt_hash.encode('utf-8'))
                    except Exception as e:
                        print(f"bcrypt 驗證錯誤: {e}")
                        password_valid = False
            else:
                # 明文密碼比較（向後兼容）
                password_valid = (current_password == db_password)
            
            if not password_valid:
                return jsonify({"message": "當前密碼錯誤"}), 401
            
            # 如果要修改帳號，檢查是否允許修改
            if new_username and new_username != old_username:
                # 檢查是否已經修改過帳號
                if username_changed == 1:
                    return jsonify({"message": "帳號只能修改一次，您已經修改過帳號了"}), 403
                
                # 檢查新帳號是否已存在
                sql_check_username = "SELECT COUNT(*) FROM user WHERE username = %s"
                cursor.execute(sql_check_username, (new_username,))
                if cursor.fetchone()[0] > 0:
                    return jsonify({"message": "此帳號已被使用"}), 409
                
                # 更新帳號
                sql_update_username = "UPDATE user SET username = %s, username_changed = 1 WHERE id = %s"
                cursor.execute(sql_update_username, (new_username, user_id))
            
            # 更新密碼（使用 PHP password_hash 格式）
            # 使用 bcrypt 生成密碼雜湊（PHP 兼容格式）
            if BCrypt_AVAILABLE:
                salt = bcrypt.gensalt(rounds=10)
                hashed_password = bcrypt.hashpw(new_password.encode('utf-8'), salt).decode('utf-8')
                # 轉換為 PHP 格式 ($2y$)
                hashed_password = hashed_password.replace('$2b$', '$2y$')
            else:
                # 如果沒有 bcrypt，使用簡單的雜湊（不推薦，但向後兼容）
                hashed_password = hashlib.sha256(new_password.encode('utf-8')).hexdigest()
            
            sql_update_password = "UPDATE user SET password = %s WHERE id = %s"
            cursor.execute(sql_update_password, (hashed_password, user_id))
            
            conn.commit()
            
            result_message = "密碼更新成功"
            if new_username and new_username != old_username:
                result_message = "帳號和密碼更新成功"
            
            return jsonify({
                "message": result_message,
                "new_username": new_username if new_username and new_username != old_username else old_username
            }), 200
    
    except pymysql.Error as e:
        if conn:
            conn.rollback()
        print(f"資料庫寫入錯誤：{e}")
        return jsonify({"message": "更新失敗，請稍後再試。原因：資料庫錯誤"}), 500
    except Exception as e:
        if conn:
            conn.rollback()
        print(f"未知錯誤：{e}")
        return jsonify({"message": "更新失敗，發生未知錯誤。"}), 500
    finally:
        if conn:
            conn.close()

# ✅ 獲取學生個人資料
@app.route('/student/profile/<username>', methods=['GET'])
def get_student_profile(username):
    try:
        conn = get_db_connection()
        if not conn:
            return jsonify({"message": "資料庫連接失敗"}), 500
            
        with conn.cursor() as cursor:
            # 先獲取user的id和基本資訊
            sql_get_user = "SELECT id, name, email FROM user WHERE username = %s"
            cursor.execute(sql_get_user, (username,))
            user_result = cursor.fetchone()
            
            if not user_result:
                return jsonify({"message": "使用者不存在"}), 404
            
            user_id = user_result[0]
            user_name = user_result[1] if user_result[1] else ''
            user_email = user_result[2] if user_result[2] else ''
            
            # 查詢學生個人資料
            sql_get_profile = """
                SELECT s.student_id, d.code as department_code, d.name as department_name,
                       g.code as grade_code, g.name as grade_name,
                       s.class_name, s.email, s.phone 
                FROM student_normalized s
                LEFT JOIN departments d ON s.department_id = d.id
                LEFT JOIN grades g ON s.grade_id = g.id
                WHERE s.user_id = %s
            """
            cursor.execute(sql_get_profile, (user_id,))
            profile = cursor.fetchone()
            
            if profile:
                # 從查詢結果中獲取科系和年級資訊（已經 JOIN 了）
                department_code = profile[1] if profile[1] else None
                department_name = profile[2] if profile[2] else None
                grade_code = profile[3] if profile[3] else None
                grade_name_from_db = profile[4] if profile[4] else None
                
                # 將代碼轉換為顯示名稱（直接使用資料庫中的名稱：專一、專二、專三、專四、專五、國一、國二、國三）
                grade_display_mapping = {
                    'F1': '專一', 'F2': '專二', 'F3': '專三', 'F4': '專四', 'F5': '專五',
                    'J1': '國一', 'J2': '國二', 'J3': '國三',
                    'H1': '高一', 'H2': '高二', 'H3': '高三'
                }
                # 優先使用代碼映射，如果沒有則使用資料庫中的名稱
                grade_name = grade_display_mapping.get(grade_code, grade_name_from_db) if grade_code else ''
                
                return jsonify({
                    "name": user_name,
                    "email": profile[6] if profile[6] else user_email,  # 優先使用 student_normalized 表的 email
                    "student_id": profile[0] if profile[0] else '',
                    "department": department_code if department_code else '',  # 返回代碼
                    "department_name": department_name if department_name else '',  # 返回名稱
                    "grade": grade_code if grade_code else '',  # 返回代碼
                    "grade_name": grade_name if grade_name else '',  # 返回名稱
                    "class_name": profile[5] if profile[5] else '',
                    "phone": profile[7] if profile[7] else ''
                }), 200
            else:
                # 如果沒有學生資料，返回基本資訊
                return jsonify({
                    "name": user_name,
                    "email": user_email,
                    "student_id": '',
                    "department": '',
                    "department_name": '',
                    "grade": '',
                    "grade_name": '',
                    "class_name": '',
                    "phone": ''
                }), 200

    except pymysql.Error as e:
        print(f"資料庫查詢錯誤：{e}")
        return jsonify({"message": "獲取個人資料失敗，請稍後再試。"}), 500
    except Exception as e:
        print(f"未知錯誤：{e}")
        return jsonify({"message": "獲取個人資料失敗，發生未知錯誤。"}), 500
    finally:
        if conn:
            conn.close()

# ✅ 保存學生個人資料
@app.route('/student/profile', methods=['POST'])
def save_student_profile():
    """保存學生個人資料"""
    # 同時支援 form 與 JSON
    data = request.form if request.form else (request.get_json(silent=True) or {})
    username = data.get('username')
    name = data.get('name')
    department = data.get('department')  # 可以是代碼或名稱
    phone = data.get('phone')
    student_id = data.get('student_id')
    grade = data.get('grade')
    class_name = data.get('class_name')
    email = data.get('email')  # 可選，如果不提供則使用 user 表的 email
    
    if not username:
        return jsonify({"message": "缺少必要參數：username"}), 400
    
    try:
        conn = get_db_connection()
        if not conn:
            return jsonify({"message": "資料庫連接失敗"}), 500
            
        with conn.cursor() as cursor:
            # 先獲取user的id
            sql_get_user_id = "SELECT id, email FROM user WHERE username = %s"
            cursor.execute(sql_get_user_id, (username,))
            user_result = cursor.fetchone()
            
            if not user_result:
                return jsonify({"message": "使用者不存在"}), 404
            
            user_id = user_result[0]
            user_email = user_result[1] if user_result[1] else ''
            
            # 如果 department 是中文名稱，轉換為代碼
            department_code = department
            if department:
                # 先檢查是否已經是代碼（嘗試查詢是否存在）
                cursor.execute("SELECT code FROM departments WHERE code = %s", (department,))
                dept_result = cursor.fetchone()
                if dept_result:
                    department_code = dept_result[0]
                else:
                    # 如果不是代碼，嘗試用名稱查詢
                    cursor.execute("SELECT code FROM departments WHERE name = %s", (department,))
                    dept_result = cursor.fetchone()
                    if dept_result:
                        department_code = dept_result[0]
                    else:
                        return jsonify({"message": f"無效的科系：{department}"}), 400
            
            # 如果 grade 是中文名稱，轉換為代碼（grade 也有外鍵約束到 identity_options）
            grade_code = grade
            if grade:
                # 創建年級映射（前端使用「專一」到「專五」和「國一」到「國三」）
                grade_mapping = {
                    # 五專
                    '專一': 'F1', '專二': 'F2', '專三': 'F3',
                    '專四': 'F4', '專五': 'F5',
                    # 國中
                    '國一': 'J1', '國二': 'J2', '國三': 'J3',
                    # 高中
                    '高一': 'H1', '高二': 'H2', '高三': 'H3',
                    # 舊格式（向後兼容）
                    '一年級': 'F1', '二年級': 'F2', '三年級': 'F3',
                    '四年級': 'F4', '五年級': 'F5'
                }
                
                # 先檢查是否已經是代碼
                cursor.execute("SELECT code FROM identity_options WHERE code = %s", (grade,))
                grade_result = cursor.fetchone()
                if grade_result:
                    grade_code = grade_result[0]
                elif grade in grade_mapping:
                    # 使用映射轉換
                    grade_code = grade_mapping[grade]
                    # 驗證映射後的代碼是否存在
                    cursor.execute("SELECT code FROM identity_options WHERE code = %s", (grade_code,))
                    if not cursor.fetchone():
                        grade_code = None  # 如果映射的代碼不存在，設為 None
                else:
                    # 既不是代碼也不是映射值，嘗試直接查詢名稱
                    cursor.execute("SELECT code FROM identity_options WHERE name = %s", (grade,))
                    grade_result = cursor.fetchone()
                    if grade_result:
                        grade_code = grade_result[0]
                    else:
                        # 如果找不到對應的代碼，設為 None（允許為空）
                        grade_code = None
            
            # 更新 user 表的 name（如果提供）
            if name:
                cursor.execute("UPDATE user SET name = %s WHERE id = %s", (name, user_id))
            
            # 使用 email（如果提供），否則使用 user 表的 email
            final_email = email if email else user_email
            
            # 將 department_code 轉換為 department_id（正規化後使用 ID）
            department_id = None
            if department_code:
                cursor.execute("SELECT id FROM departments WHERE code = %s", (department_code,))
                dept_result = cursor.fetchone()
                if dept_result:
                    department_id = dept_result[0]
            
            # 將 grade_code 轉換為 grade_id（正規化後使用 ID）
            grade_id = None
            if grade_code:
                cursor.execute("SELECT id FROM grades WHERE code = %s", (grade_code,))
                grade_result = cursor.fetchone()
                if grade_result:
                    grade_id = grade_result[0]
            
            # 獲取用戶名稱（student_normalized 表需要 name 欄位）
            cursor.execute("SELECT name FROM user WHERE id = %s", (user_id,))
            user_name_result = cursor.fetchone()
            user_name = user_name_result[0] if user_name_result else name if name else ''
            
            # 檢查是否已有個人資料（使用 student_normalized 表）
            sql_check = "SELECT COUNT(*) FROM student_normalized WHERE user_id = %s"
            cursor.execute(sql_check, (user_id,))
            exists = cursor.fetchone()[0] > 0
            
            if exists:
                # 更新現有資料
                sql_update = """
                    UPDATE student_normalized 
                    SET name = %s, student_id = %s, department_id = %s, grade_id = %s, 
                        class_name = %s, email = %s, phone = %s 
                    WHERE user_id = %s
                """
                cursor.execute(sql_update, (
                    user_name,
                    student_id if student_id else None,
                    department_id,
                    grade_id,
                    class_name if class_name else None,
                    final_email if final_email else None,
                    phone if phone else None,
                    user_id
                ))
            else:
                # 新增資料
                sql_insert = """
                    INSERT INTO student_normalized (user_id, name, student_id, department_id, grade_id, class_name, email, phone) 
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                """
                cursor.execute(sql_insert, (
                    user_id,
                    user_name,
                    student_id if student_id else None,
                    department_id,
                    grade_id,
                    class_name if class_name else None,
                    final_email if final_email else None,
                    phone if phone else None
                ))
            
            conn.commit()
            return jsonify({"message": "個人資料保存成功"}), 200

    except pymysql.Error as e:
        if conn:
            conn.rollback()
        print(f"資料庫寫入錯誤：{e}")
        return jsonify({"message": "保存失敗，請稍後再試。原因：資料庫錯誤"}), 500
    except Exception as e:
        if conn:
            conn.rollback()
        print(f"未知錯誤：{e}")
        return jsonify({"message": "保存失敗，發生未知錯誤。"}), 500
    finally:
        if conn:
            conn.close()

# ✅ QA 列表 API
@app.route('/qa', methods=['GET'])
def get_faq():
    try:
        conn = get_db_connection()
        if not conn:
            return jsonify({"message": "資料庫連接失敗"}), 500
            
        with conn.cursor() as cursor:
            # 根據你的資料表結構：id, question, answer, is_active, created_at, updated_at
            sql = "SELECT id, question, answer FROM qa WHERE is_active = 1 ORDER BY id ASC"
            cursor.execute(sql)
            faqs = cursor.fetchall()

            # 把查詢結果轉換成 JSON 格式
            result = []
            for row in faqs:
                result.append({
                    "id": row[0],
                    "question": row[1],
                    "answer": row[2]
                })

        return jsonify(result), 200

    except pymysql.Error as e:
        print(f"資料庫查詢錯誤：{e}")
        return jsonify({"message": "無法獲取 QA列表"}), 500
    except Exception as e:
        print(f"未知錯誤：{e}")
        return jsonify({"message": "發生未知錯誤"}), 500
    finally:
        if conn:
            conn.close()

# ✅ 忘記密碼 API
@app.route('/forgot-password', methods=['POST'])
def forgot_password():
    """處理忘記密碼請求，生成重置 token 並發送郵件"""
    try:
        data = request.get_json()
        if not data:
            return jsonify({"message": "請輸入帳號或電子郵件"}), 400
        
        username_or_email = data.get('username_or_email', '').strip()
        
        if not username_or_email:
            return jsonify({"message": "請輸入帳號或電子郵件"}), 400
        
        conn = get_db_connection()
        if not conn:
            return jsonify({"message": "資料庫連接失敗"}), 500
        
        try:
            with conn.cursor() as cursor:
                # 查詢用戶（根據用戶名或郵箱）
                cursor.execute(
                    "SELECT id, username, name, email FROM user WHERE username = %s OR email = %s",
                    (username_or_email, username_or_email)
                )
                user = cursor.fetchone()
                
                if not user:
                    # 為了安全，不透露用戶是否存在
                    return jsonify({
                        "message": "如果該帳號或郵箱存在，我們已發送重設密碼連結到您的註冊郵箱。"
                    }), 200
                
                user_id, username, name, email = user
                
                # 處理 None 值
                username = username or ''
                name = name or username or ''
                email = email or ''
                
                if not email:
                    return jsonify({"message": "該帳號未綁定電子郵件，無法重設密碼"}), 400
                
                # 生成重置 token
                reset_token = secrets.token_urlsafe(32)
                expires_at = datetime.now().timestamp() + 3600  # 1小時後過期
                
                # 確保 password_reset_tokens 表存在
                try:
                    # 先檢查表是否存在
                    cursor.execute("""
                        SELECT COUNT(*) FROM information_schema.tables 
                        WHERE table_schema = DATABASE() 
                        AND table_name = 'password_reset_tokens'
                    """)
                    table_exists = cursor.fetchone()[0] > 0
                    
                    if not table_exists:
                        # 創建表（不使用 FOREIGN KEY 約束以避免問題）
                        cursor.execute("""
                            CREATE TABLE password_reset_tokens (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                user_id INT NOT NULL,
                                token VARCHAR(255) NOT NULL UNIQUE,
                                expires_at BIGINT NOT NULL,
                                used TINYINT(1) DEFAULT 0,
                                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                INDEX idx_token (token),
                                INDEX idx_user_id (user_id)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                        """)
                        conn.commit()
                        print("✅ password_reset_tokens 表已創建")
                    else:
                        print("✅ password_reset_tokens 表已存在")
                except pymysql.Error as table_error:
                    # 表可能已存在，繼續執行
                    print(f"⚠️  創建表時發生錯誤（可能已存在）: {table_error}")
                    conn.rollback()
                    # 嘗試繼續執行，如果表已存在應該沒問題
                
                # 將舊的 token 標記為已使用
                try:
                    cursor.execute(
                        "UPDATE password_reset_tokens SET used = 1 WHERE user_id = %s AND used = 0",
                        (user_id,)
                    )
                except pymysql.Error:
                    # 如果表不存在或沒有舊 token，忽略錯誤
                    pass
                
                # 插入新的 token
                cursor.execute(
                    "INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (%s, %s, %s)",
                    (user_id, reset_token, int(expires_at))
                )
                conn.commit()
                
                print(f"✅ 已生成重置 token: user_id={user_id}, username={username}, email={email}")
                
                # 發送重設密碼郵件（使用 HTTP 請求調用 PHP API）
                try:
                    api_url = "http://localhost/Topics-frontend/frontend/api/send_reset_password_email.php"
                    payload = {
                        'user_id': user_id,
                        'token': reset_token,
                        'email': email,
                        'name': name or username
                    }
                    
                    try:
                        response = requests.post(api_url, data=payload, timeout=15)
                        print(f"   郵件 API 回應狀態碼: {response.status_code}")
                        
                        if response.status_code == 200:
                            try:
                                result_data = response.json()
                                if result_data.get('success'):
                                    print(f"✅ 重設密碼郵件已發送到: {email}")
                                else:
                                    print(f"⚠️  發送重設密碼郵件失敗: {result_data.get('message', '未知錯誤')}")
                            except json.JSONDecodeError:
                                print(f"⚠️  API 回應不是有效的 JSON: {response.text[:200]}")
                        else:
                            print(f"⚠️  發送重設密碼郵件 API 回應錯誤: HTTP {response.status_code}")
                            print(f"   回應內容: {response.text[:200]}")
                    except requests.exceptions.ConnectionError:
                        print(f"⚠️  無法連接到 PHP API，可能 Web 伺服器未運行")
                        print(f"   請確保 Apache/XAMPP 正在運行，並可以訪問: {api_url}")
                    except requests.exceptions.Timeout:
                        print(f"⚠️  發送郵件 API 請求超時（超過 15 秒）")
                    except requests.exceptions.RequestException as req_error:
                        print(f"⚠️  發送重設密碼郵件 API 請求失敗: {req_error}")
                except Exception as email_error:
                    print(f"❌ 發送重設密碼郵件時發生錯誤: {email_error}")
                    import traceback
                    traceback.print_exc()
                
                # 為了安全，不透露用戶是否存在
                return jsonify({
                    "message": "如果該帳號或郵箱存在，我們已發送重設密碼連結到您的註冊郵箱。"
                }), 200
                
        except pymysql.Error as e:
            conn.rollback()
            error_msg = str(e)
            print(f"❌ 資料庫錯誤: {error_msg}")
            import traceback
            traceback.print_exc()
            # 返回更詳細的錯誤訊息（僅在開發模式下）
            if app.debug:
                return jsonify({"message": f"資料庫錯誤: {error_msg}"}), 500
            return jsonify({"message": "處理請求時發生錯誤，請稍後再試"}), 500
        finally:
            if conn:
                conn.close()
            
    except Exception as e:
        error_msg = str(e)
        print(f"❌ 忘記密碼處理錯誤: {error_msg}")
        import traceback
        traceback.print_exc()
        # 返回更詳細的錯誤訊息（僅在開發模式下）
        if app.debug:
            return jsonify({"message": f"處理錯誤: {error_msg}"}), 500
        return jsonify({"message": "處理請求時發生錯誤，請稍後再試"}), 500

# ✅ 驗證重置 token API
@app.route('/verify-reset-token', methods=['GET'])
def verify_reset_token():
    """驗證重置密碼 token 是否有效"""
    try:
        token = request.args.get('token')
        
        if not token:
            return jsonify({"valid": False, "message": "缺少 token 參數"}), 400
        
        conn = get_db_connection()
        if not conn:
            return jsonify({"valid": False, "message": "資料庫連接失敗"}), 500
        
        try:
            with conn.cursor() as cursor:
                # 查詢 token
                cursor.execute("""
                    SELECT prt.user_id, prt.expires_at, prt.used, u.username, u.name
                    FROM password_reset_tokens prt
                    JOIN user u ON prt.user_id = u.id
                    WHERE prt.token = %s
                """, (token,))
                result = cursor.fetchone()
                
                if not result:
                    return jsonify({"valid": False, "message": "無效的重設連結"}), 200
                
                user_id, expires_at, used, username, name = result
                
                # 檢查是否已使用
                if used:
                    return jsonify({"valid": False, "message": "此重設連結已使用過"}), 200
                
                # 檢查是否過期
                current_time = datetime.now().timestamp()
                if expires_at < current_time:
                    return jsonify({"valid": False, "message": "此重設連結已過期"}), 200
                
                return jsonify({
                    "valid": True,
                    "username": username,
                    "name": name or username
                }), 200
                
        except pymysql.Error as e:
            print(f"❌ 資料庫錯誤: {e}")
            return jsonify({"valid": False, "message": "驗證失敗，請稍後再試"}), 500
        finally:
            conn.close()
            
    except Exception as e:
        print(f"❌ 驗證 token 錯誤: {e}")
        import traceback
        traceback.print_exc()
        return jsonify({"valid": False, "message": "驗證失敗，請稍後再試"}), 500

# ✅ 重置密碼 API
@app.route('/reset-password', methods=['POST'])
def reset_password():
    """使用 token 重置密碼"""
    try:
        data = request.get_json()
        token = data.get('token', '').strip()
        new_password = data.get('new_password', '').strip()
        
        if not token or not new_password:
            return jsonify({"message": "缺少必要參數"}), 400
        
        if len(new_password) < 6:
            return jsonify({"message": "密碼長度至少需要 6 個字元"}), 400
        
        conn = get_db_connection()
        if not conn:
            return jsonify({"message": "資料庫連接失敗"}), 500
        
        try:
            with conn.cursor() as cursor:
                # 查詢 token
                cursor.execute("""
                    SELECT prt.user_id, prt.expires_at, prt.used
                    FROM password_reset_tokens prt
                    WHERE prt.token = %s
                """, (token,))
                result = cursor.fetchone()
                
                if not result:
                    return jsonify({"message": "無效的重設連結"}), 400
                
                user_id, expires_at, used = result
                
                # 檢查是否已使用
                if used:
                    return jsonify({"message": "此重設連結已使用過"}), 400
                
                # 檢查是否過期
                current_time = datetime.now().timestamp()
                if expires_at < current_time:
                    return jsonify({"message": "此重設連結已過期"}), 400
                
                # 雜湊新密碼
                if BCrypt_AVAILABLE:
                    hashed_password = bcrypt.hashpw(new_password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
                else:
                    # 如果沒有 bcrypt，使用 SHA256（不推薦，但作為後備）
                    hashed_password = hashlib.sha256(new_password.encode('utf-8')).hexdigest()
                
                # 更新密碼
                cursor.execute(
                    "UPDATE user SET password = %s WHERE id = %s",
                    (hashed_password, user_id)
                )
                
                # 標記 token 為已使用
                cursor.execute(
                    "UPDATE password_reset_tokens SET used = 1 WHERE token = %s",
                    (token,)
                )
                
                conn.commit()
                print(f"✅ 密碼已重設: user_id={user_id}")
                
                return jsonify({
                    "message": "密碼重設成功！請使用新密碼登入。"
                }), 200
                
        except pymysql.Error as e:
            conn.rollback()
            print(f"❌ 資料庫錯誤: {e}")
            return jsonify({"message": "重設密碼失敗，請稍後再試"}), 500
        finally:
            conn.close()
            
    except Exception as e:
        print(f"❌ 重置密碼錯誤: {e}")
        import traceback
        traceback.print_exc()
        return jsonify({"message": "重設密碼失敗，請稍後再試"}), 500

if __name__ == '__main__':
    print("🚀 啟動康寧大學聊天系統後端...")
    print(f"Google Client ID: {GOOGLE_CLIENT_ID[:20]}...")
    print(f"重定向URI: {GOOGLE_REDIRECT_URI}")
    app.run(host='0.0.0.0', port=5000, debug=True)
