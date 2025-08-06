<?php
session_start();
$isLoggedIn = isset($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
	<meta charset="UTF-8">
	<title>認識產學合作</title>
	<link rel="stylesheet" href="assets/csp/QA.css">
	<!-- 浮動聊天按鈕 -->
	<div id="chat-float-btn">💬</div>

	<!-- 聊天視窗 -->
	<div id="chat-box">
		<div id="chat-header">小聊天室 <span id="chat-close">✖</span></div>
		<div id="chat-messages">
			<?php if ($isLoggedIn): ?>
				<p>歡迎使用小聊天室！</p>
				<div class="feature-intro">
					<h4>📋 網站功能介紹</h4>
					<ul>
						<li><strong>產學合作平台：</strong>提供教師與企業合作的機會</li>
						<li><strong>專題合作：</strong>學生可以參與企業實務專題</li>
						<li><strong>人才媒合：</strong>企業尋找合適的學術人才</li>
						<li><strong>技術轉移：</strong>學術研究成果商業化</li>
						<li><strong>實習申請：</strong>學生申請企業實習機會</li>
						<li><strong>專利資源：</strong>共享學術專利與技術資源</li>
					</ul>
				</div>
				<p>💡 <strong>使用提示：</strong></p>
				<p>• 您可以詢問關於產學合作的任何問題</p>
				<p>• 系統會根據您的需求提供相關資訊</p>
				<p>• 如需詳細合作資訊，請聯繫相關部門</p>
			<?php else: ?>
				<div class="login-prompt">
					<p>🔒 請先登入才能使用聊天功能</p>
					<p>登入後您可以：</p>
					<ul style="text-align: left; display: inline-block;">
						<li>詢問產學合作相關問題</li>
						<li>了解平台功能與服務</li>
						<li>獲取最新合作機會資訊</li>
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

</head>
<?php include("share/header.php"); ?>

<body>
	<main>
		<h2>認識產學合作</h2>
		<div class="faq-item">
			<div class="faq-question">Q1: 產學合作主要提供什麼服務？</div>
			<div class="faq-answer">
				A: 產學合作平台提供教師與企業合作的機會，包含專題合作、人才媒合、技術轉移、實習申請、專利資源共享等多元化服務，促進學用合一。
			</div>
		</div>

		<div class="faq-item">
			<div class="faq-question">Q2: 產學合作平台與校內平台有何不同？</div>
			<div class="faq-answer">
				A: 產學合作平台多以跨校整合資源為主，能讓更多外部企業找到合適的學術夥伴，而校內平台主要針對本校教師與學生之間的媒合。
			</div>
		</div>

		<div class="faq-item">
			<div class="faq-question">Q3: 使用平台需要申請帳號嗎？</div>
			<div class="faq-answer">
				A: 是的，使用者需要註冊帳號才能提交合作提案或查詢媒合紀錄，平台支援單一登入整合（SSO）。
			</div>
		</div>
	</main>
	<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
	<script>
		$(document).ready(function() {
			// 點擊浮動按鈕，顯示/隱藏聊天視窗
			$('#chat-float-btn').click(function() {
				$('#chat-box').toggle();
			});

			// 點擊關閉按鈕
			$('#chat-close').click(function() {
				$('#chat-box').hide();
			});

			// 發送訊息（只有登入用戶才能使用）
			$('#send-msg').click(function() {
				let msg = $('#chat-input-field').val().trim();
				if (msg) {
					$('#chat-messages').append('<p><b>你:</b> ' + msg + '</p>');
					$('#chat-input-field').val('');
					$('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
					
					// 模擬自動回覆
					setTimeout(function() {
						let responses = [
							"感謝您的提問！我們的客服人員會盡快回覆您。",
							"這是一個很好的問題，讓我為您詳細說明...",
							"根據您的需求，我建議您可以...",
							"我們有相關的合作案例可以參考，請稍等...",
							"這個問題涉及多個部門，我會轉介給相關單位。"
						];
						let randomResponse = responses[Math.floor(Math.random() * responses.length)];
						$('#chat-messages').append('<p><b>客服:</b> ' + randomResponse + '</p>');
						$('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
					}, 1000);
				}
			});

			// 按 Enter 發送
			$('#chat-input-field').keypress(function(e) {
				if (e.which == 13) {
					$('#send-msg').click();
				}
			});
		});

		$(document).ready(function() {
			// 每個答案都包一層動畫容器（只做一次）
			$('.faq-answer').each(function() {
				if (!$(this).find('.faq-content').length) {
					$(this).wrapInner('<div class="faq-content"></div>');
				}
			});
			$('.faq-content').each(function() {
				$(this).show();
				setTimeout(() => {
					$(this).hide();
				}, 0);
			});

			$('.faq-question').click(function() {
				const $this = $(this);
				const $answer = $this.next('.faq-answer');
				const $content = $answer.find('.faq-content');

				if ($this.hasClass('active')) {
					// 收回目前項目
					$content.slideUp(300, function() {
						$answer.removeClass('show');
					});
					$this.removeClass('active');
				} else {
					// 收起其他
					$('.faq-question').removeClass('active');
					$('.faq-answer .faq-content').slideUp(300);
					$('.faq-answer').removeClass('show');

					// 展開這個
					$answer.addClass('show');
					$content.slideDown(300);
					$this.addClass('active');
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

	<?php include("share/footer.php"); ?>
</body>

</html>