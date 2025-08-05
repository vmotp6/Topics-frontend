from flask import Flask, request, jsonify
from flask_cors import CORS
import re
import json

app = Flask(__name__)
CORS(app)

with open('departments_keywords.json', 'r', encoding='utf-8') as f:
    DEPARTMENT_KEYWORDS = json.load(f)

def count_keywords(text, keywords):
    count = 0
    for kw in keywords:
        if re.search(r'\b' + re.escape(kw) + r'\b', text):
            count += 1
    return count

@app.route('/recommend', methods=['POST'])
def recommend_department():
    data = request.get_json()

    # 取出欄位，預設空字串
    desc = data.get('project_desc', '').strip().lower()
    skills = data.get('required_skills', '').strip().lower()
    goal = data.get('goal', '').strip().lower()

    # 必填欄位簡單驗證
    if not desc and not skills and not goal:
        return jsonify({
            "status": "error",
            "message": "請至少填寫專案描述、所需技能或目標其中一項。"
        }), 400

    # 過短內容警告
    total_len = len(desc) + len(skills) + len(goal)
    if total_len < 10:
        return jsonify({
            "status": "error",
            "message": "輸入內容太短，請提供更詳細的資訊以利判斷。"
        }), 400

    combined_text = " ".join([desc, skills, goal])

    scores = {}
    for dept, keywords in DEPARTMENT_KEYWORDS.items():
        scores[dept] = count_keywords(combined_text, keywords)

    max_score = max(scores.values()) if scores else 0

    if max_score == 0:
        return jsonify({
            "status": "fail",
            "message": "無法根據輸入判斷適合科系，建議調整描述或技能詞彙。",
            "recommendation": "跨領域專案團隊（建議與校內媒合平台聯繫）"
        })

    recommendations = [dept for dept, score in scores.items() if score == max_score]

    return jsonify({
        "status": "success",
        "message": "推薦成功",
        "recommendation": ", ".join(recommendations)
    })

if __name__ == '__main__':
    app.run(port=5001, debug=True)
