<?php
// 載入 session 配置
require_once 'session_config.php';
require_once 'config.php';

// 驗證碼將由 captcha_image.php 生成（使用 $_SESSION['captcha_code']）

// 建立資料庫連接
$conn = getDatabaseConnection();

// 取得啟用的場次（包含最多人數、已報名人數和剩餘名額）
$sessions = [];
$sessions_query = "SELECT s.id, s.session_name, s.session_date, s.session_type, s.max_participants,
                          COUNT(a.id) as registered_count,
                          (s.max_participants - COUNT(a.id)) as remaining_spots
                   FROM admission_sessions s 
                   LEFT JOIN admission_applications a ON s.id = a.session_id 
                   WHERE s.is_active = 1 AND YEAR(s.session_date) = YEAR(CURDATE())
                   GROUP BY s.id, s.session_name, s.session_date, s.session_type, s.max_participants
                   ORDER BY 
                       CASE WHEN (s.max_participants - COUNT(a.id)) <= 0 THEN 1 ELSE 0 END,
                       s.session_date";
$sessions_result = $conn->query($sessions_query);
if ($sessions_result) {
    while ($row = $sessions_result->fetch_assoc()) {
        $sessions[] = $row;
    }
}

// 取得啟用的年級選項（從 identity_options 表，只取國一到國三）
$grades = [];
$grades_map = []; // code => name 映射
$grades_query = "SELECT code, name FROM identity_options WHERE code IN ('J1', 'J2', 'J3') ORDER BY code";
$grades_result = $conn->query($grades_query);
if ($grades_result) {
    while ($row = $grades_result->fetch_assoc()) {
        $grades[] = ['code' => $row['code'], 'name' => $row['name']];
        $grades_map[$row['code']] = $row['name'];
    }
}
// 如果查詢失敗或沒有資料，使用預設值
if (empty($grades)) {
    $grades = [
        ['code' => 'J1', 'name' => '國一'],
        ['code' => 'J2', 'name' => '國二'],
        ['code' => 'J3', 'name' => '國三']
    ];
    foreach ($grades as $g) {
        $grades_map[$g['code']] = $g['name'];
    }
}

// 1. 取得科系資料
$departments = [];
$departments_map = []; // code => name 映射

$departments_query = "SELECT code, name FROM departments ORDER BY code, name";
$departments_result = $conn->query($departments_query);

if ($departments_result) {
    while ($row = $departments_result->fetch_assoc()) {
        $departments[] = ['code' => $row['code'], 'name' => $row['name']];
        $departments_map[$row['code']] = $row['name'];
    }
}

// 如果查詢失敗或沒有資料，使用預設值
if (empty($departments)) {
    $departments = [
        ['code' => 'IM', 'name' => '資訊管理科'],
        ['code' => 'BM', 'name' => '企業管理科'],
        ['code' => 'NU', 'name' => '護理科']
    ];
    foreach ($departments as $d) {
        $departments_map[$d['code']] = $d['name'];
    }
}

// 2. 取得招生諮詢老師資訊 (user_id=90的老師資料)
$admission_teacher = [];
$teacher_query = "SELECT u.name, t.department, t.phone 
                  FROM teacher t 
                  LEFT JOIN user u ON t.user_id = u.id 
                  WHERE t.user_id = 90";
$teacher_result = $conn->query($teacher_query);

if ($teacher_result && $teacher_result->num_rows > 0) {
    $admission_teacher = $teacher_result->fetch_assoc();

    // 3. ★ 將 department 代碼轉換成中文科系名稱
    if (!empty($admission_teacher['department']) && isset($departments_map[$admission_teacher['department']])) {
        $admission_teacher['department_name'] = $departments_map[$admission_teacher['department']];
    } else {
        $admission_teacher['department_name'] = $admission_teacher['department']; // fallback
    }
}


$message = "";
$messageType = "";
$search_results = [];
$search_email = "";

// 處理成功訊息
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
    
    if (isset($_SESSION['should_refresh'])) {
        unset($_SESSION['should_refresh']);
        // 設定全域變數供 JavaScript 使用
        echo "<script>
            window.successMessage = '" . addslashes($success_message) . "';
        </script>";
    }
    
    // 檢查是否需要自動刷新（報名成功後3秒刷新）
    if (isset($_SESSION['auto_refresh'])) {
        unset($_SESSION['auto_refresh']);
        echo "<script>
            window.autoRefresh = true;
        </script>";
    }
}

// 處理搜尋請求
if (isset($_GET['action']) && $_GET['action'] === 'search' && isset($_GET['email'])) {
    $search_email = trim($_GET['email']);
    
    if (!empty($search_email) && filter_var($search_email, FILTER_VALIDATE_EMAIL)) {
        // 需要先完成 Email 驗證（以 session 保存的驗證狀態為準）
        if (!isset($_SESSION['admission_verified_email']) || $_SESSION['admission_verified_email'] !== $search_email) {
            $message = "請先完成 Email 驗證；系統會將驗證碼寄到您所輸入的信箱。";
            $messageType = "error";
            // 不執行搜尋，等待用戶輸入驗證碼
        } else {
            // 搜尋該電子郵件的所有報名記錄
            $search_query = "SELECT a.*, s.session_name, s.session_date, s.session_type, sd.name as school_name
                            FROM admission_applications a 
                            LEFT JOIN admission_sessions s ON a.session_id = s.id 
                            LEFT JOIN school_data sd ON a.school = sd.school_code
                            WHERE a.email = ? 
                            ORDER BY a.created_at DESC";
            $search_stmt = $conn->prepare($search_query);
            $search_stmt->bind_param("s", $search_email);
            $search_stmt->execute();
            $search_result = $search_stmt->get_result();
            
            while ($row = $search_result->fetch_assoc()) {
                $search_results[] = $row;
            }
            $search_stmt->close();
            
            if (empty($search_results)) {
                $message = "未找到此電子郵件的報名記錄。";
                $messageType = "info";
            }
        }
    } else {
        $message = "請輸入有效的電子郵件地址。";
        $messageType = "error";
    }
} else {
    // 如果沒有搜尋請求，清除搜尋狀態
    $search_email = "";
    $search_results = [];
}

// 處理取消報名請求
if (isset($_POST['action']) && $_POST['action'] === 'cancel' && isset($_POST['application_id'])) {
    $application_id = intval($_POST['application_id']);
    $email = trim($_POST['email']);
    
    // 調試信息
    error_log("Cancel request: application_id={$application_id}, email={$email}");
    
    // 驗證電子郵件和申請ID的匹配
    $verify_query = "SELECT id, student_name, session_id FROM admission_applications WHERE id = ? AND email = ?";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("is", $application_id, $email);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows > 0) {
        $application_data = $verify_result->fetch_assoc();
        error_log("Found application: " . json_encode($application_data));
        
        // 刪除報名記錄
        $delete_query = "DELETE FROM admission_applications WHERE id = ? AND email = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("is", $application_id, $email);
        
        if ($delete_stmt->execute()) {
            $success_message = "報名已成功取消！";
            
            // 重新搜尋以更新結果
            $search_email = $email;
            $search_query = "SELECT a.*, s.session_name, s.session_date, s.session_type, sd.name as school_name
                            FROM admission_applications a 
                            LEFT JOIN admission_sessions s ON a.session_id = s.id 
                            LEFT JOIN school_data sd ON a.school = sd.school_code
                            WHERE a.email = ? 
                            ORDER BY a.created_at DESC";
            $search_stmt = $conn->prepare($search_query);
            $search_stmt->bind_param("s", $search_email);
            $search_stmt->execute();
            $search_result = $search_stmt->get_result();
            $search_results = [];
            while ($row = $search_result->fetch_assoc()) {
                $search_results[] = $row;
            }
            $search_stmt->close();
            
            error_log("Application cancelled successfully");
            
            // 設定成功訊息和重整標記
            $_SESSION['success_message'] = $success_message;
            $_SESSION['should_refresh'] = true;
            
            // 立即重新導向到同一頁面以觸發彈跳視窗
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $message = "取消報名失敗，請稍後再試。";
            $messageType = "error";
            error_log("Delete failed: " . $delete_stmt->error);
        }
        $delete_stmt->close();
    } else {
        $message = "無效的申請記錄，無法取消。";
        $messageType = "error";
        error_log("No matching application found for id={$application_id}, email={$email}");
    }
    $verify_stmt->close();
}

// 處理修改報名請求
if (isset($_POST['action']) && $_POST['action'] === 'modify' && isset($_POST['application_id'])) {
    $application_id = intval($_POST['application_id']);
    $email = trim($_POST['email']);
    $new_session_id = intval($_POST['new_session_id']);
    $new_course_priority_1_input = trim($_POST['new_course_priority_1']);
    $new_course_priority_2_input = trim($_POST['new_course_priority_2']);
    
    // 將科系名稱轉換為代碼（如果輸入的是名稱）
    $new_course_priority_1 = '';
    if (!empty($new_course_priority_1_input)) {
        if (isset($departments_map[$new_course_priority_1_input])) {
            $new_course_priority_1 = $new_course_priority_1_input; // 已經是代碼
        } else {
            // 輸入的是名稱，需要找到對應的代碼
            foreach ($departments_map as $code => $name) {
                if ($name === $new_course_priority_1_input) {
                    $new_course_priority_1 = $code;
                    break;
                }
            }
        }
    }
    
    $new_course_priority_2 = '';
    if (!empty($new_course_priority_2_input)) {
        if (isset($departments_map[$new_course_priority_2_input])) {
            $new_course_priority_2 = $new_course_priority_2_input; // 已經是代碼
        } else {
            // 輸入的是名稱，需要找到對應的代碼
            foreach ($departments_map as $code => $name) {
                if ($name === $new_course_priority_2_input) {
                    $new_course_priority_2 = $code;
                    break;
                }
            }
        }
    }
    
    // 檢查第一科系和第二科系是否重複
    if (!empty($new_course_priority_1) && !empty($new_course_priority_2) && $new_course_priority_1 === $new_course_priority_2) {
        $message = "第一科系和第二科系不能相同，請選擇不同的科系。";
        $messageType = "error";
    } else {
        // 驗證科系代碼是否存在於 departments 表中，並將空字串轉為 NULL
        if (!empty($new_course_priority_1)) {
            $dept_check_query = "SELECT code FROM departments WHERE code = ? LIMIT 1";
            $dept_check_stmt = $conn->prepare($dept_check_query);
            if ($dept_check_stmt === false) {
                error_log("準備驗證科系代碼1失敗: " . $conn->error);
                $new_course_priority_1 = null;
            } else {
                $dept_check_stmt->bind_param("s", $new_course_priority_1);
                $dept_check_stmt->execute();
                $dept_check_result = $dept_check_stmt->get_result();
                if ($dept_check_result->num_rows === 0) {
                    error_log("警告：科系代碼1不存在於 departments 表中: " . $new_course_priority_1);
                    $new_course_priority_1 = null;
                }
                $dept_check_stmt->close();
            }
        } else {
            $new_course_priority_1 = null;
        }
        
        if (!empty($new_course_priority_2)) {
            $dept_check_query2 = "SELECT code FROM departments WHERE code = ? LIMIT 1";
            $dept_check_stmt2 = $conn->prepare($dept_check_query2);
            if ($dept_check_stmt2 === false) {
                error_log("準備驗證科系代碼2失敗: " . $conn->error);
                $new_course_priority_2 = null;
            } else {
                $dept_check_stmt2->bind_param("s", $new_course_priority_2);
                $dept_check_stmt2->execute();
                $dept_check_result2 = $dept_check_stmt2->get_result();
                if ($dept_check_result2->num_rows === 0) {
                    error_log("警告：科系代碼2不存在於 departments 表中: " . $new_course_priority_2);
                    $new_course_priority_2 = null;
                }
                $dept_check_stmt2->close();
            }
        } else {
            $new_course_priority_2 = null;
        }
        
        // 驗證電子郵件和申請ID的匹配
        $verify_query = "SELECT id FROM admission_applications WHERE id = ? AND email = ?";
        $verify_stmt = $conn->prepare($verify_query);
        $verify_stmt->bind_param("is", $application_id, $email);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows > 0) {
            // 檢查新場次是否額滿
        $session_check_query = "SELECT s.max_participants, 
                                      (s.max_participants - COUNT(a.id)) as remaining_spots,
                                      s.session_date
                               FROM admission_sessions s 
                               LEFT JOIN admission_applications a ON s.id = a.session_id 
                               WHERE s.id = ? AND s.is_active = 1
                               GROUP BY s.id, s.max_participants, s.session_date";
        $session_check_stmt = $conn->prepare($session_check_query);
        $session_check_stmt->bind_param("i", $new_session_id);
        $session_check_stmt->execute();
        $session_check_result = $session_check_stmt->get_result();
        
        if ($session_row = $session_check_result->fetch_assoc()) {
            if ($session_row['remaining_spots'] <= 0) {
                $message = "所選場次已額滿，請選擇其他場次。";
                $messageType = "error";
            } else {
                // 檢查該 email 是否在同一天已經有其他場次的報名（排除當前正在修改的報名記錄）
                $duplicate_check_query = "SELECT COUNT(*) as count 
                                           FROM admission_applications a
                                           INNER JOIN admission_sessions s ON a.session_id = s.id
                                           WHERE a.email = ? AND s.session_date = ? AND s.is_active = 1 AND a.id != ?";
                $duplicate_check_stmt = $conn->prepare($duplicate_check_query);
                $duplicate_check_stmt->bind_param("ssi", $email, $session_row['session_date'], $application_id);
                $duplicate_check_stmt->execute();
                $duplicate_check_result = $duplicate_check_stmt->get_result();
                
                if ($duplicate_row = $duplicate_check_result->fetch_assoc()) {
                    if ($duplicate_row['count'] > 0) {
                        $message = "您已經在同一天報名過其他場次，每人同一天只能報名一次。";
                        $messageType = "error";
                        $duplicate_check_stmt->close();
                        $session_check_stmt->close();
                        $verify_stmt->close();
                        // 繼續執行，不進行更新
                        goto skip_modify_update;
                    }
                }
                $duplicate_check_stmt->close();
                // 取得新場次資訊
                $new_session_name = '';
                $session_info_query = "SELECT session_name FROM admission_sessions WHERE id = ?";
                $session_info_stmt = $conn->prepare($session_info_query);
                if ($session_info_stmt === false) {
                    error_log("準備查詢新場次資訊失敗: " . $conn->error);
                } else {
                    $session_info_stmt->bind_param("i", $new_session_id);
                    $session_info_stmt->execute();
                    $session_info_result = $session_info_stmt->get_result();
                    if ($session_info_row = $session_info_result->fetch_assoc()) {
                        $new_session_name = $session_info_row['session_name'];
                    }
                    $session_info_stmt->close();
                }
                
                // 更新報名記錄（移除 session_choice 字段）
                $update_query = "UPDATE admission_applications 
                                SET session_id = ?, course_priority_1 = ?, course_priority_2 = ?
                                WHERE id = ? AND email = ?";
                $update_stmt = $conn->prepare($update_query);
                if ($update_stmt === false) {
                    error_log("準備更新報名記錄失敗: " . $conn->error);
                    $success_message = "報名修改失敗：資料庫錯誤";
                    goto skip_modify_update;
                }
                $update_stmt->bind_param("issis", $new_session_id, $new_course_priority_1, $new_course_priority_2, $application_id, $email);
                
                if ($update_stmt->execute()) {
                    $success_message = "報名已成功修改！";
                    
                    // 發送修改確認郵件
                    try {
                        require_once 'includes/email_functions.php';
                        
                        // 取得修改後的完整資料
                        $updated_query = "SELECT a.*, s.session_name, s.session_date, s.session_type, s.location, s.online_link, sd.name as school_name
                                        FROM admission_applications a 
                                        LEFT JOIN admission_sessions s ON a.session_id = s.id 
                                        LEFT JOIN school_data sd ON a.school = sd.school_code
                                        WHERE a.id = ?";
                        $updated_stmt = $conn->prepare($updated_query);
                        $updated_stmt->bind_param("i", $application_id);
                        $updated_stmt->execute();
                        $updated_result = $updated_stmt->get_result();
                        
                        if ($updated_row = $updated_result->fetch_assoc()) {
                            // 組合科系資訊用於郵件（將代碼轉換為名稱）
                            $course_info = [];
                            if (!empty($updated_row['course_priority_1'])) {
                                $dept_name_1 = isset($departments_map[$updated_row['course_priority_1']]) 
                                    ? $departments_map[$updated_row['course_priority_1']] 
                                    : $updated_row['course_priority_1'];
                                $course_info[] = "第一選擇：" . $dept_name_1;
                            }
                            if (!empty($updated_row['course_priority_2'])) {
                                $dept_name_2 = isset($departments_map[$updated_row['course_priority_2']]) 
                                    ? $departments_map[$updated_row['course_priority_2']] 
                                    : $updated_row['course_priority_2'];
                                $course_info[] = "第二選擇：" . $dept_name_2;
                            }
                            $course_text = !empty($course_info) ? implode('、', $course_info) : '未選擇科系';
                            
                            // 取得場次資訊
                            $session_type_modify = $updated_row['session_type'] ?? '實體';
                            $location_modify = $updated_row['location'] ?? '';
                            $online_link_modify = $updated_row['online_link'] ?? '';
                            
                            // 發送修改確認郵件
                            $modify_email_sent = sendModifyConfirmationEmail(
                                $updated_row['email'],
                                $updated_row['student_name'],
                                $updated_row['parent_name'],
                                $updated_row['session_name'],
                                $course_text,
                                $session_type_modify,
                                $location_modify,
                                $online_link_modify
                            );
                            
                            if ($modify_email_sent) {
                                $success_message .= " 修改確認郵件已發送到您的信箱。";
                            } else {
                                $success_message .= " 修改確認郵件發送失敗，但報名已成功修改。";
                            }
                        }
                        $updated_stmt->close();
                        
                    } catch (Exception $e) {
                        error_log("修改確認郵件發送失敗: " . $e->getMessage());
                        $success_message .= " 修改確認郵件發送失敗，但報名已成功修改。";
                    }
                    
                    // 重新搜尋以更新結果
                    $search_email = $email;
                    $search_query = "SELECT a.*, s.session_name, s.session_date, s.session_type, sd.name as school_name
                                    FROM admission_applications a 
                                    LEFT JOIN admission_sessions s ON a.session_id = s.id 
                                    LEFT JOIN school_data sd ON a.school = sd.school_code
                                    WHERE a.email = ? 
                                    ORDER BY a.created_at DESC";
                    $search_stmt = $conn->prepare($search_query);
                    $search_stmt->bind_param("s", $search_email);
                    $search_stmt->execute();
                    $search_result = $search_stmt->get_result();
                    $search_results = [];
                    while ($row = $search_result->fetch_assoc()) {
                        $search_results[] = $row;
                    }
                    $search_stmt->close();
                    
                    error_log("Application modified successfully");
                    
                    // 設定成功訊息和重整標記
                    $_SESSION['success_message'] = $success_message;
                    $_SESSION['should_refresh'] = true;
                    
                    // 立即重新導向到同一頁面以觸發彈跳視窗
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $message = "修改報名失敗，請稍後再試。";
                    $messageType = "error";
                    error_log("Update failed: " . $update_stmt->error);
                }
                $update_stmt->close();
            }
        } else {
            $message = "所選場次無效，請重新選擇。";
            $messageType = "error";
        }
        $session_check_stmt->close();
        } else {
            $message = "無效的申請記錄，無法修改。";
            $messageType = "error";
        }
        $verify_stmt->close();
        skip_modify_update:
    }
}

// 處理表單提交
if ($_POST && !isset($_POST['action'])) {
    // 驗證必填欄位 (移除 line_id，改為選填)
    $required_fields = ['email', 'school_name', 'student_name', 'grade', 'parent_name', 'contact_phone', 'session_choice', 'receive_info', 'captcha'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    // 驗證場次是否額滿
    if (!empty($_POST['session_choice'])) {
        $session_check_query = "SELECT s.max_participants, 
                                      (s.max_participants - COUNT(a.id)) as remaining_spots,
                                      s.session_date
                               FROM admission_sessions s 
                               LEFT JOIN admission_applications a ON s.id = a.session_id 
                               WHERE s.id = ? AND s.is_active = 1
                               GROUP BY s.id, s.max_participants, s.session_date";
        $session_check_stmt = $conn->prepare($session_check_query);
        $session_check_stmt->bind_param("i", $_POST['session_choice']);
        $session_check_stmt->execute();
        $session_check_result = $session_check_stmt->get_result();
        
        if ($session_row = $session_check_result->fetch_assoc()) {
            if ($session_row['remaining_spots'] <= 0) {
                $missing_fields[] = 'session_full';
            } else {
                // 檢查該 email 是否在同一天已經有其他場次的報名
                if (!empty($_POST['email'])) {
                    $duplicate_check_query = "SELECT COUNT(*) as count 
                                               FROM admission_applications a
                                               INNER JOIN admission_sessions s ON a.session_id = s.id
                                               WHERE a.email = ? AND s.session_date = ? AND s.is_active = 1";
                    $duplicate_check_stmt = $conn->prepare($duplicate_check_query);
                    $duplicate_check_stmt->bind_param("ss", $_POST['email'], $session_row['session_date']);
                    $duplicate_check_stmt->execute();
                    $duplicate_check_result = $duplicate_check_stmt->get_result();
                    
                    if ($duplicate_row = $duplicate_check_result->fetch_assoc()) {
                        if ($duplicate_row['count'] > 0) {
                            $missing_fields[] = 'duplicate_same_day';
                        }
                    }
                    $duplicate_check_stmt->close();
                }
            }
        } else {
            $missing_fields[] = 'session_invalid';
        }
        $session_check_stmt->close();
    }
    
    // 驗證驗證碼（不區分大小寫）
    $captcha_input = trim($_POST['captcha'] ?? '');
    $captcha_session = $_SESSION['captcha_code'] ?? '';
    
    // 只允許英文大寫字母和數字
    $captcha_input = preg_replace('/[^A-Z0-9]/i', '', $captcha_input);
    $captcha_input = strtoupper($captcha_input);
    
    error_log("驗證碼檢查 - 輸入: '{$captcha_input}' (長度: " . strlen($captcha_input) . "), Session: '{$captcha_session}' (長度: " . strlen($captcha_session) . "), Session ID: " . session_id());
    
    if (empty($captcha_input)) {
        $missing_fields[] = 'captcha';
        error_log("驗證碼為空");
    } elseif (empty($captcha_session)) {
        $missing_fields[] = 'captcha';
        error_log("Session 中沒有驗證碼，可能 Session 過期");
    } elseif ($captcha_input !== strtoupper($captcha_session)) {
        $missing_fields[] = 'captcha_invalid';
        error_log("驗證碼不匹配 - 輸入: '{$captcha_input}', Session: '" . strtoupper($captcha_session) . "'");
    } else {
        error_log("驗證碼驗證成功");
    }
    
    // 驗證電子郵件格式
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $missing_fields[] = 'email_invalid';
    }
    
    // 驗證電話號碼格式 (必須是09開頭的10位數字)
    if (!empty($_POST['contact_phone'])) {
        $phone = preg_replace('/[^0-9]/', '', $_POST['contact_phone']); // 移除非數字字符
        if (!preg_match('/^09[0-9]{8}$/', $phone)) {
            $missing_fields[] = 'phone_invalid';
        }
        $_POST['contact_phone'] = $phone; // 標準化電話號碼格式
    }
    
    // 驗證就讀年級（必須從系統選項中選擇，存儲 code）
    $grade_code = '';
    if (!empty($_POST['grade'])) {
        $grade_input = $_POST['grade'];
        // 檢查是代碼還是名稱，轉換為代碼
        if (isset($grades_map[$grade_input])) {
            // 輸入的是代碼
            $grade_code = $grade_input;
        } else {
            // 輸入的是名稱，需要找到對應的代碼
            $found = false;
            foreach ($grades_map as $code => $name) {
                if ($name === $grade_input) {
                    $grade_code = $code;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $missing_fields[] = 'grade_invalid';
            }
        }
    }
    
    // 驗證就讀國中格式（必須從系統選項中選擇）
    if (!empty($_POST['school_name'])) {
        $school_name = $_POST['school_name'];
        // 檢查格式是否為：學校名稱 (縣市區)
        if (!preg_match('/^.+ \(.+\)$/', $school_name)) {
            $missing_fields[] = 'school_name_invalid';
        }
    }
    
    if (empty($missing_fields)) {
        // 處理科系優先順序（存儲 departments.code）
        $course_priority_1 = '';
        $course_priority_2 = '';
        
        if (isset($_POST['course_priority_1']) && !empty($_POST['course_priority_1'])) {
            $priority1_input = $_POST['course_priority_1'];
            // 檢查是代碼還是名稱，轉換為代碼
            if (isset($departments_map[$priority1_input])) {
                $course_priority_1 = $priority1_input; // 已經是代碼
            } else {
                // 輸入的是名稱，需要找到對應的代碼
                foreach ($departments_map as $code => $name) {
                    if ($name === $priority1_input) {
                        $course_priority_1 = $code;
                        break;
                    }
                }
            }
        }
        
        if (isset($_POST['course_priority_2']) && !empty($_POST['course_priority_2'])) {
            $priority2_input = $_POST['course_priority_2'];
            // 檢查是代碼還是名稱，轉換為代碼
            if (isset($departments_map[$priority2_input])) {
                $course_priority_2 = $priority2_input; // 已經是代碼
            } else {
                // 輸入的是名稱，需要找到對應的代碼
                foreach ($departments_map as $code => $name) {
                    if ($name === $priority2_input) {
                        $course_priority_2 = $code;
                        break;
                    }
                }
            }
        }
        
        // 檢查第一科系和第二科系是否重複
        if (!empty($course_priority_1) && !empty($course_priority_2) && $course_priority_1 === $course_priority_2) {
            $missing_fields[] = 'duplicate_course';
        }
        
        // 確保資料庫連接有效
        if (!$conn || $conn->ping() === false) {
            $conn = getDatabaseConnection();
        }
        
        // 驗證科系代碼是否存在於 departments 表中，並將空字串轉為 NULL
        if (!empty($course_priority_1)) {
            $dept_check_query = "SELECT code FROM departments WHERE code = ? LIMIT 1";
            $dept_check_stmt = $conn->prepare($dept_check_query);
            if ($dept_check_stmt === false) {
                error_log("準備驗證科系代碼1失敗: " . $conn->error);
                $course_priority_1 = null; // 如果查詢失敗，設為 NULL
            } else {
                $dept_check_stmt->bind_param("s", $course_priority_1);
                $dept_check_stmt->execute();
                $dept_check_result = $dept_check_stmt->get_result();
                if ($dept_check_result->num_rows === 0) {
                    // 科系代碼不存在，設為 NULL
                    error_log("警告：科系代碼1不存在於 departments 表中: " . $course_priority_1);
                    $course_priority_1 = null;
                }
                $dept_check_stmt->close();
            }
        } else {
            // 空字串轉為 NULL，以符合外鍵約束
            $course_priority_1 = null;
        }
        
        if (!empty($course_priority_2)) {
            $dept_check_query2 = "SELECT code FROM departments WHERE code = ? LIMIT 1";
            $dept_check_stmt2 = $conn->prepare($dept_check_query2);
            if ($dept_check_stmt2 === false) {
                error_log("準備驗證科系代碼2失敗: " . $conn->error);
                $course_priority_2 = null; // 如果查詢失敗，設為 NULL
            } else {
                $dept_check_stmt2->bind_param("s", $course_priority_2);
                $dept_check_stmt2->execute();
                $dept_check_result2 = $dept_check_stmt2->get_result();
                if ($dept_check_result2->num_rows === 0) {
                    // 科系代碼不存在，設為 NULL
                    error_log("警告：科系代碼2不存在於 departments 表中: " . $course_priority_2);
                    $course_priority_2 = null;
                }
                $dept_check_stmt2->close();
            }
        } else {
            // 空字串轉為 NULL，以符合外鍵約束
            $course_priority_2 = null;
        }
        
        // 取得選擇的場次資訊
        $session_info_query = "SELECT id, session_name FROM admission_sessions WHERE id = ?";
        $session_stmt = $conn->prepare($session_info_query);
        $session_stmt->bind_param("i", $_POST['session_choice']);
        $session_stmt->execute();
        $session_result = $session_stmt->get_result();
        $session_id = $_POST['session_choice']; // 場次ID
        $session_name = '';
        if ($session_row = $session_result->fetch_assoc()) {
            $session_name = $session_row['session_name'];
        }
        $session_stmt->close();
        
        // 從學校名稱中提取 school_code
        // 格式：學校名稱 (城市區域) -> 需要查詢 school_data 表獲取 school_code
        $school_code = '';
        if (!empty($_POST['school_name'])) {
            $school_name_input = $_POST['school_name'];
            // 移除格式中的 (城市區域) 部分，只保留學校名稱
            $school_name_only = preg_replace('/\s*\([^)]*\)\s*$/', '', $school_name_input);
            
            // 查詢 school_data 表獲取 school_code
            $school_query = "SELECT school_code FROM school_data WHERE name = ? AND is_active = 1 LIMIT 1";
            $school_stmt = $conn->prepare($school_query);
            $school_stmt->bind_param("s", $school_name_only);
            $school_stmt->execute();
            $school_result = $school_stmt->get_result();
            
            if ($school_row = $school_result->fetch_assoc()) {
                $school_code = $school_row['school_code'];
            } else {
                // 如果找不到，嘗試模糊匹配
                $school_query2 = "SELECT school_code FROM school_data WHERE name LIKE ? AND is_active = 1 LIMIT 1";
                $school_stmt2 = $conn->prepare($school_query2);
                $like_pattern = "%" . $school_name_only . "%";
                $school_stmt2->bind_param("s", $like_pattern);
                $school_stmt2->execute();
                $school_result2 = $school_stmt2->get_result();
                if ($school_row2 = $school_result2->fetch_assoc()) {
                    $school_code = $school_row2['school_code'];
                }
                $school_stmt2->close();
            }
            $school_stmt->close();
            
            if (empty($school_code)) {
                $missing_fields[] = 'school_not_found';
            }
        }
        
        // 轉換 receive_info 為 tinyint (0/1)
        $receive_info_value = 0;
        if (!empty($_POST['receive_info'])) {
            $receive_info_value = ($_POST['receive_info'] === '是，願意') ? 1 : 0;
        }
        
        // 是否參加過社團活動、是否來過技藝班（選填，未選則為 0）
        $joined_club_activity = isset($_POST['joined_club_activity']) ? (int)$_POST['joined_club_activity'] : 0;
        $attended_skill_class = isset($_POST['attended_skill_class']) ? (int)$_POST['attended_skill_class'] : 0;
        
        // 插入資料（使用正確的表名和字段名）
        $sql = "INSERT INTO admission_applications (email, school, student_name, grade, parent_name, contact_phone, line_id, session_id, course_priority_1, course_priority_2, receive_info, joined_club_activity, attended_skill_class, email_sent, reminder_sent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL 準備失敗: " . $conn->error . " | SQL: " . $sql);
        }
        
        // 使用 "s" 類型綁定，但允許 NULL 值
        // course_priority_1 和 course_priority_2 可能為 NULL
        $stmt->bind_param("ssssssiissiii", 
            $_POST['email'],
            $school_code,  // 存儲 school_data.school_code (varchar(20))
            $_POST['student_name'],
            $grade_code,  // 存儲 identity_options.code (J1, J2, J3)
            $_POST['parent_name'],
            $_POST['contact_phone'],
            $_POST['line_id'],
            $session_id,  // int
            $course_priority_1,  // 存儲 departments.code 或 NULL
            $course_priority_2,  // 存儲 departments.code 或 NULL
            $receive_info_value,  // tinyint: 0=否, 1=是
            $joined_club_activity,  // 是否參加過社團活動 0=否, 1=是
            $attended_skill_class   // 是否來過技藝班 0=否, 1=是
        );
        
        if ($stmt->execute()) {
            // 獲取新插入記錄的ID
            $application_id = $conn->insert_id;
            
            // 發送歡迎郵件
            try {
                require_once 'includes/email_functions.php';
                
                // 從departments表查詢科系名稱（將代碼轉換為名稱）
                $course_info = [];
                if (!empty($course_priority_1)) {
                    // 查詢科系名稱
                    $dept_query_1 = "SELECT name FROM departments WHERE code = ? LIMIT 1";
                    $dept_stmt_1 = $conn->prepare($dept_query_1);
                    if ($dept_stmt_1 === false) {
                        error_log("準備查詢科系名稱1失敗: " . $conn->error);
                        $dept_name_1 = $course_priority_1; // 預設使用代碼
                    } else {
                        $dept_stmt_1->bind_param("s", $course_priority_1);
                        $dept_stmt_1->execute();
                        $dept_result_1 = $dept_stmt_1->get_result();
                        $dept_name_1 = $course_priority_1; // 預設使用代碼
                        if ($dept_row_1 = $dept_result_1->fetch_assoc()) {
                            $dept_name_1 = $dept_row_1['name'];
                        }
                        $dept_stmt_1->close();
                    }
                    $course_info[] = "第一選擇：" . $dept_name_1;
                }
                if (!empty($course_priority_2)) {
                    // 查詢科系名稱
                    $dept_query_2 = "SELECT name FROM departments WHERE code = ? LIMIT 1";
                    $dept_stmt_2 = $conn->prepare($dept_query_2);
                    if ($dept_stmt_2 === false) {
                        error_log("準備查詢科系名稱2失敗: " . $conn->error);
                        $dept_name_2 = $course_priority_2; // 預設使用代碼
                    } else {
                        $dept_stmt_2->bind_param("s", $course_priority_2);
                        $dept_stmt_2->execute();
                        $dept_result_2 = $dept_stmt_2->get_result();
                        $dept_name_2 = $course_priority_2; // 預設使用代碼
                        if ($dept_row_2 = $dept_result_2->fetch_assoc()) {
                            $dept_name_2 = $dept_row_2['name'];
                        }
                        $dept_stmt_2->close();
                    }
                    $course_info[] = "第二選擇：" . $dept_name_2;
                }
                $course_text = !empty($course_info) ? implode('、', $course_info) : '未選擇體驗課程';
                
                // 查詢場次資訊（包含 session_type、location、online_link）
                $session_type = '實體';
                $location = '';
                $online_link = '';
                $session_info_query = "SELECT session_type, location, online_link FROM admission_sessions WHERE id = ?";
                $session_info_stmt = $conn->prepare($session_info_query);
                if ($session_info_stmt === false) {
                    error_log("準備查詢場次資訊失敗: " . $conn->error);
                } else {
                    $session_info_stmt->bind_param("i", $session_id);
                    $session_info_stmt->execute();
                    $session_info_result = $session_info_stmt->get_result();
                    if ($session_info_row = $session_info_result->fetch_assoc()) {
                        $session_type = $session_info_row['session_type'] ?? '實體';
                        $location = $session_info_row['location'] ?? '';
                        $online_link = $session_info_row['online_link'] ?? '';
                    }
                    $session_info_stmt->close();
                }
                
                // 嘗試發送歡迎郵件
                $email_sent = sendWelcomeEmail(
                    $_POST['email'],
                    $_POST['student_name'],
                    $_POST['parent_name'],
                    $session_name,
                    $course_text,
                    $session_type,
                    $location,
                    $online_link
                );
                
                // 更新郵件發送狀態
                if ($email_sent) {
                    $update_sql = "UPDATE admission_applications SET email_sent = 1, email_sent_at = NOW() WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("i", $application_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    $message = "報名資料已成功提交！歡迎郵件已發送到您的信箱，我們會在活動前一天再次發送提醒郵件。";
                } else {
                    $message = "報名資料已成功提交！歡迎郵件發送失敗，但我們會在活動前一天發送提醒郵件。";
                }
                
            } catch (Exception $e) {
                // 即使郵件發送失敗，也不影響報名成功
                $message = "報名資料已成功提交！我們會在活動前一天發送提醒郵件。";
                error_log("歡迎郵件發送失敗: " . $e->getMessage());
            }
            
            // 檢查是否需要立即發送提醒郵件
            try {
                // 獲取場次日期和相關資訊
                $session_date_query = "SELECT session_date, session_type, location, online_link FROM admission_sessions WHERE id = ?";
                $session_date_stmt = $conn->prepare($session_date_query);
                if ($session_date_stmt === false) {
                    error_log("準備查詢場次日期失敗: " . $conn->error);
                } else {
                    $session_date_stmt->bind_param("i", $session_id);
                    $session_date_stmt->execute();
                    $session_date_result = $session_date_stmt->get_result();
                    
                    if ($session_date_row = $session_date_result->fetch_assoc()) {
                        $session_date = $session_date_row['session_date'];
                        $session_type_reminder = $session_date_row['session_type'] ?? '實體';
                        $location_reminder = $session_date_row['location'] ?? '';
                        $online_link_reminder = $session_date_row['online_link'] ?? '';
                        $today = new DateTime();
                        $session_date_obj = new DateTime($session_date);
                        $days_until_session = $today->diff($session_date_obj)->days;
                        
                        // 如果活動是明天或今天，立即發送提醒郵件
                        if ($days_until_session <= 1) {
                            $reminder_sent = sendReminderEmail(
                                $_POST['email'],
                                $_POST['student_name'],
                                $_POST['parent_name'],
                                $session_name,
                                $session_date,
                                $session_type_reminder,
                                $location_reminder,
                                $online_link_reminder
                            );
                            
                            if ($reminder_sent) {
                                // 更新提醒郵件發送狀態
                                $reminder_update_sql = "UPDATE admission_applications SET reminder_sent = 1, reminder_sent_at = NOW() WHERE id = ?";
                                $reminder_update_stmt = $conn->prepare($reminder_update_sql);
                                if ($reminder_update_stmt === false) {
                                    error_log("準備更新提醒郵件狀態失敗: " . $conn->error);
                                } else {
                                    $reminder_update_stmt->bind_param("i", $application_id);
                                    $reminder_update_stmt->execute();
                                    $reminder_update_stmt->close();
                                }
                                
                                $message .= " 提醒郵件也已發送！";
                            }
                        }
                    }
                    $session_date_stmt->close();
                }
                
            } catch (Exception $e) {
                // 提醒郵件發送失敗不影響報名成功
                error_log("提醒郵件發送失敗: " . $e->getMessage());
            }
            
            $messageType = "success";
            // 提交成功後清除驗證碼（將由 captcha_image.php 重新生成）
            unset($_SESSION['captcha_code']);
            // 設定成功訊息和自動刷新標記
            $_SESSION['success_message'] = $message;
            $_SESSION['should_refresh'] = true;
            $_SESSION['auto_refresh'] = true; // 標記需要自動刷新
            // 清空 POST 資料，避免表單資料被保留
            $_POST = array();
            
            // 重新導向到同一頁面以觸發成功訊息和自動刷新
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $message = "報名失敗：" . $stmt->error;
            $messageType = "error";
        }
        
        $stmt->close();
    } else {
        $error_messages = [
            'email' => '請填寫電子郵件',
            'email_invalid' => '電子郵件格式不正確',
            'school_name' => '請填寫學校名稱',
            'school_name_invalid' => '請從系統提供的選項中選擇學校，不能自行輸入',
            'school_not_found' => '找不到對應的學校代碼，請重新選擇學校',
            'student_name' => '請填寫學生姓名',
            'grade' => '請選擇就讀年級',
            'grade_invalid' => '請從系統提供的選項中選擇就讀年級',
            'parent_name' => '請填寫姓名',
            'contact_phone' => '請填寫聯絡電話',
            'phone_invalid' => '聯絡電話格式不正確，請輸入09開頭的10位數字',
            'session_choice' => '請選擇參加場次',
            'session_full' => '所選場次已額滿，請選擇其他場次',
            'session_invalid' => '所選場次無效，請重新選擇',
            'duplicate_same_day' => '您已經在同一天報名過其他場次，每人同一天只能報名一次',
            'duplicate_course' => '第一科系和第二科系不能相同，請選擇不同的科系',
            'receive_info' => '請選擇是否願意收到升學訊息',
            'captcha' => '請填寫驗證碼',
            'captcha_invalid' => '驗證碼錯誤'
        ];
        
        $message = "請檢查以下欄位：" . implode('、', array_map(function($field) use ($error_messages) {
            return $error_messages[$field] ?? $field;
        }, $missing_fields));
        $messageType = "error";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>康寧大學五專入學說明會報名</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
	<link rel="stylesheet" href="assets/csp/admission.css">
    <style>
        /* 錯誤提示動畫 */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .field-error {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .field-error i {
            font-size: 14px;
        }
    </style>
</head>
<body>
<?php include("share/header.php"); ?>
<main>
    <div class="admission-container">
        <div class="header">
            <h1><i class="fas fa-graduation-cap"></i> 康寧大學五專入學說明會</h1>
            <div class="subtitle">選擇康寧 • 人生雙贏 • 未來罩您</div>
            
            <div class="contact-info">
                    <div><i class="fas fa-phone"></i> 招生諮詢電話：
                        <?php echo !empty($admission_teacher['phone']) ? htmlspecialchars($admission_teacher['phone']) : '請洽學校總機'; ?>
                    </div>
                        <div><i class="fas fa-user"></i> 招生諮詢：
                            <?php echo !empty($admission_teacher['name']) ? htmlspecialchars($admission_teacher['name']) : '請洽學校總機'; ?>
                            <?php if (!empty($admission_teacher['department'])): ?>
                                - <?php echo htmlspecialchars($admission_teacher['department_name']); ?>
                            <?php endif; ?>
                        </div>
            </div>
        </div>

        <!-- 搜尋報名記錄區域 -->
        <div class="search-container">
            <div class="search-header">
                <div class="search-title">
                    <h2>查詢我的報名記錄</h2>
                    <p>輸入您的電子郵件地址，即可查看、取消或修改您的報名場次</p>
                </div>
            </div>
            
            <form id="searchForm" class="search-form" onsubmit="return false;">
                <input type="hidden" name="action" value="search">
                <div class="search-input-group">
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="searchEmail" name="email" placeholder="請輸入您的電子郵件地址" 
                               value="<?php echo isset($_GET['action']) && $_GET['action'] === 'search' ? htmlspecialchars($search_email) : ''; ?>" autocomplete="off" required>
                    </div>
                    <button type="button" id="searchBtn" class="search-btn">
                        <i class="fas fa-search"></i>
                        <span>查詢</span>
                    </button>
                </div>
            </form>

            <!-- 使用共用 header 的驗證 modal（header.php 已提供） -->
        </div>

        <!-- 搜尋結果區域 -->
        <?php if (!empty($search_results)): ?>
        <div class="search-results">
            <h3><i class="fas fa-list"></i> 您的報名記錄</h3>
            <div class="applications-list">
                <?php foreach ($search_results as $application): ?>
                <div class="application-card">
                    <div class="application-header">
                        <h4><?php echo htmlspecialchars($application['session_name']); ?></h4>
                        <span class="session-type <?php echo $application['session_type'] === '線上' ? 'online' : 'offline'; ?>">
                            <?php echo htmlspecialchars($application['session_type']); ?>
                        </span>
                    </div>
                    <div class="application-details">
                        <div class="detail-row">
                            <span class="label">學生姓名：</span>
                            <span class="value"><?php echo htmlspecialchars($application['student_name']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">學校：</span>
                            <span class="value"><?php echo htmlspecialchars($application['school_name']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">年級：</span>
                            <span class="value"><?php 
                                $grade_display = isset($grades_map[$application['grade']]) 
                                    ? $grades_map[$application['grade']] 
                                    : htmlspecialchars($application['grade']);
                                echo htmlspecialchars($grade_display); 
                            ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">聯絡人：</span>
                            <span class="value"><?php echo htmlspecialchars($application['parent_name']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">聯絡電話：</span>
                            <span class="value"><?php echo htmlspecialchars($application['contact_phone']); ?></span>
                        </div>
                        <?php if (!empty($application['course_priority_1'])): ?>
                        <div class="detail-row">
                            <span class="label">第一選擇科系：</span>
                            <span class="value"><?php 
                                $dept_name_1 = isset($departments_map[$application['course_priority_1']]) 
                                    ? $departments_map[$application['course_priority_1']] 
                                    : htmlspecialchars($application['course_priority_1']);
                                echo htmlspecialchars($dept_name_1); 
                            ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($application['course_priority_2'])): ?>
                        <div class="detail-row">
                            <span class="label">第二選擇科系：</span>
                            <span class="value"><?php 
                                $dept_name_2 = isset($departments_map[$application['course_priority_2']]) 
                                    ? $departments_map[$application['course_priority_2']] 
                                    : htmlspecialchars($application['course_priority_2']);
                                echo htmlspecialchars($dept_name_2); 
                            ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="detail-row">
                            <span class="label">報名時間：</span>
                            <span class="value"><?php echo date('Y-m-d H:i', strtotime($application['created_at'])); ?></span>
                        </div>
                    </div>
                    <div class="application-actions">
                        <button type="button" class="btn-modify" onclick="showModifyForm(<?php echo $application['id']; ?>, '<?php echo htmlspecialchars($application['email'], ENT_QUOTES); ?>', <?php echo $application['session_id']; ?>, '<?php echo htmlspecialchars($application['course_priority_1'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($application['course_priority_2'], ENT_QUOTES); ?>')">
                            <i class="fas fa-edit"></i> 修改
                        </button>
                        <button type="button" class="btn-cancel" onclick="confirmCancel(<?php echo $application['id']; ?>, '<?php echo htmlspecialchars($application['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($application['session_name'], ENT_QUOTES); ?>')">
                            <i class="fas fa-times"></i> 刪除
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-container">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="off">
                <!-- 基本資訊 -->
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> 基本資訊</h3>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span> 電子郵件：</label>
                            <input type="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" autocomplete="off" required>
                        </div>
                        <div class="field-group">
                            <label><span class="required">*</span> 學校名稱：</label>
                            <div class="modern-search-container">
                                <div class="search-input-wrapper">
                                    <input type="text" id="school_name" name="school_name" placeholder="請輸入學校名稱..." autocomplete="off" value="<?php echo isset($_POST['school_name']) ? htmlspecialchars($_POST['school_name']) : ''; ?>" required>
                                    <div class="search-icon">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <div class="clear-btn" id="clearSchoolSearch" style="display: none;">
                                        <i class="fas fa-times"></i>
                                    </div>
                                </div>
                                <div id="schoolResults" class="modern-search-results"></div>
                            </div>
                            <div class="help-text">
                                <i class="fas fa-info-circle"></i> 輸入學校名稱即可即時搜尋，請從搜尋結果中選擇學校（不能自行輸入）
                            </div>
                            <div id="school_name_error" class="field-error" style="display: none; color: #d32f2f; font-size: 13px; margin-top: 8px; padding: 8px 12px; background-color: #ffebee; border-left: 3px solid #d32f2f; border-radius: 4px; animation: slideDown 0.3s ease;">
                                <i class="fas fa-exclamation-circle"></i> <span id="school_name_error_text">請從系統提供的選項中選擇學校，不能自行輸入</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span> 學生姓名：</label>
                            <input type="text" name="student_name" value="<?php echo isset($_POST['student_name']) ? htmlspecialchars($_POST['student_name']) : ''; ?>" autocomplete="off" required>
                        </div>
                        <div class="field-group">
                            <label><span class="required">*</span> 就讀年級：</label>
                            <select name="grade" required>
                                <option value="">請選擇年級</option>
                                <?php foreach ($grades as $grade): ?>
                                    <option value="<?php echo htmlspecialchars($grade['code']); ?>" <?php echo (isset($_POST['grade']) && $_POST['grade'] === $grade['code']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($grade['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 聯絡資訊 -->
                <div class="form-section">
                    <h3><i class="fas fa-address-book"></i> 聯絡資訊</h3>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span> 姓名：</label>
                            <input type="text" name="parent_name" value="<?php echo isset($_POST['parent_name']) ? htmlspecialchars($_POST['parent_name']) : ''; ?>" autocomplete="off" required>
                        </div>
                        <div class="field-group">
                            <label><span class="required">*</span> 聯絡電話：</label>
                            <input type="tel" name="contact_phone" maxlength="10" value="<?php echo isset($_POST['contact_phone']) ? htmlspecialchars($_POST['contact_phone']) : ''; ?>" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-group">
                            <label>LINE ID (選填)：</label>
                            <input type="text" name="line_id" placeholder="如有LINE帳號可填寫，方便聯繫" value="<?php echo isset($_POST['line_id']) ? htmlspecialchars($_POST['line_id']) : ''; ?>" autocomplete="off">
                        </div>
                    </div>
                </div>

                <!-- 其他經驗（選填） -->
                <div class="form-section">
                    <h3><i class="fas fa-star"></i> 其他經驗（選填）</h3>
                    <div class="form-row">
                        <div class="field-group">
                            <label>是否參加過我們學校的社團活動：</label>
                            <div class="radio-group" style="display: flex; gap: 20px; flex-wrap: wrap;">
                                <label class="radio-item">
                                    <input type="radio" name="joined_club_activity" value="1" <?php echo (isset($_POST['joined_club_activity']) && $_POST['joined_club_activity'] === '1') ? 'checked' : ''; ?>>
                                    <span>是</span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="joined_club_activity" value="0" <?php echo (isset($_POST['joined_club_activity']) && $_POST['joined_club_activity'] === '0') ? 'checked' : ''; ?>>
                                    <span>否</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-group">
                            <label>是否有來過我們學校的技藝班：</label>
                            <div class="radio-group" style="display: flex; gap: 20px; flex-wrap: wrap;">
                                <label class="radio-item">
                                    <input type="radio" name="attended_skill_class" value="1" <?php echo (isset($_POST['attended_skill_class']) && $_POST['attended_skill_class'] === '1') ? 'checked' : ''; ?>>
                                    <span>是</span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="attended_skill_class" value="0" <?php echo (isset($_POST['attended_skill_class']) && $_POST['attended_skill_class'] === '0') ? 'checked' : ''; ?>>
                                    <span>否</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 活動選擇 -->
                <div class="form-section">
                    <h3> <span class="required">*</span> <i class="fas fa-calendar-alt"></i> 參加場次 </h3>
                    <div class="radio-group">
                        <?php if (empty($sessions)): ?>
                            <p style="color: #e74c3c; font-weight: bold;">目前沒有開放報名的場次，請稍後再試。</p>
                        <?php else: ?>
                            <?php foreach ($sessions as $session): 
                                $is_full = $session['remaining_spots'] <= 0;
                                $type_label = ((int)$session['session_type'] === 1)
                                ? '<span class="badge badge-online">線上</span>'
                                : '<span class="badge badge-offline">實體</span>';
                                $session_display = $session['session_name'] . ' ' . $type_label;
                                $registered_count = $session['registered_count'] ?? 0;
                                $spots_info = "（{$registered_count}/{$session['max_participants']} 人）";
                            ?>
                                <label class="radio-item <?php echo $is_full ? 'disabled' : ''; ?>">
                                    <input type="radio" name="session_choice" value="<?php echo $session['id']; ?>" 
                                           <?php echo (isset($_POST['session_choice']) && $_POST['session_choice'] == $session['id']) ? 'checked' : ''; ?>
                                           <?php echo $is_full ? 'disabled' : ''; ?> required>
                                           <span class="<?php echo $is_full ? 'full-session' : ''; ?>">
                                                <?php echo htmlspecialchars($session['session_name']); ?>
                                                <span class="spots-info"><?php echo $spots_info; ?></span>
                                                <?php echo $type_label; ?>
                                                <?php if ($is_full): ?>
                                                <span class="full-badge">額滿</span>
                                                <?php endif; ?>
                                           </span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 科系選擇 -->
                <div class="form-section">
                    <h3><span class="required">*</span> <i class="fas fa-book-open"></i>  科系選擇 </h3>
                    <p style="margin-bottom: 15px; color: #666;">請從下方科系中拖曳最多兩個科系到右側框框中，並可調整優先順序</p>
                    
                    <div class="course-selection-container">
                        <!-- 可選科系列表 -->
                        <div class="available-courses">
                            <h4><i class="fas fa-list"></i> 可選科系</h4>
                            <div class="course-list" id="availableCourses">
                                <?php foreach ($departments as $dept):
                                    if ($dept['code'] === 'AA' || $dept['code'] === 'LTC') continue; ?>  
                                    <div class="course-item" draggable="true" data-course="<?php echo htmlspecialchars($dept['code']); ?>" data-course-name="<?php echo htmlspecialchars($dept['name']); ?>">
                                        <i class="fas fa-grip-vertical"></i>
                                        <span><?php echo htmlspecialchars($dept['name']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- 已選課程框 -->
                        <div class="selected-courses">
                            <h4><i class="fas fa-star"></i> 我的選擇 (最多2個)</h4>
                            <div class="course-drop-zone" id="selectedCourses">
                                <div class="drop-placeholder">
                                    <i class="fas fa-hand-point-right"></i>
                                    <p>請拖曳課程到這裡</p>
                                    <small>第一個為優先選擇</small>
                                </div>
                            </div>
                            <div class="priority-info">
                                <small><i class="fas fa-info-circle"></i> 排序說明：上方為第一優先，下方為第二優先</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 隱藏欄位用於表單提交 -->
                    <input type="hidden" name="course_priority_1" id="coursePriority1" value="">
                    <input type="hidden" name="course_priority_2" id="coursePriority2" value="">
                </div>

                <!-- 資訊接收 -->
                <div class="form-section">
                    <h3> <span class="required">*</span> <i class="fas fa-envelope"></i> 資訊接收 </h3>
                    <p style="margin-bottom: 15px; color: #666;">活動結束後，是否願意收到其他相關五專升學訊息</p>
                    <div class="radio-group">
                        <label class="radio-item">
                            <input type="radio" name="receive_info" value="是，願意" <?php echo (isset($_POST['receive_info']) && $_POST['receive_info'] === '是，願意') ? 'checked' : ''; ?> required>
                            <span>是，願意</span>
                        </label>
                        <label class="radio-item">
                            <input type="radio" name="receive_info" value="否，不願意" <?php echo (isset($_POST['receive_info']) && $_POST['receive_info'] === '否，不願意') ? 'checked' : ''; ?> required>
                            <span>否，不願意</span>
                        </label>
                    </div>
                </div>

                <!-- 驗證碼 -->
                <div class="captcha-section">
                    <h3> <span class="required">*</span> <i class="fas fa-shield-alt"></i> 安全驗證 </h3>
                    <div style="display: flex; align-items: center; gap: 10px; margin: 15px 0;">
                        <input type="text" name="captcha" id="captchaInput" placeholder="請輸入驗證碼" required maxlength="6" autocomplete="off" style="flex: 1; min-width: 150px; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; text-transform: uppercase;" oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '')">
                        <img src="captcha_image.php" id="captchaImage" alt="驗證碼" onclick="refreshCaptcha()" style="height: 50px; width: 150px; border: 2px solid #ddd; border-radius: 5px; cursor: pointer;" title="點擊刷新驗證碼">
                        <button type="button" onclick="refreshCaptcha()" style="padding: 10px 15px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer;">
                            <i class="fas fa-sync-alt"></i> 刷新
                        </button>
                    </div>
                    <small style="color: #666; display: block; margin-top: 5px;">
                        <i class="fas fa-info-circle"></i> 請輸入圖片中顯示的字母和數字（英文會自動轉換為大寫）
                    </small>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> 提交報名資料
                </button>
            </form>
        </div>
    </div>

    <script>
        // 阻止所有非課程項目的拖曳行為
        document.addEventListener('dragstart', function(e) {
            // 只允許 .course-item 和 .selected-course-item 被拖曳
            const isCourseItem = e.target.closest('.course-item') || e.target.closest('.selected-course-item');
            if (!isCourseItem) {
                e.preventDefault();
                return false;
            }
        }, true);
        
        // 電話號碼格式驗證
        const phoneInput = document.querySelector('[name="contact_phone"]');
        
        phoneInput.addEventListener('input', function(e) {
            // 只保留數字
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // 檢查格式並設定邊框顏色
            if (this.value.length === 10 && this.value.startsWith('09')) {
                // 正確格式：綠色邊框
                this.style.borderColor = '#27ae60';
                this.style.borderWidth = '2px';
            } else if (this.value.length > 0) {
                // 錯誤格式：紅色邊框  
                this.style.borderColor = '#e74c3c';
                this.style.borderWidth = '2px';
            } else {
                // 空白：預設邊框
                this.style.borderColor = '#ddd';
                this.style.borderWidth = '2px';
            }
        });

        // 拖曳式課程選擇功能
        let selectedCourses = [];
        const maxCourses = 2;

        // 初始化拖曳事件
        function initializeDragAndDrop() {
            const availableCourses = document.getElementById('availableCourses');
            const selectedCoursesZone = document.getElementById('selectedCourses');

            // 為所有課程項目添加拖曳事件
            availableCourses.querySelectorAll('.course-item').forEach(item => {
                item.addEventListener('dragstart', handleDragStart);
            });

            // 為選擇區域添加放置事件
            selectedCoursesZone.addEventListener('dragover', handleDragOver);
            selectedCoursesZone.addEventListener('drop', handleDrop);
            selectedCoursesZone.addEventListener('dragenter', handleDragEnter);
            selectedCoursesZone.addEventListener('dragleave', handleDragLeave);
        }

        function handleDragStart(e) {
            // 確保只有 .course-item 元素可以被拖曳
            const courseItem = e.target.closest('.course-item');
            if (!courseItem) {
                e.preventDefault();
                return;
            }
            
            const courseCode = courseItem.dataset.course;
            const courseName = courseItem.dataset.courseName || courseCode;
            
            if (!courseCode) {
                e.preventDefault();
                return;
            }
            
            // 設置拖曳數據和標記
            e.dataTransfer.setData('text/plain', courseCode);
            e.dataTransfer.setData('text/course-name', courseName);
            e.dataTransfer.setData('application/course-item', 'true'); // 標記這是有效的課程項目
            e.dataTransfer.effectAllowed = 'copy';
        }

        function handleDragOver(e) {
            e.preventDefault();
            // 只有有效的課程項目才允許放置
            const isValidCourse = e.dataTransfer.types.includes('application/course-item');
            e.dataTransfer.dropEffect = isValidCourse ? 'copy' : 'none';
        }

        function handleDragEnter(e) {
            e.preventDefault();
            // 只有有效的課程項目才顯示拖曳效果
            const isValidCourse = e.dataTransfer.types.includes('application/course-item');
            if (isValidCourse) {
                const dropZone = e.target.closest('.course-drop-zone');
                if (dropZone) {
                    dropZone.classList.add('drag-over');
                }
            }
        }

        function handleDragLeave(e) {
            if (!e.target.closest('.course-drop-zone').contains(e.relatedTarget)) {
                e.target.closest('.course-drop-zone').classList.remove('drag-over');
            }
        }

        function handleDrop(e) {
            e.preventDefault();
            const dropZone = e.target.closest('.course-drop-zone');
            
            if (!dropZone) {
                return;
            }
            
            dropZone.classList.remove('drag-over');

            // 檢查是否為有效的課程項目（只有從 available-courses 拖來的才有效）
            const isValidCourse = e.dataTransfer.getData('application/course-item') === 'true';
            if (!isValidCourse) {
                // 不是從課程列表拖來的，忽略
                return;
            }

            const courseCode = e.dataTransfer.getData('text/plain');
            const courseName = e.dataTransfer.getData('text/course-name') || courseCode;
            
            // 驗證 courseCode 是否為空或無效
            if (!courseCode || courseCode.trim() === '') {
                return;
            }

            // 檢查是否已經選擇過這個科系
            if (selectedCourses.some(c => c.code === courseCode)) {
                alert('此科系已經被選擇了！');
                return;
            }

            // 檢查是否超過最大選擇數量
            if (selectedCourses.length >= maxCourses) {
                alert(`最多只能選擇 ${maxCourses} 個科系！`);
                return;
            }

            // 添加到選擇列表（存儲代碼和名稱）
            selectedCourses.push({code: courseCode, name: courseName});
            updateSelectedCoursesDisplay();
            updateHiddenFields();
        }

        function updateSelectedCoursesDisplay() {
            const selectedCoursesZone = document.getElementById('selectedCourses');
            
            if (selectedCourses.length === 0) {
                selectedCoursesZone.innerHTML = `
                    <div class="drop-placeholder">
                        <i class="fas fa-hand-point-right"></i>
                        <p>請拖曳課程到這裡</p>
                        <small>第一個為優先選擇</small>
                    </div>
                `;
                return;
            }

            let html = '';
            selectedCourses.forEach((course, index) => {
                const priorityText = index === 0 ? '第一優先' : '第二優先';
                const courseCode = typeof course === 'object' ? course.code : course;
                const courseName = typeof course === 'object' ? course.name : course;
                html += `
                    <div class="selected-course-item" data-course="${courseCode}">
                        <div class="course-info">
                            <div class="course-name">${courseName}</div>
                        </div>
                        <div class="course-actions">
                            <span class="priority-badge">${priorityText}</span>
                            <button type="button" class="remove-btn" onclick="removeCourse('${courseCode}')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `;
            });

            selectedCoursesZone.innerHTML = html;

            // 為已選課程添加排序功能
            selectedCoursesZone.querySelectorAll('.selected-course-item').forEach(item => {
                item.addEventListener('dragstart', handleSelectedDragStart);
                item.addEventListener('dragover', handleSelectedDragOver);
                item.addEventListener('drop', handleSelectedDrop);
                item.setAttribute('draggable', 'true');
            });
        }

        function handleSelectedDragStart(e) {
            const courseItem = e.target.closest('.selected-course-item');
            const courseCode = courseItem.dataset.course;
            e.dataTransfer.setData('text/plain', courseCode);
            e.dataTransfer.effectAllowed = 'move';
        }

        function handleSelectedDragOver(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        }

        function handleSelectedDrop(e) {
            e.preventDefault();
            const draggedCourseCode = e.dataTransfer.getData('text/plain');
            const targetCourseItem = e.target.closest('.selected-course-item');
            const targetCourseCode = targetCourseItem ? targetCourseItem.dataset.course : null;

            if (targetCourseCode && draggedCourseCode !== targetCourseCode) {
                // 交換位置
                const draggedIndex = selectedCourses.findIndex(c => (typeof c === 'object' ? c.code : c) === draggedCourseCode);
                const targetIndex = selectedCourses.findIndex(c => (typeof c === 'object' ? c.code : c) === targetCourseCode);

                if (draggedIndex > -1 && targetIndex > -1) {
                    const temp = selectedCourses[draggedIndex];
                    selectedCourses[draggedIndex] = selectedCourses[targetIndex];
                    selectedCourses[targetIndex] = temp;

                    updateSelectedCoursesDisplay();
                    updateHiddenFields();
                }
            }
        }

        function removeCourse(courseCode) {
            const index = selectedCourses.findIndex(c => (typeof c === 'object' ? c.code : c) === courseCode);
            if (index > -1) {
                selectedCourses.splice(index, 1);
                updateSelectedCoursesDisplay();
                updateHiddenFields();
            }
        }

        function updateHiddenFields() {
            // 存儲科系代碼（departments.code）
            document.getElementById('coursePriority1').value = selectedCourses[0] ? (typeof selectedCourses[0] === 'object' ? selectedCourses[0].code : selectedCourses[0]) : '';
            document.getElementById('coursePriority2').value = selectedCourses[1] ? (typeof selectedCourses[1] === 'object' ? selectedCourses[1].code : selectedCourses[1]) : '';
        }
        
        // 表單提交驗證
        document.querySelector('form').addEventListener('submit', function(e) {
            const phone = phoneInput.value;
            
            // 如果電話號碼格式不正確，阻止提交
            if (phone && !/^09[0-9]{8}$/.test(phone)) {
                e.preventDefault();
                phoneInput.focus();
                return false;
            }

            // 檢查是否至少選擇了一個科系
            if (selectedCourses.length === 0) {
                e.preventDefault();
                alert('請至少選擇一個科系！');
                document.getElementById('selectedCourses').scrollIntoView({ behavior: 'smooth' });
                return false;
            }

            // 檢查第一科系和第二科系是否重複
            if (selectedCourses.length >= 2) {
                const course1 = typeof selectedCourses[0] === 'object' ? selectedCourses[0].code : selectedCourses[0];
                const course2 = typeof selectedCourses[1] === 'object' ? selectedCourses[1].code : selectedCourses[1];
                if (course1 === course2) {
                    e.preventDefault();
                    alert('第一科系和第二科系不能相同，請選擇不同的科系！');
                    document.getElementById('selectedCourses').scrollIntoView({ behavior: 'smooth' });
                    return false;
                }
            }

            // 檢查是否選擇了額滿的場次
            const selectedSession = document.querySelector('input[name="session_choice"]:checked');
            if (selectedSession && selectedSession.disabled) {
                e.preventDefault();
                alert('所選場次已額滿，請選擇其他場次！');
                return false;
            }
            
            // 驗證就讀國中格式（必須從系統選項中選擇）
            const schoolNameInput = document.getElementById('school_name');
            if (schoolNameInput) {
                const schoolName = schoolNameInput.value.trim();
                if (schoolName) {
                    // 檢查格式是否為：學校名稱 (縣市區)
                    const schoolFormatPattern = /^.+ \(.+\)$/;
                    if (!schoolFormatPattern.test(schoolName)) {
                        e.preventDefault();
                        alert('請從系統提供的選項中選擇學校，不能自行輸入');
                        schoolNameInput.focus();
                        schoolNameInput.style.borderColor = '#d32f2f';
                        showSchoolError('請從系統提供的選項中選擇學校，不能自行輸入');
                        setTimeout(() => {
                            schoolNameInput.style.borderColor = '';
                        }, 3000);
                        return false;
                    }
                }
            }
            
            // 驗證就讀年級（必須從系統選項中選擇）
            const gradeSelect = document.querySelector('select[name="grade"]');
            if (gradeSelect) {
                const selectedGrade = gradeSelect.value;
                if (selectedGrade) {
                    // 檢查選擇的年級是否在有效選項中
                    const validGrades = <?php echo json_encode($grades); ?>;
                    if (!validGrades.includes(selectedGrade)) {
                        e.preventDefault();
                        alert('請從系統提供的選項中選擇就讀年級');
                        gradeSelect.focus();
                        gradeSelect.style.borderColor = '#d32f2f';
                        setTimeout(() => {
                            gradeSelect.style.borderColor = '';
                        }, 3000);
                        return false;
                    }
                }
            }
        });

        // 頁面載入完成後初始化
        // 學校搜尋功能
        function performSchoolSearch() {
            console.log('performSchoolSearch 被調用');
            const keyword = document.getElementById('school_name').value.trim();
            const resultsDiv = document.getElementById('schoolResults');
            const clearBtn = document.getElementById('clearSchoolSearch');
            
            console.log('keyword:', keyword);
            console.log('resultsDiv:', resultsDiv);
            console.log('clearBtn:', clearBtn);

            // 顯示/隱藏清除按鈕
            if (keyword.length > 0) {
                clearBtn.style.display = 'block';
            } else {
                clearBtn.style.display = 'none';
                resultsDiv.classList.remove('show');
                // 當搜尋結果隱藏時，清除錯誤提示
                clearSchoolError();
                return;
            }

            if (keyword.length < 2) {
                console.log('關鍵字長度不足，顯示提示');
                if (resultsDiv) {
                    resultsDiv.innerHTML = '<div class="search-result-item">請輸入至少2個字元</div>';
                    resultsDiv.classList.add('show');
                    console.log('結果區域已顯示');
                } else {
                    console.error('resultsDiv 不存在！');
                }
                // 當下拉選單顯示時，清除錯誤提示（用戶還在輸入中）
                clearSchoolError();
                return;
            }

            // 顯示載入中
            console.log('開始搜尋，顯示載入中...');
            if (resultsDiv) {
                resultsDiv.innerHTML = '<div class="search-result-item"><i class="fas fa-spinner fa-spin"></i> 搜尋中...</div>';
                resultsDiv.classList.add('show');
                console.log('載入中訊息已顯示，resultsDiv.classList:', resultsDiv.classList.toString());
            } else {
                console.error('resultsDiv 不存在，無法顯示搜尋結果！');
                return;
            }
            // 當下拉選單顯示時，清除錯誤提示（用戶還在選擇中）
            clearSchoolError();

            // 從API獲取搜尋結果
            const apiUrl = `api/school_data_api.php?action=search&keyword=${encodeURIComponent(keyword)}&v=20241014-4`;
            console.log('API URL:', apiUrl);
            
            fetch(apiUrl)
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    console.log('Response text:', text);
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed data:', data);
                        return data;
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Response text:', text);
                        throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                    }
                })
                .then(data => {
                    console.log('搜尋結果:', data);
                    if (data.schools && data.schools.length > 0) {
                        resultsDiv.innerHTML = data.schools.map(school => {
                            let displayName = school.name;
                            let additionalInfo = '';
                            
                            if (school.all_names && school.all_names.length > 1) {
                                additionalInfo = `<div class="school-alternative-names">其他名稱: ${school.all_names.join(', ')}</div>`;
                            }
                            
                            // 处理 district 可能为空的情况
                            const district = school.district || '';
                            const location = district ? `${school.city}${district}` : school.city;
                            
                            return `<div class="search-result-item" onclick="selectSchool('${school.name}', '${school.city}', '${district}')">
                                <i class="fas fa-school"></i>
                                <div class="school-info">
                                    <span class="school-name">${displayName}</span>
                                    <span class="school-location">${location}</span>
                                    ${additionalInfo}
                                </div>
                            </div>`;
                        }).join('');

                        if (data.total > 20) {
                            resultsDiv.innerHTML += `<div class="search-result-item more-results">還有 ${data.total - 20} 個結果...</div>`;
                        }
                        // 當下拉選單顯示時，清除錯誤提示
                        clearSchoolError();
                    } else {
                        resultsDiv.innerHTML = '<div class="search-result-item">找不到匹配的學校</div>';
                        // 即使找不到結果，下拉選單仍然顯示，所以清除錯誤提示
                        clearSchoolError();
                    }
                })
                .catch(error => {
                    console.error('搜尋錯誤:', error);
                    resultsDiv.innerHTML = '<div class="search-result-item">搜尋失敗，請稍後再試</div>';
                    // 即使搜尋失敗，下拉選單仍然顯示，所以清除錯誤提示
                    clearSchoolError();
                });
        }

        // 清除學校輸入錯誤提示
        function clearSchoolError() {
            const errorDiv = document.getElementById('school_name_error');
            const input = document.getElementById('school_name');
            if (errorDiv) {
                errorDiv.style.display = 'none';
            }
            if (input) {
                input.style.borderColor = '';
                input.style.borderWidth = '';
                input.style.boxShadow = '';
            }
        }
        
        // 顯示學校輸入錯誤提示
        function showSchoolError(message) {
            const errorDiv = document.getElementById('school_name_error');
            const errorText = document.getElementById('school_name_error_text');
            const input = document.getElementById('school_name');
            
            if (errorDiv && errorText) {
                errorText.textContent = message || '請從系統提供的選項中選擇學校，不能自行輸入';
                errorDiv.style.display = 'block';
                // 添加動畫效果
                errorDiv.style.animation = 'none';
                setTimeout(() => {
                    errorDiv.style.animation = 'slideDown 0.3s ease';
                }, 10);
            }
            
            if (input) {
                input.style.borderColor = '#d32f2f';
                input.style.borderWidth = '2px';
                input.style.boxShadow = '0 0 0 3px rgba(211, 47, 47, 0.1)';
            }
        }
        
        // 驗證學校輸入格式
        function validateSchoolInput() {
            const input = document.getElementById('school_name');
            if (!input) return;
            
            const value = input.value.trim();
            const resultsDiv = document.getElementById('schoolResults');
            
            // 如果為空，不顯示錯誤（由required屬性處理）
            if (!value) {
                clearSchoolError();
                return;
            }
            
            // 如果下拉選單正在顯示，表示用戶還在選擇中，不顯示錯誤
            if (resultsDiv && resultsDiv.classList.contains('show')) {
                clearSchoolError();
                return;
            }
            
            // 檢查格式是否為：學校名稱 (縣市區)
            const schoolFormatPattern = /^.+ \(.+\)$/;
            if (!schoolFormatPattern.test(value)) {
                // 只有在下拉選單隱藏時才顯示錯誤
                showSchoolError('請從系統提供的選項中選擇學校，不能自行輸入');
            } else {
                clearSchoolError();
            }
        }
        
        // 立即驗證（不延遲）- 用於失去焦點時
        function validateSchoolInputImmediate() {
            validateSchoolInput();
        }
        
        // 清除搜尋
        function clearSchoolSearch() {
            document.getElementById('school_name').value = '';
            document.getElementById('schoolResults').classList.remove('show');
            document.getElementById('clearSchoolSearch').style.display = 'none';
            clearSchoolError();
        }

        // 選擇學校
        function selectSchool(schoolName, city, district) {
            // 处理 district 可能为空的情况
            const districtPart = district ? district : '';
            const fullSchoolName = districtPart ? `${schoolName} (${city}${districtPart})` : `${schoolName} (${city})`;
            document.getElementById('school_name').value = fullSchoolName;
            document.getElementById('schoolResults').classList.remove('show');
            document.getElementById('clearSchoolSearch').style.display = 'block';
            // 清除錯誤提示（因為用戶已從系統選項中選擇）
            clearSchoolError();
        }

        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, checking for success message...');
            initializeDragAndDrop();
            
            // 綁定學校搜尋事件
            const schoolSearchInput = document.getElementById('school_name');
            const clearSchoolBtn = document.getElementById('clearSchoolSearch');
            const resultsDiv = document.getElementById('schoolResults');
            
            console.log('=== 學校搜尋初始化 ===');
            console.log('schoolSearchInput:', schoolSearchInput);
            console.log('clearSchoolBtn:', clearSchoolBtn);
            console.log('resultsDiv:', resultsDiv);
            console.log('resultsDiv 樣式:', resultsDiv ? window.getComputedStyle(resultsDiv).display : 'N/A');

            if (schoolSearchInput) {
                console.log('學校搜尋輸入框找到，綁定事件監聽器');
                // 輸入事件（即時搜尋）
                schoolSearchInput.addEventListener('input', function(e) {
                    console.log('輸入事件觸發，值:', this.value);
                    performSchoolSearch();
                    // 當下拉選單顯示時，不進行驗證（用戶還在輸入和選擇中）
                });
                
                // 添加 keyup 事件作為備用
                schoolSearchInput.addEventListener('keyup', function(e) {
                    console.log('Keyup 事件觸發，值:', this.value);
                    if (this.value.length >= 2) {
                        performSchoolSearch();
                    }
                });
                
                // 失去焦點時立即驗證
                schoolSearchInput.addEventListener('blur', function() {
                    clearTimeout(schoolSearchInput.validationTimeout);
                    // 延遲一點驗證，讓點擊下拉選單項目的時間完成
                    schoolSearchInput.validationTimeout = setTimeout(validateSchoolInputImmediate, 200);
                });
                
                // 當輸入框獲得焦點時，如果已有錯誤且下拉選單未顯示，保持顯示
                schoolSearchInput.addEventListener('focus', function() {
                    const resultsDiv = document.getElementById('schoolResults');
                    const value = this.value.trim();
                    // 只有在下拉選單未顯示時才檢查錯誤
                    if (value && !/^.+ \(.+\)$/.test(value) && 
                        (!resultsDiv || !resultsDiv.classList.contains('show'))) {
                        validateSchoolInput();
                    }
                });

                // 清除按鈕事件
                if (clearSchoolBtn) {
                    clearSchoolBtn.addEventListener('click', clearSchoolSearch);
                }

                // 鍵盤事件
                schoolSearchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        clearSchoolSearch();
                    }
                });
            }

            // 點擊其他地方隱藏搜尋結果
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.modern-search-container')) {
                    const resultsDiv = document.getElementById('schoolResults');
                    if (resultsDiv) {
                        resultsDiv.classList.remove('show');
                    }
                }
            });
            
            // 檢查是否有成功訊息需要顯示
            if (window.successMessage) {
                console.log('Found success message:', window.successMessage);
                showSuccessModal(window.successMessage);
                // 清除全域變數
                window.successMessage = null;
                
                // 如果需要自動刷新（報名成功後3秒刷新）
                if (window.autoRefresh) {
                    console.log('Auto refresh enabled, will refresh in 3 seconds');
                    setTimeout(function() {
                        console.log('Auto refreshing page...');
                        window.location.href = window.location.pathname;
                    }, 3000); // 3秒後刷新
                }
            } else {
                console.log('No success message found');
            }
        });

        // 修改報名功能
        function showModifyForm(applicationId, email, currentSessionId, currentCourse1, currentCourse2) {
            console.log('showModifyForm called with:', {applicationId, email, currentSessionId, currentCourse1, currentCourse2});
            
            try {
                // 創建修改表單的模態框
                const modal = document.createElement('div');
                modal.className = 'modal-overlay';
                modal.innerHTML = `
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3><i class="fas fa-edit"></i> 修改報名場次</h3>
                            <button type="button" class="close-btn" onclick="closeModal()">&times;</button>
                        </div>
                        <form method="POST" action="" class="modify-form">
                            <input type="hidden" name="action" value="modify">
                            <input type="hidden" name="application_id" value="${applicationId}">
                            <input type="hidden" name="email" value="${email}">
                            
                            <div class="form-group">
                                <label>選擇新場次：</label>
                                <select name="new_session_id" required>
                                    <option value="">請選擇場次</option>
                                    ${generateSessionOptions(currentSessionId)}
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>第一選擇課程：</label>
                                <select name="new_course_priority_1">
                                    <option value="">請選擇課程</option>
                                    ${generateCourseOptions(currentCourse1)}
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>第二選擇課程：</label>
                                <select name="new_course_priority_2">
                                    <option value="">請選擇課程</option>
                                    ${generateCourseOptions(currentCourse2)}
                                </select>
                            </div>
                            
                            <div class="modal-actions">
                                <button type="button" class="btn-cancel" onclick="closeModal()">取消</button>
                                <button type="submit" class="btn-submit">確認修改</button>
                            </div>
                        </form>
                    </div>
                `;
                
                document.body.appendChild(modal);
                console.log('Modal created and added to body');
                
                // 添加表單驗證：檢查第一科系和第二科系是否重複
                const modifyForm = modal.querySelector('.modify-form');
                const course1Select = modal.querySelector('select[name="new_course_priority_1"]');
                const course2Select = modal.querySelector('select[name="new_course_priority_2"]');
                
                // 創建錯誤提示元素
                let errorMessage = modal.querySelector('.course-duplicate-error');
                if (!errorMessage) {
                    errorMessage = document.createElement('div');
                    errorMessage.className = 'course-duplicate-error';
                    errorMessage.style.cssText = 'color: #d32f2f; font-size: 13px; margin-top: 8px; padding: 8px 12px; background-color: #ffebee; border-left: 3px solid #d32f2f; border-radius: 4px; display: none;';
                    errorMessage.innerHTML = '<i class="fas fa-exclamation-circle"></i> 第一科系和第二科系不能相同，請選擇不同的科系';
                    course2Select.parentElement.appendChild(errorMessage);
                }
                
                // 檢查科系是否重複的函數
                function checkCourseDuplicate() {
                    const course1 = course1Select.value;
                    const course2 = course2Select.value;
                    
                    if (course1 && course2 && course1 === course2) {
                        errorMessage.style.display = 'block';
                        course1Select.style.borderColor = '#d32f2f';
                        course1Select.style.borderWidth = '2px';
                        course2Select.style.borderColor = '#d32f2f';
                        course2Select.style.borderWidth = '2px';
                        return true; // 有重複
                    } else {
                        errorMessage.style.display = 'none';
                        course1Select.style.borderColor = '';
                        course1Select.style.borderWidth = '';
                        course2Select.style.borderColor = '';
                        course2Select.style.borderWidth = '';
                        return false; // 沒有重複
                    }
                }
                
                // 當選擇改變時檢查
                course1Select.addEventListener('change', checkCourseDuplicate);
                course2Select.addEventListener('change', checkCourseDuplicate);
                
                // 表單提交時驗證
                modifyForm.addEventListener('submit', function(e) {
                    if (checkCourseDuplicate()) {
                        e.preventDefault();
                        alert('第一科系和第二科系不能相同，請選擇不同的科系！');
                        course2Select.focus();
                        return false;
                    }
                });
                
            } catch (error) {
                console.error('Error in showModifyForm:', error);
                alert('開啟修改表單時發生錯誤，請重新整理頁面後再試。');
            }
        }

        // 生成場次選項
        function generateSessionOptions(currentSessionId) {
            try {
                const sessions = <?php echo json_encode($sessions); ?>;
                let options = '';
                
                if (sessions && Array.isArray(sessions)) {
                    sessions.forEach(session => {
                        const selected = session.id == currentSessionId ? 'selected' : '';
                        const sessionDisplay = session.session_name + (session.session_type === '線上' ? ' (線上)' : '');
                        const registeredCount = session.registered_count || 0;
                        const spotsInfo = `（${registeredCount}/${session.max_participants} 人）`;
                        const isFull = session.remaining_spots <= 0;
                        const fullText = isFull ? ' - 已滿' : '';
                        const disabled = isFull ? 'disabled' : '';
                        
                        options += `<option value="${session.id}" ${selected} ${disabled}>${sessionDisplay} ${spotsInfo}${fullText}</option>`;
                    });
                } else {
                    options = '<option value="">暫無可用場次</option>';
                }
                
                return options;
            } catch (error) {
                console.error('Error in generateSessionOptions:', error);
                return '<option value="">載入場次時發生錯誤</option>';
            }
        }

        // 生成課程選項
        function generateCourseOptions(currentCourse) {
            try {
                const departments = <?php echo json_encode($departments); ?>;
                let options = '';
                
                if (departments && Array.isArray(departments)) {
                    departments.forEach(dept => {
                        // currentCourse 可能是代碼或名稱，需要匹配
                        const courseCode = typeof dept === 'object' ? dept.code : dept;
                        const courseName = typeof dept === 'object' ? dept.name : dept;
                        const selected = (courseCode === currentCourse || courseName === currentCourse) ? 'selected' : '';
                        options += `<option value="${courseCode}" ${selected}>${courseName}</option>`;
                    });
                } else {
                    options = '<option value="">暫無可用課程</option>';
                }
                
                return options;
            } catch (error) {
                console.error('Error in generateCourseOptions:', error);
                return '<option value="">載入課程時發生錯誤</option>';
            }
        }

        // 確認取消報名
        function confirmCancel(applicationId, email, sessionName) {
            console.log('confirmCancel called with:', {applicationId, email, sessionName});
            
            if (confirm(`確定要取消「${sessionName}」的報名嗎？\n\n此操作無法復原！`)) {
                try {
                    // 創建隱藏表單提交取消請求
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';
                    
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'cancel';
                    
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'application_id';
                    idInput.value = applicationId;
                    
                    const emailInput = document.createElement('input');
                    emailInput.type = 'hidden';
                    emailInput.name = 'email';
                    emailInput.value = email;
                    
                    form.appendChild(actionInput);
                    form.appendChild(idInput);
                    form.appendChild(emailInput);
                    
                    document.body.appendChild(form);
                    console.log('Form created and submitting:', {action: 'cancel', application_id: applicationId, email: email});
                    form.submit();
                } catch (error) {
                    console.error('Error in confirmCancel:', error);
                    alert('取消報名時發生錯誤，請重新整理頁面後再試。');
                }
            }
        }

        // 關閉模態框
        function closeModal() {
            const modal = document.querySelector('.modal-overlay');
            if (modal) {
                modal.remove();
            }
        }

        // 點擊模態框外部關閉
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                closeModal();
            }
        });

        // 顯示成功小視窗
        function showSuccessModal(message) {
            console.log('showSuccessModal called with message:', message);
            
            const modal = document.createElement('div');
            modal.className = 'success-modal-overlay';
            modal.innerHTML = `
                <div class="success-modal-content">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="success-message">
                        <h3>操作成功！</h3>
                        <p>${message}</p>
                    </div>
                    <div class="success-actions">
                        <button type="button" class="btn-ok" onclick="closeSuccessModalAndRefresh()">確定</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            console.log('Success modal added to body');
        }

        // 關閉成功小視窗
        function closeSuccessModal() {
            const modal = document.querySelector('.success-modal-overlay');
            if (modal) {
                modal.remove();
            }
        }

        // 關閉成功小視窗並重整頁面
        function closeSuccessModalAndRefresh() {
            const modal = document.querySelector('.success-modal-overlay');
            if (modal) {
                modal.remove();
            }
            // 重整頁面並清空資料
            window.location.href = window.location.pathname;
        }
    </script>
    
    <script>
    // Admission email verification scripts
            (function(){
        function el(id){return document.getElementById(id);}

        async function sendVerification(email){
            try{
                const resp = await fetch('api/send_admission_verification.php', {
                    method:'POST',
                    headers:{'Content-Type':'application/x-www-form-urlencoded'},
                    body:new URLSearchParams({email: email})
                });
                return await resp.json();
            }catch(e){
                return {success:false, message:'發生錯誤，請稍後再試'};
            }
        }

        async function verifyCode(email, code){
            try{
                const resp = await fetch('api/verify_admission_code.php', {
                    method:'POST',
                    headers:{'Content-Type':'application/x-www-form-urlencoded'},
                    body:new URLSearchParams({email: email, code: code})
                });
                return await resp.json();
            }catch(e){
                return {success:false, message:'驗證失敗，請稍後再試'};
            }
        }

            document.addEventListener('DOMContentLoaded', function(){
            const searchBtn = el('searchBtn');
            const emailInput = el('searchEmail');
            const msgDiv = null; // header modal 使用自己的 message element

            function openModal(email){
                // 使用 header.php 定義的 showVerificationModal 若存在
                if (typeof showVerificationModal === 'function') {
                    try { showVerificationModal('', email); } catch (e) { console.error(e); }
                } else {
                    // fallback: 直接設定 header 顯示元素
                    const headerEmailDisplay = el('verification_email_display');
                    const headerModal = el('verificationModal');
                    if (headerEmailDisplay) headerEmailDisplay.textContent = email;
                    if (headerModal) headerModal.style.display = 'flex';
                }

                // 注入自訂 submit/resend 處理（針對 email-based flow）
                setTimeout(() => {
                    const verificationForm = document.getElementById('verificationForm');
                    const verificationCodeEl = document.getElementById('verification_code');
                    const verificationMessageEl = document.getElementById('verificationMessage');
                    const resendBtnHeader = document.getElementById('resendCodeBtn');

                    if (verificationForm && verificationCodeEl) {
                        // 移除先前可能註冊的自訂 handler
                        verificationForm.removeEventListener('submit', window.__admissionCustomVerifyHandler);
                        // 自訂 handler
                        window.__admissionCustomVerifyHandler = async function(e){
                            // 如果 header 的 verification_username 有值則交給 header 原本的流程
                            const verificationUsername = document.getElementById('verification_username');
                            if (verificationUsername && verificationUsername.value.trim() !== '') return;

                            e.preventDefault();
                            e.stopPropagation();
                            e.stopImmediatePropagation();
                            const code = (verificationCodeEl.value || '').trim();
                            if (!code || code.length !== 4) {
                                if (verificationMessageEl) { verificationMessageEl.style.color = 'red'; verificationMessageEl.innerText = '請輸入4位數驗證碼'; }
                                return;
                            }
                            if (verificationMessageEl) { verificationMessageEl.style.color = '#666'; verificationMessageEl.innerText = '驗證中...'; }
                            const emailInput = document.getElementById('verification_email_display')?.textContent || (document.getElementById('searchEmail')?.value || '');
                            const res = await verifyCode(emailInput, code);
                            if (res.success) {
                                if (verificationMessageEl) { verificationMessageEl.style.color = 'green'; verificationMessageEl.innerText = res.message || '驗證成功'; }
                                setTimeout(() => { window.location.href = window.location.pathname + '?action=search&email=' + encodeURIComponent(emailInput); }, 900);
                            } else {
                                if (verificationMessageEl) { verificationMessageEl.style.color = 'red'; verificationMessageEl.innerText = res.message || '驗證失敗'; }
                            }
                        };
                        verificationForm.addEventListener('submit', function(e){ window.__admissionCustomVerifyHandler(e); }, true);
                    }

                    if (resendBtnHeader) {
                        resendBtnHeader.removeEventListener('click', window.__admissionCustomResendHandler);
                        window.__admissionCustomResendHandler = async function(e){
                            e.preventDefault();
                            e.stopPropagation();
                            e.stopImmediatePropagation();
                            const emailInput = document.getElementById('verification_email_display')?.textContent || (document.getElementById('searchEmail')?.value || '');
                            if (!emailInput) { alert('找不到要發送的 Email'); return; }
                            if (verificationMessageEl) { verificationMessageEl.style.color = '#666'; verificationMessageEl.innerText = '正在發送驗證碼...'; }
                            const r = await sendVerification(emailInput);
                            if (verificationMessageEl) { verificationMessageEl.style.color = r.success ? 'green' : 'red'; verificationMessageEl.innerText = r.message || (r.success ? '驗證碼已發送' : '發送失敗'); }
                        };
                        resendBtnHeader.addEventListener('click', function(e){ window.__admissionCustomResendHandler(e); }, true);
                    }
                }, 200);
            }

            if(searchBtn){
                searchBtn.addEventListener('click', async function(){
                    const email = (emailInput && emailInput.value || '').trim();
                    if(!email || !/.+@.+\..+/.test(email)){
                        alert('請輸入有效的電子郵件地址');
                        return;
                    }
                    // 發送驗證碼
                    searchBtn.disabled = true;
                    const res = await sendVerification(email);
                    searchBtn.disabled = false;
                    if(res.success){
                        openModal(email);
                    } else {
                        alert(res.message || '發送驗證碼失敗');
                    }
                });
            }

            if(verifyBtn){
                verifyBtn.addEventListener('click', async function(){
                    const email = (emailInput && emailInput.value || '').trim();
                    const code = (codeInput && codeInput.value || '').trim();
                    if(!code){ msgDiv.textContent = '請輸入驗證碼'; return; }
                    verifyBtn.disabled = true;
                    const res = await verifyCode(email, code);
                    verifyBtn.disabled = false;
                    if(res.success){
                        // 驗證成功，導向查詢結果
                        window.location.href = window.location.pathname + '?action=search&email=' + encodeURIComponent(email);
                    } else {
                        msgDiv.textContent = res.message || '驗證失敗';
                    }
                });
            }

            if(resendBtn){
                resendBtn.addEventListener('click', async function(){
                    const email = (emailInput && emailInput.value || '').trim();
                    if(!email){ alert('請先輸入 email'); return; }
                    resendBtn.disabled = true;
                    const res = await sendVerification(email);
                    resendBtn.disabled = false;
                    msgDiv.textContent = res.success ? '驗證碼已重新發送' : (res.message || '重新發送失敗');
                });
            }

            if(closeBtn){ closeBtn.addEventListener('click', closeModal); }
        });
    })();

    </script>

    <script>
    // 驗證碼刷新功能
    function refreshCaptcha() {
        const captchaImage = document.getElementById('captchaImage');
        const captchaInput = document.getElementById('captchaInput');
        
        // 清空輸入框
        if (captchaInput) {
            captchaInput.value = '';
        }
        
        // 刷新驗證碼圖片（添加刷新參數強制生成新驗證碼）
        if (captchaImage) {
            captchaImage.src = 'captcha_image.php?refresh=1&t=' + new Date().getTime();
        }
    }
    </script>
</main>
<?php include("share/footer.php"); ?>

<!-- 浮動助手組件 -->
<?php include("share/chat_widget.php"); ?>
</body>
</html>
