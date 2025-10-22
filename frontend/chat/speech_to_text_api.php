<?php
/**
 * Google Cloud Speech-to-Text API 整合
 * 用於私訊聊天中的語音轉文字功能
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 載入配置
try {
    require_once '../config.php';
    require_once 'speech_config.php';
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => '配置載入失敗: ' . $e->getMessage()
    ]);
    exit;
}

class SpeechToTextService {
    private $apiKey;
    private $apiUrl;
    private $config;
    
    public function __construct() {
        $this->config = getSpeechConfig();
        $this->apiKey = $this->config['api_key'];
        $this->apiUrl = $this->config['api_url'];
    }
    
    /**
     * 將語音轉換為文字
     */
    public function transcribeAudio($audioData, $languageCode = null, $encoding = null) {
        try {
            // 使用預設值或配置值
            $languageCode = $languageCode ?: $this->config['default_language'];
            $encoding = $encoding ?: $this->config['default_encoding'];
            $sampleRate = $this->config['default_sample_rate'];
            
            // 驗證語言和編碼
            if (!isLanguageSupported($languageCode)) {
                return [
                    'success' => false,
                    'error' => '不支援的語言: ' . $languageCode
                ];
            }
            
            if (!isEncodingSupported($encoding)) {
                return [
                    'success' => false,
                    'error' => '不支援的音頻格式: ' . $encoding
                ];
            }
            
            // 構建請求數據
            $requestData = [
                'config' => [
                    'encoding' => $encoding,
                    'sampleRateHertz' => $sampleRate,
                    'languageCode' => $languageCode,
                    'enableAutomaticPunctuation' => $this->config['enable_automatic_punctuation'],
                    'enableWordTimeOffsets' => $this->config['enable_word_time_offsets'],
                    'enableSpeakerDiarization' => $this->config['enable_speaker_diarization'],
                    'model' => $this->config['model']
                ],
                'audio' => [
                    'content' => base64_encode($audioData)
                ]
            ];
            
            // 發送請求到 Google Cloud Speech-to-Text API
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl . '?key=' . $this->apiKey);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, getApiHeaders());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                return [
                    'success' => false,
                    'error' => 'CURL錯誤: ' . $error
                ];
            }
            
            if ($httpCode !== 200) {
                return [
                    'success' => false,
                    'error' => 'API錯誤: HTTP ' . $httpCode,
                    'response' => $response
                ];
            }
            
            $result = json_decode($response, true);
            
            if (isset($result['results']) && !empty($result['results'])) {
                $transcript = $result['results'][0]['alternatives'][0]['transcript'];
                $confidence = $result['results'][0]['alternatives'][0]['confidence'] ?? 0;
                
                // 檢查準確度
                $minConfidence = $this->config['min_confidence'];
                if ($confidence < $minConfidence) {
                    return [
                        'success' => false,
                        'error' => '語音識別準確度過低 (準確度: ' . round($confidence * 100) . '%)',
                        'confidence' => $confidence
                    ];
                }
                
                // 記錄轉換日誌
                logSpeechTranscription([
                    'transcript' => $transcript,
                    'confidence' => $confidence,
                    'language' => $languageCode
                ]);
                
                return [
                    'success' => true,
                    'transcript' => $transcript,
                    'confidence' => $confidence,
                    'language' => $languageCode,
                    'model' => $this->config['model']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => '無法識別語音內容',
                    'response' => $result
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => '語音轉文字服務錯誤: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 支援的語言列表
     */
    public function getSupportedLanguages() {
        return getSupportedLanguages();
    }
}

// 處理請求
try {
    $speechService = new SpeechToTextService();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'transcribe':
                handleTranscribe($speechService);
                break;
            case 'get_languages':
                getSupportedLanguages($speechService);
                break;
            default:
                echo json_encode(['success' => false, 'error' => '無效的動作']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => '只支援POST請求']);
    }
    
} catch (Exception $e) {
    // 確保輸出是JSON格式
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => '語音轉文字服務錯誤: ' . $e->getMessage()
    ]);
    exit;
}

/**
 * 處理語音轉文字請求
 */
function handleTranscribe($speechService) {
    // 檢查是否有上傳的音頻文件
    if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => '沒有上傳音頻文件']);
        return;
    }
    
    $audioFile = $_FILES['audio'];
    $languageCode = $_POST['language'] ?? 'zh-TW';
    $encoding = $_POST['encoding'] ?? 'WEBM_OPUS';
    
    // 檢查文件大小
    if (!isFileSizeValid($audioFile['size'])) {
        $maxSize = getSpeechConfig('max_file_size');
        $maxSizeMB = round($maxSize / (1024 * 1024), 1);
        echo json_encode(['success' => false, 'error' => "音頻文件大小不能超過 {$maxSizeMB}MB"]);
        return;
    }
    
    // 檢查文件類型
    $allowedTypes = ['audio/webm', 'audio/wav', 'audio/mp3', 'audio/ogg', 'audio/flac'];
    $fileType = mime_content_type($audioFile['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(['success' => false, 'error' => '不支援的音頻格式，支援的格式: ' . implode(', ', $allowedTypes)]);
        return;
    }
    
    // 讀取音頻文件
    $audioData = file_get_contents($audioFile['tmp_name']);
    
    if ($audioData === false) {
        echo json_encode(['success' => false, 'error' => '無法讀取音頻文件']);
        return;
    }
    
    // 轉換語音為文字
    $result = $speechService->transcribeAudio($audioData, $languageCode, $encoding);
    
    // 記錄轉換歷史（可選）
    if ($result['success']) {
        logTranscriptionHistory($result);
    }
    
    echo json_encode($result);
}

/**
 * 獲取支援的語言列表
 */
function getSupportedLanguages($speechService) {
    $languages = $speechService->getSupportedLanguages();
    echo json_encode([
        'success' => true,
        'languages' => $languages
    ]);
}

/**
 * 記錄轉換歷史
 */
function logTranscriptionHistory($result) {
    try {
        // 這裡可以將轉換歷史記錄到資料庫
        // 例如記錄用戶、時間、語言、準確度等
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'language' => $result['language'],
            'confidence' => $result['confidence'],
            'transcript_length' => strlen($result['transcript'])
        ];
        
        // 可以記錄到日誌文件或資料庫
        error_log('Speech-to-Text: ' . json_encode($logData));
        
    } catch (Exception $e) {
        // 記錄失敗不影響主要功能
        error_log('Speech-to-Text logging failed: ' . $e->getMessage());
    }
}
?>
