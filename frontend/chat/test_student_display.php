<?php
/**
 * 測試學生顯示功能
 */

session_start();

// 模擬老師登入
$_SESSION['username'] = 'assistant1';
$_SESSION['role'] = '老師';

$username = $_SESSION['username'];
$role = $_SESSION['role'];

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>測試學生顯示功能</h1>";
    echo "<p>當前用戶: $username (角色: $role)</p>";
    
    // 模擬聊天系統的聯絡人查詢
    $contacts = [];
    
    // 獲取所有其他老師（不依賴 teacher 表）
    $stmt = $pdo->prepare("SELECT u.id as user_id, u.username as name, '未設定' as department, u.username, '老師' as contact_type
                          FROM user u 
                          WHERE u.role = '老師' AND u.username != ?
                          ORDER BY u.username");
    $stmt->execute([$username]);
    $otherTeachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $contacts = array_merge($contacts, $otherTeachers);
    
    echo "<h2>其他老師</h2>";
    echo "<p>數量: " . count($otherTeachers) . "</p>";
    if (!empty($otherTeachers)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>用戶ID</th><th>姓名</th><th>科系</th><th>用戶名</th><th>類型</th></tr>";
        foreach ($otherTeachers as $teacher) {
            echo "<tr><td>{$teacher['user_id']}</td><td>{$teacher['name']}</td><td>{$teacher['department']}</td><td>{$teacher['username']}</td><td>{$teacher['contact_type']}</td></tr>";
        }
        echo "</table>";
    }
    
    // 獲取所有學生（直接從 user 表）
    $stmt = $pdo->prepare("SELECT u.id as user_id, u.username as name, '未設定' as department, u.username, '學生' as contact_type, 
                          CONCAT('S', LPAD(u.id, 3, '0')) as student_id, '未設定' as grade, '未設定' as class_name
                          FROM user u 
                          WHERE u.role = 'student'
                          ORDER BY u.username");
    $stmt->execute();
    $allStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $contacts = array_merge($contacts, $allStudents);
    
    echo "<h2>所有學生</h2>";
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
    $contacts = array_merge($otherTeachers, $allStudents);
    
    // 按照名稱排序
    usort($contacts, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    echo "<h2>合併後的聯絡人（按名稱排序）</h2>";
    echo "<p>總數量: " . count($contacts) . "</p>";
    if (!empty($contacts)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>用戶ID</th><th>姓名</th><th>科系</th><th>用戶名</th><th>學號</th><th>類型</th></tr>";
        foreach ($contacts as $contact) {
            $studentId = isset($contact['student_id']) ? $contact['student_id'] : '-';
            echo "<tr><td>{$contact['user_id']}</td><td>{$contact['name']}</td><td>{$contact['department']}</td><td>{$contact['username']}</td><td>{$studentId}</td><td>{$contact['contact_type']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ 沒有找到任何聯絡人！</p>";
    }
    
    echo "<h2>測試結果</h2>";
    if (count($allStudents) > 0) {
        echo "<p style='color: green;'>✅ 成功找到 " . count($allStudents) . " 個學生</p>";
        echo "<p style='color: green;'>✅ 聯絡人列表包含 " . count($contacts) . " 個聯絡人</p>";
        echo "<p style='color: green;'>✅ 學生已按名稱排序</p>";
    } else {
        echo "<p style='color: red;'>❌ 沒有找到學生用戶</p>";
        echo "<p>請檢查 user 表中是否有 role='student' 的用戶</p>";
    }
    
} catch(PDOException $e) {
    echo "資料庫連接失敗: " . $e->getMessage();
}
?>





