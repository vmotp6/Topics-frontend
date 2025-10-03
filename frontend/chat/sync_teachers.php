<?php
/**
 * 同步老師資料
 */

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>同步老師資料</h1>";
    
    // 1. 檢查所有老師用戶
    echo "<h2>1. 檢查老師用戶</h2>";
    $stmt = $pdo->query("SELECT id, username, role FROM user WHERE role = '老師' ORDER BY username");
    $teacherUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "找到 " . count($teacherUsers) . " 個老師用戶<br>";
    
    if (count($teacherUsers) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>用戶名</th><th>角色</th><th>teacher表狀態</th></tr>";
        
        foreach ($teacherUsers as $teacher) {
            // 檢查是否在teacher表中有記錄
            $stmt = $pdo->prepare("SELECT id, name, department FROM teacher WHERE user_id = ?");
            $stmt->execute([$teacher['id']]);
            $teacherDetail = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $status = $teacherDetail ? "✅ 已存在" : "❌ 缺少";
            echo "<tr>";
            echo "<td>{$teacher['id']}</td>";
            echo "<td>{$teacher['username']}</td>";
            echo "<td>{$teacher['role']}</td>";
            echo "<td>$status</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 2. 為缺少的老師創建詳細資料
    echo "<h2>2. 創建缺少的老師資料</h2>";
    
    $departments = ['資訊工程學系', '企業管理學系', '外國語文學系', '護理學系', '幼兒保育學系'];
    $createdCount = 0;
    
    foreach ($teacherUsers as $index => $teacher) {
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
            
            echo "✅ 為用戶 '{$teacher['username']}' 創建老師資料 (科系: $department)<br>";
            $createdCount++;
        } else {
            echo "ℹ️ 用戶 '{$teacher['username']}' 已有老師資料<br>";
        }
    }
    
    if ($createdCount > 0) {
        echo "<p style='color: green;'><strong>成功創建 $createdCount 個老師資料</strong></p>";
    } else {
        echo "<p style='color: blue;'>所有老師都已有詳細資料</p>";
    }
    
    // 3. 測試聯絡人查詢
    echo "<h2>3. 測試聯絡人查詢</h2>";
    
    // 測試學生視角的聯絡人查詢
    $stmt = $pdo->query("SELECT t.user_id, t.name, t.department, u.username, '老師' as contact_type
                        FROM teacher t 
                        JOIN user u ON t.user_id = u.id 
                        WHERE u.role = '老師'
                        ORDER BY t.name");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "學生可看到的老師數量: " . count($teachers) . "<br>";
    
    if (count($teachers) > 0) {
        echo "<h3>所有老師列表:</h3>";
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
    
    // 4. 檢查結果
    echo "<h2>4. 檢查結果</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user WHERE role = '老師'");
    $teacherUserCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "老師用戶數量: $teacherUserCount<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM teacher");
    $teacherDetailCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "老師詳細資料數量: $teacherDetailCount<br>";
    
    if ($teacherUserCount == $teacherDetailCount) {
        echo "<p style='color: green;'>✅ 所有老師用戶都有對應的詳細資料</p>";
    } else {
        echo "<p style='color: red;'>❌ 老師用戶和詳細資料數量不匹配</p>";
    }
    
    echo "<h2>✅ 同步完成！</h2>";
    echo "<p><a href='chat.php'>前往聊天室查看</a></p>";
    echo "<p><a href='debug_login.php'>檢查登入狀態</a></p>";
    
} catch(PDOException $e) {
    echo "<h1>❌ 錯誤</h1>";
    echo "<p>資料庫錯誤: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

