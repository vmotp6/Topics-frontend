<?php
/**
 * 時崎狂三語音生成 API
 * 使用 RVC 方法生成時崎狂三風格的聲音
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 處理 POST 請求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    $text = $input['text'] ?? '';
    $voice = $input['voice'] ?? 'ja-JP-NanamiNeural'; // 使用日文語音更適合狂三
    
    if (empty($text)) {
        echo json_encode([
            'success' => false,
            'error' => '請提供要轉換的文字'
        ]);
        exit;
    }
    
    try {
        // 生成時崎狂三語音
        $result = generateKurumiVoice($text, $voice);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'filename' => $result['filename'],
                'file_path' => $result['file_path'],
                'size' => $result['size'],
                'message' => '時崎狂三語音生成成功！'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => $result['error']
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => '語音生成失敗: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => '不支援的請求方法'
    ]);
}

function generateKurumiVoice($text, $voice) {
    // 創建臨時檔案
    $temp_file = tempnam(sys_get_temp_dir(), 'kurumi_tts_');
    file_put_contents($temp_file, $text, LOCK_EX);
    
    // 確保輸出目錄存在
    $output_dir = __DIR__ . '/../../../frontend/assets/voice/';
    if (!is_dir($output_dir)) {
        mkdir($output_dir, 0755, true);
    }
    
    // 生成檔案名
    $filename = 'kurumi_' . time() . '_' . substr(md5($text), 0, 8) . '.wav';
    $output_path = $output_dir . $filename;
    
    // 步驟 1: 使用日文語音生成基礎語音（先不使用參數，後續用音頻處理調整）
    $style_params = '';
    
    $tts_cmd = sprintf(
        'python -m edge_tts --voice "%s" --file "%s" --write-media "%s"%s 2>&1',
        $voice,
        $temp_file,
        $output_path,
        $style_params
    );
    
    $tts_output = [];
    $tts_return_code = 0;
    exec($tts_cmd, $tts_output, $tts_return_code);
    
    if ($tts_return_code !== 0) {
        unlink($temp_file);
        return [
            'success' => false,
            'error' => 'TTS 生成失敗: ' . implode("\n", $tts_output)
        ];
    }
    
    // 步驟 1.5: 使用 FFmpeg 調整音頻參數，讓聲音更像時崎狂三
    $processed_path = $output_dir . 'kurumi_processed_' . time() . '.wav';
    
    // 根據不同語音調整參數
    $audio_filter = '';
    if (strpos($voice, 'ja-JP-NanamiNeural') !== false) {
        // Nanami - 調整為更溫柔但帶有神秘感的聲音（語速更慢，音調更高，更神秘）
        $audio_filter = 'atempo=0.88,asetrate=44100*1.12,aresample=44100';
    } elseif (strpos($voice, 'ja-JP-AoiNeural') !== false) {
        // Aoi - 調整為更年輕但成熟的聲音
        $audio_filter = 'atempo=0.90,asetrate=44100*1.10,aresample=44100';
    } elseif (strpos($voice, 'ja-JP-MayuNeural') !== false) {
        // Mayu - 調整為更成熟的聲音
        $audio_filter = 'atempo=0.85,asetrate=44100*1.08,aresample=44100';
    } else {
        // 預設調整
        $audio_filter = 'atempo=0.87,asetrate=44100*1.10,aresample=44100';
    }
    
    $ffmpeg_cmd = sprintf(
        'ffmpeg -i "%s" -af "%s" "%s" -y 2>&1',
        $output_path,
        $audio_filter,
        $processed_path
    );
    
    $ffmpeg_output = [];
    $ffmpeg_return_code = 0;
    exec($ffmpeg_cmd, $ffmpeg_output, $ffmpeg_return_code);
    
    if ($ffmpeg_return_code === 0 && file_exists($processed_path)) {
        // 使用處理後的音頻檔案
        unlink($output_path);
        rename($processed_path, $output_path);
    }
    
    // 步驟 2: 使用 RVC 轉換為時崎狂三聲音
    $rvc_path = 'D:/RVC/Retrieval-based-Voice-Conversion-WebUI-main';
    $model_path = $rvc_path . '/models/checkpoints/kurumi_model.pth';
    
    // 檢查 RVC 模型是否存在
    if (file_exists($model_path)) {
        $rvc_output_path = $output_dir . 'kurumi_rvc_' . time() . '.wav';
        
        $rvc_cmd = sprintf(
            'cd "%s" && python infer-web.py --input "%s" --model "%s" --output "%s" 2>&1',
            $rvc_path,
            $output_path,
            $model_path,
            $rvc_output_path
        );
        
        $rvc_output = [];
        $rvc_return_code = 0;
        exec($rvc_cmd, $rvc_output, $rvc_return_code);
        
        if ($rvc_return_code === 0 && file_exists($rvc_output_path)) {
            // 使用 RVC 轉換後的檔案
            unlink($output_path); // 刪除原始 TTS 檔案
            rename($rvc_output_path, $output_path);
        }
    }
    
    // 清理臨時檔案
    unlink($temp_file);
    
    if (file_exists($output_path)) {
        return [
            'success' => true,
            'filename' => $filename,
            'file_path' => $output_path,
            'size' => filesize($output_path)
        ];
    } else {
        return [
            'success' => false,
            'error' => '語音檔案生成失敗'
        ];
    }
}
?>
