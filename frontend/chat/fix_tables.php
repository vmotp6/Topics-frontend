<?php
/**
 * 修復資料庫表結構
 */

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>修復資料庫表結構</h1>";
    
    // 1. 檢查並修復 teacher 表
    echo "<h2>1. 修復 teacher 表</h2>";
    
    // 檢查 teacher 表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'teacher'");
    if ($stmt->rowCount() == 0) {
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
        echo "✅ 創建 teacher 表<br>";
    } else {
        // 檢查是否有 name 欄位
        $stmt = $pdo->query("SHOW COLUMNS FROM teacher LIKE 'name'");
        if ($stmt->rowCount() == 0) {
            // 添加 name 欄位
            $pdo->exec("ALTER TABLE teacher ADD COLUMN name VARCHAR(255) NOT NULL DEFAULT '' AFTER user_id");
            echo "✅ 添加 name 欄位到 teacher 表<br>";
        } else {
            echo "ℹ️ teacher 表已有 name 欄位<br>";
        }
        
        // 檢查是否有 department 欄位
        $stmt = $pdo->query("SHOW COLUMNS FROM teacher LIKE 'department'");
        if ($stmt->rowCount() == 0) {
            // 添加 department 欄位
            $pdo->exec("ALTER TABLE teacher ADD COLUMN department VARCHAR(255) AFTER name");
            echo "✅ 添加 department 欄位到 teacher 表<br>";
        } else {
            echo "ℹ️ teacher 表已有 department 欄位<br>";
        }
    }
    
    // 2. 創建 student 表
    echo "<h2>2. 創建 student 表</h2>";
    $sql = "CREATE TABLE IF NOT EXISTS student (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        student_id VARCHAR(50) UNIQUE,
        department VARCHAR(255),
        grade VARCHAR(50),
        class_name VARCHAR(100),
        email VARCHAR(255),
        phone VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_student_id (student_id),
        INDEX idx_department (department),
        INDEX idx_name (name),
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ 創建 student 表<br>";
    
    // 3. 更新用戶角色
    echo "<h2>3. 更新用戶角色</h2>";
    
    // 更新角色名稱
    $updates = [
        'student' => '學生',
        'teacher' => '老師',
        'admin' => '學校行政人員'
    ];
    
    foreach ($updates as $oldRole => $newRole) {
        $stmt = $pdo->prepare("UPDATE user SET role = ? WHERE role = ?");
        $stmt->execute([$newRole, $oldRole]);
        $count = $stmt->rowCount();
        if ($count > 0) {
            echo "✅ 更新 $count 個用戶從 '$oldRole' 到 '$newRole'<br>";
        }
    }
    
    // 4. 為現有老師用戶創建老師詳細資料
    echo "<h2>4. 創建老師詳細資料</h2>";
    $stmt = $pdo->query("SELECT id, username FROM user WHERE role = '老師'");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "找到 " . count($teachers) . " 個老師用戶<br>";
    
    $departments = ['資訊工程學系', '企業管理學系', '外國語文學系', '護理學系', '幼兒保育學系'];
    
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
    
    // 5. 為現有學生用戶創建學生詳細資料
    echo "<h2>5. 創建學生詳細資料</h2>";
    $stmt = $pdo->query("SELECT id, username FROM user WHERE role = '學生'");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "找到 " . count($students) . " 個學生用戶<br>";
    
    $departments = ['資訊工程學系', '企業管理學系', '外國語文學系', '護理學系', '幼兒保育學系'];
    $grades = ['一年級', '二年級', '三年級', '四年級'];
    
    foreach ($students as $index => $student) {
        // 檢查是否已經有學生詳細資料
        $stmt = $pdo->prepare("SELECT id FROM student WHERE user_id = ?");
        $stmt->execute([$student['id']]);
        
        if ($stmt->rowCount() == 0) {
            // 創建學生詳細資料
            $department = $departments[$index % count($departments)];
            $grade = $grades[$index % count($grades)];
            $studentId = 'S' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
            $className = $department . $grade . '甲';
            
            $stmt = $pdo->prepare("INSERT INTO student (user_id, name, student_id, department, grade, class_name, email, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $student['id'],
                $student['username'], // 使用用戶名作為姓名
                $studentId,
                $department,
                $grade,
                $className,
                $student['username'] . '@example.com',
                '09' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT)
            ]);
            
            echo "✅ 為用戶 '{$student['username']}' 創建學生資料<br>";
        } else {
            echo "ℹ️ 用戶 '{$student['username']}' 已有學生資料<br>";
        }
    }
    
    // 6. 檢查結果
    echo "<h2>6. 檢查結果</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user WHERE role = '學生'");
    $studentCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "學生用戶數量: $studentCount<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM student");
    $studentDetailCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "學生詳細資料數量: $studentDetailCount<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user WHERE role = '老師'");
    $teacherCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "老師用戶數量: $teacherCount<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM teacher");
    $teacherDetailCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "老師詳細資料數量: $teacherDetailCount<br>";
    
    // 7. 測試聯絡人查詢
    echo "<h2>7. 測試聯絡人查詢</h2>";
    
    // 測試學生視角的聯絡人查詢
    $stmt = $pdo->query("SELECT t.user_id, t.name, t.department, u.username, '老師' as contact_type
                        FROM teacher t 
                        JOIN user u ON t.user_id = u.id 
                        WHERE u.role = '老師'
                        ORDER BY t.name
                        LIMIT 3");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "學生可看到的老師數量: " . count($teachers) . "<br>";
    
    if (count($teachers) > 0) {
        echo "<h3>老師列表範例:</h3>";
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
    
    // 測試老師視角的聯絡人查詢
    $stmt = $pdo->query("SELECT s.user_id, s.name, s.department, u.username, '學生' as contact_type, s.grade, s.class_name
                        FROM student s 
                        JOIN user u ON s.user_id = u.id 
                        WHERE u.role = '學生'
                        ORDER BY s.department, s.grade, s.name
                        LIMIT 3");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "老師可看到的學生數量: " . count($students) . "<br>";
    
    if (count($students) > 0) {
        echo "<h3>學生列表範例:</h3>";
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
    
    echo "<h2>✅ 修復完成！</h2>";
    echo "<p><a href='chat.php'>前往聊天室</a></p>";
    echo "<p><a href='debug_login.php'>重新檢查登入狀態</a></p>";
    
} catch(PDOException $e) {
    echo "<h1>❌ 錯誤</h1>";
    echo "<p>資料庫錯誤: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

