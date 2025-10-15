<?php
header('Content-Type: application/json; charset=utf-8');

// 引入資料庫配置
require_once 'config.php';

try {
    // 檢查請求方法
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('只接受 POST 請求');
    }
    
    // 獲取身分證字號
    $id_number = $_POST['id_number'] ?? '';
    
    if (empty($id_number)) {
        throw new Exception('請輸入身分證字號');
    }
    
    // 身分證字號格式驗證
    if (strlen($id_number) !== 10) {
        throw new Exception('身分證字號必須為10個字符');
    }
    if (!preg_match('/^[A-Za-z][0-9]{9}$/', $id_number)) {
        throw new Exception('身分證字號格式不正確');
    }
    
    // 使用PDO連接資料庫
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 查詢錄取狀態
    $sql = "SELECT * FROM continued_admission WHERE id_number = ? ORDER BY created_at DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_number]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        echo json_encode([
            'success' => false,
            'message' => '找不到此身分證字號的報名記錄'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 解析錄取狀態
    $status = $record['status'];
    $review_notes = $record['review_notes'] ?? '';
    $reviewed_at = $record['reviewed_at'] ?? null;
    
    // 判斷是否已檢測
    $is_reviewed = !empty($reviewed_at);
    
    if ($is_reviewed) {
        // 已檢測，顯示結果
        $status_text = '';
        switch ($status) {
            case 'approved':
                $status_text = '錄取';
                break;
            case 'rejected':
                $status_text = '未錄取';
                break;
            case 'waitlist':
                $status_text = '備取';
                break;
            default:
                $status_text = '待審核';
        }
        
        echo json_encode([
            'success' => true,
            'reviewed' => true,
            'status' => $status,
            'status_text' => $status_text,
            'review_notes' => $review_notes,
            'reviewed_at' => $reviewed_at,
            'message' => "錄取結果：{$status_text}"
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // 未檢測，返回表單資料供修改
        echo json_encode([
            'success' => true,
            'reviewed' => false,
            'status' => $status,
            'message' => '尚未檢測，您可以修改報名資料',
            'form_data' => [
                'exam_no' => $record['exam_no'],
                'student_name' => $record['name'],
                'id' => $record['id_number'],  // 修正：資料庫欄位 id_number 對應表單欄位 id
                'birth_year' => $record['birth_year'],
                'birth_month' => $record['birth_month'],
                'birth_day' => $record['birth_day'],
                'gender' => $record['gender'],
                'phone' => $record['phone'],
                'mobile' => $record['mobile'],
                'school_city' => $record['school_city'],
                'school_name' => $record['school_name'],
                'zip' => $record['zip_code'],  // 修正：資料庫欄位 zip_code 對應表單欄位 zip
                'city' => $record['city'],
                'district' => $record['district'],
                'village' => $record['village'],
                'neighbor' => $record['neighbor'],
                'road' => $record['road'],
                'section' => $record['section'],
                'lane' => $record['lane'],
                'alley' => $record['alley'],
                'no' => $record['house_no'],  // 修正：資料庫欄位 house_no 對應表單欄位 no
                'floor' => $record['floor'],
                'same_address' => $record['same_address'] ? 'yes' : 'no',
                'contact_address' => $record['contact_address'],
                'guardian' => $record['guardian_name'],  // 修正：資料庫欄位 guardian_name 對應表單欄位 guardian
                'guardian_phone' => $record['guardian_phone'],
                'guardian_mobile' => $record['guardian_mobile'],
                'self_intro' => $record['self_intro'],
                'skills' => $record['skills'],
                'choices' => json_decode($record['choices'], true) ?: [],
                'documents' => json_decode($record['documents'], true) ?: []
            ]
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '資料庫錯誤: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
