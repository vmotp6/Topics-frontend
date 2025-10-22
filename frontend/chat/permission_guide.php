<?php
/**
 * 權限狀態檢查和設定指南
 * 幫助用戶解決麥克風和通知權限問題
 */
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>權限設定指南</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
            line-height: 1.6;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .status-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .status-card.success {
            background: #d4edda;
            border-color: #c3e6cb;
        }
        
        .status-card.warning {
            background: #fff3cd;
            border-color: #ffeaa7;
        }
        
        .status-card.error {
            background: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .status-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .status-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        
        .guide-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .guide-section h3 {
            color: #495057;
            margin-bottom: 15px;
        }
        
        .step {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin: 10px 0;
            display: flex;
            align-items: center;
        }
        
        .step-number {
            background: #007bff;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .browser-guide {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .browser-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
        }
        
        .browser-card h4 {
            margin: 0 0 15px 0;
            color: #333;
        }
        
        .test-button {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            margin: 10px 5px;
            transition: all 0.3s ease;
        }
        
        .test-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }
        
        .test-button:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .log {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
            white-space: pre-wrap;
            margin-top: 15px;
        }
        
        .refresh-notice {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 權限設定指南</h1>
            <p>檢查和修復麥克風與通知權限問題</p>
        </div>
        
        <!-- 權限狀態檢查 -->
        <div class="status-card" id="microphoneStatus">
            <div class="status-title">
                <span class="status-icon">🎤</span>
                <span>麥克風權限</span>
            </div>
            <div id="microphoneStatusText">檢查中...</div>
        </div>
        
        <div class="status-card" id="notificationStatus">
            <div class="status-title">
                <span class="status-icon">🔔</span>
                <span>通知權限</span>
            </div>
            <div id="notificationStatusText">檢查中...</div>
        </div>
        
        <!-- 測試功能 -->
        <div class="guide-section">
            <h3>🧪 功能測試</h3>
            <p>點擊下方按鈕測試各項功能是否正常：</p>
            <button class="test-button" onclick="testMicrophone()">測試麥克風</button>
            <button class="test-button" onclick="testNotification()">測試通知</button>
            <button class="test-button" onclick="clearLog()">清除日誌</button>
            <div id="testLog" class="log">等待測試...</div>
        </div>
        
        <!-- 設定指南 -->
        <div class="guide-section">
            <h3>📋 權限設定步驟</h3>
            <p>如果權限被拒絕，請按照以下步驟重新啟用：</p>
            
            <div class="step">
                <div class="step-number">1</div>
                <div>點擊瀏覽器地址欄左側的鎖頭圖示 🔒 或盾牌圖示 🛡️</div>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <div>在彈出的權限設定中找到「麥克風」和「通知」選項</div>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <div>將這些選項設定為「允許」</div>
            </div>
            
            <div class="step">
                <div class="step-number">4</div>
                <div>重新整理頁面以套用設定</div>
            </div>
        </div>
        
        <!-- 瀏覽器特定指南 -->
        <div class="guide-section">
            <h3>🌐 不同瀏覽器的設定方法</h3>
            <div class="browser-guide">
                <div class="browser-card">
                    <h4>Chrome</h4>
                    <p>1. 點擊地址欄左側的鎖頭圖示</p>
                    <p>2. 選擇「網站設定」</p>
                    <p>3. 啟用麥克風和通知權限</p>
                </div>
                
                <div class="browser-card">
                    <h4>Firefox</h4>
                    <p>1. 點擊地址欄左側的盾牌圖示</p>
                    <p>2. 選擇「權限」</p>
                    <p>3. 啟用麥克風和通知權限</p>
                </div>
                
                <div class="browser-card">
                    <h4>Safari</h4>
                    <p>1. 點擊地址欄左側的「i」圖示</p>
                    <p>2. 選擇「網站設定」</p>
                    <p>3. 啟用麥克風和通知權限</p>
                </div>
                
                <div class="browser-card">
                    <h4>Edge</h4>
                    <p>1. 點擊地址欄左側的鎖頭圖示</p>
                    <p>2. 選擇「權限」</p>
                    <p>3. 啟用麥克風和通知權限</p>
                </div>
            </div>
        </div>
        
        <div class="refresh-notice">
            <strong>💡 重要提示：</strong>修改權限設定後，請重新整理頁面以確保設定生效。如果問題仍然存在，請嘗試清除瀏覽器快取或使用無痕模式。
        </div>
    </div>
    
    <script>
        // 檢查權限狀態
        async function checkPermissions() {
            await checkMicrophonePermission();
            await checkNotificationPermission();
        }
        
        // 檢查麥克風權限
        async function checkMicrophonePermission() {
            const statusCard = document.getElementById('microphoneStatus');
            const statusText = document.getElementById('microphoneStatusText');
            
            try {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    statusCard.className = 'status-card error';
                    statusText.innerHTML = '❌ 此瀏覽器不支援麥克風功能';
                    return;
                }
                
                // 檢查權限狀態
                let permissionState = 'unknown';
                if (navigator.permissions) {
                    try {
                        const result = await navigator.permissions.query({ name: 'microphone' });
                        permissionState = result.state;
                    } catch (e) {
                        console.log('無法檢查麥克風權限狀態');
                    }
                }
                
                if (permissionState === 'granted') {
                    statusCard.className = 'status-card success';
                    statusText.innerHTML = '✅ 麥克風權限已啟用';
                } else if (permissionState === 'denied') {
                    statusCard.className = 'status-card error';
                    statusText.innerHTML = '❌ 麥克風權限被拒絕，請在瀏覽器設定中啟用';
                } else {
                    statusCard.className = 'status-card warning';
                    statusText.innerHTML = '⚠️ 麥克風權限未設定，點擊測試按鈕時會要求權限';
                }
                
            } catch (error) {
                statusCard.className = 'status-card error';
                statusText.innerHTML = '❌ 檢查麥克風權限時發生錯誤: ' + error.message;
            }
        }
        
        // 檢查通知權限
        async function checkNotificationPermission() {
            const statusCard = document.getElementById('notificationStatus');
            const statusText = document.getElementById('notificationStatusText');
            
            if (!('Notification' in window)) {
                statusCard.className = 'status-card error';
                statusText.innerHTML = '❌ 此瀏覽器不支援通知功能';
                return;
            }
            
            const permission = Notification.permission;
            
            if (permission === 'granted') {
                statusCard.className = 'status-card success';
                statusText.innerHTML = '✅ 通知權限已啟用';
            } else if (permission === 'denied') {
                statusCard.className = 'status-card error';
                statusText.innerHTML = '❌ 通知權限被拒絕，請在瀏覽器設定中啟用';
            } else {
                statusCard.className = 'status-card warning';
                statusText.innerHTML = '⚠️ 通知權限未設定，點擊測試按鈕時會要求權限';
            }
        }
        
        // 測試麥克風
        async function testMicrophone() {
            log('開始測試麥克風...');
            
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                log('✅ 麥克風測試成功！');
                
                // 停止流
                stream.getTracks().forEach(track => track.stop());
                
                // 重新檢查權限狀態
                setTimeout(checkMicrophonePermission, 1000);
                
            } catch (error) {
                log('❌ 麥克風測試失敗: ' + error.message);
                
                if (error.name === 'NotAllowedError') {
                    log('💡 請在瀏覽器設定中允許麥克風權限');
                } else if (error.name === 'NotFoundError') {
                    log('💡 找不到麥克風設備，請確認麥克風已連接');
                } else if (error.name === 'NotReadableError') {
                    log('💡 麥克風被其他應用程式佔用');
                }
            }
        }
        
        // 測試通知
        async function testNotification() {
            log('開始測試通知...');
            
            try {
                if (!('Notification' in window)) {
                    log('❌ 此瀏覽器不支援通知功能');
                    return;
                }
                
                const permission = await Notification.requestPermission();
                
                if (permission === 'granted') {
                    const notification = new Notification('測試通知', {
                        body: '通知功能測試成功！',
                        icon: '../assets/icon-192x192.svg'
                    });
                    
                    log('✅ 通知測試成功！');
                    
                    // 3秒後關閉通知
                    setTimeout(() => notification.close(), 3000);
                    
                    // 重新檢查權限狀態
                    setTimeout(checkNotificationPermission, 1000);
                    
                } else {
                    log('❌ 通知權限被拒絕: ' + permission);
                }
                
            } catch (error) {
                log('❌ 通知測試失敗: ' + error.message);
            }
        }
        
        // 記錄日誌
        function log(message) {
            const logDiv = document.getElementById('testLog');
            const timestamp = new Date().toLocaleTimeString();
            logDiv.textContent += `[${timestamp}] ${message}\n`;
            logDiv.scrollTop = logDiv.scrollHeight;
        }
        
        // 清除日誌
        function clearLog() {
            document.getElementById('testLog').textContent = '日誌已清除...\n';
        }
        
        // 頁面載入時檢查權限
        document.addEventListener('DOMContentLoaded', function() {
            checkPermissions();
            log('權限檢查頁面已載入');
        });
    </script>
</body>
</html>
