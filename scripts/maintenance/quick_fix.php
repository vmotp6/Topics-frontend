<?php
header('Content-Type: text/html; charset=utf-8');
echo "<h1>🚀 康寧大學產學合作系統快速修復</h1>\n";

$host = '100.79.58.120';
$dbname = 'topics_good';
$username = 'root';
$password = '';

$errors = [];
$success = [];

try {
    echo "<h2>1. 資料庫連線測試</h2>\n";
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ 資料庫連線成功<br>\n";
    $success[] = "資料庫連線成功";
    
    // 檢查並重建資料表
    echo "<h2>2. 資料表檢查與重建</h2>\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'cooperation_applications'");
    if ($stmt->rowCount() == 0) {
        echo "❌ 資料表不存在，正在重建...<br>\n";
        
        // 刪除舊表（如果存在）
        $pdo->exec("DROP TABLE IF EXISTS cooperation_applications");
        
        // 建立新表
        $sql = "CREATE TABLE cooperation_applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_username VARCHAR(255) NOT NULL,
            application_date DATE NOT NULL,
            approval_number VARCHAR(100) NULL,
            department VARCHAR(255) NOT NULL,
            principal_investigator VARCHAR(255) NOT NULL,
            regulations_read ENUM('yes', 'no') NOT NULL,
            application_categories TEXT NOT NULL,
            project_amount DECIMAL(15,2) NOT NULL,
            admin_fee_percentage DECIMAL(5,2) DEFAULT 10.00,
            outcome_university BOOLEAN DEFAULT FALSE,
            outcome_company BOOLEAN DEFAULT FALSE,
            university_percentage DECIMAL(5,2) DEFAULT 0,
            company_percentage DECIMAL(5,2) DEFAULT 0,
            company_name VARCHAR(255) NOT NULL,
            company_contact VARCHAR(255) NOT NULL,
            company_phone VARCHAR(50) NOT NULL,
            project_title VARCHAR(500) NOT NULL,
            expected_outcomes TEXT NOT NULL,
            project_timeline TEXT NOT NULL,
            has_intellectual_property ENUM('yes', 'no') NOT NULL,
            university_ip_percentage DECIMAL(5,2) DEFAULT 0,
            company_ip_percentage DECIMAL(5,2) DEFAULT 0,
            investigator_ip_percentage DECIMAL(5,2) DEFAULT 0,
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
            future_tech_transfer ENUM('yes', 'no') NULL,
            tech_transfer_amount DECIMAL(15,2) DEFAULT 0,
            has_derived_benefits ENUM('yes', 'no') NULL,
            benefits_amount DECIMAL(15,2) DEFAULT 0,
            use_university_venue BOOLEAN DEFAULT FALSE,
            venue_fees_in_proposal BOOLEAN DEFAULT FALSE,
            employ_disadvantaged_students BOOLEAN DEFAULT FALSE,
            use_standard_contract BOOLEAN DEFAULT FALSE,
            contract_file_path VARCHAR(500) NOT NULL,
            proposal_file_path VARCHAR(500) NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            admin_username VARCHAR(255) NULL,
            admin_comment TEXT NULL,
            review_date TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_teacher_username (teacher_username),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at),
            INDEX idx_department (department)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "✅ 資料表重建成功<br>\n";
        $success[] = "資料表重建成功";
    } else {
        echo "✅ 資料表已存在<br>\n";
        $success[] = "資料表已存在";
    }
    
} catch(PDOException $e) {
    echo "❌ 資料庫錯誤: " . $e->getMessage() . "<br>\n";
    $errors[] = "資料庫錯誤: " . $e->getMessage();
}

// 建立上傳目錄
echo "<h2>3. 上傳目錄設定</h2>\n";
$upload_dir = '../uploads/cooperation/';

if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0755, true)) {
        echo "✅ 上傳目錄建立成功<br>\n";
        $success[] = "上傳目錄建立成功";
    } else {
        echo "❌ 上傳目錄建立失敗<br>\n";
        $errors[] = "上傳目錄建立失敗";
    }
} else {
    echo "✅ 上傳目錄已存在<br>\n";
    $success[] = "上傳目錄已存在";
}

// 設定目錄權限
if (is_writable($upload_dir)) {
    echo "✅ 上傳目錄可寫入<br>\n";
    $success[] = "上傳目錄可寫入";
} else {
    if (chmod($upload_dir, 0755)) {
        echo "✅ 上傳目錄權限設定成功<br>\n";
        $success[] = "上傳目錄權限設定成功";
    } else {
        echo "❌ 上傳目錄權限設定失敗<br>\n";
        $errors[] = "上傳目錄權限設定失敗";
    }
}

// 測試資料庫插入
echo "<h2>4. 功能測試</h2>\n";
try {
    if (isset($pdo)) {
        // 測試插入
        $test_sql = "INSERT INTO cooperation_applications (
            teacher_username, application_date, department, principal_investigator,
            regulations_read, application_categories, project_amount, company_name,
            company_contact, company_phone, project_title, expected_outcomes,
            project_timeline, has_intellectual_property, contract_file_path, proposal_file_path
        ) VALUES (
            'quick_fix_test', '2024-01-01', '測試系所', '測試主持人',
            'yes', '技術合作', 100000.00, '測試公司',
            '測試聯絡人', '0912345678', '測試專案', '測試成果',
            '6個月', 'no', '/test/contract.pdf', '/test/proposal.pdf'
        )";
        
        $pdo->exec($test_sql);
        echo "✅ 資料庫插入測試成功<br>\n";
        $success[] = "資料庫插入測試成功";
        
        // 清理測試資料
        $pdo->exec("DELETE FROM cooperation_applications WHERE teacher_username = 'quick_fix_test'");
        echo "✅ 測試資料清理完成<br>\n";
        $success[] = "測試資料清理完成";
    }
} catch(PDOException $e) {
    echo "❌ 資料庫測試失敗: " . $e->getMessage() . "<br>\n";
    $errors[] = "資料庫測試失敗: " . $e->getMessage();
}

// 檢查PHP設定
echo "<h2>5. PHP設定檢查</h2>\n";
$php_settings = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time'),
    'file_uploads' => ini_get('file_uploads')
];

foreach ($php_settings as $setting => $value) {
    echo "📋 $setting: $value<br>\n";
}

// 總結
echo "<h2>6. 修復結果</h2>\n";
if (empty($errors)) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<h3>✅ 修復成功！</h3>\n";
    echo "<p>所有問題都已修復，系統現在可以正常使用。</p>\n";
    echo "</div>\n";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<h3>❌ 修復過程中發現問題：</h3>\n";
    echo "<ul>\n";
    foreach ($errors as $error) {
        echo "<li>$error</li>\n";
    }
    echo "</ul>\n";
    echo "</div>\n";
}

echo "<h3>成功項目：</h3>\n";
echo "<ul>\n";
foreach ($success as $item) {
    echo "<li>✅ $item</li>\n";
}
echo "</ul>\n";

echo "<h2>7. 下一步</h2>\n";
echo "<p>現在您可以：</p>\n";
echo "<ol>\n";
echo "<li><a href='../frontend/test_cooperation_upload.php' target='_blank'>測試申請表上傳功能</a></li>\n";
echo "<li><a href='../frontend/cooperation_upload.php' target='_blank'>使用正式申請表頁面</a></li>\n";
echo "<li><a href='debug_cooperation_submission.php' target='_blank'>查看詳細調試資訊</a></li>\n";
echo "</ol>\n";

echo "<h2>8. 如果問題持續</h2>\n";
echo "<p>請檢查：</p>\n";
echo "<ul>\n";
echo "<li>資料庫伺服器是否正在運行</li>\n";
echo "<li>網路連線是否正常</li>\n";
echo "<li>PHP錯誤日誌中的詳細錯誤訊息</li>\n";
echo "<li>瀏覽器開發者工具中的網路請求詳情</li>\n";
echo "</ul>\n";
?>
