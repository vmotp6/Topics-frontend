<?php
// 載入 session 配置
require_once 'session_config.php';
require_once 'config.php';

// 檢查登入狀態（與系統其他文件保持一致）
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

// 從資料庫讀取輪播圖資料
$carousel_items = [];
$debug_info = []; // 調試信息
try {
    $conn = getDatabaseConnection();
    
    // 先檢查所有項目（包含未啟用的）用於調試
    $check_sql = "SELECT id, title, is_active FROM carousel_items";
    $check_result = $conn->query($check_sql);
    $all_items_count = $check_result ? $check_result->num_rows : 0;
    $debug_info['total_items'] = $all_items_count;
    
    // 讀取所有項目詳細信息（調試用）
    if ($check_result && $all_items_count > 0) {
        $debug_info['all_items'] = [];
        while ($item = $check_result->fetch_assoc()) {
            $debug_info['all_items'][] = $item;
        }
    }
    
    // 讀取啟用的輪播項目
    $carousel_sql = "SELECT * FROM carousel_items WHERE is_active = 1 ORDER BY display_order ASC, id DESC";
    $result = $conn->query($carousel_sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $carousel_items[] = $row;
        }
    }
    
    $debug_info['active_items_count'] = count($carousel_items);
    
    // 調試信息（可選，正式環境可移除）
    if ($all_items_count > 0 && count($carousel_items) == 0) {
        error_log("警告：資料庫中有 {$all_items_count} 個輪播項目，但沒有啟用的項目（is_active = 1）");
    }
    
    $conn->close();
} catch (Exception $e) {
    error_log("讀取輪播圖資料失敗: " . $e->getMessage());
    $debug_info['error'] = $e->getMessage();
}

// 如果 session 資料不完整，清除登入狀態
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (!isset($_SESSION['username']) || empty($_SESSION['username']) || 
        !isset($_SESSION['role']) || empty($_SESSION['role'])) {
        $_SESSION['logged_in'] = false;
        $isLoggedIn = false;
        // 清除不完整的 session 數據
        unset($_SESSION['username']);
        unset($_SESSION['role']);
        unset($_SESSION['login_method']);
    }
}

// 處理Google登入回調
if (isset($_GET['google_login']) && $_GET['google_login'] === 'success') {
    if (isset($_GET['username']) && isset($_GET['role'])) {
        // 驗證參數完整性
        $username = trim($_GET['username']);
        $role = trim($_GET['role']);
        
        // 確保參數不為空
        if (!empty($username) && !empty($role)) {
            // 設定Session
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            $_SESSION['login_method'] = 'google';
            
            // 更新 session 活動時間
            $_SESSION['last_activity'] = time();
            
            // 所有角色統一跳轉到 index.php，內容根據權限顯示
            header("Location: index.php");
            exit();
        } else {
            // 參數不完整，清除可能的錯誤 session
            $_SESSION['logged_in'] = false;
            unset($_SESSION['username']);
            unset($_SESSION['role']);
            unset($_SESSION['login_method']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>康寧大學招生平台</title>
  
  <!-- 輪播圖片樣式 -->
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

		/* 輪播圖片樣式 */
		.carousel-container {
			position: relative;
			width: 100%;
			height: 500px;
			overflow: hidden;
			margin-top: 80px;
			box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
		}

		.carousel-slide {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			opacity: 0;
			transition: opacity 0.8s ease-in-out;
			background-size: cover;
			background-position: center;
			background-repeat: no-repeat;
		}

		.carousel-slide.active {
			opacity: 1;
		}

		.slide-content {
			position: absolute;
			top: 50%;
			left: 50%;
			transform: translate(-50%, -50%);
			text-align: center;
			color: white;
			z-index: 2;
			width: 80%;
			max-width: 800px;
		}

		.slide-content h2 {
			font-size: 3rem;
			font-weight: 700;
			margin-bottom: 1rem;
			text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
			color: white;
			font-family: 'Microsoft JhengHei', sans-serif;
		}

		.slide-content p {
			font-size: 1.2rem;
			margin-bottom: 2rem;
			text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
			line-height: 1.6;
		}

		.slide-btn {
			display: inline-block;
			background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
			color: white;
			padding: 15px 30px;
			border-radius: 30px;
			text-decoration: none;
			font-weight: 600;
			font-size: 1.1rem;
			transition: all 0.3s ease;
			box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
		}

		.slide-btn:hover {
			transform: translateY(-3px);
			box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
			color: white;
		}

		.carousel-controls {
			position: absolute;
			bottom: 30px;
			left: 50%;
			transform: translateX(-50%);
			display: flex;
			gap: 15px;
			z-index: 3;
		}

		.carousel-dot {
			width: 12px;
			height: 12px;
			border-radius: 50%;
			background: rgba(255, 255, 255, 0.5);
			cursor: pointer;
			transition: all 0.3s ease;
		}

		.carousel-dot.active {
			background: white;
			transform: scale(1.2);
		}

		.carousel-arrow {
			position: absolute;
			top: 50%;
			transform: translateY(-50%);
			background: rgba(255, 255, 255, 0.2);
			color: white;
			border: none;
			width: 50px;
			height: 50px;
			border-radius: 50%;
			cursor: pointer;
			font-size: 1.5rem;
			transition: all 0.3s ease;
			z-index: 3;
			backdrop-filter: blur(10px);
		}

		.carousel-arrow:hover {
			background: rgba(255, 255, 255, 0.3);
			transform: translateY(-50%) scale(1.1);
		}

		.carousel-arrow.prev {
			left: 20px;
		}

		.carousel-arrow.next {
			right: 20px;
		}

		.slide-overlay {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: transparent;
			z-index: 1;
		}

		.loading-slide {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
			display: flex;
			align-items: center;
			justify-content: center;
			opacity: 1;
			transition: opacity 0.8s ease-in-out;
		}

		.loading-slide .slide-content {
			color: white;
		}

		/* 響應式設計 */
		@media (max-width: 768px) {
			.carousel-container {
				height: 400px;
				margin-top: 70px;
			}

			.slide-content h2 {
				font-size: 2rem;
			}

			.slide-content p {
				font-size: 1rem;
			}

			.slide-btn {
				padding: 12px 25px;
				font-size: 1rem;
			}

			.carousel-arrow {
				width: 40px;
				height: 40px;
				font-size: 1.2rem;
			}
		}

		@media (max-width: 480px) {
			.carousel-container {
				height: 350px;
			}

			.slide-content h2 {
				font-size: 1.5rem;
			}

			.slide-content p {
				font-size: 0.9rem;
			}
		}

		/* 學生管理模態視窗樣式（僅限本頁的學生管理 modal，避免影響全站登入/註冊 modal） */
		#studentManagementModal.modal {
			position: fixed;
			z-index: 1000;
			left: 0;
			top: 0;
			width: 100%;
			height: 100%;
			background-color: rgba(0,0,0,0.5);
			display: flex;
			align-items: center;
			justify-content: center;
		}

		#studentManagementModal .modal-content {
			background-color: white;
			border-radius: 12px;
			box-shadow: 0 4px 20px rgba(0,0,0,0.15);
			width: 90%;
			max-width: 1000px;
			max-height: 80vh;
			overflow-y: auto;
		}

		#studentManagementModal .modal-header {
			padding: 20px 30px;
			border-bottom: 1px solid #e0e0e0;
			display: flex;
			justify-content: space-between;
			align-items: center;
			background: #f8f9fa;
			border-radius: 12px 12px 0 0;
		}

		#studentManagementModal .modal-header h3 {
			margin: 0;
			color: #003366;
			font-size: 24px;
			font-weight: 600;
		}

		#studentManagementModal .close {
			font-size: 28px;
			font-weight: bold;
			cursor: pointer;
			color: #666;
			transition: color 0.3s;
		}

		#studentManagementModal .close:hover {
			color: #000;
		}

		#studentManagementModal .modal-body {
			padding: 30px;
		}

		.student-stats {
			display: flex;
			gap: 20px;
			margin-bottom: 30px;
		}

		.stat-card {
			background: linear-gradient(90deg, #68bbb9);
			color: white;
			padding: 20px;
			border-radius: 10px;
			text-align: center;
			flex: 1;
			box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
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

		.student-list-container {
			background: #f8f9fa;
			border-radius: 10px;
			padding: 20px;
		}

		.search-container {
			margin-bottom: 20px;
		}

		.search-input {
			width: 100%;
			padding: 12px 16px;
			border: 1px solid #ddd;
			border-radius: 8px;
			font-size: 16px;
			transition: border-color 0.3s;
		}

		.search-input:focus {
			outline: none;
			border-color: #667eea;
			box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
		}

		.student-list {
			max-height: 400px;
			overflow-y: auto;
		}

		.student-item {
			background: white;
			border: 1px solid #e0e0e0;
			border-radius: 8px;
			padding: 20px;
			margin-bottom: 15px;
			transition: all 0.3s;
			box-shadow: 0 2px 4px rgba(0,0,0,0.05);
		}

		.student-item:hover {
			box-shadow: 0 4px 12px rgba(0,0,0,0.1);
			transform: translateY(-2px);
		}

		.student-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 15px;
		}

		.student-name {
			font-size: 18px;
			font-weight: 600;
			color: #003366;
			margin: 0;
		}

		.student-identity {
			background: #e3f2fd;
			color: #1976d2;
			padding: 4px 12px;
			border-radius: 20px;
			font-size: 12px;
			font-weight: 500;
		}

		.student-info {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 15px;
			margin-bottom: 15px;
		}

		.info-item {
			display: flex;
			flex-direction: column;
		}

		.info-label {
			font-size: 12px;
			color: #666;
			margin-bottom: 4px;
			font-weight: 500;
		}

		.info-value {
			font-size: 14px;
			color: #333;
		}

		.student-intentions {
			background: #f5f5f5;
			padding: 15px;
			border-radius: 6px;
			margin-bottom: 15px;
		}

		.intentions-title {
			font-size: 14px;
			font-weight: 600;
			color: #333;
			margin-bottom: 10px;
		}

		.intention-item {
			background: white;
			padding: 8px 12px;
			border-radius: 4px;
			margin-bottom: 5px;
			font-size: 13px;
			color: #555;
		}

		.student-actions {
			display: flex;
			gap: 10px;
			justify-content: flex-end;
		}

		.action-btn {
			padding: 8px 16px;
			border: none;
			border-radius: 6px;
			cursor: pointer;
			font-size: 14px;
			font-weight: 500;
			transition: all 0.3s;
		}

		.btn-contact {
			background: #28a745;
			color: white;
		}

		.btn-contact:hover {
			background: #218838;
		}

		.btn-notes {
			background: #17a2b8;
			color: white;
		}

		.btn-notes:hover {
			background: #138496;
		}

		.btn-view-logs {
			background: #6c757d;
			color: white;
		}

		.btn-view-logs:hover {
			background: #5a6268;
		}

		.loading {
			text-align: center;
			padding: 40px;
			color: #666;
			font-size: 16px;
		}

		.empty-state {
			text-align: center;
			padding: 40px;
			color: #666;
		}

		.empty-state i {
			font-size: 48px;
			margin-bottom: 16px;
			color: #ccc;
		}

		/* 聯絡資訊模態視窗樣式 */
		.contact-info-item {
			background: white;
			border-radius: 8px;
			padding: 14px 16px;
			display: flex;
			align-items: center;
			gap: 12px;
			border: 1px solid #e0e0e0;
			box-shadow: 0 2px 4px rgba(0,0,0,0.05);
			width: 100%;
			box-sizing: border-box;
			transition: box-shadow 0.2s;
		}

		.contact-info-item:hover {
			box-shadow: 0 4px 8px rgba(0,0,0,0.08);
		}

		.contact-info-icon {
			width: 36px;
			height: 36px;
			display: flex;
			align-items: center;
			justify-content: center;
			flex-shrink: 0;
			background: #f8f9fa;
			border-radius: 8px;
		}

		.contact-info-icon i {
			font-size: 20px;
		}

		.contact-info-content {
			flex: 1;
			min-width: 120px;
			overflow: visible;
			padding-right: 12px;
		}

		.contact-info-label {
			font-size: 13px;
			color: #999;
			margin-bottom: 4px;
			white-space: nowrap;
		}

		.contact-info-value {
			font-size: 16px;
			color: #333;
			font-weight: 500;
			word-break: break-all;
			line-height: 1.5;
			white-space: normal;
		}

		.contact-info-value.contact-info-empty {
			color: #999;
		}

		.contact-info-copy-btn {
			background: #f5f5f5;
			border: none;
			padding: 0;
			width: 32px;
			height: 32px;
			border-radius: 6px;
			cursor: pointer;
			color: #666;
			display: flex;
			align-items: center;
			justify-content: center;
			transition: all 0.2s;
			flex-shrink: 0;
		}

		.contact-info-copy-btn:hover {
			background: #e8e8e8;
			color: #333;
			transform: scale(1.05);
		}

		.contact-info-copy-btn i {
			font-size: 14px;
		}

		/* 響應式設計 */
		@media (max-width: 768px) {
			.student-stats {
				flex-direction: column;
			}

			.student-info {
				grid-template-columns: 1fr;
			}

			.student-header {
				flex-direction: column;
				align-items: flex-start;
				gap: 10px;
			}

			.student-actions {
				justify-content: center;
			}
		}
	</style>
</head>

<body>
<?php include("share/header.php"); ?>
<main>
  <!-- 輪播圖片區域 -->
  <div class="carousel-container">
    <div id="carouselSlides">
      <!-- 輪播項目將由JavaScript動態載入 -->
      <div class="loading-slide">
        <div class="slide-content">
          <h2>載入中...</h2>
          <p>正在載入輪播內容</p>
        </div>
      </div>
    </div>

    <!-- 控制按鈕 -->
    <button class="carousel-arrow prev" onclick="changeSlide(-1)" id="prevBtn">❮</button>
    <button class="carousel-arrow next" onclick="changeSlide(1)" id="nextBtn">❯</button>

    <!-- 指示點 -->
    <div class="carousel-controls" id="carouselDots">
      <!-- 指示點將由JavaScript動態生成 -->
    </div>
  </div>
  <div class="teacher-container">
    <?php
    // 根據角色顯示不同的歡迎訊息
    $user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    $is_teacher = ($user_role === '老師' || $user_role === 'TEA' || $user_role === 'STA' || $user_role === '學校行政人員' || $user_role === 'DI' || $user_role === 'STA');
    $is_student = ($user_role === '學生' || $user_role === 'STU');
    $is_admin = ($user_role === '管理員' || $user_role === 'ADM');
    
    if ($isLoggedIn): ?>
      <div class="welcome-section">
        <?php if ($is_teacher): ?>
          <h1 class="welcome-title">歡迎，老師！</h1>
          <p class="welcome-subtitle">您可以在這裡管理您的產學合作相關事務</p>
        <?php elseif ($is_student): ?>
          <h1 class="welcome-title">歡迎，學生！</h1>
          <p class="welcome-subtitle">您可以在這裡管理您的產學合作相關事務</p>
        <?php elseif ($is_admin): ?>
          <h1 class="welcome-title">歡迎，管理員！</h1>
          <p class="welcome-subtitle">您可以在這裡管理系統相關事務</p>
        <?php else: ?>
          <h1 class="welcome-title">歡迎！</h1>
          <p class="welcome-subtitle">您可以在這裡使用各項功能</p>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    
    <div class="features-grid">
      <?php if (!$isLoggedIn): ?>
        <!-- 未登入/訪客：顯示所有公開功能 -->
        <div class="feature-card">
          <div class="feature-icon">🤝</div>
          <h3 class="feature-title">招生QA問答</h3>
          <p class="feature-description">提問有關招生、學費、科系、申請流程等資訊。</p>
          <a href="QA.php" class="feature-link">招生QA問答</a>
        </div>
        <div class="feature-card">
          <div class="feature-icon">🎥</div>
          <h3 class="feature-title">招生影片列表</h3>
          <p class="feature-description">查看招生影片列表，了解招生影片情況和進度。</p>
          <a href="radio.php" class="feature-link">招生影片列表</a>
        </div>

        <div class="feature-card">
          <div class="feature-icon">🗺️</div>
          <h3 class="feature-title">校園地圖</h3>
          <p class="feature-description">查看校園地圖，了解校園設施和位置。</p>
          <a href="campus_map.php" class="feature-link">校園地圖</a>
        </div>

        <div class="feature-card">
          <div class="feature-icon">🏫</div>
          <h3 class="feature-title">就讀意願登錄</h3>
          <p class="feature-description">登錄就讀意願，查看申請狀態並進行聯絡。</p>
          <a href="cooperation_upload.php" class="feature-link">就讀意願登錄</a>
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
          <div class="feature-icon">🎓</div>
          <h3 class="feature-title">國中生報名網頁</h3>
          <p class="feature-description">查看國中生報名網頁，了解國中生報名情況和進度。</p>
          <a href="mobile_junior.php" class="feature-link">國中生報名網頁</a>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">💬</div>
          <h3 class="feature-title">在校生留言板</h3>
          <p class="feature-description">查看學長姐在學校的生活經驗與心得。</p>
          <a href="senior_messages.php" class="feature-link">在校生留言板</a>
        </div>
        
      <?php elseif ($is_student): ?>
        <!-- 學生：顯示學生相關功能 -->
        <div class="feature-card">
          <div class="feature-icon">🤝</div>
          <h3 class="feature-title">招生QA問答</h3>
          <p class="feature-description">提問有關招生、學費、科系、申請流程等資訊。</p>
          <a href="QA.php" class="feature-link">招生QA問答</a>
        </div>

        <div class="feature-card">
          <div class="feature-icon">🎥</div>
          <h3 class="feature-title">招生影片列表</h3>
          <p class="feature-description">查看招生影片列表，了解招生影片情況和進度。</p>
          <a href="radio.php" class="feature-link">招生影片列表</a>
        </div>

        <div class="feature-card">
          <div class="feature-icon">🎮</div>
          <h3 class="feature-title">遊戲中心</h3>
          <p class="feature-description">查看遊戲中心，了解遊戲情況和進度。</p>
          <a href="game.php" class="feature-link">遊戲中心</a>
        </div>
        
        
        <div class="feature-card">
          <div class="feature-icon">🗺️</div>
          <h3 class="feature-title">校園地圖</h3>
          <p class="feature-description">查看校園地圖，了解校園設施和位置。</p>
          <a href="campus_map.php" class="feature-link">校園地圖</a>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">💬</div>
          <h3 class="feature-title">在校生留言板</h3>
          <p class="feature-description">查看學長姐在學校的生活經驗與心得。</p>
          <a href="senior_messages.php" class="feature-link">在校生留言板</a>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">📚</div>
          <h3 class="feature-title">私訊聊天室</h3>
          <p class="feature-description">學生或其他學生進行聊天。</p>
          <a href="chat/chat.php" class="feature-link">私訊聊天室</a>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">👑</div>
          <h3 class="feature-title">推薦報名</h3>
          <p class="feature-description">查看推薦報名情況和進度。</p>
          <a href="admission_recommend.php" class="feature-link">推薦報名</a>
        </div>
        
      <?php elseif ($is_teacher): ?>
        <!-- 老師/行政人員：顯示老師相關功能 -->
       <div class="feature-card">
          <div class="feature-icon">🗺️</div>
          <h3 class="feature-title">校園地圖</h3>
          <p class="feature-description">查看校園地圖，了解校園設施和位置。</p>
          <a href="campus_map.php" class="feature-link">校園地圖</a>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">📚</div>
          <h3 class="feature-title">私訊聊天室</h3>
          <p class="feature-description">老師或其他學生進行聊天。</p>
          <a href="chat/chat.php" class="feature-link">私訊聊天室</a>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">👑</div>
          <h3 class="feature-title">推薦報名</h3>
          <p class="feature-description">查看推薦報名情況和進度。</p>
          <a href="admission_recommend.php" class="feature-link">推薦報名</a>
        </div>

        <?php if ($user_role === '老師' || $user_role === 'TEA' || $user_role === 'DI' || $user_role === 'AA'): ?>
        <div class="feature-card">
          <div class="feature-icon">✉️</div>
          <h3 class="feature-title">活動紀錄填寫</h3>
          <p class="feature-description">填寫活動紀錄，查看填寫狀態並進行聯絡。</p>
          <a href="records.php" class="feature-link">活動紀錄填寫</a>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">📁</div>
          <h3 class="feature-title">檔案上傳</h3>
          <p class="feature-description">上傳和管理您的檔案（PPT、Word、圖片等），容量限制50GB。</p>
          <a href="teacher_file_upload.php" class="feature-link">檔案上傳</a>
        </div>
        <?php endif; ?>
        
        <div class="feature-card">
          <div class="feature-icon">👨‍🎓</div>
          <h3 class="feature-title">學生管理</h3>
          <p class="feature-description">查看和管理分配給您的所有學生（包含就讀意願和推薦報名），進行聯絡和追蹤。</p>
          <a href="student_management.php" class="feature-link">學生管理</a>
        </div>

        <div class="feature-card">
          <div class="feature-icon">📇</div>
          <h3 class="feature-title">學生聯絡管理</h3>
          <p class="feature-description">新增與查詢學生聯絡名單，管理狀態、聯絡方式與備註。</p>
          <a href="student_contact_management.php" class="feature-link">學生聯絡管理</a>
        </div>

        
        <div class="feature-card">
          <div class="feature-icon">📢</div>
          <h3 class="feature-title">學校活動通知系統</h3>
          <p class="feature-description">查看和管理學校活動通知。</p>
          <a href="mobile_teacher.php" class="feature-link">學校活動通知系統</a>
        </div>
        
        
      <?php elseif ($is_admin): ?>
        <!-- 管理員：顯示管理員相關功能 -->
        <div class="feature-card">
          <div class="feature-icon">👑</div>
          <h3 class="feature-title">推薦管理</h3>
          <p class="feature-description">管理推薦報名相關事務。</p>
          <a href="admin_recommendations.php" class="feature-link">推薦管理</a>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">📊</div>
          <h3 class="feature-title">招生管理</h3>
          <p class="feature-description">管理招生相關事務。</p>
          <a href="admin_admission.php" class="feature-link">招生管理</a>
        </div>
        
      <?php else: ?>
        <!-- 其他角色：顯示基本功能 -->
        <div class="feature-card">
          <div class="feature-icon">🤝</div>
          <h3 class="feature-title">招生QA問答</h3>
          <p class="feature-description">提問有關招生、學費、科系、申請流程等資訊。</p>
          <a href="QA.php" class="feature-link">招生QA問答</a>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">🗺️</div>
          <h3 class="feature-title">校園地圖</h3>
          <p class="feature-description">查看校園地圖，了解校園設施和位置。</p>
          <a href="campus_map.php" class="feature-link">校園地圖</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <!-- 輪播圖片JavaScript -->
  <script>
    const API_BASE_URL = 'http://localhost:5001';
    let currentSlideIndex = 0;
    let slides = [];
    let dots = [];
    let totalSlides = 0;
    let carouselSettings = {};
    let autoSlideInterval = null;

    // 載入輪播數據
    async function loadCarouselData() {
      try {
        // 從 PHP 變數中獲取輪播數據
        const carouselItems = <?php echo json_encode($carousel_items, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const debugInfo = <?php echo json_encode($debug_info ?? [], JSON_UNESCAPED_UNICODE); ?>;
        
        console.log('=== 輪播數據調試信息 ===');
        console.log('從資料庫讀取的輪播項目:', carouselItems);
        console.log('輪播項目數量:', carouselItems ? carouselItems.length : 0);
        console.log('調試信息:', debugInfo);
        
        if (debugInfo.total_items > 0 && debugInfo.all_items) {
          console.log('資料庫中的所有項目:', debugInfo.all_items);
          const inactiveItems = debugInfo.all_items.filter(item => item.is_active != 1);
          if (inactiveItems.length > 0) {
            console.warn('以下項目未啟用（is_active != 1）:', inactiveItems);
          }
        }
        
        if (carouselItems && carouselItems.length > 0) {
          // 轉換資料格式以匹配現有函數
          const formattedItems = carouselItems.map(item => ({
            id: item.id,
            title: item.title,
            description: item.description || '',
            image_url: item.image_url,
            button_text: item.button_text || '',
            button_link: item.button_link || ''
          }));
          
          console.log('格式化後的輪播項目:', formattedItems);
          console.log('=== 開始顯示資料庫輪播 ===');
          displayCarouselItems(formattedItems);
          setupCarouselControls();
          startAutoSlide();
        } else {
          // 如果沒有資料，使用預設輪播
          console.warn('資料庫中沒有啟用的輪播項目，使用預設輪播');
          console.log('提示：請確認：1. 是否在「頁面管理」中新增了項目？ 2. 項目是否已啟用（狀態欄顯示「啟用」）？');
          showDefaultCarousel();
        }
      } catch (error) {
        console.error('載入輪播數據錯誤:', error);
        showDefaultCarousel();
      }
    }

    // 顯示輪播項目
    function displayCarouselItems(items) {
      const container = document.getElementById('carouselSlides');
      
      if (items.length === 0) {
        showDefaultCarousel();
        return;
      }
      
      container.innerHTML = items.map((item, index) => {
        // 處理圖片路徑：如果是相對路徑，加上基礎路徑
        let imageUrl = item.image_url;
        if (!imageUrl.startsWith('http') && !imageUrl.startsWith('/')) {
          // 相對路徑，加上當前目錄路徑
          const basePath = '<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/'); ?>';
          imageUrl = basePath + '/' + imageUrl;
        }
        
        return `
        <div class="carousel-slide ${index === 0 ? 'active' : ''}" 
             style="background-image: url('${imageUrl}');">
          <div class="slide-overlay"></div>
          <div class="slide-content">
            <h2>${escapeHtml(item.title || '')}</h2>
            <p>${escapeHtml(item.description || '')}</p>
            ${item.button_text ? `<a href="${item.button_link || '#'}" class="slide-btn">${escapeHtml(item.button_text)}</a>` : ''}
          </div>
        </div>
      `;
      }).join('');
      
      // 更新slides數組
      slides = document.querySelectorAll('.carousel-slide');
      totalSlides = slides.length;
    }
    
    // HTML 轉義函數
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }


    // 顯示預設輪播（當API載入失敗時）
    function showDefaultCarousel() {
      const container = document.getElementById('carouselSlides');
      container.innerHTML = `
        <div class="carousel-slide active" style="background-image: url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2071&q=80');">
          <div class="slide-overlay"></div>
          <div class="slide-content">
            <h2>歡迎來到康寧大學招生平台</h2>
            <p>連結學術研究與產業發展，創造雙贏的產學合作機會</p>
            <a href="QA.php" class="slide-btn">了解更多</a>
          </div>
        </div>
        <div class="carousel-slide" style="background-image: url('https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');">
          <div class="slide-overlay"></div>
          <div class="slide-content">
            <h2>產學合作新契機</h2>
            <p>與企業攜手共創未來，提供學生實務學習機會</p>
            <a href="admission_recommend.php" class="slide-btn">推薦報名</a>
          </div>
        </div>
        <div class="carousel-slide" style="background-image: url('https://images.unsplash.com/photo-1523240798034-6c2165d05d14?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');">
          <div class="slide-overlay"></div>
          <div class="slide-content">
            <h2>五專入學說明會</h2>
            <p>深入了解五專課程特色與未來發展方向</p>
            <a href="admission.php" class="slide-btn">立即報名</a>
          </div>
        </div>
      `;
      
      slides = document.querySelectorAll('.carousel-slide');
      totalSlides = slides.length;
      
      // 設置預設控制
      const dotsContainer = document.getElementById('carouselDots');
      dotsContainer.innerHTML = '';
      for (let i = 0; i < totalSlides; i++) {
        dotsContainer.innerHTML += `<div class="carousel-dot ${i === 0 ? 'active' : ''}" onclick="currentSlide(${i + 1})"></div>`;
      }
      dots = document.querySelectorAll('.carousel-dot');
      
      // 啟動自動輪播
      startAutoSlide();
    }

    // 顯示指定索引的輪播圖片
    function showSlide(index) {
      if (totalSlides === 0) return;
      
      // 隱藏所有輪播圖片
      slides.forEach(slide => slide.classList.remove('active'));
      dots.forEach(dot => dot.classList.remove('active'));

      // 顯示當前輪播圖片
      if (slides[index]) slides[index].classList.add('active');
      if (dots[index]) dots[index].classList.add('active');
    }

    // 切換到下一張或上一張輪播圖片
    function changeSlide(direction) {
      if (totalSlides === 0) return;
      
      currentSlideIndex += direction;
      
      if (currentSlideIndex >= totalSlides) {
        currentSlideIndex = 0;
      } else if (currentSlideIndex < 0) {
        currentSlideIndex = totalSlides - 1;
      }
      
      showSlide(currentSlideIndex);
    }

    // 直接切換到指定輪播圖片
    function currentSlide(index) {
      if (totalSlides === 0) return;
      
      currentSlideIndex = index - 1;
      showSlide(currentSlideIndex);
    }

    // 自動輪播
    function autoSlide() {
      if (totalSlides > 1) {
        changeSlide(1);
      }
    }

    // 開始自動輪播
    function startAutoSlide() {
      // 清除現有的自動輪播
      if (autoSlideInterval) {
        clearInterval(autoSlideInterval);
      }
      
      // 設置新的自動輪播（預設 5 秒）
      if (totalSlides > 1) {
        autoSlideInterval = setInterval(autoSlide, 5000);
      }
    }
    
    // 設置輪播控制（簡化版）
    function setupCarouselControls() {
      const dotsContainer = document.getElementById('carouselDots');
      const prevBtn = document.getElementById('prevBtn');
      const nextBtn = document.getElementById('nextBtn');
      
      // 確保 slides 已經初始化
      if (!slides || slides.length === 0) {
        slides = document.querySelectorAll('.carousel-slide');
        totalSlides = slides.length;
      }
      
      // 生成指示點（將 NodeList 轉換為數組）
      dotsContainer.innerHTML = Array.from(slides).map((_, index) => `
        <div class="carousel-dot ${index === 0 ? 'active' : ''}" onclick="currentSlide(${index + 1})"></div>
      `).join('');
      dots = document.querySelectorAll('.carousel-dot');
      
      // 顯示控制按鈕
      if (prevBtn) prevBtn.style.display = 'flex';
      if (nextBtn) nextBtn.style.display = 'flex';
    }

    // 鍵盤控制
    document.addEventListener('keydown', function(e) {
      if (e.key === 'ArrowLeft') {
        changeSlide(-1);
      } else if (e.key === 'ArrowRight') {
        changeSlide(1);
      }
    });

    // 觸控支援（滑動切換）
    let touchStartX = 0;
    let touchEndX = 0;

    document.querySelector('.carousel-container').addEventListener('touchstart', function(e) {
      touchStartX = e.changedTouches[0].screenX;
    });

    document.querySelector('.carousel-container').addEventListener('touchend', function(e) {
      touchEndX = e.changedTouches[0].screenX;
      handleSwipe();
    });

    function handleSwipe() {
      const swipeThreshold = 50;
      const diff = touchStartX - touchEndX;
      
      if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0) {
          // 向左滑動，下一張
          changeSlide(1);
        } else {
          // 向右滑動，上一張
          changeSlide(-1);
        }
      }
    }

    // 頁面載入時執行
    document.addEventListener('DOMContentLoaded', function() {
      loadCarouselData();
    });
  </script>

  <?php if ($is_teacher): ?>
  <!-- 學生管理模態視窗 -->
  <div id="studentManagementModal" class="modal" style="display: none;">
    <div class="modal-content">
      <div class="modal-header">
        <h3>學生管理</h3>
        <span class="close" onclick="closeStudentManagement()">&times;</span>
      </div>
      <div class="modal-body">
        <div style="display:flex; flex-direction:column; gap:16px;">
          <div class="student-stats">
            <div class="stat-card">
              <div class="stat-number" id="totalStudents">0</div>
              <div class="stat-label">總學生數</div>
            </div>
            <div class="stat-card">
              <div class="stat-number" id="recentAssignments">0</div>
              <div class="stat-label">近7天分配</div>
            </div>
          </div>

          <!-- 新增學生聯絡資訊 -->
          <div style="background:#f8f9fa; border:1px solid #e0e0e0; border-radius:10px; padding:16px;">
            <h4 style="margin:0 0 12px 0; color:#003366;">新增學生聯絡資訊</h4>
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:12px;">
              <div>
                <label style="font-size:12px; color:#666;">姓名 *</label>
                <input id="newContactName" type="text" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;" placeholder="必填">
              </div>
              <div>
                <label style="font-size:12px; color:#666;">國中</label>
                <input id="newContactJuniorHigh" type="text" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;" placeholder="例：永吉國中">
              </div>
              <div>
                <label style="font-size:12px; color:#666;">興趣科系</label>
                <input id="newContactInterest" type="text" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;" placeholder="例：資管科">
              </div>
              <div>
                <label style="font-size:12px; color:#666;">活動來源</label>
                <input id="newContactSource" type="text" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;" placeholder="例：說明會/參訪/展覽">
              </div>
              <div>
                <label style="font-size:12px; color:#666;">聯絡教師</label>
                <input id="newContactTeacher" type="text" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;" placeholder="例：王小明">
              </div>
              <div>
                <label style="font-size:12px; color:#666;">狀態</label>
                <select id="newContactStatus" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;">
                  <option value="">請選擇</option>
                  <option value="新建立">新建立</option>
                  <option value="已聯絡">已聯絡</option>
                  <option value="有興趣">有興趣</option>
                  <option value="不感興趣">不感興趣</option>
                  <option value="已報名">已報名</option>
                  <option value="其他">其他</option>
                </select>
              </div>
            </div>
            <div style="margin-top:12px; display:flex; gap:8px; justify-content:flex-end;">
              <button class="btn-confirm" onclick="submitNewStudentContact()" style="background:#1890ff; color:white; border:none; padding:8px 14px; border-radius:6px;">儲存</button>
              <button class="btn-cancel" onclick="resetNewStudentContactForm()" style="background:#f5f5f5; border:none; padding:8px 14px; border-radius:6px;">清除</button>
            </div>
          </div>

          <div class="student-list-container">
            <div class="search-container">
              <input type="text" id="studentSearch" placeholder="搜尋學生姓名或電話..." class="search-input">
            </div>
            
            <div class="student-list" id="studentList">
              <div class="loading">載入中...</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 聯絡資訊模態視窗 -->
  <div id="contactInfoModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px; background: #f5f5f5; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
      <div class="modal-header" style="background: white; border-radius: 12px 12px 0 0; padding: 20px 24px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; color: #333; font-size: 18px; font-weight: 600;">聯絡資訊 - <span id="contactInfoStudentName"></span></h3>
        <span class="close" onclick="closeContactInfoModal()" style="font-size: 28px; color: #999; cursor: pointer; line-height: 1;">&times;</span>
      </div>
      <div class="modal-body" style="padding: 24px;">
        <div id="contactInfoList" style="display: flex; flex-direction: column; gap: 12px;">
          <!-- 聯絡資訊項目將在這裡動態生成 -->
        </div>
      </div>
      <div class="modal-footer" style="padding: 16px 24px; border-top: 1px solid #e0e0e0; display: flex; justify-content: center; background: white; border-radius: 0 0 12px 12px;">
        <button onclick="closeContactInfoModal()" style="background: #f5f5f5; border: none; padding: 10px 24px; border-radius: 6px; cursor: pointer; color: #333; font-size: 14px; font-weight: 500;">關閉</button>
      </div>
    </div>
  </div>

  <!-- 查看聯絡紀錄模態視窗 -->
  <div id="viewContactLogsModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
      <div class="modal-header">
        <h3>聯絡紀錄</h3>
        <span class="close" onclick="closeViewContactLogs()">&times;</span>
      </div>
      <div class="modal-body" style="padding: 24px;">
        <div style="margin-bottom: 16px; font-weight: 600; color: #003366; font-size: 16px;">
          學生：<span id="viewLogsStudentName"></span>
        </div>
        <div id="contactLogsList" style="max-height: 500px; overflow-y: auto;">
          <!-- 聯絡紀錄列表將在這裡顯示 -->
        </div>
      </div>
      <div class="modal-footer" style="padding: 16px 24px; border-top: 1px solid #e0e0e0; display:flex; justify-content:flex-end;">
        <button class="btn-cancel" onclick="closeViewContactLogs()" style="background:#f5f5f5; border:none; padding:8px 16px; border-radius:6px;">關閉</button>
      </div>
    </div>
  </div>

  <!-- 聯絡紀錄新增模態視窗 -->
  <div id="addContactLogModal" class="modal" style="display: none;">
    <div class="modal-content">
      <div class="modal-header">
        <h3>新增聯絡紀錄</h3>
        <span class="close" onclick="closeAddContactLog()">&times;</span>
      </div>
      <div class="modal-body" style="padding: 24px;">
        <div style="margin-bottom: 12px; font-weight: 600; color: #003366;">學生：<span id="contactLogStudentName"></span></div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
          <div>
            <label style="display:block; font-size: 13px; color:#666; margin-bottom:6px;">聯絡日期</label>
            <input type="date" id="contactDate" style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px;">
          </div>
          <div>
            <label style="display:block; font-size: 13px; color:#666; margin-bottom:6px;">聯絡方式</label>
            <select id="contactMethod" style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px;">
              <option value="電話">電話</option>
              <option value="Line">Line</option>
              <option value="Email">Email</option>
              <option value="面談">面談</option>
            </select>
          </div>
        </div>
        <div style="margin-top: 16px;">
          <label style="display:block; font-size: 13px; color:#666; margin-bottom:6px;">聯絡紀錄</label>
          <textarea id="contactNotes" rows="6" style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px;" placeholder="請填寫聯絡內容和結果..."></textarea>
        </div>
      </div>
      <div class="modal-footer" style="padding: 16px 24px; border-top: 1px solid #e0e0e0; display:flex; justify-content:flex-end; gap:10px;">
        <button class="btn-cancel" onclick="closeAddContactLog()" style="background:#f5f5f5; border:none; padding:8px 16px; border-radius:6px;">取消</button>
        <button class="btn-confirm" onclick="submitAddContactLog()" style="background:#1890ff; color:white; border:none; padding:8px 16px; border-radius:6px;">儲存</button>
      </div>
    </div>
  </div>

  <script>
    // 學生管理相關變數
    let studentsData = [];
    let filteredStudents = [];
    let currentContactStudentId = null;
    // 新增學生聯絡資訊
    async function submitNewStudentContact() {
      const name = document.getElementById('newContactName')?.value.trim() || '';
      const juniorHigh = document.getElementById('newContactJuniorHigh')?.value.trim() || '';
      const interest = document.getElementById('newContactInterest')?.value.trim() || '';
      const source = document.getElementById('newContactSource')?.value.trim() || '';
      const teacher = document.getElementById('newContactTeacher')?.value.trim() || '';
      const status = document.getElementById('newContactStatus')?.value.trim() || '';

      if (!name) {
        alert('請填寫姓名');
        return;
      }
      try {
        const res = await fetch('api/add_student_contact_api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            name,
            junior_high: juniorHigh,
            interest_department: interest,
            activity_source: source,
            contact_teacher: teacher,
            status
          })
        });
        const data = await res.json();
        if (data.success) {
          alert('新增成功');
          resetNewStudentContactForm();
        } else {
          alert(data.message || '新增失敗');
        }
      } catch (e) {
        console.error(e);
        alert('新增失敗，請稍後再試');
      }
    }
    function resetNewStudentContactForm() {
      const ids = ['newContactName','newContactJuniorHigh','newContactInterest','newContactSource','newContactTeacher'];
      ids.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
      });
      const status = document.getElementById('newContactStatus');
      if (status) status.value = '';
    }

    // 開啟學生管理模態視窗
    function openStudentManagement() {
      document.getElementById('studentManagementModal').style.display = 'flex';
      loadStudentsData();
    }

    // 關閉學生管理模態視窗
    function closeStudentManagement() {
      document.getElementById('studentManagementModal').style.display = 'none';
    }

    // 載入學生資料
    async function loadStudentsData() {
      const studentList = document.getElementById('studentList');
      studentList.innerHTML = '<div class="loading">載入中...</div>';

      try {
        const response = await fetch('api/teacher_students_api.php');
        
        if (!response.ok) {
          const text = await response.text();
          console.error('API 回應錯誤:', {
            status: response.status,
            statusText: response.statusText,
            content: text.substring(0, 200)
          });
          throw new Error(`伺服器錯誤 (${response.status}): ${response.statusText}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
          const text = await response.text();
          console.error('回應不是 JSON 格式:', text.substring(0, 200));
          throw new Error('伺服器回應格式錯誤，請檢查 API 端點');
        }
        
        const data = await response.json();

        if (data.success) {
          studentsData = data.students;
          filteredStudents = [...studentsData];
          
          document.getElementById('totalStudents').textContent = data.statistics.total_students;
          document.getElementById('recentAssignments').textContent = data.statistics.recent_assignments;
          
          displayStudents(filteredStudents);
        } else {
          console.error('API 錯誤:', data);
          studentList.innerHTML = `
            <div class="empty-state">
              <i class="fas fa-exclamation-triangle"></i>
              <p>載入失敗：${data.message}</p>
              ${data.debug ? `<p style="font-size: 12px; color: #999;">調試信息：${JSON.stringify(data.debug)}</p>` : ''}
            </div>
          `;
        }
      } catch (error) {
        console.error('載入學生資料錯誤:', error);
        studentList.innerHTML = `
          <div class="empty-state">
            <i class="fas fa-exclamation-triangle"></i>
            <p>載入失敗，請稍後再試</p>
            <p style="font-size: 12px; color: #999;">錯誤詳情：${error.message}</p>
          </div>
        `;
      }
    }

    // 顯示學生列表
    function displayStudents(students) {
      const studentList = document.getElementById('studentList');
      
      if (students.length === 0) {
        studentList.innerHTML = `
          <div class="empty-state">
            <i class="fas fa-user-graduate"></i>
            <p>目前沒有分配給您的學生</p>
          </div>
        `;
        return;
      }

      studentList.innerHTML = students.map(student => `
        <div class="student-item">
          <div class="student-header">
            <h4 class="student-name">${escapeHtml(student.name)}</h4>
            <span class="student-identity">${escapeHtml(student.identity)}</span>
          </div>
          
          <div class="student-info">
            <div class="info-item">
              <div class="info-label">性別</div>
              <div class="info-value">${escapeHtml(student.gender || '未提供')}</div>
            </div>
            <div class="info-item">
              <div class="info-label">聯絡電話一</div>
              <div class="info-value">${escapeHtml(student.phone1)}</div>
            </div>
            <div class="info-item">
              <div class="info-label">聯絡電話二</div>
              <div class="info-value">${escapeHtml(student.phone2 || '無')}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Email</div>
              <div class="info-value">${escapeHtml(student.email || '無')}</div>
            </div>
            <div class="info-item">
              <div class="info-label">就讀學校</div>
              <div class="info-value">${escapeHtml(student.junior_high || '無')}</div>
            </div>
            <div class="info-item">
              <div class="info-label">年級</div>
              <div class="info-value">${escapeHtml(student.current_grade || '無')}</div>
            </div>
          </div>

          <div class="student-intentions">
            <div class="intentions-title">就讀意願</div>
            ${student.intention1 ? `<div class="intention-item">意願一：${escapeHtml(student.intention1)} (${escapeHtml(student.system1 || 'N/A')})</div>` : ''}
            ${student.intention2 ? `<div class="intention-item">意願二：${escapeHtml(student.intention2)} (${escapeHtml(student.system2 || 'N/A')})</div>` : ''}
            ${student.intention3 ? `<div class="intention-item">意願三：${escapeHtml(student.intention3)} (${escapeHtml(student.system3 || 'N/A')})</div>` : ''}
          </div>

          <div class="student-actions">
            <button class="action-btn btn-contact" onclick="showContactInfo('${escapeHtml(student.name)}', '${escapeHtml(student.phone1 || '')}', '${escapeHtml(student.phone2 || '')}', '${escapeHtml(student.email || '')}', '${escapeHtml(student.line_id || '')}', '${escapeHtml(student.facebook || '')}')">
              <i class="fas fa-phone"></i> 聯絡資訊
            </button>
            <button class="action-btn btn-view-logs" onclick="viewContactLogs(${student.id}, '${escapeHtml(student.name)}')">
              <i class="fas fa-history"></i> 查看聯絡紀錄
            </button>
            <button class="action-btn btn-notes" onclick="openAddContactLog(${student.id}, '${escapeHtml(student.name)}')">
              <i class="fas fa-sticky-note"></i> 新增聯絡紀錄
            </button>
          </div>
        </div>
      `).join('');
    }

    // 搜尋學生
    function searchStudents() {
      const searchTerm = document.getElementById('studentSearch').value.toLowerCase();
      filteredStudents = studentsData.filter(student => 
        student.name.toLowerCase().includes(searchTerm) ||
        student.phone1.includes(searchTerm) ||
        (student.phone2 && student.phone2.includes(searchTerm)) ||
        (student.email && student.email.toLowerCase().includes(searchTerm))
      );
      displayStudents(filteredStudents);
    }

    // 顯示聯絡資訊
    function showContactInfo(name, phone1, phone2, email, lineId, facebook) {
      document.getElementById('contactInfoStudentName').textContent = name;
      const contactInfoList = document.getElementById('contactInfoList');
      contactInfoList.innerHTML = '';
      
      if (phone1) {
        contactInfoList.innerHTML += `
          <div class="contact-info-item">
            <div class="contact-info-icon">
              <i class="fas fa-phone" style="color: #1890ff;"></i>
            </div>
            <div class="contact-info-content">
              <div class="contact-info-label">聯絡電話一</div>
              <div class="contact-info-value">${escapeHtml(phone1)}</div>
            </div>
            <button class="contact-info-copy-btn" onclick="copyToClipboard('${escapeHtml(phone1)}')" title="複製">
              <i class="fas fa-copy"></i>
            </button>
          </div>
        `;
      }
      
      if (phone2) {
        contactInfoList.innerHTML += `
          <div class="contact-info-item">
            <div class="contact-info-icon">
              <i class="fas fa-phone" style="color: #1890ff;"></i>
            </div>
            <div class="contact-info-content">
              <div class="contact-info-label">聯絡電話二</div>
              <div class="contact-info-value">${escapeHtml(phone2)}</div>
            </div>
            <button class="contact-info-copy-btn" onclick="copyToClipboard('${escapeHtml(phone2)}')" title="複製">
              <i class="fas fa-copy"></i>
            </button>
          </div>
        `;
      }
      
      if (lineId) {
        contactInfoList.innerHTML += `
          <div class="contact-info-item">
            <div class="contact-info-icon">
              <i class="fab fa-line" style="color: #00c300; font-size: 24px;"></i>
            </div>
            <div class="contact-info-content">
              <div class="contact-info-label">Line ID</div>
              <div class="contact-info-value">${escapeHtml(lineId)}</div>
            </div>
            <button class="contact-info-copy-btn" onclick="copyToClipboard('${escapeHtml(lineId)}')" title="複製">
              <i class="fas fa-copy"></i>
            </button>
          </div>
        `;
      }
      
      contactInfoList.innerHTML += `
        <div class="contact-info-item">
          <div class="contact-info-icon">
            <i class="fas fa-envelope" style="color: #1890ff;"></i>
          </div>
          <div class="contact-info-content">
            <div class="contact-info-label">Email</div>
            <div class="contact-info-value ${email ? '' : 'contact-info-empty'}">${email ? escapeHtml(email) : '無'}</div>
          </div>
          ${email ? `<button class="contact-info-copy-btn" onclick="copyToClipboard('${escapeHtml(email)}')" title="複製">
            <i class="fas fa-copy"></i>
          </button>` : ''}
        </div>
      `;
      
      if (facebook) {
        contactInfoList.innerHTML += `
          <div class="contact-info-item">
            <div class="contact-info-icon">
              <i class="fab fa-facebook" style="color: #1877f2;"></i>
            </div>
            <div class="contact-info-content">
              <div class="contact-info-label">Facebook</div>
              <div class="contact-info-value">${escapeHtml(facebook)}</div>
            </div>
            <button class="contact-info-copy-btn" onclick="copyToClipboard('${escapeHtml(facebook)}')" title="複製">
              <i class="fas fa-copy"></i>
            </button>
          </div>
        `;
      }
      
      document.getElementById('contactInfoModal').style.display = 'flex';
    }
    
    // 複製到剪貼簿
    function copyToClipboard(text) {
      navigator.clipboard.writeText(text).then(() => {
        const toast = document.createElement('div');
        toast.textContent = '已複製到剪貼簿';
        toast.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #52c41a; color: white; padding: 12px 24px; border-radius: 6px; z-index: 10000; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
        document.body.appendChild(toast);
        setTimeout(() => {
          toast.remove();
        }, 2000);
      }).catch(err => {
        alert('複製失敗：' + err);
      });
    }
    
    // 關閉聯絡資訊模態視窗
    function closeContactInfoModal() {
      document.getElementById('contactInfoModal').style.display = 'none';
    }

    // 開啟新增聯絡紀錄模態視窗
    function openAddContactLog(studentId, studentName) {
      currentContactStudentId = studentId;
      document.getElementById('contactLogStudentName').textContent = studentName;
      const today = new Date().toISOString().slice(0, 10);
      document.getElementById('contactDate').value = today;
      document.getElementById('contactMethod').value = '電話';
      document.getElementById('contactNotes').value = '';
      document.getElementById('addContactLogModal').style.display = 'flex';
    }

    // 關閉新增聯絡紀錄模態視窗
    function closeAddContactLog() {
      document.getElementById('addContactLogModal').style.display = 'none';
      currentContactStudentId = null;
    }

// 提交新增聯絡紀錄
async function submitAddContactLog() {
  if (!currentContactStudentId) return;
  const contact_date = document.getElementById('contactDate').value;
  const contact_method = document.getElementById('contactMethod').value;
  const notes = document.getElementById('contactNotes').value.trim();

  if (!contact_date || !notes) {
    alert('請填寫聯絡日期和聯絡紀錄');
    return;
  }

  try {
    
    const response = await fetch('api/contact_logs_api.php', {  
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        student_id: currentContactStudentId,
        contact_date: contact_date,
        contact_method: contact_method,
        notes: notes
      })
    });

    const data = await response.json();

    if (data.success) {
      alert('聯絡紀錄已新增');
      closeAddContactLog();
      // 如果正在查看該學生的聯絡紀錄，重新載入
      if (document.getElementById('viewContactLogsModal').style.display === 'flex') {
        viewContactLogs(currentContactStudentId, document.getElementById('viewLogsStudentName').textContent);
      }
    } else {
      alert('新增失敗：' + (data.message || '未知錯誤'));
    }
  } catch (error) {
    console.error('新增聯絡紀錄錯誤:', error);
    alert('新增失敗，請稍後再試');
  }
}

// 查看聯絡紀錄
async function viewContactLogs(studentId, studentName) {
  document.getElementById('viewLogsStudentName').textContent = studentName;
  const contactLogsList = document.getElementById('contactLogsList');
  contactLogsList.innerHTML = '<div class="loading">載入中...</div>';
  document.getElementById('viewContactLogsModal').style.display = 'flex';

  try {
    // 修改這裡：將 api/get_contact_logs.php 改為 api/contact_logs_api.php
    const response = await fetch(`api/contact_logs_api.php?student_id=${studentId}`);
    
    // 增加錯誤檢查，避免 JSON 解析失敗
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();

    if (data.success) {
      if (data.logs.length === 0) {
        contactLogsList.innerHTML = `
          <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>目前沒有聯絡紀錄</p>
          </div>
        `;
      } else {
        contactLogsList.innerHTML = data.logs.map(log => `
          <div class="student-item">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
              <div>
                <strong>${escapeHtml(log.contact_date)}</strong>
                <span style="margin-left: 10px; background: #e3f2fd; color: #1976d2; padding: 2px 8px; border-radius: 12px; font-size: 12px;">${escapeHtml(log.method || log.contact_method)}</span>
              </div>
            </div>
            <div style="color: #666; line-height: 1.6;">${escapeHtml(log.notes)}</div>
          </div>
        `).join('');
      }
    } else {
      contactLogsList.innerHTML = `
        <div class="empty-state">
          <i class="fas fa-exclamation-triangle"></i>
          <p>載入失敗：${data.message || '未知錯誤'}</p>
        </div>
      `;
    }
  } catch (error) {
    console.error('載入聯絡紀錄錯誤:', error);
    contactLogsList.innerHTML = `
      <div class="empty-state">
        <i class="fas fa-exclamation-triangle"></i>
        <p>載入失敗，請稍後再試</p>
      </div>
    `;
  }
}

    // 關閉查看聯絡紀錄模態視窗
    function closeViewContactLogs() {
      document.getElementById('viewContactLogsModal').style.display = 'none';
    }

    // HTML 轉義函數（如果尚未定義）
    if (typeof escapeHtml === 'undefined') {
      function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
      }
    }

    // 點擊模態視窗外部關閉
    document.getElementById('studentManagementModal')?.addEventListener('click', function(e) {
      if (e.target === this) {
        closeStudentManagement();
      }
    });

    // 搜尋功能
    document.getElementById('studentSearch')?.addEventListener('input', searchStudents);
  </script>
  <?php endif; ?>
</main>
<?php include("share/footer.php"); ?>

</body>
</html>