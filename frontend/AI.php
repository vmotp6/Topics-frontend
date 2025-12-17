<?php
// 載入 session 配置
require_once 'session_config.php';
?>
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
    <div class="pp"><p>請填寫您專案的基本資訊，系統將推薦最適合承接此案的科系。</p></div>
<div class="form-card">
    <form id="aiForm">
        <label for="project_desc">📌 專案內容描述：</label><br>
        <textarea name="project_desc" rows="4" required></textarea><br><br>

        <label for="required_skills">🧰 專案需要的技能（可列多項）：</label><br>
        <input type="text" name="required_skills" placeholder="例如：Python, 設計, 機器學習" required><br><br>

        <label for="goal">🎯 專案最終目標：</label><br>
        <input type="text" name="goal" placeholder="例如：開發一個網站、分析資料、設計產品" required><br><br>

        <button type="submit" id="submitBtn">送出</button>
    </form>
</div>
    <h2 id="result"></h2>

    <script>
    document.getElementById('aiForm').addEventListener('submit', function(e) {
        e.preventDefault(); // 防止表單預設送出行為

        const desc = document.querySelector('[name="project_desc"]').value;
        const skills = document.querySelector('[name="required_skills"]').value;
        const goal = document.querySelector('[name="goal"]').value;

        // 禁用按鈕避免重複送出
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        document.getElementById('result').innerText = "🔄 分析中，請稍候...";

        fetch('http://localhost:5001/recommend', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                project_desc: desc,
                required_skills: skills,
                goal: goal
            })
        })
        .then(res => res.json())
        .then(data => {
            const resultText = data.recommendation || "尚無建議科系，請嘗試更詳細的描述。";
            document.getElementById('result').innerText = `✅ 建議媒合科系：${resultText}`;
        })
        .catch(err => {
            console.error('發生錯誤：', err);
            document.getElementById('result').innerText = "⚠️ 發生錯誤，請稍後再試。";
        })
        .finally(() => {
            submitBtn.disabled = false;
        });
    }); 
    </script>

	<!-- 引入通用聊天室組件 -->
	<?php include("share/chat_widget.php"); ?>

	<?php include("share/footer.php"); ?>
</body>
</html>