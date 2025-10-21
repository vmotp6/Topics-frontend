<?php
session_start();
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

// 引入資料庫配置
require_once '../../config/ollama_config.php';

// 使用真實的 Ollama 服務
try {
    $ollama = new OllamaService('http://localhost:11434', 'tinyllama');
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
    case 'create_model':
        createModel($ollama);
        break;
    case 'delete_model':
        deleteModel($ollama);
        break;
    case 'upload_training_data':
        uploadTrainingData($ollama);
        break;
    case 'add_training_data':
        addTrainingData();
        break;
    case 'get_training_data':
        getTrainingData();
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
        // 先嘗試從訓練資料中直接匹配答案（針對特定問題）
        $direct_answer = getDirectAnswerFromTrainingData($question);
        if ($direct_answer) {
            $response_time = round((microtime(true) - $start_time) * 1000);
            
            echo json_encode([
                'success' => true,
                'answer' => $direct_answer,
                'model' => 'training_data_direct',
                'response_time_ms' => $response_time,
                'context_used' => true
            ]);
            return;
        }
        
        $context = '';
        if ($use_context) {
            // 從資料庫獲取相關上下文
            $context = getRelevantContext($question);
        }
        
        $result = $ollama->askQuestion($question, $context, $model);
        
        if ($result['success']) {
            $response_time = round((microtime(true) - $start_time) * 1000);
            
            // 記錄問答歷史
            saveQAHistory($question, $result['answer'], $model, $response_time);
            
            echo json_encode([
                'success' => true,
                'answer' => $result['answer'],
                'model' => $result['model'],
                'context_used' => $result['context_used'],
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

function createModel($ollama) {
    // 檢查管理員權限
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['error' => '權限不足']);
        return;
    }
    
    $model_name = trim($_POST['model_name'] ?? '');
    $training_data = $_POST['training_data'] ?? [];
    
    if (empty($model_name) || empty($training_data)) {
        echo json_encode(['error' => '模型名稱和訓練資料不能為空']);
        return;
    }
    
    try {
        $success = $ollama->createCustomModel($model_name, $training_data);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => '模型創建成功']);
        } else {
            echo json_encode(['error' => '模型創建失敗']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => '創建模型時發生錯誤: ' . $e->getMessage()]);
    }
}

function deleteModel($ollama) {
    // 檢查管理員權限
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['error' => '權限不足']);
        return;
    }
    
    $model_name = trim($_POST['model_name'] ?? '');
    
    if (empty($model_name)) {
        echo json_encode(['error' => '模型名稱不能為空']);
        return;
    }
    
    try {
        $success = $ollama->deleteModel($model_name);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => '模型刪除成功']);
        } else {
            echo json_encode(['error' => '模型刪除失敗']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => '刪除模型時發生錯誤: ' . $e->getMessage()]);
    }
}

function uploadTrainingData($ollama) {
    // 檢查管理員權限
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['error' => '權限不足']);
        return;
    }
    
    $data_type = $_POST['data_type'] ?? 'qa';
    $data_content = $_POST['data_content'] ?? '';
    
    if (empty($data_content)) {
        echo json_encode(['error' => '資料內容不能為空']);
        return;
    }
    
    try {
        // 解析上傳的資料
        $training_data = parseTrainingData($data_content, $data_type);
        
        // 保存到資料庫
        saveTrainingData($training_data);
        
        echo json_encode([
            'success' => true,
            'message' => '訓練資料上傳成功',
            'count' => count($training_data)
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => '上傳資料時發生錯誤: ' . $e->getMessage()]);
    }
}

function getRelevantContext($question) {
    try {
        $context = '';
        $question_lower = strtolower($question);
        
        // 只從 ollama 訓練資料庫獲取相關資料
        $training_data = getRelevantTrainingData($question);
        if (!empty($training_data)) {
            $context .= "=== 康寧大學相關資料 ===\n";
            foreach ($training_data as $data) {
                $context .= $data['content'] . "\n\n";
                
                // 限制總上下文長度
                if (strlen($context) > 2000) {
                    break;
                }
            }
        }
        
        return $context;
        
    } catch (Exception $e) {
        error_log("獲取上下文錯誤: " . $e->getMessage());
        return '';
    }
}

// 直接從訓練資料中匹配答案
function getDirectAnswerFromTrainingData($question) {
    try {
        $conn = getOllamaDatabaseConnection();
        $question_lower = strtolower($question);
        
        // 檢查是否問科系
        if (strpos($question_lower, '科系') !== false || strpos($question_lower, '科') !== false) {
            $sql = "SELECT content_data FROM ollama_training_data WHERE content_data LIKE '%科系%' ORDER BY created_at DESC LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $content_data = json_decode($row['content_data'], true);
                if ($content_data && isset($content_data['content'])) {
                    $stmt->close();
                    $conn->close();
                    return "康寧大學有以下科系：\n" . $content_data['content'];
                }
            }
            $stmt->close();
        }
        
        // 檢查是否問學費
        if (strpos($question_lower, '學費') !== false || strpos($question_lower, '費用') !== false) {
            $sql = "SELECT content_data FROM ollama_training_data WHERE content_data LIKE '%學費%' ORDER BY created_at DESC LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $content_data = json_decode($row['content_data'], true);
                if ($content_data && isset($content_data['answer'])) {
                    $stmt->close();
                    $conn->close();
                    return $content_data['answer'];
                }
            }
        }
        
        if (isset($stmt)) {
            $stmt->close();
        }
        $conn->close();
        return null;
        
    } catch (Exception $e) {
        error_log("直接匹配答案錯誤: " . $e->getMessage());
        return null;
    }
}

// 從訓練資料庫獲取相關資料
function getRelevantTrainingData($question) {
    try {
        $conn = getOllamaDatabaseConnection();
        $question_lower = strtolower($question);
        $keywords = explode(' ', $question_lower);
        
        // 構建 SQL 查詢，尋找包含關鍵詞的訓練資料
        $where_conditions = [];
        $params = [];
        $param_types = '';
        
        foreach ($keywords as $keyword) {
            if (strlen($keyword) > 2) {
                $where_conditions[] = "content_data LIKE ?";
                $params[] = '%' . $keyword . '%';
                $param_types .= 's';
            }
        }
        
        if (empty($where_conditions)) {
            // 如果沒有關鍵詞，返回最近的幾筆資料
            $sql = "SELECT content_data FROM ollama_training_data ORDER BY created_at DESC LIMIT 3";
            $stmt = $conn->prepare($sql);
        } else {
            $sql = "SELECT content_data FROM ollama_training_data WHERE " . implode(' OR ', $where_conditions) . " ORDER BY created_at DESC LIMIT 5";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($param_types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $training_data = [];
        while ($row = $result->fetch_assoc()) {
            $content_data = json_decode($row['content_data'], true);
            if ($content_data) {
                // 根據資料類型提取內容
                if (isset($content_data['type'])) {
                    switch ($content_data['type']) {
                        case 'text':
                            $training_data[] = [
                                'content' => $content_data['content'] ?? '',
                                'title' => $content_data['title'] ?? ''
                            ];
                            break;
                        case 'qa':
                            $training_data[] = [
                                'content' => "Q: " . ($content_data['question'] ?? '') . "\nA: " . ($content_data['answer'] ?? ''),
                                'title' => $content_data['title'] ?? ''
                            ];
                            break;
                        case 'file':
                            $training_data[] = [
                                'content' => $content_data['content'] ?? '',
                                'title' => $content_data['filename'] ?? $content_data['title'] ?? ''
                            ];
                            break;
                    }
                } else {
                    // 舊格式的資料
                    $training_data[] = [
                        'content' => $row['content_data'],
                        'title' => ''
                    ];
                }
            }
        }
        
        $stmt->close();
        $conn->close();
        
        return $training_data;
        
    } catch (Exception $e) {
        error_log("獲取訓練資料錯誤: " . $e->getMessage());
        return [];
    }
}

function saveQAHistory($question, $answer, $model, $response_time) {
    try {
        $conn = getOllamaDatabaseConnection();
        
        $user_id = $_SESSION['username'] ?? 'anonymous';
        
        $sql = "INSERT INTO ollama_qa_history (user_id, question, answer, model, response_time_ms, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssi', $user_id, $question, $answer, $model, $response_time);
        $stmt->execute();
        
        $conn->close();
    } catch (Exception $e) {
        // 記錄錯誤但不影響主要功能
        error_log('保存QA歷史失敗: ' . $e->getMessage());
    }
}

function parseTrainingData($content, $type) {
    $data = [];
    
    if ($type === 'qa') {
        // 解析問答格式
        $lines = explode("\n", $content);
        $current_qa = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            if (preg_match('/^Q[：:]\s*(.+)$/', $line, $matches)) {
                if (!empty($current_qa)) {
                    $data[] = $current_qa;
                }
                $current_qa = ['question' => $matches[1], 'answer' => ''];
            } elseif (preg_match('/^A[：:]\s*(.+)$/', $line, $matches)) {
                if (!empty($current_qa)) {
                    $current_qa['answer'] = $matches[1];
                }
            } elseif (!empty($current_qa) && !empty($current_qa['question'])) {
                $current_qa['answer'] .= ($current_qa['answer'] ? "\n" : '') . $line;
            }
        }
        
        if (!empty($current_qa)) {
            $data[] = $current_qa;
        }
    } elseif ($type === 'text') {
        // 解析純文字格式
        $paragraphs = preg_split('/\n\s*\n/', $content);
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (!empty($paragraph)) {
                $data[] = ['content' => $paragraph];
            }
        }
    }
    
    return $data;
}

function saveTrainingData($training_data) {
    try {
        $conn = getOllamaDatabaseConnection();
        
        $sql = "INSERT INTO ollama_training_data (content_type, content_data, created_by, created_at) 
                VALUES (?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $created_by = $_SESSION['username'] ?? 'admin';
        
        foreach ($training_data as $item) {
            $content_data = json_encode($item, JSON_UNESCAPED_UNICODE);
            $content_type = isset($item['question']) ? 'qa' : 'text';
            
            $stmt->bind_param('sss', $content_type, $content_data, $created_by);
            $stmt->execute();
        }
        
        $conn->close();
    } catch (Exception $e) {
        throw new Exception('保存訓練資料失敗: ' . $e->getMessage());
    }
}

// 簡化版添加訓練資料功能（無需管理員權限）
function addTrainingData() {
    $content_type = $_POST['content_type'] ?? '';
    $content_data = $_POST['content_data'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    
    // 處理檔案上傳
    if ($content_type === 'file' && isset($_FILES['file'])) {
        $file = $_FILES['file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => '檔案上傳失敗']);
            return;
        }
        
        // 檢查檔案類型
        $allowed_types = ['pdf', 'txt', 'doc', 'docx', 'md'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            echo json_encode(['success' => false, 'message' => '不支援的檔案格式。支援格式：PDF、TXT、DOC、DOCX、MD']);
            return;
        }
        
        // 處理檔案內容
        $file_content = processUploadedFile($file);
        if (!$file_content) {
            echo json_encode(['success' => false, 'message' => '無法讀取檔案內容']);
            return;
        }
        
        $processed_data = [
            'type' => 'file',
            'filename' => $file['name'],
            'content' => $file_content,
            'title' => $title,
            'description' => $description
        ];
        
        $content_data = json_encode($processed_data, JSON_UNESCAPED_UNICODE);
        $content_type = 'file';
    } else {
        // 處理文字和問答對資料
        if (empty($content_data)) {
            echo json_encode(['success' => false, 'message' => '資料內容不能為空']);
            return;
        }
        
        // 驗證 JSON 格式
        $decoded = json_decode($content_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'message' => '資料格式錯誤，請確保是有效的 JSON 格式']);
            return;
        }
    }
    
    try {
        $conn = getOllamaDatabaseConnection();
        
        // 插入訓練資料
        $stmt = $conn->prepare("INSERT INTO ollama_training_data (content_type, content_data, created_by) VALUES (?, ?, ?)");
        $created_by = 'user'; // 簡化版使用固定用戶名
        
        $stmt->bind_param('sss', $content_type, $content_data, $created_by);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => '訓練資料添加成功']);
        } else {
            echo json_encode(['success' => false, 'message' => '添加失敗: ' . $stmt->error]);
        }
        
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '添加失敗: ' . $e->getMessage()]);
    }
}

// 處理上傳的檔案
function processUploadedFile($file) {
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $temp_path = $file['tmp_name'];
    
    switch ($file_extension) {
        case 'txt':
        case 'md':
            return file_get_contents($temp_path);
            
        case 'pdf':
            // 簡化的 PDF 處理 - 嘗試讀取文字內容
            $content = file_get_contents($temp_path);
            if ($content) {
                // 移除二進制數據，只保留可讀文字
                $text_content = preg_replace('/[^\x20-\x7E\x{4e00}-\x{9fff}]/u', ' ', $content);
                $text_content = preg_replace('/\s+/', ' ', $text_content);
                return "PDF 檔案內容（已處理）：" . $file['name'] . "\n\n" . substr($text_content, 0, 2000);
            }
            return "PDF 檔案內容提取功能需要額外的 PDF 解析庫。檔案名稱：" . $file['name'];
            
        case 'doc':
        case 'docx':
            // 簡化的 Word 文件處理（實際應用中可能需要使用 Word 解析庫）
            return "Word 文件內容提取功能需要額外的文檔解析庫。檔案名稱：" . $file['name'];
            
        default:
            return false;
    }
}

// 獲取訓練資料列表
function getTrainingData() {
    try {
        $conn = getOllamaDatabaseConnection();
        
        $stmt = $conn->prepare("SELECT content_type, content_data, created_at FROM ollama_training_data ORDER BY created_at DESC LIMIT 50");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $data]);
        
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '獲取失敗: ' . $e->getMessage()]);
    }
}
?>
