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
    $email = trim((string)($_POST['email'] ?? ''));
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
    // 先創建臨時 PDO 連接用於查詢科系（稍後會重新創建用於插入）
    $temp_dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $temp_pdo = new PDO($temp_dsn, DB_USERNAME, DB_PASSWORD);
    $temp_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $choices = [];
    
    // 調試：打印所有 POST 數據中的 choice_ 開頭的字段
    $choice_fields = [];
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'choice_') === 0) {
            $choice_fields[$key] = $value;
        }
    }
    error_log("接收到的志願序字段: " . json_encode($choice_fields, JSON_UNESCAPED_UNICODE));
    
    // 從資料庫取得所有科系（從 departments 表），包含 code 和 name
    $courses_query = "SELECT code, name FROM departments ORDER BY code, name";
    $courses_stmt = $temp_pdo->query($courses_query);
    $all_courses = [];
    $course_code_to_name = []; // 科系代碼到名稱的映射
    if ($courses_stmt) {
        while ($row = $courses_stmt->fetch(PDO::FETCH_ASSOC)) {
            $all_courses[] = $row['name'];
            $course_code_to_name[$row['code']] = $row['name'];
        }
    }
    error_log("資料庫中的科系列表: " . json_encode($all_courses, JSON_UNESCAPED_UNICODE));
    
    // 建立欄位名稱到科系名稱的映射（反向映射）
    // 使用科系代碼生成字段名，與前端保持一致
    $field_to_course_map = [];
    foreach ($course_code_to_name as $code => $course_name) {
        // 使用科系代碼生成字段名稱（與前端一致）
        $field_name = 'choice_' . strtolower($code);
        $field_to_course_map[$field_name] = $course_name;
    }
    error_log("欄位名稱映射: " . json_encode($field_to_course_map, JSON_UNESCAPED_UNICODE));
    
    // 收集所有選擇的志願序
    foreach ($field_to_course_map as $field_name => $choice_name) {
        if (isset($_POST[$field_name]) && !empty($_POST[$field_name])) {
            $priority = intval($_POST[$field_name]);
            $choices[$priority] = $choice_name;
            error_log("找到志願 #{$priority}: {$choice_name} (字段: {$field_name}, 值: {$_POST[$field_name]})");
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
    error_log("志願序數量: " . count($choices));
    
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
    
    // 驗證就讀國中格式（必須從系統選項中選擇）
    if (!empty($school_name)) {
        // 檢查格式是否為：學校名稱 (縣市區)
        if (!preg_match('/^.+ \(.+\)$/', $school_name)) {
            throw new Exception('請從系統提供的選項中選擇學校，不能自行輸入');
        }
    }
    
    // 驗證出生日期
    if ($birth_year <= 0 || $birth_month <= 0 || $birth_day <= 0) {
        throw new Exception('請填寫完整的出生年月日');
    }
    
    // 驗證日期有效性
    if (!checkdate($birth_month, $birth_day, $birth_year)) {
        throw new Exception('出生日期不正確，請檢查年月日');
    }
    
    // 將出生年月日合併為 birth_date (DATE格式)
    $birth_date = sprintf('%04d-%02d-%02d', $birth_year, $birth_month, $birth_day);
    
    // 轉換性別格式：male -> 1 (男), female -> 2 (女)
    $gender_int = 1; // 預設為男
    if ($gender === 'female') {
        $gender_int = 2; // 女
    } elseif ($gender === 'male') {
        $gender_int = 1; // 男
    } else {
        // 如果已經是數字格式，直接使用
        $gender_int = intval($gender);
        if ($gender_int !== 1 && $gender_int !== 2) {
            $gender_int = 1; // 預設為男
        }
    }
    
    // 使用PDO連接資料庫
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 自動生成 apply_no (格式：年份 + 序號，例如：2025001)
    $current_year = date('Y');
    // 查詢今年最大的 apply_no
    $max_apply_no_sql = "SELECT MAX(CAST(SUBSTRING(apply_no, 5) AS UNSIGNED)) as max_num 
                         FROM continued_admission 
                         WHERE apply_no LIKE ?";
    $max_stmt = $pdo->prepare($max_apply_no_sql);
    $max_stmt->execute([$current_year . '%']);
    $max_result = $max_stmt->fetch(PDO::FETCH_ASSOC);
    $next_num = 1;
    if ($max_result && $max_result['max_num'] !== null) {
        $next_num = intval($max_result['max_num']) + 1;
    }
    // 生成 apply_no (格式：YYYY + 3位數序號，例如：2025001)
    $apply_no = $current_year . str_pad($next_num, 3, '0', STR_PAD_LEFT);
    
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
    
    // 從 school_name 中提取學校代碼（格式：學校名稱 (縣市區)）
    // 需要從 school_data 表中查找對應的 school_code
    $school_code = '';
    $school_city_actual = '';
    if (!empty($school_name)) {
        // 提取學校名稱（去除括號部分）
        $school_name_only = preg_replace('/\s*\([^)]*\)\s*$/', '', $school_name);
        // 查詢學校代碼和縣市（必須存在且不為空）
        $school_query = "SELECT school_code, city FROM school_data WHERE name = ? AND school_code IS NOT NULL AND school_code != '' AND is_active = 1 LIMIT 1";
        $school_stmt = $pdo->prepare($school_query);
        $school_stmt->execute([$school_name_only]);
        $school_result = $school_stmt->fetch(PDO::FETCH_ASSOC);
        if ($school_result && !empty($school_result['school_code'])) {
            $school_code = $school_result['school_code'];
            $school_city_actual = $school_result['city'] ?? '';
            
            // 驗證縣市與學校是否一致
            if (!empty($school_city) && !empty($school_city_actual)) {
                // 標準化縣市名稱（處理「臺」vs「台」等變體）
                $normalizeCity = function($city) {
                    if (empty($city)) return '';
                    // 處理常見變體
                    $city = str_replace('臺', '台', $city);
                    return trim($city);
                };
                
                $normalized_selected = $normalizeCity($school_city);
                $normalized_actual = $normalizeCity($school_city_actual);
                
                if ($normalized_selected !== $normalized_actual) {
                    // 縣市不一致，自動修正為學校實際所在的縣市
                    $school_city = $school_city_actual;
                    error_log("警告：用戶選擇的縣市 ({$normalized_selected}) 與學校實際所在縣市 ({$normalized_actual}) 不一致，已自動修正為 {$school_city_actual}");
                }
            }
        } else {
            // 如果找不到學校，拋出錯誤（因為外鍵約束要求有效的 school_code）
            throw new Exception("找不到學校 '{$school_name_only}' 的有效代碼，請從系統提供的選項中選擇學校");
        }
    } else {
        // 如果學校名稱為空，檢查是否為外籍生
        if ($is_foreign_student !== 'yes') {
            // 本國籍：就讀國中為必填
            throw new Exception('就讀國中為必填欄位，請填寫');
        }
        // 外籍生：就讀國中不是必填，允許為空
        // 將 school_code 設為空字符串，在 INSERT 時不包含該欄位
        $school_code = '';
        $school_city = ''; // 外籍生時，就讀縣市也為空
    }
    
    // 組合完整地址字符串
    $address_parts = [];
    if (!empty($city)) $address_parts[] = $city;
    if (!empty($district)) $address_parts[] = $district;
    if (!empty($village)) $address_parts[] = $village;
    if (!empty($neighbor)) $address_parts[] = $neighbor . '鄰';
    if (!empty($road)) $address_parts[] = $road;
    if (!empty($section)) $address_parts[] = $section . '段';
    if (!empty($lane)) $address_parts[] = $lane . '巷';
    if (!empty($alley)) $address_parts[] = $alley . '弄';
    if (!empty($house_no)) $address_parts[] = $house_no . '號';
    if (!empty($floor)) $address_parts[] = $floor;
    $full_address = implode('', $address_parts);
    
    // 檢查資料表結構，判斷是否有新欄位
    $columns_check = $pdo->query("SHOW COLUMNS FROM continued_admission");
    $columns = $columns_check->fetchAll(PDO::FETCH_COLUMN);
    // 若無 email 欄位則新增（供續招委員會第三步驟寄送錄取通知使用）
    if (!in_array('email', $columns)) {
        $pdo->exec("ALTER TABLE continued_admission ADD COLUMN email VARCHAR(255) DEFAULT NULL");
        $columns[] = 'email';
    }
    $has_email = in_array('email', $columns);
    // 檢查是否有外籍生欄位（可能是 is_foreign_student 或 foreign_student）
    $has_foreign_fields = in_array('is_foreign_student', $columns) || in_array('foreign_student', $columns);
    $foreign_field_name = in_array('is_foreign_student', $columns) ? 'is_foreign_student' : 'foreign_student';
    $has_nationality = in_array('nationality', $columns);
    $has_passport_number = in_array('passport_number', $columns);
    $has_birth_date = in_array('birth_date', $columns);
    $has_apply_no = in_array('apply_no', $columns);
    
    if ($has_foreign_fields && $has_birth_date && $has_apply_no) {
        // 如果資料表有所有新欄位，使用完整欄位列表
        // 動態構建 SQL，如果 school_code 為空（外籍生），則不包含 school 欄位
        if (!empty($school_code)) {
            // 根據實際欄位名稱動態構建 SQL
            $fields = ['apply_no', 'exam_no', 'name', 'id_number', $foreign_field_name];
            $values = [$apply_no, $exam_no, $name, $id_number, $is_foreign_int];
            
            // 如果有 nationality 和 passport_number 欄位，則包含它們
            if ($has_nationality) {
                $fields[] = 'nationality';
                $values[] = $nationality;
            }
            if ($has_passport_number) {
                $fields[] = 'passport_number';
                $values[] = $passport_number;
            }
            
            $fields = array_merge($fields, ['birth_date', 'gender', 'phone', 'mobile']);
            $values = array_merge($values, [$birth_date, $gender_int, $phone, $mobile]);
            if ($has_email) {
                $fields[] = 'email';
                $values[] = $email === '' ? null : $email;
            }
            $fields = array_merge($fields, ['school',
                'guardian_name', 'guardian_phone', 'guardian_mobile', 'documents', 'self_intro', 'skills', 
                'status', 'reviewer_id', 'review_notes', 'reviewed_at', 'updated_at']);
            $values = array_merge($values, [
                $school_code,
                $guardian_name, $guardian_phone, $guardian_mobile, $documents_json, $self_intro, $skills, 
                'PE', 0, '', '0000-00-00 00:00:00', date('Y-m-d H:i:s')
            ]);
            
            $placeholders = str_repeat('?, ', count($fields) - 1) . '?';
            $sql = "INSERT INTO continued_admission (" . implode(', ', $fields) . ") VALUES ({$placeholders})";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($values);
        } else {
            // 外籍生且學校為空時，使用 NULL 值（需要資料庫允許 NULL）
            // 如果資料庫不允許 NULL，請執行 scripts/database/allow_null_school_for_foreign_students.sql
            // 根據實際欄位名稱動態構建 SQL
            $fields = ['apply_no', 'exam_no', 'name', 'id_number', $foreign_field_name];
            $values = [$apply_no, $exam_no, $name, $id_number, $is_foreign_int];
            
            // 如果有 nationality 和 passport_number 欄位，則包含它們
            if ($has_nationality) {
                $fields[] = 'nationality';
                $values[] = $nationality;
            }
            if ($has_passport_number) {
                $fields[] = 'passport_number';
                $values[] = $passport_number;
            }
            
            $fields = array_merge($fields, ['birth_date', 'gender', 'phone', 'mobile']);
            $values = array_merge($values, [$birth_date, $gender_int, $phone, $mobile]);
            if ($has_email) {
                $fields[] = 'email';
                $values[] = $email === '' ? null : $email;
            }
            $fields = array_merge($fields, ['school',
                'guardian_name', 'guardian_phone', 'guardian_mobile', 'documents', 'self_intro', 'skills', 
                'status', 'reviewer_id', 'review_notes', 'reviewed_at', 'updated_at']);
            $values = array_merge($values, [
                null, // school 設為 NULL
                $guardian_name, $guardian_phone, $guardian_mobile, $documents_json, $self_intro, $skills, 
                'PE', 0, '', '0000-00-00 00:00:00', date('Y-m-d H:i:s')
            ]);
            
            $placeholders = str_repeat('?, ', count($fields) - 1) . '?';
            $sql = "INSERT INTO continued_admission (" . implode(', ', $fields) . ") VALUES ({$placeholders})";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($values);
        }
    } elseif ($has_birth_date && $has_apply_no) {
        // 如果資料表有 birth_date 和 apply_no，但沒有外籍生欄位
        // 動態構建 SQL，如果 school_code 為空（外籍生），則不包含 school 欄位
        $emailVal = ($has_email && $email !== '') ? $email : null;
        if (!empty($school_code)) {
            if ($has_email) {
                $sql = "INSERT INTO continued_admission (
                    apply_no, exam_no, name, id_number,
                    birth_date, gender, phone, mobile, email, school,
                    guardian_name, guardian_phone, guardian_mobile, documents, self_intro, skills, status, reviewer_id, review_notes, reviewed_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    $apply_no, $exam_no, $name, $id_number,
                    $birth_date, $gender_int, $phone, $mobile, $emailVal, $school_code,
                    $guardian_name, $guardian_phone, $guardian_mobile, $documents_json, $self_intro, $skills,
                    'PE', 0, '', '0000-00-00 00:00:00', date('Y-m-d H:i:s')
                ]);
            } else {
                $sql = "INSERT INTO continued_admission (
                    apply_no, exam_no, name, id_number,
                    birth_date, gender, phone, mobile, school,
                    guardian_name, guardian_phone, guardian_mobile, documents, self_intro, skills, status, reviewer_id, review_notes, reviewed_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    $apply_no, $exam_no, $name, $id_number,
                    $birth_date, $gender_int, $phone, $mobile, $school_code,
                    $guardian_name, $guardian_phone, $guardian_mobile, $documents_json, $self_intro, $skills,
                    'PE', 0, '', '0000-00-00 00:00:00', date('Y-m-d H:i:s')
                ]);
            }
        } else {
            // 外籍生且學校為空時，使用 NULL 值（需要資料庫允許 NULL）
            // 如果資料庫不允許 NULL，請執行 scripts/database/allow_null_school_for_foreign_students.sql
            if ($has_email) {
                $sql = "INSERT INTO continued_admission (
                    apply_no, exam_no, name, id_number,
                    birth_date, gender, phone, mobile, email, school,
                    guardian_name, guardian_phone, guardian_mobile, documents, self_intro, skills, status, reviewer_id, review_notes, reviewed_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    $apply_no, $exam_no, $name, $id_number,
                    $birth_date, $gender_int, $phone, $mobile, $emailVal, null,
                    $guardian_name, $guardian_phone, $guardian_mobile, $documents_json, $self_intro, $skills,
                    'PE', 0, '', '0000-00-00 00:00:00', date('Y-m-d H:i:s')
                ]);
            } else {
                $sql = "INSERT INTO continued_admission (
                    apply_no, exam_no, name, id_number,
                    birth_date, gender, phone, mobile, school,
                    guardian_name, guardian_phone, guardian_mobile, documents, self_intro, skills, status, reviewer_id, review_notes, reviewed_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    $apply_no, $exam_no, $name, $id_number,
                    $birth_date, $gender_int, $phone, $mobile, null,
                    $guardian_name, $guardian_phone, $guardian_mobile, $documents_json, $self_intro, $skills,
                    'PE', 0, '', '0000-00-00 00:00:00', date('Y-m-d H:i:s')
                ]);
            }
        }
    } else {
        // 向後兼容：如果資料表沒有新欄位，使用舊的欄位列表（若有 email 欄位則一併寫入）
        if ($has_email) {
            $sql = "INSERT INTO continued_admission (
                exam_no, name, id_number, birth_year, birth_month, birth_day, gender, phone, mobile, email,
                school_city, school_name,
                guardian_name, guardian_phone, guardian_mobile, documents, self_intro, skills, choices
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $exam_no, $name, $id_number, $birth_year, $birth_month, $birth_day, $gender_int, $phone, $mobile, $email === '' ? null : $email,
                $school_city, $school_name,
                $guardian_name, $guardian_phone, $guardian_mobile, $documents_json, $self_intro, $skills, $choices_json
            ]);
        } else {
            $sql = "INSERT INTO continued_admission (
                exam_no, name, id_number, birth_year, birth_month, birth_day, gender, phone, mobile,
                school_city, school_name,
                guardian_name, guardian_phone, guardian_mobile, documents, self_intro, skills, choices
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $exam_no, $name, $id_number, $birth_year, $birth_month, $birth_day, $gender_int, $phone, $mobile,
                $school_city, $school_name,
                $guardian_name, $guardian_phone, $guardian_mobile, $documents_json, $self_intro, $skills, $choices_json
            ]);
        }
    }
    
    if ($result) {
        $insert_id = $pdo->lastInsertId();
        error_log("Successfully inserted record with ID: " . $insert_id . " for name: " . $name . ", apply_no: " . $apply_no);
        
        // 插入地址信息到 continued_admission_addres 表
        try {
            $address_sql = "INSERT INTO continued_admission_addres (admission_id, zip_code, address, same_address, contact_address) VALUES (?, ?, ?, ?, ?)";
            $address_stmt = $pdo->prepare($address_sql);
            $address_result = $address_stmt->execute([
                $insert_id,
                $zip_code,
                $full_address,
                $same_address_int,
                $contact_address
            ]);
            
            if ($address_result) {
                error_log("成功插入地址信息到 continued_admission_addres 表，admission_id: " . $insert_id);
            } else {
                error_log("警告：插入地址信息失敗，admission_id: " . $insert_id);
            }
        } catch (PDOException $e) {
            error_log("插入地址信息時發生錯誤: " . $e->getMessage());
            // 地址插入失敗不影響主流程，只記錄錯誤
        }
        
        // 插入志願序到 continued_admission_choices 表
        if (!empty($choices) && is_array($choices)) {
            error_log("開始插入志願序，共 " . count($choices) . " 個志願，application_id: " . $insert_id);
            
            try {
                // 準備插入志願序的 SQL
                $choice_sql = "INSERT INTO continued_admission_choices (application_id, choice_order, department_code) VALUES (?, ?, ?)";
                $choice_stmt = $pdo->prepare($choice_sql);
                
                // 準備查詢科系代碼的 SQL
                $dept_sql = "SELECT code FROM departments WHERE name = ? LIMIT 1";
                $dept_stmt = $pdo->prepare($dept_sql);
                
                $choice_insert_count = 0;
                $choice_errors = [];
                
                foreach ($choices as $index => $choice_name) {
                    $choice_order = $index + 1; // 志願順序從1開始
                    
                    try {
                        // 查詢科系代碼
                        $dept_stmt->execute([$choice_name]);
                        $dept_result = $dept_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($dept_result && isset($dept_result['code'])) {
                            $department_code = $dept_result['code'];
                            
                            // 插入志願序記錄
                            $choice_result = $choice_stmt->execute([$insert_id, $choice_order, $department_code]);
                            
                            if ($choice_result) {
                                $choice_insert_count++;
                                error_log("成功插入志願序 #{$choice_order}: {$choice_name} (代碼: {$department_code}, application_id: {$insert_id})");
                            } else {
                                $error_info = $choice_stmt->errorInfo();
                                $error_msg = "插入志願序失敗 #{$choice_order}: {$choice_name} - " . ($error_info[2] ?? '未知錯誤');
                                error_log($error_msg);
                                $choice_errors[] = $error_msg;
                            }
                        } else {
                            $error_msg = "警告：找不到科系 '{$choice_name}' 的代碼，跳過此志願";
                            error_log($error_msg);
                            $choice_errors[] = $error_msg;
                        }
                    } catch (PDOException $e) {
                        $error_msg = "插入志願序 #{$choice_order} 時發生錯誤: " . $e->getMessage();
                        error_log($error_msg);
                        $choice_errors[] = $error_msg;
                    }
                }
                
                error_log("志願序插入完成，成功插入 {$choice_insert_count} / " . count($choices) . " 個志願");
                if (!empty($choice_errors)) {
                    error_log("志願序插入錯誤: " . implode('; ', $choice_errors));
                }
                
                // 自動分配給每個志願的主任並發送郵件通知
                try {
                    // 載入續招報名通知函數
                    $notification_path = __DIR__ . '/includes/continued_admission_notification_functions.php';
                    if (file_exists($notification_path)) {
                        require_once $notification_path;
                        
                        // 準備學生資料
                        $student_data = [
                            'name' => $name,
                            'apply_no' => $apply_no,
                            'phone' => $phone,
                            'mobile' => $mobile,
                            'email' => '' // 續招報名表單可能沒有email欄位
                        ];
                        
                        // 查詢所有已插入的志願序
                        $choices_query = $pdo->prepare("
                            SELECT choice_order, department_code 
                            FROM continued_admission_choices 
                            WHERE application_id = ? 
                            ORDER BY choice_order ASC
                        ");
                        $choices_query->execute([$insert_id]);
                        $inserted_choices = $choices_query->fetchAll(PDO::FETCH_ASSOC);
                        
                        // 為每個志願分配給對應科系的主任並發送郵件
                        foreach ($inserted_choices as $choice) {
                            $dept_code = $choice['department_code'];
                            $choice_order = $choice['choice_order'];
                            
                            // 更新 assigned_department（使用第一志願的科系）
                            if ($choice_order == 1) {
                                try {
                                    $update_stmt = $pdo->prepare("UPDATE continued_admission SET assigned_department = ? WHERE id = ?");
                                    $update_stmt->execute([$dept_code, $insert_id]);
                                    error_log("已自動分配第一志願科系 {$dept_code} 給報名 ID {$insert_id}");
                                } catch (PDOException $e) {
                                    error_log("更新 assigned_department 失敗: " . $e->getMessage());
                                }
                            }
                            
                            // 發送郵件通知給該科系的主任
                            try {
                                error_log("開始發送續招報名通知郵件: 科系={$dept_code}, 志願順序={$choice_order}, 報名ID={$insert_id}");
                                $email_sent = sendContinuedAdmissionDirectorNotification($pdo, $dept_code, $choice_order, $student_data, $insert_id);
                                if ($email_sent) {
                                    error_log("✅ 續招報名通知郵件發送成功: 科系={$dept_code}, 志願順序={$choice_order}");
                                } else {
                                    error_log("❌ 續招報名通知郵件發送失敗: 科系={$dept_code}, 志願順序={$choice_order} (請檢查錯誤日誌)");
                                }
                            } catch (Exception $e) {
                                error_log("❌ 發送續招報名通知郵件時發生異常: " . $e->getMessage());
                                // 不影響主流程，繼續執行
                            }
                        }
                    } else {
                        error_log("❌ 找不到續招報名通知函數文件: $notification_path");
                    }
                } catch (Exception $e) {
                    error_log("❌ 自動分配和發送郵件時發生異常: " . $e->getMessage());
                    // 不影響主流程，繼續執行
                }
            } catch (PDOException $e) {
                error_log("插入志願序時發生嚴重錯誤: " . $e->getMessage());
                error_log("錯誤代碼: " . $e->getCode());
            }
        } else {
            error_log("警告：志願序為空或不是數組，跳過志願序插入");
            error_log("choices 變量內容: " . var_export($choices, true));
        }
        
        echo json_encode([
            'success' => true,
            'message' => '報名成功！您的報名編號是: ' . $apply_no,
            'operation' => 'insert',
            'insert_id' => $insert_id,
            'apply_no' => $apply_no
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
