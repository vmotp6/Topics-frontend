<?php
require_once '../session_config.php';
// 載入資料庫配置
require_once '../config.php';

// 移除權限檢查，允許直接查看統計分析
// 檢查權限 - 支援多種登入方式
// $is_admin = false;

// // 檢查後台管理系統登入狀態
// if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
//     $is_admin = true;
// }

// // 檢查前台系統的行政人員權限
// if (!$is_admin) {
//     $role = $_SESSION['role'] ?? '訪客';
//     $is_admin = ($role === '管理員' || $role === 'admin' || $role === '行政人員' || $role === '學校行政人員');
// }

// if (!$is_admin) {
//     http_response_code(403);
//     echo json_encode(['error' => '權限不足', 'debug' => [
//         'admin_logged_in' => $_SESSION['admin_logged_in'] ?? 'not_set',
//         'role' => $_SESSION['role'] ?? 'not_set',
//         'session_data' => $_SESSION
//     ]]);
//     exit;
// }

header('Content-Type: application/json; charset=utf-8');

try {
    // 使用統一的資料庫配置
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $action = $_GET['action'] ?? 'overview';
    $department_filter = $_GET['department'] ?? '';

    switch ($action) {
        case 'overview':
            $stats = getOverviewStats($pdo, $department_filter);
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
        case 'gender':
            $stats = getGenderStats($pdo, $department_filter);
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
        case 'school_city':
            $stats = getSchoolCityStats($pdo, $department_filter);
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
        case 'choices':
            $stats = getChoicesStats($pdo, $department_filter);
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
        case 'monthly':
            $stats = getMonthlyStats($pdo, $department_filter);
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
        case 'status':
            $stats = getStatusStats($pdo, $department_filter);
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
        case 'department_quota_status':
            $stats = getDepartmentQuotaStatusStats($pdo, $department_filter);
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => '無效的操作'], JSON_UNESCAPED_UNICODE);
    }

} catch (PDOException $e) {
    error_log("資料庫錯誤: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => '資料庫連接失敗']);
}

// 輔助函數：建立科系篩選的 WHERE 條件
function buildDepartmentFilter($pdo, $department) {
    if (empty($department)) {
        return '1=1'; // 不篩選
    }
    
    // 嘗試將科系名稱轉換為科系代碼
    // 如果 department 是「資訊管理科」或包含「資管」，則查找對應的代碼
    if (stripos($department, '資訊管理') !== false || stripos($department, '資管') !== false) {
        // 查詢 departments 表，找到資訊管理科的代碼
        try {
            $dept_check = $pdo->query("SELECT code FROM departments WHERE code = 'IM' OR name LIKE '%資訊管理%' OR name LIKE '%資管%' LIMIT 1");
            $dept_row = $dept_check->fetch(PDO::FETCH_ASSOC);
            if ($dept_row) {
                $dept_code = $dept_row['code'];
                return "assigned_department = " . $pdo->quote($dept_code);
            }
        } catch (PDOException $e) {
            error_log("查詢科系代碼失敗: " . $e->getMessage());
        }
    }
    
    // 嘗試直接查詢 departments 表，看是否為科系名稱
    try {
        $dept_check = $pdo->query("SELECT code FROM departments WHERE name = " . $pdo->quote($department) . " OR code = " . $pdo->quote($department) . " LIMIT 1");
        $dept_row = $dept_check->fetch(PDO::FETCH_ASSOC);
        if ($dept_row) {
            $dept_code = $dept_row['code'];
            return "assigned_department = " . $pdo->quote($dept_code);
        }
    } catch (PDOException $e) {
        error_log("查詢科系代碼失敗: " . $e->getMessage());
    }
    
    // 如果找不到對應的代碼，直接使用原值（可能是代碼）
    return "assigned_department = " . $pdo->quote($department);
}

// 嘗試將科系名稱轉換為科系代碼（供科系名額統計使用）
function resolveDepartmentCode($pdo, $department) {
    if (empty($department)) {
        return '';
    }

    try {
        if (stripos($department, '資訊管理') !== false || stripos($department, '資管') !== false) {
            $dept_check = $pdo->query("SELECT code FROM departments WHERE code = 'IM' OR name LIKE '%資訊管理%' OR name LIKE '%資管%' LIMIT 1");
            $dept_row = $dept_check->fetch(PDO::FETCH_ASSOC);
            if ($dept_row) {
                return $dept_row['code'];
            }
        }

        $dept_check = $pdo->query("SELECT code FROM departments WHERE name = " . $pdo->quote($department) . " OR code = " . $pdo->quote($department) . " LIMIT 1");
        $dept_row = $dept_check->fetch(PDO::FETCH_ASSOC);
        if ($dept_row) {
            return $dept_row['code'];
        }
    } catch (PDOException $e) {
        error_log("resolveDepartmentCode failed: " . $e->getMessage());
    }

    return '';
}

// 總覽統計
function getOverviewStats($pdo, $department_filter = '') {
    try {
        // 檢查 continued_admission_choices 表是否存在
        $check_table = $pdo->query("SHOW TABLES LIKE 'continued_admission_choices'");
        $has_choices_table = $check_table->rowCount() > 0;
        
        if (empty($department_filter)) {
            // 沒有部門篩選時，直接統計所有記錄
            $sql = "SELECT 
                        COUNT(DISTINCT id) as total_applications
                    FROM continued_admission";
        } else {
            // 有部門篩選時，需要統計所有選擇了該科系的學生
            // 首先嘗試將科系名稱轉換為科系代碼
            $dept_code = null;
            $dept_name = $department_filter;
            
            // 如果 department_filter 是「資訊管理科」或包含「資管」，則查找對應的代碼
            if (stripos($department_filter, '資訊管理') !== false || stripos($department_filter, '資管') !== false) {
                $dept_check = $pdo->query("SELECT code, name FROM departments WHERE code = 'IM' OR name LIKE '%資訊管理%' OR name LIKE '%資管%' LIMIT 1");
                $dept_row = $dept_check->fetch(PDO::FETCH_ASSOC);
                if ($dept_row) {
                    $dept_code = $dept_row['code'];
                    $dept_name = $dept_row['name'];
                }
            } else {
                // 嘗試直接查詢 departments 表
                $dept_check = $pdo->query("SELECT code, name FROM departments WHERE name = " . $pdo->quote($department_filter) . " OR code = " . $pdo->quote($department_filter) . " LIMIT 1");
                $dept_row = $dept_check->fetch(PDO::FETCH_ASSOC);
                if ($dept_row) {
                    $dept_code = $dept_row['code'];
                    $dept_name = $dept_row['name'];
                }
            }
            
            if ($has_choices_table && $dept_code) {
                // 使用 continued_admission_choices 表，統計所有選擇了該科系的學生
                $sql = "SELECT 
                            COUNT(DISTINCT ca.id) as total_applications
                        FROM continued_admission ca
                        INNER JOIN continued_admission_choices cac ON ca.id = cac.application_id
                        WHERE cac.department_code = " . $pdo->quote($dept_code);
            } elseif ($has_choices_table && $dept_name) {
                // 如果找不到代碼，使用科系名稱
                $sql = "SELECT 
                            COUNT(DISTINCT ca.id) as total_applications
                        FROM continued_admission ca
                        INNER JOIN continued_admission_choices cac ON ca.id = cac.application_id
                        LEFT JOIN departments d ON cac.department_code = d.code
                        WHERE d.name = " . $pdo->quote($dept_name);
        } else {
            // 如果沒有 continued_admission_choices 表，使用 assigned_department 欄位
            $filter = buildDepartmentFilter($pdo, $department_filter);
            $sql = "SELECT 
                        COUNT(DISTINCT id) as total_applications
                    FROM continued_admission 
                    WHERE $filter";
        }
        }
        
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_applications' => (int)($result['total_applications'] ?? 0),
            'total_cities' => 0,
            'total_schools' => 0
        ];
    } catch (PDOException $e) {
        error_log("總覽統計錯誤: " . $e->getMessage());
        error_log("SQL 錯誤詳情: " . print_r($e->errorInfo, true));
        return ['error' => '無法獲取總覽統計', 'total_applications' => 0];
    }
}

// 性別分布統計
function getGenderStats($pdo, $department_filter = '') {
    try {
        $filter = buildDepartmentFilter($pdo, $department_filter);
        $sql = "SELECT 
                    CASE 
                        WHEN gender = 1 OR gender = '1' THEN '男'
                        WHEN gender = 2 OR gender = '2' THEN '女'
                        WHEN gender = 'male' THEN '男'
                        WHEN gender = 'female' THEN '女'
                        ELSE COALESCE(gender, '未填寫')
                    END as gender_name,
                    COUNT(*) as count
                FROM continued_admission 
                WHERE $filter
                GROUP BY gender_name
                ORDER BY count DESC";
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($row) {
            return [
                'name' => $row['gender_name'],
                'value' => (int)$row['count']
            ];
        }, $results);
    } catch (PDOException $e) {
        error_log("性別統計錯誤: " . $e->getMessage());
        return ['error' => '無法獲取性別統計'];
    }
}

// 就讀縣市分布統計
function getSchoolCityStats($pdo, $department_filter = '') {
    try {
        $filter = buildDepartmentFilter($pdo, $department_filter);
        
        // 檢查 school_data 表是否存在
        $check_school_table = $pdo->query("SHOW TABLES LIKE 'school_data'");
        $has_school_table = $check_school_table->rowCount() > 0;
        
        // 檢查 continued_admission 表是否有 school_city 欄位
        $columns_check = $pdo->query("SHOW COLUMNS FROM continued_admission LIKE 'school_city'");
        $has_school_city_column = $columns_check->rowCount() > 0;
        
        if ($has_school_city_column) {
            // 如果表中有 school_city 欄位，直接使用
            $sql = "SELECT 
                        COALESCE(school_city, '未填寫') as city_name,
                        COUNT(*) as count
                    FROM continued_admission 
                    WHERE $filter
                    GROUP BY school_city
                    ORDER BY count DESC
                    LIMIT 10";
        } elseif ($has_school_table) {
            // 如果沒有 school_city 欄位，從 school_data 表 JOIN 獲取縣市
            $sql = "SELECT 
                        COALESCE(sd.city, '未填寫') as city_name,
                        COUNT(*) as count
                    FROM continued_admission ca
                    LEFT JOIN school_data sd ON ca.school = sd.school_code
                    WHERE $filter
                    GROUP BY sd.city
                    ORDER BY count DESC
                    LIMIT 10";
        } else {
            // 如果都沒有，返回空結果
            $sql = "SELECT '未填寫' as city_name, 0 as count WHERE 1=0";
        }
        
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($row) {
            return [
                'name' => $row['city_name'] ?: '未填寫',
                'value' => (int)$row['count']
            ];
        }, $results);
    } catch (PDOException $e) {
        error_log("縣市統計錯誤: " . $e->getMessage());
        error_log("SQL 錯誤詳情: " . print_r($e->errorInfo, true));
        return ['error' => '無法獲取縣市統計: ' . $e->getMessage()];
    }
}

// 志願選擇統計
function getChoicesStats($pdo, $department_filter = '') {
    try {
        // 檢查 continued_admission_choices 表是否存在
        $check_table = $pdo->query("SHOW TABLES LIKE 'continued_admission_choices'");
        $has_choices_table = $check_table->rowCount() > 0;
        
        // 檢查 continued_admission 表是否有 choices 欄位
        $check_column = $pdo->query("SHOW COLUMNS FROM continued_admission LIKE 'choices'");
        $has_choices_column = $check_column->rowCount() > 0;
        
        if ($has_choices_table) {
            // 使用 continued_admission_choices 表（新結構）
            $where_clause = '';
            if (!empty($department_filter)) {
                // 如果指定了科系篩選，只統計該科系的志願
                // 首先嘗試將科系名稱轉換為科系代碼
                $dept_code = null;
                $dept_name = $department_filter;
                
                // 如果 department_filter 是「資訊管理科」或包含「資管」，則查找對應的代碼
                if (stripos($department_filter, '資訊管理') !== false || stripos($department_filter, '資管') !== false) {
                    $dept_check = $pdo->query("SELECT code, name FROM departments WHERE code = 'IM' OR name LIKE '%資訊管理%' OR name LIKE '%資管%' LIMIT 1");
                    $dept_row = $dept_check->fetch(PDO::FETCH_ASSOC);
                    if ($dept_row) {
                        $dept_code = $dept_row['code'];
                        $dept_name = $dept_row['name'];
                    }
                } else {
                    // 嘗試直接查詢 departments 表
                    $dept_check = $pdo->query("SELECT code, name FROM departments WHERE name = " . $pdo->quote($department_filter) . " OR code = " . $pdo->quote($department_filter) . " LIMIT 1");
                    $dept_row = $dept_check->fetch(PDO::FETCH_ASSOC);
                    if ($dept_row) {
                        $dept_code = $dept_row['code'];
                        $dept_name = $dept_row['name'];
                    }
                }
                
                if ($dept_code) {
                    // 使用科系代碼過濾
                    $where_clause = "WHERE cac.department_code = " . $pdo->quote($dept_code);
                } else {
                    // 使用科系名稱過濾
                    $where_clause = "WHERE d.name = " . $pdo->quote($dept_name);
                }
            }
            
            $sql = "SELECT 
                        COALESCE(d.name, cac.department_code, '未知科系') as department_name,
                        COUNT(*) as count
                    FROM continued_admission_choices cac
                    INNER JOIN continued_admission ca ON cac.application_id = ca.id
                    LEFT JOIN departments d ON cac.department_code = d.code
                    $where_clause
                    GROUP BY d.name, cac.department_code
                    ORDER BY count DESC
                    LIMIT 20";
        } elseif ($has_choices_column) {
            // 使用 continued_admission 表的 choices JSON 欄位（舊結構）
            $filter = buildDepartmentFilter($pdo, $department_filter);
            $sql = "SELECT choices FROM continued_admission WHERE $filter AND choices IS NOT NULL AND choices != ''";
            $stmt = $pdo->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $choiceStats = [];
            foreach ($results as $choicesJson) {
                $choices = json_decode($choicesJson, true);
                if (is_array($choices)) {
                    foreach ($choices as $choice) {
                        if (!empty($choice)) {
                            $choiceStats[$choice] = ($choiceStats[$choice] ?? 0) + 1;
                        }
                    }
                }
            }
            
            // 轉換為API格式
            $formattedStats = [];
            foreach ($choiceStats as $choice => $count) {
                $formattedStats[] = [
                    'name' => $choice,
                    'value' => $count
                ];
            }
            
            // 按數量排序
            usort($formattedStats, function($a, $b) {
                return $b['value'] - $a['value'];
            });
            
            return $formattedStats;
        } else {
            // 如果都沒有，返回空結果
            return [];
        }
        
        // 執行查詢（僅當使用 continued_admission_choices 表時）
        if ($has_choices_table) {
            $stmt = $pdo->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 轉換為API格式
            $formattedStats = [];
            foreach ($results as $row) {
                $formattedStats[] = [
                    'name' => $row['department_name'] ?: '未知科系',
                    'value' => (int)$row['count']
                ];
            }
            
            return $formattedStats;
        }
        
        return [];
    } catch (PDOException $e) {
        error_log("志願統計錯誤: " . $e->getMessage());
        error_log("SQL 錯誤詳情: " . print_r($e->errorInfo, true));
        return ['error' => '無法獲取志願統計: ' . $e->getMessage()];
    }
}

// 月度趨勢統計
function getMonthlyStats($pdo, $department_filter = '') {
    try {
        $filter = buildDepartmentFilter($pdo, $department_filter);
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as count
                FROM continued_admission 
                WHERE $filter AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month ASC";
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($row) {
            return [
                'name' => $row['month'],
                'value' => (int)$row['count']
            ];
        }, $results);
    } catch (PDOException $e) {
        error_log("月度統計錯誤: " . $e->getMessage());
        return ['error' => '無法獲取月度統計'];
    }
}

// 審核狀態統計
function getStatusStats($pdo, $department_filter = '') {
    try {
        $filter = buildDepartmentFilter($pdo, $department_filter);
        $sql = "SELECT 
                    ca.status as status_code,
                    COALESCE(ast.name, 
                        CASE 
                            WHEN ca.status = 'pending' THEN '待審核'
                            WHEN ca.status = 'approved' THEN '已錄取'
                            WHEN ca.status = 'rejected' THEN '未錄取'
                            ELSE COALESCE(ca.status, '未知狀態')
                        END
                    ) as status_name,
                    COUNT(*) as count
                FROM continued_admission ca
                LEFT JOIN application_statuses ast ON ca.status = ast.code
                WHERE $filter
                GROUP BY ca.status
                ORDER BY count DESC";
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($row) {
            return [
                'name' => $row['status_name'],
                'value' => (int)$row['count']
            ];
        }, $results);
    } catch (PDOException $e) {
        error_log("狀態統計錯誤: " . $e->getMessage());
        return ['error' => '無法獲取狀態統計'];
    }
}

// 續招報名統計 - 科系名額與錄取狀態統計
function getDepartmentQuotaStatusStats($pdo, $department_filter = '') {
    try {
        $quota_check = $pdo->query("SHOW TABLES LIKE 'department_quotas'");
        if ($quota_check->rowCount() === 0) {
            return ['error' => '找不到 department_quotas 表'];
        }
        $dept_check = $pdo->query("SHOW TABLES LIKE 'departments'");
        if ($dept_check->rowCount() === 0) {
            return ['error' => '找不到 departments 表'];
        }
        $admission_check = $pdo->query("SHOW TABLES LIKE 'continued_admission'");
        if ($admission_check->rowCount() === 0) {
            return ['error' => '找不到 continued_admission 表'];
        }

        $where = 'WHERE 1=1';
        if (!empty($department_filter)) {
            $dept_code = resolveDepartmentCode($pdo, $department_filter);
            if (!empty($dept_code)) {
                $where .= ' AND d.code = ' . $pdo->quote($dept_code);
            } else {
                $where .= ' AND d.name LIKE ' . $pdo->quote('%' . $department_filter . '%');
            }
        }

        $sql = "
            SELECT
                d.code AS department_code,
                d.name AS department_name,
                COALESCE(dq.total_quota, 0) AS total_quota,
                COALESCE(SUM(CASE WHEN ca.status IN ('approved', 'AP') THEN 1 ELSE 0 END), 0) AS approved_count,
                COALESCE(SUM(CASE WHEN ca.status IN ('waitlist', 'AD') THEN 1 ELSE 0 END), 0) AS waitlist_count,
                COALESCE(SUM(CASE WHEN ca.status IN ('rejected', 'RE') THEN 1 ELSE 0 END), 0) AS rejected_count
            FROM departments d
            INNER JOIN department_quotas dq ON d.code = dq.department_code AND dq.is_active = 1
            LEFT JOIN continued_admission ca ON ca.assigned_department = d.code
            $where
            GROUP BY d.code, d.name, dq.total_quota
            ORDER BY d.code
        ";

        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function($row) {
            return [
                'department_code' => $row['department_code'],
                'department_name' => $row['department_name'] ?: $row['department_code'],
                'total_quota' => (int)($row['total_quota'] ?? 0),
                'approved_count' => (int)($row['approved_count'] ?? 0),
                'waitlist_count' => (int)($row['waitlist_count'] ?? 0),
                'rejected_count' => (int)($row['rejected_count'] ?? 0)
            ];
        }, $rows);
    } catch (PDOException $e) {
        error_log("科系名額統計錯誤: " . $e->getMessage());
        return ['error' => '無法獲取科系名額統計'];
    }
}
