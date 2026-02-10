<?php
require_once 'config.php';
require_once __DIR__ . '/includes/email_functions.php';

$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
$error = '';
$data = null;
$same_recs = [];

try {
    $conn = getDatabaseConnection();
    $hasColumn = function($table, $column) use ($conn) {
        $table = trim((string)$table);
        $column = trim((string)$column);
        if ($table === '' || $column === '') return false;
        $stmt = $conn->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        if (!$stmt) return false;
        $stmt->bind_param('ss', $table, $column);
        $stmt->execute();
        $cnt = 0;
        $stmt->bind_result($cnt);
        $stmt->fetch();
        $stmt->close();
        return ((int)$cnt > 0);
    };

    // 確保簽核表存在
    $conn->query("CREATE TABLE IF NOT EXISTS recommendation_approval_links (
        id INT AUTO_INCREMENT PRIMARY KEY,
        recommendation_id INT NOT NULL,
        token VARCHAR(64) NOT NULL UNIQUE,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        signature_path VARCHAR(255) DEFAULT NULL,
        signer_name VARCHAR(100) DEFAULT NULL,
        reject_reason VARCHAR(255) DEFAULT NULL,
        confirmed_by_email VARCHAR(255) DEFAULT NULL,
        group_ids TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        signed_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_rec_id (recommendation_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if ($token === '') {
        $error = '缺少簽核連結參數。';
    } else {
        $stmt = $conn->prepare("SELECT recommendation_id, status, signature_path, group_ids FROM recommendation_approval_links WHERE token = ? LIMIT 1");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $res = $stmt->get_result();
        $link = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if (!$link) {
            $error = '簽核連結無效或已失效。';
        } else {
            $rid = (int)$link['recommendation_id'];
            $group_ids = trim((string)($link['group_ids'] ?? ''));
            $group_id_list = [];
            if ($group_ids !== '') {
                $group_id_list = array_values(array_filter(array_map('intval', explode(',', $group_ids)), function($v){ return $v > 0; }));
            }
            $is_signed = ($link['status'] === 'signed');

            // 取推薦人 / 被推薦人資訊（欄位存在才選取，避免 Unknown column）
            $has_recommender_table = false;
            $has_recommended_table = false;
            $t1 = $conn->query("SHOW TABLES LIKE 'recommender'");
            if ($t1 && $t1->num_rows > 0) $has_recommender_table = true;
            $t2 = $conn->query("SHOW TABLES LIKE 'recommended'");
            if ($t2 && $t2->num_rows > 0) $has_recommended_table = true;

            $ar_has = function($col) use ($hasColumn) {
                return $hasColumn('admission_recommendations', $col);
            };
            $ar_has_recommender_name = $ar_has('recommender_name');
            $ar_has_recommender_student_id = $ar_has('recommender_student_id');
            $ar_has_recommender_department_code = $ar_has('recommender_department_code');
            $ar_has_recommender_department = $ar_has('recommender_department');
            $ar_has_recommender_grade_code = $ar_has('recommender_grade_code');
            $ar_has_recommender_grade = $ar_has('recommender_grade');
            $ar_has_recommender_phone = $ar_has('recommender_phone');
            $ar_has_recommender_email = $ar_has('recommender_email');
            $ar_has_student_name = $ar_has('student_name');
            $ar_has_student_school = $ar_has('student_school');
            $ar_has_student_school_code = $ar_has('student_school_code');
            $ar_has_student_grade_code = $ar_has('student_grade_code');
            $ar_has_student_grade = $ar_has('student_grade');
            $ar_has_student_phone = $ar_has('student_phone');
            $ar_has_student_email = $ar_has('student_email');
            $ar_has_student_line_id = $ar_has('student_line_id');
            $ar_has_student_interest = $ar_has('student_interest');
            $ar_has_student_interest_code = $ar_has('student_interest_code');
            $ar_has_recommendation_reason = $ar_has('recommendation_reason');
            $ar_has_additional_info = $ar_has('additional_info');
            $ar_has_proof_evidence = $ar_has('proof_evidence');
            $ar_has_created_at = $ar_has('created_at');
            $ar_has_status = $ar_has('status');

            $rec_name_expr = $has_recommender_table
                ? "COALESCE(rec.name, " . ($ar_has_recommender_name ? "ar.recommender_name" : "''") . ", '')"
                : ($ar_has_recommender_name ? "COALESCE(ar.recommender_name,'')" : "''");
            $rec_id_expr = $has_recommender_table
                ? "COALESCE(rec.id, " . ($ar_has_recommender_student_id ? "ar.recommender_student_id" : "''") . ", '')"
                : ($ar_has_recommender_student_id ? "COALESCE(ar.recommender_student_id,'')" : "''");
            $rec_grade_expr = $has_recommender_table
                ? "COALESCE(rec.grade, " . ($ar_has_recommender_grade_code ? "ar.recommender_grade_code" : ($ar_has_recommender_grade ? "ar.recommender_grade" : "''")) . ", '')"
                : ($ar_has_recommender_grade_code ? "COALESCE(ar.recommender_grade_code,'')" : ($ar_has_recommender_grade ? "COALESCE(ar.recommender_grade,'')" : "''"));
            $rec_dept_expr = $has_recommender_table
                ? "COALESCE(rec.department, " . ($ar_has_recommender_department_code ? "ar.recommender_department_code" : ($ar_has_recommender_department ? "ar.recommender_department" : "''")) . ", '')"
                : ($ar_has_recommender_department_code ? "COALESCE(ar.recommender_department_code,'')" : ($ar_has_recommender_department ? "COALESCE(ar.recommender_department,'')" : "''"));
            $rec_phone_expr = $has_recommender_table
                ? "COALESCE(rec.phone, " . ($ar_has_recommender_phone ? "ar.recommender_phone" : "''") . ", '')"
                : ($ar_has_recommender_phone ? "COALESCE(ar.recommender_phone,'')" : "''");
            $rec_email_expr = $has_recommender_table
                ? "COALESCE(rec.email, " . ($ar_has_recommender_email ? "ar.recommender_email" : "''") . ", '')"
                : ($ar_has_recommender_email ? "COALESCE(ar.recommender_email,'')" : "''");

            $stu_name_expr = $has_recommended_table
                ? "COALESCE(red.name, " . ($ar_has_student_name ? "ar.student_name" : "''") . ", '')"
                : ($ar_has_student_name ? "COALESCE(ar.student_name,'')" : "''");
            $stu_school_expr = $has_recommended_table
                ? "COALESCE(red.school, " . ($ar_has_student_school ? "ar.student_school" : ($ar_has_student_school_code ? "ar.student_school_code" : "''")) . ", '')"
                : ($ar_has_student_school ? "COALESCE(ar.student_school,'')" : ($ar_has_student_school_code ? "COALESCE(ar.student_school_code,'')" : "''"));
            $stu_phone_expr = $has_recommended_table
                ? "COALESCE(red.phone, " . ($ar_has_student_phone ? "ar.student_phone" : "''") . ", '')"
                : ($ar_has_student_phone ? "COALESCE(ar.student_phone,'')" : "''");
            $stu_grade_expr = $has_recommended_table
                ? "COALESCE(red.grade, " . ($ar_has_student_grade_code ? "ar.student_grade_code" : ($ar_has_student_grade ? "ar.student_grade" : "''")) . ", '')"
                : ($ar_has_student_grade_code ? "COALESCE(ar.student_grade_code,'')" : ($ar_has_student_grade ? "COALESCE(ar.student_grade,'')" : "''"));
            $stu_email_expr = $has_recommended_table
                ? "COALESCE(red.email, " . ($ar_has_student_email ? "ar.student_email" : "''") . ", '')"
                : ($ar_has_student_email ? "COALESCE(ar.student_email,'')" : "''");
            $stu_line_expr = $has_recommended_table
                ? "COALESCE(red.line_id, " . ($ar_has_student_line_id ? "ar.student_line_id" : "''") . ", '')"
                : ($ar_has_student_line_id ? "COALESCE(ar.student_line_id,'')" : "''");

            $status_expr = $ar_has_status ? "COALESCE(ar.status,'')" : "''";
            $reason_expr = $ar_has_recommendation_reason ? "COALESCE(ar.recommendation_reason,'')" : "''";
            $additional_expr = $ar_has_additional_info ? "COALESCE(ar.additional_info,'')" : "''";
            $proof_expr = $ar_has_proof_evidence ? "COALESCE(ar.proof_evidence,'')" : "''";
            $created_expr = $ar_has_created_at ? "ar.created_at" : "NULL";

            $has_interest = ($ar_has_student_interest || $ar_has_student_interest_code);
            $interest_expr = $has_interest
                ? "COALESCE(interest_dept.name, " . ($ar_has_student_interest ? "ar.student_interest" : "''") . ", " . ($ar_has_student_interest_code ? "ar.student_interest_code" : "''") . ", '')"
                : "''";

            $sql = "SELECT
                ar.id,
                {$rec_name_expr} AS recommender_name,
                {$rec_id_expr} AS recommender_student_id,
                {$rec_grade_expr} AS recommender_grade,
                {$rec_dept_expr} AS recommender_department,
                {$rec_phone_expr} AS recommender_phone,
                {$rec_email_expr} AS recommender_email,
                {$stu_name_expr} AS student_name,
                {$stu_school_expr} AS student_school,
                {$stu_grade_expr} AS student_grade,
                {$stu_phone_expr} AS student_phone,
                {$stu_email_expr} AS student_email,
                {$stu_line_expr} AS student_line_id,
                {$interest_expr} AS student_interest,
                {$reason_expr} AS recommendation_reason,
                {$additional_expr} AS additional_info,
                {$proof_expr} AS proof_evidence,
                {$created_expr} AS created_at,
                {$status_expr} AS status
            FROM admission_recommendations ar
            " . ($has_recommender_table ? "LEFT JOIN recommender rec ON ar.id = rec.recommendations_id " : "") . "
            " . ($has_recommended_table ? "LEFT JOIN recommended red ON ar.id = red.recommendations_id " : "") . "
            " . ($has_interest ? "LEFT JOIN departments interest_dept ON " . ($ar_has_student_interest ? "ar.student_interest" : "ar.student_interest_code") . " = interest_dept.code " : "") . "
            WHERE ar.id = ?
            LIMIT 1";
            $stmt2 = $conn->prepare($sql);
            $stmt2->bind_param('i', $rid);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            $data = $res2 ? $res2->fetch_assoc() : null;
            $stmt2->close();

            if (!$data) {
                $error = '找不到對應的推薦資料。';
            } else {
                $data['is_signed'] = $is_signed ? 1 : 0;
                $data['signature_path'] = $link['signature_path'] ?? '';
                $data['status'] = trim((string)($data['status'] ?? ''));
                if ($data['status'] !== 'AP' && $data['status'] !== 'approved' && $data['status'] !== 'MC') {
                    $error = '此筆推薦尚未審核通過，無法簽核。';
                }
            }

            $same_recs = [];
            $same_recs_title = '同一推薦人所有推薦學生';
            if (!empty($group_id_list)) {
                $same_recs_title = '本次待審核推薦清單';
                $ids = $group_id_list;
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $list_sql = "SELECT
                        ar.id,
                        {$rec_name_expr} AS recommender_name,
                        {$rec_id_expr} AS recommender_student_id,
                        {$rec_grade_expr} AS recommender_grade,
                        {$rec_dept_expr} AS recommender_department,
                        {$rec_phone_expr} AS recommender_phone,
                        {$rec_email_expr} AS recommender_email,
                        {$stu_name_expr} AS student_name,
                        {$stu_school_expr} AS student_school,
                        {$stu_grade_expr} AS student_grade,
                        {$stu_phone_expr} AS student_phone,
                        {$stu_email_expr} AS student_email,
                        {$stu_line_expr} AS student_line_id,
                        {$interest_expr} AS student_interest,
                        {$reason_expr} AS recommendation_reason,
                        {$additional_expr} AS additional_info,
                        {$proof_expr} AS proof_evidence,
                        {$created_expr} AS created_at,
                        {$status_expr} AS status
                    FROM admission_recommendations ar
                    " . ($has_recommender_table ? "LEFT JOIN recommender rec ON ar.id = rec.recommendations_id " : "") . "
                    " . ($has_recommended_table ? "LEFT JOIN recommended red ON ar.id = red.recommendations_id " : "") . "
                    " . ($has_interest ? "LEFT JOIN departments interest_dept ON " . ($ar_has_student_interest ? "ar.student_interest" : "ar.student_interest_code") . " = interest_dept.code " : "") . "
                    WHERE ar.id IN ($placeholders)
                    ORDER BY ar.id DESC";
                $stmt3 = $conn->prepare($list_sql);
                if ($stmt3) {
                    $types = str_repeat('i', count($ids));
                    $params = array_merge([$types], $ids);
                    $refs = [];
                    foreach ($params as $k => $v) $refs[$k] = &$params[$k];
                    call_user_func_array([$stmt3, 'bind_param'], $refs);
                    if ($stmt3->execute()) {
                        $res3 = $stmt3->get_result();
                        if ($res3) {
                            while ($row = $res3->fetch_assoc()) {
                                $same_recs[] = $row;
                            }
                        }
                    }
                    $stmt3->close();
                }
            }

            // 同一推薦人所有推薦學生資料（姓名 + 學號/教師編號）
            if ($data && $error === '' && empty($same_recs)) {
                $rk_name = trim((string)($data['recommender_name'] ?? ''));
                $rk_id = trim((string)($data['recommender_student_id'] ?? ''));
                if ($rk_name !== '' && $rk_id !== '') {
                    $list_sql = "SELECT
                        ar.id,
                        {$rec_name_expr} AS recommender_name,
                        {$rec_id_expr} AS recommender_student_id,
                        {$rec_grade_expr} AS recommender_grade,
                        {$rec_dept_expr} AS recommender_department,
                        {$rec_phone_expr} AS recommender_phone,
                        {$rec_email_expr} AS recommender_email,
                        {$stu_name_expr} AS student_name,
                        {$stu_school_expr} AS student_school,
                        {$stu_grade_expr} AS student_grade,
                        {$stu_phone_expr} AS student_phone,
                        {$stu_email_expr} AS student_email,
                        {$stu_line_expr} AS student_line_id,
                        {$interest_expr} AS student_interest,
                        {$reason_expr} AS recommendation_reason,
                        {$additional_expr} AS additional_info,
                        {$proof_expr} AS proof_evidence,
                        {$created_expr} AS created_at,
                        {$status_expr} AS status
                    FROM admission_recommendations ar
                    " . ($has_recommender_table ? "LEFT JOIN recommender rec ON ar.id = rec.recommendations_id " : "") . "
                    " . ($has_recommended_table ? "LEFT JOIN recommended red ON ar.id = red.recommendations_id " : "") . "
                    " . ($has_interest ? "LEFT JOIN departments interest_dept ON " . ($ar_has_student_interest ? "ar.student_interest" : "ar.student_interest_code") . " = interest_dept.code " : "") . "
                    WHERE {$rec_name_expr} = ? AND {$rec_id_expr} = ?
                    ORDER BY ar.id DESC";
                    $stmt3 = $conn->prepare($list_sql);
                    if ($stmt3) {
                        $stmt3->bind_param('ss', $rk_name, $rk_id);
                        if ($stmt3->execute()) {
                            $res3 = $stmt3->get_result();
                            if ($res3) {
                                while ($row = $res3->fetch_assoc()) {
                                    $same_recs[] = $row;
                                }
                            }
                        }
                        $stmt3->close();
                    }
                }
            }
        }
    }
    $conn->close();
} catch (Exception $e) {
    $error = '系統錯誤：' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>推薦學生簽核</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f5f7fb; margin:0; padding:24px; color:#333; }
    .card { max-width: 920px; margin: 0 auto; background:#fff; border-radius:12px; padding:24px; box-shadow:0 6px 20px rgba(0,0,0,0.08); }
    h2 { margin:0 0 12px 0; color:#003366; }
    .section { margin-top: 18px; }
    .grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
    .label { font-size: 12px; color:#777; margin-bottom:4px; }
    .value { font-size: 15px; font-weight: 600; }
    .alert { padding: 12px 14px; border-radius: 8px; background: #fff2f0; color:#a8071a; border:1px solid #ffccc7; }
    .success { background:#f6ffed; color:#389e0d; border:1px solid #b7eb8f; }
    #signatureCanvas { width:100%; height:240px; border:1px dashed #bbb; border-radius:10px; background:#fff; touch-action: none; }
    .btns { display:flex; gap:10px; margin-top:12px; flex-wrap: wrap; align-items: center; }
    button { padding:10px 16px; border:none; border-radius:8px; cursor:pointer; font-weight:700; }
    .btn-primary { background:#1677ff; color:#fff; }
    .btn-secondary { background:#f0f0f0; }
    .btn-danger { background:#ff4d4f; color:#fff; }
    .reject-box { margin-top: 12px; }
    .reject-box textarea {
      width: 100%;
      min-height: 90px;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 10px;
      font-size: 14px;
      resize: vertical;
      box-sizing: border-box;
    }
    .detail-wrap { display:flex; gap:18px; flex-wrap: wrap; }
    .detail-card { flex:1; min-width: 300px; }
    .detail-title { margin: 0 0 10px 0; font-size: 16px; color:#003366; }
    .detail-table { width:100%; border-collapse: collapse; font-size: 14px; }
    .detail-table td { padding: 6px 8px; border: 1px solid #ddd; }
    .detail-table td.label { background:#f5f5f5; width: 120px; color:#333; }
    .detail-section { margin-top: 18px; }
    .file-link { color:#1677ff; text-decoration: none; }
    .muted { color:#666; font-size:12px; }
  </style>
</head>
<body>
  <div class="card">
    <h2>推薦學生簽核</h2>
    <?php if ($error !== ''): ?>
      <div class="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif (!$data): ?>
      <div class="alert">資料載入失敗。</div>
    <?php else: ?>
      <div class="section">
        <div class="detail-wrap">
          <div class="detail-card">
            <h4 class="detail-title">被推薦人資訊</h4>
            <table class="detail-table">
              <tr><td class="label">姓名</td><td><?php echo htmlspecialchars($data['student_name'] ?? ''); ?></td></tr>
              <tr><td class="label">就讀學校</td><td><?php echo htmlspecialchars($data['student_school'] ?? ''); ?></td></tr>
              <tr><td class="label">年級</td><td><?php echo htmlspecialchars($data['student_grade'] ?? ''); ?></td></tr>
              <tr><td class="label">電子郵件</td><td><?php echo htmlspecialchars($data['student_email'] ?? ''); ?></td></tr>
              <tr><td class="label">聯絡電話</td><td><?php echo htmlspecialchars($data['student_phone'] ?? ''); ?></td></tr>
              <tr><td class="label">LINE ID</td><td><?php echo htmlspecialchars($data['student_line_id'] ?? ''); ?></td></tr>
              <tr><td class="label">學生興趣</td><td><?php echo htmlspecialchars($data['student_interest'] ?? ''); ?></td></tr>
            </table>
          </div>
          <div class="detail-card">
            <h4 class="detail-title">推薦人資訊</h4>
            <table class="detail-table">
              <tr><td class="label">姓名</td><td><?php echo htmlspecialchars($data['recommender_name'] ?? ''); ?></td></tr>
              <tr><td class="label">學號/教師編號</td><td><?php echo htmlspecialchars($data['recommender_student_id'] ?? ''); ?></td></tr>
              <tr><td class="label">年級</td><td><?php echo htmlspecialchars($data['recommender_grade'] ?? ''); ?></td></tr>
              <tr><td class="label">科系</td><td><?php echo htmlspecialchars($data['recommender_department'] ?? ''); ?></td></tr>
              <tr><td class="label">聯絡電話</td><td><?php echo htmlspecialchars($data['recommender_phone'] ?? ''); ?></td></tr>
              <tr><td class="label">電子郵件</td><td><?php echo htmlspecialchars($data['recommender_email'] ?? ''); ?></td></tr>
            </table>
          </div>
        </div>

        <div class="detail-section">
          <h4 class="detail-title">推薦資訊</h4>
          <table class="detail-table">
            <tr><td class="label">推薦理由</td><td><?php echo nl2br(htmlspecialchars($data['recommendation_reason'] ?? '')); ?></td></tr>
            <?php if (!empty($data['additional_info'])): ?>
            <tr><td class="label">其他補充資訊</td><td><?php echo nl2br(htmlspecialchars($data['additional_info'] ?? '')); ?></td></tr>
            <?php endif; ?>
            <?php if (!empty($data['proof_evidence'])): ?>
            <tr>
              <td class="label">證明文件</td>
              <td>
                <?php
                  $file_path = str_replace('\\', '/', $data['proof_evidence']);
                  $file_url = '/Topics-frontend/frontend/' . $file_path;
                ?>
                <a class="file-link" href="<?php echo htmlspecialchars($file_url); ?>" target="_blank" rel="noopener">查看文件</a>
              </td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($data['created_at'])): ?>
            <tr><td class="label">推薦時間</td><td><?php echo htmlspecialchars(date('Y/m/d H:i', strtotime($data['created_at']))); ?></td></tr>
            <?php endif; ?>
          </table>
        </div>

        <?php if (!empty($same_recs) && count($same_recs) > 1): ?>
        <div class="detail-section">
          <h4 class="detail-title"><?php echo htmlspecialchars($same_recs_title ?? '同一推薦人所有推薦學生'); ?></h4>
          <?php foreach ($same_recs as $row): ?>
            <div class="detail-wrap" style="margin-top:12px;">
              <div class="detail-card">
                <h4 class="detail-title">被推薦人資訊（推薦編號：<?php echo htmlspecialchars($row['id'] ?? ''); ?>）</h4>
                <table class="detail-table">
                  <tr><td class="label">姓名</td><td><?php echo htmlspecialchars($row['student_name'] ?? ''); ?></td></tr>
                  <tr><td class="label">就讀學校</td><td><?php echo htmlspecialchars($row['student_school'] ?? ''); ?></td></tr>
                  <tr><td class="label">年級</td><td><?php echo htmlspecialchars($row['student_grade'] ?? ''); ?></td></tr>
                  <tr><td class="label">電子郵件</td><td><?php echo htmlspecialchars($row['student_email'] ?? ''); ?></td></tr>
                  <tr><td class="label">聯絡電話</td><td><?php echo htmlspecialchars($row['student_phone'] ?? ''); ?></td></tr>
                  <tr><td class="label">LINE ID</td><td><?php echo htmlspecialchars($row['student_line_id'] ?? ''); ?></td></tr>
                  <tr><td class="label">學生興趣</td><td><?php echo htmlspecialchars($row['student_interest'] ?? ''); ?></td></tr>
                </table>
              </div>
              <div class="detail-card">
                <h4 class="detail-title">推薦資訊</h4>
                <table class="detail-table">
                  <tr><td class="label">推薦人姓名</td><td><?php echo htmlspecialchars($row['recommender_name'] ?? ''); ?></td></tr>
                  <tr><td class="label">學號/教師編號</td><td><?php echo htmlspecialchars($row['recommender_student_id'] ?? ''); ?></td></tr>
                  <tr><td class="label">推薦理由</td><td><?php echo nl2br(htmlspecialchars($row['recommendation_reason'] ?? '')); ?></td></tr>
                  <?php if (!empty($row['additional_info'])): ?>
                  <tr><td class="label">其他補充資訊</td><td><?php echo nl2br(htmlspecialchars($row['additional_info'] ?? '')); ?></td></tr>
                  <?php endif; ?>
                  <?php if (!empty($row['proof_evidence'])): ?>
                  <tr>
                    <td class="label">證明文件</td>
                    <td>
                      <?php
                        $file_path2 = str_replace('\\', '/', $row['proof_evidence']);
                        $file_url2 = '/Topics-frontend/frontend/' . $file_path2;
                      ?>
                      <a class="file-link" href="<?php echo htmlspecialchars($file_url2); ?>" target="_blank" rel="noopener">查看文件</a>
                    </td>
                  </tr>
                  <?php endif; ?>
                  <?php if (!empty($row['created_at'])): ?>
                  <tr><td class="label">推薦時間</td><td><?php echo htmlspecialchars(date('Y/m/d H:i', strtotime($row['created_at']))); ?></td></tr>
                  <?php endif; ?>
                </table>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <div class="section">
        <?php if ((int)$data['is_signed'] === 1): ?>
          <div class="alert success">已完成簽核，謝謝。</div>
          <?php if (!empty($data['signature_path'])): ?>
            <div class="section">
              <img src="<?php echo htmlspecialchars($data['signature_path']); ?>" alt="signature" style="max-width:100%; border:1px solid #eee; border-radius:8px;">
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="label">線上簽章</div>
          <iframe
            id="signatureFrame"
            src="/Topics-backend/frontend/signature.php?document_id=<?php echo urlencode((string)($data['id'] ?? '')); ?>&document_type=admission_recommendation&embed=1"
            style="width:100%; min-height:720px; border:1px dashed #bbb; border-radius:10px; background:#fff;"
          ></iframe>
          <div class="btns">
            <button class="btn-danger" type="button" onclick="openReject()">不通過</button>
          </div>
          <div id="rejectBox" class="reject-box" style="display:none;">
            <div class="label" style="margin-bottom:6px;">不通過原因</div>
            <textarea id="rejectReason" placeholder="請輸入不通過原因"></textarea>
            <div class="btns" style="margin-top:8px;">
              <button class="btn-secondary" type="button" onclick="closeReject()">取消</button>
              <button class="btn-danger" type="button" onclick="submitReject()">確認不通過</button>
            </div>
          </div>
          <div class="muted">請於上方區塊完成電子簽章後，系統會自動回填簽核。</div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <script>
    const token = <?php echo json_encode($token); ?>;
    window.addEventListener('message', function(event) {
      if (event.origin !== window.location.origin) return;
      const payload = event.data || {};
      if (payload.type === 'signature_saved' && payload.signature_url) {
        submitSignatureUrl(payload.signature_url);
      }
    });

    function submitSignatureUrl(signatureUrl) {
      fetch('recommendation_approval_submit.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'token=' + encodeURIComponent(token) + '&signature_url=' + encodeURIComponent(signatureUrl)
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert('簽核完成，已通知招生中心。');
          location.reload();
        } else {
          alert('簽核失敗：' + (data.message || '未知錯誤'));
        }
      })
      .catch(() => alert('簽核失敗：網路錯誤'));
    }

    function openReject() {
      const box = document.getElementById('rejectBox');
      if (box) box.style.display = 'block';
    }
    function closeReject() {
      const box = document.getElementById('rejectBox');
      const reason = document.getElementById('rejectReason');
      if (box) box.style.display = 'none';
      if (reason) reason.value = '';
    }
    function submitReject() {
      const reason = document.getElementById('rejectReason');
      const text = reason ? reason.value.trim() : '';
      if (!text) {
        alert('請輸入不通過原因');
        return;
      }
      fetch('recommendation_approval_submit.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'token=' + encodeURIComponent(token) + '&reject_reason=' + encodeURIComponent(text)
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert('已回覆不通過，已通知招生中心。');
          location.reload();
        } else {
          alert('送出失敗：' + (data.message || '未知錯誤'));
        }
      })
      .catch(() => alert('送出失敗：網路錯誤'));
    }
  </script>
</body>
</html>
