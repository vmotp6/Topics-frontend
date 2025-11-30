<?php
/**
 * 續招報名測試腳本
 * 用於快速測試 submit_continued_admission.php 的功能
 * 
 * 使用方法：
 * 1. 在瀏覽器中訪問此文件：http://localhost/Topics-frontend/frontend/test_continued_admission.php
 * 2. 或使用命令行：php test_continued_admission.php
 */

// 引入資料庫配置
require_once 'config.php';

// 獲取所有科系（用於生成志願序字段）
$conn = getDatabaseConnection();
$courses_query = "SELECT name FROM departments ORDER BY code, name";
$courses_result = $conn->query($courses_query);
$all_courses = [];
if ($courses_result) {
    while ($row = $courses_result->fetch_assoc()) {
        $all_courses[] = $row['name'];
    }
}

// 獲取一個真實的學校（用於測試）
$school_query = "SELECT name, city, district, school_code FROM school_data WHERE school_code IS NOT NULL AND school_code != '' AND is_active = 1 LIMIT 1";
$school_result = $conn->query($school_query);
$test_school = null;
if ($school_result && $school_result->num_rows > 0) {
    $test_school = $school_result->fetch_assoc();
}

// 生成志願序字段名稱映射
$courseNameToFieldMap = [];
foreach ($all_courses as $course_name) {
    $fieldName = 'choice_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $course_name));
    $courseNameToFieldMap[$course_name] = $fieldName;
}

// 準備測試數據
$test_data = [
    // 基本資料
    'request_id' => 'test_' . time(),
    'exam_no' => 'TEST' . rand(1000, 9999),
    'student_name' => '測試學生' . rand(1, 100),
    'is_foreign_student' => 'no',
    'id' => 'A' . str_pad(rand(1, 999999999), 9, '0', STR_PAD_LEFT), // 生成隨機身分證字號
    'birth_year' => rand(2005, 2010),
    'birth_month' => rand(1, 12),
    'birth_day' => rand(1, 28),
    'gender' => rand(0, 1) ? 'male' : 'female',
    'phone' => '02-' . rand(10000000, 99999999),
    'mobile' => '09' . rand(10000000, 99999999),
    'school_city' => $test_school ? $test_school['city'] : '台北市',
    'school_name' => $test_school ? ($test_school['name'] . ' (' . $test_school['city'] . $test_school['district'] . ')') : '中正國中 (台北市中正區)',
    
    // 戶籍地址
    'zip' => str_pad(rand(100, 999), 5, '0', STR_PAD_LEFT),
    'city' => '台北市',
    'district' => '大安區',
    'village' => '',
    'neighbor' => '',
    'road' => '信義路',
    'section' => '四段',
    'lane' => '',
    'alley' => '',
    'no' => rand(1, 999),
    'floor' => '',
    
    // 通訊地址
    'same_address' => 'yes',
    'contact_address' => '',
    
    // 監護人資訊
    'guardian' => '測試監護人',
    'guardian_phone' => '02-' . rand(10000000, 99999999),
    'guardian_mobile' => '09' . rand(10000000, 99999999),
    
    // 自傳和專長
    'self_intro' => '這是一個測試用的自傳內容。我對學習充滿熱忱，希望能夠進入理想的科系就讀。',
    'skills' => '我擅長程式設計、音樂和運動。曾參加過多次競賽並獲得佳績。',
    
];

// 設置志願序（選擇前3個科系作為測試）
if (!empty($all_courses)) {
    $selected_courses = array_slice($all_courses, 0, min(3, count($all_courses)));
    foreach ($selected_courses as $index => $course_name) {
        $field_name = $courseNameToFieldMap[$course_name];
        $test_data[$field_name] = $index + 1; // 志願順序從1開始
    }
}

// 設置文件勾選（使用數組格式）
$test_data['docs[]'] = ['exam']; // 只勾選必填的會考成績單

// 顯示測試數據預覽
if (isset($_GET['preview'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>測試數據預覽</title></head><body>';
    echo '<h1>測試數據預覽</h1>';
    echo '<pre>' . print_r($test_data, true) . '</pre>';
    echo '<h2>志願序字段：</h2>';
    echo '<pre>' . print_r(array_filter($test_data, function($key) {
        return strpos($key, 'choice_') === 0;
    }, ARRAY_FILTER_USE_KEY), true) . '</pre>';
    echo '<hr>';
    echo '<h2>執行測試：</h2>';
    echo '<form method="POST" action="?">';
    echo '<input type="hidden" name="execute" value="1">';
    echo '<button type="submit">執行測試提交</button>';
    echo '</form>';
    echo '</body></html>';
    exit;
}

// 執行測試提交
if (isset($_POST['execute']) || php_sapi_name() === 'cli') {
    // 保存原始的 $_POST
    $original_post = $_POST;
    
    // 設置測試數據到 $_POST
    $_POST = $test_data;
    
    // 處理 docs 數組（PHP 的 POST 數組格式）
    if (isset($test_data['docs[]'])) {
        $_POST['docs'] = $test_data['docs[]'];
        unset($_POST['docs[]']);
    }
    
    // 設置 REQUEST_METHOD
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    // 捕獲輸出
    ob_start();
    
    // 包含提交處理文件
    try {
        include 'submit_continued_admission.php';
    } catch (Exception $e) {
        $error_output = json_encode([
            'success' => false,
            'message' => '測試執行錯誤: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        echo $error_output;
    } catch (Error $e) {
        $error_output = json_encode([
            'success' => false,
            'message' => '測試執行錯誤: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        echo $error_output;
    }
    
    $output = ob_get_clean();
    
    // 恢復原始 $_POST（雖然已經 exit 了，但為了安全）
    $_POST = $original_post;
    
    // 輸出結果
    if (php_sapi_name() === 'cli') {
        echo "測試執行結果:\n";
        echo $output . "\n";
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo $output;
    }
    exit;
}

// 預設顯示預覽頁面
header('Location: ?preview=1');
exit;
?>

