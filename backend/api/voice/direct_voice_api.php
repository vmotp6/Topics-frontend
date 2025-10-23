<?php
/**
 * 直接文字語音生成 API
 * 使用 --text 參數而不是檔案
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理 OPTIONS 請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 創建輸出目錄（使用正確的絕對路徑）
$output_dir = 'C:/Topics/Topics-frontend/frontend/assets/voice/';

if (!is_dir($output_dir)) {
    mkdir($output_dir, 0755, true);
}

// 確保目錄存在
if (!is_dir($output_dir)) {
    error_log("無法創建目錄: " . $output_dir);
    echo json_encode([
        'success' => false,
        'error' => '無法創建輸出目錄: ' . $output_dir
    ]);
    exit();
}

// 處理 get_suggestions 動作
if (isset($_GET['action']) && $_GET['action'] === 'get_suggestions') {
    $character = $_GET['character'] ?? 'default';
    
    $suggestions = [
        'default' => [
            'voice' => 'en-US-AriaNeural',
            'rate' => 0,
            'pitch' => 0,
            'volume' => 0
        ],
        'kurumi' => [
            'voice' => 'ja-JP-NanamiNeural',
            'rate' => 5,
            'pitch' => 10,
            'volume' => 5
        ],
        'cute' => [
            'voice' => 'en-US-JennyNeural',
            'rate' => 10,
            'pitch' => 15,
            'volume' => 5
        ],
        'mature' => [
            'voice' => 'en-US-MichelleNeural',
            'rate' => -5,
            'pitch' => -5,
            'volume' => 0
        ]
    ];
    
    $suggestion = $suggestions[$character] ?? $suggestions['default'];
    
    echo json_encode([
        'success' => true,
        'suggestions' => $suggestion
    ]);
    exit();
}

// 處理 get_voices 動作
if (isset($_GET['action']) && $_GET['action'] === 'get_voices') {
    $voices = [
        'en-US-AriaNeural' => ['name' => 'Aria (English)', 'gender' => 'Female', 'language' => 'English', 'style' => 'Crisp, Bright, Clear'],
        'en-US-JennyNeural' => ['name' => 'Jenny (English) - 時崎狂三風格', 'gender' => 'Female', 'language' => 'English', 'style' => '神秘、優雅、略帶危險'],
        'en-US-MichelleNeural' => ['name' => 'Michelle (English)', 'gender' => 'Female', 'language' => 'English', 'style' => 'Confident, Authentic, Warm'],
        'en-US-EmmaNeural' => ['name' => 'Emma (English)', 'gender' => 'Female', 'language' => 'English', 'style' => 'Cheerful, Light-Hearted, Casual'],
        'en-US-LunaNeural' => ['name' => 'Luna (English)', 'gender' => 'Female', 'language' => 'English', 'style' => 'Sincere, Pleasant, Bright, Clear, Friendly, Warm'],
        'en-US-GuyNeural' => ['name' => 'Guy (English)', 'gender' => 'Male', 'language' => 'English', 'style' => 'Light-Hearted, Whimsical, Friendly'],
        'en-US-ChristopherNeural' => ['name' => 'Christopher (English)', 'gender' => 'Male', 'language' => 'English', 'style' => 'Deep, Warm'],
        'en-US-BrianNeural' => ['name' => 'Brian (English)', 'gender' => 'Male', 'language' => 'English', 'style' => 'Sincere, Calm, Approachable']
    ];
    
    echo json_encode([
        'success' => true,
        'voices' => $voices
    ]);
    exit();
}

// 獲取參數
$text = $_POST['text'] ?? 'こんにちは';
$voice = $_POST['voice'] ?? 'ja-JP-NanamiNeural';

try {
    // 生成檔案名
    $filename = 'voice_' . time() . '_' . substr(md5($text . $voice), 0, 8) . '.wav';
    $output_path = $output_dir . $filename;
    
    // 使用原始文字，通過參數調整情感
    $enhanced_text = $text;
    
    // 創建臨時檔案來處理編碼問題
    $temp_file = tempnam(sys_get_temp_dir(), 'tts_') . '.txt';
    file_put_contents($temp_file, $enhanced_text, LOCK_EX);
    
    // 確保檔案使用 UTF-8 編碼
    $content = file_get_contents($temp_file);
    file_put_contents($temp_file, $content, LOCK_EX);
    
    // 根據語音類型添加時崎狂三風格參數
    $style_params = '';
    if (strpos($voice, 'en-US-JennyNeural') !== false) {
        // Jenny - 時崎狂三風格（使用日文語音更適合）
        // 先使用基本語音，後續用 RVC 轉換
        $style_params = '';
    }
    
    // 構建命令（使用 --file 而不是 --text）
    $cmd = sprintf(
        'python -m edge_tts --voice "%s" --file "%s" --write-media "%s"%s 2>&1',
        $voice,
        $temp_file,
        $output_path,
        $style_params
    );
    
    // 記錄命令
    error_log("執行命令: " . $cmd);
    
    // 執行命令
    $output = [];
    $return_code = 0;
    exec($cmd, $output, $return_code);
    
    // 清理臨時檔案
    if (file_exists($temp_file)) {
        unlink($temp_file);
    }
    
    // 記錄結果
    error_log("返回碼: " . $return_code);
    error_log("輸出: " . implode("\n", $output));
    
    if ($return_code === 0 && file_exists($output_path)) {
        $file_size = filesize($output_path);
        echo json_encode([
            'success' => true,
            'filename' => $filename,
            'url' => 'http://localhost/Topics-frontend/frontend/assets/voice/' . $filename,
            'size' => $file_size,
            'message' => '語音生成成功！',
            'voice' => $voice,
            'text' => $text,
            'command' => $cmd,
            'output_path' => $output_path,
            'output_dir' => $output_dir
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => '語音生成失敗',
            'return_code' => $return_code,
            'command' => $cmd,
            'output' => $output,
            'file_exists' => file_exists($output_path),
            'debug_info' => [
                'output_path' => $output_path,
                'text_length' => strlen($text),
                'text_encoded' => base64_encode($text)
            ]
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'API 錯誤: ' . $e->getMessage()
    ]);
}
?>

