<?php include("share/header.php"); ?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
  <meta charset="UTF-8">
  <title>康寧大學產學合作平台</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
    }

    main {
      max-width: 1200px;
      margin: 120px auto 40px auto; /* 避開 fixed navbar */
      padding: 0 20px;
    }

    /* 輪播 Banner */
    .banner-container {
      position: relative;
      width: 100%;
      height: 350px;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      margin-bottom: 40px;
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

    /* 功能區 */
    .features {
      display: flex;
      justify-content: space-between;
      gap: 20px;
      flex-wrap: wrap;
      margin-bottom: 50px;
    }

    .feature-card {
      flex: 1 1 30%;
      min-width: 260px;
      padding: 25px 20px;
      border-radius: 10px;
      background-color: #fff;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      text-align: center;
      cursor: pointer;
      transition: box-shadow 0.3s ease;
    }

    .feature-card:hover {
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .feature-card h3 {
      margin-bottom: 10px;
      color: #0056b3;
    }

    .feature-card p {
      font-size: 14px;
      color: #666;
    }

    /* 最新消息 */
    .news-section {
      margin-bottom: 50px;
    }

    .news-section h2 {
      font-size: 24px;
      color: #0056b3;
      border-bottom: 3px solid #0056b3;
      padding-bottom: 6px;
      margin-bottom: 20px;
    }

    .news-list {
      list-style: none;
      padding-left: 0;
    }

    .news-list li {
      padding: 12px 0;
      border-bottom: 1px solid #eee;
      font-size: 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .news-list li a {
      text-decoration: none;
      color: #333;
      flex-grow: 1;
    }

    .news-date {
      font-size: 14px;
      color: #999;
      margin-left: 12px;
      white-space: nowrap;
    }

    /* 頁尾 */
    footer {
      background-color: #0056b3;
      color: white;
      text-align: center;
      padding: 15px 20px;
      font-size: 14px;
      margin-top: 60px;
    }

    @media (max-width: 768px) {
      .features {
        flex-direction: column;
      }
    }
  </style>
</head>

<body>

  <main>
    <!-- 輪播 Banner -->
    <div class="banner-container">
      <div class="banner-slide">
        <img src="share/EIdROxGXsAE_LSs.jpg" alt="Banner 1">
      </div>
      <div class="banner-slide">
        <img src="share/EMdrrheUEAAGkC4.jpg" alt="Banner 2">
      </div>
      <div class="banner-slide">
        <img src="share/ESmOf3yU8AA12sp.jpg" alt="Banner 3">
      </div>
      <div class="dots">
        <span class="dot"></span>
        <span class="dot"></span>
        <span class="dot"></span>
      </div>
    </div>

    <!-- 功能介紹 -->
    <section class="features">
      <div class="feature-card" onclick="location.href='internship.php'">
        <h3>實習媒合</h3>
        <p>提供學生與廠商的實習機會，推動校外實務學習。</p>
      </div>
      <div class="feature-card" onclick="location.href='projects.php'">
        <h3>研究合作</h3>
        <p>促進教師與業界合作進行專題研究與開發。</p>
      </div>
      <div class="feature-card" onclick="location.href='qa.php'">
        <h3>常見問答</h3>
        <p>快速解答您對於平台操作與合作方式的疑問。</p>
      </div>
    </section>

    <!-- 最新消息 -->
    <section class="news-section">
      <h2>最新消息</h2>
      <ul class="news-list">
        <li>
          <a href="#">112學年度第2學期實習媒合開始報名</a>
          <span class="news-date">2025-08-01</span>
        </li>
        <li>
          <a href="#">【公告】產學合作成果展示活動即將舉行</a>
          <span class="news-date">2025-07-28</span>
        </li>
        <li>
          <a href="#">平台功能更新：新增線上申請合作案功能</a>
          <span class="news-date">2025-07-20</span>
        </li>
      </ul>
    </section>
  </main>

  <footer>
    © 2025 康寧大學 產學合作平台 | 地址：台北市內湖區康寧路三段75巷123號 | 電話：(02) 2632-1181
  </footer>

  <!-- 輪播圖 JS -->
  <script>
    let slideIndex = 0;
    const slides = document.getElementsByClassName("banner-slide");
    const dots = document.getElementsByClassName("dot");

    function showSlides() {
      for (let i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
        dots[i].classList.remove("active");
      }
      slideIndex++;
      if (slideIndex > slides.length) slideIndex = 1;
      slides[slideIndex - 1].style.display = "block";
      dots[slideIndex - 1].classList.add("active");
      setTimeout(showSlides, 3000);
    }

    for (let i = 0; i < dots.length; i++) {
      dots[i].addEventListener("click", () => {
        slideIndex = i + 1;
        showSlides();
      });
    }

    showSlides();
  </script>
</body>

</html>
