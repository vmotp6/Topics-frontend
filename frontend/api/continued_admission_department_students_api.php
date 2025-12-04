<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理 OPTIONS 請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // 引入資料庫設定
    $config_path = '../config.php';
    if (!file_exists($config_path)) {
        echo json_encode(['error' => '找不到設定檔案: ' . $config_path], JSON_UNESCAPED_UNICODE);
        exit;
    }
    require_once $config_path;

    // 獲取科系名稱參數
    $department = isset($_GET['department']) ? trim($_GET['department']) : '';

    if (empty($department)) {
        echo json_encode(['error' => '科系名稱不能為空'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 建立資料庫連接
    $conn = getDatabaseConnection();

    if (!$conn) {
        echo json_encode(['error' => '資料庫連接失敗: ' . mysqli_connect_error()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 查詢該科系的學生資料
    $students = [];

    // 檢查 continued_admission 表是否存在
    $table_check = $conn->query("SHOW TABLES LIKE 'continued_admission'");
    if (!$table_check || $table_check->num_rows == 0) {
        echo json_encode(['error' => '找不到 continued_admission 表'], JSON_UNESCAPED_UNICODE);
        $conn->close();
        exit;
    }

    // 檢查 continued_admission_choices 表是否存在
    $choices_table_check = $conn->query("SHOW TABLES LIKE 'continued_admission_choices'");
    $has_choices_table = ($choices_table_check && $choices_table_check->num_rows > 0);

    // 檢查 departments 表是否存在（用於科系名稱匹配）
    $dept_table_check = $conn->query("SHOW TABLES LIKE 'departments'");
    $has_departments_table = ($dept_table_check && $dept_table_check->num_rows > 0);

    // 檢查 school_data 表是否存在（用於學校名稱）
    $school_table_check = $conn->query("SHOW TABLES LIKE 'school_data'");
    $has_school_table = ($school_table_check && $school_table_check->num_rows > 0);

    if ($has_choices_table) {
        // 使用 continued_admission_choices 表（新結構）
        // 首先嘗試通過科系名稱或代碼匹配
        $department_code = '';
        if ($has_departments_table) {
            // 查詢科系代碼（可能是名稱或代碼）
            $dept_query = "SELECT code FROM departments WHERE name = ? OR code = ? LIMIT 1";
            $dept_stmt = $conn->prepare($dept_query);
            if ($dept_stmt) {
                $dept_stmt->bind_param('ss', $department, $department);
                $dept_stmt->execute();
                $dept_result = $dept_stmt->get_result();
                if ($dept_row = $dept_result->fetch_assoc()) {
                    $department_code = $dept_row['code'];
                }
                $dept_stmt->close();
            }
        }

        // 構建 SQL 查詢
        $school_join = $has_school_table 
            ? "LEFT JOIN school_data sd ON ca.school = sd.school_code"
            : "";
        $school_select = $has_school_table 
            ? "COALESCE(sd.name, ca.school, '未填寫') as school_name,"
            : "COALESCE(ca.school, '未填寫') as school_name,";

        if (!empty($department_code)) {
            // 使用科系代碼查詢
            $sql = "SELECT DISTINCT 
                        ca.id,
                        ca.name,
                        ca.apply_no,
                        $school_select
                        ca.phone,
                        ca.mobile,
                        ca.created_at,
                        cac.choice_order,
                        cac.department_code,
                        COALESCE(d.name, cac.department_code, '未知科系') as department_name
                    FROM continued_admission ca
                    INNER JOIN continued_admission_choices cac ON ca.id = cac.application_id
                    LEFT JOIN departments d ON cac.department_code = d.code
                    $school_join
                    WHERE cac.department_code = ?
                    ORDER BY ca.created_at DESC, cac.choice_order ASC";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('s', $department_code);
            }
        } else {
            // 使用科系名稱查詢（通過 departments 表）
            if ($has_departments_table) {
                $sql = "SELECT DISTINCT 
                            ca.id,
                            ca.name,
                            ca.apply_no,
                            $school_select
                            ca.phone,
                            ca.mobile,
                            ca.created_at,
                            cac.choice_order,
                            cac.department_code,
                            COALESCE(d.name, cac.department_code, '未知科系') as department_name
                        FROM continued_admission ca
                        INNER JOIN continued_admission_choices cac ON ca.id = cac.application_id
                        LEFT JOIN departments d ON cac.department_code = d.code
                        $school_join
                        WHERE d.name = ? OR cac.department_code = ?
                        ORDER BY ca.created_at DESC, cac.choice_order ASC";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('ss', $department, $department);
                }
            } else {
                // 如果沒有 departments 表，直接使用科系名稱匹配
                $sql = "SELECT DISTINCT 
                            ca.id,
                            ca.name,
                            ca.apply_no,
                            $school_select
                            ca.phone,
                            ca.mobile,
                            ca.created_at,
                            cac.choice_order,
                            cac.department_code,
                            cac.department_code as department_name
                        FROM continued_admission ca
                        INNER JOIN continued_admission_choices cac ON ca.id = cac.application_id
                        $school_join
                        WHERE cac.department_code = ?
                        ORDER BY ca.created_at DESC, cac.choice_order ASC";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('s', $department);
                }
            }
        }

        if (!$stmt) {
            echo json_encode(['error' => 'SQL準備失敗: ' . $conn->error], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }

        if (!$stmt->execute()) {
            echo json_encode(['error' => 'SQL執行失敗: ' . $stmt->error], JSON_UNESCAPED_UNICODE);
            $stmt->close();
            $conn->close();
            exit;
        }

        $result = $stmt->get_result();
        $student_map = []; // 用於去重，因為一個學生可能有多個志願

        while ($row = $result->fetch_assoc()) {
            $student_id = $row['id'];
            
            if (!isset($student_map[$student_id])) {
                // 獲取該學生的所有志願
                $choices_stmt = $conn->prepare("
                    SELECT cac.choice_order, d.name as department_name, cac.department_code
                    FROM continued_admission_choices cac
                    LEFT JOIN departments d ON cac.department_code = d.code
                    WHERE cac.application_id = ?
                    ORDER BY cac.choice_order ASC
                ");
                $choices_stmt->bind_param('i', $student_id);
                $choices_stmt->execute();
                $choices_result = $choices_stmt->get_result();
                $choices_list = [];
                while ($choice_row = $choices_result->fetch_assoc()) {
                    $choices_list[] = $choice_row['department_name'] ?? $choice_row['department_code'];
                }
                $choices_stmt->close();

                $student_data = [
                    'name' => $row['name'] ?? '未填寫',
                    'school' => $row['school_name'] ?? '未填寫',
                    'grade' => '未填寫', // continued_admission表中沒有年級欄位
                    'department' => $department, // 顯示查詢的科系
                    'created_at' => $row['created_at'] ?? '未填寫',
                    'choices' => implode('、', $choices_list) // 所有志願
                ];
                
                // 添加聯絡電話
                if (isset($row['phone'])) {
                    $student_data['phone1'] = $row['phone'] ?? '未填寫';
                }
                if (isset($row['mobile'])) {
                    $student_data['phone2'] = $row['mobile'] ?? '未填寫';
                }
                
                $student_map[$student_id] = $student_data;
            }
        }
        $stmt->close();
        
        // 轉換為陣列
        $students = array_values($student_map);
    } else {
        // 如果沒有 continued_admission_choices 表，返回錯誤
        echo json_encode(['error' => '找不到 continued_admission_choices 表，請確認資料庫結構'], JSON_UNESCAPED_UNICODE);
        $conn->close();
        exit;
    }

    // 關閉資料庫連接
    $conn->close();

    // 返回學生資料
    echo json_encode($students, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('API錯誤: ' . $e->getMessage());
    error_log('API錯誤堆疊: ' . $e->getTraceAsString());
    echo json_encode(['error' => '伺服器內部錯誤: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

// Helper function for bind_param
function refValues($arr){
    if (strnatcmp(phpversion(),'5.3') >= 0) //Reference is required for PHP 5.3+
    {
        $refs = array();
        foreach($arr as $key => $value)
            $refs[$key] = &$arr[$key];
        return $refs;
    }
    return $arr;
}
?>
