<?php
// 載入 session 配置
require_once 'session_config.php';

// 檢查登入狀態（與 header.php 保持一致）
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

// 引入資料庫配置
require_once 'config.php';

// 從資料庫讀取 FAQ 資料
function getFAQFromDatabase() {
    try {
        $conn = getDatabaseConnection();
        
        // 假設 FAQ 資料表名稱為 'faq' 或 'qa'，欄位為 'question' 和 'answer'
        // 如果資料表名稱或欄位不同，請告訴我正確的名稱
        $sql = "SELECT question, answer FROM faq ORDER BY id ASC";
        $result = $conn->query($sql);
        
        $faqs = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $faqs[] = [
                    'question' => $row['question'],
                    'answer' => $row['answer']
                ];
            }
        }
        
        $conn->close();
        return $faqs;
        
    } catch (Exception $e) {
        // 如果資料庫連接失敗，返回錯誤資訊
        return ['error' => '資料庫連接錯誤: ' . $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
	<meta charset="UTF-8">
	<title>康寧大學招生資訊</title>
	<link rel="stylesheet" href="assets/csp/QA.css">
</head>
<?php include("share/header.php"); ?>
<body>
	<main>
		<h2>康寧大學招生資訊</h2>
        <div id="faq-container"></div>
        
        <!-- 智能問答留言區 -->
        <section class="qa-chat-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>🌟 康寧大學可愛小助手 ✨</h3>
                <div>
                    <span class="badge bg-success me-2" id="ai-status">🤖 AI驅動</span>
                    <span class="badge bg-info">⚡ Ollama增強</span>
                </div>
            </div>
            <div class="chat-container">
                <div class="chat-messages" id="chat-messages">
                    <div class="chat-message bot-message">
                        <div class="message-content">
                            🌟 哈囉！我是康寧大學的可愛小助手～✨<br>
                            🤖 我擁有超強的 AI 大腦，專門為您解答所有招生疑問！<br>
                            💡 想知道科系資訊？學費多少？申請流程？通通問我就對了！<br>
                            🎯 我會用最準確的資料庫資訊為您服務，讓您的升學之路更順利～<br>
                            💝 快來和我聊天吧！我已經準備好為您解答囉～ 😊
                        </div>
                        <div class="message-time"></div>
                    </div>
                </div>
                <div class="chat-input-container">
                    <div class="input-group mb-2">
                        <input type="text" id="user-question" class="form-control" placeholder="💭 有什麼想問我的嗎？我很樂意為您解答～" maxlength="500">
                        <button id="send-question" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> 🚀 發送
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-lightbulb"></i> 💡 小提示：可以問我科系、學費、招生、校園生活等任何問題喔～
                        </small>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="use-ai" checked>
                            <label class="form-check-label" for="use-ai">
                                🤖 使用AI回答
                            </label>
                            <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="testAIConnection()">
                                🔧 測試AI連接
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
	</main>
	<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
	<script>
$(document).ready(function() {
    let qaKeywords = null; // 儲存關鍵詞資料
    let faqData = []; // 儲存FAQ資料，用於相似度匹配
    
    // 1. 載入關鍵詞資料
    $.getJSON("assets/qa_keywords.json", function(data) {
        qaKeywords = data;
        console.log("關鍵詞資料載入成功", qaKeywords);
    }).fail(function() {
        console.error("關鍵詞資料載入失敗");
    });

    // 2. 從資料庫載入 FAQ 資料
    function loadFAQFromDatabase() {
        $.ajax({
            url: 'get_faq.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.error) {
                    $("#faq-container").html(`
                        <div style="text-align: center; color: #d9534f; padding: 20px; background: #f8d7da; border-radius: 5px; margin: 20px 0;">
                            <h4>❌ 資料庫連接錯誤</h4>
                            <p>${data.error}</p>
                            <p>請檢查：</p>
                            <ul style="text-align: left; display: inline-block;">
                                <li>資料庫伺服器是否正常運行</li>
                                <li>網路連接是否正常</li>
                                <li>資料表是否存在</li>
                            </ul>
                        </div>
                    `);
                    return;
                }
                
                if (!data || data.length === 0) {
                    $("#faq-container").html(`
                        <div style="text-align: center; color: #856404; padding: 20px; background: #fff3cd; border-radius: 5px; margin: 20px 0;">
                            <h4>📝 暫無 FAQ 資料</h4>
                            <p>資料庫中尚未新增任何 FAQ 內容</p>
                        </div>
                    `);
                    return;
                }

                // 保存FAQ資料到全局變量，用於相似度匹配
                faqData = data;
                console.log("FAQ資料已載入，共", faqData.length, "筆");
                
                let html = "";
                data.forEach(function(item) {
                    html += `
                        <div class="faq-item">
                            <div class="faq-question">${item.question}</div>
                            <div class="faq-answer">
                                <div class="faq-content">${item.answer}</div>
                            </div>
                        </div>
                    `;
                });
                $("#faq-container").html(html);

                // 3. 啟用 FAQ 動畫
                $('.faq-content').hide();

                $('.faq-question').click(function() {
                    const $this = $(this);
                    const $answer = $this.next('.faq-answer');
                    const $content = $answer.find('.faq-content');

                    if ($this.hasClass('active')) {
                        $content.slideUp(300, function() {
                            $answer.removeClass('show');
                        });
                        $this.removeClass('active');
                    } else {
                        $('.faq-question').removeClass('active');
                        $('.faq-answer .faq-content').slideUp(300);
                        $('.faq-answer').removeClass('show');

                        $answer.addClass('show');
                        $content.slideDown(300);
                        $this.addClass('active');
                    }
                });
            },
            error: function(xhr, status, error) {
                $("#faq-container").html(`
                    <div style="text-align: center; color: #d9534f; padding: 20px; background: #f8d7da; border-radius: 5px; margin: 20px 0;">
                        <h4>❌ 載入 FAQ 失敗</h4>
                        <p>AJAX 請求錯誤: ${error}</p>
                        <p>狀態碼: ${xhr.status}</p>
                        <p>請檢查 get_faq.php 檔案是否存在</p>
                    </div>
                `);
            }
        });
    }

    // 載入資料庫 FAQ
    loadFAQFromDatabase();
    
    // 檢查Ollama服務狀態
    checkOllamaHealth();
    
    // 檢查Ollama健康狀態
    function checkOllamaHealth() {
        console.log('🔍 開始檢查Ollama健康狀態...');
        $.get('../backend/api/ollama/ollama_api.php?action=check_health')
            .done(function(response) {
                console.log('✅ Ollama健康檢查響應:', response);
                if (response.success) {
                    $('#ai-status').removeClass('bg-warning').addClass('bg-success').text('🤖 AI驅動');
                    console.log('🎉 Ollama服務正常，AI可用');
                } else {
                    $('#ai-status').removeClass('bg-success').addClass('bg-warning').text('⚠️ AI離線');
                    console.warn('⚠️ Ollama服務異常:', response.message);
                }
            })
            .fail(function(xhr, status, error) {
                console.error('❌ Ollama健康檢查失敗:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error
                });
                $('#ai-status').removeClass('bg-success').addClass('bg-warning').text('❌ AI離線');
                console.error('❌ 無法連接到Ollama API，請檢查服務器是否運行');
            });
    }

    // 同義詞映射（將相似表達統一化）
    function normalizeSynonyms(text) {
        if (!text) return text;
        
        // 同義詞替換表
        const synonymMap = {
            '有那些': '有哪些',
            '那些': '哪些',
            '康寧': '康寧大學', // 將"康寧"統一為"康寧大學"
            '附近': '', // 移除位置修飾詞，因為不影響核心問題
            '周圍': '',
            '周邊': '',
            '旁邊': '',
            '周圍的': '',
            '可以': '',
            '能夠': '',
            '能': '',
            '會': '',
            '要': '',
            '想': '',
            '想要': '',
            '請問': '',
            '請': '',
            '告訴': '',
            '告訴我': ''
        };
        
        let normalized = text;
        for (const [synonym, replacement] of Object.entries(synonymMap)) {
            // 使用不區分大小寫的替換
            normalized = normalized.replace(new RegExp(synonym, 'gi'), replacement);
        }
        
        return normalized.trim();
    }
    
    // 提取關鍵詞（移除停用詞和標點）
    function extractKeywords(text) {
        if (!text) return [];
        
        // 先進行同義詞標準化
        text = normalizeSynonyms(text);
        
        // 中文停用詞列表（常見的無意義詞）
        const stopWords = [
            '的', '了', '在', '是', '我', '有', '和', '就', '不', '人', '都', '一', '一個', 
            '上', '也', '很', '到', '說', '要', '去', '你', '會', '著', '沒有', '看', '好', 
            '自己', '這', '那', '個', '嗎', '呢', '啊', '吧', '麼', '什麼', '怎麼'
        ];
        
        // 移除標點符號和空格，轉為小寫
        let cleaned = text.toLowerCase().trim();
        cleaned = cleaned.replace(/[，。！？、；：""''（）【】《》\s]/g, '');
        
        // 提取字符（對於中文，每個字符都是一個詞）
        const chars = cleaned.split('');
        
        // 過濾停用詞
        const keywords = chars.filter(char => {
            return char.length > 0 && !stopWords.includes(char);
        });
        
        return keywords;
    }
    
    // 計算Jaccard相似度（基於關鍵詞集合）
    function jaccardSimilarity(set1, set2) {
        if (set1.length === 0 && set2.length === 0) return 1;
        if (set1.length === 0 || set2.length === 0) return 0;
        
        const set1Set = new Set(set1);
        const set2Set = new Set(set2);
        
        // 計算交集
        let intersection = 0;
        set1Set.forEach(item => {
            if (set2Set.has(item)) {
                intersection++;
            }
        });
        
        // 計算並集
        const union = new Set([...set1, ...set2]);
        
        return intersection / union.size;
    }
    
    // 計算n-gram相似度（考慮詞序）
    function ngramSimilarity(str1, str2, n = 2) {
        if (!str1 || !str2) return 0;
        
        const getNgrams = (str, n) => {
            const ngrams = [];
            for (let i = 0; i <= str.length - n; i++) {
                ngrams.push(str.substr(i, n));
            }
            return ngrams;
        };
        
        const ngrams1 = getNgrams(str1, n);
        const ngrams2 = getNgrams(str2, n);
        
        if (ngrams1.length === 0 && ngrams2.length === 0) return 1;
        if (ngrams1.length === 0 || ngrams2.length === 0) return 0;
        
        const set1 = new Set(ngrams1);
        const set2 = new Set(ngrams2);
        
        let intersection = 0;
        set1.forEach(item => {
            if (set2.has(item)) {
                intersection++;
            }
        });
        
        const union = new Set([...ngrams1, ...ngrams2]);
        
        return intersection / union.size;
    }
    
    // 計算兩個字符串的相似度（0-100）- 改進版
    function calculateSimilarity(str1, str2) {
        if (!str1 || !str2) return 0;
        
        // 先進行同義詞標準化
        const normalized1 = normalizeSynonyms(str1.toLowerCase().trim());
        const normalized2 = normalizeSynonyms(str2.toLowerCase().trim());
        
        // 轉換為小寫並去除空格
        const s1 = normalized1;
        const s2 = normalized2;
        
        // 完全匹配（包括標準化後的匹配）
        if (s1 === s2) return 100;
        
        // 原始文本也檢查（以防標準化後完全相同）
        const original1 = str1.toLowerCase().trim();
        const original2 = str2.toLowerCase().trim();
        if (original1 === original2) return 100;
        
        // 1. 關鍵詞提取和Jaccard相似度
        const keywords1 = extractKeywords(s1);
        const keywords2 = extractKeywords(s2);
        const jaccardSim = jaccardSimilarity(keywords1, keywords2) * 100;
        
        // 2. 包含關係匹配（提高權重）- 改進版
        let containScore = 0;
        const s1NoSpace = s1.replace(/\s+/g, '');
        const s2NoSpace = s2.replace(/\s+/g, '');
        
        // 完全包含關係
        if (s1NoSpace.includes(s2NoSpace) || s2NoSpace.includes(s1NoSpace)) {
            const minLen = Math.min(s1NoSpace.length, s2NoSpace.length);
            const maxLen = Math.max(s1NoSpace.length, s2NoSpace.length);
            containScore = (minLen / maxLen) * 100;
        } else {
            // 部分包含關係：檢查是否一個問題的核心部分包含在另一個問題中
            // 提取核心部分（移除常見修飾詞後的主要內容）
            const getCorePart = (text) => {
                return text.replace(/[附近周圍周邊旁邊的可以能夠能會要想想要請問請告訴告訴我]/g, '').trim();
            };
            const core1 = getCorePart(s1NoSpace);
            const core2 = getCorePart(s2NoSpace);
            
            if (core1 && core2) {
                if (s1NoSpace.includes(core2) || s2NoSpace.includes(core1)) {
                    const minCoreLen = Math.min(core1.length, core2.length);
                    const maxCoreLen = Math.max(core1.length, core2.length);
                    if (maxCoreLen > 0) {
                        containScore = (minCoreLen / maxCoreLen) * 80; // 部分包含得分較低
                    }
                }
            }
        }
        
        // 3. N-gram相似度（考慮詞序）
        const ngramSim = ngramSimilarity(s1NoSpace, s2NoSpace, 2) * 100;
        
        // 4. Levenshtein距離（編輯距離）
        const distance = levenshteinDistance(s1NoSpace, s2NoSpace);
        const maxLen = Math.max(s1NoSpace.length, s2NoSpace.length);
        const editSim = maxLen > 0 ? (1 - distance / maxLen) * 100 : 0;
        
        // 5. 共同字符比例
        const commonChars = countCommonChars(s1NoSpace, s2NoSpace);
        const charSim = maxLen > 0 ? (commonChars / maxLen) * 100 : 0;
        
        // 6. 關鍵詞匹配度（檢查重要關鍵詞是否都出現）
        const importantKeywords1 = keywords1.filter(k => k.length > 1); // 長度大於1的關鍵詞
        const importantKeywords2 = keywords2.filter(k => k.length > 1);
        let keywordMatchScore = 0;
        if (importantKeywords1.length > 0 && importantKeywords2.length > 0) {
            const matchedKeywords = importantKeywords1.filter(k => 
                importantKeywords2.some(k2 => k2.includes(k) || k.includes(k2))
            );
            keywordMatchScore = (matchedKeywords.length / Math.max(importantKeywords1.length, importantKeywords2.length)) * 100;
        }
        
        // 加權平均（關鍵詞匹配和Jaccard相似度權重較高）
        const finalScore = (
            jaccardSim * 0.35 +           // 關鍵詞集合相似度
            keywordMatchScore * 0.25 +    // 重要關鍵詞匹配
            containScore * 0.15 +         // 包含關係
            ngramSim * 0.10 +             // N-gram相似度
            editSim * 0.10 +              // 編輯距離
            charSim * 0.05                // 字符相似度
        );
        
        return Math.min(100, Math.max(0, finalScore));
    }
    
    // Levenshtein距離算法（編輯距離）
    function levenshteinDistance(str1, str2) {
        const len1 = str1.length;
        const len2 = str2.length;
        
        if (len1 === 0) return len2;
        if (len2 === 0) return len1;
        
        const matrix = [];
        
        // 初始化矩陣
        for (let i = 0; i <= len1; i++) {
            matrix[i] = [i];
        }
        for (let j = 0; j <= len2; j++) {
            matrix[0][j] = j;
        }
        
        // 填充矩陣
        for (let i = 1; i <= len1; i++) {
            for (let j = 1; j <= len2; j++) {
                if (str1[i - 1] === str2[j - 1]) {
                    matrix[i][j] = matrix[i - 1][j - 1];
                } else {
                    matrix[i][j] = Math.min(
                        matrix[i - 1][j] + 1,     // 刪除
                        matrix[i][j - 1] + 1,     // 插入
                        matrix[i - 1][j - 1] + 1  // 替換
                    );
                }
            }
        }
        
        return matrix[len1][len2];
    }
    
    // 計算共同字符數量
    function countCommonChars(str1, str2) {
        const chars1 = str1.split('');
        const chars2 = str2.split('');
        let common = 0;
        
        const charCount1 = {};
        const charCount2 = {};
        
        // 統計字符出現次數
        chars1.forEach(char => {
            charCount1[char] = (charCount1[char] || 0) + 1;
        });
        chars2.forEach(char => {
            charCount2[char] = (charCount2[char] || 0) + 1;
        });
        
        // 計算共同字符
        Object.keys(charCount1).forEach(char => {
            if (charCount2[char]) {
                common += Math.min(charCount1[char], charCount2[char]);
            }
        });
        
        return common;
    }
    
    // 在FAQ資料中尋找相似問題 - 改進版
    function findSimilarFAQ(question, threshold = 60) {
        if (!faqData || faqData.length === 0) {
            return null;
        }
        
        let bestMatch = null;
        let bestSimilarity = 0;
        const candidates = []; // 候選匹配
        
        // 提取用戶問題的關鍵詞
        const userKeywords = extractKeywords(question);
        const userKeywordsSet = new Set(userKeywords);
        
        for (let i = 0; i < faqData.length; i++) {
            const faqQuestion = faqData[i].question;
            
            // 1. 計算綜合相似度
            const similarity = calculateSimilarity(question, faqQuestion);
            
            // 2. 檢查關鍵詞匹配度（額外加分）
            const faqKeywords = extractKeywords(faqQuestion);
            const faqKeywordsSet = new Set(faqKeywords);
            
            // 計算關鍵詞交集比例
            let keywordOverlap = 0;
            if (userKeywordsSet.size > 0 && faqKeywordsSet.size > 0) {
                let intersection = 0;
                userKeywordsSet.forEach(k => {
                    if (faqKeywordsSet.has(k)) {
                        intersection++;
                    }
                });
                keywordOverlap = intersection / Math.max(userKeywordsSet.size, faqKeywordsSet.size);
            }
            
            // 如果關鍵詞匹配度高，給予額外加分
            let adjustedSimilarity = similarity;
            if (keywordOverlap > 0.5) {
                adjustedSimilarity = Math.min(100, similarity + (keywordOverlap * 15)); // 最多加15分
            }
            
            // 3. 檢查是否包含核心關鍵詞（如"康寧大學"、"美食"等）- 改進版
            const coreKeywords = [
                {word: '康寧大學', aliases: ['康寧', '康寧大']}, // 支持別名
                {word: '美食', aliases: ['食物', '餐廳', '小吃']},
                {word: '科系', aliases: ['科', '系', '專業']},
                {word: '學費', aliases: ['費用', '學雜費', '收費']},
                {word: '招生', aliases: ['招收', '錄取']},
                {word: '報名', aliases: ['申請', '登記']},
                {word: '申請', aliases: ['報名', '登記']}
            ];
            
            let coreKeywordMatch = 0;
            let coreKeywordBonus = 0;
            
            coreKeywords.forEach(core => {
                // 檢查主關鍵詞
                const hasMainInQuestion = question.includes(core.word);
                const hasMainInFAQ = faqQuestion.includes(core.word);
                
                // 檢查別名
                let hasAliasInQuestion = false;
                let hasAliasInFAQ = false;
                core.aliases.forEach(alias => {
                    if (question.includes(alias)) hasAliasInQuestion = true;
                    if (faqQuestion.includes(alias)) hasAliasInFAQ = true;
                });
                
                // 如果兩邊都有主關鍵詞或別名，則匹配
                if ((hasMainInQuestion || hasAliasInQuestion) && (hasMainInFAQ || hasAliasInFAQ)) {
                    coreKeywordMatch++;
                    // 如果兩邊都有主關鍵詞，額外加分
                    if (hasMainInQuestion && hasMainInFAQ) {
                        coreKeywordBonus += 3;
                    } else {
                        coreKeywordBonus += 2; // 別名匹配加分較少
                    }
                }
            });
            
            // 核心關鍵詞匹配加分（更激進的加分策略）
            if (coreKeywordMatch > 0) {
                // 基礎加分 + 額外獎勵
                adjustedSimilarity = Math.min(100, adjustedSimilarity + (coreKeywordMatch * 8) + coreKeywordBonus);
            }
            
            // 4. 特殊處理：如果核心關鍵詞匹配度高（>=2個），即使相似度略低也給予機會
            if (coreKeywordMatch >= 2 && adjustedSimilarity < threshold && adjustedSimilarity >= threshold - 15) {
                adjustedSimilarity = threshold; // 提升到閾值
            }
            
            // 4. 如果相似度達到閾值，加入候選列表（包括調整後的相似度）
            // 即使原始相似度低於閾值，如果調整後達到閾值也加入
            if (adjustedSimilarity >= threshold || (coreKeywordMatch >= 2 && adjustedSimilarity >= threshold - 10)) {
                candidates.push({
                    question: faqQuestion,
                    answer: faqData[i].answer,
                    similarity: adjustedSimilarity,
                    originalSimilarity: similarity,
                    keywordOverlap: keywordOverlap
                });
                
                if (adjustedSimilarity > bestSimilarity) {
                    bestSimilarity = adjustedSimilarity;
                    bestMatch = {
                        question: faqQuestion,
                        answer: faqData[i].answer,
                        similarity: adjustedSimilarity,
                        originalSimilarity: similarity
                    };
                }
            }
        }
        
        // 5. 如果有多個候選，選擇相似度最高的
        if (candidates.length > 0) {
            candidates.sort((a, b) => b.similarity - a.similarity);
            const topCandidate = candidates[0];
            
            // 如果最高相似度明顯高於其他候選（差距>10%），使用它
            if (candidates.length === 1 || (candidates.length > 1 && topCandidate.similarity - candidates[1].similarity > 10)) {
                return {
                    question: topCandidate.question,
                    answer: topCandidate.answer,
                    similarity: topCandidate.similarity,
                    originalSimilarity: topCandidate.originalSimilarity
                };
            }
        }
        
        return bestMatch;
    }

    // 4. 智能問答功能 - 整合Ollama AI
    function findAnswer(question) {
        return new Promise((resolve, reject) => {
            const useAI = $('#use-ai').is(':checked');
            console.log('🔍 開始尋找答案，使用AI:', useAI);
            
            // 先檢查FAQ資料庫中的相似度匹配（閾值設為60%，更寬鬆的匹配以容納更多相似問題）
            const similarFAQ = findSimilarFAQ(question, 60);
            
            if (similarFAQ) {
                console.log('✅ 找到相似FAQ匹配，相似度:', similarFAQ.similarity.toFixed(2) + '%');
                console.log('📝 原始相似度:', (similarFAQ.originalSimilarity || similarFAQ.similarity).toFixed(2) + '%');
                console.log('📝 資料庫問題:', similarFAQ.question);
                console.log('📝 用戶問題:', question);
                
                resolve({
                    answer: similarFAQ.answer,
                    source_type: 'database_match',
                    confidence_score: similarFAQ.similarity / 100,
                    response_time: 0,
                    similarity: similarFAQ.similarity,
                    matched_question: similarFAQ.question
                });
                return;
            } else {
                console.log('❌ 未找到相似FAQ匹配（閾值60%）');
                console.log('📝 用戶問題:', question);
                console.log('📊 嘗試匹配的FAQ數量:', faqData ? faqData.length : 0);
                
                // 顯示前3個最相似的結果（即使未達閾值）用於調試
                if (faqData && faqData.length > 0) {
                    const debugResults = [];
                    for (let i = 0; i < Math.min(3, faqData.length); i++) {
                        const sim = calculateSimilarity(question, faqData[i].question);
                        debugResults.push({
                            question: faqData[i].question,
                            similarity: sim.toFixed(2) + '%'
                        });
                    }
                    debugResults.sort((a, b) => parseFloat(b.similarity) - parseFloat(a.similarity));
                    console.log('🔍 最相似的3個問題:', debugResults);
                }
            }
            
            if (useAI) {
                console.log('🤖 嘗試使用Ollama AI回答...');
                // 使用Ollama AI回答
                $.ajax({
                    url: '../backend/api/ollama/ollama_api.php',
                    type: 'POST',
                    data: 'action=ask_question&question=' + encodeURIComponent(question) + '&use_context=true',
                    dataType: 'json',
                    timeout: 30000, // 30秒超時（優化：平衡速度和成功率）
                    success: function(response) {
                        console.log('✅ AI回答響應:', response);
                        // 檢查 response.success 或 response.answer（兼容舊格式）
                        if (response.success === true || (response.answer && !response.error)) {
                            console.log('🎉 AI回答成功，使用AI回答');
                            
                            // 檢查是否沒有資料庫資料
                            let finalAnswer = response.answer;
                            if (response.no_database_data === true) {
                                // 資料庫沒有相關資料，顯示特定訊息
                                finalAnswer = '此內容沒有準確的資料可以回答您\n\n';
                                finalAnswer += '📞 招生中心聯絡資訊：\n';
                                finalAnswer += '• 電話：02-2632-1181\n';
                                finalAnswer += '• 如有任何疑問，歡迎直接聯繫招生中心，我們將為您提供最準確的資訊\n\n';
                                finalAnswer += '超出AI資訊範';
                            }
                            
                            resolve({
                                answer: finalAnswer,
                                source_type: 'ollama_ai',
                                confidence_score: 0.9,
                                response_time: response.response_time_ms || 0,
                                model: response.model || 'ollama',
                                no_database_data: response.no_database_data || false
                            });
                        } else {
                            // AI失敗時，先嘗試使用錯誤訊息，如果沒有則回退到關鍵詞匹配
                            console.warn('⚠️ AI回答失敗:', response.error || response.message);
                            const errorMessage = response.message || response.error || 'AI 暫時無法回答';
                            
                            // 如果錯誤訊息有意義，使用它；否則回退到關鍵詞匹配
                            if (errorMessage && errorMessage.length > 10 && !errorMessage.includes('系統暫時無法回應')) {
                                resolve({
                                    answer: errorMessage + '\n\n💡 提示：您也可以嘗試使用關鍵詞查詢，或稍後再試。',
                                    source_type: 'ai_error',
                                    confidence_score: 0.5,
                                    response_time: 0
                                });
                            } else {
                                const fallbackAnswer = findFallbackAnswer(question);
                                resolve({
                                    answer: fallbackAnswer,
                                    source_type: 'fallback',
                                    confidence_score: 0.3,
                                    response_time: 0
                                });
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ AI請求失敗:', {
                            status: xhr.status,
                            statusText: xhr.statusText,
                            responseText: xhr.responseText,
                            error: error
                        });
                        
                        // 嘗試解析響應，看是否有 no_database_data 標記
                        let noDatabaseData = false;
                        try {
                            if (xhr.responseText) {
                                const errorResponse = JSON.parse(xhr.responseText);
                                if (errorResponse.no_database_data === true) {
                                    noDatabaseData = true;
                                }
                            }
                        } catch (e) {
                            // 無法解析響應，繼續使用回退邏輯
                        }
                        
                        // 如果資料庫沒有相關資料，顯示特定訊息
                        if (noDatabaseData) {
                            const noDataAnswer = '此內容沒有準確的資料可以回答您\n\n' +
                                '📞 招生中心聯絡資訊：\n' +
                                '• 電話：02-2632-1181\n' +
                                '• 如有任何疑問，歡迎直接聯繫招生中心，我們將為您提供最準確的資訊\n\n' +
                                '超出AI資訊範';
                            
                            resolve({
                                answer: noDataAnswer,
                                source_type: 'no_database_data',
                                confidence_score: 0.5,
                                response_time: 0
                            });
                            return;
                        }
                        
                        // 處理超時情況
                        if (status === 'timeout' || error === 'timeout') {
                            console.warn('⏱️ AI 請求超時，檢查資料庫是否有相關資料');
                            // 超時時，假設資料庫沒有相關資料，顯示特定訊息
                            const noDataAnswer = '此內容沒有準確的資料可以回答您\n\n' +
                                '📞 招生中心聯絡資訊：\n' +
                                '• 電話：02-2632-1181\n' +
                                '• 如有任何疑問，歡迎直接聯繫招生中心，我們將為您提供最準確的資訊\n\n' +
                                '超出AI資訊範';
                            
                            resolve({
                                answer: noDataAnswer,
                                source_type: 'timeout_no_data',
                                confidence_score: 0.4,
                                response_time: 0
                            });
                        } else {
                            // 其他網路錯誤時，也顯示資料庫無資料的訊息
                            const noDataAnswer = '此內容沒有準確的資料可以回答您\n\n' +
                                '📞 招生中心聯絡資訊：\n' +
                                '• 電話：02-2632-1181\n' +
                                '• 如有任何疑問，歡迎直接聯繫招生中心，我們將為您提供最準確的資訊\n\n' +
                                '超出AI資訊範';
                            
                            resolve({
                                answer: noDataAnswer,
                                source_type: 'network_error_no_data',
                                confidence_score: 0.3,
                                response_time: 0
                            });
                        }
                    }
                });
            } else {
                console.log('📝 使用關鍵詞匹配回答');
                // 直接使用關鍵詞匹配
                const answer = findFallbackAnswer(question);
                resolve({
                    answer: answer,
                    source_type: 'keyword_match',
                    confidence_score: 0.7,
                    response_time: 0
                });
            }
        });
    }
    
    // 回退的關鍵詞匹配功能
    function findFallbackAnswer(question) {
        if (!qaKeywords || !qaKeywords.responses) {
            return qaKeywords ? qaKeywords.default_response : "系統暫時無法回應，請稍後再試。";
        }

        const userQuestion = question.toLowerCase().trim();
        
        // 尋找匹配的關鍵詞
        for (let response of qaKeywords.responses) {
            for (let keyword of response.keywords) {
                if (userQuestion.includes(keyword.toLowerCase())) {
                    return response.answer;
                }
            }
        }
        
        // 沒有找到匹配的關鍵詞
        return qaKeywords.default_response;
    }

    function addMessage(content, isUser = false, metadata = null) {
        const messageClass = isUser ? 'user-message' : 'bot-message';
        const time = new Date().toLocaleTimeString('zh-TW', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        let metadataHtml = '';
        if (metadata && !isUser) {
            let sourceBadge = '';
            if (metadata.source_type === 'database_match') {
                sourceBadge = `<span class="badge bg-primary me-1">資料庫匹配</span>`;
                if (metadata.similarity) {
                    sourceBadge += `<span class="badge bg-secondary me-1">相似度: ${metadata.similarity.toFixed(1)}%</span>`;
                }
            } else if (metadata.source_type === 'ollama_ai') {
                sourceBadge = '<span class="badge bg-success me-1">AI</span>';
            } else if (metadata.source_type === 'keyword_match') {
                sourceBadge = '<span class="badge bg-info me-1">關鍵詞</span>';
            } else {
                sourceBadge = '<span class="badge bg-warning me-1">回退</span>';
            }
            
            metadataHtml = `
                <div class="message-metadata mt-1">
                    ${sourceBadge}
                    ${metadata.model ? `<small class="text-muted">模型: ${metadata.model}</small>` : ''}
                    ${metadata.response_time ? `<small class="text-muted ms-2">回應時間: ${metadata.response_time}ms</small>` : ''}
                </div>
            `;
        }
        
        const messageHtml = `
            <div class="chat-message ${messageClass}">
                <div class="message-content">${content}</div>
                ${metadataHtml}
                <div class="message-time">${time}</div>
            </div>
        `;
        
        $('#chat-messages').append(messageHtml);
        $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
    }

    function showTypingIndicator() {
        const typingHtml = `
            <div class="chat-message bot-message typing-message">
                <div class="typing-indicator">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
        `;
        $('#chat-messages').append(typingHtml);
        $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
    }

    function removeTypingIndicator() {
        $('.typing-message').remove();
    }

    function sendQuestion() {
        const question = $('#user-question').val().trim();
        
        if (!question) {
            alert('請輸入您的問題');
            return;
        }

        // 顯示使用者問題
        addMessage(question, true);
        $('#user-question').val('');
        $('#send-question').prop('disabled', true);

        // 顯示打字動畫
        showTypingIndicator();

        // 使用AI回答
        findAnswer(question).then(function(result) {
            removeTypingIndicator();
            addMessage(result.answer, false, result);
            $('#send-question').prop('disabled', false);
        }).catch(function(error) {
            removeTypingIndicator();
            addMessage('抱歉，系統暫時無法回應，請稍後再試。', false, {source_type: 'error'});
            $('#send-question').prop('disabled', false);
            console.error('問答錯誤:', error);
        });
    }

    // 5. 綁定事件
    $('#send-question').click(sendQuestion);
    
    $('#user-question').keypress(function(e) {
        if (e.which === 13 && !e.shiftKey) { // Enter 鍵且沒按 Shift
            e.preventDefault();
            sendQuestion();
        }
    });


    // 初始化時間戳
    setTimeout(function() {
        const time = new Date().toLocaleTimeString('zh-TW', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        $('.chat-message:first .message-time').text(time);
    }, 100);
});
	</script>

	<!-- 全局函數 -->
	<script>
	// 測試AI連接函數（全局作用域）
	function testAIConnection() {
		console.log('🔧 開始測試AI連接...');
		const testButton = event.target;
		const originalText = testButton.textContent;
		testButton.textContent = '🔄 測試中...';
		testButton.disabled = true;
		
		// 測試健康檢查
		$.get('../backend/api/ollama/ollama_api.php?action=check_health')
			.done(function(response) {
				console.log('✅ 健康檢查響應:', response);
				if (response.success) {
					// 測試實際AI回答
					$.ajax({
						url: '../backend/api/ollama/ollama_api.php',
						type: 'POST',
						data: 'action=ask_question&question=你好&use_context=false',
						dataType: 'json',
						timeout: 15000,
						success: function(aiResponse) {
							console.log('✅ AI回答測試響應:', aiResponse);
							if (aiResponse.success) {
								alert('🎉 AI連接測試成功！\n\n問題：你好\n回答：' + aiResponse.answer.substring(0, 100) + '...\n響應時間：' + aiResponse.response_time_ms + 'ms');
								$('#ai-status').removeClass('bg-warning').addClass('bg-success').text('🤖 AI驅動');
							} else {
								alert('⚠️ AI回答失敗：' + aiResponse.error);
								$('#ai-status').removeClass('bg-success').addClass('bg-warning').text('⚠️ AI異常');
							}
						},
						error: function(xhr, status, error) {
							console.error('❌ AI回答測試失敗:', xhr, status, error);
							alert('❌ AI回答測試失敗：\n狀態：' + xhr.status + '\n錯誤：' + error + '\n\n請檢查：\n1. PHP服務器是否運行\n2. Ollama服務是否運行\n3. API路徑是否正確');
							$('#ai-status').removeClass('bg-success').addClass('bg-warning').text('❌ AI離線');
						}
					});
				} else {
					alert('⚠️ Ollama健康檢查失敗：' + response.message);
					$('#ai-status').removeClass('bg-success').addClass('bg-warning').text('⚠️ AI離線');
				}
			})
			.fail(function(xhr, status, error) {
				console.error('❌ 健康檢查失敗:', xhr, status, error);
				alert('❌ 無法連接到Ollama API：\n狀態：' + xhr.status + '\n錯誤：' + error + '\n\n請檢查：\n1. PHP服務器是否運行在localhost:8000\n2. API文件是否存在\n3. 路徑是否正確');
				$('#ai-status').removeClass('bg-success').addClass('bg-warning').text('❌ AI離線');
			})
			.always(function() {
				testButton.textContent = originalText;
				testButton.disabled = false;
			});
	}
	</script>

	<?php include("share/footer.php"); ?>
	<?php include("share/ai_widget.php"); ?>
</body>

</html>