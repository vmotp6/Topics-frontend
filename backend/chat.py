from flask import Flask, jsonify, request
from flask_socketio import SocketIO, emit
from flask_cors import CORS
import pymysql

app = Flask(__name__)
app.config['SECRET_KEY'] = 'secret!'
CORS(app)
socketio = SocketIO(app, cors_allowed_origins="*")

# ✅ 資料庫設定
db_config = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "topics_good",
    "charset": "utf8mb4"
}

# ✅ 寫入訊息
def insert_message(sender, receiver, role, message):
    try:
        conn = pymysql.connect(**db_config)
        cursor = conn.cursor()
        sql = "INSERT INTO chat (sender, receiver, role, message, time) VALUES (%s, %s, %s, %s, NOW())"
        cursor.execute(sql, (sender, receiver, role, message))
        conn.commit()
        cursor.close()
        conn.close()
        print("訊息寫入成功")
    except Exception as e:
        print("寫入資料庫失敗:", e)

# ✅ 抓取歷史紀錄
def fetch_history():
    conn = pymysql.connect(**db_config)
    cursor = conn.cursor(pymysql.cursors.DictCursor)
    cursor.execute("SELECT sender, receiver, role, message FROM chat ORDER BY id ASC")
    rows = cursor.fetchall()
    cursor.close()
    conn.close()
    return rows

# ✅ 提供歷史紀錄 API
@app.route('/chat_history')
def chat_history():
    return jsonify(fetch_history())

# ✅ 處理即時訊息
@socketio.on('chat_message')
def handle_chat_message(data):
    print("收到訊息：", data)
    sender = data.get('username')
    receiver = data.get('receiver', '全體')
    role = data.get('role')
    message = data.get('message')
    insert_message(sender, receiver, role, message)
    emit('chat_message', data, broadcast=True)

# ✅ 啟動 SocketIO
if __name__ == '__main__':
    socketio.run(app, port=5003, debug=True)
