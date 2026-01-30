<?php
require_once 'config.php';
require_once __DIR__ . '/includes/email_functions.php';

header('Content-Type: application/json; charset=utf-8');

$token = isset($_POST['token']) ? trim((string)$_POST['token']) : '';
$signature = isset($_POST['signature']) ? trim((string)$_POST['signature']) : '';
$reject_reason = isset($_POST['reject_reason']) ? trim((string)$_POST['reject_reason']) : '';

if ($token === '' || ($signature === '' && $reject_reason === '')) {
    echo json_encode(['success' => false, 'message' => '缺少必要參數']);
    exit;
}

try {
    $conn = getDatabaseConnection();
    $conn->query("CREATE TABLE IF NOT EXISTS recommendation_approval_links (
        id INT AUTO_INCREMENT PRIMARY KEY,
        recommendation_id INT NOT NULL,
        token VARCHAR(64) NOT NULL UNIQUE,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        signature_path VARCHAR(255) DEFAULT NULL,
        signer_name VARCHAR(100) DEFAULT NULL,
        reject_reason VARCHAR(255) DEFAULT NULL,
        confirmed_by_email VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        signed_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_rec_id (recommendation_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

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

    if (!$hasColumn('recommendation_approval_links', 'reject_reason')) {
        $conn->query("ALTER TABLE recommendation_approval_links ADD COLUMN reject_reason VARCHAR(255) DEFAULT NULL");
    }

    $stmt = $conn->prepare("SELECT recommendation_id, status FROM recommendation_approval_links WHERE token = ? LIMIT 1");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $res = $stmt->get_result();
    $link = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$link) {
        echo json_encode(['success' => false, 'message' => '簽核連結無效']);
        exit;
    }

    if (($link['status'] ?? '') === 'signed') {
        echo json_encode(['success' => true, 'message' => '已簽核']);
        exit;
    }
    if (($link['status'] ?? '') === 'rejected') {
        echo json_encode(['success' => true, 'message' => '已回覆不通過']);
        exit;
    }

    $rid = (int)$link['recommendation_id'];

    // 檢查是否審核通過
    $ar_has = function($col) use ($hasColumn) {
        return $hasColumn('admission_recommendations', $col);
    };
    $ar_has_status = $ar_has('status');
    $ar_has_student_name = $ar_has('student_name');
    $ar_has_student_school = $ar_has('student_school');
    $ar_has_student_school_code = $ar_has('student_school_code');
    $ar_has_student_phone = $ar_has('student_phone');

    $status_expr = $ar_has_status ? "COALESCE(status,'')" : "''";
    $name_expr = $ar_has_student_name ? "COALESCE(student_name,'')" : "''";
    $school_expr = $ar_has_student_school ? "COALESCE(student_school,'')" : "''";
    $school_code_expr = $ar_has_student_school_code ? "COALESCE(student_school_code,'')" : "''";
    $phone_expr = $ar_has_student_phone ? "COALESCE(student_phone,'')" : "''";

    $chk = $conn->prepare("SELECT {$status_expr} AS status,
        {$name_expr} AS student_name,
        {$school_expr} AS student_school,
        {$school_code_expr} AS student_school_code,
        {$phone_expr} AS student_phone
        FROM admission_recommendations WHERE id = ? LIMIT 1");
    $chk->bind_param('i', $rid);
    $chk->execute();
    $r2 = $chk->get_result();
    $rec = $r2 ? $r2->fetch_assoc() : null;
    $chk->close();

    if (!$rec || !in_array(strtolower(trim((string)($rec['status'] ?? ''))), ['ap', 'approved'], true)) {
        echo json_encode(['success' => false, 'message' => '此筆推薦尚未審核通過']);
        exit;
    }

    $public_path = '';
    if ($reject_reason === '') {
        // 儲存簽名檔案
        if (!preg_match('/^data:image\/png;base64,/', $signature)) {
            echo json_encode(['success' => false, 'message' => '簽名格式錯誤']);
            exit;
        }
        $raw = base64_decode(str_replace('data:image/png;base64,', '', $signature), true);
        if ($raw === false) {
            echo json_encode(['success' => false, 'message' => '簽名解碼失敗']);
            exit;
        }

        $dir = __DIR__ . '/uploads/recommendation_approvals';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $filename = 'signature_' . $rid . '_' . time() . '.png';
        $path = $dir . '/' . $filename;
        if (file_put_contents($path, $raw) === false) {
            echo json_encode(['success' => false, 'message' => '簽名保存失敗']);
            exit;
        }
        $public_path = '/Topics-frontend/frontend/uploads/recommendation_approvals/' . $filename;

        $upd = $conn->prepare("UPDATE recommendation_approval_links
            SET status = 'signed', signature_path = ?, signed_at = NOW()
            WHERE token = ? LIMIT 1");
        $upd->bind_param('ss', $public_path, $token);
        $upd->execute();
        $upd->close();
    } else {
        $upd = $conn->prepare("UPDATE recommendation_approval_links
            SET status = 'rejected', reject_reason = ?, signed_at = NOW()
            WHERE token = ? LIMIT 1");
        $upd->bind_param('ss', $reject_reason, $token);
        $upd->execute();
        $upd->close();
    }

    // 通知招生中心
    $student_name = trim((string)($rec['student_name'] ?? ''));
    $student_school = trim((string)($rec['student_school'] ?? ''));
    if ($student_school === '') $student_school = trim((string)($rec['student_school_code'] ?? ''));
    $student_phone = trim((string)($rec['student_phone'] ?? ''));

    $to_email = '110534236@stu.ukn.edu.tw';
    if ($reject_reason === '') {
        $subject = '科主任已確認通過';
        $body = "
            <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <p>科主任已經完成簽核，並確認通過。</p>
                <p>學生：{$student_name}</p>
                <p>學校：{$student_school}</p>
                <p>聯絡電話：{$student_phone}</p>
            </div>
        ";
        $altBody = "科主任已經完成簽核，並確認通過。\n學生：{$student_name}\n學校：{$student_school}\n聯絡電話：{$student_phone}";
    } else {
        $subject = '科主任回覆不通過';
        $body = "
            <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <p>科主任回覆不通過。</p>
                <p>原因：{$reject_reason}</p>
                <p>學生：{$student_name}</p>
                <p>學校：{$student_school}</p>
                <p>聯絡電話：{$student_phone}</p>
            </div>
        ";
        $altBody = "科主任回覆不通過。\n原因：{$reject_reason}\n學生：{$student_name}\n學校：{$student_school}\n聯絡電話：{$student_phone}";
    }
    if (function_exists('sendEmail')) {
        @sendEmail($to_email, $subject, $body, $altBody);
    }

    echo json_encode(['success' => true]);
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '系統錯誤：' . $e->getMessage()]);
}
?>
