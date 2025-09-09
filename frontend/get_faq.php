<?php
header('Content-Type: application/json; charset=utf-8');

// 引入資料庫配置
require_once 'config.php';

try {
    $conn = getDatabaseConnection();
    
    // 查詢 FAQ 資料
    // 請根據您實際的資料表名稱和欄位名稱修改這個 SQL 語句
    // 常見的可能名稱：faq, qa, questions, faqs
    // 常見的欄位名稱：question/answer, title/content, q/a
    
    $possible_queries = [
        "SELECT question, answer FROM faq ORDER BY id ASC",
        "SELECT question, answer FROM qa ORDER BY id ASC", 
        "SELECT title as question, content as answer FROM faq ORDER BY id ASC",
        "SELECT q as question, a as answer FROM qa ORDER BY id ASC",
        "SELECT question, answer FROM questions ORDER BY id ASC",
        "SELECT question, answer FROM faqs ORDER BY id ASC"
    ];
    
    $faqs = [];
    $query_success = false;
    $last_error = '';
    
    // 嘗試不同的查詢語句
    foreach ($possible_queries as $sql) {
        try {
            $result = $conn->query($sql);
            if ($result && $result->num_rows >= 0) {
                while ($row = $result->fetch_assoc()) {
                    $faqs[] = [
                        'question' => $row['question'],
                        'answer' => $row['answer']
                    ];
                }
                $query_success = true;
                break;
            }
        } catch (Exception $e) {
            $last_error = $e->getMessage();
            continue;
        }
    }
    
    if (!$query_success) {
        // 如果所有查詢都失敗，顯示資料庫中的所有表格
        $tables_result = $conn->query("SHOW TABLES");
        $tables = [];
        if ($tables_result) {
            while ($row = $tables_result->fetch_array()) {
                $tables[] = $row[0];
            }
        }
        
        echo json_encode([
            'error' => '找不到 FAQ 資料表。最後錯誤: ' . $last_error,
            'available_tables' => $tables,
            'suggestion' => '請檢查資料表名稱是否為: faq, qa, questions, faqs 其中之一，或告訴開發者正確的資料表名稱和欄位名稱。'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $conn->close();
    
    // 返回 JSON 格式的資料
    echo json_encode($faqs, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => '資料庫連接失敗: ' . $e->getMessage(),
        'details' => [
            'host' => DB_HOST,
            'database' => DB_NAME,
            'suggestion' => '請檢查資料庫伺服器是否正常運行，網路連接是否正常'
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>
