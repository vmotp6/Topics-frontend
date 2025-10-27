<?php
/**
 * Ollama 資料庫配置
 */

// Ollama 專用資料庫配置
function getOllamaDatabaseConnection() {
    // 使用與Topics系統相同的遠程MySQL服務器配置
    $host = '100.79.58.120';  // 遠程MySQL服務器IP
    $dbname = 'ollama';        // ollama資料庫
    $username = 'root';        // MySQL用戶名
    $password = '';             // MySQL密碼
    $port = 3306;              // MySQL端口
    
    // 增加連接重試機制
    $max_retries = 3;
    $retry_delay = 1; // 秒
    
    for ($i = 0; $i < $max_retries; $i++) {
        try {
            $conn = new mysqli($host, $username, $password, $dbname, $port);
            
            // 檢查連接
            if ($conn->connect_error) {
                throw new Exception("連接失敗: " . $conn->connect_error);
            }
            
            // 設置字符集
            $conn->set_charset("utf8mb4");
            
            return $conn;
        } catch (Exception $e) {
            error_log("Ollama 資料庫連接錯誤 (嘗試 " . ($i + 1) . "/$max_retries): " . $e->getMessage());
            
            if ($i < $max_retries - 1) {
                sleep($retry_delay);
                $retry_delay *= 2; // 指數退避
            } else {
                throw $e;
            }
        }
    }
}

// 檢查 Ollama 資料庫是否存在，如果不存在則創建
function ensureOllamaDatabase() {
    $host = '100.79.58.120';  // 使用遠程服務器
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
    $host = '100.79.58.120';  // 使用遠程服務器
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
