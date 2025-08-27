<?php
session_start();
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登入狀態調試</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .debug-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .status {
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .test-links {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-links a {
            display: inline-block;
            margin: 10px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .test-links a:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <h1>🔍 登入狀態調試</h1>
    
    <div class="debug-info">
        <h2>Session 資訊</h2>
        <?php if (isset($_SESSION['username'])): ?>
            <div class="status success">
                ✅ 已登入<br>
                用戶名: <?php echo htmlspecialchars($_SESSION['username']); ?>
            </div>
        <?php else: ?>
            <div class="status error">
                ❌ 未登入
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['role'])): ?>
            <div class="status success">
                ✅ 角色已設定<br>
                角色: <?php echo htmlspecialchars($_SESSION['role']); ?>
            </div>
        <?php else: ?>
            <div class="status error">
                ❌ 角色未設定
            </div>
        <?php endif; ?>
        
        <h3>完整 Session 資料:</h3>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>
    
    <div class="debug-info">
        <h2>權限檢查</h2>
        <?php
        $isLoggedIn = isset($_SESSION['username']);
        $isStudent = isset($_SESSION['role']) && ($_SESSION['role'] === '學生' || $_SESSION['role'] === 'student');
        $isTeacher = isset($_SESSION['role']) && ($_SESSION['role'] === '老師' || $_SESSION['role'] === 'teacher');
        $isAdmin = isset($_SESSION['role']) && ($_SESSION['role'] === '學校行政人員' || $_SESSION['role'] === 'admin');
        ?>
        
        <div class="status <?php echo $isLoggedIn ? 'success' : 'error'; ?>">
            <?php echo $isLoggedIn ? '✅' : '❌'; ?> 登入狀態: <?php echo $isLoggedIn ? '已登入' : '未登入'; ?>
        </div>
        
        <div class="status <?php echo $isStudent ? 'success' : 'warning'; ?>">
            <?php echo $isStudent ? '✅' : '❌'; ?> 學生權限: <?php echo $isStudent ? '有權限' : '無權限'; ?>
        </div>
        
        <div class="status <?php echo $isTeacher ? 'success' : 'warning'; ?>">
            <?php echo $isTeacher ? '✅' : '❌'; ?> 老師權限: <?php echo $isTeacher ? '有權限' : '無權限'; ?>
        </div>
        
        <div class="status <?php echo $isAdmin ? 'success' : 'warning'; ?>">
            <?php echo $isAdmin ? '✅' : '❌'; ?> 行政人員權限: <?php echo $isAdmin ? '有權限' : '無權限'; ?>
        </div>
    </div>
    
    <div class="test-links">
        <h2>測試連結</h2>
        <a href="one.php">🏠 回到首頁</a>
        <a href="cooperation_upload.php">📝 就讀意願登錄 (直接訪問)</a>
        <a href="admin_enrollment_review.php">🎓 就讀意願管理 (直接訪問)</a>
        <a href="teacher.php">👨‍🏫 老師頁面</a>
        <a href="admin.php">👔 行政人員頁面</a>
        <a href="logout.php">🚪 登出</a>
    </div>
    
    <div class="debug-info">
        <h2>解決方案</h2>
        <p><strong>如果無法訪問就讀意願登錄頁面：</strong></p>
        <ol>
            <li>確保你已經登入系統</li>
            <li>確保你的角色是「學生」</li>
            <li>如果角色不是學生，請聯繫管理員修改你的角色</li>
            <li>或者暫時修改權限檢查（見下方）</li>
        </ol>
        
        <p><strong>臨時解決方案：</strong></p>
        <p>如果你需要測試功能，可以暫時修改 <code>frontend/cooperation_upload.php</code> 的權限檢查：</p>
        <pre style="background: #f8f9fa; padding: 10px; border-radius: 4px;">
// 將這行：
if (!isset($_SESSION['username']) || $_SESSION['role'] !== '學生') {

// 改為：
if (!isset($_SESSION['username'])) {
        </pre>
    </div>
</body>
</html>
