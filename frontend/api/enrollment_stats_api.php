<?php
// 關閉錯誤顯示，確保只輸出 JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// 載入 session 配置
require_once '../session_config.php';
// 載入資料庫配置
$config_path = __DIR__ . '/../config.php';
if (!file_exists($config_path)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => '找不到 config.php 檔案: ' . $config_path]);
    exit;
}
require_once $config_path;

// 檢查必要的常數是否已定義
if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USERNAME') || !defined('DB_PASSWORD') || !defined('DB_CHARSET')) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => '資料庫配置常數未定義']);
    exit;
}

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
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    $action = $_GET['action'] ?? 'overview';
    $department_filter = $_GET['department'] ?? '';

    switch ($action) {
        case 'overview':
            // 總體統計
            $stats = getOverviewStats($pdo, $department_filter);
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'department':
            // 科系統計
            $stats = getDepartmentStats($pdo, $department_filter);
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'system':
            // 學制統計
            $stats = getSystemStats($pdo, $department_filter);
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'grade':
            // 年級統計
            $stats = getGradeStats($pdo, $department_filter);
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'gender':
            // 性別統計
            $stats = getGenderStats($pdo, $department_filter);
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'identity':
            // 身分別統計
            $stats = getIdentityStats($pdo, $department_filter);
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'monthly':
            // 月度統計
            $stats = getMonthlyStats($pdo, $department_filter);
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'school_department':
            // 國中選擇科系統計（限管理員和學校行政人員）
            $stats = getSchoolDepartmentStats($pdo, $department_filter);
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'assigned_department':
            // 已分配科系（assigned_department）統計，顯示中文名稱
            $stats = getAssignedDepartmentStats($pdo, $department_filter);
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => '無效的操作'], JSON_UNESCAPED_UNICODE);
    }

} catch (PDOException $e) {
    error_log("資料庫錯誤: " . $e->getMessage());
    error_log("資料庫配置: DB_HOST=" . (defined('DB_HOST') ? DB_HOST : '未定義') . ", DB_NAME=" . (defined('DB_NAME') ? DB_NAME : '未定義'));
    error_log("SQL 錯誤詳情: " . print_r($e->errorInfo, true));
    http_response_code(500);
    echo json_encode(['error' => '資料庫連接失敗: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("一般錯誤: " . $e->getMessage());
    error_log("錯誤堆疊: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => '系統錯誤: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
    // 捕獲 PHP 7+ 的 Error 類別（包括致命錯誤）
    error_log("致命錯誤: " . $e->getMessage());
    error_log("錯誤堆疊: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => '系統錯誤: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

// 輔助函數：建立科系篩選的 WHERE 條件（針對 enrollment_intention 表）
function buildDepartmentFilterForEnrollmentIntention($pdo, $department) {
    if (empty($department)) {
        return '1=1'; // 不篩選
    }
    
    // 使用 DESCRIBE 來檢查 enrollment_intention 表的欄位（最可靠的方法）
    try {
        // 首先檢查表是否存在
        $table_check = $pdo->query("SHOW TABLES LIKE 'enrollment_intention'");
        if ($table_check->rowCount() === 0) {
            error_log("enrollment_intention 表不存在");
            return '1=1';
        }
        
        $describe_result = $pdo->query("DESCRIBE enrollment_intention");
        if ($describe_result === false) {
            error_log("無法描述 enrollment_intention 表結構");
            return '1=1';
        }
        
        $columns = $describe_result->fetchAll(PDO::FETCH_COLUMN);
        if (empty($columns)) {
            error_log("enrollment_intention 表沒有欄位");
            return '1=1';
        }
        
        $has_intention1 = in_array('intention1', $columns);
        $has_intention2 = in_array('intention2', $columns);
        $has_intention3 = in_array('intention3', $columns);
        
        if ($has_intention1 && $has_intention2 && $has_intention3) {
            // 所有 intention 欄位都存在，可以構建篩選條件
            $escaped_dept = $pdo->quote('%' . $department . '%');
            return "(intention1 LIKE $escaped_dept OR intention2 LIKE $escaped_dept OR intention3 LIKE $escaped_dept)";
        } else {
            // 欄位不存在，返回不篩選
            error_log("enrollment_intention 表缺少 intention 欄位，無法進行科系篩選");
            return '1=1';
        }
    } catch (PDOException $e) {
        error_log("檢查 enrollment_intention 表結構失敗: " . $e->getMessage());
        error_log("SQL 錯誤詳情: " . print_r($e->errorInfo, true));
        return '1=1';
    } catch (Exception $e) {
        error_log("檢查 enrollment_intention 表結構時發生一般錯誤: " . $e->getMessage());
        return '1=1';
    }
}

// 輔助函數：建立科系篩選的 WHERE 條件（通用版本，可能返回針對不同表的條件）
function buildDepartmentFilter($pdo, $department) {
    if (empty($department)) {
        return '1=1'; // 不篩選
    }
    
    // 檢查 enrollment_intention 表是否有 intention1 欄位
    $check_intention1 = $pdo->query("SHOW COLUMNS FROM enrollment_intention LIKE 'intention1'");
    $has_intention_columns = $check_intention1->rowCount() > 0;
    
    // 檢查是否有 enrollment_preferences 表（正規化結構）
    $check_preferences_table = $pdo->query("SHOW TABLES LIKE 'enrollment_preferences'");
    $has_preferences_table = $check_preferences_table->rowCount() > 0;
    
    // 檢查是否有 enrollment_applications 表（舊結構）
    $check_applications_table = $pdo->query("SHOW TABLES LIKE 'enrollment_applications'");
    $has_applications_table = $check_applications_table->rowCount() > 0;
    
    $escaped_dept = $pdo->quote('%' . $department . '%');
    
    if ($has_intention_columns) {
        // 使用 enrollment_intention 表的 intention1, intention2, intention3 欄位
        return "(intention1 LIKE $escaped_dept OR intention2 LIKE $escaped_dept OR intention3 LIKE $escaped_dept)";
    } elseif ($has_preferences_table) {
        // 使用 enrollment_preferences 表（正規化結構）
        // 檢查 enrollment_preferences 表關聯到哪個表
        // 可能關聯到 enrollment_applications_normalized 或 enrollment_intention
        $check_normalized_table = $pdo->query("SHOW TABLES LIKE 'enrollment_applications_normalized'");
        $has_normalized_table = $check_normalized_table->rowCount() > 0;
        
        if ($has_normalized_table) {
            // 關聯到 enrollment_applications_normalized
            return "id IN (
                SELECT DISTINCT ep.enrollment_application_id 
                FROM enrollment_preferences ep
                INNER JOIN departments d ON ep.department_id = d.id
                WHERE d.name LIKE $escaped_dept
            )";
        } else {
            // 如果沒有 enrollment_applications_normalized，可能關聯到 enrollment_intention
            // 但需要確認 enrollment_preferences 表的實際結構
            // 暫時返回不篩選，因為無法確定關聯關係
            return '1=1';
        }
    } elseif ($has_applications_table) {
        // 使用 enrollment_applications 表（舊表）
        return "(intention1 LIKE $escaped_dept OR intention2 LIKE $escaped_dept OR intention3 LIKE $escaped_dept)";
    } else {
        // 如果都沒有，返回不篩選
        return '1=1';
    }
}

function getOverviewStats($pdo, $department_filter = '') {
    try {
        // 檢查 enrollment_intention 表是否存在
        $table_check = $pdo->query("SHOW TABLES LIKE 'enrollment_intention'");
        if ($table_check->rowCount() === 0) {
            error_log("enrollment_intention 表不存在，無法獲取總覽統計");
            return ['total' => 0, 'monthly' => 0, 'weekly' => 0, 'daily' => 0];
        }
        
        $stats = [];
        // 使用專門針對 enrollment_intention 表的篩選函數
        $filter = buildDepartmentFilterForEnrollmentIntention($pdo, $department_filter);
        
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
    } catch (PDOException $e) {
        error_log("總覽統計錯誤: " . $e->getMessage());
        error_log("SQL 錯誤詳情: " . print_r($e->errorInfo, true));
        return ['error' => '無法獲取總覽統計: ' . $e->getMessage()];
    } catch (Exception $e) {
        error_log("總覽統計一般錯誤: " . $e->getMessage());
        return ['error' => '無法獲取總覽統計: ' . $e->getMessage()];
    }
}

function getDepartmentStats($pdo, $department_filter = '') {
    try {
        // 檢查 enrollment_intention 表是否存在
        $table_check = $pdo->query("SHOW TABLES LIKE 'enrollment_intention'");
        if ($table_check->rowCount() === 0) {
            error_log("enrollment_intention 表不存在，無法獲取科系統計");
            return [];
        }
        
        $stats = [];
        
        // 檢查 enrollment_choices 表是否存在（正規化結構）
        $check_choices_table = $pdo->query("SHOW TABLES LIKE 'enrollment_choices'");
        $has_choices_table = $check_choices_table->rowCount() > 0;
        
        // 檢查 enrollment_intention 表是否有 intention1, intention2, intention3 欄位（舊結構）
        $describe_result = $pdo->query("DESCRIBE enrollment_intention");
        $columns = $describe_result ? $describe_result->fetchAll(PDO::FETCH_COLUMN) : [];
        $has_intention1 = in_array('intention1', $columns);
        $has_intention2 = in_array('intention2', $columns);
        $has_intention3 = in_array('intention3', $columns);
        
        if ($has_choices_table) {
            // 使用正規化的 enrollment_choices 表結構
            try {
                $stmt = $pdo->query("
                    SELECT 
                        CASE ec.choice_order
                            WHEN 1 THEN '第一志願'
                            WHEN 2 THEN '第二志願'
                            WHEN 3 THEN '第三志願'
                            ELSE '未知'
                        END as priority,
                        COALESCE(d.name, ec.department_code, '無特定') as department,
                        COUNT(*) as count
                    FROM enrollment_intention ei
                    INNER JOIN enrollment_choices ec ON ei.id = ec.enrollment_id
                    LEFT JOIN departments d ON ec.department_code = d.code
                    WHERE ec.department_code IS NOT NULL AND ec.department_code != ''
                    GROUP BY ec.choice_order, COALESCE(d.name, ec.department_code, '無特定')
                ");
                
                $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("查詢科系統計（enrollment_choices）失敗: " . $e->getMessage());
                error_log("SQL 錯誤詳情: " . print_r($e->errorInfo, true));
                $raw_data = [];
            }
        } elseif ($has_intention1 && $has_intention2 && $has_intention3) {
            // 使用舊的 intention1, intention2, intention3 欄位結構
            $filter = buildDepartmentFilterForEnrollmentIntention($pdo, $department_filter);
            
            try {
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
            } catch (PDOException $e) {
                error_log("查詢科系統計（intention欄位）失敗: " . $e->getMessage());
                error_log("SQL 錯誤詳情: " . print_r($e->errorInfo, true));
                $raw_data = [];
            }
        } else {
            // 兩種結構都不存在
            error_log("科系統計：找不到可用的資料表或欄位（enrollment_choices: " . ($has_choices_table ? '存在' : '不存在') . ", intention欄位: " . ($has_intention1 && $has_intention2 && $has_intention3 ? '存在' : '不存在') . "）");
            return [];
        }
        
        if (empty($raw_data)) {
            error_log("科系統計：查詢結果為空");
            return [];
        }
    
        // 合併相同科系的數據，但保留志願分布資訊
        $merged = [];
        foreach ($raw_data as $row) {
            $dept = $row['department'];
            $priority = $row['priority'];
            $count = (int)$row['count'];
            
            if (!isset($merged[$dept])) {
                $merged[$dept] = [
                    'total' => 0,
                    'priorities' => []
                ];
            }
            
            $merged[$dept]['total'] += $count;
            $merged[$dept]['priorities'][$priority] = ($merged[$dept]['priorities'][$priority] ?? 0) + $count;
        }
        
        // 計算實際的學生總數（去重）
        $total_students_stmt = $pdo->query("SELECT COUNT(DISTINCT id) as total FROM enrollment_intention");
        $total_students_result = $total_students_stmt->fetch(PDO::FETCH_ASSOC);
        $total_students = (int)($total_students_result['total'] ?? 0);
        
        // 轉換為前端需要的格式
        foreach ($merged as $department => $data) {
            // 創建詳細的志願分布描述
            $priority_details = [];
            foreach (['第一志願', '第二志願', '第三志願'] as $priority) {
                if (isset($data['priorities'][$priority]) && $data['priorities'][$priority] > 0) {
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
        
        // 回傳物件包含科系分布和實際學生總數
        return [
            'data' => $stats,
            'total_students' => $total_students
        ];
    } catch (PDOException $e) {
        error_log("科系統計錯誤: " . $e->getMessage());
        error_log("SQL 錯誤詳情: " . print_r($e->errorInfo, true));
        return ['error' => '無法獲取科系統計: ' . $e->getMessage()];
    } catch (Exception $e) {
        error_log("科系統計一般錯誤: " . $e->getMessage());
        return ['error' => '無法獲取科系統計: ' . $e->getMessage()];
    }
}

function getSystemStats($pdo, $department_filter = '') {
    try {
        $stats = [];
        
        // 檢查 enrollment_intention 表是否有 system1, system2, system3 欄位
        $check_columns = $pdo->query("SHOW COLUMNS FROM enrollment_intention LIKE 'system1'");
        $has_system_columns = $check_columns->rowCount() > 0;
        
        // 檢查是否有 enrollment_preferences 表（正規化結構）
        $check_preferences_table = $pdo->query("SHOW TABLES LIKE 'enrollment_preferences'");
        $has_preferences_table = $check_preferences_table->rowCount() > 0;
        
        // 檢查是否有 enrollment_applications 表（舊結構）
        $check_applications_table = $pdo->query("SHOW TABLES LIKE 'enrollment_applications'");
        $has_applications_table = $check_applications_table->rowCount() > 0;
        
        if ($has_system_columns) {
            // 使用 enrollment_intention 表的 system1, system2, system3 欄位（舊結構）
            // 使用 DESCRIBE 來檢查欄位是否存在（最可靠的方法）
            $describe_result = $pdo->query("DESCRIBE enrollment_intention");
            $columns = $describe_result->fetchAll(PDO::FETCH_COLUMN);
            $has_intention1 = in_array('intention1', $columns);
            $has_intention2 = in_array('intention2', $columns);
            $has_intention3 = in_array('intention3', $columns);
            
            // 確保 filter 變數已初始化為不篩選
            $filter = '1=1';
            
            // 只有在 enrollment_intention 表有所有 intention 欄位時才能進行科系篩選
            if ($has_intention1 && $has_intention2 && $has_intention3 && !empty($department_filter)) {
                // 所有 intention 欄位都存在，可以構建篩選條件
                $escaped_dept = $pdo->quote('%' . $department_filter . '%');
                $filter = "(intention1 LIKE $escaped_dept OR intention2 LIKE $escaped_dept OR intention3 LIKE $escaped_dept)";
            } else if (!empty($department_filter)) {
                // 欄位不存在但指定了科系篩選，使用不篩選並記錄警告
                $filter = '1=1';
                error_log("學制統計：enrollment_intention 表缺少 intention 欄位（intention1: " . ($has_intention1 ? '存在' : '不存在') . ", intention2: " . ($has_intention2 ? '存在' : '不存在') . ", intention3: " . ($has_intention3 ? '存在' : '不存在') . "），無法進行科系篩選");
            }
            
            // 使用 try-catch 來捕獲 SQL 錯誤
            try {
                // 構建 SQL 查詢
                $sql = "
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
                ";
                
                // 在執行前最後一次檢查：確保 filter 不包含不存在的欄位
                if (strpos($filter, 'intention1') !== false || strpos($filter, 'intention2') !== false || strpos($filter, 'intention3') !== false) {
                    // 如果 filter 包含 intention 欄位，再次確認這些欄位存在
                    $describe_result = $pdo->query("DESCRIBE enrollment_intention");
                    $columns = $describe_result->fetchAll(PDO::FETCH_COLUMN);
                    if (!in_array('intention1', $columns) || !in_array('intention2', $columns) || !in_array('intention3', $columns)) {
                        // 欄位不存在，強制使用不篩選
                        $filter = '1=1';
                        $sql = "
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
                        ";
                    }
                }
                
                $stmt = $pdo->query($sql);
                $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // 如果查詢失敗，可能是因為 filter 中包含了不存在的欄位
                // 重新嘗試不使用 filter
                error_log("學制統計查詢失敗，嘗試不使用篩選: " . $e->getMessage());
                error_log("失敗的 SQL: " . $sql);
                error_log("使用的 filter: " . $filter);
                error_log("has_intention_columns: " . ($has_intention_columns ? 'true' : 'false'));
                
                try {
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
                } catch (PDOException $e2) {
                    // 如果仍然失敗，返回空結果
                    error_log("學制統計查詢完全失敗: " . $e2->getMessage());
                    return [];
                }
            }
        } elseif ($has_preferences_table) {
            // 使用 enrollment_preferences 表（正規化結構）
            if (!empty($department_filter)) {
                // 如果指定了科系篩選，需要 JOIN departments 表
                $sql = "
                    SELECT 
                        COALESCE(es.name, '未選擇') as system_type,
                        COUNT(*) as count
                    FROM enrollment_preferences ep
                    INNER JOIN education_systems es ON ep.education_system_id = es.id
                    INNER JOIN departments d ON ep.department_id = d.id
                    WHERE d.name = " . $pdo->quote($department_filter) . "
                    GROUP BY es.name
                ";
            } else {
                $sql = "
                    SELECT 
                        COALESCE(es.name, '未選擇') as system_type,
                        COUNT(*) as count
                    FROM enrollment_preferences ep
                    INNER JOIN education_systems es ON ep.education_system_id = es.id
                    GROUP BY es.name
                ";
            }
            
            $stmt = $pdo->query($sql);
            $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($has_applications_table) {
            // 使用 enrollment_applications 表（舊表）
            $filter = buildDepartmentFilter($pdo, $department_filter);
            
            $stmt = $pdo->query("
                SELECT 
                    COALESCE(system1, '未選擇') as system_type,
                    COUNT(*) as count
                FROM enrollment_applications 
                WHERE system1 IS NOT NULL AND system1 != '' AND $filter
                GROUP BY system1
                
                UNION ALL
                
                SELECT 
                    COALESCE(system2, '未選擇') as system_type,
                    COUNT(*) as count
                FROM enrollment_applications 
                WHERE system2 IS NOT NULL AND system2 != '' AND system2 != '未選擇' AND $filter
                GROUP BY system2
                
                UNION ALL
                
                SELECT 
                    COALESCE(system3, '未選擇') as system_type,
                    COUNT(*) as count
                FROM enrollment_applications 
                WHERE system3 IS NOT NULL AND system3 != '' AND system3 != '未選擇' AND $filter
                GROUP BY system3
            ");
            
            $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // 如果都沒有，返回空結果
            error_log("學制統計：找不到可用的資料表或欄位");
            return [];
        }
        
        // 合併相同學制的數據
        $merged = [];
        foreach ($raw_data as $row) {
            $system = $row['system_type'] ?: '未選擇';
            if (isset($merged[$system])) {
                $merged[$system] += (int)$row['count'];
            } else {
                $merged[$system] = (int)$row['count'];
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
    } catch (PDOException $e) {
        error_log("學制統計錯誤: " . $e->getMessage());
        error_log("SQL 錯誤詳情: " . print_r($e->errorInfo, true));
        return ['error' => '無法獲取學制統計: ' . $e->getMessage()];
    }
}

function getGradeStats($pdo, $department_filter = '') {
    try {
        // 檢查 enrollment_intention 表是否存在
        $table_check = $pdo->query("SHOW TABLES LIKE 'enrollment_intention'");
        if ($table_check->rowCount() === 0) {
            error_log("enrollment_intention 表不存在，無法獲取年級統計");
            return [];
        }
        
        // 使用專門針對 enrollment_intention 表的篩選函數
        $filter = buildDepartmentFilterForEnrollmentIntention($pdo, $department_filter);
        
        $stmt = $pdo->query("
            SELECT 
                COALESCE(ei.current_grade, '未填寫') as grade_code,
                COALESCE(io.name, ei.current_grade, '未填寫') as grade_name,
                COUNT(*) as count
            FROM enrollment_intention ei
            LEFT JOIN identity_options io ON ei.current_grade = io.code
            WHERE $filter
            GROUP BY ei.current_grade
            ORDER BY COALESCE(io.name, ei.current_grade)
        ");
        
        $stats = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[] = [
                'name' => $row['grade_name'],
                'value' => (int)$row['count']
            ];
        }
        
        return $stats;
    } catch (PDOException $e) {
        error_log("年級統計錯誤: " . $e->getMessage());
        error_log("SQL 錯誤詳情: " . print_r($e->errorInfo, true));
        return ['error' => '無法獲取年級統計: ' . $e->getMessage()];
    } catch (Exception $e) {
        error_log("年級統計一般錯誤: " . $e->getMessage());
        return ['error' => '無法獲取年級統計: ' . $e->getMessage()];
    }
}

function getGenderStats($pdo, $department_filter = '') {
    try {
        // 檢查 enrollment_intention 表是否存在
        $table_check = $pdo->query("SHOW TABLES LIKE 'enrollment_intention'");
        if ($table_check->rowCount() === 0) {
            error_log("enrollment_intention 表不存在，無法獲取性別統計");
            return [];
        }
        
        // 使用專門針對 enrollment_intention 表的篩選函數
        $filter = buildDepartmentFilterForEnrollmentIntention($pdo, $department_filter);
        
        $stmt = $pdo->query("
            SELECT 
                COALESCE(gender, '未填寫') as gender_code,
                CASE COALESCE(gender, '未填寫')
                    WHEN 1 THEN '男'
                    WHEN 2 THEN '女'
                    WHEN '1' THEN '男'
                    WHEN '2' THEN '女'
                    ELSE COALESCE(gender, '未填寫')
                END as gender_name,
                COUNT(*) as count
            FROM enrollment_intention 
            WHERE $filter
            GROUP BY gender
            ORDER BY 
                CASE COALESCE(gender, '未填寫')
                    WHEN 1 THEN 1
                    WHEN '1' THEN 1
                    WHEN 2 THEN 2
                    WHEN '2' THEN 2
                    ELSE 3
                END
        ");
        
        $stats = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[] = [
                'name' => $row['gender_name'],
                'value' => (int)$row['count']
            ];
        }
        
        return $stats;
    } catch (PDOException $e) {
        error_log("性別統計錯誤: " . $e->getMessage());
        error_log("SQL 錯誤詳情: " . print_r($e->errorInfo, true));
        return ['error' => '無法獲取性別統計: ' . $e->getMessage()];
    } catch (Exception $e) {
        error_log("性別統計一般錯誤: " . $e->getMessage());
        return ['error' => '無法獲取性別統計: ' . $e->getMessage()];
    }
}

function getIdentityStats($pdo, $department_filter = '') {
    try {
        // 檢查 enrollment_intention 表是否存在
        $table_check = $pdo->query("SHOW TABLES LIKE 'enrollment_intention'");
        if ($table_check->rowCount() === 0) {
            error_log("enrollment_intention 表不存在，無法獲取身分別統計");
            return [];
        }
        
        // 使用專門針對 enrollment_intention 表的篩選函數
        $filter = buildDepartmentFilterForEnrollmentIntention($pdo, $department_filter);
        
        $stmt = $pdo->query("
            SELECT 
                COALESCE(identity, '未填寫') as identity_code,
                CASE COALESCE(identity, '未填寫')
                    WHEN 1 THEN '學生'
                    WHEN 2 THEN '家長'
                    WHEN '1' THEN '學生'
                    WHEN '2' THEN '家長'
                    ELSE COALESCE(identity, '未填寫')
                END as identity_name,
                COUNT(*) as count
            FROM enrollment_intention 
            WHERE $filter
            GROUP BY identity
            ORDER BY 
                CASE COALESCE(identity, '未填寫')
                    WHEN 1 THEN 1
                    WHEN '1' THEN 1
                    WHEN 2 THEN 2
                    WHEN '2' THEN 2
                    ELSE 3
                END
        ");
        
        $stats = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[] = [
                'name' => $row['identity_name'],
                'value' => (int)$row['count']
            ];
        }
        
        return $stats;
    } catch (PDOException $e) {
        error_log("身分別統計錯誤: " . $e->getMessage());
        error_log("SQL 錯誤詳情: " . print_r($e->errorInfo, true));
        return ['error' => '無法獲取身分別統計: ' . $e->getMessage()];
    } catch (Exception $e) {
        error_log("身分別統計一般錯誤: " . $e->getMessage());
        return ['error' => '無法獲取身分別統計: ' . $e->getMessage()];
    }
}

function getMonthlyStats($pdo, $department_filter = '') {
    try {
        // 檢查 enrollment_intention 表是否存在
        $table_check = $pdo->query("SHOW TABLES LIKE 'enrollment_intention'");
        if ($table_check->rowCount() === 0) {
            error_log("enrollment_intention 表不存在，無法獲取月度統計");
            return [];
        }
        
        // 使用專門針對 enrollment_intention 表的篩選函數
        $filter = buildDepartmentFilterForEnrollmentIntention($pdo, $department_filter);
        
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
    } catch (PDOException $e) {
        error_log("月度統計錯誤: " . $e->getMessage());
        error_log("SQL 錯誤詳情: " . print_r($e->errorInfo, true));
        return ['error' => '無法獲取月度統計: ' . $e->getMessage()];
    } catch (Exception $e) {
        error_log("月度統計一般錯誤: " . $e->getMessage());
        return ['error' => '無法獲取月度統計: ' . $e->getMessage()];
    }
}

function getSchoolDepartmentStats($pdo, $department_filter = '') {
    try {
        // 國中選擇科系分佈應該顯示所有數據，不受部門限制
        $filter = '1=1'; // 不應用部門篩選
        
        // 檢查 enrollment_choices 表是否存在
        $check_table = $pdo->query("SHOW TABLES LIKE 'enrollment_choices'");
        $has_choices_table = $check_table->rowCount() > 0;
        
        // 檢查 school_data 表是否存在（用於獲取學校名稱）
        $check_school_table = $pdo->query("SHOW TABLES LIKE 'school_data'");
        $has_school_table = $check_school_table->rowCount() > 0;
        
        if ($has_choices_table) {
            // 使用正規化的 enrollment_choices 表結構
            // 統計每個國中選擇的科系（三個志願都計算）
            $school_join = $has_school_table 
                ? "LEFT JOIN school_data sd ON ei.junior_high = sd.school_code"
                : "";
            $school_select = $has_school_table 
                ? "COALESCE(sd.name, ei.junior_high, '未填寫')"
                : "COALESCE(ei.junior_high, '未填寫')";
            
            try {
                // 構建 GROUP BY 子句，使用與 SELECT 相同的表達式
                $group_by_school = $has_school_table 
                    ? "COALESCE(sd.name, ei.junior_high, '未填寫')"
                    : "COALESCE(ei.junior_high, '未填寫')";
                $group_by_dept = "COALESCE(d.name, ec.department_code, '無特定')";
                
                $stmt = $pdo->query("
                    SELECT 
                        $school_select as school_name,
                        $group_by_dept as department,
                        COUNT(*) as count,
                        CASE ec.choice_order
                            WHEN 1 THEN '第一志願'
                            WHEN 2 THEN '第二志願'
                            WHEN 3 THEN '第三志願'
                            ELSE '未知'
                        END as priority
                    FROM enrollment_intention ei
                    INNER JOIN enrollment_choices ec ON ei.id = ec.enrollment_id
                    LEFT JOIN departments d ON ec.department_code = d.code
                    $school_join
                    WHERE ei.junior_high IS NOT NULL AND ei.junior_high != ''
                        AND ec.department_code IS NOT NULL AND ec.department_code != ''
                        AND $filter
                    GROUP BY $group_by_school, $group_by_dept, ec.choice_order
                    ORDER BY school_name, department, ec.choice_order
                ");
            } catch (PDOException $e) {
                error_log("查詢國中選擇科系統計失敗: " . $e->getMessage());
                error_log("SQL 錯誤詳情: " . print_r($e->errorInfo, true));
                // 返回空結果
                $stmt = $pdo->query("SELECT 1 WHERE 1=0");
            }
        } else {
            // 如果沒有 enrollment_choices 表，返回空數據
            $stmt = $pdo->query("SELECT 1 WHERE 1=0"); // 返回空結果集
        }
        
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
            if (isset($school_stats[$school][$department]['priorities'][$priority])) {
                $school_stats[$school][$department]['priorities'][$priority] += $count;
            } else {
                $school_stats[$school][$department]['priorities'][$priority] = $count;
            }
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
    } catch (Exception $e) {
        error_log("國中選擇科系統計錯誤: " . $e->getMessage());
        error_log("錯誤堆疊: " . $e->getTraceAsString());
        return ['error' => '無法獲取國中選擇科系統計: ' . $e->getMessage()];
    }
}

    // 已分配科系統計（assigned_department） — 顯示 departments.name 中文名稱
    function getAssignedDepartmentStats($pdo, $department_filter = '') {
        try {
            // 檢查 enrollment_intention 表是否存在
            $table_check = $pdo->query("SHOW TABLES LIKE 'enrollment_intention'");
            if ($table_check->rowCount() === 0) {
                error_log("enrollment_intention 表不存在，無法獲取已分配科系統計");
                return [];
            }

            // 使用專門針對 enrollment_intention 表的篩選函數
            $filter = buildDepartmentFilterForEnrollmentIntention($pdo, $department_filter);

            // 統計 assigned_department，並 LEFT JOIN departments 取得中文名稱
            $stmt = $pdo->query("SELECT \
                    COALESCE(ei.assigned_department, '未填寫') as dept_code, \
                    COALESCE(d.name, ei.assigned_department, '未填寫') as dept_name, \
                    COUNT(*) as count \
                FROM enrollment_intention ei \
                LEFT JOIN departments d ON ei.assigned_department = d.code \
                WHERE $filter \
                GROUP BY ei.assigned_department \
                ORDER BY COUNT(*) DESC");

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stats = [];
            foreach ($results as $row) {
                $name = $row['dept_name'] ?: ($row['dept_code'] ?: '未填寫');
                $stats[] = [
                    'name' => $name,
                    'value' => (int)$row['count']
                ];
            }

            return $stats;
        } catch (PDOException $e) {
            error_log("已分配科系統計錯誤: " . $e->getMessage());
            return ['error' => '無法獲取已分配科系統計'];
        }
    }