<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 啟用錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 自動修復資料庫結構
function autoFixDatabase($pdo) {
    try {
        // 強制重建資料表 - 先刪除再創建
        $pdo->exec("DROP TABLE IF EXISTS cooperation_applications");
        
        // 創建完整的資料表
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
        return "資料表已完全重建";
        
    } catch (PDOException $e) {
        return "修復失敗: " . $e->getMessage();
    }
}

// 記錄請求資訊
$log_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'post_data' => $_POST,
    'files_data' => $_FILES
];

// 寫入日誌
file_put_contents('auto_fix_api_debug.log', json_encode($log_data, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '只支援POST請求']);
    exit;
}

try {
    // 資料庫連接
    $host = '100.79.58.120';
    $dbname = 'topics_good';
    $db_username = 'root';
    $db_password = '';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 自動修復資料庫
    $fix_result = autoFixDatabase($pdo);
    
    // 簡化的欄位檢查
    $required_fields = [
        'teacher_username', 'application_date', 'department', 'principal_investigator',
        'regulations_read', 'project_amount', 'company_name', 'company_contact', 
        'company_phone', 'project_title', 'expected_outcomes', 'project_timeline',
        'has_intellectual_property'
    ];
    
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        echo json_encode(['success' => false, 'message' => '缺少必要欄位: ' . implode(', ', $missing_fields)]);
        exit;
    }
    
    // 檢查申請類別
    if (!isset($_POST['application_categories']) || empty($_POST['application_categories'])) {
        echo json_encode(['success' => false, 'message' => '請至少選擇一項申請類別']);
        exit;
    }
    
    // 檢查檔案上傳
    if (!isset($_FILES['contract_file']) || $_FILES['contract_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => '請上傳產學合作合約書']);
        exit;
    }
    
    if (!isset($_FILES['proposal_file']) || $_FILES['proposal_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => '請上傳產學合作計畫書']);
        exit;
    }
    
    $contract_file = $_FILES['contract_file'];
    $proposal_file = $_FILES['proposal_file'];
    
    // 檢查檔案類型
    $allowed_types = ['application/pdf'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    
    $contract_mime = finfo_file($finfo, $contract_file['tmp_name']);
    $proposal_mime = finfo_file($finfo, $proposal_file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($contract_mime, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => '合約書只接受PDF格式的檔案']);
        exit;
    }
    
    if (!in_array($proposal_mime, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => '計畫書只接受PDF格式的檔案']);
        exit;
    }
    
    // 建立上傳目錄
    $upload_dir = '../uploads/cooperation/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            echo json_encode(['success' => false, 'message' => '無法建立上傳目錄']);
            exit;
        }
    }
    
    // 檢查目錄權限
    if (!is_writable($upload_dir)) {
        echo json_encode(['success' => false, 'message' => '上傳目錄不可寫入']);
        exit;
    }
    
    // 生成唯一檔名
    $contract_filename = 'contract_' . uniqid() . '_' . time() . '.pdf';
    $proposal_filename = 'proposal_' . uniqid() . '_' . time() . '.pdf';
    
    $contract_path = $upload_dir . $contract_filename;
    $proposal_path = $upload_dir . $proposal_filename;
    
    // 移動上傳的檔案
    if (!move_uploaded_file($contract_file['tmp_name'], $contract_path)) {
        echo json_encode(['success' => false, 'message' => '合約書檔案上傳失敗']);
        exit;
    }
    
    if (!move_uploaded_file($proposal_file['tmp_name'], $proposal_path)) {
        // 清理已上傳的合約書
        unlink($contract_path);
        echo json_encode(['success' => false, 'message' => '計畫書檔案上傳失敗']);
        exit;
    }
    
    // 準備SQL語句
    $sql = "INSERT INTO cooperation_applications (
        teacher_username, application_date, approval_number, department, principal_investigator,
        regulations_read, application_categories, project_amount, admin_fee_percentage,
        outcome_university, outcome_company, university_percentage, company_percentage,
        company_name, company_contact, company_phone, project_title, expected_outcomes,
        project_timeline, has_intellectual_property, university_ip_percentage, company_ip_percentage,
        investigator_ip_percentage, university_patent, company_patent, investigator_patent,
        university_trademark, company_trademark, investigator_trademark,
        university_copyright, company_copyright, investigator_copyright,
        university_trade_secret, company_trade_secret, investigator_trade_secret,
        future_tech_transfer, tech_transfer_amount, has_derived_benefits, benefits_amount,
        use_university_venue, venue_fees_in_proposal, employ_disadvantaged_students,
        use_standard_contract, contract_file_path, proposal_file_path, status
    ) VALUES (
        :teacher_username, :application_date, :approval_number, :department, :principal_investigator,
        :regulations_read, :application_categories, :project_amount, :admin_fee_percentage,
        :outcome_university, :outcome_company, :university_percentage, :company_percentage,
        :company_name, :company_contact, :company_phone, :project_title, :expected_outcomes,
        :project_timeline, :has_intellectual_property, :university_ip_percentage, :company_ip_percentage,
        :investigator_ip_percentage, :university_patent, :company_patent, :investigator_patent,
        :university_trademark, :company_trademark, :investigator_trademark,
        :university_copyright, :company_copyright, :investigator_copyright,
        :university_trade_secret, :company_trade_secret, :investigator_trade_secret,
        :future_tech_transfer, :tech_transfer_amount, :has_derived_benefits, :benefits_amount,
        :use_university_venue, :venue_fees_in_proposal, :employ_disadvantaged_students,
        :use_standard_contract, :contract_file_path, :proposal_file_path, 'pending'
    )";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':teacher_username' => $_POST['teacher_username'],
        ':application_date' => $_POST['application_date'],
        ':approval_number' => $_POST['approval_number'] ?? '',
        ':department' => $_POST['department'],
        ':principal_investigator' => $_POST['principal_investigator'],
        ':regulations_read' => $_POST['regulations_read'],
        ':application_categories' => implode(',', $_POST['application_categories']),
        ':project_amount' => $_POST['project_amount'],
        ':admin_fee_percentage' => $_POST['admin_fee_percentage'] ?? 10,
        ':outcome_university' => isset($_POST['outcome_university']) ? 1 : 0,
        ':outcome_company' => isset($_POST['outcome_company']) ? 1 : 0,
        ':university_percentage' => $_POST['university_percentage'] ?? 0,
        ':company_percentage' => $_POST['company_percentage'] ?? 0,
        ':company_name' => $_POST['company_name'],
        ':company_contact' => $_POST['company_contact'],
        ':company_phone' => $_POST['company_phone'],
        ':project_title' => $_POST['project_title'],
        ':expected_outcomes' => $_POST['expected_outcomes'],
        ':project_timeline' => $_POST['project_timeline'],
        ':has_intellectual_property' => $_POST['has_intellectual_property'],
        ':university_ip_percentage' => $_POST['university_ip_percentage'] ?? 0,
        ':company_ip_percentage' => $_POST['company_ip_percentage'] ?? 0,
        ':investigator_ip_percentage' => $_POST['investigator_ip_percentage'] ?? 0,
        ':university_patent' => $_POST['university_patent'] ?? '',
        ':company_patent' => $_POST['company_patent'] ?? '',
        ':investigator_patent' => $_POST['investigator_patent'] ?? '',
        ':university_trademark' => $_POST['university_trademark'] ?? '',
        ':company_trademark' => $_POST['company_trademark'] ?? '',
        ':investigator_trademark' => $_POST['investigator_trademark'] ?? '',
        ':university_copyright' => $_POST['university_copyright'] ?? '',
        ':company_copyright' => $_POST['company_copyright'] ?? '',
        ':investigator_copyright' => $_POST['investigator_copyright'] ?? '',
        ':university_trade_secret' => $_POST['university_trade_secret'] ?? '',
        ':company_trade_secret' => $_POST['company_trade_secret'] ?? '',
        ':investigator_trade_secret' => $_POST['investigator_trade_secret'] ?? '',
        ':future_tech_transfer' => $_POST['future_tech_transfer'] ?? '',
        ':tech_transfer_amount' => $_POST['tech_transfer_amount'] ?? 0,
        ':has_derived_benefits' => $_POST['has_derived_benefits'] ?? '',
        ':benefits_amount' => $_POST['benefits_amount'] ?? 0,
        ':use_university_venue' => isset($_POST['use_university_venue']) ? 1 : 0,
        ':venue_fees_in_proposal' => isset($_POST['venue_fees_in_proposal']) ? 1 : 0,
        ':employ_disadvantaged_students' => isset($_POST['employ_disadvantaged_students']) ? 1 : 0,
        ':use_standard_contract' => isset($_POST['use_standard_contract']) ? 1 : 0,
        ':contract_file_path' => $contract_path,
        ':proposal_file_path' => $proposal_path
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => '產學合作申請表提交成功！您的申請已送交審核。',
            'application_id' => $pdo->lastInsertId(),
            'database_fix' => $fix_result
        ]);
    } else {
        // 清理已上傳的檔案
        unlink($contract_path);
        unlink($proposal_path);
        echo json_encode(['success' => false, 'message' => '資料庫插入失敗']);
    }
    
} catch (PDOException $e) {
    // 清理已上傳的檔案
    if (isset($contract_path) && file_exists($contract_path)) {
        unlink($contract_path);
    }
    if (isset($proposal_path) && file_exists($proposal_path)) {
        unlink($proposal_path);
    }
    
    echo json_encode(['success' => false, 'message' => '資料庫錯誤：' . $e->getMessage()]);
} catch (Exception $e) {
    // 清理已上傳的檔案
    if (isset($contract_path) && file_exists($contract_path)) {
        unlink($contract_path);
    }
    if (isset($proposal_path) && file_exists($proposal_path)) {
        unlink($proposal_path);
    }
    
    echo json_encode(['success' => false, 'message' => '系統錯誤：' . $e->getMessage()]);
}
?>
