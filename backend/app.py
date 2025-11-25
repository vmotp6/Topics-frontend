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
CORS(app, supports_credentials=True, origins=['http://localhost', 'http://localhost:80', 'http://127.0.0.1'])
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
                print(f"   1. 資料庫伺服器 {DB_CONFIG['host']}:{DB_CONFIG['port']} 是否可訪問")
                print(f"   2. 網路連線是否正常")
                print(f"   3. 防火牆設定是否允許連接（端口 {DB_CONFIG['port']}）")
                print(f"   4. 資料庫服務是否正在運行")
                print(f"   5. MySQL用戶權限是否正確")
        
        except Exception as e:
            last_error = e
            print(f"❌ 資料庫連接發生未知錯誤: {e}")
            import traceback
            traceback.print_exc()
            break
    
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
                    # 檢查現有頭像是否為本地上傳的
                    cursor.execute(
                        "SELECT profile_picture FROM user WHERE id = %s",
                        (existing_user[0],)
                    )
                    current_picture = cursor.fetchone()
                    current_picture_path = current_picture[0] if current_picture and current_picture[0] else None
                    
                    # 如果已經有本地上傳的頭像（以 uploads/ 開頭），不要覆蓋
                    # 只有在沒有頭像或頭像是 Google URL 的情況下，才更新為新的 Google 頭像
                    if current_picture_path and current_picture_path.startswith('uploads/'):
                        # 保留本地上傳的頭像，只更新其他資訊
                        cursor.execute(
                            "UPDATE user SET google_id = %s, email = %s WHERE id = %s",
                            (google_id, email, existing_user[0])
                        )
                        print(f"更新現有用戶（保留本地上傳頭像）: {existing_user[1]}")
                    else:
                        # 沒有頭像或頭像是 Google URL，更新為新的 Google 頭像
                        cursor.execute(
                            "UPDATE user SET google_id = %s, profile_picture = %s, email = %s WHERE id = %s",
                            (google_id, picture, email, existing_user[0])
                        )
                        print(f"更新現有用戶（更新 Google 頭像）: {existing_user[1]}")
                    
                    user_id, username, role = existing_user
                else:
                    # 創建新用戶
                    username = name or email.split('@')[0]
                    
                    # 所有新註冊用戶都設為學生（老師身分由後端管理員手動設定）
                    role = '學生'
                    print(f"新用戶預設設為學生: {email}")
                    
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
                    
                    # 同步插入 student 表
                    try:
                        cursor.execute(
                            """INSERT INTO student (user_id, name, email) 
                               VALUES (%s, %s, %s)""",
                            (user_id, name, email)
                        )
                        print(f"✅ 已同步創建學生資料: user_id={user_id}, name={name}")
                    except Exception as student_error:
                        # 如果 student 表插入失敗，記錄錯誤但不影響註冊流程
                        print(f"⚠️  插入 student 表失敗（但用戶已創建）: {student_error}")
                
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
                    # 預設重定向到聊天系統登入頁面
                    redirect_url = f"http://localhost/Topics-frontend/frontend/chat/google_chat_integration.php?google_login=success&username={username}&role={role}"
                
                print(f"重定向到: {redirect_url}")
                return redirect(redirect_url)
                
        except Exception as e:
            import traceback
            error_details = traceback.format_exc()
            print(f"資料庫操作錯誤: {e}")
            print(f"詳細錯誤資訊:\n{error_details}")
            return jsonify({"error": f"用戶資料處理失敗: {str(e)}"}), 500
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
            # 先查詢使用者（不檢查密碼）
            sql = "SELECT username, role, status, password FROM user WHERE username=%s"
            cursor.execute(sql, (username,))
            user = cursor.fetchone()
            
            if not user:
                return jsonify({"message": "帳號或密碼錯誤"}), 401
            
            # 驗證密碼（支援雜湊密碼和明文密碼）
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
                    except Exception as e:
                        print(f"bcrypt 驗證錯誤: {e}")
                        password_valid = False
                else:
                    return jsonify({"message": "系統錯誤：bcrypt 模組未安裝"}), 500
            else:
                # 明文密碼比較（向後兼容）
                password_valid = (password == db_password)
            
            if not password_valid:
                return jsonify({"message": "帳號或密碼錯誤"}), 401
            
            # 檢查帳號是否被停用
            if user[2] == 0:  # status = 0 表示停用
                return jsonify({"message": "您的帳號已被停用，請聯繫管理員。"}), 403
            
            return jsonify({
                "message": "登入成功",
                "username": user[0],
                "role": user[1]
            }), 200
                
    except Exception as e:
        print(f"登入錯誤: {e}")
        return jsonify({"message": "登入失敗，請稍後再試。"}), 500
    finally:
        conn.close()

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

            # 插入新用戶（角色預設為學生）
            print(f"📝 開始註冊用戶: username={username}, email={email}, name={name}")
            cursor.execute(
                "INSERT INTO user (username, password, email, name, role) VALUES (%s, %s, %s, %s, '學生')",
                (username, password, email, name)
            )
            user_id = cursor.lastrowid
            print(f"✅ 已插入 user 表: user_id={user_id}")
            
            # 同步插入 student 表
            try:
                cursor.execute(
                    """INSERT INTO student (user_id, name, email) 
                       VALUES (%s, %s, %s)""",
                    (user_id, name, email)
                )
                print(f"✅ 已同步創建學生資料: user_id={user_id}, name={name}")
            except Exception as student_error:
                # 如果 student 表插入失敗，記錄錯誤但不影響註冊流程
                print(f"⚠️  插入 student 表失敗（但用戶已創建）: {student_error}")
            
            # 提交事務
            conn.commit()
            print(f"✅ 註冊成功並已提交: user_id={user_id}, username={username}")

            return jsonify({"message": "註冊成功"}), 200
            
    except pymysql.err.IntegrityError as e:
        # 可能的唯一鍵衝突（如 username/email）
        if conn:
            conn.rollback()
        print(f"❌ 註冊唯一鍵衝突: {e}")
        import traceback
        traceback.print_exc()
        error_msg = str(e).lower()
        if 'username' in error_msg or 'user.username' in error_msg:
            return jsonify({"message": "用戶名已被使用"}), 400
        elif 'email' in error_msg or 'user.email' in error_msg:
            return jsonify({"message": "電子郵件已被使用過"}), 400
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
        import traceback
        traceback.print_exc()
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
                return jsonify({
                    "department": profile[0],
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
            
            # 檢查是否已有個人資料
            sql_check = "SELECT COUNT(*) FROM teacher WHERE user_id = %s"
            cursor.execute(sql_check, (user_id,))
            exists = cursor.fetchone()[0] > 0
            
            if exists:
                # 更新現有資料
                sql_update = "UPDATE teacher SET name = %s, department = %s, phone = %s WHERE user_id = %s"
                cursor.execute(sql_update, (name, department, phone, user_id))
            else:
                # 新增資料
                sql_insert = "INSERT INTO teacher (user_id, name, department, phone) VALUES (%s, %s, %s, %s)"
                cursor.execute(sql_insert, (user_id, name, department, phone))
            
            conn.commit()
            return jsonify({"message": "個人資料保存成功"}), 200

    except pymysql.Error as e:
        conn.rollback()
        print(f"資料庫寫入錯誤：{e}")
        return jsonify({"message": "保存失敗，請稍後再試。原因：資料庫錯誤"}), 500
    except Exception as e:
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

if __name__ == '__main__':
    print("🚀 啟動康寧大學聊天系統後端...")
    print(f"Google Client ID: {GOOGLE_CLIENT_ID[:20]}...")
    print(f"重定向URI: {GOOGLE_REDIRECT_URI}")
    app.run(host='0.0.0.0', port=5000, debug=True)
