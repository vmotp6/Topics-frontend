<?php
/**
 * 語音轉文字功能測試頁面
 */

// 載入配置
require_once '../config.php';
require_once 'speech_config.php';

// 檢查配置
$configErrors = validateSpeechConfig();
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>語音轉文字功能測試</title>
    <link rel="stylesheet" href="voice_styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
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
        .status.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .test-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        .test-button:hover {
            background: #0056b3;
        }
        .test-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .config-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .config-info h4 {
            margin-top: 0;
        }
        .config-info code {
            background: #e9ecef;
            padding: 2px 4px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🎤 語音轉文字功能測試</h1>
        
        <!-- 配置狀態檢查 -->
        <div class="test-section">
            <h3>📋 配置狀態檢查</h3>
            <?php if (empty($configErrors)): ?>
                <div class="status success">
                    ✅ 配置檢查通過
                </div>
            <?php else: ?>
                <div class="status error">
                    ❌ 配置錯誤：
                    <ul>
                        <?php foreach ($configErrors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 配置資訊 -->
        <div class="test-section">
            <h3>⚙️ 當前配置</h3>
            <div class="config-info">
                <h4>API 設定</h4>
                <p><strong>API Key:</strong> 
                    <?php 
                    $apiKey = getSpeechConfig('api_key');
                    echo $apiKey === 'your-google-cloud-api-key' ? 
                        '<span style="color: red;">未設定</span>' : 
                        '<span style="color: green;">已設定</span>';
                    ?>
                </p>
                <p><strong>API URL:</strong> <code><?php echo getSpeechConfig('api_url'); ?></code></p>
                
                <h4>預設設定</h4>
                <p><strong>預設語言:</strong> <?php echo getSpeechConfig('default_language'); ?></p>
                <p><strong>預設編碼:</strong> <?php echo getSpeechConfig('default_encoding'); ?></p>
                <p><strong>取樣率:</strong> <?php echo getSpeechConfig('default_sample_rate'); ?> Hz</p>
                <p><strong>模型:</strong> <?php echo getSpeechConfig('model'); ?></p>
                
                <h4>限制設定</h4>
                <p><strong>最大檔案大小:</strong> <?php echo round(getSpeechConfig('max_file_size') / (1024 * 1024), 1); ?> MB</p>
                <p><strong>最大錄製時間:</strong> <?php echo getSpeechConfig('max_recording_time'); ?> 秒</p>
                <p><strong>最低準確度:</strong> <?php echo getSpeechConfig('min_confidence') * 100; ?>%</p>
            </div>
        </div>
        
        <!-- 支援的語言 -->
        <div class="test-section">
            <h3>🌍 支援的語言</h3>
            <div class="config-info">
                <?php 
                $languages = getSupportedLanguages();
                foreach ($languages as $code => $name): 
                ?>
                    <span style="display: inline-block; margin: 5px; padding: 5px 10px; background: #e9ecef; border-radius: 3px;">
                        <strong><?php echo $code; ?></strong> - <?php echo $name; ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- 功能測試 -->
        <div class="test-section">
            <h3>🧪 功能測試</h3>
            <p>請確保您的瀏覽器支援語音錄製功能，並且已授權麥克風權限。</p>
            
            <div style="text-align: center; margin: 20px 0;">
                <button id="testVoiceBtn" class="test-button" onclick="testVoiceRecording()">
                    🎤 測試語音錄製
                </button>
                <button id="testApiBtn" class="test-button" onclick="testApiConnection()">
                    🔗 測試 API 連接
                </button>
            </div>
            
            <div id="testResults" style="margin-top: 20px;"></div>
        </div>
        
        <!-- 使用說明 -->
        <div class="test-section">
            <h3>📖 使用說明</h3>
            <ol>
                <li>確保已設定 Google Cloud API Key</li>
                <li>點擊「測試 API 連接」檢查 API 是否可用</li>
                <li>點擊「測試語音錄製」開始語音測試</li>
                <li>允許瀏覽器使用麥克風</li>
                <li>說話並等待轉換結果</li>
            </ol>
        </div>
        
        <!-- 故障排除 -->
        <div class="test-section">
            <h3>🔧 故障排除</h3>
            <div class="config-info">
                <h4>常見問題：</h4>
                <ul>
                    <li><strong>麥克風權限被拒絕：</strong> 檢查瀏覽器設定，確保使用 HTTPS</li>
                    <li><strong>API 連接失敗：</strong> 檢查 API Key 是否正確，API 是否已啟用</li>
                    <li><strong>語音識別失敗：</strong> 確保在安靜環境中錄製，說話清晰</li>
                    <li><strong>檔案大小超限：</strong> 縮短錄製時間或降低音頻品質</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- 語音錄製指示器 -->
    <div id="recordingIndicator" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(255,0,0,0.8); color: white; padding: 20px; border-radius: 10px; z-index: 1000;">
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
        // 測試語音錄製
        function testVoiceRecording() {
            if (!voiceRecorder) {
                showTestResult('語音錄製功能尚未初始化', 'error');
                return;
            }
            
            if (voiceRecorder.isCurrentlyRecording()) {
                voiceRecorder.stopRecording();
            } else {
                voiceRecorder.startRecording();
            }
        }
        
        // 測試 API 連接
        async function testApiConnection() {
            const testBtn = document.getElementById('testApiBtn');
            testBtn.disabled = true;
            testBtn.textContent = '🔄 測試中...';
            
            try {
                const response = await fetch('speech_to_text_api.php?action=get_languages');
                const result = await response.json();
                
                if (result.success) {
                    showTestResult('✅ API 連接成功！支援 ' + Object.keys(result.languages).length + ' 種語言', 'success');
                } else {
                    showTestResult('❌ API 連接失敗: ' + result.error, 'error');
                }
            } catch (error) {
                showTestResult('❌ 網路請求失敗: ' + error.message, 'error');
            } finally {
                testBtn.disabled = false;
                testBtn.textContent = '🔗 測試 API 連接';
            }
        }
        
        // 顯示測試結果
        function showTestResult(message, type) {
            const resultsDiv = document.getElementById('testResults');
            const resultDiv = document.createElement('div');
            resultDiv.className = `status ${type}`;
            resultDiv.textContent = message;
            resultsDiv.appendChild(resultDiv);
            
            // 5秒後自動移除
            setTimeout(() => {
                if (resultDiv.parentNode) {
                    resultDiv.parentNode.removeChild(resultDiv);
                }
            }, 5000);
        }
        
        // 頁面載入完成後初始化
        document.addEventListener('DOMContentLoaded', function() {
            console.log('語音轉文字測試頁面已載入');
        });
    </script>
</body>
</html>

