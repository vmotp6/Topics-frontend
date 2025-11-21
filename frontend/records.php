<?php
// 載入 session 配置
require_once 'session_config.php';

// 引入配置檔案和驗證碼系統
require_once 'config.php';
require_once 'generate_captcha.php';

// 檢查登入狀態 (調試模式)
$debug_mode = true; // 設為 false 可關閉調試模式

if ($debug_mode) {
    // 調試模式：顯示詳細資訊
    // 檢查 user_id、id 或 username 其中之一存在即可
    if ((!isset($_SESSION['user_id']) && !isset($_SESSION['id']) && !isset($_SESSION['username'])) || !isset($_SESSION['role']) || $_SESSION['role'] !== '老師') {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; margin: 20px; border-radius: 5px; border: 1px solid #f5c6cb;'>";
        echo "<h3>⚠️ 登入驗證失敗</h3>";
        echo "<p><strong>原因分析：</strong></p>";
        echo "<ul>";
        
        if (!isset($_SESSION['user_id']) && !isset($_SESSION['id']) && !isset($_SESSION['username'])) {
            echo "<li>❌ 缺少識別資訊 (SESSION中沒有 user_id、id 或 username)</li>";
        } else {
            if (isset($_SESSION['user_id'])) {
                echo "<li>✅ user_id 存在: " . $_SESSION['user_id'] . "</li>";
            }
            if (isset($_SESSION['id'])) {
                echo "<li>✅ id 存在: " . $_SESSION['id'] . "</li>";
            }
            if (isset($_SESSION['username'])) {
                echo "<li>✅ username 存在: " . $_SESSION['username'] . "</li>";
            }
        }
        
        if (!isset($_SESSION['role'])) {
            echo "<li>❌ 缺少 role (role)</li>";
        } else {
            echo "<li>✅ role 存在: " . $_SESSION['role'];
            if ($_SESSION['role'] !== '老師') {
                echo " (但不是 '老師')";
            }
            echo "</li>";
        }
        
        echo "</ul>";
        echo "<p><strong>SESSION 內容：</strong></p>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>";
        print_r($_SESSION);
        echo "</pre>";
        
        echo "<div style='margin-top: 15px;'>";
        echo "<a href='debug_session.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px; margin-right: 10px;'>檢查 SESSION 狀態</a>";
        echo "<a href='login.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;'>前往登入頁面</a>";
        echo "</div>";
        echo "</div>";
        exit();
    }
} else {
    // 正常模式：直接跳轉
    if ((!isset($_SESSION['user_id']) && !isset($_SESSION['id']) && !isset($_SESSION['username'])) || !isset($_SESSION['role']) || $_SESSION['role'] !== '老師') {
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
    // 使用 user_id 查詢 (如果 SESSION 中有 user_id)
    $teacher_id = $_SESSION['user_id'];
    $teacher_sql = "SELECT * FROM teacher WHERE user_id = ?";
    $teacher_stmt = $conn->prepare($teacher_sql);
    if ($teacher_stmt) {
        $teacher_stmt->bind_param("i", $teacher_id);
    }
} elseif (isset($_SESSION['id'])) {
    // 使用 id 查詢 (如果 SESSION 中有 id)
    $teacher_id = $_SESSION['id'];
    $teacher_sql = "SELECT * FROM teacher WHERE user_id = ?";
    $teacher_stmt = $conn->prepare($teacher_sql);
    if ($teacher_stmt) {
        $teacher_stmt->bind_param("i", $teacher_id);
    }
} elseif (isset($_SESSION['username'])) {
    // 使用 username 查詢：先從 user 表找到對應的 id，再用這個 id 去 teacher 表找 user_id
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
        // 如果是用 username 查詢的，設定 teacher_id
        if (!$teacher_id && $teacher_info) {
            $teacher_id = $teacher_info['user_id'];
        }
    }
    $teacher_stmt->close();
} else {
    // SQL 準備失敗，顯示錯誤資訊
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; margin: 20px; border-radius: 5px;'>";
    echo "<h3>❌ 資料庫查詢錯誤</h3>";
    echo "<p><strong>錯誤訊息：</strong> " . $conn->error . "</p>";
    echo "<p><strong>可能原因：</strong></p>";
    echo "<ul>";
    echo "<li>teacher 表不存在</li>";
    echo "<li>欄位名稱錯誤 (username, teacher_name, user_id)</li>";
    echo "<li>資料庫連線問題</li>";
    echo "</ul>";
    echo "<p><strong>SESSION 內容：</strong></p>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    echo "</div>";
    exit();
}

// 查詢該教師的活動記錄（通過 JOIN 獲取 teacher 名稱）
$activity_records = [];
if ($teacher_id) {
    $records_sql = "SELECT ar.*, t.name AS teacher_name_display, t.department AS teacher_department_display
                    FROM activity_records ar
                    LEFT JOIN teacher t ON ar.teacher_id = t.user_id
                    WHERE ar.teacher_id = ? 
                    ORDER BY ar.activity_date DESC, ar.id DESC";
    $records_stmt = $conn->prepare($records_sql);
    if ($records_stmt) {
        $records_stmt->bind_param("i", $teacher_id);
        $records_stmt->execute();
        $records_result = $records_stmt->get_result();
        
        if ($records_result) {
            while ($row = $records_result->fetch_assoc()) {
                // teacher_name 字段存儲的是代碼，teacher_name_display 是從 teacher 表 JOIN 來的名稱
                $activity_records[] = $row;
            }
        }
        $records_stmt->close();
    }
}

// 從 participant_options 表讀取參與者選項
$participants_options = [];
$participants_options_map = []; // code => name 映射
$participants_options_query = "SELECT code, name, category, display_order FROM participant_options WHERE is_active = 1 ORDER BY display_order, id";
$participants_options_result = $conn->query($participants_options_query);
if ($participants_options_result) {
    while ($row = $participants_options_result->fetch_assoc()) {
        $participants_options[] = $row;
        $participants_options_map[$row['code']] = $row['name'];
    }
} else {
    // 如果表不存在，使用預設選項（向後兼容）
    $participants_options = [
        ['code' => 'JHS_9', 'name' => '國中九年級', 'category' => '國中', 'display_order' => 3],
        ['code' => 'JHS_8', 'name' => '國中八年級', 'category' => '國中', 'display_order' => 2],
        ['code' => 'JHS_7', 'name' => '國中七年級', 'category' => '國中', 'display_order' => 1],
        ['code' => 'SHS_3', 'name' => '高中三年級', 'category' => '高中', 'display_order' => 6],
        ['code' => 'SHS_2', 'name' => '高中二年級', 'category' => '高中', 'display_order' => 5],
        ['code' => 'SHS_1', 'name' => '高中一年級', 'category' => '高中', 'display_order' => 4],
        ['code' => 'TEACHER', 'name' => '教師(職員工)', 'category' => '其他', 'display_order' => 7],
        ['code' => 'PARENT', 'name' => '家長', 'category' => '其他', 'display_order' => 8],
        ['code' => 'OTHER', 'name' => '其他', 'category' => '其他', 'display_order' => 9]
    ];
    foreach ($participants_options as $opt) {
        $participants_options_map[$opt['code']] = $opt['name'];
    }
}

// 從 activity_type_options 表讀取活動類型選項
$activity_type_options = [];
$activity_type_options_map = []; // code => name 映射
$activity_type_options_query = "SELECT code, name, category, display_order FROM activity_type_options WHERE is_active = 1 ORDER BY display_order, id";
$activity_type_options_result = $conn->query($activity_type_options_query);
if ($activity_type_options_result) {
    while ($row = $activity_type_options_result->fetch_assoc()) {
        $activity_type_options[] = $row;
        $activity_type_options_map[$row['code']] = $row['name'];
    }
} else {
    // 如果表不存在，使用預設選項（向後兼容）
    $activity_type_options = [
        ['code' => 'TYPE_SCHOOL_VISIT', 'name' => '來校體驗', 'category' => '校內', 'display_order' => 1],
        ['code' => 'TYPE_OFF_CAMPUS', 'name' => '校外參訪', 'category' => '校外', 'display_order' => 2],
        ['code' => 'TYPE_LECTURE', 'name' => '講座分享', 'category' => '其他', 'display_order' => 3]
    ];
    foreach ($activity_type_options as $opt) {
        $activity_type_options_map[$opt['code']] = $opt['name'];
    }
}

$message = "";
$messageType = "";

// 檢查是否有成功提交的參數
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = "資料已成功提交！";
    $messageType = "success";
    // 重新生成驗證碼
    $_SESSION['captcha_code'] = generateCaptcha();
}

// 處理表單提交
if ($_POST) {
    // 驗證必填欄位
    $required_fields = ['activity_date', 'teacher_unit', 'school_name', 'activity_type', 'activity_time', 'captcha'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    // 驗證驗證碼（不區分大小寫）
    if (!isset($_SESSION['captcha_code']) || strtoupper(trim($_POST['captcha'])) !== strtoupper(trim($_SESSION['captcha_code']))) {
        $missing_fields[] = 'captcha_invalid';
    }
    
    // 驗證電話號碼格式（如果有填寫的話）
    if (!empty($_POST['contact_phone'])) {
        if (!preg_match('/^[0-9]{1,10}$/', $_POST['contact_phone'])) {
            $missing_fields[] = 'phone_invalid';
        }
    }
    
    if (empty($missing_fields)) {
        // 處理參與對象多選（使用代碼存儲）
        $participants = [];
        if (isset($_POST['participants'])) {
            $participants = $_POST['participants'];
            // 處理「其他」選項：如果有自定義文字，存儲為 "OTHER:自定義文字"
            if (isset($_POST['participants_other']) && !empty(trim($_POST['participants_other']))) {
                $other_text = trim($_POST['participants_other']);
                // 檢查是否有 OTHER 代碼被選中
                $has_other = false;
                foreach ($participants as $key => $code) {
                    if ($code === 'OTHER') {
                        $participants[$key] = 'OTHER:' . $other_text;
                        $has_other = true;
                        break;
                    }
                }
                // 如果沒有 OTHER 但填寫了其他文字，添加它
                if (!$has_other) {
                    $participants[] = 'OTHER:' . $other_text;
                }
            }
        }
        $participants_str = implode(',', $participants);
        
        // 處理活動紀錄多選
        $feedback = [];
        if (isset($_POST['activity_feedback'])) {
            $feedback = $_POST['activity_feedback'];
        }
        $feedback_str = implode(',', $feedback);
        
        // 處理檔案上傳
        $upload_dir = UPLOAD_DIR;
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $uploaded_files = [];
        if (isset($_FILES['files'])) {
            foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['files']['error'][$key] == 0 && !empty($tmp_name)) {
                    $original_name = $_FILES['files']['name'][$key];
                    $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
                    $safe_filename = time() . "_" . $key . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '', $original_name);
                    $target_file = $upload_dir . $safe_filename;
                    
                    // 檢查檔案大小 (10MB)
                    if ($_FILES['files']['size'][$key] <= 10 * 1024 * 1024) {
                        if (move_uploaded_file($tmp_name, $target_file)) {
                            $uploaded_files[] = $target_file;
                        }
                    }
                }
            }
        }
        
        // 將檔案路徑轉為 JSON 字串儲存
        $files_json = !empty($uploaded_files) ? json_encode($uploaded_files) : null;
        
        // 插入資料庫
        // activity_type 字段存儲代碼，而不是名稱
        // 顯示時通過 JOIN 相應表獲取名稱
        // 注意：已移除 teacher_name 字段，只使用 teacher_id
        $sql = "INSERT INTO activity_records (activity_date, teacher_unit, teacher_id, school_name, contact_person, contact_phone, activity_type, activity_time, participants, activity_feedback, suggestion, uploaded_files) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        // activity_type 存儲代碼，如果提交的是名稱，則轉換為代碼
        $activity_type_code = $_POST['activity_type'];
        if (isset($activity_type_options_map) && !empty($activity_type_options_map)) {
            // 檢查是否為名稱，如果是則轉換為代碼
            $code_found = false;
            foreach ($activity_type_options_map as $code => $name) {
                if ($name === $_POST['activity_type']) {
                    $activity_type_code = $code;
                    $code_found = true;
                    break;
                }
            }
            // 如果找不到對應的代碼，且不是代碼格式，保留原值（向後兼容）
            if (!$code_found && !isset($activity_type_options_map[$_POST['activity_type']])) {
                // 可能是舊數據或直接輸入的代碼，保持原樣
                $activity_type_code = $_POST['activity_type'];
            }
        }
        
        $stmt->bind_param("ssisssssssss", 
            $_POST['activity_date'],
            $_POST['teacher_unit'],
            $teacher_id,
            $_POST['school_name'],
            $_POST['contact_person'],
            $_POST['contact_phone'],
            $activity_type_code, // 存儲代碼而不是名稱
            $_POST['activity_time'],
            $participants_str,
            $feedback_str,
            $_POST['suggestion'],
            $files_json
        );
        
        if ($stmt->execute()) {
            // 提交成功後重新生成驗證碼
            $_SESSION['captcha_code'] = generateCaptcha();
            // 清空 POST 資料，避免表單資料被保留
            $_POST = array();
            // 重定向到當前頁面，帶上成功參數，並清空表單
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
            exit();
        } else {
            $message = "提交失敗：" . $stmt->error;
            $messageType = "error";
        }
        
        $stmt->close();
    } else {
        // 產生詳細的錯誤訊息
        $error_messages = [];
        
        if (in_array('captcha_invalid', $missing_fields)) {
            $error_messages[] = "驗證碼錯誤";
        }
        
        if (in_array('phone_invalid', $missing_fields)) {
            $error_messages[] = "電話號碼格式錯誤（請輸入1-10位數字）";
        }
        
        // 檢查其他必填欄位
        $required_field_names = [
            'activity_date' => '活動日期',
            'teacher_unit' => '教師單位',
            'teacher_name' => '教師姓名', 
            'school_name' => '學校名稱',
            'activity_type' => '活動性質',
            'activity_time' => '活動時間'
        ];
        
        foreach ($required_field_names as $field => $name) {
            if (in_array($field, $missing_fields)) {
                $error_messages[] = "請填寫「{$name}」";
            }
        }
        
        if (empty($error_messages)) {
            $message = "請檢查表單內容並重新提交！";
        } else {
            $message = "表單填寫有誤：" . implode('、', $error_messages);
        }
        
        $messageType = "error";
        
        // 驗證失敗後重新生成驗證碼
        if (in_array('captcha_invalid', $missing_fields)) {
            $_SESSION['captcha_code'] = generateCaptcha();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>	
    <link rel="stylesheet" href="assets/csp/records.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>活動紀錄填報表單</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
</head>
<?php include("share/header.php"); ?>
<body class="page-body">

    <div class="main-content">
        <div class="records-form-container">
        <div class="form-header">
            <h1><i class="fas fa-clipboard-list"></i> 活動紀錄填報表單</h1>
            <p>請詳細填寫活動相關資訊，標有 * 號的欄位為必填項目</p>
        </div>
        
        <div class="form-content">
            <?php if ($message): ?>
                <div class="form-message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <!-- 登入教師資訊顯示 -->
            <?php if ($teacher_info): ?>
                <div class="teacher-info-section" style="background: linear-gradient(90deg, rgba(122, 201, 199, 0.05) 0%, rgba(149, 109, 189, 0.05) 100%); padding: 20px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #956dbd;">
                    <h4 style="color: #495057; margin-bottom: 10px;">
                        <i class="fas fa-user-check"></i> 登入教師資訊
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div>
                            <strong>教師ID:</strong> <?php echo htmlspecialchars($teacher_info['user_id']); ?>
                        </div>
                                                 <div>
                             <strong>教師姓名:</strong> <?php echo htmlspecialchars($teacher_info['name'] ?? '未設定'); ?>
                         </div>
                         <div>
                             <strong>教師單位:</strong> <?php echo htmlspecialchars($teacher_info['department'] ?? '未設定'); ?>
                         </div>
                        <div>
                            <strong>電子信箱:</strong> <?php echo htmlspecialchars($teacher_info['email'] ?? '未設定'); ?>
                        </div>
                    </div>
                    <small style="color: #6c757d; margin-top: 10px; display: block;">
                        <i class="fas fa-info-circle"></i> 系統將自動使用您的登入資訊填入表單
                    </small>
                </div>
            <?php endif; ?>

            <!-- 查看記錄按鈕區塊 -->
            <div class="view-records-section" style="background: linear-gradient(90deg, rgba(122, 201, 199, 0.05) 0%, rgba(149, 109, 189, 0.05) 100%); padding: 20px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #956dbd; text-align: center;">
                <h4 style="color: #856404; margin-bottom: 15px;">
                    <i class="fas fa-database"></i> 活動記錄管理
                </h4>

                <button type="button" id="toggleRecordsBtn" class="toggle-records-btn" onclick="window.location.href='activity_records_management.php'" 
                        style="background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%); color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-size: 1.1em; font-weight: bold; box-shadow: 0 3px 6px rgba(0,0,0,0.2); transition: all 0.3s ease;">
                    <i class="fas fa-cogs" id="recordsIcon"></i> 
                    <span id="recordsText">進入活動記錄管理</span>
                    <?php if (!empty($activity_records)): ?>
                        <span style="background: #e74c3c; color: white; padding: 4px 8px; border-radius: 50%; font-size: 0.8em; margin-left: 8px;">
                            <?php echo count($activity_records); ?>
                        </span>
                    <?php endif; ?>
                </button>
                <div style="margin-top: 10px;">
                    <small style="color: #856404;">
                        <i class="fas fa-info-circle"></i> 
                        <?php if (!empty($activity_records)): ?>
                            共有 <?php echo count($activity_records); ?> 筆記錄
                        <?php else: ?>
                            目前尚無活動記錄
                        <?php endif; ?>
                    </small>
                </div>
            </div>


<form action="" method="post" enctype="multipart/form-data">
                <div class="form-grid">
                    <!-- 基本資訊 -->
                    <div class="form-section">
                        <h3><i class="fas fa-info-circle"></i> 基本資訊</h3>
                        <div class="form-row">
                            <div class="field-group">
                                <label><span class="required">*</span> 活動日期:</label>
                                <input type="date" name="activity_date" value="<?php echo isset($_POST['activity_date']) ? htmlspecialchars($_POST['activity_date']) : ''; ?>" required>
                            </div>
                            <div class="field-group readonly-field">
                                <label><span class="required">*</span> 教師單位: <small style="color: #6c757d; font-style: italic;">(系統自動填入)</small></label>
                                                                 <input type="text" name="teacher_unit" placeholder="請輸入教師單位" 
                                        value="<?php 
                                        echo isset($_POST['teacher_unit']) ? htmlspecialchars($_POST['teacher_unit']) : 
                                             (isset($teacher_info['department']) ? htmlspecialchars($teacher_info['department']) : ''); 
                                        ?>" readonly>
                            </div>
                            <div class="field-group readonly-field">
                                <label><span class="required"></span> 教師姓名: <small style="color: #6c757d; font-style: italic;">(系統自動填入，僅供顯示)</small></label>
                                <input type="text" name="teacher_name_display" placeholder="系統自動填入" 
                                       value="<?php 
                                       echo isset($teacher_info['name']) ? htmlspecialchars($teacher_info['name']) : ''; 
                                       ?>" readonly disabled>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="field-group">
                                <label><span class="required">*</span> 國(高)中學校名稱:</label>
                                <input type="text" name="school_name" placeholder="請輸入學校全名" value="<?php echo isset($_POST['school_name']) ? htmlspecialchars($_POST['school_name']) : ''; ?>" required>
                            </div>
                            <div class="field-group">
                                <label>聯絡窗口:</label>
                                <input type="text" name="contact_person" placeholder="請輸入聯絡人姓名" value="<?php echo isset($_POST['contact_person']) ? htmlspecialchars($_POST['contact_person']) : ''; ?>">
                            </div>
                            <div class="field-group">
                                <label>聯絡電話:</label>
                                <input type="text" name="contact_phone" placeholder="請輸入聯絡電話 (最多10位數字)" value="<?php echo isset($_POST['contact_phone']) ? htmlspecialchars($_POST['contact_phone']) : ''; ?>" maxlength="10" pattern="[0-9]{1,10}" title="請輸入1-10位數字">
                            </div>
                        </div>
                    </div>

                    <!-- 活動設定 -->
                    <div class="form-section">
                        <h3><i class="fas fa-calendar-alt"></i> 活動設定</h3>
                        <div class="form-row">
                            <div class="field-group">
                                <label><span class="required">*</span> 活動性質:</label>
                                <select name="activity_type" required>
                                    <option value="">請選擇活動性質</option>
                                    <?php 
                                    // 從 activity_type_options 表讀取選項
                                    $selected_activity_type = isset($_POST['activity_type']) ? $_POST['activity_type'] : '';
                                    foreach ($activity_type_options as $option): 
                                        // 檢查是否選中（支持代碼或名稱）
                                        $is_selected = ($selected_activity_type === $option['code'] || $selected_activity_type === $option['name']);
                                    ?>
                                    <option value="<?php echo htmlspecialchars($option['code']); ?>" <?php echo $is_selected ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($option['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field-group">
                                <label><span class="required">*</span> 活動時間:</label>
                                <select name="activity_time" required>
                                    <option value="">請選擇活動時間</option>
                                    <option value="上班日" <?php echo (isset($_POST['activity_time']) && $_POST['activity_time'] === '上班日') ? 'selected' : ''; ?>>上班日</option>
                                    <option value="假日" <?php echo (isset($_POST['activity_time']) && $_POST['activity_time'] === '假日') ? 'selected' : ''; ?>>假日</option>
                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 參與對象 -->
                    <div class="form-section">
                        <h3><i class="fas fa-users"></i> 參與對象 <span class="required">*</span></h3>
                        <div class="checkbox-grid">
                            <?php 
                            $selected_participants = isset($_POST['participants']) ? $_POST['participants'] : [];
                            $participants_other_text = isset($_POST['participants_other']) ? htmlspecialchars($_POST['participants_other']) : '';
                            
                            // 檢查是否有「其他」選項被選中，並提取自定義文字
                            $is_participants_other_checked = false;
                            $participants_other_value = '';
                            foreach ($selected_participants as $selected) {
                                if ($selected === 'OTHER' || strpos($selected, 'OTHER:') === 0) {
                                    $is_participants_other_checked = true;
                                    if (strpos($selected, 'OTHER:') === 0) {
                                        $participants_other_text = htmlspecialchars(substr($selected, 6)); // 移除「OTHER:」前綴
                                        $participants_other_value = $selected; // 保留完整值用於 checkbox
                                    } else {
                                        $participants_other_value = 'OTHER';
                                    }
                                    break;
                                }
                            }
                            ?>
                            <?php foreach ($participants_options as $option): ?>
                            <label class="checkbox-item" style="display: flex; align-items: center; gap: 8px;">
                                <?php 
                                $is_checked = false;
                                $checkbox_value = $option['code'];
                                if ($option['code'] === 'OTHER') {
                                    $is_checked = $is_participants_other_checked;
                                    $checkbox_value = $is_participants_other_checked && !empty($participants_other_value) ? $participants_other_value : 'OTHER';
                                } else {
                                    $is_checked = in_array($option['code'], $selected_participants);
                                }
                                ?>
                                <input type="checkbox" name="participants[]" value="<?php echo htmlspecialchars($checkbox_value); ?>" 
                                       <?php echo $is_checked ? 'checked' : ''; ?>
                                       <?php if ($option['code'] === 'OTHER'): ?>
                                       onchange="toggleOtherInput(this, 'participants_other')"
                                       <?php endif; ?>>
                                <span><?php echo htmlspecialchars($option['name']); ?></span>
                                <?php if ($option['code'] === 'OTHER'): ?>
                                <input type="text" name="participants_other" id="participants_other" 
                                       placeholder="請輸入其他參與對象" 
                                       value="<?php echo $participants_other_text; ?>"
                                       style="flex: 1; padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; margin-left: 10px; display: <?php echo $is_participants_other_checked ? 'block' : 'none'; ?>;"
                                       onchange="updateOtherCheckboxValue('participants', this)">
                                <?php endif; ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 活動紀錄 -->
                    <div class="form-section">
                        <h3><i class="fas fa-chart-line"></i> 活動紀錄 <span class="required">*</span></h3>
                        <div class="checkbox-grid">
                            <?php 
                            $feedback_options = ['反應熱絡、詢問度高', '反應冷淡', '願意參與小活動', '願意加入LINE', '願意追蹤FB、IG', '其他'];
                            $selected_feedback = isset($_POST['activity_feedback']) ? $_POST['activity_feedback'] : [];
                            $feedback_other_text = isset($_POST['activity_feedback_other']) ? htmlspecialchars($_POST['activity_feedback_other']) : '';
                            
                            // 檢查是否有「其他」選項被選中，並提取自定義文字
                            $is_feedback_other_checked = false;
                            $feedback_other_value = '';
                            foreach ($selected_feedback as $selected) {
                                if ($selected === '其他' || strpos($selected, '其他: ') === 0) {
                                    $is_feedback_other_checked = true;
                                    if (strpos($selected, '其他: ') === 0) {
                                        $feedback_other_text = htmlspecialchars(substr($selected, 4)); // 移除「其他: 」前綴
                                        $feedback_other_value = $selected; // 保留完整值用於 checkbox
                                    } else {
                                        $feedback_other_value = '其他';
                                    }
                                    break;
                                }
                            }
                            ?>
                            <?php foreach ($feedback_options as $option): ?>
                            <label class="checkbox-item" style="display: flex; align-items: center; gap: 8px;">
                                <?php 
                                $is_checked = false;
                                $checkbox_value = $option;
                                if ($option === '其他') {
                                    $is_checked = $is_feedback_other_checked;
                                    $checkbox_value = $is_feedback_other_checked && !empty($feedback_other_value) ? $feedback_other_value : '其他';
                                } else {
                                    $is_checked = in_array($option, $selected_feedback);
                                }
                                ?>
                                <input type="checkbox" name="activity_feedback[]" value="<?php echo htmlspecialchars($checkbox_value); ?>" 
                                       <?php echo $is_checked ? 'checked' : ''; ?>
                                       <?php if ($option === '其他'): ?>
                                       onchange="toggleOtherInput(this, 'activity_feedback_other')"
                                       <?php endif; ?>>
                                <span><?php echo htmlspecialchars($option); ?></span>
                                <?php if ($option === '其他'): ?>
                                <input type="text" name="activity_feedback_other" id="activity_feedback_other" 
                                       placeholder="請輸入其他活動紀錄" 
                                       value="<?php echo $feedback_other_text; ?>"
                                       style="flex: 1; padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; margin-left: 10px; display: <?php echo $is_feedback_other_checked ? 'block' : 'none'; ?>;"
                                       onchange="updateOtherCheckboxValue('activity_feedback', this)">
                                <?php endif; ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 檢討與建議 -->
                    <div class="form-section">
                        <h3><i class="fas fa-comments"></i> 檢討與建議</h3>
                        <div class="field-group">
                            <label>請詳述活動檢討與建議:</label>
                            <textarea name="suggestion" placeholder="請輸入活動檢討與建議內容..."><?php echo isset($_POST['suggestion']) ? htmlspecialchars($_POST['suggestion']) : ''; ?></textarea>
                        </div>
                    </div>

                    <!-- 佐證資料 -->
                    <div class="form-section">
                        <h3><i class="fas fa-file-upload"></i> 佐證資料</h3>
                        <div class="file-upload-area">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <h4>上傳佐證檔案</h4>
                            <p>支援圖片檔案或壓縮檔，單檔最大 10MB</p>
                            <div class="file-inputs" id="file-inputs-container">
                                <div class="file-input-group">
                                    <input type="file" name="files[]" accept="image/*,.zip,.rar,.pdf">
                                    <button type="button" class="remove-file-btn" onclick="removeFileInput(this)" style="display: none;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="file-input-group">
                                    <input type="file" name="files[]" accept="image/*,.zip,.rar,.pdf">
                                    <button type="button" class="remove-file-btn" onclick="removeFileInput(this)" style="display: none;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="file-input-group">
                                    <input type="file" name="files[]" accept="image/*,.zip,.rar,.pdf">
                                    <button type="button" class="remove-file-btn" onclick="removeFileInput(this)" style="display: none;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="file-upload-controls">
                                <button type="button" class="add-file-btn" onclick="addFileInput()">
                                    <i class="fas fa-plus"></i> 新增更多檔案
                                </button>
                                <small class="file-info">
                                    <i class="fas fa-info-circle"></i> 
                                    可上傳多個檔案，支援 JPG、PNG、PDF、ZIP 格式
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- 驗證碼 -->
                    <div class="form-section">
                        <h3><i class="fas fa-shield-alt"></i> 驗證碼 <span class="required">*</span></h3>
                        <div class="captcha-section" style="display: flex; align-items: center; gap: 10px; margin: 15px 0; flex-wrap: wrap;">
                            <input type="text" name="captcha" id="captcha-input" class="captcha-input" placeholder="請輸入驗證碼" maxlength="6" required autocomplete="off" style="flex: 1; min-width: 150px; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; pointer-events: auto !important; cursor: text !important; user-select: text !important; -webkit-user-select: text !important; -moz-user-select: text !important; -ms-user-select: text !important; position: relative !important; z-index: 1000 !important; background-color: white !important; color: #333 !important; opacity: 1 !important; visibility: visible !important; text-transform: uppercase;">
                            <img src="captcha_image.php" id="captcha-display" alt="驗證碼" onclick="refreshCaptcha()" style="height: 50px; width: 150px; border: 2px solid #ddd; border-radius: 5px; cursor: pointer;" title="點擊刷新驗證碼">
                            <button type="button" class="refresh-btn" onclick="refreshCaptcha()" title="重新產生驗證碼" style="padding: 10px 15px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer;">
                                <i class="fas fa-sync-alt"></i> 重整
                            </button>
                        </div>
                        <small style="color: #666; margin-top: 8px; display: block;">
                            <i class="fas fa-info-circle"></i> 請輸入圖片中顯示的字母和數字（不區分大小寫，自動轉為大寫），點擊圖片或「重整」按鈕可產生新的驗證碼
                        </small>
                    </div>
                </div>

                <!-- 注意事項 -->
                <div class="form-notes">
                    <h4><i class="fas fa-exclamation-triangle"></i> 注意事項</h4>
                    <ul>
                        <li>附件可傳單張相片檔，亦可上傳壓縮檔</li>
                        <li>請老師確實填寫紀錄，務必上傳佐證附件，敬請配合。謝謝</li>
                        <li>招生宣導實為辛苦，感謝大家為了廣學而努力！招生中心致上十二萬分之謝意！！</li>
                    </ul>
                </div>

                <!-- 提交按鈕 -->
                <div class="submit-section">
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> 確定送出
                    </button>
    </div>
</form>
        </div>
    </div>
    </div>

    <style>
        /* RIC 功能樣式 */
        .ric-status-bar {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            color: white;
            padding: 12px 20px;
            border-radius: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
        }
        .ric-status-bar.show {
            opacity: 1;
            transform: translateY(0);
        }
        .ric-status-bar.saving {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
        }
        .ric-status-bar.saved {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
        }
        .form-progress {
            position: sticky;
            top: 100px;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            z-index: 100;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 10px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
            border-radius: 4px;
        }
        .field-validation {
            margin-top: 5px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .field-validation.valid {
            color: #28a745;
        }
        .field-validation.invalid {
            color: #dc3545;
        }
        .field-validation i {
            font-size: 14px;
        }
        .file-preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .file-preview-item {
            position: relative;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            background: #f8f9fa;
        }
        .file-preview-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .file-preview-item .file-info {
            padding: 8px;
            font-size: 12px;
            color: #666;
            word-break: break-all;
        }
        .file-preview-item .remove-preview {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        .char-counter {
            text-align: right;
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        .draft-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .draft-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .draft-btn.load {
            background: #17a2b8;
            color: white;
        }
        .draft-btn.clear {
            background: #6c757d;
            color: white;
        }
        .draft-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
    </style>

    <script>
        // 禁用瀏覽器的自動滾動恢復功能，確保頁面總是從頂部開始
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }
        
        // 立即滾動到頂部的函數（通用函數，用於所有情況）
        const forceScrollToTop = function() {
            window.scrollTo(0, 0);
            if (document.documentElement) {
                document.documentElement.scrollTop = 0;
            }
            if (document.body) {
                document.body.scrollTop = 0;
            }
        };
        
        // 立即執行滾動到頂部（確保頁面首次加載時從頂部開始）
        forceScrollToTop();
        
        // 監聽滾動事件，防止頁面自動滾動到底部
        let scrollProtectionActive = true;
        let lastScrollTop = 0;
        const scrollProtection = function() {
            if (!scrollProtectionActive) return;
            
            const currentScrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop;
            
            // 如果頁面滾動到底部附近（可能是自動聚焦導致的），強制滾動回頂部
            const scrollHeight = document.documentElement.scrollHeight || document.body.scrollHeight;
            const clientHeight = document.documentElement.clientHeight || window.innerHeight;
            const isNearBottom = (scrollHeight - currentScrollTop - clientHeight) < 100;
            
            // 如果檢測到頁面滾動到底部，且不是用戶主動滾動（滾動距離突然變大），則強制回到頂部
            if (isNearBottom && currentScrollTop > 500 && (currentScrollTop - lastScrollTop) > 200) {
                console.log('檢測到自動滾動到底部，強制回到頂部');
                forceScrollToTop();
            }
            
            lastScrollTop = currentScrollTop;
        };
        
        // 在頁面加載的前3秒內，持續監聽並防止自動滾動
        window.addEventListener('scroll', scrollProtection, { passive: true });
        setTimeout(() => {
            scrollProtectionActive = false;
            window.removeEventListener('scroll', scrollProtection);
        }, 3000);
        
        // 立即檢查並滾動到頂部（在頁面加載時立即執行，不等待 DOMContentLoaded）
        (function() {
            const urlParams = new URLSearchParams(window.location.search);
            const isSuccessPage = urlParams.get('success') === '1';
            
            // 無論是否有 success 參數，都確保滾動到頂部
            forceScrollToTop();
            
            if (isSuccessPage) {
                // 持續檢查並滾動，直到頁面完全加載
                const scrollToTop = function() {
                    forceScrollToTop();
                    
                    // 移除 scrollIntoView，只使用 scrollTo(0, 0) 確保頁面在頂部
                };
                
                // 在頁面加載的各個階段都執行滾動
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', scrollToTop);
                }
                window.addEventListener('load', scrollToTop);
                
                // 使用多個延遲確保滾動執行
                setTimeout(scrollToTop, 0);
                setTimeout(scrollToTop, 10);
                setTimeout(scrollToTop, 50);
                setTimeout(scrollToTop, 100);
                setTimeout(scrollToTop, 200);
                setTimeout(scrollToTop, 500);
                setTimeout(scrollToTop, 1000);
            } else {
                // 即使沒有 success 參數，也確保頁面從頂部開始
                // 在頁面加載的各個階段都執行滾動
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', forceScrollToTop);
                }
                window.addEventListener('load', forceScrollToTop);
                
                // 使用多個延遲確保滾動執行
                setTimeout(forceScrollToTop, 0);
                setTimeout(forceScrollToTop, 10);
                setTimeout(forceScrollToTop, 50);
                setTimeout(forceScrollToTop, 100);
                setTimeout(forceScrollToTop, 200);
                setTimeout(forceScrollToTop, 500);
            }
        })();
        
        // 控制「其他」選項的輸入框顯示/隱藏
        function toggleOtherInput(checkbox, inputId) {
            const input = document.getElementById(inputId);
            if (input) {
                if (checkbox.checked) {
                    // 使用 flex 顯示，因為父元素是 flex 容器
                    input.style.display = 'block';
                    input.focus();
                } else {
                    input.style.display = 'none';
                    input.value = ''; // 取消勾選時清空輸入框
                    // 恢復 checkbox 值為「其他」
                    if (checkbox.value.startsWith('其他: ')) {
                        checkbox.value = '其他';
                    }
                }
            }
        }
        
        // 更新「其他」選項的 checkbox 值，將自定義文字附加到值中
        function updateOtherCheckboxValue(type, inputElement) {
            // 找到對應的 checkbox（在同一個 label 中）
            const label = inputElement.closest('label');
            if (label) {
                const checkbox = label.querySelector('input[type="checkbox"]');
                if (checkbox && (checkbox.value === '其他' || checkbox.value.startsWith('其他: '))) {
                    const customText = inputElement.value.trim();
                    if (customText) {
                        // 將自定義文字附加到 checkbox 值中
                        checkbox.value = '其他: ' + customText;
                    } else {
                        // 如果沒有輸入文字，恢復為「其他」
                        checkbox.value = '其他';
                    }
                }
            }
        }
        
        // ==================== RIC 核心功能 ====================
        
        // 表單資料管理
        const FORM_STORAGE_KEY = 'activity_record_draft';
        let autoSaveTimer = null;
        let isSubmitting = false;
        
        // 初始化 RIC 功能
        document.addEventListener('DOMContentLoaded', function() {
            console.log('RIC 功能開始初始化...');
            
            // 檢查是否有成功提交的參數
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success') === '1') {
                // 滾動到頂部的函數（只滾動到頂部，不使用 scrollIntoView 避免干擾）
                const forceScrollToTop = function() {
                    window.scrollTo(0, 0);
                    if (document.documentElement) {
                        document.documentElement.scrollTop = 0;
                    }
                    if (document.body) {
                        document.body.scrollTop = 0;
                    }
                    // 移除 scrollIntoView，避免任何自動滾動干擾
                };
                
                // 立即執行滾動
                forceScrollToTop();
                
                // 提交成功，重置表單並清除草稿
                const form = document.querySelector('form');
                if (form) {
                    form.reset();
                    console.log('✅ 表單已重置');
                }
                
                // 清除草稿
                localStorage.removeItem(FORM_STORAGE_KEY);
                console.log('✅ 草稿已清除');
                
                // 刷新驗證碼圖片（因為服務器已生成新的驗證碼）
                // 使用直接刷新圖片的方式，避免調用 refreshCaptcha 可能導致的聚焦
                setTimeout(() => {
                    const captchaImg = document.getElementById('captcha-display');
                    const captchaInput = document.querySelector('input[name="captcha"]');
                    if (captchaImg) {
                        captchaImg.src = 'captcha_image.php?t=' + new Date().getTime();
                    }
                    if (captchaInput) {
                        captchaInput.value = '';
                        // 確保輸入框可用，但不聚焦
                        captchaInput.disabled = false;
                        captchaInput.readOnly = false;
                        captchaInput.removeAttribute('disabled');
                        captchaInput.removeAttribute('readonly');
                    }
                    // 刷新後再次確保頁面在頂部
                    forceScrollToTop();
                }, 100);
                
                // 確保頁面完全加載後再次滾動到頂部（多重保障，增加更多時間點）
                setTimeout(forceScrollToTop, 10);
                setTimeout(forceScrollToTop, 50);
                setTimeout(forceScrollToTop, 100);
                setTimeout(forceScrollToTop, 200);
                setTimeout(forceScrollToTop, 300);
                setTimeout(forceScrollToTop, 500);
                setTimeout(forceScrollToTop, 800);
                setTimeout(forceScrollToTop, 1000);
                setTimeout(forceScrollToTop, 1500);
                setTimeout(forceScrollToTop, 2000);
                
                // 監聽窗口大小變化，確保滾動位置正確
                const resizeHandler = function() {
                    forceScrollToTop();
                };
                window.addEventListener('resize', resizeHandler);
                setTimeout(() => {
                    window.removeEventListener('resize', resizeHandler);
                }, 3000);
                
                // 持續監聽滾動事件，防止任何自動滾動到底部
                let scrollProtectionCount = 0;
                const scrollProtectionHandler = function() {
                    const currentScrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop;
                    if (currentScrollTop > 100) {
                        scrollProtectionCount++;
                        if (scrollProtectionCount > 2) {
                            console.log('檢測到意外滾動，強制回到頂部');
                            forceScrollToTop();
                        }
                    } else {
                        scrollProtectionCount = 0;
                    }
                };
                window.addEventListener('scroll', scrollProtectionHandler, { passive: true });
                setTimeout(() => {
                    window.removeEventListener('scroll', scrollProtectionHandler);
                }, 3000);
                
                // 移除URL中的success參數，避免刷新時重複觸發
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }
            
            initRIC();
            initAutoSave();
            initRealTimeValidation();
            initFilePreview();
            initProgressTracker();
            initCharCounter();
            
            // 自動載入草稿（靜默模式）
            // 延遲載入，確保頁面完全渲染
            // 只有在沒有成功提交時才載入草稿
            if (urlParams.get('success') !== '1') {
                setTimeout(() => {
                console.log('🔍 開始檢查草稿...');
                
                // 檢查是否有草稿
                const hasDraft = localStorage.getItem(FORM_STORAGE_KEY);
                if (!hasDraft) {
                    console.log('❌ 沒有找到儲存的草稿');
                    return;
                }
                
                console.log('✅ 找到草稿，準備載入...');
                console.log('草稿內容:', JSON.parse(hasDraft));
                
                // 檢查是否有伺服器端資料（PHP 回傳的資料）
                // 如果表單已經有資料，可能是 PHP 回傳的，不應該覆蓋
                const form = document.querySelector('form');
                if (!form) {
                    console.warn('⚠️ 找不到表單元素');
                    return;
                }
                
                const activityDate = form.querySelector('input[name="activity_date"]');
                const schoolName = form.querySelector('input[name="school_name"]');
                const suggestion = form.querySelector('textarea[name="suggestion"]');
                
                // 檢查是否有明顯的伺服器端資料（排除預設值和 readonly 欄位）
                // 注意：teacher_unit 和 teacher_name 是 readonly，有值不代表是伺服器端資料
                const hasServerData = (activityDate && activityDate.value && activityDate.value !== '') ||
                                     (schoolName && schoolName.value && schoolName.value.trim() !== '') ||
                                     (suggestion && suggestion.value && suggestion.value.trim() !== '');
                
                console.log('📊 伺服器端資料檢查:', {
                    activityDate: activityDate?.value,
                    schoolName: schoolName?.value,
                    suggestion: suggestion?.value,
                    hasServerData: hasServerData
                });
                
                // 只有在沒有伺服器端資料時才載入草稿
                if (!hasServerData) {
                    console.log('🚀 沒有伺服器端資料，開始載入草稿...');
                    const loaded = loadDraft(false); // false = 靜默模式，不顯示提示
                    if (loaded) {
                        console.log('✅ 草稿載入成功');
                    } else {
                        console.warn('⚠️ 草稿載入失敗或沒有載入任何欄位');
                    }
                } else {
                    console.log('⏭️ 檢測到伺服器端資料，跳過草稿載入（避免覆蓋伺服器資料）');
                }
                }, 1500); // 增加延遲到 1.5 秒，確保頁面完全載入
            } // 結束 if (urlParams.get('success') !== '1')
            
            console.log('RIC 功能初始化完成');
        });
        
        // 初始化 RIC 系統
        function initRIC() {
            console.log('初始化 RIC 系統...');
            
            // 創建狀態欄
            const statusBar = document.createElement('div');
            statusBar.id = 'ric-status-bar';
            statusBar.className = 'ric-status-bar';
            statusBar.innerHTML = '<i class="fas fa-save"></i> <span>就緒</span>';
            document.body.appendChild(statusBar);
            console.log('狀態欄已創建');
            
            // 創建進度條
            const form = document.querySelector('form');
            if (form) {
                console.log('找到表單，創建進度條...');
                const progressHTML = `
                    <div class="form-progress">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <strong><i class="fas fa-tasks"></i> 表單填寫進度</strong>
                            <span id="progress-percentage">0%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
                        </div>
                        <div class="draft-actions" style="margin-top: 15px;">
                            <button type="button" class="draft-btn load" onclick="loadDraft(true)">
                                <i class="fas fa-download"></i> 載入草稿
                            </button>
                            <button type="button" class="draft-btn clear" onclick="clearDraft()">
                                <i class="fas fa-trash"></i> 清除草稿
                            </button>
                        </div>
                    </div>
                `;
                form.insertAdjacentHTML('afterbegin', progressHTML);
                console.log('進度條已創建');
            } else {
                console.error('找不到表單元素！');
            }
        }
        
        // 顯示狀態訊息
        function showStatus(message, type = 'info') {
            const statusBar = document.getElementById('ric-status-bar');
            if (!statusBar) return;
            
            const icons = {
                saving: '<i class="fas fa-spinner fa-spin"></i>',
                saved: '<i class="fas fa-check-circle"></i>',
                info: '<i class="fas fa-info-circle"></i>'
            };
            
            statusBar.className = `ric-status-bar show ${type}`;
            statusBar.innerHTML = `${icons[type] || icons.info} <span>${message}</span>`;
            
            if (type === 'saved') {
                setTimeout(() => {
                    statusBar.classList.remove('show');
                }, 2000);
            }
        }
        
        // ==================== 自動儲存功能 ====================
        
        // 防抖函數（全局）
        let saveTimeout = null;
        const debouncedSave = () => {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                console.log('⏰ 觸發自動儲存（防抖延遲結束）...');
                saveDraft();
            }, 1000); // 1秒後儲存
        };
        
        function initAutoSave() {
            console.log('初始化自動儲存功能...');
            
            // 找到正確的表單（使用更具體的選擇器）
            // 先嘗試找到包含 activity_date 欄位的表單
            let form = null;
            const activityDateInput = document.querySelector('input[name="activity_date"]');
            if (activityDateInput) {
                form = activityDateInput.closest('form');
                console.log('通過 activity_date 找到表單:', form);
            }
            
            // 如果沒找到，使用通用選擇器
            if (!form) {
                form = document.querySelector('form[action=""]');
                console.log('通過 action="" 找到表單:', form);
            }
            
            // 如果還是沒找到，使用第一個表單
            if (!form) {
                form = document.querySelector('form');
                console.log('使用第一個表單:', form);
            }
            
            if (!form) {
                console.error('找不到表單，無法初始化自動儲存');
                return;
            }
            
            console.log('✅ 表單找到，開始設置事件監聽器...');
            console.log('表單資訊:', {
                action: form.action,
                method: form.method,
                id: form.id,
                className: form.className
            });
            
            // 使用事件委派（Event Delegation）來監聽表單內的所有變化
            // 使用 document 來監聽，確保能捕獲到所有事件
            const handleFormChange = function(e) {
                const target = e.target;
                
                // 找到目標元素所在的表單
                const targetForm = target.closest('form');
                
                // 記錄所有事件（用於調試）
                console.log('🔔 事件觸發:', {
                    type: e.type,
                    tagName: target.tagName,
                    typeAttr: target.type,
                    name: target.name,
                    value: target.value,
                    targetForm: targetForm,
                    ourForm: form,
                    isSameForm: targetForm === form,
                    formContains: form.contains(target)
                });
                
                // 確保目標元素在我們要監聽的表單內
                // 使用 closest 方法更可靠
                if (targetForm !== form) {
                    // 如果目標元素沒有表單，可能是動態添加的，嘗試檢查是否在我們的表單內
                    if (!targetForm && form.contains(target)) {
                        console.log('  ℹ️ 元素沒有表單標籤，但在目標表單內，繼續處理');
                    } else {
                        console.log('  ⏭️ 跳過：元素不在目標表單內');
                        console.log('  - 目標元素所在表單:', targetForm);
                        console.log('  - 我們監聽的表單:', form);
                        return;
                    }
                }
                
                // 所有欄位都記錄，包括檔案和驗證碼
                
                // 處理檔案上傳（記錄檔案名稱）
                if (target.type === 'file') {
                    console.log('📁 檔案選擇改變！');
                    if (target.files && target.files.length > 0) {
                        Array.from(target.files).forEach(file => {
                            console.log('  - 檔案名稱: ' + file.name + ' (大小: ' + (file.size / 1024).toFixed(2) + ' KB)');
                        });
                    }
                    debouncedSave();
                    return;
                }
                
                // 處理 checkbox
                if (target.type === 'checkbox') {
                    console.log('☑️ Checkbox 被點擊！');
                    console.log('  - 名稱: ' + target.name);
                    console.log('  - 值: ' + target.value);
                    console.log('  - 狀態: ' + target.checked);
                    debouncedSave();
                    return;
                }
                
                // 處理文字輸入框、選擇框、文字區域（包括驗證碼）
                if (target.tagName === 'INPUT' || target.tagName === 'SELECT' || target.tagName === 'TEXTAREA') {
                    console.log('📝 欄位 ' + target.name + ' 發生變化，值: ' + target.value);
                    debouncedSave();
                    return;
                }
                
                console.log('  ⏭️ 跳過：不是表單元素類型');
            };
            
            // 監聽 input 事件（文字輸入）
            document.addEventListener('input', handleFormChange, true);
            console.log('✅ input 事件監聽器已設置（document 級別）');
            
            // 監聽 change 事件（選擇框、checkbox、日期等）
            document.addEventListener('change', handleFormChange, true);
            console.log('✅ change 事件監聽器已設置（document 級別）');
            
            // 也直接在表單上監聽（雙重保險）
            form.addEventListener('input', handleFormChange, true);
            form.addEventListener('change', handleFormChange, true);
            console.log('✅ 表單事件監聽器已設置（form 級別）');
            
            // 測試：手動觸發一個測試事件來確認監聽器工作
            setTimeout(() => {
                const testInput = form.querySelector('input[name="school_name"]');
                if (testInput) {
                    console.log('🧪 測試：找到 school_name 欄位，準備測試事件...');
                    // 創建並觸發一個測試事件
                    const testEvent = new Event('input', { bubbles: true, cancelable: true });
                    testInput.dispatchEvent(testEvent);
                    console.log('🧪 測試事件已觸發，請查看上方是否有事件日誌');
                } else {
                    console.warn('⚠️ 測試：找不到 school_name 欄位');
                }
            }, 1000);
            
            // 延遲檢查元素數量（用於調試）
            setTimeout(() => {
                const allInputs = form.querySelectorAll('input, select, textarea');
                const participantsCheckboxes = form.querySelectorAll('input[name="participants[]"]');
                const feedbackCheckboxes = form.querySelectorAll('input[name="activity_feedback[]"]');
                const allCheckboxes = form.querySelectorAll('input[type="checkbox"]');
                
                console.log('📊 表單元素統計:');
                console.log('  - 總表單欄位: ' + allInputs.length);
                console.log('  - 參與對象 checkbox: ' + participantsCheckboxes.length);
                console.log('  - 活動紀錄 checkbox: ' + feedbackCheckboxes.length);
                console.log('  - 總 checkbox: ' + allCheckboxes.length);
                
                // 如果還是找不到，嘗試其他方法
                if (allCheckboxes.length === 0) {
                    console.warn('⚠️ 警告：未找到任何 checkbox，嘗試其他選擇器...');
                    const allInputsInForm = form.getElementsByTagName('input');
                    let checkboxCount = 0;
                    for (let i = 0; i < allInputsInForm.length; i++) {
                        if (allInputsInForm[i].type === 'checkbox') {
                            checkboxCount++;
                            console.log('找到 checkbox: ' + allInputsInForm[i].name + ' = ' + allInputsInForm[i].value);
                        }
                    }
                    console.log('使用 getElementsByTagName 找到 ' + checkboxCount + ' 個 checkbox');
                } else {
                    // 列出所有找到的 checkbox
                    allCheckboxes.forEach((cb, index) => {
                        console.log('Checkbox #' + index + ': name=' + cb.name + ', value=' + cb.value);
                    });
                }
            }, 500); // 增加延遲時間到 500ms
            
            console.log('✅ 自動儲存功能初始化完成（使用事件委派）');
        }
        
        // 儲存草稿（記錄所有欄位）
        function saveDraft() {
            if (isSubmitting) {
                console.log('表單正在提交，跳過儲存');
                return;
            }
            
            // 找到正確的表單（活動紀錄表單）
            let form = null;
            const activityDateInput = document.querySelector('input[name="activity_date"]');
            if (activityDateInput) {
                form = activityDateInput.closest('form');
            }
            
            if (!form) {
                form = document.querySelector('form[action=""]');
            }
            
            if (!form) {
                console.error('找不到表單，無法儲存草稿');
                return;
            }
            
            // 確認這是正確的表單（應該包含 activity_date 欄位）
            if (!form.querySelector('input[name="activity_date"]')) {
                console.warn('⚠️ 找到的表單不包含 activity_date，可能不是正確的表單，跳過儲存');
                return;
            }
            
            // 靜默儲存，不顯示提示
            // console.log('開始儲存草稿...');
            
            const draftData = {};
            let fieldCount = 0;
            
            // 收集所有文字輸入框和選擇框（包括驗證碼）
            // 只收集這個表單內的欄位
            const inputs = form.querySelectorAll('input[type="text"], input[type="date"], input[type="email"], input[type="tel"], select, textarea');
            console.log('找到 ' + inputs.length + ' 個輸入欄位');
            
            inputs.forEach(input => {
                // 確保欄位在表單內，並且有 name 屬性
                if (input.name && form.contains(input)) {
                    const value = input.value.trim();
                    // 只記錄這個表單的欄位（排除其他表單的欄位）
                    const validFields = ['activity_date', 'teacher_unit', 'teacher_name', 'school_name', 
                                       'contact_person', 'contact_phone', 'activity_type', 'activity_time', 
                                       'suggestion', 'captcha'];
                    
                    if (validFields.includes(input.name) || input.name.startsWith('participants') || 
                        input.name.startsWith('activity_feedback')) {
                        draftData[input.name] = value;
                        if (value) {
                            fieldCount++;
                            // 靜默儲存，不顯示每個欄位的日誌
                        }
                    }
                }
            });
            
            // 儲存檔案名稱（無法儲存檔案本身，但可以記錄檔案名稱）
            const fileInputs = form.querySelectorAll('input[type="file"][name="files[]"]');
            const fileNames = [];
            fileInputs.forEach(fileInput => {
                if (fileInput.files && fileInput.files.length > 0) {
                    Array.from(fileInput.files).forEach(file => {
                        fileNames.push(file.name);
                        // 靜默儲存，不顯示檔案名稱日誌
                    });
                }
            });
            if (fileNames.length > 0) {
                draftData['files_names'] = fileNames;
                fieldCount += fileNames.length;
            }
            
            // 儲存 checkbox 群組（參與對象）
            const participants = Array.from(form.querySelectorAll('input[name="participants[]"]:checked'))
                .map(cb => cb.value);
            if (participants.length > 0) {
                draftData.participants = participants;
                fieldCount += participants.length;
                // 靜默儲存，不顯示日誌
            }
            
            // 儲存 checkbox 群組（活動紀錄）
            const feedback = Array.from(form.querySelectorAll('input[name="activity_feedback[]"]:checked'))
                .map(cb => cb.value);
            if (feedback.length > 0) {
                draftData.activity_feedback = feedback;
                fieldCount += feedback.length;
                // 靜默儲存，不顯示日誌
            }
            
            // 記錄儲存時間
            draftData.saved_at = new Date().toISOString();
            
            // 儲存到 localStorage（靜默儲存，不顯示提示）
            try {
                const dataString = JSON.stringify(draftData);
                localStorage.setItem(FORM_STORAGE_KEY, dataString);
                // 靜默儲存，不顯示狀態提示
                // 只在 console 記錄（用於調試）
                if (fieldCount > 0) {
                    console.log('✅ 草稿已自動儲存（靜默），包含 ' + fieldCount + ' 個有值的欄位');
                }
            } catch (e) {
                console.error('❌ 儲存草稿失敗:', e);
                // 只有失敗時才顯示提示
                showStatus('儲存失敗', 'info');
            }
            
            updateProgress();
        }
        
        // 載入草稿（靜默模式：有草稿就載入，沒有就不提示）
        function loadDraft(showMessage = false) {
            try {
                const draftData = localStorage.getItem(FORM_STORAGE_KEY);
                if (!draftData) {
                    // 只有在手動點擊載入按鈕時才顯示提示
                    if (showMessage) {
                        alert('沒有找到儲存的草稿');
                    }
                    return false;
                }
                
                const data = JSON.parse(draftData);
                console.log('準備載入草稿資料:', data);
                
                // 檢查資料格式是否正確（應該包含活動紀錄表單的欄位）
                const validFields = ['activity_date', 'teacher_unit', 'teacher_name', 'school_name', 
                                   'contact_person', 'contact_phone', 'activity_type', 'activity_time', 
                                   'suggestion', 'captcha', 'participants', 'activity_feedback', 
                                   'files_names', 'saved_at'];
                
                const hasValidFields = Object.keys(data).some(key => 
                    validFields.includes(key) || key.startsWith('participants') || key.startsWith('activity_feedback')
                );
                
                if (!hasValidFields) {
                    console.error('❌ 草稿資料格式錯誤：不包含有效的活動紀錄欄位');
                    console.error('資料內容:', data);
                    if (showMessage) {
                        alert('草稿資料格式錯誤或無法載入。請清除草稿後重新填寫。');
                    }
                    return false;
                }
                
                // 找到正確的表單
                let form = null;
                const activityDateInput = document.querySelector('input[name="activity_date"]');
                if (activityDateInput) {
                    form = activityDateInput.closest('form');
                }
                
                if (!form) {
                    form = document.querySelector('form[action=""]');
                }
                
                if (!form) {
                    console.warn('⚠️ 表單元素未找到，無法載入草稿');
                    return false;
                }
                
                // 確認這是正確的表單
                if (!form.querySelector('input[name="activity_date"]')) {
                    console.warn('⚠️ 找到的表單不包含 activity_date，可能不是正確的表單');
                    return false;
                }
                
                let loadedCount = 0;
                
                // 載入文字欄位和選擇框
                Object.keys(data).forEach(key => {
                    // 跳過特殊欄位
                    if (key === 'participants' || key === 'activity_feedback' || key === 'saved_at' || key === 'files_names') {
                        return;
                    }
                    
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input) {
                        if (input.type === 'file') {
                            // 檔案無法載入，但可以顯示提示
                            console.log('⚠️ 檔案欄位 ' + key + ' 無法自動載入，請重新選擇檔案');
                            return;
                        } else if (input.type === 'checkbox') {
                            // 單選 checkbox
                            if (data[key] === input.value) {
                                input.checked = true;
                                loadedCount++;
                                console.log('✅ 載入 checkbox: ' + key + ' = ' + data[key]);
                            }
                        } else if (input.type === 'radio') {
                            // radio button
                            if (data[key] === input.value) {
                                input.checked = true;
                                loadedCount++;
                                console.log('✅ 載入 radio: ' + key + ' = ' + data[key]);
                            }
                        } else {
                            // 文字輸入框、選擇框等
                            input.value = data[key] || '';
                            if (data[key]) {
                                loadedCount++;
                                console.log('✅ 載入欄位: ' + key + ' = ' + data[key]);
                            }
                        }
                    } else {
                        console.warn('⚠️ 找不到欄位: ' + key);
                    }
                });
                
                // 載入 checkbox 群組（參與對象）
                if (data.participants && Array.isArray(data.participants)) {
                    data.participants.forEach(value => {
                        const checkbox = form.querySelector(`input[name="participants[]"][value="${CSS.escape(value)}"]`);
                        if (checkbox) {
                            checkbox.checked = true;
                            loadedCount++;
                        }
                    });
                }
                
                // 載入 checkbox 群組（活動紀錄）
                if (data.activity_feedback && Array.isArray(data.activity_feedback)) {
                    data.activity_feedback.forEach(value => {
                        const checkbox = form.querySelector(`input[name="activity_feedback[]"][value="${CSS.escape(value)}"]`);
                        if (checkbox) {
                            checkbox.checked = true;
                            loadedCount++;
                        }
                    });
                }
                
                // 載入成功時顯示提示（無論是手動還是自動）
                if (loadedCount > 0) {
                    showStatus('草稿已載入', 'saved');
                    console.log('✅ 草稿已載入，共載入 ' + loadedCount + ' 個欄位');
                } else {
                    // 載入失敗時才顯示錯誤
                    if (showMessage) {
                        alert('草稿資料格式錯誤或無法載入');
                    } else {
                        console.warn('⚠️ 草稿載入失敗或沒有載入任何欄位');
                    }
                }
                
                updateProgress();
                
                // 觸發驗證和進度更新
                form.querySelectorAll('input, select, textarea').forEach(input => {
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                });
                
                return loadedCount > 0;
            } catch (e) {
                console.error('載入草稿失敗:', e);
                if (showMessage) {
                    alert('載入草稿時發生錯誤: ' + e.message);
                }
                return false;
            }
        }
        
        // 清除草稿
        function clearDraft() {
            if (confirm('確定要清除所有草稿資料嗎？')) {
                localStorage.removeItem(FORM_STORAGE_KEY);
                showStatus('草稿已清除', 'info');
                document.querySelector('form').reset();
                updateProgress();
            }
        }
        
        // ==================== 即時驗證功能 ====================
        
        function initRealTimeValidation() {
            const form = document.querySelector('form');
            if (!form) return;
            
            // 驗證活動日期
            const dateInput = form.querySelector('input[name="activity_date"]');
            if (dateInput) {
                dateInput.addEventListener('change', function() {
                    validateDate(this);
                });
            }
            
            // 驗證學校名稱
            const schoolInput = form.querySelector('input[name="school_name"]');
            if (schoolInput) {
                schoolInput.addEventListener('input', function() {
                    validateRequired(this, '請輸入學校名稱');
                });
            }
            
            // 驗證電話
            const phoneInput = form.querySelector('input[name="contact_phone"]');
            if (phoneInput) {
                phoneInput.addEventListener('input', function() {
                    validatePhone(this);
                });
            }
            
            // 驗證活動性質和時間
            const activityType = form.querySelector('select[name="activity_type"]');
            const activityTime = form.querySelector('select[name="activity_time"]');
            
            if (activityType) {
                activityType.addEventListener('change', function() {
                    validateRequired(this, '請選擇活動性質');
                });
            }
            
            if (activityTime) {
                activityTime.addEventListener('change', function() {
                    validateRequired(this, '請選擇活動時間');
                });
            }
            
            // 驗證參與對象和活動紀錄
            const participants = form.querySelectorAll('input[name="participants[]"]');
            const feedback = form.querySelectorAll('input[name="activity_feedback[]"]');
            
            participants.forEach(cb => {
                cb.addEventListener('change', function() {
                    validateCheckboxGroup('participants[]', '請至少選擇一個參與對象');
                });
            });
            
            feedback.forEach(cb => {
                cb.addEventListener('change', function() {
                    validateCheckboxGroup('activity_feedback[]', '請至少選擇一個活動紀錄');
                });
            });
        }
        
        function validateRequired(input, message) {
            const isValid = input.value.trim() !== '';
            showFieldValidation(input, isValid, message);
            return isValid;
        }
        
        function validateDate(input) {
            const date = new Date(input.value);
            const today = new Date();
            const isValid = input.value && date <= today;
            showFieldValidation(input, isValid, isValid ? '' : '活動日期不能是未來日期');
            return isValid;
        }
        
        function validatePhone(input) {
            if (!input.value) {
                removeFieldValidation(input);
                return true; // 電話是選填的
            }
            const isValid = /^[0-9]{1,10}$/.test(input.value);
            showFieldValidation(input, isValid, isValid ? '' : '請輸入1-10位數字');
            return isValid;
        }
        
        function validateCheckboxGroup(name, message) {
            const checked = document.querySelectorAll(`input[name="${name}"]:checked`);
            const isValid = checked.length > 0;
            
            // 找到第一個 checkbox 來顯示驗證訊息
            const firstCheckbox = document.querySelector(`input[name="${name}"]`);
            if (firstCheckbox) {
                showFieldValidation(firstCheckbox, isValid, isValid ? '' : message);
            }
            
            return isValid;
        }
        
        function showFieldValidation(input, isValid, message) {
            // 移除舊的驗證訊息
            removeFieldValidation(input);
            
            if (!message) return;
            
            const validation = document.createElement('div');
            validation.className = `field-validation ${isValid ? 'valid' : 'invalid'}`;
            validation.innerHTML = `<i class="fas fa-${isValid ? 'check' : 'exclamation-circle'}"></i> ${message}`;
            
            input.parentElement.appendChild(validation);
            
            // 添加視覺回饋
            if (isValid) {
                input.style.borderColor = '#28a745';
            } else {
                input.style.borderColor = '#dc3545';
            }
        }
        
        function removeFieldValidation(input) {
            const existing = input.parentElement.querySelector('.field-validation');
            if (existing) {
                existing.remove();
            }
            input.style.borderColor = '';
        }
        
        // ==================== 檔案預覽功能 ====================
        
        function initFilePreview() {
            const fileInputs = document.querySelectorAll('input[type="file"][name="files[]"]');
            
            fileInputs.forEach((input, index) => {
                // 為每個輸入添加唯一標識符
                if (!input.dataset.inputId) {
                    input.dataset.inputId = 'file-input-' + index;
                }
                
                input.addEventListener('change', function(e) {
                    handleFilePreview(this);
                });
            });
        }
        
        function handleFilePreview(input) {
            const files = Array.from(input.files);
            const container = input.closest('.file-upload-area');
            if (!container) return;
            
            // 獲取或創建輸入的唯一標識符
            let inputId = input.dataset.inputId;
            if (!inputId) {
                // 如果沒有標識符，創建一個
                const allInputs = container.querySelectorAll('input[type="file"][name="files[]"]');
                const inputIndex = Array.from(allInputs).indexOf(input);
                inputId = 'file-input-' + inputIndex;
                input.dataset.inputId = inputId;
            }
            
            // 創建預覽容器
            let previewContainer = container.querySelector('.file-preview-container');
            if (!previewContainer) {
                previewContainer = document.createElement('div');
                previewContainer.className = 'file-preview-container';
                container.querySelector('.file-upload-controls').insertAdjacentElement('beforebegin', previewContainer);
            }
            
            // 清除該輸入的舊預覽項（如果重新選擇文件）
            const existingPreviews = previewContainer.querySelectorAll(`.file-preview-item[data-input-id="${inputId}"]`);
            existingPreviews.forEach(preview => preview.remove());
            
            // 如果沒有選擇文件，直接返回
            if (files.length === 0) {
                return;
            }
            
            files.forEach((file, fileIndex) => {
                // 為每個預覽項創建唯一標識符
                const previewId = inputId + '-file-' + fileIndex;
                
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // 檢查是否已經存在相同ID的預覽項（防止重複）
                        const existingPreview = previewContainer.querySelector(`.file-preview-item[data-preview-id="${previewId}"]`);
                        if (existingPreview) {
                            return;
                        }
                        
                        const previewItem = document.createElement('div');
                        previewItem.className = 'file-preview-item';
                        previewItem.dataset.inputId = inputId;
                        previewItem.dataset.previewId = previewId;
                        previewItem.dataset.fileName = file.name;
                        previewItem.innerHTML = `
                            <img src="${e.target.result}" alt="${file.name}">
                            <div class="file-info">${file.name}</div>
                            <button type="button" class="remove-preview" onclick="removeFilePreview(this, '${input.name}', '${inputId}')">
                                <i class="fas fa-times"></i>
                            </button>
                        `;
                        previewContainer.appendChild(previewItem);
                    };
                    reader.readAsDataURL(file);
                } else {
                    // 檢查是否已經存在相同ID的預覽項（防止重複）
                    const existingPreview = previewContainer.querySelector(`.file-preview-item[data-preview-id="${previewId}"]`);
                    if (existingPreview) {
                        return;
                    }
                    
                    // 非圖片檔案顯示檔案資訊
                    const previewItem = document.createElement('div');
                    previewItem.className = 'file-preview-item';
                    previewItem.dataset.inputId = inputId;
                    previewItem.dataset.previewId = previewId;
                    previewItem.dataset.fileName = file.name;
                    previewItem.innerHTML = `
                        <div style="padding: 20px; text-align: center;">
                            <i class="fas fa-file" style="font-size: 48px; color: #6c757d;"></i>
                            <div class="file-info">${file.name}</div>
                        </div>
                        <button type="button" class="remove-preview" onclick="removeFilePreview(this, '${input.name}', '${inputId}')">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    previewContainer.appendChild(previewItem);
                }
            });
        }
        
        function removeFilePreview(button, inputName, inputId) {
            const previewItem = button.closest('.file-preview-item');
            if (previewItem) {
                // 獲取對應的文件輸入
                const inputIdToUse = inputId || previewItem.dataset.inputId;
                if (inputIdToUse) {
                    const fileInput = document.querySelector(`input[type="file"][data-input-id="${inputIdToUse}"]`);
                    if (fileInput) {
                        // 清除文件輸入的值
                        fileInput.value = '';
                        // 隱藏刪除按鈕
                        const removeBtn = fileInput.closest('.file-input-group').querySelector('.remove-file-btn');
                        if (removeBtn) {
                            removeBtn.style.display = 'none';
                        }
                    }
                }
                previewItem.remove();
            }
        }
        
        // ==================== 進度追蹤功能 ====================
        
        function initProgressTracker() {
            updateProgress();
        }
        
        function updateProgress() {
            const form = document.querySelector('form');
            if (!form) return;
            
            const requiredFields = [
                { name: 'activity_date', type: 'input' },
                { name: 'school_name', type: 'input' },
                { name: 'activity_type', type: 'select' },
                { name: 'activity_time', type: 'select' },
                { name: 'participants[]', type: 'checkbox' },
                { name: 'activity_feedback[]', type: 'checkbox' }
            ];
            
            let completed = 0;
            const total = requiredFields.length;
            
            requiredFields.forEach(field => {
                let isCompleted = false;
                
                if (field.type === 'checkbox') {
                    const checked = form.querySelectorAll(`input[name="${field.name}"]:checked`);
                    isCompleted = checked.length > 0;
                } else if (field.type === 'select') {
                    const select = form.querySelector(`select[name="${field.name}"]`);
                    isCompleted = select && select.value !== '';
                } else {
                    const input = form.querySelector(`input[name="${field.name}"]`);
                    isCompleted = input && input.value.trim() !== '';
                }
                
                if (isCompleted) completed++;
            });
            
            const percentage = Math.round((completed / total) * 100);
            const progressFill = document.getElementById('progress-fill');
            const progressPercentage = document.getElementById('progress-percentage');
            
            if (progressFill) {
                progressFill.style.width = percentage + '%';
            }
            if (progressPercentage) {
                progressPercentage.textContent = percentage + '%';
            }
        }
        
        // ==================== 字數統計功能 ====================
        
        function initCharCounter() {
            const textarea = document.querySelector('textarea[name="suggestion"]');
            if (!textarea) return;
            
            const counter = document.createElement('div');
            counter.className = 'char-counter';
            counter.id = 'char-counter';
            counter.textContent = '0 字';
            textarea.parentElement.appendChild(counter);
            
            textarea.addEventListener('input', function() {
                const length = this.value.length;
                counter.textContent = `${length} 字`;
                
                if (length > 1000) {
                    counter.style.color = '#dc3545';
                } else if (length > 500) {
                    counter.style.color = '#ffc107';
                } else {
                    counter.style.color = '#6c757d';
                }
            });
        }
        
        // ==================== 工具函數 ====================
        
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        
        // ==================== 原有功能 ====================
        // 重整驗證碼函數
        function refreshCaptcha() {
            const refreshBtn = document.querySelector('.refresh-btn');
            const captchaDisplay = document.getElementById('captcha-display');
            const captchaInput = document.querySelector('input[name="captcha"]');
            
            // 顯示載入狀態
            if (refreshBtn) {
                refreshBtn.disabled = true;
                refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 載入中...';
            }
            
            // 刷新驗證碼圖片（添加時間戳防止緩存）
            if (captchaDisplay && captchaDisplay.tagName === 'IMG') {
                captchaDisplay.src = 'captcha_image.php?t=' + new Date().getTime();
                // 清空輸入框並確保可以輸入
                if (captchaInput) {
                    captchaInput.value = '';
                    // 強制確保輸入框沒有被禁用
                    captchaInput.disabled = false;
                    captchaInput.readOnly = false;
                    captchaInput.removeAttribute('disabled');
                    captchaInput.removeAttribute('readonly');
                    captchaInput.style.pointerEvents = 'auto';
                    captchaInput.style.opacity = '1';
                    captchaInput.style.cursor = 'text';
                }
                // 恢復按鈕狀態
                if (refreshBtn) {
                    refreshBtn.disabled = false;
                    refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> 重整';
                }
                // 不自動聚焦到輸入框，避免頁面滾動到底部
                // 只有在用戶主動點擊刷新按鈕時才聚焦
                // if (captchaInput) {
                //     setTimeout(function() {
                //         captchaInput.focus();
                //     }, 100);
                // }
            } else {
                // 備用方案：如果圖片元素不存在
                if (refreshBtn) {
                    refreshBtn.disabled = false;
                    refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> 重整';
                }
                alert('驗證碼重整失敗，請重新載入頁面。');
            }
        }
        
        // 驗證碼輸入框限制只能輸入數字（確保在DOM加載完成後執行）
        function initCaptchaInput() {
            const captchaInput = document.querySelector('input[name="captcha"]') || document.getElementById('captcha-input');
            if (captchaInput) {
                // 強制確保輸入框沒有被禁用
                captchaInput.disabled = false;
                captchaInput.readOnly = false;
                captchaInput.removeAttribute('disabled');
                captchaInput.removeAttribute('readonly');
                
                // 確保輸入框可以接收輸入
                captchaInput.style.pointerEvents = 'auto';
                captchaInput.style.opacity = '1';
                captchaInput.style.cursor = 'text';
                captchaInput.style.userSelect = 'text';
                captchaInput.style.webkitUserSelect = 'text';
                captchaInput.style.mozUserSelect = 'text';
                captchaInput.style.msUserSelect = 'text';
                
                // 確保z-index足夠高
                captchaInput.style.position = 'relative';
                captchaInput.style.zIndex = '1000';
                
                // 檢查是否有覆蓋層
                const rect = captchaInput.getBoundingClientRect();
                const elementAtPoint = document.elementFromPoint(rect.left + rect.width / 2, rect.top + rect.height / 2);
                if (elementAtPoint && elementAtPoint !== captchaInput && !captchaInput.contains(elementAtPoint)) {
                    console.warn('發現可能的覆蓋層:', elementAtPoint);
                    if (elementAtPoint.style) {
                        elementAtPoint.style.pointerEvents = 'none';
                    }
                }
                
                // 移除所有可能阻止輸入的事件監聽器（使用新的事件處理方式）
                const originalInput = captchaInput;
                
                // 移除舊的事件監聽器（通過克隆）
                const newInput = originalInput.cloneNode(true);
                // 確保克隆的元素保留所有屬性
                newInput.value = originalInput.value || '';
                newInput.id = originalInput.id || 'captcha-input';
                newInput.name = originalInput.name || 'captcha';
                newInput.type = originalInput.type || 'text';
                newInput.placeholder = originalInput.placeholder || '請輸入驗證碼';
                newInput.maxLength = originalInput.maxLength || 4;
                newInput.required = originalInput.required || true;
                newInput.autocomplete = originalInput.autocomplete || 'off';
                
                // 保留所有內聯樣式
                if (originalInput.style.cssText) {
                    newInput.style.cssText = originalInput.style.cssText;
                }
                
                originalInput.parentNode.replaceChild(newInput, originalInput);
                const freshInput = document.querySelector('input[name="captcha"]') || document.getElementById('captcha-input');
                
                // 再次確保屬性正確
                freshInput.disabled = false;
                freshInput.readOnly = false;
                freshInput.style.pointerEvents = 'auto';
                freshInput.style.cursor = 'text';
                freshInput.style.userSelect = 'text';
                
                // 限制只能輸入字母和數字（驗證碼包含字母和數字）
                freshInput.addEventListener('input', function(e) {
                    // 保存原始值
                    const originalValue = e.target.value || '';
                    
                    // 只保留字母和數字，轉換為大寫（驗證碼通常不區分大小寫）
                    const alphanumericValue = originalValue.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
                    // 限制最多6位（驗證碼通常是5-6位）
                    const finalValue = alphanumericValue.slice(0, 6);
                    
                    // 只有在值確實改變時才更新（避免無限循環）
                    if (this.value !== finalValue) {
                        // 保存光標位置
                        const cursorPosition = this.selectionStart || 0;
                        
                        // 設置新值
                        this.value = finalValue;
                        
                        // 強制確保值被設置（防止被其他代碼覆蓋）
                        setTimeout(() => {
                            if (this.value !== finalValue) {
                                this.value = finalValue;
                            }
                        }, 0);
                        
                        // 恢復光標位置（如果刪除了字符，調整位置）
                        setTimeout(() => {
                            const newCursorPosition = Math.min(
                                Math.max(0, cursorPosition - (originalValue.length - finalValue.length)), 
                                finalValue.length
                            );
                            this.setSelectionRange(newCursorPosition, newCursorPosition);
                        }, 0);
                    }
                }, { passive: true });
                
                // 在keydown時處理，允許字母和數字（但允許控制鍵）
                freshInput.addEventListener('keydown', function(e) {
                    // 允許控制鍵（退格、刪除、方向鍵等）
                    const allowedKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab', 'Home', 'End', 'Enter'];
                    if (allowedKeys.includes(e.key)) {
                        return; // 允許這些鍵，不阻止
                    }
                    
                    // 允許 Ctrl/Cmd + A, C, V, X 等組合鍵
                    if (e.ctrlKey || e.metaKey) {
                        return; // 允許複製、粘貼等操作
                    }
                    
                    // 允許數字鍵
                    if (e.key >= '0' && e.key <= '9') {
                        return; // 允許數字，不阻止
                    }
                    
                    // 允許字母鍵（大小寫都可以，會在input事件中轉為大寫）
                    if ((e.key >= 'a' && e.key <= 'z') || (e.key >= 'A' && e.key <= 'Z')) {
                        return; // 允許字母，不阻止
                    }
                    
                    // 阻止其他字符
                    e.preventDefault();
                }, { passive: false });
                
                // 確保可以聚焦
                freshInput.addEventListener('focus', function(e) {
                    this.style.borderColor = '#6c7aed';
                    this.style.boxShadow = '0 0 0 4px rgba(108, 122, 237, 0.15)';
                    this.style.outline = 'none';
                }, { passive: true });
                
                freshInput.addEventListener('blur', function(e) {
                    this.style.borderColor = '#ddd';
                    this.style.boxShadow = 'none';
                }, { passive: true });
                
                // 不自動聚焦，避免頁面滾動到底部
                // 只有在用戶主動操作時才聚焦
                // setTimeout(function() {
                //     if (document.activeElement !== freshInput) {
                //         freshInput.focus();
                //         console.log('強制聚焦到驗證碼輸入框');
                //     }
                // }, 100);
                
                console.log('驗證碼輸入框已初始化，可以輸入', freshInput);
            } else {
                console.error('找不到驗證碼輸入框');
            }
        }
        
        // 在DOM加載完成後執行（但不自動聚焦）
        // 延遲執行，確保不會在頁面首次加載時干擾滾動到頂部
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                // 延遲執行，避免干擾頁面滾動到頂部
                setTimeout(initCaptchaInput, 1000);
            });
        } else {
            // 延遲執行，避免干擾頁面滾動到頂部
            setTimeout(initCaptchaInput, 1000);
        }
        
        // 電話欄位限制只能輸入數字且最多10位（確保在DOM加載完成後執行）
        document.addEventListener('DOMContentLoaded', function() {
            const phoneInput = document.querySelector('input[name="contact_phone"]');
            if (phoneInput) {
                // 確保輸入框沒有被禁用
                phoneInput.removeAttribute('disabled');
                phoneInput.removeAttribute('readonly');
                
                // 移除非數字字符
                phoneInput.addEventListener('input', function(e) {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    // 限制最多10位數字
                    if (this.value.length > 10) {
                        this.value = this.value.slice(0, 10);
                    }
                });
                
                // 電話欄位按鍵限制（防止輸入非數字字符）
                phoneInput.addEventListener('keypress', function(e) {
                    // 只允許數字鍵、退格鍵、刪除鍵、方向鍵等
                    const allowedKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'];
                    if (!allowedKeys.includes(e.key) && (e.key < '0' || e.key > '9')) {
                        e.preventDefault();
                    }
                });
            }
        });
        
        // 表單驗證與提交處理
        document.querySelector('form').addEventListener('submit', function(e) {
            const participants = document.querySelectorAll('input[name="participants[]"]:checked');
            const feedback = document.querySelectorAll('input[name="activity_feedback[]"]:checked');
            const captchaInput = document.querySelector('input[name="captcha"]');
            
            // 檢查參與對象
            if (participants.length === 0) {
                e.preventDefault();
                alert('請至少選擇一個參與對象！');
                return;
            }
            
            // 檢查是否勾選了「其他」參與對象，如果有，必須輸入自定義文字
            const participantsOtherInput = document.getElementById('participants_other');
            let hasParticipantsOther = false;
            participants.forEach(function(checkbox) {
                if (checkbox.value === '其他' || checkbox.value.startsWith('其他: ')) {
                    hasParticipantsOther = true;
                }
            });
            if (hasParticipantsOther && participantsOtherInput) {
                const otherText = participantsOtherInput.value.trim();
                if (!otherText) {
                    e.preventDefault();
                    alert('請輸入「其他」參與對象的具體內容！');
                    participantsOtherInput.focus();
                    return;
                }
                // 更新 checkbox 值
                participants.forEach(function(checkbox) {
                    if (checkbox.value === '其他' || checkbox.value.startsWith('其他: ')) {
                        checkbox.value = '其他: ' + otherText;
                    }
                });
            }
            
            // 檢查活動紀錄
            if (feedback.length === 0) {
                e.preventDefault();
                alert('請至少選擇一個活動紀錄！');
                return;
            }
            
            // 檢查是否勾選了「其他」活動紀錄，如果有，必須輸入自定義文字
            const feedbackOtherInput = document.getElementById('activity_feedback_other');
            let hasFeedbackOther = false;
            feedback.forEach(function(checkbox) {
                if (checkbox.value === '其他' || checkbox.value.startsWith('其他: ')) {
                    hasFeedbackOther = true;
                }
            });
            if (hasFeedbackOther && feedbackOtherInput) {
                const otherText = feedbackOtherInput.value.trim();
                if (!otherText) {
                    e.preventDefault();
                    alert('請輸入「其他」活動紀錄的具體內容！');
                    feedbackOtherInput.focus();
                    return;
                }
                // 更新 checkbox 值
                feedback.forEach(function(checkbox) {
                    if (checkbox.value === '其他' || checkbox.value.startsWith('其他: ')) {
                        checkbox.value = '其他: ' + otherText;
                    }
                });
            }
            
            // 驗證碼長度檢查（5-6位字母數字）
            const captchaValue = captchaInput.value.trim();
            if (captchaValue.length < 4 || captchaValue.length > 6) {
                e.preventDefault();
                alert('請輸入4-6位字母或數字的驗證碼！');
                captchaInput.focus();
                return;
            }
            
            // 標記為正在提交，防止自動儲存
            isSubmitting = true;
            
            // 提交成功後清除草稿（在頁面重新載入時）
            setTimeout(() => {
                localStorage.removeItem(FORM_STORAGE_KEY);
            }, 1000);
        });
        
        // 檔案上傳相關函數
        function addFileInput() {
            const container = document.getElementById('file-inputs-container');
            const fileInputs = container.querySelectorAll('.file-input-group');
            
            // 限制最多上傳10個檔案
            if (fileInputs.length >= 10) {
                alert('最多只能上傳10個檔案！');
                return;
            }
            
            const newFileGroup = document.createElement('div');
            newFileGroup.className = 'file-input-group';
            const newInput = document.createElement('input');
            newInput.type = 'file';
            newInput.name = 'files[]';
            newInput.accept = 'image/*,.zip,.rar,.pdf';
            
            // 為新輸入添加唯一標識符
            const inputIndex = fileInputs.length;
            newInput.dataset.inputId = 'file-input-' + inputIndex;
            
            // 添加事件監聽器
            newInput.addEventListener('change', function(e) {
                handleFilePreview(this);
            });
            
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'remove-file-btn';
            removeBtn.onclick = function() { removeFileInput(this); };
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            
            newFileGroup.appendChild(newInput);
            newFileGroup.appendChild(removeBtn);
            
            container.appendChild(newFileGroup);
            updateRemoveButtons();
            
            // 聚焦到新增的檔案輸入框
            newInput.focus();
        }
        
        function removeFileInput(button) {
            const fileGroup = button.parentElement;
            fileGroup.remove();
            updateRemoveButtons();
        }
        
        function updateRemoveButtons() {
            const container = document.getElementById('file-inputs-container');
            const fileInputs = container.querySelectorAll('.file-input-group');
            const removeButtons = container.querySelectorAll('.remove-file-btn');
            
            // 如果只有一個檔案輸入框，隱藏刪除按鈕
            removeButtons.forEach(button => {
                button.style.display = fileInputs.length > 1 ? 'block' : 'none';
            });
        }
        
        // 監聽檔案選擇事件，自動顯示刪除按鈕
        document.addEventListener('change', function(e) {
            if (e.target.type === 'file' && e.target.name === 'files[]') {
                const fileGroup = e.target.parentElement;
                const removeBtn = fileGroup.querySelector('.remove-file-btn');
                
                if (e.target.files.length > 0) {
                    removeBtn.style.display = 'block';
                    
                    // 檢查檔案大小
                    Array.from(e.target.files).forEach(file => {
                        if (file.size > 10 * 1024 * 1024) {
                            alert(`檔案 "${file.name}" 超過 10MB 限制！`);
                            e.target.value = '';
                            removeBtn.style.display = 'none';
                        }
                    });
                }
                
                updateRemoveButtons();
            }
        });
        
        // 按鈕hover效果
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('toggleRecordsBtn');
            if (toggleBtn) {
                toggleBtn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 5px 10px rgba(0,0,0,0.3)';
                });
                
                toggleBtn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 3px 6px rgba(0,0,0,0.2)';
                });
            }
        });
        
        // 頁面載入完成後初始化
        document.addEventListener('DOMContentLoaded', function() {
            updateRemoveButtons(); // 初始化刪除按鈕狀態
            
            // 監聽表單欄位變化以更新進度
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('input', updateProgress);
                form.addEventListener('change', updateProgress);
            }
        });
    </script>
    
    <?php include("share/footer.php"); ?>
    
    <!-- 浮動助手組件 -->
    <?php include("share/chat_widget.php"); ?>
    <?php include("share/ai_widget.php"); ?>
</body>
</html>
