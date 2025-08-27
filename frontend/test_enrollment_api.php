<?php
session_start();
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API測試頁面</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .test-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
        }
        .result {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <h1>🔧 API測試頁面</h1>
    
    <div class="test-form">
        <h2>測試就讀意願登錄API</h2>
        <form id="testForm">
            <div class="form-group">
                <label for="name">姓名:</label>
                <input type="text" id="name" name="name" value="測試用戶" required>
            </div>
            
            <div class="form-group">
                <label for="identity">身分別:</label>
                <select id="identity" name="identity" required>
                    <option value="學生">學生</option>
                    <option value="家長">家長</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="phone1">聯絡電話:</label>
                <input type="tel" id="phone1" name="phone1" value="0912345678" required>
            </div>
            
            <div class="form-group">
                <label for="intention1">就讀意願:</label>
                <select id="intention1" name="intention1">
                    <option value="資訊工程學系">資訊工程學系</option>
                    <option value="企業管理學系">企業管理學系</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="system1">學制:</label>
                <select id="system1" name="system1">
                    <option value="五專">五專</option>
                    <option value="大學部">大學部</option>
                    <option value="碩士班">碩士班</option>
                </select>
            </div>
            
            <button type="submit">測試提交</button>
        </form>
    </div>
    
    <div id="result" class="result" style="display: none;">
        <h3>測試結果:</h3>
        <pre id="resultContent"></pre>
    </div>
    
    <div class="test-form">
        <h2>當前Session資訊</h2>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>
    
    <script>
        document.getElementById('testForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('username', '<?php echo htmlspecialchars($_SESSION['username'] ?? 'test_user'); ?>');
            
            fetch('../backend/enrollment_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const resultDiv = document.getElementById('result');
                const resultContent = document.getElementById('resultContent');
                
                resultDiv.style.display = 'block';
                resultDiv.className = 'result ' + (data.success ? 'success' : 'error');
                resultContent.textContent = JSON.stringify(data, null, 2);
            })
            .catch(error => {
                const resultDiv = document.getElementById('result');
                const resultContent = document.getElementById('resultContent');
                
                resultDiv.style.display = 'block';
                resultDiv.className = 'result error';
                resultContent.textContent = '錯誤: ' + error.message;
            });
        });
    </script>
</body>
</html>
