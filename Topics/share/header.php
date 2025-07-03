<?php
session_start();
$isLoggedIn = isset($_SESSION['username']);
?>

<style>
  	.navbar {
    	background-color: #e0f0ff; /* 淺藍色 */
    	padding: 15px 20px;
    	color: #003366; /* 深藍文字 */
    	display: flex;
    	justify-content: center;
    	align-items: center;
    	font-family: "Helvetica Neue", Arial, sans-serif;
    	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    	flex-wrap: wrap;
  	}

  	.navbar-links, .navbar-user {
    	display: flex;
    	align-items: center;
    	margin: 5px 10px;
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

  	/* 登出按鈕風格 */
  	.btn-logout {
    	color: white;
    	background-color: #007bff;
    	text-decoration: none;
    	padding: 9px 15px;
    	margin-left: 10px;
    	border-radius: 20px;
   	 	font-size: 18px;
    	font-weight: 600;
    	transition: background-color 0.3s;
    	display: inline-block;
  	}

  	.btn-logout:hover {
    	background-color: #0056b3;
  	}

  	/* 註冊/登入包在同一按鈕框框，裡面有兩個文字連結 */
  	.btn-auth-wrapper {
    	background-color: #007bff;
    	border-radius: 20px;
    	padding: 8px 18px;
    	display: inline-flex;
    	align-items: center;
   	 	gap: 8px;
    	margin-left: 10px;
    	user-select: none;
  	}

  	/* 註冊與登入連結文字 */
  	.btn-auth-wrapper a {
    	color: white;
    	text-decoration: none;
    	font-size: 18px;
    	font-weight: 600;
    	transition: text-decoration 0.3s;
    	cursor: pointer;
  	}

  	.btn-auth-wrapper a:hover {
    	color:rgb(178, 182, 182); 
    	text-decoration: none;
  	}

  	/* 分隔符號 */
  	.btn-auth-wrapper .separator {
    	color: white;
    	user-select: none;
    	font-weight: 600;
    	font-size: 18px;
  	}
</style>

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
        <a href="register.php">註冊</a>
        <span class="separator">/</span>
        <a href="login.php">登入</a>
      </div>
    <?php endif; ?>
  </div>
</div>
