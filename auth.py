from fastapi import FastAPI, Request, Form
from fastapi.responses import HTMLResponse, RedirectResponse
from fastapi.templating import Jinja2Templates
from starlette.middleware.sessions import SessionMiddleware
import pymysql

app = FastAPI()
app.add_middleware(SessionMiddleware, secret_key="your-secret-key")
templates = Jinja2Templates(directory="templates")

# 設定 MySQL 連線
def get_db():
    return pymysql.connect(
        host="localhost",
        user="root",          # 你的 MySQL 帳號
        password="",          # 你的 MySQL 密碼
        database="messeage",  # 你的資料庫名稱
        cursorclass=pymysql.cursors.DictCursor
    )

@app.get("/", response_class=HTMLResponse)
async def login_page(request: Request):
    return templates.TemplateResponse("login.html", {"request": request})

@app.post("/login")
async def login(request: Request, username: str = Form(...), password: str = Form(...)):
    conn = get_db()
    with conn:
        with conn.cursor() as cursor:
            sql = "SELECT * FROM users WHERE username=%s AND password=%s"
            cursor.execute(sql, (username, password))
            user = cursor.fetchone()
            if user:
                request.session["user"] = username
                return RedirectResponse(url="/chat", status_code=302)
    return templates.TemplateResponse("login.html", {"request": request, "error": "登入失敗"})

@app.get("/register", response_class=HTMLResponse)
async def register_page(request: Request):
    return templates.TemplateResponse("register.html", {"request": request})

@app.post("/register")
async def register(username: str = Form(...), password: str = Form(...)):
    conn = get_db()
    with conn:
        with conn.cursor() as cursor:
            cursor.execute("SELECT * FROM users WHERE username=%s", (username,))
            if cursor.fetchone():
                return HTMLResponse("帳號已存在", status_code=400)
            sql = "INSERT INTO users (username, password) VALUES (%s, %s)"
            cursor.execute(sql, (username, password))
            conn.commit()
    return RedirectResponse(url="/", status_code=302)

@app.get("/chat", response_class=HTMLResponse)
async def chat_page(request: Request):
    if "user" not in request.session:
        return RedirectResponse(url="/")
    return templates.TemplateResponse("chat.html", {"request": request, "user": request.session["user"]})
