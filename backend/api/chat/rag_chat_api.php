<?php
/**
 * RAG 聊天 API
 * 使用檢索增強生成（RAG）方式回答問題
 * 1. 從資料庫中搜索相似的資料
 * 2. 如果找到相似資料，使用這些資料作為上下文
 * 3. 如果沒有找到，使用 AI 自動回答
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理 OPTIONS 請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once dirname(__DIR__, 3) . '/frontend/config.php';

// 載入 AI 服務
require_once __DIR__ . '/rag_ai_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => '只支援 POST 請求']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['message'])) {
        echo json_encode(['error' => '請提供訊息內容']);
        exit;
    }
    
    $question = trim($data['message']);
    if (empty($question)) {
        echo json_encode(['error' => '訊息內容不能為空']);
        exit;
    }
    
    // 初始化 RAG 服務
    $ragService = new RAGChatService();
    
    // 處理問題並獲取回答
    $result = $ragService->processQuestion($question);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("RAG Chat API 錯誤: " . $e->getMessage());
    echo json_encode([
        'error' => '處理請求時發生錯誤',
        'message' => $e->getMessage()
    ]);
}
?>

