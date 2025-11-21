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

    // 4. 智能問答功能 - 整合Ollama AI
    function findAnswer(question) {
        return new Promise((resolve, reject) => {
            const useAI = $('#use-ai').is(':checked');
            console.log('🔍 開始尋找答案，使用AI:', useAI);
            
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
                            resolve({
                                answer: response.answer,
                                source_type: 'ollama_ai',
                                confidence_score: 0.9,
                                response_time: response.response_time_ms || 0,
                                model: response.model || 'ollama'
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
                        
                        // 處理超時情況
                        if (status === 'timeout' || error === 'timeout') {
                            console.warn('⏱️ AI 請求超時，使用關鍵詞匹配作為回退');
                            const fallbackAnswer = findFallbackAnswer(question);
                            resolve({
                                answer: '⏱️ AI 回答時間較長，為您提供以下資訊：\n\n' + fallbackAnswer + '\n\n💡 提示：如果問題較複雜，AI 可能需要更長時間處理。您可以稍後再試，或使用更簡短的問題。',
                                source_type: 'timeout_fallback',
                                confidence_score: 0.4,
                                response_time: 0
                            });
                        } else {
                            // 其他網路錯誤時快速回退到關鍵詞匹配
                            const fallbackAnswer = findFallbackAnswer(question);
                            resolve({
                                answer: '❌ AI 服務暫時無法連接，為您提供以下資訊：\n\n' + fallbackAnswer + '\n\n💡 提示：請檢查 Ollama 服務是否正常運行，或稍後再試。',
                                source_type: 'network_fallback',
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