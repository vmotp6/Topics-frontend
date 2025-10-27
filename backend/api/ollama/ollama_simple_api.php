<?php
/**
 * 簡化版 Ollama API - 不依賴資料庫
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 引入Ollama服務
require_once 'ollama_service.php';

// 使用真實的 Ollama 服務
try {
    $ollama = new OllamaService('http://localhost:11434', 'qwen2.5:3b');
} catch (Exception $e) {
    // 如果服務不可用，返回錯誤
    echo json_encode(['error' => 'Ollama 服務不可用: ' . $e->getMessage()]);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'ask_question':
        handleAskQuestion($ollama);
        break;
    case 'check_health':
        checkHealth($ollama);
        break;
    case 'get_models':
        getModels($ollama);
        break;
    default:
        echo json_encode(['error' => '無效的操作']);
        break;
}

function handleAskQuestion($ollama) {
    $question = trim($_POST['question'] ?? '');
    $model = trim($_POST['model'] ?? 'qwen2.5:3b');
    $use_context = ($_POST['use_context'] ?? 'false') === 'true';
    
    if (empty($question)) {
        echo json_encode(['error' => '請輸入問題']);
        return;
    }
    
    $start_time = microtime(true);
    
    try {
        // 簡化版：不使用資料庫上下文，直接使用AI回答
        $context = '';
        
        $result = $ollama->askQuestion($question, $context, $model);
        
        if ($result['success']) {
            $response_time = round((microtime(true) - $start_time) * 1000);
            
            echo json_encode([
                'success' => true,
                'answer' => $result['answer'],
                'model' => $result['model'],
                'context_used' => false, // 簡化版不使用上下文
                'response_time_ms' => $response_time
            ]);
        } else {
            echo json_encode(['error' => $result['error']]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['error' => '處理問題時發生錯誤: ' . $e->getMessage()]);
    }
}

function checkHealth($ollama) {
    $is_healthy = $ollama->checkHealth();
    
    if ($is_healthy) {
        $models = $ollama->getModels();
        echo json_encode([
            'success' => true,
            'status' => 'healthy',
            'models' => $models
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'status' => 'unhealthy',
            'message' => 'Ollama服務未運行或無法連接'
        ]);
    }
}

function getModels($ollama) {
    $models = $ollama->getModels();
    echo json_encode([
        'success' => true,
        'models' => $models
    ]);
}
?>
