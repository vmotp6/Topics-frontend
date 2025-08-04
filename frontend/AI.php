<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <?php include("share/header.php"); ?>
    <link rel="stylesheet" href="assets/csp/AI.css">
</head>
<body>
    <h1>AI 科系推薦系統（廠商端）</h1>
    <p>請填寫您專案的基本資訊，系統將推薦最適合承接此案的科系。</p>

    <form id="aiForm">
        <label for="project_desc">📌 專案內容描述：</label><br>
        <textarea name="project_desc" rows="4" required></textarea><br><br>

        <label for="required_skills">🧰 專案需要的技能（可列多項）：</label><br>
        <input type="text" name="required_skills" placeholder="例如：Python, 設計, 機器學習" required><br><br>

        <label for="goal">🎯 專案最終目標：</label><br>
        <input type="text" name="goal" placeholder="例如：開發一個網站、分析資料、設計產品" required><br><br>

        <button type="submit">送出</button>
    </form>

    <h2 id="result"></h2>

    <script>
        document.getElementById("aiForm").addEventListener("submit", function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            const data = {
                project_desc: formData.get("project_desc"),
                required_skills: formData.get("required_skills"),
                goal: formData.get("goal")
            };

            // 呼叫後端 recommend_api.py
            fetch("http://localhost:5001/recommend", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                document.getElementById("result").innerText = "✅ 建議媒合科系：" + result.recommendation;
            })
            .catch(error => {
                document.getElementById("result").innerText = "❌ 發生錯誤：" + error;
            });
        });
    </script>
</body>
</html>
