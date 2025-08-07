<!DOCTYPE html>
<html lang="zh-Hant">

<head>
	<meta charset="UTF-8">
	<?php include("share/header.php"); ?>
	<title>老師</title>
	<link rel="stylesheet" href="assets/csp/QA.css">
	<style>
		.teacher-container {
			max-width: 1200px;
			margin: 120px auto 40px;
			padding: 40px;
			background: white;
			border-radius: 16px;
			box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
		}

		.welcome-section {
			text-align: center;
			margin-bottom: 40px;
		}

		.welcome-title {
			color: #003366;
			font-size: 32px;
			font-weight: bold;
			margin-bottom: 10px;
		}

		.welcome-subtitle {
			color: #666;
			font-size: 18px;
			margin-bottom: 30px;
		}

		.features-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
			gap: 30px;
			margin-top: 40px;
		}

		.feature-card {
			background: #f8f9fa;
			padding: 30px;
			border-radius: 12px;
			text-align: center;
			transition: transform 0.3s, box-shadow 0.3s;
		}

		.feature-card:hover {
			transform: translateY(-5px);
			box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
		}

		.feature-icon {
			font-size: 48px;
			margin-bottom: 20px;
			color: #007bff;
		}

		.feature-title {
			color: #003366;
			font-size: 20px;
			font-weight: bold;
			margin-bottom: 15px;
		}

		.feature-description {
			color: #666;
			line-height: 1.6;
		}

		.profile-reminder {
			background: #fff3cd;
			border: 1px solid #ffeaa7;
			border-radius: 8px;
			padding: 20px;
			margin-bottom: 30px;
			text-align: center;
		}

		.profile-reminder h3 {
			color: #856404;
			margin-bottom: 10px;
		}

		.profile-reminder p {
			color: #856404;
			margin-bottom: 15px;
		}

		.profile-btn {
			background: #007bff;
			color: white;
			text-decoration: none;
			padding: 10px 20px;
			border-radius: 6px;
			font-weight: 600;
			transition: background-color 0.3s;
		}

		.profile-btn:hover {
			background: #0056b3;
		}
	</style>
</head>

<body>
	<div class="teacher-container">
		<div class="welcome-section">
			<h1 class="welcome-title">歡迎，老師！</h1>
			<p class="welcome-subtitle">您可以在這裡管理您的產學合作相關事務</p>
		</div>

		<?php if (isset($_SESSION['role']) && $_SESSION['role'] === '老師'): ?>
			<div class="profile-reminder" id="profileReminder" style="display: none;">
				<h3>📝 完善個人資料</h3>
				<p>請填寫您的科系和聯絡電話，以便我們為您提供更好的服務。</p>
				<a href="teacher_profile.php" class="profile-btn">立即填寫</a>
			</div>
		<?php endif; ?>

		<div class="features-grid">
			<div class="feature-card">
				<div class="feature-icon">🤝</div>
				<h3 class="feature-title">產學合作</h3>
				<p class="feature-description">瀏覽和管理您的產學合作專案，與企業建立合作關係。</p>
			</div>
			
			<div class="feature-card">
				<div class="feature-icon">📚</div>
				<h3 class="feature-title">課程管理</h3>
				<p class="feature-description">管理您的課程內容，整合產學合作資源到教學中。</p>
			</div>
			
			<div class="feature-card">
				<div class="feature-icon">👥</div>
				<h3 class="feature-title">學生管理</h3>
				<p class="feature-description">查看參與產學合作專案的學生名單和進度。</p>
			</div>
			
			<div class="feature-card">
				<div class="feature-icon">📊</div>
				<h3 class="feature-title">數據分析</h3>
				<p class="feature-description">查看產學合作專案的統計數據和成效分析。</p>
			</div>
			
			<div class="feature-card">
				<div class="feature-icon">💼</div>
				<h3 class="feature-title">企業合作</h3>
				<p class="feature-description">與企業建立合作關係，尋找合適的合作夥伴。</p>
			</div>
			
			<div class="feature-card">
				<div class="feature-icon">📋</div>
				<h3 class="feature-title">報告管理</h3>
				<p class="feature-description">管理產學合作專案的報告和文件。</p>
			</div>
		</div>
	</div>

	<script>
		// 檢查是否需要顯示個人資料提醒
		function checkProfileReminder() {
			const username = '<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?>';
			const role = '<?php echo isset($_SESSION['role']) ? $_SESSION['role'] : ''; ?>';
			const reminder = document.getElementById('profileReminder');
			
			if (username && role === '老師' && reminder) {
				fetch(`http://localhost:5000/teacher/profile/${username}`)
					.then(response => {
						if (response.status === 404) {
							// 尚未填寫個人資料，顯示提醒
							reminder.style.display = 'block';
						} else {
							// 已填寫個人資料，隱藏提醒
							reminder.style.display = 'none';
						}
					})
					.catch(error => {
						console.log('檢查個人資料時發生錯誤');
					});
			}
		}

		// 頁面載入時檢查
		window.addEventListener('load', checkProfileReminder);
	</script>
	
	<?php include("share/ai_widget.php"); ?>
</body>

</html>