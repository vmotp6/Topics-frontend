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
  max-width: 1200px;   /* ✅ 最大寬度 */
  height: 350px;
  margin: 100px auto 40px auto;  /* ✅ 上 100px（留給 header），左右自動置中 */
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

    .banner-container {
  position: relative;
  width: 100%;
  max-width: 1200px;
  height: 350px;
  margin: 100px auto 40px auto;
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
  width: auto;
  padding: 10px;
  color: white;
  font-weight: bold;
  font-size: 24px;
  border-radius: 0 3px 3px 0;
  background-color: rgba(0,0,0,0.3);
  user-select: none;
  transform: translateY(-50%);
  z-index: 1;
}
.prev {
  left: 10px;
}
.next {
  right: 10px;
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

  </style>
</head>

<body>

  <!-- 導覽列 -->
  <?php include("share/header.php"); ?>

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

<!-- 左右箭頭 -->
<a class="prev">&#10094;</a>
<a class="next">&#10095;</a>

<!-- 底部小圓點 -->
<div class="dots">
  <span class="dot"></span>
  <span class="dot"></span>
  <span class="dot"></span>
</div>
</div>

  </main>

  <!-- 輪播圖 JS -->
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
