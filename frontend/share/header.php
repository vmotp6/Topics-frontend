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
    max-width: 2100px;
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
    gap: 15px;
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
    background:  #a964a0 0% ;
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
    font-size: 0.55rem;
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
    font-size: 0.9rem;
    padding: 8px 12px;
    border-radius: 8px;
    transition: all 0.3s ease;
    position: relative;
    white-space: nowrap;
    min-width: fit-content;
    flex-shrink: 0;
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
    z-index: 10000; /* 提高 z-index 確保在其他元素之上 */
    pointer-events: auto; /* 確保可以點擊 */
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
    background:   #a964a0 0% ;
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
      gap: 12px;
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
      gap: 10px;
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
  <a href="<?php echo getCorrectPath('index.php'); ?>" class="logo" style="text-decoration: none; color: inherit;">
    <div class="logo-icon">
      <i class="fas fa-university"></i>
    </div>
    <div class="logo-text">
      <h1 class="logo-title">康寧大學招生平台</h1>
      <p class="logo-subtitle">Kang Ning University Industry-Academia Cooperation Platform</p>
    </div>
  </a>

  <div class="navbar-links">
    <?php 
    // 統一設定角色變數（支援代碼和中文名稱）
    $header_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    $is_header_teacher = ($header_role === '老師' || $header_role === 'TEA' || $header_role === 'STA' || $header_role === '學校行政人員' || $header_role === 'DI' || $header_role === 'STA');
    $is_header_student = ($header_role === '學生' || $header_role === 'STU');
    ?>
    <!-- 共同可見的連結 -->
    <?php if (!($isLoggedIn && $is_header_teacher)): ?>
      <a href="<?php echo getCorrectPath('QA.php'); ?>" class="<?php echo getActiveClass('QA.php'); ?>">招生QA問答</a>
    <?php endif; ?>
        <?php if (!$isLoggedIn): ?>
      <a href="<?php echo getCorrectPath('new_student_basic_info.php'); ?>" class="<?php echo getActiveClass('new_student_basic_info.php'); ?>">新生入學基本資料登錄</a>
    <?php endif; ?>
    <?php if (!($isLoggedIn && $is_header_teacher)): ?>
      <a href="<?php echo getCorrectPath('radio.php'); ?>" class="<?php echo getActiveClass('radio.php'); ?>">招生影片列表</a>
    <?php endif; ?>
    <?php if ($isLoggedIn && $is_header_student): ?>
      <a href="<?php echo getCorrectPath('game.php'); ?>" class="<?php echo getActiveClass('game.php'); ?>" style="white-space: nowrap !important; flex-shrink: 0; word-break: keep-all;">遊戲中心</a>
    <?php endif; ?>
    <a href="<?php echo getCorrectPath('campus_map.php'); ?>" class="<?php echo getActiveClass('campus_map.php'); ?>">校園地圖</a>
    <a href="<?php echo getCorrectPath('bulletin_board.php'); ?>" class="<?php echo getActiveClass('bulletin_board.php'); ?>" style="white-space: nowrap !important; flex-shrink: 0; word-break: keep-all;">招生公告欄</a>
    <?php if (($isLoggedIn && $is_header_teacher)): ?>
      <a href="<?php echo getCorrectPath('radio.php'); ?>" class="<?php echo getActiveClass('radio.php'); ?>">招生影片列表</a>
    <?php endif; ?>
    <?php if (!$isLoggedIn): ?>
      <!-- 僅訪客可見的連結 -->
      <a href="<?php echo getCorrectPath('cooperation_upload.php'); ?>" class="<?php echo getActiveClass('cooperation_upload.php'); ?>">就讀意願登錄</a>
      <a href="<?php echo getCorrectPath('continued_admission.php'); ?>" class="<?php echo getActiveClass('continued_admission.php'); ?>">續招報名</a>
      <a href="<?php echo getCorrectPath('admission.php'); ?>" class="<?php echo getActiveClass('admission.php'); ?>">五專入學說明會</a>
      <a href="<?php echo getCorrectPath('mobile_junior.php'); ?>" class="<?php echo getActiveClass('mobile_junior.php'); ?>">國中招生報名網頁</a>
      <!-- 在校生留言板 - 未登入時也可見 -->
      <a href="<?php echo getCorrectPath('senior_messages.php'); ?>" class="<?php echo getActiveClass('senior_messages.php'); ?>" style="white-space: nowrap !important; flex-shrink: 0; word-break: keep-all;">在校生留言板</a>
    <?php else: ?>
      <!-- 僅登入用戶可見的連結 -->
      <?php if ($isLoggedIn && $is_header_student): ?>
      <a href="<?php echo getCorrectPath('senior_messages.php'); ?>" class="<?php echo getActiveClass('senior_messages.php'); ?>" style="white-space: nowrap !important; flex-shrink: 0; word-break: keep-all;">在校生留言板</a>
    <?php endif; ?>
    <?php if ($isLoggedIn && $is_header_teacher): ?>
      <a href="<?php echo getCorrectPath('game.php'); ?>" class="<?php echo getActiveClass('game.php'); ?>" style="white-space: nowrap !important; flex-shrink: 0; word-break: keep-all;">遊戲中心</a>
    <?php endif; ?>
      <a href="<?php echo getCorrectPath('chat/chat.php'); ?>" class="<?php echo getActiveClass('chat.php'); ?>">私訊聊天室</a>
    <?php endif; ?>

    <?php if ($isLoggedIn): ?>
      <a href="<?php echo getCorrectPath('admission_recommend.php'); ?>" class="<?php echo getActiveClass('admission_recommend.php'); ?>">推薦報名</a>
    <?php endif; ?>
    
    <?php if ($isLoggedIn &&  $is_header_teacher): ?>
      <a href="<?php echo getCorrectPath('records.php'); ?>" class="<?php echo getActiveClass('records.php'); ?>">活動紀錄填報表單</a>
    <?php endif; ?>

    <?php if ($isLoggedIn && $is_header_teacher): ?>
      <a href="<?php echo getCorrectPath('student_management.php'); ?>" class="<?php echo getActiveClass('student_management.php'); ?>">學生管理</a>
      <a href="<?php echo getCorrectPath('student_contact_management.php'); ?>" class="<?php echo getActiveClass('student_contact_management.php'); ?>">學生聯絡管理</a>
    <?php endif; ?>
    
    <?php if ($isLoggedIn && $is_header_teacher): ?>
      <a href="<?php echo getCorrectPath('teacher_file_upload.php'); ?>" class="<?php echo getActiveClass('teacher_file_upload.php'); ?>">檔案上傳</a>
    <?php endif; ?>
    
    <?php if ($isLoggedIn && $is_header_teacher): ?>
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
                   <div class="avatar-btn" onclick="toggleDropdown(event)">
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
                                 $profile_picture = $row['profile_picture'];
                                 
                                 // 優先檢查是否為上傳的頭像（uploads/ 開頭）
                                 if (strpos($profile_picture, 'uploads/') === 0) {
                                    // 上傳的頭像，檢查檔案是否存在
                                    $file_path = dirname(__DIR__) . '/' . $profile_picture;
                                    if (file_exists($file_path)) {
                                        // 檔案存在，使用 getCorrectPath 獲取正確路徑並添加時間戳避免快取
                                        $avatar_src = getCorrectPath($profile_picture) . '?v=' . filemtime($file_path);
                                    } else {
                                        // 檔案不存在，記錄錯誤並使用預設頭像
                                        error_log("頭像檔案不存在: {$file_path}, 資料庫路徑: {$profile_picture}");
                                        $avatar_src = getResourcePath('EIdROxGXsAE_LSs.jpg');
                                    }
                                 } elseif (filter_var($profile_picture, FILTER_VALIDATE_URL)) {
                                    // 完整 URL（如 Google 頭像），直接使用
                                     $avatar_src = $profile_picture;
                                 } else {
                                     // share 目錄的檔案，使用 getResourcePath
                                     $avatar_src = getResourcePath($profile_picture);
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
            <?php 
            $user_role = $_SESSION['role'];
            // 支援角色代碼和中文名稱
            if ($user_role === '老師' || $user_role === 'TEA' || $user_role === 'STA' || $user_role === '學校行政人員' || $user_role === 'DI' || $user_role === 'STA'): ?>
                <a href="<?php echo getCorrectPath('teacher_profile.php'); ?>" class="btn-logout">個人資料</a>
            <?php elseif ($user_role === '學生' || $user_role === 'STU'): ?>
                <a href="<?php echo getCorrectPath('student_profile.php'); ?>" class="btn-logout">個人資料</a>
            <?php else: ?>
                <a href="#" class="btn-logout">個人資料</a>
            <?php endif; ?>

                        <?php 
                        // 檢查是否為允許進入後台的角色（管理員、行政人員、主任）
                        $allowed_backend_roles = ['ADM', 'STA', 'DI', 'AS','TEA' , '管理員', '行政人員', '主任' , '科助'];
                        $can_access_backend = in_array($user_role, $allowed_backend_roles);
                        if ($can_access_backend) {
                          // 與前台相同網域下的 Topics-backend 入口，攜帶目前 session id 供後台可選擇採用
                          $sid = session_id();
                          $backend_url = $base_url . '/Topics-backend/frontend/index.php' . (empty($sid) ? '' : ('?sid=' . urlencode($sid)));
                        ?>
                          <a href="<?php echo htmlspecialchars($backend_url); ?>" class="btn-logout">前往後台</a>
                        <?php } ?>
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
    <?php 
    // 統一設定角色變數（支援代碼和中文名稱）
    $mobile_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    $is_mobile_teacher = ($mobile_role === '老師' || $mobile_role === 'TEA' || $mobile_role === 'STA' || $mobile_role === '學校行政人員' || $mobile_role === 'AA');
    $is_mobile_student = ($mobile_role === '學生' || $mobile_role === 'STU');
    if (!($isLoggedIn && $is_mobile_teacher)): ?>
      <a href="<?php echo getCorrectPath('QA.php'); ?>" class="<?php echo getActiveClass('QA.php'); ?>">招生QA問答</a>
    <?php endif; ?>
    <a href="<?php echo getCorrectPath('campus_map.php'); ?>" class="<?php echo getActiveClass('campus_map.php'); ?>">校園地圖</a>
    <a href="<?php echo getCorrectPath('bulletin_board.php'); ?>" class="<?php echo getActiveClass('bulletin_board.php'); ?>" style="white-space: nowrap !important; flex-shrink: 0; word-break: keep-all;">招生公告欄</a>
    
    <?php if (!$isLoggedIn): ?>
      <!-- 僅訪客可見的連結 -->
      <a href="<?php echo getCorrectPath('cooperation_upload.php'); ?>" class="<?php echo getActiveClass('cooperation_upload.php'); ?>">就讀意願登錄</a>
      <a href="<?php echo getCorrectPath('continued_admission.php'); ?>" class="<?php echo getActiveClass('continued_admission.php'); ?>">續招報名</a>
      <a href="<?php echo getCorrectPath('admission.php'); ?>" class="<?php echo getActiveClass('admission.php'); ?>">五專入學說明會</a>
      <a href="<?php echo getCorrectPath('mobile_junior.php'); ?>" class="<?php echo getActiveClass('mobile_junior.php'); ?>">國中招生報名網頁</a>
      <!-- 在校生留言板 - 未登入時也可見 -->
      <a href="<?php echo getCorrectPath('senior_messages.php'); ?>" class="<?php echo getActiveClass('senior_messages.php'); ?>" style="white-space: nowrap !important; flex-shrink: 0; word-break: keep-all;">在校生留言板</a>
    <?php else: ?>
      <!-- 僅登入用戶可見的連結 -->
      <a href="<?php echo getCorrectPath('chat/chat.php'); ?>" class="<?php echo getActiveClass('chat.php'); ?>">私訊聊天室</a>
    <?php endif; ?>
    
    <?php if ($isLoggedIn && $is_mobile_student): ?>
      <a href="<?php echo getCorrectPath('senior_messages.php'); ?>" class="<?php echo getActiveClass('senior_messages.php'); ?>" style="white-space: nowrap !important; flex-shrink: 0; word-break: keep-all;">在校生留言板</a>
    <?php endif; ?>
    
    <?php if ($isLoggedIn): ?>
      <a href="<?php echo getCorrectPath('admission_recommend.php'); ?>" class="<?php echo getActiveClass('admission_recommend.php'); ?>">推薦報名</a>
    <?php endif; ?>
    
    <?php if ($isLoggedIn && $is_mobile_teacher): ?>
      <a href="<?php echo getCorrectPath('records.php'); ?>" class="<?php echo getActiveClass('records.php'); ?>">活動紀錄填報表單</a>
    <?php endif; ?>

    <?php if ($isLoggedIn && $is_mobile_teacher): ?>
      <a href="<?php echo getCorrectPath('student_management.php'); ?>" class="<?php echo getActiveClass('student_management.php'); ?>">學生管理</a>
      <a href="<?php echo getCorrectPath('student_contact_management.php'); ?>" class="<?php echo getActiveClass('student_contact_management.php'); ?>">學生聯絡管理</a>
    <?php endif; ?>
    
    <?php if ($isLoggedIn && $is_mobile_teacher): ?>
      <a href="<?php echo getCorrectPath('teacher_file_upload.php'); ?>" class="<?php echo getActiveClass('teacher_file_upload.php'); ?>">檔案上傳</a>
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
        <a href="#" id="openForgotPasswordBtn">忘記密碼</a>
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

<!-- 忘記密碼視窗 -->
<div class="modal" id="forgotPasswordModal">
  <div class="modal-content">
    <span class="close-btn" id="closeForgotPasswordBtn">&times;</span>
    <h1>忘記密碼</h1>
    <p style="color: #666; margin-bottom: 20px; font-size: 14px;">請輸入您的帳號或電子郵件，我們將發送重設密碼連結到您的註冊郵箱。</p>
    <form id="forgotPasswordForm">
      <div class="input-field">
        <input type="text" name="username_or_email" id="username_or_email" required>
        <label>帳號或電子郵件</label>
      </div>
      <button type="submit">發送重設密碼郵件</button>
      <p id="forgotPasswordMessage" style="color: red; margin-top: 10px;"></p>
    </form>
    <p class="helper-text" style="margin-top: 15px;">
      <a href="#" id="backToLoginFromForgot">返回登入</a>
    </p>
  </div>
</div>

<!-- Email 驗證碼視窗 -->
<div class="modal" id="verificationModal">
  <div class="modal-content">
    <span class="close-btn" id="closeVerificationBtn">&times;</span>
    <h1>Email 驗證</h1>
    <p style="color: #666; margin-bottom: 10px; font-size: 14px;">我們已發送驗證碼到以下 Email：</p>
    <p style="color: #667eea; font-weight: bold; margin-bottom: 20px; font-size: 16px; text-align: center; padding: 10px; background: #f0f4ff; border-radius: 8px;" id="verification_email_display"></p>
    <p style="color: #666; margin-bottom: 20px; font-size: 14px;">請輸入驗證碼以完成註冊。</p>
    <form id="verificationForm">
      <div class="input-field" style="display: none;">
        <input type="text" name="username" id="verification_username" readonly style="background: #f5f5f5;">
        <label>帳號</label>
      </div>
      <div class="input-field">
        <input type="text" name="code" id="verification_code" required maxlength="4" pattern="[0-9]{4}" placeholder="0000" style="text-align: center; font-size: 24px; letter-spacing: 8px;">
        <label>驗證碼（4位數字）</label>
      </div>
      <button type="submit">驗證</button>
      <p id="verificationMessage" style="color: red; margin-top: 10px;"></p>
    </form>
    <p class="helper-text" style="margin-top: 15px;">
      <a href="#" id="resendCodeBtn">重新發送驗證碼</a>
    </p>
  </div>
</div>

<!-- JavaScript 控制 modal -->
<script>
  // 確保 DOM 完全載入後再獲取元素
  let registerModal, loginModal, forgotPasswordModal, verificationModal;
  
  function initModals() {
    registerModal = document.getElementById("registerModal");
    loginModal = document.getElementById("loginModal");
    forgotPasswordModal = document.getElementById("forgotPasswordModal");
    verificationModal = document.getElementById("verificationModal");
    console.log("Modal 元素初始化:", {
      registerModal: !!registerModal,
      loginModal: !!loginModal,
      forgotPasswordModal: !!forgotPasswordModal,
      verificationModal: !!verificationModal
    });
  }
  
  // 立即初始化（如果 DOM 已載入）
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initModals);
  } else {
    initModals();
  }

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
  
  // 忘記密碼模態視窗控制
  document.getElementById("openForgotPasswordBtn")?.addEventListener("click", (e) => {
    e.preventDefault();
    loginModal.style.display = "none";
    forgotPasswordModal.style.display = "flex";
  });
  document.getElementById("closeForgotPasswordBtn")?.addEventListener("click", () => {
    forgotPasswordModal.style.display = "none";
  });
  document.getElementById("backToLoginFromForgot")?.addEventListener("click", (e) => {
    e.preventDefault();
    forgotPasswordModal.style.display = "none";
    loginModal.style.display = "flex";
  });
  
  // Email 驗證碼視窗控制
  document.getElementById("closeVerificationBtn")?.addEventListener("click", () => {
    if (verificationModal) {
      verificationModal.style.display = "none";
    } else {
      const modal = document.getElementById("verificationModal");
      if (modal) {
        modal.style.display = "none";
      }
    }
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
    if (event.target === forgotPasswordModal) forgotPasswordModal.style.display = "none";
    if (event.target === verificationModal) verificationModal.style.display = "none";
  };

  // 👉 註冊送出（呼叫 Flask /sign）
document.getElementById("registerForm")?.addEventListener("submit", function (e) {
  e.preventDefault();
  
  // 前端密碼驗證
  const password = this.querySelector('input[name="password"]').value;
  const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
  
  if (password.length < 6) {
    document.getElementById("registerMessage").style.color = "red";
    document.getElementById("registerMessage").innerText = "密碼長度至少需 6 碼";
    return;
  }
  
  // 驗證密碼必須包含至少一個英文字母
  if (!/[a-zA-Z]/.test(password)) {
    document.getElementById("registerMessage").style.color = "red";
    document.getElementById("registerMessage").innerText = "密碼必須包含至少一個英文字母";
    return;
  }
  
  // 驗證密碼必須包含至少一個數字
  if (!/[0-9]/.test(password)) {
    document.getElementById("registerMessage").style.color = "red";
    document.getElementById("registerMessage").innerText = "密碼必須包含至少一個數字";
    return;
  }
  
  if (password !== confirmPassword) {
    document.getElementById("registerMessage").style.color = "red";
    document.getElementById("registerMessage").innerText = "兩次密碼輸入不一致";
    return;
  }
  
  const formData = new FormData(this);

              fetch("http://localhost:5000/sign", {
    method: "POST",
    body: formData
  })
  .then(async res => {
    const data = await res.json();
    console.log("註冊回應:", data);
    if (res.ok) {
      document.getElementById("registerMessage").style.color = "green";
      document.getElementById("registerMessage").innerText = data.message;
      
      // 如果需要驗證，顯示驗證碼輸入視窗
      if (data.requires_verification && data.username) {
        // 獲取 email（優先使用回應中的 email，否則從表單獲取）
        const email = data.email || formData.get('email') || this.querySelector('input[name="email"]')?.value;
        console.log("需要驗證，準備顯示驗證碼視窗，帳號:", data.username, "Email:", email);
        setTimeout(() => {
          document.getElementById("registerModal").style.display = "none";
          showVerificationModal(data.username, email);
          this.reset();
          document.getElementById("registerMessage").innerText = "";
        }, 1500);
      } else {
        setTimeout(() => {
          document.getElementById("registerModal").style.display = "none";
          document.getElementById("loginModal").style.display = "flex";  // 打開登入視窗
          this.reset();
          document.getElementById("registerMessage").innerText = "";     // 清除訊息
        }, 1500);
      }

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
    
      // 檢查是否需要 Email 驗證
      if (data.requires_verification && data.username) {
        document.getElementById("loginMessage").style.color = "orange";
        document.getElementById("loginMessage").innerText = data.message;
        // 顯示驗證碼輸入視窗（登入時可能沒有 email，需要從資料庫查詢）
        const email = data.email || "（請查看您的 Email）";
        setTimeout(() => {
          document.getElementById("loginModal").style.display = "none";
          showVerificationModal(data.username, email);
        }, 1000);
        return;
      }
      
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
          // 所有角色統一跳轉到 index.php，內容根據權限顯示
          console.log("準備跳轉到首頁，角色:", data.role);
          window.location.href = "<?php echo getCorrectPath('index.php'); ?>";
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

// 👉 忘記密碼送出
document.getElementById("forgotPasswordForm")?.addEventListener("submit", function (e) {
  e.preventDefault();
  const usernameOrEmail = document.getElementById("username_or_email").value.trim();
  const messageElement = document.getElementById("forgotPasswordMessage");
  
  if (!usernameOrEmail) {
    messageElement.style.color = "red";
    messageElement.innerText = "請輸入帳號或電子郵件";
    return;
  }
  
  messageElement.innerText = "處理中...";
  messageElement.style.color = "#666";
  
  const requestPayload = { username_or_email: usernameOrEmail };
  // #region agent log
  console.log('[DEBUG] 發送忘記密碼請求', {url: 'http://localhost:5000/forgot-password', payload: requestPayload});
  fetch('http://127.0.0.1:7243/ingest/38fc1b08-ca61-4fb8-8158-2bccfce761e5',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'header.php:1484',message:'Sending forgot password request',data:{url:'http://localhost:5000/forgot-password',payload:requestPayload},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'B'})}).catch(()=>{});
  // #endregion
  
  // #region agent log
  fetch('http://127.0.0.1:7243/ingest/38fc1b08-ca61-4fb8-8158-2bccfce761e5',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'header.php:1494',message:'Before fetch call',data:{targetUrl:'http://localhost:5000/forgot-password',method:'POST'},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'C'})}).catch(()=>{});
  // #endregion
  fetch("http://localhost:5000/forgot-password", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(requestPayload)
  })
  .then(async res => {
    let responseText = '';
    try {
      responseText = await res.text();
      // #region agent log
      console.log('[DEBUG] 回應內容', {responseText, length: responseText.length});
      fetch('http://127.0.0.1:7243/ingest/38fc1b08-ca61-4fb8-8158-2bccfce761e5',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'header.php:1508',message:'Response text received',data:{responseText:responseText,length:responseText.length},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'E'})}).catch(()=>{});
      // #endregion
      
      let data;
      try {
        data = JSON.parse(responseText);
      } catch (parseErr) {
        throw parseErr;
      }
      
      if (res.ok) {
        // #region agent log
        fetch('http://127.0.0.1:7242/ingest/df4e40a7-29fb-4588-a7c2-d2869ede0fa9',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'header.php:1503',message:'Success response handled',data:{message:data.message},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'G'})}).catch(()=>{});
        // #endregion
        messageElement.style.color = "green";
        messageElement.innerText = data.message || "重設密碼郵件已發送，請檢查您的郵箱。";
        document.getElementById("forgotPasswordForm").reset();
      } else {
        messageElement.style.color = "red";
        messageElement.innerText = data.message || "發送失敗，請稍後再試。";
      }
    } catch (textErr) {
      // #region agent log
      fetch('http://127.0.0.1:7242/ingest/df4e40a7-29fb-4588-a7c2-d2869ede0fa9',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'header.php:1513',message:'Error reading response text',data:{error:textErr.message,errorName:textErr.name},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'I'})}).catch(()=>{});
      // #endregion
      throw textErr;
    }
  })
  .catch(err => {
    console.error('忘記密碼錯誤:', err);
    messageElement.style.color = "red";
    if (err.message && err.message.includes('Failed to fetch')) {
      messageElement.innerText = "無法連接到伺服器，請確認後端服務是否運行在 port 5000。";
    } else {
      messageElement.innerText = "發送失敗：" + (err.message || "請稍後再試。");
    }
  });
});

// 顯示驗證碼輸入視窗
function showVerificationModal(username, email) {
  console.log("顯示驗證碼視窗，帳號:", username, "Email:", email);
  
  // 等待 DOM 完全載入
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      showVerificationModal(username, email);
    });
    return;
  }
  
  const verificationModal = document.getElementById("verificationModal");
  const verificationUsername = document.getElementById("verification_username");
  const verificationCode = document.getElementById("verification_code");
  const verificationMessage = document.getElementById("verificationMessage");
  const emailDisplay = document.getElementById("verification_email_display");
  
  console.log("驗證碼視窗元素檢查:", {
    verificationModal: !!verificationModal,
    verificationUsername: !!verificationUsername,
    verificationCode: !!verificationCode,
    verificationMessage: !!verificationMessage,
    emailDisplay: !!emailDisplay
  });
  
  if (verificationModal && verificationUsername && verificationCode) {
    verificationUsername.value = username || "";
    verificationCode.value = "";
    if (verificationMessage) {
      verificationMessage.innerText = "";
    }
    // 顯示 Email（優先使用傳入的 email，如果沒有則嘗試從表單獲取）
    if (emailDisplay) {
      if (email) {
        emailDisplay.textContent = email;
        console.log("已設置 Email 顯示:", email);
      } else {
        // 如果沒有傳入 email，嘗試從註冊表單獲取
        const emailInput = document.querySelector('#registerForm input[name="email"]');
        if (emailInput && emailInput.value) {
          emailDisplay.textContent = emailInput.value;
          console.log("從表單獲取 Email:", emailInput.value);
        } else {
          emailDisplay.textContent = "（未提供 Email）";
          console.warn("無法獲取 Email");
        }
      }
    } else {
      console.error("找不到 verification_email_display 元素");
    }
    verificationModal.style.display = "flex";
    console.log("驗證碼視窗已顯示");
  } else {
    console.error("找不到驗證碼視窗元素");
    console.error("嘗試查找的元素:", {
      verificationModal: document.getElementById("verificationModal"),
      verificationUsername: document.getElementById("verification_username"),
      verificationCode: document.getElementById("verification_code")
    });
  }
}

// 驗證碼表單提交
document.getElementById("verificationForm")?.addEventListener("submit", function (e) {
  e.preventDefault();
  const username = document.getElementById("verification_username").value;
  const code = document.getElementById("verification_code").value;
  
  if (!code || code.length !== 4) {
    document.getElementById("verificationMessage").style.color = "red";
    document.getElementById("verificationMessage").innerText = "請輸入4位數驗證碼";
    return;
  }
  
  const formData = new FormData();
  formData.append("action", "verify_code");
  formData.append("username", username);
  formData.append("code", code);
  
  fetch("<?php echo getCorrectPath('api/verify_email.php'); ?>", {
    method: "POST",
    body: formData
  })
  .then(async res => {
    const data = await res.json();
    if (data.success) {
      document.getElementById("verificationMessage").style.color = "green";
      document.getElementById("verificationMessage").innerText = data.message;
      setTimeout(() => {
        const modal = document.getElementById("verificationModal");
        if (modal) {
          modal.style.display = "none";
        }
        if (loginModal) {
          loginModal.style.display = "flex";
        } else {
          const login = document.getElementById("loginModal");
          if (login) {
            login.style.display = "flex";
          }
        }
        document.getElementById("verificationMessage").innerText = "";
      }, 1500);
    } else {
      document.getElementById("verificationMessage").style.color = "red";
      document.getElementById("verificationMessage").innerText = data.message;
    }
  })
  .catch(err => {
    document.getElementById("verificationMessage").style.color = "red";
    document.getElementById("verificationMessage").innerText = "驗證失敗，請稍後再試。";
  });
});

// 重新發送驗證碼
document.getElementById("resendCodeBtn")?.addEventListener("click", function (e) {
  e.preventDefault();
  const username = document.getElementById("verification_username").value;
  
  if (!username) {
    alert("請先完成註冊");
    return;
  }
  
  const formData = new FormData();
  formData.append("action", "resend_code");
  formData.append("username", username);
  
  document.getElementById("verificationMessage").style.color = "#666";
  document.getElementById("verificationMessage").innerText = "正在發送驗證碼...";
  
  fetch("<?php echo getCorrectPath('api/verify_email.php'); ?>", {
    method: "POST",
    body: formData
  })
  .then(async res => {
    const data = await res.json();
    if (data.success) {
      document.getElementById("verificationMessage").style.color = "green";
      document.getElementById("verificationMessage").innerText = data.message;
    } else {
      document.getElementById("verificationMessage").style.color = "red";
      document.getElementById("verificationMessage").innerText = data.message;
    }
  })
  .catch(err => {
    document.getElementById("verificationMessage").style.color = "red";
    document.getElementById("verificationMessage").innerText = "發送失敗，請稍後再試。";
  });
});

// 驗證碼輸入限制為數字
document.getElementById("verification_code")?.addEventListener("input", function (e) {
  this.value = this.value.replace(/[^0-9]/g, "");
});

function toggleDropdown(event) {
  if (event) {
    event.stopPropagation(); // 阻止事件冒泡
  }
  const menu = document.getElementById("dropdownMenu");
  if (menu) {
    const isVisible = menu.style.display === "block";
    menu.style.display = isVisible ? "none" : "block";
    console.log("下拉選單切換:", isVisible ? "關閉" : "開啟");
  } else {
    console.error("找不到下拉選單元素");
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
  
  if (username && (role === '老師' || role === 'TEA' || role === 'STA' || role === '學校行政人員' || role === 'DI' || role === 'STA') && notificationDot) {
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

<!-- 通用助手功能 -->
<style>
  .universal-assistant {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1000;
    font-family: 'Microsoft JhengHei', sans-serif;
    display: flex;
    align-items: flex-end;
    gap: 20px;
  }

  /* 對話氣泡樣式 */
  .assistant-speech-bubble {
    background: #fff;
    border: 3px solid #667eea;
    border-radius: 20px;
    padding: 20px 25px;
    max-width: 350px;
    min-width: 250px;
    min-height: 80px;
    position: relative;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    opacity: 0;
    visibility: hidden;
    transform: translateX(20px);
    transition: all 0.4s ease;
  }

  .assistant-speech-bubble.active {
    opacity: 1;
    visibility: visible;
    transform: translateX(0);
  }

  /* 對話氣泡三角形指向按鈕 */
  .assistant-speech-bubble::after {
    content: '';
    position: absolute;
    right: -20px;
    bottom: 30px;
    width: 0;
    height: 0;
    border-top: 15px solid transparent;
    border-bottom: 15px solid transparent;
    border-left: 20px solid #667eea;
  }

  .assistant-speech-bubble::before {
    content: '';
    position: absolute;
    right: -17px;
    bottom: 32px;
    width: 0;
    height: 0;
    border-top: 13px solid transparent;
    border-bottom: 13px solid transparent;
    border-left: 17px solid #fff;
    z-index: 1;
  }

  .speech-bubble-content {
    font-size: 16px;
    line-height: 1.8;
    color: #333;
    text-align: left;
    position: relative;
    z-index: 2;
  }

  .speech-bubble-content.typing {
    animation: typing 0.5s steps(20, end);
  }

  @keyframes typing {
    from { width: 0; }
    to { width: 100%; }
  }

  /* 對話氣泡關閉按鈕 */
  .speech-bubble-close {
    position: absolute;
    top: 8px;
    right: 8px;
    background: none;
    border: none;
    font-size: 20px;
    color: #999;
    cursor: pointer;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
    z-index: 3;
  }

  .speech-bubble-close:hover {
    background: #f0f0f0;
    color: #667eea;
  }

  .universal-assistant-btn {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
    background-color: white;
  }

  .universal-assistant-btn:hover {
    transform: scale(1.1);
    background-color: white;
    box-shadow: 0 0 0 0;
  }

  .universal-assistant-btn img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
  }

  .universal-assistant-menu {
    position: absolute;
    bottom: 90px;
    right: 0;
    background: white;
    border-radius: 15px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
    min-width: 200px;
    padding: 15px 0;
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: all 0.3s ease;
  }

  .universal-assistant-menu.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
  }

  .universal-menu-item {
    padding: 15px 20px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #2c3e50;
    font-size: 16px;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
  }

  .universal-menu-item:hover {
    background: #f0f4ff;
    color: #667eea;
  }

  .universal-menu-item:first-child {
    border-top-left-radius: 15px;
    border-top-right-radius: 15px;
  }

  .universal-menu-item:last-child {
    border-bottom-left-radius: 15px;
    border-bottom-right-radius: 15px;
  }

  .universal-menu-item i {
    font-size: 18px;
    width: 24px;
    text-align: center;
  }

  .universal-assistant-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    align-items: center;
    justify-content: center;
  }

  .universal-assistant-modal.active {
    display: flex;
  }

  .universal-modal-content {
    background: white;
    border-radius: 20px;
    padding: 30px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 10px 50px rgba(0, 0, 0, 0.3);
  }

  .universal-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
  }

  .universal-modal-header h2 {
    margin: 0;
    color: #667eea;
    font-size: 24px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .universal-modal-close {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: #999;
    transition: color 0.2s;
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
  }

  .universal-modal-close:hover {
    color: #667eea;
    background: #f0f4ff;
  }

  .universal-modal-body {
    color: #333;
    line-height: 1.8;
    font-size: 16px;
  }

  /* 聊天對話框容器 - 顯示在小助手按鈕左邊 */
  .assistant-chat-window {
    position: fixed;
    bottom: 30px;
    right: 190px; /* 在小助手按鈕左邊，按鈕寬度150px + 間距40px */
    width: 800px;
    height: 750px;
    max-height: calc(100vh - 80px);
    background: white;
    border: 3px solid #667eea;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    z-index: 1001;
    display: none;
    flex-direction: column;
    font-family: 'Microsoft JhengHei', sans-serif;
    opacity: 0;
    transform: translateX(20px) scale(0.95);
    transition: all 0.3s ease;
  }

  .assistant-chat-window.active {
    display: flex;
    opacity: 1;
    transform: translateX(0) scale(1);
  }

  /* 聊天窗口標題 */
  .chat-window-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 20px;
    background: linear-gradient(135deg, #667eea 0%);
    color: white;
    border-top-left-radius: 17px;
    border-top-right-radius: 17px;
  }

  .chat-window-title {
    font-size: 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 12px;
  }

  /* 小助手頭像 GIF */
  .chat-window-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid white;
    object-fit: cover;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  }

  .chat-window-close {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
  }

  .chat-window-close:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: rotate(90deg);
  }

  /* 聊天容器 */
  .assistant-chat-container {
    flex: 1;
    overflow-y: auto;
    padding: 25px;
    background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%);
    position: relative;
    min-height: 300px;
  }

  /* 聊天容器滾動條樣式 */
  .assistant-chat-container::-webkit-scrollbar {
    width: 8px;
  }

  .assistant-chat-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
  }

  .assistant-chat-container::-webkit-scrollbar-thumb {
    background: #667eea;
    border-radius: 10px;
  }

  .assistant-chat-container::-webkit-scrollbar-thumb:hover {
    background: #5568d3;
  }

  /* 對話氣泡容器 - 包含頭像和訊息 */
  .assistant-chat-message-wrapper {
    display: flex;
    align-items: flex-end;
    gap: 12px;
    margin-bottom: 20px;
    opacity: 0;
    transform: translateY(10px);
    animation: fadeInUp 0.4s ease-out forwards;
  }

  /* 對話氣泡樣式 - 聊天窗口中使用 */
  .assistant-chat-message {
    margin-bottom: 0;
    padding: 18px 22px;
    border-radius: 20px;
    max-width: 80%;
    position: relative;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    word-wrap: break-word;
    font-size: 16px;
  }

  /* 用戶訊息容器 - 右側對齊 */
  .assistant-chat-message-wrapper.user-wrapper {
    justify-content: flex-end;
  }

  /* 用戶訊息氣泡 - 右側，藍紫色 */
  .assistant-chat-message.user {
    background: linear-gradient(#764ba2 100%);
    color: white;
    margin-left: 0;
    margin-right: 0;
    text-align: left;
    border-bottom-right-radius: 5px;
  }

  /* 用戶訊息氣泡的三角形 */
  .assistant-chat-message.user::after {
    content: '';
    position: absolute;
    right: -10px;
    bottom: 15px;
    width: 0;
    height: 0;
    border-top: 10px solid transparent;
    border-bottom: 10px solid transparent;
    border-left: 12px solid #764ba2;
  }

  /* 助手訊息頭像 - 在對話框外部左側 */
  .assistant-message-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 2px solid #667eea;
    object-fit: cover;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
    background: white;
  }

  /* 助手訊息氣泡 - 左側，白色帶邊框 */
  .assistant-chat-message.assistant {
    background: white;
    color: #333;
    border: 3px solid #667eea;
    margin-right: auto;
    border-bottom-left-radius: 5px;
    position: relative;
    flex: 1;
    min-width: 0;
  }

  /* 助手訊息內容區域 */
  .assistant-message-content {
    width: 100%;
  }

  /* 助手訊息氣泡的三角形 - 指向左側頭像 */
  .assistant-chat-message.assistant::after {
    content: '';
    position: absolute;
    left: -12px;
    bottom: 15px;
    width: 0;
    height: 0;
    border-top: 10px solid transparent;
    border-bottom: 10px solid transparent;
    border-right: 12px solid #667eea;
  }

  /* 助手訊息氣泡的內層三角形（白色） */
  .assistant-chat-message.assistant::before {
    content: '';
    position: absolute;
    left: -9px;
    bottom: 16px;
    width: 0;
    height: 0;
    border-top: 9px solid transparent;
    border-bottom: 9px solid transparent;
    border-right: 10px solid white;
    z-index: 1;
  }

  /* 打字動畫效果 */
  .assistant-chat-message.typing {
    animation: typingEffect 0.3s steps(20, end);
  }

  @keyframes fadeInUp {
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @keyframes typingEffect {
    from {
      width: 0;
    }
    to {
      width: 100%;
    }
  }

  /* 訊息中的文字樣式 */
  .assistant-chat-message strong {
    display: block;
    margin-bottom: 8px;
    font-size: 0.9em;
    opacity: 0.9;
    font-weight: 600;
  }

  .assistant-message-content strong {
    color: #667eea;
    margin-bottom: 6px;
  }

  .assistant-chat-message.user strong {
    color: rgba(255, 255, 255, 0.95);
  }

  /* 打字文字樣式 */
  .assistant-chat-message .typing-text {
    display: inline-block;
    line-height: 1.6;
  }

  /* 訊息文字內容 */
  .assistant-chat-message {
    line-height: 1.6;
    font-size: 15px;
  }

  /* 聊天輸入容器 */
  .assistant-chat-input-container {
    display: flex;
    gap: 12px;
    padding: 18px 20px;
    background: white;
    border-top: 2px solid #e9ecef;
    border-bottom-left-radius: 17px;
    border-bottom-right-radius: 17px;
  }

  .assistant-chat-input {
    flex: 1;
    padding: 14px 18px;
    border: 2px solid #e0e0e0;
    border-radius: 25px;
    font-size: 15px;
    outline: none;
    transition: border-color 0.2s;
    font-family: 'Microsoft JhengHei', sans-serif;
  }

  .assistant-chat-input:focus {
    border-color: #667eea;
  }

  .assistant-chat-send {
    padding: 12px 25px;
    background:  #764ba2 100%;
    color: white;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.2s;
    font-family: 'Microsoft JhengHei', sans-serif;
    white-space: nowrap;
    width:20%;
  }

  .assistant-chat-send:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
  }

  .assistant-chat-send:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
  }

  @media (max-width: 768px) {
    .universal-assistant {
      bottom: 20px;
      right: 20px;
      flex-direction: column;
      align-items: flex-end;
    }

    .universal-assistant-btn {
      width: 60px;
      height: 60px;
    }

    .universal-assistant-menu {
      bottom: 80px;
      min-width: 180px;
    }

    .universal-modal-content {
      padding: 20px;
      width: 95%;
    }

    .assistant-speech-bubble {
      max-width: 280px;
      min-width: 200px;
      padding: 15px 20px;
      margin-bottom: 10px;
    }

    .assistant-speech-bubble::after {
      right: 30px;
      bottom: -20px;
      border-left: 15px solid transparent;
      border-right: 15px solid transparent;
      border-top: 20px solid #667eea;
      border-bottom: none;
    }

    .assistant-speech-bubble::before {
      right: 32px;
      bottom: -17px;
      border-left: 13px solid transparent;
      border-right: 13px solid transparent;
      border-top: 17px solid #fff;
      border-bottom: none;
    }

    .speech-bubble-content {
      font-size: 14px;
    }

    /* 手機版聊天對話框樣式 */
    .assistant-chat-window {
      bottom: 100px;
      right: 10px;
      left: 10px;
      width: auto;
      height: 500px;
      max-height: calc(100vh - 120px);
    }

    .assistant-chat-message {
      max-width: 85%;
      padding: 14px 18px;
      font-size: 14px;
    }

    .assistant-message-avatar {
      width: 40px;
      height: 40px;
    }

    .assistant-chat-message-wrapper {
      gap: 8px;
    }

    .assistant-chat-container {
      padding: 18px;
      min-height: 250px;
    }

    .assistant-chat-input-container {
      padding: 12px 15px;
    }

    .chat-window-avatar {
      width: 32px;
      height: 32px;
    }

    .chat-window-title {
      font-size: 16px;
      gap: 10px;
    }
  }
</style>

<?php 
  // 檢查當前頁面，如果是 game_undertale.php 則不顯示小助手
  $currentPageForAssistant = basename($_SERVER['PHP_SELF']);
  $hideAssistant = ($currentPageForAssistant === 'game_undertale.php');
?>
<?php if (!$hideAssistant): ?>
<div class="universal-assistant">
  <!-- 對話氣泡 -->
  <div class="assistant-speech-bubble" id="assistantSpeechBubble">
    <button class="speech-bubble-close" onclick="closeSpeechBubble()">&times;</button>
    <div class="speech-bubble-content" id="speechBubbleContent">
      你好！我是小助手，點擊我可以了解更多功能！👋
    </div>
  </div>
  
  <!-- 助手按鈕 -->
  <button class="universal-assistant-btn" id="universalAssistantBtn" title="奶油">
    <img src="http://localhost/game/AI01.png" alt="奶油" onerror="this.innerHTML='🤖'; this.style.fontSize='30px';">
  </button>
  
  <!-- 選單 -->
  <div class="universal-assistant-menu" id="universalAssistantMenu">
    <button class="universal-menu-item" onclick="showPageIntroduction()">
      <i class="fas fa-info-circle"></i>
      <span>介紹此網頁</span>
    </button>
    <button class="universal-menu-item" onclick="openUniversalChat()">
      <i class="fas fa-comments"></i>
      <span>聊天</span>
    </button>
    <button class="universal-menu-item" onclick="universalRestMode()">
      <i class="fas fa-moon"></i>
      <span>休息</span>
    </button>
  </div>
</div>
<?php endif; ?>

<?php if (!$hideAssistant): ?>
<!-- 介紹網頁 Modal -->
<div class="universal-assistant-modal" id="introModal">
  <div class="universal-modal-content">
    <div class="universal-modal-header">
      <h2><i class="fas fa-info-circle"></i> 網頁介紹</h2>
      <button class="universal-modal-close" onclick="closeIntroModal()">&times;</button>
    </div>
    <div class="universal-modal-body">
      <div class="universal-chat-container" id="introContent">
        <!-- 對話內容將在這裡動態添加 -->
      </div>
      <div style="text-align: center; margin-top: 20px;">
        <button class="universal-chat-send" onclick="closeIntroModal()" style="width: 120px;">我知道了</button>
      </div>
    </div>
  </div>
</div>

<!-- 聊天對話框 - 直接顯示在頁面上，在小助手按鈕左邊 -->
<div class="assistant-chat-window" id="assistantChatWindow">
  <div class="chat-window-header">
    <div class="chat-window-title">
      <img src="http://localhost/game/AIblink.gif" alt="小助手" class="chat-window-avatar" id="chatWindowAvatar" onerror="this.style.display='none';">
      <span>與小助手聊天</span>
    </div>
    <button class="chat-window-close" onclick="closeChatWindow()">&times;</button>
  </div>
  <div class="assistant-chat-container" id="chatContainer">
    <!-- 初始歡迎訊息將通過 JavaScript 動態添加 -->
  </div>
  <div class="assistant-chat-input-container">
    <input type="text" class="assistant-chat-input" id="chatInput" placeholder="輸入你的問題..." onkeypress="handleChatKeyPress(event)">
    <button class="assistant-chat-send" onclick="sendChatMessage()">發送</button>
  </div>
</div>
<?php endif; ?>

<?php if (!$hideAssistant): ?>
<script>
  // 通用助手功能
  const universalAssistantBtn = document.getElementById('universalAssistantBtn');
  const universalAssistantMenu = document.getElementById('universalAssistantMenu');
  const introModal = document.getElementById('introModal');
  
  // 圖片路徑
  const initialImage = 'http://localhost/game/AI01.png';
  const clickImage = 'http://localhost/game/AI02.gif';
  const blinkImage = 'http://localhost/game/AIblink.gif';
  
  // 獲取圖片元素
  let assistantImage = null;
  if (universalAssistantBtn) {
    assistantImage = universalAssistantBtn.querySelector('img');
  }
  
  // 標記是否已經點擊過
  let hasBeenClicked = false;
  let isShowingBlink = false;

  // 切換到 blink 圖片
  function switchToBlink() {
    if (!assistantImage || isShowingBlink) return;
    isShowingBlink = true;
    
    // 切換到 blink 圖片
    assistantImage.src = blinkImage;
    
    // 當 blink 圖片載入完成後，允許顯示選單
    assistantImage.onload = function() {
      // 現在可以顯示選單了
      if (universalAssistantMenu) {
        universalAssistantMenu.style.pointerEvents = 'auto';
      }
      assistantImage.onload = null; // 移除事件監聽
    };
  }

  // 切換選單顯示
  if (universalAssistantBtn) {
    universalAssistantBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      
      // 如果還沒點擊過，執行圖片切換
      if (!hasBeenClicked && assistantImage) {
        hasBeenClicked = true;
        
        // 先隱藏選單，不允許點擊
        if (universalAssistantMenu) {
          universalAssistantMenu.style.pointerEvents = 'none';
          universalAssistantMenu.classList.remove('active');
        }
        
        // 先顯示 AI02.gif
        // 使用時間戳來強制重新載入，確保 GIF 從頭開始播放
        assistantImage.src = clickImage + '?t=' + Date.now();
        
        // 1.2 秒後切換到 AIblink.gif
        setTimeout(function() {
          switchToBlink();
        }, 1700); // 1.2 秒後切換
      } else if (isShowingBlink) {
        // 已經顯示 blink，可以切換選單
        universalAssistantMenu.classList.toggle('active');
      }
    });
  }

  // 點擊外部關閉選單
  document.addEventListener('click', function(e) {
    if (universalAssistantBtn && universalAssistantMenu) {
      if (!universalAssistantBtn.contains(e.target) && !universalAssistantMenu.contains(e.target)) {
        universalAssistantMenu.classList.remove('active');
      }
    }
  });

  // 對話氣泡相關元素
  const speechBubble = document.getElementById('assistantSpeechBubble');
  const speechBubbleContent = document.getElementById('speechBubbleContent');
  
  // 打字效果定時器
  let typingInterval = null;
  let dialogueSequenceTimeouts = [];
  let autoCloseTimeout = null; // 自動關閉定時器
  
  // 關閉對話氣泡
  function closeSpeechBubble() {
    if (speechBubble) {
      speechBubble.classList.remove('active');
    }
    // 清除所有定時器
    if (typingInterval) {
      clearInterval(typingInterval);
      typingInterval = null;
    }
    if (autoCloseTimeout) {
      clearTimeout(autoCloseTimeout);
      autoCloseTimeout = null;
    }
    dialogueSequenceTimeouts.forEach(timeout => clearTimeout(timeout));
    dialogueSequenceTimeouts = [];
  }
  
  // 顯示對話（打字效果）- 只有在顯示 AIblink.gif 時才能顯示
  function showDialogue(text, shouldAutoClose = true) {
    if (!speechBubble || !speechBubbleContent || !assistantImage) return;
    
    // 檢查當前圖片是否是 AIblink.gif，只有是 blink 狀態才顯示對話
    const currentSrc = assistantImage.src;
    if (!currentSrc.includes('AIblink.gif') && !isShowingBlink) {
      return; // 只有在 blink 狀態才能顯示對話
    }
    
    // 清除之前的定時器
    if (typingInterval) {
      clearInterval(typingInterval);
    }
    
    // 確保圖片保持為 AIblink.gif
    if (!currentSrc.includes('AIblink.gif')) {
      assistantImage.src = blinkImage;
      isShowingBlink = true;
    }
    
    // 顯示對話氣泡
    speechBubble.classList.add('active');
    
    // 清空內容
    speechBubbleContent.textContent = '';
    speechBubbleContent.classList.add('typing');
    
    // 清除之前的自動關閉定時器
    if (autoCloseTimeout) {
      clearTimeout(autoCloseTimeout);
      autoCloseTimeout = null;
    }
    
    // 打字效果
    let index = 0;
    typingInterval = setInterval(() => {
      if (index < text.length) {
        speechBubbleContent.textContent += text[index];
        index++;
      } else {
        clearInterval(typingInterval);
        typingInterval = null;
        speechBubbleContent.classList.remove('typing');
        // 對話顯示時，保持為 AIblink.gif，不切換回其他圖片
        // 如果 shouldAutoClose 為 true，對話完成後3秒自動關閉
        if (shouldAutoClose) {
          autoCloseTimeout = setTimeout(() => {
            closeSpeechBubble();
          }, 3000);
        }
      }
    }, 50);
  }
  
  // 逐條顯示對話
  function showDialogueSequence(messages) {
    // 清除之前的序列定時器
    dialogueSequenceTimeouts.forEach(timeout => clearTimeout(timeout));
    dialogueSequenceTimeouts = [];
    // 清除自動關閉定時器（因為是序列，會在最後一條完成後才關閉）
    if (autoCloseTimeout) {
      clearTimeout(autoCloseTimeout);
      autoCloseTimeout = null;
    }
    
    messages.forEach((message, index) => {
      const isLastMessage = index === messages.length - 1;
      const timeout = setTimeout(() => {
        // 只有最後一條消息才設置自動關閉
        showDialogue(message, isLastMessage);
      }, index * 3500); // 每條消息間隔3.5秒（包含打字時間）
      dialogueSequenceTimeouts.push(timeout);
    });
  }

  // 介紹此網頁 - 對話式介紹（在對話氣泡中顯示）
  function showPageIntroduction() {
    // 檢查是否在 blink 狀態，只有是才顯示對話
    if (!isShowingBlink || !assistantImage || !assistantImage.src.includes('AIblink.gif')) {
      // 如果不是 blink 狀態，先切換到 blink
      if (assistantImage) {
        assistantImage.src = blinkImage;
        isShowingBlink = true;
        // 等待圖片載入後再顯示對話
        assistantImage.onload = function() {
          assistantImage.onload = null;
          showPageIntroductionContent();
        };
        // 如果圖片已經載入，直接調用
        if (assistantImage.complete) {
          showPageIntroductionContent();
        }
      }
      return;
    }
    
    showPageIntroductionContent();
  }
  
  function showPageIntroductionContent() {
    if (universalAssistantMenu) {
      universalAssistantMenu.classList.remove('active');
    }
    
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    const pageName = currentPage.replace('.php', '').replace('.html', '');

    // 根據當前頁面準備對話內容
    const pageTitle = document.title || '康寧大學';
    let messages = [];
    
    switch(pageName) {
      case 'index':
        messages = [
          '你好！歡迎來到' + pageTitle + '！👋',
          '這是康寧大學的主頁面，這裡提供各種豐富的學習資源和功能。',
          '你可以在這裡瀏覽課程內容、查看最新公告、參與互動遊戲等。',
          '讓我們一起探索這個平台，開始你的學習之旅吧！✨'
        ];
        break;
      case 'game_NU':
        messages = [
          '歡迎來到護理科互動遊戲！🎮',
          '這是一個專為護理科學生設計的互動學習遊戲。',
          '你可以與可愛的角色「奶油」進行互動，回答護理相關的問題。',
          '透過遊戲的方式，讓學習護理知識變得更有趣！加油！💪'
        ];
        break;
      case 'game':
        messages = [
          '歡迎來到遊戲中心！🎯',
          '這裡提供了各種有趣的互動遊戲供你選擇。',
          '選擇你喜歡的遊戲開始挑戰吧！',
          '在遊戲中學習，在學習中成長！🚀'
        ];
        break;
      default:
        messages = [
          '歡迎來到' + pageTitle + '！👋',
          '這是 ' + pageTitle + ' 的頁面。',
          '歡迎使用本系統！如有任何問題，隨時都可以詢問我。',
          '祝你在這裡有愉快的使用體驗！😊'
        ];
    }
    
    // 逐條顯示對話（確保在 blink 狀態）
    if (isShowingBlink && assistantImage && assistantImage.src.includes('AIblink.gif')) {
      showDialogueSequence(messages);
    }
  }

  function closeIntroModal() {
    if (introModal) {
      introModal.classList.remove('active');
    }
  }

  // 聊天功能 - 切換聊天對話框顯示/隱藏
  function openUniversalChat() {
    if (universalAssistantMenu) {
      universalAssistantMenu.classList.remove('active');
    }
    
    const chatWindow = document.getElementById('assistantChatWindow');
    if (chatWindow) {
      // 切換顯示/隱藏
      const isActive = chatWindow.classList.contains('active');
      
      if (isActive) {
        // 如果已經顯示，則隱藏
        closeChatWindow();
      } else {
        // 如果未顯示，則顯示
        chatWindow.classList.add('active');
        
        // 更新頭像為說話的 GIF（如果有的話，之後可以根據聊天狀態切換）
        const chatAvatar = document.getElementById('chatWindowAvatar');
        if (chatAvatar && assistantImage) {
          // 可以根據需要切換到說話的 GIF
          // chatAvatar.src = 'http://localhost/game/AIspeak.gif'; // 說話的 GIF
          chatAvatar.src = blinkImage; // 暫時使用 blink 圖片
        }
        
        // 檢查是否已經有歡迎訊息
        const chatContainer = document.getElementById('chatContainer');
        if (chatContainer && chatContainer.children.length === 0) {
          // 添加歡迎訊息（帶打字效果和頭像）
          const messageWrapper = document.createElement('div');
          messageWrapper.className = 'assistant-chat-message-wrapper';
          messageWrapper.innerHTML = `
            <img src="http://localhost/game/AIblink.gif" alt="小助手" class="assistant-message-avatar" onerror="this.style.display='none';">
            <div class="assistant-chat-message assistant">
              <div class="assistant-message-content">
                <strong>助手：</strong>
                <span class="typing-text"></span>
              </div>
            </div>
          `;
          chatContainer.appendChild(messageWrapper);
          
          const welcomeMessage = messageWrapper.querySelector('.assistant-chat-message');
          const welcomeText = '你好！我是你的聊天助手，有什麼可以幫助你的嗎？😊';
          const typingText = messageWrapper.querySelector('.typing-text');
          let index = 0;
          
          const welcomeTypingInterval = setInterval(() => {
            if (index < welcomeText.length) {
              typingText.textContent += welcomeText[index];
              index++;
              // 自動滾動到底部
              chatContainer.scrollTop = chatContainer.scrollHeight;
            } else {
              clearInterval(welcomeTypingInterval);
              // 打字完成後聚焦輸入框
              const chatInput = document.getElementById('chatInput');
              if (chatInput) {
                chatInput.focus();
              }
            }
          }, 30);
        } else {
          // 如果已有訊息，直接聚焦輸入框
          const chatInput = document.getElementById('chatInput');
          if (chatInput) {
            chatInput.focus();
          }
        }
      }
    }
  }

  // 關閉聊天對話框
  function closeChatWindow() {
    const chatWindow = document.getElementById('assistantChatWindow');
    if (chatWindow) {
      chatWindow.classList.remove('active');
    }
  }

  function handleChatKeyPress(event) {
    if (event.key === 'Enter') {
      sendChatMessage();
    }
  }

  // 聊天打字效果
  let chatTypingInterval = null;
  
  function sendChatMessage() {
    const chatInput = document.getElementById('chatInput');
    const message = chatInput ? chatInput.value.trim() : '';
    
    if (!message) return;

    const chatContainer = document.getElementById('chatContainer');
    if (!chatContainer) return;
    
    // 顯示用戶訊息（立即顯示，不需要打字效果）
    const userWrapper = document.createElement('div');
    userWrapper.className = 'assistant-chat-message-wrapper user-wrapper';
    userWrapper.innerHTML = `
      <div class="assistant-chat-message user">
        <strong>你：</strong> ${message}
      </div>
    `;
    chatContainer.appendChild(userWrapper);
    
    if (chatInput) {
      chatInput.value = '';
      chatInput.disabled = true; // 發送時禁用輸入
    }
    
    // 滾動到底部
    setTimeout(() => {
      chatContainer.scrollTop = chatContainer.scrollHeight;
    }, 100);

    // 創建助手訊息容器（顯示「思考中」）
    const messageWrapper = document.createElement('div');
    messageWrapper.className = 'assistant-chat-message-wrapper';
    messageWrapper.innerHTML = `
      <img src="http://localhost/game/AIblink.gif" alt="小助手" class="assistant-message-avatar" onerror="this.style.display='none';">
      <div class="assistant-chat-message assistant">
        <div class="assistant-message-content">
          <strong>助手：</strong>
          <span class="typing-text">思考中...</span>
        </div>
      </div>
    `;
    chatContainer.appendChild(messageWrapper);
    
    // 切換頭像為說話的 GIF（如果有的話）
    const chatAvatar = document.getElementById('chatWindowAvatar');
    const messageAvatar = messageWrapper.querySelector('.assistant-message-avatar');
    if (chatAvatar) {
      // chatAvatar.src = 'http://localhost/game/AIspeak.gif'; // 說話的 GIF
    }
    if (messageAvatar) {
      // messageAvatar.src = 'http://localhost/game/AIspeak.gif'; // 說話的 GIF
    }
    
    const typingText = messageWrapper.querySelector('.typing-text');
    
    // 調用 RAG API - 使用相對於根目錄的路徑
    const ragApiUrl = '/Topics-frontend/backend/api/chat/rag_chat_api.php';
    fetch(ragApiUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        message: message
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success && data.answer) {
        // 清空「思考中...」，準備顯示回答
        typingText.textContent = '';
        
        // 打字效果顯示回應
        const response = data.answer;
        let index = 0;
        
        if (chatTypingInterval) {
          clearInterval(chatTypingInterval);
        }
        
        chatTypingInterval = setInterval(() => {
          if (index < response.length) {
            typingText.textContent += response[index];
            index++;
            // 自動滾動到底部
            chatContainer.scrollTop = chatContainer.scrollHeight;
          } else {
            clearInterval(chatTypingInterval);
            chatTypingInterval = null;
            
            // 回應完成後，切換頭像回 blink（如果需要的話）
            if (messageAvatar) {
              // messageAvatar.src = blinkImage; // 切換回 blink
            }
            if (chatAvatar) {
              // chatAvatar.src = blinkImage; // 切換回 blink
            }
            
            // 啟用輸入框
            if (chatInput) {
              chatInput.disabled = false;
              chatInput.focus();
            }
          }
        }, 30); // 每個字符間隔30毫秒
      } else {
        // 錯誤處理
        typingText.textContent = data.error || '抱歉，處理您的問題時發生錯誤，請稍後再試。';
        
        // 啟用輸入框
        if (chatInput) {
          chatInput.disabled = false;
          chatInput.focus();
        }
      }
      
      // 滾動到底部
      setTimeout(() => {
        chatContainer.scrollTop = chatContainer.scrollHeight;
      }, 100);
    })
    .catch(error => {
      console.error('RAG API 錯誤:', error);
      typingText.textContent = '抱歉，無法連接到服務器，請檢查網路連接後再試。';
      
      // 啟用輸入框
      if (chatInput) {
        chatInput.disabled = false;
        chatInput.focus();
      }
    });
  }

  // 休息模式
  function universalRestMode() {
    if (universalAssistantMenu) {
      universalAssistantMenu.classList.remove('active');
    }
    
    // 創建休息模式覆蓋層
    const restOverlay = document.createElement('div');
    restOverlay.id = 'restOverlay';
    restOverlay.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.8);
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      color: white;
      font-size: 24px;
      font-family: 'Microsoft JhengHei', sans-serif;
    `;
    
    restOverlay.innerHTML = `
      <div style="text-align: center;">
        <i class="fas fa-moon" style="font-size: 60px; margin-bottom: 20px; display: block;"></i>
        <h2 style="margin-bottom: 20px;">休息模式</h2>
        <p style="font-size: 18px; margin-bottom: 30px; opacity: 0.9;">放鬆一下，隨時點擊任意位置返回</p>
        <button onclick="document.getElementById('restOverlay').remove()" 
                style="padding: 12px 30px; background: #667eea; color: white; border: none; 
                       border-radius: 25px; cursor: pointer; font-size: 16px; font-weight: bold;">
          返回
        </button>
      </div>
    `;
    
    document.body.appendChild(restOverlay);
    
    // 點擊任意位置關閉
    restOverlay.addEventListener('click', function(e) {
      if (e.target === restOverlay || e.target.closest('button')) {
        restOverlay.remove();
      }
    });
  }

  // 點擊 Modal 外部關閉
  if (introModal) {
    introModal.addEventListener('click', function(e) {
      if (e.target === introModal) {
        introModal.classList.remove('active');
      }
    });
  }
  
  // 點擊聊天窗口外部關閉（可選，如果需要的話可以取消註解）
  // document.addEventListener('click', function(e) {
  //   const chatWindow = document.getElementById('assistantChatWindow');
  //   const chatBtn = document.getElementById('universalAssistantBtn');
  //   if (chatWindow && chatWindow.classList.contains('active')) {
  //     if (!chatWindow.contains(e.target) && !chatBtn.contains(e.target) && 
  //         !universalAssistantMenu.contains(e.target)) {
  //       closeChatWindow();
  //     }
  //   }
  // });

  // 頁面載入時顯示歡迎消息 - 只有在 blink 狀態才顯示
  document.addEventListener('DOMContentLoaded', function() {
    // 等待 blink 狀態後再顯示歡迎消息
    function checkAndShowWelcome() {
      if (isShowingBlink && assistantImage && assistantImage.src.includes('AIblink.gif')) {
        if (speechBubbleContent) {
          showDialogue('你好！我是小助手，點擊我可以了解更多功能！👋');
        }
      } else {
        // 如果還沒到 blink 狀態，再等待
        setTimeout(checkAndShowWelcome, 200);
      }
    }
    
    // 延遲2秒後開始檢查（等待圖片切換完成）
    setTimeout(checkAndShowWelcome, 2000);
  });
</script>
<?php endif; ?>
