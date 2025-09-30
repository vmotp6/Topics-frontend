<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['username']);

// 路徑配置
$config = [
    'base_url' => '/Topics-frontend/frontend/',
    'share_url' => '/Topics-frontend/frontend/share/'
];

// 路徑生成函數
function getCorrectPath($targetFile) {
    global $config;
    return $config['base_url'] . $targetFile;
}

// 資源路徑生成函數
function getResourcePath($resourceFile) {
    global $config;
    return $config['share_url'] . $resourceFile;
}
?>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- CSS -->
<style>
  .navbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 999;
    background: rgba(217, 229, 234, 0.95);
    backdrop-filter: blur(10px);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
    height: 80px;
    padding: 0;
    color: #2c3e50;
    font-family: 'Microsoft JhengHei', sans-serif;
  }

  .container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .logo {
    display: flex;
    align-items: center;
    gap: 15px;
  }

  .logo-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, rgb(168, 186, 221) 100%);
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
  }

  .logo-subtitle {
    font-size: 0.9rem;
    color: #7f8c8d;
    margin: 0;
    font-weight: 500;
  }

  .navbar-links {
    display: flex;
    align-items: center;
    gap: 30px;
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
  }

  .navbar-links a:hover {
    background: #667eea;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
  }

  .navbar-user {
    display: flex;
    align-items: center;
  }

  .auth-buttons {
    display: flex;
    align-items: center;
    gap: 15px;
  }

  .btn-auth {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
  }

  .btn-auth:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    color: white;
  }

  .btn-register {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  }

  .btn-login {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
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
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    transform: translateY(-1px);
  }

  /* 響應式設計 */
  @media (max-width: 1024px) {
    .navbar-links {
      gap: 20px;
    }

    .navbar-links a {
      font-size: 0.9rem;
      padding: 8px 12px;
    }

    .logo-title {
      font-size: 1.2rem;
    }

    .logo-subtitle {
      font-size: 0.8rem;
    }
  }

  @media (max-width: 768px) {
    .navbar-links {
      display: none;
    }

    .logo-title {
      font-size: 1rem;
    }

    .logo-subtitle {
      font-size: 0.7rem;
    }

    .logo-icon {
      width: 40px;
      height: 40px;
      font-size: 20px;
    }

    .auth-buttons {
      gap: 10px;
    }

    .btn-auth {
      padding: 8px 15px;
      font-size: 0.8rem;
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
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
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
    .google-login-btn {
      min-width: 180px;
      padding: 10px 14px;
      font-size: 13px;
    }
    
    .google-login-btn svg {
      width: 16px;
      height: 16px;
      margin-right: 10px;
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
  <div class="logo">
    <div class="logo-icon">
      <i class="fas fa-university"></i>
    </div>
    <div class="logo-text">
      <h1 class="logo-title">康寧大學招生平台</h1>
      <p class="logo-subtitle">Kang Ning University Industry-Academia Cooperation Platform</p>
    </div>
  </div>

  <div class="navbar-links">
    <a href="<?php echo getCorrectPath('index.php'); ?>">首頁</a>
    <a href="<?php echo getCorrectPath('QA.php'); ?>">招生QA問答</a>
    <a href="<?php echo getCorrectPath('chat_settings.php'); ?>">🤖 助手設置</a>
    <?php if ($isLoggedIn): ?>
      <a href="<?php echo getCorrectPath('chat/chat.php'); ?>">私訊聊天室</a>
    <?php endif; ?>
  
  </div>

<?php if ($isLoggedIn): ?>
  <div class="user-dropdown">
                   <div class="avatar-btn" onclick="toggleDropdown()">
             <?php
             // 獲取用戶頭像
             $avatar_src = getResourcePath('EIdROxGXsAE_LSs.jpg'); // 預設頭像
             if (isset($_SESSION['username'])) {
                 try {
                     require_once 'config.php';
                     $conn = getDatabaseConnection();
                     if ($conn) {
                         $stmt = $conn->prepare("SELECT profile_picture FROM user WHERE username = ?");
                         $stmt->bind_param("s", $_SESSION['username']);
                         $stmt->execute();
                         $result = $stmt->get_result();
                         if ($row = $result->fetch_assoc()) {
                             if (!empty($row['profile_picture'])) {
                                 $avatar_src = $row['profile_picture'];
                             }
                         }
                         $conn->close();
                     }
                 } catch (Exception $e) {
                     // 使用預設頭像
                 }
             }
             ?>
             <img src="<?php echo htmlspecialchars($avatar_src); ?>" alt="頭像" class="avatar-img">
        <div class="notification-dot" id="notificationDot"></div>
      </div>
                                     <div class="dropdown-menu" id="dropdownMenu">
         <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
         <?php if (isset($_SESSION['role']) && $_SESSION['role'] === '老師'): ?>
           <a href="<?php echo getCorrectPath('teacher_profile.php'); ?>" class="btn-logout">個人資料</a>
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


<!-- 註冊視窗 -->
<div class="modal" id="registerModal">
  <div class="modal-content">
    <span class="close-btn" id="closeModalBtn">&times;</span>
    <h1>註冊</h1>
    <form id="registerForm">
      <div class="input-field"><input type="text" name="username" required><label>帳號</label></div>
      <div class="input-field"><input type="text" name="name" required><label>姓名</label></div>
      <div class="input-field"><input type="email" name="email" required><label>電子郵件</label></div>
      <div class="input-field">
        <select name="role" required>
          <option value="" disabled selected hidden></option>
          <option value="老師">老師</option>
          <option value="學生">學生</option>
          <option value="廠商">廠商</option>
          <option value="學校行政人員">學校行政人員</option>
        </select>
        <label>身分</label>
      </div>
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

      // 儲存到 sessionStorage
      sessionStorage.setItem("username", data.username);
      sessionStorage.setItem("role", data.role);

      // 將資料儲存進 PHP session
      fetch("<?php echo getCorrectPath('set_session.php'); ?>", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          username: data.username,
          role: data.role
        })
      })
      .then(() => {
        // 根據身分跳轉頁面
        setTimeout(() => {
          if (data.role === "老師") {
            window.location.href = "<?php echo getCorrectPath('teacher.php'); ?>";
          } else if (data.role === "學生") {
            window.location.href = "<?php echo getCorrectPath('student.php'); ?>";
          } else if (data.role === "廠商") {
            window.location.href = "<?php echo getCorrectPath('company.php'); ?>";
          } else if (data.role === "學校行政人員") {
            window.location.href = "<?php echo getCorrectPath('admin.php'); ?>";
          } else {
            window.location.href = "<?php echo getCorrectPath('index.php'); ?>";
          }
        }, 500);
      });
    } else {
      document.getElementById("loginMessage").style.color = "red";
      document.getElementById("loginMessage").innerText = data.message;
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

// 點擊外部收起選單
window.addEventListener("click", function (e) {
  const dropdown = document.getElementById("dropdownMenu");
  const avatar = document.querySelector(".avatar-btn");
  if (dropdown && avatar && !avatar.contains(e.target) && !dropdown.contains(e.target)) {
    dropdown.style.display = "none";
  }
});

// 檢查老師個人資料是否已填寫
function checkTeacherProfile() {
  const username = '<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?>';
  const role = '<?php echo isset($_SESSION['role']) ? $_SESSION['role'] : ''; ?>';
  const notificationDot = document.getElementById('notificationDot');
  
  if (username && role === '老師') {
                fetch(`http://100.79.58.120:5000/teacher/profile/${username}`)
      .then(response => {
        if (response.status === 404) {
          // 尚未填寫個人資料，顯示紅點
          notificationDot.style.display = 'block';
        } else {
          // 已填寫個人資料，隱藏紅點
          notificationDot.style.display = 'none';
        }
      })
      .catch(error => {
        console.log('檢查個人資料時發生錯誤');
      });
  }
}

// 頁面載入時檢查
window.addEventListener('load', checkTeacherProfile);

</script>

