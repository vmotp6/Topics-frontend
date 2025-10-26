<?php
/**
 * Ollama 資料庫配置
 */

// Ollama 專用資料庫配置
function getOllamaDatabaseConnection() {
    $host = 'localhost';
    $dbname = 'ollama';  // 使用新的 ollama 資料庫
    $username = 'root';
    $password = '';
    
    try {
        $conn = new mysqli($host, $username, $password, $dbname);
        
        // 檢查連接
        if ($conn->connect_error) {
            throw new Exception("連接失敗: " . $conn->connect_error);
        }
        
        // 設置字符集
        $conn->set_charset("utf8mb4");
        
        return $conn;
    } catch (Exception $e) {
        error_log("Ollama 資料庫連接錯誤: " . $e->getMessage());
        throw $e;
    }
}

// 檢查 Ollama 資料庫是否存在，如果不存在則創建
function ensureOllamaDatabase() {
    $host = 'localhost';
    $username = 'root';
    $password = '';
    
    try {
        // 連接到 MySQL 伺服器（不指定資料庫）
        $conn = new mysqli($host, $username, $password);
        
        if ($conn->connect_error) {
            throw new Exception("連接失敗: " . $conn->connect_error);
        }
        
        // 創建 ollama 資料庫（如果不存在）
        $sql = "CREATE DATABASE IF NOT EXISTS ollama CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        if ($conn->query($sql) === TRUE) {
            echo "✅ Ollama 資料庫已準備就緒\n";
        } else {
            throw new Exception("創建資料庫失敗: " . $conn->error);
        }
        
        $conn->close();
        return true;
    } catch (Exception $e) {
        error_log("確保 Ollama 資料庫錯誤: " . $e->getMessage());
        return false;
    }
}

// 獲取 FAQ 資料（從 topics_good 資料庫）
function getFAQFromTopicsGood() {
    $host = 'localhost';
    $dbname = 'topics_good';
    $username = 'root';
    $password = '';
    
    try {
        $conn = new mysqli($host, $username, $password, $dbname);
        
        if ($conn->connect_error) {
            throw new Exception("連接失敗: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        
        // 嘗試不同的 FAQ 表名
        $possible_tables = ['faq', 'qa', 'questions', 'faqs'];
        $faqs = [];
        
        foreach ($possible_tables as $table) {
            $sql = "SELECT question, answer FROM $table ORDER BY id ASC LIMIT 10";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $faqs[] = [
                        'question' => $row['question'],
                        'answer' => $row['answer']
                    ];
                }
                break; // 找到資料就停止
            }
        }
        
        $conn->close();
        return $faqs;
        
    } catch (Exception $e) {
        error_log("獲取 FAQ 資料錯誤: " . $e->getMessage());
        return [];
    }
}
?>
