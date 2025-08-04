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

# 注意：這裡不再需要全域的 cursor，因為我們會使用 with db.cursor() as cursor:
# 這能確保每次操作後 cursor 都會被正確關閉，並處理連接池問題

@app.route('/sign', methods=['POST'])
def register():
    username = request.form.get('username')
    name = request.form.get('name')
    email = request.form.get('email')
    role = request.form.get('role')
    password = request.form.get('password')
    confirm_password = request.form.get('confirm_password')

    if password != confirm_password:
        # 返回 JSON 響應和 HTTP 狀態碼，方便前端處理
        return jsonify({"message": "密碼與確認密碼不一致，請重新輸入。"}), 400 # Bad Request

    try:
        # 使用 with 語句確保 cursor 在操作結束後被正確關閉
        with db.cursor() as cursor:
            # 1. 檢查帳號是否已存在
            sql_check_username = "SELECT COUNT(*) FROM user WHERE username = %s"
            cursor.execute(sql_check_username, (username,))
            if cursor.fetchone()[0] > 0:
                return jsonify({"message": "此帳號已被註冊，請使用其他帳號。"}), 409 # Conflict

            # 2. 檢查電子郵件是否已存在
            sql_check_email = "SELECT COUNT(*) FROM user WHERE email = %s"
            cursor.execute(sql_check_email, (email,))
            if cursor.fetchone()[0] > 0:
                return jsonify({"message": "此電子郵件已被註冊，請使用其他電子郵件。"}), 409 # Conflict

            # 如果帳號和電子郵件都未重複，則執行插入操作
            sql_insert_user = "INSERT INTO user (`username`, `name`, `email`, `role`, `password`) VALUES (%s, %s, %s, %s, %s)"
            cursor.execute(sql_insert_user, (username, name, email, role, password))
            db.commit() # 提交事務以保存更改
            return jsonify({"message": "成功註冊"}), 201 # Created

    except pymysql.Error as e:
        db.rollback() # 如果發生錯誤，回滾事務
        print(f"資料庫寫入錯誤：{e}") # 在伺服器端日誌中記錄實際錯誤
        return jsonify({"message": "註冊失敗，請稍後再試。原因：資料庫錯誤"}), 500 # Internal Server Error
    except Exception as e:
        db.rollback()
        print(f"未知錯誤：{e}")
        return jsonify({"message": "註冊失敗，發生未知錯誤。"}), 500

@app.route('/login', methods=['POST'])
def login():
    username = request.form.get('username')
    password = request.form.get('password')

    try:
        with db.cursor() as cursor:
            sql = "SELECT * FROM user WHERE username=%s AND password=%s"
            cursor.execute(sql, (username, password))
            user = cursor.fetchone()

        if user:
            # 您可能需要在這裡啟動會話或發送令牌等
            return jsonify({"message": "登入成功", "username": username}), 200
        else:
            return jsonify({"message": "帳號或密碼錯誤"}), 401 # Unauthorized
    except pymysql.Error as e:
        print(f"資料庫查詢錯誤：{e}")
        return jsonify({"message": "登入失敗，請稍後再試。"}), 500
    except Exception as e:
        print(f"未知錯誤：{e}")
        return jsonify({"message": "登入失敗，發生未知錯誤。"}), 500

if __name__ == '__main__':
    app.run(debug=True)