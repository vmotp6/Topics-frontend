<!DOCTYPE html>
<html lang="zh-Hant">

<head>
	<meta charset="UTF-8">
	<?php include("share/header.php"); ?>
	<title>行政人員管理平台</title>
	<style>
		body { padding-top: 100px; }
		main { flex: 1; }
		.admin-container {
			max-width: 1200px;
			margin: 40px auto 40px;
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
			margin-bottom: 20px;
		}

		.feature-link {
			display: inline-block;
			background: #007bff;
			color: white;
			text-decoration: none;
			padding: 10px 20px;
			border-radius: 6px;
			font-size: 14px;
			font-weight: 600;
			transition: background-color 0.3s;
		}

		.feature-link:hover {
			background: #0056b3;
		}

		.stats-section {
			background: linear-gradient(135deg, #667eea, #764ba2);
			color: white;
			padding: 30px;
			border-radius: 12px;
			margin-bottom: 30px;
		}

		.stats-title {
			font-size: 24px;
			font-weight: bold;
			margin-bottom: 20px;
			text-align: center;
		}

		.stats-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 20px;
		}

		.stat-item {
			text-align: center;
			padding: 20px;
			background: rgba(255, 255, 255, 0.1);
			border-radius: 8px;
		}

		.stat-number {
			font-size: 32px;
			font-weight: bold;
			margin-bottom: 5px;
		}

		.stat-label {
			font-size: 14px;
			opacity: 0.9;
		}
	</style>
</head>

<body>
	<main>
	<div class="admin-container">
		<div class="welcome-section">
			<h1 class="welcome-title">歡迎，行政人員！</h1>
			<p class="welcome-subtitle">您可以在這裡管理產學合作相關事務</p>
		</div>

		<!-- 統計資訊 -->
		<div class="stats-section">
			<h2 class="stats-title">📊 申請統計</h2>
			<div class="stats-grid">
				<div class="stat-item">
					<div class="stat-number" id="pendingCount">0</div>
					<div class="stat-label">待審核申請</div>
				</div>
				<div class="stat-item">
					<div class="stat-number" id="approvedCount">0</div>
					<div class="stat-label">已通過申請</div>
				</div>
				<div class="stat-item">
					<div class="stat-number" id="rejectedCount">0</div>
					<div class="stat-label">已拒絕申請</div>
				</div>
				<div class="stat-item">
					<div class="stat-number" id="totalCount">0</div>
					<div class="stat-label">總申請數</div>
				</div>
			</div>
		</div>

		<div class="features-grid">
			<div class="feature-card">
				<div class="feature-icon">📋</div>
				<h3 class="feature-title">申請審核</h3>
				<p class="feature-description">審核老師提交的產學合作申請表，查看詳細資訊並進行審核決策。</p>
				<a href="admin_cooperation_review.php" class="feature-link">開始審核</a>
			</div>
			
			<div class="feature-card">
				<div class="feature-icon">📊</div>
				<h3 class="feature-title">數據分析</h3>
				<p class="feature-description">查看產學合作專案的統計數據、趨勢分析和成效報告。</p>
				<a href="#" class="feature-link">查看報表</a>
			</div>
			
			<div class="feature-card">
				<div class="feature-icon">👥</div>
				<h3 class="feature-title">用戶管理</h3>
				<p class="feature-description">管理平台用戶帳號，包括老師、學生、廠商等各類用戶。</p>
				<a href="#" class="feature-link">管理用戶</a>
			</div>
			
			<div class="feature-card">
				<div class="feature-icon">⚙️</div>
				<h3 class="feature-title">系統設定</h3>
				<p class="feature-description">管理系統參數、權限設定和平台配置。</p>
				<a href="#" class="feature-link">系統設定</a>
			</div>
			
			<div class="feature-card">
				<div class="feature-icon">📁</div>
				<h3 class="feature-title">檔案管理</h3>
				<p class="feature-description">管理上傳的申請表檔案、合約書和計畫書等文件。</p>
				<a href="#" class="feature-link">檔案管理</a>
			</div>
			
			<div class="feature-card">
				<div class="feature-icon">📧</div>
				<h3 class="feature-title">通知管理</h3>
				<p class="feature-description">發送系統通知、審核結果通知和重要公告。</p>
				<a href="#" class="feature-link">發送通知</a>
			</div>
		</div>
	</div>
	</main>

	<script>
		// 載入統計數據
		function loadStats() {
			fetch('/backend/cooperation_stats_api.php')
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						document.getElementById('pendingCount').textContent = data.stats.pending || 0;
						document.getElementById('approvedCount').textContent = data.stats.approved || 0;
						document.getElementById('rejectedCount').textContent = data.stats.rejected || 0;
						document.getElementById('totalCount').textContent = data.stats.total || 0;
					} else {
						console.log('統計API返回錯誤:', data.message);
					}
				})
				.catch(error => {
					console.log('載入統計數據時發生錯誤:', error);
				});
		}

		// 頁面載入時執行
		window.addEventListener('load', loadStats);
	</script>
	
    <?php include("share/footer.php"); ?>
    <?php include("share/chat_widget.php"); ?>
    <?php include("share/ai_widget.php"); ?>
</body>

</html>
