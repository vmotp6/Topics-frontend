<?php
/**
 * 驗證資料庫 3NF 正規化狀態
 * 檢查所有正規化表和視圖是否正確創建
 */

// 資料庫連接
$host = 'localhost';
$dbname = 'topics_good';
$username = 'root';
$password = '';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>3NF 正規化驗證</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #28a745; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; border-bottom: 2px solid #e0e0e0; padding-bottom: 8px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; font-size: 14px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #28a745; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .status-ok { background-color: #d4edda; }
        .status-error { background-color: #f8d7da; }
        .status-warning { background-color: #fff3cd; }
        .section { background: #f8f9fa; padding: 20px; margin: 20px 0; border-left: 4px solid #28a745; border-radius: 5px; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: #333; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
    </style>
</head>
<body>
<div class='container'>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>📊 資料庫 3NF 正規化驗證報告</h1>";
    echo "<div class='info'>本報告檢查資料庫是否符合第三正規化（3NF）標準</div>";
    
    $totalChecks = 0;
    $passedChecks = 0;
    $failedChecks = 0;
    $warnings = 0;
    
    // =====================================================
    // 1. 檢查基礎參考表
    // =====================================================
    echo "<div class='section'>";
    echo "<h2>1. 基礎參考表檢查</h2>";
    
    $referenceTables = [
        'departments' => '科系表',
        'education_systems' => '學制表',
        'application_statuses' => '申請狀態表',
        'identities' => '身分別表',
        'genders' => '性別表',
        'grades' => '年級表',
        'companies' => '公司表',
        'message_types' => '訊息類型表',
        'role_types' => '角色類型表',
        'notification_types' => '通知類型表'
    ];
    
    echo "<table>";
    echo "<tr><th>表名</th><th>中文名稱</th><th>狀態</th><th>記錄數</th></tr>";
    
    foreach ($referenceTables as $table => $name) {
        $totalChecks++;
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<tr class='status-ok'>";
            echo "<td><strong>$table</strong></td>";
            echo "<td>$name</td>";
            echo "<td><span class='badge badge-success'>✅ 存在</span></td>";
            echo "<td>$count 筆</td>";
            echo "</tr>";
            $passedChecks++;
        } catch (PDOException $e) {
            echo "<tr class='status-error'>";
            echo "<td><strong>$table</strong></td>";
            echo "<td>$name</td>";
            echo "<td><span class='badge badge-danger'>❌ 不存在</span></td>";
            echo "<td>-</td>";
            echo "</tr>";
            $failedChecks++;
        }
    }
    
    echo "</table>";
    echo "</div>";
    
    // =====================================================
    // 2. 檢查正規化的主要表
    // =====================================================
    echo "<div class='section'>";
    echo "<h2>2. 正規化表檢查</h2>";
    
    $normalizedTables = [
        'student_normalized' => ['原始表' => 'student', '說明' => '學生表（使用 user_id 作為主鍵）'],
        'teacher_normalized' => ['原始表' => 'teacher', '說明' => '老師表（使用 user_id 作為主鍵）'],
        'chat_groups_normalized' => ['原始表' => 'chat_groups', '說明' => '聊天群組表'],
        'group_members_normalized' => ['原始表' => 'group_members', '說明' => '群組成員表'],
        'private_chat_history_normalized' => ['原始表' => 'private_chat_history', '說明' => '私聊訊息表'],
        'group_messages_normalized' => ['原始表' => 'group_messages', '說明' => '群組訊息表'],
        'enrollment_applications_normalized' => ['原始表' => 'enrollment_applications', '說明' => '就讀意願申請表'],
        'enrollment_preferences' => ['原始表' => null, '說明' => '就讀意願明細表（新增）'],
        'cooperation_applications_normalized' => ['原始表' => 'cooperation_applications', '說明' => '產學合作申請表'],
        'ai_chat_history_normalized' => ['原始表' => 'ai_chat_history', '說明' => 'AI 聊天記錄表'],
        'user_activity_normalized' => ['原始表' => 'user_activity', '說明' => '用戶活動表'],
        'unread_notifications_normalized' => ['原始表' => 'unread_notifications', '說明' => '未讀通知表'],
        'notification_sent_log_normalized' => ['原始表' => 'notification_sent_log', '說明' => '通知發送記錄表'],
        'message_likes_normalized' => ['原始表' => 'message_likes', '說明' => '訊息點讚表'],
        'senior_messages_normalized' => ['原始表' => 'senior_messages', '說明' => '學長姐留言表']
    ];
    
    echo "<table>";
    echo "<tr><th>正規化表</th><th>原始表</th><th>說明</th><th>狀態</th><th>記錄數</th><th>原始表記錄數</th></tr>";
    
    foreach ($normalizedTables as $normalizedTable => $info) {
        $totalChecks++;
        $originalTable = $info['原始表'];
        $description = $info['說明'];
        
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $normalizedTable");
            $normalizedCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            $originalCount = '-';
            if ($originalTable) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM $originalTable");
                    $originalCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                } catch (PDOException $e) {
                    $originalCount = '表不存在';
                }
            }
            
            echo "<tr class='status-ok'>";
            echo "<td><strong>$normalizedTable</strong></td>";
            echo "<td>" . ($originalTable ?: '-') . "</td>";
            echo "<td>$description</td>";
            echo "<td><span class='badge badge-success'>✅ 存在</span></td>";
            echo "<td>$normalizedCount 筆</td>";
            echo "<td>$originalCount</td>";
            echo "</tr>";
            $passedChecks++;
        } catch (PDOException $e) {
            echo "<tr class='status-error'>";
            echo "<td><strong>$normalizedTable</strong></td>";
            echo "<td>" . ($originalTable ?: '-') . "</td>";
            echo "<td>$description</td>";
            echo "<td><span class='badge badge-danger'>❌ 不存在</span></td>";
            echo "<td>-</td>";
            echo "<td>-</td>";
            echo "</tr>";
            $failedChecks++;
        }
    }
    
    echo "</table>";
    echo "</div>";
    
    // =====================================================
    // 3. 檢查視圖
    // =====================================================
    echo "<div class='section'>";
    echo "<h2>3. 向後兼容視圖檢查</h2>";
    
    $views = [
        'student_view',
        'teacher_view',
        'private_chat_history_view',
        'group_messages_view',
        'chat_groups_view',
        'group_members_view',
        'user_activity_view',
        'unread_notifications_view',
        'notification_sent_log_view',
        'message_likes_view',
        'senior_messages_view'
    ];
    
    echo "<table>";
    echo "<tr><th>視圖名稱</th><th>狀態</th><th>記錄數</th></tr>";
    
    foreach ($views as $view) {
        $totalChecks++;
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $view");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<tr class='status-ok'>";
            echo "<td><strong>$view</strong></td>";
            echo "<td><span class='badge badge-success'>✅ 存在</span></td>";
            echo "<td>$count 筆</td>";
            echo "</tr>";
            $passedChecks++;
        } catch (PDOException $e) {
            echo "<tr class='status-error'>";
            echo "<td><strong>$view</strong></td>";
            echo "<td><span class='badge badge-danger'>❌ 不存在</span></td>";
            echo "<td>-</td>";
            echo "</tr>";
            $failedChecks++;
        }
    }
    
    echo "</table>";
    echo "</div>";
    
    // =====================================================
    // 4. 檢查外鍵關係
    // =====================================================
    echo "<div class='section'>";
    echo "<h2>4. 外鍵關係檢查</h2>";
    
    $foreignKeyChecks = [
        'student_normalized' => [
            ['column' => 'user_id', 'references' => 'user(id)'],
            ['column' => 'department_id', 'references' => 'departments(id)'],
            ['column' => 'grade_id', 'references' => 'grades(id)']
        ],
        'teacher_normalized' => [
            ['column' => 'user_id', 'references' => 'user(id)'],
            ['column' => 'department_id', 'references' => 'departments(id)']
        ],
        'enrollment_applications_normalized' => [
            ['column' => 'status_id', 'references' => 'application_statuses(id)'],
            ['column' => 'identity_id', 'references' => 'identities(id)'],
            ['column' => 'gender_id', 'references' => 'genders(id)'],
            ['column' => 'current_grade_id', 'references' => 'grades(id)'],
            ['column' => 'recommended_teacher_user_id', 'references' => 'teacher_normalized(user_id)']
        ],
        'cooperation_applications_normalized' => [
            ['column' => 'teacher_user_id', 'references' => 'teacher_normalized(user_id)'],
            ['column' => 'department_id', 'references' => 'departments(id)'],
            ['column' => 'company_id', 'references' => 'companies(id)'],
            ['column' => 'status_id', 'references' => 'application_statuses(id)']
        ]
    ];
    
    echo "<table>";
    echo "<tr><th>表名</th><th>欄位</th><th>引用</th><th>狀態</th></tr>";
    
    foreach ($foreignKeyChecks as $table => $fks) {
        try {
            // 檢查表是否存在
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table LIMIT 1");
            foreach ($fks as $fk) {
                $totalChecks++;
                // 查詢外鍵約束
                $sql = "SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = '$table' 
                        AND COLUMN_NAME = '{$fk['column']}' 
                        AND REFERENCED_TABLE_NAME IS NOT NULL";
                
                try {
                    $stmt = $pdo->query($sql);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result) {
                        $ref = $result['REFERENCED_TABLE_NAME'] . '(' . $result['REFERENCED_COLUMN_NAME'] . ')';
                        echo "<tr class='status-ok'>";
                        echo "<td>$table</td>";
                        echo "<td>{$fk['column']}</td>";
                        echo "<td>$ref</td>";
                        echo "<td><span class='badge badge-success'>✅ 已設置</span></td>";
                        echo "</tr>";
                        $passedChecks++;
                    } else {
                        echo "<tr class='status-warning'>";
                        echo "<td>$table</td>";
                        echo "<td>{$fk['column']}</td>";
                        echo "<td>{$fk['references']}</td>";
                        echo "<td><span class='badge badge-warning'>⚠️ 未設置</span></td>";
                        echo "</tr>";
                        $warnings++;
                    }
                } catch (PDOException $e) {
                    echo "<tr class='status-warning'>";
                    echo "<td>$table</td>";
                    echo "<td>{$fk['column']}</td>";
                    echo "<td>{$fk['references']}</td>";
                    echo "<td><span class='badge badge-warning'>⚠️ 檢查失敗</span></td>";
                    echo "</tr>";
                    $warnings++;
                }
            }
        } catch (PDOException $e) {
            // 表不存在
            foreach ($fks as $fk) {
                $totalChecks++;
                echo "<tr class='status-error'>";
                echo "<td>$table</td>";
                echo "<td>{$fk['column']}</td>";
                echo "<td>{$fk['references']}</td>";
                echo "<td><span class='badge badge-danger'>❌ 表不存在</span></td>";
                echo "</tr>";
                $failedChecks++;
            }
        }
    }
    
    echo "</table>";
    echo "</div>";
    
    // =====================================================
    // 5. 檢查是否還有使用 username 的表（違反 3NF）
    // =====================================================
    echo "<div class='section'>";
    echo "<h2>5. 違反 3NF 檢查（使用 username/email 而非 user_id）</h2>";
    
    $sql = "SELECT TABLE_NAME, COLUMN_NAME 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND COLUMN_NAME IN ('username', 'sender_username', 'from_user', 'to_user', 'created_by', 'user_email')
            AND TABLE_NAME NOT LIKE '%_normalized'
            AND TABLE_NAME NOT IN ('user', 'view') 
            AND TABLE_NAME NOT LIKE '%_view'
            ORDER BY TABLE_NAME, COLUMN_NAME";
    
    try {
        $stmt = $pdo->query($sql);
        $violations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($violations)) {
            echo "<p class='success'>✅ 沒有發現違反 3NF 的情況！所有表都使用 user_id 作為外鍵。</p>";
            $passedChecks++;
        } else {
            echo "<table>";
            echo "<tr><th>表名</th><th>欄位</th><th>問題</th><th>建議</th></tr>";
            foreach ($violations as $violation) {
                $totalChecks++;
                $table = $violation['TABLE_NAME'];
                $column = $violation['COLUMN_NAME'];
                echo "<tr class='status-warning'>";
                echo "<td><strong>$table</strong></td>";
                echo "<td>$column</td>";
                echo "<td>使用字串而非 user_id</td>";
                echo "<td>應改為使用 user_id 並創建 *_normalized 表</td>";
                echo "</tr>";
                $warnings++;
            }
            echo "</table>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>檢查失敗: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "</div>";
    
    // =====================================================
    // 6. 檢查是否還有重複的字串值（應該正規化為參考表）
    // =====================================================
    echo "<div class='section'>";
    echo "<h2>6. 重複數據檢查（應該正規化為參考表）</h2>";
    
    $duplicateChecks = [
        ['table' => 'student', 'column' => 'department', 'reference' => 'departments'],
        ['table' => 'student', 'column' => 'grade', 'reference' => 'grades'],
        ['table' => 'teacher', 'column' => 'department', 'reference' => 'departments'],
    ];
    
    echo "<table>";
    echo "<tr><th>表名</th><th>欄位</th><th>狀態</th><th>唯一值數量</th><th>建議</th></tr>";
    
    foreach ($duplicateChecks as $check) {
        $totalChecks++;
        try {
            $stmt = $pdo->query("SELECT COUNT(DISTINCT {$check['column']}) as unique_count FROM {$check['table']} WHERE {$check['column']} IS NOT NULL");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $uniqueCount = $result['unique_count'];
            
            // 檢查是否已經有對應的正規化表
            $normalizedTable = $check['table'] . '_normalized';
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM $normalizedTable LIMIT 1");
                echo "<tr class='status-ok'>";
                echo "<td><strong>{$check['table']}</strong></td>";
                echo "<td>{$check['column']}</td>";
                echo "<td><span class='badge badge-success'>✅ 已正規化</span></td>";
                echo "<td>$uniqueCount</td>";
                echo "<td>已有 $normalizedTable 表</td>";
                echo "</tr>";
                $passedChecks++;
            } catch (PDOException $e) {
                echo "<tr class='status-warning'>";
                echo "<td><strong>{$check['table']}</strong></td>";
                echo "<td>{$check['column']}</td>";
                echo "<td><span class='badge badge-warning'>⚠️ 未正規化</span></td>";
                echo "<td>$uniqueCount</td>";
                echo "<td>應創建 $normalizedTable 並引用 {$check['reference']} 表</td>";
                echo "</tr>";
                $warnings++;
            }
        } catch (PDOException $e) {
            // 表或欄位不存在，可能已經正規化
            echo "<tr class='status-ok'>";
            echo "<td><strong>{$check['table']}</strong></td>";
            echo "<td>{$check['column']}</td>";
            echo "<td><span class='badge badge-success'>✅ 可能已正規化</span></td>";
            echo "<td>-</td>";
            echo "<td>表或欄位不存在</td>";
            echo "</tr>";
            $passedChecks++;
        }
    }
    
    echo "</table>";
    echo "</div>";
    
    // =====================================================
    // 7. 數據一致性檢查
    // =====================================================
    echo "<div class='section'>";
    echo "<h2>7. 數據一致性檢查</h2>";
    
    echo "<table>";
    echo "<tr><th>檢查項目</th><th>狀態</th><th>詳情</th></tr>";
    
    // 檢查 student_normalized 和 student 的數據一致性
    $totalChecks++;
    try {
        $stmt = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM student) as original_count,
                (SELECT COUNT(*) FROM student_normalized) as normalized_count
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $original = $result['original_count'];
        $normalized = $result['normalized_count'];
        
        if ($original == $normalized || $original == 0) {
            echo "<tr class='status-ok'>";
            echo "<td>student 數據一致性</td>";
            echo "<td><span class='badge badge-success'>✅ 一致</span></td>";
            echo "<td>原始表: $original 筆, 正規化表: $normalized 筆</td>";
            echo "</tr>";
            $passedChecks++;
        } else {
            echo "<tr class='status-warning'>";
            echo "<td>student 數據一致性</td>";
            echo "<td><span class='badge badge-warning'>⚠️ 不一致</span></td>";
            echo "<td>原始表: $original 筆, 正規化表: $normalized 筆（可能需要遷移數據）</td>";
            echo "</tr>";
            $warnings++;
        }
    } catch (PDOException $e) {
        echo "<tr class='status-error'>";
        echo "<td>student 數據一致性</td>";
        echo "<td><span class='badge badge-danger'>❌ 檢查失敗</span></td>";
        echo "<td>" . htmlspecialchars(substr($e->getMessage(), 0, 100)) . "</td>";
        echo "</tr>";
        $failedChecks++;
    }
    
    // 檢查 teacher_normalized 和 teacher 的數據一致性
    $totalChecks++;
    try {
        $stmt = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM teacher) as original_count,
                (SELECT COUNT(*) FROM teacher_normalized) as normalized_count
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $original = $result['original_count'];
        $normalized = $result['normalized_count'];
        
        if ($original == $normalized || $original == 0) {
            echo "<tr class='status-ok'>";
            echo "<td>teacher 數據一致性</td>";
            echo "<td><span class='badge badge-success'>✅ 一致</span></td>";
            echo "<td>原始表: $original 筆, 正規化表: $normalized 筆</td>";
            echo "</tr>";
            $passedChecks++;
        } else {
            echo "<tr class='status-warning'>";
            echo "<td>teacher 數據一致性</td>";
            echo "<td><span class='badge badge-warning'>⚠️ 不一致</span></td>";
            echo "<td>原始表: $original 筆, 正規化表: $normalized 筆（可能需要遷移數據）</td>";
            echo "</tr>";
            $warnings++;
        }
    } catch (PDOException $e) {
        echo "<tr class='status-error'>";
        echo "<td>teacher 數據一致性</td>";
        echo "<td><span class='badge badge-danger'>❌ 檢查失敗</span></td>";
        echo "<td>" . htmlspecialchars(substr($e->getMessage(), 0, 100)) . "</td>";
        echo "</tr>";
        $failedChecks++;
    }
    
    echo "</table>";
    echo "</div>";
    
    // =====================================================
    // 總結
    // =====================================================
    echo "<div class='section'>";
    echo "<h2>📊 驗證總結</h2>";
    
    $passRate = $totalChecks > 0 ? round(($passedChecks / $totalChecks) * 100, 1) : 0;
    
    echo "<table>";
    echo "<tr><th>檢查項目</th><th>數量</th></tr>";
    echo "<tr><td>總檢查項</td><td><strong>$totalChecks</strong></td></tr>";
    echo "<tr class='status-ok'><td>✅ 通過</td><td><strong class='success'>$passedChecks</strong></td></tr>";
    echo "<tr class='status-warning'><td>⚠️ 警告</td><td><strong class='warning'>$warnings</strong></td></tr>";
    echo "<tr class='status-error'><td>❌ 失敗</td><td><strong class='error'>$failedChecks</strong></td></tr>";
    echo "<tr><td>通過率</td><td><strong>" . ($passRate >= 80 ? "<span class='success'>$passRate%</span>" : "<span class='warning'>$passRate%</span>") . "</strong></td></tr>";
    echo "</table>";
    
    if ($passRate >= 80) {
        echo "<p class='success'><strong>✅ 資料庫已基本符合 3NF 正規化標準！</strong></p>";
    } elseif ($passRate >= 60) {
        echo "<p class='warning'><strong>⚠️ 資料庫部分符合 3NF，但還有改進空間。</strong></p>";
    } else {
        echo "<p class='error'><strong>❌ 資料庫尚未達到 3NF 標準，需要進一步正規化。</strong></p>";
    }
    
    echo "<h3>正規化程度：</h3>";
    echo "<ul>";
    echo "<li><strong>基礎參考表</strong>: 用於消除重複數據（科系、狀態、類型等）</li>";
    echo "<li><strong>正規化主表</strong>: 使用外鍵引用參考表，而非存儲字串</li>";
    echo "<li><strong>視圖</strong>: 提供向後兼容，讓舊代碼可以繼續使用</li>";
    echo "<li><strong>外鍵約束</strong>: 確保數據完整性和一致性</li>";
    echo "</ul>";
    
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='section'>";
    echo "<h2 class='error'>❌ 資料庫錯誤</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div></body></html>";
?>

