<?php
header('Content-Type: text/html; charset=utf-8');
echo "<h1>🚨 終極資料庫修復工具</h1>\n";

$host = '100.79.58.120';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    echo "<h2>1. 連接資料庫</h2>\n";
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ 資料庫連接成功<br>\n";
    
    echo "<h2>2. 強制重建資料表</h2>\n";
    
    // 先刪除舊表
    echo "🗑️ 刪除舊資料表...<br>\n";
    $pdo->exec("DROP TABLE IF EXISTS cooperation_applications");
    echo "✅ 舊資料表已刪除<br>\n";
    
    // 創建新表
    echo "🔨 創建新資料表...<br>\n";
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
    echo "✅ 新資料表創建成功<br>\n";
    
    echo "<h2>3. 驗證資料表結構</h2>\n";
    $stmt = $pdo->query("DESCRIBE cooperation_applications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>資料表欄位：</h3>\n";
    echo "<table border='1'>\n";
    echo "<tr><th>欄位名</th><th>類型</th><th>NULL</th><th>預設值</th></tr>\n";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // 檢查關鍵欄位是否存在
    $column_names = array_column($columns, 'Field');
    $critical_columns = [
        'application_date', 
        'teacher_username', 
        'department', 
        'project_title',
        'principal_investigator',
        'regulations_read',
        'application_categories',
        'project_amount',
        'company_name',
        'company_contact',
        'company_phone',
        'expected_outcomes',
        'project_timeline',
        'has_intellectual_property',
        'contract_file_path',
        'proposal_file_path'
    ];
    
    echo "<h3>關鍵欄位檢查：</h3>\n";
    foreach ($critical_columns as $col) {
        if (in_array($col, $column_names)) {
            echo "✅ $col 存在<br>\n";
        } else {
            echo "❌ $col 不存在<br>\n";
        }
    }
    
    echo "<h2>4. 測試資料插入</h2>\n";
    try {
        $test_sql = "INSERT INTO cooperation_applications (
            teacher_username, application_date, department, principal_investigator,
            regulations_read, application_categories, project_amount, company_name,
            company_contact, company_phone, project_title, expected_outcomes,
            project_timeline, has_intellectual_property, contract_file_path, proposal_file_path
        ) VALUES (
            'test_user', '2024-01-01', '測試系所', '測試主持人',
            'yes', '技術合作', 100000.00, '測試公司',
            '測試聯絡人', '0912345678', '測試專案', '測試成果',
            '6個月', 'no', '/test/contract.pdf', '/test/proposal.pdf'
        )";
        
        $pdo->exec($test_sql);
        echo "✅ 測試插入成功<br>\n";
        
        // 清理測試資料
        $pdo->exec("DELETE FROM cooperation_applications WHERE teacher_username = 'test_user'");
        echo "✅ 測試資料清理完成<br>\n";
        
    } catch (PDOException $e) {
        echo "❌ 測試插入失敗: " . $e->getMessage() . "<br>\n";
    }
    
    echo "<h2>5. 修復完成</h2>\n";
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<h3>✅ 資料庫終極修復成功！</h3>\n";
    echo "<p>資料表已完全重建，所有必要欄位都已添加。</p>\n";
    echo "<p>現在請重新測試申請表提交功能。</p>\n";
    echo "</div>\n";
    
    echo "<h2>6. 立即測試</h2>\n";
    echo "<p><a href='../frontend/cooperation_upload.php' target='_blank'>返回申請表頁面</a></p>\n";
    echo "<p><a href='../frontend/test_cooperation_upload.php' target='_blank'>測試申請表功能</a></p>\n";
    
} catch(PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<h3>❌ 資料庫錯誤</h3>\n";
    echo "<p>錯誤訊息: " . $e->getMessage() . "</p>\n";
    echo "</div>\n";
}
?>
