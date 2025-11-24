<?php
// 設定時區為台灣時區 (UTC+8)
date_default_timezone_set('Asia/Taipei');

// 載入 session 配置
require_once 'session_config.php';
require_once 'config.php';
require_once 'config/email_notification_config.php';

$message = '';
$messageType = '';
$courses = []; 
$search_results = [];
$search_student_id = '';

// 從資料庫撈取科系資料
try {
    $conn = getDatabaseConnection();
    $sql = "SELECT course_name FROM admission_courses ORDER BY course_name";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row['course_name'];
        }
    }
    $conn->close();
} catch (Exception $e) {
    // 如果資料庫查詢失敗，使用預設科系
    $courses = ['資訊管理科', '視光科', '幼兒保育科', '餐飲管理科', '觀光事業科'];
    error_log("無法從資料庫撈取科系資料: " . $e->getMessage());
}

// 處理通過 ID 查詢（用於後台管理系統）
$single_detail = null; // 用於儲存單筆詳細記錄
if (isset($_GET['id']) && !empty($_GET['id'])) {
    try {
        $search_id = intval($_GET['id']);
        $conn = getDatabaseConnection();
        $sql = "SELECT * FROM admission_recommendations WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $search_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $single_detail = $result->fetch_assoc(); // 儲存單筆記錄
            $search_results[] = $single_detail; // 同時加入搜尋結果陣列
            $message = "找到推薦記錄";
            $messageType = "success";
        } else {
            $message = "未找到 ID 為 " . htmlspecialchars($search_id) . " 的推薦記錄";
            $messageType = "error";
        }
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        $message = "查詢失敗：" . $e->getMessage();
        $messageType = "error";
    }
}

// 處理學號查詢
if ($_POST && isset($_POST['search_action']) && $_POST['search_action'] === 'search') {
    try {
        $search_student_id = trim($_POST['search_student_id']);
        if (!empty($search_student_id)) {
            $conn = getDatabaseConnection();
            $sql = "SELECT * FROM admission_recommendations WHERE recommender_student_id = ? ORDER BY created_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $search_student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $search_results[] = $row;
                }
                $message = "找到 " . count($search_results) . " 筆推薦記錄";
                $messageType = "success";
            } else {
                $message = "未找到學號或教師編號 " . htmlspecialchars($search_student_id) . " 的推薦記錄";
                $messageType = "error";
            }
            $stmt->close();
            $conn->close();
        } else {
            $message = "請輸入學號";
            $messageType = "error";
        }
    } catch (Exception $e) {
        $message = "查詢失敗：" . $e->getMessage();
        $messageType = "error";
    }
}

// 處理表單提交
if ($_POST && isset($_POST['submit_recommendation'])) {
    try {
        $conn = getDatabaseConnection();
        
        // 驗證必填欄位
        $required_fields = [
            'recommender_name', 'recommender_student_id', 'recommender_grade', 
            'recommender_department', 'recommender_phone', 'recommender_email',
            'student_name', 'student_school', 
            'student_phone', 'recommendation_reason'
        ];
        
        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            $field_names = [
                'recommender_name' => '推薦人姓名',
                'recommender_student_id' => '推薦人學號',
                'recommender_grade' => '推薦人年級',
                'recommender_department' => '推薦人科系',
                'recommender_phone' => '推薦人聯絡電話',
                'recommender_email' => '推薦人電子郵件',
                'student_name' => '學生姓名',
                'student_school' => '就讀學校',
                'student_phone' => '學生聯絡電話',
                'recommendation_reason' => '推薦理由'
            ];
            
            $missing_field_names = [];
            foreach ($missing_fields as $field) {
                $missing_field_names[] = $field_names[$field] ?? $field;
            }
            
            throw new Exception('請填寫所有必填欄位：' . implode('、', $missing_field_names));
        }
        
        // 驗證電子郵件格式
        if (!filter_var($_POST['recommender_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('推薦人電子郵件格式不正確');
        }
        
        // 如果學生有填寫電子郵件，則驗證格式
        if (!empty($_POST['student_email']) && !filter_var($_POST['student_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('被推薦學生電子郵件格式不正確');
        }
        
        // 驗證電話號碼格式（必須為10位數字）
        if (!empty($_POST['recommender_phone'])) {
            $phone = preg_replace('/[^0-9]/', '', $_POST['recommender_phone']); // 移除非數字字符
            if (strlen($phone) !== 10 || !preg_match('/^[0-9]{10}$/', $phone)) {
                throw new Exception('推薦人聯絡電話必須為10位數字');
            }
            $_POST['recommender_phone'] = $phone; // 標準化電話號碼格式
        }
        
        if (!empty($_POST['student_phone'])) {
            $phone = preg_replace('/[^0-9]/', '', $_POST['student_phone']); // 移除非數字字符
            if (strlen($phone) !== 10 || !preg_match('/^[0-9]{10}$/', $phone)) {
                throw new Exception('學生聯絡電話必須為10位數字');
            }
            $_POST['student_phone'] = $phone; // 標準化電話號碼格式
        }
        
        // 插入資料
        $sql = "INSERT INTO admission_recommendations (
            recommender_name, recommender_student_id, recommender_grade, 
            recommender_department, recommender_phone, recommender_email,
            student_name, student_school, student_grade, 
            student_phone, student_email, student_line_id,
            recommendation_reason, student_interest, additional_info, proof_evidence
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL 準備失敗: " . $conn->error);
        }
        
        // 處理檔案上傳
        $proof_evidence_path = '';
        
        if (isset($_FILES['proof_evidence']) && $_FILES['proof_evidence']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/proof_evidence/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_info = pathinfo($_FILES['proof_evidence']['name']);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
            $max_file_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array(strtolower($file_info['extension']), $allowed_extensions)) {
                throw new Exception('不支援的檔案格式。請上傳圖片檔（JPG, PNG, GIF）、PDF或Word文件。');
            }
            
            if ($_FILES['proof_evidence']['size'] > $max_file_size) {
                throw new Exception('檔案大小超過5MB限制。');
            }
            
            $new_filename = uniqid() . '_' . time() . '.' . $file_info['extension'];
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['proof_evidence']['tmp_name'], $upload_path)) {
                $proof_evidence_path = $upload_path;
            } else {
                throw new Exception('檔案上傳失敗，請重試。');
            }
        }
        
        // 準備變數，避免 bind_param 的引用問題
        $student_grade = $_POST['student_grade'] ?? '';
        $student_email = $_POST['student_email'] ?? '';
        $student_line_id = $_POST['student_line_id'] ?? '';
        $student_interest = $_POST['student_interest'] ?? '';
        $additional_info = $_POST['additional_info'] ?? '';
        
        $stmt->bind_param("ssssssssssssssss",
            $_POST['recommender_name'],
            $_POST['recommender_student_id'],
            $_POST['recommender_grade'],
            $_POST['recommender_department'],
            $_POST['recommender_phone'],
            $_POST['recommender_email'],
            $_POST['student_name'],
            $_POST['student_school'],
            $student_grade,
            $_POST['student_phone'],
            $student_email,
            $student_line_id,
            $_POST['recommendation_reason'],
            $student_interest,
            $additional_info,
            $proof_evidence_path
        );
        
        if ($stmt->execute()) {
            // 獲取新插入的記錄ID
            $recommendation_id = $conn->insert_id;
            
            // 準備郵件資料
            // 確保使用台灣時區顯示時間
            $email_data = [
                'recommender_name' => $_POST['recommender_name'],
                'recommender_student_id' => $_POST['recommender_student_id'],
                'recommender_department' => $_POST['recommender_department'],
                'student_name' => $_POST['student_name'],
                'student_school' => $_POST['student_school'],
                'student_grade' => $_POST['student_grade'],
                'submission_time' => date('Y-m-d H:i:s', time()) // 使用台灣時區 (UTC+8)，郵件模板會自動加上時區標示
            ];
            
            // 發送推薦成功通知郵件
            try {
                $email_sent = sendNotificationEmail(
                    $_POST['recommender_email'],
                    $_POST['recommender_name'],
                    'recommendation_success',
                    $email_data
                );
                
                if ($email_sent) {
                    // 記錄郵件發送成功
                    logNotification(
                        $recommendation_id,
                        'recommendation_success',
                        $_POST['recommender_email'],
                        'sent'
                    );
                } else {
                    // 記錄郵件發送失敗
                    logNotification(
                        $recommendation_id,
                        'recommendation_success',
                        $_POST['recommender_email'],
                        'failed'
                    );
                }
            } catch (Exception $email_error) {
                // 郵件發送失敗不影響主要流程，只記錄錯誤
                error_log("推薦成功郵件發送失敗: " . $email_error->getMessage());
                logNotification(
                    $recommendation_id,
                    'recommendation_success',
                    $_POST['recommender_email'],
                    'failed'
                );
            }
            
            $message = "推薦報名表單提交成功！我們會盡快處理您的推薦申請。確認郵件已發送至您的信箱。";
            $messageType = "success";
            // 清空表單
            $_POST = array();
        } else {
            throw new Exception("提交失敗: " . $stmt->error);
        }
        
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        $message = "提交失敗：" . $e->getMessage();
        $messageType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>招生推薦報名表</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/csp/admission_recommend.css">
</head>
<body>
<?php include("share/header.php"); ?>
<div class="recommend-page-wrapper">
<div class="recommend-container">
  <div class="recommend-header">
    <h1><i class="fas fa-user-friends"></i> 招生推薦報名表</h1>
    <p>在校生推薦國中生報名康寧大學五專部</p>
  </div>

  <div class="form-container">
    <?php if ($message): ?>
      <div class="message <?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <!-- 學號查詢功能（當通過ID查詢時隱藏） -->
    <?php if (!$single_detail): ?>
    <div class="search-section">
      <h3><i class="fas fa-search"></i> 查詢推薦記錄</h3>
      <form method="POST" action="" class="search-form">
        <div class="search-row">
          <input type="text" name="search_student_id" placeholder="請輸入推薦人學號或教師編號" 
                 value="<?php echo htmlspecialchars($search_student_id); ?>" required>
          <button type="submit" name="search_action" value="search" class="search-btn">
            <i class="fas fa-search"></i> 查詢
          </button>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <!-- 單筆詳細資訊（從後台查看詳情時顯示） -->
    <?php if ($single_detail): ?>
    <div class="detail-card">
      <div class="detail-header">
        <h3><i class="fas fa-file-alt"></i> 推薦記錄詳細資訊</h3>
        <a href="javascript:window.close()" class="close-btn" title="關閉視窗">
          <i class="fas fa-times"></i>
        </a>
      </div>
      
      <div class="detail-content">
        <!-- 推薦人資訊 -->
        <div class="detail-section">
          <h4><i class="fas fa-user"></i> 推薦人資訊</h4>
          <div class="detail-grid">
            <div class="detail-item">
              <div class="detail-label">姓名</div>
              <div class="detail-value"><?php echo htmlspecialchars($single_detail['recommender_name']); ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">學號/教師編號</div>
              <div class="detail-value"><?php echo htmlspecialchars($single_detail['recommender_student_id']); ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">年級</div>
              <div class="detail-value"><?php echo htmlspecialchars($single_detail['recommender_grade']); ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">科系</div>
              <div class="detail-value"><?php echo htmlspecialchars($single_detail['recommender_department']); ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">聯絡電話</div>
              <div class="detail-value"><?php echo htmlspecialchars($single_detail['recommender_phone']); ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">電子郵件</div>
              <div class="detail-value"><?php echo htmlspecialchars($single_detail['recommender_email']); ?></div>
            </div>
          </div>
        </div>

        <!-- 被推薦學生資訊 -->
        <div class="detail-section">
          <h4><i class="fas fa-graduation-cap"></i> 被推薦學生資訊</h4>
          <div class="detail-grid">
            <div class="detail-item">
              <div class="detail-label">姓名</div>
              <div class="detail-value"><?php echo htmlspecialchars($single_detail['student_name']); ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">電子郵件</div>
              <div class="detail-value"><?php echo !empty($single_detail['student_email']) ? htmlspecialchars($single_detail['student_email']) : '<span style="color: #8c8c8c;">未填寫</span>'; ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">就讀學校</div>
              <div class="detail-value"><?php echo htmlspecialchars($single_detail['student_school']); ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">年級</div>
              <div class="detail-value"><?php echo !empty($single_detail['student_grade']) ? htmlspecialchars($single_detail['student_grade']) : '<span style="color: #8c8c8c;">未填寫</span>'; ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">聯絡電話</div>
              <div class="detail-value"><?php echo htmlspecialchars($single_detail['student_phone']); ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">LINE ID</div>
              <div class="detail-value"><?php echo !empty($single_detail['student_line_id']) ? htmlspecialchars($single_detail['student_line_id']) : '<span style="color: #8c8c8c;">未填寫</span>'; ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">學生興趣</div>
              <div class="detail-value"><?php echo !empty($single_detail['student_interest']) ? htmlspecialchars($single_detail['student_interest']) : '<span style="color: #8c8c8c;">未填寫</span>'; ?></div>
            </div>
          </div>
        </div>

        <!-- 推薦資訊 -->
        <div class="detail-section">
          <h4><i class="fas fa-heart"></i> 推薦資訊</h4>
          <div class="detail-grid">
            <div class="detail-item" style="grid-column: 1 / -1;">
              <div class="detail-label">推薦理由</div>
              <div class="detail-value"><?php echo nl2br(htmlspecialchars($single_detail['recommendation_reason'])); ?></div>
            </div>
            <?php if (!empty($single_detail['additional_info'])): ?>
            <div class="detail-item" style="grid-column: 1 / -1;">
              <div class="detail-label">其他補充資訊</div>
              <div class="detail-value"><?php echo nl2br(htmlspecialchars($single_detail['additional_info'])); ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($single_detail['proof_evidence'])): ?>
            <div class="detail-item" style="grid-column: 1 / -1;">
              <div class="detail-label">證明文件</div>
              <div class="detail-value">
                <a href="<?php echo htmlspecialchars($single_detail['proof_evidence']); ?>" target="_blank" class="file-link">
                  <i class="fas fa-file"></i> 查看文件
                </a>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- 狀態資訊 -->
        <div class="detail-section">
          <h4><i class="fas fa-info-circle"></i> 狀態資訊</h4>
          <div class="detail-grid">
            <div class="detail-item">
              <div class="detail-label">處理狀態</div>
              <div class="detail-value">
                <span class="status status-<?php echo $single_detail['status'] ?? 'pending'; ?>">
                  <?php 
                  $status_text = [
                    'pending' => '待處理',
                    'contacted' => '已聯繫',
                    'registered' => '已報名',
                    'rejected' => '已拒絕'
                  ];
                  echo $status_text[$single_detail['status'] ?? 'pending'] ?? '待處理';
                  ?>
                </span>
              </div>
            </div>
            <div class="detail-item">
              <div class="detail-label">入學狀態</div>
              <div class="detail-value">
                <span class="enrollment-status enrollment-<?php echo $single_detail['enrollment_status'] ?? '未入學'; ?>">
                  <?php 
                  $enrollment_text = [
                    '未入學' => '未入學',
                    '已入學' => '已入學',
                    '放棄入學' => '放棄入學'
                  ];
                  echo $enrollment_text[$single_detail['enrollment_status'] ?? '未入學'] ?? '未入學';
                  ?>
                </span>
              </div>
            </div>
            <div class="detail-item">
              <div class="detail-label">建立時間</div>
              <div class="detail-value">
                <?php 
                $date = new DateTime($single_detail['created_at'], new DateTimeZone('UTC'));
                $date->setTimezone(new DateTimeZone('Asia/Taipei'));
                echo $date->format('Y-m-d H:i:s');
                ?>
              </div>
            </div>
            <?php if (!empty($single_detail['updated_at'])): ?>
            <div class="detail-item">
              <div class="detail-label">更新時間</div>
              <div class="detail-value">
                <?php 
                $date = new DateTime($single_detail['updated_at'], new DateTimeZone('UTC'));
                $date->setTimezone(new DateTimeZone('Asia/Taipei'));
                echo $date->format('Y-m-d H:i:s');
                ?>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- 查詢結果（多筆記錄時顯示） -->
    <?php if (!empty($search_results) && !$single_detail): ?>
    <div class="search-results">
      <h4>查詢結果</h4>
      
      <!-- 結果篩選功能 -->
      <div class="result-filter">
        <div class="filter-header">
          <h5><i class="fas fa-filter"></i> 進一步篩選結果</h5>
          <span class="result-count">共 <?php echo count($search_results); ?> 筆記錄</span>
        </div>
        <div class="filter-inputs">
          <div class="filter-group">
            <label for="filter_student_name">被推薦學生姓名</label>
            <input type="text" id="filter_student_name" placeholder="輸入學生姓名進行篩選">
          </div>
          <div class="filter-group">
            <label for="filter_school">國中學校</label>
            <input type="text" id="filter_school" placeholder="輸入學校名稱進行篩選">
          </div>
          
        </div>
        <div class="filter-note">
          <i class="fas fa-info-circle"></i> 輸入姓名或學校名稱可即時篩選結果
        </div>
      </div>
      
      <div class="results-table">
        <table id="resultsTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>推薦人</th>
              <th>被推薦學生</th>
              <th>學校</th>
              <th>年級</th>
              <th>狀態</th>
              <th>入學狀態</th>
              <th>建立時間</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($search_results as $result): ?>
            <tr class="result-row" 
                data-student-name="<?php echo htmlspecialchars($result['student_name']); ?>"
                data-school="<?php echo htmlspecialchars($result['student_school']); ?>">
              <td><?php echo $result['id']; ?></td>
              <td>
                <?php echo htmlspecialchars($result['recommender_name']); ?><br>
                <small><?php echo htmlspecialchars($result['recommender_student_id']); ?></small>
              </td>
              <td>
                <?php echo htmlspecialchars($result['student_name']); ?><br>
                <small><?php echo htmlspecialchars($result['student_email']); ?></small>
              </td>
              <td><?php echo htmlspecialchars($result['student_school']); ?></td>
              <td><?php echo htmlspecialchars($result['student_grade']); ?></td>
              <td>
                <span class="status status-<?php echo $result['status']; ?>">
                  <?php 
                  $status_text = [
                    'pending' => '待處理',
                    'contacted' => '已聯繫',
                    'registered' => '已報名',
                    'rejected' => '已拒絕'
                  ];
                  echo $status_text[$result['status']] ?? $result['status'];
                  ?>
                </span>
              </td>
              <td>
                <span class="enrollment-status enrollment-<?php echo $result['enrollment_status'] ?? '未入學'; ?>">
                  <?php 
                  $enrollment_text = [
                    '未入學' => '未入學',
                    '已入學' => '已入學',
                    '放棄入學' => '放棄入學'
                  ];
                  echo $enrollment_text[$result['enrollment_status'] ?? '未入學'] ?? '未入學';
                  ?>
                </span>
              </td>
              <td><?php 
                  // 確保使用台灣時區顯示時間
                  $date = new DateTime($result['created_at'], new DateTimeZone('UTC'));
                  $date->setTimezone(new DateTimeZone('Asia/Taipei'));
                  echo $date->format('Y-m-d H:i');
              ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- 當通過ID查詢時，不顯示表單 -->
    <?php if (!$single_detail): ?>
    <form method="POST" action="" enctype="multipart/form-data">
      <!-- 推薦人資訊 -->
      <div class="form-section">
        <h3><i class="fas fa-user"></i> 推薦人資訊（在校生或教職員）</h3>
        
        <div class="form-row">
          <div class="form-group">
            <label for="recommender_name">姓名 <span class="required">*</span></label>
            <input type="text" id="recommender_name" name="recommender_name" 
                   value="<?php echo isset($_POST['recommender_name']) ? htmlspecialchars($_POST['recommender_name']) : ''; ?>" required>
          </div>
          
          <div class="form-group">
            <label for="recommender_student_id">學號或教師編號 <span class="required">*</span></label>
            <input type="text" id="recommender_student_id" name="recommender_student_id" 
                   value="<?php echo isset($_POST['recommender_student_id']) ? htmlspecialchars($_POST['recommender_student_id']) : ''; ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="recommender_grade">年級 <span class="required">*</span></label>
            <select id="recommender_grade" name="recommender_grade" required>
              <option value="">請選擇年級</option>
              <option value="一年級" <?php echo (isset($_POST['recommender_grade']) && $_POST['recommender_grade'] === '一年級') ? 'selected' : ''; ?>>一年級</option>
              <option value="二年級" <?php echo (isset($_POST['recommender_grade']) && $_POST['recommender_grade'] === '二年級') ? 'selected' : ''; ?>>二年級</option>
              <option value="三年級" <?php echo (isset($_POST['recommender_grade']) && $_POST['recommender_grade'] === '三年級') ? 'selected' : ''; ?>>三年級</option>
              <option value="四年級" <?php echo (isset($_POST['recommender_grade']) && $_POST['recommender_grade'] === '四年級') ? 'selected' : ''; ?>>四年級</option>
              <option value="五年級" <?php echo (isset($_POST['recommender_grade']) && $_POST['recommender_grade'] === '五年級') ? 'selected' : ''; ?>>五年級</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="recommender_department">科系 <span class="required">*</span></label>
            <select id="recommender_department" name="recommender_department" required>
              <option value="">請選擇科系</option>
              <?php foreach ($courses as $course): ?>
                <option value="<?php echo htmlspecialchars($course); ?>" 
                        <?php echo (isset($_POST['recommender_department']) && $_POST['recommender_department'] === $course) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($course); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="recommender_phone">聯絡電話 <span class="required">*</span></label>
            <input type="tel" id="recommender_phone" name="recommender_phone" 
                   value="<?php echo isset($_POST['recommender_phone']) ? htmlspecialchars($_POST['recommender_phone']) : ''; ?>" 
                   pattern="[0-9]{10}" maxlength="10" placeholder="請輸入電話號碼" required>
            <small class="phone-hint" style="display: none; color: #d32f2f; font-size: 12px; margin-top: 4px;">電話號碼錯誤</small>
          </div>
          
          <div class="form-group">
            <label for="recommender_email">電子郵件 <span class="required">*</span></label>
            <input type="email" id="recommender_email" name="recommender_email" 
                   value="<?php echo isset($_POST['recommender_email']) ? htmlspecialchars($_POST['recommender_email']) : ''; ?>" required>
          </div>
        </div>
      </div>

      <!-- 被推薦學生資訊 -->
      <div class="form-section">
        <h3><i class="fas fa-graduation-cap"></i> 被推薦學生資訊（國中生）</h3>
        
        <div class="form-row">
          <div class="form-group">
            <label for="student_name">學生姓名 <span class="required">*</span></label>
            <input type="text" id="student_name" name="student_name" 
                   value="<?php echo isset($_POST['student_name']) ? htmlspecialchars($_POST['student_name']) : ''; ?>" required>
          </div>
          
          <div class="form-group">
            <label for="student_school">就讀學校 <span class="required">*</span></label>
            <input type="text" id="student_school" name="student_school" 
                   value="<?php echo isset($_POST['student_school']) ? htmlspecialchars($_POST['student_school']) : ''; ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="student_grade">年級（選填）</label>
            <select id="student_grade" name="student_grade">
              <option value="">請選擇年級</option>
              <option value="國三" <?php echo (isset($_POST['student_grade']) && $_POST['student_grade'] === '國三') ? 'selected' : ''; ?>>國三</option>
              <option value="已畢業" <?php echo (isset($_POST['student_grade']) && $_POST['student_grade'] === '已畢業') ? 'selected' : ''; ?>>已畢業</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="student_phone">聯絡電話 <span class="required">*</span></label>
            <input type="tel" id="student_phone" name="student_phone" 
                   value="<?php echo isset($_POST['student_phone']) ? htmlspecialchars($_POST['student_phone']) : ''; ?>" 
                   pattern="[0-9]{10}" maxlength="10" placeholder="請輸入電話號碼" required>
            <small class="phone-hint" style="display: none; color: #d32f2f; font-size: 12px; margin-top: 4px;">電話號碼錯誤</small>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="student_email">電子郵件（選填）</label>
            <input type="email" id="student_email" name="student_email" 
                   value="<?php echo isset($_POST['student_email']) ? htmlspecialchars($_POST['student_email']) : ''; ?>">
          </div>
          
          <div class="form-group">
            <label for="student_line_id">LINE ID（選填）</label>
            <input type="text" id="student_line_id" name="student_line_id" 
                   value="<?php echo isset($_POST['student_line_id']) ? htmlspecialchars($_POST['student_line_id']) : ''; ?>">
          </div>
        </div>
      </div>

      <!-- 推薦資訊 -->
      <div class="form-section">
        <h3><i class="fas fa-heart"></i> 推薦資訊</h3>
        
        <div class="form-group full-width">
          <label for="recommendation_reason">推薦理由 <span class="required">*</span></label>
          <textarea id="recommendation_reason" name="recommendation_reason" 
                    placeholder="請詳細說明推薦這位學生的理由，例如：學習態度、特殊才能、品格表現等..." required><?php echo isset($_POST['recommendation_reason']) ? htmlspecialchars($_POST['recommendation_reason']) : ''; ?></textarea>
        </div>

        <div class="form-group full-width">
          <label for="student_interest">學生興趣領域（選填）</label>
          <select id="student_interest" name="student_interest">
            <option value="">請選擇興趣領域</option>
            <option value="不限定" <?php echo (isset($_POST['student_interest']) && $_POST['student_interest'] === '不限定') ? 'selected' : ''; ?>>不限定</option>
            <?php foreach ($courses as $course): ?>
              <option value="<?php echo htmlspecialchars($course); ?>" 
                      <?php echo (isset($_POST['student_interest']) && $_POST['student_interest'] === $course) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($course); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group full-width">
          <div class="document-item">
            <label>其他相關證明文件</label>
            <input type="file" id="proof_evidence" name="proof_evidence" 
                   accept="image/*,.pdf,.doc,.docx" class="file-input">
          </div>
          <div class="note">
            <i class="fas fa-info-circle"></i> 請上傳相關證明文件(支援PDF、JPG、PNG格式,單個文件大小不超過5MB)
          </div>
        </div>

        <div class="form-group full-width">
          <label for="additional_info">其他補充資訊（選填）</label>
          <textarea id="additional_info" name="additional_info" 
                    placeholder="其他您認為重要的資訊，例如：家庭背景、特殊經歷、未來規劃等..."><?php echo isset($_POST['additional_info']) ? htmlspecialchars($_POST['additional_info']) : ''; ?></textarea>
        </div>
      </div>

      <button type="submit" name="submit_recommendation" class="submit-btn">
        <i class="fas fa-paper-plane"></i> 提交推薦申請
      </button>
    </form>
    <?php endif; ?>
  </div>
</div>
</div>

<?php include("share/footer.php"); ?>

<script>
// 電話號碼驗證功能
document.addEventListener('DOMContentLoaded', function() {
    // 獲取兩個電話輸入框
    const recommenderPhone = document.getElementById('recommender_phone');
    const studentPhone = document.getElementById('student_phone');
    
    // 電話號碼驗證函數
    function setupPhoneValidation(phoneInput) {
        if (!phoneInput) return;
        
        const hint = phoneInput.nextElementSibling;
        
        // 驗證函數
        function validatePhone() {
            const value = phoneInput.value.trim();
            if (value.length > 0 && value.length !== 10) {
                // 顯示錯誤狀態
                if (hint && hint.classList.contains('phone-hint')) {
                    hint.style.display = 'block';
                }
                phoneInput.style.borderColor = '#d32f2f';
                phoneInput.style.borderWidth = '2px';
                phoneInput.classList.add('phone-error');
            } else {
                // 清除錯誤狀態
                if (hint && hint.classList.contains('phone-hint')) {
                    hint.style.display = 'none';
                }
                phoneInput.style.borderColor = '';
                phoneInput.style.borderWidth = '';
                phoneInput.classList.remove('phone-error');
            }
        }
        
        // 只允許輸入數字
        phoneInput.addEventListener('input', function(e) {
            // 移除非數字字符
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // 限制最大長度為10
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
            
            // 即時驗證
            validatePhone();
        });
        
        // 失去焦點時驗證
        phoneInput.addEventListener('blur', function() {
            validatePhone();
        });
        
        // 獲得焦點時也檢查（處理初始值）
        phoneInput.addEventListener('focus', function() {
            validatePhone();
        });
        
        // 頁面載入時檢查初始值
        validatePhone();
    }
    
    // 設置兩個電話輸入框的驗證
    setupPhoneValidation(recommenderPhone);
    setupPhoneValidation(studentPhone);
    
    // 表單提交驗證
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            let hasError = false;
            let firstErrorField = null;
            
            // 驗證推薦人電話
            if (recommenderPhone) {
                const phoneValue = recommenderPhone.value.trim();
                if (!phoneValue) {
                    hasError = true;
                    if (!firstErrorField) firstErrorField = recommenderPhone;
                    recommenderPhone.style.borderColor = '#d32f2f';
                    if (recommenderPhone.nextElementSibling && recommenderPhone.nextElementSibling.classList.contains('phone-hint')) {
                        recommenderPhone.nextElementSibling.textContent = '請填寫聯絡電話';
                        recommenderPhone.nextElementSibling.style.display = 'block';
                    }
                } else if (phoneValue.length !== 10 || !/^[0-9]{10}$/.test(phoneValue)) {
                    e.preventDefault();
                    hasError = true;
                    if (!firstErrorField) firstErrorField = recommenderPhone;
                    recommenderPhone.style.borderColor = '#d32f2f';
                    recommenderPhone.focus();
                    if (recommenderPhone.nextElementSibling && recommenderPhone.nextElementSibling.classList.contains('phone-hint')) {
                        recommenderPhone.nextElementSibling.textContent = '電話號碼必須為10位數字';
                        recommenderPhone.nextElementSibling.style.display = 'block';
                    }
                    alert('推薦人聯絡電話必須為10位數字！');
                    return false;
                } else {
                    recommenderPhone.style.borderColor = '';
                    if (recommenderPhone.nextElementSibling && recommenderPhone.nextElementSibling.classList.contains('phone-hint')) {
                        recommenderPhone.nextElementSibling.style.display = 'none';
                    }
                }
            }
            
            // 驗證學生電話
            if (studentPhone) {
                const phoneValue = studentPhone.value.trim();
                if (!phoneValue) {
                    hasError = true;
                    if (!firstErrorField) firstErrorField = studentPhone;
                    studentPhone.style.borderColor = '#d32f2f';
                    if (studentPhone.nextElementSibling && studentPhone.nextElementSibling.classList.contains('phone-hint')) {
                        studentPhone.nextElementSibling.textContent = '請填寫聯絡電話';
                        studentPhone.nextElementSibling.style.display = 'block';
                    }
                } else if (phoneValue.length !== 10 || !/^[0-9]{10}$/.test(phoneValue)) {
                    e.preventDefault();
                    hasError = true;
                    if (!firstErrorField) firstErrorField = studentPhone;
                    studentPhone.style.borderColor = '#d32f2f';
                    studentPhone.focus();
                    if (studentPhone.nextElementSibling && studentPhone.nextElementSibling.classList.contains('phone-hint')) {
                        studentPhone.nextElementSibling.textContent = '電話號碼必須為10位數字';
                        studentPhone.nextElementSibling.style.display = 'block';
                    }
                    alert('學生聯絡電話必須為10位數字！');
                    return false;
                } else {
                    studentPhone.style.borderColor = '';
                    if (studentPhone.nextElementSibling && studentPhone.nextElementSibling.classList.contains('phone-hint')) {
                        studentPhone.nextElementSibling.style.display = 'none';
                    }
                }
            }
            
            if (hasError && firstErrorField) {
                e.preventDefault();
                firstErrorField.focus();
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
        });
    }
});

// 檔案上傳區域互動功能
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('proof_evidence');
    const documentItem = document.querySelector('.document-item');
    
    // 檔案選擇後的視覺反饋
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                this.style.borderColor = '#28a745';
                this.style.backgroundColor = '#f8fff9';
            } else {
                this.style.borderColor = '#ddd';
                this.style.backgroundColor = 'white';
            }
        });
    }
});
</script>

<style>
/* 電話號碼錯誤樣式 */
.phone-error {
  border-color: #d32f2f !important;
  border-width: 2px !important;
  box-shadow: 0 0 0 3px rgba(211, 47, 47, 0.1) !important;
}

.phone-hint {
  display: block;
  color: #d32f2f;
  font-size: 12px;
  margin-top: 4px;
  font-weight: 500;
}

/* 詳細資訊卡片樣式 */
.detail-card {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  margin-bottom: 30px;
  overflow: hidden;
}

.detail-header {
  background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
  color: white;
  padding: 20px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.detail-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.detail-header h3 i {
  margin-right: 8px;
}

.close-btn {
  color: white;
  font-size: 20px;
  text-decoration: none;
  padding: 8px 12px;
  border-radius: 4px;
  transition: background 0.3s;
}

.close-btn:hover {
  background: rgba(255,255,255,0.2);
}

.detail-content {
  padding: 24px;
}

.detail-section {
  margin-bottom: 30px;
  padding-bottom: 30px;
  border-bottom: 1px solid #e9ecef;
}

.detail-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.detail-section h4 {
  color: #495057;
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid #e9ecef;
}

.detail-section h4 i {
  margin-right: 8px;
  color: #667eea;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.detail-label {
  font-weight: 600;
  color: #6c757d;
  font-size: 14px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-value {
  color: #212529;
  font-size: 16px;
  line-height: 1.6;
  word-break: break-word;
}

.detail-value .status,
.detail-value .enrollment-status {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 500;
}

.file-link {
  color: #007bff;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  border: 1px solid #007bff;
  border-radius: 4px;
  transition: all 0.3s;
}

.file-link:hover {
  background: #007bff;
  color: white;
}

/* 狀態標籤樣式 */
.status-pending {
  background: #fff7e6;
  color: #d46b08;
  border: 1px solid #ffd591;
}

.status-contacted {
  background: #e6f7ff;
  color: #0958d9;
  border: 1px solid #91d5ff;
}

.status-registered {
  background: #f6ffed;
  color: #52c41a;
  border: 1px solid #b7eb8f;
}

.status-rejected {
  background: #fff2f0;
  color: #cf1322;
  border: 1px solid #ffa39e;
}

.enrollment-未入學 {
  background: #f5f5f5;
  color: #8c8c8c;
}

.enrollment-已入學 {
  background: #f6ffed;
  color: #52c41a;
}

.enrollment-放棄入學 {
  background: #fff7e6;
  color: #fa8c16;
}

/* 結果篩選樣式 */
.result-filter {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
}

.filter-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.filter-header h5 {
  margin: 0;
  color: #495057;
  font-size: 16px;
}

.result-count {
  background: #007bff;
  color: white;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.filter-inputs {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 15px;
  align-items: end;
  margin-bottom: 10px;
}

.filter-group {
  display: flex;
  flex-direction: column;
}

.filter-group label {
  font-weight: 600;
  margin-bottom: 5px;
  color: #333;
  font-size: 14px;
}

.filter-group input {
  padding: 8px 12px;
  border: 2px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.3s ease;
}

.filter-group input:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.filter-btn, .clear-filter-btn {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}

.filter-btn {
  background: #28a745;
  color: white;
}

.filter-btn:hover {
  background: #218838;
  transform: translateY(-1px);
}

.clear-filter-btn {
  background: #6c757d;
  color: white;
}

.clear-filter-btn:hover {
  background: #545b62;
  transform: translateY(-1px);
}

.filter-note {
  background: #e7f3ff;
  border: 1px solid #b3d9ff;
  border-radius: 6px;
  padding: 10px 12px;
  font-size: 12px;
  color: #0066cc;
  display: flex;
  align-items: center;
  gap: 6px;
}

.filter-note i {
  color: #007bff;
}

/* 隱藏的行 */
.result-row.hidden {
  display: none;
}

/* 響應式設計 */
@media (max-width: 768px) {
  .filter-inputs {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .filter-group:last-child {
    display: flex;
    flex-direction: row;
    gap: 10px;
  }
  
  .filter-btn, .clear-filter-btn {
    flex: 1;
    justify-content: center;
  }
}

.notification-toast {
  position: fixed;
  top: 20px;
  right: 20px;
  background: #28a745;
  color: white;
  padding: 15px 20px;
  border-radius: 5px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
  z-index: 1000;
  transform: translateX(100%);
  transition: transform 0.3s ease;
}

.notification-toast.show {
  transform: translateX(0);
}

.notification-toast.error {
  background: #dc3545;
}
</style>

<script>

// 顯示通知
function showNotification(message, type = 'success') {
  const toast = document.createElement('div');
  toast.className = `notification-toast ${type}`;
  toast.textContent = message;
  
  document.body.appendChild(toast);
  
  // 顯示動畫
  setTimeout(() => {
    toast.classList.add('show');
  }, 100);
  
  // 自動隱藏
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => {
      document.body.removeChild(toast);
    }, 300);
  }, 3000);
}

// 篩選結果功能
function applyFilter() {
  const studentNameFilter = document.getElementById('filter_student_name').value.toLowerCase().trim();
  const schoolFilter = document.getElementById('filter_school').value.toLowerCase().trim();
  const rows = document.querySelectorAll('.result-row');
  let visibleCount = 0;
  
  rows.forEach(row => {
    const studentName = row.getAttribute('data-student-name').toLowerCase();
    const school = row.getAttribute('data-school').toLowerCase();
    
    let showRow = true;
    
    // 檢查學生姓名篩選
    if (studentNameFilter && !studentName.includes(studentNameFilter)) {
      showRow = false;
    }
    
    // 檢查學校篩選
    if (schoolFilter && !school.includes(schoolFilter)) {
      showRow = false;
    }
    
    if (showRow) {
      row.classList.remove('hidden');
      visibleCount++;
    } else {
      row.classList.add('hidden');
    }
  });
  
  // 更新結果計數
  updateResultCount(visibleCount);
  

}

// 清除篩選
function clearFilter() {
  document.getElementById('filter_student_name').value = '';
  document.getElementById('filter_school').value = '';
  
  const rows = document.querySelectorAll('.result-row');
  rows.forEach(row => {
    row.classList.remove('hidden');
  });
  
  // 恢復原始計數
  updateResultCount(rows.length);
  showNotification('篩選已清除', 'success');
}

// 更新結果計數
function updateResultCount(count) {
  const resultCountElement = document.querySelector('.result-count');
  if (resultCountElement) {
    resultCountElement.textContent = `共 ${count} 筆記錄`;
  }
}

// 即時篩選功能
function setupRealTimeFilter() {
  const studentNameInput = document.getElementById('filter_student_name');
  const schoolInput = document.getElementById('filter_school');
  
  if (studentNameInput) {
    studentNameInput.addEventListener('input', applyFilter);
  }
  
  if (schoolInput) {
    schoolInput.addEventListener('input', applyFilter);
  }
}

// 為表格行添加data-id屬性
document.addEventListener('DOMContentLoaded', function() {
  const rows = document.querySelectorAll('.results-table tbody tr');
  rows.forEach((row, index) => {
    const idCell = row.querySelector('td:first-child');
    if (idCell) {
      row.setAttribute('data-id', idCell.textContent.trim());
    }
  });
  
  // 設置即時篩選
  setupRealTimeFilter();
});
</script>

<!-- 浮動助手組件 -->
<?php include("share/chat_widget.php"); ?>
<?php include("share/ai_widget.php"); ?>
</body>
</html>
