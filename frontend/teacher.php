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
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
			background: linear-gradient(135deg, rgba(0, 0, 0, 0.4) 0%, rgba(0, 0, 0, 0.2) 100%);
			z-index: 1;
		}

		.loading-slide {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

		/* 響應式設計 */
		@media (max-width: 768px) {
			.modal-content {
				width: 95%;
				margin: 20px;
			}

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
        // 暫時禁用API調用，直接使用預設輪播
        console.log('使用預設輪播內容');
        showDefaultCarousel();
        return;
        
        // 原始API調用代碼（已註解）
        /*
        const response = await fetch(`${API_BASE_URL}/api/carousel`);
        const data = await response.json();
        
        if (response.ok) {
          carouselSettings = data.settings;
          displayCarouselItems(data.items);
          setupCarouselControls();
          startAutoSlide();
        } else {
          console.error('載入輪播數據失敗:', data.message);
          showDefaultCarousel();
        }
        */
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
      
      container.innerHTML = items.map((item, index) => `
        <div class="carousel-slide ${index === 0 ? 'active' : ''}" 
             style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('${item.image_url}');">
          <div class="slide-overlay"></div>
          <div class="slide-content">
            <h2>${item.title}</h2>
            <p>${item.description || ''}</p>
            ${item.button_text ? `<a href="${item.button_link || '#'}" class="slide-btn">${item.button_text}</a>` : ''}
          </div>
        </div>
      `).join('');
      
      // 更新slides數組
      slides = document.querySelectorAll('.carousel-slide');
      totalSlides = slides.length;
    }

    // 設置輪播控制
    function setupCarouselControls() {
      const dotsContainer = document.getElementById('carouselDots');
      const prevBtn = document.getElementById('prevBtn');
      const nextBtn = document.getElementById('nextBtn');
      
      // 生成指示點
      if (carouselSettings.enable_indicators !== 0) {
        dotsContainer.innerHTML = slides.map((_, index) => `
          <div class="carousel-dot ${index === 0 ? 'active' : ''}" onclick="currentSlide(${index + 1})"></div>
        `).join('');
        dots = document.querySelectorAll('.carousel-dot');
      } else {
        dotsContainer.innerHTML = '';
        dots = [];
      }
      
      // 設置控制按鈕可見性
      if (carouselSettings.enable_controls !== 0) {
        prevBtn.style.display = 'flex';
        nextBtn.style.display = 'flex';
      } else {
        prevBtn.style.display = 'none';
        nextBtn.style.display = 'none';
      }
    }

    // 顯示預設輪播（當API載入失敗時）
    function showDefaultCarousel() {
      const container = document.getElementById('carouselSlides');
      container.innerHTML = `
        <div class="carousel-slide active" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2071&q=80');">
          <div class="slide-overlay"></div>
          <div class="slide-content">
            <h2>歡迎來到康寧大學招生平台</h2>
            <p>連結學術研究與產業發展，創造雙贏的產學合作機會</p>
            <a href="QA.php" class="slide-btn">了解更多</a>
          </div>
        </div>
        <div class="carousel-slide" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');">
          <div class="slide-overlay"></div>
          <div class="slide-content">
            <h2>產學合作新契機</h2>
            <p>與企業攜手共創未來，提供學生實務學習機會</p>
            <a href="admission_recommend.php" class="slide-btn">推薦報名</a>
          </div>
        </div>
        <div class="carousel-slide" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1523240798034-6c2165d05d14?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');">
          <div class="slide-overlay"></div>
          
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
      if (carouselSettings.enable_auto_slide !== 0 && totalSlides > 1) {
        changeSlide(1);
      }
    }

    // 開始自動輪播
    function startAutoSlide() {
      // 清除現有的自動輪播
      if (autoSlideInterval) {
        clearInterval(autoSlideInterval);
      }
      
      // 設置新的自動輪播
      if (carouselSettings.enable_auto_slide !== 0 && totalSlides > 1) {
        const interval = carouselSettings.auto_slide_interval || 5000;
        autoSlideInterval = setInterval(autoSlide, interval);
      }
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
				<div class="feature-icon">👑</div>
				<h3 class="feature-title">推薦報名</h3>
				<p class="feature-description">查看推薦報名情況和進度。</p>
				<a href="admission_recommend.php" class="feature-link">推薦報名</a>
			</div>

			<div class="feature-card">
				<div class="feature-icon">✉️</div>
				<h3 class="feature-title">活動紀錄填寫</h3>
				<p class="feature-description">填寫活動紀錄，查看填寫狀態並進行聯絡。</p>
				<a href="records.php" class="feature-link">活動紀錄填寫</a>
			</div>

			<div class="feature-card">
				<div class="feature-icon">📱</div>
				<h3 class="feature-title">學校活動通知系統</h3>
				<p class="feature-description">發送學校活動通知給學生。</p>
				<a href="mobile_teacher.php" class="feature-link">學校活動通知系統</a>
			</div>
			
			<div class="feature-card">
				<div class="feature-icon">👨‍🎓</div>
				<h3 class="feature-title">學生管理</h3>
				<p class="feature-description">查看和管理分配給您的學生，進行聯絡和追蹤。</p>
				<button onclick="openStudentManagement()" class="feature-link" style="border: none; cursor: pointer; width: 35%;">學生管理</button>
			</div>
		</div>
	</div>
	</main>

	<!-- 學生管理模態視窗 -->
	<div id="studentManagementModal" class="modal" style="display: none;">
		<div class="modal-content">
			<div class="modal-header">
				<h3>學生管理</h3>
				<span class="close" onclick="closeStudentManagement()">&times;</span>
			</div>
			<div class="modal-body">
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
			
			fetch(`http://localhost:5000/teacher/profile/${username}`, {
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

		// 學生管理相關變數
		let studentsData = [];
		let filteredStudents = [];

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
				// 先測試基本 API 功能
				console.log('測試基本 API 功能...');
				const testResponse = await fetch('api/test_api.php');
				const testData = await testResponse.json();
				console.log('測試 API 結果:', testData);
				
				if (!testData.success) {
					throw new Error('基本 API 測試失敗：' + testData.message);
				}
				
				// 如果基本測試通過，再載入學生資料
				const response = await fetch('api/teacher_students_api.php');
				const data = await response.json();

				if (data.success) {
					studentsData = data.students;
					filteredStudents = [...studentsData];
					
					// 更新統計資訊
					document.getElementById('totalStudents').textContent = data.statistics.total_students;
					document.getElementById('recentAssignments').textContent = data.statistics.recent_assignments;
					
					// 顯示學生列表
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
						<h4 class="student-name">${student.name}</h4>
						<span class="student-identity">${student.identity}</span>
					</div>
					
					<div class="student-info">
						<div class="info-item">
							<div class="info-label">性別</div>
							<div class="info-value">${student.gender || '未提供'}</div>
						</div>
						<div class="info-item">
							<div class="info-label">聯絡電話一</div>
							<div class="info-value">${student.phone1}</div>
						</div>
						<div class="info-item">
							<div class="info-label">聯絡電話二</div>
							<div class="info-value">${student.phone2 || '無'}</div>
						</div>
						<div class="info-item">
							<div class="info-label">Email</div>
							<div class="info-value">${student.email || '無'}</div>
						</div>
						<div class="info-item">
							<div class="info-label">就讀學校</div>
							<div class="info-value">${student.junior_high || '無'}</div>
						</div>
						<div class="info-item">
							<div class="info-label">年級</div>
							<div class="info-value">${student.current_grade || '無'}</div>
						</div>
					</div>

					<div class="student-intentions">
						<div class="intentions-title">就讀意願</div>
						${student.intention1 ? `<div class="intention-item">意願一：${student.intention1} (${student.system1 || 'N/A'})</div>` : ''}
						${student.intention2 ? `<div class="intention-item">意願二：${student.intention2} (${student.system2 || 'N/A'})</div>` : ''}
						${student.intention3 ? `<div class="intention-item">意願三：${student.intention3} (${student.system3 || 'N/A'})</div>` : ''}
					</div>

					<div class="student-actions">
						<button class="action-btn btn-contact" onclick="contactStudent('${student.phone1}', '${student.name}')">
							<i class="fas fa-phone"></i> 聯絡
						</button>
                        <button class="action-btn btn-notes" onclick="openAddContactLog(${student.id}, '${student.name}')">
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

		// 聯絡學生
		function contactStudent(phone, name) {
			if (confirm(`是否要聯絡 ${name}？\n電話：${phone}`)) {
				// 這裡可以添加實際的聯絡功能，比如打開電話應用或發送簡訊
				window.open(`tel:${phone}`);
			}
		}

		// 添加學生備註
		function addStudentNotes(studentId, studentName) {
			const notes = prompt(`為 ${studentName} 添加備註：`);
			if (notes !== null) {
				// 這裡可以添加保存備註的功能
				alert('備註功能開發中...');
			}
		}

		// 點擊模態視窗外部關閉
		document.getElementById('studentManagementModal').addEventListener('click', function(e) {
			if (e.target === this) {
				closeStudentManagement();
			}
		});

		// 搜尋功能
		document.getElementById('studentSearch').addEventListener('input', searchStudents);
	</script>

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
                    <label style="display:block; font-size: 13px; color:#666; margin-bottom:6px;">聯絡結果</label>
                    <textarea id="contactResult" rows="4" style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px;"></textarea>
                </div>
                <div style="margin-top: 16px;">
                    <label style="display:block; font-size: 13px; color:#666; margin-bottom:6px;">後續追蹤備註（選填）</label>
                    <textarea id="followUpNotes" rows="3" style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px;"></textarea>
                </div>
            </div>
            <div class="modal-footer" style="padding: 16px 24px; border-top: 1px solid #e0e0e0; display:flex; justify-content:flex-end; gap:10px;">
                <button class="btn-cancel" onclick="closeAddContactLog()" style="background:#f5f5f5; border:none; padding:8px 16px; border-radius:6px;">取消</button>
                <button class="btn-confirm" onclick="submitAddContactLog()" style="background:#1890ff; color:white; border:none; padding:8px 16px; border-radius:6px;">儲存</button>
            </div>
        </div>
    </div>

    <script>
        let currentContactStudentId = null;

        function openAddContactLog(studentId, studentName) {
            currentContactStudentId = studentId;
            document.getElementById('contactLogStudentName').textContent = studentName;
            const today = new Date().toISOString().slice(0, 10);
            document.getElementById('contactDate').value = today;
            document.getElementById('contactMethod').value = '電話';
            document.getElementById('contactResult').value = '';
            document.getElementById('followUpNotes').value = '';
            document.getElementById('addContactLogModal').style.display = 'flex';
        }

        function closeAddContactLog() {
            document.getElementById('addContactLogModal').style.display = 'none';
            currentContactStudentId = null;
        }

        async function submitAddContactLog() {
            if (!currentContactStudentId) return;
            const contact_date = document.getElementById('contactDate').value;
            const contact_method = document.getElementById('contactMethod').value;
            const contact_result = document.getElementById('contactResult').value.trim();
            const follow_up_notes = document.getElementById('followUpNotes').value.trim();

            if (!contact_result) {
                alert('請填寫聯絡結果');
                return;
            }

            try {
                const resp = await fetch('api/contact_logs_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        student_id: currentContactStudentId,
                        contact_date,
                        contact_method,
                        contact_result,
                        follow_up_notes
                    })
                });
                const data = await resp.json();
                if (data.success) {
                    alert('已新增聯絡紀錄');
                    closeAddContactLog();
                } else {
                    alert('新增失敗：' + (data.message || '未知錯誤'));
                }
            } catch (e) {
                alert('請求失敗：' + e.message);
            }
        }

        // 點擊模態外關閉
        document.getElementById('addContactLogModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddContactLog();
            }
        });
    </script>
	
    <?php include("share/footer.php"); ?>
    <?php include("share/chat_widget.php"); ?>
    <?php include("share/ai_widget.php"); ?>
</body>

</html>