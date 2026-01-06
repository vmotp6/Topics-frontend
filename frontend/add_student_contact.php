<?php
// 新增學生聯絡資訊
require_once 'session_config.php';
require_once 'config.php';

// 角色驗證：教師/行政/主任/IM
$allowed_roles = ['老師', 'TEA', 'STA', '學校行政人員', 'AA', 'DI', 'IM'];
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true &&
              isset($_SESSION['role']) && in_array($_SESSION['role'], $allowed_roles, true);

if (!$isLoggedIn) {
    header("Location: index.php");
    exit();
}

$errors = [];
$success_message = '';

// 建立資料表（若不存在）
function ensureStudentContactTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS student_contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        junior_high VARCHAR(150) DEFAULT NULL,
        interest_department VARCHAR(150) DEFAULT NULL,
        activity_source VARCHAR(150) DEFAULT NULL,
        contact_teacher VARCHAR(150) DEFAULT NULL,
        status VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_name (name),
        INDEX idx_junior_high (junior_high),
        INDEX idx_interest_department (interest_department),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $junior_high = trim($_POST['junior_high'] ?? '');
    $interest_department = trim($_POST['interest_department'] ?? '');
    $activity_source = trim($_POST['activity_source'] ?? '');
    $contact_teacher = trim($_POST['contact_teacher'] ?? '');
    $status = trim($_POST['status'] ?? '');

    if ($name === '') {
        $errors[] = '姓名為必填欄位';
    }

    if (empty($errors)) {
        try {
            $conn = getDatabaseConnection();
            ensureStudentContactTable($conn);

            $stmt = $conn->prepare("INSERT INTO student_contacts (name, junior_high, interest_department, activity_source, contact_teacher, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $name, $junior_high, $interest_department, $activity_source, $contact_teacher, $status);
            if ($stmt->execute()) {
                $success_message = '已成功新增學生聯絡資訊。';
                // 清空欄位
                $name = $junior_high = $interest_department = $activity_source = $contact_teacher = $status = '';
            } else {
                $errors[] = '寫入資料庫時發生錯誤，請稍後再試。';
            }
            $stmt->close();
            $conn->close();
        } catch (Exception $e) {
            $errors[] = '系統錯誤：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>新增學生聯絡資訊 - 康寧大學招生平台</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #1890ff;
      --text-color: #262626;
      --text-secondary-color: #8c8c8c;
      --border-color: #f0f0f0;
      --background-color: #f0f2f5;
      --card-background: #fff;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif, 'Microsoft JhengHei';
      background: var(--background-color);
      padding-top: 100px;
      color: var(--text-color);
    }
    .container {
      max-width: 960px;
      margin: 32px auto 48px auto;
      padding: 0 16px;
    }
    .card {
      background: var(--card-background);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      padding: 24px;
    }
    .page-title {
      font-size: 24px;
      font-weight: 700;
      color: var(--text-color);
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .page-subtitle {
      color: var(--text-secondary-color);
      margin-bottom: 20px;
      font-size: 14px;
    }
    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 16px;
      margin-bottom: 16px;
    }
    .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    label {
      font-weight: 600;
      color: #333;
      font-size: 14px;
    }
    input, select {
      padding: 10px 12px;
      border: 1px solid #d9d9d9;
      border-radius: 8px;
      font-size: 14px;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    input:focus, select:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 2px rgba(24,144,255,0.2);
    }
    .btn-row {
      display: flex;
      gap: 12px;
      margin-top: 12px;
    }
    .btn {
      padding: 10px 18px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      font-size: 14px;
      transition: transform 0.15s, box-shadow 0.15s;
    }
    .btn-primary {
      background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%);
      color: white;
      box-shadow: 0 4px 10px rgba(24,144,255,0.25);
    }
    .btn-primary:hover { transform: translateY(-1px); }
    .btn-secondary {
      background: #f5f5f5;
      color: #333;
      border: 1px solid #e0e0e0;
    }
    .alert {
      padding: 12px 16px;
      border-radius: 8px;
      margin-bottom: 16px;
      font-size: 14px;
    }
    .alert-success {
      background: #f6ffed;
      color: #52c41a;
      border: 1px solid #b7eb8f;
    }
    .alert-error {
      background: #fff2f0;
      color: #ff4d4f;
      border: 1px solid #ffccc7;
    }
  </style>
</head>
<body>
<?php include("share/header.php"); ?>
<main>
  <div class="container">
    <div class="card">
      <div class="page-title"><i class="fas fa-user-plus"></i> 新增學生聯絡資訊</div>
      <div class="page-subtitle">填寫基本聯絡資訊後送出，即可建立一筆學生聯絡記錄。</div>

      <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
      <?php endif; ?>
      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <i class="fas fa-exclamation-circle"></i>
          <?php echo htmlspecialchars(implode('；', $errors)); ?>
        </div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-grid">
          <div class="form-group">
            <label for="name">姓名<span style="color:#ff4d4f;">*</span></label>
            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($name ?? ''); ?>" placeholder="請輸入學生姓名">
          </div>
          <div class="form-group">
            <label for="junior_high">國中</label>
            <input type="text" id="junior_high" name="junior_high" value="<?php echo htmlspecialchars($junior_high ?? ''); ?>" placeholder="例：永吉國中">
          </div>
          <div class="form-group">
            <label for="interest_department">興趣科系</label>
            <input type="text" id="interest_department" name="interest_department" value="<?php echo htmlspecialchars($interest_department ?? ''); ?>" placeholder="例：資管科">
          </div>
          <div class="form-group">
            <label for="activity_source">活動來源</label>
            <input type="text" id="activity_source" name="activity_source" value="<?php echo htmlspecialchars($activity_source ?? ''); ?>" placeholder="例：說明會、校園參訪、展覽攤位">
          </div>
          <div class="form-group">
            <label for="contact_teacher">聯絡教師</label>
            <input type="text" id="contact_teacher" name="contact_teacher" value="<?php echo htmlspecialchars($contact_teacher ?? ''); ?>" placeholder="例：王小明老師">
          </div>
          <div class="form-group">
            <label for="status">狀態</label>
            <select id="status" name="status">
              <option value="">請選擇</option>
              <?php
                $status_options = ['新建立', '已聯絡', '有興趣', '不感興趣', '已報名', '其他'];
                $current_status = $status ?? '';
                foreach ($status_options as $opt) {
                    $sel = ($current_status === $opt) ? 'selected' : '';
                    echo "<option value=\"".htmlspecialchars($opt)."\" $sel>".htmlspecialchars($opt)."</option>";
                }
              ?>
            </select>
          </div>
        </div>
        <div class="btn-row">
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 儲存</button>
          <button type="reset" class="btn btn-secondary"><i class="fas fa-undo"></i> 清除</button>
        </div>
      </form>
    </div>
  </div>
</main>
<?php include("share/footer.php"); ?>
</body>
</html>



