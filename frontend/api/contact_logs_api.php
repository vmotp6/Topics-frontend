<?php
// 載入 session 與資料庫設定
// 後台使用 'KANGNING_SESSION'（與 session_config.php 一致）
session_name('KANGNING_SESSION');

// 如果 session 尚未啟動，則啟動
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 載入資料庫設定
require_once '../config.php';

// 如果 session 中沒有 user_id，嘗試從 username 查找（後台兼容）
if (empty($_SESSION['user_id']) && !empty($_SESSION['username'])) {
    try {
        require_once '../config.php';
        $conn_temp = getDatabaseConnection();
        $user_stmt = $conn_temp->prepare("SELECT id FROM user WHERE username = ? LIMIT 1");
        $user_stmt->bind_param("s", $_SESSION['username']);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        if ($user_row = $user_result->fetch_assoc()) {
            $_SESSION['user_id'] = (int)$user_row['id'];
        }
        $user_stmt->close();
        $conn_temp->close();
    } catch (Exception $e) {
        error_log('contact_logs_api.php: 無法從 username 查找 user_id: ' . $e->getMessage());
    }
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
            contact_result VARCHAR(20) DEFAULT 'contacted',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_enrollment_id (enrollment_id),
            INDEX idx_teacher_id (teacher_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $conn->query($sql);
    } else {
        // 現有表：若無 contact_result 欄位則新增
        $col = $conn->query("SHOW COLUMNS FROM enrollment_contact_logs LIKE 'contact_result'");
        if (!$col || $col->num_rows === 0) {
            @$conn->query("ALTER TABLE enrollment_contact_logs ADD COLUMN contact_result VARCHAR(20) DEFAULT 'contacted' COMMENT 'contacted=已聯絡, unreachable=聯絡不到' AFTER notes");
        }
    }
}

function ensureCaseClosedColumn($conn) {
    $r = @$conn->query("SHOW COLUMNS FROM enrollment_intention LIKE 'case_closed'");
    if (!$r || $r->num_rows === 0) {
        @$conn->query("ALTER TABLE enrollment_intention ADD COLUMN case_closed TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=否,1=是(結案後顯示於歷史紀錄)'");
    }
}

try {
    $conn = getDatabaseConnection();
    ensureContactLogsTable($conn);
    ensureCaseClosedColumn($conn);

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        // 檢查登入狀態（支援前台和後台）
        $isLoggedIn = ((isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) ||
                      (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true)) &&
                     isset($_SESSION['username']) && !empty($_SESSION['username']) &&
                     isset($_SESSION['role']) && !empty($_SESSION['role']);
        $isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
        $user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
        $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        
        // 招生中心不能寫聯絡記錄
        $isAdmissionCenter = in_array($user_role, ['ADM', 'STA']);
        if ($isAdmissionCenter) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '招生中心不能寫聯絡記錄']);
            exit;
        }
        
        // 允許老師或主任寫聯絡記錄
        $isTeacher = ($user_role === '老師' || $user_role === 'TEA' || $user_role === 'STA' || $user_role === '學校行政人員' || $user_role === 'AA');
        $isDirector = ($user_role === 'DI');
        
        if (!$isLoggedIn || (!$isTeacher && !$isDirector)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '權限不足']);
            exit;
        }

        // 取得使用者 user.id
        $username = $_SESSION['username'];
        if ($isDirector) {
            // 主任：使用 user_id
            if ($user_id <= 0) {
                $tstmt = $conn->prepare("SELECT u.id FROM user u WHERE u.username = ? AND u.role = 'DI'");
                $tstmt->bind_param('s', $username);
                $tstmt->execute();
                $tres = $tstmt->get_result();
                $teacher = $tres->fetch_assoc();
                if (!$teacher) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => '找不到主任']);
                    exit;
                }
                $teacher_id = (int)$teacher['id'];
            } else {
                $teacher_id = $user_id;
            }
        } else {
            // 老師：查詢 user.id
            $tstmt = $conn->prepare("SELECT u.id FROM user u WHERE u.username = ? AND (u.role = '老師' OR u.role = 'TEA' OR u.role = 'STA' OR u.role = 'AA')");
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
        }

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
        if (isset($input['notes']) && trim((string)$input['notes']) !== '') {
            $notes = trim((string)$input['notes']);
        } else {
            $legacy_result = isset($input['contact_result']) ? trim((string)$input['contact_result']) : '';
            $follow_up_notes = isset($input['follow_up_notes']) ? trim((string)$input['follow_up_notes']) : '';
            $notes = $legacy_result;
            if ($follow_up_notes !== '') {
                $notes .= ($notes ? "\n\n後續追蹤備註：\n" : '') . $follow_up_notes;
            }
        }

        // 聯絡結果：unreachable=聯絡不到，其他或未填=已聯絡
        $contact_result = (isset($input['contact_result']) && (string)$input['contact_result'] === 'unreachable') ? 'unreachable' : 'contacted';

        if ($enrollment_id <= 0 || $method === '' || $notes === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '缺少必要欄位']);
            exit;
        }

        // 檢查權限：僅能對分配給自己的學生新增紀錄
        // 對於老師：必須 assigned_teacher_id = 自己的ID
        // 對於主任：必須 assigned_teacher_id = 自己的ID（自行聯絡）或 NULL（尚未分配）
        if ($isDirector) {
            // 主任：檢查 assigned_teacher_id 是否為自己（自行聯絡）
            $astmt = $conn->prepare("SELECT assigned_teacher_id, assigned_department FROM enrollment_intention WHERE id = ?");
            $astmt->bind_param('i', $enrollment_id);
            $astmt->execute();
            $ares = $astmt->get_result();
            $enrollment = $ares->fetch_assoc();
            
            if (!$enrollment) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => '找不到該學生']);
                exit;
            }
            
            $assigned_teacher_id = $enrollment['assigned_teacher_id'] ?? null;
            $assigned_department = $enrollment['assigned_department'] ?? null;
            
            // 獲取主任的科系代碼
            $table_check = $conn->query("SHOW TABLES LIKE 'director'");
            $has_director_table = $table_check && $table_check->num_rows > 0;
            
            if ($has_director_table) {
                $dept_stmt = $conn->prepare("SELECT department FROM director WHERE user_id = ?");
            } else {
                $dept_stmt = $conn->prepare("SELECT department FROM teacher WHERE user_id = ?");
            }
            $dept_stmt->bind_param('i', $teacher_id);
            $dept_stmt->execute();
            $dept_res = $dept_stmt->get_result();
            $dept_row = $dept_res->fetch_assoc();
            $director_department = $dept_row ? $dept_row['department'] : null;
            
            // 檢查：必須是主任自己科系的學生，且 assigned_teacher_id 為主任自己（自行聯絡）
            // 使用 TRIM 和大小寫不敏感比較
            $assigned_dept_trim = trim($assigned_department ?? '');
            $director_dept_trim = trim($director_department ?? '');
            if (strtoupper($assigned_dept_trim) !== strtoupper($director_dept_trim)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => '僅能對自己科系的學生寫聯絡記錄']);
                exit;
            }
            
            // 如果已分配給其他老師，主任不能寫記錄
            // 如果 assigned_teacher_id 為 NULL 或等於主任自己的ID，則允許寫記錄
            if ($assigned_teacher_id !== null && $assigned_teacher_id != $teacher_id) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => '該學生已分配給其他老師，主任不能寫聯絡記錄']);
                exit;
            }
        } else {
            // 老師：必須 assigned_teacher_id = 自己的ID
            $astmt = $conn->prepare("SELECT 1 FROM enrollment_intention WHERE id = ? AND assigned_teacher_id = ?");
            $astmt->bind_param('ii', $enrollment_id, $teacher_id);
            $astmt->execute();
            $ares = $astmt->get_result();
            if ($ares->num_rows === 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => '僅能新增分配給您的學生']);
                exit;
            }
        }

        // 寫入資料（含 contact_result: contacted=已聯絡, unreachable=聯絡不到）
        $stmt = $conn->prepare("INSERT INTO enrollment_contact_logs (enrollment_id, teacher_id, contact_date, method, notes, contact_result) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('iissss', $enrollment_id, $teacher_id, $contact_date, $method, $notes, $contact_result);
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
        $isLoggedIn = ((isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) ||
                      (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true)) &&
                     isset($_SESSION['username']) && !empty($_SESSION['username']) &&
                     isset($_SESSION['role']) && !empty($_SESSION['role']);
        $user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
        $isTeacher = ($user_role === '老師' || $user_role === 'TEA' || $user_role === 'AA');
        $isDirector = ($user_role === 'DI');
        $isAdmissionCenter = in_array($user_role, ['ADM', 'STA']);
        
        // 允許的用戶：管理員、招生中心、老師、主任
        if (!$isAdmin && !$isAdmissionCenter && (!$isLoggedIn || (!$isTeacher && !$isDirector))) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '權限不足', 'debug' => [
                'isAdmin' => $isAdmin,
                'isAdmissionCenter' => $isAdmissionCenter,
                'isLoggedIn' => $isLoggedIn,
                'user_role' => $user_role,
                'isTeacher' => $isTeacher,
                'isDirector' => $isDirector
            ]]);
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

        // 如果是招生中心或管理員，可以查看所有記錄，跳過權限檢查
        if ($isAdmin || $isAdmissionCenter) {
            // 招生中心和管理員可以查看所有記錄，不需要進一步檢查
        }
        // 如果是老師（非管理端），則檢查是否為分配給自己的學生
        else if (!$isDirector) {
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

        // 查詢聯絡紀錄（含 contact_result、ei.case_closed 供判斷是否顯示結案按鈕）
        $q = $conn->prepare("
            SELECT 
                cl.id, 
                cl.enrollment_id, 
                cl.teacher_id, 
                cl.contact_date, 
                cl.method, 
                cl.notes, 
                cl.contact_result,
                cl.created_at,
                ei.assigned_teacher_id,
                ei.case_closed,
                u.name AS teacher_name,
                u.username AS teacher_username,
                assigned_teacher.name AS assigned_teacher_name,
                assigned_teacher.username AS assigned_teacher_username
            FROM enrollment_contact_logs cl
            LEFT JOIN enrollment_intention ei ON cl.enrollment_id = ei.id
            LEFT JOIN user u ON cl.teacher_id = u.id
            LEFT JOIN user assigned_teacher ON ei.assigned_teacher_id = assigned_teacher.id
            WHERE cl.enrollment_id = ? 
            ORDER BY cl.contact_date DESC, cl.id DESC
        ");
        $q->bind_param('i', $enrollment_id);
        $q->execute();
        $res = $q->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);

        // 若 ei 無 case_closed 欄（舊 DB），視為 0
        $case_closed = 0;
        if (isset($rows[0]['case_closed']) && $rows[0]['case_closed'] !== null) {
            $case_closed = (int)$rows[0]['case_closed'];
        }

        // 主任且未結案，且最近 3 筆皆為「聯絡不到」→ 顯示結案按鈕
        $show_close_button = false;
        if ($isDirector && $case_closed === 0 && count($rows) >= 3) {
            $last3 = array_slice($rows, 0, 3);
            $all_unreachable = true;
            foreach ($last3 as $r) {
                if (($r['contact_result'] ?? '') !== 'unreachable') {
                    $all_unreachable = false;
                    break;
                }
            }
            $show_close_button = $all_unreachable;
        }
        
        // 為了向後兼容，將 notes 拆分為 result 和 follow_up_notes
        // 同時增加分配資訊、contact_result 顯示用
        foreach ($rows as &$row) {
            $row['student_id'] = $row['enrollment_id']; // 向後兼容
            $notes = $row['notes'] ?? '';
            if (strpos($notes, '後續追蹤備註：') !== false) {
                $parts = explode('後續追蹤備註：', $notes, 2);
                $row['result'] = trim($parts[0]);
                $row['follow_up_notes'] = isset($parts[1]) ? trim($parts[1]) : '';
            } else {
                $row['result'] = $notes;
                $row['follow_up_notes'] = '';
            }
            
            $row['assigned_teacher_name'] = $row['assigned_teacher_name'] ?? null;
            $row['assigned_teacher_username'] = $row['assigned_teacher_username'] ?? null;
            $row['assigned_teacher_id'] = $row['assigned_teacher_id'] ?? null;
            
            if (!empty($row['assigned_teacher_id'])) {
                $assigned_name = $row['assigned_teacher_name'] ?? $row['assigned_teacher_username'] ?? '未知';
                $row['assigned_info'] = "分配給：{$assigned_name}";
            } else {
                $row['assigned_info'] = "尚未分配";
            }
            // contact_result 向後兼容：NULL 或空視為 contacted
            if (!isset($row['contact_result']) || $row['contact_result'] === '') {
                $row['contact_result'] = 'contacted';
            }
        }
        unset($row);
        
        echo json_encode(['success' => true, 'logs' => $rows, 'show_close_button' => $show_close_button, 'case_closed' => (int)$case_closed]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '方法不被允許']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '系統錯誤', 'error' => $e->getMessage()]);
}
?>



