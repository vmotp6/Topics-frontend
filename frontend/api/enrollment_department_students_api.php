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
    
    // 查詢 enrollment_intention 表
    $table_check = $conn->query("SHOW TABLES LIKE 'enrollment_intention'");
    if ($table_check && $table_check->num_rows > 0) {
        // 檢查 enrollment_intention 表的欄位結構
        $columns_result = $conn->query("DESCRIBE enrollment_intention");
        $available_columns = [];
        while ($column = $columns_result->fetch_assoc()) {
            $available_columns[] = $column['Field'];
        }
        
        // 根據可用欄位動態構建SELECT語句
        $select_fields = [];
        $field_mapping = [
            'name' => 'name',
            'junior_high' => 'school',
            'current_grade' => 'grade', 
            'phone1' => 'phone1',
            'phone2' => 'phone2',
            'intention1' => 'intention1',
            'intention2' => 'intention2',
            'intention3' => 'intention3',
            'created_at' => 'created_at'
        ];
        
        foreach ($field_mapping as $field => $alias) {
            if (in_array($field, $available_columns)) {
                $select_fields[] = $field;
            }
        }
        
        if (empty($select_fields)) {
            echo json_encode(['error' => 'enrollment_intention表中沒有可用的欄位'], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }
        
        // 檢查是否有意願相關的欄位
        $intention_fields = ['intention1', 'intention2', 'intention3'];
        $available_intention_fields = [];
        foreach ($intention_fields as $field) {
            if (in_array($field, $available_columns)) {
                $available_intention_fields[] = $field;
            }
        }
        
        if (empty($available_intention_fields)) {
            echo json_encode(['error' => '找不到意願相關欄位，可用欄位: ' . implode(', ', $available_columns)], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }
        
        // 構建WHERE子句 - 查詢任一意願欄位包含該科系
        $where_conditions = [];
        foreach ($available_intention_fields as $field) {
            $where_conditions[] = $field . " = ?";
        }
        $where_clause = "(" . implode(" OR ", $where_conditions) . ")";
        
        // 為了支援多個參數，我們需要重複綁定參數
        $sql = "SELECT " . implode(', ', $select_fields) . " FROM enrollment_intention WHERE " . $where_clause . " ORDER BY created_at DESC";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['error' => 'SQL準備失敗: ' . $conn->error], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }
        
        // 為每個意願欄位綁定相同的科系名稱
        $param_types = str_repeat("s", count($available_intention_fields));
        $params = array_fill(0, count($available_intention_fields), $department);
        $stmt->bind_param($param_types, ...$params);
        
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
                'school' => $row['junior_high'] ?? '未填寫',
                'grade' => $row['current_grade'] ?? '未填寫',
                'department' => $department, // 顯示查詢的科系
                'created_at' => $row['created_at'] ?? '未填寫'
            ];
            
            // 添加聯絡電話
            if (isset($row['phone1'])) {
                $student_data['phone1'] = $row['phone1'] ?? '未填寫';
            }
            if (isset($row['phone2'])) {
                $student_data['phone2'] = $row['phone2'] ?? '未填寫';
            }
            
            // 添加意願資訊
            if (isset($row['intention1'])) {
                $student_data['intention1'] = $row['intention1'] ?? '未填寫';
            }
            if (isset($row['intention2'])) {
                $student_data['intention2'] = $row['intention2'] ?? '未填寫';
            }
            if (isset($row['intention3'])) {
                $student_data['intention3'] = $row['intention3'] ?? '未填寫';
            }
            
            $students[] = $student_data;
        }
        $stmt->close();
    } else {
        // 如果沒有 enrollment_intention 表，返回錯誤
        echo json_encode(['error' => '找不到 enrollment_intention 表'], JSON_UNESCAPED_UNICODE);
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
?>
