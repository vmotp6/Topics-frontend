<?php
// 載入 session 配置
require_once '../session_config.php';

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

// 資料庫連接
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $action = $_GET['action'] ?? 'overview';
    $department_filter = $_GET['department'] ?? '';

    switch ($action) {
        case 'overview':
            // 總體統計
            $stats = getOverviewStats($pdo, $department_filter);
            echo json_encode($stats);
            break;
            
        case 'department':
            // 科系統計
            $stats = getDepartmentStats($pdo, $department_filter);
            echo json_encode($stats);
            break;
            
        case 'system':
            // 學制統計
            $stats = getSystemStats($pdo, $department_filter);
            echo json_encode($stats);
            break;
            
        case 'grade':
            // 年級統計
            $stats = getGradeStats($pdo, $department_filter);
            echo json_encode($stats);
            break;
            
        case 'gender':
            // 性別統計
            $stats = getGenderStats($pdo, $department_filter);
            echo json_encode($stats);
            break;
            
        case 'identity':
            // 身分別統計
            $stats = getIdentityStats($pdo, $department_filter);
            echo json_encode($stats);
            break;
            
        case 'monthly':
            // 月度統計
            $stats = getMonthlyStats($pdo, $department_filter);
            echo json_encode($stats);
            break;
            
        case 'school_department':
            // 國中選擇科系統計（僅限admin1）
            $stats = getSchoolDepartmentStats($pdo, $department_filter);
            echo json_encode($stats);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => '無效的操作']);
    }

} catch (PDOException $e) {
    error_log("資料庫錯誤: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => '資料庫連接失敗']);
}

// 輔助函數：建立科系篩選的 WHERE 條件
function buildDepartmentFilter($department) {
    if (empty($department)) {
        return '1=1'; // 不篩選
    }
    // 篩選三個志願中任一個包含指定科系的記錄
    return "(intention1 LIKE '%{$department}%' OR intention2 LIKE '%{$department}%' OR intention3 LIKE '%{$department}%')";
}

function getOverviewStats($pdo, $department_filter = '') {
    $stats = [];
    $filter = buildDepartmentFilter($department_filter);
    
    // 總報名人數
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM enrollment_intention WHERE $filter");
    $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 本月報名人數
    $stmt = $pdo->query("SELECT COUNT(*) as monthly FROM enrollment_intention WHERE $filter AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')");
    $stats['monthly'] = $stmt->fetch(PDO::FETCH_ASSOC)['monthly'];
    
    // 本週報名人數
    $stmt = $pdo->query("SELECT COUNT(*) as weekly FROM enrollment_intention WHERE $filter AND YEARWEEK(created_at) = YEARWEEK(NOW())");
    $stats['weekly'] = $stmt->fetch(PDO::FETCH_ASSOC)['weekly'];
    
    // 今日報名人數
    $stmt = $pdo->query("SELECT COUNT(*) as daily FROM enrollment_intention WHERE $filter AND DATE(created_at) = CURDATE()");
    $stats['daily'] = $stmt->fetch(PDO::FETCH_ASSOC)['daily'];
    
    return $stats;
}

function getDepartmentStats($pdo, $department_filter = '') {
    $stats = [];
    $filter = buildDepartmentFilter($department_filter);
    
    // 科系統計（三個意願合併，但保留志願順序資訊）
    // 如果有科系篩選，只統計包含該科系的學生的所有選擇
    $stmt = $pdo->query("
        SELECT 
            '第一志願' as priority,
            COALESCE(intention1, '無特定') as department,
            COUNT(*) as count
        FROM enrollment_intention 
        WHERE intention1 IS NOT NULL AND intention1 != '' AND $filter
        GROUP BY intention1
        
        UNION ALL
        
        SELECT 
            '第二志願' as priority,
            COALESCE(intention2, '無特定') as department,
            COUNT(*) as count
        FROM enrollment_intention 
        WHERE intention2 IS NOT NULL AND intention2 != '' AND intention2 != '無特定' AND $filter
        GROUP BY intention2
        
        UNION ALL
        
        SELECT 
            '第三志願' as priority,
            COALESCE(intention3, '無特定') as department,
            COUNT(*) as count
        FROM enrollment_intention 
        WHERE intention3 IS NOT NULL AND intention3 != '' AND intention3 != '無特定' AND $filter
        GROUP BY intention3
    ");
    
    $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 合併相同科系的數據，但保留志願分布資訊
    $merged = [];
    foreach ($raw_data as $row) {
        $dept = $row['department'];
        $priority = $row['priority'];
        $count = $row['count'];
        
        if (!isset($merged[$dept])) {
            $merged[$dept] = [
                'total' => 0,
                'priorities' => []
            ];
        }
        
        $merged[$dept]['total'] += $count;
        $merged[$dept]['priorities'][$priority] = $count;
    }
    
    // 轉換為前端需要的格式
    foreach ($merged as $department => $data) {
        // 創建詳細的志願分布描述
        $priority_details = [];
        foreach (['第一志願', '第二志願', '第三志願'] as $priority) {
            if (isset($data['priorities'][$priority])) {
                $priority_details[] = $priority . ': ' . $data['priorities'][$priority] . '人';
            }
        }
        
        $stats[] = [
            'name' => $department,
            'value' => $data['total'],
            'priority_details' => implode(', ', $priority_details),
            'first_choice' => $data['priorities']['第一志願'] ?? 0,
            'second_choice' => $data['priorities']['第二志願'] ?? 0,
            'third_choice' => $data['priorities']['第三志願'] ?? 0
        ];
    }
    
    // 按總數量排序
    usort($stats, function($a, $b) {
        return $b['value'] - $a['value'];
    });
    
    return $stats;
}

function getSystemStats($pdo, $department_filter = '') {
    $stats = [];
    $filter = buildDepartmentFilter($department_filter);
    
    // 學制統計
    $stmt = $pdo->query("
        SELECT 
            COALESCE(system1, '未選擇') as system_type,
            COUNT(*) as count
        FROM enrollment_intention 
        WHERE system1 IS NOT NULL AND system1 != '' AND $filter
        GROUP BY system1
        
        UNION ALL
        
        SELECT 
            COALESCE(system2, '未選擇') as system_type,
            COUNT(*) as count
        FROM enrollment_intention 
        WHERE system2 IS NOT NULL AND system2 != '' AND system2 != '未選擇' AND $filter
        GROUP BY system2
        
        UNION ALL
        
        SELECT 
            COALESCE(system3, '未選擇') as system_type,
            COUNT(*) as count
        FROM enrollment_intention 
        WHERE system3 IS NOT NULL AND system3 != '' AND system3 != '未選擇' AND $filter
        GROUP BY system3
    ");
    
    $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 合併相同學制的數據
    $merged = [];
    foreach ($raw_data as $row) {
        $system = $row['system_type'];
        if (isset($merged[$system])) {
            $merged[$system] += $row['count'];
        } else {
            $merged[$system] = $row['count'];
        }
    }
    
    // 轉換為圖表格式
    foreach ($merged as $system => $count) {
        $stats[] = [
            'name' => $system,
            'value' => $count
        ];
    }
    
    return $stats;
}

function getGradeStats($pdo, $department_filter = '') {
    $filter = buildDepartmentFilter($department_filter);
    
    $stmt = $pdo->query("
        SELECT 
            COALESCE(current_grade, '未填寫') as grade,
            COUNT(*) as count
        FROM enrollment_intention 
        WHERE $filter
        GROUP BY current_grade
        ORDER BY 
            CASE current_grade
                WHEN '國一' THEN 1
                WHEN '國二' THEN 2
                WHEN '國三' THEN 3
                WHEN '已畢業' THEN 4
                ELSE 5
            END
    ");
    
    $stats = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats[] = [
            'name' => $row['grade'],
            'value' => (int)$row['count']
        ];
    }
    
    return $stats;
}

function getGenderStats($pdo, $department_filter = '') {
    $filter = buildDepartmentFilter($department_filter);
    
    $stmt = $pdo->query("
        SELECT 
            COALESCE(gender, '未填寫') as gender,
            COUNT(*) as count
        FROM enrollment_intention 
        WHERE $filter
        GROUP BY gender
        ORDER BY 
            CASE gender
                WHEN '男' THEN 1
                WHEN '女' THEN 2
                ELSE 3
            END
    ");
    
    $stats = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats[] = [
            'name' => $row['gender'],
            'value' => (int)$row['count']
        ];
    }
    
    return $stats;
}

function getIdentityStats($pdo, $department_filter = '') {
    $filter = buildDepartmentFilter($department_filter);
    
    $stmt = $pdo->query("
        SELECT 
            COALESCE(identity, '未填寫') as identity,
            COUNT(*) as count
        FROM enrollment_intention 
        WHERE $filter
        GROUP BY identity
        ORDER BY 
            CASE identity
                WHEN '學生' THEN 1
                WHEN '家長' THEN 2
                ELSE 3
            END
    ");
    
    $stats = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats[] = [
            'name' => $row['identity'],
            'value' => (int)$row['count']
        ];
    }
    
    return $stats;
}

function getMonthlyStats($pdo, $department_filter = '') {
    $filter = buildDepartmentFilter($department_filter);
    
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM enrollment_intention 
        WHERE $filter AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
    ");
    
    $stats = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats[] = [
            'name' => $row['month'],
            'value' => (int)$row['count']
        ];
    }
    
    return $stats;
}

function getSchoolDepartmentStats($pdo, $department_filter = '') {
    $filter = buildDepartmentFilter($department_filter);
    
    // 統計每個國中選擇的科系（三個志願都計算）
    $stmt = $pdo->query("
        SELECT 
            COALESCE(junior_high, '未填寫') as school_name,
            COALESCE(intention1, '無特定') as department,
            COUNT(*) as count,
            '第一志願' as priority
        FROM enrollment_intention 
        WHERE junior_high IS NOT NULL AND junior_high != '' 
            AND intention1 IS NOT NULL AND intention1 != '' AND intention1 != '無特定'
            AND $filter
        GROUP BY junior_high, intention1
        
        UNION ALL
        
        SELECT 
            COALESCE(junior_high, '未填寫') as school_name,
            COALESCE(intention2, '無特定') as department,
            COUNT(*) as count,
            '第二志願' as priority
        FROM enrollment_intention 
        WHERE junior_high IS NOT NULL AND junior_high != '' 
            AND intention2 IS NOT NULL AND intention2 != '' AND intention2 != '無特定'
            AND $filter
        GROUP BY junior_high, intention2
        
        UNION ALL
        
        SELECT 
            COALESCE(junior_high, '未填寫') as school_name,
            COALESCE(intention3, '無特定') as department,
            COUNT(*) as count,
            '第三志願' as priority
        FROM enrollment_intention 
        WHERE junior_high IS NOT NULL AND junior_high != '' 
            AND intention3 IS NOT NULL AND intention3 != '' AND intention3 != '無特定'
            AND $filter
        GROUP BY junior_high, intention3
        
        ORDER BY school_name, department
    ");
    
    $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 按國中分組，統計每個國中選擇的科系
    $school_stats = [];
    foreach ($raw_data as $row) {
        $school = $row['school_name'];
        $department = $row['department'];
        $count = (int)$row['count'];
        $priority = $row['priority'];
        
        if (!isset($school_stats[$school])) {
            $school_stats[$school] = [];
        }
        
        if (!isset($school_stats[$school][$department])) {
            $school_stats[$school][$department] = [
                'total' => 0,
                'priorities' => [
                    '第一志願' => 0,
                    '第二志願' => 0,
                    '第三志願' => 0
                ]
            ];
        }
        
        $school_stats[$school][$department]['total'] += $count;
        $school_stats[$school][$department]['priorities'][$priority] += $count;
    }
    
    // 轉換為前端需要的格式
    $stats = [];
    foreach ($school_stats as $school => $departments) {
        $department_list = [];
        foreach ($departments as $dept => $data) {
            $department_list[] = [
                'name' => $dept,
                'total' => $data['total'],
                'first_choice' => $data['priorities']['第一志願'],
                'second_choice' => $data['priorities']['第二志願'],
                'third_choice' => $data['priorities']['第三志願']
            ];
        }
        
        // 按總數排序
        usort($department_list, function($a, $b) {
            return $b['total'] - $a['total'];
        });
        
        $stats[] = [
            'school' => $school,
            'departments' => $department_list,
            'total_students' => array_sum(array_column($department_list, 'total'))
        ];
    }
    
    // 按總學生數排序
    usort($stats, function($a, $b) {
        return $b['total_students'] - $a['total_students'];
    });
    
    return $stats;
}
?>