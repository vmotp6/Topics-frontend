<?php
/**
 * RAG AI 服務類
 * 負責處理檢索增強生成（RAG）邏輯
 */

require_once dirname(__DIR__, 3) . '/frontend/config.php';
require_once dirname(__DIR__, 2) . '/config/ollama_config.php';

class RAGChatService {
    private $conn;
    private $aiService;
    
    // 相似度閾值（0-1之間，越高越嚴格）
    private $similarityThreshold = 0.6;
    
    // 最多返回的相似結果數量
    private $maxResults = 3;
    
    public function __construct() {
        // 使用 ollama 資料庫連接
        $this->conn = getOllamaDatabaseConnection();
        $this->aiService = new AIService();
    }
    
    /**
     * 處理問題並返回回答
     */
    public function processQuestion($question) {
        try {
            // 步驟1: 從資料庫搜索相似的資料
            $similarDocs = $this->searchSimilarDocuments($question);
            
            // 步驟2: 根據搜索結果決定回答方式
            if (!empty($similarDocs) && count($similarDocs) > 0) {
                // 找到相似資料，使用 RAG 方式回答
                $context = $this->buildContext($similarDocs);
                $answer = $this->aiService->generateAnswerWithContext($question, $context);
                
                return [
                    'success' => true,
                    'answer' => $answer,
                    'source' => 'rag', // 標記為 RAG 回答
                    'similar_docs_found' => count($similarDocs),
                    'used_context' => true
                ];
            } else {
                // 沒有找到相似資料，使用 AI 直接回答
                $answer = $this->aiService->generateAnswer($question);
                
                return [
                    'success' => true,
                    'answer' => $answer,
                    'source' => 'ai', // 標記為 AI 回答
                    'similar_docs_found' => 0,
                    'used_context' => false
                ];
            }
        } catch (Exception $e) {
            error_log("RAGChatService 錯誤: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 從資料庫搜索相似的文檔
     * 使用多種搜索策略：
     * 1. 全文搜索（MATCH AGAINST）
     * 2. LIKE 搜索
     * 3. 關鍵詞匹配
     */
    private function searchSimilarDocuments($question) {
        $results = [];
        $question = $this->conn->real_escape_string($question);
        $question_lower = mb_strtolower($question, 'UTF-8');
        
        try {
            // 策略1: 從 QA 表搜索（如果有這個表）
            $qaResults = $this->searchFromQATable($question, $question_lower);
            $results = array_merge($results, $qaResults);
            
            // 策略2: 從知識庫表搜索（如果有這個表）
            $kbResults = $this->searchFromKnowledgeBase($question, $question_lower);
            $results = array_merge($results, $kbResults);
            
            // 策略3: 從 FAQ 表搜索
            $faqResults = $this->searchFromFAQTable($question, $question_lower);
            $results = array_merge($results, $faqResults);
            
            // 計算相似度並排序
            $results = $this->calculateSimilarity($question_lower, $results);
            
            // 過濾低相似度的結果
            $results = array_filter($results, function($item) {
                return isset($item['similarity']) && $item['similarity'] >= $this->similarityThreshold;
            });
            
            // 按相似度排序並返回前 N 個
            usort($results, function($a, $b) {
                return ($b['similarity'] ?? 0) <=> ($a['similarity'] ?? 0);
            });
            
            return array_slice($results, 0, $this->maxResults);
            
        } catch (Exception $e) {
            error_log("搜索相似文檔錯誤: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 從 QA 表搜索（搜索 ollama 資料庫中的 ollama_training_data 表）
     */
    private function searchFromQATable($question, $question_lower) {
        $results = [];
        
        try {
            // 優先搜索 ollama_training_data 表（ollama 資料庫）
            $checkTable = $this->conn->query("SHOW TABLES LIKE 'ollama_training_data'");
            if ($checkTable && $checkTable->num_rows > 0) {
                // 查詢所有 Q&A 資料
                $sql = "SELECT id, content_data, created_at FROM ollama_training_data 
                        WHERE content_type = 'qa' 
                        ORDER BY created_at DESC 
                        LIMIT 50";
                $stmt = $this->conn->prepare($sql);
                if ($stmt) {
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($row = $result->fetch_assoc()) {
                        $content_data = json_decode($row['content_data'], true);
                        if ($content_data && isset($content_data['question']) && isset($content_data['answer'])) {
                            $db_question = mb_strtolower($content_data['question'], 'UTF-8');
                            
                            // 簡單的相似度檢查
                            if (mb_strpos($question_lower, $db_question) !== false || 
                                mb_strpos($db_question, $question_lower) !== false ||
                                mb_strpos($content_data['answer'], $question) !== false) {
                                $results[] = [
                                    'type' => 'ollama_qa',
                                    'id' => $row['id'],
                                    'question' => $content_data['question'],
                                    'answer' => $content_data['answer'],
                                    'content' => $content_data['question'] . ' ' . $content_data['answer']
                                ];
                            }
                        }
                    }
                    $stmt->close();
                }
            }
            
            // 也搜索 topics_good 資料庫的 qa 表（如果需要的話）
            // 這裡可以添加跨資料庫查詢邏輯
        } catch (Exception $e) {
            error_log("搜索 QA 表錯誤: " . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * 從知識庫表搜索（優先搜索 RAG 專用知識庫，在 ollama 資料庫中）
     */
    private function searchFromKnowledgeBase($question, $question_lower) {
        $results = [];
        
        try {
            // 優先檢查 RAG 專用知識庫表（在 ollama 資料庫中）
            $tableNames = ['rag_knowledge_base', 'knowledge_base', 'kb', 'rag_knowledge', 'documents'];
            
            foreach ($tableNames as $tableName) {
                $checkTable = $this->conn->query("SHOW TABLES LIKE '{$tableName}'");
                if ($checkTable && $checkTable->num_rows > 0) {
                    // 嘗試不同的欄位組合
                    $fieldCombinations = [
                        ['title', 'content'],
                        ['question', 'answer'],
                        ['topic', 'text'],
                        ['content']
                    ];
                    
                    foreach ($fieldCombinations as $fields) {
                        try {
                            $fieldsStr = implode(', ', $fields);
                            
                            // 如果是 rag_knowledge_base 表，使用全文搜索
                            if ($tableName === 'rag_knowledge_base') {
                                $sql = "SELECT id, title, content, category, keywords, priority 
                                        FROM {$tableName} 
                                        WHERE is_active = 1 AND (
                                            MATCH(title, content, keywords) AGAINST(? IN NATURAL LANGUAGE MODE)
                                            OR title LIKE ?
                                            OR content LIKE ?
                                            OR keywords LIKE ?
                                        )
                                        ORDER BY priority DESC, 
                                                 CASE 
                                                     WHEN MATCH(title, content, keywords) AGAINST(? IN NATURAL LANGUAGE MODE) THEN 1
                                                     WHEN title LIKE ? THEN 2
                                                     WHEN keywords LIKE ? THEN 3
                                                     ELSE 4
                                                 END
                                        LIMIT 10";
                                
                                $stmt = $this->conn->prepare($sql);
                                if ($stmt) {
                                    $searchTerm = "%{$question}%";
                                    $searchTerm2 = $question; // 用於全文搜索
                                    $stmt->bind_param("sssssss", 
                                        $searchTerm2, $searchTerm, $searchTerm, $searchTerm,
                                        $searchTerm2, $searchTerm, $searchTerm
                                    );
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    
                                    while ($row = $result->fetch_assoc()) {
                                        $content = ($row['title'] ?? '') . ' ' . ($row['content'] ?? '') . ' ' . ($row['keywords'] ?? '');
                                        $results[] = [
                                            'type' => 'rag_knowledge_base',
                                            'id' => $row['id'],
                                            'title' => $row['title'] ?? '',
                                            'content' => trim($content),
                                            'category' => $row['category'] ?? '',
                                            'priority' => $row['priority'] ?? 0,
                                            'fields' => $row
                                        ];
                                    }
                                    $stmt->close();
                                    
                                    // 如果找到結果，跳出循環
                                    if (count($results) > 0) {
                                        break 2;
                                    }
                                }
                            } else {
                                // 其他表使用 LIKE 搜索
                                $sql = "SELECT id, {$fieldsStr} FROM {$tableName} WHERE (
                                    " . implode(' LIKE ? OR ', $fields) . " LIKE ?
                                ) LIMIT 10";
                                
                                $stmt = $this->conn->prepare($sql);
                                if ($stmt) {
                                    $searchTerm = "%{$question}%";
                                    $params = str_repeat('s', count($fields));
                                    $stmt->bind_param($params, ...array_fill(0, count($fields), $searchTerm));
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    
                                    while ($row = $result->fetch_assoc()) {
                                        $content = '';
                                        foreach ($fields as $field) {
                                            if (isset($row[$field])) {
                                                $content .= $row[$field] . ' ';
                                            }
                                        }
                                        
                                        $results[] = [
                                            'type' => 'knowledge_base',
                                            'id' => $row['id'],
                                            'content' => trim($content),
                                            'fields' => $row
                                        ];
                                    }
                                    $stmt->close();
                                    
                                    // 如果找到結果，跳出字段循環
                                    if (count($results) > 0) {
                                        break 2;
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            // 嘗試下一個字段組合
                            continue;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("搜索知識庫錯誤: " . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * 從 FAQ 表搜索（搜索 ollama 資料庫中的表）
     */
    private function searchFromFAQTable($question, $question_lower) {
        $results = [];
        
        try {
            // 搜索 ollama 資料庫中的 faq 表
            $checkTable = $this->conn->query("SHOW TABLES LIKE 'faq'");
            if ($checkTable && $checkTable->num_rows > 0) {
                $sql = "SELECT id, question, answer FROM faq WHERE (
                    question LIKE ? OR answer LIKE ?
                ) LIMIT 10";
                
                $stmt = $this->conn->prepare($sql);
                if ($stmt) {
                    $searchTerm = "%{$question}%";
                    $stmt->bind_param("ss", $searchTerm, $searchTerm);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($row = $result->fetch_assoc()) {
                        $results[] = [
                            'type' => 'faq',
                            'id' => $row['id'],
                            'question' => $row['question'],
                            'answer' => $row['answer'],
                            'content' => $row['question'] . ' ' . $row['answer']
                        ];
                    }
                    $stmt->close();
                }
            }
        } catch (Exception $e) {
            error_log("搜索 FAQ 表錯誤: " . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * 計算相似度（使用簡單的文本相似度算法）
     * 可以使用更複雜的算法，如 TF-IDF、余弦相似度等
     */
    private function calculateSimilarity($question, $results) {
        $questionWords = $this->extractWords($question);
        
        foreach ($results as &$result) {
            $content = mb_strtolower($result['content'] ?? '', 'UTF-8');
            $contentWords = $this->extractWords($content);
            
            // 計算共同詞彙比例
            $commonWords = count(array_intersect($questionWords, $contentWords));
            $totalWords = count(array_unique(array_merge($questionWords, $contentWords)));
            
            $similarity = $totalWords > 0 ? $commonWords / $totalWords : 0;
            
            // 如果有包含關係，提高相似度
            if (mb_strpos($content, $question) !== false || mb_strpos($question, $content) !== false) {
                $similarity = min(1.0, $similarity + 0.3);
            }
            
            $result['similarity'] = $similarity;
        }
        
        return $results;
    }
    
    /**
     * 提取詞彙（簡單版本，可改進）
     */
    private function extractWords($text) {
        // 移除標點符號，保留中英文和數字
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        // 分割成詞組
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return array_filter(array_map('trim', $words));
    }
    
    /**
     * 構建上下文文本
     */
    private function buildContext($similarDocs) {
        $contextParts = [];
        
        foreach ($similarDocs as $doc) {
            if (isset($doc['answer'])) {
                $contextParts[] = "問題: {$doc['question']}\n答案: {$doc['answer']}";
            } elseif (isset($doc['content'])) {
                $contextParts[] = $doc['content'];
            }
        }
        
        return implode("\n\n", $contextParts);
    }
    
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

/**
 * AI 服務類
 * 負責調用 AI API 生成回答
 * 支援多種免費 AI 服務：Ollama（推薦）、Hugging Face 等
 */
class AIService {
    // AI 服務配置
    private $aiProvider; // 'ollama', 'openai', 'huggingface', 'fallback'
    private $apiKey;
    private $apiUrl;
    
    // Ollama 配置
    private $ollamaUrl = 'http://localhost:11434';
    private $ollamaModel = 'qwen2.5:3b'; // 推薦使用 qwen2.5:3b（速度快，效果好）
    
    public function __construct() {
        // 優先使用 Ollama（本地免費，速度快）
        $this->aiProvider = 'ollama';
        
        // 如果 Ollama 不可用，可以配置其他服務
        $this->apiKey = getenv('OPENAI_API_KEY') ?: '';
        $this->apiUrl = 'https://api.openai.com/v1/chat/completions';
        
        // 檢查 Ollama 是否可用
        if (!$this->checkOllamaAvailable()) {
            // Ollama 不可用，嘗試其他服務
            if (!empty($this->apiKey)) {
                $this->aiProvider = 'openai';
            } else {
                $this->aiProvider = 'fallback';
            }
        }
    }
    
    /**
     * 檢查 Ollama 是否可用
     */
    private function checkOllamaAvailable() {
        try {
            $ch = curl_init($this->ollamaUrl . '/api/tags');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $httpCode === 200;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 使用上下文生成回答（RAG 模式）
     */
    public function generateAnswerWithContext($question, $context) {
        $prompt = "你是一個友善的助手，請根據以下上下文資料回答用戶的問題。\n\n";
        $prompt .= "上下文資料：\n{$context}\n\n";
        $prompt .= "用戶問題：{$question}\n\n";
        $prompt .= "請根據上下文資料回答問題。如果上下文資料中沒有相關資訊，請禮貌地告知用戶。回答要簡潔明確，使用繁體中文。";
        
        return $this->callAI($prompt);
    }
    
    /**
     * 直接生成回答（無上下文）
     */
    public function generateAnswer($question) {
        $prompt = "你是一個友善的助手，請回答用戶的問題。回答要簡潔明確，使用繁體中文。\n\n";
        $prompt .= "用戶問題：{$question}";
        
        return $this->callAI($prompt);
    }
    
    /**
     * 調用 AI API
     */
    private function callAI($prompt) {
        switch ($this->aiProvider) {
            case 'ollama':
                return $this->callOllama($prompt);
            case 'openai':
                return $this->callOpenAI($prompt);
            default:
                return $this->getFallbackResponse($prompt);
        }
    }
    
    /**
     * 調用 Ollama API（推薦：免費、快速、本地運行）
     */
    private function callOllama($prompt) {
        try {
            $data = [
                'model' => $this->ollamaModel,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'temperature' => 0.7,      // 創造性（0-1，越高越有創意）
                    'top_p' => 0.9,            // 核採樣
                    'top_k' => 40,             // Top-K 採樣
                    'repeat_penalty' => 1.15,  // 重複懲罰
                    'num_predict' => 200,      // 最大生成長度
                    'num_ctx' => 2048,         // 上下文長度
                    'num_thread' => 4,         // 線程數
                ]
            ];
            
            $ch = curl_init($this->ollamaUrl . '/api/generate');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $result = json_decode($response, true);
                if (isset($result['response'])) {
                    return trim($result['response']);
                }
            }
            
            error_log("Ollama API 錯誤: HTTP {$httpCode}, 錯誤: {$curlError}");
            // Ollama 失敗時回退到後備回應
            return $this->getFallbackResponse($prompt);
            
        } catch (Exception $e) {
            error_log("調用 Ollama API 錯誤: " . $e->getMessage());
            return $this->getFallbackResponse($prompt);
        }
    }
    
    /**
     * 調用 OpenAI API
     */
    private function callOpenAI($prompt) {
        try {
            $data = [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => '你是一個友善、專業的助手，專門幫助用戶解答關於康寧大學的問題。'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 500
            ];
            
            $ch = curl_init($this->apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $result = json_decode($response, true);
                if (isset($result['choices'][0]['message']['content'])) {
                    return trim($result['choices'][0]['message']['content']);
                }
            }
            
            error_log("OpenAI API 錯誤: HTTP {$httpCode}, 回應: {$response}");
            return $this->getFallbackResponse($prompt);
            
        } catch (Exception $e) {
            error_log("調用 OpenAI API 錯誤: " . $e->getMessage());
            return $this->getFallbackResponse($prompt);
        }
    }
    
    /**
     * 後備回應（當所有 AI API 都不可用時）
     */
    private function getFallbackResponse($prompt) {
        // 簡單的關鍵詞匹配回應
        $prompt_lower = mb_strtolower($prompt, 'UTF-8');
        
        if (mb_strpos($prompt_lower, '你好') !== false || mb_strpos($prompt_lower, 'hello') !== false || mb_strpos($prompt_lower, 'hi') !== false) {
            return '你好！很高興為你服務！有什麼我可以幫助你的嗎？😊';
        } elseif (mb_strpos($prompt_lower, '謝謝') !== false || mb_strpos($prompt_lower, 'thank') !== false || mb_strpos($prompt_lower, '感謝') !== false) {
            return '不客氣！隨時歡迎你的提問！😊';
        } elseif (mb_strpos($prompt_lower, '幫助') !== false || mb_strpos($prompt_lower, 'help') !== false || mb_strpos($prompt_lower, '怎麼用') !== false) {
            return '我可以幫助你了解康寧大學的相關資訊。請告訴我你想了解什麼？';
        } elseif (mb_strpos($prompt_lower, '功能') !== false || mb_strpos($prompt_lower, '可以做什麼') !== false) {
            return '我可以：1. 介紹網頁功能 2. 回答關於康寧大學的問題 3. 提供招生相關資訊。你想了解什麼呢？';
        } elseif (mb_strpos($prompt_lower, '招生') !== false || mb_strpos($prompt_lower, '報名') !== false) {
            return '關於招生和報名的資訊，建議你查看「招生QA問答」頁面，或聯繫招生辦公室獲取詳細資訊。';
        } elseif (mb_strpos($prompt_lower, '科系') !== false || mb_strpos($prompt_lower, '專業') !== false) {
            return '康寧大學提供多個科系，建議你查看官網或聯繫招生辦公室了解各科系的詳細資訊。';
        } else {
            return '感謝你的問題！我目前正在學習中，建議你可以查詢我們的官方網站或聯繫招生辦公室獲取更詳細的資訊。';
        }
    }
}
?>

