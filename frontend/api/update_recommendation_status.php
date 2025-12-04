<?php
// 更新推薦狀態的API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 載入配置
require_once '../session_config.php';
require_once '../config.php';
require_once '../config/email_notification_config.php';

// 檢查是否為POST請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '只允許POST請求']);
    exit;
}

// 檢查管理員權限
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || $_SESSION['role'] !== '管理員') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '需要管理員權限']);
    exit;
}

try {
    // 獲取POST數據
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('無效的JSON數據');
    }
    
    $recommendation_id = $input['recommendation_id'] ?? null;
    $new_status = $input['status'] ?? null;
    $enrollment_status = $input['enrollment_status'] ?? null;
    
    if (!$recommendation_id || !$new_status) {
        throw new Exception('缺少必要參數');
    }
    
    // 驗證狀態值
    $valid_statuses = ['pending', 'contacted', 'registered', 'rejected'];
    $valid_enrollment_statuses = ['未入學', '已入學', '放棄入學'];
    
    if (!in_array($new_status, $valid_statuses)) {
        throw new Exception('無效的狀態值');
    }
    
    if ($enrollment_status && !in_array($enrollment_status, $valid_enrollment_statuses)) {
        throw new Exception('無效的入學狀態值');
    }
    
    // 連接資料庫
    $conn = getDatabaseConnection();
    
    // 先獲取原始數據（從 admission_recommendations 表）
    $sql = "SELECT * FROM admission_recommendations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $recommendation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('找不到指定的推薦記錄');
    }
    
    $original_data = $result->fetch_assoc();
    $stmt->close();
    
    // 從關聯表獲取推薦人和學生信息（如果存在）
    // 查詢推薦人信息
    $recommender_data = [];
    $recommender_sql = "SELECT * FROM recommender WHERE recommendations_id = ? LIMIT 1";
    $recommender_stmt = $conn->prepare($recommender_sql);
    if ($recommender_stmt) {
        $recommender_stmt->bind_param("i", $recommendation_id);
        $recommender_stmt->execute();
        $recommender_result = $recommender_stmt->get_result();
        if ($recommender_result->num_rows > 0) {
            $recommender_data = $recommender_result->fetch_assoc();
        }
        $recommender_stmt->close();
    }
    
    // 查詢被推薦學生信息
    $recommended_data = [];
    $recommended_sql = "SELECT * FROM recommended WHERE recommendations_id = ? LIMIT 1";
    $recommended_stmt = $conn->prepare($recommended_sql);
    if ($recommended_stmt) {
        $recommended_stmt->bind_param("i", $recommendation_id);
        $recommended_stmt->execute();
        $recommended_result = $recommended_stmt->get_result();
        if ($recommended_result->num_rows > 0) {
            $recommended_data = $recommended_result->fetch_assoc();
        }
        $recommended_stmt->close();
    }
    
    // 獲取科系和年級映射表（用於代碼轉換）
    $departments = [];
    $departments_sql = "SELECT code, name FROM departments";
    $dept_result = $conn->query($departments_sql);
    if ($dept_result) {
        while ($row = $dept_result->fetch_assoc()) {
            $departments[$row['code']] = $row['name'];
        }
    }
    
    $grades = [];
    $grades_sql = "SELECT code, name FROM identity_options";
    $grade_result = $conn->query($grades_sql);
    if ($grade_result) {
        while ($row = $grade_result->fetch_assoc()) {
            $grades[$row['code']] = $row['name'];
        }
    }
    
    // 合併數據（優先使用關聯表的數據）
    $email_data_source = array_merge(
        $original_data,
        $recommender_data,
        $recommended_data
    );
    
    // 更新狀態
    $update_sql = "UPDATE admission_recommendations SET status = ?";
    $params = [$new_status];
    $types = "s";
    
    if ($enrollment_status) {
        $update_sql .= ", enrollment_status = ?";
        $params[] = $enrollment_status;
        $types .= "s";
    }
    
    $update_sql .= ", updated_at = NOW() WHERE id = ?";
    $params[] = $recommendation_id;
    $types .= "i";
    
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception('更新失敗: ' . $stmt->error);
    }
    
    $stmt->close();
    
    // 發送通知郵件
    $email_sent = false;
    $notification_type = '';
    
    // 審核通過通知
    if ($new_status === 'registered' && $original_data['status'] !== 'registered') {
        // 轉換科系代碼為名稱
        $recommender_department_name = $email_data_source['recommender_department'] ?? '';
        if (!empty($email_data_source['department']) && isset($departments[$email_data_source['department']])) {
            $recommender_department_name = $departments[$email_data_source['department']];
        } elseif (!empty($email_data_source['recommender_department_code']) && isset($departments[$email_data_source['recommender_department_code']])) {
            $recommender_department_name = $departments[$email_data_source['recommender_department_code']];
        }
        
        // 轉換年級代碼為名稱
        $student_grade_name = $email_data_source['student_grade'] ?? '';
        if (!empty($email_data_source['grade']) && isset($grades[$email_data_source['grade']])) {
            $student_grade_name = $grades[$email_data_source['grade']];
        } elseif (!empty($email_data_source['student_grade_code']) && isset($grades[$email_data_source['student_grade_code']])) {
            $student_grade_name = $grades[$email_data_source['student_grade_code']];
        } elseif ($email_data_source['grade'] === 'GRADUATED' || $email_data_source['student_grade_code'] === 'GRADUATED') {
            $student_grade_name = '已畢業';
        }
        
        $email_data = [
            'student_name' => $email_data_source['student_name'] ?? $email_data_source['name'] ?? '',
            'recommender_name' => $email_data_source['recommender_name'] ?? $email_data_source['name'] ?? '',
            'recommender_student_id' => $email_data_source['recommender_student_id'] ?? $email_data_source['id'] ?? '',
            'recommender_department' => $recommender_department_name, // 使用轉換後的名稱
            'student_school' => $email_data_source['student_school'] ?? $email_data_source['school'] ?? '',
            'student_grade' => $student_grade_name, // 使用轉換後的年級名稱
            'approval_time' => date('Y-m-d H:i:s')
        ];
        
        $email_sent = sendNotificationEmail(
            $email_data_source['recommender_email'] ?? $email_data_source['email'] ?? '',
            $email_data['recommender_name'],
            'approval_notification',
            $email_data
        );
        
        $notification_type = 'approval_notification';
    }
    
    // 入學確認通知
    if ($enrollment_status === '已入學' && $original_data['enrollment_status'] !== '已入學') {
        // 轉換科系代碼為名稱
        $recommender_department_name = $email_data_source['recommender_department'] ?? '';
        if (!empty($email_data_source['department']) && isset($departments[$email_data_source['department']])) {
            $recommender_department_name = $departments[$email_data_source['department']];
        } elseif (!empty($email_data_source['recommender_department_code']) && isset($departments[$email_data_source['recommender_department_code']])) {
            $recommender_department_name = $departments[$email_data_source['recommender_department_code']];
        }
        
        $email_data = [
            'student_name' => $email_data_source['student_name'] ?? $email_data_source['name'] ?? '',
            'recommender_name' => $email_data_source['recommender_name'] ?? $email_data_source['name'] ?? '',
            'recommender_student_id' => $email_data_source['recommender_student_id'] ?? $email_data_source['id'] ?? '',
            'recommender_department' => $recommender_department_name, // 使用轉換後的名稱
            'enrollment_time' => date('Y-m-d H:i:s')
        ];
        
        $email_sent = sendNotificationEmail(
            $email_data_source['recommender_email'] ?? $email_data_source['email'] ?? '',
            $email_data['recommender_name'],
            'enrollment_notification',
            $email_data
        );
        
        $notification_type = 'enrollment_notification';
    }
    
    // 記錄通知日誌
    $recommender_email = $email_data_source['recommender_email'] ?? $email_data_source['email'] ?? '';
    if ($email_sent && $notification_type) {
        logNotification(
            $recommendation_id,
            $notification_type,
            $recommender_email,
            'sent'
        );
    } elseif ($notification_type) {
        logNotification(
            $recommendation_id,
            $notification_type,
            $recommender_email,
            'failed'
        );
    }
    
    $conn->close();
    
    // 返回成功響應
    echo json_encode([
        'success' => true,
        'message' => '狀態更新成功',
        'email_sent' => $email_sent,
        'notification_type' => $notification_type
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
