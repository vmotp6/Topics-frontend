<?php
session_start();
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API路徑測試</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .result {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            margin-top: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
        }
        .success {
            background: #d4edda;
            border-color: #c3e6cb;
        }
        .error {
            background: #f8d7da;
            border-color: #f5c6cb;
        }
        button {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <h1>🔧 API路徑測試</h1>
    
    <div class="test-section">
        <h2>測試API路徑</h2>
        <button onclick="testPath('backend/enrollment_api.php')">測試 backend/enrollment_api.php</button>
        <button onclick="testPath('../backend/enrollment_api.php')">測試 ../backend/enrollment_api.php</button>
        <button onclick="testPath('/backend/enrollment_api.php')">測試 /backend/enrollment_api.php</button>
        <button onclick="testPath('./backend/enrollment_api.php')">測試 ./backend/enrollment_api.php</button>
        <div id="pathResult" class="result"></div>
    </div>
    
    <div class="test-section">
        <h2>當前目錄資訊</h2>
        <div class="result">
當前目錄: <?php echo __DIR__; ?>
當前URL: <?php echo $_SERVER['REQUEST_URI']; ?>
Document Root: <?php echo $_SERVER['DOCUMENT_ROOT']; ?>
        </div>
    </div>
    
    <div class="test-section">
        <h2>檔案存在檢查</h2>
        <div class="result">
<?php
$paths = [
    'backend/enrollment_api.php',
    '../backend/enrollment_api.php',
    './backend/enrollment_api.php',
    __DIR__ . '/../backend/enrollment_api.php'
];

foreach ($paths as $path) {
    $exists = file_exists($path);
    echo "$path: " . ($exists ? "✅ 存在" : "❌ 不存在") . "\n";
}
?>
        </div>
    </div>
    
    <div class="test-section">
        <h2>簡單API測試</h2>
        <button onclick="testSimpleAPI()">測試簡單API調用</button>
        <div id="apiResult" class="result"></div>
    </div>
    
    <script>
        function testPath(path) {
            const resultDiv = document.getElementById('pathResult');
            resultDiv.textContent = `測試路徑: ${path}`;
            
            fetch(path, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'test=1'
            })
            .then(response => {
                resultDiv.textContent += `\n狀態: ${response.status} ${response.statusText}`;
                resultDiv.className = 'result ' + (response.ok ? 'success' : 'error');
                return response.text();
            })
            .then(text => {
                resultDiv.textContent += `\n回應內容:\n${text.substring(0, 500)}...`;
            })
            .catch(error => {
                resultDiv.textContent += `\n錯誤: ${error.message}`;
                resultDiv.className = 'result error';
            });
        }
        
        function testSimpleAPI() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.textContent = '測試中...';
            
            const formData = new FormData();
            formData.append('username', 'test_user');
            formData.append('name', '測試用戶');
            formData.append('identity', '學生');
            formData.append('phone1', '0912345678');
            
            // 嘗試不同的路徑
            const paths = [
                'backend/enrollment_api.php',
                '../backend/enrollment_api.php',
                './backend/enrollment_api.php'
            ];
            
            let currentPathIndex = 0;
            
            function tryNextPath() {
                if (currentPathIndex >= paths.length) {
                    resultDiv.textContent = '所有路徑都失敗了';
                    resultDiv.className = 'result error';
                    return;
                }
                
                const path = paths[currentPathIndex];
                resultDiv.textContent = `嘗試路徑: ${path}`;
                
                fetch(path, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    resultDiv.textContent += `\n狀態: ${response.status}`;
                    if (response.ok) {
                        return response.json();
                    } else {
                        throw new Error(`HTTP ${response.status}`);
                    }
                })
                .then(data => {
                    resultDiv.textContent += `\n成功! 回應: ${JSON.stringify(data, null, 2)}`;
                    resultDiv.className = 'result success';
                })
                .catch(error => {
                    resultDiv.textContent += `\n路徑 ${path} 失敗: ${error.message}`;
                    currentPathIndex++;
                    setTimeout(tryNextPath, 1000);
                });
            }
            
            tryNextPath();
        }
    </script>
</body>
</html>
