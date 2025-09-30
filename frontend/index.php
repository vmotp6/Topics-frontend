<?php
// 在文件最開始啟動 session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
        } elseif ($_GET['role'] === '老師') {
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
  <title>康寧大學招生平台</title>
  
  <!-- 輪播圖片樣式 -->
  <style>
    /* 輪播圖片容器 */
    .carousel-container {
      position: relative;
      width: 100%;
      height: 500px;
      overflow: hidden;
      margin-top: 80px; /* 為固定導覽列留出空間 */
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

    /* 輪播圖片內容 */
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

    /* 輪播控制按鈕 */
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

    /* 左右箭頭 */
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

    /* 輪播圖片遮罩 */
    .slide-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, rgba(0, 0, 0, 0.4) 0%, rgba(0, 0, 0, 0.2) 100%);
      z-index: 1;
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

<?php include("share/header.php"); ?>

<body>
  <!-- 輪播圖片區域 -->
  <div class="carousel-container">
    <!-- 輪播圖片1 -->
    <div class="carousel-slide active" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2071&q=80');">
      <div class="slide-overlay"></div>
      <div class="slide-content">
        <h2>歡迎來到康寧大學招生平台</h2>
        <p>連結學術研究與產業發展，創造雙贏的產學合作機會</p>
        <a href="QA.php" class="slide-btn">了解更多</a>
      </div>
    </div>

    <!-- 輪播圖片2 -->
    <div class="carousel-slide" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1552664730-d307ca884978?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');">
      <div class="slide-overlay"></div>
      <div class="slide-content">
        <h2>AI智能產學合作</h2>
        <p>運用最新的人工智慧技術，為您的產學合作項目提供智能建議與分析</p>
        <a href="AI.php" class="slide-btn">體驗AI服務</a>
      </div>
    </div>

    <!-- 輪播圖片3 -->
    <div class="carousel-slide" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');">
      <div class="slide-overlay"></div>
      <div class="slide-content">
        <h2>專業團隊支持</h2>
        <p>我們擁有豐富的產學合作經驗，為您提供全方位的專業服務與支持</p>
        <a href="chat_settings.php" class="slide-btn">聯繫我們</a>
      </div>
    </div>

    <!-- 輪播圖片4 -->
    <div class="carousel-slide" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1559136555-9303baea8ebd?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');">
      <div class="slide-overlay"></div>
      <div class="slide-content">
        <h2>創新合作模式</h2>
        <p>探索創新的產學合作模式，促進學術研究與產業應用的深度融合</p>
        <a href="QA.php" class="slide-btn">探索合作</a>
      </div>
    </div>

    <!-- 控制按鈕 -->
    <button class="carousel-arrow prev" onclick="changeSlide(-1)">❮</button>
    <button class="carousel-arrow next" onclick="changeSlide(1)">❯</button>

    <!-- 指示點 -->
    <div class="carousel-controls">
      <div class="carousel-dot active" onclick="currentSlide(1)"></div>
      <div class="carousel-dot" onclick="currentSlide(2)"></div>
      <div class="carousel-dot" onclick="currentSlide(3)"></div>
      <div class="carousel-dot" onclick="currentSlide(4)"></div>
    </div>
  </div>

  <!-- 輪播圖片JavaScript -->
  <script>
    let currentSlideIndex = 0;
    const slides = document.querySelectorAll('.carousel-slide');
    const dots = document.querySelectorAll('.carousel-dot');
    const totalSlides = slides.length;

    // 顯示指定索引的輪播圖片
    function showSlide(index) {
      // 隱藏所有輪播圖片
      slides.forEach(slide => slide.classList.remove('active'));
      dots.forEach(dot => dot.classList.remove('active'));

      // 顯示當前輪播圖片
      slides[index].classList.add('active');
      dots[index].classList.add('active');
    }

    // 切換到下一張或上一張輪播圖片
    function changeSlide(direction) {
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
      currentSlideIndex = index - 1;
      showSlide(currentSlideIndex);
    }

    // 自動輪播
    function autoSlide() {
      changeSlide(1);
    }

    // 每5秒自動切換輪播圖片
    setInterval(autoSlide, 5000);

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
  </script>

<?php include("share/footer.php"); ?>
<?php include("share/ai_widget.php"); ?>

</body>
</html>
