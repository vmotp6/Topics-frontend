<?php
// 載入 session 配置
require_once dirname(__DIR__) . '/session_config.php';

// 更嚴格的登入狀態檢查
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

// 如果 session 資料不完整，清除登入狀態
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (!isset($_SESSION['username']) || empty($_SESSION['username']) || 
        !isset($_SESSION['role']) || empty($_SESSION['role'])) {
        $_SESSION['logged_in'] = false;
        $isLoggedIn = false;
    }
}

// 路徑配置
if (!isset($config)) {
    $config = [
        'base_url' => '/Topics-frontend/frontend/',
        'share_url' => '/Topics-frontend/frontend/share/'
    ];
}

// 獲取當前域名和端口
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . '://' . $host;

// 路徑生成函數
if (!function_exists('getCorrectPath')) {
    function getCorrectPath($targetFile) {
        global $config;
        return $config['base_url'] . $targetFile;
    }
}

// 資源路徑生成函數
if (!function_exists('getResourcePath')) {
    function getResourcePath($resourceFile) {
        global $config;
        return $config['share_url'] . $resourceFile;
    }
}

// 檢查當前頁面並返回 active 類別
function getActiveClass($targetFile) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    return ($currentPage === $targetFile) ? 'active' : '';
}
?>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- CSS -->
<style>
  /* 通用樣式重置 - 確保所有頁面都有正確的間距 */
  body {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }
  
  /* 為使用 header.php 的頁面提供標準間距 */
  body:not(.custom-spacing) {
    padding-top: 100px;
  }
  
  @media (max-width: 768px) {
    body:not(.custom-spacing) {
      padding-top: 120px;
    }
  }

  @media (max-width: 480px) {
    body:not(.custom-spacing) {
      padding-top: 130px;
    }
  }
  .navbar {
    position: fixed; /* 固定在頁面頂部 */
    top: 0;
    left: 0;
    width: 100%;
    z-index: 999;
    background: rgba(217, 229, 234, 0.95);
    backdrop-filter: blur(10px);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
    min-height: 80px;
    padding: 10px 0;
    color: #2c3e50;
    font-family: 'Microsoft JhengHei', sans-serif;
    box-sizing: border-box;
  }

  .container {
    max-width: 1800px;
    margin: 0 auto;
    padding: 0 20px;
    min-height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-width: 0;
    flex-wrap: wrap;
    box-sizing: border-box;
  }

  .logo {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-shrink: 0;
    margin-right: 20px;
    margin-left: 0;
    transition: all 0.3s ease;
    border-radius: 8px;
    padding: 5px;
  }

  .logo:hover {
    background: rgba(102, 126, 234, 0.1);
    transform: translateY(-1px);
  }

  .navbar-links {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    overflow: visible;
    flex: 1;
    justify-content: center;
    padding: 0 20px;
    max-width: calc(100vw - 300px);
  }

  .navbar-user {
    display: flex;
    align-items: center;
    flex-shrink: 0;
    margin-left: 20px;
  }

  /* 漢堡選單樣式 */
  .hamburger-menu {
    display: none;
    cursor: pointer;
    padding: 10px;
    z-index: 1001;
  }

  .hamburger-icon {
    width: 25px;
    height: 20px;
    position: relative;
    transform: rotate(0deg);
    transition: 0.5s ease-in-out;
  }

  .hamburger-icon span {
    display: block;
    position: absolute;
    height: 3px;
    width: 100%;
    background: #2c3e50;
    border-radius: 2px;
    opacity: 1;
    left: 0;
    transform: rotate(0deg);
    transition: 0.25s ease-in-out;
  }

  .hamburger-icon span:nth-child(1) {
    top: 0px;
  }

  .hamburger-icon span:nth-child(2) {
    top: 8px;
  }

  .hamburger-icon span:nth-child(3) {
    top: 16px;
  }

  /* 漢堡選單動畫 */
  .hamburger-menu.active .hamburger-icon span:nth-child(1) {
    top: 8px;
    transform: rotate(135deg);
  }

  .hamburger-menu.active .hamburger-icon span:nth-child(2) {
    opacity: 0;
    left: -60px;
  }

  .hamburger-menu.active .hamburger-icon span:nth-child(3) {
    top: 8px;
    transform: rotate(-135deg);
  }

  /* 手機版選單 */
  .mobile-menu {
    display: none;
    position: fixed;
    top: 80px;
    left: 0;
    width: 100%;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    padding: 20px;
    max-height: calc(100vh - 80px);
    overflow-y: auto;
  }

  .mobile-menu.active {
    display: block;
  }

  .mobile-menu .mobile-nav-links {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 20px;
  }

  .mobile-menu .mobile-nav-links a {
    color: #2c3e50;
    text-decoration: none;
    font-weight: 600;
    font-size: 1.1rem;
    padding: 15px 20px;
    border-radius: 10px;
    transition: all 0.3s ease;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
  }

  .mobile-menu .mobile-nav-links a:hover {
    background: #667eea;
    color: white;
    transform: translateX(5px);
  }

  .mobile-menu .mobile-auth-buttons {
    display: flex;
    flex-direction: column;
    gap: 15px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
  }

  .mobile-menu .mobile-auth-buttons .btn-auth {
    width: 100%;
    text-align: center;
    padding: 15px 20px;
    font-size: 1.1rem;
  }

  .logo-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    font-weight: bold;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
  }

  .logo-text {
    display: flex;
    flex-direction: column;
  }

  .logo-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
    line-height: 1.2;
    white-space: nowrap;
  }

  .logo-subtitle {
    font-size: 0.9rem;
    color: #7f8c8d;
    margin: 0;
    font-weight: 500;
    white-space: nowrap;
  }

  .navbar-links::-webkit-scrollbar {
    display: none;
  }

  .navbar-links a {
    color: #2c3e50;
    text-decoration: none;
    font-weight: 600;
    font-size: 1rem;
    padding: 10px 15px;
    border-radius: 8px;
    transition: all 0.3s ease;
    position: relative;
    white-space: nowrap;
    min-width: fit-content;
  }

  .navbar-links a:hover {
    background: #667eea;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
  }

  /* 當前頁面高亮樣式 */
  .navbar-links a.active {
    background: #667eea;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    font-weight: 700;
  }

  .mobile-menu .mobile-nav-links a.active {
    background: #667eea;
    color: white;
    transform: translateX(5px);
    font-weight: 700;
  }


  .auth-buttons {
    display: flex;
    align-items: center;
    gap: 15px;
  }

  .btn-auth {
    background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
    border-radius: 25px;
    padding: 10px 20px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    user-select: none;
    transition: all 0.3s ease;
    text-decoration: none;
    color: white;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    white-space: nowrap;
    min-width: fit-content;
  }

  .btn-auth:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    color: white;
  }

  .btn-register {
    background:  #956dbd 100%;
  }

  .btn-login {
    background: #956dbd 100%;
  }

  .btn-login:hover {
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
  }

  /* modal 樣式 */
  .modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    justify-content: center;
    align-items: center;
    z-index: 1000;
  }

  .modal-content {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 40px;
    border-radius: 20px;
    max-width: 600px;
    width: 100%;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    position: relative;
    text-align: center;
    border: 1px solid rgba(102, 126, 234, 0.1);
  }

  .close-btn {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 24px;
    cursor: pointer;
  }

  .input-field {
    position: relative;
    border-bottom: 2px solid rgba(102, 126, 234, 0.3);
    margin: 20px 0;
    text-align: left;
    transition: all 0.3s ease;
    padding-top: 14px; /* 預留標籤空間，避免與輸入文字重疊 */
    box-sizing: border-box;
  }

  .input-field:focus-within {
    border-bottom-color: #667eea;
  }

  .input-field label {
    position: absolute;
    top: 0;
    left: 0;
    transform: none;
    color: #2c3e50;
    font-size: 14px;
    transition: 0.3s ease;
    pointer-events: none;
  }

  .input-field input,
  .input-field select {
    width: 100%;
    height: 40px;
    padding-top: 6px; /* 與上方標籤錯開 */
    background: transparent;
    border: none;
    font-size: 16px;
    outline: none;
    color: #2c3e50;
  }

  .input-field input:focus~label,
  .input-field input:valid~label,
  .input-field select:focus~label,
  .input-field select:valid~label {
    font-size: 12px;
    top: -10px;
    transform: none;
    color: #667eea;
  }

  .helper-text {
    margin-top: 15px;
    text-align: center;
    color: #7f8c8d;
  }

  .helper-text a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s ease;
  }

  .helper-text a:hover {
    color: #5a6fd8;
  }

  .forget {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 25px 0 15px 0;
    color: #000000;
  }

  button {
    background: #4e7d7cad 0%;
    color: white;
    font-weight: 600;
    border: none;
    padding: 12px 20px;
    cursor: pointer;
    border-radius: 25px;
    font-size: 16px;
    margin-bottom: 5px;
    width: 100%;
    transition: all 0.3s ease;
  }

  button:hover {
    background: #4e7d7cad 0%;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
  }
  .user-dropdown {
    position: relative;
  }

  .avatar-btn {
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    padding: 0;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    transition: all 0.3s ease;
  }

  .avatar-btn:hover {
    background: rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
  }

  .avatar-img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 3px solid white;
    background-color: #ffffff;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    object-fit: cover;
  }

  .notification-dot {
    position: absolute;
    top: 0px;
    right: 0px;
    width: 14px;
    height: 14px;
    background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
    border-radius: 50%;
    border: 2px solid white;
    display: none;
    box-shadow: 0 2px 8px rgba(231, 76, 60, 0.3);
  }

  .dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 60px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(102, 126, 234, 0.2);
    border-radius: 12px;
    padding: 15px;
    min-width: 150px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    z-index: 2000;
  }

  .dropdown-menu .username {
    display: block;
    margin-bottom: 12px;
    font-weight: 700;
    color: #2c3e50;
    font-size: 1rem;
    text-align: center;
    padding-bottom: 8px;
    border-bottom: 1px solid rgba(102, 126, 234, 0.2);
  }

  .dropdown-menu a.btn-logout {
    color: #667eea;
    text-decoration: none;
    display: block;
    text-align: center;
    font-weight: 600;
    padding: 8px 12px;
    border-radius: 8px;
    transition: all 0.3s ease;
    margin-bottom: 5px;
  }

  .dropdown-menu a.btn-logout:hover {
    background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
    color: white;
    transform: translateY(-1px);
  }

  /* 響應式設計 */
  @media (max-width: 1200px) {
    .logo {
      margin-right: 15px;
      margin-left: 0;
    }

    .navbar-user {
      margin-left: 15px;
    }

    .navbar-links {
      gap: 15px;
      padding: 0 15px;
      max-width: calc(100vw - 250px);
    }

    .navbar-links a {
      font-size: 0.9rem;
      padding: 8px 12px;
      white-space: nowrap;
    }
  }

  @media (max-width: 1024px) {
    .logo {
      margin-right: 10px;
      margin-left: 0;
    }

    .navbar-user {
      margin-left: 15px;
    }

    .navbar-links {
      gap: 12px;
      padding: 0 10px;
    }

    .navbar-links a {
      font-size: 0.85rem;
      padding: 8px 10px;
      white-space: nowrap;
    }

    .logo-title {
      font-size: 1.1rem;
      white-space: nowrap;
    }

    .logo-subtitle {
      font-size: 0.75rem;
      white-space: nowrap;
    }
  }

  @media (max-width: 768px) {
    .navbar {
      padding: 10px 15px;
    }

    .container {
      padding: 0 15px;
    }

    .logo {
      margin-right: 10px;
      margin-left: 0;
      flex-shrink: 0;
    }

    .logo-text {
      display: flex;
      flex-direction: column;
    }
    
    .logo-title {
      font-size: 1rem;
      font-weight: 600;
    }
    
    .logo-subtitle {
      display: none;
    }

    .logo-icon {
      width: 40px;
      height: 40px;
      font-size: 20px;
      margin-right: 0;
    }

    .navbar-links {
      display: none;
    }

    .hamburger-menu {
      display: block;
    }

    .navbar-user {
      margin-left: 10px;
      flex-shrink: 0;
    }

    .auth-buttons {
      display: none;
    }

    .btn-auth {
      padding: 8px 12px;
      font-size: 0.75rem;
      white-space: nowrap;
    }

    .avatar-btn {
      width: 35px;
      height: 35px;
    }

    .avatar-img {
      width: 28px;
      height: 28px;
    }

    .dropdown-menu {
      right: -10px;
      min-width: 120px;
    }
  }

  /* Google 登入按鈕樣式 - 官方設計風格 */
  .google-login-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    color: #3c4043;
    border: 1px solid #dadce0;
    border-radius: 4px;
    padding: 12px 16px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    font-family: 'Google Sans', Roboto, Arial, sans-serif;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px 0 rgba(60, 64, 67, 0.3), 0 1px 3px 1px rgba(60, 64, 67, 0.15);
    min-width: 200px;
    position: relative;
    overflow: hidden;
  }

  .google-login-btn:hover {
    background: #f8f9fa;
    border-color: #dadce0;
    box-shadow: 0 1px 3px 0 rgba(60, 64, 67, 0.3), 0 4px 8px 3px rgba(60, 64, 67, 0.15);
    transform: translateY(-1px);
  }

  .google-login-btn:active {
    background: #f1f3f4;
    box-shadow: 0 1px 2px 0 rgba(60, 64, 67, 0.3), 0 2px 6px 2px rgba(60, 64, 67, 0.15);
    transform: translateY(0);
  }

  .google-login-btn img {
    width: 18px;
    height: 18px;
    margin-right: 12px;
    border-radius: 2px;
  }

  /* Google 按鈕動畫效果 */
  .google-login-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(149,109,189,0.4), transparent);
    transition: left 0.5s;
  }

  .google-login-btn:hover::before {
    left: 100%;
  }

  /* Google 按鈕聚焦效果 */
  .google-login-btn:focus {
    outline: none;
    border-color: #4285f4;
    box-shadow: 0 1px 3px 0 rgba(60, 64, 67, 0.3), 0 4px 8px 3px rgba(60, 64, 67, 0.15), 0 0 0 3px rgba(66, 133, 244, 0.2);
  }

  /* 響應式設計 */
  @media (max-width: 480px) {
    .navbar {
      padding: 8px 10px;
    }

    .logo {
      margin-left: 0;
    }

    .logo-icon {
      width: 35px;
      height: 35px;
      font-size: 18px;
    }

    .navbar-user {
      margin-left: 5px;
    }

    .auth-buttons {
      gap: 5px;
    }

    .btn-auth {
      padding: 6px 8px;
      font-size: 0.7rem;
    }

    .avatar-btn {
      width: 30px;
      height: 30px;
    }

    .avatar-img {
      width: 24px;
      height: 24px;
    }

    .dropdown-menu {
      right: -15px;
      min-width: 100px;
      padding: 10px;
    }

    .google-login-btn {
      min-width: 160px;
      padding: 8px 12px;
      font-size: 12px;
    }
    
    .google-login-btn svg {
      width: 14px;
      height: 14px;
      margin-right: 8px;
    }
  }

  /* 載入動畫 */
  .google-login-btn.loading {
    pointer-events: none;
    opacity: 0.7;
  }

  .google-login-btn.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 16px;
    height: 16px;
    margin: -8px 0 0 -8px;
    border: 2px solid #dadce0;
    border-top: 2px solid #4285f4;
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

</style>

<!-- 導覽列 -->
<div class="navbar">
<div class="container">
  <!-- Logo 區域 -->
  <a href="<?php 
    // 根據登入狀態和角色決定導向頁面
    if ($isLoggedIn && isset($_SESSION['role'])) {
      switch ($_SESSION['role']) {
        case '學生':
          echo getCorrectPath('student.php');
          break;
        case '老師':
          echo getCorrectPath('teacher.php');
          break;
        case '學校行政人員':
          echo getCorrectPath('admin.php');
          break;
        default:
          echo getCorrectPath('index.php');
          break;
      }
    } else {
      echo getCorrectPath('index.php');
    }
  ?>" class="logo" style="text-decoration: none; color: inherit;">
    <div class="logo-icon">
      <i class="fas fa-university"></i>
    </div>
    <div class="logo-text">
      <h1 class="logo-title">康寧大學招生平台</h1>
      <p class="logo-subtitle">Kang Ning University Industry-Academia Cooperation Platform</p>
    </div>
  </a>

  <div class="navbar-links">
    <!-- 共同可見的連結 -->
    <?php if (!($isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === '老師')): ?>
      <a href="<?php echo getCorrectPath('QA.php'); ?>" class="<?php echo getActiveClass('QA.php'); ?>">招生QA問答</a>
    <?php endif; ?>
    <a href="<?php echo getCorrectPath('campus_map.php'); ?>" class="<?php echo getActiveClass('campus_map.php'); ?>">校園地圖</a>
    
    <?php if (!$isLoggedIn): ?>
      <!-- 僅訪客可見的連結 -->
      <a href="<?php echo getCorrectPath('cooperation_upload.php'); ?>" class="<?php echo getActiveClass('cooperation_upload.php'); ?>">就讀意願登錄</a>
      <a href="<?php echo getCorrectPath('continued_admission.php'); ?>" class="<?php echo getActiveClass('continued_admission.php'); ?>">續招報名</a>
      <a href="<?php echo getCorrectPath('admission.php'); ?>" class="<?php echo getActiveClass('admission.php'); ?>">五專入學說明會</a>
      <a href="<?php echo getCorrectPath('mobile_junior.php'); ?>" class="<?php echo getActiveClass('mobile_junior.php'); ?>">國中招生報名網頁</a>
    <?php else: ?>
      <!-- 僅登入用戶可見的連結 -->
      <?php if ($isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === '學生'): ?>
      <a href="<?php echo getCorrectPath('senior_messages.php'); ?>" class="<?php echo getActiveClass('senior_messages.php'); ?>">學長姐留言板</a>
    <?php endif; ?>
      <a href="<?php echo getCorrectPath('chat/chat.php'); ?>" class="<?php echo getActiveClass('chat.php'); ?>">私訊聊天室</a>
    <?php endif; ?>

    <a href="<?php echo getCorrectPath('admission_recommend.php'); ?>" class="<?php echo getActiveClass('admission_recommend.php'); ?>">推薦報名</a>
    
    <?php if ($isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === '老師'): ?>
      <a href="<?php echo getCorrectPath('records.php'); ?>" class="<?php echo getActiveClass('records.php'); ?>">活動紀錄填報表單</a>
    <?php endif; ?>
    
    <?php if ($isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === '老師'): ?>
      <a href="<?php echo getCorrectPath('mobile_teacher.php'); ?>" class="<?php echo getActiveClass('mobile_teacher.php'); ?>">學校活動通知系統</a>
    <?php endif; ?>
    
    <?php if ($isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === '管理員'): ?>
      <a href="<?php echo getCorrectPath('admin_recommendations.php'); ?>" class="<?php echo getActiveClass('admin_recommendations.php'); ?>">推薦管理</a>
    <?php endif; ?>
  </div>

  <!-- 漢堡選單按鈕 -->
  <div class="hamburger-menu" id="hamburgerMenu">
    <div class="hamburger-icon">
      <span></span>
      <span></span>
      <span></span>
    </div>
  </div>

<?php if ($isLoggedIn): ?>
  <div class="user-dropdown">
                   <div class="avatar-btn" onclick="toggleDropdown()">
             <?php
             // 獲取用戶頭像和姓名
             $avatar_src = getResourcePath('EIdROxGXsAE_LSs.jpg'); // 預設頭像
             $user_display_name = $isLoggedIn ? ($_SESSION['username'] ?? '未知用戶') : '未知用戶';
             if (isset($_SESSION['username'])) {
                 try {
                     // 使用絕對路徑來避免相對路徑問題
                     $configPath = dirname(__DIR__) . '/config.php';
                     if (file_exists($configPath)) {
                         require_once $configPath;
                         $conn = getDatabaseConnection();
                     } else {
                         // 如果找不到config.php，跳過資料庫操作
                         $conn = null;
                     }
                     if ($conn) {
                         $stmt = $conn->prepare("SELECT profile_picture, name FROM user WHERE username = ?");
                         $stmt->bind_param("s", $_SESSION['username']);
                         $stmt->execute();
                         $result = $stmt->get_result();
                         if ($row = $result->fetch_assoc()) {
                             // 獲取姓名，如果沒有則使用 username
                             if (!empty($row['name'])) {
                                 $user_display_name = $row['name'];
                             }
                             
                             if (!empty($row['profile_picture'])) {
                                 // 檢查是否為完整URL或相對路徑
                                 if (filter_var($row['profile_picture'], FILTER_VALIDATE_URL)) {
                                     // 完整 URL（如 Google 頭像），直接使用
                                     $avatar_src = $row['profile_picture'];
                                 } else {
                                     // 相對路徑
                                     if (strpos($row['profile_picture'], 'uploads/') === 0) {
                                         // 上傳的頭像，使用 getCorrectPath 而不是 getResourcePath
                                         $avatar_src = getCorrectPath($row['profile_picture']);
                                     } else {
                                         // share 目錄的檔案，使用 getResourcePath
                                         $avatar_src = getResourcePath($row['profile_picture']);
                                     }
                                 }
                             }
                         }
                         $conn->close();
                     }
                 } catch (Exception $e) {
                     // 使用預設頭像
                     error_log("頭像載入錯誤: " . $e->getMessage());
                 }
             }
             ?>
             <img src="<?php echo htmlspecialchars($avatar_src); ?>" alt="頭像" class="avatar-img" onerror="this.src='<?php echo htmlspecialchars(getResourcePath('EIdROxGXsAE_LSs.jpg')); ?>';">
        <div class="notification-dot" id="notificationDot"></div>
      </div>
                                     <div class="dropdown-menu" id="dropdownMenu">
         <span class="username"><?php echo htmlspecialchars($user_display_name); ?></span>
         <?php if ($isLoggedIn && isset($_SESSION['role'])): ?>
            <?php if ($_SESSION['role'] === '老師'): ?>
                <a href="<?php echo getCorrectPath('teacher_profile.php'); ?>" class="btn-logout">個人資料</a>
            <?php elseif ($_SESSION['role'] === '學生'): ?>
                <a href="<?php echo getCorrectPath('student_profile.php'); ?>" class="btn-logout">個人資料</a>
            <?php else: ?>
                <a href="#" class="btn-logout">個人資料</a>
            <?php endif; ?>
         <?php else: ?>
           <a href="#" class="btn-logout">個人資料</a>
         <?php endif; ?>
         <a href="<?php echo getCorrectPath('logout.php'); ?>" class="btn-logout">登出</a>
       </div>
  </div>
<?php else: ?>
  <div class="auth-buttons">
    <a href="#" id="openModalBtn" class="btn-auth btn-register">
      <i class="fas fa-user-plus"></i>
      註冊
    </a>
    <a href="#" id="openLoginBtn" class="btn-auth btn-login">
      <i class="fas fa-sign-in-alt"></i>
      登入
    </a>
  </div>
<?php endif; ?>

</div>
</div>

<!-- 手機版選單 -->
<div class="mobile-menu" id="mobileMenu">
  <div class="mobile-nav-links">
    <!-- 共同可見的連結 -->
    <?php if (!($isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === '老師')): ?>
      <a href="<?php echo getCorrectPath('QA.php'); ?>" class="<?php echo getActiveClass('QA.php'); ?>">招生QA問答</a>
    <?php endif; ?>
    <a href="<?php echo getCorrectPath('campus_map.php'); ?>" class="<?php echo getActiveClass('campus_map.php'); ?>">校園地圖</a>
    
    <?php if (!$isLoggedIn): ?>
      <!-- 僅訪客可見的連結 -->
      <a href="<?php echo getCorrectPath('cooperation_upload.php'); ?>" class="<?php echo getActiveClass('cooperation_upload.php'); ?>">就讀意願登錄</a>
      <a href="<?php echo getCorrectPath('continued_admission.php'); ?>" class="<?php echo getActiveClass('continued_admission.php'); ?>">續招報名</a>
      <a href="<?php echo getCorrectPath('admission.php'); ?>" class="<?php echo getActiveClass('admission.php'); ?>">五專入學說明會</a>
      <a href="<?php echo getCorrectPath('mobile_junior.php'); ?>" class="<?php echo getActiveClass('mobile_junior.php'); ?>">國中招生報名網頁</a>
    <?php else: ?>
      <!-- 僅登入用戶可見的連結 -->
      <a href="<?php echo getCorrectPath('chat_settings.php'); ?>" class="<?php echo getActiveClass('chat_settings.php'); ?>">🤖 助手設置</a>
      <a href="<?php echo getCorrectPath('chat/chat.php'); ?>" class="<?php echo getActiveClass('chat.php'); ?>">私訊聊天室</a>
    <?php endif; ?>
    
    <?php if ($isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === '學生'): ?>
      <a href="<?php echo getCorrectPath('senior_messages.php'); ?>" class="<?php echo getActiveClass('senior_messages.php'); ?>">在校生留言板</a>
    <?php endif; ?>
    
    <a href="<?php echo getCorrectPath('admission_recommend.php'); ?>" class="<?php echo getActiveClass('admission_recommend.php'); ?>">推薦報名</a>
    
    <?php if ($isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === '老師'): ?>
      <a href="<?php echo getCorrectPath('records.php'); ?>" class="<?php echo getActiveClass('records.php'); ?>">活動紀錄填報表單</a>
    <?php endif; ?>
    
    <?php if ($isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === '管理員'): ?>
      <a href="<?php echo getCorrectPath('admin_recommendations.php'); ?>" class="<?php echo getActiveClass('admin_recommendations.php'); ?>">推薦管理</a>
    <?php endif; ?>
  </div>
  
  <?php if (!$isLoggedIn): ?>
    <div class="mobile-auth-buttons">
      <a href="#" id="mobileOpenModalBtn" class="btn-auth btn-register">
        <i class="fas fa-user-plus"></i>
        註冊
      </a>
      <a href="#" id="mobileOpenLoginBtn" class="btn-auth btn-login">
        <i class="fas fa-sign-in-alt"></i>
        登入
      </a>
    </div>
  <?php endif; ?>
</div>

<!-- 註冊視窗 -->
<div class="modal" id="registerModal">
  <div class="modal-content">
    <span class="close-btn" id="closeModalBtn">&times;</span>
    <h1>註冊</h1>
    <form id="registerForm">
      <div class="input-field"><input type="text" name="username" required><label>帳號</label></div>
      <div class="input-field"><input type="text" name="name" required><label>姓名</label></div>
      <div class="input-field"><input type="email" name="email" required><label>電子郵件</label></div>
      <!-- 移除身分選擇，預設為學生 -->
      <input type="hidden" name="role" value="學生">
      <div class="input-field"><input type="password" name="password" required><label>密碼</label></div>
      <div class="input-field"><input type="password" name="confirm_password" required><label>確認密碼</label></div>
      <button type="submit">註冊</button>
      <p id="registerMessage" style="color: red; margin-top: 10px;"></p>
    </form>
    <p class="helper-text">已經有帳號了嗎？<a href="#" id="switchToLogin">登入</a></p>
  </div>
</div>

<!-- 登入視窗 -->
<div class="modal" id="loginModal">
  <div class="modal-content">
    <span class="close-btn" id="closeLoginBtn">&times;</span>
    <h1>登入</h1>
    <form id="loginForm">
      <div class="input-field"><input type="text" name="username" required><label>帳號</label></div>
      <div class="input-field"><input type="password" name="password" required><label>密碼</label></div>
      <div class="forget">
        <label for="remember" style="display: flex; align-items: center;">
          <input type="checkbox" id="remember" style="margin-right: 5px;">
          <span>記住我</span>
        </label>
        <a href="#">忘記密碼</a>
      </div>
      <button type="submit">登入</button>
      <p id="loginMessage" style="color: red; margin-top: 10px;"></p>
    </form>
    
    <!-- Google 登入按鈕 -->
    <div style="text-align: center; margin: 20px 0;">
      <div style="margin: 15px 0; color: #5f6368; font-size: 14px; position: relative;">
        <span style="background: white; padding: 0 15px;">或</span>
        <div style="position: absolute; top: 50%; left: 0; right: 0; height: 1px; background: #dadce0; z-index: -1;"></div>
      </div>
      <a href="http://localhost:5000/auth/google" class="google-login-btn">
        <svg width="18" height="18" viewBox="0 0 24 24" style="margin-right: 12px;">
          <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
          <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
          <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
          <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
        使用 Google 登入
      </a>
      <div style="margin-top: 10px; color: #666; font-size: 12px; font-style: italic;">
        <i class="fas fa-info-circle" style="margin-right: 5px;"></i>
        教職員(@ukn.edu.tw)為老師，學生(@stu.ukn.edu.tw)及其他帳號為學生
      </div>
    </div>
    
    <p class="helper-text">還沒有帳號？<a href="#" id="switchToRegister">註冊</a></p>
  </div>
</div>
<!-- JavaScript 控制 modal -->
<script>
  const registerModal = document.getElementById("registerModal");
  const loginModal = document.getElementById("loginModal");

  document.getElementById("openModalBtn")?.addEventListener("click", () => {
    registerModal.style.display = "flex";
  });
  document.getElementById("closeModalBtn")?.addEventListener("click", () => {
    registerModal.style.display = "none";
  });
  document.getElementById("openLoginBtn")?.addEventListener("click", (e) => {
    e.preventDefault();
    console.log("登入按鈕被點擊");
    loginModal.style.display = "flex";
  });
  document.getElementById("closeLoginBtn")?.addEventListener("click", () => {
    loginModal.style.display = "none";
  });
  document.getElementById("switchToRegister")?.addEventListener("click", (e) => {
    e.preventDefault();
    loginModal.style.display = "none";
    registerModal.style.display = "flex";
  });
  document.getElementById("switchToLogin")?.addEventListener("click", (e) => {
    e.preventDefault();
    registerModal.style.display = "none";
    loginModal.style.display = "flex";
  });

  // Google 登入按鈕載入效果
  document.querySelector('.google-login-btn')?.addEventListener('click', function(e) {
    // 添加載入狀態
    this.classList.add('loading');
    
    // 3秒後移除載入狀態（防止用戶等待太久）
    setTimeout(() => {
      this.classList.remove('loading');
    }, 3000);
  });
  window.onclick = function (event) {
    if (event.target === registerModal) registerModal.style.display = "none";
    if (event.target === loginModal) loginModal.style.display = "none";
  };

  // 👉 註冊送出（呼叫 Flask /sign）
document.getElementById("registerForm")?.addEventListener("submit", function (e) {
  e.preventDefault();
  const formData = new FormData(this);

              fetch("http://localhost:5000/sign", {
    method: "POST",
    body: formData
  })
  .then(async res => {
    const data = await res.json();
    if (res.ok) {
      document.getElementById("registerMessage").style.color = "green";
      document.getElementById("registerMessage").innerText = data.message;
      setTimeout(() => {
  document.getElementById("registerModal").style.display = "none";
  document.getElementById("loginModal").style.display = "flex";  // 打開登入視窗
  this.reset();
  document.getElementById("registerMessage").innerText = "";     // 清除訊息
}, 1500);

    } else {
      document.getElementById("registerMessage").style.color = "red";
      document.getElementById("registerMessage").innerText = data.message;
    }
  })
  .catch(err => {
    document.getElementById("registerMessage").innerText = "註冊失敗，請稍後再試。";
  });
});

// 👉 登入送出（呼叫 Flask /login）
document.getElementById("loginForm")?.addEventListener("submit", function (e) {
  e.preventDefault();
  console.log("登入表單被提交");
  const formData = new FormData(this);

  fetch("http://localhost:5000/login", {
    method: "POST",
    body: formData
  })
  .then(async res => {
    console.log("登入API回應狀態:", res.status);
    const data = await res.json();
    console.log("登入API回應數據:", data);
    
    if (res.ok) {
      document.getElementById("loginMessage").style.color = "green";
      document.getElementById("loginMessage").innerText = data.message;

      // 將資料儲存進 PHP session
      fetch("<?php echo getCorrectPath('set_session.php'); ?>", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          logged_in: true, // 增加 logged_in 狀態
          username: data.username,
          role: data.role
        })
      })
      .then(response => response.json())
      .then((sessionResult) => {
        console.log("Session 設定結果:", sessionResult);
        if (sessionResult.success) {
          // 在 set_session.php 成功後才進行頁面跳轉
          console.log("準備跳轉，角色:", data.role);
          if (data.role === "老師") {
            window.location.href = "<?php echo getCorrectPath('teacher.php'); ?>";
          } else if (data.role === "學生") {
            window.location.href = "<?php echo getCorrectPath('student.php'); ?>";
          } else if (data.role === "學校行政人員") {
            window.location.href = "<?php echo getCorrectPath('admin.php'); ?>";
          } else {
            // 預設跳轉或重新載入當前頁面
            console.log("未知角色，重新載入頁面");
            window.location.reload();
          }
        } else {
          console.error('Session 設定失敗:', sessionResult);
          document.getElementById("loginMessage").innerText = "登入狀態同步失敗，請重試。";
        }
      })
      .catch(err => {
        console.error('設定 Session 失敗:', err);
        document.getElementById("loginMessage").innerText = "登入狀態同步失敗，請重試。";
      });
    } else {
      // 處理錯誤訊息
      document.getElementById("loginMessage").style.color = "red";
      
      // 特別處理停用帳號的錯誤訊息
      if (res.status === 403) {
        document.getElementById("loginMessage").innerText = data.message;
        document.getElementById("loginMessage").style.color = "#e74c3c";
      } else {
        document.getElementById("loginMessage").innerText = data.message;
      }
    }
  })
  .catch(err => {
    document.getElementById("loginMessage").innerText = "登入失敗，請稍後再試。";
  });
});

function toggleDropdown() {
  const menu = document.getElementById("dropdownMenu");
  if (menu) {
    menu.style.display = menu.style.display === "block" ? "none" : "block";
  }
}

// 漢堡選單功能
function toggleMobileMenu() {
  const hamburgerMenu = document.getElementById('hamburgerMenu');
  const mobileMenu = document.getElementById('mobileMenu');
  
  if (hamburgerMenu && mobileMenu) {
    hamburgerMenu.classList.toggle('active');
    mobileMenu.classList.toggle('active');
  }
}

// 關閉手機版選單
function closeMobileMenu() {
  const hamburgerMenu = document.getElementById('hamburgerMenu');
  const mobileMenu = document.getElementById('mobileMenu');
  
  if (hamburgerMenu && mobileMenu) {
    hamburgerMenu.classList.remove('active');
    mobileMenu.classList.remove('active');
  }
}

// 點擊外部收起選單
window.addEventListener("click", function (e) {
  const dropdown = document.getElementById("dropdownMenu");
  const avatar = document.querySelector(".avatar-btn");
  if (dropdown && avatar && !avatar.contains(e.target) && !dropdown.contains(e.target)) {
    dropdown.style.display = "none";
  }
});

// 漢堡選單事件監聽器
document.addEventListener('DOMContentLoaded', function() {
  const hamburgerMenu = document.getElementById('hamburgerMenu');
  const mobileMenu = document.getElementById('mobileMenu');
  const mobileOpenLoginBtn = document.getElementById('mobileOpenLoginBtn');
  const mobileOpenModalBtn = document.getElementById('mobileOpenModalBtn');
  
  // 漢堡選單點擊事件
  if (hamburgerMenu) {
    hamburgerMenu.addEventListener('click', toggleMobileMenu);
  }
  
  // 手機版選單連結點擊後關閉選單
  if (mobileMenu) {
    const mobileLinks = mobileMenu.querySelectorAll('a');
    mobileLinks.forEach(link => {
      link.addEventListener('click', closeMobileMenu);
    });
  }
  
  // 手機版登入按鈕
  if (mobileOpenLoginBtn) {
    mobileOpenLoginBtn.addEventListener('click', function(e) {
      e.preventDefault();
      closeMobileMenu();
      const loginModal = document.getElementById('loginModal');
      if (loginModal) {
        loginModal.style.display = 'flex';
      }
    });
  }
  
  // 手機版註冊按鈕
  if (mobileOpenModalBtn) {
    mobileOpenModalBtn.addEventListener('click', function(e) {
      e.preventDefault();
      closeMobileMenu();
      const registerModal = document.getElementById('registerModal');
      if (registerModal) {
        registerModal.style.display = 'flex';
      }
    });
  }
  
  // 點擊外部關閉手機版選單
  document.addEventListener('click', function(e) {
    if (mobileMenu && mobileMenu.classList.contains('active')) {
      if (!hamburgerMenu.contains(e.target) && !mobileMenu.contains(e.target)) {
        closeMobileMenu();
      }
    }
  });
});

// 檢查老師個人資料是否已填寫
function checkTeacherProfile() {
  const username = '<?php echo $isLoggedIn ? $_SESSION['username'] : ''; ?>';
  const role = '<?php echo $isLoggedIn ? $_SESSION['role'] : ''; ?>';
  const notificationDot = document.getElementById('notificationDot');
  
  // 暫時禁用此功能，避免 500 錯誤
  // 等後端服務器修復後再啟用
  return;
  
  if (username && role === '老師' && notificationDot) {
    // 使用 AbortController 來設置超時
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 3000); // 3秒超時
    
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
          // 尚未填寫個人資料，顯示紅點
          notificationDot.style.display = 'block';
        } else if (response.ok) {
          // 已填寫個人資料，隱藏紅點
          notificationDot.style.display = 'none';
        }
        // 對於其他狀態碼（包括500），不做任何處理
      })
      .catch(error => {
        clearTimeout(timeoutId);
        // 靜默處理錯誤，不顯示任何錯誤訊息
        // 這樣可以避免在控制台顯示 500 錯誤或網路錯誤
      });
  }
}

// 頁面載入時檢查（暫時禁用）
// window.addEventListener('load', checkTeacherProfile);

</script>
