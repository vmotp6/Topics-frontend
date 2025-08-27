<?php
session_start();
$isLoggedIn = isset($_SESSION['username']);
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
            <h3>🎓 招生智能問答助手(並非AI)</h3>
            <div class="chat-container">
                <div class="chat-messages" id="chat-messages">
                    <div class="chat-message bot-message">
                        <div class="message-content">
                            🎓 您好！我是康寧大學招生智能助手，有任何招生相關問題都可以問我喔！歡迎詢問招生、學費、科系、申請流程等資訊～
                        </div>
                        <div class="message-time"></div>
                    </div>
                </div>
                <div class="chat-input-container">
                    <input type="text" id="user-question" placeholder="請輸入您的問題..." maxlength="200">
                    <button id="send-question">發送</button>
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

    // 2. 呼叫 Flask API 撈 FAQ
    $.get("http://localhost:5000/qa", function(data) {
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
    }).fail(function() {
        $("#faq-container").html('<p style="text-align: center; color: #666; padding: 20px;">暫時無法載入 FAQ 資料，請稍後再試。</p>');
    });

    // 4. 智能問答功能
    function findAnswer(question) {
        if (!qaKeywords || !qaKeywords.responses) {
            return qaKeywords ? qaKeywords.default_response : "系統尚未準備就緒，請稍後再試。";
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

    function addMessage(content, isUser = false) {
        const messageClass = isUser ? 'user-message' : 'bot-message';
        const time = new Date().toLocaleTimeString('zh-TW', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        const messageHtml = `
            <div class="chat-message ${messageClass}">
                <div class="message-content">${content}</div>
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

        // 模擬思考時間，然後回答
        setTimeout(function() {
            removeTypingIndicator();
            const answer = findAnswer(question);
            addMessage(answer, false);
            $('#send-question').prop('disabled', false);
        }, 800 + Math.random() * 1000); // 0.8-1.8秒的隨機延遲
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