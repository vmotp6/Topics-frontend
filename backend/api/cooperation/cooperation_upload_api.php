<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '只支援POST請求']);
    exit;
}

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 檢查必要欄位
    $required_fields = [
        'teacher_username', 'application_date', 'department', 'principal_investigator',
        'regulations_read', 'project_amount', 'company_name', 'company_contact', 
        'company_phone', 'project_title', 'expected_outcomes', 'project_timeline',
        'has_intellectual_property'
    ];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "缺少必要欄位: $field"]);
            exit;
        }
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
    
    // 檢查檔案大小 (10MB)
    if ($contract_file['size'] > 10 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => '合約書檔案大小不能超過10MB']);
        exit;
    }
    
    if ($proposal_file['size'] > 10 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => '計畫書檔案大小不能超過10MB']);
        exit;
    }
    
    // 建立上傳目錄
    $upload_dir = '../uploads/cooperation/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // 生成唯一檔名
    $contract_extension = pathinfo($contract_file['name'], PATHINFO_EXTENSION);
    $proposal_extension = pathinfo($proposal_file['name'], PATHINFO_EXTENSION);
    
    $contract_filename = 'contract_' . uniqid() . '_' . time() . '.' . $contract_extension;
    $proposal_filename = 'proposal_' . uniqid() . '_' . time() . '.' . $proposal_extension;
    
    $contract_path = $upload_dir . $contract_filename;
    $proposal_path = $upload_dir . $proposal_filename;
    
    // 移動上傳的檔案
    if (!move_uploaded_file($contract_file['tmp_name'], $contract_path)) {
        echo json_encode(['success' => false, 'message' => '合約書檔案上傳失敗']);
        exit;
    }
    
    if (!move_uploaded_file($proposal_file['tmp_name'], $proposal_path)) {
        echo json_encode(['success' => false, 'message' => '計畫書檔案上傳失敗']);
        exit;
    }
    
    // 準備SQL語句 - 需要更新資料表結構
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
    $stmt->execute([
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
    
    echo json_encode([
        'success' => true, 
        'message' => '產學合作申請表提交成功！您的申請已送交審核。'
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料庫錯誤：' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '系統錯誤：' . $e->getMessage()]);
}
?>
