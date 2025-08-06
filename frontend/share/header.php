<?php
session_start();
$isLoggedIn = isset($_SESSION['username']);
?>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- CSS -->
<style>
  .navbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 999;
    background-color: #e0f0ff;
    padding: 20px 0;
    color: #003366;
    font-family: "Helvetica Neue", Arial, sans-serif;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .logo {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .logo img {
    height: 40px;
    width: auto;
  }

  .navbar-links {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-grow: 1;
  }

  .navbar-links a {
    color: #0056b3;
    text-decoration: none;
    margin: 0 12px;
    font-weight: bold;
    font-size: 16px;
    transition: color 0.3s;
  }

  .navbar-links a:hover {
    color: #003366;
  }

  .navbar-user {
    display: flex;
    align-items: center;
  }

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
    background-color: white;
    padding: 40px;
    border-radius: 16px;
    max-width: 600px;
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

  .forget {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 25px 0 15px 0;
    color: #000000;
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
  }
  .user-dropdown {
  position: relative;
}

.avatar-btn {
  cursor: pointer;
  display: flex;
  align-items: center;
}

.avatar-img {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 2px solid white;
  background-color: #ffffff;
}

.dropdown-menu {
  display: none;
  position: absolute;
  right: 0;
  top: 48px;
  background-color: white;
  border: 1px solid #ccc;
  border-radius: 8px;
  padding: 10px;
  min-width: 120px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
  z-index: 2000;
}

.dropdown-menu .username {
  display: block;
  margin-bottom: 8px;
  font-weight: bold;
  color: #003366;
}

.dropdown-menu a.btn-logout {
  color: #007bff;
  text-decoration: none;
  display: block;
  text-align: center;
  font-weight: 600;
}

.dropdown-menu a.btn-logout:hover {
  color: #000000;
}

</style>

<!-- 導覽列 -->
<div class="navbar">
<div class="container">
  <div class="spacer"></div>

  <div class="navbar-links">
    <a href="one.php">首頁</a>
    <a href="teach.php">產學合作2</a>
    <a href="QA.php">認識產學合作</a>
    <a href="about.php">認識平台</a>
    <a href="AI.php">AI產學合作</a>
    <a href="chat_settings.php">💬 聊天設置</a>
  </div>

<?php if ($isLoggedIn): ?>
  <div class="user-dropdown">
    <div class="avatar-btn" onclick="toggleDropdown()">
      <img src="share/EIdROxGXsAE_LSs.jpg" alt="頭像" class="avatar-img">
    </div>
    <div class="dropdown-menu" id="dropdownMenu">
      <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
      <a href="logout.php" class="btn-logout">登出</a>
    </div>
  </div>
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
  document.getElementById("openLoginBtn")?.addEventListener("click", () => {
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
  const formData = new FormData(this);

  fetch("http://localhost:5000/login", {
    method: "POST",
    body: formData
  })
  .then(async res => {
    const data = await res.json();
    if (res.ok) {
      document.getElementById("loginMessage").style.color = "green";
      document.getElementById("loginMessage").innerText = data.message;

      // 👉 可加 sessionStorage 或轉跳頁面
      sessionStorage.setItem("username", data.username);
sessionStorage.setItem("role", data.role); // 儲存角色資訊

if (res.ok) {
  document.getElementById("loginMessage").style.color = "green";
  document.getElementById("loginMessage").innerText = data.message;

  // 1. 將資料儲存進 PHP session
  fetch("set_session.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      username: data.username,
      role: data.role
    })
  })
  .then(() => {
    // 2. 根據身分跳轉頁面
    setTimeout(() => {
      if (data.role === "老師") {
        window.location.href = "teacher.php";
      } else if (data.role === "學生") {
        window.location.href = "student.php";
      } else if (data.role === "廠商") {
        window.location.href = "company.php";
      } else if (data.role === "學校行政人員") {
        window.location.href = "admin.php";
      } else {
        window.location.href = "index.php";
      }
    }, 500);
  });
}


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
  menu.style.display = menu.style.display === "block" ? "none" : "block";
}

// 點擊外部收起選單
window.addEventListener("click", function (e) {
  const dropdown = document.getElementById("dropdownMenu");
  const avatar = document.querySelector(".avatar-btn");
  if (!avatar.contains(e.target) && !dropdown.contains(e.target)) {
    dropdown.style.display = "none";
  }
});

</script>
