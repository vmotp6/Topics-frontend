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

// 檢查管理員權限 - 連接到 topics_good 資料庫檢查用戶角色
function checkAdminPermission() {
    // 檢查基本登入狀態
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || 
        !isset($_SESSION['username']) || empty($_SESSION['username'])) {
        return false;
    }
    
    // 連接到 topics_good 資料庫檢查用戶角色
    $host = '100.79.58.120';
    $dbname = 'topics_good';
    $username = 'root';
    $password = '';
    
    try {
        $conn = new mysqli($host, $username, $password, $dbname);
        
        if ($conn->connect_error) {
            error_log("資料庫連接失敗: " . $conn->connect_error);
            return false;
        }
        
        $conn->set_charset("utf8mb4");
        
        // 查詢用戶角色
        $sql = "SELECT role FROM user WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $role = $row['role'];
            $stmt->close();
            $conn->close();
            
            // 檢查是否為管理員
            return $role === 'admin';
        }
        
        $stmt->close();
        $conn->close();
        return false;
        
    } catch (Exception $e) {
        error_log("權限檢查錯誤: " . $e->getMessage());
        return false;
    }
}

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
    // 確保問題是UTF-8編碼
    $question = mb_convert_encoding($question, 'UTF-8', 'auto');
    $model = trim($_POST['model'] ?? 'qwen2.5:3b');
    $use_context = ($_POST['use_context'] ?? 'false') === 'true';
    
    if (empty($question)) {
        echo json_encode(['error' => '請輸入問題']);
        return;
    }
    
    $start_time = microtime(true);
    
    try {
        // 所有問題都使用 AI 回答，確保一致性
        
        $context = '';
        if ($use_context) {
            // 從資料庫獲取相關上下文
            $context = getRelevantContext($question);
        }
        
        // 檢查是否為科系或學費問題，如果是則直接從資料庫回答
        $question_lower = mb_strtolower($question, 'UTF-8');
        if (mb_strpos($question_lower, '科系', 0, 'UTF-8') !== false || mb_strpos($question_lower, '科', 0, 'UTF-8') !== false) {
            // 直接從資料庫獲取科系資料
            $department_answer = getDepartmentAnswer($question);
            if (!empty($department_answer)) {
                $response_time = round((microtime(true) - $start_time) * 1000);
                
                // 保存問答歷史
                saveQAHistory($question, $department_answer, 'database', $response_time);
                
                echo json_encode([
                    'success' => true,
                    'answer' => $department_answer,
                    'model' => 'database',
                    'context_used' => true,
                    'response_time_ms' => $response_time
                ]);
                return;
            }
        }
        
        // 檢查是否為學費問題
        if (mb_strpos($question_lower, '學費', 0, 'UTF-8') !== false || mb_strpos($question_lower, '費用', 0, 'UTF-8') !== false) {
            // 直接從資料庫獲取學費資料
            $tuition_answer = getTuitionAnswer($question);
            if (!empty($tuition_answer)) {
                $response_time = round((microtime(true) - $start_time) * 1000);
                
                // 保存問答歷史
                saveQAHistory($question, $tuition_answer, 'database', $response_time);
                
                echo json_encode([
                    'success' => true,
                    'answer' => $tuition_answer,
                    'model' => 'database',
                    'context_used' => true,
                    'response_time_ms' => $response_time
                ]);
                return;
            }
        }
        
        // 檢查是否為創造者問題
        if (mb_strpos($question_lower, '創造者', 0, 'UTF-8') !== false || mb_strpos($question_lower, '創作者', 0, 'UTF-8') !== false) {
            // 直接從資料庫獲取創造者資料
            $creator_answer = getCreatorAnswer($question);
            if (!empty($creator_answer)) {
                $response_time = round((microtime(true) - $start_time) * 1000);
                
                // 保存問答歷史
                saveQAHistory($question, $creator_answer, 'database', $response_time);
                
                echo json_encode([
                    'success' => true,
                    'answer' => $creator_answer,
                    'model' => 'database',
                    'context_used' => true,
                    'response_time_ms' => $response_time
                ]);
                return;
            }
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
        // 確保問題是UTF-8編碼
        $question = mb_convert_encoding($question, 'UTF-8', 'auto');
        $question_lower = mb_strtolower($question, 'UTF-8');
        
        // 只從 ollama 訓練資料庫獲取相關資料
        $training_data = getRelevantTrainingData($question);
        error_log("獲取上下文 - 問題: " . $question . ", 訓練資料數量: " . count($training_data));
        
        if (!empty($training_data)) {
            $context .= "=== 康寧大學相關資料 ===\n";
            foreach ($training_data as $data) {
                $context .= $data['content'] . "\n\n";
                
                // 限制總上下文長度（增加長度以包含完整科系資訊）
                if (strlen($context) > 3000) {
                    break;
                }
            }
            error_log("獲取上下文成功 - 上下文長度: " . strlen($context));
        } else {
            error_log("獲取上下文失敗 - 沒有找到相關資料");
        }
        
        return $context;
        
    } catch (Exception $e) {
        error_log("獲取上下文錯誤: " . $e->getMessage());
        // 資料庫連接失敗時返回空字符串，讓AI仍然可以回答
        return '';
    }
}

// 直接從訓練資料中匹配答案
function getDirectAnswerFromTrainingData($question) {
    try {
        $conn = getOllamaDatabaseConnection();
        $question_lower = strtolower($question);
        
        // 檢查是否問科系
        if (strpos($question_lower, '科系') !== false || strpos($question_lower, '科') !== false || strpos($question_lower, '系') !== false) {
            $sql = "SELECT content_data FROM ollama_training_data WHERE content_data LIKE '%科系%' OR content_data LIKE '%科%' ORDER BY created_at DESC LIMIT 1";
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

// 通用關鍵詞提取函數 - 自動從問題中提取有意義的關鍵詞
function extractKeywords($question_lower) {
    $keywords = [];
    
    // 移除常見的停用詞（中文和英文）
    $stop_words = [
        // 中文停用詞
        '的', '了', '在', '是', '我', '你', '他', '她', '它', '們', '這', '那', '什麼', '怎麼', '為什麼', '如何', '哪', '哪個', '哪些', '多少', '什麼時候', '哪裡', '誰', '什麼', '怎樣', '如何', '是否', '有沒有', '可不可以', '能不能', '會不會', '要不要', '好不好', '對不對', '是不是', '有沒有', '可不可以', '能不能', '會不會', '要不要', '好不好', '對不對', '是不是',
        // 英文停用詞
        'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'can', 'must', 'shall', 'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they', 'me', 'him', 'her', 'us', 'them', 'my', 'your', 'his', 'her', 'its', 'our', 'their'
    ];
    
    // 同義詞映射 - 處理不同表達方式
    $synonyms = [
        '創作者' => ['創造者', '創建者', '開發者', '製作者'],
        '創造者' => ['創作者', '創建者', '開發者', '製作者'],
        '創建者' => ['創造者', '創作者', '開發者', '製作者'],
        '開發者' => ['創造者', '創作者', '創建者', '製作者'],
        '製作者' => ['創造者', '創作者', '創建者', '開發者'],
        '學費' => ['費用', '學雜費', '學費'],
        '費用' => ['學費', '學雜費', '學費'],
        '科系' => ['科', '系', '專業', '學系'],
        '科' => ['科系', '系', '專業', '學系'],
        '系' => ['科系', '科', '專業', '學系'],
        '專業' => ['科系', '科', '系', '學系'],
        '學系' => ['科系', '科', '系', '專業']
    ];
    
    // 按空格和標點符號分割問題
    $words = preg_split('/[\s\p{P}]+/u', $question_lower, -1, PREG_SPLIT_NO_EMPTY);
    
    foreach ($words as $word) {
        $word = trim($word);
        // 過濾停用詞和太短的詞
        if (strlen($word) > 1 && !in_array($word, $stop_words)) {
            $keywords[] = $word;
            
            // 添加同義詞
            if (isset($synonyms[$word])) {
                foreach ($synonyms[$word] as $synonym) {
                    if (!in_array($synonym, $keywords)) {
                        $keywords[] = $synonym;
                    }
                }
            }
        }
    }
    
    // 也提取中文詞組（2-4個字符的組合）
    $chinese_pattern = '/[\x{4e00}-\x{9fff}]{2,4}/u';
    preg_match_all($chinese_pattern, $question_lower, $chinese_matches);
    
    foreach ($chinese_matches[0] as $chinese_word) {
        if (!in_array($chinese_word, $keywords)) {
            $keywords[] = $chinese_word;
            
            // 為中文詞組也添加同義詞
            if (isset($synonyms[$chinese_word])) {
                foreach ($synonyms[$chinese_word] as $synonym) {
                    if (!in_array($synonym, $keywords)) {
                        $keywords[] = $synonym;
                    }
                }
            }
        }
    }
    
    // 額外的中文詞組分割 - 處理更複雜的中文表達
    $additional_chinese_words = [];
    
    // 從長詞組中提取可能的關鍵詞
    foreach ($keywords as $keyword) {
        if (mb_strlen($keyword, 'UTF-8') > 4) {
            // 嘗試從長詞組中提取2-3個字符的子詞組
            for ($i = 0; $i <= mb_strlen($keyword, 'UTF-8') - 2; $i++) {
                for ($len = 2; $len <= 3 && $i + $len <= mb_strlen($keyword, 'UTF-8'); $len++) {
                    $sub_word = mb_substr($keyword, $i, $len, 'UTF-8');
                    if (!in_array($sub_word, $additional_chinese_words) && 
                        !in_array($sub_word, $stop_words) && 
                        strlen($sub_word) > 1) {
                        $additional_chinese_words[] = $sub_word;
                    }
                }
            }
        }
    }
    
    // 添加額外的中文詞組
    foreach ($additional_chinese_words as $word) {
        if (!in_array($word, $keywords)) {
            $keywords[] = $word;
            
            // 為額外的詞組也添加同義詞
            if (isset($synonyms[$word])) {
                foreach ($synonyms[$word] as $synonym) {
                    if (!in_array($synonym, $keywords)) {
                        $keywords[] = $synonym;
                    }
                }
            }
        }
    }
    
    return array_unique($keywords);
}

// 構建通用相關性排序
function buildRelevanceOrder($keywords) {
    $order_parts = [];
    
    foreach ($keywords as $index => $keyword) {
        $priority = count($keywords) - $index; // 第一個關鍵詞優先級最高
        $order_parts[] = "WHEN content_data LIKE '%" . $keyword . "%' THEN " . $priority;
    }
    
    if (empty($order_parts)) {
        return "created_at DESC";
    }
    
    return "CASE " . implode(' ', $order_parts) . " ELSE 999 END";
}

// 從訓練資料庫獲取相關資料
function getRelevantTrainingData($question) {
    try {
        $conn = getOllamaDatabaseConnection();
        // 確保問題是UTF-8編碼
        $question = mb_convert_encoding($question, 'UTF-8', 'auto');
        $question_lower = mb_strtolower($question, 'UTF-8');
        
        // 通用關鍵詞提取 - 自動從問題中提取有意義的關鍵詞
        $keywords = extractKeywords($question_lower);
        
        // 構建 SQL 查詢，尋找包含關鍵詞的訓練資料
        $where_conditions = [];
        $params = [];
        $param_types = '';
        
        // 添加提取的關鍵詞
        foreach ($keywords as $keyword) {
            if (strlen($keyword) > 1) { // 至少2個字符
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
            // 使用通用相關性排序 - 根據關鍵詞匹配數量排序
            $relevance_order = buildRelevanceOrder($keywords);
            
            $sql = "SELECT content_data FROM ollama_training_data WHERE " . implode(' OR ', $where_conditions) . " ORDER BY " . $relevance_order . ", created_at DESC LIMIT 5";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($param_types, ...$params);
        }
        
        // 執行查詢
        $stmt->execute();
        $result = $stmt->get_result();
        
        // 如果沒有找到相關資料，至少返回一些通用資料讓AI能夠回答
        if ($result->num_rows === 0) {
            // 沒有找到相關資料，返回一些通用資料
            $stmt->close();
            $sql = "SELECT content_data FROM ollama_training_data ORDER BY created_at DESC LIMIT 3";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
        }
        
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
                    // 舊格式的資料 - 嘗試解析為Q&A格式
                    $old_data = json_decode($row['content_data'], true);
                    if ($old_data && isset($old_data['question']) && isset($old_data['answer'])) {
                        $training_data[] = [
                            'content' => "Q: " . $old_data['question'] . "\nA: " . $old_data['answer'],
                            'title' => ''
                        ];
                    } else {
                        $training_data[] = [
                            'content' => $row['content_data'],
                            'title' => ''
                        ];
                    }
                }
            }
        }
        
        $stmt->close();
        $conn->close();
        
        return $training_data;
        
    } catch (Exception $e) {
        error_log("獲取訓練資料錯誤: " . $e->getMessage());
        // 資料庫連接失敗時返回空數組，讓AI仍然可以回答
        return [];
    }
}

function getDepartmentAnswer($question) {
    try {
        $conn = getOllamaDatabaseConnection();
        
        // 查詢科系相關資料
        $sql = "SELECT content_data FROM ollama_training_data WHERE content_data LIKE '%科系%' ORDER BY created_at DESC LIMIT 1";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $content_data = json_decode($row['content_data'], true);
            
            if ($content_data && isset($content_data['type']) && $content_data['type'] === 'qa') {
                $answer = $content_data['answer'] ?? '';
                if (!empty($answer)) {
                    // 清理答案中的前綴
                    $answer = preg_replace('/^回答：\s*/', '', $answer);
                    $answer = preg_replace('/^A:\s*/', '', $answer);
                    $conn->close();
                    return $answer;
                }
            }
        }
        
        $conn->close();
        return '';
        
    } catch (Exception $e) {
        error_log("獲取科系答案錯誤: " . $e->getMessage());
        return '';
    }
}

function getCreatorAnswer($question) {
    try {
        $conn = getOllamaDatabaseConnection();
        
        // 查詢創造者相關資料
        $sql = "SELECT content_data FROM ollama_training_data WHERE content_data LIKE '%創造者%' OR content_data LIKE '%創作者%' ORDER BY created_at DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $content_data = json_decode($row['content_data'], true);
            if ($content_data && isset($content_data['answer'])) {
                $answer = $content_data['answer'];
                
                // 移除可能的回答前綴
                $answer = preg_replace('/^(回答：|A：|答案：)/u', '', $answer);
                
                // 添加可愛的語氣
                $answer = "我的創造者是：" . $answer . " 😊✨";
                
                $stmt->close();
                $conn->close();
                return $answer;
            }
        }
        
        $stmt->close();
        $conn->close();
        return '';
        
    } catch (Exception $e) {
        error_log("獲取創造者答案錯誤: " . $e->getMessage());
        return '';
    }
}

function getTuitionAnswer($question) {
    try {
        $conn = getOllamaDatabaseConnection();
        
        // 查詢學費相關資料
        $sql = "SELECT content_data FROM ollama_training_data WHERE content_data LIKE '%學費%' ORDER BY created_at DESC LIMIT 1";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $content_data = json_decode($row['content_data'], true);
            
            if ($content_data && isset($content_data['type']) && $content_data['type'] === 'qa') {
                $answer = $content_data['answer'] ?? '';
                if (!empty($answer)) {
                    // 清理答案中的前綴
                    $answer = preg_replace('/^回答：\s*/', '', $answer);
                    $answer = preg_replace('/^A:\s*/', '', $answer);
                    $conn->close();
                    return $answer;
                }
            }
        }
        
        $conn->close();
        return '';
        
    } catch (Exception $e) {
        error_log("獲取學費答案錯誤: " . $e->getMessage());
        return '';
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
        // 不拋出異常，讓AI問答繼續工作
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
            // 專業 PDF 文字提取 - 支援中文和複雜格式
            $content = file_get_contents($temp_path);
            if ($content) {
                $text_content = '';
                
                // 方法1: 提取 PDF 文字流 (BT...ET) - 改進版本
                if (preg_match_all('/BT\s+.*?ET/s', $content, $matches)) {
                    foreach ($matches[0] as $match) {
                        // 提取 (text) Tj 格式的文字 - 支援中文
                        if (preg_match_all('/\((.*?)\)\s*Tj/', $match, $text_matches)) {
                            foreach ($text_matches[1] as $text) {
                                // 解碼 PDF 文字編碼
                                $decoded_text = $text;
                                // 處理 PDF 轉義字符
                                $decoded_text = str_replace(['\\n', '\\r', '\\t'], [' ', ' ', ' '], $decoded_text);
                                // 處理 PDF 括號轉義
                                $decoded_text = str_replace(['\\(', '\\)'], ['(', ')'], $decoded_text);
                                $text_content .= $decoded_text . ' ';
                            }
                        }
                        // 提取 <hex> Tj 格式的文字 - 支援 Unicode
                        if (preg_match_all('/<([0-9A-Fa-f]+)>\s*Tj/', $match, $hex_matches)) {
                            foreach ($hex_matches[1] as $hex) {
                                if (strlen($hex) % 2 == 0) {
                                    $decoded = '';
                                    for ($i = 0; $i < strlen($hex); $i += 2) {
                                        $byte = hexdec(substr($hex, $i, 2));
                                        if ($byte >= 32 && $byte <= 126) {
                                            $decoded .= chr($byte);
                                        } elseif ($byte >= 128) {
                                            // 處理 UTF-8 編碼
                                            $decoded .= chr($byte);
                                        }
                                    }
                                    $text_content .= $decoded . ' ';
                                }
                            }
                        }
                    }
                }
                
                // 方法2: 提取 PDF 內容流中的文字
                if (empty($text_content)) {
                    // 查找所有文字內容
                    if (preg_match_all('/stream\s*(.*?)\s*endstream/s', $content, $stream_matches)) {
                        foreach ($stream_matches[1] as $stream) {
                            // 解碼流內容
                            $decoded_stream = '';
                            if (preg_match('/FlateDecode/', $stream)) {
                                // 嘗試解碼 FlateDecode 流
                                $stream_data = preg_replace('/.*?stream\s*/s', '', $stream);
                                $stream_data = preg_replace('/\s*endstream.*/s', '', $stream_data);
                                $stream_data = trim($stream_data);
                                
                                // 提取可讀文字
                                $decoded_stream = preg_replace('/[^\x20-\x7E\x{4e00}-\x{9fff}\x{3400}-\x{4dbf}]/u', ' ', $stream_data);
                            } else {
                                // 直接提取文字
                                $decoded_stream = preg_replace('/[^\x20-\x7E\x{4e00}-\x{9fff}\x{3400}-\x{4dbf}]/u', ' ', $stream);
                            }
                            $text_content .= $decoded_stream . ' ';
                        }
                    }
                }
                
                // 方法3: 暴力提取所有可讀字符
                if (empty($text_content)) {
                    // 提取所有可讀字符，包括完整的中文 Unicode 範圍
                    $text_content = preg_replace('/[^\x20-\x7E\x{4e00}-\x{9fff}\x{3400}-\x{4dbf}\x{20000}-\x{2a6df}\x{2a700}-\x{2b73f}\x{2b740}-\x{2b81f}\x{2b820}-\x{2ceaf}\x{2ceb0}-\x{2ebef}\x{2f800}-\x{2fa1f}\x{30000}-\x{3134f}]/u', ' ', $content);
                    $text_content = preg_replace('/\s+/', ' ', $text_content);
                }
                
                // 方法4: 提取 PDF 物件中的文字
                if (empty($text_content)) {
                    // 查找 PDF 物件中的文字內容
                    if (preg_match_all('/obj\s*(.*?)\s*endobj/s', $content, $obj_matches)) {
                        foreach ($obj_matches[1] as $obj) {
                            // 提取物件中的文字
                            $obj_text = preg_replace('/[^\x20-\x7E\x{4e00}-\x{9fff}\x{3400}-\x{4dbf}]/u', ' ', $obj);
                            $obj_text = preg_replace('/\s+/', ' ', $obj_text);
                            if (strlen(trim($obj_text)) > 10) {
                                $text_content .= $obj_text . ' ';
                            }
                        }
                    }
                }
                
                // 清理和格式化文字
                $text_content = trim($text_content);
                $text_content = preg_replace('/\s+/', ' ', $text_content);
                
                // 移除 PDF 特有的控制字符和垃圾字符
                $text_content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text_content);
                
                // 移除常見的 PDF 垃圾字符
                $text_content = preg_replace('/[^\x20-\x7E\x{4e00}-\x{9fff}\x{3400}-\x{4dbf}]/u', ' ', $text_content);
                $text_content = preg_replace('/\s+/', ' ', $text_content);
                
                // 檢查是否提取到有效文字
                if (!empty($text_content) && strlen($text_content) > 10) {
                    // 進一步清理：移除重複的空白和無意義字符
                    $text_content = preg_replace('/\s+/', ' ', $text_content);
                    $text_content = trim($text_content);
                    
                    return "PDF 檔案內容（" . $file['name'] . "）：\n\n" . substr($text_content, 0, 8000);
                }
            }
            return "PDF 檔案內容提取失敗。檔案名稱：" . $file['name'] . "。請確認 PDF 包含可提取的文字內容，或嘗試使用其他格式（如 TXT 或 MD）。";
            
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
