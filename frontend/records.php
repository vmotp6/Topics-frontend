<?php
session_start();

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

// 查詢該教師的活動記錄
$activity_records = [];
if ($teacher_id) {
    $records_sql = "SELECT * FROM activity_records WHERE teacher_id = ? ORDER BY activity_date DESC, id DESC";
    $records_stmt = $conn->prepare($records_sql);
    if ($records_stmt) {
        $records_stmt->bind_param("i", $teacher_id);
        $records_stmt->execute();
        $records_result = $records_stmt->get_result();
        
        if ($records_result) {
            while ($row = $records_result->fetch_assoc()) {
                $activity_records[] = $row;
            }
        }
        $records_stmt->close();
    }
}

$message = "";
$messageType = "";

// 處理表單提交
if ($_POST) {
    // 驗證必填欄位
    $required_fields = ['activity_date', 'teacher_unit', 'teacher_name', 'school_name', 'activity_type', 'activity_time', 'captcha'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    // 驗證驗證碼
    if (!isset($_SESSION['captcha']) || $_POST['captcha'] !== $_SESSION['captcha']) {
        $missing_fields[] = 'captcha_invalid';
    }
    
    // 驗證電話號碼格式（如果有填寫的話）
    if (!empty($_POST['contact_phone'])) {
        if (!preg_match('/^[0-9]{1,10}$/', $_POST['contact_phone'])) {
            $missing_fields[] = 'phone_invalid';
        }
    }
    
    if (empty($missing_fields)) {
        // 處理參與對象多選
        $participants = [];
        if (isset($_POST['participants'])) {
            $participants = $_POST['participants'];
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
        $sql = "INSERT INTO activity_records (activity_date, teacher_unit, teacher_name, teacher_id, school_name, contact_person, contact_phone, activity_type, activity_time, participants, activity_feedback, suggestion, uploaded_files) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssisssssssss", 
            $_POST['activity_date'],
            $_POST['teacher_unit'],
            $_POST['teacher_name'],
            $teacher_id,
            $_POST['school_name'],
            $_POST['contact_person'],
            $_POST['contact_phone'],
            $_POST['activity_type'],
            $_POST['activity_time'],
            $participants_str,
            $feedback_str,
            $_POST['suggestion'],
            $files_json
        );
        
        if ($stmt->execute()) {
            $message = "資料已成功提交！";
            $messageType = "success";
            // 提交成功後重新生成驗證碼
            $_SESSION['captcha'] = generateCaptcha();
            // 清空 POST 資料，避免表單資料被保留
            $_POST = array();
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
            $_SESSION['captcha'] = generateCaptcha();
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
                <div class="teacher-info-section" style="background: linear-gradient(135deg, #e8f4fd 0%, #f0f8ff 100%); padding: 20px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #6c7aed;">
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
            <div class="view-records-section" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); padding: 20px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #f0ad4e; text-align: center;">
                <h4 style="color: #856404; margin-bottom: 15px;">
                    <i class="fas fa-database"></i> 活動記錄管理
                </h4>
                <p style="color: #856404; margin-bottom: 20px;">
                    進入專門的管理頁面來查看、編輯、刪除
                </p>
                <button type="button" id="toggleRecordsBtn" class="toggle-records-btn" onclick="window.location.href='activity_records_management.php'" 
                        style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-size: 1.1em; font-weight: bold; box-shadow: 0 3px 6px rgba(0,0,0,0.2); transition: all 0.3s ease;">
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
                            <div class="field-group">
                                <label><span class="required">*</span> 教師單位:</label>
                                                                 <input type="text" name="teacher_unit" placeholder="請輸入教師單位" 
                                        value="<?php 
                                        echo isset($_POST['teacher_unit']) ? htmlspecialchars($_POST['teacher_unit']) : 
                                             (isset($teacher_info['department']) ? htmlspecialchars($teacher_info['department']) : ''); 
                                        ?>" required>
                            </div>
                            <div class="field-group">
                                <label><span class="required">*</span> 教師姓名:</label>
                                <input type="text" name="teacher_name" placeholder="請輸入教師姓名" 
                                       value="<?php 
                                       echo isset($_POST['teacher_name']) ? htmlspecialchars($_POST['teacher_name']) : 
                                            (isset($teacher_info['name']) ? htmlspecialchars($teacher_info['name']) : ''); 
                                       ?>" required>
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
                                    <option value="來校體驗" <?php echo (isset($_POST['activity_type']) && $_POST['activity_type'] === '來校體驗') ? 'selected' : ''; ?>>來校體驗</option>
                                    <option value="校外參訪" <?php echo (isset($_POST['activity_type']) && $_POST['activity_type'] === '校外參訪') ? 'selected' : ''; ?>>校外參訪</option>
                                    <option value="講座分享" <?php echo (isset($_POST['activity_type']) && $_POST['activity_type'] === '講座分享') ? 'selected' : ''; ?>>講座分享</option>
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
                            $participants_options = ['國中九年級', '國中八年級', '國中七年級', '高中三年級', '高中二年級', '高中一年級', '教師(職員工)', '家長', '其他'];
                            $selected_participants = isset($_POST['participants']) ? $_POST['participants'] : [];
                            ?>
                            <?php foreach ($participants_options as $option): ?>
                            <label class="checkbox-item">
                                <input type="checkbox" name="participants[]" value="<?php echo htmlspecialchars($option); ?>" <?php echo in_array($option, $selected_participants) ? 'checked' : ''; ?>>
                                <span><?php echo htmlspecialchars($option); ?></span>
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
                            ?>
                            <?php foreach ($feedback_options as $option): ?>
                            <label class="checkbox-item">
                                <input type="checkbox" name="activity_feedback[]" value="<?php echo htmlspecialchars($option); ?>" <?php echo in_array($option, $selected_feedback) ? 'checked' : ''; ?>>
                                <span><?php echo htmlspecialchars($option); ?></span>
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
                        <div class="captcha-section">
                            <label>請輸入右側驗證碼:</label>
                            <input type="text" name="captcha" class="captcha-input" placeholder="請輸入驗證碼" maxlength="4" required autocomplete="off">
                            <div class="captcha-code" id="captcha-display"><?php echo getCurrentCaptcha(); ?></div>
                            <button type="button" class="refresh-btn" onclick="refreshCaptcha()" title="重新產生驗證碼">
                                <i class="fas fa-sync-alt"></i> 重整
                            </button>
                        </div>
                        <small style="color: #666; margin-top: 8px; display: block;">
                            <i class="fas fa-info-circle"></i> 點擊「重整」按鈕可產生新的驗證碼
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

    <script>
        // 重整驗證碼函數
        function refreshCaptcha() {
            const refreshBtn = document.querySelector('.refresh-btn');
            const captchaDisplay = document.getElementById('captcha-display');
            const captchaInput = document.querySelector('input[name="captcha"]');
            
            // 顯示載入狀態
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 載入中...';
            
            // 發送 AJAX 請求獲取新驗證碼
            fetch('generate_captcha.php?action=refresh', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                // 更新驗證碼顯示
                captchaDisplay.textContent = data.captcha;
                // 清空輸入框
                captchaInput.value = '';
                // 恢復按鈕狀態
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> 重整';
                // 聚焦到輸入框
                captchaInput.focus();
            })
            .catch(error => {
                console.error('驗證碼重整失敗:', error);
                // 備用方案：生成前端隨機數字
                const fallbackCode = Math.floor(1000 + Math.random() * 9000);
                captchaDisplay.textContent = fallbackCode;
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> 重整';
                alert('驗證碼重整失敗，請重新載入頁面或稍後再試。');
            });
        }
        
        // 驗證碼輸入框限制只能輸入數字
        document.querySelector('input[name="captcha"]').addEventListener('input', function(e) {
            // 移除非數字字符
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // 電話欄位限制只能輸入數字且最多10位
        document.querySelector('input[name="contact_phone"]').addEventListener('input', function(e) {
            // 移除非數字字符
            this.value = this.value.replace(/[^0-9]/g, '');
            // 限制最多10位數字
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
        });
        
        // 電話欄位按鍵限制（防止輸入非數字字符）
        document.querySelector('input[name="contact_phone"]').addEventListener('keypress', function(e) {
            // 只允許數字鍵、退格鍵、刪除鍵、方向鍵等
            const allowedKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'];
            if (!allowedKeys.includes(e.key) && (e.key < '0' || e.key > '9')) {
                e.preventDefault();
            }
        });
        
        // 表單驗證
        document.querySelector('form').addEventListener('submit', function(e) {
            const participants = document.querySelectorAll('input[name="participants[]"]:checked');
            const feedback = document.querySelectorAll('input[name="activity_feedback[]"]:checked');
            const captchaInput = document.querySelector('input[name="captcha"]');
            
            if (participants.length === 0) {
                e.preventDefault();
                alert('請至少選擇一個參與對象！');
                return;
            }
            
            if (feedback.length === 0) {
                e.preventDefault();
                alert('請至少選擇一個活動紀錄！');
                return;
            }
            
            if (captchaInput.value.length !== 4) {
                e.preventDefault();
                alert('請輸入4位數字的驗證碼！');
                captchaInput.focus();
                return;
            }
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
            newFileGroup.innerHTML = `
                <input type="file" name="files[]" accept="image/*,.zip,.rar,.pdf">
                <button type="button" class="remove-file-btn" onclick="removeFileInput(this)">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            container.appendChild(newFileGroup);
            updateRemoveButtons();
            
            // 聚焦到新增的檔案輸入框
            newFileGroup.querySelector('input[type="file"]').focus();
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
        
        // 頁面載入完成後聚焦到第一個輸入框
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[name="activity_date"]').focus();
            updateRemoveButtons(); // 初始化刪除按鈕狀態
        });
    </script>
    
    <?php include("share/footer.php"); ?>
</body>
</html>
