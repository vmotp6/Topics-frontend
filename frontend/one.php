<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>康寧大學產學合作平台</title>
  
  <style>

    /* 卡片快速導覽 */
    .quick-links {
      max-width: 1200px;
      margin: 0 auto 50px;
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      justify-content: center;
    }

    .card {
      width: 200px;
      height: 120px;
      background-color: #f3faff;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      text-align: center;
      padding: 20px 10px;
      text-decoration: none;
      color: #004080;
      transition: transform 0.2s ease;
    }

    .card:hover {
      transform: translateY(-5px);
      background-color: #e0f0ff;
    }

    /* 申請表卡片特殊樣式 */
    #applyCard {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      border: 2px solid #667eea;
    }

    #applyCard:hover {
      background: linear-gradient(135deg, #5a6fd8, #6a4190);
      transform: translateY(-5px) scale(1.02);
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }

    #applyCard .icon {
      color: white;
    }

    #applyCard .title {
      color: white;
    }

    .card .icon {
      font-size: 38px;
      margin-bottom: 18px;
    }

    .card .title {
      font-size: 22px;
      font-weight: bold;
    }

  </style>
</head>
<?php include("share/header.php"); ?>
<body>

  

  <main>


    <!-- 快速導覽卡片 -->
    <section class="quick-links">
      <a href="news.php" class="card"><div class="icon">📢</div><div class="title">最新消息</div></a>
      <a href="project_list.php" class="card"><div class="icon">📂</div><div class="title">研究案總覽</div></a>
      <a href="video.php" class="card"><div class="icon">🎬</div><div class="title">成果影片</div></a>
      <a href="partner_map.php" class="card"><div class="icon">🗺️</div><div class="title">合作夥伴地圖</div></a>
      <a href="faq.php" class="card"><div class="icon">❓</div><div class="title">常見問題</div></a>
      <a href="cooperation_upload.php" class="card" id="applyCard"><div class="icon">📝</div><div class="title">申請表填寫</div></a>
    </section>
  </main>

	<!-- 引入通用聊天室組件 -->
	<?php include("share/chat_widget.php"); ?>
	<?php include("share/ai_widget.php"); ?>

<?php include("share/footer.php"); ?>

  <!-- 輪播圖 JavaScript -->
  <script>
    let slideIndex = 0;
    const slides = document.getElementsByClassName("banner-slide");
    const dots = document.getElementsByClassName("dot");
    let timer;

    function showSlides(index = null) {
      if (index !== null) slideIndex = index;
      for (let i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
        dots[i].classList.remove("active");
      }
      slideIndex = (slideIndex + slides.length) % slides.length;
      slides[slideIndex].style.display = "block";
      dots[slideIndex].classList.add("active");
      timer = setTimeout(() => showSlides(slideIndex + 1), 3000);
    }

    function plusSlides(n) {
      clearTimeout(timer);
      showSlides(slideIndex + n);
    }

    for (let i = 0; i < dots.length; i++) {
      dots[i].addEventListener("click", () => {
        clearTimeout(timer);
        showSlides(i);
      });
    }

    document.querySelector(".prev").addEventListener("click", () => plusSlides(-1));
    document.querySelector(".next").addEventListener("click", () => plusSlides(1));

    showSlides();

    // 處理申請表卡片的點擊事件
    document.getElementById('applyCard').addEventListener('click', function(e) {
      e.preventDefault();
      
      // 檢查是否已登入
      const isLoggedIn = <?php echo isset($_SESSION['username']) ? 'true' : 'false'; ?>;
      const userRole = '<?php echo isset($_SESSION['role']) ? $_SESSION['role'] : ''; ?>';
      
      if (!isLoggedIn) {
        alert('請先登入才能填寫申請表！');
        // 觸發登入視窗
        document.getElementById('openLoginBtn').click();
        return;
      }
      
      if (userRole !== '老師') {
        alert('只有老師身分才能填寫產學合作申請表！');
        return;
      }
      
      // 如果已登入且為老師身分，跳轉到申請表頁面
      window.location.href = 'cooperation_upload.php';
    });
  </script>

</body>
</html>
