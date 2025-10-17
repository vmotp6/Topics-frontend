<?php
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
            $stats = getOverviewStats($pdo);
            echo json_encode($stats);
            break;
        case 'gender':
            $stats = getGenderStats($pdo);
            echo json_encode($stats);
            break;
        case 'school_city':
            $stats = getSchoolCityStats($pdo);
            echo json_encode($stats);
            break;
        case 'choices':
            $stats = getChoicesStats($pdo);
            echo json_encode($stats);
            break;
        case 'monthly':
            $stats = getMonthlyStats($pdo);
            echo json_encode($stats);
            break;
        case 'status':
            $stats = getStatusStats($pdo);
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

// 總覽統計
function getOverviewStats($pdo) {
    try {
        $sql = "SELECT 
                    COUNT(*) as total_applications,
                    COUNT(DISTINCT school_city) as total_cities,
                    COUNT(DISTINCT school_name) as total_schools
                FROM continued_admission";
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_applications' => (int)$result['total_applications'],
            'total_cities' => (int)$result['total_cities'],
            'total_schools' => (int)$result['total_schools']
        ];
    } catch (PDOException $e) {
        error_log("總覽統計錯誤: " . $e->getMessage());
        return ['error' => '無法獲取總覽統計'];
    }
}

// 性別分布統計
function getGenderStats($pdo) {
    try {
        $sql = "SELECT 
                    CASE 
                        WHEN gender = 'male' THEN '男'
                        WHEN gender = 'female' THEN '女'
                        ELSE '未填寫'
                    END as gender_name,
                    COUNT(*) as count
                FROM continued_admission 
                GROUP BY gender
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
function getSchoolCityStats($pdo) {
    try {
        $sql = "SELECT 
                    COALESCE(school_city, '未填寫') as city_name,
                    COUNT(*) as count
                FROM continued_admission 
                GROUP BY school_city
                ORDER BY count DESC
                LIMIT 10";
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($row) {
            return [
                'name' => $row['city_name'],
                'value' => (int)$row['count']
            ];
        }, $results);
    } catch (PDOException $e) {
        error_log("縣市統計錯誤: " . $e->getMessage());
        return ['error' => '無法獲取縣市統計'];
    }
}

// 志願選擇統計
function getChoicesStats($pdo) {
    try {
        $sql = "SELECT choices FROM continued_admission WHERE choices IS NOT NULL AND choices != ''";
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $choiceStats = [];
        $totalChoices = 0;
        
        foreach ($results as $choicesJson) {
            $choices = json_decode($choicesJson, true);
            if (is_array($choices)) {
                foreach ($choices as $choice) {
                    if (!empty($choice)) {
                        $choiceStats[$choice] = ($choiceStats[$choice] ?? 0) + 1;
                        $totalChoices++;
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
    } catch (PDOException $e) {
        error_log("志願統計錯誤: " . $e->getMessage());
        return ['error' => '無法獲取志願統計'];
    }
}

// 月度趨勢統計
function getMonthlyStats($pdo) {
    try {
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as count
                FROM continued_admission 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
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
function getStatusStats($pdo) {
    try {
        $sql = "SELECT 
                    CASE 
                        WHEN status = 'pending' THEN '待審核'
                        WHEN status = 'approved' THEN '已錄取'
                        WHEN status = 'rejected' THEN '未錄取'
                        ELSE '未知狀態'
                    END as status_name,
                    COUNT(*) as count
                FROM continued_admission 
                GROUP BY status
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
?>
