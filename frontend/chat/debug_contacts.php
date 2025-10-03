<?php
/**
 * 調試聯絡人數據
 */

session_start();
$username = $_SESSION['username'] ?? 'test_teacher';
$role = $_SESSION['role'] ?? '老師';

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>調試聯絡人數據</h1>";
    echo "<p>當前用戶: $username (角色: $role)</p>";
    
    // 檢查所有用戶
    echo "<h2>所有用戶</h2>";
    $stmt = $pdo->query("SELECT id, username, role FROM user ORDER BY role, username");
    $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>用戶名</th><th>角色</th></tr>";
    foreach ($allUsers as $user) {
        echo "<tr><td>{$user['id']}</td><td>{$user['username']}</td><td>{$user['role']}</td></tr>";
    }
    echo "</table>";
    
    // 檢查學生用戶
    echo "<h2>學生用戶</h2>";
    $stmt = $pdo->query("SELECT id, username, role FROM user WHERE role = 'student' ORDER BY username");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>學生數量: " . count($students) . "</p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>用戶名</th><th>角色</th></tr>";
    foreach ($students as $student) {
        echo "<tr><td>{$student['id']}</td><td>{$student['username']}</td><td>{$student['role']}</td></tr>";
    }
    echo "</table>";
    
    // 檢查老師用戶
    echo "<h2>老師用戶</h2>";
    $stmt = $pdo->query("SELECT id, username, role FROM user WHERE role = '老師' ORDER BY username");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>老師數量: " . count($teachers) . "</p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>用戶名</th><th>角色</th></tr>";
    foreach ($teachers as $teacher) {
        echo "<tr><td>{$teacher['id']}</td><td>{$teacher['username']}</td><td>{$teacher['role']}</td></tr>";
    }
    echo "</table>";
    
    // 模擬聊天系統的聯絡人查詢
    echo "<h2>模擬聊天系統聯絡人查詢</h2>";
    
    if ($role === '老師') {
        // 獲取同科系老師
        $stmt = $pdo->prepare("SELECT t.user_id, t.name, t.department, u.username, '老師' as contact_type
                              FROM teacher t 
                              JOIN user u ON t.user_id = u.id 
                              WHERE u.role = '老師' AND u.username != ?
                              ORDER BY t.name");
        $stmt->execute([$username]);
        $sameDeptTeachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>同科系老師</h3>";
        echo "<p>數量: " . count($sameDeptTeachers) . "</p>";
        if (!empty($sameDeptTeachers)) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>用戶ID</th><th>姓名</th><th>科系</th><th>用戶名</th><th>類型</th></tr>";
            foreach ($sameDeptTeachers as $teacher) {
                echo "<tr><td>{$teacher['user_id']}</td><td>{$teacher['name']}</td><td>{$teacher['department']}</td><td>{$teacher['username']}</td><td>{$teacher['contact_type']}</td></tr>";
            }
            echo "</table>";
        }
        
        // 獲取所有學生
        $stmt = $pdo->prepare("SELECT u.id as user_id, u.username as name, '未設定' as department, u.username, '學生' as contact_type, 
                              CONCAT('S', LPAD(u.id, 3, '0')) as student_id, '未設定' as grade, '未設定' as class_name
                              FROM user u 
                              WHERE u.role = 'student'
                              ORDER BY u.username");
        $stmt->execute();
        $allStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>所有學生</h3>";
        echo "<p>數量: " . count($allStudents) . "</p>";
        if (!empty($allStudents)) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>用戶ID</th><th>姓名</th><th>科系</th><th>用戶名</th><th>學號</th><th>類型</th></tr>";
            foreach ($allStudents as $student) {
                echo "<tr><td>{$student['user_id']}</td><td>{$student['name']}</td><td>{$student['department']}</td><td>{$student['username']}</td><td>{$student['student_id']}</td><td>{$student['contact_type']}</td></tr>";
            }
            echo "</table>";
        }
        
        // 合併聯絡人
        $contacts = array_merge($sameDeptTeachers, $allStudents);
        
        // 按照名稱排序
        usort($contacts, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        echo "<h3>合併後的聯絡人（按名稱排序）</h3>";
        echo "<p>總數量: " . count($contacts) . "</p>";
        if (!empty($contacts)) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>用戶ID</th><th>姓名</th><th>科系</th><th>用戶名</th><th>類型</th></tr>";
            foreach ($contacts as $contact) {
                echo "<tr><td>{$contact['user_id']}</td><td>{$contact['name']}</td><td>{$contact['department']}</td><td>{$contact['username']}</td><td>{$contact['contact_type']}</td></tr>";
            }
            echo "</table>";
        }
    }
    
} catch(PDOException $e) {
    echo "資料庫連接失敗: " . $e->getMessage();
}
?>
