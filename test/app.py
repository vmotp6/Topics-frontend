from flask import Flask, request, redirect, jsonify
import pymysql

app = Flask(__name__)

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
    # 從表單抓資料
    username = request.form.get('username')
    name = request.form.get('name')
    email = request.form.get('email')
    role = request.form.get('role')
    password = request.form.get('password')
    confirm_password = request.form.get('confirm_password')

    # 檢查密碼是否一致
    if password != confirm_password:
        return "密碼與確認密碼不一致，請重新輸入。"

    # 寫入資料庫
    sql = "INSERT INTO user (`帳號`, `姓名`, `電子郵件`, `身分`, `密碼`) VALUES (%s, %s, %s, %s, %s)"
    try:
        cursor.execute(sql, (username, name, email, role, password))
        db.commit()
        return "成功輸入"
    except Exception as e:
        db.rollback()
        return f"資料寫入錯誤：{e}"

if __name__ == '__main__':
    app.run(debug=True)

