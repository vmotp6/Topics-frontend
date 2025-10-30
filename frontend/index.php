<?php
// 載入 session 配置
require_once 'session_config.php';

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
            $redirect_url = 'admin_admission.php';
        } elseif ($_GET['role'] === '老師') {
            $redirect_url = 'teacher.php';
        } elseif ($_GET['role'] === '學生') {
            $redirect_url = 'senior_messages.php';
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
    <div class="features-grid">
			<div class="feature-card">
				<div class="feature-icon">🤝</div>
				<h3 class="feature-title">招生QA問答</h3>
				<p class="feature-description">提問有關招生、學費、科系、申請流程等資訊。</p>
				<a href="QA.php" class="feature-link">招生QA問答</a>
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
				<div class="feature-icon">👑</div>
				<h3 class="feature-title">推薦報名</h3>
				<p class="feature-description">查看推薦報名情況和進度。</p>
				<a href="admission_recommend.php" class="feature-link">推薦報名</a>
			</div>
			
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
</main>
<?php include("share/footer.php"); ?>
<?php include("share/ai_widget.php"); ?>

</body>
</html>