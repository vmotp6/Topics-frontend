<?php
require_once 'config.php';
require_once __DIR__ . '/includes/email_functions.php';
require_once __DIR__ . '/../../Topics-backend/frontend/recommendation_review_email.php';

// 確保 application_statuses 中存在主任簽核狀態
function ensure_application_status_code($conn, $code, $name, $order) {
    if (!$conn) return;
    $t = $conn->query("SHOW TABLES LIKE 'application_statuses'");
    if (!$t || $t->num_rows <= 0) return;
    $cols = [];
    $cr = $conn->query("SHOW COLUMNS FROM application_statuses");
    if ($cr) {
        while ($row = $cr->fetch_assoc()) {
            $cols[] = $row['Field'];
        }
    }
    if (!in_array('code', $cols, true)) return;
    $has_name = in_array('name', $cols, true);
    $has_order = in_array('display_order', $cols, true);
    $stmt_check = $conn->prepare("SELECT code FROM application_statuses WHERE code = ? LIMIT 1");
    if (!$stmt_check) return;
    $stmt_check->bind_param('s', $code);
    if ($stmt_check->execute()) {
        $res = $stmt_check->get_result();
        if ($res && $res->num_rows > 0) {
            $stmt_check->close();
            return;
        }
    }
    $stmt_check->close();
    if ($has_name && $has_order) {
        $stmt_ins = $conn->prepare("INSERT INTO application_statuses (code, name, display_order) VALUES (?, ?, ?)");
        if ($stmt_ins) {
            $stmt_ins->bind_param('ssi', $code, $name, $order);
            @$stmt_ins->execute();
            $stmt_ins->close();
        }
    } elseif ($has_name) {
        $stmt_ins = $conn->prepare("INSERT INTO application_statuses (code, name) VALUES (?, ?)");
        if ($stmt_ins) {
            $stmt_ins->bind_param('ss', $code, $name);
            @$stmt_ins->execute();
            $stmt_ins->close();
        }
    } else {
        $stmt_ins = $conn->prepare("INSERT INTO application_statuses (code) VALUES (?)");
        if ($stmt_ins) {
            $stmt_ins->bind_param('s', $code);
            @$stmt_ins->execute();
            $stmt_ins->close();
        }
    }
}

header('Content-Type: application/json; charset=utf-8');

$token = isset($_POST['token']) ? trim((string)$_POST['token']) : '';
$signature = isset($_POST['signature']) ? trim((string)$_POST['signature']) : '';
$signature_url = isset($_POST['signature_url']) ? trim((string)$_POST['signature_url']) : '';
$reject_reason = isset($_POST['reject_reason']) ? trim((string)$_POST['reject_reason']) : '';
$waive_bonus = isset($_POST['waive_bonus']) ? intval($_POST['waive_bonus']) : 0;
$review_decision = isset($_POST['review_decision']) ? trim((string)$_POST['review_decision']) : '';
$selected_review_ids_raw = isset($_POST['selected_review_ids']) ? trim((string)$_POST['selected_review_ids']) : '';
$review_decision_map_raw = isset($_POST['review_decision_map']) ? trim((string)$_POST['review_decision_map']) : '';

if ($token === '' || ($signature === '' && $signature_url === '' && $reject_reason === '' && $waive_bonus !== 1)) {
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
        group_ids TEXT NULL,
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

    $stmt = $conn->prepare("SELECT recommendation_id, status, group_ids FROM recommendation_approval_links WHERE token = ? LIMIT 1");
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
    if (($link['status'] ?? '') === 'waived') {
        echo json_encode(['success' => true, 'message' => '已設定放棄獎金']);
        exit;
    }

    $rid = (int)$link['recommendation_id'];
    $group_ids = trim((string)($link['group_ids'] ?? ''));
    $group_id_list = [];
    if ($group_ids !== '') {
        $group_id_list = array_values(array_filter(array_map('intval', explode(',', $group_ids)), function($v){ return $v > 0; }));
    }

    // 檢查是否審核通過
    $ar_has = function($col) use ($hasColumn) {
        return $hasColumn('admission_recommendations', $col);
    };
    $ar_has_status = $ar_has('status');
    $ar_has_student_name = $ar_has('student_name');
    $ar_has_student_school = $ar_has('student_school');
    $ar_has_student_school_code = $ar_has('student_school_code');
    $ar_has_student_phone = $ar_has('student_phone');
    $has_recommended_table = false;
    $t_recommended = $conn->query("SHOW TABLES LIKE 'recommended'");
    if ($t_recommended && $t_recommended->num_rows > 0) $has_recommended_table = true;

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
    $rec = null;
    $is_apd_status = function($status) {
        $st = trim((string)$status);
        $st_norm = strtolower($st);
        return ($st_norm === 'apd' || mb_strpos($st, '審核完成') !== false || mb_strpos($st, '可發獎金') !== false);
    };
    $waive_allowed = true;
    if (!empty($group_id_list)) {
        $ok = true;
        foreach ($group_id_list as $gid) {
            $chk->bind_param('i', $gid);
            $chk->execute();
            $r2 = $chk->get_result();
            $row = $r2 ? $r2->fetch_assoc() : null;
            if (!$row || !in_array(strtolower(trim((string)($row['status'] ?? ''))), ['ap', 'approved', 'mc', 'apd'], true)) {
                $ok = false;
                break;
            }
            if (!$is_apd_status($row['status'] ?? '')) {
                $waive_allowed = false;
            }
        }
        if (!$ok) {
            $chk->close();
            echo json_encode(['success' => false, 'message' => '此筆推薦尚未審核通過']);
            exit;
        }
    } else {
        $chk->bind_param('i', $rid);
        $chk->execute();
        $r2 = $chk->get_result();
        $rec = $r2 ? $r2->fetch_assoc() : null;
        if (!$rec || !in_array(strtolower(trim((string)($rec['status'] ?? ''))), ['ap', 'approved', 'mc', 'apd'], true)) {
            $chk->close();
            echo json_encode(['success' => false, 'message' => '此筆推薦尚未審核通過']);
            exit;
        }
        if (!$is_apd_status($rec['status'] ?? '')) {
            $waive_allowed = false;
        }
    }
    $chk->close();

    $allowed_target_ids = !empty($group_id_list) ? $group_id_list : [$rid];
    $allowed_target_ids = array_values(array_unique(array_filter(array_map('intval', $allowed_target_ids), function($v){ return $v > 0; })));

    $selected_review_ids = [];
    if ($selected_review_ids_raw !== '') {
        $selected_review_ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $selected_review_ids_raw)), function($v){ return $v > 0; })));
    }
    $allowed_id_map = [];
    foreach ($allowed_target_ids as $aid) $allowed_id_map[$aid] = true;
    $selected_target_ids = [];
    foreach ($selected_review_ids as $sid) {
        if (isset($allowed_id_map[$sid])) $selected_target_ids[] = $sid;
    }

    $decision_map = [];
    if ($review_decision_map_raw !== '') {
        $decoded = json_decode($review_decision_map_raw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $idKey => $decisionVal) {
                $did = intval($idKey);
                $dv = trim((string)$decisionVal);
                if ($did > 0 && isset($allowed_id_map[$did]) && in_array($dv, ['pass', 'fail'], true)) {
                    $decision_map[$did] = $dv;
                }
            }
        }
    }

    $is_signature_submit = ($waive_bonus !== 1 && $reject_reason === '' && ($signature !== '' || $signature_url !== ''));
    if ($is_signature_submit) {
        if (empty($decision_map)) {
            if (!in_array($review_decision, ['pass', 'fail'], true)) {
                echo json_encode(['success' => false, 'message' => '請先選擇通過或不通過']);
                exit;
            }
            if (empty($selected_target_ids)) {
                echo json_encode(['success' => false, 'message' => '請先勾選要審核的推薦人']);
                exit;
            }
            foreach ($selected_target_ids as $tid) $decision_map[$tid] = $review_decision;
        }
        if (empty($decision_map)) {
            echo json_encode(['success' => false, 'message' => '缺少可更新的審核項目']);
            exit;
        }
    }

    $public_path = '';
    if ($waive_bonus === 1) {
        if (!$waive_allowed) {
            echo json_encode(['success' => false, 'message' => '僅審核完成（可發獎金）的簽核可放棄獎金']);
            exit;
        }
        $upd = $conn->prepare("UPDATE recommendation_approval_links
            SET status = 'waived', reject_reason = ?, signed_at = NOW()
            WHERE token = ? LIMIT 1");
        $waive_reason = '放棄獎金';
        $upd->bind_param('ss', $waive_reason, $token);
        $upd->execute();
        $upd->close();

        // 若簽核者改為放棄獎金，重置同被推薦人的既有「已發送」紀錄，
        // 讓招生中心依最新可領人數重新手動發送（避免沿用舊平分金額）。
        $target_ids = !empty($group_id_list) ? $group_id_list : [$rid];
        $student_names = [];
        foreach ($target_ids as $tid) {
            $name_sql = $has_recommended_table
                ? "SELECT COALESCE(red.name,'') AS student_name
                   FROM admission_recommendations ar
                   LEFT JOIN recommended red ON ar.id = red.recommendations_id
                   WHERE ar.id = ? LIMIT 1"
                : "SELECT COALESCE(ar.student_name,'') AS student_name
                   FROM admission_recommendations ar
                   WHERE ar.id = ? LIMIT 1";
            $name_stmt = $conn->prepare($name_sql);
            if ($name_stmt) {
                $name_stmt->bind_param('i', $tid);
                $name_stmt->execute();
                $name_res = $name_stmt->get_result();
                if ($name_res && ($name_row = $name_res->fetch_assoc())) {
                    $sn = trim((string)($name_row['student_name'] ?? ''));
                    if ($sn !== '') $student_names[$sn] = true;
                }
                $name_stmt->close();
            }
        }

        $reset_ids = [];
        foreach (array_keys($student_names) as $sn) {
            $ids_sql = $has_recommended_table
                ? "SELECT ar.id
                   FROM admission_recommendations ar
                   LEFT JOIN recommended red ON ar.id = red.recommendations_id
                   WHERE red.name = ? AND COALESCE(ar.status,'') IN ('APD')"
                : "SELECT ar.id
                   FROM admission_recommendations ar
                   WHERE ar.student_name = ? AND COALESCE(ar.status,'') IN ('APD')";
            $ids_stmt = $conn->prepare($ids_sql);
            if ($ids_stmt) {
                $ids_stmt->bind_param('s', $sn);
                $ids_stmt->execute();
                $ids_res = $ids_stmt->get_result();
                if ($ids_res) {
                    while ($ids_row = $ids_res->fetch_assoc()) {
                        $idv = (int)($ids_row['id'] ?? 0);
                        if ($idv > 0) $reset_ids[$idv] = true;
                    }
                }
                $ids_stmt->close();
            }
        }

        if (!empty($reset_ids)) {
            $id_values = array_values(array_keys($reset_ids));
            $id_list_sql = implode(',', array_map('intval', $id_values));
            if ($id_list_sql !== '') {
                @$conn->query("DELETE FROM bonus_send_logs WHERE recommendation_id IN ({$id_list_sql})");
                @$conn->query("DELETE FROM bonus_send_email_logs WHERE recommendation_id IN ({$id_list_sql})");
            }
        }
    } elseif ($reject_reason === '') {
        // 依簽核頁「通過/不通過」決策更新勾選清單的狀態。
        $target_ids = !empty($decision_map) ? array_values(array_keys($decision_map)) : (!empty($selected_target_ids) ? $selected_target_ids : $allowed_target_ids);
        $status_stmt = $conn->prepare("SELECT {$status_expr} AS status FROM admission_recommendations WHERE id = ? LIMIT 1");
        $upd_status = $conn->prepare("UPDATE admission_recommendations SET status = ? WHERE id = ? LIMIT 1");
        if ($status_stmt && $upd_status) {
            ensure_application_status_code($conn, 'APD', '科主任已審核', 95);
            ensure_application_status_code($conn, 'APDR', '科主任審核未通過', 96);
            foreach ($target_ids as $tid) {
                $status_stmt->bind_param('i', $tid);
                $status_stmt->execute();
                $sres = $status_stmt->get_result();
                $srow = $sres ? $sres->fetch_assoc() : null;
                $st = strtolower(trim((string)($srow['status'] ?? '')));
                $decision_for_id = isset($decision_map[$tid]) ? $decision_map[$tid] : $review_decision;
                if ($decision_for_id === 'pass' && in_array($st, ['ap', 'approved', 'mc', 'apd'], true)) {
                    $new_status = 'APD';
                    $upd_status->bind_param('si', $new_status, $tid);
                    @$upd_status->execute();
                    if (function_exists('send_director_approved_email_once')) {
                        @send_director_approved_email_once($conn, $tid, 'director');
                    }
                } elseif ($decision_for_id === 'fail' && in_array($st, ['ap', 'approved', 'mc', 'apd', 'apdr'], true)) {
                    $new_status = 'APDR';
                    $upd_status->bind_param('si', $new_status, $tid);
                    @$upd_status->execute();
                }
            }
            $status_stmt->close();
            $upd_status->close();
        } else {
            if ($status_stmt) $status_stmt->close();
            if ($upd_status) $upd_status->close();
        }
        if ($signature_url !== '') {
            $url = $signature_url;
            if (!preg_match('/^https?:\/\//i', $url) && strpos($url, '/') !== 0) {
                $url = '/Topics-backend/frontend/' . ltrim($url, '/');
            }
            $public_path = $url;
        } else {
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
        }

        $upd = $conn->prepare("UPDATE recommendation_approval_links
            SET status = 'signed', signature_path = ?, signed_at = NOW()
            WHERE token = ? LIMIT 1");
        $upd->bind_param('ss', $public_path, $token);
        $upd->execute();
        $upd->close();
    } else {
        // 主任未通過：更新狀態為科主任審核未通過
        ensure_application_status_code($conn, 'APDR', '科主任審核未通過', 96);
        $upd_status = $conn->prepare("UPDATE admission_recommendations SET status = ? WHERE id = ? LIMIT 1");
        if ($upd_status) {
            $new_status = 'APDR';
            $target_ids = !empty($group_id_list) ? $group_id_list : [$rid];
            foreach ($target_ids as $tid) {
                $upd_status->bind_param('si', $new_status, $tid);
                @$upd_status->execute();
            }
            $upd_status->close();
        }
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
    if ($waive_bonus === 1) {
        $subject = '推薦人已放棄獎金';
        $body = "
            <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <p>推薦人已確認放棄本筆推薦獎金。</p>
                <p>學生：{$student_name}</p>
                <p>學校：{$student_school}</p>
                <p>聯絡電話：{$student_phone}</p>
            </div>
        ";
        $altBody = "推薦人已確認放棄本筆推薦獎金。\n學生：{$student_name}\n學校：{$student_school}\n聯絡電話：{$student_phone}";
    } elseif ($reject_reason === '' && !in_array('fail', array_values($decision_map), true) && $review_decision !== 'fail') {
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
        $notify_reason = ($reject_reason !== '') ? $reject_reason : '推薦人推薦資訊不通過';
        $subject = '科主任回覆不通過';
        $body = "
            <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <p>科主任回覆不通過。</p>
                <p>原因：{$notify_reason}</p>
                <p>學生：{$student_name}</p>
                <p>學校：{$student_school}</p>
                <p>聯絡電話：{$student_phone}</p>
            </div>
        ";
        $altBody = "科主任回覆不通過。\n原因：{$notify_reason}\n學生：{$student_name}\n學校：{$student_school}\n聯絡電話：{$student_phone}";
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
