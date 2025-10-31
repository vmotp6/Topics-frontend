<?php
// 關閉錯誤顯示，避免輸出 HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 載入 reCAPTCHA 設定
require_once '../../config/recaptcha_config.php';

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
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 檢查資料表是否存在，如果不存在則創建
    $stmt = $pdo->query("SHOW TABLES LIKE 'enrollment_applications'");
    if ($stmt->rowCount() == 0) {
        // 創建就讀意願登錄資料表
        $sql = "CREATE TABLE enrollment_applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL,
            name VARCHAR(255) NOT NULL,
            identity ENUM('學生', '家長') NOT NULL,
            gender ENUM('男', '女') NULL,
            phone1 VARCHAR(50) NOT NULL,
            phone2 VARCHAR(50) NULL,
            email VARCHAR(255) NULL,
            intention1 VARCHAR(255) DEFAULT '無特定',
            system1 VARCHAR(50) NULL,
            department1 VARCHAR(255) NULL,
            intention2 VARCHAR(255) DEFAULT '無特定',
            system2 VARCHAR(50) NULL,
            department2 VARCHAR(255) NULL,
            intention3 VARCHAR(255) DEFAULT '無特定',
            system3 VARCHAR(50) NULL,
            department3 VARCHAR(255) NULL,
            junior_high VARCHAR(255) NULL,
            current_grade VARCHAR(50) NULL,
            line_id VARCHAR(255) NULL,
            facebook VARCHAR(255) NULL,
            recommended_teacher VARCHAR(255) NULL,
            remarks TEXT NULL,
            status ENUM('pending', 'contacted', 'enrolled') DEFAULT 'pending',
            admin_comment TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_username (username),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
    }
    
    // reCAPTCHA 驗證函數
    function verifyRecaptcha($response, $secret_key) {
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => $secret_key,
            'response' => $response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === FALSE) {
            return false;
        }
        
        $json = json_decode($result, true);
        return $json['success'] === true;
    }
    
    // 驗證驗證碼
    session_start();
    $captcha_input = $_POST['captcha'] ?? '';
    $captcha_session = $_SESSION['captcha_code'] ?? '';
    
    if (empty($captcha_input) || empty($captcha_session) || $captcha_input !== $captcha_session) {
        echo json_encode(['success' => false, 'message' => '驗證碼錯誤，請重新輸入']);
        exit;
    }
    
    // 清除已使用的驗證碼
    unset($_SESSION['captcha_code']);
    
    // 檢查必要欄位
    $required_fields = ['username', 'name', 'identity', 'phone1'];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "缺少必要欄位: $field"]);
            exit;
        }
    }
    
    // 準備插入資料
    $sql = "INSERT INTO enrollment_applications (
        username, name, identity, gender, phone1, phone2, email,
        intention1, system1, department1,
        intention2, system2, department2,
        intention3, system3, department3,
        junior_high, current_grade, line_id, facebook, recommended_teacher, remarks
    ) VALUES (
        :username, :name, :identity, :gender, :phone1, :phone2, :email,
        :intention1, :system1, :department1,
        :intention2, :system2, :department2,
        :intention3, :system3, :department3,
        :junior_high, :current_grade, :line_id, :facebook, :recommended_teacher, :remarks
    )";
    
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        ':username' => $_POST['username'],
        ':name' => $_POST['name'],
        ':identity' => $_POST['identity'],
        ':gender' => $_POST['gender'] ?? null,
        ':phone1' => $_POST['phone1'],
        ':phone2' => $_POST['phone2'] ?? null,
        ':email' => $_POST['email'] ?? null,
        ':intention1' => $_POST['intention1'] ?? '無特定',
        ':system1' => $_POST['system1'] ?? null,
        ':department1' => $_POST['department1'] ?? null,
        ':intention2' => $_POST['intention2'] ?? '無特定',
        ':system2' => $_POST['system2'] ?? null,
        ':department2' => $_POST['department2'] ?? null,
        ':intention3' => $_POST['intention3'] ?? '無特定',
        ':system3' => $_POST['system3'] ?? null,
        ':department3' => $_POST['department3'] ?? null,
        ':junior_high' => $_POST['junior_high'] ?? null,
        ':current_grade' => $_POST['current_grade'] ?? null,
        ':line_id' => $_POST['line_id'] ?? null,
        ':facebook' => $_POST['facebook'] ?? null,
        ':recommended_teacher' => $_POST['recommended_teacher'] ?? null,
        ':remarks' => $_POST['remarks'] ?? null
    ]);
    
    $application_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true, 
        'message' => '就讀意願登錄成功！康寧大學將儘快與您聯絡。',
        'application_id' => $application_id
    ]);
    
} catch (PDOException $e) {
    error_log("資料庫錯誤: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '資料庫連接失敗，請檢查資料庫設定']);
} catch (Exception $e) {
    error_log("系統錯誤: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '系統錯誤: ' . $e->getMessage()]);
}
?>
