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
	<h1>老師專區</h1>
	<p>歡迎來到老師專區！</p>
	
	<!-- 引入通用聊天室組件 -->
	<?php include("share/chat_widget.php"); ?>
	
	<?php include("share/footer.php"); ?>
	<?php include("share/ai_widget.php"); ?>
</body>

</html>