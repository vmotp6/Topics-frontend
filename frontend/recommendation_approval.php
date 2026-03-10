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
        $stmt = $conn->prepare("SELECT recommendation_id, status, signature_path, group_ids, COALESCE(confirmed_by_email,'') AS confirmed_by_email FROM recommendation_approval_links WHERE token = ? LIMIT 1");
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
            // 預設不是「確認推薦對象」模式；需後續明確比對 email 才啟用
            $is_target_confirmation_mode = false;
            $is_signed = ($link['status'] === 'signed');
            $is_waived = ($link['status'] === 'waived');

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
                $data['is_waived'] = $is_waived ? 1 : 0;
                $data['signature_path'] = $link['signature_path'] ?? '';
                // 僅「寄給被推薦人」且同一批多筆時，才視為確認推薦對象模式。
                $link_email = strtolower(trim((string)($link['confirmed_by_email'] ?? '')));
                $student_email = strtolower(trim((string)($data['student_email'] ?? '')));
                if (!empty($group_id_list) && count($group_id_list) > 1 && $link_email !== '' && $student_email !== '' && $link_email === $student_email) {
                    $is_target_confirmation_mode = true;
                }
                $data['status'] = trim((string)($data['status'] ?? ''));
                $status_norm = strtolower($data['status']);
                $is_apd_status = ($status_norm === 'apd' || mb_strpos((string)$data['status'], '審核完成') !== false || mb_strpos((string)$data['status'], '可發獎金') !== false);
                if ($is_target_confirmation_mode) {
                    // 「確認推薦對象」流程固定走決策，不提供放棄獎金
                    $data['can_waive_bonus'] = 0;
                    $data['requires_review_decision'] = 1;
                } else {
                    $data['can_waive_bonus'] = $is_apd_status ? 1 : 0;
                    $data['requires_review_decision'] = $is_apd_status ? 0 : 1;
                }
                $data['is_target_confirmation_mode'] = $is_target_confirmation_mode ? 1 : 0;
                if (
                    !$is_signed &&
                    !$is_waived &&
                    ($link['status'] ?? '') !== 'rejected' &&
                    !$is_target_confirmation_mode &&
                    !in_array($status_norm, ['ap', 'approved', 'mc', 'apd'], true) &&
                    mb_strpos((string)$data['status'], '審核完成') === false
                ) {
                    $error = '此筆推薦尚未審核通過，無法簽核。';
                }
            }

            $same_recs = [];
            $same_recs_title = '同一推薦人所有推薦學生';
            if (!empty($group_id_list)) {
                $same_recs_title = '本次待審核推薦清單如下';
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

            // 去重：避免 recommender/recommended 關聯重複列造成同一推薦編號重複顯示
            // 若同一編號有多列，保留「資訊較完整」那筆，避免推薦資訊空白。
            if (!empty($same_recs)) {
                $score_row = function($row) {
                    $score = 0;
                    $fields = [
                        'recommender_name',
                        'recommender_student_id',
                        'recommendation_reason',
                        'student_name',
                        'student_school',
                        'student_phone',
                    ];
                    foreach ($fields as $f) {
                        if (trim((string)($row[$f] ?? '')) !== '') $score++;
                    }
                    return $score;
                };

                $dedup_map = [];
                foreach ($same_recs as $row) {
                    $rid_key = (int)($row['id'] ?? 0);
                    $uniq_key = ($rid_key > 0)
                        ? ('id:' . $rid_key)
                        : ('hash:' . md5(json_encode($row, JSON_UNESCAPED_UNICODE)));

                    if (!isset($dedup_map[$uniq_key])) {
                        $dedup_map[$uniq_key] = $row;
                        continue;
                    }

                    $old_score = $score_row($dedup_map[$uniq_key]);
                    $new_score = $score_row($row);
                    if ($new_score > $old_score) {
                        $dedup_map[$uniq_key] = $row;
                    }
                }
                $same_recs = array_values($dedup_map);
            }

            // 清單僅顯示「其他待審核」資料，避免與頁面上方主資料（當前推薦編號）重複。
            if (!empty($same_recs)) {
                $same_recs = array_values(array_filter($same_recs, function($row) use ($rid) {
                    return (int)($row['id'] ?? 0) !== (int)$rid;
                }));
            }

            // 年級/科系代碼轉顯示名稱（例如 F5 -> 五專五年級、IM -> 資訊管理科）
            $department_name_map = [];
            $grade_name_map = [];
            $school_name_map = [];
            try {
                $t_dept = $conn->query("SHOW TABLES LIKE 'departments'");
                if ($t_dept && $t_dept->num_rows > 0) {
                    $r_dept = $conn->query("SELECT code, name FROM departments");
                    if ($r_dept) {
                        while ($dr = $r_dept->fetch_assoc()) {
                            $code = trim((string)($dr['code'] ?? ''));
                            $name = trim((string)($dr['name'] ?? ''));
                            if ($code !== '' && $name !== '') $department_name_map[$code] = $name;
                        }
                    }
                }

                $t_grade = $conn->query("SHOW TABLES LIKE 'identity_options'");
                if ($t_grade && $t_grade->num_rows > 0) {
                    $r_grade = $conn->query("SELECT code, name FROM identity_options");
                    if ($r_grade) {
                        while ($gr = $r_grade->fetch_assoc()) {
                            $code = trim((string)($gr['code'] ?? ''));
                            $name = trim((string)($gr['name'] ?? ''));
                            if ($code !== '' && $name !== '') $grade_name_map[$code] = $name;
                        }
                    }
                }

                $t_school = $conn->query("SHOW TABLES LIKE 'school_data'");
                if ($t_school && $t_school->num_rows > 0) {
                    $r_school = $conn->query("SELECT school_code, name FROM school_data");
                    if ($r_school) {
                        while ($sr = $r_school->fetch_assoc()) {
                            $code = trim((string)($sr['school_code'] ?? ''));
                            $name = trim((string)($sr['name'] ?? ''));
                            if ($code !== '' && $name !== '') $school_name_map[$code] = $name;
                        }
                    }
                }
            } catch (Exception $e) {
                // ignore mapping failures and keep original text
            }

            $resolve_dept_name = function($raw) use ($department_name_map) {
                $v = trim((string)$raw);
                if ($v === '') return '';
                // 支援多科系代碼（例如：IM,LTC）轉中文名稱顯示
                $parts = preg_split('/\s*[,，]\s*/u', $v);
                if (!$parts || count($parts) <= 1) {
                    if (isset($department_name_map[$v])) return (string)$department_name_map[$v];
                    $up = strtoupper($v);
                    if (isset($department_name_map[$up])) return (string)$department_name_map[$up];
                    return $v;
                }
                $mapped = [];
                foreach ($parts as $p) {
                    $p = trim((string)$p);
                    if ($p === '') continue;
                    if (isset($department_name_map[$p])) {
                        $mapped[] = (string)$department_name_map[$p];
                    } else {
                        $up = strtoupper($p);
                        $mapped[] = isset($department_name_map[$up]) ? (string)$department_name_map[$up] : $p;
                    }
                }
                if (empty($mapped)) return $v;
                // 去重且保留順序
                $uniq = [];
                $seen = [];
                foreach ($mapped as $name) {
                    if (isset($seen[$name])) continue;
                    $seen[$name] = true;
                    $uniq[] = $name;
                }
                return implode('、', $uniq);
            };
            $resolve_grade_name = function($raw) use ($grade_name_map) {
                $v = trim((string)$raw);
                if ($v === '') return '';
                if (isset($grade_name_map[$v])) return (string)$grade_name_map[$v];
                $up = strtoupper($v);
                if (isset($grade_name_map[$up])) return (string)$grade_name_map[$up];
                return $v;
            };
            $resolve_school_name = function($raw) use ($school_name_map) {
                $v = trim((string)$raw);
                if ($v === '') return '';
                if (isset($school_name_map[$v])) return (string)$school_name_map[$v];
                return $v;
            };
            $apply_display_name = function(&$row) use ($resolve_dept_name, $resolve_grade_name, $resolve_school_name) {
                if (!is_array($row)) return;
                $row['student_grade'] = $resolve_grade_name($row['student_grade'] ?? '');
                $row['recommender_grade'] = $resolve_grade_name($row['recommender_grade'] ?? '');
                $row['recommender_department'] = $resolve_dept_name($row['recommender_department'] ?? '');
                $row['student_interest'] = $resolve_dept_name($row['student_interest'] ?? '');
                $row['student_school'] = $resolve_school_name($row['student_school'] ?? '');
            };

            if (is_array($data)) $apply_display_name($data);
            if (!empty($same_recs)) {
                foreach ($same_recs as &$rec_row) $apply_display_name($rec_row);
                unset($rec_row);
            }

            // 決策勾選清單：每筆推薦資料獨立一列（不再合併同推薦人）
            $decision_recommenders = [];
            $decision_rows = [];
            if (is_array($data)) $decision_rows[] = $data;
            if (!empty($same_recs)) {
                foreach ($same_recs as $r) $decision_rows[] = $r;
            }
            $decision_seen_ids = [];
            foreach ($decision_rows as $dr) {
                if (!is_array($dr)) continue;
                $dr_id = (int)($dr['id'] ?? 0);
                if ($dr_id <= 0) continue;
                if (isset($decision_seen_ids[$dr_id])) continue;
                $decision_seen_ids[$dr_id] = true;
                $decision_recommenders[] = [
                    'recommender_name' => trim((string)($dr['recommender_name'] ?? '')),
                    'recommender_student_id' => trim((string)($dr['recommender_student_id'] ?? '')),
                    'recommender_department' => trim((string)($dr['recommender_department'] ?? '')),
                    'student_name' => trim((string)($dr['student_name'] ?? '')),
                    'student_school' => trim((string)($dr['student_school'] ?? '')),
                    'ids' => [$dr_id]
                ];
            }
        }
        // 初審未通過（待科主任審核）時，標題改為「請審核學生資訊是否正確?」
        $is_preliminary_fail_mode = false;
        if ((int)($data['is_target_confirmation_mode'] ?? 0) === 1 && !empty($decision_rows)) {
            foreach ($decision_rows as $dr) {
                $st = trim((string)($dr['status'] ?? ''));
                if (strtolower($st) === 'mc' || strtolower($st) === 'manual'
                    || mb_strpos($st, '初審未通過（待科主任審核）') !== false
                    || mb_strpos($st, '需人工審查') !== false) {
                    $is_preliminary_fail_mode = true;
                    break;
                }
            }
        }
        // 被推薦人姓名相同：需顯示「是否為同一人」問題
        $has_duplicate_student_names = false;
        if ((int)($data['is_target_confirmation_mode'] ?? 0) === 1 && count($decision_recommenders) >= 2) {
            $name_count = [];
            foreach ($decision_recommenders as $dr) {
                $sn = trim((string)($dr['student_name'] ?? ''));
                if ($sn === '') continue;
                $name_count[$sn] = ($name_count[$sn] ?? 0) + 1;
            }
            foreach ($name_count as $cnt) {
                if ($cnt >= 2) {
                    $has_duplicate_student_names = true;
                    break;
                }
            }
        }
    }
    if (!isset($has_duplicate_student_names)) $has_duplicate_student_names = false;
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
    h2 { margin:0 0 12px 0; color:#588dd1; font-size: 34px; }
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
    .waive-q { margin-bottom: 12px; color:#cf1322; font-weight: 700; font-size: 21px; }
    .btn-warning {
      background:#fa8c16;
      color:#fff;
      padding: 10px 20px;
      font-size: 18px;
      border-radius: 10px;
    }
    .signature-note {
      color: #69b1ff;
      font-size: 24px;
      font-weight: 700;
      margin: 8px 0 10px 0;
    }
    .review-required-title {
      color: #003366;
      font-size: 24px;
      font-weight: 700;
      margin: 6px 0 10px 0;
    }
    .review-decision-wrap {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 10px;
    }
    .review-decision-box {
      border: 1px solid #d9d9d9;
      border-radius: 8px;
      background: #fafafa;
      padding: 14px;
      margin-bottom: 12px;
    }
    .decision-check-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 8px;
      background: #fff;
    }
    .decision-check-table th,
    .decision-check-table td {
      border: 1px solid #d9d9d9;
      padding: 10px 12px;
      text-align: left;
      font-size: 20px;
    }
    .decision-check-table th {
      background: #f5f5f5;
      font-weight: 700;
      color: #333;
    }
    .decision-check-col {
      width: 86px;
      text-align: center !important;
    }
    .decision-rec-checkbox {
      width: 20px;
      height: 20px;
      cursor: pointer;
    }
    .btn-pass { background:#52c41a; color:#fff; }
    .btn-fail { background:#ff4d4f; color:#fff; }
    .decision-reason-label {
      margin-top: 4px;
      margin-bottom: 6px;
      color: #595959;
      font-size: 14px;
      font-weight: 700;
    }
    .decision-reason-input {
      width: 100%;
      min-height: 84px;
      border: 1px solid #d9d9d9;
      border-radius: 8px;
      padding: 10px;
      font-size: 14px;
      resize: vertical;
      box-sizing: border-box;
      margin-bottom: 8px;
      background: #fff;
    }
    .decision-result-note {
      font-size: 22px;
      font-weight: 700;
      margin: 8px 0 10px 0;
      color: #69b1ff;
    }
    .decision-inline-select,
    .decision-inline-reason {
      width: 100%;
      box-sizing: border-box;
      border: 1px solid #d9d9d9;
      border-radius: 6px;
      padding: 6px 8px;
      font-size: 15px;
      background: #fff;
    }
    .decision-inline-reason {
      min-height: 64px;
      resize: vertical;
    }
    .decision-action-wrap {
      display: flex;
      gap: 8px;
      align-items: center;
      flex-wrap: wrap;
    }
    .decision-bulk-wrap {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 8px;
    }
    .decision-cell-btn {
      padding: 6px 10px;
      border: none;
      border-radius: 6px;
      color: #fff;
      font-weight: 700;
      cursor: pointer;
    }
    .decision-cell-btn.pass { background: #52c41a; }
    .decision-cell-btn.fail { background: #ff4d4f; }
    .decision-status-text {
      font-weight: 700;
      font-size: 15px;
      display: none;
    }
    .modal-mask {
      position: fixed;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.45);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
    }
    .modal-card {
      width: 92%;
      max-width: 520px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 8px 28px rgba(0,0,0,0.22);
      overflow: hidden;
    }
    .modal-head { padding: 16px 18px; border-bottom: 1px solid #f0f0f0; font-weight: 700; }
    .modal-body { padding: 20px 18px; color: #262626; line-height: 1.8; }
    .modal-foot {
      padding: 14px 18px;
      border-top: 1px solid #f0f0f0;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }
    .rec-list-top-divider,
    .rec-separator {
      width: 100%;
      height: 2px;
      margin: 12px 0 14px 0;
      background-image: repeating-linear-gradient(
        to right,
        #e3e8ef 0px,
        #e3e8ef 8px,
        transparent 8px,
        transparent 12px
      );
      opacity: 1;
    }
    .rec-separator { margin: 18px 0 14px 0; }
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
      <?php if (!empty($same_recs) && count($same_recs) > 0): ?>
      <div class="detail-section" style="margin-top:12px; padding-top:12px;">
        <h4 class="detail-title"><?php echo htmlspecialchars($same_recs_title ?? '同一推薦人所有推薦學生'); ?></h4>
        <div class="rec-list-top-divider"></div>
        <?php $same_recs_count = count($same_recs); ?>
        <?php foreach ($same_recs as $idx => $row): ?>
          <?php
            $show_sep_before = false;
            if ((int)$idx > 0) {
                $prev = $same_recs[$idx - 1] ?? [];
                $prev_name = trim((string)($prev['recommender_name'] ?? ''));
                $prev_sid = trim((string)($prev['recommender_student_id'] ?? ''));
                $cur_name = trim((string)($row['recommender_name'] ?? ''));
                $cur_sid = trim((string)($row['recommender_student_id'] ?? ''));
                $show_sep_before = (($prev_name . '|' . $prev_sid) !== ($cur_name . '|' . $cur_sid));
            }
          ?>
          <?php if ($show_sep_before): ?><div class="rec-separator"></div><?php endif; ?>
          <div style="margin-top:12px; padding-top:2px;">
            <div class="detail-wrap" style="margin-top:8px;">
              <div class="detail-card">
                <h4 class="detail-title">被推薦人資訊</h4>
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
                <h4 class="detail-title">推薦人資訊</h4>
                <table class="detail-table">
                  <tr><td class="label">姓名</td><td><?php echo htmlspecialchars($row['recommender_name'] ?? ''); ?></td></tr>
                  <tr><td class="label">學號/教師編號</td><td><?php echo htmlspecialchars($row['recommender_student_id'] ?? ''); ?></td></tr>
                  <tr><td class="label">年級</td><td><?php echo htmlspecialchars($row['recommender_grade'] ?? ''); ?></td></tr>
                  <tr><td class="label">科系</td><td><?php echo htmlspecialchars($row['recommender_department'] ?? ''); ?></td></tr>
                  <tr><td class="label">聯絡電話</td><td><?php echo htmlspecialchars($row['recommender_phone'] ?? ''); ?></td></tr>
                  <tr><td class="label">電子郵件</td><td><?php echo htmlspecialchars($row['recommender_email'] ?? ''); ?></td></tr>
                </table>
              </div>
            </div>
            <div class="detail-section" style="margin-top:12px;">
              <h4 class="detail-title">推薦資訊</h4>
              <table class="detail-table">
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

      </div>

      <div class="section">
        <?php if ((int)($data['is_waived'] ?? 0) === 1): ?>
          <div class="alert">您已選擇放棄獎金，招生中心將無法再發送推薦獎金。</div>
        <?php elseif ((int)$data['is_signed'] === 1): ?>
          <div class="alert success">已完成線上審核，無法再進行簽核。</div>
        <?php else: ?>
          <?php if ((int)($data['requires_review_decision'] ?? 1) === 1): ?>
          <?php if (!empty($has_duplicate_student_names)): ?>
          <div class="same-person-q-box" style="margin-bottom:18px; padding:16px 18px; background:#fff7e6; border:1px solid #ffd591; border-radius:10px;">
            <div class="same-person-q-title" style="font-weight:700; color:#ad6800; margin-bottom:10px;">被推薦人姓名相同請選擇是否為同一人? <span style="color:#d4380d;">（必填）</span></div>
            <div class="btns" style="margin-top:0; margin-bottom:8px;">
              <button type="button" class="btn-primary same-person-btn" data-value="yes" onclick="setSamePersonChoice('yes')">是</button>
              <button type="button" class="btn-secondary same-person-btn" data-value="no" onclick="setSamePersonChoice('no')">否</button>
            </div>
            <div id="samePersonResultText" style="font-weight:600; margin-top:8px; min-height:24px;"></div>
          </div>
          <?php endif; ?>
          <div class="review-decision-box">
            <div class="review-required-title"><?php echo ((int)($data['is_target_confirmation_mode'] ?? 0) === 1) ? (isset($is_preliminary_fail_mode) && $is_preliminary_fail_mode ? '請審核學生資訊是否正確?' : '請問以下推薦人你覺得誰比較有與你聯絡?(可複選)') : '推薦人推薦資訊是否通過?'; ?></div>
            <?php if (!empty($decision_recommenders)): ?>
            <div class="decision-bulk-wrap">
              <button type="button" class="btn-pass" onclick="openBulkPassConfirm()">全部通過</button>
            </div>
            <table class="decision-check-table">
              <thead>
                <tr>
                  <th>推薦人姓名</th>
                  <th>推薦人學號/編號</th>
                  <th>推薦人科系</th>
                  <th>通過/不通過</th>
                  <th>不通過原因</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($decision_recommenders as $dr): ?>
                <?php $id_csv = implode(',', array_map('intval', (array)($dr['ids'] ?? []))); ?>
                <?php $first_id = (int)($dr['ids'][0] ?? 0); ?>
                <tr>
                  <td><?php echo htmlspecialchars($dr['recommender_name'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($dr['recommender_student_id'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($dr['recommender_department'] ?? ''); ?></td>
                  <td style="min-width:150px;">
                    <div class="decision-action-wrap" data-rec-id="<?php echo (int)$first_id; ?>">
                      <button type="button" class="decision-cell-btn pass" onclick="openRowDecisionConfirm(<?php echo (int)$first_id; ?>, 'pass')">通過</button>
                      <button type="button" class="decision-cell-btn fail" onclick="openRowDecisionConfirm(<?php echo (int)$first_id; ?>, 'fail')">不通過</button>
                      <span class="decision-status-text" id="decisionStatusText-<?php echo (int)$first_id; ?>"></span>
                    </div>
                  </td>
                  <td style="min-width:220px;">
                    <textarea class="decision-inline-reason decision-row-reason" data-rec-id="<?php echo (int)$first_id; ?>" data-rec-ids="<?php echo htmlspecialchars($id_csv); ?>" placeholder="不通過時必填"></textarea>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php else: ?>
            <div class="alert" style="margin-bottom:10px;">查無可勾選的推薦人清單，請重新整理後再試。</div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          <?php if ((int)($data['can_waive_bonus'] ?? 0) === 1): ?>
          <div class="waive-q">是否放棄獎金 ?</div>
            <div class="btns" style="margin-top:0; margin-bottom:12px;">
              <button class="btn-warning" type="button" onclick="openWaiveConfirm()" id="waiveBonusBtn">放棄</button>
            </div>
            <div id="waivePendingNotice" style="display: none; background: #fff7e6; border: 1px solid #ffd591; color: #ad6800; padding: 14px 18px; border-radius: 8px; margin-bottom: 16px;">
              <strong>您已選擇放棄獎金。</strong> 請於下方完成線上簽核，簽核完成後放棄獎金即生效。
            </div>
          <?php endif; ?>
          <div class="signature-note">若資訊確認無誤請進行線上簽核，以便招生中心後續作業</div>
          <div class="label">線上簽章</div>
          <iframe
            id="signatureFrame"
            src="/Topics-backend/frontend/signature.php?document_id=<?php echo urlencode((string)($data['id'] ?? '')); ?>&document_type=admission_recommendation&embed=1"
            style="width:100%; min-height:720px; border:1px dashed #bbb; border-radius:10px; background:#fff;"
          ></iframe>
          <div class="muted">請於上方區塊完成電子簽章後，系統會自動回填簽核。</div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div id="waiveConfirmModal" class="modal-mask">
    <div class="modal-card">
      <div class="modal-head">提醒</div>
      <div class="modal-body">放棄後無法拿到推薦獎金；需同時完成下方線上簽核，放棄獎金才會生效。是否要放棄？</div>
      <div class="modal-foot">
        <button class="btn-secondary" type="button" onclick="closeWaiveConfirm()">再想想</button>
        <button class="btn-danger" type="button" onclick="confirmWaiveThenSign()">確定放棄</button>
      </div>
    </div>
  </div>

  <div id="decisionConfirmModal" class="modal-mask">
    <div class="modal-card">
      <div class="modal-head">提醒</div>
      <div class="modal-body" id="decisionConfirmText"></div>
      <div class="modal-foot">
        <button class="btn-secondary" type="button" onclick="closeDecisionConfirm()">取消</button>
        <button class="btn-primary" type="button" onclick="confirmDecision()">確定</button>
      </div>
    </div>
  </div>

  <script>
    const token = <?php echo json_encode($token); ?>;
    const requiresReviewDecision = <?php echo ((int)($data['requires_review_decision'] ?? 1) === 1) ? 'true' : 'false'; ?>;
    const isTargetConfirmationMode = <?php echo ((int)($data['is_target_confirmation_mode'] ?? 0) === 1) ? 'true' : 'false'; ?>;
    const hasDuplicateStudentNames = <?php echo !empty($has_duplicate_student_names) ? 'true' : 'false'; ?>;
    let pendingDecisionType = '';
    let pendingDecisionRecId = 0;
    let pendingBulkPass = false;
    const decidedIdMap = {};
    const decidedReasonMap = {};
    let pendingWaiveBonus = false;
    let samePersonChoice = '';

    function setSamePersonChoice(val) {
      samePersonChoice = (val === 'yes' || val === 'no') ? val : '';
      const btns = document.querySelectorAll('.same-person-btn');
      btns.forEach(btn => {
        const v = btn.getAttribute('data-value');
        btn.classList.toggle('btn-primary', v === val);
        btn.classList.toggle('btn-secondary', v !== val);
      });
      const resultEl = document.getElementById('samePersonResultText');
      if (resultEl) {
        resultEl.textContent = val === 'yes' ? '被推薦人為同一人' : (val === 'no' ? '被推薦人為不同人' : '');
      }
    }

    function openRowDecisionConfirm(recId, type) {
      const id = parseInt(String(recId || '0'), 10);
      if (!id) return;
      const decision = (type === 'pass') ? 'pass' : 'fail';
      const reasonEl = document.querySelector('.decision-row-reason[data-rec-id="' + String(id) + '"]');
      const reasonText = reasonEl ? String(reasonEl.value || '').trim() : '';
      if (decision === 'fail' && !reasonText) {
        alert('請先填寫不通過原因');
        if (reasonEl) reasonEl.focus();
        return;
      }
      pendingDecisionRecId = id;
      pendingDecisionType = decision;
      const textEl = document.getElementById('decisionConfirmText');
      const modal = document.getElementById('decisionConfirmModal');
      if (textEl) {
        textEl.textContent = decision === 'pass'
          ? '確認是否為通過?'
          : '確認是否為不通過?';
      }
      if (modal) modal.style.display = 'flex';
    }

    function openBulkPassConfirm() {
      const rowWrapEls = Array.from(document.querySelectorAll('.decision-action-wrap[data-rec-id]'));
      if (!rowWrapEls.length) {
        alert('查無可設定的推薦人清單。');
        return;
      }
      pendingBulkPass = true;
      pendingDecisionRecId = 0;
      pendingDecisionType = '';
      const textEl = document.getElementById('decisionConfirmText');
      const modal = document.getElementById('decisionConfirmModal');
      if (textEl) textEl.textContent = '是否確定為全部通過?';
      if (modal) modal.style.display = 'flex';
    }

    function closeDecisionConfirm() {
      const modal = document.getElementById('decisionConfirmModal');
      if (modal) modal.style.display = 'none';
      pendingDecisionType = '';
      pendingDecisionRecId = 0;
      pendingBulkPass = false;
    }

    function confirmDecision() {
      if (pendingBulkPass) {
        const rowWrapEls = Array.from(document.querySelectorAll('.decision-action-wrap[data-rec-id]'));
        rowWrapEls.forEach(wrapEl => {
          const id = parseInt(String(wrapEl.getAttribute('data-rec-id') || '0'), 10);
          if (!id) return;
          decidedIdMap[id] = 'pass';
          if (decidedReasonMap[id]) delete decidedReasonMap[id];
          const btns = wrapEl.querySelectorAll('button.decision-cell-btn');
          btns.forEach(btn => {
            btn.style.display = 'none';
            btn.disabled = true;
          });
          const statusTextEl = document.getElementById('decisionStatusText-' + String(id));
          if (statusTextEl) {
            statusTextEl.style.display = 'inline';
            statusTextEl.textContent = '通過';
            statusTextEl.style.color = '#389e0d';
          }
        });
        closeDecisionConfirm();
        return;
      }
      if (!pendingDecisionType || !pendingDecisionRecId) {
        closeDecisionConfirm();
        return;
      }
      const id = pendingDecisionRecId;
      const decision = pendingDecisionType;
      const reasonEl = document.querySelector('.decision-row-reason[data-rec-id="' + String(id) + '"]');
      const reasonText = reasonEl ? String(reasonEl.value || '').trim() : '';
      decidedIdMap[id] = decision;
      if (decision === 'fail') {
        decidedReasonMap[id] = reasonText;
      } else if (decidedReasonMap[id]) {
        delete decidedReasonMap[id];
      }
      const wrap = document.querySelector('.decision-action-wrap[data-rec-id="' + String(id) + '"]');
      const statusTextEl = document.getElementById('decisionStatusText-' + String(id));
      if (wrap) {
        const btns = wrap.querySelectorAll('button.decision-cell-btn');
        btns.forEach(btn => {
          btn.style.display = 'none';
          btn.disabled = true;
        });
      }
      if (statusTextEl) {
        statusTextEl.style.display = 'inline';
        statusTextEl.textContent = (decision === 'pass') ? '通過' : '不通過';
        statusTextEl.style.color = (decision === 'pass') ? '#389e0d' : '#cf1322';
      }
      closeDecisionConfirm();
    }

    window.addEventListener('message', function(event) {
      if (event.origin !== window.location.origin) return;
      const payload = event.data || {};
      if (payload.type === 'signature_saved' && payload.signature_url) {
        submitSignatureUrl(payload.signature_url);
      }
    });

    function submitSignatureUrl(signatureUrl) {
      let payloadDecisionMap = Object.assign({}, decidedIdMap);
      let payloadReasonMap = Object.assign({}, decidedReasonMap);

      if (requiresReviewDecision && !pendingWaiveBonus) {
        const rowWrapEls = Array.from(document.querySelectorAll('.decision-action-wrap[data-rec-id]'));
        if (!rowWrapEls.length) {
          alert('查無可填寫的推薦人清單，請重新整理後再試。');
          return;
        }
        let passCount = 0;
        for (const wrapEl of rowWrapEls) {
          const firstId = parseInt(String(wrapEl.getAttribute('data-rec-id') || '0'), 10);
          if (!firstId) continue;
          const decisionVal = String(payloadDecisionMap[firstId] || '').trim();
          if (!decisionVal || (decisionVal !== 'pass' && decisionVal !== 'fail')) {
            alert('請先點擊每位推薦人的通過/不通過按鈕並完成確認。');
            return;
          }
          const reasonEl = document.querySelector('.decision-row-reason[data-rec-id="' + String(firstId) + '"]');
          const reasonVal = reasonEl ? String(reasonEl.value || '').trim() : '';
          if (decisionVal === 'fail' && !reasonVal) {
            alert('不通過時，請填寫不通過原因。');
            if (reasonEl) reasonEl.focus();
            return;
          }
          if (reasonVal) payloadReasonMap[firstId] = reasonVal;
          if (decisionVal === 'pass') passCount += 1;
        }
        if (!Object.keys(payloadDecisionMap).length) {
          alert('請先完成表格中的通過/不通過與理由。');
          return;
        }
        if (isTargetConfirmationMode && passCount <= 0) {
          alert('請至少選擇一位推薦人為通過（可複選）。');
          return;
        }
      }

      const allDecidedIds = Object.keys(payloadDecisionMap).map(v => parseInt(v, 10)).filter(v => v > 0);
      if (requiresReviewDecision && !allDecidedIds.length && !pendingWaiveBonus) {
        alert('請先完成「推薦人推薦資訊是否通過」必填選項。');
        return;
      }
      if (hasDuplicateStudentNames && (samePersonChoice !== 'yes' && samePersonChoice !== 'no')) {
        alert('請選擇「被推薦人姓名相同是否為同一人」的答案（是/否）。');
        return;
      }
      let body = 'token=' + encodeURIComponent(token)
        + '&signature_url=' + encodeURIComponent(signatureUrl)
        + '&review_decision_map=' + encodeURIComponent(JSON.stringify(payloadDecisionMap))
        + '&review_reason_map=' + encodeURIComponent(JSON.stringify(payloadReasonMap))
        + '&review_fail_reason_map=' + encodeURIComponent(JSON.stringify(payloadReasonMap));
      if (hasDuplicateStudentNames && samePersonChoice) {
        body += '&is_same_person=' + encodeURIComponent(samePersonChoice);
      }
      let isWaiveSubmit = false;
      if (pendingWaiveBonus) {
        body += '&waive_bonus=1';
        isWaiveSubmit = true;
        pendingWaiveBonus = false;
        const noticeEl = document.getElementById('waivePendingNotice');
        if (noticeEl) noticeEl.style.display = 'none';
        const waiveBtn = document.getElementById('waiveBonusBtn');
        if (waiveBtn) { waiveBtn.disabled = false; waiveBtn.style.opacity = '1'; }
      }
      fetch('recommendation_approval_submit.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert(isWaiveSubmit ? '放棄獎金已生效，招生中心將無法發送獎金。' : '簽核完成，已通知招生中心。');
          location.reload();
        } else {
          alert('簽核失敗：' + (data.message || '未知錯誤'));
        }
      })
      .catch(() => alert('簽核失敗：網路錯誤'));
    }

    function openWaiveConfirm() {
      const modal = document.getElementById('waiveConfirmModal');
      if (modal) modal.style.display = 'flex';
    }

    function closeWaiveConfirm() {
      const modal = document.getElementById('waiveConfirmModal');
      if (modal) modal.style.display = 'none';
    }

    function confirmWaiveThenSign() {
      closeWaiveConfirm();
      pendingWaiveBonus = true;
      const noticeEl = document.getElementById('waivePendingNotice');
      if (noticeEl) noticeEl.style.display = 'block';
      const waiveBtn = document.getElementById('waiveBonusBtn');
      if (waiveBtn) { waiveBtn.disabled = true; waiveBtn.style.opacity = '0.6'; }
      const frame = document.getElementById('signatureFrame');
      if (frame) frame.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    const waiveConfirmModal = document.getElementById('waiveConfirmModal');
    if (waiveConfirmModal) {
      waiveConfirmModal.addEventListener('click', function(e) {
        if (e.target === waiveConfirmModal) closeWaiveConfirm();
      });
    }
    const decisionConfirmModal = document.getElementById('decisionConfirmModal');
    if (decisionConfirmModal) {
      decisionConfirmModal.addEventListener('click', function(e) {
        if (e.target === decisionConfirmModal) closeDecisionConfirm();
      });
    }
  </script>
</body>
</html>
