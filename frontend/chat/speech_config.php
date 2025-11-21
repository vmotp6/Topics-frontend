<?php
/**
 * Google Cloud Speech-to-Text API 配置
 */

// Google Cloud Speech-to-Text API 配置
$speech_config = [
    // API 設定
    'api_key' => getenv('GOOGLE_CLOUD_API_KEY') ?: 'your-google-cloud-api-key',
    'api_url' => 'https://speech.googleapis.com/v1/speech:recognize',
    
    // 預設設定
    'default_language' => 'zh-TW',
    'default_encoding' => 'WEBM_OPUS',
    'default_sample_rate' => 48000,
    
    // 支援的語言
    'supported_languages' => [
        'zh-TW' => '繁體中文',
        'zh-CN' => '簡體中文',
        'en-US' => 'English (US)',
        'en-GB' => 'English (UK)',
        'ja-JP' => '日本語',
        'ko-KR' => '한국어',
        'th-TH' => 'ไทย',
        'vi-VN' => 'Tiếng Việt'
    ],
    
    // 支援的音頻格式
    'supported_encodings' => [
        'WEBM_OPUS' => 'WebM Opus',
        'LINEAR16' => 'Linear PCM 16-bit',
        'FLAC' => 'FLAC',
        'MULAW' => 'μ-law',
        'AMR' => 'AMR',
        'AMR_WB' => 'AMR Wideband',
        'OGG_OPUS' => 'OGG Opus',
        'SPEEX_WITH_HEADER_BYTE' => 'Speex'
    ],
    
    // 檔案大小限制
    'max_file_size' => 10 * 1024 * 1024, // 10MB
    
    // 錄製時間限制
    'max_recording_time' => 60, // 60秒
    
    // 準確度設定
    'min_confidence' => 0.5,
    
    // 啟用功能
    'enable_automatic_punctuation' => true,
    'enable_word_time_offsets' => false,
    'enable_speaker_diarization' => false,
    
    // 模型設定
    'model' => 'latest_long', // 或 'latest_short'
    
    // 除錯模式
    'debug_mode' => false
];

/**
 * 獲取語音配置
 */
function getSpeechConfig($key = null) {
    global $speech_config;
    
    if ($key === null) {
        return $speech_config;
    }
    
    return isset($speech_config[$key]) ? $speech_config[$key] : null;
}

/**
 * 驗證語音配置
 */
function validateSpeechConfig() {
    global $speech_config;
    
    $errors = [];
    
    // 檢查 API Key
    if (empty($speech_config['api_key']) || $speech_config['api_key'] === 'your-google-cloud-api-key') {
        $errors[] = 'Google Cloud API Key 未設定';
    }
    
    // 檢查 API URL
    if (empty($speech_config['api_url'])) {
        $errors[] = 'API URL 未設定';
    }
    
    return $errors;
}

/**
 * 獲取支援的語言列表
 */
function getSupportedLanguages() {
    return getSpeechConfig('supported_languages');
}

/**
 * 獲取支援的音頻格式
 */
function getSupportedEncodings() {
    return getSpeechConfig('supported_encodings');
}

/**
 * 檢查語言是否支援
 */
function isLanguageSupported($languageCode) {
    $languages = getSupportedLanguages();
    return isset($languages[$languageCode]);
}

/**
 * 檢查音頻格式是否支援
 */
function isEncodingSupported($encoding) {
    $encodings = getSupportedEncodings();
    return isset($encodings[$encoding]);
}

/**
 * 獲取預設設定
 */
function getDefaultSettings() {
    return [
        'language' => getSpeechConfig('default_language'),
        'encoding' => getSpeechConfig('default_encoding'),
        'sample_rate' => getSpeechConfig('default_sample_rate'),
        'model' => getSpeechConfig('model')
    ];
}

/**
 * 記錄語音轉換日誌
 */
function logSpeechTranscription($data) {
    if (!getSpeechConfig('debug_mode')) {
        return;
    }
    
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'language' => $data['language'] ?? 'unknown',
        'confidence' => $data['confidence'] ?? 0,
        'transcript_length' => strlen($data['transcript'] ?? ''),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    error_log('Speech-to-Text: ' . json_encode($logData));
}

/**
 * 檢查檔案大小是否在限制內
 */
function isFileSizeValid($fileSize) {
    $maxSize = getSpeechConfig('max_file_size');
    return $fileSize <= $maxSize;
}

/**
 * 檢查錄製時間是否在限制內
 */
function isRecordingTimeValid($duration) {
    $maxTime = getSpeechConfig('max_recording_time');
    return $duration <= $maxTime;
}

/**
 * 獲取API請求標頭
 */
function getApiHeaders() {
    return [
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: Topics-Chat-System/1.0'
    ];
}
?>





















