<?php
// 載入 session 與資料庫設定
// 先嘗試讀取後台的 session（如果存在）
// 後台使用預設的 session name，前台使用 'KANGNING_SESSION'
$backend_session_name = 'PHPSESSID'; // 後台通常使用預設的 PHPSESSID
$has_backend_cookie = isset($_COOKIE[$backend_session_name]) && !isset($_COOKIE['KANGNING_SESSION']);

if ($has_backend_cookie && session_status() === PHP_SESSION_NONE) {
    // 如果有後台的 session cookie，使用預設 session name 啟動 session
    session_name($backend_session_name);
    session_start();
    // 載入資料庫設定（不載入 session_config.php，因為它會設定不同的 session name）
    require_once '../config.php';
} else {
    // 否則使用前台的 session config
    require_once '../session_config.php';
    require_once '../config.php';
}

header('Content-Type: application/json; charset=utf-8');

// 注意：實際資料表結構為 enrollment_id, notes (不是 student_id, result, follow_up_notes)
// 此函數保留用於向後兼容，但實際應使用現有的 enrollment_contact_logs 表
function ensureContactLogsTable($conn) {
    // 檢查表是否存在，如果不存在則創建（使用實際的欄位名稱）
    $check = $conn->query("SHOW TABLES LIKE 'enrollment_contact_logs'");
    if ($check->num_rows == 0) {
        $sql = "CREATE TABLE enrollment_contact_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            enrollment_id INT NOT NULL,
            teacher_id INT NOT NULL,
            contact_date DATE NOT NULL,
            method VARCHAR(20) NOT NULL,
            notes TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_enrollment_id (enrollment_id),
            INDEX idx_teacher_id (teacher_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $conn->query($sql);
    }
}

try {
    $conn = getDatabaseConnection();
    ensureContactLogsTable($conn);

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        // 僅老師可新增聯絡紀錄（支援 '老師' 或 'TEA'）
        $isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
                     isset($_SESSION['username']) && !empty($_SESSION['username']) &&
                     isset($_SESSION['role']) && !empty($_SESSION['role']);
        $isTeacher = ($_SESSION['role'] === '老師' || $_SESSION['role'] === 'TEA' || $_SESSION['role'] === 'STA' || $_SESSION['role'] === '學校行政人員');
        
        if (!$isLoggedIn || !$isTeacher) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '權限不足']);
            exit;
        }

        // 取得老師 user.id
        $username = $_SESSION['username'];
        $tstmt = $conn->prepare("SELECT u.id FROM user u WHERE u.username = ? AND (u.role = '老師' OR u.role = 'TEA' OR u.role = 'STA')");
        $tstmt->bind_param('s', $username);
        $tstmt->execute();
        $tres = $tstmt->get_result();
        $teacher = $tres->fetch_assoc();
        if (!$teacher) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '找不到老師']);
            exit;
        }
        $teacher_id = (int)$teacher['id'];

        // 讀取輸入
        $input = $_POST;
        if (empty($input)) {
            // 支援 JSON 請求
            $raw = file_get_contents('php://input');
            $json = json_decode($raw, true);
            if (is_array($json)) { $input = $json; }
        }

        // 支援 student_id 或 enrollment_id（向後兼容）
        $enrollment_id = isset($input['enrollment_id']) ? (int)$input['enrollment_id'] : 
                        (isset($input['student_id']) ? (int)$input['student_id'] : 0);
        $contact_date = isset($input['contact_date']) ? trim($input['contact_date']) : date('Y-m-d');
        // 支援 method 或 contact_method（向後兼容）
        $method = isset($input['method']) ? trim($input['method']) : 
                 (isset($input['contact_method']) ? trim($input['contact_method']) : '');
        
        // 優先使用 notes，如果沒有則合併 result 和 follow_up_notes（向後兼容）
        if (isset($input['notes']) && !empty(trim($input['notes']))) {
            $notes = trim($input['notes']);
        } else {
            $contact_result = isset($input['contact_result']) ? trim($input['contact_result']) : '';
            $follow_up_notes = isset($input['follow_up_notes']) ? trim($input['follow_up_notes']) : '';
            $notes = $contact_result;
            if (!empty($follow_up_notes)) {
                $notes .= ($notes ? "\n\n後續追蹤備註：\n" : '') . $follow_up_notes;
            }
        }

        if ($enrollment_id <= 0 || $method === '' || $notes === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '缺少必要欄位']);
            exit;
        }

        // 僅能對分配給自己的學生新增紀錄
        $astmt = $conn->prepare("SELECT 1 FROM enrollment_intention WHERE id = ? AND assigned_teacher_id = ?");
        $astmt->bind_param('ii', $enrollment_id, $teacher_id);
        $astmt->execute();
        $ares = $astmt->get_result();
        if ($ares->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '僅能新增分配給您的學生']);
            exit;
        }

        // 寫入資料（使用實際的欄位名稱：enrollment_id, method, notes）
        $stmt = $conn->prepare("INSERT INTO enrollment_contact_logs (enrollment_id, teacher_id, contact_date, method, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('iisss', $enrollment_id, $teacher_id, $contact_date, $method, $notes);
        $ok = $stmt->execute();

        if ($ok) {
            echo json_encode(['success' => true, 'id' => $conn->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '寫入失敗']);
        }
        exit;
    }

    if ($method === 'GET') {
        // 允許老師查詢自己學生的紀錄，或後台管理端查詢（主任可查看自己科系老師的紀錄）
        $isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
        $isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
                     isset($_SESSION['username']) && !empty($_SESSION['username']) &&
                     isset($_SESSION['role']) && !empty($_SESSION['role']);
        $user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
        $isTeacher = ($user_role === '老師' || $user_role === 'TEA' || $user_role === 'STA' || $user_role === '學校行政人員' || $user_role === 'AA');
        $isDirector = ($user_role === 'DI');
        $isAdmissionCenter = in_array($user_role, ['ADM', 'STA']);
        
        if (!$isAdmin && (!$isLoggedIn || !$isTeacher)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '權限不足']);
            exit;
        }

        // 支援 student_id 或 enrollment_id（向後兼容）
        $enrollment_id = isset($_GET['enrollment_id']) ? (int)$_GET['enrollment_id'] : 
                        (isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0);
        if ($enrollment_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '缺少 enrollment_id 或 student_id']);
            exit;
        }

        // 如果是老師（非管理端），則檢查是否為分配給自己的學生
        if (!$isAdmin) {
            $username = $_SESSION['username'];
            $tstmt = $conn->prepare("SELECT u.id FROM user u WHERE u.username = ? AND (u.role = '老師' OR u.role = 'TEA' OR u.role = 'AA')");
            $tstmt->bind_param('s', $username);
            $tstmt->execute();
            $tres = $tstmt->get_result();
            $teacher = $tres->fetch_assoc();
            $teacher_id = $teacher ? (int)$teacher['id'] : 0;

            $astmt = $conn->prepare("SELECT 1 FROM enrollment_intention WHERE id = ? AND assigned_teacher_id = ?");
            $astmt->bind_param('ii', $enrollment_id, $teacher_id);
            $astmt->execute();
            $ares = $astmt->get_result();
            if ($ares->num_rows === 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => '僅能查詢分配給您的學生']);
                exit;
            }
        } elseif ($isDirector) {
            // 如果是主任，檢查該學生的 assigned_teacher_id 對應的老師是否屬於主任的科系
            $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
            if ($user_id <= 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => '無法識別使用者']);
                exit;
            }
            
            // 獲取主任的科系代碼
            $table_check = $conn->query("SHOW TABLES LIKE 'director'");
            $has_director_table = $table_check && $table_check->num_rows > 0;
            
            if ($has_director_table) {
                $dept_stmt = $conn->prepare("SELECT department FROM director WHERE user_id = ?");
            } else {
                $dept_stmt = $conn->prepare("SELECT department FROM teacher WHERE user_id = ?");
            }
            $dept_stmt->bind_param('i', $user_id);
            $dept_stmt->execute();
            $dept_res = $dept_stmt->get_result();
            $dept_row = $dept_res->fetch_assoc();
            $director_department = $dept_row ? $dept_row['department'] : null;
            
            if (empty($director_department)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => '無法取得主任科系資訊']);
                exit;
            }
            
            // 檢查該學生的 assigned_teacher_id 對應的老師是否屬於主任的科系
            // 方法1: 檢查 assigned_department 是否等於主任的科系
            $check_stmt1 = $conn->prepare("SELECT 1 FROM enrollment_intention WHERE id = ? AND assigned_department = ?");
            $check_stmt1->bind_param('is', $enrollment_id, $director_department);
            $check_stmt1->execute();
            $check_res1 = $check_stmt1->get_result();
            
            // 方法2: 檢查 assigned_teacher_id 對應的老師是否屬於主任的科系
            if ($has_director_table) {
                $check_stmt2 = $conn->prepare("
                    SELECT 1 
                    FROM enrollment_intention ei
                    LEFT JOIN user u ON ei.assigned_teacher_id = u.id
                    LEFT JOIN teacher t ON u.id = t.user_id
                    LEFT JOIN director d ON u.id = d.user_id
                    WHERE ei.id = ? 
                    AND COALESCE(d.department, t.department) = ?
                ");
            } else {
                $check_stmt2 = $conn->prepare("
                    SELECT 1 
                    FROM enrollment_intention ei
                    LEFT JOIN user u ON ei.assigned_teacher_id = u.id
                    LEFT JOIN teacher t ON u.id = t.user_id
                    WHERE ei.id = ? 
                    AND t.department = ?
                ");
            }
            $check_stmt2->bind_param('is', $enrollment_id, $director_department);
            $check_stmt2->execute();
            $check_res2 = $check_stmt2->get_result();
            
            if ($check_res1->num_rows === 0 && $check_res2->num_rows === 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => '僅能查詢自己科系老師的學生']);
                exit;
            }
        }
        // 如果是招生中心/行政人員（ADM/STA），則允許查看所有學生的聯絡紀錄，不需要額外檢查

        // 查詢聯絡紀錄（使用實際的欄位名稱：enrollment_id, notes）
        // 為了向後兼容，將 notes 拆分為 result 和 follow_up_notes（如果包含分隔符）
        $q = $conn->prepare("SELECT id, enrollment_id, teacher_id, contact_date, method, notes, created_at FROM enrollment_contact_logs WHERE enrollment_id = ? ORDER BY contact_date DESC, id DESC");
        $q->bind_param('i', $enrollment_id);
        $q->execute();
        $res = $q->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        
        // 為了向後兼容，將 notes 拆分為 result 和 follow_up_notes
        foreach ($rows as &$row) {
            $row['student_id'] = $row['enrollment_id']; // 向後兼容
            $notes = $row['notes'] ?? '';
            // 嘗試從 notes 中提取 follow_up_notes（如果有分隔符）
            if (strpos($notes, '後續追蹤備註：') !== false) {
                $parts = explode('後續追蹤備註：', $notes, 2);
                $row['result'] = trim($parts[0]);
                $row['follow_up_notes'] = isset($parts[1]) ? trim($parts[1]) : '';
            } else {
                $row['result'] = $notes;
                $row['follow_up_notes'] = '';
            }
        }
        unset($row);
        
        echo json_encode(['success' => true, 'logs' => $rows]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '方法不被允許']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '系統錯誤', 'error' => $e->getMessage()]);
}
?>



