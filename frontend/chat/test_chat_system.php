<?php
/**
 * 聊天系統測試頁面
 * 用於測試老師-學生聊天功能
 */

session_start();

$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? null;
$name = $_SESSION['name'] ?? null;

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 模擬登入（測試用）
    if (!isset($_SESSION['username'])) {
        // 從資料庫獲取現有用戶
        $testUsers = [];
        
        // 獲取一個老師用戶
        $stmt = $pdo->query("SELECT username FROM user WHERE role = '老師' LIMIT 1");
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($teacher) {
            $testUsers[] = ['username' => $teacher['username'], 'role' => '老師', 'name' => '測試老師'];
        }
        
        // 獲取一個學生用戶
        $stmt = $pdo->query("SELECT username FROM user WHERE role = 'student' LIMIT 1");
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($student) {
            $testUsers[] = ['username' => $student['username'], 'role' => '學生', 'name' => '測試學生'];
        }
        
        if (isset($_GET['login'])) {
            $userIndex = (int)$_GET['login'];
            if ($userIndex >= 0 && $userIndex < count($testUsers)) {
                $_SESSION['username'] = $testUsers[$userIndex]['username'];
                $_SESSION['role'] = $testUsers[$userIndex]['role'];
                $_SESSION['name'] = $testUsers[$userIndex]['name'];
                // 重新載入頁面
                header('Location: test_chat_system.php');
                exit;
            }
        }
    }
    
    // 檢查資料庫表
    $tables = ['user', 'teacher', 'student', 'private_chat_history'];
    $tableStatus = [];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $tableStatus[$table] = ['exists' => true, 'count' => $count];
        } catch (PDOException $e) {
            $tableStatus[$table] = ['exists' => false, 'error' => $e->getMessage()];
        }
    }
    
    // 檢查學生資料（直接從 user 表）
    $students = [];
    $stmt = $pdo->query("SELECT u.id, u.username, u.role FROM user u WHERE u.role = 'student' ORDER BY u.username");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 檢查老師資料
    $teachers = [];
    if ($tableStatus['teacher']['exists']) {
        $stmt = $pdo->query("SELECT t.*, u.username FROM teacher t JOIN user u ON t.user_id = u.id WHERE u.role = '老師'");
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch(PDOException $e) {
    die("資料庫連接失敗: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>聊天系統測試</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .status {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .login-buttons {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }
        .login-btn {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .login-btn:hover { background: #0056b3; }
        .chat-link {
            display: inline-block;
            padding: 15px 30px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 18px;
            margin: 20px 0;
        }
        .chat-link:hover { background: #218838; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 聊天系統測試頁面</h1>
        
        <?php if (!$username): ?>
            <div class="status info">
                <h3>請選擇測試角色登入：</h3>
                <div class="login-buttons">
                    <a href="?login=0" class="login-btn">👨‍🏫 以老師身份登入</a>
                    <a href="?login=1" class="login-btn">👨‍🎓 以學生身份登入</a>
                </div>
            </div>
        <?php else: ?>
            <div class="status success">
                <h3>✅ 已登入</h3>
                <p><strong>用戶名：</strong><?php echo htmlspecialchars($username); ?></p>
                <p><strong>角色：</strong><?php echo htmlspecialchars($role); ?></p>
                <p><strong>姓名：</strong><?php echo htmlspecialchars($name); ?></p>
                <a href="?logout=1" class="login-btn" style="background: #dc3545;">登出</a>
            </div>
            
            <div style="text-align: center;">
                <a href="chat.php" class="chat-link">🚀 進入聊天系統</a>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['logout'])): ?>
            <?php
            session_destroy();
            header('Location: test_chat_system.php');
            exit;
            ?>
        <?php endif; ?>
    </div>
    
    <div class="container">
        <h2>📊 資料庫狀態檢查</h2>
        
        <?php foreach ($tableStatus as $table => $status): ?>
            <div class="status <?php echo $status['exists'] ? 'success' : 'error'; ?>">
                <strong><?php echo $table; ?> 表：</strong>
                <?php if ($status['exists']): ?>
                    ✅ 存在 (記錄數: <?php echo $status['count']; ?>)
                <?php else: ?>
                    ❌ 不存在 (錯誤: <?php echo $status['error']; ?>)
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (!empty($students)): ?>
    <div class="container">
        <h2>👨‍🎓 學生用戶 (共 <?php echo count($students); ?> 人)</h2>
        <table>
            <thead>
                <tr>
                    <th>用戶ID</th>
                    <th>用戶名</th>
                    <th>角色</th>
                    <th>狀態</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['id']); ?></td>
                    <td><?php echo htmlspecialchars($student['username']); ?></td>
                    <td><?php echo htmlspecialchars($student['role']); ?></td>
                    <td><span style="color: green;">✅ 可用於聊天</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="status info">
            <p><strong>說明：</strong>這些學生用戶會自動顯示在聊天系統的聯絡人列表中，老師和學生都可以與他們聊天。</p>
        </div>
    </div>
    <?php else: ?>
    <div class="container">
        <div class="status warning">
            <h2>⚠️ 沒有找到學生用戶</h2>
            <p>請在 user 表中添加 role='學生' 的用戶，例如：</p>
            <pre style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;">
INSERT INTO user (username, password, role) VALUES 
('student1', '123456', '學生'),
('student2', '123456', '學生'),
('student3', '123456', '學生');
            </pre>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($teachers)): ?>
    <div class="container">
        <h2>👨‍🏫 老師資料</h2>
        <table>
            <thead>
                <tr>
                    <th>姓名</th>
                    <th>科系</th>
                    <th>用戶名</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teachers as $teacher): ?>
                <tr>
                    <td><?php echo htmlspecialchars($teacher['name']); ?></td>
                    <td><?php echo htmlspecialchars($teacher['department']); ?></td>
                    <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <div class="container">
        <h2>🔧 系統設置</h2>
        <div class="status info">
            <h3>設置步驟：</h3>
            <ol>
                <li>執行 <code>scripts/setup/setup_chat_for_students.php</code> 檢查系統設置</li>
                <li>確保 user 表中有 role='學生' 的用戶</li>
                <li>確保有聊天相關的資料表（private_chat_history 等）</li>
                <li>選擇角色登入測試聊天功能</li>
                <li>測試搜尋功能和群組創建</li>
            </ol>
        </div>
        
        <div class="status warning">
            <h3>⚠️ 注意事項：</h3>
            <ul>
                <li>這是測試頁面，請勿在生產環境使用</li>
                <li>系統會自動使用現有的老師和學生用戶進行測試</li>
                <li>如果資料庫表不存在，請先執行設置腳本</li>
                <li>如果沒有學生用戶，請先在 user 表中添加 role='學生' 的用戶</li>
            </ul>
        </div>
    </div>
</body>
</html>
