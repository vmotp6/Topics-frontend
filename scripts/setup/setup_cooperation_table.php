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
    
    // 創建產學合作案申請表資料表 - 更新為康寧大學格式
    $sql = "CREATE TABLE IF NOT EXISTS cooperation_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        
        -- 基本申請資訊
        teacher_username VARCHAR(255) NOT NULL,
        application_date DATE NOT NULL,
        approval_number VARCHAR(100) NULL,
        department VARCHAR(255) NOT NULL,
        principal_investigator VARCHAR(255) NOT NULL,
        regulations_read ENUM('yes', 'no') NOT NULL,
        
        -- 申請類別
        application_categories TEXT NOT NULL,
        
        -- 計畫詳細資訊
        project_amount DECIMAL(15,2) NOT NULL,
        admin_fee_percentage DECIMAL(5,2) DEFAULT 10.00,
        outcome_university BOOLEAN DEFAULT FALSE,
        outcome_company BOOLEAN DEFAULT FALSE,
        university_percentage DECIMAL(5,2) DEFAULT 0,
        company_percentage DECIMAL(5,2) DEFAULT 0,
        
        -- 合作廠商資訊
        company_name VARCHAR(255) NOT NULL,
        company_contact VARCHAR(255) NOT NULL,
        company_phone VARCHAR(50) NOT NULL,
        
        -- 計畫內容
        project_title VARCHAR(500) NOT NULL,
        expected_outcomes TEXT NOT NULL,
        project_timeline TEXT NOT NULL,
        
        -- 智慧財產權
        has_intellectual_property ENUM('yes', 'no') NOT NULL,
        university_ip_percentage DECIMAL(5,2) DEFAULT 0,
        company_ip_percentage DECIMAL(5,2) DEFAULT 0,
        investigator_ip_percentage DECIMAL(5,2) DEFAULT 0,
        
        -- 智慧財產權詳細資訊
        university_patent VARCHAR(255) NULL,
        company_patent VARCHAR(255) NULL,
        investigator_patent VARCHAR(255) NULL,
        university_trademark VARCHAR(255) NULL,
        company_trademark VARCHAR(255) NULL,
        investigator_trademark VARCHAR(255) NULL,
        university_copyright VARCHAR(255) NULL,
        company_copyright VARCHAR(255) NULL,
        investigator_copyright VARCHAR(255) NULL,
        university_trade_secret VARCHAR(255) NULL,
        company_trade_secret VARCHAR(255) NULL,
        investigator_trade_secret VARCHAR(255) NULL,
        
        -- 其他問題
        future_tech_transfer ENUM('yes', 'no') NULL,
        tech_transfer_amount DECIMAL(15,2) DEFAULT 0,
        has_derived_benefits ENUM('yes', 'no') NULL,
        benefits_amount DECIMAL(15,2) DEFAULT 0,
        use_university_venue BOOLEAN DEFAULT FALSE,
        venue_fees_in_proposal BOOLEAN DEFAULT FALSE,
        employ_disadvantaged_students BOOLEAN DEFAULT FALSE,
        use_standard_contract BOOLEAN DEFAULT FALSE,
        
        -- 檔案路徑
        contract_file_path VARCHAR(500) NOT NULL,
        proposal_file_path VARCHAR(500) NOT NULL,
        
        -- 審核狀態
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        admin_username VARCHAR(255) NULL,
        admin_comment TEXT NULL,
        review_date TIMESTAMP NULL,
        
        -- 時間戳記
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        -- 索引
        INDEX idx_teacher_username (teacher_username),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at),
        INDEX idx_department (department)
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
