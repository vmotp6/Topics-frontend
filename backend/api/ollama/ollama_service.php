<?php
/**
 * Ollama 整合服務
 * 用於與本地Ollama API進行通信
 */

class OllamaService {
    private $ollama_url;
    private $model_name;
    
    public function __construct($ollama_url = 'http://localhost:11434', $model_name = 'qwen2.5:3b') {
        $this->ollama_url = rtrim($ollama_url, '/');
        $this->model_name = $model_name;
    }
    
    /**
     * 檢查Ollama服務是否運行
     */
    public function checkHealth() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->ollama_url . '/api/tags');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $http_code === 200;
    }
    
    /**
     * 獲取可用的模型列表
     */
    public function getModels() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->ollama_url . '/api/tags');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $data = json_decode($response, true);
            return $data['models'] ?? [];
        }
        
        return [];
    }
    
    /**
     * 向Ollama發送問題並獲取回答
     */
    public function askQuestion($question, $context = '', $model = null) {
        $model = $model ?: $this->model_name;
        
        // 構建提示詞，包含上下文
        $prompt = $this->buildPrompt($question, $context);
        
        $data = [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'temperature' => 0.7,  // 提高溫度，加快生成速度
                'top_p' => 0.95,        // 提高多樣性
                'top_k' => 40,          // 增加候選詞，提高速度
                'repeat_penalty' => 1.15, // 適中重複懲罰
                'num_predict' => 80,    // 優化：進一步減少預測長度，提高速度（從100減少到80）
                'num_ctx' => 1024,      // 優化：減少上下文長度，大幅提高速度（從2048減少到1024）
                'num_thread' => 4,      // 優化：增加線程數，提高處理速度（從2增加到4）
                'num_gpu' => 0,         // 禁用GPU，使用CPU加速
                'num_batch' => 32       // 優化：進一步減少批次大小，提高響應速度（從64減少到32）
            ]
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->ollama_url . '/api/generate');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);  // 優化：減少到30秒，如果超過則快速失敗
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);  // 優化：連接超時5秒
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 30000);  // 優化：毫秒級超時30秒
        curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);  // 啟用TCP keepalive
        curl_setopt($ch, CURLOPT_TCP_KEEPIDLE, 60);  // keepalive idle時間
        curl_setopt($ch, CURLOPT_TCP_KEEPINTVL, 30);  // keepalive間隔
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            return [
                'success' => false,
                'error' => '連接錯誤: ' . $curl_error
            ];
        }
        
        if ($http_code === 200) {
            $result = json_decode($response, true);
            if ($result && isset($result['response'])) {
                return [
                    'success' => true,
                    'answer' => $result['response'],
                    'model' => $model,
                    'context_used' => !empty($context)
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Ollama 回應格式錯誤: ' . $response
                ];
            }
        }
        
        return [
            'success' => false,
            'error' => 'Ollama API請求失敗，HTTP狀態碼: ' . $http_code . '，回應: ' . $response
        ];
    }
    
    /**
     * 構建提示詞
     */
    private function buildPrompt($question, $context = '') {
        if (!empty($context)) {
            // 優化：簡化提示詞，減少處理時間
            $prompt = "你是康寧大學的智能小助手，個性可愛活潑。\n\n";
            $prompt .= "資料庫資訊：\n" . $context . "\n\n";
            $prompt .= "問題：" . $question . "\n\n";
            $prompt .= "規則：使用繁體中文，優先使用資料庫內容，如果沒有則用你的知識回答。保持可愛活潑語氣，使用表情符號。絕對不能說「不清楚」或「不知道」！";
        } else {
            // 優化：簡化提示詞，減少處理時間
            $prompt = "你是康寧大學的智能小助手，個性可愛活潑。\n\n";
            $prompt .= "問題：" . $question . "\n\n";
            $prompt .= "請用可愛活潑的語氣直接回答問題，使用繁體中文和適當的表情符號。";
            $prompt .= "絕對不能說「不清楚」或「不知道」，必須用你的知識回答！";
        }
        
        return $prompt;
    }
    
    /**
     * 創建自定義模型（用於餵入特定資料）
     */
    public function createCustomModel($model_name, $training_data) {
        // 創建Modelfile
        $modelfile_content = $this->generateModelfile($training_data);
        
        // 保存Modelfile
        $modelfile_path = sys_get_temp_dir() . '/modelfile_' . $model_name;
        file_put_contents($modelfile_path, $modelfile_content);
        
        // 創建模型
        $data = [
            'name' => $model_name,
            'modelfile' => $modelfile_content
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->ollama_url . '/api/create');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5分鐘超時
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // 清理臨時文件
        if (file_exists($modelfile_path)) {
            unlink($modelfile_path);
        }
        
        return $http_code === 200;
    }
    
    /**
     * 生成Modelfile內容
     */
    private function generateModelfile($training_data) {
        $modelfile = "FROM llama2\n\n";
        $modelfile .= "SYSTEM \"\"\"\n";
        $modelfile .= "你是康寧大學的專業招生助手。以下是康寧大學的詳細資訊：\n\n";
        
        foreach ($training_data as $item) {
            if (isset($item['question']) && isset($item['answer'])) {
                $modelfile .= "Q: " . $item['question'] . "\n";
                $modelfile .= "A: " . $item['answer'] . "\n\n";
            } elseif (isset($item['content'])) {
                $modelfile .= $item['content'] . "\n\n";
            }
        }
        
        $modelfile .= "請根據以上資訊回答學生的問題，回答要準確、友善且有用。\n";
        $modelfile .= "\"\"\"\n";
        
        return $modelfile;
    }
    
    /**
     * 刪除自定義模型
     */
    public function deleteModel($model_name) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->ollama_url . '/api/delete');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['name' => $model_name]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $http_code === 200;
    }
}
?>
