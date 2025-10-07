<?php
/**
 * 修復 teacher 表結構
 */

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>修復 teacher 表結構</h1>";
    
    // 1. 檢查 teacher 表是否存在
    echo "<h2>1. 檢查 teacher 表</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'teacher'");
    if ($stmt->rowCount() == 0) {
        echo "❌ teacher 表不存在，正在創建...<br>";
        
        // 創建 teacher 表
        $sql = "CREATE TABLE teacher (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            department VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_department (department),
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "✅ 創建 teacher 表成功<br>";
    } else {
        echo "✅ teacher 表已存在<br>";
        
        // 檢查表結構
        $stmt = $pdo->query("DESCRIBE teacher");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "現有欄位: " . implode(', ', $columns) . "<br>";
        
        // 檢查是否有 name 欄位
        if (!in_array('name', $columns)) {
            echo "❌ 缺少 name 欄位，正在添加...<br>";
            $pdo->exec("ALTER TABLE teacher ADD COLUMN name VARCHAR(255) NOT NULL DEFAULT '' AFTER user_id");
            echo "✅ 添加 name 欄位成功<br>";
        } else {
            echo "✅ name 欄位已存在<br>";
        }
        
        // 檢查是否有 department 欄位
        if (!in_array('department', $columns)) {
            echo "❌ 缺少 department 欄位，正在添加...<br>";
            $pdo->exec("ALTER TABLE teacher ADD COLUMN department VARCHAR(255) AFTER name");
            echo "✅ 添加 department 欄位成功<br>";
        } else {
            echo "✅ department 欄位已存在<br>";
        }
    }
    
    // 2. 更新用戶角色（統一為中文）
    echo "<h2>2. 更新用戶角色</h2>";
    
    // 更新 student 到 學生
    $stmt = $pdo->prepare("UPDATE user SET role = '學生' WHERE role = 'student'");
    $stmt->execute();
    $studentCount = $stmt->rowCount();
    if ($studentCount > 0) {
        echo "✅ 更新 $studentCount 個用戶從 'student' 到 '學生'<br>";
    }
    
    // 更新 teacher 到 老師
    $stmt = $pdo->prepare("UPDATE user SET role = '老師' WHERE role = 'teacher'");
    $stmt->execute();
    $teacherCount = $stmt->rowCount();
    if ($teacherCount > 0) {
        echo "✅ 更新 $teacherCount 個用戶從 'teacher' 到 '老師'<br>";
    }
    
    // 3. 為現有老師用戶創建老師詳細資料
    echo "<h2>3. 創建老師詳細資料</h2>";
    $stmt = $pdo->query("SELECT id, username FROM user WHERE role = '老師'");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "找到 " . count($teachers) . " 個老師用戶<br>";
    
    $departments = ['資訊管理科', '企業管理科', '護理科', '幼保科', '應用外語科', '視光科', '動畫科'];
    
    foreach ($teachers as $index => $teacher) {
        // 檢查是否已經有老師詳細資料
        $stmt = $pdo->prepare("SELECT id FROM teacher WHERE user_id = ?");
        $stmt->execute([$teacher['id']]);
        
        if ($stmt->rowCount() == 0) {
            // 創建老師詳細資料
            $department = $departments[$index % count($departments)];
            
            $stmt = $pdo->prepare("INSERT INTO teacher (user_id, name, department) VALUES (?, ?, ?)");
            $stmt->execute([
                $teacher['id'],
                $teacher['username'], // 使用用戶名作為姓名
                $department
            ]);
            
            echo "✅ 為用戶 '{$teacher['username']}' 創建老師資料<br>";
        } else {
            echo "ℹ️ 用戶 '{$teacher['username']}' 已有老師資料<br>";
        }
    }
    
    // 4. 測試聯絡人查詢
    echo "<h2>4. 測試聯絡人查詢</h2>";
    
    // 測試學生視角的聯絡人查詢
    $stmt = $pdo->query("SELECT t.user_id, t.name, t.department, u.username, '老師' as contact_type
                        FROM teacher t 
                        JOIN user u ON t.user_id = u.id 
                        WHERE u.role = '老師'
                        ORDER BY t.name
                        LIMIT 5");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "學生可看到的老師數量: " . count($teachers) . "<br>";
    
    if (count($teachers) > 0) {
        echo "<h3>老師列表:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>姓名</th><th>科系</th><th>用戶名</th></tr>";
        foreach ($teachers as $teacher) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($teacher['name']) . "</td>";
            echo "<td>" . htmlspecialchars($teacher['department']) . "</td>";
            echo "<td>" . htmlspecialchars($teacher['username']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 測試老師視角的聯絡人查詢（假設有學生表）
    echo "<h3>測試老師視角查詢學生:</h3>";
    try {
        $stmt = $pdo->query("SELECT s.user_id, s.name, s.department, u.username, '學生' as contact_type, s.grade, s.class_name
                            FROM student s 
                            JOIN user u ON s.user_id = u.id 
                            WHERE u.role = '學生'
                            ORDER BY s.department, s.grade, s.name
                            LIMIT 3");
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "老師可看到的學生數量: " . count($students) . "<br>";
        
        if (count($students) > 0) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>姓名</th><th>科系</th><th>年級</th><th>班級</th><th>用戶名</th></tr>";
            foreach ($students as $student) {
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
    } catch (PDOException $e) {
        echo "⚠️ 學生表查詢失敗: " . htmlspecialchars($e->getMessage()) . "<br>";
        echo "這表示學生表可能不存在或結構不同<br>";
    }
    
    // 5. 檢查結果
    echo "<h2>5. 檢查結果</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user WHERE role = '學生'");
    $studentCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "學生用戶數量: $studentCount<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user WHERE role = '老師'");
    $teacherCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "老師用戶數量: $teacherCount<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM teacher");
    $teacherDetailCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "老師詳細資料數量: $teacherDetailCount<br>";
    
    echo "<h2>✅ 修復完成！</h2>";
    echo "<p><a href='chat.php'>前往聊天室</a></p>";
    echo "<p><a href='debug_login.php'>重新檢查登入狀態</a></p>";
    
} catch(PDOException $e) {
    echo "<h1>❌ 錯誤</h1>";
    echo "<p>資料庫錯誤: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>




