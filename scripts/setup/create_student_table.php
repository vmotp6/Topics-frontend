<?php
/**
 * 創建學生表腳本
 */

$host = 'localhost';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "📋 開始創建學生表...\n";
    
    // 創建學生表
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
    echo "✅ 學生表創建成功\n";
    
    // 更新 user 表，添加學生角色（如果不存在）
    $sql = "ALTER TABLE user MODIFY COLUMN role ENUM('老師', '學校行政人員', '學生', '廠商') NOT NULL";
    $pdo->exec($sql);
    echo "✅ 用戶表角色欄位更新成功\n";
    
    // 檢查現有的學生用戶
    $stmt = $pdo->query("SELECT id, username FROM user WHERE role = '學生'");
    $existingStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 發現 " . count($existingStudents) . " 個現有學生用戶\n";
    
    // 為現有學生用戶創建學生詳細資料（如果還沒有）
    foreach ($existingStudents as $studentUser) {
        // 檢查是否已經有學生詳細資料
        $stmt = $pdo->prepare("SELECT id FROM student WHERE user_id = ?");
        $stmt->execute([$studentUser['id']]);
        
        if ($stmt->rowCount() == 0) {
            // 為現有學生創建基本資料
            $stmt = $pdo->prepare("INSERT INTO student (user_id, name, student_id, department, grade, class_name) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $studentUser['id'], 
                $studentUser['username'], // 暫時用用戶名作為姓名
                'S' . str_pad($studentUser['id'], 3, '0', STR_PAD_LEFT), // 生成學號
                '未設定', // 預設科系
                '未設定', // 預設年級
                '未設定'  // 預設班級
            ]);
            echo "✅ 為現有學生創建資料: {$studentUser['username']}\n";
        } else {
            echo "⚠️  學生資料已存在: {$studentUser['username']}\n";
        }
    }
    
    echo "\n🎉 學生表設置完成！\n";
    echo "📝 已為現有學生用戶創建詳細資料\n";
    echo "💡 提示：您可以在資料庫中手動更新學生的詳細資訊（姓名、科系、年級、班級等）\n";
    
} catch (Exception $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
}
?>
