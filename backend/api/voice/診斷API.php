<?php
/**
 * 系統診斷 API
 * 檢查時穎白語音系統的完整狀態
 */

// 關閉錯誤顯示
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // 檢查路徑
    $paths = [
        'D:/RVC' => 'RVC 主目錄',
        'D:/RVC/時穎白語音模型' => '時穎白模型目錄',
        'D:/RVC/時穎白語音模型/訓練資料' => '訓練資料目錄',
        'D:/RVC/時穎白語音模型/訓練資料/models' => '模型儲存目錄',
        'D:/RVC/時穎白語音模型/訓練資料/audio' => '音頻目錄'
    ];
    
    $path_status = [];
    foreach ($paths as $path => $description) {
        $exists = is_dir($path);
        $path_status[$description] = [
            'path' => $path,
            'exists' => $exists,
            'writable' => $exists ? is_writable($path) : false
        ];
    }
    
    // 檢查訓練影片
    $training_videos = [
        'D:/Downloads/Tokisakikurumi.mp4' => '主要訓練影片',
        'D:/Downloads/Tokisakikurumi02.mp4' => '輔助訓練影片'
    ];
    
    $video_status = [];
    foreach ($training_videos as $path => $description) {
        $exists = file_exists($path);
        $video_status[$description] = [
            'path' => $path,
            'exists' => $exists,
            'size' => $exists ? filesize($path) : 0,
            'readable' => $exists ? is_readable($path) : false
        ];
    }
    
    // 檢查音頻檔案
    $audio_dir = 'D:/RVC/時穎白語音模型/訓練資料/audio';
    $audio_files = [];
    if (is_dir($audio_dir)) {
        $files = scandir($audio_dir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'wav') {
                $file_path = $audio_dir . '/' . $file;
                $audio_files[] = [
                    'name' => $file,
                    'path' => $file_path,
                    'size' => filesize($file_path),
                    'modified' => date('Y-m-d H:i:s', filemtime($file_path))
                ];
            }
        }
    }
    
    // 檢查模型檔案
    $model_path = 'D:/RVC/時穎白語音模型/訓練資料/models/時穎白.pth';
    $model_status = [
        'path' => $model_path,
        'exists' => file_exists($model_path),
        'size' => file_exists($model_path) ? filesize($model_path) : 0,
        'modified' => file_exists($model_path) ? date('Y-m-d H:i:s', filemtime($model_path)) : null
    ];
    
    // 檢查 Python 環境
    $python_status = [];
    $python_commands = [
        'python --version' => 'Python 版本',
        'pip list | findstr torch' => 'PyTorch 安裝',
        'pip list | findstr librosa' => 'Librosa 安裝',
        'pip list | findstr numpy' => 'NumPy 安裝'
    ];
    
    foreach ($python_commands as $command => $description) {
        $output = [];
        $return_code = 0;
        exec($command . ' 2>&1', $output, $return_code);
        
        $python_status[$description] = [
            'command' => $command,
            'success' => $return_code === 0,
            'output' => implode("\n", $output)
        ];
    }
    
    // 檢查 FFmpeg
    $ffmpeg_status = [];
    $ffmpeg_commands = [
        'ffmpeg -version' => 'FFmpeg 版本',
        'ffmpeg -f lavfi -i testsrc=duration=1:size=320x240:rate=1 -t 1 -f null -' => 'FFmpeg 功能測試'
    ];
    
    foreach ($ffmpeg_commands as $command => $description) {
        $output = [];
        $return_code = 0;
        exec($command . ' 2>&1', $output, $return_code);
        
        $ffmpeg_status[$description] = [
            'command' => $command,
            'success' => $return_code === 0,
            'output' => implode("\n", array_slice($output, 0, 3)) // 只取前3行
        ];
    }
    
    // 計算系統就緒狀態
    $videos_available = count(array_filter($video_status, function($status) { return $status['exists']; }));
    $audio_available = count($audio_files);
    $model_available = $model_status['exists'];
    
    $system_ready = [
        'training_data_ready' => $videos_available > 0 || $audio_available > 0,
        'model_ready' => $model_available,
        'python_ready' => $python_status['Python 版本']['success'],
        'ffmpeg_ready' => $ffmpeg_status['FFmpeg 版本']['success'],
        'overall_ready' => ($videos_available > 0 || $audio_available > 0) && $model_available
    ];
    
    $response = [
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'system_info' => [
            'name' => '時穎白語音系統診斷',
            'version' => '1.0',
            'overall_status' => $system_ready['overall_ready'] ? '✅ 就緒' : '⏳ 準備中'
        ],
        'paths' => $path_status,
        'training_videos' => $video_status,
        'audio_files' => $audio_files,
        'model_status' => $model_status,
        'python_environment' => $python_status,
        'ffmpeg_status' => $ffmpeg_status,
        'readiness' => $system_ready,
        'summary' => [
            'videos_available' => $videos_available,
            'audio_files_count' => $audio_available,
            'model_exists' => $model_available,
            'ready_for_training' => $system_ready['training_data_ready'],
            'ready_for_inference' => $system_ready['model_ready']
        ],
        'recommendations' => generateRecommendations($system_ready, $video_status, $audio_files, $model_status)
    ];
    
    ob_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}

function generateRecommendations($system_ready, $video_status, $audio_files, $model_status) {
    $recommendations = [];
    
    if (!$system_ready['training_data_ready']) {
        $recommendations[] = '📁 請確保 Tokisakikurumi.mp4 檔案存在於 D:/Downloads/ 目錄中';
    }
    
    if (count($audio_files) === 0 && $system_ready['training_data_ready']) {
        $recommendations[] = '🎵 需要從影片中提取音頻，請點擊「提取訓練音頻」按鈕';
    }
    
    if (!$system_ready['model_ready']) {
        $recommendations[] = '🤖 需要訓練 RVC 模型，請點擊「訓練 RVC 模型」按鈕';
    }
    
    if (!$system_ready['python_ready']) {
        $recommendations[] = '🐍 請安裝 Python 環境';
    }
    
    if (!$system_ready['ffmpeg_ready']) {
        $recommendations[] = '🎬 請安裝 FFmpeg';
    }
    
    if (empty($recommendations)) {
        $recommendations[] = '✅ 系統已準備就緒，可以開始使用！';
    }
    
    return $recommendations;
}
?>

