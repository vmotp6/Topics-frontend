<?php
session_start();
$isLoggedIn = isset($_SESSION['username']);
?>

<!-- 導覽列與 Modal 所需 CSS -->
<style>
  /* 導覽列樣式 */
  .navbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 999;
    background-color: #e0f0ff;
    padding: 20px 0px;
    color: #003366;
    display: flex;
    align-items: center;
    font-family: "Helvetica Neue", Arial, sans-serif;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    flex-wrap: wrap;
  }

  .navbar-links,
  .navbar-user {
    display: flex;
    align-items: center;
    margin-right: 25px;
  }

  .navbar-links {
    justify-content: center;
    flex-grow: 1;
  }

  .navbar-links a {
    color: #0056b3;
    text-decoration: none;
    margin: 0 15px;
    font-weight: bold;
    font-size: 16px;
    transition: color 0.3s;
  }

  .navbar-links a:hover {
    color: #003366;
  }

  /* 登出按鈕樣式 */
  .btn-logout {
    color: white;
    background-color: #007bff;
    padding: 9px 15px;
    margin-left: 10px;
    border-radius: 20px;
    font-size: 18px;
    font-weight: 600;
    transition: background-color 0.3s;
    display: inline-block;
    text-decoration: none;
  }

  .btn-logout:hover {
    background-color: #0056b3;
  }

  /* 登入／註冊按鈕容器 */
  .btn-auth-wrapper {
    background-color: #007bff;
    border-radius: 20px;
    padding: 8px 16px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    user-select: none;

  }

  .btn-auth-wrapper a {
    color: white;
    text-decoration: none;
    font-size: 18px;
    font-weight: 600;
    cursor: pointer;
  }

  .btn-auth-wrapper a:hover {
    color: rgb(178, 182, 182);
  }

  .btn-auth-wrapper .separator {
    color: white;
    font-weight: 600;
    font-size: 18px;
  }

  /* 彈跳視窗共用樣式 */
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
    background-color: white;
    padding: 40px;
    border-radius: 16px;
    max-width: 400px;
    width: 100%;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    position: relative;
    text-align: center;
  }

  .close-btn {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 24px;
    cursor: pointer;
  }

  button {
    background: #2828FF;
    color: white;
    font-weight: 600;
    border: none;
    padding: 12px 20px;
    cursor: pointer;
    border-radius: 8px;
    font-size: 16px;
    margin-bottom: 5px;
    width: 100%;
  }

  button:hover {
    background: #000000;
    color: #ffffff;
    border-color: #000000;
  }

  .input-field {
    position: relative;
    border-bottom: 2px solid #ccc;
    margin: 20px 0;
    text-align: left;
  }

  .input-field label {
    position: absolute;
    top: 50%;
    left: 0;
    transform: translateY(-50%);
    color: #000000;
    font-size: 16px;
    transition: 0.15s ease;
    pointer-events: none;
  }

  .input-field input,
  .input-field select {
    width: 100%;
    height: 40px;
    background: transparent;
    border: none;
    font-size: 16px;
    outline: none;
    color: #000000;
  }

  .input-field input:focus~label,
  .input-field input:valid~label,
  .input-field select:focus~label,
  .input-field select:valid~label {
    font-size: 0.8rem;
    top: 10px;
    transform: translateY(-120%);
  }

  .helper-text {
    margin-top: 15px;
    text-align: center;
  }

  .modal a:hover {
    text-decoration: underline;
  }

  .forget {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 25px 0 15px 0;
    color: #000000;
  }
</style>

<!-- 導覽列 -->
<div class="navbar">
  <div class="navbar-links">
    <a href="index.php">產學合作1</a>
    <a href="teach.php">產學合作2</a>
    <?php if ($isLoggedIn): ?>
      <a href="life.php">產學合作3</a>
    <?php endif; ?>
    <a href="QA.php">認識產學合作</a>
    <a href="about.php">認識平台</a>
  </div>
  <div class="navbar-user">
    <?php if ($isLoggedIn): ?>
      你好, <?php echo htmlspecialchars($_SESSION['username']); ?> |
      <a href="logout.php" class="btn-logout">登出</a>
    <?php else: ?>
      <div class="btn-auth-wrapper">
        <a href="#" id="openModalBtn">註冊</a>
        <span class="separator">/</span>
        <a href="#" id="openLoginBtn">登入</a>
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
    <p class="helper-text">還沒有帳號？<a href="#" id="switchToRegister">註冊</a></p>
  </div>
</div>

<!-- JavaScript 控制 modal 開關 -->
<script>
  const registerModal = document.getElementById("registerModal");
  const loginModal = document.getElementById("loginModal");

  // 開啟註冊
  document.getElementById("openModalBtn")?.addEventListener("click", () => {
    registerModal.style.display = "flex";
  });

  // 關閉註冊
  document.getElementById("closeModalBtn")?.addEventListener("click", () => {
    registerModal.style.display = "none";
  });

  // 開啟登入
  document.getElementById("openLoginBtn")?.addEventListener("click", () => {
    loginModal.style.display = "flex";
  });

  // 關閉登入
  document.getElementById("closeLoginBtn")?.addEventListener("click", () => {
    loginModal.style.display = "none";
  });

  // 切換：登入 → 註冊
  document.getElementById("switchToRegister")?.addEventListener("click", (e) => {
    e.preventDefault();
    loginModal.style.display = "none";
    registerModal.style.display = "flex";
  });

  // 切換：註冊 → 登入
  document.getElementById("switchToLogin")?.addEventListener("click", (e) => {
    e.preventDefault();
    registerModal.style.display = "none";
    loginModal.style.display = "flex";
  });

  // 點背景關閉 modal
  window.onclick = function(event) {
    if (event.target === registerModal) registerModal.style.display = "none";
    if (event.target === loginModal) loginModal.style.display = "none";
  };

  // 密碼確認檢查
  function checkPasswordMatch() {
    const password = document.querySelector('input[name="password"]').value;
    const confirm = document.querySelector('input[name="confirm_password"]').value;
    if (password !== confirm) {
      alert("密碼與確認密碼不一致！");
      return false;
    }
    return true;
  }

  // 處理註冊表單提交
  document.getElementById("registerForm")?.addEventListener("submit", async (e) => {
    e.preventDefault(); // 阻止表單預設提交

    const registerMessage = document.getElementById("registerMessage");
    registerMessage.textContent = ""; // 清除之前的訊息

    const password = document.querySelector('#registerForm input[name="password"]').value;
    const confirmPassword = document.querySelector('#registerForm input[name="confirm_password"]').value;

    if (password !== confirmPassword) {
      alert("密碼與確認密碼不一致！"); // 仍然使用 alert 或直接顯示在頁面上
      return;
    }

    const formData = new FormData(e.target); // 獲取表單數據

    try {
      const response = await fetch("http://localhost:5000/sign", {
        method: "POST",
        body: formData, // 直接發送 FormData
      });

      const data = await response.json(); // 解析 JSON 響應

      if (response.ok) { // HTTP 狀態碼 200-299 之間表示成功
        alert(data.message); // 顯示成功訊息
        registerModal.style.display = "none"; // 關閉註冊視窗
        // 可以在這裡重定向或更新頁面狀態
      } else {
        // 處理錯誤訊息
        registerMessage.textContent = data.message;
        registerMessage.style.color = "red";
      }
    } catch (error) {
      console.error("註冊請求失敗:", error);
      registerMessage.textContent = "網路錯誤，請稍後再試。";
      registerMessage.style.color = "red";
    }
  });

  // 處理登入表單提交
  document.getElementById("loginForm")?.addEventListener("submit", async (e) => {
    e.preventDefault(); // 阻止表單預設提交

    const loginMessage = document.getElementById("loginMessage");
    loginMessage.textContent = ""; // 清除之前的訊息

    const formData = new FormData(e.target);

    try {
      const response = await fetch("http://localhost:5000/login", {
        method: "POST",
        body: formData,
      });

      const data = await response.json();

      if (response.ok) {
        alert(data.message); // 顯示成功訊息
        loginModal.style.display = "none"; // 關閉登入視窗
        // 這裡可以重定向到其他頁面，例如：
        // window.location.href = "index.php"; // 重載頁面以更新登入狀態
        // 或者使用 AJAX 更新導覽列
        // 例如：如果登入成功，將導覽列中的登入/註冊按鈕替換為使用者名稱和登出按鈕
        // 這會涉及到更複雜的 DOM 操作或重新載入頁面
        window.location.reload(); // 最簡單的方式是重新載入頁面以反映登入狀態
      } else {
        loginMessage.textContent = data.message;
        loginMessage.style.color = "red";
      }
    } catch (error) {
      console.error("登入請求失敗:", error);
      loginMessage.textContent = "網路錯誤，請稍後再試。";
      loginMessage.style.color = "red";
    }
  });
</script>