<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '只支援POST請求']);
    exit;
}

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 讀取POST資料
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // 檢查必要欄位
    if (!isset($data['application_id']) || !isset($data['status']) || !isset($data['admin_username'])) {
        echo json_encode(['success' => false, 'message' => '缺少必要欄位']);
        exit;
    }
    
    // 檢查狀態值是否有效
    $valid_statuses = ['approved', 'rejected'];
    if (!in_array($data['status'], $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => '無效的審核狀態']);
        exit;
    }
    
    // 檢查申請表是否存在且狀態為待審核
    $check_sql = "SELECT id, status FROM cooperation_applications WHERE id = :id";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([':id' => $data['application_id']]);
    $application = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        echo json_encode(['success' => false, 'message' => '找不到指定的申請表']);
        exit;
    }
    
    if ($application['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => '此申請表已被審核過']);
        exit;
    }
    
    // 更新申請表狀態
    $update_sql = "UPDATE cooperation_applications SET 
                   status = :status,
                   admin_username = :admin_username,
                   admin_comment = :admin_comment,
                   review_date = NOW()
                   WHERE id = :id";
    
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([
        ':status' => $data['status'],
        ':admin_username' => $data['admin_username'],
        ':admin_comment' => $data['comment'] ?? null,
        ':id' => $data['application_id']
    ]);
    
    // 取得申請表資訊用於通知
    $app_info_sql = "SELECT teacher_username, project_title FROM cooperation_applications WHERE id = :id";
    $app_info_stmt = $pdo->prepare($app_info_sql);
    $app_info_stmt->execute([':id' => $data['application_id']]);
    $app_info = $app_info_stmt->fetch(PDO::FETCH_ASSOC);
    
    $status_text = $data['status'] === 'approved' ? '通過' : '拒絕';
    $message = "申請表「{$app_info['project_title']}」已審核{$status_text}";
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料庫錯誤: ' . $e->getMessage()]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => '系統錯誤: ' . $e->getMessage()]);
}
?>
