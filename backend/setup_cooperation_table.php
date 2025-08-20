<?php
// 資料庫連線設定
$host = '100.79.58.120';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "正在建立產學合作案申請表資料表...\n";
    
    // 創建產學合作案申請表資料表
    $sql = "CREATE TABLE IF NOT EXISTS cooperation_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_username VARCHAR(255) NOT NULL,
        teacher_name VARCHAR(255) NOT NULL,
        department VARCHAR(255) NOT NULL,
        project_title VARCHAR(500) NOT NULL,
        project_description TEXT NOT NULL,
        company_name VARCHAR(255) NOT NULL,
        company_contact VARCHAR(255) NOT NULL,
        company_phone VARCHAR(50) NOT NULL,
        company_email VARCHAR(255) NOT NULL,
        project_start_date DATE NOT NULL,
        project_end_date DATE NOT NULL,
        budget_amount DECIMAL(15,2) NOT NULL,
        expected_outcomes TEXT NOT NULL,
        application_file_path VARCHAR(500) NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        admin_username VARCHAR(255) NULL,
        admin_comment TEXT NULL,
        review_date TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_teacher_username (teacher_username),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ 產學合作案申請表資料表建立成功！\n";
    
    // 檢查資料表是否建立成功
    $stmt = $pdo->query("SHOW TABLES LIKE 'cooperation_applications'");
    if ($stmt->rowCount() > 0) {
        echo "✅ 資料表 'cooperation_applications' 已存在並可以使用。\n";
        
        // 顯示資料表結構
        echo "\n📋 資料表結構：\n";
        $stmt = $pdo->query("DESCRIBE cooperation_applications");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo "- {$column['Field']}: {$column['Type']} " . 
                 ($column['Null'] === 'NO' ? '(必填)' : '(選填)') . "\n";
        }
    } else {
        echo "❌ 警告：資料表建立可能失敗。\n";
    }
    
    // 建立上傳目錄
    $upload_dir = '../uploads/cooperation/';
    if (!file_exists($upload_dir)) {
        if (mkdir($upload_dir, 0755, true)) {
            echo "✅ 上傳目錄建立成功：$upload_dir\n";
        } else {
            echo "❌ 上傳目錄建立失敗：$upload_dir\n";
        }
    } else {
        echo "✅ 上傳目錄已存在：$upload_dir\n";
    }
    
} catch(PDOException $e) {
    echo "❌ 資料庫錯誤: " . $e->getMessage() . "\n";
} catch(Exception $e) {
    echo "❌ 系統錯誤: " . $e->getMessage() . "\n";
}
?>
