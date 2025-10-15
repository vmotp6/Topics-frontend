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
CORS(app, supports_credentials=True, origins=['http://localhost', 'http://localhost:80', 'http://127.0.0.1'])
app.secret_key = 'your-secret-key-here'  # 請更改為安全的密鑰

# 資料庫連接配置
DB_CONFIG = {
    'host': '100.79.58.120',
    'user': 'root',
    'password': '',
    'database': 'topics_good',
    'charset': 'utf8mb4'
}

# Google OAuth 配置 - 優先使用環境變數，否則使用config.py
GOOGLE_CLIENT_ID = os.getenv('GOOGLE_CLIENT_ID') or globals().get('GOOGLE_CLIENT_ID', 'your-google-client-id')
GOOGLE_CLIENT_SECRET = os.getenv('GOOGLE_CLIENT_SECRET') or globals().get('GOOGLE_CLIENT_SECRET', 'your-google-client-secret')
GOOGLE_REDIRECT_URI = os.getenv('GOOGLE_REDIRECT_URI') or globals().get('GOOGLE_REDIRECT_URI', 'http://localhost:5000/auth/google/callback')

# 存儲 state 參數（生產環境應使用 Redis 或資料庫）
google_states = {}

def get_db_connection():
    """獲取資料庫連接"""
    try:
        return pymysql.connect(**DB_CONFIG)
    except Exception as e:
        print(f"資料庫連接失敗: {e}")
        return None

@app.route('/')
def home():
    """首頁"""
    return jsonify({"message": "康寧大學聊天系統後端API", "status": "running"})

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
                    # 更新現有用戶資訊
                    cursor.execute(
                        "UPDATE user SET google_id = %s, profile_picture = %s, email = %s WHERE id = %s",
                        (google_id, picture, email, existing_user[0])
                    )
                    user_id, username, role = existing_user
                    print(f"更新現有用戶: {username}")
                else:
                    # 創建新用戶
                    username = name or email.split('@')[0]
                    
                    # 基於email domain自動判斷身分
                    if email.endswith('@stu.ukn.edu.tw'):
                        role = '學生'
                        print(f"根據email domain判斷為學生: {email}")
                    elif email.endswith('@ukn.edu.tw'):
                        role = '老師'
                        print(f"根據email domain判斷為老師: {email}")
                    else:
                        role = '學生'  # 非學校帳號都視為學生
                        print(f"非學校帳號，判斷為學生: {email}")
                    
                    # 確保用戶名唯一
                    original_username = username
                    counter = 1
                    while True:
                        cursor.execute("SELECT COUNT(*) FROM user WHERE username = %s", (username,))
                        if cursor.fetchone()[0] == 0:
                            break
                        username = f"{original_username}_{counter}"
                        counter += 1
                    
                    cursor.execute(
                        """INSERT INTO user (username, name, email, google_id, role, password, profile_picture) 
                           VALUES (%s, %s, %s, %s, %s, '', %s)""",
                        (username, name, email, google_id, role, picture)
                    )
                    user_id = cursor.lastrowid
                    print(f"創建新用戶: {username}, ID: {user_id}")
                
                conn.commit()
                print(f"用戶資料保存成功: {username}")
                
                # 清理 state
                del google_states[state]
                
                # 重定向到前端頁面
                if role == '管理員':
                    redirect_url = f"http://localhost/Topics-frontend/frontend/admin_admission.php?google_login=success&username={username}&role={role}"
                elif role == '老師':
                    redirect_url = f"http://localhost/Topics-frontend/frontend/teacher.php?google_login=success&username={username}&role={role}"
                elif role == '學生':
                    redirect_url = f"http://localhost/Topics-frontend/frontend/student.php?google_login=success&username={username}&role={role}"
                else:
                    redirect_url = f"http://localhost/Topics-frontend/frontend/index.php?google_login=success&username={username}&role={role}"
                
                print(f"重定向到: {redirect_url}")
                return redirect(redirect_url)
                
        except Exception as e:
            print(f"資料庫操作錯誤: {e}")
            return jsonify({"error": "用戶資料處理失敗"}), 500
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
        conn.close()

@app.route('/login', methods=['POST'])
def login():
    """一般登入功能"""
    username = request.form.get('username')
    password = request.form.get('password')
    
    if not username or not password:
        return jsonify({"message": "請填寫帳號和密碼"}), 400
    
    conn = get_db_connection()
    if not conn:
        return jsonify({"message": "資料庫連接失敗"}), 500
    
    try:
        with conn.cursor() as cursor:
            # 查詢使用者帳號、角色是否正確
            sql = "SELECT username, role FROM user WHERE username=%s AND password=%s"
            cursor.execute(sql, (username, password))
            user = cursor.fetchone()
            
            if user:
                return jsonify({
                    "message": "登入成功",
                    "username": user[0],
                    "role": user[1]
                }), 200
            else:
                return jsonify({"message": "帳號或密碼錯誤"}), 401
                
    except Exception as e:
        print(f"登入錯誤: {e}")
        return jsonify({"message": "登入失敗，請稍後再試。"}), 500
    finally:
        conn.close()

@app.route('/sign', methods=['POST'])
def register():
    """註冊功能"""
    username = request.form.get('username')
    password = request.form.get('password')
    email = request.form.get('email')
    name = request.form.get('name')
    
    if not all([username, password, email, name]):
        return jsonify({"message": "請填寫所有必填欄位"}), 400
    
    conn = get_db_connection()
    if not conn:
        return jsonify({"message": "資料庫連接失敗"}), 500
    
    try:
        with conn.cursor() as cursor:
            # 檢查用戶名是否已存在
            cursor.execute("SELECT COUNT(*) FROM user WHERE username = %s", (username,))
            if cursor.fetchone()[0] > 0:
                return jsonify({"message": "用戶名已存在"}), 400
            
            # 插入新用戶
            cursor.execute(
                "INSERT INTO user (username, password, email, name, role) VALUES (%s, %s, %s, %s, '學生')",
                (username, password, email, name)
            )
            conn.commit()
            
            return jsonify({"message": "註冊成功"}), 200
            
    except Exception as e:
        print(f"註冊錯誤: {e}")
        return jsonify({"message": "註冊失敗，請稍後再試。"}), 500
    finally:
        conn.close()

@app.route('/user/select-role', methods=['POST'])
def select_role():
    """Gmail用戶選擇角色"""
    data = request.get_json()
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
        conn.close()

@app.route('/health', methods=['GET'])
def health_check():
    """健康檢查端點"""
    return jsonify({"status": "healthy", "timestamp": datetime.now().isoformat()}), 200

@app.route('/enrollment/submit', methods=['POST'])
def submit_enrollment():
    """提交就讀意願表單"""
    try:
        # 獲取表單資料
        data = request.form.to_dict()
        
        # 連接資料庫
        connection = pymysql.connect(
            host='100.79.58.120',
            user='root',
            password='',
            database='topics_good',
            charset='utf8mb4'
        )
        
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
        return jsonify({
            'success': False,
            'message': f'提交失敗: {str(e)}'
        }), 500

if __name__ == '__main__':
    print("🚀 啟動康寧大學聊天系統後端...")
    print(f"Google Client ID: {GOOGLE_CLIENT_ID[:20]}...")
    print(f"重定向URI: {GOOGLE_REDIRECT_URI}")
    app.run(host='0.0.0.0', port=5000, debug=True)
