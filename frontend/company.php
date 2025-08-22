<!DOCTYPE html>
<html lang="zh-Hant">
<head>
	<meta charset="UTF-8">
	<?php include("share/header.php"); ?>
	<title>康寧大學產學合作平台</title>
	


	<!-- Font Awesome -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
	<!-- 表頭 -->
	<header class="header">
		<div class="header-container">
			<!-- Logo 區域 -->
			<div class="logo-section">
				<div class="logo">
					<i class="fas fa-university"></i>
				</div>
				<div class="logo-text">
					<h1 class="logo-title">康寧大學產學合作平台</h1>
					<p class="logo-subtitle">Kang Ning University Industry-Academia Cooperation Platform</p>
				</div>
			</div>
			</div>
		</div>
	</header>

	<!-- 主要內容區域 -->
	<main style="flex: 1; padding-top: 100px; padding-bottom: 50px;">
		<div style="max-width: 1400px; margin: 0 auto; padding: 0 20px;">
			<h2 style="color: #2c3e50; margin-bottom: 30px;">歡迎來到康寧大學產學合作平台</h2>
			<p style="color: #7f8c8d; line-height: 1.6; margin-bottom: 20px;">
				這裡是廠商儀表板的主要內容區域。您可以在此查看合作專案、管理訊息通知，以及查看歷史紀錄。
			</p>
			<div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);">
				<h3 style="color: #2c3e50; margin-bottom: 20px;">平台功能</h3>
				<ul style="color: #7f8c8d; line-height: 1.8;">
					<li>發布合作專案</li>
					<li>查看訊息中心與通知</li>
					<li>瀏覽歷史紀錄</li>
					<li>管理帳號設定</li>
				</ul>
			</div>
		</div>
	</main>

	<script>
		// 導航選單點擊效果
		document.querySelectorAll('.nav-item').forEach(item => {
			item.addEventListener('click', function(e) {
				// 檢查是否有實際的連結
				const href = this.getAttribute('href');
				if (href && href !== '#') {
					// 如果有實際連結，允許跳轉
					return;
				}
				
				// 如果沒有實際連結，阻止預設行為並處理active狀態
				e.preventDefault();
				// 移除所有active類別
				document.querySelectorAll('.nav-item').forEach(nav => {
					nav.classList.remove('active');
				});
				// 添加active類別到當前點擊的項目
				this.classList.add('active');
			});
		});

		// 漢堡選單功能
		document.getElementById('menuToggle').addEventListener('click', function() {
			const navMenu = document.querySelector('.nav-menu');
			navMenu.style.display = navMenu.style.display === 'flex' ? 'none' : 'flex';
		});
	</script>

    <?php include("share/footer.php"); ?>
    <?php include("share/chat_widget.php"); ?>
    <?php include("share/ai_widget.php"); ?>
</body>
</html>