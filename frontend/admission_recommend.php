<?php
// 設定時區為台灣時區 (UTC+8)
date_default_timezone_set('Asia/Taipei');

// 載入 session 配置
require_once 'session_config.php';
require_once 'config.php';
require_once 'config/email_notification_config.php';

$message = '';
$messageType = '';
$departments = []; // 科系資料 (code => name)
$grades = []; // 年級資料 (code => name)
$search_results = [];
$search_student_id = '';

// 從資料庫撈取科系資料 (departments 表)
// 推薦人科系和被推薦人科系（興趣）都關聯 departments.code
try {
    $conn = getDatabaseConnection();
    $sql = "SELECT code, name FROM departments ORDER BY name";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $departments[$row['code']] = $row['name'];
        }
    }
    $conn->close();
} catch (Exception $e) {
    // 如果資料庫查詢失敗，使用預設科系
    $departments = ['IM' => '資訊管理科', 'OPT' => '視光科', 'ECCE' => '嬰幼兒保育科'];
    error_log("無法從資料庫撈取科系資料: " . $e->getMessage());
}

// 從資料庫撈取年級資料 (identity_options 表)
// 推薦人年級：只取專科年級 (F1-F5)
// 被推薦學生年級：國三 (J3) 和已畢業
try {
    $conn = getDatabaseConnection();
    // 只取專科年級 (F1-F5) 和國三 (J3)
    $sql = "SELECT code, name FROM identity_options WHERE code IN ('F1', 'F2', 'F3', 'F4', 'F5', 'J3') ORDER BY code";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $grades[$row['code']] = $row['name'];
        }
    }
    // 添加「已畢業」選項（不在 identity_options 表中）
    $grades['GRADUATED'] = '已畢業';
    $conn->close();
} catch (Exception $e) {
    // 如果資料庫查詢失敗，使用預設年級
    $grades = ['F1' => '專一', 'F2' => '專二', 'F3' => '專三', 'F4' => '專四', 'F5' => '專五', 'J3' => '國三', 'GRADUATED' => '已畢業'];
    error_log("無法從資料庫撈取年級資料: " . $e->getMessage());
}

// 處理通過 ID 查詢（用於後台管理系統）
$single_detail = null; // 用於儲存單筆詳細記錄
if (isset($_GET['id']) && !empty($_GET['id'])) {
    try {
        $search_id = intval($_GET['id']);
        $conn = getDatabaseConnection();
        
        // 檢查表結構，決定使用哪種查詢方式
        $has_recommender_table = false;
        $has_recommended_table = false;
        $table_check = $conn->query("SHOW TABLES LIKE 'recommender'");
        if ($table_check && $table_check->num_rows > 0) {
            $has_recommender_table = true;
        }
        $table_check = $conn->query("SHOW TABLES LIKE 'recommended'");
        if ($table_check && $table_check->num_rows > 0) {
            $has_recommended_table = true;
        }
        
        if ($has_recommender_table && $has_recommended_table) {
            // 使用新的表結構：recommender 和 recommended 表
            $sql = "SELECT 
                ar.*,
                rec.name as recommender_name,
                rec.id as recommender_student_id,
                rec.phone as recommender_phone,
                rec.email as recommender_email,
                rec.grade as recommender_grade_code,
                rec.department as recommender_department_code,
                rg.name as recommender_grade_name,
                rd.name as recommender_department_name,
                red.name as student_name,
                red.phone as student_phone,
                red.email as student_email,
                red.line_id as student_line_id,
                red.grade as student_grade_code,
                red.school as student_school_code,
                sg.name as student_grade_name,
                sd.name as student_school_name,
                si.name as student_interest_name
            FROM admission_recommendations ar
            LEFT JOIN recommender rec ON ar.id = rec.recommendations_id
            LEFT JOIN recommended red ON ar.id = red.recommendations_id
            LEFT JOIN identity_options rg ON rec.grade = rg.code
            LEFT JOIN departments rd ON rec.department = rd.code
            LEFT JOIN identity_options sg ON red.grade = sg.code
            LEFT JOIN school_data sd ON red.school = sd.school_code
            LEFT JOIN departments si ON ar.student_interest = si.code
            WHERE ar.id = ?";
        } else {
            // 使用舊的表結構：所有資料都在 admission_recommendations 表中
            $sql = "SELECT 
                ar.*,
                rg.name as recommender_grade_name,
                rd.name as recommender_department_name,
                sg.name as student_grade_name,
                si.name as student_interest_name,
                sd.name as student_school_name
            FROM admission_recommendations ar
            LEFT JOIN identity_options rg ON ar.recommender_grade_code = rg.code
            LEFT JOIN departments rd ON ar.recommender_department_code = rd.code
            LEFT JOIN identity_options sg ON ar.student_grade_code = sg.code
            LEFT JOIN departments si ON ar.student_interest_code = si.code
            LEFT JOIN school_data sd ON ar.student_school_code = sd.school_code
            WHERE ar.id = ?";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $search_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $single_detail = $result->fetch_assoc(); // 儲存單筆記錄
            $search_results[] = $single_detail; // 同時加入搜尋結果陣列
            $message = "找到推薦記錄";
            $messageType = "success";
        } else {
            $message = "未找到 ID 為 " . htmlspecialchars($search_id) . " 的推薦記錄";
            $messageType = "error";
        }
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        $message = "查詢失敗：" . $e->getMessage();
        $messageType = "error";
    }
}

// 處理學號查詢
if ($_POST && isset($_POST['search_action']) && $_POST['search_action'] === 'search') {
    try {
        $search_student_id = trim($_POST['search_student_id']);
        if (!empty($search_student_id)) {
            $conn = getDatabaseConnection();
            
            // 檢查表結構，決定使用哪種查詢方式
            $has_recommender_table = false;
            $has_recommended_table = false;
            $table_check = $conn->query("SHOW TABLES LIKE 'recommender'");
            if ($table_check && $table_check->num_rows > 0) {
                $has_recommender_table = true;
            }
            $table_check = $conn->query("SHOW TABLES LIKE 'recommended'");
            if ($table_check && $table_check->num_rows > 0) {
                $has_recommended_table = true;
            }
            
            if ($has_recommender_table && $has_recommended_table) {
                // 使用新的表結構：從 recommender 表查詢推薦人學號
                $sql = "SELECT 
                    ar.*,
                    rec.name as recommender_name,
                    rec.id as recommender_student_id,
                    rec.phone as recommender_phone,
                    rec.email as recommender_email,
                    rec.grade as recommender_grade_code,
                    rec.department as recommender_department_code,
                    rg.name as recommender_grade_name,
                    rd.name as recommender_department_name,
                    red.name as student_name,
                    red.phone as student_phone,
                    red.email as student_email,
                    red.line_id as student_line_id,
                    red.grade as student_grade_code,
                    red.school as student_school_code,
                    sg.name as student_grade_name,
                    sd.name as student_school_name,
                    si.name as student_interest_name
                FROM admission_recommendations ar
                LEFT JOIN recommender rec ON ar.id = rec.recommendations_id
                LEFT JOIN recommended red ON ar.id = red.recommendations_id
                LEFT JOIN identity_options rg ON rec.grade = rg.code
                LEFT JOIN departments rd ON rec.department = rd.code
                LEFT JOIN identity_options sg ON red.grade = sg.code
                LEFT JOIN school_data sd ON red.school = sd.school_code
                LEFT JOIN departments si ON ar.student_interest = si.code
                WHERE rec.id = ? ORDER BY ar.created_at DESC";
            } else {
                // 使用舊的表結構：從 admission_recommendations 表查詢
                $sql = "SELECT 
                    ar.*,
                    rg.name as recommender_grade_name,
                    rd.name as recommender_department_name,
                    sg.name as student_grade_name,
                    si.name as student_interest_name,
                    sd.name as student_school_name
                FROM admission_recommendations ar
                LEFT JOIN identity_options rg ON ar.recommender_grade_code = rg.code
                LEFT JOIN departments rd ON ar.recommender_department_code = rd.code
                LEFT JOIN identity_options sg ON ar.student_grade_code = sg.code
                LEFT JOIN departments si ON ar.student_interest_code = si.code
                LEFT JOIN school_data sd ON ar.student_school_code = sd.school_code
                WHERE ar.recommender_student_id = ? ORDER BY ar.created_at DESC";
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $search_student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $search_results[] = $row;
                }
                $message = "找到 " . count($search_results) . " 筆推薦記錄";
                $messageType = "success";
            } else {
                $message = "未找到學號或教師編號 " . htmlspecialchars($search_student_id) . " 的推薦記錄";
                $messageType = "error";
            }
            $stmt->close();
            $conn->close();
        } else {
            $message = "請輸入學號";
            $messageType = "error";
        }
    } catch (Exception $e) {
        $message = "查詢失敗：" . $e->getMessage();
        $messageType = "error";
    }
}

// 處理表單提交
if ($_POST && isset($_POST['submit_recommendation'])) {
    try {
        $conn = getDatabaseConnection();
        
        // 檢查資料表是否存在（不再自動添加欄位，只使用現有的表結構）
        $table_check = $conn->query("SHOW TABLES LIKE 'admission_recommendations'");
        if (!$table_check || $table_check->num_rows == 0) {
            throw new Exception("資料表 'admission_recommendations' 不存在於資料庫 '" . DB_NAME . "' 中。請檢查資料表是否已創建。");
        }
        
        // 檢查哪些欄位存在
        $existing_columns = [];
        $columns_result = $conn->query("SHOW COLUMNS FROM admission_recommendations");
        if (!$columns_result) {
            throw new Exception("無法查詢資料表結構: " . $conn->error . " (資料庫: " . DB_NAME . ", 資料表: admission_recommendations)");
        }
        while ($row = $columns_result->fetch_assoc()) {
            $existing_columns[] = $row['Field'];
        }
        
        // 調試：記錄現有欄位（僅在開發環境）
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            error_log("資料庫: " . DB_NAME);
            error_log("資料表: admission_recommendations");
            error_log("資料表現有欄位: " . implode(', ', $existing_columns));
        }
        
        // 如果沒有欄位，可能是資料表結構有問題
        if (empty($existing_columns)) {
            throw new Exception("資料表 'admission_recommendations' 沒有任何欄位。請檢查資料表結構。");
        }
        
        // 注意：不再自動添加欄位，只使用現有的表結構
        // 調試：記錄現有的欄位列表
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            error_log("資料表現有欄位: " . implode(', ', $existing_columns));
        }
        
        // 驗證必填欄位
        $required_fields = [
            'recommender_name', 'recommender_student_id', 'recommender_grade', 
            'recommender_department', 'recommender_phone', 'recommender_email',
            'student_name', 'student_school', 
            'student_phone', 'recommendation_reason'
        ];
        
        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        // 驗證就讀學校格式（必須從系統選項中選擇）
        $student_school_code = null;
        $student_school_name = '';
        
        // 調試：記錄提交的學校相關數據
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            error_log("student_school_code: " . ($_POST['student_school_code'] ?? 'empty'));
            error_log("student_school: " . ($_POST['student_school'] ?? 'empty'));
        }
        
        // 優先從隱藏欄位讀取 school_code
        if (!empty($_POST['student_school_code'])) {
            $student_school_code = trim($_POST['student_school_code']);
            // 查詢學校是否存在（不限制格式，只要在資料庫中存在即可）
            $school_check = $conn->prepare("SELECT name, city, district FROM school_data WHERE school_code = ? LIMIT 1");
            $school_check->bind_param("s", $student_school_code);
            $school_check->execute();
            $school_result = $school_check->get_result();
            if ($school_result->num_rows > 0) {
                // 學校存在於資料庫中，驗證通過
                $school_row = $school_result->fetch_assoc();
                $student_school_name = $school_row['name'] . ' (' . ($school_row['city'] ?? '') . ($school_row['district'] ?? '') . ')';
                $school_check->close();
                // 驗證通過，不再檢查其他條件
            } else {
                // 如果查不到，但 student_school 格式正確（"學校名稱 (縣市區)"），仍然允許
                $school_check->close();
                if (!empty($_POST['student_school']) && preg_match('/^.+ \(.+\)$/', trim($_POST['student_school']))) {
                    // student_school 格式正確，允許通過
                    $student_school_name = trim($_POST['student_school']);
                    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                        error_log("警告：找不到 school_code '$student_school_code' 對應的學校，但 student_school 格式正確，繼續處理。");
                    }
                } else {
                    // 既找不到學校，格式也不正確
                    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                        error_log("錯誤：找不到 school_code '$student_school_code' 對應的學校，且 student_school 格式不正確。");
                    }
                    $missing_fields[] = 'student_school_invalid';
                }
            }
        } elseif (!empty($_POST['student_school'])) {
            // 向後兼容：從顯示的學校名稱解析
            $student_school_input = trim($_POST['student_school']);
            
            // 檢查格式是否為 "學校名稱 (縣市區)"
            if (preg_match('/^.+ \(.+\)$/', $student_school_input)) {
                // 向後兼容：舊格式 "學校名稱 (縣市區)"
                if (preg_match('/^(.+?) \(.+\)$/', $student_school_input, $matches)) {
                    $school_name = trim($matches[1]);
                    $school_check = $conn->prepare("SELECT school_code, name, city, district FROM school_data WHERE name = ? LIMIT 1");
                    $school_check->bind_param("s", $school_name);
                    $school_check->execute();
                    $school_result = $school_check->get_result();
                    if ($school_result->num_rows > 0) {
                        $school_row = $school_result->fetch_assoc();
                        $student_school_code = $school_row['school_code'];
                        $student_school_name = $school_row['name'] . ' (' . ($school_row['city'] ?? '') . ($school_row['district'] ?? '') . ')';
                    } else {
                        // 如果查不到，但格式正確，仍然允許（可能是新學校或名稱不完全匹配）
                        $student_school_name = $student_school_input;
                        error_log("警告：找不到學校名稱 '$school_name' 對應的記錄，但格式正確，繼續處理");
                    }
                    $school_check->close();
                } else {
                    $missing_fields[] = 'student_school_invalid';
                }
            } else {
                // 格式不符合任何已知格式
                if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                    error_log("student_school 格式不符合: '$student_school_input'");
                }
                $missing_fields[] = 'student_school_invalid';
            }
        } else {
            // 兩個欄位都為空，但用戶可能填寫了，檢查格式
            if (!empty($_POST['student_school']) || !empty($_POST['student_school_code'])) {
                // 檢查格式是否正確
                $has_valid_format = false;
                if (!empty($_POST['student_school_code'])) {
                    // 如果有 school_code，檢查是否在資料庫中存在
                    $check_code = trim($_POST['student_school_code']);
                    $school_check = $conn->prepare("SELECT school_code FROM school_data WHERE school_code = ? LIMIT 1");
                    $school_check->bind_param("s", $check_code);
                    $school_check->execute();
                    $school_result = $school_check->get_result();
                    if ($school_result->num_rows > 0) {
                        // school_code 存在於資料庫中
                        $has_valid_format = true;
                        $student_school_code = $check_code;
                        $student_school_name = $_POST['student_school'] ?? '';
                    }
                    $school_check->close();
                }
                
                // 如果 school_code 驗證失敗，檢查 student_school 格式
                if (!$has_valid_format && !empty($_POST['student_school'])) {
                    $school_input = trim($_POST['student_school']);
                    if (preg_match('/^.+ \(.+\)$/', $school_input)) {
                        // 格式正確（"學校名稱 (縣市區)"）
                        $has_valid_format = true;
                        $student_school_name = $school_input;
                    }
                }
                
                // 如果格式不正確，才標記為錯誤
                if (!$has_valid_format) {
                    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                        error_log("學校格式驗證失敗 - student_school_code: " . ($_POST['student_school_code'] ?? 'empty') . ", student_school: " . ($_POST['student_school'] ?? 'empty'));
                    }
                    $missing_fields[] = 'student_school_invalid';
                }
            } else {
                // 兩個欄位都為空
                if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                    error_log("student_school_code 和 student_school 都為空");
                }
            }
        }
        
        if (!empty($missing_fields)) {
            $field_names = [
                'recommender_name' => '推薦人姓名',
                'recommender_student_id' => '推薦人學號',
                'recommender_grade' => '推薦人年級',
                'recommender_department' => '推薦人科系',
                'recommender_phone' => '推薦人聯絡電話',
                'recommender_email' => '推薦人電子郵件',
                'student_name' => '學生姓名',
                'student_school' => '就讀學校',
                'student_school_invalid' => '請從系統提供的選項中選擇學校，不能自行輸入',
                'student_phone' => '學生聯絡電話',
                'recommendation_reason' => '推薦理由'
            ];
            
            $missing_field_names = [];
            foreach ($missing_fields as $field) {
                $missing_field_names[] = $field_names[$field] ?? $field;
            }
            
            throw new Exception('請填寫所有必填欄位：' . implode('、', $missing_field_names));
        }
        
        // 驗證電子郵件格式
        if (!filter_var($_POST['recommender_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('推薦人電子郵件格式不正確');
        }
        
        // 如果學生有填寫電子郵件，則驗證格式
        if (!empty($_POST['student_email']) && !filter_var($_POST['student_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('被推薦學生電子郵件格式不正確');
        }
        
        // 驗證電話號碼格式（必須為10位數字）
        if (!empty($_POST['recommender_phone'])) {
            $phone = preg_replace('/[^0-9]/', '', $_POST['recommender_phone']); // 移除非數字字符
            if (strlen($phone) !== 10 || !preg_match('/^[0-9]{10}$/', $phone)) {
                throw new Exception('推薦人聯絡電話必須為10位數字');
            }
            $_POST['recommender_phone'] = $phone; // 標準化電話號碼格式
        }
        
        if (!empty($_POST['student_phone'])) {
            $phone = preg_replace('/[^0-9]/', '', $_POST['student_phone']); // 移除非數字字符
            if (strlen($phone) !== 10 || !preg_match('/^[0-9]{10}$/', $phone)) {
                throw new Exception('學生聯絡電話必須為10位數字');
            }
            $_POST['student_phone'] = $phone; // 標準化電話號碼格式
        }
        
        // 處理檔案上傳
        $proof_evidence_path = '';
        
        if (isset($_FILES['proof_evidence']) && $_FILES['proof_evidence']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/proof_evidence/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_info = pathinfo($_FILES['proof_evidence']['name']);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
            $max_file_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array(strtolower($file_info['extension']), $allowed_extensions)) {
                throw new Exception('不支援的檔案格式。請上傳圖片檔（JPG, PNG, GIF）、PDF或Word文件。');
            }
            
            if ($_FILES['proof_evidence']['size'] > $max_file_size) {
                throw new Exception('檔案大小超過5MB限制。');
            }
            
            $new_filename = uniqid() . '_' . time() . '.' . $file_info['extension'];
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['proof_evidence']['tmp_name'], $upload_path)) {
                $proof_evidence_path = $upload_path;
            } else {
                throw new Exception('檔案上傳失敗，請重試。');
            }
        }
        
        // 準備變數，獲取 code 值
        $recommender_grade_code = $_POST['recommender_grade'] ?? '';
        $recommender_department_code = $_POST['recommender_department'] ?? '';
        $student_grade_code = $_POST['student_grade'] ?? '';
        $student_interest_input = !empty($_POST['student_interest']) ? trim($_POST['student_interest']) : '';
        $student_interest_code = null;
        
        // 驗證並轉換 student_interest（可能是 code 或名稱）
        if (!empty($student_interest_input)) {
            // 先檢查是否為有效的 code
            $dept_check = $conn->prepare("SELECT code FROM departments WHERE code = ? LIMIT 1");
            $dept_check->bind_param("s", $student_interest_input);
            $dept_check->execute();
            $dept_result = $dept_check->get_result();
            
            if ($dept_result->num_rows > 0) {
                // 是有效的 code
                $student_interest_code = $student_interest_input;
            } else {
                // 不是 code，可能是科系名稱，嘗試查找對應的 code
                $dept_check2 = $conn->prepare("SELECT code FROM departments WHERE name = ? LIMIT 1");
                $dept_check2->bind_param("s", $student_interest_input);
                $dept_check2->execute();
                $dept_result2 = $dept_check2->get_result();
                
                if ($dept_result2->num_rows > 0) {
                    // 找到對應的 code
                    $dept_row = $dept_result2->fetch_assoc();
                    $student_interest_code = $dept_row['code'];
                    error_log("將科系名稱 '$student_interest_input' 轉換為 code: '$student_interest_code'");
                } else {
                    // 既不是 code 也不是有效的科系名稱，設為 null
                    error_log("警告：student_interest '$student_interest_input' 既不是有效的 code 也不是有效的科系名稱，將設為 NULL");
                    $student_interest_code = null;
                }
                $dept_check2->close();
            }
            $dept_check->close();
        }
        
        // 準備其他變數
        $student_grade = $_POST['student_grade'] ?? '';
        $student_email = $_POST['student_email'] ?? '';
        $student_line_id = $_POST['student_line_id'] ?? '';
        $student_interest = $_POST['student_interest'] ?? '';
        $additional_info = $_POST['additional_info'] ?? '';
        
        // 動態構建 INSERT 語句，只使用存在的欄位
        // 注意：admission_recommendations 表的實際結構：
        // id, recommendation_reason, student_interest (FK: departments.code), 
        // additional_info, status, enrollment_status, admin_notes, 
        // created_at, updated_at, proof_evidence, assigned_department, assigned_teacher_id
        // 其他字段（如推薦人信息、學生信息等）可能存儲在其他表中
        $insert_fields = [];
        $insert_values = [];
        $insert_params = [];
        $bind_types = '';
        
        // 基本欄位（檢查是否存在）
        if (in_array('recommender_name', $existing_columns)) {
            $insert_fields[] = 'recommender_name';
            $insert_values[] = $_POST['recommender_name'];
            $bind_types .= 's';
        }
        
        if (in_array('recommender_student_id', $existing_columns)) {
            $insert_fields[] = 'recommender_student_id';
            $insert_values[] = $_POST['recommender_student_id'];
            $bind_types .= 's';
        }
        
        // 推薦人年級（檢查舊欄位是否存在）
        if (in_array('recommender_grade', $existing_columns)) {
            $recommender_grade_name = $grades[$recommender_grade_code] ?? $recommender_grade_code;
            $insert_fields[] = 'recommender_grade';
            $insert_values[] = $recommender_grade_name;
            $bind_types .= 's';
        }
        if (in_array('recommender_grade_code', $existing_columns)) {
            $insert_fields[] = 'recommender_grade_code';
            $insert_values[] = $recommender_grade_code;
            $bind_types .= 's';
        }
        
        // 推薦人科系
        if (in_array('recommender_department', $existing_columns)) {
            $recommender_department_name = $departments[$recommender_department_code] ?? $recommender_department_code;
            $insert_fields[] = 'recommender_department';
            $insert_values[] = $recommender_department_name;
            $bind_types .= 's';
        }
        if (in_array('recommender_department_code', $existing_columns)) {
            $insert_fields[] = 'recommender_department_code';
            $insert_values[] = $recommender_department_code;
            $bind_types .= 's';
        }
        
        if (in_array('recommender_phone', $existing_columns)) {
            $insert_fields[] = 'recommender_phone';
            $insert_values[] = $_POST['recommender_phone'];
            $bind_types .= 's';
        }
        
        if (in_array('recommender_email', $existing_columns)) {
            $insert_fields[] = 'recommender_email';
            $insert_values[] = $_POST['recommender_email'];
            $bind_types .= 's';
        }
        
        if (in_array('student_name', $existing_columns)) {
            $insert_fields[] = 'student_name';
            $insert_values[] = $_POST['student_name'];
            $bind_types .= 's';
        }
        
        // 學生學校
        if (in_array('student_school', $existing_columns)) {
            $insert_fields[] = 'student_school';
            $insert_values[] = $student_school_name;
            $bind_types .= 's';
        }
        if (in_array('student_school_code', $existing_columns)) {
            $insert_fields[] = 'student_school_code';
            $insert_values[] = $student_school_code;
            $bind_types .= 's';
        }
        
        // 學生年級
        if (in_array('student_grade', $existing_columns)) {
            if ($student_grade_code === 'GRADUATED') {
                $student_grade_name = '已畢業';
            } else {
                $student_grade_name = $student_grade_code ? ($grades[$student_grade_code] ?? $student_grade_code) : '';
            }
            $insert_fields[] = 'student_grade';
            $insert_values[] = $student_grade_name;
            $bind_types .= 's';
        }
        if (in_array('student_grade_code', $existing_columns)) {
            $insert_fields[] = 'student_grade_code';
            $insert_values[] = $student_grade_code;
            $bind_types .= 's';
        }
        
        if (in_array('student_phone', $existing_columns)) {
            $insert_fields[] = 'student_phone';
            $insert_values[] = $_POST['student_phone'];
            $bind_types .= 's';
        }
        
        if (in_array('student_email', $existing_columns)) {
            $insert_fields[] = 'student_email';
            $insert_values[] = $student_email;
            $bind_types .= 's';
        }
        
        if (in_array('student_line_id', $existing_columns)) {
            $insert_fields[] = 'student_line_id';
            $insert_values[] = $student_line_id;
            $bind_types .= 's';
        }
        
        if (in_array('recommendation_reason', $existing_columns)) {
            $insert_fields[] = 'recommendation_reason';
            $insert_values[] = $_POST['recommendation_reason'];
            $bind_types .= 's';
        }
        
        // 學生興趣
        // 注意：根據錯誤信息，student_interest 字段有外鍵約束引用 departments.code
        // 因此應該插入 code 而不是名稱，如果為空則插入 NULL
        if (in_array('student_interest', $existing_columns)) {
            // student_interest 字段有外鍵約束，應該插入 code
            $insert_fields[] = 'student_interest';
            // 如果為空或無效，插入 NULL 而不是空字符串（外鍵約束要求）
            // 注意：NULL 值需要使用特殊處理，不能直接綁定字符串
            if ($student_interest_code !== null && $student_interest_code !== '') {
                // 再次驗證確保 code 存在（雙重檢查）
                $final_check = $conn->prepare("SELECT code FROM departments WHERE code = ? LIMIT 1");
                $final_check->bind_param("s", $student_interest_code);
                $final_check->execute();
                $final_result = $final_check->get_result();
                if ($final_result->num_rows > 0) {
                    $insert_values[] = $student_interest_code;
                    $bind_types .= 's';
                } else {
                    // 即使驗證過，再次檢查時發現不存在，設為 NULL
                    error_log("錯誤：student_interest_code '$student_interest_code' 在最終檢查時不存在於 departments 表中，將設為 NULL");
                    $insert_values[] = null;
                    $bind_types .= 's'; // NULL 仍然使用 's' 類型，但值為 null
                }
                $final_check->close();
            } else {
                // 為空或無效，插入 NULL
                $insert_values[] = null;
                $bind_types .= 's'; // NULL 使用 's' 類型
            }
        }
        if (in_array('student_interest_code', $existing_columns)) {
            $insert_fields[] = 'student_interest_code';
            // 如果為空，插入 NULL 而不是空字符串
            $insert_values[] = ($student_interest_code !== null && $student_interest_code !== '') ? $student_interest_code : null;
            $bind_types .= 's';
        }
        
        if (in_array('additional_info', $existing_columns)) {
            $insert_fields[] = 'additional_info';
            $insert_values[] = $additional_info;
            $bind_types .= 's';
        }
        
        if (in_array('proof_evidence', $existing_columns)) {
            $insert_fields[] = 'proof_evidence';
            $insert_values[] = $proof_evidence_path;
            $bind_types .= 's';
        }
        
        // 確保至少有一些欄位要插入
        if (empty($insert_fields)) {
            throw new Exception("沒有可插入的欄位。請檢查資料表結構。");
        }
        
        // 再次驗證所有要插入的欄位都存在（雙重檢查）
        // 確保欄位和值的順序一致
        $valid_fields = [];
        $valid_values = [];
        $valid_bind_types = '';
        
        // 只保留存在的欄位，保持欄位和值的對應關係
        foreach ($insert_fields as $index => $field) {
            if (in_array($field, $existing_columns)) {
                $valid_fields[] = $field;
                // 使用對應索引的值，確保順序一致
                // 注意：如果原值是 null，保持為 null（不要轉換為空字符串）
                $value = $insert_values[$index] ?? null;
                // 對於 student_interest 字段，如果是空字符串，轉換為 null（外鍵約束要求）
                if ($field === 'student_interest' && ($value === '' || $value === null)) {
                    $value = null;
                }
                $valid_values[] = $value;
                $valid_bind_types .= 's';
            } else {
                error_log("警告：欄位 '$field' 不存在於資料表中，將被跳過");
            }
        }
        
        // 更新變數
        $insert_fields = $valid_fields;
        $insert_values = $valid_values;
        $bind_types = $valid_bind_types;
        
        // 確保至少有一些欄位要插入
        if (empty($insert_fields)) {
            $error_msg = "沒有可插入的欄位。請檢查資料表結構。\n";
            $error_msg .= "資料庫: " . DB_NAME . "\n";
            $error_msg .= "資料表: admission_recommendations\n";
            $error_msg .= "現有欄位: " . implode(', ', $existing_columns) . "\n";
            $error_msg .= "嘗試插入的欄位: " . implode(', ', $valid_fields);
            throw new Exception($error_msg);
        }
        
        // 最終驗證：確保所有要插入的欄位都確實存在
        $missing_in_final = array_diff($insert_fields, $existing_columns);
        if (!empty($missing_in_final)) {
            $error_msg = "錯誤：以下欄位在最終檢查時不存在於資料表中：\n";
            $error_msg .= implode(', ', $missing_in_final) . "\n";
            $error_msg .= "資料庫: " . DB_NAME . "\n";
            $error_msg .= "資料表: admission_recommendations\n";
            $error_msg .= "現有欄位: " . implode(', ', $existing_columns);
            throw new Exception($error_msg);
        }
        
        // 構建 SQL
        $fields_str = implode(', ', $insert_fields);
        $placeholders = implode(', ', array_fill(0, count($insert_fields), '?'));
        $sql = "INSERT INTO admission_recommendations ($fields_str) VALUES ($placeholders)";
        
        // 調試：記錄要插入的欄位（僅在開發環境）
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            error_log("資料庫: " . DB_NAME);
            error_log("資料表: admission_recommendations");
            error_log("要插入的欄位: " . implode(', ', $insert_fields));
            error_log("現有欄位: " . implode(', ', $existing_columns));
            error_log("SQL: " . $sql);
        }
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            // 提供更詳細的錯誤信息，包括實際表結構
            $error_msg = "SQL 準備失敗: " . $conn->error;
            $error_msg .= "\n\n資料庫: " . DB_NAME;
            $error_msg .= "\n資料表: admission_recommendations";
            $error_msg .= "\n\n實際資料表中的欄位: " . implode(', ', $existing_columns);
            $error_msg .= "\n\n嘗試插入的欄位: " . implode(', ', $insert_fields);
            $error_msg .= "\n\n缺少的欄位: " . implode(', ', array_diff($insert_fields, $existing_columns));
            $error_msg .= "\n\nSQL 語句: " . $sql;
            
            // 如果錯誤訊息包含 "Unknown column"，提供更詳細的幫助
            if (strpos($conn->error, 'Unknown column') !== false) {
                $error_msg .= "\n\n提示：資料表結構可能與預期不同。";
                $error_msg .= "\n請檢查資料表 'admission_recommendations' 的實際結構。";
                $error_msg .= "\n預期的欄位應包括：recommender_name, recommender_student_id, recommender_grade, recommender_department 等。";
            }
            
            throw new Exception($error_msg);
        }
        
        // 使用 call_user_func_array 來動態綁定參數
        // 處理 NULL 值：MySQLi 需要特殊處理 NULL 值
        $bind_params = [$bind_types];
        foreach ($insert_values as $value) {
            $bind_params[] = $value;
        }
        $refs = [];
        foreach ($bind_params as $key => $value) {
            $refs[$key] = &$bind_params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
        
        if (!$stmt->execute()) {
            // 執行失敗，提供詳細錯誤信息
            $error_msg = "SQL 執行失敗: " . $stmt->error;
            $error_msg .= "\n\n資料庫: " . DB_NAME;
            $error_msg .= "\n資料表: admission_recommendations";
            $error_msg .= "\n\n實際資料表中的欄位: " . implode(', ', $existing_columns);
            $error_msg .= "\n\n嘗試插入的欄位: " . implode(', ', $insert_fields);
            $error_msg .= "\n\n缺少的欄位: " . implode(', ', array_diff($insert_fields, $existing_columns));
            $error_msg .= "\n\nSQL 語句: " . $sql;
            
            // 如果是外鍵約束錯誤，提供更詳細的信息
            if (strpos($stmt->error, 'foreign key constraint') !== false || strpos($stmt->error, 'FOREIGN KEY') !== false) {
                $error_msg .= "\n\n外鍵約束錯誤詳情：";
                // 查找 student_interest 相關的錯誤
                if (in_array('student_interest', $insert_fields)) {
                    $interest_index = array_search('student_interest', $insert_fields);
                    $interest_value = $insert_values[$interest_index] ?? '未設置';
                    $error_msg .= "\n- student_interest 字段的值: " . ($interest_value === null ? 'NULL' : "'$interest_value'");
                    $error_msg .= "\n- 此值必須存在於 departments 表的 code 字段中";
                    $error_msg .= "\n- 請確認輸入的科系代碼是否正確";
                    
                    // 查詢所有可用的科系代碼
                    $dept_list_query = $conn->query("SELECT code, name FROM departments ORDER BY code");
                    if ($dept_list_query && $dept_list_query->num_rows > 0) {
                        $error_msg .= "\n\n可用的科系代碼列表：";
                        while ($dept_row = $dept_list_query->fetch_assoc()) {
                            $error_msg .= "\n  - " . $dept_row['code'] . " (" . $dept_row['name'] . ")";
                        }
                    }
                }
            }
            
            // 如果錯誤訊息包含 "Unknown column"，提供更詳細的幫助
            if (strpos($stmt->error, 'Unknown column') !== false) {
                $error_msg .= "\n\n提示：資料表結構可能與預期不同。";
                $error_msg .= "\n請檢查資料表 'admission_recommendations' 的實際結構。";
                $error_msg .= "\n預期的欄位應包括：recommender_name, recommender_student_id, recommender_grade, recommender_department 等。";
            }
            
            throw new Exception($error_msg);
        }
        
        // 獲取新插入的記錄ID
        $recommendation_id = $conn->insert_id;
        
        if ($recommendation_id > 0) {
            
            // 插入推薦人資料到 recommender 表
            try {
                // 檢查 recommender 表是否存在
                $table_check = $conn->query("SHOW TABLES LIKE 'recommender'");
                if ($table_check && $table_check->num_rows > 0) {
                    // 查詢 recommender 表的欄位
                    $recommender_columns = [];
                    $columns_result = $conn->query("SHOW COLUMNS FROM recommender");
                    if ($columns_result) {
                        while ($row = $columns_result->fetch_assoc()) {
                            $recommender_columns[] = $row['Field'];
                        }
                    }
                    
                    // 構建插入 recommender 表的 SQL
                    $recommender_fields = [];
                    $recommender_values = [];
                    $recommender_bind_types = '';
                    
                    if (in_array('recommendations_id', $recommender_columns)) {
                        $recommender_fields[] = 'recommendations_id';
                        $recommender_values[] = $recommendation_id;
                        $recommender_bind_types .= 'i';
                    }
                    if (in_array('name', $recommender_columns)) {
                        $recommender_fields[] = 'name';
                        $recommender_values[] = $_POST['recommender_name'];
                        $recommender_bind_types .= 's';
                    }
                    if (in_array('id', $recommender_columns)) {
                        $recommender_fields[] = 'id';
                        $recommender_values[] = $_POST['recommender_student_id'];
                        $recommender_bind_types .= 's';
                    }
                    if (in_array('grade', $recommender_columns)) {
                        $recommender_fields[] = 'grade';
                        $recommender_values[] = $recommender_grade_code; // 關聯 identity_options.code
                        $recommender_bind_types .= 's';
                    }
                    if (in_array('department', $recommender_columns)) {
                        $recommender_fields[] = 'department';
                        $recommender_values[] = $recommender_department_code; // 關聯 departments.code
                        $recommender_bind_types .= 's';
                    }
                    if (in_array('phone', $recommender_columns)) {
                        $recommender_fields[] = 'phone';
                        $recommender_values[] = $_POST['recommender_phone'];
                        $recommender_bind_types .= 's';
                    }
                    if (in_array('email', $recommender_columns)) {
                        $recommender_fields[] = 'email';
                        $recommender_values[] = $_POST['recommender_email'];
                        $recommender_bind_types .= 's';
                    }
                    
                    if (!empty($recommender_fields)) {
                        $recommender_fields_str = implode(', ', $recommender_fields);
                        $recommender_placeholders = implode(', ', array_fill(0, count($recommender_fields), '?'));
                        $recommender_sql = "INSERT INTO recommender ($recommender_fields_str) VALUES ($recommender_placeholders)";
                        
                        $recommender_stmt = $conn->prepare($recommender_sql);
                        if ($recommender_stmt) {
                            $recommender_bind_params = array_merge([$recommender_bind_types], $recommender_values);
                            $recommender_refs = [];
                            foreach ($recommender_bind_params as $key => $value) {
                                $recommender_refs[$key] = &$recommender_bind_params[$key];
                            }
                            call_user_func_array([$recommender_stmt, 'bind_param'], $recommender_refs);
                            $recommender_stmt->execute();
                            $recommender_stmt->close();
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("插入 recommender 表失敗: " . $e->getMessage());
                // 不中斷主流程，只記錄錯誤
            }
            
            // 插入被推薦人資料到 recommended 表（如果存在）
            // 注意：推薦人信息和學生信息可能存儲在 recommended 表中
            // admission_recommendations 表只存儲推薦申請的核心信息
            try {
                // 檢查 recommended 表是否存在
                $table_check = $conn->query("SHOW TABLES LIKE 'recommended'");
                if ($table_check && $table_check->num_rows > 0) {
                    // 查詢 recommended 表的欄位和約束
                    $recommended_columns = [];
                    $columns_result = $conn->query("SHOW COLUMNS FROM recommended");
                    if ($columns_result) {
                        while ($row = $columns_result->fetch_assoc()) {
                            $recommended_columns[] = $row['Field'];
                        }
                    }
                    
                    // 構建插入 recommended 表的 SQL
                    $recommended_fields = [];
                    $recommended_values = [];
                    $recommended_bind_types = '';
                    
                    if (in_array('recommendations_id', $recommended_columns)) {
                        $recommended_fields[] = 'recommendations_id';
                        $recommended_values[] = $recommendation_id;
                        $recommended_bind_types .= 'i';
                    }
                    if (in_array('name', $recommended_columns)) {
                        $recommended_fields[] = 'name';
                        $recommended_values[] = $_POST['student_name'];
                        $recommended_bind_types .= 's';
                    }
                    if (in_array('school', $recommended_columns)) {
                        // 驗證 school_code 是否在 school_data 表中存在
                        if ($student_school_code !== null && $student_school_code !== '') {
                            $school_verify = $conn->prepare("SELECT school_code FROM school_data WHERE school_code = ? LIMIT 1");
                            $school_verify->bind_param("s", $student_school_code);
                            $school_verify->execute();
                            $school_verify_result = $school_verify->get_result();
                            if ($school_verify_result->num_rows > 0) {
                                // school_code 存在，可以插入
                                $recommended_fields[] = 'school';
                                $recommended_values[] = $student_school_code; // 關聯 school_data.school_code
                                $recommended_bind_types .= 's';
                            } else {
                                // school_code 不存在，如果欄位允許 NULL，插入 NULL
                                error_log("警告：school_code '$student_school_code' 不在 school_data 表中，嘗試插入 NULL");
                                $recommended_fields[] = 'school';
                                $recommended_values[] = null;
                                $recommended_bind_types .= 's';
                            }
                            $school_verify->close();
                        } else {
                            // school_code 為空，插入 NULL
                            $recommended_fields[] = 'school';
                            $recommended_values[] = null;
                            $recommended_bind_types .= 's';
                        }
                    }
                    if (in_array('grade', $recommended_columns)) {
                        // 驗證 grade_code 是否在 identity_options 表中存在（如果不是 GRADUATED）
                        if ($student_grade_code !== null && $student_grade_code !== '' && $student_grade_code !== 'GRADUATED') {
                            $grade_verify = $conn->prepare("SELECT code FROM identity_options WHERE code = ? LIMIT 1");
                            $grade_verify->bind_param("s", $student_grade_code);
                            $grade_verify->execute();
                            $grade_verify_result = $grade_verify->get_result();
                            if ($grade_verify_result->num_rows > 0) {
                                // grade_code 存在，可以插入
                                $recommended_fields[] = 'grade';
                                $recommended_values[] = $student_grade_code; // 關聯 identity_options.code
                                $recommended_bind_types .= 's';
                            } else {
                                // grade_code 不存在，插入 NULL
                                error_log("警告：grade_code '$student_grade_code' 不在 identity_options 表中，插入 NULL");
                                $recommended_fields[] = 'grade';
                                $recommended_values[] = null;
                                $recommended_bind_types .= 's';
                            }
                            $grade_verify->close();
                        } else {
                            // GRADUATED 或為空，插入 NULL
                            $recommended_fields[] = 'grade';
                            $recommended_values[] = null;
                            $recommended_bind_types .= 's';
                        }
                    }
                    if (in_array('phone', $recommended_columns)) {
                        $recommended_fields[] = 'phone';
                        $recommended_values[] = $_POST['student_phone'];
                        $recommended_bind_types .= 's';
                    }
                    if (in_array('email', $recommended_columns)) {
                        $recommended_fields[] = 'email';
                        $recommended_values[] = $student_email;
                        $recommended_bind_types .= 's';
                    }
                    if (in_array('line_id', $recommended_columns)) {
                        $recommended_fields[] = 'line_id';
                        $recommended_values[] = $student_line_id;
                        $recommended_bind_types .= 's';
                    }
                    
                    if (!empty($recommended_fields)) {
                        $recommended_fields_str = implode(', ', $recommended_fields);
                        $recommended_placeholders = implode(', ', array_fill(0, count($recommended_fields), '?'));
                        $recommended_sql = "INSERT INTO recommended ($recommended_fields_str) VALUES ($recommended_placeholders)";
                        
                        // 調試：記錄要插入的資料
                        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                            error_log("插入 recommended 表 - SQL: " . $recommended_sql);
                            error_log("插入 recommended 表 - 欄位: " . implode(', ', $recommended_fields));
                            error_log("插入 recommended 表 - 值: " . print_r($recommended_values, true));
                            error_log("插入 recommended 表 - recommendations_id: " . $recommendation_id);
                            error_log("插入 recommended 表 - student_school_code: " . ($student_school_code ?? 'NULL'));
                            error_log("插入 recommended 表 - student_grade_code: " . ($student_grade_code ?? 'NULL'));
                        }
                        
                        $recommended_stmt = $conn->prepare($recommended_sql);
                        if ($recommended_stmt) {
                            $recommended_bind_params = array_merge([$recommended_bind_types], $recommended_values);
                            $recommended_refs = [];
                            foreach ($recommended_bind_params as $key => $value) {
                                $recommended_refs[$key] = &$recommended_bind_params[$key];
                            }
                            call_user_func_array([$recommended_stmt, 'bind_param'], $recommended_refs);
                            
                            if (!$recommended_stmt->execute()) {
                                error_log("插入 recommended 表執行失敗: " . $recommended_stmt->error);
                                error_log("SQL: " . $recommended_sql);
                                error_log("值: " . print_r($recommended_values, true));
                            } else {
                                if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                                    error_log("插入 recommended 表成功，插入 ID: " . $conn->insert_id);
                                }
                            }
                            $recommended_stmt->close();
                        } else {
                            error_log("插入 recommended 表準備失敗: " . $conn->error);
                            error_log("SQL: " . $recommended_sql);
                        }
                    } else {
                        error_log("插入 recommended 表：沒有可插入的欄位");
                        error_log("recommended 表現有欄位: " . implode(', ', $recommended_columns));
                    }
                } else {
                    error_log("插入 recommended 表：表不存在");
                }
            } catch (Exception $e) {
                error_log("插入 recommended 表失敗: " . $e->getMessage());
                error_log("錯誤堆疊: " . $e->getTraceAsString());
                // 不中斷主流程，只記錄錯誤
            }
            
            // 準備郵件資料
            // 確保使用台灣時區顯示時間
            // 將科系代碼轉換為名稱
            $recommender_department_name = $_POST['recommender_department'];
            if (!empty($recommender_department_code) && isset($departments[$recommender_department_code])) {
                $recommender_department_name = $departments[$recommender_department_code];
            }
            
            // 將年級代碼轉換為名稱
            $student_grade_name = $_POST['student_grade'];
            if (!empty($student_grade_code)) {
                if ($student_grade_code === 'GRADUATED') {
                    $student_grade_name = '已畢業';
                } elseif (isset($grades[$student_grade_code])) {
                    $student_grade_name = $grades[$student_grade_code];
                }
            }
            
            $email_data = [
                'recommender_name' => $_POST['recommender_name'],
                'recommender_student_id' => $_POST['recommender_student_id'],
                'recommender_department' => $recommender_department_name, // 使用轉換後的名稱
                'student_name' => $_POST['student_name'],
                'student_school' => $student_school_name ?: $_POST['student_school'], // 使用驗證後的學校名稱
                'student_grade' => $student_grade_name, // 使用轉換後的年級名稱
                'submission_time' => date('Y-m-d H:i:s', time()) // 使用台灣時區 (UTC+8)，郵件模板會自動加上時區標示
            ];
            
            // 發送推薦成功通知郵件
            $email_sent = false;
            $email_error_msg = '';
            try {
                $email_sent = sendNotificationEmail(
                    $_POST['recommender_email'],
                    $_POST['recommender_name'],
                    'recommendation_success',
                    $email_data
                );
                
                if ($email_sent) {
                    // 記錄郵件發送成功
                    logNotification(
                        $recommendation_id,
                        'recommendation_success',
                        $_POST['recommender_email'],
                        'sent'
                    );
                    error_log("推薦成功郵件發送成功: {$_POST['recommender_email']}");
                } else {
                    // 記錄郵件發送失敗
                    logNotification(
                        $recommendation_id,
                        'recommendation_success',
                        $_POST['recommender_email'],
                        'failed'
                    );
                    $email_error_msg = "郵件發送失敗，請檢查您的電子郵件地址是否正確，或稍後聯繫我們。";
                    error_log("推薦成功郵件發送失敗: {$_POST['recommender_email']}");
                }
            } catch (Exception $email_error) {
                // 郵件發送失敗不影響主要流程，只記錄錯誤
                $email_error_msg = "郵件發送時發生錯誤：" . $email_error->getMessage();
                error_log("推薦成功郵件發送異常: {$_POST['recommender_email']} - " . $email_error->getMessage());
                logNotification(
                    $recommendation_id,
                    'recommendation_success',
                    $_POST['recommender_email'],
                    'failed'
                );
            }
            
            // 根據郵件發送結果顯示不同的訊息
            if ($email_sent) {
                $message = "推薦報名表單提交成功！我們會盡快處理您的推薦申請。確認郵件已發送至您的信箱（{$_POST['recommender_email']}），請檢查收件匣或垃圾郵件資料夾。";
            } else {
                $message = "推薦報名表單提交成功！我們會盡快處理您的推薦申請。";
                if (!empty($email_error_msg)) {
                    $message .= " 注意：" . $email_error_msg;
                } else {
                    $message .= " 注意：確認郵件發送失敗，請確認您的電子郵件地址（{$_POST['recommender_email']}）是否正確，或稍後聯繫我們。";
                }
            }
            $messageType = "success";
            // 清空表單
            $_POST = array();
        } else {
            // 如果插入成功但沒有獲取到 ID，可能是資料表結構問題
            throw new Exception("提交失敗：無法獲取插入記錄的 ID。請檢查資料表結構。");
        }
        
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        $message = "提交失敗：" . $e->getMessage();
        $messageType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>招生推薦報名表</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/csp/admission_recommend.css">
  <style>
    /* 錯誤提示動畫 */
    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .field-error {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .field-error i {
      font-size: 14px;
    }
    
    /* 學校搜尋相關樣式 - 與 admission.php 一致 */
    .modern-search-container {
      position: relative;
      width: 100%;
    }
    
    .search-input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }
    
    .search-input-wrapper input {
      width: 100%;
      padding: 12px 45px 12px 15px;
      border: 2px solid #e1e8ed;
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.3s ease;
      box-sizing: border-box;
    }
    
    .search-input-wrapper input:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .search-icon {
      position: absolute;
      right: 15px;
      color: #6c757d;
      pointer-events: none;
      z-index: 1;
    }
    
    .modern-search-results {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      background: white;
      border: 1px solid #e1e8ed;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      max-height: 300px;
      overflow-y: auto;
      z-index: 1000;
      display: none;
      margin-top: 2px;
    }
    
    .modern-search-results.show {
      display: block;
    }
    
    .search-result-item {
      padding: 12px 15px;
      cursor: pointer;
      border-bottom: 1px solid #f1f3f4;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: background-color 0.2s ease;
    }
    
    .search-result-item:last-child {
      border-bottom: none;
    }
    
    .search-result-item:hover {
      background-color: #f8f9fa;
    }
    
    .search-result-item i {
      color: #667eea;
      font-size: 0.9rem;
    }
    
    .school-info {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    
    .school-name {
      font-weight: 600;
      color: #333;
      font-size: 14px;
    }
    
    .school-location {
      font-size: 12px;
      color: #666;
    }
    
    .school-alternative-names {
      font-size: 11px;
      color: #999;
      margin-top: 4px;
    }
    
    .search-result-item.more-results {
      background-color: #f8f9fa;
      color: #666;
      font-size: 12px;
      cursor: default;
      text-align: center;
      font-style: italic;
    }
    
    .search-result-item.more-results:hover {
      background-color: #f8f9fa;
    }
    
    .help-text {
      margin-top: 8px;
      font-size: 12px;
      color: #666;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    
    .help-text i {
      color: #667eea;
    }
  </style>
</head>
<body>
<?php include("share/header.php"); ?>
<div class="recommend-page-wrapper">
<div class="recommend-container">
  <div class="recommend-header">
    <h1><i class="fas fa-user-friends"></i> 招生推薦報名表</h1>
    <p>在校生推薦國中生報名康寧大學五專部</p>
  </div>

  <div class="form-container">
    <?php if ($message): ?>
      <div class="message <?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <!-- 學號查詢功能（當通過ID查詢時隱藏） -->
    <?php if (!$single_detail): ?>
    <div class="search-section">
      <h3><i class="fas fa-search"></i> 查詢推薦記錄</h3>
      <form method="POST" action="" class="search-form">
        <div class="search-row">
          <input type="text" name="search_student_id" placeholder="請輸入推薦人學號或教師編號" 
                 value="<?php echo htmlspecialchars($search_student_id); ?>" required>
          <button type="submit" name="search_action" value="search" class="search-btn" style="margin-bottom: 1px">
            <i class="fas fa-search"></i> 查詢
          </button>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <!-- 單筆詳細資訊（從後台查看詳情時顯示） -->
    <?php if ($single_detail): ?>
    <div class="detail-card">
      <div class="detail-header">
        <h3><i class="fas fa-file-alt"></i> 推薦記錄詳細資訊</h3>
        <a href="javascript:window.close()" class="close-btn" title="關閉視窗">
          <i class="fas fa-times"></i>
        </a>
      </div>
      
      <div class="detail-content">
        <!-- 推薦人資訊 -->
        <div class="detail-section">
          <h4><i class="fas fa-user"></i> 推薦人資訊</h4>
          <div class="detail-grid">
            <div class="detail-item">
              <div class="detail-label">姓名</div>
              <div class="detail-value"><?php echo htmlspecialchars($single_detail['recommender_name']); ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">學號/教師編號</div>
              <div class="detail-value"><?php echo htmlspecialchars($single_detail['recommender_student_id']); ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">年級</div>
              <div class="detail-value"><?php echo htmlspecialchars($single_detail['recommender_grade_name'] ?? $single_detail['recommender_grade'] ?? ''); ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">科系</div>
              <div class="detail-value"><?php echo htmlspecialchars($single_detail['recommender_department_name'] ?? $single_detail['recommender_department'] ?? ''); ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">聯絡電話</div>
              <div class="detail-value"><?php echo htmlspecialchars($single_detail['recommender_phone']); ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">電子郵件</div>
              <div class="detail-value"><?php echo htmlspecialchars($single_detail['recommender_email']); ?></div>
            </div>
          </div>
        </div>

        <!-- 被推薦學生資訊 -->
        <div class="detail-section">
          <h4><i class="fas fa-graduation-cap"></i> 被推薦學生資訊</h4>
          <div class="detail-grid">
            <div class="detail-item">
              <div class="detail-label">姓名</div>
              <div class="detail-value"><?php echo htmlspecialchars($single_detail['student_name']); ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">電子郵件</div>
              <div class="detail-value"><?php echo !empty($single_detail['student_email']) ? htmlspecialchars($single_detail['student_email']) : '<span style="color: #8c8c8c;">未填寫</span>'; ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">就讀學校</div>
              <div class="detail-value"><?php echo htmlspecialchars($single_detail['student_school_name'] ?? $single_detail['student_school'] ?? ''); ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">年級</div>
              <div class="detail-value">
                <?php 
                $student_grade_display = '';
                if (!empty($single_detail['student_grade_code'])) {
                    // 如果是「已畢業」，特殊處理
                    if ($single_detail['student_grade_code'] === 'GRADUATED') {
                        $student_grade_display = '已畢業';
                    } else {
                        $student_grade_display = $single_detail['student_grade_name'] ?? $single_detail['student_grade'] ?? '';
                    }
                } else {
                    $student_grade_display = $single_detail['student_grade_name'] ?? $single_detail['student_grade'] ?? '';
                }
                echo !empty($student_grade_display) ? htmlspecialchars($student_grade_display) : '<span style="color: #8c8c8c;">未填寫</span>';
                ?>
              </div>
            </div>
            <div class="detail-item">
              <div class="detail-label">聯絡電話</div>
              <div class="detail-value"><?php echo htmlspecialchars($single_detail['student_phone']); ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">LINE ID</div>
              <div class="detail-value"><?php echo !empty($single_detail['student_line_id']) ? htmlspecialchars($single_detail['student_line_id']) : '<span style="color: #8c8c8c;">未填寫</span>'; ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">學生興趣</div>
              <div class="detail-value"><?php echo !empty($single_detail['student_interest_name'] ?? $single_detail['student_interest']) ? htmlspecialchars($single_detail['student_interest_name'] ?? $single_detail['student_interest']) : '<span style="color: #8c8c8c;">未填寫</span>'; ?></div>
            </div>
          </div>
        </div>

        <!-- 推薦資訊 -->
        <div class="detail-section">
          <h4><i class="fas fa-heart"></i> 推薦資訊</h4>
          <div class="detail-grid">
            <div class="detail-item" style="grid-column: 1 / -1;">
              <div class="detail-label">推薦理由</div>
              <div class="detail-value"><?php echo nl2br(htmlspecialchars($single_detail['recommendation_reason'])); ?></div>
            </div>
            <?php if (!empty($single_detail['additional_info'])): ?>
            <div class="detail-item" style="grid-column: 1 / -1;">
              <div class="detail-label">其他補充資訊</div>
              <div class="detail-value"><?php echo nl2br(htmlspecialchars($single_detail['additional_info'])); ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($single_detail['proof_evidence'])): ?>
            <div class="detail-item" style="grid-column: 1 / -1;">
              <div class="detail-label">證明文件</div>
              <div class="detail-value">
                <a href="<?php echo htmlspecialchars($single_detail['proof_evidence']); ?>" target="_blank" class="file-link">
                  <i class="fas fa-file"></i> 查看文件
                </a>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- 狀態資訊 -->
        <div class="detail-section">
          <h4><i class="fas fa-info-circle"></i> 狀態資訊</h4>
          <div class="detail-grid">
            <div class="detail-item">
              <div class="detail-label">處理狀態</div>
              <div class="detail-value">
                <span class="status status-<?php echo $single_detail['status'] ?? 'pending'; ?>">
                  <?php 
                  $status_text = [
                    'pending' => '待處理',
                    'contacted' => '已聯繫',
                    'registered' => '已報名',
                    'rejected' => '已拒絕'
                  ];
                  echo $status_text[$single_detail['status'] ?? 'pending'] ?? '待處理';
                  ?>
                </span>
              </div>
            </div>
            <div class="detail-item">
              <div class="detail-label">入學狀態</div>
              <div class="detail-value">
                <span class="enrollment-status enrollment-<?php echo $single_detail['enrollment_status'] ?? '未入學'; ?>">
                  <?php 
                  $enrollment_text = [
                    '未入學' => '未入學',
                    '已入學' => '已入學',
                    '放棄入學' => '放棄入學'
                  ];
                  echo $enrollment_text[$single_detail['enrollment_status'] ?? '未入學'] ?? '未入學';
                  ?>
                </span>
              </div>
            </div>
            <div class="detail-item">
              <div class="detail-label">建立時間</div>
              <div class="detail-value">
                <?php 
                $date = new DateTime($single_detail['created_at'], new DateTimeZone('UTC'));
                $date->setTimezone(new DateTimeZone('Asia/Taipei'));
                echo $date->format('Y-m-d H:i:s');
                ?>
              </div>
            </div>
            <?php if (!empty($single_detail['updated_at'])): ?>
            <div class="detail-item">
              <div class="detail-label">更新時間</div>
              <div class="detail-value">
                <?php 
                $date = new DateTime($single_detail['updated_at'], new DateTimeZone('UTC'));
                $date->setTimezone(new DateTimeZone('Asia/Taipei'));
                echo $date->format('Y-m-d H:i:s');
                ?>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- 查詢結果（多筆記錄時顯示） -->
    <?php if (!empty($search_results) && !$single_detail): ?>
    <div class="search-results">
      <h4>查詢結果</h4>
      
      <!-- 結果篩選功能 -->
      <div class="result-filter">
        <div class="filter-header">
          <h5><i class="fas fa-filter"></i> 進一步篩選結果</h5>
          <span class="result-count">共 <?php echo count($search_results); ?> 筆記錄</span>
        </div>
        <div class="filter-inputs">
          <div class="filter-group">
            <label for="filter_student_name">被推薦學生姓名</label>
            <input type="text" id="filter_student_name" placeholder="輸入學生姓名進行篩選">
          </div>
          <div class="filter-group">
            <label for="filter_school">國中學校</label>
            <input type="text" id="filter_school" placeholder="輸入學校名稱進行篩選">
          </div>
          
        </div>
        <div class="filter-note">
          <i class="fas fa-info-circle"></i> 輸入姓名或學校名稱可即時篩選結果
        </div>
      </div>
      
      <div class="results-table">
        <table id="resultsTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>推薦人</th>
              <th>被推薦學生</th>
              <th>學校</th>
              <th>年級</th>
              <th>狀態</th>
              <th>入學狀態</th>
              <th>建立時間</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($search_results as $result): ?>
            <tr class="result-row" 
                data-student-name="<?php echo htmlspecialchars($result['student_name'] ?? ''); ?>"
                data-school="<?php echo htmlspecialchars($result['student_school_name'] ?? $result['student_school'] ?? ''); ?>">
              <td><?php echo $result['id']; ?></td>
              <td>
                <?php echo htmlspecialchars($result['recommender_name']); ?><br>
                <small><?php echo htmlspecialchars($result['recommender_student_id']); ?></small>
              </td>
              <td>
                <?php echo htmlspecialchars($result['student_name']); ?><br>
                <small><?php echo htmlspecialchars($result['student_email']); ?></small>
              </td>
              <td><?php echo htmlspecialchars($result['student_school_name'] ?? $result['student_school'] ?? ''); ?></td>
              <td>
                <?php 
                $student_grade_display = '';
                if (!empty($result['student_grade_code'])) {
                    // 如果是「已畢業」，特殊處理
                    if ($result['student_grade_code'] === 'GRADUATED') {
                        $student_grade_display = '已畢業';
                    } else {
                        $student_grade_display = $result['student_grade_name'] ?? $result['student_grade'] ?? '';
                    }
                } else {
                    $student_grade_display = $result['student_grade_name'] ?? $result['student_grade'] ?? '';
                }
                echo htmlspecialchars($student_grade_display);
                ?>
              </td>
              <td>
                <span class="status status-<?php echo $result['status']; ?>">
                  <?php 
                  $status_text = [
                    'pending' => '待處理',
                    'contacted' => '已聯繫',
                    'registered' => '已報名',
                    'rejected' => '已拒絕'
                  ];
                  echo $status_text[$result['status']] ?? $result['status'];
                  ?>
                </span>
              </td>
              <td>
                <span class="enrollment-status enrollment-<?php echo $result['enrollment_status'] ?? '未入學'; ?>">
                  <?php 
                  $enrollment_text = [
                    '未入學' => '未入學',
                    '已入學' => '已入學',
                    '放棄入學' => '放棄入學'
                  ];
                  echo $enrollment_text[$result['enrollment_status'] ?? '未入學'] ?? '未入學';
                  ?>
                </span>
              </td>
              <td><?php 
                  // 確保使用台灣時區顯示時間
                  $date = new DateTime($result['created_at'], new DateTimeZone('UTC'));
                  $date->setTimezone(new DateTimeZone('Asia/Taipei'));
                  echo $date->format('Y-m-d H:i');
              ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- 當通過ID查詢時，不顯示表單 -->
    <?php if (!$single_detail): ?>
    <form method="POST" action="" enctype="multipart/form-data">
      <!-- 推薦人資訊 -->
      <div class="form-section">
        <h3><i class="fas fa-user"></i> 推薦人資訊（在校生或教職員）</h3>
        
        <div class="form-row">
          <div class="form-group">
            <label for="recommender_name"><span class="required">*</span> 姓名</label>
            <input type="text" id="recommender_name" name="recommender_name" 
                   value="<?php echo isset($_POST['recommender_name']) ? htmlspecialchars($_POST['recommender_name']) : ''; ?>" required>
          </div>
          
          <div class="form-group">
            <label for="recommender_student_id"><span class="required">*</span> 學號或教師編號 </label>
            <input type="text" id="recommender_student_id" name="recommender_student_id" 
                   value="<?php echo isset($_POST['recommender_student_id']) ? htmlspecialchars($_POST['recommender_student_id']) : ''; ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="recommender_grade"> <span class="required">*</span> 年級</label>
            <select id="recommender_grade" name="recommender_grade" required>
              <option value="">請選擇年級</option>
              <?php 
              // 只顯示專科年級 (F1-F5) - 推薦人年級：一到五年級，關聯 identity_options.code
              $recommender_grades = array_filter($grades, function($code) {
                  return in_array($code, ['F1', 'F2', 'F3', 'F4', 'F5']);
              }, ARRAY_FILTER_USE_KEY);
              foreach ($recommender_grades as $code => $name): ?>
                <option value="<?php echo htmlspecialchars($code); ?>" 
                        <?php echo (isset($_POST['recommender_grade']) && $_POST['recommender_grade'] === $code) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($name); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="form-group">
            <label for="recommender_department"> <span class="required">*</span> 科系 </label>
            <select id="recommender_department" name="recommender_department" required>
              <option value="">請選擇科系</option>
              <?php 
              // 推薦人科系：關聯 departments.code
              foreach ($departments as $code => $name): ?>
                <option value="<?php echo htmlspecialchars($code); ?>" 
                        <?php echo (isset($_POST['recommender_department']) && $_POST['recommender_department'] === $code) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($name); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="recommender_phone"><span class="required">*</span> 聯絡電話</label>
            <input type="tel" id="recommender_phone" name="recommender_phone" 
                   value="<?php echo isset($_POST['recommender_phone']) ? htmlspecialchars($_POST['recommender_phone']) : ''; ?>" 
                   pattern="[0-9]{10}" maxlength="10" placeholder="請輸入電話號碼" required>
            <small class="phone-hint" style="display: none; color: #d32f2f; font-size: 12px; margin-top: 4px;">電話號碼錯誤</small>
          </div>
          
          <div class="form-group">
            <label for="recommender_email"> <span class="required">*</span> 電子郵件 </label>
            <input type="email" id="recommender_email" name="recommender_email" 
                   value="<?php echo isset($_POST['recommender_email']) ? htmlspecialchars($_POST['recommender_email']) : ''; ?>" required>
          </div>
        </div>
      </div>

      <!-- 被推薦學生資訊 -->
      <div class="form-section">
        <h3><i class="fas fa-graduation-cap"></i> 被推薦學生資訊（國中生）</h3>
        
        <div class="form-row">
          <div class="form-group">
            <label for="student_name"> <span class="required">*</span> 學生姓名 </label>
            <input type="text" id="student_name" name="student_name" 
                   value="<?php echo isset($_POST['student_name']) ? htmlspecialchars($_POST['student_name']) : ''; ?>" required>
          </div>
          
          <div class="form-group">
            <label for="student_school"> <span class="required">*</span> 就讀學校 </label>
            <div class="modern-search-container">
              <div class="search-input-wrapper">
                <!-- 學校：關聯 school_data.school_code -->
                <input type="text" id="student_school" name="student_school" placeholder="請輸入學校名稱..." autocomplete="off" required 
                       value="<?php echo isset($_POST['student_school']) ? htmlspecialchars($_POST['student_school']) : ''; ?>" />
                <input type="hidden" id="student_school_code" name="student_school_code" value="<?php echo isset($_POST['student_school_code']) ? htmlspecialchars($_POST['student_school_code']) : ''; ?>" />
                <div class="search-icon">
                  <i class="fas fa-search"></i>
                </div>
                <div class="clear-btn" id="clearSchoolSearch" style="display: none;">
                  <i class="fas fa-times"></i>
                </div>
              </div>
              <div id="schoolResults" class="modern-search-results"></div>
            </div>
            <div class="help-text">
              <i class="fas fa-info-circle"></i> 輸入學校名稱即可即時搜尋，請從搜尋結果中選擇學校（不能自行輸入）
            </div>
            <div id="student_school_error" class="field-error" style="display: none; color: #d32f2f; font-size: 13px; margin-top: 8px; padding: 8px 12px; background-color: #ffebee; border-left: 3px solid #d32f2f; border-radius: 4px; animation: slideDown 0.3s ease;">
              <i class="fas fa-exclamation-circle"></i> <span id="student_school_error_text">請從系統提供的選項中選擇學校，不能自行輸入</span>
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="student_grade">年級（選填）</label>
            <select id="student_grade" name="student_grade">
              <option value="">請選擇年級</option>
              <?php 
              // 只顯示國三 (J3) 和已畢業 (GRADUATED) - 被推薦人年級：國三、已畢業，關聯 identity_options.code
              $student_grades = [];
              if (isset($grades['J3'])) {
                  $student_grades['J3'] = $grades['J3']; // 國三，關聯 identity_options.code
              }
              if (isset($grades['GRADUATED'])) {
                  $student_grades['GRADUATED'] = $grades['GRADUATED']; // 已畢業（特殊狀態）
              }
              foreach ($student_grades as $code => $name): ?>
                <option value="<?php echo htmlspecialchars($code); ?>" 
                        <?php echo (isset($_POST['student_grade']) && $_POST['student_grade'] === $code) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($name); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="form-group">
            <label for="student_phone"> <span class="required">*</span> 聯絡電話 </label>
            <input type="tel" id="student_phone" name="student_phone" 
                   value="<?php echo isset($_POST['student_phone']) ? htmlspecialchars($_POST['student_phone']) : ''; ?>" 
                   pattern="[0-9]{10}" maxlength="10" placeholder="請輸入電話號碼" required>
            <small class="phone-hint" style="display: none; color: #d32f2f; font-size: 12px; margin-top: 4px;">電話號碼錯誤</small>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="student_email">電子郵件（選填）</label>
            <input type="email" id="student_email" name="student_email" 
                   value="<?php echo isset($_POST['student_email']) ? htmlspecialchars($_POST['student_email']) : ''; ?>">
          </div>
          
          <div class="form-group">
            <label for="student_line_id">LINE ID（選填）</label>
            <input type="text" id="student_line_id" name="student_line_id" 
                   value="<?php echo isset($_POST['student_line_id']) ? htmlspecialchars($_POST['student_line_id']) : ''; ?>">
          </div>
        </div>
      </div>

      <!-- 推薦資訊 -->
      <div class="form-section">
        <h3><i class="fas fa-heart"></i> 推薦資訊</h3>
        
        <div class="form-group full-width">
          <label for="recommendation_reason"> <span class="required">*</span> 推薦理由 </label>
          <textarea id="recommendation_reason" name="recommendation_reason" 
                    placeholder="請詳細說明推薦這位學生的理由，例如：學習態度、特殊才能、品格表現等..." required><?php echo isset($_POST['recommendation_reason']) ? htmlspecialchars($_POST['recommendation_reason']) : ''; ?></textarea>
        </div>

        <div class="form-group full-width">
          <label for="student_interest">學生興趣領域（選填）</label>
          <select id="student_interest" name="student_interest">
            <option value="">請選擇興趣領域</option>
            <?php 
            // 被推薦人科系（興趣）：關聯 departments.code
            foreach ($departments as $code => $name): ?>
              <option value="<?php echo htmlspecialchars($code); ?>" 
                      <?php echo (isset($_POST['student_interest']) && $_POST['student_interest'] === $code) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($name); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group full-width">
          <div class="document-item">
            <label>其他相關證明文件</label>
            <input type="file" id="proof_evidence" name="proof_evidence" 
                   accept="image/*,.pdf,.doc,.docx" class="file-input">
          </div>
          <div class="note">
            <i class="fas fa-info-circle"></i> 請上傳相關證明文件(支援PDF、JPG、PNG格式,單個文件大小不超過5MB)
          </div>
        </div>

        <div class="form-group full-width">
          <label for="additional_info">其他補充資訊（選填）</label>
          <textarea id="additional_info" name="additional_info" 
                    placeholder="其他您認為重要的資訊，例如：家庭背景、特殊經歷、未來規劃等..."><?php echo isset($_POST['additional_info']) ? htmlspecialchars($_POST['additional_info']) : ''; ?></textarea>
        </div>
      </div>

      <button type="submit" name="submit_recommendation" class="submit-btn">
        <i class="fas fa-paper-plane"></i> 提交推薦申請
      </button>
    </form>
    <?php endif; ?>
  </div>
</div>
</div>

<?php include("share/footer.php"); ?>

<script>
// 電話號碼驗證功能
document.addEventListener('DOMContentLoaded', function() {
    // 獲取兩個電話輸入框
    const recommenderPhone = document.getElementById('recommender_phone');
    const studentPhone = document.getElementById('student_phone');
    
    // 電話號碼驗證函數
    function setupPhoneValidation(phoneInput) {
        if (!phoneInput) return;
        
        const hint = phoneInput.nextElementSibling;
        
        // 驗證函數
        function validatePhone() {
            const value = phoneInput.value.trim();
            if (value.length > 0 && value.length !== 10) {
                // 顯示錯誤狀態
                if (hint && hint.classList.contains('phone-hint')) {
                    hint.style.display = 'block';
                }
                phoneInput.style.borderColor = '#d32f2f';
                phoneInput.style.borderWidth = '2px';
                phoneInput.classList.add('phone-error');
            } else {
                // 清除錯誤狀態
                if (hint && hint.classList.contains('phone-hint')) {
                    hint.style.display = 'none';
                }
                phoneInput.style.borderColor = '';
                phoneInput.style.borderWidth = '';
                phoneInput.classList.remove('phone-error');
            }
        }
        
        // 只允許輸入數字
        phoneInput.addEventListener('input', function(e) {
            // 移除非數字字符
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // 限制最大長度為10
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
            
            // 即時驗證
            validatePhone();
        });
        
        // 失去焦點時驗證
        phoneInput.addEventListener('blur', function() {
            validatePhone();
        });
        
        // 獲得焦點時也檢查（處理初始值）
        phoneInput.addEventListener('focus', function() {
            validatePhone();
        });
        
        // 頁面載入時檢查初始值
        validatePhone();
    }
    
    // 設置兩個電話輸入框的驗證
    setupPhoneValidation(recommenderPhone);
    setupPhoneValidation(studentPhone);
    
    // 表單提交驗證
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            let hasError = false;
            let firstErrorField = null;
            
            // 驗證推薦人電話
            if (recommenderPhone) {
                const phoneValue = recommenderPhone.value.trim();
                if (!phoneValue) {
                    hasError = true;
                    if (!firstErrorField) firstErrorField = recommenderPhone;
                    recommenderPhone.style.borderColor = '#d32f2f';
                    if (recommenderPhone.nextElementSibling && recommenderPhone.nextElementSibling.classList.contains('phone-hint')) {
                        recommenderPhone.nextElementSibling.textContent = '請填寫聯絡電話';
                        recommenderPhone.nextElementSibling.style.display = 'block';
                    }
                } else if (phoneValue.length !== 10 || !/^[0-9]{10}$/.test(phoneValue)) {
                    e.preventDefault();
                    hasError = true;
                    if (!firstErrorField) firstErrorField = recommenderPhone;
                    recommenderPhone.style.borderColor = '#d32f2f';
                    recommenderPhone.focus();
                    if (recommenderPhone.nextElementSibling && recommenderPhone.nextElementSibling.classList.contains('phone-hint')) {
                        recommenderPhone.nextElementSibling.textContent = '電話號碼必須為10位數字';
                        recommenderPhone.nextElementSibling.style.display = 'block';
                    }
                    alert('推薦人聯絡電話必須為10位數字！');
                    return false;
                } else {
                    recommenderPhone.style.borderColor = '';
                    if (recommenderPhone.nextElementSibling && recommenderPhone.nextElementSibling.classList.contains('phone-hint')) {
                        recommenderPhone.nextElementSibling.style.display = 'none';
                    }
                }
            }
            
            // 驗證學生電話
            if (studentPhone) {
                const phoneValue = studentPhone.value.trim();
                if (!phoneValue) {
                    hasError = true;
                    if (!firstErrorField) firstErrorField = studentPhone;
                    studentPhone.style.borderColor = '#d32f2f';
                    if (studentPhone.nextElementSibling && studentPhone.nextElementSibling.classList.contains('phone-hint')) {
                        studentPhone.nextElementSibling.textContent = '請填寫聯絡電話';
                        studentPhone.nextElementSibling.style.display = 'block';
                    }
                } else if (phoneValue.length !== 10 || !/^[0-9]{10}$/.test(phoneValue)) {
                    e.preventDefault();
                    hasError = true;
                    if (!firstErrorField) firstErrorField = studentPhone;
                    studentPhone.style.borderColor = '#d32f2f';
                    studentPhone.focus();
                    if (studentPhone.nextElementSibling && studentPhone.nextElementSibling.classList.contains('phone-hint')) {
                        studentPhone.nextElementSibling.textContent = '電話號碼必須為10位數字';
                        studentPhone.nextElementSibling.style.display = 'block';
                    }
                    alert('學生聯絡電話必須為10位數字！');
                    return false;
                } else {
                    studentPhone.style.borderColor = '';
                    if (studentPhone.nextElementSibling && studentPhone.nextElementSibling.classList.contains('phone-hint')) {
                        studentPhone.nextElementSibling.style.display = 'none';
                    }
                }
            }
            
            // 驗證就讀學校格式（必須從系統選項中選擇）
            const studentSchoolInput = document.getElementById('student_school');
            const studentSchoolCodeInput = document.getElementById('student_school_code');
            if (studentSchoolInput) {
                const studentSchool = studentSchoolInput.value.trim();
                const studentSchoolCode = studentSchoolCodeInput ? studentSchoolCodeInput.value.trim() : '';
                
                if (studentSchool) {
                    // 檢查是否有有效的 school_code（只要存在且不為空即可，不限制格式）
                    if (studentSchoolCode) {
                        // 有 school_code，檢查顯示格式是否為學校名稱 (縣市區)
                        const schoolFormatPattern = /^.+ \(.+\)$/;
                        if (!schoolFormatPattern.test(studentSchool)) {
                            e.preventDefault();
                            alert('請從系統提供的選項中選擇學校，不能自行輸入');
                            studentSchoolInput.focus();
                            studentSchoolInput.style.borderColor = '#d32f2f';
                            showSchoolError('請從系統提供的選項中選擇學校，不能自行輸入');
                            setTimeout(() => {
                                studentSchoolInput.style.borderColor = '';
                            }, 3000);
                            return false;
                        }
                    } else {
                        // 沒有 school_code，檢查格式是否為學校名稱 (縣市區)
                        const schoolFormatPattern = /^.+ \(.+\)$/;
                        if (!schoolFormatPattern.test(studentSchool)) {
                            e.preventDefault();
                            alert('請從系統提供的選項中選擇學校，不能自行輸入');
                            studentSchoolInput.focus();
                            studentSchoolInput.style.borderColor = '#d32f2f';
                            showSchoolError('請從系統提供的選項中選擇學校，不能自行輸入');
                            setTimeout(() => {
                                studentSchoolInput.style.borderColor = '';
                            }, 3000);
                            return false;
                        }
                    }
                }
            }
            
            if (hasError && firstErrorField) {
                e.preventDefault();
                firstErrorField.focus();
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
        });
    }
    
    // 初始化學校搜尋功能
    function initializeSchoolSearch() {
        const schoolInput = document.getElementById('student_school');
        const resultsDiv = document.getElementById('schoolResults');
        const clearBtn = document.getElementById('clearSchoolSearch');
        
        if (!schoolInput || !resultsDiv) {
            console.warn('學校搜尋元素未找到');
            return;
        }
        
        // 防抖函數
        let searchTimeout;
        const debounceSearch = (callback, delay) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(callback, delay);
        };
        
        // 輸入事件監聽
        schoolInput.addEventListener('input', function() {
            const keyword = this.value.trim();
            
            // 顯示/隱藏清除按鈕
            if (clearBtn) {
                clearBtn.style.display = keyword.length > 0 ? 'block' : 'none';
            }
            
            if (keyword.length === 0) {
                resultsDiv.classList.remove('show');
                // 當搜尋結果隱藏時，清除錯誤提示
                clearSchoolError();
                return;
            }
            
            // 防抖搜尋
            debounceSearch(() => {
                performSchoolSearch(keyword);
            }, 300);
        });
        
        // 失去焦點時立即驗證
        schoolInput.addEventListener('blur', function() {
            clearTimeout(schoolInput.validationTimeout);
            // 延遲一點驗證，讓點擊下拉選單項目的時間完成
            schoolInput.validationTimeout = setTimeout(validateSchoolInputImmediate, 200);
        });
        
        // 當輸入框獲得焦點時，如果已有錯誤且下拉選單未顯示，保持顯示
        schoolInput.addEventListener('focus', function() {
            const resultsDiv = document.getElementById('schoolResults');
            const value = this.value.trim();
            // 只有在下拉選單未顯示時才檢查錯誤
            if (value && !/^.+ \(.+\)$/.test(value) && 
                (!resultsDiv || !resultsDiv.classList.contains('show'))) {
                validateSchoolInput();
            }
        });
        
        // 清除按鈕事件
        if (clearBtn) {
            clearBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                schoolInput.value = '';
                const schoolCodeInput = document.getElementById('student_school_code');
                if (schoolCodeInput) {
                    schoolCodeInput.value = '';
                }
                schoolInput.removeAttribute('data-school-code');
                schoolInput.removeAttribute('data-school-name');
                resultsDiv.classList.remove('show');
                clearBtn.style.display = 'none';
                clearSchoolError();
                schoolInput.focus();
            });
        }
        
        // 鍵盤事件
        schoolInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                clearSchoolSearch();
            }
        });
        
        // 如果有初始值，顯示清除按鈕
        if (schoolInput.value) {
            if (clearBtn) {
                clearBtn.style.display = 'block';
            }
        }
    }
    
    // 執行學校搜尋
    function performSchoolSearch(keyword) {
        const resultsDiv = document.getElementById('schoolResults');
        const schoolInput = document.getElementById('student_school');
        
        if (keyword.length < 2) {
            resultsDiv.innerHTML = '<div class="search-result-item">請輸入至少2個字元</div>';
            resultsDiv.classList.add('show');
            // 當下拉選單顯示時，清除錯誤提示（用戶還在輸入中）
            clearSchoolError();
            return;
        }
        
        // 顯示載入中
        resultsDiv.innerHTML = '<div class="search-result-item"><i class="fas fa-spinner fa-spin"></i> 搜尋中...</div>';
        resultsDiv.classList.add('show');
        // 當下拉選單顯示時，清除錯誤提示（用戶還在選擇中）
        clearSchoolError();
        
        // 從API獲取搜尋結果
        fetch(`api/school_data_api.php?action=search&keyword=${encodeURIComponent(keyword)}&v=20241014-4`)
            .then(response => response.json())
            .then(data => {
                console.log('搜尋結果:', data);
                if (data.schools && data.schools.length > 0) {
                    resultsDiv.innerHTML = data.schools.map(school => {
                        let displayName = school.name;
                        let additionalInfo = '';
                        
                        if (school.all_names && school.all_names.length > 1) {
                            additionalInfo = `<div class="school-alternative-names">其他名稱: ${school.all_names.join(', ')}</div>`;
                        }
                        
                        return `<div class="search-result-item" onclick="selectSchool('${school.school_code || ''}', '${school.name.replace(/'/g, "\\'")}', '${school.city || ''}', '${school.district || ''}')">
                            <i class="fas fa-school"></i>
                            <div class="school-info">
                                <span class="school-name">${displayName}</span>
                                <span class="school-location">${school.city || ''} ${school.district || ''}</span>
                                ${additionalInfo}
                            </div>
                        </div>`;
                    }).join('');
                    
                    if (data.total > 20) {
                        resultsDiv.innerHTML += `<div class="search-result-item more-results">還有 ${data.total - 20} 個結果...</div>`;
                    }
                    // 當下拉選單顯示時，清除錯誤提示
                    clearSchoolError();
                } else {
                    resultsDiv.innerHTML = '<div class="search-result-item">找不到匹配的學校</div>';
                    // 即使找不到結果，下拉選單仍然顯示，所以清除錯誤提示
                    clearSchoolError();
                }
            })
            .catch(error => {
                console.error('搜尋錯誤:', error);
                resultsDiv.innerHTML = '<div class="search-result-item">搜尋失敗，請稍後再試</div>';
                // 即使搜尋失敗，下拉選單仍然顯示，所以清除錯誤提示
                clearSchoolError();
            });
    }
    
    // 清除學校輸入錯誤提示
    function clearSchoolError() {
        const errorDiv = document.getElementById('student_school_error');
        const input = document.getElementById('student_school');
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
        if (input) {
            input.style.borderColor = '';
            input.style.borderWidth = '';
            input.style.boxShadow = '';
        }
    }
    
    // 顯示學校輸入錯誤提示
    function showSchoolError(message) {
        const errorDiv = document.getElementById('student_school_error');
        const errorText = document.getElementById('student_school_error_text');
        const input = document.getElementById('student_school');
        
        if (errorDiv && errorText) {
            errorText.textContent = message || '請從系統提供的選項中選擇學校，不能自行輸入';
            errorDiv.style.display = 'block';
            // 添加動畫效果
            errorDiv.style.animation = 'none';
            setTimeout(() => {
                errorDiv.style.animation = 'slideDown 0.3s ease';
            }, 10);
        }
        
        if (input) {
            input.style.borderColor = '#d32f2f';
            input.style.borderWidth = '2px';
            input.style.boxShadow = '0 0 0 3px rgba(211, 47, 47, 0.1)';
        }
    }
    
    // 驗證學校輸入格式
    function validateSchoolInput() {
        const input = document.getElementById('student_school');
        if (!input) return;
        
        const value = input.value.trim();
        const resultsDiv = document.getElementById('schoolResults');
        
        // 如果為空，不顯示錯誤（由required屬性處理）
        if (!value) {
            clearSchoolError();
            return;
        }
        
        // 如果下拉選單正在顯示，表示用戶還在選擇中，不顯示錯誤
        if (resultsDiv && resultsDiv.classList.contains('show')) {
            clearSchoolError();
            return;
        }
        
        // 檢查格式：優先檢查隱藏欄位的 school_code，或檢查顯示格式
        const schoolCodeInput = document.getElementById('student_school_code');
        const hasValidCode = schoolCodeInput && schoolCodeInput.value.trim().length > 0;
        
        if (hasValidCode) {
            // 如果有 school_code（只要存在且不為空即可），檢查顯示格式是否為學校名稱 (縣市區)
            const schoolFormatPattern = /^.+ \(.+\)$/;
            if (!schoolFormatPattern.test(value)) {
                // 只有在下拉選單隱藏時才顯示錯誤
                showSchoolError('請從系統提供的選項中選擇學校，不能自行輸入');
            } else {
                clearSchoolError();
            }
        } else {
            // 沒有 school_code，檢查格式是否為學校名稱 (縣市區)
            const schoolFormatPattern = /^.+ \(.+\)$/;
            if (!schoolFormatPattern.test(value)) {
                // 只有在下拉選單隱藏時才顯示錯誤
                showSchoolError('請從系統提供的選項中選擇學校，不能自行輸入');
            } else {
                clearSchoolError();
            }
        }
    }
    
    // 立即驗證（不延遲）- 用於失去焦點時
    function validateSchoolInputImmediate() {
        validateSchoolInput();
    }
    
    // 清除搜尋
    function clearSchoolSearch() {
        const schoolInput = document.getElementById('student_school');
        const schoolCodeInput = document.getElementById('student_school_code');
        
        if (schoolInput) {
            schoolInput.value = '';
            schoolInput.removeAttribute('data-school-code');
            schoolInput.removeAttribute('data-school-name');
        }
        if (schoolCodeInput) {
            schoolCodeInput.value = '';
        }
        
        document.getElementById('schoolResults').classList.remove('show');
        document.getElementById('clearSchoolSearch').style.display = 'none';
        clearSchoolError();
    }
    
    // 選擇學校
    function selectSchool(schoolCode, schoolName, city, district) {
        const schoolInput = document.getElementById('student_school');
        const schoolCodeInput = document.getElementById('student_school_code');
        
        // 顯示學校名稱（格式：學校名稱 (縣市區)）
        const displayName = `${schoolName} (${city || ''}${district || ''})`;
        schoolInput.value = displayName;
        
        // 保存 school_code 到隱藏欄位
        if (schoolCodeInput) {
            schoolCodeInput.value = schoolCode;
        }
        
        // 同時保存到 data 屬性作為備份
        schoolInput.setAttribute('data-school-code', schoolCode);
        schoolInput.setAttribute('data-school-name', displayName);
        
        document.getElementById('schoolResults').classList.remove('show');
        const clearBtn = document.getElementById('clearSchoolSearch');
        if (clearBtn) {
            clearBtn.style.display = 'block';
        }
        
        // 清除錯誤提示（因為用戶已從系統選項中選擇）
        clearSchoolError();
    }
    
    // 將函數暴露到全局作用域
    window.selectSchool = selectSchool;
    
    // 點擊其他地方隱藏搜尋結果
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.modern-search-container')) {
            const resultsDiv = document.getElementById('schoolResults');
            if (resultsDiv && resultsDiv.classList.contains('show')) {
                resultsDiv.classList.remove('show');
                // 當下拉選單隱藏時，驗證輸入
                setTimeout(validateSchoolInput, 100);
            }
        }
    });
    
    // 初始化學校搜尋功能
    initializeSchoolSearch();
});

// 檔案上傳區域互動功能
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('proof_evidence');
    const documentItem = document.querySelector('.document-item');
    
    // 檔案選擇後的視覺反饋
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                this.style.borderColor = '#28a745';
                this.style.backgroundColor = '#f8fff9';
            } else {
                this.style.borderColor = '#ddd';
                this.style.backgroundColor = 'white';
            }
        });
    }
});
</script>

<style>
/* 電話號碼錯誤樣式 */
.phone-error {
  border-color: #d32f2f !important;
  border-width: 2px !important;
  box-shadow: 0 0 0 3px rgba(211, 47, 47, 0.1) !important;
}

.phone-hint {
  display: block;
  color: #d32f2f;
  font-size: 12px;
  margin-top: 4px;
  font-weight: 500;
}

/* 詳細資訊卡片樣式 */
.detail-card {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  margin-bottom: 30px;
  overflow: hidden;
}

.detail-header {
  background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
  color: white;
  padding: 20px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.detail-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.detail-header h3 i {
  margin-right: 8px;
}

.close-btn {
  color: white;
  font-size: 20px;
  text-decoration: none;
  padding: 8px 12px;
  border-radius: 4px;
  transition: background 0.3s;
}

.close-btn:hover {
  background: rgba(255,255,255,0.2);
}

.detail-content {
  padding: 24px;
}

.detail-section {
  margin-bottom: 30px;
  padding-bottom: 30px;
  border-bottom: 1px solid #e9ecef;
}

.detail-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.detail-section h4 {
  color: #495057;
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid #e9ecef;
}

.detail-section h4 i {
  margin-right: 8px;
  color: #667eea;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.detail-label {
  font-weight: 600;
  color: #6c757d;
  font-size: 14px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-value {
  color: #212529;
  font-size: 16px;
  line-height: 1.6;
  word-break: break-word;
}

.detail-value .status,
.detail-value .enrollment-status {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 500;
}

.file-link {
  color: #007bff;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  border: 1px solid #007bff;
  border-radius: 4px;
  transition: all 0.3s;
}

.file-link:hover {
  background: #007bff;
  color: white;
}

/* 狀態標籤樣式 */
.status-pending {
  background: #fff7e6;
  color: #d46b08;
  border: 1px solid #ffd591;
}

.status-contacted {
  background: #e6f7ff;
  color: #0958d9;
  border: 1px solid #91d5ff;
}

.status-registered {
  background: #f6ffed;
  color: #52c41a;
  border: 1px solid #b7eb8f;
}

.status-rejected {
  background: #fff2f0;
  color: #cf1322;
  border: 1px solid #ffa39e;
}

.enrollment-未入學 {
  background: #f5f5f5;
  color: #8c8c8c;
}

.enrollment-已入學 {
  background: #f6ffed;
  color: #52c41a;
}

.enrollment-放棄入學 {
  background: #fff7e6;
  color: #fa8c16;
}

/* 結果篩選樣式 */
.result-filter {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
}

.filter-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.filter-header h5 {
  margin: 0;
  color: #495057;
  font-size: 16px;
}

.result-count {
  background: #007bff;
  color: white;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.filter-inputs {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 15px;
  align-items: end;
  margin-bottom: 10px;
}

.filter-group {
  display: flex;
  flex-direction: column;
}

.filter-group label {
  font-weight: 600;
  margin-bottom: 5px;
  color: #333;
  font-size: 14px;
}

.filter-group input {
  padding: 8px 12px;
  border: 2px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.3s ease;
}

.filter-group input:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.filter-btn, .clear-filter-btn {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}

.filter-btn {
  background: #28a745;
  color: white;
}

.filter-btn:hover {
  background: #218838;
  transform: translateY(-1px);
}

.clear-filter-btn {
  background: #6c757d;
  color: white;
}

.clear-filter-btn:hover {
  background: #545b62;
  transform: translateY(-1px);
}

.filter-note {
  background: #e7f3ff;
  border: 1px solid #b3d9ff;
  border-radius: 6px;
  padding: 10px 12px;
  font-size: 12px;
  color: #0066cc;
  display: flex;
  align-items: center;
  gap: 6px;
}

.filter-note i {
  color: #007bff;
}

/* 隱藏的行 */
.result-row.hidden {
  display: none;
}

/* 響應式設計 */
@media (max-width: 768px) {
  .filter-inputs {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .filter-group:last-child {
    display: flex;
    flex-direction: row;
    gap: 10px;
  }
  
  .filter-btn, .clear-filter-btn {
    flex: 1;
    justify-content: center;
  }
}

.notification-toast {
  position: fixed;
  top: 20px;
  right: 20px;
  background: #28a745;
  color: white;
  padding: 15px 20px;
  border-radius: 5px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
  z-index: 1000;
  transform: translateX(100%);
  transition: transform 0.3s ease;
}

.notification-toast.show {
  transform: translateX(0);
}

.notification-toast.error {
  background: #dc3545;
}
</style>

<script>

// 顯示通知
function showNotification(message, type = 'success') {
  const toast = document.createElement('div');
  toast.className = `notification-toast ${type}`;
  toast.textContent = message;
  
  document.body.appendChild(toast);
  
  // 顯示動畫
  setTimeout(() => {
    toast.classList.add('show');
  }, 100);
  
  // 自動隱藏
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => {
      document.body.removeChild(toast);
    }, 300);
  }, 3000);
}

// 篩選結果功能
function applyFilter() {
  const studentNameFilter = document.getElementById('filter_student_name').value.toLowerCase().trim();
  const schoolFilter = document.getElementById('filter_school').value.toLowerCase().trim();
  const rows = document.querySelectorAll('.result-row');
  let visibleCount = 0;
  
  rows.forEach(row => {
    const studentName = row.getAttribute('data-student-name').toLowerCase();
    const school = row.getAttribute('data-school').toLowerCase();
    
    let showRow = true;
    
    // 檢查學生姓名篩選
    if (studentNameFilter && !studentName.includes(studentNameFilter)) {
      showRow = false;
    }
    
    // 檢查學校篩選
    if (schoolFilter && !school.includes(schoolFilter)) {
      showRow = false;
    }
    
    if (showRow) {
      row.classList.remove('hidden');
      visibleCount++;
    } else {
      row.classList.add('hidden');
    }
  });
  
  // 更新結果計數
  updateResultCount(visibleCount);
  

}

// 清除篩選
function clearFilter() {
  document.getElementById('filter_student_name').value = '';
  document.getElementById('filter_school').value = '';
  
  const rows = document.querySelectorAll('.result-row');
  rows.forEach(row => {
    row.classList.remove('hidden');
  });
  
  // 恢復原始計數
  updateResultCount(rows.length);
  showNotification('篩選已清除', 'success');
}

// 更新結果計數
function updateResultCount(count) {
  const resultCountElement = document.querySelector('.result-count');
  if (resultCountElement) {
    resultCountElement.textContent = `共 ${count} 筆記錄`;
  }
}

// 即時篩選功能
function setupRealTimeFilter() {
  const studentNameInput = document.getElementById('filter_student_name');
  const schoolInput = document.getElementById('filter_school');
  
  if (studentNameInput) {
    studentNameInput.addEventListener('input', applyFilter);
  }
  
  if (schoolInput) {
    schoolInput.addEventListener('input', applyFilter);
  }
}

// 為表格行添加data-id屬性
document.addEventListener('DOMContentLoaded', function() {
  const rows = document.querySelectorAll('.results-table tbody tr');
  rows.forEach((row, index) => {
    const idCell = row.querySelector('td:first-child');
    if (idCell) {
      row.setAttribute('data-id', idCell.textContent.trim());
    }
  });
  
  // 設置即時篩選
  setupRealTimeFilter();
});
</script>

<!-- 浮動助手組件 -->
<?php include("share/chat_widget.php"); ?>
<?php include("share/ai_widget.php"); ?>
</body>
</html>
