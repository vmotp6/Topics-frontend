<?php
require_once 'session_config.php';
require_once 'config.php';

// 訪客可使用（不做登入限制）

function ensureNewStudentBasicInfoTable($conn) {
  $sql = "CREATE TABLE IF NOT EXISTS new_student_basic_info (
      id INT AUTO_INCREMENT PRIMARY KEY,
      student_no VARCHAR(50) NOT NULL,
      student_name VARCHAR(100) NOT NULL,
      class_name VARCHAR(100) NOT NULL,
      department_id VARCHAR(50) DEFAULT NULL,
      enrollment_identity VARCHAR(100) DEFAULT NULL,
      birthday DATE DEFAULT NULL,
      gender VARCHAR(20) DEFAULT NULL,
      id_number VARCHAR(50) DEFAULT NULL,
      mobile VARCHAR(50) DEFAULT NULL,
      address VARCHAR(255) DEFAULT NULL,
      previous_school VARCHAR(150) DEFAULT NULL,
      photo_path VARCHAR(255) DEFAULT NULL,

      parent_title VARCHAR(50) DEFAULT NULL,
      parent_name VARCHAR(100) DEFAULT NULL,
      parent_birth_year VARCHAR(20) DEFAULT NULL,
      parent_occupation VARCHAR(100) DEFAULT NULL,
      parent_phone VARCHAR(50) DEFAULT NULL,
      parent_education VARCHAR(100) DEFAULT NULL,

      guardian_relation VARCHAR(50) DEFAULT NULL,
      guardian_name VARCHAR(100) DEFAULT NULL,
      guardian_phone VARCHAR(50) DEFAULT NULL,
      guardian_mobile VARCHAR(50) DEFAULT NULL,
      guardian_line VARCHAR(100) DEFAULT NULL,
      guardian_email VARCHAR(150) DEFAULT NULL,

      emergency_name VARCHAR(100) DEFAULT NULL,
      emergency_phone VARCHAR(50) DEFAULT NULL,
      emergency_mobile VARCHAR(50) DEFAULT NULL,

      is_indigenous TINYINT(1) DEFAULT 0,
      is_new_immigrant_child TINYINT(1) DEFAULT 0,
      is_overseas_chinese TINYINT(1) DEFAULT 0,

      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_student_no (student_no),
      INDEX idx_student_name (student_name),
      INDEX idx_created_at (created_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
  $conn->query($sql);
}

function safePost($key) {
  return isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
}

$message = '';
$messageType = '';

// 前一學校顯示文字（回填用）
$previous_school_display_value = safePost('previous_school_display');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $student_no = safePost('student_no');
    $student_name = safePost('student_name');
    $class_name = safePost('class_name');
    $department_id = safePost('department_id'); // 所在科系（departments.code）
    $enrollment_identity = safePost('enrollment_identity');
    $birthday = safePost('birthday');
    $gender = safePost('gender');
    $id_number = safePost('id_number');
    $mobile = safePost('mobile');
    $address = safePost('address');
    // previous_school 需存 school_data.school_code（因資料庫外鍵約束）
    $previous_school = safePost('previous_school'); // school_code
    $previous_school_display = safePost('previous_school_display'); // 顯示文字（學校名稱）

    $parent_title = safePost('parent_title');
    $parent_name = safePost('parent_name');
    $parent_birth_year = safePost('parent_birth_year');
    $parent_occupation = safePost('parent_occupation');
    $parent_phone = safePost('parent_phone');
    $parent_education = safePost('parent_education');

    $guardian_relation = safePost('guardian_relation');
    $guardian_name = safePost('guardian_name');
    $guardian_phone = safePost('guardian_phone');
    $guardian_mobile = safePost('guardian_mobile');
    $guardian_line = safePost('guardian_line');
    $guardian_email = safePost('guardian_email');

    $emergency_name = safePost('emergency_name');
    $emergency_phone = safePost('emergency_phone');
    $emergency_mobile = safePost('emergency_mobile');

    $is_indigenous = (safePost('is_indigenous') === '1') ? 1 : 0;
    $is_new_immigrant_child = (safePost('is_new_immigrant_child') === '1') ? 1 : 0;
    $is_overseas_chinese = (safePost('is_overseas_chinese') === '1') ? 1 : 0;

    // 基本驗證（必填）
    if ($student_no === '' || $student_name === '' || $class_name === '') {
      throw new Exception('請填寫：學號、姓名、班級');
    }
    if ($previous_school === '' || $id_number === '' || $mobile === '') {
      throw new Exception('請填寫：前一學校、身分證號、手機');
    }

    // 2 吋照片上傳（選填）
    $photo_path = null;
    if (isset($_FILES['photo']) && is_array($_FILES['photo']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
      if (($_FILES['photo']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new Exception('照片上傳失敗，請重試');
      }
      if (($_FILES['photo']['size'] ?? 0) > MAX_FILE_SIZE) {
        throw new Exception('照片檔案過大（上限 10MB）');
      }

      $original = (string)($_FILES['photo']['name'] ?? '');
      $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
      $allowed = ['jpg','jpeg','png'];
      if (!in_array($ext, $allowed, true)) {
        throw new Exception('照片格式僅支援 JPG / PNG');
      }

      $upload_dir = rtrim(UPLOAD_DIR, '/\\') . '/new_student_photos/';
      if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
      }

      $safe_base = preg_replace('/[^a-zA-Z0-9_-]/', '', $student_no);
      if ($safe_base === '') $safe_base = 'student';
      $filename = $safe_base . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
      $target_path = $upload_dir . $filename;

      if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
        throw new Exception('照片存檔失敗，請重試');
      }

      // 存相對路徑，方便之後展示
      $photo_path = $target_path;
    }

    $conn = getDatabaseConnection();
    if (!$conn) throw new Exception('資料庫連接失敗');
    ensureNewStudentBasicInfoTable($conn);

    // 驗證 previous_school（school_code）是否存在於 school_data，避免外鍵失敗
    $verify_code = $previous_school;
    if ($verify_code !== '') {
      $chk = $conn->prepare("SELECT school_code, name, city, district FROM school_data WHERE school_code = ? LIMIT 1");
      if ($chk) {
        $chk->bind_param("s", $verify_code);
        $chk->execute();
        $r = $chk->get_result();
        $row = $r ? $r->fetch_assoc() : null;
        $chk->close();
        if (!$row) {
          // 若 hidden code 不存在，嘗試用顯示文字找 name 對應 code（向後相容）
          if ($previous_school_display !== '') {
            $name_only = $previous_school_display;
            // 若格式是 "學校 (縣市區)"，取出學校名稱部分
            if (preg_match('/^(.+?)\s*\(.+\)$/u', $previous_school_display, $m)) {
              $name_only = trim($m[1]);
            }
            $chk2 = $conn->prepare("SELECT school_code, name, city, district FROM school_data WHERE name = ? LIMIT 1");
            if ($chk2) {
              $chk2->bind_param("s", $name_only);
              $chk2->execute();
              $r2 = $chk2->get_result();
              $row2 = $r2 ? $r2->fetch_assoc() : null;
              $chk2->close();
              if ($row2 && !empty($row2['school_code'])) {
                $previous_school = (string)$row2['school_code'];
              } else {
                throw new Exception('前一學校請從下拉選單選擇正確的學校（學校代碼無效）');
              }
            } else {
              throw new Exception('前一學校驗證失敗，請稍後再試');
            }
          } else {
            throw new Exception('前一學校請從下拉選單選擇正確的學校（學校代碼無效）');
          }
        } else {
          // 回填顯示文字（避免送出失敗時顯示空白）
          if ($previous_school_display_value === '') {
            $city = $row['city'] ?? '';
            $district = $row['district'] ?? '';
            $name = $row['name'] ?? '';
            $suffix = trim($city . $district);
            $previous_school_display_value = $suffix !== '' ? ($name . ' (' . $suffix . ')') : $name;
          }
        }
      } else {
        throw new Exception('前一學校驗證失敗，請稍後再試');
      }
    }

    // 科系驗證（允許空白；若填寫需存在於 departments）
    if ($department_id === '') {
      $department_id = null;
    } else {
      $conn_dept = getDatabaseConnection();
      if (!$conn_dept) {
        throw new Exception('科系驗證失敗，請稍後再試');
      }
      $dept_stmt = $conn_dept->prepare("SELECT code FROM departments WHERE code = ? LIMIT 1");
      if (!$dept_stmt) {
        throw new Exception('科系驗證失敗，請稍後再試');
      }
      $dept_stmt->bind_param("s", $department_id);
      $dept_stmt->execute();
      $dept_res = $dept_stmt->get_result();
      if (!$dept_res || $dept_res->num_rows === 0) {
        throw new Exception('所選科系不存在，請重新選擇');
      }
      $dept_stmt->close();
      $conn_dept->close();
    }

    $sql = "INSERT INTO new_student_basic_info (
        student_no, student_name, class_name, department_id, enrollment_identity, birthday, gender, id_number, mobile, address, previous_school, photo_path,
        parent_title, parent_name, parent_birth_year, parent_occupation, parent_phone, parent_education,
        guardian_relation, guardian_name, guardian_phone, guardian_mobile, guardian_line, guardian_email,
        emergency_name, emergency_phone, emergency_mobile,
        is_indigenous, is_new_immigrant_child, is_overseas_chinese
      ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?
      )";
    // debug: log the SQL in case there are syntax issues
    error_log('DEBUG: insert SQL: ' . $sql);
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
      // record database error for troubleshooting
      $err = $conn->error;
      error_log('DEBUG: prepare failed for insert into new_student_basic_info: ' . $err);
      throw new Exception('資料庫寫入準備失敗：' . $err);
    }

    $stmt->bind_param(
      "sssssssssssssssssssssssssssiii",
      $student_no, $student_name, $class_name, $department_id, $enrollment_identity, $birthday, $gender, $id_number, $mobile, $address, $previous_school, $photo_path,
      $parent_title, $parent_name, $parent_birth_year, $parent_occupation, $parent_phone, $parent_education,
      $guardian_relation, $guardian_name, $guardian_phone, $guardian_mobile, $guardian_line, $guardian_email,
      $emergency_name, $emergency_phone, $emergency_mobile,
      $is_indigenous, $is_new_immigrant_child, $is_overseas_chinese
    );
    if (!$stmt->execute()) {
      throw new Exception('寫入失敗，請稍後再試');
    }
    $stmt->close();
    $conn->close();

    header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
    exit;
  } catch (Exception $e) {
    $message = $e->getMessage();
    $messageType = 'error';
  }
}

if (isset($_GET['success']) && $_GET['success'] === '1') {
  $message = '資料已送出成功！';
  $messageType = 'success';
}

$department_id = isset($department_id) ? $department_id : safePost('department_id');

$departments = [];

$conn = getDatabaseConnection();
if ($conn) {
  $sql = "SELECT code, name FROM departments ORDER BY name";
  $res = $conn->query($sql);
  if ($res) {
    while ($row = $res->fetch_assoc()) {
      $departments[] = $row;
    }
  }
}

// GET / 或送出失敗時：如果只有 school_code，補回顯示文字
try {
  if ($previous_school_display_value === '') {
    $code = safePost('previous_school');
    if ($code !== '') {
      $conn_tmp = getDatabaseConnection();
      if ($conn_tmp) {
        $stmt = $conn_tmp->prepare("SELECT name, city, district FROM school_data WHERE school_code = ? LIMIT 1");
        if ($stmt) {
          $stmt->bind_param("s", $code);
          $stmt->execute();
          $res = $stmt->get_result();
          $row = $res ? $res->fetch_assoc() : null;
          $stmt->close();
          if ($row) {
            $suffix = trim(($row['city'] ?? '') . ($row['district'] ?? ''));
            $previous_school_display_value = $suffix !== '' ? (($row['name'] ?? '') . ' (' . $suffix . ')') : ($row['name'] ?? '');
          }
        }
        $conn_tmp->close();
      }
    }
  }
} catch (Exception $e) {
  // ignore
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>新生入學基本資料登錄 - 康寧大學招生平台</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    /* 避免固定導覽列遮住內容 */
    body.custom-spacing { padding-top: 140px !important; margin: 0; background: #f3f6fb; font-family: 'Microsoft JhengHei', sans-serif; }
    .page {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 16px 40px;
      box-sizing: border-box;
    }
    .top-spacer { height: 10px; }
    .hero {
      background: #667eea !important;
      border-radius: 18px;
      padding: 28px 18px;
      box-shadow: 0 10px 24px rgba(100, 120, 224, 0.14);
      margin-bottom: 14px;
      text-align: center;
      color: #fff;
    }
    .hero h2 { margin: 0; font-size: 34px; font-weight: 900; }
    .hero p { margin: 10px 0 0 0; font-size: 16px; color: rgba(34, 32, 32, 0.92); line-height: 1.7; }
    .card {
      background: #fff;
      border: 1px solid #e9ecef;
      border-radius: 12px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.06);
      padding: 20px;
    }
    .msg {
      border-radius: 10px;
      padding: 12px 14px;
      margin-bottom: 14px;
      font-weight: 700;
    }
    .msg.success { background: #f6ffed; border: 1px solid #b7eb8f; color: #237804; }
    .msg.error { background: #fff2f0; border: 1px solid #ffccc7; color: #a8071a; }

    /* 前一學校搜尋下拉 */
    .school-search-wrap { position: relative; }
    .school-results {
      position: absolute;
      left: 0;
      right: 0;
      top: calc(100% + 6px);
      z-index: 20;
      background: #fff;
      border: 1px solid #e6e6e6;
      box-shadow: 0 8px 18px rgba(0,0,0,0.08);
      max-height: 260px;
      overflow: auto;
      display: none;
    }
    .school-item {
      padding: 10px 12px;
      cursor: pointer;
      border-bottom: 1px solid #f0f0f0;
      font-size: 14px;
      line-height: 1.35;
    }
    .school-item:hover { background: #f5faff; }
    .school-item small { color: #8c8c8c; }

    .section-title {
      margin: 16px 0 10px 0;
      font-size: 16px;
      font-weight: 900;
      color: #003366;
      display: flex;
      gap: 8px;
      align-items: center;
    }
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 12px;
    }
    .req-star { color: #ff4d4f; font-weight: 900; }
    label { display: block; font-size: 14px; color: #666; margin-bottom: 6px; font-weight: 800; }
    input, select, textarea {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #ddd;
      border-radius: 0;
      box-sizing: border-box;
      background: #fff;
      font-size: 16px;
    }
    textarea { resize: vertical; }
    .actions {
      margin-top: 14px;
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      align-items: center;
      flex-wrap: nowrap;
    }
    .btn {
      border: none;
      border-radius: 0;
      padding: 10px 14px;
      cursor: pointer;
      font-weight: 900;
    }
    .btn.primary { background: #1890ff; color: #fff; }
    .btn.secondary { background: #f5f5f5; color: #333; border: 1px solid #e0e0e0; }
    .hint { font-size: 12px; color: #777; margin-top: 6px; }
  </style>
</head>
<body class="custom-spacing">
<?php include("share/header.php"); ?>
<main>
  <div class="page">
    <div class="top-spacer"></div>
    <section class="hero">
      <h2>新生入學基本資料登錄</h2>
      <p>請填寫新生入學基本資料與家長/監護人資訊（含 2 吋照片上傳）。</p>
    </section>

    <div class="card">
      <?php if (!empty($message)): ?>
        <div class="msg <?php echo htmlspecialchars($messageType); ?>">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <div class="section-title"><i class="fas fa-id-card"></i> 新生入學基本資料</div>
        <div class="grid">
          <div>
            <label>學號 <span class="req-star">*</span></label>
            <input name="student_no" value="<?php echo htmlspecialchars(safePost('student_no')); ?>" maxlength="10" required>
          </div>

          <div>
            <label>姓名 <span class="req-star">*</span></label>
            <input name="student_name" value="<?php echo htmlspecialchars(safePost('student_name')); ?>" required>
          </div>

          <div>
            <label>班級 <span class="req-star">*</span></label>
            <input name="class_name" value="<?php echo htmlspecialchars(safePost('class_name')); ?>" required>
          </div>

          <div>
              <label>所在科系</label>
              <select name="department_id" id="department_id">
                <option value="">請選擇科系</option>
                <?php foreach ($departments as $dept): 
                  if ($dept['code'] === 'AA') continue; 
                  $selected = ($department_id === $dept['code']) ? 'selected' : '';
                ?>
                  <option value="<?php echo htmlspecialchars($dept['code']); ?>" <?php echo $selected; ?>>
                    <?php echo htmlspecialchars($dept['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
          </div>

          <div>
            <label>在學身分</label>
            <?php $enroll_identity = safePost('enrollment_identity'); ?>
            <select name="enrollment_identity">
              <option value="" <?php echo $enroll_identity===''?'selected':''; ?>>請選擇</option>
              <option value="一般生" <?php echo $enroll_identity==='一般生'?'selected':''; ?>>一般生</option>
              <option value="特殊生" <?php echo $enroll_identity==='特殊生'?'selected':''; ?>>特殊生</option>
            </select>
          </div>

          <div>
            <label>生日</label>
            <input type="date" name="birthday" value="<?php echo htmlspecialchars(safePost('birthday')) ? date('Y-m-d', strtotime(safePost('birthday')) ) : ''; ?>" max="<?php echo date('Y-m-d'); ?>">
          </div>

          <div> 
            <label>性別</label>
            <select name="gender">
              <?php $g = safePost('gender'); ?>
              <option value="" <?php echo $g===''?'selected':''; ?>>請選擇</option>
              <option value="男" <?php echo $g==='男'?'selected':''; ?>>男</option>
              <option value="女" <?php echo $g==='女'?'selected':''; ?>>女</option>
              <option value="其他" <?php echo $g==='其他'?'selected':''; ?>>其他</option>
            </select>
          </div>

          <div>
            <label>身分證號 <span class="req-star">*</span></label>
            <input name="id_number" value="<?php echo htmlspecialchars(safePost('id_number')); ?>" maxlength="10" required>
          </div>

          <div>
            <label>手機 <span class="req-star">*</span></label>
            <input name="mobile" value="<?php echo htmlspecialchars(safePost('mobile')); ?>" maxlength="10" required>
          </div>

          <div style="grid-column: 1 / -1;">
            <label>通訊地址</label>
            <input name="address" value="<?php echo htmlspecialchars(safePost('address')); ?>">
          </div>

          <div>
            <label>前一學校 <span class="req-star">*</span></label>
            <div class="school-search-wrap">
              <input id="previous_school_display" name="previous_school_display" value="<?php echo htmlspecialchars($previous_school_display_value); ?>" placeholder="請輸入學校名稱並從下拉選擇" required autocomplete="off">
              <input type="hidden" id="previous_school" name="previous_school" value="<?php echo htmlspecialchars(safePost('previous_school')); ?>">
              <div id="previousSchoolResults" class="school-results" aria-label="school search results"></div>
            </div>
          </div> 


          <div>
            <label>2 吋照片（JPG/PNG）</label>
            <input type="file" name="photo" accept="image/jpeg,image/png">
            <div class="hint">單檔最大 10MB</div>
          </div>
        </div>

        <div class="section-title"><i class="fas fa-user-friends"></i> 家長或監護人資訊</div>
        <div class="grid">
          <div><label>稱謂</label><input name="parent_title" value="<?php echo htmlspecialchars(safePost('parent_title')); ?>" placeholder="例：父/母/監護人"></div>
          <div><label>姓名</label><input name="parent_name" value="<?php echo htmlspecialchars(safePost('parent_name')); ?>"></div>
          <div><label>年次</label><input name="parent_birth_year" value="<?php echo htmlspecialchars(safePost('parent_birth_year')); ?>" placeholder="例：70"></div>
          <div><label>職業</label><input name="parent_occupation" value="<?php echo htmlspecialchars(safePost('parent_occupation')); ?>"></div>
          <div><label>電話</label><input name="parent_phone" value="<?php echo htmlspecialchars(safePost('parent_phone')); ?>" maxlength="10"></div>
          <div><label>教育程度</label><input name="parent_education" value="<?php echo htmlspecialchars(safePost('parent_education')); ?>"></div>
        </div>

        <div class="section-title"><i class="fas fa-user-shield"></i> 監護人資料</div>
        <div class="grid">
          <div><label>關係</label><input name="guardian_relation" value="<?php echo htmlspecialchars(safePost('guardian_relation')); ?>"></div>
          <div><label>姓名</label><input name="guardian_name" value="<?php echo htmlspecialchars(safePost('guardian_name')); ?>"></div>
          <div><label>電話</label><input name="guardian_phone" value="<?php echo htmlspecialchars(safePost('guardian_phone')); ?>" maxlength="10"></div>
          <div><label>手機</label><input name="guardian_mobile" value="<?php echo htmlspecialchars(safePost('guardian_mobile')); ?>" maxlength="10"></div>
          <div><label>LINE</label><input name="guardian_line" value="<?php echo htmlspecialchars(safePost('guardian_line')); ?>"></div>
          <div><label>EMAIL</label><input type="email" name="guardian_email" value="<?php echo htmlspecialchars(safePost('guardian_email')); ?>"></div>
        </div>

        <div class="section-title"><i class="fas fa-phone"></i> 緊急聯絡人</div>
        <div class="grid">
          <div><label>姓名</label><input name="emergency_name" value="<?php echo htmlspecialchars(safePost('emergency_name')); ?>"></div>
          <div><label>電話</label><input name="emergency_phone" value="<?php echo htmlspecialchars(safePost('emergency_phone')); ?>" maxlength="10"></div>
          <div><label>手機</label><input name="emergency_mobile" value="<?php echo htmlspecialchars(safePost('emergency_mobile')); ?>" maxlength="10"></div>
        </div>

        <div class="section-title"><i class="fas fa-clipboard-check"></i> 個人身分資料</div>
        <div class="grid">
          <?php
            $v_indigenous = safePost('is_indigenous');
            if ($v_indigenous === '') $v_indigenous = '0';
            $v_new_immigrant = safePost('is_new_immigrant_child');
            if ($v_new_immigrant === '') $v_new_immigrant = '0';
            $v_overseas = safePost('is_overseas_chinese');
            if ($v_overseas === '') $v_overseas = '0';
          ?>

          <div>
            <label>本人是否為原住民</label>
            <div style="display:flex; gap:16px; align-items:center;">
              <label style="display:flex; gap:8px; align-items:center; font-weight:900; margin:0;">
                <input type="radio" name="is_indigenous" value="1" <?php echo ($v_indigenous === '1') ? 'checked' : ''; ?> style="width:auto;">
                是
              </label>
              <label style="display:flex; gap:8px; align-items:center; font-weight:900; margin:0;">
                <input type="radio" name="is_indigenous" value="0" <?php echo ($v_indigenous === '0') ? 'checked' : ''; ?> style="width:auto;">
                否
              </label>
            </div>
          </div>

          <div>
            <label>本人是否為新住民子女</label>
            <div style="display:flex; gap:16px; align-items:center;">
              <label style="display:flex; gap:8px; align-items:center; font-weight:900; margin:0;">
                <input type="radio" name="is_new_immigrant_child" value="1" <?php echo ($v_new_immigrant === '1') ? 'checked' : ''; ?> style="width:auto;">
                是
              </label>
              <label style="display:flex; gap:8px; align-items:center; font-weight:900; margin:0;">
                <input type="radio" name="is_new_immigrant_child" value="0" <?php echo ($v_new_immigrant === '0') ? 'checked' : ''; ?> style="width:auto;">
                否
              </label>
            </div>
          </div>

          <div>
            <label>本人是否為僑生</label>
            <div style="display:flex; gap:16px; align-items:center;">
              <label style="display:flex; gap:8px; align-items:center; font-weight:900; margin:0;">
                <input type="radio" name="is_overseas_chinese" value="1" <?php echo ($v_overseas === '1') ? 'checked' : ''; ?> style="width:auto;">
                是
              </label>
              <label style="display:flex; gap:8px; align-items:center; font-weight:900; margin:0;">
                <input type="radio" name="is_overseas_chinese" value="0" <?php echo ($v_overseas === '0') ? 'checked' : ''; ?> style="width:auto;">
                否
              </label>
            </div>
          </div>
        </div>

        <div class="actions">
          <button type="submit" class="btn primary"><i class="fas fa-paper-plane"></i> 送出</button>
          <button type="reset" class="btn secondary"><i class="fas fa-undo"></i> 清除</button>
        </div>
      </form>
    </div>
  </div>
</main>
<script>
  // 動態調整本頁 padding-top，避免固定導覽列（可能兩排）遮住內容
  (function() {
    function applyNavbarOffset() {
      const navbar = document.querySelector('.navbar');
      if (!navbar) return;
      const extraGap = 8;
      const h = navbar.offsetHeight || 0;
      if (h > 0) document.body.style.paddingTop = (h + extraGap) + 'px';
    }
    document.addEventListener('DOMContentLoaded', applyNavbarOffset);
    window.addEventListener('load', applyNavbarOffset);
    window.addEventListener('resize', applyNavbarOffset);
  })();

  // 前一學校：使用 school_data_api.php 搜尋並選擇 school_code（避免資料表外鍵錯誤）
  (function() {
    const input = document.getElementById('previous_school_display');
    const hidden = document.getElementById('previous_school');
    const results = document.getElementById('previousSchoolResults');
    if (!input || !hidden || !results) return;

    let lastKeyword = '';
    let inflight = null;

    function hideResults() {
      results.style.display = 'none';
      results.innerHTML = '';
    }

    function showResults(items) {
      if (!items || items.length === 0) {
        hideResults();
        return;
      }
      results.innerHTML = items.map(s => {
        const code = (s.school_code || '').replace(/"/g, '&quot;');
        const name = (s.name || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        const city = (s.city || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        const district = (s.district || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        const suffix = (city + district).trim();
        const display = suffix ? `${name} (${suffix})` : name;
        return `<div class="school-item" data-code="${code}" data-display="${display.replace(/"/g, '&quot;')}">
          <div>${display}</div>
          <small>代碼：${code}</small>
        </div>`;
      }).join('');
      results.style.display = 'block';
    }

    async function searchSchools(keyword) {
      if (keyword.length < 2) {
        hideResults();
        return;
      }
      // 只要使用者修改文字，就先清掉 hidden code（避免舊 code 被誤送出）
      hidden.value = '';

      lastKeyword = keyword;
      try {
        if (inflight) inflight.abort();
        inflight = new AbortController();
        const url = `api/school_data_api.php?action=search&keyword=${encodeURIComponent(keyword)}`;
        const resp = await fetch(url, { signal: inflight.signal });
        const data = await resp.json();
        // keyword 變了就忽略舊結果
        if (keyword !== lastKeyword) return;
        showResults((data && data.schools) ? data.schools.slice(0, 20) : []);
      } catch (e) {
        // ignore
        hideResults();
      }
    }

    let t = null;
    input.addEventListener('input', function() {
      const kw = (input.value || '').trim();
      clearTimeout(t);
      t = setTimeout(() => searchSchools(kw), 200);
    });

    results.addEventListener('click', function(e) {
      const item = e.target.closest('.school-item');
      if (!item) return;
      const code = item.getAttribute('data-code') || '';
      const display = item.getAttribute('data-display') || '';
      hidden.value = code;
      input.value = display;
      hideResults();
    });

    document.addEventListener('click', function(e) {
      if (e.target === input || results.contains(e.target)) return;
      hideResults();
    });
  })();
</script>
<?php include("share/footer.php"); ?>
</body>
</html>


