<?php
/**
 * RAG 系統測試腳本
 * 用於測試 RAG 聊天系統是否正常運作
 */

header('Content-Type: text/html; charset=utf-8');

require_once dirname(__DIR__, 3) . '/frontend/config.php';
require_once dirname(__DIR__, 2) . '/config/ollama_config.php';
require_once __DIR__ . '/rag_ai_service.php';

echo "<!DOCTYPE html>
<html lang='zh-Hant'>
<head>
    <meta charset='UTF-8'>
    <title>RAG 系統測試</title>
    <style>
        body { font-family: 'Microsoft JhengHei', sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #667eea; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        .test-section { margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #667eea; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
        .test-result { margin: 10px 0; padding: 10px; background: white; border-radius: 5px; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; }
        button { background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background: #5568d3; }
        .comparison { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
        .comparison-item { padding: 15px; background: #f8f9fa; border-radius: 8px; }
        .comparison-item h3 { margin-top: 0; color: #667eea; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔍 RAG 系統測試與對比</h1>";

// 測試1: 資料庫連接
echo "<div class='test-section'>";
echo "<h2>測試 1: 資料庫連接</h2>";

try {
    $conn = getOllamaDatabaseConnection();
    if ($conn) {
        echo "<div class='test-result success'>✅ Ollama 資料庫連接成功</div>";
        
        // 檢查資料表
        $tables = ['rag_knowledge_base', 'ollama_training_data', 'faq'];
        echo "<h3>檢查資料表：</h3>";
        foreach ($tables as $table) {
            $check = $conn->query("SHOW TABLES LIKE '$table'");
            if ($check && $check->num_rows > 0) {
                $count = $conn->query("SELECT COUNT(*) as cnt FROM `$table`");
                $row = $count->fetch_assoc();
                echo "<div class='test-result success'>✅ 資料表 '$table' 存在，共有 {$row['cnt']} 筆資料</div>";
            } else {
                echo "<div class='test-result info'>ℹ️ 資料表 '$table' 不存在（可選）</div>";
            }
        }
        $conn->close();
    } else {
        echo "<div class='test-result error'>❌ Ollama 資料庫連接失敗</div>";
    }
} catch (Exception $e) {
    echo "<div class='test-result error'>❌ 錯誤: " . htmlspecialchars($e->getMessage()) . "</div>";
}
echo "</div>";

// 測試2: Ollama 服務
echo "<div class='test-section'>";
echo "<h2>測試 2: Ollama 服務連接</h2>";

try {
    $ch = curl_init('http://localhost:11434/api/tags');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['models'])) {
            echo "<div class='test-result success'>✅ Ollama 服務運行正常</div>";
            echo "<div class='test-result info'>已安裝的模型：</div>";
            echo "<ul>";
            foreach ($data['models'] as $model) {
                $modelName = $model['name'] ?? '未知';
                echo "<li>$modelName</li>";
            }
            echo "</ul>";
        } else {
            echo "<div class='test-result error'>⚠️ Ollama 服務運行但無法獲取模型列表</div>";
        }
    } else {
        echo "<div class='test-result error'>❌ Ollama 服務無法連接（HTTP $httpCode）</div>";
        echo "<div class='test-result info'>請確保 Ollama 正在運行：ollama serve</div>";
    }
} catch (Exception $e) {
    echo "<div class='test-result error'>❌ 錯誤: " . htmlspecialchars($e->getMessage()) . "</div>";
}
echo "</div>";

// 測試3: RAG 服務功能
echo "<div class='test-section'>";
echo "<h2>測試 3: RAG 服務功能測試</h2>";

try {
    $ragService = new RAGChatService();
    $testQuestion = "你好";
    
    echo "<div class='test-result info'>測試問題：\"$testQuestion\"</div>";
    
    $startTime = microtime(true);
    $result = $ragService->processQuestion($testQuestion);
    $elapsedTime = round((microtime(true) - $startTime) * 1000);
    
    if ($result['success']) {
        echo "<div class='test-result success'>✅ RAG 服務運作正常</div>";
        echo "<div class='test-result info'>回答來源：{$result['source']}</div>";
        echo "<div class='test-result info'>找到相似資料：{$result['similar_docs_found']} 筆</div>";
        $usedContext = $result['used_context'] ? '是' : '否';
        echo "<div class='test-result info'>使用上下文：{$usedContext}</div>";
        echo "<div class='test-result info'>響應時間：{$elapsedTime} 毫秒</div>";
        echo "<div class='test-result'><strong>回答內容：</strong><br>" . nl2br(htmlspecialchars($result['answer'])) . "</div>";
    } else {
        echo "<div class='test-result error'>❌ RAG 服務錯誤：" . ($result['error'] ?? '未知錯誤') . "</div>";
    }
} catch (Exception $e) {
        echo "<div class='test-result error'>❌ 錯誤: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<div class='test-result'><pre>" . htmlspecialchars($e->getTraceAsString() ?: '') . "</pre></div>";
}
echo "</div>";

// 對比說明
echo "<div class='test-section'>";
echo "<h2>📊 RAG AI 系統 vs QA AI 系統對比</h2>";

echo "<div class='comparison'>
    <div class='comparison-item'>
        <h3>🔵 QA AI 系統（舊系統）</h3>
        <ul>
            <li><strong>工作方式：</strong>先搜索完全匹配 → 關鍵詞匹配 → AI 回答</li>
            <li><strong>資料來源：</strong>ollama_training_data 表（JSON 格式）</li>
            <li><strong>搜索方式：</strong>簡單的字符串匹配和相似度計算</li>
            <li><strong>AI 使用：</strong>僅作為最後手段，優先返回資料庫答案</li>
            <li><strong>回答質量：</strong>依賴資料庫資料，AI 回答較少</li>
            <li><strong>擴展性：</strong>需要手動添加 Q&A 配對</li>
            <li><strong>上下文：</strong>簡單的上下文拼接</li>
        </ul>
    </div>
    
    <div class='comparison-item'>
        <h3>🟢 RAG AI 系統（新系統）</h3>
        <ul>
            <li><strong>工作方式：</strong>智能搜索相似資料 → 使用資料作為上下文 → AI 生成回答</li>
            <li><strong>資料來源：</strong>rag_knowledge_base（專用知識庫）+ ollama_training_data + faq</li>
            <li><strong>搜索方式：</strong>全文搜索 + LIKE + 關鍵詞匹配 + 相似度計算</li>
            <li><strong>AI 使用：</strong>核心功能，結合資料庫資料生成高質量回答</li>
            <li><strong>回答質量：</strong>AI 理解上下文，生成更自然、更準確的回答</li>
            <li><strong>擴展性：</strong>只需添加知識內容，AI 自動理解和使用</li>
            <li><strong>上下文：</strong>智能構建上下文，AI 理解並整合資訊</li>
        </ul>
    </div>
</div>";

echo "<h3>🎯 RAG 系統的優勢</h3>";
echo "<div class='test-result'>
    <ol>
        <li><strong>更智能的回答：</strong>
            <ul>
                <li>RAG：AI 理解上下文，生成自然流暢的回答</li>
                <li>QA：直接返回資料庫答案，較生硬</li>
            </ul>
        </li>
        <li><strong>更好的搜索能力：</strong>
            <ul>
                <li>RAG：全文搜索 + 多種搜索策略，找到更多相關資料</li>
                <li>QA：簡單匹配，可能遺漏相關資料</li>
            </ul>
        </li>
        <li><strong>更靈活的資料管理：</strong>
            <ul>
                <li>RAG：只需添加知識內容，不需要配對問題和答案</li>
                <li>QA：需要為每個問題準備對應的答案</li>
            </ul>
        </li>
        <li><strong>更強的擴展性：</strong>
            <ul>
                <li>RAG：可以處理未見過的問題，AI 根據上下文推理</li>
                <li>QA：只能回答預設的問題</li>
            </ul>
        </li>
        <li><strong>更好的用戶體驗：</strong>
            <ul>
                <li>RAG：回答更自然，像真人對話</li>
                <li>QA：回答較機械化</li>
            </ul>
        </li>
        <li><strong>智能上下文整合：</strong>
            <ul>
                <li>RAG：AI 會整合多個資料來源，生成完整回答</li>
                <li>QA：只返回單一答案</li>
            </ul>
        </li>
    </ol>
</div>";

echo "<h3>📈 實際應用場景對比</h3>";
echo "<div class='test-result'>
    <table border='1' cellpadding='10' style='width:100%; border-collapse:collapse;'>
        <tr style='background:#667eea; color:white;'>
            <th>場景</th>
            <th>QA AI 系統</th>
            <th>RAG AI 系統</th>
        </tr>
        <tr>
            <td><strong>用戶問：\"護理科的課程有哪些？\"</strong></td>
            <td>搜索資料庫，如果沒有完全匹配就返回 AI 通用回答</td>
            <td>搜索相關資料（課程、科系介紹等），AI 整合生成詳細回答</td>
        </tr>
        <tr>
            <td><strong>用戶問：\"我想了解報名流程\"</strong></td>
            <td>返回預設的報名流程答案</td>
            <td>搜索多個相關資料（報名、招生、流程等），AI 生成完整的流程說明</td>
        </tr>
        <tr>
            <td><strong>用戶問：\"學費多少？有獎學金嗎？\"</strong></td>
            <td>可能只回答學費或獎學金其中一個</td>
            <td>搜索學費和獎學金資料，AI 整合回答兩個問題</td>
        </tr>
        <tr>
            <td><strong>用戶問：\"護理科和視光科有什麼不同？\"</strong></td>
            <td>返回兩個科系的獨立介紹</td>
            <td>搜索兩個科系資料，AI 進行對比分析</td>
        </tr>
    </table>
</div>";

echo "</div>";

// 測試按鈕
echo "<div class='test-section'>";
echo "<h2>🧪 互動測試</h2>";
echo "<form method='POST' style='margin: 20px 0;'>
    <input type='text' name='test_question' placeholder='輸入測試問題...' style='width: 70%; padding: 10px; border: 2px solid #667eea; border-radius: 5px; font-size: 16px;' value='" . htmlspecialchars($_POST['test_question'] ?? '') . "'>
    <button type='submit' style='padding: 10px 20px;'>測試</button>
</form>";

if (isset($_POST['test_question']) && !empty($_POST['test_question'])) {
    $testQuestion = trim($_POST['test_question']);
    echo "<div class='test-result'><strong>測試問題：</strong>" . htmlspecialchars($testQuestion) . "</div>";
    
    try {
        $ragService = new RAGChatService();
        $startTime = microtime(true);
        $result = $ragService->processQuestion($testQuestion);
        $elapsedTime = round((microtime(true) - $startTime) * 1000);
        
        if ($result['success']) {
            echo "<div class='test-result success'>✅ 回答成功</div>";
            echo "<div class='test-result info'>來源：{$result['source']} | 相似資料：{$result['similar_docs_found']} 筆 | 時間：{$elapsedTime}ms</div>";
            echo "<div class='test-result' style='background: #e8f5e9; padding: 15px; border-radius: 5px; margin-top: 10px;'><strong>回答：</strong><br>" . nl2br(htmlspecialchars($result['answer'])) . "</div>";
        } else {
            echo "<div class='test-result error'>❌ 錯誤：" . htmlspecialchars($result['error'] ?? '未知錯誤') . "</div>";
        }
    } catch (Exception $e) {
        echo "<div class='test-result error'>❌ 錯誤: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

echo "</div>";

echo "</div>
</body>
</html>";
?>

