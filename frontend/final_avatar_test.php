<?php
// 載入 session 配置
require_once 'session_config.php';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>最終頭像測試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-container { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 10px; 
            margin: 20px 0; 
            border: 2px solid #dee2e6;
        }
        .avatar { 
            width: 50px; 
            height: 50px; 
            border-radius: 50%; 
            border: 3px solid #007bff; 
            margin: 10px;
            object-fit: cover;
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        .status { 
            padding: 10px; 
            border-radius: 5px; 
            margin: 10px 0; 
        }
        .status.success { background: #d4edda; color: #155724; }
        .status.error { background: #f8d7da; color: #721c24; }
        .status.info { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <h1>🎯 最終頭像測試</h1>
    
    <div class="test-container">
        <h3>📊 測試結果</h3>
        <div id="testResults"></div>
    </div>
    
    <div class="test-container">
        <h3>🖼️ 頭像顯示測試</h3>
        <p>測試修正後的頭像路徑：</p>
        
        <div style="display: flex; align-items: center; gap: 20px; margin: 20px 0;">
            <div>
                <h4>預設頭像</h4>
                <img src="./share/EIdROxGXsAE_LSs.jpg" 
                     alt="預設頭像" 
                     class="avatar" 
                     id="defaultAvatar"
                     onload="updateTestResult('defaultAvatar', true)"
                     onerror="updateTestResult('defaultAvatar', false)">
            </div>
            
            <div>
                <h4>頭像2</h4>
                <img src="./share/EMdrrheUEAAGkC4.jpg" 
                     alt="頭像2" 
                     class="avatar" 
                     id="avatar2"
                     onload="updateTestResult('avatar2', true)"
                     onerror="updateTestResult('avatar2', false)">
            </div>
            
            <div>
                <h4>頭像3</h4>
                <img src="./share/ESmOf3yU8AA12sp.jpg" 
                     alt="頭像3" 
                     class="avatar" 
                     id="avatar3"
                     onload="updateTestResult('avatar3', true)"
                     onerror="updateTestResult('avatar3', false)">
            </div>
        </div>
    </div>
    
    <div class="test-container">
        <h3>🧭 導航列模擬</h3>
        <p>模擬導航列中的頭像顯示：</p>
        <div style="background: rgba(217, 229, 234, 0.95); padding: 15px; border-radius: 8px; display: flex; align-items: center; justify-content: space-between;">
            <div style="font-weight: bold; color: #2c3e50;">康寧大學招生平台</div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="color: #2c3e50;">歡迎，<?php echo $_SESSION['username'] ?? '用戶'; ?></span>
                <img src="./share/EIdROxGXsAE_LSs.jpg" 
                     alt="用戶頭像" 
                     class="avatar" 
                     id="navAvatar"
                     onload="updateTestResult('navAvatar', true)"
                     onerror="updateTestResult('navAvatar', false)">
            </div>
        </div>
    </div>
    
    <div class="test-container">
        <h3>📋 測試說明</h3>
        <ul>
            <li>✅ <strong>綠色邊框</strong>：頭像載入成功</li>
            <li>❌ <strong>紅色邊框</strong>：頭像載入失敗</li>
            <li>🔍 請檢查瀏覽器控制台查看詳細錯誤訊息</li>
            <li>📱 如果所有頭像都無法顯示，可能是伺服器配置問題</li>
        </ul>
    </div>
    
    <div class="test-container">
        <h3>🔧 修正內容</h3>
        <p>已將頭像路徑從絕對路徑改為相對路徑：</p>
        <pre style="background: #e9ecef; padding: 10px; border-radius: 5px; overflow-x: auto;">
// 修正前
$avatar_src = '/Topics-frontend/frontend/share/EIdROxGXsAE_LSs.jpg';

// 修正後  
$avatar_src = './share/EIdROxGXsAE_LSs.jpg';
        </pre>
    </div>
    
    <div style="margin-top: 30px; text-align: center;">
        <a href="index.php" style="color: #007bff; text-decoration: none; margin: 0 10px;">🏠 返回首頁</a>
        <a href="simple_logout_test.php" style="color: #007bff; text-decoration: none; margin: 0 10px;">🚪 測試登出</a>
    </div>

    <script>
        let testResults = {};
        
        function updateTestResult(avatarId, success) {
            testResults[avatarId] = success;
            updateResultsDisplay();
        }
        
        function updateResultsDisplay() {
            const resultsDiv = document.getElementById('testResults');
            let html = '';
            
            for (const [id, success] of Object.entries(testResults)) {
                const status = success ? 'success' : 'error';
                const icon = success ? '✅' : '❌';
                const text = success ? '載入成功' : '載入失敗';
                
                html += `<div class="status ${status}">${icon} ${id}: ${text}</div>`;
            }
            
            if (Object.keys(testResults).length === 0) {
                html = '<div class="status info">⏳ 正在載入頭像...</div>';
            }
            
            resultsDiv.innerHTML = html;
        }
        
        // 頁面載入時初始化
        window.onload = function() {
            console.log('🎯 最終頭像測試頁面載入完成');
            updateResultsDisplay();
            
            // 5秒後顯示最終結果
            setTimeout(() => {
                const totalTests = Object.keys(testResults).length;
                const successCount = Object.values(testResults).filter(r => r).length;
                const failedCount = totalTests - successCount;
                
                console.log(`📊 測試完成: 成功 ${successCount}/${totalTests}, 失敗 ${failedCount}`);
                
                if (failedCount === 0) {
                    console.log('🎉 所有頭像測試通過！');
                } else {
                    console.log('⚠️ 部分頭像載入失敗，請檢查路徑配置');
                }
            }, 5000);
        };
    </script>
</body>
</html>
