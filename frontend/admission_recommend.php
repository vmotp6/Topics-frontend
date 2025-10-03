<?php
// 確保 session 正確啟動
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

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
                $message = "未找到學號 " . htmlspecialchars($search_student_id) . " 的推薦記錄";
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
            'student_name', 'student_school', 'student_grade', 
            'student_phone', 'student_email', 'recommendation_reason'
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
                'student_grade' => '學生年級',
                'student_phone' => '學生聯絡電話',
                'student_email' => '學生電子郵件',
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
        
        if (!filter_var($_POST['student_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('被推薦學生電子郵件格式不正確');
        }
        
        // 插入資料
        $sql = "INSERT INTO admission_recommendations (
            recommender_name, recommender_student_id, recommender_grade, 
            recommender_department, recommender_phone, recommender_email,
            student_name, student_school, student_grade, 
            student_phone, student_email, student_line_id,
            recommendation_reason, student_interest, additional_info
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL 準備失敗: " . $conn->error);
        }
        
        // 準備變數，避免 bind_param 的引用問題
        $student_line_id = $_POST['student_line_id'] ?? '';
        $student_interest = $_POST['student_interest'] ?? '';
        $additional_info = $_POST['additional_info'] ?? '';
        
        $stmt->bind_param("sssssssssssssss",
            $_POST['recommender_name'],
            $_POST['recommender_student_id'],
            $_POST['recommender_grade'],
            $_POST['recommender_department'],
            $_POST['recommender_phone'],
            $_POST['recommender_email'],
            $_POST['student_name'],
            $_POST['student_school'],
            $_POST['student_grade'],
            $_POST['student_phone'],
            $_POST['student_email'],
            $student_line_id,
            $_POST['recommendation_reason'],
            $student_interest,
            $additional_info
        );
        
        if ($stmt->execute()) {
            $message = "推薦報名表單提交成功！我們會盡快處理您的推薦申請。";
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
</head>
<body>
<?php include("share/header.php"); ?>

<style>
/* 推薦報名頁面專用樣式 - 放在 header 之後避免衝突 */
.recommend-page-wrapper {
  font-family: 'Microsoft JhengHei', sans-serif;
  min-height: 100vh;
  margin: 0;
  padding: 100px 20px 0 20px; /* 移除底部 padding，讓 footer 自然顯示在底部 */
  display: block; /* 改為 block 佈局，不使用 flex */
}

.recommend-page-wrapper .recommend-container {
  max-width: 1000px; /* 增加表單寬度 */
  width: 100%;
  margin: 0 auto 40px auto; /* 增加底部 margin，為 footer 留空間 */
  background: white;
  border-radius: 20px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.1);
  overflow: visible; /* 改為 visible 確保內容不被隱藏 */
}

.recommend-page-wrapper .recommend-header {
  background: linear-gradient(135deg, #6c7aed 0%, #5a6fd8 100%);
  border-radius: 10px;
  color: white;
  padding: 40px;
  text-align: center;
}

.recommend-page-wrapper .recommend-header h1 {
  margin: 0;
  font-size: 2.5rem;
  font-weight: 700;
}

.recommend-page-wrapper .recommend-header p {
  margin: 10px 0 0 0;
  font-size: 1.1rem;
  opacity: 0.9;
}

.recommend-page-wrapper .form-container {
  padding: 40px 50px 40px 50px; /* 調整底部 padding，讓 footer 可以正確顯示 */
}

.recommend-page-wrapper .form-section {
  margin-bottom: 50px; /* 增加區塊間距 */
}

.recommend-page-wrapper .form-section h3 {
  color: #2c3e50;
  font-size: 1.3rem;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid #ecf0f1;
  display: flex;
  align-items: center;
  gap: 10px;
}

.recommend-page-wrapper .form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
}

.recommend-page-wrapper .form-group {
  margin-bottom: 20px;
}

.recommend-page-wrapper .form-group.full-width {
  grid-column: 1 / -1;
}

.recommend-page-wrapper label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #2c3e50;
}

.recommend-page-wrapper .required {
  color: #e74c3c;
}

.recommend-page-wrapper input, 
.recommend-page-wrapper select, 
.recommend-page-wrapper textarea {
  width: 100%;
  padding: 12px 15px;
  border: 2px solid #ecf0f1;
  border-radius: 10px;
  font-size: 1rem;
  transition: all 0.3s ease;
  box-sizing: border-box;
  background: white;
  font-family: 'Microsoft JhengHei', sans-serif;
}

.recommend-page-wrapper input:focus, 
.recommend-page-wrapper select:focus, 
.recommend-page-wrapper textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.recommend-page-wrapper textarea {
  resize: vertical;
  min-height: 100px;
}

.recommend-page-wrapper .submit-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 15px 40px;
  border: none;
  border-radius: 25px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: block;
  margin: 30px auto 0;
  font-family: 'Microsoft JhengHei', sans-serif;
}

.recommend-page-wrapper .submit-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
}

.recommend-page-wrapper .message {
  padding: 15px 20px;
  border-radius: 10px;
  margin-bottom: 20px;
  font-weight: 500;
}

.recommend-page-wrapper .message.success {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.recommend-page-wrapper .message.error {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

/* 查詢功能樣式 */
.recommend-page-wrapper .search-section {
  background: #f8f9fa;
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 30px;
  border: 1px solid #e9ecef;
}

.recommend-page-wrapper .search-section h3 {
  color: #2c3e50;
  font-size: 1.2rem;
  margin-bottom: 15px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.recommend-page-wrapper .search-form {
  margin-bottom: 0;
}

.recommend-page-wrapper .search-row {
  display: flex;
  gap: 10px;
  align-items: center;
}

.recommend-page-wrapper .search-row input {
  flex: 1;
  padding: 10px 15px;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  font-size: 1rem;
}

.recommend-page-wrapper .search-btn {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  width:45%;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  white-space: nowrap;
}

.recommend-page-wrapper .search-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
}

.recommend-page-wrapper .search-results {
  background: white;
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 30px;
  border: 1px solid #e9ecef;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.recommend-page-wrapper .search-results h4 {
  color: #2c3e50;
  margin-bottom: 15px;
  font-size: 1.1rem;
}

.recommend-page-wrapper .results-table {
  overflow-x: auto;
}

.recommend-page-wrapper .results-table table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.recommend-page-wrapper .results-table th,
.recommend-page-wrapper .results-table td {
  padding: 8px 12px;
  text-align: left;
  border-bottom: 1px solid #e9ecef;
}

.recommend-page-wrapper .results-table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #2c3e50;
}

.recommend-page-wrapper .results-table td small {
  color: #6c757d;
  font-size: 0.8rem;
}

.recommend-page-wrapper .status {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 500;
}

.recommend-page-wrapper .status-pending {
  background: #fff3cd;
  color: #856404;
}

.recommend-page-wrapper .status-contacted {
  background: #d1ecf1;
  color: #0c5460;
}

.recommend-page-wrapper .status-registered {
  background: #d4edda;
  color: #155724;
}

.recommend-page-wrapper .status-rejected {
  background: #f8d7da;
  color: #721c24;
}

.recommend-page-wrapper .enrollment-status {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 500;
}

.recommend-page-wrapper .enrollment-未入學 {
  background: #e2e3e5;
  color: #383d41;
}

.recommend-page-wrapper .enrollment-已入學 {
  background: #d4edda;
  color: #155724;
}

.recommend-page-wrapper .enrollment-放棄入學 {
  background: #f8d7da;
  color: #721c24;
}

/* 確保推薦報名頁面的 footer 正常顯示 */
.recommend-page-wrapper {
  position: relative;
}

/* 中等螢幕尺寸 (平板) */
@media (max-width: 1024px) and (min-width: 769px) {
  .recommend-page-wrapper .recommend-container {
    max-width: 900px; /* 中等螢幕使用稍小的寬度 */
  }
  
  .recommend-page-wrapper .form-container {
    padding: 35px 40px; /* 適中的 padding */
  }
}

@media (max-width: 768px) {
  .recommend-page-wrapper {
    padding: 120px 15px 0 15px; /* 手機版也移除底部 padding */
  }
  
  .recommend-page-wrapper .recommend-container {
    max-width: 100%; /* 手機版使用全寬 */
    margin: 0 auto 30px auto; /* 手機版調整底部 margin */
  }
  
  .recommend-page-wrapper .form-row {
    grid-template-columns: 1fr;
  }
  
  .recommend-page-wrapper .recommend-header h1 {
    font-size: 2rem;
  }
  
  .recommend-page-wrapper .form-container {
    padding: 30px 25px 30px 25px; /* 手機版調整底部 padding */
  }
  
  .recommend-page-wrapper .form-section {
    margin-bottom: 40px; /* 手機版減少區塊間距 */
  }
}
</style>

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
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <form method="POST" action="">
      <!-- 推薦人資訊 -->
      <div class="form-section">
        <h3><i class="fas fa-user"></i> 推薦人資訊（在校生）</h3>
        
        <div class="form-row">
          <div class="form-group">
            <label for="recommender_name">姓名 <span class="required">*</span></label>
            <input type="text" id="recommender_name" name="recommender_name" 
                   value="<?php echo isset($_POST['recommender_name']) ? htmlspecialchars($_POST['recommender_name']) : ''; ?>" required>
          </div>
          
          <div class="form-group">
            <label for="recommender_student_id">學號 <span class="required">*</span></label>
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
            <label for="student_grade">年級 <span class="required">*</span></label>
            <select id="student_grade" name="student_grade" required>
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
            <label for="student_email">電子郵件 <span class="required">*</span></label>
            <input type="email" id="student_email" name="student_email" 
                   value="<?php echo isset($_POST['student_email']) ? htmlspecialchars($_POST['student_email']) : ''; ?>" required>
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
            <?php foreach ($courses as $course): ?>
              <option value="<?php echo htmlspecialchars($course); ?>" 
                      <?php echo (isset($_POST['student_interest']) && $_POST['student_interest'] === $course) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($course); ?>
              </option>
            <?php endforeach; ?>
          </select>
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
</body>
</html>
