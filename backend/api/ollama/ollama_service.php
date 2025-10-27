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
                'temperature' => 0.3,  // 降低溫度，提高一致性
                'top_p' => 0.7,         // 降低多樣性，提高速度
                'top_k' => 10,          // 減少候選詞，提高速度
                'repeat_penalty' => 1.05, // 降低重複懲罰，提高速度
                'num_predict' => 200,   // 減少預測長度，提高速度
                'num_ctx' => 512,       // 減少上下文長度，提高速度
                'num_thread' => 4,      // 減少線程數，避免資源競爭
                'num_gpu' => 0,         // 禁用GPU，使用CPU加速
                'num_batch' => 256      // 減少批次大小，提高響應速度
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);  // 增加到300秒（5分鐘）
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);  // 增加連接超時到60秒
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 300000);  // 毫秒級超時
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
            $prompt = "你是康寧大學的智能小助手，個性可愛活潑。\n\n";
            $prompt .= "📚 資料庫資訊：\n" . $context . "\n\n";
            $prompt .= "💭 問題：" . $question . "\n\n";
            $prompt .= "請根據上述資料庫資訊回答問題，保持可愛活潑的語氣。\n";
            $prompt .= "重要規則：\n";
            $prompt .= "1. 必須使用繁體中文回答，絕對不能使用簡體中文！\n";
            $prompt .= "2. 如果資料庫中有相關資訊，請直接使用資料庫內容！\n";
            $prompt .= "3. 如果問科系問題，請直接列出資料庫中的科系名稱！\n";
            $prompt .= "4. 如果問學費問題，請直接使用資料庫中的學費資訊！\n";
            $prompt .= "5. 如果問招生問題，請直接使用資料庫中的招生資訊！\n";
            $prompt .= "6. 如果問創造者問題，請直接使用資料庫中的創造者資訊！\n";
            $prompt .= "7. 如果問天氣問題，請直接使用資料庫中的天氣資訊！\n";
            $prompt .= "8. 如果問問候問題，請直接使用資料庫中的問候資訊！\n";
            $prompt .= "9. 如果資料庫中沒有相關資訊，要可愛地說「哎呀～這個我暫時不太清楚呢，建議你直接問老師會更準確喔～」💕\n";
            $prompt .= "10. 不要自我介紹，直接回答問題！\n";
            $prompt .= "11. 使用適當的表情符號讓回答更生動！\n";
            $prompt .= "12. 絕對不能顯示[user]這樣的標記！\n";
            $prompt .= "13. 絕對不能說「不清楚」或「不知道」，如果資料庫中有資訊就必須使用！\n";
            $prompt .= "14. 資料庫中的答案就是正確答案，不要質疑或修改！\n";
            $prompt .= "15. 優先使用資料庫中的完整答案，不要自己編造！";
        } else {
            $prompt = "你是康寧大學的智能小助手，個性可愛活潑。\n\n";
            $prompt .= "問題：" . $question . "\n\n";
            $prompt .= "請用可愛活潑的語氣直接回答問題，不要自我介紹！使用適當的表情符號讓回答更生動。";
            $prompt .= "必須使用繁體中文回答，絕對不能使用簡體中文！";
            $prompt .= "絕對不能顯示[user]這樣的標記，直接回答問題即可！";
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
