<?php
/**
 * 簡單的語音功能測試頁面
 * 用於驗證語音轉文字功能是否正常工作
 */
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>語音功能測試</title>
    <link rel="stylesheet" href="voice_styles.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .test-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: #fafafa;
        }
        
        .test-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        .test-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .test-button {
            background: linear-gradient(135deg, #00C851 0%, #00A041 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        
        .test-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 200, 81, 0.4);
        }
        
        .test-button:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .status {
            padding: 10px;
            border-radius: 6px;
            margin-top: 15px;
            font-size: 14px;
        }
        
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
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
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🎤 語音功能測試</h1>
        
        <div class="test-section">
            <div class="test-title">1. 基本語音錄製測試</div>
            <p>點擊下方按鈕測試語音錄製功能（類似LINE的開關模式）</p>
            <button id="voiceTestBtn" class="test-button" onclick="testVoiceRecording()">🎤 開始語音測試</button>
            <div id="voiceStatus" class="status" style="display: none;"></div>
        </div>
        
        <div class="test-section">
            <div class="test-title">2. 語音轉文字測試</div>
            <p>錄製語音後會自動轉換為文字並顯示在下方輸入框中</p>
            <input type="text" id="transcriptionResult" class="test-input" placeholder="語音轉換結果將顯示在這裡..." readonly>
            <button class="test-button" onclick="clearResult()">清除結果</button>
        </div>
        
        <div class="test-section">
            <div class="test-title">3. 功能狀態檢查</div>
            <div id="featureStatus"></div>
        </div>
        
        <div class="test-section">
            <div class="test-title">4. 測試日誌</div>
            <div id="testLog" class="log">等待測試開始...</div>
            <button class="test-button" onclick="clearLog()">清除日誌</button>
        </div>
    </div>
    
    <!-- 語音錄製指示器 -->
    <div id="recordingIndicator" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(255,68,68,0.95); color: white; padding: 20px; border-radius: 15px; z-index: 1000; box-shadow: 0 8px 32px rgba(255, 68, 68, 0.4);">
        <div style="text-align: center;">
            <div style="font-size: 24px; margin-bottom: 10px;">🎤</div>
            <div>正在錄製語音...</div>
            <div id="recordingTimer" style="font-size: 18px; margin-top: 5px;"></div>
        </div>
    </div>
    
    <!-- 處理中指示器 -->
    <div id="processingIndicator" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 20px; border-radius: 10px; z-index: 1000;">
        <div style="text-align: center;">
            <div style="font-size: 24px; margin-bottom: 10px;">⏳</div>
            <div>正在轉換語音為文字...</div>
        </div>
    </div>
    
    <script src="voice_recorder.js"></script>
    <script>
        let isRecording = false;
        
        // 初始化測試
        document.addEventListener('DOMContentLoaded', function() {
            checkFeatureSupport();
            log('測試頁面已載入');
        });
        
        // 檢查功能支援
        function checkFeatureSupport() {
            const statusDiv = document.getElementById('featureStatus');
            let status = '';
            
            // 檢查瀏覽器支援
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                status += '<div class="status error">❌ 此瀏覽器不支援語音錄製功能</div>';
            } else {
                status += '<div class="status success">✅ 瀏覽器支援語音錄製</div>';
            }
            
            // 檢查語音錄製器
            if (typeof voiceRecorder !== 'undefined' && voiceRecorder) {
                status += '<div class="status success">✅ 語音錄製器已初始化</div>';
            } else {
                status += '<div class="status error">❌ 語音錄製器未初始化</div>';
            }
            
            // 檢查API端點
            status += '<div class="status info">ℹ️ 語音轉文字API: speech_to_text_api.php</div>';
            
            statusDiv.innerHTML = status;
        }
        
        // 測試語音錄製
        function testVoiceRecording() {
            if (!voiceRecorder) {
                showStatus('語音錄製器未初始化', 'error');
                return;
            }
            
            if (isRecording) {
                // 停止錄製
                voiceRecorder.stopRecording();
                isRecording = false;
                showStatus('已停止錄製', 'info');
            } else {
                // 開始錄製
                voiceRecorder.startRecording();
                isRecording = true;
                showStatus('開始錄製語音...', 'info');
                log('開始語音錄製測試');
            }
        }
        
        // 顯示狀態
        function showStatus(message, type) {
            const statusDiv = document.getElementById('voiceStatus');
            statusDiv.textContent = message;
            statusDiv.className = `status ${type}`;
            statusDiv.style.display = 'block';
            
            // 3秒後隱藏
            setTimeout(() => {
                statusDiv.style.display = 'none';
            }, 3000);
        }
        
        // 記錄日誌
        function log(message) {
            const logDiv = document.getElementById('testLog');
            const timestamp = new Date().toLocaleTimeString();
            logDiv.textContent += `[${timestamp}] ${message}\n`;
            logDiv.scrollTop = logDiv.scrollHeight;
        }
        
        // 清除結果
        function clearResult() {
            document.getElementById('transcriptionResult').value = '';
            log('清除轉換結果');
        }
        
        // 清除日誌
        function clearLog() {
            document.getElementById('testLog').textContent = '日誌已清除...\n';
        }
        
        // 監聽語音錄製事件
        if (typeof voiceRecorder !== 'undefined') {
            // 重寫insertTranscription方法以在測試頁面中顯示結果
            const originalInsertTranscription = voiceRecorder.insertTranscription;
            voiceRecorder.insertTranscription = function(text) {
                // 在測試頁面中顯示結果
                document.getElementById('transcriptionResult').value = text;
                showStatus('語音轉換成功！', 'success');
                log(`語音轉換結果: ${text}`);
                
                // 也調用原始方法（如果有的話）
                if (originalInsertTranscription) {
                    originalInsertTranscription.call(this, text);
                }
            };
            
            // 重寫showSuccess方法
            const originalShowSuccess = voiceRecorder.showSuccess;
            voiceRecorder.showSuccess = function(message) {
                showStatus(message, 'success');
                log(`成功: ${message}`);
                
                if (originalShowSuccess) {
                    originalShowSuccess.call(this, message);
                }
            };
            
            // 重寫showError方法
            const originalShowError = voiceRecorder.showError;
            voiceRecorder.showError = function(message) {
                showStatus(message, 'error');
                log(`錯誤: ${message}`);
                
                if (originalShowError) {
                    originalShowError.call(this, message);
                }
            };
        }
    </script>
</body>
</html>
