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

    // --------- 1. 計算總分配人數（以 assigned_department 為基準）---------
    $total_assigned = 0;
    $sql_total = "SELECT COUNT(*) AS cnt FROM enrollment_intention WHERE assigned_department IN ($in_clause) $year_cond_sql";
    if ($rs_total = $conn->query($sql_total)) {
        if ($row = $rs_total->fetch_assoc()) {
            $total_assigned = (int)($row['cnt'] ?? 0);
        }
        $rs_total->free();
    }

    // --------- 2. 各狀態統計 ---------
    // 已報名：若有 is_registered 欄位，使用 is_registered=1；否則以 total_assigned 近似
    $applied = 0;
    if ($has_is_registered) {
        $sql_applied = "SELECT COUNT(*) AS cnt FROM enrollment_intention WHERE assigned_department IN ($in_clause) AND is_registered = 1 $year_cond_sql";
        if ($rs_applied = $conn->query($sql_applied)) {
            if ($row = $rs_applied->fetch_assoc()) {
                $applied = (int)($row['cnt'] ?? 0);
            }
            $rs_applied->free();
        }
    } else {
        $applied = $total_assigned;
    }

    // 已報到 / 放棄（報到流程）
    $checked_in = 0;
    $declined   = 0;
    if ($has_check_in_status) {
        $sql_checkin = "
            SELECT check_in_status, COUNT(*) AS cnt
            FROM enrollment_intention
            WHERE assigned_department IN ($in_clause)
              AND check_in_status IN ('completed', 'declined')
              $year_cond_sql
            GROUP BY check_in_status
        ";
        if ($rs_checkin = $conn->query($sql_checkin)) {
            while ($row = $rs_checkin->fetch_assoc()) {
                $status = $row['check_in_status'] ?? '';
                $cnt    = (int)($row['cnt'] ?? 0);
                if ($status === 'completed') {
                    $checked_in = $cnt;
                } elseif ($status === 'declined') {
                    $declined = $cnt;
                }
            }
            $rs_checkin->free();
        }
    }

    // 尚在追蹤：follow_up_status = 'tracking'
    $tracking = 0;
    if ($has_follow_up_status) {
        $sql_track = "
            SELECT COUNT(*) AS cnt
            FROM enrollment_intention
            WHERE assigned_department IN ($in_clause)
              AND follow_up_status = 'tracking'
              $year_cond_sql
        ";
        if ($rs_track = $conn->query($sql_track)) {
            if ($row = $rs_track->fetch_assoc()) {
                $tracking = (int)($row['cnt'] ?? 0);
            }
            $rs_track->free();
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

            foreach ($temp_schools as $sName => $data) {
                $source_list = [];
                foreach ($data['sources'] as $src => $cnt) {
                    $source_list[] = ['name' => $src, 'count' => $cnt];
                }
                usort($source_list, function($a, $b) { return $b['count'] <=> $a['count']; });
                $schools[] = ['name' => $sName, 'count' => $data['count'], 'sources' => $source_list];
            }
            usort($schools, function($a, $b) { return $b['count'] <=> $a['count']; });
        }
    }

    $conn->close();

    echo json_encode([
        'success' => true,
        'department' => $department,
        'department_codes' => $dept_codes,
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

