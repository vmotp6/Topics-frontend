from flask import Flask, request, redirect, jsonify
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
cursor = db.cursor()

@app.route('/sign', methods=['POST'])
def register():
    username = request.form.get('username')
    name = request.form.get('name')
    email = request.form.get('email')
    role = request.form.get('role')
    password = request.form.get('password')
    confirm_password = request.form.get('confirm_password')

    if password != confirm_password:
        return "密碼與確認密碼不一致，請重新輸入。"

    sql = "INSERT INTO user (`帳號`, `姓名`, `電子郵件`, `身分`, `密碼`) VALUES (%s, %s, %s, %s, %s)"
    try:
        cursor.execute(sql, (username, name, email, role, password))
        db.commit()
        return "成功註冊"
    except Exception as e:
        db.rollback()
        return f"資料寫入錯誤：{e}"

@app.route('/login', methods=['POST'])
def login():
    username = request.form.get('username')
    password = request.form.get('password')

    sql = "SELECT * FROM user WHERE 帳號=%s AND 密碼=%s"
    cursor.execute(sql, (username, password))
    user = cursor.fetchone()

    if user:
        return "登入成功"
    else:
        return "帳號或密碼錯誤"

# ✅ 最後再啟動 Flask
if __name__ == '__main__':
    app.run(debug=True)
