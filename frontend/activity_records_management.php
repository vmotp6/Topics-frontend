<?php
// 載入 session 配置
require_once 'session_config.php';

// 引入配置檔案
require_once 'config.php';

// 檢查登入狀態
$debug_mode = true; // 設為 false 可關閉調試模式

// 支援角色代碼和中文名稱
$allowed_roles = ['老師', 'TEA', '學校行政人員', 'STA', 'DI', '管理員', 'ADM'];
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// 檢查登入狀態和角色
$has_user_id = isset($_SESSION['user_id']) || isset($_SESSION['id']) || isset($_SESSION['username']);
$has_role = isset($_SESSION['role']) && !empty($user_role);
$role_allowed = in_array($user_role, $allowed_roles);

if ($debug_mode) {
    // 調試模式：顯示詳細資訊
    if (!$has_user_id || !$has_role || !$role_allowed) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; margin: 20px; border-radius: 5px; border: 1px solid #f5c6cb;'>";
        echo "<h3>⚠️ 登入驗證失敗</h3>";
        echo "<p><strong>原因分析：</strong>您需要以教師身分登入才能使用此功能</p>";
        echo "<hr style='margin: 15px 0; border-color: #f5c6cb;'>";
        echo "<p><strong>調試資訊：</strong></p>";
        echo "<ul style='margin: 10px 0; padding-left: 20px;'>";
        echo "<li>有使用者ID/帳號: " . ($has_user_id ? '是' : '否') . "</li>";
        echo "<li>有角色資訊: " . ($has_role ? '是' : '否') . "</li>";
        echo "<li>角色被允許: " . ($role_allowed ? '是' : '否') . "</li>";
        echo "<li>當前角色: " . htmlspecialchars($user_role ?: '(空)') . "</li>";
        echo "<li>允許的角色: " . implode(', ', $allowed_roles) . "</li>";
        echo "</ul>";
        echo "<div style='margin-top: 15px;'>";
        echo "<a href='login.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;'>前往登入頁面</a>";
        echo "</div>";
        echo "</div>";
        exit();
    }
} else {
    // 正常模式：直接跳轉
    if (!$has_user_id || !$has_role || !$role_allowed) {
        header("Location: login.php");
        exit();
    }
}

// 建立資料庫連接
$conn = getDatabaseConnection();

// 獲取登入教師的資訊
$teacher_id = null;
$teacher_info = null;

// 從 teacher 表獲取教師詳細資訊
if (isset($_SESSION['user_id'])) {
    $teacher_id = $_SESSION['user_id'];
    $teacher_sql = "SELECT * FROM teacher WHERE user_id = ?";
    $teacher_stmt = $conn->prepare($teacher_sql);
    if ($teacher_stmt) {
        $teacher_stmt->bind_param("i", $teacher_id);
    }
} elseif (isset($_SESSION['id'])) {
    $teacher_id = $_SESSION['id'];
    $teacher_sql = "SELECT * FROM teacher WHERE user_id = ?";
    $teacher_stmt = $conn->prepare($teacher_sql);
    if ($teacher_stmt) {
        $teacher_stmt->bind_param("i", $teacher_id);
    }
} elseif (isset($_SESSION['username'])) {
    $teacher_sql = "SELECT t.* FROM teacher t 
                    INNER JOIN user u ON t.user_id = u.id 
                    WHERE u.username = ?";
    $teacher_stmt = $conn->prepare($teacher_sql);
    if ($teacher_stmt) {
        $teacher_stmt->bind_param("s", $_SESSION['username']);
    }
}

if (isset($teacher_stmt) && $teacher_stmt !== false) {
    $teacher_stmt->execute();
    $teacher_result = $teacher_stmt->get_result();

    if ($teacher_result && $teacher_result->num_rows > 0) {
        $teacher_info = $teacher_result->fetch_assoc();
        if (!$teacher_id && $teacher_info) {
            $teacher_id = $teacher_info['user_id'];
        }
    }
    $teacher_stmt->close();
}

 // 處理記錄操作 (編輯)
 $message = "";
 $messageType = "";
 
 if ($_POST) {
     if (isset($_POST['action'])) {
         switch ($_POST['action']) {
             case 'update':
                if (isset($_POST['record_id']) && is_numeric($_POST['record_id'])) {
                    $record_id = $_POST['record_id'];
                    
                    // 先獲取現有的文件列表
                    $get_files_sql = "SELECT uploaded_files FROM activity_records WHERE id = ? AND teacher_id = ?";
                    $get_files_stmt = $conn->prepare($get_files_sql);
                    $existing_files = [];
                    if ($get_files_stmt) {
                        $get_files_stmt->bind_param("ii", $record_id, $teacher_id);
                        $get_files_stmt->execute();
                        $result = $get_files_stmt->get_result();
                        if ($row = $result->fetch_assoc()) {
                            if (!empty($row['uploaded_files'])) {
                                $existing_files = json_decode($row['uploaded_files'], true) ?: [];
                            }
                        }
                        $get_files_stmt->close();
                    }
                    
                    // 處理文件上傳
                    $upload_dir = UPLOAD_DIR;
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $new_files = [];
                    if (isset($_FILES['new_files']) && !empty($_FILES['new_files']['tmp_name'][0])) {
                        foreach ($_FILES['new_files']['tmp_name'] as $key => $tmp_name) {
                            if ($_FILES['new_files']['error'][$key] == 0 && !empty($tmp_name)) {
                                $original_name = $_FILES['new_files']['name'][$key];
                                $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
                                $safe_filename = time() . "_" . $key . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '', $original_name);
                                $target_file = $upload_dir . $safe_filename;
                                
                                // 檢查檔案大小 (10MB)
                                if ($_FILES['new_files']['size'][$key] <= 10 * 1024 * 1024) {
                                    if (move_uploaded_file($tmp_name, $target_file)) {
                                        $new_files[] = $target_file;
                                    }
                                }
                            }
                        }
                    }
                    
                    // 合併現有文件和新文件
                    $all_files = array_merge($existing_files, $new_files);
                    $files_json = !empty($all_files) ? json_encode($all_files) : (!empty($existing_files) ? json_encode($existing_files) : null);
                    
                    $update_sql = "UPDATE activity_records SET 
                                   activity_date = ?, 
                                   school = ?, 
                                   activity_type = ?, 
                                   activity_time = ?,
                                   suggestion = ?,
                                   uploaded_files = ?
                                   WHERE id = ? AND teacher_id = ?";
                    
                    // 活動類型存儲 ID（從 activity_types 表）
                    $activity_type_id = (int)$_POST['activity_type'];
                    
                    // 處理活動時間：轉換為 1=上班日, 2=假日
                    $activity_time_value = 1; // 預設上班日
                    if (isset($_POST['activity_time'])) {
                        if ($_POST['activity_time'] === '2' || $_POST['activity_time'] === '假日') {
                            $activity_time_value = 2;
                        } elseif ($_POST['activity_time'] === '1' || $_POST['activity_time'] === '上班日') {
                            $activity_time_value = 1;
                        }
                    }
                    
                    $update_stmt = $conn->prepare($update_sql);
                    if ($update_stmt) {
                        $update_stmt->bind_param("ssiissii", 
                            $_POST['activity_date'],
                            $_POST['school_name'],  // 表單欄位是 school_name，資料表欄位是 school
                            $activity_type_id,      // 存儲 ID 而不是代碼
                            $activity_time_value,   // 1=上班日, 2=假日
                            $_POST['suggestion'],
                            $files_json,
                            $record_id,
                            $teacher_id
                        );
                        
                        if ($update_stmt->execute()) {
                            $message = "記錄已成功更新！";
                            $messageType = "success";
                        } else {
                            $message = "更新失敗：" . $update_stmt->error;
                            $messageType = "error";
                        }
                        $update_stmt->close();
                    }
                }
                break;
            case 'delete_file':
                // 檢查是否為 AJAX 請求
                $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
                
                if (isset($_POST['record_id']) && isset($_POST['file_path'])) {
                    $record_id = $_POST['record_id'];
                    $file_to_delete = $_POST['file_path'];
                    
                    // 獲取現有文件列表
                    $get_files_sql = "SELECT uploaded_files FROM activity_records WHERE id = ?";
                    // 檢查權限：管理員可以刪除所有記錄的文件，教師只能刪除自己的
                    $current_role = $_SESSION['role'] ?? '';
                    if (isset($_SESSION['role']) && in_array($current_role, ['學校行政人員', 'STA', 'DI', '管理員', 'ADM'])) {
                        // 管理員可以刪除任何記錄的文件
                        $get_files_stmt = $conn->prepare($get_files_sql);
                        if ($get_files_stmt) {
                            $get_files_stmt->bind_param("i", $record_id);
                        }
                    } else {
                        // 教師只能刪除自己的記錄的文件
                        $get_files_sql .= " AND teacher_id = ?";
                        $get_files_stmt = $conn->prepare($get_files_sql);
                        if ($get_files_stmt && $teacher_id) {
                            $get_files_stmt->bind_param("ii", $record_id, $teacher_id);
                        } else {
                            $get_files_stmt = false;
                        }
                    }
                    
                    if ($get_files_stmt) {
                        $get_files_stmt->execute();
                        $result = $get_files_stmt->get_result();
                        if ($row = $result->fetch_assoc()) {
                            $files = json_decode($row['uploaded_files'], true) ?: [];
                            // 移除指定文件
                            $files = array_filter($files, function($file) use ($file_to_delete) {
                                return $file !== $file_to_delete;
                            });
                            $files = array_values($files); // 重新索引
                            
                            // 刪除物理文件
                            if (file_exists($file_to_delete)) {
                                @unlink($file_to_delete);
                            }
                            
                            // 更新資料庫
                            $files_json = !empty($files) ? json_encode($files) : null;
                            $update_files_sql = "UPDATE activity_records SET uploaded_files = ? WHERE id = ?";
                            $current_role = $_SESSION['role'] ?? '';
                            if (isset($_SESSION['role']) && in_array($current_role, ['學校行政人員', 'STA', 'DI', '管理員', 'ADM'])) {
                                // 管理員
                                $update_files_stmt = $conn->prepare($update_files_sql);
                                if ($update_files_stmt) {
                                    $update_files_stmt->bind_param("si", $files_json, $record_id);
                                }
                            } else {
                                // 教師
                                $update_files_sql .= " AND teacher_id = ?";
                                $update_files_stmt = $conn->prepare($update_files_sql);
                                if ($update_files_stmt && $teacher_id) {
                                    $update_files_stmt->bind_param("sii", $files_json, $record_id, $teacher_id);
                                } else {
                                    $update_files_stmt = false;
                                }
                            }
                            
                            if ($update_files_stmt) {
                                $update_files_stmt->execute();
                                $update_files_stmt->close();
                                
                                // 如果是 AJAX 請求，返回 JSON
                                if ($is_ajax || isset($_POST['ajax'])) {
                                    header('Content-Type: application/json');
                                    echo json_encode([
                                        'success' => true,
                                        'message' => '文件已成功刪除！',
                                        'remaining_files' => $files
                                    ]);
                                    exit;
                                }
                                
                                $message = "文件已成功刪除！";
                                $messageType = "success";
                            }
                        }
                        $get_files_stmt->close();
                    }
                }
                break;
        }
    }
}

// 查詢該教師的活動記錄
// 查詢活動記錄
$activity_records = [];

$current_role = $_SESSION['role'] ?? '';
if (isset($_SESSION['role']) && in_array($current_role, ['學校行政人員', 'STA', 'DI', '管理員', 'ADM'])) {
    // 🔹 若是招生中心 → 查看所有老師紀錄
    $records_sql = "SELECT ar.*, u.name AS teacher_name_display, 
                           (SELECT name FROM departments WHERE code = t.department) AS teacher_department_display,
                           t.department AS teacher_department_code
                    FROM activity_records ar
                    LEFT JOIN user u ON ar.teacher_id = u.id
                    LEFT JOIN teacher t ON ar.teacher_id = t.user_id
                    WHERE 1 ";

                     // 篩選參數
    $params = [];
    $types = '';

    if (!empty($_GET['teacher_name'])) {
        $records_sql .= " AND u.name LIKE ? ";
        $params[] = "%" . $_GET['teacher_name'] . "%";
        $types .= 's';
    }

    if (!empty($_GET['department'])) {
        $records_sql .= " AND t.department = ? ";
        $params[] = $_GET['department'];
        $types .= 's';
    }

    $records_sql .= " ORDER BY ar.activity_date DESC, ar.id DESC";

    $records_stmt = $conn->prepare($records_sql);
    if ($records_stmt) {
        if (!empty($params)) {
            $records_stmt->bind_param($types, ...$params);
        }
        $records_stmt->execute();
        $records_result = $records_stmt->get_result();

        if ($records_result) {
            while ($row = $records_result->fetch_assoc()) {
                // 從 activity_participants 表讀取參與對象
                $participants_codes = [];
                $participants_stmt = $conn->prepare("SELECT participants FROM activity_participants WHERE activity_id = ?");
                if ($participants_stmt) {
                    $participants_stmt->bind_param("i", $row['id']);
                    $participants_stmt->execute();
                    $participants_result = $participants_stmt->get_result();
                    while ($p_row = $participants_result->fetch_assoc()) {
                        $participants_codes[] = $p_row['participants'];
                    }
                    $participants_stmt->close();
                }
                // 轉換參與對象代碼為名稱
                if (!empty($participants_codes)) {
                    $participants_names = [];
                    foreach ($participants_codes as $code) {
                        if (isset($participants_options_map[$code])) {
                            $participants_names[] = $participants_options_map[$code];
                        }
                    }
                    $row['participants_display'] = implode(', ', $participants_names);
                    // 如果有 participants_other_text，也加入顯示
                    if (!empty($row['participants_other_text'])) {
                        $row['participants_display'] .= (empty($row['participants_display']) ? '' : ', ') . '其他: ' . htmlspecialchars($row['participants_other_text']);
                    }
                } else if (!empty($row['participants_other_text'])) {
                    $row['participants_display'] = '其他: ' . htmlspecialchars($row['participants_other_text']);
                } else {
                    $row['participants_display'] = '';
                }
                
                // 從 activity_types 表讀取活動類型名稱
                if (!empty($row['activity_type'])) {
                    if (isset($activity_type_options_map[$row['activity_type']])) {
                        $row['activity_type_display'] = $activity_type_options_map[$row['activity_type']];
                    } else {
                        $row['activity_type_display'] = '';
                    }
                } else {
                    $row['activity_type_display'] = '';
                }
                
                // 從 activity_feedback 表讀取活動紀錄
                $feedback_option_ids = [];
                $feedback_stmt = $conn->prepare("SELECT option_id FROM activity_feedback WHERE activity_id = ?");
                if ($feedback_stmt) {
                    $feedback_stmt->bind_param("i", $row['id']);
                    $feedback_stmt->execute();
                    $feedback_result = $feedback_stmt->get_result();
                    while ($f_row = $feedback_result->fetch_assoc()) {
                        $feedback_option_ids[] = $f_row['option_id'];
                    }
                    $feedback_stmt->close();
                }
                // 轉換活動紀錄選項ID為名稱
                if (!empty($feedback_option_ids)) {
                    $feedback_names = [];
                    foreach ($feedback_option_ids as $option_id) {
                        if (isset($activity_feedback_options_map[$option_id])) {
                            $feedback_names[] = $activity_feedback_options_map[$option_id];
                        }
                    }
                    $row['activity_feedback_display'] = implode(', ', $feedback_names);
                    // 如果有 feedback_other_text，也加入顯示
                    if (!empty($row['feedback_other_text'])) {
                        $row['activity_feedback_display'] .= (empty($row['activity_feedback_display']) ? '' : ', ') . '其他: ' . htmlspecialchars($row['feedback_other_text']);
                    }
                } else if (!empty($row['feedback_other_text'])) {
                    $row['activity_feedback_display'] = '其他: ' . htmlspecialchars($row['feedback_other_text']);
                } else {
                    $row['activity_feedback_display'] = '';
                }
                
                // 確保 uploaded_files 字段存在並正確處理
                if (!isset($row['uploaded_files'])) {
                    $row['uploaded_files'] = null;
                } else if (!empty($row['uploaded_files']) && is_string($row['uploaded_files'])) {
                    // 如果 uploaded_files 是 JSON 字符串，先解析為數組
                    // 這樣 json_encode 會正確處理，JavaScript 可以直接使用數組
                    $decoded = json_decode($row['uploaded_files'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        // JSON 有效，將數組賦值給 uploaded_files
                        // 這樣 json_encode 會將其編碼為 JSON 數組，而不是字符串
                        $row['uploaded_files'] = $decoded;
                    } else {
                        // JSON 無效，設為 null
                        $row['uploaded_files'] = null;
                    }
                } else if (empty($row['uploaded_files'])) {
                    // 空字符串也設為 null
                    $row['uploaded_files'] = null;
                }
                $activity_records[] = $row;
            }
        }
        $records_stmt->close();
    }
                    



} elseif ($teacher_id) {
    // 🔹 若是一般老師 → 只看自己的，並包含所屬系所
    $records_sql = "
        SELECT 
            ar.*, 
            u.name AS teacher_name_display, 
            (SELECT name FROM departments WHERE code = t.department) AS teacher_department_display,
            t.department AS teacher_department_code
        FROM activity_records ar
        LEFT JOIN user u ON ar.teacher_id = u.id
        LEFT JOIN teacher t ON ar.teacher_id = t.user_id
        WHERE ar.teacher_id = ?";
    
    // 添加搜索功能
    $params = [];
    $types = 'i';
    $params[] = $teacher_id;
    
    // 搜索功能：对于一般老师，搜索学校名称
    if (!empty($_GET['teacher_name'])) {
        $records_sql .= " AND ar.school LIKE ?";
        $params[] = "%" . $_GET['teacher_name'] . "%";
        $types .= 's';
    }
    
    if (!empty($_GET['department'])) {
        $records_sql .= " AND t.department = ?";
        $params[] = $_GET['department'];
        $types .= 's';
    }
    
    $records_sql .= " ORDER BY ar.activity_date DESC, ar.id DESC";

    $records_stmt = $conn->prepare($records_sql);
    if ($records_stmt) {
        if (!empty($params)) {
            $records_stmt->bind_param($types, ...$params);
        }
        $records_stmt->execute();
        $records_result = $records_stmt->get_result();
        
        if ($records_result) {
            while ($row = $records_result->fetch_assoc()) {
                // 從 activity_participants 表讀取參與對象
                $participants_codes = [];
                $participants_stmt = $conn->prepare("SELECT participants FROM activity_participants WHERE activity_id = ?");
                if ($participants_stmt) {
                    $participants_stmt->bind_param("i", $row['id']);
                    $participants_stmt->execute();
                    $participants_result = $participants_stmt->get_result();
                    while ($p_row = $participants_result->fetch_assoc()) {
                        $participants_codes[] = $p_row['participants'];
                    }
                    $participants_stmt->close();
                }
                // 轉換參與對象代碼為名稱
                if (!empty($participants_codes)) {
                    $participants_names = [];
                    foreach ($participants_codes as $code) {
                        if (isset($participants_options_map[$code])) {
                            $participants_names[] = $participants_options_map[$code];
                        }
                    }
                    $row['participants_display'] = implode(', ', $participants_names);
                    // 如果有 participants_other_text，也加入顯示
                    if (!empty($row['participants_other_text'])) {
                        $row['participants_display'] .= (empty($row['participants_display']) ? '' : ', ') . '其他: ' . htmlspecialchars($row['participants_other_text']);
                    }
                } else if (!empty($row['participants_other_text'])) {
                    $row['participants_display'] = '其他: ' . htmlspecialchars($row['participants_other_text']);
                } else {
                    $row['participants_display'] = '';
                }
                
                // 從 activity_types 表讀取活動類型名稱
                if (!empty($row['activity_type'])) {
                    if (isset($activity_type_options_map[$row['activity_type']])) {
                        $row['activity_type_display'] = $activity_type_options_map[$row['activity_type']];
                    } else {
                        $row['activity_type_display'] = '';
                    }
                } else {
                    $row['activity_type_display'] = '';
                }
                
                // 從 activity_feedback 表讀取活動紀錄
                $feedback_option_ids = [];
                $feedback_stmt = $conn->prepare("SELECT option_id FROM activity_feedback WHERE activity_id = ?");
                if ($feedback_stmt) {
                    $feedback_stmt->bind_param("i", $row['id']);
                    $feedback_stmt->execute();
                    $feedback_result = $feedback_stmt->get_result();
                    while ($f_row = $feedback_result->fetch_assoc()) {
                        $feedback_option_ids[] = $f_row['option_id'];
                    }
                    $feedback_stmt->close();
                }
                // 轉換活動紀錄選項ID為名稱
                if (!empty($feedback_option_ids)) {
                    $feedback_names = [];
                    foreach ($feedback_option_ids as $option_id) {
                        if (isset($activity_feedback_options_map[$option_id])) {
                            $feedback_names[] = $activity_feedback_options_map[$option_id];
                        }
                    }
                    $row['activity_feedback_display'] = implode(', ', $feedback_names);
                    // 如果有 feedback_other_text，也加入顯示
                    if (!empty($row['feedback_other_text'])) {
                        $row['activity_feedback_display'] .= (empty($row['activity_feedback_display']) ? '' : ', ') . '其他: ' . htmlspecialchars($row['feedback_other_text']);
                    }
                } else if (!empty($row['feedback_other_text'])) {
                    $row['activity_feedback_display'] = '其他: ' . htmlspecialchars($row['feedback_other_text']);
                } else {
                    $row['activity_feedback_display'] = '';
                }
                // 確保 uploaded_files 字段存在並正確處理
                if (!isset($row['uploaded_files'])) {
                    $row['uploaded_files'] = null;
                } else if (!empty($row['uploaded_files']) && is_string($row['uploaded_files'])) {
                    // 如果 uploaded_files 是 JSON 字符串，先解析為數組
                    // 這樣 json_encode 會正確處理，JavaScript 可以直接使用數組
                    $decoded = json_decode($row['uploaded_files'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        // JSON 有效，將數組賦值給 uploaded_files
                        // 這樣 json_encode 會將其編碼為 JSON 數組，而不是字符串
                        $row['uploaded_files'] = $decoded;
                    } else {
                        // JSON 無效，設為 null
                        $row['uploaded_files'] = null;
                    }
                } else if (empty($row['uploaded_files'])) {
                    // 空字符串也設為 null
                    $row['uploaded_files'] = null;
                }
                $activity_records[] = $row;
            }
        }
        $records_stmt->close();
    }
}

// 讀取活動類型選項，用於編輯表單（從 activity_types 表）
$activity_type_options = [];
$activity_type_options_map = []; // ID => name 映射
$activity_type_options_query = "SELECT ID, name FROM activity_types ORDER BY ID";
try {
    $activity_type_options_result = $conn->query($activity_type_options_query);
    if ($activity_type_options_result && $activity_type_options_result->num_rows > 0) {
        while ($row = $activity_type_options_result->fetch_assoc()) {
            $activity_type_options[] = ['id' => $row['ID'], 'name' => $row['name']];
            $activity_type_options_map[$row['ID']] = $row['name'];
        }
    } else {
        // 如果表不存在或沒有資料，使用預設選項（向後兼容）
        throw new Exception('Table not found or empty');
    }
} catch (Exception $e) {
    // 如果表不存在，使用預設選項（向後兼容）
    $activity_type_options = [
        ['id' => 1, 'name' => '來校體驗'],
        ['id' => 2, 'name' => '校外參訪'],
        ['id' => 3, 'name' => '講座分享']
    ];
    foreach ($activity_type_options as $opt) {
        $activity_type_options_map[$opt['id']] = $opt['name'];
    }
}

// 讀取參與對象選項（從 identity_options 表，排除專一到專五）
$participants_options = [];
$participants_options_map = []; // code => name 映射
$participants_options_query = "SELECT code, name FROM identity_options WHERE code NOT IN ('F1', 'F2', 'F3', 'F4', 'F5') ORDER BY CASE WHEN code = 'O1' THEN 1 ELSE 0 END, code";
try {
    $participants_options_result = $conn->query($participants_options_query);
    if ($participants_options_result && $participants_options_result->num_rows > 0) {
        while ($row = $participants_options_result->fetch_assoc()) {
            $participants_options[] = ['code' => $row['code'], 'name' => $row['name']];
            $participants_options_map[$row['code']] = $row['name'];
        }
        // 確保「其他」(O1) 在最後
        usort($participants_options, function($a, $b) {
            if ($a['code'] === 'O1') return 1;
            if ($b['code'] === 'O1') return -1;
            return strcmp($a['code'], $b['code']);
        });
    } else {
        throw new Exception('Table not found or empty');
    }
} catch (Exception $e) {
    $participants_options = [
        ['code' => 'H1', 'name' => '高一'],
        ['code' => 'H2', 'name' => '高二'],
        ['code' => 'H3', 'name' => '高三'],
        ['code' => 'J1', 'name' => '國一'],
        ['code' => 'J2', 'name' => '國二'],
        ['code' => 'J3', 'name' => '國三'],
        ['code' => 'T1', 'name' => '教師(職員工)'],
        ['code' => 'P1', 'name' => '家長'],
        ['code' => 'O1', 'name' => '其他']
    ];
    foreach ($participants_options as $opt) {
        $participants_options_map[$opt['code']] = $opt['name'];
    }
}

// 讀取活動紀錄選項（從 activity_feedback_options 表）
$activity_feedback_options = [];
$activity_feedback_options_map = []; // id => option 映射
$activity_feedback_options_query = "SELECT id, option FROM activity_feedback_options ORDER BY id";
try {
    $activity_feedback_options_result = $conn->query($activity_feedback_options_query);
    if ($activity_feedback_options_result && $activity_feedback_options_result->num_rows > 0) {
        while ($row = $activity_feedback_options_result->fetch_assoc()) {
            $activity_feedback_options[] = ['id' => $row['id'], 'option' => $row['option']];
            $activity_feedback_options_map[$row['id']] = $row['option'];
        }
    } else {
        throw new Exception('Table not found or empty');
    }
} catch (Exception $e) {
    $activity_feedback_options = [
        ['id' => 1, 'option' => '反應熱絡'],
        ['id' => 2, 'option' => '詢問度高'],
        ['id' => 3, 'option' => '反應冷淡'],
        ['id' => 4, 'option' => '願意參與小活動'],
        ['id' => 5, 'option' => '願意加入LINE'],
        ['id' => 6, 'option' => '願意追蹤FB、IG'],
        ['id' => 7, 'option' => '其他']
    ];
    foreach ($activity_feedback_options as $opt) {
        $activity_feedback_options_map[$opt['id']] = $opt['option'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>活動記錄管理系統</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/csp/records.css">
    <style>
         .management-container {
             max-width: 1200px;
             margin: 0 auto;
             padding: 20px;
             background: #f8f9fa;
             min-height: auto;
         }
        
        .header-section {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
         .stats-grid {
             display: grid;
             grid-template-columns: repeat(4, 1fr);
             gap: 20px;
             margin-bottom: 30px;
         }
         
         @media (max-width: 768px) {
             .stats-grid {
                 grid-template-columns: repeat(2, 1fr);
             }
         }
         
         @media (max-width: 480px) {
             .stats-grid {
                 grid-template-columns: 1fr;
             }
         }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #6c757d;
            font-weight: 500;
        }
        
         .records-table-container {
             background: white;
             border-radius: 15px;
             padding: 30px;
             box-shadow: 0 4px 15px rgba(0,0,0,0.1);
             margin-bottom: 50px;
         }
        
        .records-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .records-table th {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            color: white;
            padding: 15px 10px;
            text-align: left;
            font-weight: 600;
            border: none;
        }
        
        .records-table td {
            padding: 15px 10px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        
        .records-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85em;
            margin: 2px;
            transition: all 0.3s ease;
        }
        
         .btn-edit {
             background: #28a745;
             color: white;
         }
         
         .btn-view {
             background: #007bff;
             color: white;
         }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .search-tools {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .tools-grid {
            display: grid;
            grid-template-columns: 1fr 1fr auto auto;
            gap: 15px;
            align-items: end;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
        }
        
        .form-group input,
        .form-group select {
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 0.9em;
        }
        
        .btn-primary {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover,
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 3% auto;
            padding: 30px;
            border-radius: 15px;
            width: 80%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
        }
        
        /* 自定義滾動條樣式 */
        .modal-content::-webkit-scrollbar {
            width: 10px;
        }
        
        .modal-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 5px;
        }
        
        .modal-content::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 5px;
        }
        
        .modal-content::-webkit-scrollbar-thumb:hover {
            background: #5568d3;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #dee2e6;
            flex-shrink: 0;
        }
        
        #viewModalBody,
        #editModalBody {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 5px;
        }
        
        /* 文件預覽區域的滾動條樣式 */
        #viewModalBody::-webkit-scrollbar,
        #editModalBody::-webkit-scrollbar {
            width: 8px;
        }
        
        #viewModalBody::-webkit-scrollbar-track,
        #editModalBody::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        #viewModalBody::-webkit-scrollbar-thumb,
        #editModalBody::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 4px;
        }
        
        #viewModalBody::-webkit-scrollbar-thumb:hover,
        #editModalBody::-webkit-scrollbar-thumb:hover {
            background: #5568d3;
        }
        
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        
        .close:hover {
            color: #000;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .back-btn {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
         .back-btn:hover {
             transform: translateY(-2px);
             box-shadow: 0 4px 12px rgba(0,0,0,0.2);
             text-decoration: none;
             color: white;
         }
         
         body.page-body {
             padding-bottom: 0;
             margin-bottom: 0;
             display: block !important;
             min-height: auto !important;
         }
         
         .page-body .management-container {
             position: relative;
             z-index: 1;
             margin-bottom: 200px;
         }
         
         /* 頁面包裝器 */
         .page-wrapper {
             min-height: 100vh;
             padding-bottom: 150px;
             box-sizing: border-box;
         }
         
         /* 確保footer不會遮擋內容 */
         .footer {
             position: relative !important;
             margin-top: 50px !important;
             clear: both;
         }
         
    </style>
</head>

<?php include("share/header.php"); ?>

<body class="page-body">
    <div class="page-wrapper">
        <div class="management-container">
        <!-- 返回按鈕 -->
        <a href="records.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            返回活動記錄填報
        </a>
        
        <!-- 頁面標題 -->
        <div class="header-section">
            <h1><i class="fas fa-chart-line"></i> 活動記錄管理系統</h1>
            <p>管理您的所有活動記錄 | 編輯、查看、統計分析</p>
            <?php if ($teacher_info): ?>
                <div style="margin-top: 15px; font-size: 1.1em;">
                    <i class="fas fa-user"></i> 
                    <?php echo htmlspecialchars($teacher_info['name'] ?? '未設定'); ?> - 
                    <?php echo htmlspecialchars($teacher_info['department'] ?? '未設定'); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 訊息顯示 -->
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- 統計資訊 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($activity_records); ?></div>
                <div class="stat-label">
                    <i class="fas fa-list"></i> 總記錄數
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number">
                    <?php
                    $recent_count = 0;
                    $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
                    foreach ($activity_records as $record) {
                        if ($record['activity_date'] >= $thirty_days_ago) {
                            $recent_count++;
                        }
                    }
                    echo $recent_count;
                    ?>
                </div>
                <div class="stat-label">
                    <i class="fas fa-calendar-week"></i> 近30天記錄
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number">
                    <?php
                    $schools = [];
                    foreach ($activity_records as $record) {
                        $schools[$record['school']] = true;
                    }
                    echo count($schools);
                    ?>
                </div>
                <div class="stat-label">
                    <i class="fas fa-school"></i> 合作學校數
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number">
                    <?php
                    $types = [];
                    foreach ($activity_records as $record) {
                        $types[$record['activity_type']] = true;
                    }
                    echo count($types);
                    ?>
                </div>
                <div class="stat-label">
                    <i class="fas fa-tags"></i> 活動類型數
                </div>
            </div>
        </div>

        <!-- 搜索和篩選工具 -->
        <div class="search-tools">
            <div class="tools-grid">
                <div class="form-group">
                    <label><i class="fas fa-search"></i> 搜索記錄</label>
                    <input type="text" id="searchRecords" placeholder="輸入學校名稱或活動類型..." onkeyup="filterRecords()">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> 活動日期</label>
                    <input type="date" id="filterActivityDate" onchange="filterRecords()">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-filter"></i> 活動類型</label>
                    <select id="filterActivityType" onchange="filterRecords()">
                        <option value="">全部類型</option>
                        <option value="來校體驗">來校體驗</option>
                        <option value="校外參訪">校外參訪</option>
                        <option value="講座分享">講座分享</option>
                    </select>
                </div>
                
                <button type="button" class="btn-secondary" onclick="resetRecordsFilter()">
                    <i class="fas fa-undo"></i> 重置
                </button>
                
                <button type="button" class="btn-primary" onclick="window.location.href='records.php'">
                    <i class="fas fa-plus"></i> 新增記錄
                </button>
            </div>
            
            <div id="filterStats" style="margin-top: 15px; font-size: 0.9em; color: #6c757d;">
                <i class="fas fa-info-circle"></i> 顯示全部 <?php echo count($activity_records); ?> 筆記錄
            </div>
        </div>


        <!-- 記錄列表 -->
        <div class="records-table-container">
            <h3><i class="fas fa-table"></i> 活動記錄列表</h3>
            <div class="filter-bar" style="margin-bottom: 20px; display: flex1; gap: 10px; align-items: center; flex-wrap: nowrap;">
    <form method="GET" action="activity_records_management.php" style="display: flex; gap: 10px; align-items: center; flex-wrap: nowrap; width: 48%;">
        <input type="text" name="teacher_name" placeholder="輸入學校名稱或活動類型..."
               value="<?php echo htmlspecialchars($_GET['teacher_name'] ?? ''); ?>"
               style="padding: 5px 10px; border-radius: 6px; border: 1px solid #ccc;">
        <button type="submit" style="padding: 5px 15px; border: none; border-radius: 6px; background-color: #4CAF50; color: white; cursor: pointer; white-space: nowrap;">
            🔍 篩選
        </button>
        <a href="activity_records_management.php" style="padding: 5px 25px; border: none; border-radius: 6px; background-color: #888; color: white; text-decoration: none; display: inline-block; white-space: nowrap; align-self: flex-start; margin-top: 0px;">
            重置
        </a>
    </form>
</div>
            <?php if (!empty($activity_records)): ?>
                <table class="records-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-calendar"></i> 活動日期</th>
                            <th><i class="fas fa-user"></i> 教師姓名</th>
                            <th><i class="fas fa-building"></i> 所屬系所</th>
                            <th><i class="fas fa-school"></i> 學校名稱</th>
                            <th><i class="fas fa-tag"></i> 活動類型</th>
                            <th><i class="fas fa-clock"></i> 活動時間</th>
                            <th><i class="fas fa-calendar-plus"></i> 提交時間</th>
                            <th><i class="fas fa-cogs"></i> 操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activity_records as $record): ?>
                            <tr class="record-row">
                                <td><?php echo htmlspecialchars($record['activity_date']); ?></td>
                                <td><?php echo htmlspecialchars($record['teacher_name_display'] ?? $record['teacher_name'] ?? '—'); ?></td>
                                 <td><?php echo htmlspecialchars($record['teacher_department_display'] ?? $record['teacher_department'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($record['school'] ?? ''); ?></td>
                                <td>
                                    <span class="activity-type"><?php echo htmlspecialchars($record['activity_type_display'] ?? convertActivityTypeCodeToName($record['activity_type'], $conn)); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($record['activity_time']); ?></td>
                                <td><?php echo date('Y/m/d H:i', strtotime($record['created_at'] ?? $record['activity_date'])); ?></td>
                                <td>
                                    <button class="action-btn btn-view" onclick="viewRecord(<?php echo $record['id']; ?>)">
                                        <i class="fas fa-eye"></i> 查看
                                    </button>
                                    <button class="action-btn btn-edit" onclick="editRecord(<?php echo $record['id']; ?>)">
                                        <i class="fas fa-edit"></i> 編輯
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 60px; color: #6c757d;">
                    <i class="fas fa-inbox" style="font-size: 4em; margin-bottom: 20px; color: #dee2e6;"></i>
                    <h3>尚無活動記錄</h3>
                    <p>您還沒有任何活動記錄，<a href="records.php" style="color: #667eea;">點此新增第一筆記錄</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>

    <!-- 查看記錄模態框 -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-eye"></i> 記錄詳細資訊</h3>
                <span class="close" onclick="closeModal('viewModal')">&times;</span>
            </div>
            <div id="viewModalBody">
                <!-- 內容將由JavaScript動態填入 -->
            </div>
        </div>
    </div>

    <!-- 編輯記錄模態框 -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> 編輯記錄</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <div id="editModalBody">
                <!-- 內容將由JavaScript動態填入 -->
            </div>
        </div>
    </div>

    <script>
        // 記錄資料 (轉為JavaScript可用格式)
        // 使用 JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES 確保正確編碼
        const activityRecords = <?php echo json_encode($activity_records, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const activityTypeOptions = <?php echo json_encode($activity_type_options); ?>;
        
        // 調試：輸出記錄數據以便排查問題
        console.log('活動記錄數據:', activityRecords);
        console.log('總記錄數:', activityRecords.length);
        activityRecords.forEach((record, index) => {
            console.log(`記錄 ${index + 1} (ID: ${record.id}):`, {
                id: record.id,
                teacher_id: record.teacher_id,
                uploaded_files: record.uploaded_files,
                type: typeof record.uploaded_files,
                isNull: record.uploaded_files === null,
                isUndefined: record.uploaded_files === undefined,
                isEmpty: record.uploaded_files === '',
                isArray: Array.isArray(record.uploaded_files),
                length: Array.isArray(record.uploaded_files) ? record.uploaded_files.length : 'N/A'
            });
        });
        
        // 特別檢查 ID 22 的記錄
        const record22 = activityRecords.find(r => r.id == 22);
        if (record22) {
            console.log('=== 找到記錄 ID 22 ===');
            console.log('完整記錄:', record22);
            console.log('uploaded_files 值:', record22.uploaded_files);
            console.log('uploaded_files 類型:', typeof record22.uploaded_files);
            console.log('是否為數組:', Array.isArray(record22.uploaded_files));
        } else {
            console.log('=== 未找到記錄 ID 22 ===');
            console.log('所有記錄的 ID:', activityRecords.map(r => r.id));
        }
        
        // 篩選記錄功能
        function filterRecords() {
            const searchValue = document.getElementById('searchRecords').value.toLowerCase();
            const filterType = document.getElementById('filterActivityType').value;
            const filterDate = document.getElementById('filterActivityDate').value;
            const rows = document.querySelectorAll('.record-row');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const schoolName = row.cells[1].textContent.toLowerCase();
                const activityType = row.cells[2].textContent.toLowerCase();
                
                const matchesSearch = searchValue === '' || 
                                    schoolName.includes(searchValue) || 
                                    activityType.includes(searchValue);
                                    
                const matchesType = filterType === '' || activityType.includes(filterType.toLowerCase());
                const activityDate = row.cells[0].textContent.trim(); // 假設活動日期是第0欄
                const matchesDate = filterDate === '' || activityDate === filterDate;

                if (matchesSearch && matchesType && matchesDate) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // 更新篩選統計
            const filterStats = document.getElementById('filterStats');
            const totalCount = rows.length;
            if (visibleCount === totalCount) {
                filterStats.innerHTML = `<i class="fas fa-info-circle"></i> 顯示全部 ${totalCount} 筆記錄`;
            } else {
                filterStats.innerHTML = `<i class="fas fa-filter"></i> 篩選顯示 ${visibleCount} / ${totalCount} 筆記錄`;
            }
        }
        
        // 重置篩選
        function resetRecordsFilter() {
            document.getElementById('searchRecords').value = '';
            document.getElementById('filterActivityType').value = '';
            document.getElementById('filterActivityDate').value = '';
            filterRecords();
        }
        
        // 查看記錄詳細資訊
        function viewRecord(recordId) {
            const record = activityRecords.find(r => r.id == recordId);
            if (!record) return;
            
            // 解析文件列表
            let filesHtml = '';
            // 檢查 uploaded_files 是否存在且不為 null
            if (record.uploaded_files !== null && record.uploaded_files !== undefined && record.uploaded_files !== '') {
                try {
                    let files;
                    if (typeof record.uploaded_files === 'string') {
                        // 如果是字符串，嘗試解析 JSON
                        files = JSON.parse(record.uploaded_files);
                    } else if (Array.isArray(record.uploaded_files)) {
                        // 如果已經是數組，直接使用
                        files = record.uploaded_files;
                    } else {
                        files = [];
                    }
                    
                    if (Array.isArray(files) && files.length > 0) {
                        filesHtml = '<div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #dee2e6;"><strong><i class="fas fa-file-upload"></i> 佐證資料:</strong><div style="max-height: 400px; overflow-y: auto; overflow-x: hidden; margin-top: 15px; padding-right: 10px;"><div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;">';
                        files.forEach((filePath, index) => {
                            const fileName = filePath.split('/').pop() || filePath.split('\\\\').pop() || `檔案 ${index + 1}`;
                            const fileExt = fileName.split('.').pop().toLowerCase();
                            const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt);
                            const downloadUrl = 'download_file.php?file=' + encodeURIComponent(filePath) + '&record_id=' + recordId;
                            
                            filesHtml += `
                                <div style="border: 1px solid #dee2e6; border-radius: 8px; padding: 10px; text-align: center; background: #f8f9fa;">
                                    ${isImage ? `<img src="${downloadUrl}" style="max-width: 100%; max-height: 120px; width: auto; height: auto; object-fit: contain; border-radius: 4px; margin-bottom: 8px; display: block; margin-left: auto; margin-right: auto;" alt="${fileName}">` : `<i class="fas fa-file" style="font-size: 48px; color: #6c757d; margin-bottom: 8px;"></i>`}
                                    <div style="font-size: 12px; word-break: break-all; margin-bottom: 8px; max-height: 40px; overflow: hidden; text-overflow: ellipsis;">${fileName}</div>
                                    <a href="${downloadUrl}" target="_blank" style="display: inline-block; padding: 5px 10px; background: #667eea; color: white; text-decoration: none; border-radius: 4px; font-size: 12px;">
                                        <i class="fas fa-download"></i> 下載
                                    </a>
                                </div>
                            `;
                        });
                        filesHtml += '</div></div></div>';
                    }
                } catch (e) {
                    console.error('解析文件列表失敗:', e);
                }
            }
            
            const modalBody = document.getElementById('viewModalBody');
            modalBody.innerHTML = `
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div><strong>活動日期:</strong><br>${record.activity_date}</div>
                    <div><strong>教師單位:</strong><br>${record.teacher_department_display || record.teacher_department || '—'}</div>
                    <div><strong>教師姓名:</strong><br>${record.teacher_name_display || record.teacher_name || '—'}</div>
                    <div><strong>學校名稱:</strong><br>${record.school || ''}</div>
                    <div><strong>聯絡窗口:</strong><br>${record.contact_person || '未填寫'}</div>
                    <div><strong>聯絡電話:</strong><br>${record.contact_phone || '未填寫'}</div>
                    <div><strong>活動性質:</strong><br>${record.activity_type_display || record.activity_type || '—'}</div>
                    <div><strong>活動時間:</strong><br>${record.activity_time == 1 ? '上班日' : (record.activity_time == 2 ? '假日' : record.activity_time)}</div>
                </div>
                ${record.participants_display ? `<div style="margin-top: 15px;"><strong>參與對象:</strong><br>${record.participants_display}</div>` : ''}
                ${record.activity_feedback_display ? `<div style="margin-top: 15px;"><strong>活動紀錄:</strong><br>${record.activity_feedback_display}</div>` : ''}
                ${record.suggestion ? `<div style="margin-top: 15px;"><strong>檢討與建議:</strong><br>${record.suggestion}</div>` : ''}
                ${filesHtml || '<div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #dee2e6;"><strong><i class="fas fa-file-upload"></i> 佐證資料:</strong><br><span style="color: #6c757d;">無上傳文件</span></div>'}
            `;
            
            document.getElementById('viewModal').style.display = 'block';
        }
        
        // 編輯記錄
        function editRecord(recordId) {
            const record = activityRecords.find(r => r.id == recordId);
            if (!record) {
                console.error('找不到記錄 ID:', recordId);
                return;
            }
            
            // 解析文件列表
            let filesHtml = '';
            let files = [];
            
            // 調試：輸出當前記錄的文件信息
            console.log('編輯記錄 ID:', recordId);
            console.log('uploaded_files 原始值:', record.uploaded_files);
            console.log('uploaded_files 類型:', typeof record.uploaded_files);
            console.log('uploaded_files 是否為 null:', record.uploaded_files === null);
            console.log('uploaded_files 是否為 undefined:', record.uploaded_files === undefined);
            console.log('uploaded_files 是否為空字符串:', record.uploaded_files === '');
            
            // 檢查 uploaded_files 是否存在且不為 null
            // 更寬鬆的檢查：只要不是 null、undefined 或空字符串，就嘗試處理
            const hasFiles = record.uploaded_files !== null && 
                           record.uploaded_files !== undefined && 
                           record.uploaded_files !== '' &&
                           !(Array.isArray(record.uploaded_files) && record.uploaded_files.length === 0);
            
            console.log('hasFiles 檢查結果:', hasFiles);
            
            if (hasFiles) {
                try {
                    if (Array.isArray(record.uploaded_files)) {
                        // 如果已經是數組，直接使用（這是我們期望的情況）
                        console.log('已經是數組，直接使用，長度:', record.uploaded_files.length);
                        files = record.uploaded_files;
                    } else if (typeof record.uploaded_files === 'string') {
                        // 如果是字符串，嘗試解析 JSON
                        console.log('嘗試解析 JSON 字符串:', record.uploaded_files);
                        files = JSON.parse(record.uploaded_files);
                        console.log('解析結果:', files);
                    } else {
                        console.log('未知格式，設為空數組，類型:', typeof record.uploaded_files);
                        files = [];
                    }
                    
                    console.log('最終文件列表:', files);
                    console.log('文件列表類型:', typeof files);
                    console.log('文件列表是否為數組:', Array.isArray(files));
                    console.log('文件列表長度:', Array.isArray(files) ? files.length : 'N/A');
                    
                    if (Array.isArray(files) && files.length > 0) {
                        filesHtml = '<div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #dee2e6;"><strong><i class="fas fa-file-upload"></i> 現有佐證資料:</strong><div style="max-height: 400px; overflow-y: auto; overflow-x: hidden; margin-top: 15px; padding-right: 10px;"><div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;">';
                        files.forEach((filePath, index) => {
                            const fileName = filePath.split('/').pop() || filePath.split('\\\\').pop() || `檔案 ${index + 1}`;
                            const fileExt = fileName.split('.').pop().toLowerCase();
                            const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt);
                            const downloadUrl = 'download_file.php?file=' + encodeURIComponent(filePath) + '&record_id=' + recordId;
                            
                            filesHtml += `
                                <div style="border: 1px solid #dee2e6; border-radius: 8px; padding: 10px; text-align: center; background: #f8f9fa; position: relative;">
                                    ${isImage ? `<img src="${downloadUrl}" style="max-width: 100%; max-height: 120px; width: auto; height: auto; object-fit: contain; border-radius: 4px; margin-bottom: 8px; display: block; margin-left: auto; margin-right: auto;" alt="${fileName}">` : `<i class="fas fa-file" style="font-size: 48px; color: #6c757d; margin-bottom: 8px;"></i>`}
                                    <div style="font-size: 12px; word-break: break-all; margin-bottom: 8px; max-height: 40px; overflow: hidden; text-overflow: ellipsis;">${fileName}</div>
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <a href="${downloadUrl}" target="_blank" style="display: inline-block; padding: 5px 10px; background: #667eea; color: white; text-decoration: none; border-radius: 4px; font-size: 12px;">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button type="button" onclick="deleteFile(${recordId}, '${filePath.replace(/'/g, "\\'")}', event)" style="padding: 5px 10px; background: #dc3545; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            `;
                        });
                        filesHtml += '</div></div></div>';
                    }
                } catch (e) {
                    console.error('解析文件列表失敗:', e);
                }
            }
            
            const modalBody = document.getElementById('editModalBody');
            modalBody.innerHTML = `
                <form method="post" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="record_id" value="${record.id}">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label><strong>活動日期:</strong></label>
                            <input type="date" name="activity_date" value="${record.activity_date}" required style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 5px;">
                        </div>
                        <div>
                            <label><strong>學校名稱:</strong></label>
                            <input type="text" name="school_name" value="${record.school || ''}" required style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 5px;">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label><strong>活動性質:</strong></label>
                            <select name="activity_type" required style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 5px;">
                                ${activityTypeOptions.map(option => 
                                    `<option value="${option.id}" ${record.activity_type == option.id || record.activity_type === option.id ? 'selected' : ''}>${option.name}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div>
                            <label><strong>活動時間:</strong></label>
                            <select name="activity_time" required style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 5px;">
                                <option value="1" ${record.activity_time == 1 || record.activity_time === '1' || record.activity_time === '上班日' ? 'selected' : ''}>上班日</option>
                                <option value="2" ${record.activity_time == 2 || record.activity_time === '2' || record.activity_time === '假日' ? 'selected' : ''}>假日</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label><strong>檢討與建議:</strong></label>
                        <textarea name="suggestion" rows="4" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 5px;">${record.suggestion || ''}</textarea>
                    </div>
                    
                    ${filesHtml || '<div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #dee2e6;"><strong><i class="fas fa-file-upload"></i> 現有佐證資料:</strong><br><span style="color: #6c757d;">無上傳文件</span></div>'}
                    
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #dee2e6;">
                        <label><strong><i class="fas fa-plus-circle"></i> 新增佐證資料:</strong></label>
                        <div id="edit-file-inputs-container" style="margin-top: 10px;">
                            <div class="edit-file-input-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <input type="file" name="new_files[]" accept="image/*,.zip,.rar,.pdf" style="flex: 1; padding: 8px; border: 1px solid #ced4da; border-radius: 5px;">
                                <button type="button" class="edit-remove-file-btn" onclick="removeEditFileInput(this)" style="display: none; padding: 8px 12px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="edit-file-input-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <input type="file" name="new_files[]" accept="image/*,.zip,.rar,.pdf" style="flex: 1; padding: 8px; border: 1px solid #ced4da; border-radius: 5px;">
                                <button type="button" class="edit-remove-file-btn" onclick="removeEditFileInput(this)" style="display: none; padding: 8px 12px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="edit-file-input-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <input type="file" name="new_files[]" accept="image/*,.zip,.rar,.pdf" style="flex: 1; padding: 8px; border: 1px solid #ced4da; border-radius: 5px;">
                                <button type="button" class="edit-remove-file-btn" onclick="removeEditFileInput(this)" style="display: none; padding: 8px 12px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                            <button type="button" onclick="addEditFileInput()" style="padding: 8px 15px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 500;">
                                <i class="fas fa-plus"></i> 新增更多檔案
                            </button>
                            <small style="color: #6c757d;">
                                <i class="fas fa-info-circle"></i> 單檔最大 10MB，支援圖片、PDF、ZIP、RAR 格式
                            </small>
                        </div>
                    </div>
                    
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="button" class="btn-secondary" onclick="closeModal('editModal')" style="margin-right: 10px;">取消</button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> 儲存變更
                        </button>
                    </div>
                </form>
            `;
            
            document.getElementById('editModal').style.display = 'block';
            
            // 初始化文件輸入功能
            setTimeout(() => {
                initEditFileInputs();
            }, 100);
        }
        
        // 刪除文件（使用 AJAX，不重新整理頁面）
        function deleteFile(recordId, filePath, event) {
            if (!confirm('確定要刪除這個文件嗎？此操作無法復原。')) {
                return;
            }
            
            // 顯示載入狀態
            const deleteBtn = event ? event.target.closest('button') : document.querySelector(`button[onclick*="deleteFile(${recordId}"]`);
            if (deleteBtn) {
                const originalHTML = deleteBtn.innerHTML;
                deleteBtn.disabled = true;
                deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            }
            
            // 使用 FormData 發送 AJAX 請求
            const formData = new FormData();
            formData.append('action', 'delete_file');
            formData.append('record_id', recordId);
            formData.append('file_path', filePath);
            formData.append('ajax', '1'); // 標記為 AJAX 請求
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 更新當前記錄的 uploaded_files
                    const record = activityRecords.find(r => r.id == recordId);
                    if (record) {
                        // 使用服務器返回的剩餘文件列表
                        if (data.remaining_files) {
                            record.uploaded_files = data.remaining_files;
                        } else {
                            // 如果服務器沒有返回，手動移除
                            if (Array.isArray(record.uploaded_files)) {
                                record.uploaded_files = record.uploaded_files.filter(f => f !== filePath);
                            } else if (typeof record.uploaded_files === 'string') {
                                try {
                                    const files = JSON.parse(record.uploaded_files);
                                    record.uploaded_files = files.filter(f => f !== filePath);
                                } catch (e) {
                                    console.error('解析失敗:', e);
                                }
                            }
                        }
                    }
                    
                    // 重新渲染編輯模態框（保持表單狀態）
                    refreshEditModal(recordId);
                } else {
                    alert(data.message || '刪除失敗，請稍後再試');
                    if (deleteBtn) {
                        deleteBtn.disabled = false;
                        deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                    }
                }
            })
            .catch(error => {
                console.error('刪除文件時發生錯誤:', error);
                alert('刪除失敗，請稍後再試');
                if (deleteBtn) {
                    deleteBtn.disabled = false;
                    deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                }
            });
        }
        
        // 重新整理編輯模態框（保持表單狀態）
        function refreshEditModal(recordId) {
            const record = activityRecords.find(r => r.id == recordId);
            if (!record) return;
            
            // 保存當前表單的狀態（表單字段值）
            const form = document.querySelector('#editModalBody form');
            let formData = {};
            if (form) {
                // 保存所有輸入字段的值
                const inputs = form.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    if (input.type === 'file') {
                        // 文件輸入無法保存，跳過
                        return;
                    }
                    if (input.name && input.name !== 'action' && input.name !== 'record_id') {
                        formData[input.name] = input.value;
                    }
                });
            }
            
            // 重新調用 editRecord 函數
            editRecord(recordId);
            
            // 恢復表單字段的值（延遲執行，確保 DOM 已更新）
            setTimeout(() => {
                const newForm = document.querySelector('#editModalBody form');
                if (newForm && Object.keys(formData).length > 0) {
                    Object.keys(formData).forEach(name => {
                        const input = newForm.querySelector(`[name="${name}"]`);
                        if (input && input.type !== 'file') {
                            input.value = formData[name];
                        }
                    });
                }
                // 初始化文件輸入功能
                initEditFileInputs();
            }, 100);
        }
        
        
        // 編輯模態框中的文件輸入管理
        function addEditFileInput() {
            const container = document.getElementById('edit-file-inputs-container');
            if (!container) return;
            
            const fileInputs = container.querySelectorAll('.edit-file-input-group');
            
            // 限制最多上傳10個檔案
            if (fileInputs.length >= 10) {
                alert('最多只能上傳10個檔案！');
                return;
            }
            
            const newFileGroup = document.createElement('div');
            newFileGroup.className = 'edit-file-input-group';
            newFileGroup.style.cssText = 'display: flex; align-items: center; gap: 10px; margin-bottom: 10px;';
            
            const newInput = document.createElement('input');
            newInput.type = 'file';
            newInput.name = 'new_files[]';
            newInput.accept = 'image/*,.zip,.rar,.pdf';
            newInput.style.cssText = 'flex: 1; padding: 8px; border: 1px solid #ced4da; border-radius: 5px;';
            
            // 添加文件大小檢查
            newInput.addEventListener('change', function(e) {
                if (e.target.files && e.target.files.length > 0) {
                    Array.from(e.target.files).forEach(file => {
                        if (file.size > 10 * 1024 * 1024) {
                            alert(`檔案 "${file.name}" 超過 10MB 限制！`);
                            e.target.value = '';
                            return;
                        }
                    });
                    // 顯示刪除按鈕
                    const removeBtn = newFileGroup.querySelector('.edit-remove-file-btn');
                    if (removeBtn) {
                        removeBtn.style.display = 'block';
                    }
                }
                updateEditRemoveButtons();
            });
            
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'edit-remove-file-btn';
            removeBtn.onclick = function() { removeEditFileInput(this); };
            removeBtn.style.cssText = 'padding: 8px 12px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer; display: none;';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            
            newFileGroup.appendChild(newInput);
            newFileGroup.appendChild(removeBtn);
            
            container.appendChild(newFileGroup);
            updateEditRemoveButtons();
        }
        
        function removeEditFileInput(button) {
            const fileGroup = button.closest('.edit-file-input-group');
            if (fileGroup) {
                fileGroup.remove();
                updateEditRemoveButtons();
            }
        }
        
        function updateEditRemoveButtons() {
            const container = document.getElementById('edit-file-inputs-container');
            if (!container) return;
            
            const fileInputs = container.querySelectorAll('.edit-file-input-group');
            const removeButtons = container.querySelectorAll('.edit-remove-file-btn');
            
            // 如果只有一個檔案輸入框，隱藏刪除按鈕
            removeButtons.forEach(button => {
                const fileGroup = button.closest('.edit-file-input-group');
                const fileInput = fileGroup ? fileGroup.querySelector('input[type="file"]') : null;
                // 如果文件輸入有值，顯示刪除按鈕；如果只有一個輸入框，隱藏
                if (fileInput && fileInput.files && fileInput.files.length > 0) {
                    button.style.display = 'block';
                } else {
                    button.style.display = fileInputs.length > 1 ? 'block' : 'none';
                }
            });
        }
        
        // 初始化編輯模態框中的文件輸入事件
        function initEditFileInputs() {
            const container = document.getElementById('edit-file-inputs-container');
            if (!container) return;
            
            const fileInputs = container.querySelectorAll('input[type="file"][name="new_files[]"]');
            fileInputs.forEach(input => {
                input.addEventListener('change', function(e) {
                    if (e.target.files && e.target.files.length > 0) {
                        // 檢查檔案大小
                        Array.from(e.target.files).forEach(file => {
                            if (file.size > 10 * 1024 * 1024) {
                                alert(`檔案 "${file.name}" 超過 10MB 限制！`);
                                e.target.value = '';
                                return;
                            }
                        });
                        // 顯示刪除按鈕
                        const removeBtn = e.target.closest('.edit-file-input-group').querySelector('.edit-remove-file-btn');
                        if (removeBtn) {
                            removeBtn.style.display = 'block';
                        }
                    }
                    updateEditRemoveButtons();
                });
            });
            
            updateEditRemoveButtons();
        }
        
        // 關閉模態框
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // 點擊模態框外部關閉
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

    </script>

    <?php include("share/footer.php"); ?>
    
    <!-- 浮動助手組件 -->
    <?php include("share/chat_widget.php"); ?>
    <?php include("share/ai_widget.php"); ?>
</body>
</html>
