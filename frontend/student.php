<?php
// 載入 session 配置
require_once 'session_config.php';

// 檢查登入狀態（與 header.php 保持一致）
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

// 如果未登入，重定向到首頁
if (!$isLoggedIn) {
    header("Location: index.php");
    exit;
}

// 檢查是否為學生角色
if (!isset($_SESSION['role']) || $_SESSION['role'] !== '學生') {
    header("Location: index.php");
    exit;
}

// 處理Google登入回調
if (isset($_GET['google_login']) && $_GET['google_login'] === 'success') {
    if (isset($_GET['username']) && isset($_GET['role'])) {
        // 設定Session
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $_GET['username'];
        $_SESSION['role'] = $_GET['role'];
        $_SESSION['login_method'] = 'google';
        
        // 重定向到相應頁面（避免URL參數顯示）
        $redirect_url = 'index.php';
        if ($_GET['role'] === '管理員') {
            $redirect_url = 'admin.php';
        } elseif ($_GET['role'] === '學生') {
            $redirect_url = 'teacher.php';
        }
        
        header("Location: $redirect_url");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
	<meta charset="UTF-8">
	<?php include("share/header.php"); ?>
	<title>學生</title>
	<link rel="stylesheet" href="assets/csp/QA.css">
	<style>
		body { padding-top: 100px; }
		main { flex: 1; }
		.teacher-container {
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

		.feature-link {
			display: inline-block;
			background: #007bff;
			color: white;
			text-decoration: none;
			padding: 8px 16px;
			border-radius: 4px;
			font-size: 14px;
			margin: 5px;
			transition: background-color 0.3s;
		}

		.feature-link:hover {
			background: #0056b3;
		}
	</style>
</head>

<body>
	<main>
	<div class="teacher-container">
		<div class="welcome-section">
			<h1 class="welcome-title">歡迎，學生！</h1>
		</div>

		<?php if (isset($_SESSION['role']) && $_SESSION['role'] === '學生'): ?>
			<div class="profile-reminder" id="profileReminder" style="display: none;">
				<h3>📝 完善個人資料</h3>
				<p>請填寫您的科系和聯絡電話，以便我們為您提供更好的服務。</p>
				<a href="teacher_profile.php" class="profile-btn">立即填寫</a>
			</div>
		<?php endif; ?>

		<div class="features-grid">
			<div class="feature-card">
				<div class="feature-icon">🤝</div>
				<h3 class="feature-title">招生QA問答</h3>
				<p class="feature-description">提問有關招生、學費、科系、申請流程等資訊。</p>
				<a href="QA.php" class="feature-link">招生QA問答</a>
			</div>
			
			<div class="feature-card">
				<div class="feature-icon">📚</div>
				<h3 class="feature-title">私訊聊天室</h3>
				<p class="feature-description">老師或其他學生進行聊天。</p>
				<a href="chat/chat.php" class="feature-link">私訊聊天室</a>
			</div>
			
			<div class="feature-card">
				<div class="feature-icon">👥</div>
				<h3 class="feature-title">續招報名</h3>
				<p class="feature-description">查看續招報名情況和進度。</p>
				<a href="continued_admission.php" class="feature-link">續招報名</a>
			</div>
			
			<div class="feature-card">
				<div class="feature-icon">📊</div>
				<h3 class="feature-title">五專入學說明會</h3>
				<p class="feature-description">查看五專入學說明會情況和進度。</p>
				<a href="admission.php" class="feature-link">五專入學說明會</a>
			</div>
			
			<div class="feature-card">
				<div class="feature-icon">👥</div>
				<h3 class="feature-title">推薦報名</h3>
				<p class="feature-description">查看推薦報名情況和進度。</p>
				<a href="admission_recommend.php" class="feature-link">推薦報名</a>
			</div>
			
			<div class="feature-card">
				<div class="feature-icon">🎓</div>
				<h3 class="feature-title">就讀意願管理</h3>
				<p class="feature-description">管理學生和家長的就讀意願登錄，查看申請狀態並進行聯絡。</p>
				<a href="admin_enrollment_review_fixed.php" class="feature-link">管理就讀意願</a>
			</div>
		</div>
	</div>
	</main>

	<script>
		// 檢查是否需要顯示個人資料提醒
		function checkProfileReminder() {
			const username = '<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?>';
			const role = '<?php echo isset($_SESSION['role']) ? $_SESSION['role'] : ''; ?>';
			const reminder = document.getElementById('profileReminder');
			
			// 暫時禁用此功能，避免 500 錯誤
			// 等後端服務器修復後再啟用
			console.log('個人資料提醒檢查功能已暫時禁用');
			return;
			
		if (username && role === '學生' && reminder) {
			// 使用 AbortController 來設置超時
			const controller = new AbortController();
			const timeoutId = setTimeout(() => controller.abort(), 5000); // 5秒超時
			
			fetch(`http://100.79.58.120:5000/teacher/profile/${username}`, {
				signal: controller.signal,
				method: 'GET',
				headers: {
					'Accept': 'application/json',
				}
			})
			.then(response => {
				clearTimeout(timeoutId);
				if (response.status === 404) {
					// 尚未填寫個人資料，顯示提醒
					reminder.style.display = 'block';
				} else if (response.ok) {
					// 已填寫個人資料，隱藏提醒
					reminder.style.display = 'none';
				}
				// 對於其他狀態碼（包括500），不做任何處理
			})
			.catch(error => {
				clearTimeout(timeoutId);
				// 靜默處理錯誤，不顯示任何錯誤訊息
			});
		}
		}

		// 頁面載入時檢查（暫時禁用）
		// window.addEventListener('load', checkProfileReminder);
	</script>
	
    <?php include("share/footer.php"); ?>
    <?php include("share/chat_widget.php"); ?>
    <?php include("share/ai_widget.php"); ?>
</body>

</html>