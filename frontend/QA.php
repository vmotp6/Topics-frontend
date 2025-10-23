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
        $.get('../backend/api/ollama/ollama_api.php?action=check_health')
            .done(function(response) {
                if (response.success) {
                    $('#ai-status').removeClass('bg-warning').addClass('bg-success').text('AI驅動');
                } else {
                    $('#ai-status').removeClass('bg-success').addClass('bg-warning').text('AI離線');
                }
            })
            .fail(function() {
                $('#ai-status').removeClass('bg-success').addClass('bg-warning').text('AI離線');
            });
    }

    // 4. 智能問答功能 - 整合Ollama AI
    function findAnswer(question) {
        return new Promise((resolve, reject) => {
            const useAI = $('#use-ai').is(':checked');
            
            if (useAI) {
                // 使用Ollama AI回答
                $.ajax({
                    url: '../backend/api/ollama/ollama_api.php',
                    type: 'POST',
                    data: {
                        action: 'ask_question',
                        question: question,
                        use_context: true
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            resolve({
                                answer: response.answer,
                                source_type: 'ollama_ai',
                                confidence_score: 0.9,
                                response_time: response.response_time_ms,
                                model: response.model
                            });
                        } else {
                            // AI失敗時回退到關鍵詞匹配
                            const fallbackAnswer = findFallbackAnswer(question);
                            resolve({
                                answer: fallbackAnswer,
                                source_type: 'fallback',
                                confidence_score: 0.3,
                                response_time: 0
                            });
                        }
                    },
                    error: function() {
                        // 網路錯誤時回退到關鍵詞匹配
                        const fallbackAnswer = findFallbackAnswer(question);
                        resolve({
                            answer: fallbackAnswer,
                            source_type: 'fallback',
                            confidence_score: 0.3,
                            response_time: 0
                        });
                    }
                });
            } else {
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
            if (metadata.source_type === 'ollama_ai') {
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

	<?php include("share/footer.php"); ?>
	<?php include("share/ai_widget.php"); ?>
</body>

</html>