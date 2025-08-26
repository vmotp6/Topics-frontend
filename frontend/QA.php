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
	</main>
	<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
	<script>
$(document).ready(function() {
    // 1. 呼叫 Flask API 撈 FAQ
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

        // 2. 啟用動畫
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
    });
});
	</script>

	<?php include("share/footer.php"); ?>
	<?php include("share/ai_widget.php"); ?>
</body>

</html>