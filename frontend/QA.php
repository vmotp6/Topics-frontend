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
	</script>

	<?php include("share/footer.php"); ?>
	<?php include("share/ai_widget.php"); ?>
</body>

</html>