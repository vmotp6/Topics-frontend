<?php
/**
 * 匯入教育部CSV（如 106_basej.csv）到 school_data
 * - 支援上傳檔案或指定伺服器路徑
 * - 僅匯入「國民中學」
 * - 以學校代碼為主鍵邏輯進行 INSERT/UPDATE（若無代碼則以名稱+縣市嘗試匹配）
 */

// 環境與連線設定（沿用專案現有連線設定邏輯）
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo "<p style='color:red;'>資料庫連線失敗：" . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

function renderPageStart() {
    echo "<meta charset='utf-8'>";
    echo "<title>匯入教育部CSV（106_basej.csv）</title>";
    echo "<style>body{font-family:Arial,Helvetica,sans-serif;margin:20px;} .card{background:#f8f9fa;padding:20px;border-radius:10px;margin-bottom:20px;border:1px solid #e9ecef;} .success{color:#155724;background:#d4edda;border-left:4px solid #28a745;padding:12px;border-radius:6px;} .error{color:#721c24;background:#f8d7da;border-left:4px solid #dc3545;padding:12px;border-radius:6px;} .muted{color:#6c757d;} label{display:block;margin:8px 0 4px;} input[type=file],input[type=text]{width:100%;padding:8px;border:1px solid #ced4da;border-radius:6px;} button{background:#007cba;color:#fff;border:none;padding:10px 16px;border-radius:6px;cursor:pointer;} button:disabled{opacity:.7;cursor:not-allowed;} .row{display:flex;gap:16px;flex-wrap:wrap;} .col{flex:1 1 360px;} pre{white-space:pre-wrap;background:#f1f3f5;padding:12px;border-radius:6px;overflow:auto;} .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin-top:10px;} .pill{background:#eef3ff;color:#1c3faa;padding:6px 10px;border-radius:999px;display:inline-block;margin-right:6px;}</style>";
    echo "<h1>匯入教育部CSV（106_basej.csv）</h1>";
}

function renderForm() {
    echo "<div class='card'>";
    echo "<h2>上傳或指定路徑</h2>";
    echo "<form method='post' enctype='multipart/form-data'>";
    echo "<div class='row'>";
    echo "  <div class='col'>";
    echo "    <label>上傳CSV檔（建議：106_basej.csv，UTF-8）</label>";
    echo "    <input type='file' name='csv_file' accept='.csv'>";
    echo "    <div class='muted' style='margin-top:6px;'>或</div>";
    echo "    <label>伺服器檔案路徑（例：/var/www/data/106_basej.csv 或 C:\\path\\106_basej.csv）</label>";
    echo "    <input type='text' name='server_path' placeholder='選填'>";
    echo "  </div>";
    echo "</div>";
    echo "<div style='margin-top:12px;'><button type='submit'>開始匯入</button></div>";
    echo "</form>";
    echo "</div>";
}

function detectEncodingAndConvertToUtf8(string $content): string {
    $enc = mb_detect_encoding($content, ['UTF-8','BIG-5','Big5','CP950','ISO-8859-1','ASCII'], true);
    if ($enc && strtoupper($enc) !== 'UTF-8') {
        return mb_convert_encoding($content, 'UTF-8', $enc);
    }
    return $content;
}

function parseCsv(string $filepath): array {
    if (!is_readable($filepath)) {
        throw new RuntimeException('檔案不可讀：' . $filepath);
    }

    $raw = file_get_contents($filepath);
    if ($raw === false) {
        throw new RuntimeException('無法讀取檔案內容');
    }

    $raw = detectEncodingAndConvertToUtf8($raw);

    $tmp = tmpfile();
    $meta = stream_get_meta_data($tmp);
    $tmpPath = $meta['uri'];
    fwrite($tmp, $raw);
    rewind($tmp);

    $handle = fopen($tmpPath, 'r');
    if ($handle === false) {
        throw new RuntimeException('無法開啟暫存檔');
    }

    $rows = [];
    while (($data = fgetcsv($handle)) !== false) {
        // 去除BOM殘留與空白
        foreach ($data as $i => $v) {
            $v = preg_replace('/^\xEF\xBB\xBF/', '', (string)$v);
            $data[$i] = trim($v);
        }
        if (count($data) === 1 && $data[0] === '') {
            continue;
        }
        $rows[] = $data;
    }
    fclose($handle);
    fclose($tmp);

    if (empty($rows)) {
        throw new RuntimeException('CSV 無內容');
    }

    // 尋找表頭
    $header = $rows[0];
    $map = [];
    foreach ($header as $idx => $col) {
        $map[$col] = $idx;
    }

    // 期待欄位（資料集描述顯示）
    $expected = ['縣市代碼','縣市名稱','學校代碼','學校名稱'];
    foreach ($expected as $key) {
        if (!array_key_exists($key, $map)) {
            // 某些年度欄名略有差異，嘗試容錯
            if ($key === '縣市名稱') {
                $aliases = ['縣市'];
            } else if ($key === '學校代碼') {
                $aliases = ['學校代號','代碼'];
            } else if ($key === '學校名稱') {
                $aliases = ['學校'];
            } else {
                $aliases = [];
            }
            $found = false;
            foreach ($aliases as $alt) {
                if (array_key_exists($alt, $map)) { $map[$key] = $map[$alt]; $found = true; break; }
            }
            if (!$found && !array_key_exists($key, $map)) {
                throw new RuntimeException('缺少必要欄位：' . $key);
            }
        }
    }

    $records = [];
    for ($i = 1; $i < count($rows); $i++) {
        $r = $rows[$i];
        // 跳過空列
        if (!isset($r[$map['學校名稱']]) || $r[$map['學校名稱']] === '') continue;
        $schoolName = $r[$map['學校名稱']];
        // 只匯入國民中學
        if (mb_strpos($schoolName, '國中') === false && mb_strpos($schoolName, '國民中學') === false) {
            continue;
        }
        $records[] = [
            'school_code' => isset($map['學校代碼']) && isset($r[$map['學校代碼']]) ? (string)$r[$map['學校代碼']] : '',
            'name'        => $schoolName,
            'city'        => isset($map['縣市名稱']) && isset($r[$map['縣市名稱']]) ? (string)$r[$map['縣市名稱']] : '',
            // CSV 通常未含行政區，先留空（之後可再從其他來源補）
            'district'    => '',
            'type'        => '國民中學',
        ];
    }

    return $records;
}

function upsertSchools(PDO $pdo, array $schools): array {
    $inserted = 0; $updated = 0; $skipped = 0;

    // 準備語句
    $selectByCode = $pdo->prepare("SELECT id FROM school_data WHERE school_code = ? LIMIT 1");
    $selectByNameCity = $pdo->prepare("SELECT id FROM school_data WHERE name = ? AND city = ? AND type = '國民中學' LIMIT 1");
    $insertStmt = $pdo->prepare("INSERT INTO school_data (name, city, district, type, school_code, is_active, data_source) VALUES (?,?,?,?,?,1,'教育部統計處(CSV)')");
    $updateById = $pdo->prepare("UPDATE school_data SET name=?, city=?, district=?, type=?, school_code=?, is_active=1, data_source='教育部統計處(CSV)' WHERE id=?");

    $pdo->beginTransaction();
    try {
        foreach ($schools as $s) {
            $id = null;
            $code = trim((string)($s['school_code'] ?? ''));
            if ($code !== '') {
                $selectByCode->execute([$code]);
                $row = $selectByCode->fetch(PDO::FETCH_ASSOC);
                if ($row) { $id = (int)$row['id']; }
            }

            if ($id === null) {
                $selectByNameCity->execute([$s['name'], $s['city']]);
                $row = $selectByNameCity->fetch(PDO::FETCH_ASSOC);
                if ($row) { $id = (int)$row['id']; }
            }

            if ($id === null) {
                $insertStmt->execute([$s['name'], $s['city'], $s['district'], $s['type'], $code]);
                $inserted++;
            } else {
                $updateById->execute([$s['name'], $s['city'], $s['district'], $s['type'], $code, $id]);
                $updated++;
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return ['inserted' => $inserted, 'updated' => $updated, 'skipped' => $skipped];
}

renderPageStart();

// 顯示匯入表單
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<div class='card'>";
    echo "<p>此工具支援：自動偵測更新（既有 <span class='pill'>smart_api_monitor.php</span> / <span class='pill'>auto_update_from_api.php</span>）之外的 <strong>手動CSV匯入</strong>，能在API尚未恢復時先導入資料。</p>";
    echo "<p class='muted'>CSV 欄位需包含：縣市名稱、學校代碼、學校名稱（官方提供之 106_basej.csv 已符合）。</p>";
    echo "</div>";
    renderForm();
    echo "<div class='card'>";
    echo "<h3>流程建議</h3>";
    echo "<ol>";
    echo "<li>先用此頁匯入 106_basej.csv，讓前端搜尋立即可用。</li>";
    echo "<li>持續啟用自動偵測（<code>smart_api_monitor.php</code>）與排程更新（<code>auto_update_from_api.php</code>）。</li>";
    echo "<li>未來API恢復後，系統將自動比對新增/更名/停辦並更新。</li>";
    echo "</ol>";
    echo "</div>";
    exit;
}

// 接收與解析檔案
$targetPath = '';
if (!empty($_FILES['csv_file']['name']) && is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
    $tmpName = $_FILES['csv_file']['tmp_name'];
    $safeName = 'upload_' . date('Ymd_His') . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $_FILES['csv_file']['name']);
    $dest = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $safeName;
    if (!move_uploaded_file($tmpName, $dest)) {
        echo "<div class='error'>上傳檔案移動失敗</div>";
        renderForm();
        exit;
    }
    $targetPath = $dest;
    echo "<div class='success'>已接收上傳檔案：" . htmlspecialchars($_FILES['csv_file']['name']) . "</div>";
} elseif (!empty($_POST['server_path'])) {
    $candidate = trim((string)$_POST['server_path']);
    if (!is_file($candidate)) {
        echo "<div class='error'>伺服器路徑不存在：" . htmlspecialchars($candidate) . "</div>";
        renderForm();
        exit;
    }
    $targetPath = $candidate;
    echo "<div class='success'>使用伺服器路徑：" . htmlspecialchars($candidate) . "</div>";
} else {
    echo "<div class='error'>請上傳CSV或填寫伺服器路徑</div>";
    renderForm();
    exit;
}

// 開始處理
try {
    $schools = parseCsv($targetPath);
    if (empty($schools)) {
        echo "<div class='error'>CSV 內找不到任何國民中學資料</div>";
        renderForm();
        exit;
    }

    echo "<div class='card'>";
    echo "<h3>解析結果</h3>";
    echo "<div class='stats'>";
    echo "  <div class='pill'>總筆數：" . count($schools) . "</div>";
    $cityCounts = [];
    foreach ($schools as $s) { $cityCounts[$s['city']] = ($cityCounts[$s['city']] ?? 0) + 1; }
    echo "</div>";

    echo "<details style='margin-top:10px;'><summary>查看各縣市筆數</summary><pre>";
    ksort($cityCounts);
    foreach ($cityCounts as $city => $cnt) {
        echo htmlspecialchars(($city !== '' ? $city : '（未標示縣市）')) . ': ' . $cnt . "\n";
    }
    echo "</pre></details>";
    echo "</div>";

    $result = upsertSchools($pdo, $schools);
    echo "<div class='success'>匯入完成：新增 {$result['inserted']} 筆、更新 {$result['updated']} 筆</div>";

    echo "<div class='card'>";
    echo "<h3>下一步</h3>";
    echo "<ul>";
    echo "<li><a href='cooperation_upload.php' target='_blank'>前往前端頁面測試搜尋</a></li>";
    echo "<li><a href='smart_api_monitor.php' target='_blank'>啟用/檢查 API 自動偵測</a></li>";
    echo "<li><a href='auto_update_from_api.php' target='_blank'>手動觸發自動更新腳本</a></li>";
    echo "</ul>";
    echo "</div>";

} catch (Throwable $e) {
    echo "<div class='error'>匯入失敗：" . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre class='muted'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    renderForm();
    exit;
}
