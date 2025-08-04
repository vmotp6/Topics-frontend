
from fastapi import FastAPI, Request, Form
from fastapi.responses import HTMLResponse
from fastapi.templating import Jinja2Templates
from fastapi.staticfiles import StaticFiles
import uvicorn

app = FastAPI()
templates = Jinja2Templates(directory="templates")

@app.get("/", response_class=HTMLResponse)
async def get_form(request: Request):
    return templates.TemplateResponse("index.html", {"request": request})

@app.post("/recommend", response_class=HTMLResponse)
async def recommend(request: Request, user_input: str = Form(...)):
    # 簡單 AI 推薦邏輯（可用 ChatGPT 接 API 來擴充）
    if "設計" in user_input:
        result = "建議與視覺傳達設計系合作"
    elif "程式" in user_input or "AI" in user_input:
        result = "建議與資訊工程系合作"
    elif "商業" in user_input or "行銷" in user_input:
        result = "建議與企業管理系合作"
    else:
        result = "建議與跨領域科系合作"

    return templates.TemplateResponse("index.html", {"request": request, "result": result})

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8000)
