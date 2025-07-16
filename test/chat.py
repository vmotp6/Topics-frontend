from fastapi import FastAPI, WebSocket, WebSocketDisconnect
from fastapi.responses import HTMLResponse
from fastapi.templating import Jinja2Templates
from starlette.requests import Request

from urllib.parse import parse_qs
import pymysql
import json
from datetime import datetime

app = FastAPI()
templates = Jinja2Templates(directory="templates")

# 你的 MySQL 設定（照 XAMPP 預設）
db_config = {
    "host": "localhost",
    "user": "root",
    "password": "",  # 如果你有密碼，請填上
    "database": "messeage",  # 注意拼字跟你資料庫相符
    "charset": "utf8mb4",
}

# 儲存每個聊天室房間的 WebSocket 連線
rooms = {}  # 例如：{ "fun": [ws1, ws2], "test": [ws3] }


# 儲存訊息進資料庫
def save_message(room, sender, text):
    conn = pymysql.connect(**db_config)
    cursor = conn.cursor()
    cursor.execute(
        "INSERT INTO chat (room, users, text, time) VALUES (%s, %s, %s, %s)",
        (room, sender, text, datetime.now().strftime("%Y-%m-%d %H:%M:%S"))
    )
    conn.commit()
    conn.close()


# 讀取歷史訊息（進房時使用）
def load_messages(room):
    conn = pymysql.connect(**db_config)
    cursor = conn.cursor()
    cursor.execute(
        "SELECT users, text, time FROM chat WHERE room=%s ORDER BY time ASC",
        (room,)
    )
    result = cursor.fetchall()
    conn.close()
    return result


@app.get("/", response_class=HTMLResponse)
async def get(request: Request):
    return templates.TemplateResponse("chat.html", {"request": request})


@app.websocket("/ws")
async def websocket_endpoint(websocket: WebSocket):
    await websocket.accept()
    query_params = parse_qs(websocket.url.query)
    room = query_params.get("room", ["default"])[0]

    # 將 WebSocket 加入對應房間
    if room not in rooms:
        rooms[room] = []
    rooms[room].append(websocket)

    # 傳送歷史訊息
    messages = load_messages(room)
    for sender, text, timestamp in messages:
        await websocket.send_text(json.dumps({
            "sender": sender,
            "text": text,
            "timestamp": timestamp.strftime("%Y-%m-%d %H:%M:%S")
        }))

    try:
        while True:
            data = await websocket.receive_text()
            parsed = json.loads(data)

            # 儲存訊息
            save_message(room, parsed["sender"], parsed["text"])

            # 廣播訊息給所有房間成員
            for client in rooms[room]:
                await client.send_text(json.dumps({
                    "sender": parsed["sender"],
                    "text": parsed["text"],
                    "timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                }))
    except WebSocketDisconnect:
        rooms[room].remove(websocket)
