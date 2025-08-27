<?php
session_start();
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>簡化測試頁面</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
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
            width: 100%;
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
        .debug {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            margin-top: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <h1>🔧 簡化測試頁面</h1>
    
    <div class="test-form">
        <h2>測試就讀意願登錄 (無reCAPTCHA)</h2>
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
                <input type="tel" id="phone1" name="phone1" value="0912345678" maxlength="10" required>
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
        <div id="resultContent"></div>
        <div id="debugInfo" class="debug" style="display: none;"></div>
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
            
            // 顯示要提交的資料
            let debugInfo = '準備提交的資料:\n';
            for (let [key, value] of formData.entries()) {
                debugInfo += key + ': ' + value + '\n';
            }
            
            const resultDiv = document.getElementById('result');
            const resultContent = document.getElementById('resultContent');
            const debugDiv = document.getElementById('debugInfo');
            
            resultDiv.style.display = 'block';
            resultContent.textContent = '提交中...';
            debugDiv.textContent = debugInfo;
            debugDiv.style.display = 'block';
            
            fetch('../backend/test_enrollment_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                debugInfo += '\nAPI回應狀態: ' + response.status;
                debugDiv.textContent = debugInfo;
                return response.json();
            })
            .then(data => {
                debugInfo += '\nAPI回應資料: ' + JSON.stringify(data, null, 2);
                debugDiv.textContent = debugInfo;
                
                resultDiv.className = 'result ' + (data.success ? 'success' : 'error');
                resultContent.textContent = data.success ? 
                    '✅ 提交成功: ' + data.message : 
                    '❌ 提交失敗: ' + data.message;
            })
            .catch(error => {
                debugInfo += '\n錯誤: ' + error.message;
                debugDiv.textContent = debugInfo;
                
                resultDiv.className = 'result error';
                resultContent.textContent = '❌ 提交失敗: ' + error.message;
            });
        });
    </script>
</body>
</html>
