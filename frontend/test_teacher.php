<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>測試老師功能</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .test-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .test-btn:hover {
            background: #0056b3;
        }
        .result {
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <h1>老師功能測試頁面</h1>
    
    <div class="test-section">
        <h3>測試保存老師個人資料</h3>
        <form id="saveProfileForm">
            <label>使用者名稱: <input type="text" id="username" value="test_teacher" required></label><br><br>
            <label>科系: 
                <select id="department" required>
                    <option value="">請選擇科系</option>
                    <option value="資訊工程學系">資訊工程學系</option>
                    <option value="電機工程學系">電機工程學系</option>
                    <option value="機械工程學系">機械工程學系</option>
                </select>
            </label><br><br>
            <label>電話: <input type="tel" id="phone" value="0912345678" required></label><br><br>
            <button type="submit" class="test-btn">保存個人資料</button>
        </form>
        <div id="saveResult" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>測試獲取老師個人資料</h3>
        <input type="text" id="getUsername" value="test_teacher" placeholder="輸入使用者名稱">
        <button onclick="getProfile()" class="test-btn">獲取個人資料</button>
        <div id="getResult" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>測試紅點提示功能</h3>
        <p>這個功能會在老師頁面自動檢查是否已填寫個人資料，並在頭像上顯示紅點提示。</p>
        <a href="teacher.php" class="test-btn">前往老師頁面</a>
    </div>

    <script>
        // 保存個人資料
        document.getElementById('saveProfileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('username', document.getElementById('username').value);
            formData.append('department', document.getElementById('department').value);
            formData.append('phone', document.getElementById('phone').value);
            
            fetch('http://localhost:5000/teacher/profile', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const resultDiv = document.getElementById('saveResult');
                if (response.ok) {
                    resultDiv.style.color = 'green';
                    resultDiv.textContent = '成功: ' + data.message;
                } else {
                    resultDiv.style.color = 'red';
                    resultDiv.textContent = '錯誤: ' + data.message;
                }
            })
            .catch(error => {
                document.getElementById('saveResult').textContent = '錯誤: ' + error.message;
            });
        });
        
        // 獲取個人資料
        function getProfile() {
            const username = document.getElementById('getUsername').value;
            
            fetch(`http://localhost:5000/teacher/profile/${username}`)
            .then(response => response.json())
            .then(data => {
                const resultDiv = document.getElementById('getResult');
                if (response.ok) {
                    resultDiv.style.color = 'green';
                    resultDiv.textContent = `成功: 科系=${data.department}, 電話=${data.phone}`;
                } else {
                    resultDiv.style.color = 'red';
                    resultDiv.textContent = '錯誤: ' + data.message;
                }
            })
            .catch(error => {
                document.getElementById('getResult').textContent = '錯誤: ' + error.message;
            });
        }
    </script>
</body>
</html> 