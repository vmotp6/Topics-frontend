<?php
$isLoggedIn = isset($_SESSION['username']);
?>

<!-- 浮動聊天按鈕 -->
<div id="chat-float-btn" style="display: <?php echo isset($_COOKIE['chat_hidden']) ? 'none' : 'flex'; ?>">💬</div>

<!-- 聊天視窗 -->
<div id="chat-box">
	<div id="chat-header">
		小聊天室 
		<span id="chat-clear" title="清除聊天記錄">🗑️</span>
		<span id="chat-close">✖</span>
		<span id="chat-hide-permanently" title="永久關閉聊天按鈕">🚫</span>
		<div id="resize-hint" title="拖拽聊天框邊緣可調整大小">⤡</div>
	</div>
	<div id="chat-messages">
		<?php if ($isLoggedIn): ?>
			<p>👋 歡迎來到小聊天室！</p>
			<div class="feature-intro">
				<h4>💬 聊天室功能介紹</h4>
				<ul>
					<li><strong>即時聊天：</strong>與其他用戶即時交流</li>
					<li><strong>訊息記錄：</strong>自動保存聊天記錄</li>
					<li><strong>簡潔介面：</strong>清爽的聊天體驗</li>
					<li><strong>響應式設計：</strong>支援各種設備</li>
				</ul>
			</div>
			<p>💡 <strong>使用提示：</strong></p>
			<p>• 輸入訊息後按Enter或點擊送出</p>
			<p>• 聊天記錄會自動保存</p>
			<p>• 可以調整聊天框大小</p>
		<?php else: ?>
			<div class="login-prompt">
				<p>🔒 請先登入才能使用聊天功能</p>
				<p>登入後您可以：</p>
				<ul style="text-align: left; display: inline-block;">
					<li>與其他用戶即時聊天</li>
					<li>保存聊天記錄</li>
					<li>享受流暢的聊天體驗</li>
				</ul>
				<p><a href="#" onclick="openLoginModal()">點擊這裡登入</a></p>
			</div>
		<?php endif; ?>
	</div>
	<?php if ($isLoggedIn): ?>
		<div id="chat-input">
			<input type="text" placeholder="輸入訊息..." id="chat-input-field">
			<button id="send-msg">送出</button>
		</div>
	<?php endif; ?>
</div>

<!-- 聊天室樣式 -->
<style>
/* 浮動聊天按鈕 */
#chat-float-btn {
  position: fixed;
  bottom: 30px;
  right: 30px;
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #00bcd4, #0097a7);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(0, 188, 212, 0.4);
  transition: all 0.3s ease;
  z-index: 1000;
  border: none;
}

#chat-float-btn:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 16px rgba(0, 188, 212, 0.6);
}

/* 聊天室 */
#chat-box {
  position: fixed;
  bottom: 100px;
  right: 30px;
  width: 350px;
  height: 500px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  display: none;
  flex-direction: column;
  z-index: 999;
  border: 2px solid #e0e0e0;
  resize: both;
  overflow: hidden;
  min-width: 300px;
  min-height: 400px;
  max-width: 600px;
  max-height: 700px;
  cursor: default;
}

#chat-box:hover {
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
  border-color: #00bcd4;
}

/* 讓整個聊天框都可以拖拽調整大小 - 像Google一樣 */
#chat-box {
  cursor: default;
}

#chat-box:hover {
  cursor: se-resize;
}

/* 添加拖拽邊框效果 - 讓整個邊框都可以拖拽 */
#chat-box::before {
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

#chat-box:hover::before {
  border-color: #00bcd4;
  pointer-events: auto;
  cursor: se-resize;
}

/* 確保聊天框內容區域不會影響拖拽 */
#chat-header,
#chat-messages,
#chat-input {
  position: relative;
  z-index: 2;
}

/* 確保聊天框內容區域不會影響拖拽 */
#chat-header,
#chat-messages,
#chat-input {
  position: relative;
  z-index: 2;
}

#chat-header {
  background: linear-gradient(135deg, #00bcd4, #0097a7);
  color: white;
  padding: 15px 20px;
  border-radius: 12px 12px 0 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: bold;
}

#chat-close {
  cursor: pointer;
  font-size: 18px;
  padding: 2px 6px;
  border-radius: 4px;
  transition: background-color 0.2s;
}

#chat-clear {
  cursor: pointer;
  font-size: 16px;
  padding: 2px 6px;
  border-radius: 4px;
  transition: background-color 0.2s;
  margin-right: 8px;
}

#chat-clear:hover {
  background-color: rgba(255, 193, 7, 0.2);
}

#chat-close {
  cursor: pointer;
  font-size: 18px;
  padding: 2px 6px;
  border-radius: 4px;
  transition: background-color 0.2s;
}

#chat-close:hover {
  background-color: rgba(255, 255, 255, 0.2);
}

#chat-hide-permanently {
  cursor: pointer;
  font-size: 16px;
  padding: 2px 6px;
  border-radius: 4px;
  transition: background-color 0.2s;
  margin-left: 8px;
}

#chat-hide-permanently:hover {
  background-color: rgba(255, 0, 0, 0.2);
}

#resize-hint {
  position: absolute;
  bottom: 5px;
  right: 5px;
  font-size: 16px;
  opacity: 0.8;
  pointer-events: none;
  animation: pulse 2s infinite;
  background: rgba(0, 188, 212, 0.1);
  border-radius: 4px;
  padding: 2px 4px;
}

@keyframes pulse {
  0% { opacity: 0.8; transform: scale(1); }
  50% { opacity: 1; transform: scale(1.1); }
  100% { opacity: 0.8; transform: scale(1); }
}

#chat-messages {
  flex: 1;
  padding: 15px;
  overflow-y: auto;
  background: #f8f9fa;
  max-height: 350px;
}

#chat-messages p {
  margin: 8px 0;
  padding: 10px 12px;
  border-radius: 8px;
  background: white;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  line-height: 1.4;
  white-space: pre-line;
}

#chat-messages p:first-child {
  background: #e3f2fd;
  border-left: 4px solid #00bcd4;
}

/* AI助手回覆樣式 */
#chat-messages p:has(b:contains("AI助手")) {
  background: #f0f8ff;
  border-left: 4px solid #007bff;
  font-weight: 500;
}

#chat-messages p:has(b:contains("AI助手")) strong {
  color: #007bff;
}

#chat-input {
  padding: 15px;
  background: white;
  border-top: 1px solid #e0e0e0;
  display: flex;
  gap: 10px;
}

#chat-input input {
  flex: 1;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}

#chat-input input:focus {
  outline: none;
  border-color: #00bcd4;
}

#send-msg {
  background: #00bcd4;
  color: white;
  border: none;
  padding: 10px 15px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: bold;
  transition: background-color 0.2s;
}

#send-msg:hover {
  background: #0097a7;
}

/* 登入提示 */
.login-prompt {
  text-align: center;
  padding: 20px;
  color: #666;
}

.login-prompt a {
  color: #00bcd4;
  text-decoration: none;
  font-weight: bold;
}

.login-prompt a:hover {
  text-decoration: underline;
}

/* 網站功能介紹 */
.feature-intro {
  background: #f0f8ff;
  border: 1px solid #b3d9ff;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 15px;
}

.feature-intro h4 {
  color: #0066cc;
  margin: 0 0 10px 0;
  font-size: 16px;
}

.feature-intro ul {
  margin: 0;
  padding-left: 20px;
  color: #333;
}

.feature-intro li {
  margin: 5px 0;
  font-size: 14px;
}

/* 響應式設計 */
@media screen and (max-width: 768px) {
  #chat-box {
    width: 90%;
    right: 5%;
    bottom: 80px;
  }
  
  #chat-float-btn {
    bottom: 20px;
    right: 20px;
    width: 50px;
    height: 50px;
    font-size: 20px;
  }
}
</style>

<!-- 聊天室 JavaScript -->
<script>
$(document).ready(function() {
	// 載入聊天記錄
	loadChatHistory();
	
	// 點擊浮動按鈕，顯示/隱藏聊天視窗
	$('#chat-float-btn').click(function() {
		$('#chat-box').toggle();
		
		// 如果聊天框顯示了，給用戶一個提示
		if ($('#chat-box').is(':visible')) {
			// 顯示調整大小提示
			setTimeout(function() {
				$('#resize-hint').css('opacity', '1');
				$('#resize-hint').css('transform', 'scale(1.2)');
				setTimeout(function() {
					$('#resize-hint').css('opacity', '0.8');
					$('#resize-hint').css('transform', 'scale(1)');
				}, 2000);
			}, 500);
		}
	});

	// 點擊清除聊天記錄按鈕
	$('#chat-clear').click(function() {
		if (confirm('確定要清除所有聊天記錄嗎？此操作無法復原。')) {
			$('#chat-messages').html('');
			localStorage.removeItem('chatHistory');
			// 重新載入歡迎訊息
			loadWelcomeMessage();
		}
	});

	// 點擊關閉按鈕
	$('#chat-close').click(function() {
		$('#chat-box').hide();
	});

	// 點擊永久關閉按鈕
	$('#chat-hide-permanently').click(function() {
		if (confirm('確定要永久關閉聊天按鈕嗎？您可以在瀏覽器設定中重新啟用。')) {
			$.post('hide_chat.php', {hide_chat: true}, function(data) {
				if (data.success) {
					$('#chat-float-btn').hide();
					$('#chat-box').hide();
				}
			}, 'json');
		}
	});

	// 發送訊息（只有登入用戶才能使用）
	$('#send-msg').click(function() {
		let msg = $('#chat-input-field').val().trim();
		if (msg) {
			addMessage('你', msg);
			$('#chat-input-field').val('');
			
			// 模擬其他用戶回覆（可以根據需要修改）
			setTimeout(function() {
				let responses = [
					"收到您的訊息了！",
					"謝謝分享！",
					"很有趣的觀點！",
					"我也有同感！"
				];
				let randomResponse = responses[Math.floor(Math.random() * responses.length)];
				addMessage('系統', randomResponse);
			}, 1000);
		}
	});
	
	// 添加訊息到聊天記錄
	function addMessage(sender, message) {
		let messageHtml = '<p><b>' + sender + ':</b> ' + message + '</p>';
		$('#chat-messages').append(messageHtml);
		$('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
		
		// 保存到localStorage
		saveChatHistory();
	}
	
	// 保存聊天記錄
	function saveChatHistory() {
		let chatContent = $('#chat-messages').html();
		// 只保存純對話內容，不保存歡迎訊息
		if (!chatContent.includes('歡迎使用AI科系推薦助手') && 
			!chatContent.includes('請先登入才能使用AI科系推薦功能') &&
			!chatContent.includes('feature-intro') &&
			!chatContent.includes('login-prompt')) {
			localStorage.setItem('chatHistory', chatContent);
		}
	}
	
	// 載入聊天記錄
	function loadChatHistory() {
		let savedHistory = localStorage.getItem('chatHistory');
		if (savedHistory) {
			// 檢查是否包含PHP生成的歡迎訊息或登入提示，如果包含則清除
			if (savedHistory.includes('歡迎使用AI科系推薦助手') || 
				savedHistory.includes('請先登入才能使用AI科系推薦功能') ||
				savedHistory.includes('feature-intro') ||
				savedHistory.includes('login-prompt')) {
				localStorage.removeItem('chatHistory');
				loadWelcomeMessage();
			} else {
				$('#chat-messages').html(savedHistory);
				$('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
			}
		} else {
			// 如果沒有保存的記錄，載入歡迎訊息
			loadWelcomeMessage();
		}
	}
	
	// 載入歡迎訊息
	function loadWelcomeMessage() {
		let welcomeMessage;
		<?php if ($isLoggedIn): ?>
		welcomeMessage = `
			<p>👋 歡迎使用AI科系推薦助手！</p>
			<div class="feature-intro">
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
		welcomeMessage = `
			<div class="login-prompt">
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
		
		$('#chat-messages').html(welcomeMessage);
		// 不保存歡迎訊息到localStorage
	}
	


	// 按 Enter 發送
	$('#chat-input-field').keypress(function(e) {
		if (e.which == 13) {
			$('#send-msg').click();
		}
	});
});

// 打開登入模態視窗的函數
function openLoginModal() {
	const loginModal = document.getElementById("loginModal");
	if (loginModal) {
		loginModal.style.display = "flex";
	}
}
</script> 