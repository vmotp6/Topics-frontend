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
		AI科系推薦助手 
		<span id="ai-clear" title="清除對話記錄">🗑️</span>
		<span id="ai-close">✖</span>
		<span id="ai-hide-permanently" title="永久關閉AI按鈕">🚫</span>
		<div id="ai-resize-hint" title="拖拽AI框邊緣可調整大小">⤡</div>
	</div>
	<div id="ai-messages">
		<?php if ($isLoggedIn): ?>
			<p>👋 歡迎使用AI科系推薦助手！</p>
			<div class="ai-feature-intro">
				<h4>🎯 AI助手功能介紹</h4>
				<ul>
					<li><strong>科系推薦：</strong>根據您的需求推薦最適合的合作科系</li>
					<li><strong>專業分析：</strong>分析您的專案需求與科系優勢</li>
					<li><strong>合作建議：</strong>提供具體的合作方向與建議</li>
					<li><strong>三大科系：</strong>資訊管理科、護理科、視光科</li>
					<li><strong>即時回覆：</strong>AI智能分析，快速推薦</li>
					<li><strong>專業諮詢：</strong>提供產學合作的專業建議</li>
				</ul>
			</div>
			<p>💡 <strong>使用提示：</strong></p>
			<p>• 請描述您的專案需求或合作目標</p>
			<p>• AI會分析並推薦最適合的科系</p>
			<p>• 可詢問具體的合作方式與優勢</p>
		<?php else: ?>
			<div class="ai-login-prompt">
				<p>🔒 請先登入才能使用AI科系推薦功能</p>
				<p>登入後您可以：</p>
				<ul style="text-align: left; display: inline-block;">
					<li>使用AI助手推薦最適合的科系</li>
					<li>獲得專業的合作建議與分析</li>
					<li>了解三大科系的專業優勢</li>
				</ul>
				<p><a href="#" onclick="openLoginModal()">點擊這裡登入</a></p>
			</div>
		<?php endif; ?>
	</div>
	<?php if ($isLoggedIn): ?>
		<div id="ai-input">
			<input type="text" placeholder="輸入您的需求..." id="ai-input-field">
			<button id="ai-send-msg">送出</button>
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
  background: linear-gradient(135deg, #ff6b35, #f7931e);
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
  top: calc(100vh - 650px); /* 改用top定位，從下邊算起 */
  left: calc(100vw - 430px); /* 改用left定位，從右邊算起 */
  width: 400px;
  height: 550px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  display: none;
  flex-direction: column;
  z-index: 999;
  border: 2px solid #e0e0e0;
  resize: both;
  overflow: hidden;
  min-width: 350px;
  min-height: 450px;
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
  background: linear-gradient(135deg, #ff6b35, #f7931e);
  color: white;
  padding: 15px 20px;
  border-radius: 12px 12px 0 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: bold;
}

#ai-close {
  cursor: pointer;
  font-size: 18px;
  padding: 2px 6px;
  border-radius: 4px;
  transition: background-color 0.2s;
}

#ai-clear {
  cursor: pointer;
  font-size: 16px;
  padding: 2px 6px;
  border-radius: 4px;
  transition: background-color 0.2s;
  margin-right: 8px;
}

#ai-clear:hover {
  background-color: rgba(255, 193, 7, 0.2);
}

#ai-close:hover {
  background-color: rgba(255, 255, 255, 0.2);
}

#ai-hide-permanently {
  cursor: pointer;
  font-size: 16px;
  padding: 2px 6px;
  border-radius: 4px;
  transition: background-color 0.2s;
  margin-left: 8px;
}

#ai-hide-permanently:hover {
  background-color: rgba(255, 0, 0, 0.2);
}

#ai-resize-hint {
  position: absolute;
  bottom: 5px;
  right: 5px;
  font-size: 16px;
  opacity: 0.8;
  pointer-events: none;
  animation: ai-pulse 2s infinite;
  background: rgba(255, 107, 53, 0.1);
  border-radius: 4px;
  padding: 2px 4px;
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

#ai-input input {
  flex: 1;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
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
    width: 90%;
    right: 5%;
    bottom: 80px;
  }
  
  #ai-float-btn {
    bottom: 20px;
    right: 20px;
    width: 50px;
    height: 50px;
    font-size: 20px;
  }
}
</style>

<!-- AI功能 JavaScript -->
<script>
$(document).ready(function() {
	// 載入AI對話記錄
	loadAIHistory();
	
	// 點擊AI浮動按鈕，顯示/隱藏AI視窗
	$('#ai-float-btn').click(function() {
		$('#ai-box').toggle();
		
		// 如果AI框顯示了，給用戶一個提示
		if ($('#ai-box').is(':visible')) {
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

	// 點擊清除AI對話記錄按鈕
	$('#ai-clear').click(function() {
		if (confirm('確定要清除所有AI對話記錄嗎？此操作無法復原。')) {
			<?php if ($isLoggedIn): ?>
			// 從資料庫清除記錄
			$.ajax({
				url: '../backend/ai_chat_api.php',
				type: 'POST',
				data: { action: 'clear_history' },
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						$('#ai-messages').html('');
						// 重新載入歡迎訊息
						loadAIWelcomeMessage();
					} else {
						alert('清除記錄失敗: ' + response.error);
					}
				},
				error: function() {
					alert('清除記錄失敗');
				}
			});
			<?php else: ?>
			$('#ai-messages').html('');
			// 重新載入歡迎訊息
			loadAIWelcomeMessage();
			<?php endif; ?>
		}
	});

	// 點擊關閉按鈕
	$('#ai-close').click(function() {
		$('#ai-box').hide();
	});

	// 點擊永久關閉AI按鈕
	$('#ai-hide-permanently').click(function() {
		if (confirm('確定要永久關閉AI按鈕嗎？您可以在瀏覽器設定中重新啟用。')) {
			$.post('hide_ai.php', {hide_ai: true}, function(data) {
				if (data.success) {
					$('#ai-float-btn').hide();
					$('#ai-box').hide();
				}
			}, 'json');
		}
	});

	// 發送AI訊息（只有登入用戶才能使用）
	$('#ai-send-msg').click(function() {
		let msg = $('#ai-input-field').val().trim();
		if (msg) {
			addAIMessage('你', msg);
			$('#ai-input-field').val('');
			
			// AI科系推薦回覆
			setTimeout(function() {
				let aiResponse = getAIResponse(msg);
				addAIMessage('AI助手', aiResponse);
			}, 1000);
		}
	});
	
	// 添加AI訊息到對話記錄
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
		let messageType = sender === '你' ? 'user' : 'ai';
		console.log('正在保存AI訊息:', {sender, message, messageType});
		
		$.ajax({
			url: '../backend/ai_chat_api.php',
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
			url: '../backend/ai_chat_api.php',
			type: 'GET',
			data: { action: 'get_history' },
			dataType: 'json',
			success: function(response) {
				console.log('AI載入響應:', response);
				if (response.success && response.history.length > 0) {
					console.log('✅ 載入到', response.history.length, '條聊天記錄');
					// 顯示歷史記錄
					let historyHtml = '';
					response.history.forEach(function(msg) {
						let sender = msg.message_type === 'user' ? '你' : 'AI助手';
						historyHtml += '<p><b>' + sender + ':</b> ' + msg.message_content + '</p>';
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
			<p>👋 歡迎使用AI科系推薦助手！</p>
			<div class="ai-feature-intro">
				<h4>🎯 AI助手功能介紹</h4>
				<ul>
					<li><strong>科系推薦：</strong>根據您的需求推薦最適合的合作科系</li>
					<li><strong>專業分析：</strong>分析您的專案需求與科系優勢</li>
					<li><strong>合作建議：</strong>提供具體的合作方向與建議</li>
					<li><strong>三大科系：</strong>資訊管理科、護理科、視光科</li>
					<li><strong>即時回覆：</strong>AI智能分析，快速推薦</li>
					<li><strong>專業諮詢：</strong>提供產學合作的專業建議</li>
				</ul>
			</div>
			<p>💡 <strong>使用提示：</strong></p>
			<p>• 請描述您的專案需求或合作目標</p>
			<p>• AI會分析並推薦最適合的科系</p>
			<p>• 可詢問具體的合作方式與優勢</p>
		`;
		<?php else: ?>
		aiWelcomeMessage = `
			<div class="ai-login-prompt">
				<p>🔒 請先登入才能使用AI科系推薦功能</p>
				<p>登入後您可以：</p>
				<ul style="text-align: left; display: inline-block;">
					<li>使用AI助手推薦最適合的科系</li>
					<li>獲得專業的合作建議與分析</li>
					<li>了解三大科系的專業優勢</li>
				</ul>
				<p><a href="#" onclick="openLoginModal()">點擊這裡登入</a></p>
			</div>
		`;
		<?php endif; ?>
		
		$('#ai-messages').html(aiWelcomeMessage);
		// 不保存歡迎訊息到localStorage
	}
	
	// AI科系推薦功能
	function getAIResponse(message) {
		message = message.toLowerCase();
		
		// 資訊管理科關鍵字
		if (message.includes('程式') || message.includes('軟體') || message.includes('系統') || 
			message.includes('資料') || message.includes('數據') || message.includes('分析') ||
			message.includes('網站') || message.includes('app') || message.includes('應用') ||
			message.includes('資訊') || message.includes('管理') || message.includes('開發')) {
			return "根據您的需求，我推薦您與 <strong>資訊管理科</strong> 合作！\n\n📋 合作優勢：\n• 專業的程式開發與系統設計能力\n• 豐富的資料分析與管理經驗\n• 可協助開發網站、APP或管理系統\n• 具備最新的資訊技術知識\n\n💡 建議合作方向：\n• 企業資訊系統開發\n• 資料庫設計與管理\n• 網站與APP開發\n• 數據分析與視覺化";
		}
		
		// 護理科關鍵字
		else if (message.includes('健康') || message.includes('醫療') || message.includes('護理') ||
				 message.includes('照護') || message.includes('病人') || message.includes('醫院') ||
				 message.includes('保健') || message.includes('衛生') || message.includes('治療') ||
				 message.includes('診斷') || message.includes('康復')) {
			return "根據您的需求，我推薦您與 <strong>護理科</strong> 合作！\n\n📋 合作優勢：\n• 專業的護理照護技術與知識\n• 豐富的臨床實務經驗\n• 具備醫療保健相關專業能力\n• 可提供健康促進與疾病預防服務\n\n💡 建議合作方向：\n• 社區健康促進計畫\n• 長期照護服務\n• 醫療保健諮詢\n• 健康管理與監測";
		}
		
		// 視光科關鍵字
		else if (message.includes('眼睛') || message.includes('視力') || message.includes('眼鏡') ||
				 message.includes('視光') || message.includes('光學') || message.includes('鏡片') ||
				 message.includes('視覺') || message.includes('檢查') || message.includes('配鏡') ||
				 message.includes('隱形眼鏡') || message.includes('視力保健')) {
			return "根據您的需求，我推薦您與 <strong>視光科</strong> 合作！\n\n📋 合作優勢：\n• 專業的視光檢查與驗光技術\n• 豐富的配鏡與視力保健經驗\n• 具備光學儀器操作與維護能力\n• 可提供視力保健與眼鏡配製服務\n\n💡 建議合作方向：\n• 視力檢查與驗光服務\n• 眼鏡配製與銷售\n• 視力保健諮詢\n• 光學儀器維護與銷售";
		}
		
		// 一般回覆
		else {
			let generalResponses = [
				"您好！我是AI助手，專門協助您找到最適合的科系合作夥伴。\n\n我們目前有三大科系：\n• 📊 資訊管理科 - 程式開發、系統設計、資料分析\n• 🏥 護理科 - 醫療照護、健康促進、臨床實務\n• 👁️ 視光科 - 視力檢查、配鏡服務、視力保健\n\n請告訴我您的具體需求，我會為您推薦最適合的科系！",
				"感謝您的提問！為了更好地為您推薦合適的科系，請您詳細描述：\n• 您的專案或合作需求是什麼？\n• 需要什麼樣的技術或專業能力？\n• 希望達到什麼樣的目標？\n\n我會根據您的需求，從我們的三大科系中為您找到最佳合作夥伴！",
				"您好！我是您的AI科系推薦助手。\n\n我們提供三大專業科系的合作機會：\n• 資訊管理科：適合程式開發、系統設計需求\n• 護理科：適合醫療保健、照護服務需求\n• 視光科：適合視力檢查、配鏡服務需求\n\n請描述您的具體需求，我會立即為您推薦最適合的科系！"
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
</script> 