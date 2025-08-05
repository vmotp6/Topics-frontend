<<<<<<< HEAD
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <?php include("share/header.php"); ?>
    <link rel="stylesheet" href="assets/csp/AI.css">
    <style>
        #result {
            margin-top: 1em;
            white-space: pre-wrap;
            font-weight: bold;
        }
        button[disabled] {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <h1>科系推薦系統</h1>
    <p>請填寫您專案的基本資訊，系統將推薦最適合承接此案的科系。</p>

    <form id="aiForm">
        <label for="project_desc">📌 專案內容描述：</label><br>
        <textarea name="project_desc" rows="4" required></textarea><br><br>

        <label for="required_skills">🧰 專案需要的技能（可列多項）：</label><br>
        <input type="text" name="required_skills" placeholder="例如：Python, 設計, 機器學習" required><br><br>

        <label for="goal">🎯 專案最終目標：</label><br>
        <input type="text" name="goal" placeholder="例如：開發一個網站、分析資料、設計產品" required><br><br>

        <button type="submit" id="submitBtn">送出</button>
    </form>

    <h2 id="result"></h2>

    <script>
        const form = document.getElementById("aiForm");
        const resultEl = document.getElementById("result");
        const submitBtn = document.getElementById("submitBtn");

        form.addEventListener("submit", async function(e) {
            e.preventDefault();

            // 先清空結果
            resultEl.innerText = "";

            // 取得並 trim 資料
            const project_desc = form.project_desc.value.trim();
            const required_skills = form.required_skills.value.trim();
            const goal = form.goal.value.trim();

            // 客戶端簡單防呆：判斷是否填寫至少一欄
            if (!project_desc && !required_skills && !goal) {
                resultEl.innerText = "⚠️ 請至少填寫專案內容、所需技能或目標其中一項。";
                return;
            }

            // 判斷總長度是否過短
            if ((project_desc + required_skills + goal).length < 10) {
                resultEl.innerText = "⚠️ 內容過於簡短，請提供更詳細的資訊。";
                return;
            }

            // 送出時鎖定按鈕
            submitBtn.disabled = true;
            submitBtn.innerText = "處理中...";

            try {
                const response = await fetch("http://localhost:5001/recommend", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        project_desc,
                        required_skills,
                        goal
                    })
                });

                if (!response.ok) {
                    // HTTP 非 200，解析錯誤訊息
                    const errData = await response.json().catch(() => ({}));
                    throw new Error(errData.message || "伺服器錯誤");
                }

                const result = await response.json();

                if (result.status === "success") {
                    const rec = result.recommendation?.trim();
                    resultEl.innerText = `✅ 建議媒合科系：${rec || "無法判斷適合科系，請提供更多資訊。"}`;
                } else if (result.status === "fail" || result.status === "error") {
                    resultEl.innerText = `⚠️ ${result.message || "無法取得推薦結果，請稍後再試。"}`;
                } else {
                    resultEl.innerText = "⚠️ 收到未知的回應狀態。";
                }
            } catch (error) {
                resultEl.innerText = `❌ 發生錯誤：${error.message}`;
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerText = "送出";
            }
        });
    </script>
</body>
</html>
=======
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
>>>>>>> 967f62bb757142e87d0280f32f1cec66e4d30f42
