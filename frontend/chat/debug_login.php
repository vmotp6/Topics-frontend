<?php
/**
 * 調試登入狀態
 */

session_start();

echo "<h1>登入狀態調試</h1>";

echo "<h2>會話資訊</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>當前狀態</h2>";
$username = $_SESSION['username'] ?? '未設定';
$role = $_SESSION['role'] ?? '未設定';

echo "<p><strong>用戶名:</strong> " . htmlspecialchars($username) . "</p>";
echo "<p><strong>角色:</strong> " . htmlspecialchars($role) . "</p>";

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>資料庫中的用戶</h2>";
    
    // 檢查所有用戶
    $stmt = $pdo->query("SELECT id, username, role FROM user ORDER BY role, username");
    $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>用戶名</th><th>角色</th><th>操作</th></tr>";
    foreach ($allUsers as $user) {
        $loginLink = "<a href='?login={$user['username']}'>登入</a>";
        echo "<tr><td>{$user['id']}</td><td>{$user['username']}</td><td>{$user['role']}</td><td>$loginLink</td></tr>";
    }
    echo "</table>";
    
    // 處理登入
    if (isset($_GET['login'])) {
        $loginUsername = $_GET['login'];
        $stmt = $pdo->prepare("SELECT username, role FROM user WHERE username = ?");
        $stmt->execute([$loginUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            echo "<p style='color: green;'>✅ 已登入為: {$user['username']} ({$user['role']})</p>";
            echo "<p><a href='chat.php'>前往聊天室</a></p>";
        } else {
            echo "<p style='color: red;'>❌ 用戶不存在</p>";
        }
    }
    
    // 檢查學生表
    echo "<h2>學生表檢查</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM student");
    $studentCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>學生表記錄數: $studentCount</p>";
    
    if ($studentCount > 0) {
        $stmt = $pdo->query("SELECT s.name, s.department, s.grade, u.username, u.role 
                            FROM student s 
                            JOIN user u ON s.user_id = u.id 
                            LIMIT 5");
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>姓名</th><th>科系</th><th>年級</th><th>用戶名</th><th>角色</th></tr>";
        foreach ($students as $student) {
            echo "<tr><td>{$student['name']}</td><td>{$student['department']}</td><td>{$student['grade']}</td><td>{$student['username']}</td><td>{$student['role']}</td></tr>";
        }
        echo "</table>";
    }
    
    // 檢查老師表
    echo "<h2>老師表檢查</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM teacher");
    $teacherCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>老師表記錄數: $teacherCount</p>";
    
    if ($teacherCount > 0) {
        $stmt = $pdo->query("SELECT t.name, t.department, u.username, u.role 
                            FROM teacher t 
                            JOIN user u ON t.user_id = u.id 
                            LIMIT 5");
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>姓名</th><th>科系</th><th>用戶名</th><th>角色</th></tr>";
        foreach ($teachers as $teacher) {
            echo "<tr><td>{$teacher['name']}</td><td>{$teacher['department']}</td><td>{$teacher['username']}</td><td>{$teacher['role']}</td></tr>";
        }
        echo "</table>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ 資料庫連接失敗: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>解決方案</h2>";
echo "<ol>";
echo "<li>如果沒有學生或老師用戶，請先執行 <code>setup_student_chat.sql</code></li>";
echo "<li>點擊上方的「登入」連結來登入為學生或老師</li>";
echo "<li>登入後再訪問 <a href='chat.php'>chat.php</a></li>";
echo "</ol>";
?>













