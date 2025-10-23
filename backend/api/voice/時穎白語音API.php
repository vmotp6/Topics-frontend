<?php
/**
 * 時穎白語音模型 API
 * 基於訓練好的模型生成時崎狂三風格語音
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

// 載入模型配置
$model_path = 'D:/RVC/時穎白語音模型/訓練資料/models/時穎白_快速模型.json';
$config_path = 'D:/RVC/時穎白語音模型/訓練資料/training_config.json';

function loadModel($model_path) {
    if (!file_exists($model_path)) {
        return null;
    }
    
    $model_data = json_decode(file_get_contents($model_path), true);
    return $model_data;
}

function loadConfig($config_path) {
    if (!file_exists($config_path)) {
        return null;
    }
    
    $config_data = json_decode(file_get_contents($config_path), true);
    return $config_data;
}

function generateVoiceFeatures($text, $style = 'mysterious') {
    /**
     * 生成時穎白語音特徵
     * 基於訓練好的模型參數
     */
    
    // 基礎特徵生成
    $features = [];
    $text_length = strlen($text);
    $text_hash = crc32($text);
    
    // 使用文字特徵生成一致的參數
    for ($i = 0; $i < 10; $i++) {
        $seed = ($text_hash + $i * 1000) % 1000000;
        $random = ($seed * 9301 + 49297) % 233280 / 233280;
        
        if ($i == 0) { // 頻率特徵
            $features[] = 0.3 + $random * 0.2;
        } elseif ($i == 1) { // 語調特徵
            $features[] = 0.7 + $random * 0.2;
        } elseif ($i == 2) { // 情感特徵
            $features[] = 0.8 + $random * 0.1;
        } else {
            $features[] = $random * 2 - 1;
        }
    }
    
    return $features;
}

function predictVoiceStyle($features, $model) {
    /**
     * 使用訓練好的模型預測語音風格
     */
    
    if (!$model || !isset($model['weights'])) {
        return 0.5; // 預設值
    }
    
    $weights = $model['weights'];
    $prediction = 0.0;
    
    for ($i = 0; $i < min(count($features), count($weights)); $i++) {
        $prediction += $features[$i] * $weights[$i];
    }
    
    return $prediction;
}

function generateVoiceParameters($text, $style = 'mysterious', $model = null) {
    /**
     * 生成時穎白語音參數
     */
    
    // 生成語音特徵
    $features = generateVoiceFeatures($text, $style);
    
    // 預測語音風格
    $style_score = predictVoiceStyle($features, $model);
    
    // 基礎參數
    $base_pitch = 0.6 + ($style_score * 0.3);
    $base_speed = 0.8 + ($style_score * 0.2);
    $base_volume = 0.7 + ($style_score * 0.2);
    
    // 根據風格調整參數
    $style_adjustments = [
        'mysterious' => ['pitch' => 0.1, 'speed' => -0.1, 'volume' => 0.0],
        'cute' => ['pitch' => 0.2, 'speed' => 0.1, 'volume' => 0.1],
        'mature' => ['pitch' => -0.1, 'speed' => -0.1, 'volume' => 0.0],
        'dangerous' => ['pitch' => -0.2, 'speed' => -0.2, 'volume' => -0.1]
    ];
    
    $adjustment = $style_adjustments[$style] ?? ['pitch' => 0, 'speed' => 0, 'volume' => 0];
    
    // 計算最終參數
    $pitch = max(0.1, min(1.0, $base_pitch + $adjustment['pitch']));
    $speed = max(0.1, min(1.0, $base_speed + $adjustment['speed']));
    $volume = max(0.1, min(1.0, $base_volume + $adjustment['volume']));
    
    // 情感映射
    $emotions = [
        'mysterious' => '神秘可愛',
        'cute' => '溫柔甜美',
        'mature' => '成熟穩重',
        'dangerous' => '危險迷人'
    ];
    
    return [
        'text' => $text,
        'pitch' => round($pitch, 3),
        'speed' => round($speed, 3),
        'volume' => round($volume, 3),
        'emotion' => $emotions[$style] ?? '神秘可愛',
        'style_score' => round($style_score, 3),
        'style' => $style,
        'character' => '時穎白',
        'language' => '日語',
        'model_version' => '1.0',
        'generated_at' => date('Y-m-d H:i:s')
    ];
}

function generateTTSAudio($text, $voice_params) {
    /**
     * 生成 TTS 音頻（整合 Edge-TTS）
     */
    
    try {
        // 設置輸出目錄
        $output_dir = 'C:/Topics/Topics-frontend/frontend/assets/voice/';
        if (!is_dir($output_dir)) {
            mkdir($output_dir, 0777, true);
        }
        
        // 生成檔案名
        $filename = '時穎白_' . time() . '_' . substr(md5($text), 0, 8) . '.wav';
        $output_path = $output_dir . $filename;
        
        // 使用 Edge-TTS 生成音頻
        $voice = 'ja-JP-NanamiNeural'; // 日語語音
        
        // 根據參數調整語音
        $rate = round(($voice_params['speed'] - 0.5) * 100); // 轉換為百分比
        $pitch = round(($voice_params['pitch'] - 0.5) * 200); // 轉換為 Hz
        $volume = round(($voice_params['volume'] - 0.5) * 100); // 轉換為百分比
        
        // 創建臨時文字檔案
        $temp_file = tempnam(sys_get_temp_dir(), 'shiyinbai_');
        file_put_contents($temp_file, $text, LOCK_EX);
        
        // Edge-TTS 命令
        $cmd = sprintf(
            'python -m edge_tts --voice "%s" --file "%s" --write-media "%s" --rate %+d%% --pitch %+dHz --volume %+d%% 2>&1',
            $voice,
            $temp_file,
            $output_path,
            $rate,
            $pitch,
            $volume
        );
        
        // 執行命令
        $output = [];
        $return_code = 0;
        exec($cmd, $output, $return_code);
        
        // 清理臨時檔案
        unlink($temp_file);
        
        if ($return_code === 0 && file_exists($output_path)) {
            return [
                'success' => true,
                'audio_url' => 'http://localhost/Topics-frontend/frontend/assets/voice/' . $filename,
                'filename' => $filename,
                'file_size' => filesize($output_path)
            ];
        } else {
            return [
                'success' => false,
                'error' => 'TTS 生成失敗',
                'command_output' => implode("\n", $output)
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'TTS 生成錯誤: ' . $e->getMessage()
        ];
    }
}

// 主處理邏輯
try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'POST') {
        // 處理語音生成請求
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['text'])) {
            throw new Exception('缺少必要參數: text');
        }
        
        $text = $input['text'];
        $style = $input['style'] ?? 'mysterious';
        $generate_audio = $input['generate_audio'] ?? false;
        
        // 載入模型
        $model = loadModel($model_path);
        $config = loadConfig($config_path);
        
        // 生成語音參數
        $voice_params = generateVoiceParameters($text, $style, $model);
        
        $response = [
            'success' => true,
            'voice_params' => $voice_params,
            'model_info' => [
                'name' => '時穎白',
                'version' => '1.0',
                'style' => '時崎狂三',
                'language' => '日語',
                'character' => '神秘可愛'
            ]
        ];
        
        // 如果需要生成音頻
        if ($generate_audio) {
            $audio_result = generateTTSAudio($text, $voice_params);
            $response['audio'] = $audio_result;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } elseif ($method === 'GET') {
        // 處理資訊請求
        $model = loadModel($model_path);
        $config = loadConfig($config_path);
        
        $response = [
            'success' => true,
            'model_info' => [
                'name' => '時穎白',
                'version' => '1.0',
                'style' => '時崎狂三',
                'language' => '日語',
                'character' => '神秘可愛',
                'model_loaded' => $model !== null,
                'config_loaded' => $config !== null
            ],
            'available_styles' => [
                'mysterious' => '神秘可愛',
                'cute' => '溫柔甜美',
                'mature' => '成熟穩重',
                'dangerous' => '危險迷人'
            ],
            'usage' => [
                'method' => 'POST',
                'parameters' => [
                    'text' => '要轉換的文字',
                    'style' => '語音風格 (mysterious/cute/mature/dangerous)',
                    'generate_audio' => '是否生成音頻 (true/false)'
                ]
            ]
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } else {
        throw new Exception('不支援的請求方法: ' . $method);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

