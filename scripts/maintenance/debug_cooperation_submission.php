<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>康寧大學產學合作申請表提交調試工具</h1>\n";

// 1. 檢查POST資料
echo "<h2>1. POST資料檢查</h2>\n";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "✅ 收到POST請求<br>\n";
    echo "<h3>POST資料：</h3>\n";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h3>FILES資料：</h3>\n";
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";
} else {
    echo "❌ 未收到POST請求，當前方法：" . $_SERVER['REQUEST_METHOD'] . "<br>\n";
}

// 2. 檢查資料庫連線
echo "<h2>2. 資料庫連線測試</h2>\n";
$host = '100.79.58.120';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ 資料庫連線成功<br>\n";
    
    // 檢查資料表
    $stmt = $pdo->query("SHOW TABLES LIKE 'cooperation_applications'");
    if ($stmt->rowCount() > 0) {
        echo "✅ 資料表存在<br>\n";
        
        // 檢查資料表結構
        $stmt = $pdo->query("DESCRIBE cooperation_applications");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>資料表欄位：</h3>\n";
        echo "<table border='1'>\n";
        echo "<tr><th>欄位名</th><th>類型</th><th>NULL</th><th>預設值</th></tr>\n";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
    } else {
        echo "❌ 資料表不存在<br>\n";
    }
    
} catch(PDOException $e) {
    echo "❌ 資料庫連線失敗: " . $e->getMessage() . "<br>\n";
}

// 3. 檢查檔案上傳設定
echo "<h2>3. 檔案上傳設定檢查</h2>\n";
echo "📋 upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>\n";
echo "📋 post_max_size: " . ini_get('post_max_size') . "<br>\n";
echo "📋 max_file_uploads: " . ini_get('max_file_uploads') . "<br>\n";
echo "📋 file_uploads: " . (ini_get('file_uploads') ? '啟用' : '停用') . "<br>\n";

// 4. 檢查上傳目錄
echo "<h2>4. 上傳目錄檢查</h2>\n";
$upload_dir = '../uploads/cooperation/';
echo "📁 上傳目錄路徑: $upload_dir<br>\n";
echo "📁 絕對路徑: " . realpath($upload_dir) . "<br>\n";
echo "📁 目錄存在: " . (file_exists($upload_dir) ? '是' : '否') . "<br>\n";
echo "📁 可讀取: " . (is_readable($upload_dir) ? '是' : '否') . "<br>\n";
echo "📁 可寫入: " . (is_writable($upload_dir) ? '是' : '否') . "<br>\n";

// 5. 模擬檔案上傳測試
echo "<h2>5. 檔案上傳測試</h2>\n";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['contract_file'])) {
    $contract_file = $_FILES['contract_file'];
    echo "📄 合約書檔案資訊：<br>\n";
    echo "- 檔案名: " . $contract_file['name'] . "<br>\n";
    echo "- 檔案大小: " . $contract_file['size'] . " bytes<br>\n";
    echo "- 檔案類型: " . $contract_file['type'] . "<br>\n";
    echo "- 上傳錯誤: " . $contract_file['error'] . "<br>\n";
    echo "- 臨時檔案: " . $contract_file['tmp_name'] . "<br>\n";
    
    if ($contract_file['error'] === UPLOAD_ERR_OK) {
        echo "✅ 合約書上傳成功<br>\n";
        
        // 檢查檔案類型
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $contract_file['tmp_name']);
        finfo_close($finfo);
        echo "📄 實際檔案類型: $mime_type<br>\n";
        
        // 測試移動檔案
        $test_filename = 'test_' . uniqid() . '.pdf';
        $test_path = $upload_dir . $test_filename;
        
        if (move_uploaded_file($contract_file['tmp_name'], $test_path)) {
            echo "✅ 檔案移動成功: $test_path<br>\n";
            // 清理測試檔案
            unlink($test_path);
            echo "✅ 測試檔案已清理<br>\n";
        } else {
            echo "❌ 檔案移動失敗<br>\n";
        }
    } else {
        echo "❌ 合約書上傳失敗，錯誤代碼: " . $contract_file['error'] . "<br>\n";
        switch ($contract_file['error']) {
            case UPLOAD_ERR_INI_SIZE:
                echo "錯誤原因: 檔案超過 upload_max_filesize 限制<br>\n";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                echo "錯誤原因: 檔案超過 MAX_FILE_SIZE 限制<br>\n";
                break;
            case UPLOAD_ERR_PARTIAL:
                echo "錯誤原因: 檔案只有部分上傳<br>\n";
                break;
            case UPLOAD_ERR_NO_FILE:
                echo "錯誤原因: 沒有檔案上傳<br>\n";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                echo "錯誤原因: 找不到臨時目錄<br>\n";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                echo "錯誤原因: 無法寫入磁碟<br>\n";
                break;
            case UPLOAD_ERR_EXTENSION:
                echo "錯誤原因: 檔案上傳被擴展停止<br>\n";
                break;
        }
    }
}

// 6. 測試資料庫插入
echo "<h2>6. 資料庫插入測試</h2>\n";
try {
    if (isset($pdo)) {
        // 測試插入一條記錄
        $test_sql = "INSERT INTO cooperation_applications (
            teacher_username, application_date, department, principal_investigator,
            regulations_read, application_categories, project_amount, company_name,
            company_contact, company_phone, project_title, expected_outcomes,
            project_timeline, has_intellectual_property, contract_file_path, proposal_file_path
        ) VALUES (
            'debug_test', '2024-01-01', '測試系所', '測試主持人',
            'yes', '技術合作', 100000.00, '測試公司',
            '測試聯絡人', '0912345678', '測試專案', '測試成果',
            '6個月', 'no', '/test/contract.pdf', '/test/proposal.pdf'
        )";
        
        $pdo->exec($test_sql);
        echo "✅ 測試資料插入成功<br>\n";
        
        // 檢查插入的資料
        $stmt = $pdo->query("SELECT * FROM cooperation_applications WHERE teacher_username = 'debug_test' ORDER BY id DESC LIMIT 1");
        $test_record = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($test_record) {
            echo "✅ 測試資料查詢成功<br>\n";
            echo "<h3>插入的測試資料：</h3>\n";
            echo "<pre>";
            print_r($test_record);
            echo "</pre>";
            
            // 清理測試資料
            $pdo->exec("DELETE FROM cooperation_applications WHERE teacher_username = 'debug_test'");
            echo "✅ 測試資料已清理<br>\n";
        }
    }
} catch(PDOException $e) {
    echo "❌ 資料庫插入測試失敗: " . $e->getMessage() . "<br>\n";
}

// 7. 檢查PHP錯誤日誌
echo "<h2>7. PHP錯誤日誌檢查</h2>\n";
$error_log = ini_get('error_log');
echo "📋 錯誤日誌路徑: " . ($error_log ? $error_log : '未設定') . "<br>\n";

// 8. 提供測試表單
echo "<h2>8. 測試表單</h2>\n";
echo "<form method='POST' enctype='multipart/form-data'>\n";
echo "<p>老師帳號: <input type='text' name='teacher_username' value='test_teacher' required></p>\n";
echo "<p>申請日期: <input type='date' name='application_date' value='2024-01-01' required></p>\n";
echo "<p>科系: <input type='text' name='department' value='資訊工程系' required></p>\n";
echo "<p>主持人: <input type='text' name='principal_investigator' value='測試主持人' required></p>\n";
echo "<p>已讀規定: <select name='regulations_read' required><option value='yes'>是</option><option value='no'>否</option></select></p>\n";
echo "<p>申請類別: <input type='checkbox' name='application_categories[]' value='技術合作' checked> 技術合作</p>\n";
echo "<p>計畫金額: <input type='number' name='project_amount' value='100000' required></p>\n";
echo "<p>公司名稱: <input type='text' name='company_name' value='測試公司' required></p>\n";
echo "<p>聯絡人: <input type='text' name='company_contact' value='測試聯絡人' required></p>\n";
echo "<p>聯絡電話: <input type='text' name='company_phone' value='0912345678' required></p>\n";
echo "<p>專案名稱: <input type='text' name='project_title' value='測試專案' required></p>\n";
echo "<p>預期成果: <textarea name='expected_outcomes' required>測試成果</textarea></p>\n";
echo "<p>專案時程: <textarea name='project_timeline' required>6個月</textarea></p>\n";
echo "<p>智慧財產權: <select name='has_intellectual_property' required><option value='no'>否</option><option value='yes'>是</option></select></p>\n";
echo "<p>合約書: <input type='file' name='contract_file' accept='.pdf' required></p>\n";
echo "<p>計畫書: <input type='file' name='proposal_file' accept='.pdf' required></p>\n";
echo "<input type='submit' value='測試提交'>\n";
echo "</form>\n";

echo "<h2>9. 建議解決方案</h2>\n";
echo "<ol>\n";
echo "<li>如果資料庫連線失敗，檢查資料庫伺服器狀態和連線設定</li>\n";
echo "<li>如果檔案上傳失敗，檢查上傳目錄權限和PHP設定</li>\n";
echo "<li>如果資料表結構不匹配，執行 fix_cooperation_system.php</li>\n";
echo "<li>檢查PHP錯誤日誌中的詳細錯誤訊息</li>\n";
echo "</ol>\n";
?>
