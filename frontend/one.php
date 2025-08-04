<!DOCTYPE html>
<html lang="zh-Hant">

<head>
  <meta charset="UTF-8">
  <title>輪播 Banner 範例</title>
  <?php include("share/header.php"); ?>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: Arial, sans-serif;
    }

    /* 輪播區塊 */
    .banner-container {
      position: relative;
      max-width: 1200px;
      /* 限制最大寬度 */
      height: 400px;
      overflow: hidden;
      margin: 80px auto 0 auto;
      /* 上方留空 + 左右置中 */
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
      text-align: center;
      position: absolute;
      bottom: 15px;
      width: 100%;
    }

    .dot {
      height: 15px;
      width: 15px;
      margin: 0 5px;
      background-color: #bbb;
      border-radius: 50%;
      display: inline-block;
      transition: background-color 0.8s ease;
      cursor: pointer;
    }

    .dot.active {
      background-color: #717171;
    }
  </style>
</head>

<body>

  <!-- 輪播 Banner 放這裡 -->
  <div class="banner-container">
    <div class="banner-slide">
      <img src="share/EIdROxGXsAE_LSs.jpg" alt="圖片1">
    </div>
    <div class="banner-slide">
      <img src="share/EMdrrheUEAAGkC4.jpg" alt="圖片2">
    </div>
    <div class="banner-slide">
      <img src="share/ESmOf3yU8AA12sp.jpg" alt="圖片3">
    </div>

    <div class="dots">
      <span class="dot"></span>
      <span class="dot"></span>
      <span class="dot"></span>
    </div>
  </div>

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

      setTimeout(showSlides, 3000); // 每3秒自動切換
    }

    for (let i = 0; i < dots.length; i++) {
      dots[i].addEventListener("click", () => {
        slideIndex = i;
        showSlides();
      });
    }

    showSlides();
  </script>

</body>

</html>