<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理 OPTIONS 預檢請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // 引入資料庫設定
    $config_path = '../config.php';
    if (!file_exists($config_path)) {
        echo json_encode(['success' => false, 'error' => '找不到設定檔案: ' . $config_path], JSON_UNESCAPED_UNICODE);
        exit;
    }
    require_once $config_path;

    // 取得科系名稱參數（中文名稱或科代碼皆可）
    $department = isset($_GET['department']) ? trim($_GET['department']) : '';
    if ($department === '') {
        echo json_encode(['success' => false, 'error' => '科系名稱不能為空'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // 屆別（學年度民國年），0 表示全部
    $roc_year = isset($_GET['roc_year']) ? (int)$_GET['roc_year'] : 0;
    // 目前階段：continued_recruitment=續招, priority_exam=優先免試, joint_exam=聯合免試。「目前招生狀況」只統計本報名階段＋僅國三生
    // 若未傳 stage，則由 API 依目前日期與 department_quotas 自動判斷目前階段
    $stage = isset($_GET['stage']) ? trim($_GET['stage']) : '';

    // 建立資料庫連線（使用 mysqli）
    $conn = getDatabaseConnection();
    if (!$conn || $conn->connect_error) {
        echo json_encode(['success' => false, 'error' => '資料庫連線失敗'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $year_cond_sql = '';
    $year_cond_sql_ei = '';
    if ($roc_year > 0) {
        $ad_year = $roc_year + 1911;
        $start = $ad_year . '-06-01 00:00:00';
        $end = ($ad_year + 1) . '-06-30 23:59:59';
        $start_esc = $conn->real_escape_string($start);
        $end_esc = $conn->real_escape_string($end);
        $year_cond_sql   = " AND enrollment_intention.created_at >= '$start_esc' AND enrollment_intention.created_at <= '$end_esc' ";
        $year_cond_sql_ei = " AND ei.created_at >= '$start_esc' AND ei.created_at <= '$end_esc' ";
    }

    // 目前階段條件：只統計「本報名階段」內的資料（created_at 在該階段區間），且僅國三生（只用於 status_summary／目前招生狀況）
    // 若未傳 stage，依目前日期與 department_quotas 自動判斷目前階段
    $stage_cond_sql = '';
    $current_stage_display = '';
    $stage_display_names = [
        'continued_recruitment' => '續招階段',
        'priority_exam' => '優先免試',
        'joint_exam' => '聯合免試',
    ];
    if ($stage === '') {
        // 自動判斷目前階段（與就讀意願名單/科系名額管理一致）
        $cr_range = null;
        $tbl_dq = @$conn->query("SHOW TABLES LIKE 'department_quotas'");
        if ($tbl_dq && $tbl_dq->num_rows > 0) {
            $r = @$conn->query("SELECT MIN(register_start) AS min_start, MAX(register_end) AS max_end FROM department_quotas WHERE is_active = 1 AND register_start IS NOT NULL AND register_end IS NOT NULL");
            if ($r && $r->num_rows > 0) {
                $row = $r->fetch_assoc();
                if (!empty($row['min_start']) && !empty($row['max_end'])) {
                    $cr_range = ['start' => $row['min_start'], 'end' => $row['max_end']];
                }
                $r->free();
            }
            $tbl_dq->free();
        }
        if ($cr_range) {
            try {
                $tz = new DateTimeZone('Asia/Taipei');
                $now = new DateTime('now', $tz);
                $start = new DateTime($cr_range['start'], $tz);
                $end = new DateTime($cr_range['end'], $tz);
                if ($now >= $start && $now <= $end) {
                    $stage = 'continued_recruitment';
                }
            } catch (Exception $e) { }
        }
        if ($stage === '') {
            $m = (int)date('n');
            if ($m >= 5 && $m < 6) {
                $stage = 'priority_exam';
            } elseif ($m >= 6 && $m < 8) {
                $stage = 'joint_exam';
            }
        }
    }
    if ($stage !== '') {
        $ad_year = $roc_year > 0 ? $roc_year + 1911 : (date('n') >= 6 ? date('Y') : date('Y') - 1);
        if ($stage === 'continued_recruitment') {
            $tbl_dq = @$conn->query("SHOW TABLES LIKE 'department_quotas'");
            if ($tbl_dq && $tbl_dq->num_rows > 0) {
                $r = @$conn->query("SELECT MIN(register_start) AS min_start, MAX(register_end) AS max_end FROM department_quotas WHERE is_active = 1 AND register_start IS NOT NULL AND register_end IS NOT NULL");
                if ($r && $r->num_rows > 0) {
                    $row = $r->fetch_assoc();
                    if (!empty($row['min_start']) && !empty($row['max_end'])) {
                        $start_esc = $conn->real_escape_string($row['min_start']);
                        $end_esc = $conn->real_escape_string($row['max_end']);
                        $stage_cond_sql = " AND enrollment_intention.created_at >= '$start_esc' AND enrollment_intention.created_at <= '$end_esc' ";
                    }
                    $r->free();
                }
                $tbl_dq->free();
            }
        } elseif ($stage === 'priority_exam') {
            $start_esc = $conn->real_escape_string($ad_year . '-05-01 00:00:00');
            $end_esc = $conn->real_escape_string($ad_year . '-05-31 23:59:59');
            $stage_cond_sql = " AND enrollment_intention.created_at >= '$start_esc' AND enrollment_intention.created_at <= '$end_esc' ";
        } elseif ($stage === 'joint_exam') {
            $start_esc = $conn->real_escape_string($ad_year . '-06-01 00:00:00');
            $end_esc = $conn->real_escape_string($ad_year . '-07-31 23:59:59');
            $stage_cond_sql = " AND enrollment_intention.created_at >= '$start_esc' AND enrollment_intention.created_at <= '$end_esc' ";
        }
        $current_stage_display = $stage_display_names[$stage] ?? $stage;
    }

    // 檢查 enrollment_intention 表是否存在
    $tbl_check = $conn->query("SHOW TABLES LIKE 'enrollment_intention'");
    if (!$tbl_check || $tbl_check->num_rows === 0) {
        // 沒有就讀意願表，直接回傳空資料
        echo json_encode([
            'success' => true,
            'department' => $department,
            'department_codes' => [],
            'status_summary' => [
                'total_assigned' => 0,
                'applied' => 0,
                'checked_in' => 0,
                'declined' => 0,
                'tracking' => 0,
            ],
            'grades'  => [],
            'schools' => []
        ], JSON_UNESCAPED_UNICODE);
        $conn->close();
        exit;
    }

    // 取得 enrollment_intention 欄位結構
    $cols_rs = $conn->query("DESCRIBE enrollment_intention");
    $columns = [];
    while ($col = $cols_rs->fetch_assoc()) {
        $columns[] = $col['Field'];
    }

    $has_assigned_department = in_array('assigned_department', $columns, true);
    $has_is_registered       = in_array('is_registered', $columns, true);
    $has_check_in_status     = in_array('check_in_status', $columns, true);
    $has_follow_up_status    = in_array('follow_up_status', $columns, true);
    $has_junior_high         = in_array('junior_high', $columns, true);
    $has_current_grade       = in_array('current_grade', $columns, true);
    $has_priority_exam_registered    = in_array('priority_exam_registered', $columns, true);
    $has_joint_exam_registered      = in_array('joint_exam_registered', $columns, true);
    $has_continued_recruitment_reg  = in_array('continued_recruitment_registered', $columns, true);

    // 僅國三生條件（「目前招生狀況」只算國三；有傳 stage 時才套用在 status_summary）
    $grade_cond_sql = '';
    if ($has_current_grade && $stage !== '') {
        $grade_cond_sql = " AND (enrollment_intention.current_grade = 'J3' OR enrollment_intention.current_grade = '國三') ";
    }

    // 本階段已報名欄位（用於：已報名=本階段已報名且未報到未放棄、已報到、放棄、追蹤=本階段還沒報名）
    $stage_registered_col = null;
    if ($stage === 'continued_recruitment' && $has_continued_recruitment_reg) {
        $stage_registered_col = 'continued_recruitment_registered';
    } elseif ($stage === 'priority_exam' && $has_priority_exam_registered) {
        $stage_registered_col = 'priority_exam_registered';
    } elseif ($stage === 'joint_exam' && $has_joint_exam_registered) {
        $stage_registered_col = 'joint_exam_registered';
    }

    // 若沒有 assigned_department 欄位，則無法以「分配到本系」為基準，只回傳空資料避免 SQL 錯誤
    if (!$has_assigned_department) {
        echo json_encode([
            'success' => true,
            'department' => $department,
            'department_codes' => [],
            'status_summary' => [
                'total_assigned' => 0,
                'applied' => 0,
                'checked_in' => 0,
                'declined' => 0,
                'tracking' => 0,
            ],
            'grades'  => [],
            'schools' => []
        ], JSON_UNESCAPED_UNICODE);
        $conn->close();
        exit;
    }

    // 先嘗試從 departments 依中文名稱找科代碼；找不到時，將傳入值視為 code 使用
    $dept_codes = [];
    $stmt = $conn->prepare("SELECT code FROM departments WHERE name = ? OR code = ?");
    if ($stmt) {
        $stmt->bind_param('ss', $department, $department);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            if (!empty($row['code'])) {
                $dept_codes[] = $row['code'];
            }
        }
        $stmt->close();
    }

    if (empty($dept_codes)) {
        // 若找不到對應資料，就直接用傳入的參數當作科代碼比對
        $dept_codes[] = $department;
    }

    // 構建 IN 子句（安全轉義）
    $escaped_codes = array_map(function ($c) use ($conn) {
        return "'" . $conn->real_escape_string($c) . "'";
    }, $dept_codes);
    $in_clause = implode(', ', $escaped_codes);

    // --------- 1. 總人數（目前招生狀況：國三＋分配到本系；有本階段欄位時不依 created_at 篩階段，以「本階段已/未報名」區分）---------
    $total_assigned = 0;
    $base_cond = " assigned_department IN ($in_clause) $year_cond_sql $grade_cond_sql ";
    $base_cond_with_stage = " assigned_department IN ($in_clause) $year_cond_sql $stage_cond_sql $grade_cond_sql ";
    if ($stage_registered_col !== null) {
        $sql_total = "SELECT COUNT(*) AS cnt FROM enrollment_intention WHERE $base_cond";
    } else {
        $sql_total = "SELECT COUNT(*) AS cnt FROM enrollment_intention WHERE $base_cond_with_stage";
    }
    if ($rs_total = $conn->query($sql_total)) {
        if ($row = $rs_total->fetch_assoc()) {
            $total_assigned = (int)($row['cnt'] ?? 0);
        }
        $rs_total->free();
    }

    // --------- 2. 各狀態統計（定義：已報名=本階段已報名且未報到未放棄、已報到=已報名且已完成報到、放棄=已報名但放棄報到、追蹤=本階段還沒報名）---------
    $applied = 0;
    $checked_in = 0;
    $declined = 0;
    $tracking = 0;

    if ($stage_registered_col !== null && $has_check_in_status) {
        // 有本階段報名欄位：依 stage_*_registered 與 check_in_status 精確區分
        $reg_col = $stage_registered_col; // 已知安全欄位名：priority_exam_registered / joint_exam_registered / continued_recruitment_registered
        $base = " assigned_department IN ($in_clause) $year_cond_sql $grade_cond_sql ";
        $reg1 = " (IFNULL($reg_col, 0) = 1) ";
        $reg0 = " (IFNULL($reg_col, 0) = 0 OR $reg_col IS NULL) ";
        $applied = 0;
        $sql_applied = "SELECT COUNT(*) AS cnt FROM enrollment_intention WHERE $base AND $reg1 AND (check_in_status IS NULL OR check_in_status = '' OR check_in_status NOT IN ('completed', 'declined'))";
        if ($rs = $conn->query($sql_applied)) {
            if ($row = $rs->fetch_assoc()) $applied = (int)($row['cnt'] ?? 0);
            $rs->free();
        }
        $checked_in = 0;
        $sql_ci = "SELECT COUNT(*) AS cnt FROM enrollment_intention WHERE $base AND $reg1 AND check_in_status = 'completed'";
        if ($rs = $conn->query($sql_ci)) {
            if ($row = $rs->fetch_assoc()) $checked_in = (int)($row['cnt'] ?? 0);
            $rs->free();
        }
        $declined = 0;
        $sql_dec = "SELECT COUNT(*) AS cnt FROM enrollment_intention WHERE $base AND $reg1 AND check_in_status = 'declined'";
        if ($rs = $conn->query($sql_dec)) {
            if ($row = $rs->fetch_assoc()) $declined = (int)($row['cnt'] ?? 0);
            $rs->free();
        }
        $tracking = 0;
        $sql_track = "SELECT COUNT(*) AS cnt FROM enrollment_intention WHERE $base AND $reg0";
        if ($rs = $conn->query($sql_track)) {
            if ($row = $rs->fetch_assoc()) $tracking = (int)($row['cnt'] ?? 0);
            $rs->free();
        }
    } else {
        // 無本階段報名欄位：沿用 created_at 階段 + is_registered / follow_up_status
        if ($has_is_registered) {
            $sql_applied = "SELECT COUNT(*) AS cnt FROM enrollment_intention WHERE assigned_department IN ($in_clause) AND is_registered = 1 $year_cond_sql $stage_cond_sql $grade_cond_sql";
            if ($rs_applied = $conn->query($sql_applied)) {
                if ($row = $rs_applied->fetch_assoc()) $applied = (int)($row['cnt'] ?? 0);
                $rs_applied->free();
            }
        } else {
            $applied = $total_assigned;
        }
        if ($has_check_in_status) {
            $sql_checkin = "SELECT check_in_status, COUNT(*) AS cnt FROM enrollment_intention WHERE assigned_department IN ($in_clause) AND check_in_status IN ('completed', 'declined') $year_cond_sql $stage_cond_sql $grade_cond_sql GROUP BY check_in_status";
            if ($rs_checkin = $conn->query($sql_checkin)) {
                while ($row = $rs_checkin->fetch_assoc()) {
                    $st = $row['check_in_status'] ?? '';
                    $cnt = (int)($row['cnt'] ?? 0);
                    if ($st === 'completed') $checked_in = $cnt;
                    elseif ($st === 'declined') $declined = $cnt;
                }
                $rs_checkin->free();
            }
        }
        if ($has_follow_up_status) {
            $sql_track = "SELECT COUNT(*) AS cnt FROM enrollment_intention WHERE assigned_department IN ($in_clause) AND follow_up_status = 'tracking' $year_cond_sql $stage_cond_sql $grade_cond_sql";
            if ($rs_track = $conn->query($sql_track)) {
                if ($row = $rs_track->fetch_assoc()) $tracking = (int)($row['cnt'] ?? 0);
                $rs_track->free();
            }
        }
    }

    // --------- 3. 年級分布統計（該科分配到的學生按年級分布）---------
    // 年級代碼與中文名稱的對應
    $grade_mapping = [
        'J1' => '國一',
        'J2' => '國二',
        'J3' => '國三'
    ];
    $grade_order = ['J1', 'J2', 'J3'];

    $grades = [];
    if ($has_current_grade) {
        $sql_grades = "
            SELECT current_grade, COUNT(*) AS cnt
            FROM enrollment_intention
            WHERE assigned_department IN ($in_clause)
              AND current_grade IS NOT NULL
              AND current_grade <> ''
              $year_cond_sql
            GROUP BY current_grade
            ORDER BY FIELD(current_grade, 'J1', 'J2', 'J3')
        ";
        if ($rs_grades = $conn->query($sql_grades)) {
            while ($row = $rs_grades->fetch_assoc()) {
                $grade_code = $row['current_grade'] ?? '';
                $grade_name = isset($grade_mapping[$grade_code]) ? $grade_mapping[$grade_code] : $grade_code;
                $grades[] = [
                    'grade' => $grade_name,
                    'count' => (int)($row['cnt'] ?? 0),
                ];
            }
            $rs_grades->free();
        }
    }

    // --------- 4. 來源國中統計---------
    $schools = [];
    if ($has_junior_high) {
        $has_how_hear = in_array('how_hear', $columns, true);
        $check_school_tbl = $conn->query("SHOW TABLES LIKE 'school_data'");
        $has_school_data = $check_school_tbl && $check_school_tbl->num_rows > 0;

        // 定義欄位 (不含 AS)，用於 GROUP BY
        $col_school_expr = $has_school_data 
            ? "COALESCE(sd.name, ei.junior_high, '未填寫')" 
            : "ei.junior_high";
        $col_source_expr = $has_how_hear ? "COALESCE(ei.how_hear, '未填寫')" : "'未填寫'";

        // SQL 查詢
        $sql_schools = "
            SELECT
                $col_school_expr AS school_name,
                $col_source_expr AS how_hear,
                COUNT(*) AS cnt
            FROM enrollment_intention ei
            LEFT JOIN school_data sd ON ei.junior_high = sd.school_code
            WHERE ei.assigned_department IN ($in_clause)
              AND ei.junior_high IS NOT NULL
              AND ei.junior_high <> ''
              $year_cond_sql_ei
            GROUP BY $col_school_expr, $col_source_expr
            ORDER BY cnt DESC, school_name ASC
        ";

        if ($rs_sch = $conn->query($sql_schools)) {
            $temp_schools = [];
            while ($row = $rs_sch->fetch_assoc()) {
                $sName = $row['school_name'] ?? '未填寫';
                $sSource = trim($row['how_hear'] ?? '');
                $sCount = (int)($row['cnt'] ?? 0);
                if ($sSource === '') $sSource = '未填寫';

                if (!isset($temp_schools[$sName])) {
                    $temp_schools[$sName] = [ 'name' => $sName, 'count' => 0, 'sources' => [] ];
                }
                $temp_schools[$sName]['count'] += $sCount;
                if (!isset($temp_schools[$sName]['sources'][$sSource])) {
                    $temp_schools[$sName]['sources'][$sSource] = 0;
                }
                $temp_schools[$sName]['sources'][$sSource] += $sCount;
            }
            $rs_sch->free();

            // 各國中依年級的人數（用於堆疊長條圖）
            $temp_school_grades = [];
            // 各國中依年級的來源分布（點擊圖表時只顯示該年級的來源）
            $temp_school_sources_by_grade = [];
            if ($has_current_grade && $has_how_hear) {
                $grade_expr = "COALESCE(NULLIF(TRIM(ei.current_grade), ''), '未填寫')";
                $sql_school_grade = "
                    SELECT
                        $col_school_expr AS school_name,
                        $grade_expr AS grade_key,
                        COUNT(*) AS cnt
                    FROM enrollment_intention ei
                    LEFT JOIN school_data sd ON ei.junior_high = sd.school_code
                    WHERE ei.assigned_department IN ($in_clause)
                      AND ei.junior_high IS NOT NULL
                      AND ei.junior_high <> ''
                      $year_cond_sql_ei
                    GROUP BY $col_school_expr, $grade_expr
                ";
                if ($rs_sg = $conn->query($sql_school_grade)) {
                    while ($row = $rs_sg->fetch_assoc()) {
                        $sName = $row['school_name'] ?? '未填寫';
                        $gKey = trim($row['grade_key'] ?? '');
                        if ($gKey === '') $gKey = '未填寫';
                        $gCode = ($gKey === '國一' || $gKey === 'J1') ? 'J1' : (($gKey === '國二' || $gKey === 'J2') ? 'J2' : (($gKey === '國三' || $gKey === 'J3') ? 'J3' : null));
                        $gName = $gCode !== null ? $grade_mapping[$gCode] : $gKey;
                        if (!isset($temp_school_grades[$sName])) {
                            $temp_school_grades[$sName] = [];
                        }
                        $temp_school_grades[$sName][$gName] = (int)($row['cnt'] ?? 0);
                    }
                    $rs_sg->free();
                }
                // 國中 + 年級 + 得知管道 統計（用於點擊圖表顯示該年級來源）
                $sql_school_grade_source = "
                    SELECT
                        $col_school_expr AS school_name,
                        $grade_expr AS grade_key,
                        $col_source_expr AS how_hear,
                        COUNT(*) AS cnt
                    FROM enrollment_intention ei
                    LEFT JOIN school_data sd ON ei.junior_high = sd.school_code
                    WHERE ei.assigned_department IN ($in_clause)
                      AND ei.junior_high IS NOT NULL
                      AND ei.junior_high <> ''
                      $year_cond_sql_ei
                    GROUP BY $col_school_expr, $grade_expr, $col_source_expr
                ";
                if ($rs_sgs = $conn->query($sql_school_grade_source)) {
                    while ($row = $rs_sgs->fetch_assoc()) {
                        $sName = $row['school_name'] ?? '未填寫';
                        $gKey = trim($row['grade_key'] ?? '');
                        if ($gKey === '') $gKey = '未填寫';
                        $gCode = ($gKey === '國一' || $gKey === 'J1') ? 'J1' : (($gKey === '國二' || $gKey === 'J2') ? 'J2' : (($gKey === '國三' || $gKey === 'J3') ? 'J3' : null));
                        $gName = $gCode !== null ? $grade_mapping[$gCode] : $gKey;
                        $srcName = trim($row['how_hear'] ?? '');
                        if ($srcName === '') $srcName = '未填寫';
                        $cnt = (int)($row['cnt'] ?? 0);
                        if (!isset($temp_school_sources_by_grade[$sName])) {
                            $temp_school_sources_by_grade[$sName] = [];
                        }
                        if (!isset($temp_school_sources_by_grade[$sName][$gName])) {
                            $temp_school_sources_by_grade[$sName][$gName] = [];
                        }
                        if (!isset($temp_school_sources_by_grade[$sName][$gName][$srcName])) {
                            $temp_school_sources_by_grade[$sName][$gName][$srcName] = 0;
                        }
                        $temp_school_sources_by_grade[$sName][$gName][$srcName] += $cnt;
                    }
                    $rs_sgs->free();
                }
            } elseif ($has_current_grade) {
                $grade_expr = "COALESCE(NULLIF(TRIM(ei.current_grade), ''), '未填寫')";
                $sql_school_grade = "
                    SELECT
                        $col_school_expr AS school_name,
                        $grade_expr AS grade_key,
                        COUNT(*) AS cnt
                    FROM enrollment_intention ei
                    LEFT JOIN school_data sd ON ei.junior_high = sd.school_code
                    WHERE ei.assigned_department IN ($in_clause)
                      AND ei.junior_high IS NOT NULL
                      AND ei.junior_high <> ''
                      $year_cond_sql_ei
                    GROUP BY $col_school_expr, $grade_expr
                ";
                if ($rs_sg = $conn->query($sql_school_grade)) {
                    while ($row = $rs_sg->fetch_assoc()) {
                        $sName = $row['school_name'] ?? '未填寫';
                        $gKey = trim($row['grade_key'] ?? '');
                        if ($gKey === '') $gKey = '未填寫';
                        $gCode = ($gKey === '國一' || $gKey === 'J1') ? 'J1' : (($gKey === '國二' || $gKey === 'J2') ? 'J2' : (($gKey === '國三' || $gKey === 'J3') ? 'J3' : null));
                        $gName = $gCode !== null ? $grade_mapping[$gCode] : $gKey;
                        if (!isset($temp_school_grades[$sName])) {
                            $temp_school_grades[$sName] = [];
                        }
                        $temp_school_grades[$sName][$gName] = (int)($row['cnt'] ?? 0);
                    }
                    $rs_sg->free();
                }
            }
            $grade_order_display = ['國一', '國二', '國三'];

            foreach ($temp_schools as $sName => $data) {
                $source_list = [];
                foreach ($data['sources'] as $src => $cnt) {
                    $source_list[] = ['name' => $src, 'count' => $cnt];
                }
                usort($source_list, function($a, $b) { return $b['count'] <=> $a['count']; });
                $grades_list = [];
                if ($has_current_grade) {
                    foreach ($grade_order_display as $gLabel) {
                        $grades_list[] = [
                            'grade' => $gLabel,
                            'count' => (int)($temp_school_grades[$sName][$gLabel] ?? 0),
                        ];
                    }
                }
                // 各年級的來源列表（點擊圖表時只顯示該年級）
                $sources_by_grade = [];
                if (!empty($temp_school_sources_by_grade[$sName])) {
                    foreach ($grade_order_display as $gLabel) {
                        $by_src = $temp_school_sources_by_grade[$sName][$gLabel] ?? [];
                        $list = [];
                        foreach ($by_src as $src => $cnt) {
                            $list[] = ['name' => $src, 'count' => $cnt];
                        }
                        usort($list, function($a, $b) { return $b['count'] <=> $a['count']; });
                        $sources_by_grade[$gLabel] = $list;
                    }
                }
                $schools[] = [
                    'name' => $sName,
                    'count' => $data['count'],
                    'sources' => $source_list,
                    'grades' => $grades_list,
                    'sources_by_grade' => $sources_by_grade,
                ];
            }
            usort($schools, function($a, $b) { return $b['count'] <=> $a['count']; });
        }
    }

    $conn->close();

    echo json_encode([
        'success' => true,
        'department' => $department,
        'department_codes' => $dept_codes,
        'current_stage_display' => $current_stage_display,
        'status_summary' => [
            'total_assigned' => $total_assigned,
            'applied'        => $applied,
            'checked_in'     => $checked_in,
            'declined'       => $declined,
            'tracking'       => $tracking,
        ],
        'grades'  => $grades,
        'schools' => $schools,
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    // 捕捉所有錯誤，避免輸出非 JSON
    error_log('enrollment_department_detail_stats_api 錯誤: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => '伺服器內部錯誤'], JSON_UNESCAPED_UNICODE);
    exit;
}

