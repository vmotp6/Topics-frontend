<?php
/**
 * 系統修復 API
 * 自動診斷和修復時穎白語音系統問題
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
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // 系統診斷
        $diagnosis = performSystemDiagnosis();
        
        $response = [
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'diagnosis' => $diagnosis,
            'recommendations' => generateRepairRecommendations($diagnosis)
        ];
        
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? 'diagnose';
        
        switch ($action) {
            case 'extract_audio':
                $result = extractTrainingAudio();
                break;
            case 'create_directories':
                $result = createRequiredDirectories();
                break;
            case 'check_dependencies':
                $result = checkSystemDependencies();
                break;
            case 'repair_system':
                $result = repairSystem();
                break;
            default:
                $result = performSystemDiagnosis();
        }
        
        $response = [
            'success' => true,
            'action' => $action,
            'result' => $result,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
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

function performSystemDiagnosis() {
    $issues = [];
    $fixes = [];
    
    // 檢查路徑
    $required_paths = [
        'D:/RVC' => 'RVC 主目錄',
        'D:/RVC/時穎白語音模型' => '時穎白模型目錄',
        'D:/RVC/時穎白語音模型/訓練資料' => '訓練資料目錄',
        'D:/RVC/時穎白語音模型/訓練資料/models' => '模型儲存目錄',
        'D:/RVC/時穎白語音模型/訓練資料/audio' => '音頻目錄'
    ];
    
    foreach ($required_paths as $path => $description) {
        if (!is_dir($path)) {
            $issues[] = "❌ {$description} 不存在: {$path}";
            $fixes[] = "mkdir -p \"{$path}\"";
        }
    }
    
    // 檢查訓練影片
    $training_videos = [
        'D:/Downloads/Tokisakikurumi.mp4' => '主要訓練影片',
        'D:/Downloads/Tokisakikurumi02.mp4' => '輔助訓練影片'
    ];
    
    $videos_found = 0;
    foreach ($training_videos as $path => $description) {
        if (file_exists($path)) {
            $videos_found++;
        } else {
            $issues[] = "❌ {$description} 不存在: {$path}";
        }
    }
    
    // 檢查音頻檔案
    $audio_dir = 'D:/RVC/時穎白語音模型/訓練資料/audio';
    $audio_files = [];
    if (is_dir($audio_dir)) {
        $files = scandir($audio_dir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'wav') {
                $audio_files[] = $file;
            }
        }
    }
    
    // 檢查模型檔案
    $model_path = 'D:/RVC/時穎白語音模型/訓練資料/models/時穎白.pth';
    $model_exists = file_exists($model_path);
    
    // 檢查 Python 環境
    $python_available = false;
    $python_output = [];
    $python_return = 0;
    exec('python --version 2>&1', $python_output, $python_return);
    if ($python_return === 0) {
        $python_available = true;
    } else {
        $issues[] = "❌ Python 未安裝或不在 PATH 中";
    }
    
    // 檢查 FFmpeg
    $ffmpeg_available = false;
    $ffmpeg_output = [];
    $ffmpeg_return = 0;
    exec('ffmpeg -version 2>&1', $ffmpeg_output, $ffmpeg_return);
    if ($ffmpeg_return === 0) {
        $ffmpeg_available = true;
    } else {
        $issues[] = "❌ FFmpeg 未安裝或不在 PATH 中";
    }
    
    return [
        'issues' => $issues,
        'fixes' => $fixes,
        'status' => [
            'videos_available' => $videos_found,
            'audio_files_count' => count($audio_files),
            'model_exists' => $model_exists,
            'python_available' => $python_available,
            'ffmpeg_available' => $ffmpeg_available,
            'training_data_ready' => $videos_found > 0 || count($audio_files) > 0,
            'model_ready' => $model_exists
        ],
        'paths' => $required_paths,
        'training_videos' => $training_videos,
        'audio_files' => $audio_files
    ];
}

function extractTrainingAudio() {
    $results = [];
    $training_videos = [
        'D:/Downloads/Tokisakikurumi.mp4' => 'D:/RVC/時穎白語音模型/訓練資料/audio/training_1.wav',
        'D:/Downloads/Tokisakikurumi02.mp4' => 'D:/RVC/時穎白語音模型/訓練資料/audio/training_2.wav'
    ];
    
    // 確保音頻目錄存在
    $audio_dir = 'D:/RVC/時穎白語音模型/訓練資料/audio';
    if (!is_dir($audio_dir)) {
        mkdir($audio_dir, 0777, true);
    }
    
    foreach ($training_videos as $video_path => $audio_path) {
        if (file_exists($video_path)) {
            $command = "ffmpeg -i \"{$video_path}\" -ar 44100 -ac 1 \"{$audio_path}\" 2>&1";
            $output = [];
            $return_code = 0;
            exec($command, $output, $return_code);
            
            $results[] = [
                'video' => basename($video_path),
                'audio' => basename($audio_path),
                'success' => $return_code === 0 && file_exists($audio_path),
                'output' => implode("\n", $output)
            ];
        }
    }
    
    return $results;
}

function createRequiredDirectories() {
    $directories = [
        'D:/RVC',
        'D:/RVC/時穎白語音模型',
        'D:/RVC/時穎白語音模型/訓練資料',
        'D:/RVC/時穎白語音模型/訓練資料/models',
        'D:/RVC/時穎白語音模型/訓練資料/audio',
        'D:/RVC/時穎白語音模型/訓練資料/logs'
    ];
    
    $results = [];
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            $created = mkdir($dir, 0777, true);
            $results[] = [
                'directory' => $dir,
                'created' => $created,
                'status' => $created ? '✅ 已創建' : '❌ 創建失敗'
            ];
        } else {
            $results[] = [
                'directory' => $dir,
                'created' => false,
                'status' => '✅ 已存在'
            ];
        }
    }
    
    return $results;
}

function checkSystemDependencies() {
    $dependencies = [
        'python' => 'python --version',
        'ffmpeg' => 'ffmpeg -version',
        'pip' => 'pip --version'
    ];
    
    $results = [];
    foreach ($dependencies as $name => $command) {
        $output = [];
        $return_code = 0;
        exec($command . ' 2>&1', $output, $return_code);
        
        $results[$name] = [
            'available' => $return_code === 0,
            'version' => $return_code === 0 ? implode(' ', $output) : '未安裝',
            'command' => $command
        ];
    }
    
    return $results;
}

function repairSystem() {
    $repair_steps = [];
    
    // 步驟1: 創建必要目錄
    $dir_result = createRequiredDirectories();
    $repair_steps[] = [
        'step' => '創建目錄結構',
        'result' => $dir_result,
        'success' => true
    ];
    
    // 步驟2: 檢查依賴
    $dep_result = checkSystemDependencies();
    $repair_steps[] = [
        'step' => '檢查系統依賴',
        'result' => $dep_result,
        'success' => true
    ];
    
    // 步驟3: 提取音頻（如果有影片）
    $audio_result = extractTrainingAudio();
    $repair_steps[] = [
        'step' => '提取訓練音頻',
        'result' => $audio_result,
        'success' => true
    ];
    
    return $repair_steps;
}

function generateRepairRecommendations($diagnosis) {
    $recommendations = [];
    
    if (empty($diagnosis['issues'])) {
        $recommendations[] = "✅ 系統狀態良好，無需修復";
        return $recommendations;
    }
    
    if (count($diagnosis['status']['videos_available']) === 0) {
        $recommendations[] = "📁 請將 Tokisakikurumi.mp4 和 Tokisakikurumi02.mp4 放入 D:/Downloads/ 目錄";
    }
    
    if (!$diagnosis['status']['python_available']) {
        $recommendations[] = "🐍 請安裝 Python 3.8+ 並確保在 PATH 中";
    }
    
    if (!$diagnosis['status']['ffmpeg_available']) {
        $recommendations[] = "🎵 請安裝 FFmpeg 並確保在 PATH 中";
    }
    
    if (!$diagnosis['status']['model_ready']) {
        $recommendations[] = "🤖 需要訓練 RVC 模型，請使用訓練功能";
    }
    
    return $recommendations;
}
?>
