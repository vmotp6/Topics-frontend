<?php
// 設定 JSON 回應標頭
header('Content-Type: application/json; charset=utf-8');

// 載入 session 配置
require_once '../session_config.php';
require_once '../config.php';

// 檢查是否已登入且為老師角色
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

$isTeacher = ($_SESSION['role'] === '老師' || $_SESSION['role'] === 'TEA' || $_SESSION['role'] === 'STA' || $_SESSION['role'] === '學校行政人員' || $_SESSION['role'] === 'AA');

if (!$isLoggedIn || !$isTeacher) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '權限不足']);
    exit;
}

// 確保資料表存在
function ensureTeacherFilesTable($conn) {
    $check = $conn->query("SHOW TABLES LIKE 'teacher_files'");
    if ($check->num_rows == 0) {
        $sql = "CREATE TABLE teacher_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_id INT NOT NULL,
            original_filename VARCHAR(255) NOT NULL,
            stored_filename VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size BIGINT NOT NULL,
            file_type VARCHAR(100),
            upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_teacher_id (teacher_id),
            INDEX idx_upload_time (upload_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $conn->query($sql);
    }
}

try {
    $conn = getDatabaseConnection();
    ensureTeacherFilesTable($conn);
    
    // 獲取當前老師的用戶ID
    $username = $_SESSION['username'];
    $teacher_stmt = $conn->prepare("
        SELECT u.id 
        FROM user u 
        WHERE u.username = ? AND (u.role = '老師' OR u.role = 'TEA' OR u.role = 'STA' OR u.role = 'AA')
    ");
    $teacher_stmt->bind_param("s", $username);
    $teacher_stmt->execute();
    $teacher_result = $teacher_stmt->get_result();
    $teacher = $teacher_result->fetch_assoc();
    
    if (!$teacher) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => '找不到老師資料']);
        exit;
    }
    
    $teacher_id = (int)$teacher['id'];
    $method = $_SERVER['REQUEST_METHOD'];
    
    // 處理上傳
    if ($method === 'POST') {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '檔案上傳失敗']);
            exit;
        }
        
        $file = $_FILES['file'];
        $original_filename = $file['name'];
        $file_size = $file['size'];
        $file_type = $file['type'];
        $tmp_name = $file['tmp_name'];
        
        // 檢查檔案大小（單個檔案限制 50GB = 50 * 1024 * 1024 * 1024 bytes）
        $max_file_size = 50 * 1024 * 1024 * 1024; // 50GB
        if ($file_size > $max_file_size) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '檔案大小超過 50GB 限制']);
            exit;
        }
        
        // 允許的檔案類型（擴展名檢查）
        $allowed_extensions = ['ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx', 'pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'zip', 'rar', '7z', 'txt', 'csv'];
        $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '不支援的檔案類型，僅支援：' . implode(', ', $allowed_extensions)]);
            exit;
        }
        
        // 創建上傳目錄
        $upload_dir = __DIR__ . '/../uploads/teacher_files/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // 生成唯一檔名
        $stored_filename = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $original_filename);
        $file_path = $upload_dir . $stored_filename;
        
        // 移動檔案
        if (move_uploaded_file($tmp_name, $file_path)) {
            // 儲存到資料庫（使用相對路徑）
            $relative_path = 'uploads/teacher_files/' . $stored_filename;
            $insert_stmt = $conn->prepare("
                INSERT INTO teacher_files (teacher_id, original_filename, stored_filename, file_path, file_size, file_type) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $insert_stmt->bind_param("isssis", $teacher_id, $original_filename, $stored_filename, $relative_path, $file_size, $file_type);
            
            if ($insert_stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => '檔案上傳成功',
                    'file_id' => $conn->insert_id,
                    'filename' => $original_filename,
                    'file_size' => $file_size,
                    'upload_time' => date('Y-m-d H:i:s')
                ]);
            } else {
                // 如果資料庫插入失敗，刪除已上傳的檔案
                unlink($file_path);
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => '檔案資訊儲存失敗']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '檔案移動失敗']);
        }
        exit;
    }
    
    // 處理刪除
    if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $file_id = isset($input['file_id']) ? (int)$input['file_id'] : 0;
        
        if ($file_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '缺少檔案ID']);
            exit;
        }
        
        // 檢查檔案是否屬於該老師
        $check_stmt = $conn->prepare("SELECT file_path FROM teacher_files WHERE id = ? AND teacher_id = ?");
        $check_stmt->bind_param("ii", $file_id, $teacher_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $file_data = $check_result->fetch_assoc();
        
        if (!$file_data) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '找不到檔案或無權限刪除']);
            exit;
        }
        
        // 刪除檔案
        $full_path = __DIR__ . '/../' . $file_data['file_path'];
        if (file_exists($full_path)) {
            unlink($full_path);
        }
        
        // 從資料庫刪除記錄
        $delete_stmt = $conn->prepare("DELETE FROM teacher_files WHERE id = ? AND teacher_id = ?");
        $delete_stmt->bind_param("ii", $file_id, $teacher_id);
        
        if ($delete_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => '檔案已刪除']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '刪除失敗']);
        }
        exit;
    }
    
    // 處理列表查詢
    if ($method === 'GET') {
        $files_stmt = $conn->prepare("
            SELECT id, original_filename, stored_filename, file_path, file_size, file_type, upload_time 
            FROM teacher_files 
            WHERE teacher_id = ? 
            ORDER BY upload_time DESC
        ");
        $files_stmt->bind_param("i", $teacher_id);
        $files_stmt->execute();
        $files_result = $files_stmt->get_result();
        $files = [];
        
        while ($row = $files_result->fetch_assoc()) {
            $files[] = [
                'id' => $row['id'],
                'original_filename' => $row['original_filename'],
                'file_size' => (int)$row['file_size'],
                'file_size_formatted' => formatFileSize($row['file_size']),
                'file_type' => $row['file_type'],
                'upload_time' => $row['upload_time']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'files' => $files
        ]);
        exit;
    }
    
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '方法不被允許']);
    
} catch (Exception $e) {
    error_log('Teacher Files API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '系統錯誤：' . $e->getMessage()]);
}

// 格式化檔案大小
function formatFileSize($bytes) {
    if ($bytes >= 1024 * 1024 * 1024) {
        return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
    } elseif ($bytes >= 1024 * 1024) {
        return round($bytes / (1024 * 1024), 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}
?>

