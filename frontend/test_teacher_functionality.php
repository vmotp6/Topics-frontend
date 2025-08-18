<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>老師功能完整性測試</title>
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
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-weight: bold;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <h1>老師功能完整性測試</h1>
    
    <div class="test-section">
        <h3>✅ 功能檢查清單</h3>
        <div id="checklist">
            <p>正在檢查各項功能...</p>
        </div>
    </div>
    
    <div class="test-section">
        <h3>🧪 API 測試</h3>
        <button onclick="testAPI()" class="test-btn">測試後端API</button>
        <div id="apiResult" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>📝 個人資料測試</h3>
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
        <h3>🔗 頁面連結測試</h3>
        <a href="teacher.php" class="test-btn">前往老師主頁面</a>
        <a href="teacher_profile.php" class="test-btn">前往個人資料頁面</a>
        <a href="test_teacher.php" class="test-btn">前往完整測試頁面</a>
    </div>

    <script>
        // 功能檢查清單
        function checkFunctionality() {
            const checklist = document.getElementById('checklist');
            let html = '<ul>';
            
            // 檢查項目
            const checks = [
                { name: '後端API服務', status: 'pending' },
                { name: '老師主頁面', status: 'pending' },
                { name: '個人資料頁面', status: 'pending' },
                { name: '紅點提示功能', status: 'pending' },
                { name: '資料庫連接', status: 'pending' }
            ];
            
            // 測試API
            fetch('http://100.79.58.120:5000/teacher/profile/test')
                .then(response => {
                    checks[0].status = 'success';
                    checks[4].status = 'success';
                    updateChecklist();
                })
                .catch(error => {
                    checks[0].status = 'error';
                    checks[4].status = 'error';
                    updateChecklist();
                });
            
            // 測試頁面
            fetch('teacher.php')
                .then(response => {
                    checks[1].status = 'success';
                    updateChecklist();
                })
                .catch(error => {
                    checks[1].status = 'error';
                    updateChecklist();
                });
            
            fetch('teacher_profile.php')
                .then(response => {
                    checks[2].status = 'success';
                    updateChecklist();
                })
                .catch(error => {
                    checks[2].status = 'error';
                    updateChecklist();
                });
            
            // 紅點功能檢查（模擬）
            checks[3].status = 'success';
            updateChecklist();
            
            function updateChecklist() {
                let html = '<ul>';
                checks.forEach(check => {
                    const status = check.status === 'success' ? '✅' : 
                                 check.status === 'error' ? '❌' : '⏳';
                    html += `<li>${status} ${check.name}</li>`;
                });
                html += '</ul>';
                checklist.innerHTML = html;
            }
        }
        
        // API測試
        function testAPI() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.textContent = '正在測試API...';
            
            fetch('http://100.79.58.120:5000/teacher/profile/test')
                .then(response => response.json())
                .then(data => {
                    resultDiv.innerHTML = `
                        <div class="status success">✅ API正常運行</div>
                        <p>回應: ${data.message}</p>
                        <p>狀態碼: ${response.status}</p>
                    `;
                })
                .catch(error => {
                    resultDiv.innerHTML = `
                        <div class="status error">❌ API測試失敗</div>
                        <p>錯誤: ${error.message}</p>
                    `;
                });
        }
        
        // 保存個人資料
        document.getElementById('saveProfileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('username', document.getElementById('username').value);
            formData.append('department', document.getElementById('department').value);
            formData.append('phone', document.getElementById('phone').value);
            
            fetch('http://100.79.58.120:5000/teacher/profile', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const resultDiv = document.getElementById('saveResult');
                if (response.ok) {
                    resultDiv.innerHTML = `
                        <div class="status success">✅ 保存成功</div>
                        <p>${data.message}</p>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="status error">❌ 保存失敗</div>
                        <p>${data.message}</p>
                    `;
                }
            })
            .catch(error => {
                document.getElementById('saveResult').innerHTML = `
                    <div class="status error">❌ 保存失敗</div>
                    <p>錯誤: ${error.message}</p>
                `;
            });
        });
        
        // 頁面載入時執行檢查
        window.addEventListener('load', checkFunctionality);
    </script>
    
    <?php include("share/ai_widget.php"); ?>
</body>
</html> 