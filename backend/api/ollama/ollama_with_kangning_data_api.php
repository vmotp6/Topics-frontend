<?php
/**
 * 包含康寧大學資料的 Ollama API
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

// 康寧大學資料庫
function getKangningData() {
    return [
        '學費' => [
            '大學部學費' => '每學期約新台幣45,000-55,000元',
            '研究所學費' => '每學期約新台幣50,000-60,000元',
            '住宿費' => '每學期約新台幣15,000-25,000元',
            '雜費' => '每學期約新台幣3,000-5,000元',
            '獎學金' => '提供多種獎學金，包括學業優秀獎學金、清寒助學金等',
            '助學貸款' => '可申請就學貸款，利率優惠'
        ],
        '科系' => [
            '資訊科技學院' => [
                '資訊工程學系',
                '資訊管理學系',
                '多媒體設計學系',
                '網路工程學系'
            ],
            '商學院' => [
                '企業管理學系',
                '國際貿易學系',
                '會計學系',
                '財務金融學系'
            ],
            '人文社會學院' => [
                '應用外語學系',
                '社會工作學系',
                '心理學系',
                '傳播學系'
            ],
            '健康學院' => [
                '護理學系',
                '物理治療學系',
                '營養學系',
                '健康管理學系'
            ]
        ],
        '招生' => [
            '申請時間' => '每年3-5月',
            '申請方式' => '線上申請或現場報名',
            '考試科目' => '依科系而定，一般包括國文、英文、數學',
            '面試' => '部分科系需要面試',
            '錄取標準' => '學測成績、面試表現、備審資料'
        ],
        '校園生活' => [
            '宿舍' => '提供男女生宿舍，設備完善',
            '餐廳' => '校內有多個餐廳，提供多樣化餐點',
            '圖書館' => '24小時開放，藏書豐富',
            '體育設施' => '健身房、游泳池、籃球場等',
            '社團活動' => '超過50個學生社團'
        ]
    ];
}

// 根據問題獲取相關資料
function getRelevantKangningData($question) {
    $data = getKangningData();
    $question_lower = strtolower($question);
    $relevant_data = [];
    
    // 檢查學費相關問題
    if (strpos($question_lower, '學費') !== false || strpos($question_lower, '費用') !== false || 
        strpos($question_lower, '錢') !== false || strpos($question_lower, '多少') !== false) {
        $relevant_data['學費'] = $data['學費'];
    }
    
    // 檢查科系相關問題
    if (strpos($question_lower, '科系') !== false || strpos($question_lower, '科') !== false || 
        strpos($question_lower, '系') !== false || strpos($question_lower, '專業') !== false) {
        $relevant_data['科系'] = $data['科系'];
    }
    
    // 檢查招生相關問題
    if (strpos($question_lower, '招生') !== false || strpos($question_lower, '申請') !== false || 
        strpos($question_lower, '報名') !== false || strpos($question_lower, '考試') !== false) {
        $relevant_data['招生'] = $data['招生'];
    }
    
    // 檢查校園生活相關問題
    if (strpos($question_lower, '宿舍') !== false || strpos($question_lower, '餐廳') !== false || 
        strpos($question_lower, '圖書館') !== false || strpos($question_lower, '社團') !== false) {
        $relevant_data['校園生活'] = $data['校園生活'];
    }
    
    return $relevant_data;
}

// 使用真實的 Ollama 服務
try {
    $ollama = new OllamaService('http://localhost:11434', 'qwen2.5:3b');
} catch (Exception $e) {
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
        // 獲取康寧大學相關資料
        $kangning_data = getRelevantKangningData($question);
        $context = '';
        
        if (!empty($kangning_data)) {
            $context .= "=== 康寧大學官方資料 ===\n";
            foreach ($kangning_data as $category => $data) {
                $context .= "\n【" . $category . "】\n";
                if (is_array($data)) {
                    foreach ($data as $key => $value) {
                        if (is_array($value)) {
                            $context .= $key . "：\n";
                            foreach ($value as $item) {
                                $context .= "- " . $item . "\n";
                            }
                        } else {
                            $context .= $key . "：" . $value . "\n";
                        }
                    }
                }
                $context .= "\n";
            }
        }
        
        $result = $ollama->askQuestion($question, $context, $model);
        
        if ($result['success']) {
            $response_time = round((microtime(true) - $start_time) * 1000);
            
            echo json_encode([
                'success' => true,
                'answer' => $result['answer'],
                'model' => $result['model'],
                'context_used' => !empty($context),
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
