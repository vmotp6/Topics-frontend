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
        'teacher_username', 'teacher_name', 'department', 'project_title', 
        'project_description', 'company_name', 'company_contact', 'company_phone',
        'company_email', 'project_start_date', 'project_end_date', 'budget_amount',
        'expected_outcomes'
    ];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "缺少必要欄位: $field"]);
            exit;
        }
    }
    
    // 檢查檔案上傳
    if (!isset($_FILES['application_file']) || $_FILES['application_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => '請上傳申請表檔案']);
        exit;
    }
    
    $file = $_FILES['application_file'];
    
    // 檢查檔案類型
    $allowed_types = ['application/pdf'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => '只接受PDF格式的檔案']);
        exit;
    }
    
    // 檢查檔案大小 (10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => '檔案大小不能超過10MB']);
        exit;
    }
    
    // 建立上傳目錄
    $upload_dir = '../uploads/cooperation/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // 生成唯一檔名
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $file_path = $upload_dir . $unique_filename;
    
    // 移動上傳的檔案
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        echo json_encode(['success' => false, 'message' => '檔案上傳失敗']);
        exit;
    }
    
    // 準備SQL語句
    $sql = "INSERT INTO cooperation_applications (
        teacher_username, teacher_name, department, project_title, project_description,
        company_name, company_contact, company_phone, company_email,
        project_start_date, project_end_date, budget_amount, expected_outcomes,
        application_file_path, status
    ) VALUES (
        :teacher_username, :teacher_name, :department, :project_title, :project_description,
        :company_name, :company_contact, :company_phone, :company_email,
        :project_start_date, :project_end_date, :budget_amount, :expected_outcomes,
        :application_file_path, 'pending'
    )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':teacher_username' => $_POST['teacher_username'],
        ':teacher_name' => $_POST['teacher_name'],
        ':department' => $_POST['department'],
        ':project_title' => $_POST['project_title'],
        ':project_description' => $_POST['project_description'],
        ':company_name' => $_POST['company_name'],
        ':company_contact' => $_POST['company_contact'],
        ':company_phone' => $_POST['company_phone'],
        ':company_email' => $_POST['company_email'],
        ':project_start_date' => $_POST['project_start_date'],
        ':project_end_date' => $_POST['project_end_date'],
        ':budget_amount' => $_POST['budget_amount'],
        ':expected_outcomes' => $_POST['expected_outcomes'],
        ':application_file_path' => $file_path
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => '申請表提交成功！您的申請已送交審核，請等待行政人員審核結果。',
        'application_id' => $pdo->lastInsertId()
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料庫錯誤: ' . $e->getMessage()]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => '系統錯誤: ' . $e->getMessage()]);
}
?>
