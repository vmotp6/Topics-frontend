<?php
$isLoggedIn = isset($_SESSION['username']);
?>

<!-- AI功能按鈕 -->
<div id="ai-float-btn" style="display: <?php echo isset($_COOKIE['ai_hidden']) ? 'none' : 'flex'; ?>">🤖</div>

<!-- AI功能視窗 -->
<div id="ai-box">
	<!-- 拖拽調整大小的控制點 -->
	<div class="resize-handle-t"></div>
	<div class="resize-handle-b"></div>
	<div class="resize-handle-l"></div>
	<div class="resize-handle-r"></div>
	
	<div id="ai-header">
		<div id="ai-header-left">
			<span id="ai-title">🌟 康寧大學可愛小助手 ✨</span>
			<div id="ai-badges">
				<span class="badge bg-success" id="ai-status">🤖 AI驅動</span>
				<span class="badge bg-info">⚡ Ollama增強</span>
			</div>
		</div>
		<div id="ai-header-right">
			<span id="ai-close">✖</span>
		</div>
		<div id="ai-resize-hint" title="拖拽AI框邊緣可調整大小">⤡</div>
	</div>
	<div id="ai-messages">
		<?php if ($isLoggedIn): ?>
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
		<?php else: ?>
			<div class="ai-login-prompt">
				<p>🔒 請先登入才能使用康寧大學可愛小助手</p>
				<p>登入後您可以：</p>
				<ul style="text-align: left; display: inline-block;">
					<li>使用 AI 智能問答功能</li>
					<li>獲得招生相關問題的專業解答</li>
					<li>了解科系、學費、申請流程等資訊</li>
					<li>獲得個人化的升學建議</li>
				</ul>
				<p><a href="#" onclick="openLoginModal()">點擊這裡登入</a></p>
			</div>
		<?php endif; ?>
	</div>
	<?php if ($isLoggedIn): ?>
		<div id="ai-input">
			<input type="text" placeholder="💭 有什麼想問我的嗎？我很樂意為您解答～" id="ai-input-field" maxlength="500" style="pointer-events: auto !important; cursor: text !important;">
			<button id="ai-send-msg">🚀 發送</button>
		</div>
		<div id="ai-controls">
			<div id="ai-controls-tip">
				<small class="text-muted">💡 小提示：可以問我科系、學費、招生、校園生活等任何問題喔～</small>
			</div>
			<div id="ai-controls-buttons">
				<button type="button" id="ai-test-btn" onclick="testAIConnection()">
					🔧 測試AI
				</button>
			</div>
		</div>
	<?php endif; ?>
</div>

<!-- AI功能樣式 -->
<style>
/* AI浮動按鈕 */
#ai-float-btn {
  position: fixed;
  bottom: 30px;
  right: 30px;
  width: 60px;
  height: 60px;
  background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(255, 107, 53, 0.4);
  transition: all 0.3s ease;
  z-index: 1000;
  border: none;
}

#ai-float-btn:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 16px rgba(255, 107, 53, 0.6);
}


/* AI功能視窗 */
#ai-box {
  position: fixed;
  bottom: 30px;
  right: 30px;
  width: 480px;
  height: 600px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  display: none;
  flex-direction: column;
  z-index: 999;
  border: 2px solid #e0e0e0;
  resize: both;
  overflow: hidden;
  min-width: 420px;
  min-height: 500px;
  max-width: 700px;
  max-height: 800px;
  cursor: default;
}

#ai-box:hover {
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
  border-color: #ff6b35;
}

/* 多方向拖拽調整大小 */
#ai-box {
  cursor: default;
}

/* 拖拽調整大小的邊框 */
#ai-box::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  border: 3px solid transparent;
  border-radius: 12px;
  pointer-events: none;
  transition: all 0.2s ease;
  z-index: 1;
}

#ai-box:hover::before {
  border-color: #ff6b35;
  pointer-events: auto;
}

/* 八個角的拖拽點 */
#ai-box::after {
  content: '';
  position: absolute;
  width: 12px;
  height: 12px;
  background: #ff6b35;
  border-radius: 50%;
  opacity: 0;
  transition: opacity 0.2s ease;
  z-index: 3;
}



/* 四個邊的拖拽點 */
#ai-box .resize-handle-t {
  position: absolute;
  top: -3px;
  left: 50%;
  transform: translateX(-50%);
  width: 30px;
  height: 6px;
  background: #ff6b35;
  border-radius: 3px;
  opacity: 0;
  transition: opacity 0.2s ease;
  z-index: 3;
  cursor: n-resize;
}

#ai-box:hover .resize-handle-t {
  opacity: 1;
}

#ai-box .resize-handle-b {
  position: absolute;
  bottom: -3px;
  left: 50%;
  transform: translateX(-50%);
  width: 30px;
  height: 6px;
  background: #ff6b35;
  border-radius: 3px;
  opacity: 0;
  transition: opacity 0.2s ease;
  z-index: 3;
  cursor: s-resize;
}

#ai-box:hover .resize-handle-b {
  opacity: 1;
}

#ai-box .resize-handle-l {
  position: absolute;
  left: -3px;
  top: 50%;
  transform: translateY(-50%);
  width: 6px;
  height: 30px;
  background: #ff6b35;
  border-radius: 3px;
  opacity: 0;
  transition: opacity 0.2s ease;
  z-index: 3;
  cursor: w-resize;
}

#ai-box:hover .resize-handle-l {
  opacity: 1;
}

#ai-box .resize-handle-r {
  position: absolute;
  right: -3px;
  top: 50%;
  transform: translateY(-50%);
  width: 6px;
  height: 30px;
  background: #ff6b35;
  border-radius: 3px;
  opacity: 0;
  transition: opacity 0.2s ease;
  z-index: 3;
  cursor: e-resize;
}

#ai-box:hover .resize-handle-r {
  opacity: 1;
}

/* 確保AI框內容區域不會影響拖拽 */
#ai-header,
#ai-messages,
#ai-input {
  position: relative;
  z-index: 2;
}

#ai-header {
  background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
  color: white;
  padding: 12px 18px;
  border-radius: 12px 12px 0 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: bold;
  position: relative;
  min-height: 58px;
  gap: 12px;
  overflow: visible;
  z-index: 100;
  flex-shrink: 0;
}

#ai-header-left {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
  min-width: 0;
  overflow: visible;
}

#ai-title {
  font-size: 14px;
  white-space: nowrap;
  flex-shrink: 0;
  line-height: 1.4;
  margin-right: 0;
  padding-right: 0;
}

#ai-badges {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
  overflow: visible;
}

#ai-header-right {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
  margin-left: auto;
  padding-left: 8px;
  z-index: 101;
  position: relative;
  overflow: visible;
}

#ai-close {
  cursor: pointer;
  font-size: 16px;
  padding: 6px 10px;
  border-radius: 4px;
  transition: background-color 0.2s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 32px;
  min-height: 32px;
  flex-shrink: 0;
}


#ai-close:hover {
  background-color: rgba(255, 255, 255, 0.3);
}


#ai-resize-hint {
  position: absolute;
  bottom: 8px;
  right: 8px;
  font-size: 12px;
  opacity: 0.6;
  pointer-events: none;
  animation: ai-pulse 2s infinite;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 4px;
  padding: 2px 5px;
  z-index: 5;
}

@keyframes ai-pulse {
  0% { opacity: 0.8; transform: scale(1); }
  50% { opacity: 1; transform: scale(1.1); }
  100% { opacity: 0.8; transform: scale(1); }
}

#ai-messages {
  flex: 1;
  padding: 15px;
  overflow-y: auto;
  background: #f8f9fa;
  max-height: 400px;
}

#ai-messages p {
  margin: 8px 0;
  padding: 10px 12px;
  border-radius: 8px;
  background: white;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  line-height: 1.4;
  white-space: pre-line;
}

#ai-messages p:first-child {
  background: #fff3e0;
  border-left: 4px solid #ff6b35;
}

/* 聊天消息樣式 */
.chat-message {
  margin-bottom: 15px;
  display: flex;
  align-items: flex-start;
  animation: fadeInUp 0.3s ease;
}

.chat-message.user-message {
  justify-content: flex-end;
}

.chat-message.user-message .message-content {
  background: #ff6b35;
  color: white;
  border-radius: 18px 18px 5px 18px;
  max-width: 70%;
}

.chat-message.bot-message .message-content {
  background: white;
  color: #333;
  border: 1px solid #e0e0e0;
  border-radius: 5px 18px 18px 18px;
  max-width: 70%;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.message-content {
  padding: 12px 16px;
  font-size: 14px;
  line-height: 1.5;
  word-wrap: break-word;
  white-space: pre-line;
}

.message-time {
  font-size: 11px;
  color: #999;
  margin-top: 5px;
  text-align: right;
}

.message-metadata {
  margin-top: 5px;
  font-size: 11px;
}

/* 打字動畫 */
.typing-indicator {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 12px 16px;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 5px 18px 18px 18px;
  max-width: 70px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.typing-dot {
  width: 6px;
  height: 6px;
  background: #ff6b35;
  border-radius: 50%;
  animation: typing 1.4s infinite;
}

.typing-dot:nth-child(2) {
  animation-delay: 0.2s;
}

.typing-dot:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes typing {
  0%, 60%, 100% {
    transform: translateY(0);
    opacity: 0.4;
  }
  30% {
    transform: translateY(-10px);
    opacity: 1;
  }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.badge {
  display: inline-block;
  padding: 4px 10px;
  font-size: 11px;
  font-weight: 600;
  line-height: 1.4;
  text-align: center;
  white-space: nowrap;
  vertical-align: baseline;
  border-radius: 4px;
  flex-shrink: 0;
}

.bg-success {
  background-color: #28a745 !important;
  color: white;
}

.bg-info {
  background-color: #17a2b8 !important;
  color: white;
}

.bg-warning {
  background-color: #ffc107 !important;
  color: #212529;
}

.me-1 {
  margin-right: 0.25rem !important;
}

.me-2 {
  margin-right: 0.5rem !important;
}

.ms-2 {
  margin-left: 0.5rem !important;
}

.text-muted {
  color: #6c757d !important;
}

/* AI助手回覆樣式 */
#ai-messages p:has(b:contains("AI助手")) {
  background: #fff8e1;
  border-left: 4px solid #ff9800;
  font-weight: 500;
}

#ai-messages p:has(b:contains("AI助手")) strong {
  color: #ff6b35;
}

#ai-input {
  padding: 15px;
  background: white;
  border-top: 1px solid #e0e0e0;
  display: flex;
  gap: 10px;
}

#ai-controls {
  padding: 10px 15px;
  background: #f8f9fa;
  border-top: 1px solid #e0e0e0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  font-size: 12px;
}

#ai-controls-tip {
  flex: 1;
  min-width: 200px;
}

#ai-controls-tip small {
  color: #6c757d;
  line-height: 1.4;
}

#ai-controls-buttons {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

#ai-test-btn {
  background-color: #007bff;
  color: white;
  border: 1px solid #007bff;
  padding: 4px 8px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 10px;
  font-weight: 500;
  white-space: nowrap;
  transition: all 0.2s ease;
  flex-shrink: 0;
  line-height: 1.2;
}

#ai-test-btn:hover {
  background-color: #0056b3;
  border-color: #0056b3;
  transform: translateY(-1px);
}

#ai-test-btn:active {
  transform: translateY(0);
}

#ai-test-btn:disabled {
  background-color: #6c757d;
  border-color: #6c757d;
  cursor: not-allowed;
  opacity: 0.6;
}

#ai-input input {
  flex: 1;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  min-width: 220px;
}

#ai-input input:focus {
  outline: none;
  border-color: #ff6b35;
}

#ai-send-msg {
  background: #ff6b35;
  color: white;
  border: none;
  padding: 10px 15px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: bold;
  transition: background-color 0.2s;
}

#ai-send-msg:hover {
  background: #f7931e;
}

/* AI登入提示 */
.ai-login-prompt {
  text-align: center;
  padding: 20px;
  color: #666;
}

.ai-login-prompt a {
  color: #ff6b35;
  text-decoration: none;
  font-weight: bold;
}

.ai-login-prompt a:hover {
  text-decoration: underline;
}

/* AI功能介紹 */
.ai-feature-intro {
  background: #fff8e1;
  border: 1px solid #ffcc80;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 15px;
}

.ai-feature-intro h4 {
  color: #e65100;
  margin: 0 0 10px 0;
  font-size: 16px;
}

.ai-feature-intro ul {
  margin: 0;
  padding-left: 20px;
  color: #333;
}

.ai-feature-intro li {
  margin: 5px 0;
  font-size: 14px;
}

/* 響應式設計 */
@media screen and (max-width: 768px) {
  #ai-box {
    width: 95%;
    max-width: 480px;
    right: 2.5%;
    bottom: 80px;
    min-width: 380px;
  }
  
  #ai-float-btn {
    bottom: 20px;
    right: 20px;
    width: 50px;
    height: 50px;
    font-size: 20px;
  }
  
  #ai-header {
    padding: 10px 12px;
    min-height: 65px;
    gap: 8px;
  }
  
  #ai-title {
    font-size: 13px;
  }
  
  .badge {
    font-size: 9px;
    padding: 2px 6px;
  }
  
  #ai-header-right {
    gap: 6px;
    margin-left: 8px;
  }
  
  #ai-close {
    font-size: 14px;
    min-width: 24px;
    min-height: 24px;
    padding: 4px 6px;
  }
  
  #ai-controls {
    padding: 8px 12px;
    gap: 8px;
    flex-direction: column;
    align-items: flex-start;
  }
  
  #ai-controls-tip {
    min-width: 100%;
    width: 100%;
  }
  
  #ai-controls-buttons {
    width: 100%;
    justify-content: space-between;
    gap: 8px;
  }
  
  #ai-controls-tip small {
    font-size: 11px;
  }
  
  #ai-controls-buttons {
    width: 100%;
    justify-content: space-between;
    gap: 8px;
  }
  
  #ai-test-btn {
    font-size: 9px;
    padding: 3px 8px;
  }
}
</style>

<!-- AI功能 JavaScript -->
<script>
$(document).ready(function() {
	let qaKeywords = null; // 儲存關鍵詞資料
	
	// 1. 載入關鍵詞資料（使用相對路徑，兼容不同頁面位置）
	let keywordsPath = 'assets/qa_keywords.json';
	// 嘗試從當前頁面路徑推斷正確的相對路徑
	if (window.location.pathname.includes('/frontend/')) {
		keywordsPath = 'assets/qa_keywords.json';
	} else if (window.location.pathname.includes('/share/')) {
		keywordsPath = '../assets/qa_keywords.json';
	} else {
		// 默認嘗試當前目錄
		keywordsPath = 'assets/qa_keywords.json';
	}
	
	$.getJSON(keywordsPath, function(data) {
		qaKeywords = data;
		console.log("關鍵詞資料載入成功", qaKeywords);
	}).fail(function(jqXHR, textStatus, errorThrown) {
		console.error("關鍵詞資料載入失敗:", textStatus, errorThrown);
		console.error("嘗試的路徑:", keywordsPath);
		// 嘗試備用路徑
		const altPath = '../assets/qa_keywords.json';
		if (keywordsPath !== altPath) {
			$.getJSON(altPath, function(data) {
				qaKeywords = data;
				console.log("關鍵詞資料載入成功（使用備用路徑）", qaKeywords);
			}).fail(function() {
				console.error("備用路徑也失敗:", altPath);
			});
		}
	});
	
	// 2. 檢查Ollama服務狀態
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
	
	// 初始化時間戳
	setTimeout(function() {
		const time = new Date().toLocaleTimeString('zh-TW', { 
			hour: '2-digit', 
			minute: '2-digit' 
		});
		$('.chat-message:first .message-time').text(time);
	}, 100);
	
	// 載入AI對話記錄
	loadAIHistory();
	
	// 點擊AI浮動按鈕，顯示/隱藏AI視窗
	$('#ai-float-btn').click(function() {
		if ($('#ai-box').is(':visible')) {
			// 如果已經顯示，則隱藏
			$('#ai-box').hide();
			$('#ai-float-btn').show();
		} else {
			// 如果隱藏，則顯示並隱藏開啟按鈕
			$('#ai-box').show();
			$('#ai-float-btn').hide();
			
			// 顯示調整大小提示
			setTimeout(function() {
				$('#ai-resize-hint').css('opacity', '1');
				$('#ai-resize-hint').css('transform', 'scale(1.2)');
				setTimeout(function() {
					$('#ai-resize-hint').css('opacity', '0.8');
					$('#ai-resize-hint').css('transform', 'scale(1)');
				}, 2000);
			}, 500);
		}
	});

	// 點擊關閉按鈕
	$('#ai-close').click(function() {
		$('#ai-box').hide();
		// 顯示開啟按鈕
		$('#ai-float-btn').show();
	});


	// 發送AI訊息（只有登入用戶才能使用）
	function sendQuestion() {
		const question = $('#ai-input-field').val().trim();
		
		if (!question) {
			alert('請輸入您的問題');
			return;
		}

		// 顯示使用者問題
		addMessage(question, true);
		$('#ai-input-field').val('');
		$('#ai-send-msg').prop('disabled', true);

		// 顯示打字動畫
		showTypingIndicator();

		// 使用AI回答
		findAnswer(question).then(function(result) {
			removeTypingIndicator();
			addMessage(result.answer, false, result);
			$('#ai-send-msg').prop('disabled', false);
			
			// 保存到資料庫
			saveAIMessageToDatabase('ai', result.answer);
		}).catch(function(error) {
			removeTypingIndicator();
			addMessage('抱歉，系統暫時無法回應，請稍後再試。', false, {source_type: 'error'});
			$('#ai-send-msg').prop('disabled', false);
			console.error('問答錯誤:', error);
		});
	}
	
	$('#ai-send-msg').click(sendQuestion);
	
	// 確保輸入框可以輸入
	function initAIInputField() {
		const aiInputField = $('#ai-input-field');
		if (aiInputField.length) {
			// 強制確保輸入框沒有被禁用
			aiInputField.prop('disabled', false);
			aiInputField.prop('readonly', false);
			aiInputField.removeAttr('disabled');
			aiInputField.removeAttr('readonly');
			aiInputField.css({
				'pointer-events': 'auto',
				'cursor': 'text',
				'opacity': '1'
			});
			console.log('AI輸入框已初始化，可以輸入');
		}
	}
	
	// 初始化輸入框
	initAIInputField();
	
	// 延遲執行一次，確保所有腳本都執行完畢
	setTimeout(initAIInputField, 500);
	
	$('#ai-input-field').keypress(function(e) {
		if (e.which === 13 && !e.shiftKey) { // Enter 鍵且沒按 Shift
			e.preventDefault();
			sendQuestion();
		}
	});
	
	// 當AI視窗顯示時，確保輸入框可以輸入
	$('#ai-float-btn').on('click', function() {
		setTimeout(initAIInputField, 100);
	});
	
	// 添加消息到對話記錄（新版本，支持元數據）
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
		
		$('#ai-messages').append(messageHtml);
		$('#ai-messages').scrollTop($('#ai-messages')[0].scrollHeight);
		
		// 如果是用戶消息，保存到資料庫
		if (isUser) {
			saveAIMessageToDatabase('user', content);
		}
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
		$('#ai-messages').append(typingHtml);
		$('#ai-messages').scrollTop($('#ai-messages')[0].scrollHeight);
	}

	function removeTypingIndicator() {
		$('.typing-message').remove();
	}
	
	// 舊版本添加AI訊息到對話記錄（保留用於歷史記錄載入）
	function addAIMessage(sender, message) {
		let messageHtml = '<p><b>' + sender + ':</b> ' + message + '</p>';
		$('#ai-messages').append(messageHtml);
		$('#ai-messages').scrollTop($('#ai-messages')[0].scrollHeight);
		
		// 保存到資料庫
		saveAIMessageToDatabase(sender, message);
	}
	
	// 保存AI對話記錄到資料庫
	function saveAIMessageToDatabase(sender, message) {
		<?php if ($isLoggedIn): ?>
		let messageType = (sender === '你' || sender === 'user') ? 'user' : 'ai';
		console.log('正在保存AI訊息:', {sender, message, messageType});
		
		$.ajax({
			url: '../backend/api/chat/ai_chat_api.php',
			type: 'POST',
			data: {
				action: 'save_message',
				message_type: messageType,
				message_content: message
			},
			dataType: 'json',
			success: function(response) {
				console.log('AI保存響應:', response);
				if (response.success) {
					console.log('✅ AI訊息保存成功');
				} else {
					console.error('❌ 保存AI訊息失敗:', response.error);
				}
			},
			error: function(xhr, status, error) {
				console.error('❌ 保存AI訊息錯誤:', {xhr, status, error});
			}
		});
		<?php else: ?>
		console.log('用戶未登入，跳過保存');
		<?php endif; ?>
	}
	
	// 載入AI對話記錄
	function loadAIHistory() {
		<?php if ($isLoggedIn): ?>
		console.log('正在載入AI聊天記錄...');
		// 從資料庫載入聊天記錄
		$.ajax({
			url: '../backend/api/chat/ai_chat_api.php',
			type: 'GET',
			data: { action: 'get_history' },
			dataType: 'json',
			success: function(response) {
				console.log('AI載入響應:', response);
				if (response.success && response.history.length > 0) {
					console.log('✅ 載入到', response.history.length, '條聊天記錄');
					// 顯示歷史記錄（使用新格式）
					let historyHtml = '';
					response.history.forEach(function(msg) {
						const isUser = msg.message_type === 'user';
						historyHtml += `
							<div class="chat-message ${isUser ? 'user-message' : 'bot-message'}">
								<div class="message-content">${msg.message_content}</div>
								<div class="message-time"></div>
							</div>
						`;
					});
					$('#ai-messages').html(historyHtml);
					$('#ai-messages').scrollTop($('#ai-messages')[0].scrollHeight);
				} else {
					console.log('沒有歷史記錄，載入歡迎訊息');
					// 如果沒有歷史記錄，載入歡迎訊息
					loadAIWelcomeMessage();
				}
			},
			error: function(xhr, status, error) {
				console.error('❌ 載入AI記錄錯誤:', {xhr, status, error});
				// 如果API失敗，載入歡迎訊息
				loadAIWelcomeMessage();
			}
		});
		<?php else: ?>
		console.log('用戶未登入，載入歡迎訊息');
		// 未登入用戶載入歡迎訊息
		loadAIWelcomeMessage();
		<?php endif; ?>
	}
	
	// 載入AI歡迎訊息
	function loadAIWelcomeMessage() {
		let aiWelcomeMessage;
		<?php if ($isLoggedIn): ?>
		aiWelcomeMessage = `
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
		`;
		<?php else: ?>
		aiWelcomeMessage = `
			<div class="ai-login-prompt">
				<p>🔒 請先登入才能使用康寧大學可愛小助手</p>
				<p>登入後您可以：</p>
				<ul style="text-align: left; display: inline-block;">
					<li>使用 AI 智能問答功能</li>
					<li>獲得招生相關問題的專業解答</li>
					<li>了解科系、學費、申請流程等資訊</li>
					<li>獲得個人化的升學建議</li>
				</ul>
				<p><a href="#" onclick="openLoginModal()">點擊這裡登入</a></p>
			</div>
		`;
		<?php endif; ?>
		
		$('#ai-messages').html(aiWelcomeMessage);
		// 不保存歡迎訊息到localStorage
	}
	
	// 3. 智能問答功能 - 整合Ollama AI
	function findAnswer(question) {
		return new Promise((resolve, reject) => {
			// 預設使用AI回答
			const useAI = true;
			console.log('🔍 開始尋找答案，使用AI:', useAI);
			
			if (useAI) {
				console.log('🤖 嘗試使用Ollama AI回答...');
				// 使用Ollama AI回答
				$.ajax({
					url: '../backend/api/ollama/ollama_api.php',
					type: 'POST',
					data: 'action=ask_question&question=' + encodeURIComponent(question) + '&use_context=true',
					dataType: 'json',
					timeout: 120000, // 120秒超時
					success: function(response) {
						console.log('✅ AI回答響應:', response);
						if (response.success) {
							console.log('🎉 AI回答成功，使用AI回答');
							resolve({
								answer: response.answer,
								source_type: 'ollama_ai',
								confidence_score: 0.9,
								response_time: response.response_time_ms,
								model: response.model
							});
						} else {
							console.warn('⚠️ AI回答失敗，使用回退機制:', response.error);
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
					error: function(xhr, status, error) {
						console.error('❌ AI請求失敗:', {
							status: xhr.status,
							statusText: xhr.statusText,
							responseText: xhr.responseText,
							error: error
						});
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
	
	// 舊的AI科系推薦功能（已棄用，保留作為備份）
	function getAIResponse(message) {
		message = message.toLowerCase();
		
		// 幼保科關鍵字
		if (message.includes('幼兒') || message.includes('嬰兒') || message.includes('小孩') || 
			message.includes('兒童') || message.includes('保育') || message.includes('教育') ||
			message.includes('托育') || message.includes('幼教') || message.includes('照顧') ||
			message.includes('育兒') || message.includes('親子') || message.includes('發展')) {
			return "根據您的興趣，我推薦您選擇 <strong>幼保科</strong>！\n\n🎓 科系特色：\n• 培養嬰幼兒教育與保育專業人才\n• 適合對幼兒教育有熱忱的學生\n• 課程涵蓋幼兒發展、教育心理學、課程設計\n• 結合理論與實務，培養專業能力\n\n💼 就業前景：\n• 幼兒園教師\n• 托育中心保育員\n• 親子教育講師\n• 兒童發展評估師\n\n📚 入學管道：五專申請入學、免試入學";
		}
		
		// 企業管理科關鍵字
		else if (message.includes('管理') || message.includes('企業') || message.includes('商業') ||
				 message.includes('行銷') || message.includes('財務') || message.includes('人資') ||
				 message.includes('營運') || message.includes('策略') || message.includes('創業') ||
				 message.includes('經營') || message.includes('領導') || message.includes('組織')) {
			return "根據您的興趣，我推薦您選擇 <strong>企業管理科</strong>！\n\n🎓 科系特色：\n• 培養企業經營管理專業人才\n• 適合對商業管理有興趣的學生\n• 課程涵蓋行銷管理、財務管理、人力資源管理\n• 結合理論與實務，培養管理能力\n\n💼 就業前景：\n• 企業管理專員\n• 行銷企劃人員\n• 人力資源專員\n• 創業家\n\n📚 入學管道：五專申請入學、免試入學";
		}
		
		// 護理科關鍵字
		else if (message.includes('護理') || message.includes('護士') || message.includes('醫療') ||
				 message.includes('健康') || message.includes('病人') || message.includes('醫院') ||
				 message.includes('保健') || message.includes('衛生') || message.includes('治療') ||
				 message.includes('診斷') || message.includes('康復') || message.includes('臨床')) {
			return "根據您的興趣，我推薦您選擇 <strong>護理科</strong>！\n\n🎓 科系特色：\n• 培養專業護理人才\n• 提供豐富的臨床實習機會\n• 具備完整的護理專業知識與技能\n• 符合醫療照護產業需求\n\n💼 就業前景：\n• 醫院護理師\n• 社區護理師\n• 學校護理師\n• 護理教育工作者\n\n📚 入學管道：五專申請入學、免試入學";
		}
		
		// 視光科關鍵字
		else if (message.includes('眼睛') || message.includes('視力') || message.includes('眼鏡') ||
				 message.includes('視光') || message.includes('光學') || message.includes('鏡片') ||
				 message.includes('視覺') || message.includes('檢查') || message.includes('配鏡') ||
				 message.includes('隱形眼鏡') || message.includes('視力保健') || message.includes('驗光')) {
			return "根據您的興趣，我推薦您選擇 <strong>視光科</strong>！\n\n🎓 科系特色：\n• 專精視力保健與眼鏡配製\n• 結合理論與實務操作\n• 培養視光檢查與驗光技術\n• 具備光學儀器操作能力\n\n💼 就業前景：\n• 視光師\n• 眼鏡行配鏡師\n• 視力保健諮詢師\n• 光學儀器銷售員\n\n📚 入學管道：五專申請入學、免試入學";
		}
		
		// 資訊管理科關鍵字
		else if (message.includes('程式') || message.includes('軟體') || message.includes('系統') || 
				 message.includes('資料') || message.includes('數據') || message.includes('分析') ||
				 message.includes('網站') || message.includes('app') || message.includes('應用') ||
				 message.includes('資訊') || message.includes('管理') || message.includes('開發') ||
				 message.includes('電腦') || message.includes('網路') || message.includes('科技')) {
			return "根據您的興趣，我推薦您選擇 <strong>資訊管理科</strong>！\n\n🎓 科系特色：\n• 培養資訊科技與管理整合人才\n• 符合數位時代的產業需求\n• 課程涵蓋程式設計、系統分析、資料庫管理\n• 結合理論與實務應用\n\n💼 就業前景：\n• 軟體工程師\n• 系統分析師\n• 資料庫管理師\n• 資訊管理專員\n\n📚 入學管道：五專申請入學、免試入學";
		}
		
		// 應用外語科關鍵字
		else if (message.includes('外語') || message.includes('英語') || message.includes('日語') ||
				 message.includes('韓語') || message.includes('翻譯') || message.includes('口譯') ||
				 message.includes('語言') || message.includes('國際') || message.includes('商務') ||
				 message.includes('溝通') || message.includes('文書') || message.includes('貿易')) {
			return "根據您的興趣，我推薦您選擇 <strong>應用外語科</strong>！\n\n🎓 科系特色：\n• 培養外語應用與國際商務專業人才\n• 提升語言能力與國際視野\n• 課程涵蓋多國語言、商務溝通、國際貿易\n• 結合理論與實務，培養跨文化溝通能力\n\n💼 就業前景：\n• 外語翻譯人員\n• 國際商務專員\n• 觀光導遊\n• 語言教學工作者\n\n📚 入學管道：五專申請入學、免試入學";
		}
		
		// 動畫科關鍵字
		else if (message.includes('動畫') || message.includes('影片') || message.includes('3d') ||
				 message.includes('渲染') || message.includes('影像') || message.includes('剪輯') ||
				 message.includes('多媒體') || message.includes('視覺效果') || message.includes('角色設計') ||
				 message.includes('動態') || message.includes('分鏡') || message.includes('2d')) {
			return "根據您的興趣，我推薦您選擇 <strong>動畫科</strong>！\n\n🎓 科系特色：\n• 培養動畫製作與多媒體設計專業人才\n• 適合對創意設計有興趣的學生\n• 課程涵蓋2D/3D動畫、影像剪輯、視覺特效\n• 結合理論與實務，培養創意設計能力\n\n💼 就業前景：\n• 動畫師\n• 多媒體設計師\n• 影片剪輯師\n• 遊戲美術設計師\n\n📚 入學管道：五專申請入學、免試入學";
		}
		
		// 入學相關問題
		else if (message.includes('入學') || message.includes('報名') || message.includes('申請') ||
				 message.includes('考試') || message.includes('分數') || message.includes('錄取') ||
				 message.includes('繁星') || message.includes('申請入學') || message.includes('分發')) {
			return "關於康寧大學入學資訊：\n\n📋 入學管道：\n• 繁星推薦：適合在校成績優異的學生\n• 申請入學：重視多元表現與面試\n• 分發入學：依據學測成績分發\n\n💰 學費資訊：\n• 提供學雜費減免措施\n• 可申請就學貸款\n• 設有獎學金制度\n\n🏠 住宿資訊：\n• 提供學生宿舍\n• 住宿申請詳情請洽學務處\n\n📞 聯絡方式：\n• 招生中心：02-2632-1181\n• 官網：www.ukn.edu.tw";
		}
		
		// 一般回覆
		else {
			let generalResponses = [
				"歡迎來到康寧大學！我是您的新生科系推薦助手。\n\n🎓 我們提供七大科系：\n• 💻 資訊管理科 - 資訊科技與管理\n• 🏢 企業管理科 - 企業經營管理\n• 🏥 護理科 - 專業護理與醫療照護\n• 👶 幼保科 - 幼兒教育與保育\n• 🌍 應用外語科 - 外語應用與國際商務\n• 👁️ 視光科 - 視力保健與配鏡服務\n• 🎨 動畫科 - 動畫製作與多媒體設計\n\n請告訴我您的興趣或想了解的科系，我會為您詳細介紹！",
				"您好！我是康寧大學的新生科系推薦助手。\n\n為了更好地為您推薦合適的科系，請告訴我：\n• 您對哪個領域感興趣？\n• 您希望未來從事什麼樣的工作？\n• 您有什麼特殊的興趣或特質？\n\n我會根據您的回答，為您推薦最適合的科系！",
				"歡迎加入康寧大學！我是您的專屬科系推薦助手。\n\n🎯 我可以幫助您：\n• 了解各科系的特色與課程\n• 分析您的興趣與科系匹配度\n• 提供就業前景與發展方向\n• 解答入學相關問題\n\n請描述您的興趣或想了解的科系，我會為您提供專業建議！"
			];
			return generalResponses[Math.floor(Math.random() * generalResponses.length)];
		}
	}

	// 按 Enter 發送AI訊息
	$('#ai-input-field').keypress(function(e) {
		if (e.which == 13) {
			$('#ai-send-msg').click();
		}
	});
	
	// 多方向拖拽調整大小功能
	let isResizing = false;
	let currentHandle = null;
	let startX, startY, startWidth, startHeight, startLeft, startTop;
	
	// 綁定所有拖拽控制點的事件
	$('.resize-handle-t, .resize-handle-b, .resize-handle-l, .resize-handle-r').on('mousedown', function(e) {
		e.preventDefault();
		isResizing = true;
		currentHandle = $(this).hasClass('resize-handle-t') ? 't' :
					   $(this).hasClass('resize-handle-b') ? 'b' :
					   $(this).hasClass('resize-handle-l') ? 'l' : 'r';
		
		const $aiBox = $('#ai-box');
		startX = e.clientX;
		startY = e.clientY;
		startWidth = $aiBox.width();
		startHeight = $aiBox.height();
		startLeft = parseInt($aiBox.css('left'));
		startTop = parseInt($aiBox.css('top'));
		
		$(document).on('mousemove', handleMouseMove);
		$(document).on('mouseup', handleMouseUp);
	});
	
	function handleMouseMove(e) {
		if (!isResizing) return;
		
		const $aiBox = $('#ai-box');
		const deltaX = e.clientX - startX;
		const deltaY = e.clientY - startY;
		
		let newWidth = startWidth;
		let newHeight = startHeight;
		let newLeft = startLeft;
		let newTop = startTop;
		
		// 調試信息
		console.log('拖動:', currentHandle, 'deltaX:', deltaX, 'deltaY:', deltaY);
		
		switch (currentHandle) {
			case 't': // 上邊
				// 上邊跟隨滑鼠移動，下邊保持固定
				newHeight = Math.max(450, startHeight - deltaY);
				// 上邊緣移動時，top值需要減少以讓上邊緣向上拓展
				newTop = startTop + deltaY;
				console.log('上邊拖動:', {startHeight, deltaY, newHeight, startTop, newTop});
				break;
			case 'b': // 下邊
				// 下邊跟隨滑鼠移動，上邊保持固定
				newHeight = Math.max(450, startHeight + deltaY);
				// 下邊緣移動，不改變top值
				console.log('下邊拖動:', {startHeight, deltaY, newHeight});
				break;
			case 'l': // 左邊
				// 左邊跟隨滑鼠移動，右邊保持固定
				newWidth = Math.max(350, startWidth - deltaX);
				// 左邊緣移動時，left值需要增加以讓左邊緣向左拓展
				newLeft = startLeft + deltaX;
				console.log('左邊拖動:', {startWidth, deltaX, newWidth, startLeft, newLeft});
				break;
			case 'r': // 右邊
				// 右邊跟隨滑鼠移動，左邊保持固定
				newWidth = Math.max(350, startWidth + deltaX);
				// 右邊緣移動，不改變left值
				console.log('右邊拖動:', {startWidth, deltaX, newWidth});
				break;
		}
		
		// 限制最大尺寸
		newWidth = Math.min(700, newWidth);
		newHeight = Math.min(800, newHeight);
		
		$aiBox.css({
			width: newWidth + 'px',
			height: newHeight + 'px',
			left: newLeft + 'px',
			top: newTop + 'px'
		});
	}
	
	function handleMouseUp() {
		isResizing = false;
		currentHandle = null;
		$(document).off('mousemove', handleMouseMove);
		$(document).off('mouseup', handleMouseUp);
	}
});

// 打開登入模態視窗的函數
function openLoginModal() {
	const loginModal = document.getElementById("loginModal");
	if (loginModal) {
		loginModal.style.display = "flex";
	}
}

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