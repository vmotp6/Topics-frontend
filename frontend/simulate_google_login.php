<?php
// 載入 session 配置
require_once 'session_config.php';

// 模擬 Google 登入回調
if (isset($_GET['test']) && $_GET['test'] === 'gmail') {
    // 模擬 Gmail 帳號登入
    $_SESSION['logged_in'] = true;
    $_SESSION['username'] = 'test@gmail.com';
    $_SESSION['role'] = '學生';
    $_SESSION['login_method'] = 'google';
    
    echo "<h1>模擬 Gmail 登入成功</h1>";
    echo "<p>Session 已設定</p>";
    echo "<p><a href='student.php'>前往學生頁面</a></p>";
    echo "<p><a href='index.php'>前往首頁</a></p>";
    echo "<p><a href='debug_google_login.php'>查看 Session 狀態</a></p>";
    exit;
}

if (isset($_GET['test']) && $_GET['test'] === 'school') {
    // 模擬學校帳號登入
    $_SESSION['logged_in'] = true;
    $_SESSION['username'] = 'teacher@ukn.edu.tw';
    $_SESSION['role'] = '老師';
    $_SESSION['login_method'] = 'google';
    
    echo "<h1>模擬學校帳號登入成功</h1>";
    echo "<p>Session 已設定</p>";
    echo "<p><a href='teacher.php'>前往老師頁面</a></p>";
    echo "<p><a href='index.php'>前往首頁</a></p>";
    echo "<p><a href='debug_google_login.php'>查看 Session 狀態</a></p>";
    exit;
}

?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>模擬 Google 登入測試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>模擬 Google 登入測試</h1>
    
    <div class="test-section">
        <h2>1. 模擬 Gmail 帳號登入</h2>
        <p>這會模擬一個 Gmail 帳號登入，角色設為「學生」</p>
        <a href="?test=gmail"><button>模擬 Gmail 登入</button></a>
    </div>
    
    <div class="test-section">
        <h2>2. 模擬學校帳號登入</h2>
        <p>這會模擬一個學校帳號登入，角色設為「老師」</p>
        <a href="?test=school"><button>模擬學校帳號登入</button></a>
    </div>
    
    <div class="test-section">
        <h2>3. 測試頁面</h2>
        <a href="debug_google_login.php"><button>查看 Session 狀態</button></a>
        <a href="student.php"><button>學生頁面</button></a>
        <a href="teacher.php"><button>老師頁面</button></a>
        <a href="index.php"><button>首頁</button></a>
    </div>
    
    <div class="test-section">
        <h2>4. 真實 Google 登入</h2>
        <a href="http://localhost:5000/auth/google"><button>真實 Google 登入</button></a>
    </div>
</body>
</html>
