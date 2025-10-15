<?php
// 載入 session 配置
require_once 'session_config.php';

// 處理Google登入回調（必須在登入檢查之前）
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
            $redirect_url = 'admin_admission.php';
        } elseif ($_GET['role'] === '老師') {
            $redirect_url = 'teacher.php';
        } elseif ($_GET['role'] === '學生') {
            $redirect_url = 'student.php';
        }
        
        header("Location: $redirect_url");
        exit();
    }
}

// 檢查登入狀態（與 header.php 保持一致）
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

// 如果未登入，重定向到首頁
if (!$isLoggedIn) {
    header("Location: index.php");
    exit;
}

// 檢查是否為老師角色
if (!isset($_SESSION['role']) || $_SESSION['role'] !== '老師') {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
	<meta charset="UTF-8">
	<?php include("share/header.php"); ?>
	<title>老師</title>
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
				<a href="cooperation_upload.php" class="feature-link">📝 上傳申請表</a>
				<a href="teacher_cooperation_status.php" class="feature-link">📋 查看申請狀態</a>
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
			
		if (username && role === '老師' && reminder) {
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