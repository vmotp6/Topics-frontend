<?php
/**
 * 驗證 3NF 正規化合規性腳本
 * 檢查資料庫是否符合第三正規化標準
 */

// 資料庫連接配置
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

// 設置錯誤顯示
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>驗證 3NF 正規化合規性</title>
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        h2 {
            color: #34495e;
            margin-top: 30px;
            padding: 10px;
            background: #ecf0f1;
            border-left: 4px solid #3498db;
        }
        .success {
            color: #27ae60;
            padding: 10px;
            background: #d5f4e6;
            border-left: 4px solid #27ae60;
            margin: 10px 0;
        }
        .warning {
            color: #f39c12;
            padding: 10px;
            background: #fef5e7;
            border-left: 4px solid #f39c12;
            margin: 10px 0;
        }
        .error {
            color: #e74c3c;
            padding: 10px;
            background: #fadbd8;
            border-left: 4px solid #e74c3c;
            margin: 10px 0;
        }
        .info {
            color: #3498db;
            padding: 10px;
            background: #ebf5fb;
            border-left: 4px solid #3498db;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background: #3498db;
            color: white;
        }
        table tr:hover {
            background: #f5f5f5;
        }
        .pass {
            color: #27ae60;
            font-weight: bold;
        }
        .fail {
            color: #e74c3c;
            font-weight: bold;
        }
        .progress-bar {
            width: 100%;
            background: #ecf0f1;
            border-radius: 10px;
            height: 30px;
            margin: 10px 0;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: #27ae60;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 驗證 3NF 正規化合規性</h1>

<?php
try {
    // 連接資料庫
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<div class='success'>✅ 資料庫連接成功！</div>";
    
    $total_checks = 0;
    $passed_checks = 0;
    $failed_checks = [];
    $warnings = [];
    
    // =====================================================
    // 檢查 1: 基礎參考表是否存在
    // =====================================================
    
    echo "<h2>1. 檢查基礎參考表</h2>";
    
    $reference_tables = [
        'departments' => '科系表',
        'education_systems' => '學制表',
        'application_statuses' => '申請狀態表',
        'identities' => '身分別表',
        'genders' => '性別表',
        'grades' => '年級表',
        'companies' => '公司表',
        'message_types' => '訊息類型表',
        'role_types' => '角色類型表'
    ];
    
    echo "<table>";
    echo "<tr><th>表名</th><th>說明</th><th>狀態</th><th>記錄數</th></tr>";
    
    foreach ($reference_tables as $table => $description) {
        $total_checks++;
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<tr>";
            echo "<td>$table</td>";
            echo "<td>$description</td>";
            echo "<td><span class='pass'>✅ 存在</span></td>";
            echo "<td>$count</td>";
            echo "</tr>";
            $passed_checks++;
        } catch (PDOException $e) {
            echo "<tr>";
            echo "<td>$table</td>";
            echo "<td>$description</td>";
            echo "<td><span class='fail'>❌ 不存在</span></td>";
            echo "<td>-</td>";
            echo "</tr>";
            $failed_checks[] = "基礎參考表 $table 不存在";
        }
    }
    echo "</table>";
    
    // =====================================================
    // 檢查 2: 正規化表是否存在
    // =====================================================
    
    echo "<h2>2. 檢查正規化表</h2>";
    
    $normalized_tables = [
        'student_normalized' => '正規化學生表',
        'teacher_normalized' => '正規化老師表',
        'enrollment_applications_normalized' => '正規化就讀意願申請表',
        'enrollment_preferences' => '就讀意願明細表',
        'cooperation_applications_normalized' => '正規化產學合作申請表',
        'cooperation_application_categories' => '產學合作申請類別明細表',
        'ip_rights' => '智慧財產權明細表',
        'chat_groups_normalized' => '正規化聊天群組表',
        'group_members_normalized' => '正規化群組成員表',
        'private_chat_history_normalized' => '正規化私聊訊息表',
        'group_messages_normalized' => '正規化群組訊息表'
    ];
    
    echo "<table>";
    echo "<tr><th>表名</th><th>說明</th><th>狀態</th><th>記錄數</th></tr>";
    
    foreach ($normalized_tables as $table => $description) {
        $total_checks++;
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<tr>";
            echo "<td>$table</td>";
            echo "<td>$description</td>";
            echo "<td><span class='pass'>✅ 存在</span></td>";
            echo "<td>$count</td>";
            echo "</tr>";
            $passed_checks++;
        } catch (PDOException $e) {
            echo "<tr>";
            echo "<td>$table</td>";
            echo "<td>$description</td>";
            echo "<td><span class='fail'>❌ 不存在</span></td>";
            echo "<td>-</td>";
            echo "</tr>";
            $failed_checks[] = "正規化表 $table 不存在";
        }
    }
    echo "</table>";
    
    // =====================================================
    // 檢查 3: 外鍵約束
    // =====================================================
    
    echo "<h2>3. 檢查外鍵約束</h2>";
    
    $expected_foreign_keys = [
        'student_normalized' => ['user_id', 'department_id', 'grade_id'],
        'teacher_normalized' => ['user_id', 'department_id'],
        'enrollment_applications_normalized' => [
            'status_id', 'identity_id', 'gender_id', 'current_grade_id', 
            'recommended_teacher_user_id'
        ],
        'enrollment_preferences' => [
            'enrollment_application_id', 'department_id', 'education_system_id'
        ],
        'cooperation_applications_normalized' => [
            'teacher_user_id', 'department_id', 'company_id', 'status_id'
        ]
    ];
    
    echo "<table>";
    echo "<tr><th>表名</th><th>外鍵欄位</th><th>狀態</th></tr>";
    
    foreach ($expected_foreign_keys as $table => $columns) {
        foreach ($columns as $column) {
            $total_checks++;
            try {
                $stmt = $pdo->query("
                    SELECT COUNT(*) as count
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = '$table'
                    AND COLUMN_NAME = '$column'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                if ($count > 0) {
                    echo "<tr>";
                    echo "<td>$table</td>";
                    echo "<td>$column</td>";
                    echo "<td><span class='pass'>✅ 已設置</span></td>";
                    echo "</tr>";
                    $passed_checks++;
                } else {
                    echo "<tr>";
                    echo "<td>$table</td>";
                    echo "<td>$column</td>";
                    echo "<td><span class='fail'>❌ 未設置</span></td>";
                    echo "</tr>";
                    $failed_checks[] = "$table.$column 外鍵未設置";
                }
            } catch (PDOException $e) {
                echo "<tr>";
                echo "<td>$table</td>";
                echo "<td>$column</td>";
                echo "<td><span class='fail'>❌ 檢查失敗</span></td>";
                echo "</tr>";
                $failed_checks[] = "$table.$column 外鍵檢查失敗";
            }
        }
    }
    echo "</table>";
    
    // =====================================================
    // 檢查 4: 數據一致性
    // =====================================================
    
    echo "<h2>4. 檢查數據一致性</h2>";
    
    // 檢查 student 和 student_normalized 數據一致性
    try {
        $stmt = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM student WHERE user_id IS NOT NULL) AS original_count,
                (SELECT COUNT(*) FROM student_normalized) AS normalized_count
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_checks++;
        if ($result['original_count'] == $result['normalized_count']) {
            echo "<div class='success'>✅ Student 數據一致性：原始表 {$result['original_count']} 筆，正規化表 {$result['normalized_count']} 筆</div>";
            $passed_checks++;
        } else {
            echo "<div class='warning'>⚠️ Student 數據不一致：原始表 {$result['original_count']} 筆，正規化表 {$result['normalized_count']} 筆</div>";
            $warnings[] = "Student 數據不一致";
        }
    } catch (PDOException $e) {
        $failed_checks[] = "Student 數據一致性檢查失敗";
    }
    
    // 檢查 teacher 和 teacher_normalized 數據一致性
    try {
        $stmt = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM teacher WHERE user_id IS NOT NULL) AS original_count,
                (SELECT COUNT(*) FROM teacher_normalized) AS normalized_count
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_checks++;
        if ($result['original_count'] == $result['normalized_count']) {
            echo "<div class='success'>✅ Teacher 數據一致性：原始表 {$result['original_count']} 筆，正規化表 {$result['normalized_count']} 筆</div>";
            $passed_checks++;
        } else {
            echo "<div class='warning'>⚠️ Teacher 數據不一致：原始表 {$result['original_count']} 筆，正規化表 {$result['normalized_count']} 筆</div>";
            $warnings[] = "Teacher 數據不一致";
        }
    } catch (PDOException $e) {
        $failed_checks[] = "Teacher 數據一致性檢查失敗";
    }
    
    // =====================================================
    // 檢查 5: 視圖是否存在
    // =====================================================
    
    echo "<h2>5. 檢查向後兼容視圖</h2>";
    
    $views = [
        'student_view',
        'teacher_view',
        'private_chat_history_view',
        'group_messages_view',
        'chat_groups_view',
        'group_members_view'
    ];
    
    echo "<table>";
    echo "<tr><th>視圖名</th><th>狀態</th></tr>";
    
    foreach ($views as $view) {
        $total_checks++;
        try {
            $stmt = $pdo->query("SELECT 1 FROM $view LIMIT 1");
            echo "<tr>";
            echo "<td>$view</td>";
            echo "<td><span class='pass'>✅ 存在</span></td>";
            echo "</tr>";
            $passed_checks++;
        } catch (PDOException $e) {
            echo "<tr>";
            echo "<td>$view</td>";
            echo "<td><span class='fail'>❌ 不存在</span></td>";
            echo "</tr>";
            $failed_checks[] = "視圖 $view 不存在";
        }
    }
    echo "</table>";
    
    // =====================================================
    // 顯示最終結果
    // =====================================================
    
    echo "<h2>📊 驗證結果</h2>";
    
    $pass_rate = $total_checks > 0 ? round(($passed_checks / $total_checks) * 100, 2) : 0;
    
    echo "<div class='progress-bar'>";
    echo "<div class='progress-fill' style='width: {$pass_rate}%'>";
    echo "通過率: {$pass_rate}% ({$passed_checks}/{$total_checks})";
    echo "</div>";
    echo "</div>";
    
    if ($pass_rate >= 90) {
        echo "<div class='success'>";
        echo "<h3>✅ 恭喜！資料庫已符合 3NF 正規化標準</h3>";
        echo "<p>通過率: <strong>{$pass_rate}%</strong></p>";
        echo "</div>";
    } elseif ($pass_rate >= 70) {
        echo "<div class='warning'>";
        echo "<h3>⚠️ 資料庫基本符合 3NF 標準，但仍有改進空間</h3>";
        echo "<p>通過率: <strong>{$pass_rate}%</strong></p>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h3>❌ 資料庫尚未完全符合 3NF 標準</h3>";
        echo "<p>通過率: <strong>{$pass_rate}%</strong></p>";
        echo "</div>";
    }
    
    if (!empty($failed_checks)) {
        echo "<h3>❌ 失敗的檢查項目</h3>";
        echo "<ul>";
        foreach ($failed_checks as $check) {
            echo "<li>$check</li>";
        }
        echo "</ul>";
    }
    
    if (!empty($warnings)) {
        echo "<h3>⚠️ 警告訊息</h3>";
        echo "<ul>";
        foreach ($warnings as $warning) {
            echo "<li>$warning</li>";
        }
        echo "</ul>";
    }
    
    echo "<p>";
    echo "<a href='execute_complete_3nf_normalization.php' class='btn'>🔄 執行 3NF 正規化</a> ";
    echo "<a href='verify_3nf_compliance.php' class='btn'>🔄 重新驗證</a>";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ 錯誤: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

    </div>
</body>
</html>

