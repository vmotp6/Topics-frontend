<?php
// 載入 session 配置
require_once '../session_config.php';

// 檢查權限 - 支援多種登入方式
$is_admin = false;

// 檢查後台管理系統登入狀態
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    $is_admin = true;
}

// 檢查前台系統的行政人員權限
if (!$is_admin) {
    $role = $_SESSION['role'] ?? '訪客';
    $is_admin = ($role === '管理員' || $role === 'admin' || $role === '行政人員' || $role === '學校行政人員');
}

if (!$is_admin) {
    http_response_code(403);
    echo json_encode(['error' => '權限不足', 'debug' => [
        'admin_logged_in' => $_SESSION['admin_logged_in'] ?? 'not_set',
        'role' => $_SESSION['role'] ?? 'not_set',
        'session_data' => $_SESSION
    ]]);
    exit;
}

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $action = $_GET['action'] ?? 'overview';

    switch ($action) {
        case 'overview':
            // 總體統計
            $stats = getOverviewStats($pdo);
            echo json_encode($stats);
            break;
            
        case 'department':
            // 科系統計
            $stats = getDepartmentStats($pdo);
            echo json_encode($stats);
            break;
            
        case 'system':
            // 學制統計
            $stats = getSystemStats($pdo);
            echo json_encode($stats);
            break;
            
        case 'grade':
            // 年級統計
            $stats = getGradeStats($pdo);
            echo json_encode($stats);
            break;
            
        case 'gender':
            // 性別統計
            $stats = getGenderStats($pdo);
            echo json_encode($stats);
            break;
            
        case 'identity':
            // 身分別統計
            $stats = getIdentityStats($pdo);
            echo json_encode($stats);
            break;
            
        case 'monthly':
            // 月度統計
            $stats = getMonthlyStats($pdo);
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

function getOverviewStats($pdo) {
    $stats = [];
    
    // 總報名人數
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM enrollment_intention");
    $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 本月報名人數
    $stmt = $pdo->query("SELECT COUNT(*) as monthly FROM enrollment_intention WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')");
    $stats['monthly'] = $stmt->fetch(PDO::FETCH_ASSOC)['monthly'];
    
    // 本週報名人數
    $stmt = $pdo->query("SELECT COUNT(*) as weekly FROM enrollment_intention WHERE YEARWEEK(created_at) = YEARWEEK(NOW())");
    $stats['weekly'] = $stmt->fetch(PDO::FETCH_ASSOC)['weekly'];
    
    // 今日報名人數
    $stmt = $pdo->query("SELECT COUNT(*) as daily FROM enrollment_intention WHERE DATE(created_at) = CURDATE()");
    $stats['daily'] = $stmt->fetch(PDO::FETCH_ASSOC)['daily'];
    
    return $stats;
}

function getDepartmentStats($pdo) {
    $stats = [];
    
    // 科系統計（三個意願合併，但保留志願順序資訊）
    $stmt = $pdo->query("
        SELECT 
            '第一志願' as priority,
            COALESCE(intention1, '無特定') as department,
            COUNT(*) as count
        FROM enrollment_intention 
        WHERE intention1 IS NOT NULL AND intention1 != ''
        GROUP BY intention1
        
        UNION ALL
        
        SELECT 
            '第二志願' as priority,
            COALESCE(intention2, '無特定') as department,
            COUNT(*) as count
        FROM enrollment_intention 
        WHERE intention2 IS NOT NULL AND intention2 != '' AND intention2 != '無特定'
        GROUP BY intention2
        
        UNION ALL
        
        SELECT 
            '第三志願' as priority,
            COALESCE(intention3, '無特定') as department,
            COUNT(*) as count
        FROM enrollment_intention 
        WHERE intention3 IS NOT NULL AND intention3 != '' AND intention3 != '無特定'
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

function getSystemStats($pdo) {
    $stats = [];
    
    // 學制統計
    $stmt = $pdo->query("
        SELECT 
            COALESCE(system1, '未選擇') as system_type,
            COUNT(*) as count
        FROM enrollment_intention 
        WHERE system1 IS NOT NULL AND system1 != ''
        GROUP BY system1
        
        UNION ALL
        
        SELECT 
            COALESCE(system2, '未選擇') as system_type,
            COUNT(*) as count
        FROM enrollment_intention 
        WHERE system2 IS NOT NULL AND system2 != '' AND system2 != '未選擇'
        GROUP BY system2
        
        UNION ALL
        
        SELECT 
            COALESCE(system3, '未選擇') as system_type,
            COUNT(*) as count
        FROM enrollment_intention 
        WHERE system3 IS NOT NULL AND system3 != '' AND system3 != '未選擇'
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

function getGradeStats($pdo) {
    $stmt = $pdo->query("
        SELECT 
            COALESCE(current_grade, '未填寫') as grade,
            COUNT(*) as count
        FROM enrollment_intention 
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

function getGenderStats($pdo) {
    $stmt = $pdo->query("
        SELECT 
            COALESCE(gender, '未填寫') as gender,
            COUNT(*) as count
        FROM enrollment_intention 
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

function getIdentityStats($pdo) {
    $stmt = $pdo->query("
        SELECT 
            COALESCE(identity, '未填寫') as identity,
            COUNT(*) as count
        FROM enrollment_intention 
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

function getMonthlyStats($pdo) {
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM enrollment_intention 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
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
?>