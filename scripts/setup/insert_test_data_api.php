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
    
    // 讀取POST資料
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['action']) || $data['action'] !== 'insert_test_data') {
        echo json_encode(['success' => false, 'message' => '無效的操作']);
        exit;
    }
    
    // 插入測試資料
    $test_data = [
        [
            'teacher_username' => 'teacher1',
            'application_date' => '2025-01-15',
            'approval_number' => 'AP001',
            'department' => '資訊管理科',
            'principal_investigator' => '張教授',
            'regulations_read' => 'yes',
            'application_categories' => '技術合作,研究合作',
            'project_amount' => 150000.00,
            'admin_fee_percentage' => 10.00,
            'outcome_university' => 1,
            'outcome_company' => 1,
            'university_percentage' => 60.00,
            'company_percentage' => 40.00,
            'company_name' => '科技公司A',
            'company_contact' => '王經理',
            'company_phone' => '02-12345678',
            'project_title' => 'AI技術研發合作計畫',
            'expected_outcomes' => '開發新一代AI演算法',
            'project_timeline' => '2025年1月-2025年12月',
            'has_intellectual_property' => 'yes',
            'university_ip_percentage' => 70.00,
            'company_ip_percentage' => 30.00,
            'investigator_ip_percentage' => 0.00,
            'future_tech_transfer' => 'yes',
            'tech_transfer_amount' => 50000.00,
            'has_derived_benefits' => 'yes',
            'benefits_amount' => 20000.00,
            'use_university_venue' => 1,
            'venue_fees_in_proposal' => 1,
            'employ_disadvantaged_students' => 1,
            'use_standard_contract' => 1,
            'contract_file_path' => '/uploads/contracts/test_contract1.pdf',
            'proposal_file_path' => '/uploads/proposals/test_proposal1.pdf',
            'status' => 'pending'
        ],
        [
            'teacher_username' => 'teacher2',
            'application_date' => '2025-01-16',
            'approval_number' => 'AP002',
            'department' => '企業管理科',
            'principal_investigator' => '李教授',
            'regulations_read' => 'yes',
            'application_categories' => '人才培育,技術合作',
            'project_amount' => 200000.00,
            'admin_fee_percentage' => 10.00,
            'outcome_university' => 1,
            'outcome_company' => 0,
            'university_percentage' => 80.00,
            'company_percentage' => 20.00,
            'company_name' => '電子公司B',
            'company_contact' => '陳總監',
            'company_phone' => '02-87654321',
            'project_title' => '智慧電網技術合作',
            'expected_outcomes' => '建立智慧電網示範系統',
            'project_timeline' => '2025年2月-2025年11月',
            'has_intellectual_property' => 'no',
            'university_ip_percentage' => 0.00,
            'company_ip_percentage' => 0.00,
            'investigator_ip_percentage' => 0.00,
            'future_tech_transfer' => 'no',
            'tech_transfer_amount' => 0.00,
            'has_derived_benefits' => 'no',
            'benefits_amount' => 0.00,
            'use_university_venue' => 0,
            'venue_fees_in_proposal' => 0,
            'employ_disadvantaged_students' => 0,
            'use_standard_contract' => 1,
            'contract_file_path' => '/uploads/contracts/test_contract2.pdf',
            'proposal_file_path' => '/uploads/proposals/test_proposal2.pdf',
            'status' => 'approved',
            'admin_username' => 'admin1',
            'admin_comment' => '計畫內容完整，經費編列合理',
            'review_date' => '2025-01-20 10:30:00'
        ],
        [
            'teacher_username' => 'teacher3',
            'application_date' => '2025-01-17',
            'approval_number' => 'AP003',
            'department' => '護理科',
            'principal_investigator' => '劉教授',
            'regulations_read' => 'yes',
            'application_categories' => '研究合作',
            'project_amount' => 120000.00,
            'admin_fee_percentage' => 10.00,
            'outcome_university' => 1,
            'outcome_company' => 1,
            'university_percentage' => 50.00,
            'company_percentage' => 50.00,
            'company_name' => '製造公司C',
            'company_contact' => '林廠長',
            'company_phone' => '02-11223344',
            'project_title' => '精密製造技術研發',
            'expected_outcomes' => '提升製造精度和效率',
            'project_timeline' => '2025年3月-2025年10月',
            'has_intellectual_property' => 'yes',
            'university_ip_percentage' => 50.00,
            'company_ip_percentage' => 50.00,
            'investigator_ip_percentage' => 0.00,
            'future_tech_transfer' => 'yes',
            'tech_transfer_amount' => 30000.00,
            'has_derived_benefits' => 'yes',
            'benefits_amount' => 15000.00,
            'use_university_venue' => 1,
            'venue_fees_in_proposal' => 1,
            'employ_disadvantaged_students' => 1,
            'use_standard_contract' => 1,
            'contract_file_path' => '/uploads/contracts/test_contract3.pdf',
            'proposal_file_path' => '/uploads/proposals/test_proposal3.pdf',
            'status' => 'rejected',
            'admin_username' => 'admin1',
            'admin_comment' => '經費編列過於樂觀，需要重新評估',
            'review_date' => '2025-01-22 14:15:00'
        ]
    ];
    
    $inserted_count = 0;
    
    foreach ($test_data as $data) {
        // 根據狀態決定要插入哪些欄位
        if ($data['status'] === 'pending') {
            $sql = "INSERT INTO cooperation_applications (
                teacher_username, application_date, approval_number, department, 
                principal_investigator, regulations_read, application_categories, 
                project_amount, admin_fee_percentage, outcome_university, outcome_company,
                university_percentage, company_percentage, company_name, company_contact,
                company_phone, project_title, expected_outcomes, project_timeline,
                has_intellectual_property, university_ip_percentage, company_ip_percentage,
                investigator_ip_percentage, future_tech_transfer, tech_transfer_amount,
                has_derived_benefits, benefits_amount, use_university_venue,
                venue_fees_in_proposal, employ_disadvantaged_students, use_standard_contract,
                contract_file_path, proposal_file_path, status
            ) VALUES (
                :teacher_username, :application_date, :approval_number, :department,
                :principal_investigator, :regulations_read, :application_categories,
                :project_amount, :admin_fee_percentage, :outcome_university, :outcome_company,
                :university_percentage, :company_percentage, :company_name, :company_contact,
                :company_phone, :project_title, :expected_outcomes, :project_timeline,
                :has_intellectual_property, :university_ip_percentage, :company_ip_percentage,
                :investigator_ip_percentage, :future_tech_transfer, :tech_transfer_amount,
                :has_derived_benefits, :benefits_amount, :use_university_venue,
                :venue_fees_in_proposal, :employ_disadvantaged_students, :use_standard_contract,
                :contract_file_path, :proposal_file_path, :status
            )";
            
            // 移除審核相關欄位
            unset($data['admin_username'], $data['admin_comment'], $data['review_date']);
        } else {
            $sql = "INSERT INTO cooperation_applications (
                teacher_username, application_date, approval_number, department, 
                principal_investigator, regulations_read, application_categories, 
                project_amount, admin_fee_percentage, outcome_university, outcome_company,
                university_percentage, company_percentage, company_name, company_contact,
                company_phone, project_title, expected_outcomes, project_timeline,
                has_intellectual_property, university_ip_percentage, company_ip_percentage,
                investigator_ip_percentage, future_tech_transfer, tech_transfer_amount,
                has_derived_benefits, benefits_amount, use_university_venue,
                venue_fees_in_proposal, employ_disadvantaged_students, use_standard_contract,
                contract_file_path, proposal_file_path, status, admin_username,
                admin_comment, review_date
            ) VALUES (
                :teacher_username, :application_date, :approval_number, :department,
                :principal_investigator, :regulations_read, :application_categories,
                :project_amount, :admin_fee_percentage, :outcome_university, :outcome_company,
                :university_percentage, :company_percentage, :company_name, :company_contact,
                :company_phone, :project_title, :expected_outcomes, :project_timeline,
                :has_intellectual_property, :university_ip_percentage, :company_ip_percentage,
                :investigator_ip_percentage, :future_tech_transfer, :tech_transfer_amount,
                :has_derived_benefits, :benefits_amount, :use_university_venue,
                :venue_fees_in_proposal, :employ_disadvantaged_students, :use_standard_contract,
                :contract_file_path, :proposal_file_path, :status, :admin_username,
                :admin_comment, :review_date
            )";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        $inserted_count++;
    }
    
    echo json_encode([
        'success' => true,
        'message' => "成功插入 $inserted_count 筆測試資料"
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料庫錯誤: ' . $e->getMessage()]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => '系統錯誤: ' . $e->getMessage()]);
}
?>
