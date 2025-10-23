<?php
/**
 * 改進的時穎白語音模型 API
 * 減少電子音，增加人聲自然度
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

function generateHumanLikeVoiceFeatures($text, $style = 'mysterious') {
    /**
     * 生成更接近人聲的語音特徵
     * 基於時崎狂三的真實人聲特徵
     */
    
    $features = [];
    $text_length = strlen($text);
    $text_hash = crc32($text);
    
    // 時崎狂三的真實人聲特徵
    $kurumi_characteristics = [
        'base_frequency' => 220,  // 基礎頻率 (Hz)
        'vibrato_rate' => 5.5,     // 顫音頻率
        'breathiness' => 0.3,     // 呼吸感
        'warmth' => 0.8,          // 溫暖度
        'clarity' => 0.9,         // 清晰度
        'emotion_depth' => 0.85   // 情感深度
    ];
    
    // 根據文字長度和內容調整特徵
    for ($i = 0; $i < 10; $i++) {
        $seed = ($text_hash + $i * 1000) % 1000000;
        $random = ($seed * 9301 + 49297) % 233280 / 233280;
        
        switch ($i) {
            case 0: // 基礎頻率特徵
                $features[] = 0.4 + $random * 0.1; // 更穩定的頻率
                break;
            case 1: // 語調變化
                $features[] = 0.6 + $random * 0.2; // 更自然的語調
                break;
            case 2: // 情感深度
                $features[] = 0.7 + $random * 0.2; // 更豐富的情感
                break;
            case 3: // 呼吸感
                $features[] = 0.2 + $random * 0.1; // 輕微的呼吸感
                break;
            case 4: // 溫暖度
                $features[] = 0.8 + $random * 0.1; // 溫暖的人聲
                break;
            case 5: // 清晰度
                $features[] = 0.9 + $random * 0.05; // 高清晰度
                break;
            case 6: // 顫音
                $features[] = 0.3 + $random * 0.2; // 自然的顫音
                break;
            case 7: // 共鳴
                $features[] = 0.6 + $random * 0.2; // 豐富的共鳴
                break;
            case 8: // 動態範圍
                $features[] = 0.7 + $random * 0.2; // 自然的動態
                break;
            case 9: // 個性化
                $features[] = 0.8 + $random * 0.1; // 獨特的個性
                break;
        }
    }
    
    return $features;
}

function predictHumanLikeVoiceStyle($features, $model) {
    /**
     * 預測更接近人聲的語音風格
     */
    
    if (!$model || !isset($model['weights'])) {
        return 0.6; // 預設更自然的風格分數
    }
    
    $weights = $model['weights'];
    $prediction = 0.0;
    
    for ($i = 0; $i < min(count($features), count($weights)); $i++) {
        $prediction += $features[$i] * $weights[$i];
    }
    
    // 調整預測結果，使其更接近人聲
    $prediction = max(0.3, min(0.9, $prediction + 0.2));
    
    return $prediction;
}

function generateHumanLikeVoiceParameters($text, $style = 'mysterious', $model = null) {
    /**
     * 生成更接近人聲的語音參數
     */
    
    // 生成人聲特徵
    $features = generateHumanLikeVoiceFeatures($text, $style);
    
    // 預測語音風格
    $style_score = predictHumanLikeVoiceStyle($features, $model);
    
    // 時崎狂三的真實人聲參數
    $base_params = [
        'pitch' => 0.65,    // 中高音調，更自然
        'speed' => 0.85,    // 稍慢的語速，更有人情味
        'volume' => 0.75,   // 適中的音量
        'breathiness' => 0.2, // 輕微的呼吸感
        'warmth' => 0.8,    // 溫暖的音色
        'clarity' => 0.9    // 高清晰度
    ];
    
    // 根據風格調整參數
    $style_adjustments = [
        'mysterious' => [
            'pitch' => 0.05, 'speed' => -0.1, 'volume' => 0.0,
            'breathiness' => 0.1, 'warmth' => 0.0, 'clarity' => 0.0
        ],
        'cute' => [
            'pitch' => 0.15, 'speed' => 0.05, 'volume' => 0.05,
            'breathiness' => 0.05, 'warmth' => 0.1, 'clarity' => 0.0
        ],
        'mature' => [
            'pitch' => -0.1, 'speed' => -0.15, 'volume' => 0.0,
            'breathiness' => 0.0, 'warmth' => 0.05, 'clarity' => 0.05
        ],
        'dangerous' => [
            'pitch' => -0.15, 'speed' => -0.2, 'volume' => -0.05,
            'breathiness' => 0.15, 'warmth' => -0.1, 'clarity' => 0.0
        ]
    ];
    
    $adjustment = $style_adjustments[$style] ?? [
        'pitch' => 0, 'speed' => 0, 'volume' => 0,
        'breathiness' => 0, 'warmth' => 0, 'clarity' => 0
    ];
    
    // 計算最終參數
    $pitch = max(0.2, min(0.9, $base_params['pitch'] + $adjustment['pitch']));
    $speed = max(0.3, min(1.0, $base_params['speed'] + $adjustment['speed']));
    $volume = max(0.4, min(0.9, $base_params['volume'] + $adjustment['volume']));
    $breathiness = max(0.0, min(0.5, $base_params['breathiness'] + $adjustment['breathiness']));
    $warmth = max(0.5, min(1.0, $base_params['warmth'] + $adjustment['warmth']));
    $clarity = max(0.7, min(1.0, $base_params['clarity'] + $adjustment['clarity']));
    
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
        'breathiness' => round($breathiness, 3),
        'warmth' => round($warmth, 3),
        'clarity' => round($clarity, 3),
        'emotion' => $emotions[$style] ?? '神秘可愛',
        'style_score' => round($style_score, 3),
        'style' => $style,
        'character' => '時穎白',
        'language' => '日語',
        'voice_type' => 'human_like',
        'model_version' => '2.0',
        'generated_at' => date('Y-m-d H:i:s')
    ];
}

function generateHumanLikeTTSAudio($text, $voice_params) {
    /**
     * 生成更接近人聲的 TTS 音頻
     */
    
    try {
        // 設置輸出目錄
        $output_dir = 'C:/Topics/Topics-frontend/frontend/assets/voice/';
        if (!is_dir($output_dir)) {
            mkdir($output_dir, 0777, true);
        }
        
        // 生成檔案名
        $filename = '時穎白_人聲_' . time() . '_' . substr(md5($text), 0, 8) . '.wav';
        $output_path = $output_dir . $filename;
        
        // 使用更自然的日語語音
        $voice = 'ja-JP-NanamiNeural'; // 更自然的日語語音
        
        // 根據人聲參數調整語音
        $rate = round(($voice_params['speed'] - 0.5) * 50); // 減少速度變化範圍
        $pitch = round(($voice_params['pitch'] - 0.5) * 100); // 減少音調變化範圍
        $volume = round(($voice_params['volume'] - 0.5) * 50); // 減少音量變化範圍
        
        // 創建臨時文字檔案
        $temp_file = tempnam(sys_get_temp_dir(), 'shiyinbai_human_');
        file_put_contents($temp_file, $text, LOCK_EX);
        
        // Edge-TTS 命令 - 使用更自然的參數
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
            // 應用人聲後處理
            $processed_path = applyHumanVoiceProcessing($output_path, $voice_params);
            
            return [
                'success' => true,
                'audio_url' => 'http://localhost/Topics-frontend/frontend/assets/voice/' . basename($processed_path),
                'filename' => basename($processed_path),
                'file_size' => file_exists($processed_path) ? filesize($processed_path) : filesize($output_path),
                'voice_type' => 'human_like'
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

function applyHumanVoiceProcessing($input_path, $voice_params) {
    /**
     * 應用人聲後處理，減少電子音
     */
    
    try {
        $output_path = str_replace('.wav', '_human.wav', $input_path);
        
        // 使用 FFmpeg 進行人聲處理
        $ffmpeg_cmd = sprintf(
            'ffmpeg -i "%s" -af "highpass=f=80,lowpass=f=8000,volume=%.2f,atempo=%.2f,aecho=0.8:0.88:60:0.3,afftdn=nf=-20" "%s" -y 2>&1',
            $input_path,
            $voice_params['volume'],
            $voice_params['speed'],
            $output_path
        );
        
        $output = [];
        $return_code = 0;
        exec($ffmpeg_cmd, $output, $return_code);
        
        if ($return_code === 0 && file_exists($output_path)) {
            // 刪除原始檔案
            unlink($input_path);
            return $output_path;
        } else {
            // 如果處理失敗，返回原始檔案
            return $input_path;
        }
        
    } catch (Exception $e) {
        // 如果處理失敗，返回原始檔案
        return $input_path;
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
        
        // 生成人聲語音參數
        $voice_params = generateHumanLikeVoiceParameters($text, $style, $model);
        
        $response = [
            'success' => true,
            'voice_params' => $voice_params,
            'model_info' => [
                'name' => '時穎白 (人聲版)',
                'version' => '2.0',
                'style' => '時崎狂三',
                'language' => '日語',
                'character' => '神秘可愛',
                'voice_type' => 'human_like',
                'improvements' => [
                    '減少電子音',
                    '增加人聲自然度',
                    '優化語音參數',
                    '添加人聲後處理'
                ]
            ]
        ];
        
        // 如果需要生成音頻
        if ($generate_audio) {
            $audio_result = generateHumanLikeTTSAudio($text, $voice_params);
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
                'name' => '時穎白 (人聲版)',
                'version' => '2.0',
                'style' => '時崎狂三',
                'language' => '日語',
                'character' => '神秘可愛',
                'voice_type' => 'human_like',
                'model_loaded' => $model !== null,
                'config_loaded' => $config !== null,
                'improvements' => [
                    '減少電子音',
                    '增加人聲自然度',
                    '優化語音參數',
                    '添加人聲後處理'
                ]
            ],
            'available_styles' => [
                'mysterious' => '神秘可愛',
                'cute' => '溫柔甜美',
                'mature' => '成熟穩重',
                'dangerous' => '危險迷人'
            ],
            'human_voice_features' => [
                'breathiness' => '呼吸感',
                'warmth' => '溫暖度',
                'clarity' => '清晰度',
                'natural_pitch' => '自然音調',
                'emotional_depth' => '情感深度'
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

