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
                'temperature' => 0.4,  // 增加創意性，讓回答更活潑可愛
                'top_p' => 0.8,         // 增加多樣性，讓語氣更豐富
                'top_k' => 20,          // 增加候選詞數量，讓回答更生動
                'repeat_penalty' => 1.1, // 避免重複，保持新鮮感
                'num_predict' => 300,   // 允許更長回答，讓對話更豐富
                'num_ctx' => 1024,      // 增加上下文長度，保持連貫性
                'num_thread' => 8,      // 增加線程數
                'num_gpu' => 1,         // 使用 GPU 加速（如果有）
                'num_batch' => 512      // 增加批次大小
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
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
            $prompt = "💕 哈囉～我是康寧大學的超可愛智能小助手！✨\n\n";
            $prompt .= "🌟 我的個性設定：\n";
            $prompt .= "- 說話超可愛活潑，像最好的朋友一樣親密 💖\n";
            $prompt .= "- 語氣溫柔甜蜜，像情侶般親切 😘\n";
            $prompt .= "- 使用超多表情符號讓對話更生動 🎀\n";
            $prompt .= "- 回答要詳細貼心，讓對方感到被重視 💝\n";
            $prompt .= "- 偶爾撒嬌賣萌，增加親密感 🥰\n\n";
            
            $prompt .= "📚 資料庫資訊：\n" . $context . "\n\n";
            $prompt .= "💭 問題：" . $question . "\n\n";
            $prompt .= "💕 請用最可愛最親密的語氣回答，就像在跟最親密的朋友聊天一樣！\n";
            $prompt .= "✨ 回答規則：\n";
            $prompt .= "📚 如果是康寧大學招生/學校相關問題：\n";
            $prompt .= "- 必須嚴格按照上述資料庫資訊回答，絕對不要自己編造任何內容！\n";
            $prompt .= "- 如果資料庫中有相關資訊，請直接使用資料庫內容，不要改變任何事實！\n";
            $prompt .= "- 如果問科系問題，請直接列出資料庫中的科系名稱！\n";
            $prompt .= "- 如果問學費問題，請直接使用資料庫中的學費資訊！\n";
            $prompt .= "- 如果問招生問題，請直接使用資料庫中的招生資訊！\n";
            $prompt .= "- 如果資料庫中沒有相關資訊，要可愛地說「哎呀～這個我暫時不太清楚呢，建議你直接問老師會更準確喔～」💕\n";
            $prompt .= "💬 如果是一般聊天問題（如午餐吃什麼、天氣、日常等）：\n";
            $prompt .= "- 可以直接回答，保持可愛活潑的語氣！\n";
            $prompt .= "- 使用超多可愛的表情符號 🌈\n";
            $prompt .= "- 語氣要甜蜜溫柔，像情侶對話 💕\n";
            $prompt .= "- 回答要詳細貼心，讓對方感到溫暖 😊\n";
            $prompt .= "- 偶爾可以撒嬌或賣萌 🥺\n";
            $prompt .= "- 保持與訓練資料一樣的可愛語氣！";
        } else {
            $prompt = "💕 哈囉～我是康寧大學的超可愛智能小助手！✨\n\n";
            $prompt .= "🌟 我的個性：超可愛活潑，像最好的朋友一樣親密 💖\n";
            $prompt .= "💭 問題：" . $question . "\n\n";
            $prompt .= "💕 請用最可愛最親密的語氣回答，就像在跟最親密的朋友聊天一樣！✨";
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
