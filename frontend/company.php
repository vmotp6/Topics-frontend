<!DOCTYPE html>
<html lang="zh-Hant">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php include("share/header.php"); ?>
	<title>康寧大學產學合作平台</title>
	
	<style>
		/* 廠商儀表板樣式 */
		body {
			margin: 0;
			padding: 0;
			font-family: font-family: 'Microsoft JhengHei', sans-serif;
			background: linear-gradient(135deg,rgb(244, 246, 252) 0%,rgb(249, 249, 249) 100%);
			min-height: 100vh;
			display: flex;
			flex-direction: column;
		}

		/* 表頭樣式 */
		.header {
			background: rgba(217, 229, 234, 0.95);
			backdrop-filter: blur(10px);
			box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			z-index: 1000;
			padding: 15px 0;
		}

		.header-container {
			max-width: 1400px;
			margin: 0 auto;
			padding: 0 20px;
			display: flex;
			align-items: center;
			justify-content: space-between;
		}

		/* Logo 區域 */
		.logo-section {
			display: flex;
			align-items: center;
			gap: 15px;
		}

		.logo {
			width: 50px;
			height: 50px;
			background: linear-gradient(135deg, #667eea 0%,rgb(168, 186, 221) 100%);
			border-radius: 12px;
			display: flex;
			align-items: center;
			justify-content: center;
			color: white;
			font-size: 24px;
			font-weight: bold;
			box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
		}

		.logo-text {
			display: flex;
			flex-direction: column;
		}

		.logo-title {
			font-size: 1.4rem;
			font-weight: 700;
			color: #2c3e50;
			margin: 0;
			line-height: 1.2;
		}

		.logo-subtitle {
			font-size: 0.9rem;
			color: #7f8c8d;
			margin: 0;
			font-weight: 500;
		}

		/* 導航選單 */
		.nav-menu {
			display: flex;
			align-items: center;
			gap: 30px;
		}

		.nav-item {
			text-decoration: none;
			color: #2c3e50;
			font-weight: 600;
			font-size: 1rem;
			padding: 10px 15px;
			border-radius: 8px;
			transition: all 0.3s ease;
			position: relative;
		}

		.nav-item:hover {
			background:  #667eea;
			color: white;
			transform: translateY(-2px);
			box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
		}

		.nav-item.active {
			background: linear-gradient(135deg,rgb(223, 73, 173) 0%,rgb(202, 106, 146) 100%);
			color: white;
		}

		/* 右側功能區 */
		.header-right {
			display: flex;
			align-items: center;
			gap: 20px;
		}

		.account-section {
			display: flex;
			align-items: center;
			gap: 15px;
		}

		.account-btn {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
			border: none;
			padding: 10px 20px;
			border-radius: 25px;
			font-size: 0.9rem;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
			text-decoration: none;
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.account-btn:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
		}

		.logout-btn {
			background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
			color: white;
			border: none;
			padding: 10px 20px;
			border-radius: 25px;
			font-size: 0.9rem;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
			text-decoration: none;
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.logout-btn:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
		}

		/* 漢堡選單 */
		.menu-toggle {
			display: none;
			flex-direction: column;
			cursor: pointer;
			padding: 5px;
			border-radius: 8px;
			transition: all 0.3s ease;
		}

		.menu-toggle:hover {
			background: rgba(102, 126, 234, 0.1);
		}

		.menu-line {
			width: 25px;
			height: 3px;
			background: #2c3e50;
			margin: 3px 0;
			border-radius: 2px;
			transition: all 0.3s ease;
		}

		.menu-toggle:hover .menu-line {
			background: #667eea;
		}

		/* 響應式設計 */
		@media (max-width: 1024px) {
			.nav-menu {
				gap: 20px;
			}

			.nav-item {
				font-size: 0.9rem;
				padding: 8px 12px;
			}
		}

		@media (max-width: 768px) {
			.nav-menu {
				display: none;
			}

			.menu-toggle {
				display: flex;
			}

			.logo-title {
				font-size: 1.2rem;
			}

			.logo-subtitle {
				font-size: 0.8rem;
			}

			.account-section {
				gap: 10px;
			}

			.account-btn, .logout-btn {
				padding: 8px 15px;
				font-size: 0.8rem;
			}
		}

		/* 頁尾樣式 */
		.footer {
			background: rgba(217, 229, 234, 0.95);
			backdrop-filter: blur(10px);
			box-shadow: 0 -2px 20px rgba(0, 0, 0, 0.1);
			padding: 30px 0;
			margin-top: auto;
			position: relative;
			z-index: 100;
		}

		.footer-container {
			max-width: 1400px;
			margin: 0 auto;
			padding: 0 20px;
			display: flex;
			align-items: center;
			justify-content: space-between;
		}

		.footer-logo-section {
			display: flex;
			align-items: center;
			gap: 15px;
		}

		.footer-logo {
			width: 40px;
			height: 40px;
			background: linear-gradient(135deg, #667eea 0%,rgb(168, 186, 221) 100%);
			border-radius: 10px;
			display: flex;
			align-items: center;
			justify-content: center;
			color: white;
			font-size: 20px;
			font-weight: bold;
			box-shadow: 0 3px 12px rgba(102, 126, 234, 0.3);
		}

		.footer-logo-text {
			display: flex;
			flex-direction: column;
		}

		.footer-logo-title {
			font-size: 1.1rem;
			font-weight: 700;
			color: #2c3e50;
			margin: 0;
			line-height: 1.2;
		}

		.footer-logo-subtitle {
			font-size: 0.8rem;
			color: #7f8c8d;
			margin: 0;
			font-weight: 500;
		}

		.footer-info {
			text-align: right;
			color: #2c3e50;
		}

		.footer-address {
			font-size: 0.9rem;
			margin: 0 0 5px 0;
			font-weight: 500;
		}

		.footer-phone {
			font-size: 0.9rem;
			margin: 0;
			font-weight: 500;
		}

		/* 響應式頁尾 */
		@media (max-width: 768px) {
			.footer-container {
				flex-direction: column;
				gap: 20px;
				text-align: center;
			}

			.footer-info {
				text-align: center;
			}

			.footer-logo-title {
				font-size: 1rem;
			}

			.footer-logo-subtitle {
				font-size: 0.7rem;
			}

			.footer-address, .footer-phone {
				font-size: 0.8rem;
			}
		}
	</style>

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

			<!-- 導航選單 -->
			<nav class="nav-menu">
				<a href="one.php" class="nav-item">
					首頁
				</a>
				<a href="#" class="nav-item">
					發布合作專案
				</a>
				<a href="#" class="nav-item">
					訊息中心/通知
				</a>
				<a href="#" class="nav-item">
					歷史紀錄
				</a>
				<a href="#" class="nav-item">
					表單下載
				</a>
			</nav>

			<!-- 右側功能區 -->
			<div class="header-right">
				<div class="account-section">
					<a href="#" class="account-btn">
						<i class="fas fa-user-cog"></i>
						帳號設定
					</a>
					<a href="logout.php" class="logout-btn">
						<i class="fas fa-sign-out-alt"></i>
						登出
					</a>
				</div>
				
				<!-- 漢堡選單 -->
				<div class="menu-toggle" id="menuToggle">
					<div class="menu-line"></div>
					<div class="menu-line"></div>
					<div class="menu-line"></div>
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

	<!-- 頁尾 -->
	<footer class="footer">
		<div class="footer-container">
			<div class="footer-logo-section">
				<div class="footer-logo">
					<i class="fas fa-university"></i>
				</div>
				<div class="footer-logo-text">
					<h3 class="footer-logo-title">康寧大學產學合作平台</h3>
					<p class="footer-logo-subtitle">Kang Ning University Industry-Academia Cooperation Platform</p>
				</div>
			</div>
			<div class="footer-info">
				<p class="footer-address">
					地址：台北市內湖區康寧路三段75巷137號
				</p>
				<p class="footer-phone">
					電話：02-26321181分機107
				</p>
			</div>
		</div>
	</footer>
</body>
</html>