<?php
header('Content-Type: application/json; charset=utf-8');

// 引入資料庫配置
require_once 'config.php';

try {
    // 檢查請求方法
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('只接受 POST 請求');
    }
    
    // 獲取身分證字號或護照號碼
    $id_number = $_POST['id_number'] ?? '';
    
    if (empty($id_number)) {
        echo json_encode([
            'success' => false,
            'message' => '請輸入身分證字號或護照號碼'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 建立資料庫連接
    $host = 'localhost';
    $dbname = 'topics_good';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 查詢記錄
    // 先嘗試直接查詢
    $sql = "SELECT * FROM continued_admission WHERE id_number = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_number]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 如果沒有找到，且輸入不是以 PASSPORT_ 開頭，嘗試加上 PASSPORT_ 前綴查詢（外籍生）
    if (!$record && strpos($id_number, 'PASSPORT_') !== 0) {
        $passport_id = 'PASSPORT_' . $id_number;
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$passport_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($record) {
            // 如果找到記錄，更新查詢的 id_number 為帶前綴的版本
            $id_number = $passport_id;
        }
    }
    
    // 如果還是沒有找到，且輸入是以 PASSPORT_ 開頭，嘗試移除前綴查詢
    if (!$record && strpos($id_number, 'PASSPORT_') === 0) {
        $passport_only = str_replace('PASSPORT_', '', $id_number);
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$passport_only]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($record) {
            // 如果找到記錄，更新查詢的 id_number 為不帶前綴的版本
            $id_number = $passport_only;
        }
    }
    
    if (!$record) {
        echo json_encode([
            'success' => false,
            'message' => '查無此身分證字號或護照號碼的報名記錄'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 判斷是否已審核（status 不是 'pending' 或 'PE' 表示已審核）
    $status = $record['status'] ?? '';
    $reviewed = !in_array($status, ['pending', 'PE', '']);
    
    // 狀態文字對應（包含更多可能的狀態值）
    $status_text_map = [
        'pending' => '待審核',
        'PE' => '待審核',
        'approved' => '已錄取',
        'rejected' => '未錄取',
        '通過' => '已錄取',
        '不通過' => '未錄取',
        '已錄取' => '已錄取',
        '未錄取' => '未錄取',
        '錄取' => '已錄取',
        'AP' => '已錄取',
        'RE' => '未錄取',
        'AD' => '備取'
    ];
    
    // 如果找不到映射，直接使用資料庫中的原始狀態值
    $status_text = $status_text_map[$status] ?? $status;
    
    // 如果狀態為空，設為待審核
    if (empty($status_text)) {
        $status_text = '待審核';
    }
    
    // 判斷顯示類型
    $display_type = 'info';
    if ($reviewed) {
        // 判斷是否為通過/錄取相關的狀態
        $approved_statuses = ['approved', '通過', '已錄取', '錄取', 'AP'];
        $rejected_statuses = ['rejected', '不通過', '未錄取', 'RE'];
        
        if (in_array($status, $approved_statuses)) {
            $display_type = 'success';
        } else if (in_array($status, $rejected_statuses)) {
            $display_type = 'error';
        } else {
            // 其他已審核狀態，預設為 info
            $display_type = 'info';
        }
    }
    
    // 準備訊息
    $message = $reviewed 
        ? "您的報名記錄已審核完成，狀態：{$status_text}"
        : "您的報名記錄目前為待審核狀態";
    
    // 解析出生日期
    $birth_date = $record['birth_date'] ?? '';
    $birth_year = '';
    $birth_month = '';
    $birth_day = '';
    if (!empty($birth_date) && $birth_date !== '0000-00-00') {
        $date_parts = explode('-', $birth_date);
        if (count($date_parts) === 3) {
            $birth_year = $date_parts[0];
            $birth_month = $date_parts[1];
            $birth_day = $date_parts[2];
        }
    }
    
    // 查詢地址資訊（從 continued_admission_addres 表）
    $zip = '';
    $city = '';
    $district = '';
    $village = '';
    $neighbor = '';
    $road = '';
    $section = '';
    $lane = '';
    $alley = '';
    $house_no = '';
    $floor = '';
    $same_address = '';
    $contact_address = '';
    
    $admission_id = $record['id'] ?? 0;
    if ($admission_id > 0) {
        try {
            $address_sql = "SELECT zip_code, address, same_address, contact_address FROM continued_admission_addres WHERE admission_id = ? LIMIT 1";
            $address_stmt = $pdo->prepare($address_sql);
            $address_stmt->execute([$admission_id]);
            $address_record = $address_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($address_record) {
                $zip = $address_record['zip_code'] ?? '';
                $full_address = $address_record['address'] ?? '';
                $same_address = ($address_record['same_address'] == 1) ? 'yes' : '';
                $contact_address = $address_record['contact_address'] ?? '';
                
                // 如果地址是完整字串，嘗試解析（但通常地址是組合字串，無法完全還原）
                // 這裡我們保留完整地址，前端可能需要特殊處理
            }
        } catch (PDOException $e) {
            // 如果地址表不存在或查詢失敗，繼續使用空值
            error_log("查詢地址資訊失敗: " . $e->getMessage());
        }
    }
    
    // 如果 continued_admission 表中有直接存儲的地址欄位，優先使用
    if (isset($record['zip_code'])) $zip = $record['zip_code'] ?? $zip;
    if (isset($record['city'])) $city = $record['city'] ?? $city;
    if (isset($record['district'])) $district = $record['district'] ?? $district;
    
    // 解析學校資訊
    $school_code = $record['school'] ?? '';
    $school_name = $record['school_name'] ?? '';
    $school_city = $record['school_city'] ?? '';
    
    // 如果沒有直接存儲的學校名稱，從 school_code 查詢
    if (empty($school_name) && !empty($school_code)) {
        $school_sql = "SELECT name, city FROM school_data WHERE school_code = ? AND is_active = 1 LIMIT 1";
        $school_stmt = $pdo->prepare($school_sql);
        $school_stmt->execute([$school_code]);
        $school_result = $school_stmt->fetch(PDO::FETCH_ASSOC);
        if ($school_result) {
            $school_name = $school_result['name'];
            $school_city = $school_result['city'] ?? $school_city;
        }
    }
    
    // 解析志願序（choices 欄位可能是 JSON 格式）
    $choices = [];
    if (!empty($record['choices'])) {
        $choices_data = json_decode($record['choices'], true);
        if (is_array($choices_data)) {
            $choices = $choices_data;
        }
    }
    
    // 解析文件（documents 欄位可能是 JSON 格式）
    $documents = [];
    if (!empty($record['documents'])) {
        $documents_data = json_decode($record['documents'], true);
        if (is_array($documents_data)) {
            $documents = $documents_data;
        }
    }
    
    // 判斷是否為外籍生
    $is_foreign_student = 'no';
    $foreign_field_name = 'is_foreign_student';
    if (isset($record[$foreign_field_name])) {
        $is_foreign_student = ($record[$foreign_field_name] == 1) ? 'yes' : 'no';
    } else if (isset($record['foreign_student'])) {
        $is_foreign_student = ($record['foreign_student'] == 1) ? 'yes' : 'no';
    } else if (strpos($id_number, 'PASSPORT_') === 0) {
        // 如果 id_number 以 PASSPORT_ 開頭，表示是外籍生
        $is_foreign_student = 'yes';
    }
    
    // 處理身分證字號（如果是外籍生，移除 PASSPORT_ 前綴）
    $id_display = $id_number;
    if ($is_foreign_student === 'yes' && strpos($id_number, 'PASSPORT_') === 0) {
        $id_display = str_replace('PASSPORT_', '', $id_number);
    }
    
    // 準備表單資料
    $form_data = [
        'exam_no' => $record['exam_no'] ?? '',
        'student_name' => $record['name'] ?? '',
        'id' => $id_display,
        'birth_year' => $birth_year,
        'birth_month' => $birth_month,
        'birth_day' => $birth_day,
        'gender' => $record['gender'] ?? '',
        'phone' => $record['phone'] ?? '',
        'mobile' => $record['mobile'] ?? '',
        'school_city' => $school_city,
        'school_name' => $school_name,
        'zip' => $zip,
        'city' => $city,
        'district' => $district,
        'village' => $village,
        'neighbor' => $neighbor,
        'road' => $road,
        'section' => $section,
        'lane' => $lane,
        'alley' => $alley,
        'no' => $house_no,
        'floor' => $floor,
        'guardian' => $record['guardian_name'] ?? '',
        'guardian_phone' => $record['guardian_phone'] ?? '',
        'guardian_mobile' => $record['guardian_mobile'] ?? '',
        'self_intro' => $record['self_intro'] ?? '',
        'skills' => $record['skills'] ?? '',
        'choices' => $choices,
        'documents' => $documents,
        'is_foreign_student' => $is_foreign_student,
        'nationality' => $record['nationality'] ?? '',
        'passport_number' => $record['passport_number'] ?? $id_display,
        'same_address' => $same_address,
        'contact_address' => $contact_address
    ];
    
    // 返回結果
    echo json_encode([
        'success' => true,
        'reviewed' => $reviewed,
        'message' => $message,
        'status_text' => $status_text,
        'review_notes' => $record['review_notes'] ?? '',
        'display_type' => $display_type,
        'form_data' => $form_data
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log("查詢錄取狀態錯誤: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '資料庫查詢錯誤，請稍後再試'
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("查詢錄取狀態錯誤: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

