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

    // 查詢 continued_admission 表
    $table_check = $conn->query("SHOW TABLES LIKE 'continued_admission'");
    if ($table_check && $table_check->num_rows > 0) {
        // 檢查 continued_admission 表的欄位結構
        $columns_result = $conn->query("DESCRIBE continued_admission");
        $available_columns = [];
        while ($column = $columns_result->fetch_assoc()) {
            $available_columns[] = $column['Field'];
        }
        
        // 根據可用欄位動態構建SELECT語句
        $select_fields = [];
        $field_mapping = [
            'name' => 'name',
            'school_name' => 'school',
            'phone' => 'phone1',
            'mobile' => 'phone2',
            'choices' => 'choices',
            'created_at' => 'created_at'
        ];
        
        foreach ($field_mapping as $field => $alias) {
            if (in_array($field, $available_columns)) {
                $select_fields[] = $field;
            }
        }
        
        if (empty($select_fields)) {
            echo json_encode(['error' => 'continued_admission表中沒有可用的欄位'], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }

        // 檢查是否有志願相關的欄位
        if (!in_array('choices', $available_columns)) {
            echo json_encode(['error' => '找不到choices欄位，可用欄位: ' . implode(', ', $available_columns)], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }

        // 構建WHERE子句 - 查詢choices欄位包含該科系
        $sql = "SELECT " . implode(', ', $select_fields) . " FROM continued_admission WHERE choices LIKE ? ORDER BY created_at DESC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['error' => 'SQL準備失敗: ' . $conn->error], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }

        // 使用LIKE查詢，因為choices可能包含多個志願
        $search_pattern = '%' . $department . '%';
        $stmt->bind_param('s', $search_pattern);

        if (!$stmt->execute()) {
            echo json_encode(['error' => 'SQL執行失敗: ' . $stmt->error], JSON_UNESCAPED_UNICODE);
            $stmt->close();
            $conn->close();
            exit;
        }
        
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $student_data = [
                'name' => $row['name'] ?? '未填寫',
                'school' => $row['school_name'] ?? '未填寫',
                'grade' => '未填寫', // continued_admission表中沒有年級欄位
                'department' => $department, // 顯示查詢的科系
                'created_at' => $row['created_at'] ?? '未填寫'
            ];
            
            // 添加聯絡電話
            if (isset($row['phone'])) {
                $student_data['phone1'] = $row['phone'] ?? '未填寫';
            }
            if (isset($row['mobile'])) {
                $student_data['phone2'] = $row['mobile'] ?? '未填寫';
            }
            
            // 添加志願資訊
            if (isset($row['choices'])) {
                $student_data['choices'] = $row['choices'] ?? '未填寫';
            }
            
            $students[] = $student_data;
        }
        $stmt->close();
    } else {
        // 如果沒有 continued_admission 表，返回錯誤
        echo json_encode(['error' => '找不到 continued_admission 表'], JSON_UNESCAPED_UNICODE);
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
