<?php
/**
 * 簡化的語音轉文字API
 * 用於測試和基本功能
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 處理語音轉文字請求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'transcribe') {
        handleTranscribe();
    } else {
        echo json_encode(['success' => false, 'error' => '無效的動作']);
    }
} else {
    echo json_encode(['success' => false, 'error' => '只支援POST請求']);
}

function handleTranscribe() {
    // 檢查是否有上傳的音頻文件
    if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => '沒有上傳音頻文件']);
        return;
    }
    
    $audioFile = $_FILES['audio'];
    $languageCode = $_POST['language'] ?? 'zh-TW';
    
    // 調試信息
    error_log('語音轉換請求: ' . json_encode([
        'file_name' => $audioFile['name'],
        'file_size' => $audioFile['size'],
        'file_type' => $audioFile['type'],
        'file_error' => $audioFile['error'],
        'language' => $languageCode
    ]));
    
    // 檢查文件大小
    if ($audioFile['size'] > 10 * 1024 * 1024) { // 10MB
        echo json_encode(['success' => false, 'error' => '音頻文件大小不能超過10MB']);
        return;
    }
    
    // 檢查文件類型
    $allowedTypes = [
        'audio/webm', 
        'audio/wav', 
        'audio/mp3', 
        'audio/ogg', 
        'audio/flac',
        'audio/webm;codecs=opus',
        'application/octet-stream'  // 有時WebM會被識別為此類型
    ];
    
    $fileType = mime_content_type($audioFile['tmp_name']);
    
    // 如果無法識別MIME類型，檢查文件擴展名
    if (!in_array($fileType, $allowedTypes)) {
        $fileName = $audioFile['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['webm', 'wav', 'mp3', 'ogg', 'flac'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            echo json_encode([
                'success' => false, 
                'error' => '不支援的音頻格式。檢測到的類型: ' . $fileType . ', 文件名: ' . $fileName
            ]);
            return;
        }
    }
    
    // 模擬語音轉文字結果
    $mockTranscriptions = [
        'zh-TW' => [
            '你好，這是一個測試訊息',
            '今天天氣很好',
            '我正在測試語音功能',
            '請確認收到此訊息',
            '語音轉文字功能正常運作'
        ],
        'en-US' => [
            'Hello, this is a test message',
            'The weather is nice today',
            'I am testing the voice function',
            'Please confirm you received this message',
            'Voice to text function is working properly'
        ]
    ];
    
    $transcriptions = $mockTranscriptions[$languageCode] ?? $mockTranscriptions['zh-TW'];
    $transcript = $transcriptions[array_rand($transcriptions)];
    $confidence = 0.85 + (rand(0, 15) / 100); // 0.85-1.0
    
    // 記錄轉換日誌
    error_log('Simple Speech-to-Text: ' . json_encode([
        'timestamp' => date('Y-m-d H:i:s'),
        'language' => $languageCode,
        'transcript' => $transcript,
        'confidence' => $confidence,
        'file_size' => $audioFile['size'],
        'file_type' => $fileType
    ]));
    
    echo json_encode([
        'success' => true,
        'transcript' => $transcript,
        'confidence' => $confidence,
        'language' => $languageCode,
        'model' => 'simple-mock'
    ]);
}
?>
