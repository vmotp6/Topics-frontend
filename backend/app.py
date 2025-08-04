from flask import Flask, request, jsonify
from flask_cors import CORS
import pymysql

app = Flask(__name__)
CORS(app)

# 連接資料庫
db = pymysql.connect(
    host="localhost",
    user="root",
    password="",
    database="topics_good",
    charset="utf8mb4"
)

# ✅ 註冊功能
@app.route('/sign', methods=['POST'])
def register():
    username = request.form.get('username')
    name = request.form.get('name')
    email = request.form.get('email')
    role = request.form.get('role')
    password = request.form.get('password')
    confirm_password = request.form.get('confirm_password')

    if password != confirm_password:
        return jsonify({"message": "密碼與確認密碼不一致，請重新輸入。"}), 400

    try:
        with db.cursor() as cursor:
            # 檢查帳號是否已存在
            sql_check_username = "SELECT COUNT(*) FROM user WHERE username = %s"
            cursor.execute(sql_check_username, (username,))
            if cursor.fetchone()[0] > 0:
                return jsonify({"message": "此帳號已被註冊，請使用其他帳號。"}), 409

            # 檢查電子郵件是否已存在
            sql_check_email = "SELECT COUNT(*) FROM user WHERE email = %s"
            cursor.execute(sql_check_email, (email,))
            if cursor.fetchone()[0] > 0:
                return jsonify({"message": "此電子郵件已被註冊，請使用其他電子郵件。"}), 409

            # 新增使用者資料
            sql_insert_user = "INSERT INTO user (`username`, `name`, `email`, `role`, `password`) VALUES (%s, %s, %s, %s, %s)"
            cursor.execute(sql_insert_user, (username, name, email, role, password))
            db.commit()
            return jsonify({"message": "成功註冊"}), 201

    except pymysql.Error as e:
        db.rollback()
        print(f"資料庫寫入錯誤：{e}")
        return jsonify({"message": "註冊失敗，請稍後再試。原因：資料庫錯誤"}), 500
    except Exception as e:
        db.rollback()
        print(f"未知錯誤：{e}")
        return jsonify({"message": "註冊失敗，發生未知錯誤。"}), 500

# ✅ 登入功能（含回傳身分）
@app.route('/login', methods=['POST'])
def login():
    username = request.form.get('username')
    password = request.form.get('password')

    try:
        with db.cursor() as cursor:
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

    except pymysql.Error as e:
        print(f"資料庫查詢錯誤：{e}")
        return jsonify({"message": "登入失敗，請稍後再試。"}), 500
    except Exception as e:
        print(f"未知錯誤：{e}")
        return jsonify({"message": "登入失敗，發生未知錯誤。"}), 500

# 啟動伺服器
if __name__ == '__main__':
    app.run(debug=True)
