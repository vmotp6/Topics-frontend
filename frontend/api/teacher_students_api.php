<?php
// 設定 JSON 回應標頭
header('Content-Type: application/json; charset=utf-8');

// 載入 session 配置
require_once '../session_config.php';

// 檢查是否已登入且為老師角色
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

// 調試信息
$debug_info = [
    'session_logged_in' => isset($_SESSION['logged_in']) ? $_SESSION['logged_in'] : 'not_set',
    'session_username' => isset($_SESSION['username']) ? $_SESSION['username'] : 'not_set',
    'session_role' => isset($_SESSION['role']) ? $_SESSION['role'] : 'not_set',
    'is_logged_in' => $isLoggedIn
];

// 檢查角色：接受 '老師' 或 'TEA'
$isTeacher = ($_SESSION['role'] === '老師' || $_SESSION['role'] === 'TEA');

if (!$isLoggedIn || !$isTeacher) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => '權限不足',
        'debug' => $debug_info
    ]);
    exit;
}

// 引入資料庫設定
require_once '../config.php';

try {
    // 建立資料庫連接
    $conn = getDatabaseConnection();
    
    if (!$conn) {
        throw new Exception('資料庫連接失敗');
    }
    
    // 獲取當前老師的用戶ID
    $username = $_SESSION['username'];
    $teacher_stmt = $conn->prepare("
        SELECT u.id, u.username, u.name, t.department 
        FROM user u 
        LEFT JOIN teacher t ON u.id = t.user_id 
        WHERE u.username = ? AND (u.role = '老師' OR u.role = 'TEA')
    ");
    $teacher_stmt->bind_param("s", $username);
    $teacher_stmt->execute();
    $teacher_result = $teacher_stmt->get_result();
    $teacher = $teacher_result->fetch_assoc();
    
    if (!$teacher) {
        echo json_encode(['success' => false, 'message' => '找不到老師資料']);
        exit;
    }
    
    $teacher_id = $teacher['id'];
    
    // 獲取分配給此老師的就讀意願學生列表
    // 注意：intention 和 system 欄位不存在於 enrollment_intention 表，需從 enrollment_choices 表獲取
    $students_stmt = $conn->prepare("
        SELECT 
            ei.id,
            ei.name,
            ei.identity,
            ei.gender,
            ei.phone1,
            ei.phone2,
            ei.email,
            NULL as intention1,
            NULL as intention2,
            NULL as intention3,
            NULL as system1,
            NULL as system2,
            NULL as system3,
            ei.junior_high,
            ei.current_grade,
            ei.line_id,
            ei.facebook,
            ei.remarks,
            ei.assigned_teacher_id,
            ei.created_at,
            ei.updated_at as assigned_at,
            NULL as assigned_by,
            'enrollment_intention' as source_type
        FROM enrollment_intention ei
        WHERE ei.assigned_teacher_id = ?
        ORDER BY ei.created_at DESC
    ");
    $students_stmt->bind_param("i", $teacher_id);
    $students_stmt->execute();
    $students_result = $students_stmt->get_result();
    $enrollment_students = $students_result->fetch_all(MYSQLI_ASSOC);
    
    // 檢查 admission_recommendations 表是否有 assigned_teacher_id 欄位
    $check_column = $conn->query("SHOW COLUMNS FROM admission_recommendations LIKE 'assigned_teacher_id'");
    $has_assigned_teacher_id = $check_column && $check_column->num_rows > 0;
    
    // 檢查是否有 recommended 表（包含學生資訊）
    $check_recommended_table = $conn->query("SHOW TABLES LIKE 'recommended'");
    $has_recommended_table = $check_recommended_table && $check_recommended_table->num_rows > 0;
    
    // 檢查是否有 recommendation_assignment_logs 表
    $check_ral_table = $conn->query("SHOW TABLES LIKE 'recommendation_assignment_logs'");
    $has_ral_table = $check_ral_table && $check_ral_table->num_rows > 0;
    
    $recommendation_students = [];
    
    // 只有當 assigned_teacher_id 欄位存在時才查詢
    if ($has_assigned_teacher_id) {
        if ($has_recommended_table) {
            // 使用 recommended 表獲取學生資訊
            $recommendations_stmt = $conn->prepare("
                SELECT 
                    ar.id,
                    COALESCE(red.name, '') as name,
                    '學生' as identity,
                    NULL as gender,
                    COALESCE(red.phone, '') as phone1,
                    NULL as phone2,
                    COALESCE(red.email, '') as email,
                    ar.student_interest as intention1,
                    NULL as intention2,
                    NULL as intention3,
                    NULL as system1,
                    NULL as system2,
                    NULL as system3,
                    COALESCE(red.school, '') as junior_high,
                    COALESCE(red.grade, '') as current_grade,
                    COALESCE(red.line_id, '') as line_id,
                    NULL as facebook,
                    ar.additional_info as remarks,
                    ar.assigned_teacher_id,
                    ar.created_at,
                    " . ($has_ral_table ? "ral.assigned_at" : "NULL as assigned_at") . ",
                    " . ($has_ral_table ? "ral.assigned_by" : "NULL as assigned_by") . ",
                    'admission_recommendations' as source_type
                FROM admission_recommendations ar
                LEFT JOIN recommended red ON ar.id = red.recommendations_id
                " . ($has_ral_table ? "LEFT JOIN recommendation_assignment_logs ral ON ar.id = ral.recommendation_id AND ral.teacher_id = ?" : "") . "
                WHERE ar.assigned_teacher_id = ?
                ORDER BY ar.created_at DESC
            ");
            if ($has_ral_table) {
                $recommendations_stmt->bind_param("ii", $teacher_id, $teacher_id);
            } else {
                $recommendations_stmt->bind_param("i", $teacher_id);
            }
        } else {
            // 如果沒有 recommended 表，嘗試從 admission_recommendations 表直接獲取（如果欄位存在）
            // 但根據 SQL 檔案，admission_recommendations 表沒有這些欄位，所以返回空陣列
            $recommendation_students = [];
        }
        
        if (isset($recommendations_stmt)) {
            $recommendations_stmt->execute();
            $recommendations_result = $recommendations_stmt->get_result();
            $recommendation_students = $recommendations_result->fetch_all(MYSQLI_ASSOC);
        }
    }
    
    // 合併兩個列表
    $students = array_merge($enrollment_students, $recommendation_students);
    
    // 獲取統計資訊
    $total_students = count($students);
    $recent_assignments = 0;
    $current_date = date('Y-m-d');
    $week_ago = date('Y-m-d', strtotime('-7 days'));
    
    foreach ($students as $student) {
        if ($student['assigned_at'] && $student['assigned_at'] >= $week_ago) {
            $recent_assignments++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'teacher' => [
            'id' => $teacher['id'],
            'username' => $teacher['username'],
            'name' => $teacher['name'] ?? $teacher['username'],
            'department' => $teacher['department'] ?? '未設定科系'
        ],
        'students' => $students,
        'statistics' => [
            'total_students' => $total_students,
            'recent_assignments' => $recent_assignments
        ]
    ]);
    
    $conn->close();
    
} catch (Exception $e) {
    error_log('Teacher Students API Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => '系統錯誤：' . $e->getMessage(),
        'debug' => [
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'error_trace' => $e->getTraceAsString()
        ]
    ]);
}
?>
