<?php
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
        $has_other_proof = isset($_POST['other_proof_checkbox']) && $_POST['other_proof_checkbox'] === '1';
        
        if ($has_other_proof && isset($_FILES['proof_evidence']) && $_FILES['proof_evidence']['error'] === UPLOAD_ERR_OK) {
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
            $email_data = [
                'recommender_name' => $_POST['recommender_name'],
                'recommender_student_id' => $_POST['recommender_student_id'],
                'recommender_department' => $_POST['recommender_department'],
                'student_name' => $_POST['student_name'],
                'student_school' => $_POST['student_school'],
                'student_grade' => $_POST['student_grade'],
                'submission_time' => date('Y-m-d H:i:s')
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

    <!-- 學號查詢功能 -->
    <div class="search-section">
      <h3><i class="fas fa-search"></i> 查詢推薦記錄</h3>
      <form method="POST" action="" class="search-form">
        <div class="search-row">
          <input type="text" name="search_student_id" placeholder="請輸入推薦人學號" 
                 value="<?php echo htmlspecialchars($search_student_id); ?>" required>
          <button type="submit" name="search_action" value="search" class="search-btn">
            <i class="fas fa-search"></i> 查詢
          </button>
        </div>
      </form>
    </div>

    <!-- 查詢結果 -->
    <?php if (!empty($search_results)): ?>
    <div class="search-results">
      <h4>查詢結果</h4>
      <div class="results-table">
        <table>
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
              <th>操作</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($search_results as $result): ?>
            <tr>
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
              <td><?php echo date('Y-m-d H:i', strtotime($result['created_at'])); ?></td>
              <td>
                <div class="action-buttons">
                  <button class="btn-status" onclick="updateStatus(<?php echo $result['id']; ?>, 'registered')" 
                          title="審核通過（被推薦學生）">
                    <i class="fas fa-check"></i>
                  </button>
                  <button class="btn-status" onclick="updateStatus(<?php echo $result['id']; ?>, 'rejected')" 
                          title="審核拒絕（被推薦學生）">
                    <i class="fas fa-times"></i>
                  </button>
                  <button class="btn-enrollment" onclick="updateEnrollment(<?php echo $result['id']; ?>, '已入學')" 
                          title="確認入學（被推薦學生）">
                    <i class="fas fa-graduation-cap"></i>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

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
                   value="<?php echo isset($_POST['recommender_phone']) ? htmlspecialchars($_POST['recommender_phone']) : ''; ?>" required>
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
              <option value="七年級" <?php echo (isset($_POST['student_grade']) && $_POST['student_grade'] === '七年級') ? 'selected' : ''; ?>>七年級</option>
              <option value="八年級" <?php echo (isset($_POST['student_grade']) && $_POST['student_grade'] === '八年級') ? 'selected' : ''; ?>>八年級</option>
              <option value="九年級" <?php echo (isset($_POST['student_grade']) && $_POST['student_grade'] === '九年級') ? 'selected' : ''; ?>>九年級</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="student_phone">聯絡電話 <span class="required">*</span></label>
            <input type="tel" id="student_phone" name="student_phone" 
                   value="<?php echo isset($_POST['student_phone']) ? htmlspecialchars($_POST['student_phone']) : ''; ?>" required>
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
  </div>
</div>
</div>

<?php include("share/footer.php"); ?>

<script>
// 檔案上傳區域互動功能
document.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.querySelector('input[name="other_proof_checkbox"]');
    const fileInput = document.getElementById('proof_evidence');
    const documentItem = document.querySelector('.document-item');
    
    // 控制檔案上傳區域的顯示/隱藏
    function updateFileUploadVisibility() {
        if (checkbox.checked) {
            fileInput.style.display = 'block';
            fileInput.disabled = false;
        } else {
            fileInput.style.display = 'none';
            fileInput.disabled = true;
            fileInput.value = ''; // 清空檔案選擇
        }
    }
    
    // 監聽複選框變化
    checkbox.addEventListener('change', updateFileUploadVisibility);
    
    // 初始化
    updateFileUploadVisibility();
    
    // 檔案選擇後的視覺反饋
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            this.style.borderColor = '#28a745';
            this.style.backgroundColor = '#f8fff9';
        } else {
            this.style.borderColor = '#ddd';
            this.style.backgroundColor = 'white';
        }
    });
=======
<style>
.action-buttons {
  display: flex;
  gap: 5px;
  justify-content: center;
}

.btn-status, .btn-enrollment {
  background: #667eea;
  color: white;
  border: none;
  padding: 8px 12px;
  border-radius: 5px;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.3s ease;
}

.btn-status:hover {
  background: #5a6fd8;
  transform: translateY(-2px);
}

.btn-enrollment {
  background: #28a745;
}

.btn-enrollment:hover {
  background: #218838;
  transform: translateY(-2px);
}

.btn-status:disabled, .btn-enrollment:disabled {
  background: #6c757d;
  cursor: not-allowed;
  transform: none;
}

.status-updating {
  opacity: 0.6;
  pointer-events: none;
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
// 更新推薦狀態
function updateStatus(recommendationId, status) {
  if (!confirm('確定要更新狀態嗎？')) {
    return;
  }
  
  const row = document.querySelector(`tr[data-id="${recommendationId}"]`);
  if (row) {
    row.classList.add('status-updating');
  }
  
  fetch('api/update_recommendation_status.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      recommendation_id: recommendationId,
      status: status
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showNotification('狀態更新成功！' + (data.email_sent ? ' 已發送通知郵件。' : ''), 'success');
      // 重新載入頁面以顯示更新後的狀態
      setTimeout(() => {
        location.reload();
      }, 2000);
    } else {
      showNotification('更新失敗：' + data.message, 'error');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showNotification('更新失敗，請稍後再試', 'error');
  })
  .finally(() => {
    if (row) {
      row.classList.remove('status-updating');
    }
  });
}

// 更新入學狀態
function updateEnrollment(recommendationId, enrollmentStatus) {
  if (!confirm('確定要更新入學狀態嗎？')) {
    return;
  }
  
  const row = document.querySelector(`tr[data-id="${recommendationId}"]`);
  if (row) {
    row.classList.add('status-updating');
  }
  
  fetch('api/update_recommendation_status.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      recommendation_id: recommendationId,
      enrollment_status: enrollmentStatus
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showNotification('入學狀態更新成功！' + (data.email_sent ? ' 已發送通知郵件。' : ''), 'success');
      // 重新載入頁面以顯示更新後的狀態
      setTimeout(() => {
        location.reload();
      }, 2000);
    } else {
      showNotification('更新失敗：' + data.message, 'error');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showNotification('更新失敗，請稍後再試', 'error');
  })
  .finally(() => {
    if (row) {
      row.classList.remove('status-updating');
    }
  });
}

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

// 為表格行添加data-id屬性
document.addEventListener('DOMContentLoaded', function() {
  const rows = document.querySelectorAll('.results-table tbody tr');
  rows.forEach((row, index) => {
    const idCell = row.querySelector('td:first-child');
    if (idCell) {
      row.setAttribute('data-id', idCell.textContent.trim());
    }
  });
});
</script>

</body>
</html>
