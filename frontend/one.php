<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>康寧大學產學合作平台</title>
  <style>

    /* 輪播 Banner */
    .banner-container {
      position: relative;
      width: 100%;
      max-width: 1200px;
      height: 350px;
      margin: 100px auto 40px;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .banner-slide {
      display: none;
      width: 100%;
      height: 100%;
    }

    .banner-slide img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .prev, .next {
      cursor: pointer;
      position: absolute;
      top: 50%;
      padding: 10px;
      font-size: 24px;
      font-weight: bold;
      color: white;
      background-color: rgba(0,0,0,0.3);
      border-radius: 0 3px 3px 0;
      user-select: none;
      transform: translateY(-50%);
      z-index: 1;
    }

    .prev { left: 10px; }
    .next { right: 10px; }

    .dots {
      position: absolute;
      bottom: 15px;
      width: 100%;
      text-align: center;
    }

    .dot {
      height: 12px;
      width: 12px;
      margin: 0 6px;
      background-color: rgba(255, 255, 255, 0.6);
      border-radius: 50%;
      display: inline-block;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .dot.active {
      background-color: #007bff;
    }

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

<body>

  <?php include("share/header.php"); ?>

  <main>
    <!-- 輪播 Banner -->
    <div class="banner-container">
      <div class="banner-slide"><img src="share/EIdROxGXsAE_LSs.jpg" alt="Banner 1"></div>
      <div class="banner-slide"><img src="share/EMdrrheUEAAGkC4.jpg" alt="Banner 2"></div>
      <div class="banner-slide"><img src="share/ESmOf3yU8AA12sp.jpg" alt="Banner 3"></div>

      <a class="prev">&#10094;</a>
      <a class="next">&#10095;</a>

      <div class="dots">
        <span class="dot"></span>
        <span class="dot"></span>
        <span class="dot"></span>
      </div>
    </div>

    <!-- 快速導覽卡片 -->
    <section class="quick-links">
      <a href="news.php" class="card"><div class="icon">📢</div><div class="title">最新消息</div></a>
      <a href="project_list.php" class="card"><div class="icon">📂</div><div class="title">研究案總覽</div></a>
      <a href="video.php" class="card"><div class="icon">🎬</div><div class="title">成果影片</div></a>
      <a href="partner_map.php" class="card"><div class="icon">🗺️</div><div class="title">合作夥伴地圖</div></a>
      <a href="faq.php" class="card"><div class="icon">❓</div><div class="title">常見問題</div></a>
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
  </script>

</body>
</html>
