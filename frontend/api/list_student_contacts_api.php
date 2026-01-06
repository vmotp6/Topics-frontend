<?php
// API：列出學生聯絡資訊（student_contacts）
header('Content-Type: application/json; charset=utf-8');

require_once '../session_config.php';
require_once '../config.php';

$allowed_roles = ['老師', 'TEA', 'STA', '學校行政人員', 'AA', 'DI', 'IM'];
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true &&
              isset($_SESSION['role']) && in_array($_SESSION['role'], $allowed_roles, true);

if (!$isLoggedIn) {
  http_response_code(403);
  echo json_encode(['success' => false, 'message' => '未登入或權限不足']);
  exit;
}

function ensureStudentContactTable($conn) {
  $sql = "CREATE TABLE IF NOT EXISTS student_contacts (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(100) NOT NULL,
      junior_high VARCHAR(150) DEFAULT NULL,
      current_grade VARCHAR(50) DEFAULT NULL,
      interest_department VARCHAR(150) DEFAULT NULL,
      activity_source VARCHAR(150) DEFAULT NULL,
      contact_teacher VARCHAR(150) DEFAULT NULL,
      status VARCHAR(100) DEFAULT NULL,
      contact_method VARCHAR(50) DEFAULT NULL,
      contact_method_value VARCHAR(255) DEFAULT NULL,
      contact_content TEXT DEFAULT NULL,
      contact_note VARCHAR(255) DEFAULT NULL,
      contact_date DATE DEFAULT NULL,
      created_by INT DEFAULT NULL,
      created_by_username VARCHAR(150) DEFAULT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_name (name),
      INDEX idx_junior_high (junior_high),
      INDEX idx_current_grade (current_grade),
      INDEX idx_interest_department (interest_department),
      INDEX idx_status (status),
      INDEX idx_created_by (created_by),
      INDEX idx_created_at (created_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
  $conn->query($sql);
}

try {
  $conn = getDatabaseConnection();
  if (!$conn) throw new Exception('資料庫連接失敗');

  ensureStudentContactTable($conn);

  // 向後兼容：確保欄位存在
  $conn->query("ALTER TABLE student_contacts ADD COLUMN IF NOT EXISTS current_grade VARCHAR(50) DEFAULT NULL");
  $conn->query("ALTER TABLE student_contacts ADD COLUMN IF NOT EXISTS contact_method VARCHAR(50) DEFAULT NULL");
  $conn->query("ALTER TABLE student_contacts ADD COLUMN IF NOT EXISTS contact_method_value VARCHAR(255) DEFAULT NULL");
  $conn->query("ALTER TABLE student_contacts ADD COLUMN IF NOT EXISTS contact_content TEXT DEFAULT NULL");
  $conn->query("ALTER TABLE student_contacts ADD COLUMN IF NOT EXISTS contact_note VARCHAR(255) DEFAULT NULL");
  $conn->query("ALTER TABLE student_contacts ADD COLUMN IF NOT EXISTS contact_date DATE DEFAULT NULL");
  $conn->query("ALTER TABLE student_contacts ADD COLUMN IF NOT EXISTS created_by INT DEFAULT NULL");
  $conn->query("ALTER TABLE student_contacts ADD COLUMN IF NOT EXISTS created_by_username VARCHAR(150) DEFAULT NULL");

  // 篩選條件
  // - name：姓名（LIKE）
  // - activity_source：活動來源（LIKE）
  // - q：通用搜尋（LIKE 多欄位，向後兼容）
  // - status：狀態（精確比對，向後兼容）
  $name = isset($_GET['name']) ? trim($_GET['name']) : '';
  $activity_source = isset($_GET['activity_source']) ? trim($_GET['activity_source']) : '';
  $q = isset($_GET['q']) ? trim($_GET['q']) : '';
  $status = isset($_GET['status']) ? trim($_GET['status']) : '';

  $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
  $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
  if ($limit <= 0) $limit = 50;
  if ($limit > 200) $limit = 200;
  if ($offset < 0) $offset = 0;

  $where = [];
  $types = '';
  $params = [];

  if ($q !== '') {
    $where[] = "(name LIKE ? OR junior_high LIKE ? OR current_grade LIKE ? OR interest_department LIKE ? OR activity_source LIKE ? OR contact_teacher LIKE ? OR status LIKE ? OR contact_method LIKE ? OR contact_method_value LIKE ? OR contact_content LIKE ? OR contact_note LIKE ?)";
    $like = '%' . $q . '%';
    $types .= str_repeat('s', 11);
    for ($i = 0; $i < 11; $i++) $params[] = $like;
  }

  if ($name !== '') {
    $where[] = "name LIKE ?";
    $types .= 's';
    $params[] = '%' . $name . '%';
  }

  if ($activity_source !== '') {
    $where[] = "activity_source LIKE ?";
    $types .= 's';
    $params[] = '%' . $activity_source . '%';
  }

  if ($status !== '') {
    $where[] = "status = ?";
    $types .= 's';
    $params[] = $status;
  }

  $whereSql = '';
  if (!empty($where)) $whereSql = ' WHERE ' . implode(' AND ', $where);

  // total
  $total = 0;
  $countSql = "SELECT COUNT(*) AS cnt FROM student_contacts" . $whereSql;
  $countStmt = $conn->prepare($countSql);
  if (!$countStmt) throw new Exception('查詢準備失敗');
  if (!empty($params)) $countStmt->bind_param($types, ...$params);
  $countStmt->execute();
  $countRes = $countStmt->get_result();
  if ($row = $countRes->fetch_assoc()) $total = (int)$row['cnt'];
  $countStmt->close();

  // list
  $listSql = "SELECT id, name, junior_high, current_grade, interest_department, activity_source, contact_teacher, status,
                     contact_method, contact_method_value, contact_content, contact_note, contact_date, created_by, created_by_username,
                     DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at
              FROM student_contacts" . $whereSql . "
              ORDER BY created_at DESC, id DESC
              LIMIT ? OFFSET ?";

  $listStmt = $conn->prepare($listSql);
  if (!$listStmt) throw new Exception('查詢準備失敗');

  $types2 = $types . 'ii';
  $params2 = $params;
  $params2[] = $limit;
  $params2[] = $offset;
  $listStmt->bind_param($types2, ...$params2);
  $listStmt->execute();
  $res = $listStmt->get_result();
  $contacts = $res->fetch_all(MYSQLI_ASSOC);
  $listStmt->close();

  $conn->close();
  echo json_encode([
    'success' => true,
    'total' => $total,
    'limit' => $limit,
    'offset' => $offset,
    'contacts' => $contacts
  ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => '系統錯誤：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
