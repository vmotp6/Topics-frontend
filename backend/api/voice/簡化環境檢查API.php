<?php
/**
 * 簡化環境檢查 API
 * 修正 JSON 解析問題
 */

// 關閉錯誤顯示
error_reporting(0);
ini_set('display_errors', 0);

// 設置輸出緩衝
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? 'check_environment';
        
        if ($action === 'check_environment') {
            // 簡化的環境檢查
            $checks = [];
            
            // 檢查 Python
            $python_output = [];
            $python_return = 0;
            exec('python --version 2>&1', $python_output, $python_return);
            $checks['python'] = [
                'available' => $python_return === 0,
                'version' => implode(' ', $python_output),
                'command' => 'python --version'
            ];
            
            // 檢查 FFmpeg
            $ffmpeg_output = [];
            $ffmpeg_return = 0;
            exec('ffmpeg -version 2>&1', $ffmpeg_output, $ffmpeg_return);
            $checks['ffmpeg'] = [
                'available' => $ffmpeg_return === 0,
                'version' => isset($ffmpeg_output[0]) ? $ffmpeg_output[0] : 'Unknown',
                'command' => 'ffmpeg -version'
            ];
            
            // 檢查 Python 套件
            $packages = ['torch', 'numpy', 'librosa'];
            $package_checks = [];
            
            foreach ($packages as $package) {
                $output = [];
                $return = 0;
                exec("python -c \"import $package; print($package.__version__)\" 2>&1", $output, $return);
                
                $package_checks[$package] = [
                    'available' => $return === 0,
                    'version' => $return === 0 ? (isset($output[0]) ? $output[0] : 'Unknown') : 'Not installed',
                    'error' => $return !== 0 ? implode(' ', $output) : null
                ];
            }
            
            $checks['packages'] = $package_checks;
            
            // 檢查路徑
            $rvc_path = 'D:/RVC';
            $training_data_path = $rvc_path . '/時穎白語音模型/訓練資料';
            $model_path = $training_data_path . '/models';
            $audio_path = $training_data_path . '/audio';
            
            $path_checks = [
                'rvc_path' => is_dir($rvc_path),
                'training_data_path' => is_dir($training_data_path),
                'model_path' => is_dir($model_path),
                'audio_path' => is_dir($audio_path)
            ];
            
            $checks['paths'] = $path_checks;
            
            // 檢查訓練影片
            $video_checks = [
                'Tokisakikurumi.mp4' => file_exists('D:/Downloads/Tokisakikurumi.mp4'),
                'Tokisakikurumi02.mp4' => file_exists('D:/Downloads/Tokisakikurumi02.mp4')
            ];
            
            $checks['videos'] = $video_checks;
            
            $result = [
                'success' => true,
                'python' => $checks['python'],
                'ffmpeg' => $checks['ffmpeg'],
                'packages' => $checks['packages'],
                'paths' => $checks['paths'],
                'videos' => $checks['videos'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } else {
            $result = [
                'success' => false,
                'error' => '未知操作: ' . $action
            ];
        }
        
        // 清除輸出緩衝並輸出 JSON
        ob_clean();
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } elseif ($method === 'GET') {
        // 執行環境檢查
        $checks = [];
        
        // 檢查 Python
        $python_output = [];
        $python_return = 0;
        exec('python --version 2>&1', $python_output, $python_return);
        $checks['python'] = [
            'available' => $python_return === 0,
            'version' => implode(' ', $python_output),
            'command' => 'python --version'
        ];
        
        // 檢查 FFmpeg
        $ffmpeg_output = [];
        $ffmpeg_return = 0;
        exec('ffmpeg -version 2>&1', $ffmpeg_output, $ffmpeg_return);
        $checks['ffmpeg'] = [
            'available' => $ffmpeg_return === 0,
            'version' => isset($ffmpeg_output[0]) ? $ffmpeg_output[0] : 'Unknown',
            'command' => 'ffmpeg -version'
        ];
        
        // 檢查路徑
        $rvc_path = 'D:/RVC';
        $training_data_path = $rvc_path . '/時穎白語音模型/訓練資料';
        $model_path = $training_data_path . '/models';
        $audio_path = $training_data_path . '/audio';
        
        $path_checks = [
            'rvc_path' => is_dir($rvc_path),
            'training_data_path' => is_dir($training_data_path),
            'model_path' => is_dir($model_path),
            'audio_path' => is_dir($audio_path)
        ];
        
        $checks['paths'] = $path_checks;
        
        // 檢查訓練影片
        $video_checks = [
            'Tokisakikurumi.mp4' => file_exists('D:/Downloads/Tokisakikurumi.mp4'),
            'Tokisakikurumi02.mp4' => file_exists('D:/Downloads/Tokisakikurumi02.mp4')
        ];
        
        $checks['videos'] = $video_checks;
        
        // 判斷整體狀態
        $training_data_ready = $path_checks['training_data_path'] || $path_checks['audio_path'];
        $model_ready = $path_checks['model_path'];
        $audio_processing_ready = $checks['ffmpeg']['available'];
        
        $response = [
            'success' => true,
            'message' => '環境檢查完成',
            'version' => '1.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'training_data_ready' => $training_data_ready,
            'model_ready' => $model_ready,
            'audio_processing_ready' => $audio_processing_ready,
            'python' => $checks['python'],
            'ffmpeg' => $checks['ffmpeg'],
            'paths' => $checks['paths'],
            'videos' => $checks['videos']
        ];
        
        // 清除輸出緩衝並輸出 JSON
        ob_clean();
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } else {
        throw new Exception('不支援的請求方法: ' . $method);
    }
    
} catch (Exception $e) {
    // 清除輸出緩衝
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?>

