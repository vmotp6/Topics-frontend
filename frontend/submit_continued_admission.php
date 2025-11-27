<?php
header('Content-Type: application/json; charset=utf-8');

// 引入資料庫配置
require_once 'config.php';

try {
    // 添加請求日誌
    $request_id = $_POST['request_id'] ?? 'unknown';
    error_log("Submit request received at " . date('Y-m-d H:i:s') . " - Method: " . $_SERVER['REQUEST_METHOD'] . " - Request ID: " . $request_id);
    error_log("Request data: " . json_encode($_POST));
    
    // 檢查請求方法
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('只接受 POST 請求');
    }
    
    // 獲取表單數據
    $exam_no = $_POST['exam_no'] ?? '';
    $name = $_POST['name'] ?? $_POST['student_name'] ?? '';
    $is_foreign_student = $_POST['is_foreign_student'] ?? 'no';
    $id_number = $_POST['id'] ?? '';
    $nationality = $_POST['nationality'] ?? '';
    $passport_number = $_POST['passport_number'] ?? '';
    $birth_year = intval($_POST['birth_year'] ?? 0);
    $birth_month = intval($_POST['birth_month'] ?? 0);
    $birth_day = intval($_POST['birth_day'] ?? 0);
    $gender = $_POST['gender'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $school_city = $_POST['school_city'] ?? '';
    $school_name = $_POST['school_name'] ?? '';
    $zip_code = $_POST['zip'] ?? '';
    $city = $_POST['city'] ?? '';
    $district = $_POST['district'] ?? '';
    $village = $_POST['village'] ?? '';
    $neighbor = $_POST['neighbor'] ?? '';
    $road = $_POST['road'] ?? '';
    $section = $_POST['section'] ?? '';
    $lane = $_POST['lane'] ?? '';
    $alley = $_POST['alley'] ?? '';
    $house_no = $_POST['no'] ?? '';
    $floor = $_POST['floor'] ?? '';
    $same_address = $_POST['same_address'] ?? '';
    $contact_address = $_POST['contact_address'] ?? '';
    $guardian_name = $_POST['guardian'] ?? '';
    $guardian_phone = $_POST['guardian_phone'] ?? '';
    $guardian_mobile = $_POST['guardian_mobile'] ?? '';
    $self_intro = $_POST['self_intro'] ?? '';
    $skills = $_POST['skills'] ?? '';
    
    // 處理志願序 - 從資料庫讀取科系映射
    $conn = getDatabaseConnection();
    $choices = [];
    
    // 從資料庫取得所有啟用的科系
    $courses_query = "SELECT course_name FROM admission_courses WHERE is_active = 1 ORDER BY sort_order, course_name";
    $courses_result = $conn->query($courses_query);
    $all_courses = [];
    if ($courses_result) {
        while ($row = $courses_result->fetch_assoc()) {
            $all_courses[] = $row['course_name'];
        }
    }
    
    // 建立欄位名稱到科系名稱的映射（反向映射）
    $field_to_course_map = [];
    foreach ($all_courses as $course_name) {
        // 將科系名稱轉換為欄位名稱（與前端一致）
        $field_name = 'choice_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $course_name));
        $field_to_course_map[$field_name] = $course_name;
    }
    
    // 收集所有選擇的志願序
    foreach ($field_to_course_map as $field_name => $choice_name) {
        if (isset($_POST[$field_name]) && !empty($_POST[$field_name])) {
            $priority = intval($_POST[$field_name]);
            $choices[$priority] = $choice_name;
        }
    }
    
    // 按優先順序排序
    ksort($choices);
    $choices = array_values($choices); // 重新索引數組
    
    $choices_json = json_encode($choices, JSON_UNESCAPED_UNICODE);
    $same_address_int = ($same_address === 'yes') ? 1 : 0;
    
    // 調試日誌：記錄志願序數據
    error_log("志願序處理結果: " . $choices_json);
    error_log("志願序數組: " . print_r($choices, true));
    
    // 驗證必填欄位
    if (empty($name)) {
        throw new Exception('姓名為必填欄位');
    }
    
    // 根據是否外籍生驗證不同的身份識別欄位
    if ($is_foreign_student === 'yes') {
        // 外籍生：驗證國籍和護照號碼
        if (empty($nationality)) {
            throw new Exception('國籍為必填欄位');
        }
        if (empty($passport_number)) {
            throw new Exception('護照號碼為必填欄位');
        }
        if (strlen($passport_number) < 6 || strlen($passport_number) > 20) {
            throw new Exception('護照號碼長度應為6-20個字符');
        }
        // 外籍生使用護照號碼作為唯一識別，將護照號碼存入id_number欄位（或使用passport_number）
        // 為了兼容現有資料庫結構，我們可以將護照號碼存入id_number欄位，並在資料庫中標記為外籍生
        $id_number = 'PASSPORT_' . $passport_number; // 加上前綴以區分
    } else {
        // 本國籍：驗證身分證字號
        if (empty($id_number)) {
            throw new Exception('身分證字號為必填欄位');
        }
        if (strlen($id_number) !== 10) {
            throw new Exception('身分證字號必須為10個字符');
        }
        if (!preg_match('/^[A-Za-z][0-9]{9}$/', $id_number)) {
            throw new Exception('身分證字號格式不正確，第一個字符必須是英文字母，後面9個字符必須是數字');
        }
    }
    
    if (empty($mobile) && empty($phone)) {
        throw new Exception('請填寫手機號碼或電話號碼');
    }
    
    // 驗證行動電話格式（10個數字）
    if (!empty($mobile) && !preg_match('/^[0-9]{10}$/', $mobile)) {
        throw new Exception('行動電話必須為10個數字');
    }
    
    // 使用PDO連接資料庫
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 移除身分證字號重複檢查，允許相同身分證字號報名
    
    // 處理文件上傳
    $uploaded_documents = [];
    $upload_dir = 'uploads/continued_admission/';
    
    // 確保上傳目錄存在
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // 處理各種文件上傳
    $file_fields = [
        'doc_exam' => 'exam',
        'doc_skill' => 'skill', 
        'doc_leader' => 'leader',
        'doc_service' => 'service',
        'doc_fitness' => 'fitness',
        'doc_contest' => 'contest',
        'doc_other' => 'other'
    ];
    
    foreach ($file_fields as $field_name => $doc_type) {
        if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$field_name];
            
            // 檢查文件大小（限制為 5MB）
            if ($file['size'] > 5 * 1024 * 1024) {
                throw new Exception("文件 {$field_name} 大小超過 5MB 限制");
            }
            
            // 檢查文件類型
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file_extension, $allowed_types)) {
                throw new Exception("文件 {$field_name} 類型不允許，只允許: " . implode(', ', $allowed_types));
            }
            
            // 生成唯一文件名
            $unique_filename = time() . '_' . uniqid() . '_' . $file['name'];
            $upload_path = $upload_dir . $unique_filename;
            
            // 移動文件
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $uploaded_documents[] = [
                    'type' => $doc_type,
                    'filename' => $file['name'],
                    'path' => $upload_path
                ];
            } else {
                throw new Exception("文件 {$field_name} 上傳失敗");
            }
        }
    }
    
    // 將上傳的文件信息轉換為 JSON
    $documents_json = json_encode($uploaded_documents, JSON_UNESCAPED_UNICODE);
    
    // 檢查是否已存在記錄（根據身分證字號或護照號碼）
    // 注意：外籍生的護照號碼會加上PASSPORT_前綴存入id_number欄位
    $check_sql = "SELECT id, name FROM continued_admission WHERE id_number = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$id_number]);
    $existing_record = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_record) {
        // 如果已存在記錄，返回錯誤提示
        if ($is_foreign_student === 'yes') {
            throw new Exception('此護照號碼已被使用過，無法重複報名。如有疑問，請聯繫相關單位。');
        } else {
            throw new Exception('此身分證字號已被使用過，無法重複報名。如有疑問，請聯繫相關單位。');
        }
    }
    
    // 準備外籍生相關數據
    $is_foreign_int = ($is_foreign_student === 'yes') ? 1 : 0;
    
    // 檢查資料表是否有外籍生相關欄位（動態構建SQL）
    $columns_check = $pdo->query("SHOW COLUMNS FROM continued_admission LIKE 'is_foreign_student'");
    $has_foreign_fields = $columns_check->rowCount() > 0;
    
    if ($has_foreign_fields) {
        // 如果資料表有外籍生欄位，使用完整欄位列表
        $sql = "INSERT INTO continued_admission (
            exam_no, name, id_number, is_foreign_student, nationality, passport_number,
            birth_year, birth_month, birth_day, gender, phone, mobile,
            school_city, school_name, zip_code, city, district, village, neighbor,
            road, section, lane, alley, house_no, floor, same_address, contact_address,
            guardian_name, guardian_phone, guardian_mobile, documents, self_intro, skills, choices
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        
        // 執行插入（包含外籍生欄位）
        $result = $stmt->execute([
            $exam_no, $name, $id_number, $is_foreign_int, $nationality, $passport_number,
            $birth_year, $birth_month, $birth_day, $gender, $phone, $mobile,
            $school_city, $school_name, $zip_code, $city, $district, $village, $neighbor,
            $road, $section, $lane, $alley, $house_no, $floor, $same_address_int, $contact_address,
            $guardian_name, $guardian_phone, $guardian_mobile, $documents_json, $self_intro, $skills, $choices_json
        ]);
    } else {
        // 如果資料表沒有外籍生欄位，使用原有欄位列表（向後兼容）
        // 外籍生的護照號碼會存入id_number欄位（格式：PASSPORT_XXXXX）
        $sql = "INSERT INTO continued_admission (
            exam_no, name, id_number, birth_year, birth_month, birth_day, gender, phone, mobile,
            school_city, school_name, zip_code, city, district, village, neighbor,
            road, section, lane, alley, house_no, floor, same_address, contact_address,
            guardian_name, guardian_phone, guardian_mobile, documents, self_intro, skills, choices
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        
        // 執行插入（不包含外籍生欄位，但護照號碼已存入id_number）
        $result = $stmt->execute([
            $exam_no, $name, $id_number, $birth_year, $birth_month, $birth_day, $gender, $phone, $mobile,
            $school_city, $school_name, $zip_code, $city, $district, $village, $neighbor,
            $road, $section, $lane, $alley, $house_no, $floor, $same_address_int, $contact_address,
            $guardian_name, $guardian_phone, $guardian_mobile, $documents_json, $self_intro, $skills, $choices_json
        ]);
    }
    
    if ($result) {
        $insert_id = $pdo->lastInsertId();
        error_log("Successfully inserted record with ID: " . $insert_id . " for name: " . $name);
        
        echo json_encode([
            'success' => true,
            'message' => '報名成功！您的報名編號是: ' . $insert_id,
            'operation' => 'insert',
            'insert_id' => $insert_id
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('報名失敗，請稍後再試');
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage() . " - Request ID: " . ($request_id ?? 'unknown'));
    
    // 檢查是否為重複鍵錯誤
    if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false) {
        // 提取重複的身分證字號
        if (preg_match("/Duplicate entry '([^']+)' for key 'id_number'/", $e->getMessage(), $matches)) {
            $duplicate_id = $matches[1];
            echo json_encode([
                'success' => false,
                'message' => "此身分證字號 '{$duplicate_id}' 已經報名過了，請檢查是否重複提交或使用其他身分證字號"
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => false,
                'message' => '此身分證字號已經報名過了，請檢查是否重複提交或使用其他身分證字號'
            ], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => '資料庫錯誤: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
