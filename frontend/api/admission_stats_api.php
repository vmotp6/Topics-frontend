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
        case 'grade':
            $stats = getGradeStats($pdo, $department_filter);
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
        case 'school':
            $stats = getSchoolStats($pdo, $department_filter);
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
        case 'session':
            $stats = getSessionStats($pdo, $department_filter);
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
        case 'course':
            $stats = getCourseStats($pdo, $department_filter);
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
        case 'monthly':
            $stats = getMonthlyStats($pdo, $department_filter);
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
        case 'receive_info':
            $stats = getReceiveInfoStats($pdo, $department_filter);
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
function buildDepartmentFilter($department) {
    if (empty($department)) {
        return '1=1'; // 不篩選
    }
    // 篩選五專入學說明會中與指定科系相關的記錄
    // 這裡假設有department欄位或相關的科系資訊
    return "department = '{$department}' OR department LIKE '%{$department}%'";
}

// 總覽統計
function getOverviewStats($pdo, $department_filter = '') {
    try {
        $filter = buildDepartmentFilter($department_filter);
        $sql = "SELECT 
                    COUNT(*) as total_applications,
                    COUNT(DISTINCT a.school) as total_schools,
                    COUNT(DISTINCT a.session_id) as total_sessions
                FROM admission_applications a WHERE $filter";
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_applications' => (int)$result['total_applications'],
            'total_schools' => (int)$result['total_schools'],
            'total_sessions' => (int)$result['total_sessions']
        ];
    } catch (PDOException $e) {
        error_log("總覽統計錯誤: " . $e->getMessage());
        return ['error' => '無法獲取總覽統計'];
    }
}

// 年級分布統計
function getGradeStats($pdo) {
    try {
        $sql = "SELECT 
                    COALESCE(a.grade, '未填寫') as grade_code,
                    COALESCE(io.name, a.grade, '未填寫') as grade_name,
                    COUNT(*) as count
                FROM admission_applications a
                LEFT JOIN identity_options io ON a.grade = io.code
                GROUP BY a.grade
                ORDER BY COALESCE(io.name, a.grade)";
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($row) {
            return [
                'name' => $row['grade_name'],
                'value' => (int)$row['count']
            ];
        }, $results);
    } catch (PDOException $e) {
        error_log("年級統計錯誤: " . $e->getMessage());
        return ['error' => '無法獲取年級統計'];
    }
}

// 學校分布統計
function getSchoolStats($pdo) {
    try {
        $sql = "SELECT 
                    sd.name as school_name,
                    a.school as school_code,
                    COUNT(*) as count
                FROM admission_applications a
                LEFT JOIN school_data sd ON a.school = sd.school_code
                GROUP BY a.school, sd.name
                ORDER BY count DESC
                LIMIT 10";
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 處理結果，將 NULL 轉換為適當的顯示名稱
        $formattedResults = array_map(function($row) {
            $schoolName = $row['school_name'];
            if ($schoolName === null || $schoolName === '') {
                $schoolName = $row['school_code'] ?: '未填寫';
            }
            return [
                'name' => $schoolName,
                'value' => (int)$row['count']
            ];
        }, $results);
        
        return $formattedResults;
    } catch (PDOException $e) {
        error_log("學校統計錯誤: " . $e->getMessage());
        return ['error' => '無法獲取學校統計'];
    }
}

// 場次分布統計
function getSessionStats($pdo) {
    try {
        $sql = "SELECT 
                    s.session_name,
                    a.session_id,
                    COUNT(*) as count
                FROM admission_applications a
                LEFT JOIN admission_sessions s ON a.session_id = s.id
                WHERE a.session_id IS NOT NULL
                GROUP BY a.session_id, s.session_name
                ORDER BY count DESC";
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 處理結果，將 NULL 轉換為適當的顯示名稱
        $formattedResults = array_map(function($row) {
            $sessionName = $row['session_name'];
            if ($sessionName === null || $sessionName === '') {
                $sessionName = '場次ID: ' . $row['session_id'];
            }
            return [
                'name' => $sessionName,
                'value' => (int)$row['count']
            ];
        }, $results);
        
        return $formattedResults;
    } catch (PDOException $e) {
        error_log("場次統計錯誤: " . $e->getMessage());
        return ['error' => '無法獲取場次統計'];
    }
}

// 課程選擇統計
function getCourseStats($pdo) {
    try {
        // 統計第一選擇課程
        $sql1 = "SELECT 
                    COALESCE(d1.name, course_priority_1, '未選擇') as course_name,
                    COUNT(*) as count
                FROM admission_applications a
                LEFT JOIN departments d1 ON a.course_priority_1 = d1.code
                WHERE course_priority_1 IS NOT NULL AND course_priority_1 != ''
                GROUP BY course_priority_1, d1.name
                ORDER BY count DESC";
        $stmt1 = $pdo->query($sql1);
        $results1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);
        
        // 統計第二選擇課程
        $sql2 = "SELECT 
                    COALESCE(d2.name, course_priority_2, '未選擇') as course_name,
                    COUNT(*) as count
                FROM admission_applications a
                LEFT JOIN departments d2 ON a.course_priority_2 = d2.code
                WHERE course_priority_2 IS NOT NULL AND course_priority_2 != ''
                GROUP BY course_priority_2, d2.name
                ORDER BY count DESC";
        $stmt2 = $pdo->query($sql2);
        $results2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        // 合併統計結果
        $courseStats = [];
        
        // 處理第一選擇
        foreach ($results1 as $row) {
            $courseName = $row['course_name'];
            if (!isset($courseStats[$courseName])) {
                $courseStats[$courseName] = ['first' => 0, 'second' => 0];
            }
            $courseStats[$courseName]['first'] = (int)$row['count'];
        }
        
        // 處理第二選擇
        foreach ($results2 as $row) {
            $courseName = $row['course_name'];
            if (!isset($courseStats[$courseName])) {
                $courseStats[$courseName] = ['first' => 0, 'second' => 0];
            }
            $courseStats[$courseName]['second'] = (int)$row['count'];
        }
        
        // 轉換為API格式
        $formattedStats = [];
        foreach ($courseStats as $course => $stats) {
            $total = $stats['first'] + $stats['second'];
            $formattedStats[] = [
                'name' => $course,
                'value' => $total,
                'first_choice' => $stats['first'],
                'second_choice' => $stats['second']
            ];
        }
        
        // 按總數排序
        usort($formattedStats, function($a, $b) {
            return $b['value'] - $a['value'];
        });
        
        return $formattedStats;
    } catch (PDOException $e) {
        error_log("課程統計錯誤: " . $e->getMessage());
        return ['error' => '無法獲取課程統計'];
    }
}

// 月度趨勢統計
function getMonthlyStats($pdo) {
    try {
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as count
                FROM admission_applications 
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

// 資訊接收意願統計
function getReceiveInfoStats($pdo) {
    try {
        $sql = "SELECT 
                    COALESCE(receive_info, '') as receive_info,
                    COUNT(*) as count
                FROM admission_applications 
                GROUP BY receive_info
                ORDER BY count DESC";
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 將 0/1 或 '0'/'1' 映射為 中文 標籤
        $mapped = [];
        foreach ($results as $row) {
            $raw = $row['receive_info'];
            if ($raw === null || $raw === '') {
                $label = '未填寫';
            } elseif ($raw === 1 || $raw === '1') {
                $label = '是';
            } elseif ($raw === 0 || $raw === '0') {
                $label = '否';
            } else {
                // 其他可能的值，直接轉為字串顯示
                $label = (string)$raw;
            }

            $mapped[] = [
                'name' => $label,
                'value' => (int)$row['count']
            ];
        }

        return $mapped;
    } catch (PDOException $e) {
        error_log("資訊接收統計錯誤: " . $e->getMessage());
        return ['error' => '無法獲取資訊接收統計'];
    }
}

