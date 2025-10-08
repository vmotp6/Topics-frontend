<?php
/**
 * 測試學生聊天系統
 */

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>學生聊天系統測試</h1>";
    
    // 1. 檢查學生表是否存在
    echo "<h2>1. 檢查資料庫表</h2>";
    $tables = ['student', 'user', 'private_chat_history', 'chat_groups', 'group_members', 'group_messages'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ 表 '$table' 存在<br>";
        } else {
            echo "❌ 表 '$table' 不存在<br>";
        }
    }
    
    // 2. 檢查學生用戶
    echo "<h2>2. 檢查學生用戶</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user WHERE role = '學生'");
    $studentCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "學生用戶數量: $studentCount<br>";
    
    if ($studentCount > 0) {
        $stmt = $pdo->query("SELECT u.username, s.name, s.department, s.grade, s.class_name 
                            FROM user u 
                            LEFT JOIN student s ON u.id = s.user_id 
                            WHERE u.role = '學生' 
                            LIMIT 5");
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>用戶名</th><th>姓名</th><th>科系</th><th>年級</th><th>班級</th></tr>";
        foreach ($students as $student) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($student['username']) . "</td>";
            echo "<td>" . htmlspecialchars($student['name'] ?? '未設定') . "</td>";
            echo "<td>" . htmlspecialchars($student['department'] ?? '未設定') . "</td>";
            echo "<td>" . htmlspecialchars($student['grade'] ?? '未設定') . "</td>";
            echo "<td>" . htmlspecialchars($student['class_name'] ?? '未設定') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. 檢查老師用戶
    echo "<h2>3. 檢查老師用戶</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user WHERE role = '老師'");
    $teacherCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "老師用戶數量: $teacherCount<br>";
    
    if ($teacherCount > 0) {
        $stmt = $pdo->query("SELECT u.username, t.name, t.department 
                            FROM user u 
                            LEFT JOIN teacher t ON u.id = t.user_id 
                            WHERE u.role = '老師' 
                            LIMIT 5");
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>用戶名</th><th>姓名</th><th>科系</th></tr>";
        foreach ($teachers as $teacher) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($teacher['username']) . "</td>";
            echo "<td>" . htmlspecialchars($teacher['name'] ?? '未設定') . "</td>";
            echo "<td>" . htmlspecialchars($teacher['department'] ?? '未設定') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. 測試聊天記錄
    echo "<h2>4. 檢查聊天記錄</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM private_chat_history");
    $chatCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "私聊記錄數量: $chatCount<br>";
    
    if ($chatCount > 0) {
        $stmt = $pdo->query("SELECT from_user, to_user, message, timestamp 
                            FROM private_chat_history 
                            ORDER BY timestamp DESC 
                            LIMIT 5");
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>發送者</th><th>接收者</th><th>訊息</th><th>時間</th></tr>";
        foreach ($messages as $message) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($message['from_user']) . "</td>";
            echo "<td>" . htmlspecialchars($message['to_user']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($message['message'], 0, 50)) . "...</td>";
            echo "<td>" . $message['timestamp'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 5. 測試聯絡人查詢（模擬老師視角）
    echo "<h2>5. 測試老師聯絡人查詢</h2>";
    $stmt = $pdo->query("SELECT u.username FROM user WHERE role = '老師' LIMIT 1");
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($teacher) {
        $teacherUsername = $teacher['username'];
        echo "測試老師: " . htmlspecialchars($teacherUsername) . "<br>";
        
        // 獲取同科系老師
        $stmt = $pdo->prepare("SELECT t.user_id, t.name, t.department, u.username, '老師' as contact_type
                              FROM teacher t 
                              JOIN user u ON t.user_id = u.id 
                              WHERE u.role = '老師' AND u.username != ?
                              ORDER BY t.name
                              LIMIT 3");
        $stmt->execute([$teacherUsername]);
        $sameDeptTeachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "同科系老師數量: " . count($sameDeptTeachers) . "<br>";
        
        // 獲取所有學生
        $stmt = $pdo->query("SELECT s.user_id, s.name, s.department, u.username, '學生' as contact_type, s.grade, s.class_name
                            FROM student s 
                            JOIN user u ON s.user_id = u.id 
                            WHERE u.role = '學生'
                            ORDER BY s.department, s.grade, s.name
                            LIMIT 5");
        $allStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "學生聯絡人數量: " . count($allStudents) . "<br>";
        
        if (count($allStudents) > 0) {
            echo "<h3>學生聯絡人範例:</h3>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>姓名</th><th>科系</th><th>年級</th><th>班級</th><th>用戶名</th></tr>";
            foreach ($allStudents as $student) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($student['name']) . "</td>";
                echo "<td>" . htmlspecialchars($student['department']) . "</td>";
                echo "<td>" . htmlspecialchars($student['grade']) . "</td>";
                echo "<td>" . htmlspecialchars($student['class_name']) . "</td>";
                echo "<td>" . htmlspecialchars($student['username']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    echo "<h2>✅ 測試完成</h2>";
    echo "<p><a href='chat.php'>前往聊天室</a></p>";
    
} catch(PDOException $e) {
    echo "<h1>❌ 資料庫連接失敗</h1>";
    echo "<p>錯誤: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>請檢查資料庫連接設定。</p>";
}
?>









