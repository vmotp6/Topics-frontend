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
    
    // 先獲取原始數據
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
        $email_data = [
            'student_name' => $original_data['student_name'],
            'recommender_name' => $original_data['recommender_name'],
            'recommender_student_id' => $original_data['recommender_student_id'],
            'recommender_department' => $original_data['recommender_department'],
            'student_school' => $original_data['student_school'],
            'student_grade' => $original_data['student_grade'],
            'approval_time' => date('Y-m-d H:i:s')
        ];
        
        $email_sent = sendNotificationEmail(
            $original_data['recommender_email'],
            $original_data['recommender_name'],
            'approval_notification',
            $email_data
        );
        
        $notification_type = 'approval_notification';
    }
    
    // 入學確認通知
    if ($enrollment_status === '已入學' && $original_data['enrollment_status'] !== '已入學') {
        $email_data = [
            'student_name' => $original_data['student_name'],
            'recommender_name' => $original_data['recommender_name'],
            'recommender_student_id' => $original_data['recommender_student_id'],
            'recommender_department' => $original_data['recommender_department'],
            'enrollment_time' => date('Y-m-d H:i:s')
        ];
        
        $email_sent = sendNotificationEmail(
            $original_data['recommender_email'],
            $original_data['recommender_name'],
            'enrollment_notification',
            $email_data
        );
        
        $notification_type = 'enrollment_notification';
    }
    
    // 記錄通知日誌
    if ($email_sent && $notification_type) {
        logNotification(
            $recommendation_id,
            $notification_type,
            $original_data['recommender_email'],
            'sent'
        );
    } elseif ($notification_type) {
        logNotification(
            $recommendation_id,
            $notification_type,
            $original_data['recommender_email'],
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
