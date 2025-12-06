<?php
// ===============================================
// 康寧大學 - 國中學校招生申請系統
// ===============================================

// 設定時區為台灣
date_default_timezone_set('Asia/Taipei');

// 啟用輸出緩衝，避免 header() 錯誤
ob_start();

// 載入設定檔與 Session 設定
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session_config.php';

// 載入 PHPMailer
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --------------------------------------------------
// 建立 PDO 資料庫連線
// --------------------------------------------------
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USERNAME,
        DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('資料庫連接失敗: ' . htmlspecialchars($e->getMessage()));
}

// --------------------------------------------------
// 資料表結構說明（表已存在於 topics_good.sql 中）
// --------------------------------------------------
// 注意：資料表結構已在 topics_good.sql 中定義
// 如果表不存在，請執行 topics_good.sql 來建立資料表
// 學校欄位使用 school_code（學校代號），不是 school_name

// --------------------------------------------------
// 讀取資料庫選項資料
// --------------------------------------------------
// 讀取目標年級選項（國一、國二、國三）
$target_grades_options = [];
try {
    $stmt = $pdo->prepare("SELECT code, name FROM identity_options WHERE code IN ('J1', 'J2', 'J3') ORDER BY code");
    $stmt->execute();
    $target_grades_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("讀取目標年級選項失敗: " . $e->getMessage());
}

// 讀取場地類型選項
$venue_options = [];
try {
    $stmt = $pdo->prepare("SELECT code, name FROM venue ORDER BY code");
    $stmt->execute();
    $venue_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("讀取場地類型選項失敗: " . $e->getMessage());
}

// --------------------------------------------------
// 初始化變數
// --------------------------------------------------
$search_email = '';
$application_data = null;
$application_list = [];
$selected_application_id = 0;
$action = '';
$result_message = '';
$result_type = '';
$selected_target_grades = []; // 已選的目標年級（用於更新時）
$search_debug_info = ''; // 搜尋診斷信息

// --------------------------------------------------
// 處理 GET 搜尋邏輯
// --------------------------------------------------
if (isset($_GET['application_id'])) {
    $selected_application_id = (int)$_GET['application_id'];
}
if (isset($_GET['action'])) {
    $action = $_GET['action'];
}

if ($action === 'search' && isset($_GET['email'])) {
    // 清理輸入的 email
    $search_email = trim($_GET['email']);
    if ($search_email !== '') {
        try {
            // ============================================
            // 重新編寫的搜尋邏輯 - 使用最簡單直接的方式
            // ============================================
            
            $application_list = [];
            
            // 方法1: 先找到聯絡人 ID，然後用 ID 查詢申請記錄（最可靠的方式）
            // 步驟1: 查找聯絡人（使用多種比對方式確保能找到）
            $contact_ids = [];
            
            // 嘗試1: 精確比對
            $stmt_contact = $pdo->prepare("SELECT id FROM schools_contacts WHERE email = ?");
            $stmt_contact->execute([$search_email]);
            $contact_ids = array_merge($contact_ids, $stmt_contact->fetchAll(PDO::FETCH_COLUMN));
            
            // 嘗試2: 大小寫不敏感比對
            if (empty($contact_ids)) {
                $stmt_contact = $pdo->prepare("SELECT id FROM schools_contacts WHERE LOWER(email) = LOWER(?)");
                $stmt_contact->execute([$search_email]);
                $contact_ids = array_merge($contact_ids, $stmt_contact->fetchAll(PDO::FETCH_COLUMN));
            }
            
            // 嘗試3: 去除空格後比對
            if (empty($contact_ids)) {
                $stmt_contact = $pdo->prepare("SELECT id FROM schools_contacts WHERE TRIM(email) = TRIM(?)");
                $stmt_contact->execute([$search_email]);
                $contact_ids = array_merge($contact_ids, $stmt_contact->fetchAll(PDO::FETCH_COLUMN));
            }
            
            // 去除重複的 ID 並重新索引數組
            $contact_ids = array_values(array_unique($contact_ids));
            
            // 步驟2: 如果有找到聯絡人 ID，查詢對應的申請記錄
            if (!empty($contact_ids)) {
                $placeholders = implode(',', array_fill(0, count($contact_ids), '?'));
                $stmt = $pdo->prepare("
                    SELECT a.*,
                           COALESCE(a.admin_comment, '') as admin_remarks
                    FROM junior_school_recruitment_applications a
                    WHERE a.contacts_id IN ($placeholders)
                    ORDER BY a.created_at DESC
                ");
                $stmt->execute($contact_ids);
                $application_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // 方法2: 如果還是沒有結果，嘗試使用 JOIN 查詢（備用方案）
            if (empty($application_list)) {
                $stmt = $pdo->prepare("
                    SELECT a.*,
                           COALESCE(a.admin_comment, '') as admin_remarks
                    FROM junior_school_recruitment_applications a
                    INNER JOIN schools_contacts c ON a.contacts_id = c.id
                    WHERE c.email = ?
                    ORDER BY a.created_at DESC
                ");
                $stmt->execute([$search_email]);
                $application_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // 方法3: 如果還是沒有結果，嘗試大小寫不敏感的 JOIN
            if (empty($application_list)) {
                $stmt = $pdo->prepare("
                    SELECT a.*,
                           COALESCE(a.admin_comment, '') as admin_remarks
                    FROM junior_school_recruitment_applications a
                    INNER JOIN schools_contacts c ON a.contacts_id = c.id
                    WHERE LOWER(c.email) = LOWER(?)
                    ORDER BY a.created_at DESC
                ");
                $stmt->execute([$search_email]);
                $application_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // 方法4: 嘗試查詢 contact_email 欄位（舊資料備用）
            if (empty($application_list)) {
                try {
                    $stmt = $pdo->prepare("
                        SELECT a.*,
                               COALESCE(a.admin_comment, '') as admin_remarks
                        FROM junior_school_recruitment_applications a
                        WHERE a.contact_email = ?
                        ORDER BY a.created_at DESC
                    ");
                    $stmt->execute([$search_email]);
                    $application_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    // contact_email 欄位可能不存在，忽略錯誤
                }
            }
            
            // 如果沒有找到結果，進行詳細診斷
            if (empty($application_list)) {
                $debug_messages = [];
                $debug_messages[] = "═══════════════════════════════════════════════════════════";
                $debug_messages[] = "=== 搜尋診斷信息 ===";
                $debug_messages[] = "═══════════════════════════════════════════════════════════";
                $debug_messages[] = "";
                $debug_messages[] = "搜尋的 Email: " . htmlspecialchars($search_email, ENT_QUOTES, 'UTF-8');
                $debug_messages[] = "原始 GET 參數: " . htmlspecialchars($_GET['email'] ?? '', ENT_QUOTES, 'UTF-8');
                $debug_messages[] = "";
                
                // ============================================
                // 直接顯示 schools_contacts 表的所有資料（簡化格式）
                // ============================================
                $debug_messages[] = "【資料表內容】schools_contacts 表的所有記錄：";
                $debug_messages[] = "───────────────────────────────────────────────────────────";
                try {
                    $all_contacts_stmt = $pdo->query("SELECT id, email, contact_name, school_code, phone, title, is_active, created_at FROM schools_contacts ORDER BY id DESC LIMIT 50");
                    $all_contacts = $all_contacts_stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (count($all_contacts) > 0) {
                        $debug_messages[] = "✓ 總共找到 " . count($all_contacts) . " 筆記錄（顯示前 50 筆）";
                        $debug_messages[] = "";
                        foreach ($all_contacts as $contact) {
                            $match_indicator = (stripos($contact['email'], $search_email) !== false) ? " ⭐匹配" : "";
                            $debug_messages[] = sprintf(
                                "ID: %d | Email: %s%s | 姓名: %s | 學校: %s | 電話: %s | 職稱: %s | 啟用: %s | 建立: %s",
                                $contact['id'],
                                htmlspecialchars($contact['email'], ENT_QUOTES, 'UTF-8'),
                                $match_indicator,
                                htmlspecialchars($contact['contact_name'] ?? 'NULL', ENT_QUOTES, 'UTF-8'),
                                htmlspecialchars($contact['school_code'] ?? 'NULL', ENT_QUOTES, 'UTF-8'),
                                htmlspecialchars($contact['phone'] ?? 'NULL', ENT_QUOTES, 'UTF-8'),
                                htmlspecialchars($contact['title'] ?? 'NULL', ENT_QUOTES, 'UTF-8'),
                                $contact['is_active'] ? '是' : '否',
                                $contact['created_at']
                            );
                        }
                    } else {
                        $debug_messages[] = "✗ schools_contacts 表是空的！";
                    }
                } catch (PDOException $e) {
                    $debug_messages[] = "✗ 無法讀取 schools_contacts 表";
                    $debug_messages[] = "錯誤訊息: " . $e->getMessage();
                    $debug_messages[] = "錯誤代碼: " . $e->getCode();
                    $debug_messages[] = "SQL 狀態: " . $e->getCode();
                } catch (Exception $e) {
                    $debug_messages[] = "✗ 發生未知錯誤";
                    $debug_messages[] = "錯誤訊息: " . $e->getMessage();
                }
                
                $debug_messages[] = "";
                $debug_messages[] = "【搜尋比對】檢查 email 是否存在於 schools_contacts 表中";
                $check_stmt = $pdo->prepare("
                    SELECT id, email, contact_name, school_code
                    FROM schools_contacts 
                    WHERE email = ? OR LOWER(email) = LOWER(?) OR TRIM(email) = TRIM(?)
                    LIMIT 10
                ");
                $check_stmt->execute([$search_email, $search_email, $search_email]);
                $contacts_found = $check_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($contacts_found) > 0) {
                    $debug_messages[] = "✓ 找到 " . count($contacts_found) . " 筆聯絡人記錄";
                    $found_contact_ids = array_column($contacts_found, 'id');
                    $debug_messages[] = "聯絡人 ID: " . implode(', ', $found_contact_ids);
                    foreach ($contacts_found as $contact) {
                        $debug_messages[] = "  - ID " . $contact['id'] . ": " . htmlspecialchars($contact['email'], ENT_QUOTES, 'UTF-8') . " (" . ($contact['contact_name'] ?? '無姓名') . ")";
                    }
                    
                    // 步驟2: 檢查這些聯絡人是否有對應的申請記錄
                    $debug_messages[] = "";
                    $debug_messages[] = "【步驟2】檢查申請記錄";
                    if (!empty($found_contact_ids)) {
                        $placeholders = implode(',', array_fill(0, count($found_contact_ids), '?'));
                        $app_check_stmt = $pdo->prepare("
                            SELECT id, contacts_id, school_code, created_at
                            FROM junior_school_recruitment_applications 
                            WHERE contacts_id IN ($placeholders)
                            ORDER BY created_at DESC
                        ");
                        $app_check_stmt->execute($found_contact_ids);
                        $apps_with_contact = $app_check_stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($apps_with_contact) > 0) {
                            $debug_messages[] = "✓ 找到 " . count($apps_with_contact) . " 筆申請記錄！";
                            $debug_messages[] = "申請 ID: " . implode(', ', array_column($apps_with_contact, 'id'));
                            foreach ($apps_with_contact as $app) {
                                $debug_messages[] = "  - 申請 ID " . $app['id'] . ": contacts_id=" . $app['contacts_id'] . ", school_code=" . ($app['school_code'] ?? 'NULL');
                            }
                            $debug_messages[] = "";
                            $debug_messages[] = "⚠️ 奇怪：資料庫中有申請記錄，但查詢沒有返回結果";
                            $debug_messages[] = "這可能是 PHP 查詢邏輯的問題，請檢查程式碼";
                        } else {
                            $debug_messages[] = "✗ 沒有找到對應的申請記錄";
                            $debug_messages[] = "";
                            $debug_messages[] = "這表示：";
                            $debug_messages[] = "  - 聯絡人記錄存在 ✓";
                            $debug_messages[] = "  - 但沒有使用此聯絡人 ID 的申請記錄 ✗";
                            $debug_messages[] = "";
                            $debug_messages[] = "可能的原因：";
                            $debug_messages[] = "  1. 尚未使用此 email 提交過申請";
                            $debug_messages[] = "  2. 申請記錄的 contacts_id 欄位為 NULL";
                            $debug_messages[] = "  3. 申請記錄使用了不同的聯絡人 ID";
                        }
                    }
                } else {
                    $debug_messages[] = "✗ 在 schools_contacts 表中找不到此 Email";
                    
                    // 嘗試模糊搜尋來幫助診斷
                    $fuzzy_stmt = $pdo->prepare("
                        SELECT id, email, contact_name 
                        FROM schools_contacts 
                        WHERE email LIKE ?
                        LIMIT 5
                    ");
                    $fuzzy_stmt->execute(['%' . $search_email . '%']);
                    $fuzzy_contacts = $fuzzy_stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (count($fuzzy_contacts) > 0) {
                        $debug_messages[] = "  找到相似的 Email:";
                        foreach ($fuzzy_contacts as $fuzzy) {
                            $debug_messages[] = "    - " . htmlspecialchars($fuzzy['email'], ENT_QUOTES, 'UTF-8') . " (ID: " . $fuzzy['id'] . ")";
                        }
                    }
                }
                
                // ============================================
                // 直接顯示 junior_school_recruitment_applications 表的相關資料（簡化格式）
                // ============================================
                $debug_messages[] = "";
                $debug_messages[] = "【資料表內容】junior_school_recruitment_applications 表的所有記錄：";
                $debug_messages[] = "───────────────────────────────────────────────────────────";
                try {
                    $all_apps_stmt = $pdo->query("SELECT id, contacts_id, school_code, preferred_date, status, created_at FROM junior_school_recruitment_applications ORDER BY id DESC LIMIT 50");
                    $all_apps = $all_apps_stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (count($all_apps) > 0) {
                        $debug_messages[] = "✓ 總共找到 " . count($all_apps) . " 筆記錄（顯示前 50 筆）";
                        $debug_messages[] = "";
                        foreach ($all_apps as $app) {
                            $match_indicator = (isset($found_contact_ids) && in_array($app['contacts_id'], $found_contact_ids)) ? " ⭐匹配" : "";
                            $debug_messages[] = sprintf(
                                "申請ID: %d | contacts_id: %s%s | 學校: %s | 日期: %s | 狀態: %s | 建立: %s",
                                $app['id'],
                                $app['contacts_id'] ?? 'NULL',
                                $match_indicator,
                                htmlspecialchars($app['school_code'] ?? 'NULL', ENT_QUOTES, 'UTF-8'),
                                htmlspecialchars($app['preferred_date'] ?? 'NULL', ENT_QUOTES, 'UTF-8'),
                                htmlspecialchars($app['status'] ?? 'NULL', ENT_QUOTES, 'UTF-8'),
                                $app['created_at']
                            );
                        }
                    } else {
                        $debug_messages[] = "✗ junior_school_recruitment_applications 表是空的！";
                    }
                } catch (PDOException $e) {
                    $debug_messages[] = "✗ 無法讀取 junior_school_recruitment_applications 表";
                    $debug_messages[] = "錯誤訊息: " . $e->getMessage();
                    $debug_messages[] = "錯誤代碼: " . $e->getCode();
                } catch (Exception $e) {
                    $debug_messages[] = "✗ 發生未知錯誤";
                    $debug_messages[] = "錯誤訊息: " . $e->getMessage();
                }
                
                // 檢查字符集和 collation
                $debug_messages[] = "";
                $debug_messages[] = "【資料庫設定】檢查字符集和 collation";
                try {
                    $charset_stmt = $pdo->prepare("
                        SELECT TABLE_NAME, COLUMN_NAME, CHARACTER_SET_NAME, COLLATION_NAME
                        FROM information_schema.COLUMNS
                        WHERE TABLE_SCHEMA = DATABASE()
                        AND TABLE_NAME = 'schools_contacts'
                        AND COLUMN_NAME = 'email'
                    ");
                    $charset_stmt->execute();
                    $charset_info = $charset_stmt->fetch(PDO::FETCH_ASSOC);
                    if ($charset_info) {
                        $debug_messages[] = "  Email 欄位字符集: " . ($charset_info['CHARACTER_SET_NAME'] ?? 'N/A');
                        $debug_messages[] = "  Email 欄位 collation: " . ($charset_info['COLLATION_NAME'] ?? 'N/A');
                    }
                } catch (PDOException $e) {
                    $debug_messages[] = "  無法檢查字符集信息";
                }
                
                // 將診斷信息儲存到變數中，供頁面顯示
                $search_debug_info = implode("\n", $debug_messages);
            }

            if ($selected_application_id > 0) {
                foreach ($application_list as $app) {
                    if ($app['id'] == $selected_application_id) {
                        $application_data = $app;
                        break;
                    }
                }
            } elseif (count($application_list) > 0) {
                $application_data = $application_list[0];
            }
            
            // 讀取已選的目標年級和聯絡人資訊
            if ($application_data) {
                try {
                    $stmt = $pdo->prepare("SELECT target_grades FROM recruitment_target WHERE application_id = ?");
                    $stmt->execute([$application_data['id']]);
                    $selected_target_grades = $stmt->fetchAll(PDO::FETCH_COLUMN);
                } catch (PDOException $e) {
                    error_log("讀取目標年級失敗: " . $e->getMessage());
                    $selected_target_grades = [];
                }
                
                // 查詢學校名稱用於顯示（資料表只存 school_code）
                if (!empty($application_data['school_code'])) {
                    try {
                        $stmt = $pdo->prepare("SELECT name FROM school_data WHERE school_code = ? LIMIT 1");
                        $stmt->execute([$application_data['school_code']]);
                        $school_data = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($school_data && !empty($school_data['name'])) {
                            $application_data['school_name_display'] = $school_data['name'];
                        } else {
                            $application_data['school_name_display'] = $application_data['school_code'];
                        }
                    } catch (PDOException $e) {
                        error_log("查詢學校名稱失敗: " . $e->getMessage());
                        $application_data['school_name_display'] = $application_data['school_code'];
                    }
                }
                
                // 查詢聯絡人資訊
                if (!empty($application_data['contacts_id'])) {
                    try {
                        $stmt = $pdo->prepare("SELECT contact_name, email, phone, title FROM schools_contacts WHERE id = ? LIMIT 1");
                        $stmt->execute([$application_data['contacts_id']]);
                        $contact_data = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($contact_data) {
                            $application_data['contact_name'] = $contact_data['contact_name'];
                            $application_data['contact_email'] = $contact_data['email'];
                            $application_data['contact_phone'] = $contact_data['phone'];
                            $application_data['contact_title'] = $contact_data['title'];
                        }
                    } catch (PDOException $e) {
                        error_log("查詢聯絡人資訊失敗: " . $e->getMessage());
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("搜尋申請資料失敗: " . $e->getMessage());
            // 即使發生錯誤，也設置診斷信息並顯示資料表內容
            $debug_messages = [];
            $debug_messages[] = "=== 搜尋診斷信息（發生錯誤）===";
            $debug_messages[] = "";
            $debug_messages[] = "✗ 搜尋時發生資料庫錯誤: " . $e->getMessage();
            $debug_messages[] = "錯誤代碼: " . $e->getCode();
            $debug_messages[] = "";
            $debug_messages[] = "═══════════════════════════════════════════════════════════";
            $debug_messages[] = "【資料表內容】schools_contacts 表的所有記錄";
            $debug_messages[] = "═══════════════════════════════════════════════════════════";
            try {
                $all_contacts_stmt = $pdo->query("SELECT id, email, contact_name, school_code, phone, title, is_active, created_at FROM schools_contacts ORDER BY id DESC LIMIT 50");
                $all_contacts = $all_contacts_stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($all_contacts) > 0) {
                    $debug_messages[] = "✓ 總共找到 " . count($all_contacts) . " 筆記錄";
                    foreach ($all_contacts as $contact) {
                        $debug_messages[] = sprintf("ID: %d | Email: %s | 姓名: %s | 學校: %s", 
                            $contact['id'],
                            htmlspecialchars($contact['email'], ENT_QUOTES, 'UTF-8'),
                            htmlspecialchars($contact['contact_name'] ?? 'NULL', ENT_QUOTES, 'UTF-8'),
                            htmlspecialchars($contact['school_code'] ?? 'NULL', ENT_QUOTES, 'UTF-8')
                        );
                    }
                } else {
                    $debug_messages[] = "✗ schools_contacts 表是空的！";
                }
            } catch (PDOException $e2) {
                $debug_messages[] = "✗ 無法讀取 schools_contacts 表: " . $e2->getMessage();
            }
            $search_debug_info = implode("\n", $debug_messages);
        } catch (Exception $e) {
            error_log("搜尋申請資料發生未知錯誤: " . $e->getMessage());
            // 即使發生錯誤，也設置診斷信息
            $search_debug_info = "搜尋時發生錯誤: " . $e->getMessage();
        }
    }
}

// --------------------------------------------------
// 處理 POST 表單提交邏輯
// --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_post = $_POST['action'] ?? 'submit';
    $application_id = isset($_POST['application_id']) ? (int)$_POST['application_id'] : 0;

    // 讀取表單資料
    $school_code = trim($_POST['school_code'] ?? '');
    $school_address = trim($_POST['school_address'] ?? '');
    $contact_name = trim($_POST['contact_name'] ?? '');
    $contact_title = trim($_POST['contact_title'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $preferred_date = trim($_POST['preferred_date'] ?? '');
    $preferred_time = trim($_POST['preferred_time'] ?? '');
    // 目標年級改為複選陣列
    $target_grades = isset($_POST['target_grades']) && is_array($_POST['target_grades']) ? $_POST['target_grades'] : [];
    $expected_students = trim($_POST['expected_students'] ?? '');
    $venue_type = trim($_POST['venue_type'] ?? ''); // 存 code
    $special_requirements = trim($_POST['special_requirements'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $captcha = trim($_POST['captcha'] ?? '');
    
    // 記錄所有接收到的 POST 資料（用於調試）
    error_log("=== 接收到的 POST 資料 ===");
    error_log("POST keys: " . implode(', ', array_keys($_POST)));
    foreach ($_POST as $key => $value) {
        if (is_array($value)) {
            error_log("  $key: [" . implode(', ', $value) . "]");
        } else {
            error_log("  $key: '" . substr($value, 0, 100) . "'");
        }
    }

    // 表單驗證 - 添加詳細的錯誤日誌
    error_log("=== 開始表單驗證 ===");
    error_log("POST 資料: " . print_r($_POST, true));
    error_log("school_code: '" . $school_code . "'");
    error_log("contact_name: '" . $contact_name . "'");
    error_log("contact_phone: '" . $contact_phone . "'");
    error_log("contact_email: '" . $contact_email . "'");
    error_log("preferred_date: '" . $preferred_date . "'");
    error_log("preferred_time: '" . $preferred_time . "'");
    error_log("target_grades: " . print_r($target_grades, true));
    error_log("expected_students: '" . $expected_students . "'");
    
    // 檢查每個必填欄位
    $missing_fields = [];
    if ($school_code === '') {
        $missing_fields[] = '學校名稱';
        error_log("❌ 缺少：school_code");
    }
    if ($contact_name === '') {
        $missing_fields[] = '聯絡人姓名';
        error_log("❌ 缺少：contact_name");
    }
    if ($contact_phone === '') {
        $missing_fields[] = '聯絡人電話';
        error_log("❌ 缺少：contact_phone");
    }
    if ($contact_email === '') {
        $missing_fields[] = '聯絡人 Email';
        error_log("❌ 缺少：contact_email");
    }
    if ($preferred_date === '') {
        $missing_fields[] = '首選日期';
        error_log("❌ 缺少：preferred_date");
    }
    if ($preferred_time === '') {
        $missing_fields[] = '首選時段';
        error_log("❌ 缺少：preferred_time");
    }
    if (empty($target_grades)) {
        $missing_fields[] = '目標年級';
        error_log("❌ 缺少：target_grades");
    }
    if ($expected_students === '') {
        $missing_fields[] = '預期參與學生數';
        error_log("❌ 缺少：expected_students");
    }
    
    if (!empty($missing_fields)) {
        $result_message = '請填寫所有必填欄位。缺少的欄位：' . implode('、', $missing_fields);
        $result_type = 'error';
        error_log("驗證失敗：缺少欄位 - " . implode(', ', $missing_fields));
    } elseif (empty($school_code)) {
        // 驗證學校代號必須存在
        $result_message = '請從系統提供的選項中選擇學校，不能自行輸入';
        $result_type = 'error';
        error_log("驗證失敗：school_code 為空");
    } else {
        // 驗證通過，繼續處理
        error_log("✅ 表單驗證通過，繼續處理");
        
        // 根據 school_code 查詢學校資訊（用於後續處理，如郵件）
        // 注意：不再需要驗證縣市與學校是否一致，因為現在使用 school_code 作為唯一識別
        try {
            if (!empty($school_code)) {
                $school_check_stmt = $pdo->prepare("SELECT name, city, district FROM school_data WHERE school_code = ? AND is_active = 1 LIMIT 1");
                $school_check_stmt->execute([$school_code]);
                $school_result = $school_check_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($school_result) {
                    error_log("查詢到學校資訊: " . print_r($school_result, true));
                    // 學校資訊可用於後續處理（如郵件）
                } else {
                    error_log("警告：找不到 school_code = " . $school_code . " 的學校記錄");
                }
            }
        } catch (PDOException $e) {
            error_log("查詢學校資訊失敗: " . $e->getMessage());
            // 查詢失敗時不阻擋提交，但記錄錯誤
        }
        
        // 繼續處理表單提交（進入資料庫操作）
    }
    
    // 驗證碼檢查（如果前面的驗證都通過）
    if (empty($result_message) && $captcha === '') {
        $result_message = '請輸入驗證碼。';
        $result_type = 'error';
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $result_message = '請輸入有效的 Email 格式。';
        $result_type = 'error';
    } elseif (!is_numeric($expected_students) || (int)$expected_students <= 0) {
        $result_message = '預期學生數必須為大於 0 的數字。';
        $result_type = 'error';
    } else {
        // 驗證碼檢查
        $captcha_session = $_SESSION['captcha_code'] ?? '';
        if (empty($captcha_session)) {
            $result_message = '驗證碼已過期，請重新載入。';
            $result_type = 'error';
        } elseif (strtoupper($captcha) !== strtoupper($captcha_session)) {
            $result_message = '驗證碼錯誤。';
            $result_type = 'error';
        } else {
            unset($_SESSION['captcha_code']); // 驗證成功後清除

            try {
                $pdo->beginTransaction();
                $expected_students_int = (int)$expected_students;

                if ($action_post === 'update' && $application_id > 0) {
                    // 先處理聯絡人：檢查或新增到 schools_contacts
                    $contacts_id = null;
                    try {
                        // 檢查是否已存在相同的聯絡人（根據 school_code 和 email）
                        $check_contact = $pdo->prepare("SELECT id FROM schools_contacts WHERE school_code = ? AND email = ? LIMIT 1");
                        $check_contact->execute([$school_code, $contact_email]);
                        $existing_contact = $check_contact->fetch(PDO::FETCH_ASSOC);
                        
                        if ($existing_contact) {
                            // 更新現有聯絡人（包含 phone 欄位）
                            $contacts_id = (int)$existing_contact['id'];
                            $upd_contact = $pdo->prepare("UPDATE schools_contacts SET contact_name=?, phone=?, title=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
                            $upd_contact->execute([$contact_name ?: null, $contact_phone ?: null, $contact_title ?: null, $contacts_id]);
                        } else {
                            // 新增聯絡人（包含 phone 欄位）
                            $ins_contact = $pdo->prepare("INSERT INTO schools_contacts (school_code, contact_name, email, phone, title) VALUES (?, ?, ?, ?, ?)");
                            $ins_contact->execute([$school_code, $contact_name ?: null, $contact_email, $contact_phone ?: null, $contact_title ?: null]);
                            $contacts_id = (int)$pdo->lastInsertId();
                        }
                    } catch (PDOException $e) {
                        error_log("處理聯絡人資料失敗: " . $e->getMessage());
                        throw $e;
                    }
                    
                    if (!$contacts_id) {
                        throw new PDOException("無法取得聯絡人ID");
                    }
                    
                    // 更新申請資料（當用戶修改資料時，將狀態改為 PE（待審核））
                    $upd = $pdo->prepare("UPDATE junior_school_recruitment_applications SET
                        school_code=?, school_address=?, contacts_id=?, preferred_date=?, preferred_time=?,
                        expected_students=?, venue_type=?, special_requirements=?, remarks=?, 
                        status=?, updated_at=CURRENT_TIMESTAMP
                        WHERE id=?");

                    // 驗證狀態代碼是否存在
                    $reset_status = 'PE'; // 重新提交時將狀態改為待審核
                    try {
                        $stmt = $pdo->prepare("SELECT code FROM application_statuses WHERE code = ? LIMIT 1");
                        $stmt->execute([$reset_status]);
                        $status_row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (!$status_row) {
                            $reset_status = null;
                            error_log("警告：application_statuses 表中沒有 'PE' 狀態，使用 NULL");
                        }
                    } catch (PDOException $e) {
                        error_log("無法查詢狀態代碼: " . $e->getMessage());
                        $reset_status = null;
                    }
                    
                    $upd->execute([
                        $school_code, $school_address ?: null, $contacts_id,
                        $preferred_date, $preferred_time,
                        $expected_students_int, $venue_type ?: null, $special_requirements ?: null, $remarks ?: null,
                        $reset_status, // 將狀態重置為待審核
                        $application_id
                    ]);

                    if ($upd->rowCount() > 0) {
                        // 更新目標年級：先刪除舊的，再插入新的
                        try {
                            $del_target = $pdo->prepare("DELETE FROM recruitment_target WHERE application_id = ?");
                            $del_target->execute([$application_id]);
                            
                            if (!empty($target_grades) && is_array($target_grades)) {
                                $ins_target = $pdo->prepare("INSERT INTO recruitment_target (application_id, target_grades) VALUES (?, ?)");
                                foreach ($target_grades as $grade_code) {
                                    // 驗證 grade_code 是否為有效的 J1, J2, J3
                                    $grade_code = trim($grade_code);
                                    if (in_array($grade_code, ['J1', 'J2', 'J3'])) {
                                        try {
                                            $ins_target->execute([$application_id, $grade_code]);
                                            error_log("成功插入目標年級: application_id={$application_id}, target_grades={$grade_code}");
                                        } catch (PDOException $e) {
                                            // 記錄錯誤但不中斷流程（可能是重複鍵）
                                            error_log("插入目標年級失敗: " . $e->getMessage() . " (application_id={$application_id}, target_grades={$grade_code})");
                                            if (strpos($e->getMessage(), 'Duplicate entry') === false && 
                                                strpos($e->getMessage(), 'foreign key constraint') === false) {
                                                throw $e; // 重新拋出非重複鍵和非外鍵的錯誤
                                            }
                                        }
                                    } else {
                                        error_log("警告：無效的年級代碼: {$grade_code}");
                                    }
                                }
                            } else {
                                error_log("警告：目標年級為空或不是陣列: " . print_r($target_grades, true));
                            }
                        } catch (PDOException $e) {
                            error_log("更新目標年級時發生錯誤: " . $e->getMessage());
                            throw $e; // 重新拋出錯誤，觸發 rollback
                        }
                        
                        $pdo->commit();
                        header('Location: ' . $_SERVER['PHP_SELF'] . '?updated=1&id=' . $application_id);
                        exit;
                    } else {
                        $result_message = '更新失敗，請確認申請資料。';
                        $result_type = 'error';
                        $pdo->rollBack();
                    }
                } else {
                    // 新增申請資料（注意：status 欄位有外鍵約束，需要設定有效的狀態代碼）
                    // 根據 topics_good.sql，application_statuses 表中的狀態代碼為：'PE'（待審核）、'AP'（通過）、'RE'（不通過）、'AD'（備取）
                    // status 欄位有外鍵約束到 application_statuses.code，所以必須使用有效的代碼或 NULL
                    $default_status = 'PE'; // 使用 'PE'（待審核）作為預設狀態
                    // 驗證狀態代碼是否存在
                    try {
                        $stmt = $pdo->prepare("SELECT code FROM application_statuses WHERE code = ? LIMIT 1");
                        $stmt->execute([$default_status]);
                        $status_row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (!$status_row) {
                            // 如果 'PE' 不存在，使用 NULL（但這可能會違反外鍵約束）
                            $default_status = null;
                            error_log("警告：application_statuses 表中沒有 'PE' 狀態，使用 NULL");
                        }
                    } catch (PDOException $e) {
                        // 如果查詢失敗，使用 NULL（但這可能會違反外鍵約束）
                        error_log("無法查詢狀態代碼: " . $e->getMessage());
                        $default_status = null;
                    }
                    
                    // 先處理聯絡人：檢查或新增到 schools_contacts
                    $contacts_id = null;
                    try {
                        // 檢查是否已存在相同的聯絡人（根據 school_code 和 email）
                        $check_contact = $pdo->prepare("SELECT id FROM schools_contacts WHERE school_code = ? AND email = ? LIMIT 1");
                        $check_contact->execute([$school_code, $contact_email]);
                        $existing_contact = $check_contact->fetch(PDO::FETCH_ASSOC);
                        
                        if ($existing_contact) {
                            // 更新現有聯絡人（包含 phone 欄位）
                            $contacts_id = (int)$existing_contact['id'];
                            $upd_contact = $pdo->prepare("UPDATE schools_contacts SET contact_name=?, phone=?, title=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
                            $upd_contact->execute([$contact_name ?: null, $contact_phone ?: null, $contact_title ?: null, $contacts_id]);
                        } else {
                            // 新增聯絡人（包含 phone 欄位）
                            $ins_contact = $pdo->prepare("INSERT INTO schools_contacts (school_code, contact_name, email, phone, title) VALUES (?, ?, ?, ?, ?)");
                            $ins_contact->execute([$school_code, $contact_name ?: null, $contact_email, $contact_phone ?: null, $contact_title ?: null]);
                            $contacts_id = (int)$pdo->lastInsertId();
                        }
                    } catch (PDOException $e) {
                        error_log("處理聯絡人資料失敗: " . $e->getMessage());
                        throw $e;
                    }
                    
                    if (!$contacts_id) {
                        throw new PDOException("無法取得聯絡人ID");
                    }
                    
                    $ins = $pdo->prepare("INSERT INTO junior_school_recruitment_applications
                        (school_code, school_address, contacts_id, preferred_date, preferred_time,
                         expected_students, venue_type, special_requirements, remarks, status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                    // 確保所有必填欄位不為空字串
                    // 注意：preferred_date 和 expected_students 在資料表中是 DEFAULT NULL，但我們驗證為必填
                    
                    // 詳細記錄所有參數值（用於調試）
                    error_log("=== 準備插入申請資料 ===");
                    error_log("school_code: " . ($school_code ?: 'NULL/空'));
                    error_log("school_address: " . ($school_address ?: 'NULL/空'));
                    error_log("contacts_id: " . ($contacts_id ?: 'NULL/空'));
                    error_log("preferred_date: " . ($preferred_date ?: 'NULL/空'));
                    error_log("preferred_time: " . ($preferred_time ?: 'NULL/空'));
                    error_log("expected_students_int: " . ($expected_students_int > 0 ? $expected_students_int : 'NULL/0'));
                    error_log("venue_type: " . ($venue_type ?: 'NULL/空'));
                    error_log("special_requirements: " . ($special_requirements ?: 'NULL/空'));
                    error_log("remarks: " . ($remarks ?: 'NULL/空'));
                    error_log("default_status: " . ($default_status ?: 'NULL/空'));
                    
                    // 驗證必填欄位
                    if (empty($school_code)) {
                        error_log("❌ 錯誤：school_code 為空");
                        throw new PDOException("學校代號為空，請從系統選項中選擇學校");
                    }
                    if (empty($contacts_id)) {
                        error_log("❌ 錯誤：contacts_id 為空");
                        throw new PDOException("聯絡人ID為空");
                    }
                    if (empty($preferred_date)) {
                        error_log("❌ 錯誤：preferred_date 為空");
                        throw new PDOException("首選日期為空");
                    }
                    if (empty($preferred_time)) {
                        error_log("❌ 錯誤：preferred_time 為空");
                        throw new PDOException("首選時段為空");
                    }
                    if ($expected_students_int <= 0) {
                        error_log("❌ 錯誤：expected_students 為空或無效");
                        throw new PDOException("預期參與學生數為空或無效");
                    }
                    
                    // 確保必填欄位不為空或 null
                    // 再次驗證（雙重檢查）
                    if (empty($school_code)) {
                        throw new PDOException("學校代號為空");
                    }
                    if (empty($contacts_id) || $contacts_id <= 0) {
                        throw new PDOException("聯絡人ID無效: " . $contacts_id);
                    }
                    if (empty($preferred_date)) {
                        throw new PDOException("首選日期為空");
                    }
                    if (empty($preferred_time)) {
                        throw new PDOException("首選時段為空");
                    }
                    if ($expected_students_int <= 0) {
                        throw new PDOException("預期參與學生數無效: " . $expected_students);
                    }
                    
                    $ins_params = [
                        $school_code, // 必填，不允許 null
                        !empty($school_address) ? $school_address : null, // 可選
                        $contacts_id, // 必填，不允許 null
                        $preferred_date, // 必填，不允許 null
                        $preferred_time, // 必填，不允許 null
                        $expected_students_int, // 必填，不允許 null
                        !empty($venue_type) ? $venue_type : null, // 可選
                        !empty($special_requirements) ? $special_requirements : null, // 可選
                        !empty($remarks) ? $remarks : null, // 可選
                        $default_status // status 欄位
                    ];
                    
                    // 記錄插入參數以便除錯
                    error_log("插入參數數量: " . count($ins_params));
                    error_log("插入參數詳細: " . print_r($ins_params, true));
                    error_log("參數類型檢查:");
                    error_log("  school_code: " . gettype($ins_params[0]) . " = '" . $ins_params[0] . "'");
                    error_log("  contacts_id: " . gettype($ins_params[2]) . " = " . $ins_params[2]);
                    error_log("  preferred_date: " . gettype($ins_params[3]) . " = '" . $ins_params[3] . "'");
                    error_log("  preferred_time: " . gettype($ins_params[4]) . " = '" . $ins_params[4] . "'");
                    error_log("  expected_students: " . gettype($ins_params[5]) . " = " . $ins_params[5]);
                    
                    try {
                        $ins->execute($ins_params);
                        error_log("✅ INSERT 執行成功");
                    } catch (PDOException $insert_error) {
                        error_log("❌ INSERT 執行失敗: " . $insert_error->getMessage());
                        error_log("❌ 錯誤代碼: " . $insert_error->getCode());
                        error_log("❌ SQL 狀態: " . $insert_error->errorInfo[0] ?? 'N/A');
                        error_log("❌ 錯誤資訊: " . print_r($insert_error->errorInfo, true));
                        throw $insert_error;
                    }

                    $application_id = (int)$pdo->lastInsertId();
                    
                    if ($application_id <= 0) {
                        throw new PDOException("無法取得新插入的申請編號");
                    }
                    
                    // 插入目標年級到 recruitment_target 表
                    if (!empty($target_grades) && is_array($target_grades)) {
                        $ins_target = $pdo->prepare("INSERT INTO recruitment_target (application_id, target_grades) VALUES (?, ?)");
                        foreach ($target_grades as $grade_code) {
                            // 驗證 grade_code 是否為有效的 J1, J2, J3
                            $grade_code = trim($grade_code);
                            if (in_array($grade_code, ['J1', 'J2', 'J3'])) {
                                try {
                                    $ins_target->execute([$application_id, $grade_code]);
                                    error_log("成功插入目標年級: application_id={$application_id}, target_grades={$grade_code}");
                                } catch (PDOException $e) {
                                    // 記錄詳細錯誤
                                    error_log("插入目標年級失敗: " . $e->getMessage() . " (application_id={$application_id}, target_grades={$grade_code})");
                                    error_log("錯誤詳情: " . print_r($e->errorInfo, true));
                                    
                                    // 如果是重複鍵錯誤，忽略（可能已經存在）
                                    if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                                        // 外鍵約束錯誤或其他錯誤，重新拋出
                                        throw $e;
                                    }
                                }
                            } else {
                                error_log("警告：無效的年級代碼: {$grade_code} (application_id={$application_id})");
                            }
                        }
                    } else {
                        error_log("警告：目標年級為空或不是陣列: " . print_r($target_grades, true) . " (application_id={$application_id})");
                        // 如果目標年級為空，這是一個錯誤，應該回滾
                        throw new PDOException("目標年級為必填欄位，請至少選擇一個年級");
                    }
                    
                    $pdo->commit();

                    // 發送確認信
                    try {
                        $mail = new PHPMailer(true);
                        $mail->isSMTP();
                        $mail->Host = SMTP_HOST;
                        $mail->SMTPAuth = true;
                        $mail->Username = SMTP_USERNAME;
                        $mail->Password = SMTP_PASSWORD;
                        $mail->SMTPSecure = SMTP_SECURE;
                        $mail->Port = SMTP_PORT;
                        $mail->CharSet = 'UTF-8';
                        $mail->setFrom(SMTP_FROM_EMAIL, '康寧大學招生系統');
                        $mail->addAddress($contact_email, $contact_name);
                        $mail->isHTML(true);
                        // 查詢學校名稱用於郵件
                        $school_name_for_email = $school_code;
                        try {
                            $stmt = $pdo->prepare("SELECT name FROM school_data WHERE school_code = ? LIMIT 1");
                            $stmt->execute([$school_code]);
                            $school_data = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($school_data && !empty($school_data['name'])) {
                                $school_name_for_email = $school_data['name'];
                            }
                        } catch (PDOException $e) {
                            error_log("查詢學校名稱失敗: " . $e->getMessage());
                        }
                        
                        $mail->Subject = '國中學校招生申請已收到 - ' . htmlspecialchars($school_name_for_email, ENT_QUOTES, 'UTF-8');

                        $mailBody = "
                            <html><body style='font-family:Arial,sans-serif'>
                            <h2>感謝您的申請</h2>
                            <p>親愛的 {$contact_name} 您好，</p>
                            <p>貴校 <strong>{$school_name_for_email}</strong> 的招生申請已收到，申請編號為：<strong>#{$application_id}</strong>。</p>
                            <p>我們將於 3-5 個工作天內與您聯繫。</p>
                            <hr>
                            <p style='font-size:12px;color:#777;'>此為系統自動發送郵件，請勿直接回覆。</p>
                            </body></html>";

                        $mail->Body = $mailBody;
                        $mail->AltBody = "感謝您的申請。貴校 {$school_name_for_email} 的招生申請已收到，申請編號 #{$application_id}。";
                        $mail->send();
                    } catch (Exception $e) {
                        error_log("郵件發送失敗: " . $e->getMessage());
                    }

                    // 重導向避免重複提交
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?submitted=1&id=' . $application_id);
                    exit;
                }
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                
                // 優化錯誤提示
                $error_message = $ex->getMessage();
                $error_code = $ex->getCode();
                $error_info = $ex->errorInfo ?? [];
                
                // 記錄詳細錯誤到日誌（包含更多資訊）
                error_log("資料庫錯誤 [Code: $error_code] [SQLState: " . ($error_info[0] ?? 'N/A') . "]: " . $error_message);
                error_log("錯誤詳情: " . print_r($error_info, true));
                error_log("POST 資料: " . print_r($_POST, true));
                
                // 根據錯誤類型提供友好的中文提示
                if ($error_code == 1644) {
                    // MySQL SIGNAL 錯誤（自定義錯誤）
                    if (strpos($error_message, '期望招生日期不能是過去的日期') !== false) {
                        $result_message = '期望招生日期不能是過去的日期，請選擇今天或未來的日期。';
                    } else {
                        // 提取自定義錯誤訊息（去除 SQLSTATE 前綴）
                        $result_message = '資料驗證失敗：' . preg_replace('/^SQLSTATE\[45000\]:.*?:\s*/', '', $error_message);
                    }
                } elseif (strpos($error_message, 'Duplicate entry') !== false) {
                    $result_message = '此申請資料已存在，請勿重複提交。';
                } elseif (strpos($error_message, 'foreign key constraint') !== false) {
                    // 詳細記錄外鍵約束錯誤
                    if (strpos($error_message, 'recruitment_target') !== false) {
                        if (strpos($error_message, 'application_id') !== false) {
                            $result_message = '資料關聯錯誤：申請編號不存在，請重新提交申請。';
                        } elseif (strpos($error_message, 'target_grades') !== false) {
                            $result_message = '資料關聯錯誤：目標年級代碼無效，請選擇有效的年級（國一、國二、國三）。';
                        } else {
                            $result_message = '資料關聯錯誤：目標年級資料驗證失敗，請檢查選擇的年級是否正確。';
                        }
                    } elseif (strpos($error_message, 'school_code') !== false) {
                        $result_message = '資料關聯錯誤：學校代號不存在，請從系統選項中選擇學校。';
                    } else {
                        $result_message = '資料關聯錯誤，請檢查輸入的資料是否正確。';
                    }
                } elseif (strpos($error_message, 'cannot be null') !== false || strpos($error_message, 'Column') !== false && strpos($error_message, 'cannot be null') !== false) {
                    $result_message = '必填欄位未填寫完整，請檢查所有標示 * 的欄位。';
                } elseif (strpos($error_message, 'Unknown column') !== false) {
                    $result_message = '資料表結構錯誤，請聯繫系統管理員檢查資料庫設定。';
                } elseif (strpos($error_message, 'Table') !== false && strpos($error_message, "doesn't exist") !== false) {
                    $result_message = '資料表不存在，請聯繫系統管理員檢查資料庫設定。';
                } else {
                    // 其他資料庫錯誤，提供通用提示
                    // 在開發環境可以通過 URL 參數 ?debug=1 顯示詳細錯誤
                    $show_debug = isset($_GET['debug']) && $_GET['debug'] == '1';
                    if ($show_debug) {
                        $result_message = '申請提交失敗：' . htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') . ' (錯誤代碼: ' . $error_code . ')';
                    } else {
                        $result_message = '申請提交失敗，請檢查輸入的資料是否正確。如問題持續，請聯繫系統管理員。';
                    }
                }
                
                $result_type = 'error';
            } catch (Exception $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                
                // 記錄一般錯誤
                error_log("申請提交錯誤: " . $ex->getMessage());
                
                $result_message = '申請提交失敗，請稍後再試。如問題持續，請聯繫系統管理員。';
                $result_type = 'error';
            }
        }
    }
}

// 處理提交成功後的顯示
if (isset($_GET['submitted']) && $_GET['submitted'] == '1' && isset($_GET['id'])) {
    $result_message = '申請已成功提交！申請編號：' . htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8') . '。我們將盡快處理您的申請。';
    $result_type = 'success';
    // 清除搜尋結果，避免顯示舊資料
    $application_data = null;
    $search_email = '';
}

// 處理更新成功後的顯示
if (isset($_GET['updated']) && $_GET['updated'] == '1' && isset($_GET['id'])) {
    $result_message = '申請資料已更新！申請編號：' . htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8');
    $result_type = 'success';
    // 清除搜尋結果，避免顯示舊資料
    $application_data = null;
    $search_email = '';
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>國中學校招生申請 - 康寧大學</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/csp/mobile_junior.css">
    <link rel="stylesheet" href="assets/css/maps.css">
    <style>
        /* 錯誤提示動畫 */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* 學校地址相關欄位樣式 - 與 admission_recommend.php 一致 */
        .field-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .field-group select[name="city"],
        .field-group select[name="district"],
        .field-group input[name="school_address"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
            background: white;
            font-family: 'Microsoft JhengHei', sans-serif;
        }
        
        .field-group select[name="city"]:focus,
        .field-group select[name="district"]:focus,
        .field-group input[name="school_address"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .field-error {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .field-error i {
            font-size: 14px;
        }
    </style>
    <style>
        .recruitment-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #1976d2;
        }
        
        .info-box i {
            color: #1976d2;
            margin-right: 8px;
        }
        
        .field-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .field-group label .required {
            color: #e74c3c;
            margin-right: 4px;
        }
        
        .field-group input,
        .field-group select,
        .field-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: inherit;
            background-color: #ffffff;
            color: #333;
        }
        
        /* 可用狀態 - 明顯的視覺提示 */
        .field-group input:not(:disabled),
        .field-group select:not(:disabled),
        .field-group textarea:not(:disabled) {
            background-color: #ffffff;
            border-color: #d0d0d0;
            box-shadow: 0 0 0 1px rgba(102, 126, 234, 0.1);
        }
        
        .field-group input:not(:disabled):hover,
        .field-group select:not(:disabled):hover,
        .field-group textarea:not(:disabled):hover {
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.15);
        }
        
        .field-group input:focus,
        .field-group select:focus,
        .field-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
            background-color: #ffffff;
        }
        
        /* 禁用狀態 - 明顯的灰色提示 */
        .field-group input:disabled,
        .field-group select:disabled,
        .field-group textarea:disabled {
            background-color: #f5f5f5;
            border-color: #d0d0d0;
            color: #999;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .field-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .submit-btn {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-top: 20px;
        }
        
        /* 啟用狀態 - 藍色 */
        .submit-btn:not(:disabled) {
            background:  #956dbd 100%;
            color: white;
            cursor: pointer;
            opacity: 1;
        }
        
        .submit-btn:not(:disabled):hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        /* 禁用狀態 - 灰色 */
        .submit-btn:disabled {
            background: #cccccc;
            color: #999999;
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .submit-btn:disabled:hover {
            transform: none;
            box-shadow: none;
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .recruitment-container .header {
            background: #667eea !important;
            border-radius: 10px !important;
            color: white !important;
            padding: 40px !important;
            text-align: center !important;
            margin-bottom: 30px !important;
            box-shadow: none !important;
        }
        
        .recruitment-container .header h1 {
            margin: 0 !important;
            font-size: 2.5rem !important;
            font-weight: 700 !important;
            color: white !important;
            text-shadow: none !important;
        }
        
        .recruitment-container .header h1 i {
            margin-right: 10px !important;
            color: white !important;
        }
        
        .recruitment-container .header .subtitle {
            margin: 10px 0 0 0 !important;
            font-size: 1.1rem !important;
            opacity: 0.9 !important;
            color: white !important;
        }
        
    </style>
</head>
<body>
	<?php include("share/header.php"); ?>
<main>
    <div class="recruitment-container">
        <div class="header" style="background: #667eea !important; color: white !important; padding: 40px !important; border-radius: 10px !important; text-align: center !important; margin-bottom: 30px !important;">
            <h1 style="color: white !important; font-size: 2.5rem !important; font-weight: 700 !important; margin: 0 !important;"><i class="fas fa-graduation-cap" style="color: white !important; margin-right: 10px !important;"></i> 國中學校招生報名網頁</h1>
        </div>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <strong>申請須知：</strong>請填寫完整資料，我們將在收到申請後 3-5 個工作天內與您聯繫。您也可以使用申請時填寫的 Email 搜尋查詢您的申請狀態。
        </div>

        <!-- 搜尋申請資料功能 -->
        <div class="form-container" style="margin-bottom: 20px;">
            <div class="form-section">
                <h3><i class="fas fa-search"></i> 搜尋/查詢申請資料</h3>
                <?php if ($action === 'search' && $search_email !== ''): ?>
                    <div style="margin-bottom: 15px; padding: 12px; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px;">
                        <p style="margin: 0; color: #1976d2; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-info-circle"></i>
                            <span>正在搜尋：<strong><?php echo htmlspecialchars($search_email, ENT_QUOTES, 'UTF-8'); ?></strong></span>
                        </p>
                    </div>
                <?php endif; ?>
                <form method="get" id="searchForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" style="display: flex; gap: 10px; align-items: flex-end;">
                    <input type="hidden" name="action" value="search">
                    <div class="field-group" style="width: 50%;">
                        <label>請輸入 Email 搜尋申請資料</label>
                        <input type="email" name="email" id="search_email_input" placeholder="請輸入您申請時使用的 Email" 
                               value="<?php echo htmlspecialchars($search_email, ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <button type="submit" id="search_submit_btn" style="background: #28a745; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; width: 40%; margin-bottom: 1px; transition: all 0.3s ease;">
                        <i class="fas fa-search"></i> 搜尋
                    </button>
                </form>
                
                <?php if (count($application_list) > 0): ?>
                    <div style="margin-top: 20px;">
                        <p style="margin: 0 0 15px 0; font-weight: 600; color: #2e7d32; font-size: 16px;">
                            <i class="fas fa-check-circle"></i> 找到 <?php echo count($application_list); ?> 筆申請資料
                        </p>
                        
                        <!-- 申請資料列表 -->
                        <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px; background: #fff;">
                            <?php foreach ($application_list as $app):
                                // 為每個申請讀取聯絡人資訊
                                $app_contact_name = '';
                                $app_contact_email = '';
                                $app_contact_phone = '';
                                if (!empty($app['contacts_id'])) {
                                    try {
                                        $stmt = $pdo->prepare("SELECT contact_name, email, phone, title FROM schools_contacts WHERE id = ? LIMIT 1");
                                        $stmt->execute([$app['contacts_id']]);
                                        $app_contact = $stmt->fetch(PDO::FETCH_ASSOC);
                                        if ($app_contact) {
                                            $app_contact_name = $app_contact['contact_name'] ?? '';
                                            $app_contact_email = $app_contact['email'] ?? '';
                                            $app_contact_phone = $app_contact['phone'] ?? '';
                                        }
                                    } catch (PDOException $e) {
                                        // 忽略錯誤
                                    }
                                } 
                                $is_selected = ($application_data && $application_data['id'] == $app['id']);
                                // 狀態對應表（支援多種狀態格式）
                                $status_code = $app['status'] ?? '';
                                $status_text_map = [
                                    'PE' => ['text' => '待審核', 'color' => '#ff9800'],
                                    'pending' => ['text' => '待審核', 'color' => '#ff9800'],
                                    'AP' => ['text' => '已通過', 'color' => '#28a745'],
                                    'approved' => ['text' => '已通過', 'color' => '#28a745'],
                                    'RE' => ['text' => '未通過', 'color' => '#dc3545'],
                                    'rejected' => ['text' => '未通過', 'color' => '#dc3545'],
                                    'AD' => ['text' => '備取', 'color' => '#17a2b8'],
                                    'completed' => ['text' => '已完成', 'color' => '#17a2b8']
                                ];
                                $status = $status_text_map[$status_code] ?? ['text' => $status_code ?: '待審核', 'color' => '#6c757d'];
                            ?>
                                <div style="padding: 15px; border-bottom: 1px solid #eee; <?php echo $is_selected ? 'background: #e3f2fd; border-left: 4px solid #2196f3;' : ''; ?>">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                        <div style="flex: 1;">
                                            <div style="font-weight: 600; color: #333; margin-bottom: 5px;">
                                                申請編號：<?php echo $app['id']; ?>
                                                <?php if ($is_selected): ?>
                                                    <span style="background: #2196f3; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; margin-left: 8px;">已選取</span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-size: 14px; color: #666; margin-bottom: 5px;">
                                                <i class="fas fa-building"></i> 
                                                <?php 
                                                // 查詢學校名稱用於顯示
                                                $display_name = $app['school_code'] ?? '';
                                                if (!empty($app['school_code'])) {
                                                    try {
                                                        $stmt = $pdo->prepare("SELECT name FROM school_data WHERE school_code = ? LIMIT 1");
                                                        $stmt->execute([$app['school_code']]);
                                                        $school_data = $stmt->fetch(PDO::FETCH_ASSOC);
                                                        if ($school_data && !empty($school_data['name'])) {
                                                            $display_name = $school_data['name'];
                                                        }
                                                    } catch (PDOException $e) {
                                                        // 忽略錯誤，使用代號
                                                    }
                                                }
                                                echo htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8'); 
                                                ?>
                                            </div>
                                            <div style="font-size: 13px; color: #888;">
                                                <i class="fas fa-calendar"></i> <?php echo date('Y-m-d H:i', strtotime($app['created_at'])); ?>
                                                <?php if ($app['preferred_date']): ?>
                                                    | <i class="fas fa-clock"></i> 首選日期：<?php echo htmlspecialchars($app['preferred_date'], ENT_QUOTES, 'UTF-8'); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <span style="background: <?php echo $status['color']; ?>; color: white; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                                <?php echo $status['text']; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                                        <button type="button" onclick="selectApplication(<?php echo $app['id']; ?>)" 
                                                style="background: #667eea; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                            <i class="fas fa-edit"></i> <?php echo $is_selected ? '已選取' : '選取此筆'; ?>
                                        </button>
                                        <?php if ($is_selected): ?>
                                            <button type="button" onclick="loadApplicationData()" 
                                                    style="background: #28a745; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                                <i class="fas fa-edit"></i> 載入資料到表單
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php elseif ($search_email !== ''): ?>
                    <div style="margin-top: 20px; padding: 20px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 24px; color: #ff9800;"></i>
                            <div>
                                <p style="margin: 0; color: #856404; font-weight: 600; font-size: 16px;">
                                    找不到該 Email 的申請資料
                                </p>
                                <p style="margin: 5px 0 0 0; color: #856404; font-size: 13px;">
                                    搜尋的 Email: <strong><?php echo htmlspecialchars($search_email, ENT_QUOTES, 'UTF-8'); ?></strong>
                                </p>
                            </div>
                        </div>
                        <?php if (!empty($search_debug_info)): ?>
                            <details open style="margin-top: 15px; border-top: 1px solid #ffc107; padding-top: 15px;">
                                <summary style="cursor: pointer; color: #856404; font-weight: 600; user-select: none; padding: 10px; background: #fff; border-radius: 4px; border: 1px solid #ffc107; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-bug"></i> 
                                    <span>查看詳細診斷信息（點擊展開/收起）</span>
                                    <i class="fas fa-chevron-down" style="margin-left: auto; font-size: 12px;"></i>
                                </summary>
                                <div style="margin-top: 15px; padding: 15px; background: #fff; border-radius: 4px; font-family: 'Courier New', monospace; font-size: 13px; color: #333; white-space: pre-line; border: 1px solid #ddd; max-height: 500px; overflow-y: auto; line-height: 1.6;">
                                    <?php echo htmlspecialchars($search_debug_info, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </details>
                        <?php else: ?>
                            <div style="margin-top: 15px; padding: 10px; background: #fff; border-radius: 4px; border: 1px solid #ddd;">
                                <p style="margin: 0; color: #666; font-size: 13px;">
                                    <i class="fas fa-info-circle"></i> 提示：請確認您輸入的 Email 與申請時填寫的 Email 完全一致（包括大小寫）。
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($result_message !== ''): ?>
            <div class="message <?php echo $result_type; ?>" id="result_message" style="padding: 15px 20px; margin-bottom: 20px; border-radius: 8px; display: flex; align-items: center; gap: 10px; font-size: 15px; font-weight: 500; animation: slideDown 0.3s ease; <?php if ($result_type === 'error'): ?>background: #ffebee; border: 1px solid #ffcdd2; border-left: 4px solid #d32f2f; color: #c62828;<?php else: ?>background: #e8f5e9; border: 1px solid #c8e6c9; border-left: 4px solid #2e7d32; color: #1b5e20;<?php endif; ?>">
                <i class="fas fa-<?php echo ($result_type === 'success') ? 'check-circle' : 'exclamation-triangle'; ?>" style="font-size: 20px;"></i>
                <div style="flex: 1;">
                    <?php echo nl2br(htmlspecialchars($result_message, ENT_QUOTES, 'UTF-8')); ?>
                </div>
                <button onclick="document.getElementById('result_message').style.display='none'" style="
                    background: none;
                    border: none;
                    color: inherit;
                    cursor: pointer;
                    font-size: 18px;
                    padding: 0;
                    width: 24px;
                    height: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    opacity: 0.7;
                " title="關閉">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <script>
                // 自動滾動到錯誤訊息
                document.addEventListener('DOMContentLoaded', function() {
                    const resultMsg = document.getElementById('result_message');
                    if (resultMsg) {
                        resultMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        // 如果是錯誤訊息，3秒後高亮顯示
                        if (resultMsg.classList.contains('error')) {
                            setTimeout(function() {
                                resultMsg.style.boxShadow = '0 0 20px rgba(211, 47, 47, 0.3)';
                                setTimeout(function() {
                                    resultMsg.style.boxShadow = '';
                                }, 2000);
                            }, 500);
                        }
                    }
                });
            </script>
        <?php endif; ?>
        
        <?php
        // 檢查申請狀態並顯示相應提示
        $application_status = $application_data['status'] ?? null;
        $admin_remarks = $application_data['admin_remarks'] ?? '';
        $is_approved = ($application_status === 'AP' || $application_status === 'approved');
        $is_rejected = ($application_status === 'RE' || $application_status === 'rejected');
        $is_pending = ($application_status === 'PE' || $application_status === 'pending' || empty($application_status));
        
        // 用於表單欄位的 disabled 屬性
        $form_disabled = $is_approved ? 'disabled' : '';
        $form_readonly = $is_approved ? 'readonly' : '';
        ?>
        
        <?php if ($application_data && ($is_approved || $is_rejected)): ?>
            <div class="form-container" style="margin-bottom: 20px;">
                <?php if ($is_approved): ?>
                    <div style="background: #d4edda; border: 1px solid #c3e6cb; border-left: 4px solid #28a745; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <i class="fas fa-check-circle" style="color: #28a745; font-size: 24px;"></i>
                            <h3 style="margin: 0; color: #155724; font-size: 18px; font-weight: 600;">✅ 申請已通過審核</h3>
                        </div>
                        <p style="margin: 10px 0 0 0; color: #155724; font-size: 14px;">
                            您的申請已經通過審核，目前無法修改資料。如需變更，請聯繫管理員。
                        </p>
                        <?php if (!empty($admin_remarks)): ?>
                            <div style="margin-top: 15px; padding: 12px; background: #f8f9fa; border-radius: 6px; border-left: 3px solid #28a745;">
                                <strong style="color: #155724; display: block; margin-bottom: 5px;">管理員備註：</strong>
                                <p style="margin: 0; color: #495057; white-space: pre-wrap;"><?php echo htmlspecialchars($admin_remarks, ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($is_rejected): ?>
                    <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-left: 4px solid #dc3545; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <i class="fas fa-times-circle" style="color: #dc3545; font-size: 24px;"></i>
                            <h3 style="margin: 0; color: #721c24; font-size: 18px; font-weight: 600;">❌ 申請未通過審核</h3>
                        </div>
                        <p style="margin: 10px 0 0 0; color: #721c24; font-size: 14px;">
                            您的申請未通過審核，請修改資料後重新提交。修改後，申請狀態將變更為「待審核」。
                        </p>
                        <?php if (!empty($admin_remarks)): ?>
                            <div style="margin-top: 15px; padding: 12px; background: #fff; border-radius: 6px; border-left: 3px solid #dc3545;">
                                <strong style="color: #721c24; display: block; margin-bottom: 5px;">管理員備註：</strong>
                                <p style="margin: 0; color: #495057; white-space: pre-wrap;"><?php echo htmlspecialchars($admin_remarks, ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="post" id="recruitmentForm"<?php if ($is_approved): ?> onsubmit="event.preventDefault(); alert('申請已通過審核，無法修改資料。'); return false;"<?php endif; ?>>
                <input type="hidden" name="action" id="form_action" value="submit">
                <input type="hidden" name="application_id" id="application_id" value="<?php echo $application_data ? $application_data['id'] : '0'; ?>">
                
                <!-- 學校基本資料 -->
                <div class="form-section">
                    <h3><i class="fas fa-building"></i> 學校基本資料</h3>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span> 學校名稱：</label>
                            <div class="modern-search-container">
                                <div class="search-input-wrapper">
                                    <input type="hidden" id="school_code" name="school_code" value="<?php 
                                           $school_code_value = '';
                                           if ($application_data && isset($application_data['school_code'])) {
                                               $school_code_value = htmlspecialchars($application_data['school_code'], ENT_QUOTES, 'UTF-8');
                                           } elseif (isset($_POST['school_code'])) {
                                               $school_code_value = htmlspecialchars($_POST['school_code'], ENT_QUOTES, 'UTF-8');
                                           }
                                           echo $school_code_value;
                                           ?>" />
                                    <input type="hidden" id="school_name_display" name="school_name_display" value="<?php 
                                           $school_name_display_hidden = '';
                                           if ($application_data && isset($application_data['school_name_display'])) {
                                               $school_name_display_hidden = htmlspecialchars($application_data['school_name_display'], ENT_QUOTES, 'UTF-8');
                                           } elseif (isset($_POST['school_name_display'])) {
                                               $school_name_display_hidden = htmlspecialchars($_POST['school_name_display'], ENT_QUOTES, 'UTF-8');
                                           }
                                           echo $school_name_display_hidden;
                                           ?>" />
                                    <input type="text" id="school_name" placeholder="請輸入學校名稱..." autocomplete="off" <?php echo $form_disabled; ?>
                                           value="<?php 
                                           $school_name_display = '';
                                           if ($application_data && isset($application_data['school_name_display'])) {
                                               $school_name_display = htmlspecialchars($application_data['school_name_display'], ENT_QUOTES, 'UTF-8');
                                           } elseif (isset($_POST['school_name_display'])) {
                                               $school_name_display = htmlspecialchars($_POST['school_name_display'], ENT_QUOTES, 'UTF-8');
                                           } elseif (isset($_POST['school_code']) && !empty($_POST['school_code'])) {
                                               // 如果只有 school_code，嘗試從資料庫查詢學校名稱
                                               try {
                                                   $stmt = $pdo->prepare("SELECT name, city, district FROM school_data WHERE school_code = ? AND is_active = 1 LIMIT 1");
                                                   $stmt->execute([$_POST['school_code']]);
                                                   $school_data = $stmt->fetch(PDO::FETCH_ASSOC);
                                                   if ($school_data && !empty($school_data['name'])) {
                                                       $school_name_display = $school_data['name'] . ' (' . $school_data['city'] . $school_data['district'] . ')';
                                                       $school_name_display = htmlspecialchars($school_name_display, ENT_QUOTES, 'UTF-8');
                                                   }
                                               } catch (PDOException $e) {
                                                   // 忽略錯誤
                                               }
                                           }
                                           echo $school_name_display;
                                           ?>" />
                                    <div class="search-icon">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <div class="clear-btn" id="clearSchoolSearch" style="display: none;">
                                        <i class="fas fa-times"></i>
                                    </div>
                                </div>
                                <div id="schoolResults" class="modern-search-results"></div>
                            </div>
                            <div class="help-text">
                                <i class="fas fa-info-circle"></i> 輸入學校名稱即可即時搜尋，請從搜尋結果中選擇學校（不能自行輸入）
                            </div>
                            <div id="school_name_error" class="field-error" style="display: none; color: #d32f2f; font-size: 13px; margin-top: 8px; padding: 8px 12px; background-color: #ffebee; border-left: 3px solid #d32f2f; border-radius: 4px; animation: slideDown 0.3s ease;">
                                <i class="fas fa-exclamation-circle"></i> <span id="school_name_error_text">請從系統提供的選項中選擇學校，不能自行輸入</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-group">
                            <label>學校地址</label>
                            <input type="text" name="school_address" id="school_address" placeholder="將根據學校資訊自動產生" 
                                   readonly
                                   style="background-color: #f5f5f5; cursor: not-allowed;"
                                   value="<?php 
                                   $school_address_value = '';
                                   if ($application_data) {
                                       $school_address_value = htmlspecialchars($application_data['school_address'] ?? '', ENT_QUOTES, 'UTF-8');
                                   } elseif (isset($_POST['school_address'])) {
                                       $school_address_value = htmlspecialchars($_POST['school_address'], ENT_QUOTES, 'UTF-8');
                                   }
                                   echo $school_address_value;
                                   ?>" />
                            <small style="color: #666; margin-top: 5px; display: block;">
                                <i class="fas fa-info-circle"></i> 地址會根據選擇的學校自動填入，無法手動修改
                            </small>
                        </div>
                    </div>
                    <div id="city_school_mismatch_error" class="field-error" style="display: none; color: #d32f2f; font-size: 13px; margin-top: 8px; padding: 8px 12px; background-color: #ffebee; border-left: 3px solid #d32f2f; border-radius: 4px; animation: slideDown 0.3s ease;">
                        <i class="fas fa-exclamation-circle"></i> <span id="city_school_mismatch_error_text">就讀縣市與選擇的學校所在縣市不一致，系統已自動更新為正確的縣市</span>
                    </div>
                    <div id="district_school_mismatch_error" class="field-error" style="display: none; color: #d32f2f; font-size: 13px; margin-top: 8px; padding: 8px 12px; background-color: #ffebee; border-left: 3px solid #d32f2f; border-radius: 4px; animation: slideDown 0.3s ease;">
                        <i class="fas fa-exclamation-circle"></i> <span id="district_school_mismatch_error_text">區/鄉鎮市與選擇的學校所在區/鄉鎮市不一致，系統已自動更新為正確的區/鄉鎮市</span>
                    </div>
                </div>

                <!-- 聯絡人資訊 -->
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> 聯絡人資訊</h3>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span> 聯絡人姓名</label>
                            <input type="text" name="contact_name" placeholder="請輸入聯絡人姓名" required <?php echo $form_disabled; ?>
                                   value="<?php 
                                   $contact_name_value = '';
                                   if ($application_data) {
                                       $contact_name_value = htmlspecialchars($application_data['contact_name'], ENT_QUOTES, 'UTF-8');
                                   } elseif (isset($_POST['contact_name'])) {
                                       $contact_name_value = htmlspecialchars($_POST['contact_name'], ENT_QUOTES, 'UTF-8');
                                   }
                                   echo $contact_name_value;
                                   ?>" />
                        </div>
                        <div class="field-group">
                            <label><span class="required">*</span> 職稱</label>
                            <input type="text" id="contact_title" name="contact_title" placeholder="例如：教務主任、輔導主任（選填）" <?php echo $form_disabled; ?>
                                   value="<?php 
                                   $contact_title_value = '';
                                   if ($application_data) {
                                       $contact_title_value = htmlspecialchars($application_data['contact_title'] ?? '', ENT_QUOTES, 'UTF-8');
                                   } elseif (isset($_POST['contact_title'])) {
                                       $contact_title_value = htmlspecialchars($_POST['contact_title'], ENT_QUOTES, 'UTF-8');
                                   }
                                   echo $contact_title_value;
                                   ?>" />
                            <small style="color: #666; margin-top: 5px; display: block; font-size: 12px;">
                                <i class="fas fa-info-circle"></i> 此欄位為選填，如無職稱可留空
                            </small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span> 聯絡人電話</label>
                            <input type="tel" id="contact_phone" name="contact_phone" pattern="[0-9]{10}" maxlength="10" placeholder="請輸入電話號碼" required 
                                   value="<?php 
                                   $contact_phone_value = '';
                                   if ($application_data) {
                                       $contact_phone_value = htmlspecialchars($application_data['contact_phone'] ?? '', ENT_QUOTES, 'UTF-8');
                                   } elseif (isset($_POST['contact_phone'])) {
                                       $contact_phone_value = htmlspecialchars($_POST['contact_phone'], ENT_QUOTES, 'UTF-8');
                                   }
                                   echo $contact_phone_value;
                                   ?>" />
                            <small class="phone-hint" style="display: none; color: #d32f2f; font-size: 12px; margin-top: 4px;">電話號碼輸入錯誤</small>
                        </div>
                        <div class="field-group">
                            <label><span class="required">*</span> 聯絡人 Email</label>
                            <input type="email" name="contact_email" placeholder="例如：contact@school.edu.tw" required <?php echo $form_disabled; ?>
                                   value="<?php 
                                   $contact_email_value = '';
                                   if ($application_data) {
                                       $contact_email_value = htmlspecialchars($application_data['contact_email'], ENT_QUOTES, 'UTF-8');
                                   } elseif (isset($_POST['contact_email'])) {
                                       $contact_email_value = htmlspecialchars($_POST['contact_email'], ENT_QUOTES, 'UTF-8');
                                   }
                                   echo $contact_email_value;
                                   ?>" />
                        </div>
                    </div>
                </div>

                <!-- 招生安排資料 -->
                <div class="form-section">
                    <h3><i class="fas fa-calendar-check"></i> 招生安排資料</h3>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span><i class="fas fa-calendar-alt" style="color:#667eea;"></i> 首選日期</label>
                            <input type="date" name="preferred_date" required min="<?php echo date('Y-m-d'); ?>"
                                   value="<?php 
                                   $preferred_date_value = '';
                                   if ($application_data) {
                                       $preferred_date_value = $application_data['preferred_date'];
                                   } elseif (isset($_POST['preferred_date'])) {
                                       $preferred_date_value = htmlspecialchars($_POST['preferred_date'], ENT_QUOTES, 'UTF-8');
                                   }
                                   echo $preferred_date_value;
                                   ?>" />
                            <small style="color: #666; margin-top: 5px; display: block;">
                                <i class="fas fa-info-circle"></i> 請選擇今天或未來的日期
                            </small>
                        </div>
                        <div class="field-group">
                            <label><span class="required">*</span><i class="fas fa-clock" style="color:#667eea;"></i> 首選時段</label>
                            <select name="preferred_time" required <?php echo $form_disabled; ?>>
                                <option value="">請選擇</option>
                                <?php
                                $preferred_time_value = '';
                                if ($application_data) {
                                    $preferred_time_value = $application_data['preferred_time'];
                                } elseif (isset($_POST['preferred_time'])) {
                                    $preferred_time_value = $_POST['preferred_time'];
                                }
                                ?>
                                <option value="上午" <?php echo ($preferred_time_value === '上午') ? 'selected' : ''; ?>>上午</option>
                                <option value="下午" <?php echo ($preferred_time_value === '下午') ? 'selected' : ''; ?>>下午</option>
                                <option value="晚上" <?php echo ($preferred_time_value === '晚上') ? 'selected' : ''; ?>>晚上</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span> 目標年級</label>
                            <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 8px;">
                                <?php
                                // 確定已選的年級
                                $checked_grades = [];
                                if (!empty($selected_target_grades)) {
                                    $checked_grades = $selected_target_grades;
                                } elseif (isset($_POST['target_grades']) && is_array($_POST['target_grades'])) {
                                    $checked_grades = $_POST['target_grades'];
                                }
                                
                                foreach ($target_grades_options as $option):
                                    $is_checked = in_array($option['code'], $checked_grades);
                                ?>
                                    <label style="display: flex; align-items: center; cursor: pointer; padding: 8px 15px; border: 2px solid #e0e0e0; border-radius: 8px; transition: all 0.3s; background: <?php echo $is_checked ? '#e3f2fd' : '#fff'; ?>; border-color: <?php echo $is_checked ? '#667eea' : '#e0e0e0'; ?>;">
                                        <input type="checkbox" name="target_grades[]" value="<?php echo htmlspecialchars($option['code'], ENT_QUOTES, 'UTF-8'); ?>" 
                                               <?php echo $is_checked ? 'checked' : ''; ?> 
                                               style="margin-right: 8px; width: 18px; height: 18px; cursor: pointer;"
                                               onchange="this.parentElement.style.background = this.checked ? '#e3f2fd' : '#fff'; this.parentElement.style.borderColor = this.checked ? '#667eea' : '#e0e0e0';">
                                        <span style="font-size: 15px; color: #333;"><?php echo htmlspecialchars($option['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <small style="color: #666; margin-top: 8px; display: block;">
                                <i class="fas fa-info-circle"></i> 可複選多個年級
                            </small>
                        </div>
                        <div class="field-group">
                            <label><span class="required">*</span> 預期參與學生數</label>
                            <input type="number" name="expected_students" min="1" placeholder="例如：100" required <?php echo $form_disabled; ?>
                                   value="<?php 
                                   $expected_students_value = '';
                                   if ($application_data) {
                                       $expected_students_value = $application_data['expected_students'];
                                   } elseif (isset($_POST['expected_students'])) {
                                       $expected_students_value = htmlspecialchars($_POST['expected_students'], ENT_QUOTES, 'UTF-8');
                                   }
                                   echo $expected_students_value;
                                   ?>" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-group">
                            <label>場地類型</label>
                            <select name="venue_type">
                                <option value="">請選擇（選填）</option>
                                <?php
                                $venue_type_value = '';
                                if ($application_data) {
                                    $venue_type_value = $application_data['venue_type'];
                                } elseif (isset($_POST['venue_type'])) {
                                    $venue_type_value = $_POST['venue_type'];
                                }
                                
                                foreach ($venue_options as $venue):
                                ?>
                                    <option value="<?php echo htmlspecialchars($venue['code'], ENT_QUOTES, 'UTF-8'); ?>" 
                                            <?php echo ($venue_type_value === $venue['code']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($venue['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-group">
                            <label>特殊需求</label>
                            <textarea name="special_requirements" placeholder="請描述任何特殊需求或注意事項（選填）" <?php echo $form_disabled; ?>><?php 
                            $special_requirements_value = '';
                            if ($application_data) {
                                $special_requirements_value = htmlspecialchars($application_data['special_requirements'] ?? '', ENT_QUOTES, 'UTF-8');
                            } elseif (isset($_POST['special_requirements'])) {
                                $special_requirements_value = htmlspecialchars($_POST['special_requirements'], ENT_QUOTES, 'UTF-8');
                            }
                            echo $special_requirements_value;
                            ?></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-group">
                            <label>備註</label>
                            <textarea name="remarks" placeholder="其他需要補充的資訊（選填）" <?php echo $form_disabled; ?>><?php 
                            $remarks_value = '';
                            if ($application_data) {
                                $remarks_value = htmlspecialchars($application_data['remarks'] ?? '', ENT_QUOTES, 'UTF-8');
                            } elseif (isset($_POST['remarks'])) {
                                $remarks_value = htmlspecialchars($_POST['remarks'], ENT_QUOTES, 'UTF-8');
                            }
                            echo $remarks_value;
                            ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- 驗證碼 -->
                <div class="form-section">
                    <h3> <span class="required">*</span> <i class="fas fa-shield-alt"></i> 驗證碼 </h3>
                    <div class="captcha-section" style="display: flex; align-items: center; gap: 10px; margin: 15px 0; flex-wrap: wrap;">
                        <input type="text" name="captcha" id="captchaInput" placeholder="請輸入驗證碼" maxlength="6" required autocomplete="off" <?php echo $form_disabled; ?> style="flex: 1; min-width: 150px; padding: 12px; border: 2px solid #d0d0d0; border-radius: 8px; font-size: 15px; background-color: #ffffff; color: #333; transition: all 0.3s; text-transform: uppercase;" oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '')">
                        <img src="captcha_image.php" id="captchaImage" alt="驗證碼" onclick="refreshCaptcha()" style="height: 50px; width: 150px; border: 2px solid #e0e0e0; border-radius: 8px; cursor: pointer;" title="點擊刷新驗證碼" onerror="this.onerror=null; this.src='captcha_image.php?t='+Date.now();">
                        <button type="button" onclick="refreshCaptcha()" style="padding: 12px 20px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                            <i class="fas fa-sync-alt"></i> 刷新
                        </button>
                    </div>
                    <small style="color: #666; margin-top: 8px; display: block;">
                        <i class="fas fa-info-circle"></i> 請輸入圖片中顯示的字母和數字（不區分大小寫）
                    </small>
                </div>

                <button type="submit" class="submit-btn" id="submit_btn" <?php echo $form_disabled; ?>>
                    <i class="fas fa-paper-plane"></i> <span id="submit_btn_text"><?php echo $is_approved ? '申請已通過，無法修改' : '提交申請'; ?></span>
                </button>
            </form>
        </div>
    </div>
</main>
    <?php include("share/footer.php"); ?>
    
<script>
// 檢查必填欄位並更新提交按鈕狀態
function checkRequiredFields() {
    const submitBtn = document.getElementById('submit_btn');
    if (submitBtn) {
        // 如果申請已通過，保持按鈕禁用狀態
        const isApproved = submitBtn.hasAttribute('data-approved') || submitBtn.disabled;
        if (!isApproved) {
            submitBtn.disabled = false;
        }
    }
}

// 如果申請已通過，禁用所有表單欄位
<?php if ($is_approved): ?>
document.addEventListener('DOMContentLoaded', function() {
    // 禁用所有輸入欄位
    const form = document.getElementById('recruitmentForm');
    if (form) {
        const inputs = form.querySelectorAll('input, textarea, select, button');
        inputs.forEach(function(input) {
            if (input.type !== 'hidden' && input.id !== 'captchaInput' && !input.onclick) {
                input.disabled = true;
                input.style.cursor = 'not-allowed';
                input.style.opacity = '0.6';
            }
        });
        
        // 特別確保送出按鈕被禁用
        const submitBtn = document.getElementById('submit_btn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.style.cursor = 'not-allowed';
            submitBtn.style.opacity = '0.6';
            submitBtn.setAttribute('data-approved', 'true');
        }
        
        // 禁用驗證碼相關元素
        const captchaInput = document.getElementById('captchaInput');
        const captchaImage = document.getElementById('captchaImage');
        if (captchaInput) {
            captchaInput.disabled = true;
            captchaInput.style.cursor = 'not-allowed';
        }
        if (captchaImage) {
            captchaImage.style.pointerEvents = 'none';
            captchaImage.style.opacity = '0.5';
            captchaImage.style.cursor = 'not-allowed';
        }
    }
    
    // 禁用學校搜尋功能
    const schoolNameInput = document.getElementById('school_name');
    if (schoolNameInput) {
        schoolNameInput.disabled = true;
        schoolNameInput.style.cursor = 'not-allowed';
    }
    
    // 禁用所有表單相關的按鈕（不包括搜尋按鈕）
    const formButtons = form.querySelectorAll('button');
    formButtons.forEach(function(btn) {
        if (btn.id !== 'search_submit_btn') { // 保留搜尋按鈕可用
            btn.disabled = true;
            btn.style.cursor = 'not-allowed';
            btn.style.opacity = '0.6';
        }
    });
    
    // 禁用所有輸入欄位、選擇框和文字區域
    const allFormFields = form.querySelectorAll('input:not([type="hidden"]), textarea, select');
    allFormFields.forEach(function(field) {
        field.disabled = true;
        field.style.cursor = 'not-allowed';
        field.style.opacity = '0.6';
    });
    
    // 防止表單提交
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            alert('申請已通過審核，無法修改資料。');
            return false;
        });
    }
});
<?php endif; ?>

// 搜尋表單處理
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('searchForm');
    const searchSubmitBtn = document.getElementById('search_submit_btn');
    
    if (searchForm && searchSubmitBtn) {
        // 在表單提交時顯示載入狀態
        searchForm.addEventListener('submit', function(e) {
            const emailInput = document.getElementById('search_email_input');
            if (emailInput && emailInput.value.trim() === '') {
                e.preventDefault();
                alert('請輸入 Email 進行搜尋');
                emailInput.focus();
                return false;
            }
            
            // 顯示載入狀態
            searchSubmitBtn.disabled = true;
            searchSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 搜尋中...';
            searchSubmitBtn.style.opacity = '0.7';
            searchSubmitBtn.style.cursor = 'wait';
        });
        
        // 如果有診斷信息，自動展開並滾動到該位置
        const formSection = searchForm.closest('.form-section');
        if (formSection) {
            const debugDetails = formSection.querySelector('details');
            if (debugDetails) {
                // 確保診斷信息是展開的
                debugDetails.open = true;
                // 延遲滾動，確保頁面已完全載入
                setTimeout(function() {
                    debugDetails.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 300);
            }
        }
    }
});

// 頁面載入時初始化輸入框視覺效果和學校搜尋
document.addEventListener('DOMContentLoaded', function() {
    // 電話號碼驗證功能（參考 cooperation_upload.php）
    const contactPhoneInput = document.getElementById('contact_phone');
    
    if (contactPhoneInput) {
        const phoneHint = contactPhoneInput.nextElementSibling;
        
        // 驗證函數
        function validatePhone() {
            const value = contactPhoneInput.value.trim();
            if (value.length > 0 && value.length !== 10) {
                // 顯示錯誤狀態
                if (phoneHint && phoneHint.classList.contains('phone-hint')) {
                    phoneHint.textContent = '電話號碼必須為10位數字';
                    phoneHint.style.display = 'block';
                }
                contactPhoneInput.style.borderColor = '#d32f2f';
                contactPhoneInput.style.borderWidth = '2px';
                contactPhoneInput.classList.add('phone-error');
            } else {
                // 清除錯誤狀態
                if (phoneHint && phoneHint.classList.contains('phone-hint')) {
                    phoneHint.style.display = 'none';
                }
                contactPhoneInput.style.borderColor = '';
                contactPhoneInput.style.borderWidth = '';
                contactPhoneInput.classList.remove('phone-error');
            }
        }
        
        // 只允許輸入數字
        contactPhoneInput.addEventListener('input', function(e) {
            // 移除非數字字元
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // 限制最大長度為10
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
            
            // 即時驗證
            validatePhone();
        });
        
        // 失去焦點時驗證
        contactPhoneInput.addEventListener('blur', function() {
            validatePhone();
        });
        
        // 獲得焦點時也檢查（處理初始值）
        contactPhoneInput.addEventListener('focus', function() {
            validatePhone();
        });
        
        // 頁面載入時檢查初始值
        validatePhone();
    }
    
    // 初始化縣市和區/鄉鎮市下拉選單
    const citySelect = document.getElementById('citySelect');
    const districtSelect = document.getElementById('districtSelect');
    
    if (citySelect) {
        // 縣市改變時更新區/鄉鎮市選項
        citySelect.addEventListener('change', function() {
            updateDistricts();
            validateCitySchoolMatch();
        });
        
        // 如果有預設值，初始化區/鄉鎮市選項
        if (citySelect.value) {
            updateDistricts();
        }
    }
    
    if (districtSelect) {
        // 區/鄉鎮市改變時更新學校地址並驗證
        districtSelect.addEventListener('change', function() {
            updateSchoolAddress();
            validateCitySchoolMatch();
        });
    }
    
    // 綁定學校搜尋事件
    const schoolSearchInput = document.getElementById('school_name');
    const clearSchoolBtn = document.getElementById('clearSchoolSearch');

    if (schoolSearchInput) {
        // 輸入事件（即時搜尋）
        schoolSearchInput.addEventListener('input', function() {
            performSchoolSearch();
            // 當下拉選單顯示時，不進行驗證（用戶還在輸入和選擇中）
        });

        // 失去焦點時立即驗證
        schoolSearchInput.addEventListener('blur', function() {
            clearTimeout(schoolSearchInput.validationTimeout);
            // 延遲一點驗證，讓點擊下拉選單項目的時間完成
            schoolSearchInput.validationTimeout = setTimeout(validateSchoolInputImmediate, 200);
        });
        
        // 當輸入框獲得焦點時，如果已有錯誤且下拉選單未顯示，保持顯示
        schoolSearchInput.addEventListener('focus', function() {
            const resultsDiv = document.getElementById('schoolResults');
            const value = this.value.trim();
            // 只有在下拉選單未顯示時才檢查錯誤
            if (value && !/^.+ \(.+\)$/.test(value) && 
                (!resultsDiv || !resultsDiv.classList.contains('show'))) {
                validateSchoolInput();
            }
        });

        // 清除按鈕事件
        if (clearSchoolBtn) {
            clearSchoolBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                clearSchoolSearch();
            });
        }

        // 鍵盤事件
        schoolSearchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                clearSchoolSearch();
            }
        });
        
        // 如果有初始值，顯示清除按鈕
        if (schoolSearchInput.value) {
            if (clearSchoolBtn) {
                clearSchoolBtn.style.display = 'block';
            }
        }
    }

    // 點擊其他地方隱藏搜尋結果
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.modern-search-container')) {
            const resultsDiv = document.getElementById('schoolResults');
            if (resultsDiv && resultsDiv.classList.contains('show')) {
                resultsDiv.classList.remove('show');
                // 當下拉選單隱藏時，驗證輸入
                setTimeout(validateSchoolInput, 100);
            }
        }
    });
    
    // 表單提交前驗證
    function validateFormBeforeSubmit(e) {
        console.log('=== 開始表單驗證 ===');
        
        const schoolCodeInput = document.getElementById('school_code');
        const schoolNameInput = document.getElementById('school_name');
        
        // 驗證學校代號是否存在
        if (schoolCodeInput) {
            const schoolCode = schoolCodeInput.value.trim();
            console.log('學校代號檢查:', schoolCode);
            if (!schoolCode) {
                e.preventDefault();
                console.error('❌ 學校代號為空');
                showSchoolError('請從系統提供的選項中選擇學校，不能自行輸入');
                if (schoolNameInput) {
                    schoolNameInput.focus();
                    schoolNameInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }
        } else {
            console.error('❌ 找不到 school_code 輸入框');
            e.preventDefault();
            alert('系統錯誤：找不到學校代號欄位，請重新整理頁面後再試');
            return false;
        }
        
        // 驗證學校名稱格式（可選，但建議檢查）
        if (schoolNameInput) {
            const schoolName = schoolNameInput.value.trim();
            console.log('學校名稱檢查:', schoolName);
            // 檢查格式是否為：學校名稱 (縣市區)
            const schoolFormatPattern = /^.+ \(.+\)$/;
            if (schoolName && !schoolFormatPattern.test(schoolName)) {
                console.warn('⚠️ 學校名稱格式不正確:', schoolName);
                // 不阻止提交，因為主要依賴 school_code
            }
        }
        
        // 驗證目標年級至少選擇一個
        const targetGradesCheckboxes = document.querySelectorAll('input[name="target_grades[]"]:checked');
        console.log('目標年級檢查:', targetGradesCheckboxes.length);
        if (targetGradesCheckboxes.length === 0) {
            e.preventDefault();
            alert('請至少選擇一個目標年級');
            const firstCheckbox = document.querySelector('input[name="target_grades[]"]');
            if (firstCheckbox) {
                firstCheckbox.focus();
                firstCheckbox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return false;
        }
        
        // 驗證其他必填欄位
        const requiredFields = [
            { id: 'contact_name', name: '聯絡人姓名' },
            { id: 'contact_phone', name: '聯絡人電話' },
            { id: 'contact_email', name: '聯絡人 Email' },
            { id: 'preferred_date', name: '首選日期' },
            { id: 'preferred_time', name: '首選時段' },
            { id: 'expected_students', name: '預期參與學生數' }
        ];
        
        for (const field of requiredFields) {
            const input = document.getElementById(field.id) || document.querySelector(`[name="${field.id}"]`);
            if (input) {
                const value = input.value ? input.value.trim() : '';
                console.log(`${field.name} 檢查:`, value ? '✓' : '✗');
                if (!value) {
                    e.preventDefault();
                    alert(`請填寫「${field.name}」`);
                    input.focus();
                    input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }
            } else {
                console.warn(`⚠️ 找不到 ${field.name} 欄位`);
            }
        }
        
        // 驗證驗證碼
        const captchaInput = document.getElementById('captchaInput');
        if (captchaInput) {
            const captcha = captchaInput.value.trim();
            console.log('驗證碼檢查:', captcha ? '✓' : '✗');
            if (!captcha) {
                e.preventDefault();
                alert('請輸入驗證碼');
                captchaInput.focus();
                captchaInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
        }
        
        console.log('✅ 表單驗證通過');
        return true;
    }
    
    // 為驗證碼輸入框添加視覺反饋
    const captchaInput = document.getElementById('captchaInput');
    if (captchaInput) {
        // 添加 hover 效果
        captchaInput.addEventListener('mouseenter', function() {
            if (!this.disabled) {
                this.style.borderColor = '#667eea';
                this.style.boxShadow = '0 0 0 2px rgba(102, 126, 234, 0.15)';
            }
        });
        captchaInput.addEventListener('mouseleave', function() {
            if (!this.disabled && document.activeElement !== this) {
                this.style.borderColor = '#d0d0d0';
                this.style.boxShadow = 'none';
            }
        });
        // 添加 focus 效果
        captchaInput.addEventListener('focus', function() {
            if (!this.disabled) {
                this.style.borderColor = '#667eea';
                this.style.boxShadow = '0 0 0 3px rgba(102, 126, 234, 0.2)';
            }
        });
        captchaInput.addEventListener('blur', function() {
            if (!this.disabled) {
                this.style.borderColor = '#d0d0d0';
                this.style.boxShadow = 'none';
            }
        });
    }
    
    // 確保提交按鈕可用（但如果申請已通過則保持禁用）
    const submitBtn = document.getElementById('submit_btn');
    if (submitBtn) {
        // 如果申請已通過，保持按鈕禁用狀態
        const isApproved = submitBtn.hasAttribute('data-approved');
        if (!isApproved) {
            submitBtn.disabled = false;
        }
    }
    
    // 監聽所有必填欄位的變化（保留用於其他用途，但不再禁用按鈕）
    const form = document.getElementById('recruitmentForm');
    if (form) {
        // 監聽 input、select 和 textarea 的變化
        form.addEventListener('input', checkRequiredFields);
        form.addEventListener('change', checkRequiredFields);
        
        // 初始檢查一次
        checkRequiredFields();
    }
    
    // 處理更新成功後的顯示，5秒後清除URL參數避免重新整理時重複顯示
    if (window.location.search.includes('updated=1')) {
        setTimeout(function() {
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }, 5000);
    }
    
    // 處理表單提交
    let isSubmitting = false;
    const formAction = document.getElementById('form_action');
    const submitBtnText = document.getElementById('submit_btn_text');
    
    if (form) {
        // 移除舊的 onsubmit 處理器，統一使用 addEventListener
        form.onsubmit = null;
        
        form.addEventListener('submit', function(e) {
            console.log('=== 開始表單驗證 ===');
            console.log('表單提交事件觸發');
            
            // 檢查是否已經驗證通過（避免無限循環）
            if (form.getAttribute('data-validated') === 'true') {
                console.log('表單已驗證通過，允許提交');
                form.removeAttribute('data-validated');
                return true; // 允許提交
            }
            
            console.log('阻止預設行為以進行驗證');
            // 先阻止預設提交行為，驗證通過後再允許提交
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            // 防止重複提交
            if (isSubmitting) {
                console.log('⚠️ 表單正在提交中，阻止重複提交');
                return false;
            }
            
            // 完整的必填欄位驗證
            const missingFields = [];
            
            // 1. 驗證學校名稱（school_code）
            const schoolCodeInput = document.getElementById('school_code');
            const schoolNameInput = document.getElementById('school_name');
            if (!schoolCodeInput || !schoolCodeInput.value || schoolCodeInput.value.trim() === '') {
                missingFields.push({ field: '學校名稱', element: schoolNameInput || schoolCodeInput });
                console.log('❌ 缺少：學校名稱 (school_code)');
            } else {
                console.log('✓ 學校名稱:', schoolCodeInput.value);
            }
            
            // 2. 驗證聯絡人姓名
            const contactNameInput = form.querySelector('input[name="contact_name"]');
            if (!contactNameInput || !contactNameInput.value || contactNameInput.value.trim() === '') {
                missingFields.push({ field: '聯絡人姓名', element: contactNameInput });
                console.log('❌ 缺少：聯絡人姓名');
            } else {
                console.log('✓ 聯絡人姓名:', contactNameInput.value);
            }
            
            // 3. 驗證聯絡人電話
            const contactPhoneInput = form.querySelector('input[name="contact_phone"]') || document.getElementById('contact_phone');
            if (!contactPhoneInput || !contactPhoneInput.value || contactPhoneInput.value.trim() === '') {
                missingFields.push({ field: '聯絡人電話', element: contactPhoneInput });
                console.log('❌ 缺少：聯絡人電話');
            } else {
                const phoneValue = contactPhoneInput.value.trim();
                // 驗證電話號碼必須為10位數字
                if (phoneValue.length !== 10 || !/^[0-9]{10}$/.test(phoneValue)) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    isSubmitting = false;
                    
                    const errorMessage = '聯絡人電話輸入錯誤';
                    alert(errorMessage);
                    
                    contactPhoneInput.focus();
                    contactPhoneInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    contactPhoneInput.style.borderColor = '#d32f2f';
                    contactPhoneInput.style.boxShadow = '0 0 0 2px rgba(211, 47, 47, 0.2)';
                    
                    // 顯示錯誤提示
                    const phoneHint = contactPhoneInput.nextElementSibling;
                    if (phoneHint && phoneHint.classList.contains('phone-hint')) {
                        phoneHint.textContent = errorMessage;
                        phoneHint.style.display = 'block';
                    }
                    
                    setTimeout(() => {
                        contactPhoneInput.style.borderColor = '';
                        contactPhoneInput.style.boxShadow = '';
                        if (phoneHint && phoneHint.classList.contains('phone-hint')) {
                            phoneHint.style.display = 'none';
                        }
                    }, 3000);
                    
                    console.log('❌ 聯絡人電話格式錯誤:', phoneValue);
                    return false;
                }
                console.log('✓ 聯絡人電話:', phoneValue);
            }
            
            // 4. 驗證聯絡人 Email
            const contactEmailInput = form.querySelector('input[name="contact_email"]');
            if (!contactEmailInput || !contactEmailInput.value || contactEmailInput.value.trim() === '') {
                missingFields.push({ field: '聯絡人 Email', element: contactEmailInput });
                console.log('❌ 缺少：聯絡人 Email');
            } else {
                console.log('✓ 聯絡人 Email:', contactEmailInput.value);
            }
            
            // 5. 驗證首選日期
            const preferredDateInput = form.querySelector('input[name="preferred_date"]');
            if (!preferredDateInput || !preferredDateInput.value || preferredDateInput.value.trim() === '') {
                missingFields.push({ field: '首選日期', element: preferredDateInput });
                console.log('❌ 缺少：首選日期');
            } else {
                console.log('✓ 首選日期:', preferredDateInput.value);
                // 驗證日期不能是過去的日期
                const selectedDate = new Date(preferredDateInput.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                selectedDate.setHours(0, 0, 0, 0);
                
                if (selectedDate < today) {
                    e.preventDefault();
                    alert('期望招生日期不能是過去的日期，請選擇今天或未來的日期。');
                    preferredDateInput.focus();
                    preferredDateInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    isSubmitting = false;
                    return false;
                }
            }
            
            // 6. 驗證首選時段
            const preferredTimeInput = form.querySelector('select[name="preferred_time"]');
            if (!preferredTimeInput || !preferredTimeInput.value || preferredTimeInput.value.trim() === '') {
                missingFields.push({ field: '首選時段', element: preferredTimeInput });
                console.log('❌ 缺少：首選時段');
            } else {
                console.log('✓ 首選時段:', preferredTimeInput.value);
            }
            
            // 7. 驗證目標年級（至少選擇一個）
            const targetGradesCheckboxes = form.querySelectorAll('input[name="target_grades[]"]:checked');
            if (!targetGradesCheckboxes || targetGradesCheckboxes.length === 0) {
                const firstCheckbox = form.querySelector('input[name="target_grades[]"]');
                missingFields.push({ field: '目標年級', element: firstCheckbox });
                console.log('❌ 缺少：目標年級（至少需選擇一個）');
            } else {
                console.log('✓ 目標年級:', Array.from(targetGradesCheckboxes).map(cb => cb.value).join(', '));
            }
            
            // 8. 驗證預期參與學生數
            const expectedStudentsInput = form.querySelector('input[name="expected_students"]');
            if (!expectedStudentsInput || !expectedStudentsInput.value || expectedStudentsInput.value.trim() === '') {
                missingFields.push({ field: '預期參與學生數', element: expectedStudentsInput });
                console.log('❌ 缺少：預期參與學生數');
            } else {
                const studentsNum = parseInt(expectedStudentsInput.value);
                if (isNaN(studentsNum) || studentsNum <= 0) {
                    e.preventDefault();
                    alert('預期參與學生數必須為大於 0 的數字。');
                    expectedStudentsInput.focus();
                    expectedStudentsInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    isSubmitting = false;
                    return false;
                }
                console.log('✓ 預期參與學生數:', expectedStudentsInput.value);
            }
            
            // 9. 驗證驗證碼
            const captchaInput = document.getElementById('captchaInput');
            if (!captchaInput || !captchaInput.value || captchaInput.value.trim() === '') {
                missingFields.push({ field: '驗證碼', element: captchaInput });
                console.log('❌ 缺少：驗證碼');
            } else {
                console.log('✓ 驗證碼:', captchaInput.value);
            }
            
            // 如果有缺少的欄位，阻止提交並顯示錯誤
            if (missingFields.length > 0) {
                e.preventDefault();
                isSubmitting = false;
                
                const missingFieldNames = missingFields.map(f => f.field).join('、');
                const errorMessage = `請填寫所有必填欄位。\n\n缺少的欄位：\n${missingFields.map(f => '• ' + f.field).join('\n')}`;
                
                alert(errorMessage);
                
                // 聚焦到第一個缺少的欄位
                const firstMissingField = missingFields[0].element;
                if (firstMissingField) {
                    firstMissingField.focus();
                    firstMissingField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // 添加視覺提示（紅色邊框）
                    firstMissingField.style.borderColor = '#d32f2f';
                    firstMissingField.style.boxShadow = '0 0 0 2px rgba(211, 47, 47, 0.2)';
                    setTimeout(() => {
                        firstMissingField.style.borderColor = '';
                        firstMissingField.style.boxShadow = '';
                    }, 3000);
                }
                
                console.log('❌ 表單驗證失敗，缺少欄位：', missingFieldNames);
                return false;
            }
            
            // 驗證縣市與學校是否一致
            if (!validateCitySchoolMatch()) {
                e.preventDefault();
                isSubmitting = false;
                const citySelect = document.getElementById('citySelect');
                if (citySelect) {
                    citySelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    citySelect.focus();
                }
                return false;
            }
            
            console.log('✅ 所有必填欄位驗證通過');
            
            if (formAction) {
                const action = formAction.value;
                if (action === 'update') {
                    if (!confirm('確定要更新申請資料嗎？')) {
                        e.preventDefault();
                        isSubmitting = false;
                        return false;
                    }
                }
            }
            
            // 設定提交狀態
            isSubmitting = true;
            console.log('✅ 驗證通過，準備提交表單');
            
            // 更新按鈕文字
            if (submitBtn && submitBtnText) {
                const originalText = submitBtnText.textContent;
                submitBtnText.textContent = '處理中...';
                
                // 如果5秒後仍在提交，恢復按鈕文字
                setTimeout(function() {
                    if (isSubmitting) {
                        isSubmitting = false;
                        submitBtnText.textContent = originalText;
                    }
                }, 5000);
            }
            
            // 驗證通過，使用原生表單提交（不觸發事件監聽器）
            console.log('提交表單到伺服器...');
            
            // 保存事件處理器引用
            const submitHandler = arguments.callee;
            
            // 移除事件監聽器，避免無限循環
            form.removeEventListener('submit', submitHandler);
            
            // 使用原生 submit 方法提交表單（不會觸發 submit 事件）
            // 但為了確保，我們使用一個標記
            form.setAttribute('data-validated', 'true');
            
            // 直接調用原生 submit（不觸發事件）
            HTMLFormElement.prototype.submit.call(form);
        });
    }
    
    // 處理表單初始化
    if (formAction && formAction.value === 'update') {
        if (submitBtnText) {
            submitBtnText.textContent = '更新申請資料';
        }
        // 如果是更新模式，檢查必填欄位並更新按鈕狀態
        setTimeout(checkRequiredFields, 100);
    }
});

// 選取申請資料
function selectApplication(applicationId) {
    // 優先從搜索框獲取 email
    let email = '';
    const emailInput = document.getElementById('search_email_input') || document.querySelector('#searchForm input[name="email"]');
    if (emailInput) {
        email = emailInput.value.trim();
    }
    
    // 如果搜索框沒有值，嘗試從 URL 參數獲取
    if (!email) {
        const urlParams = new URLSearchParams(window.location.search);
        email = urlParams.get('email') || '';
    }
    
    // 如果還是沒有，使用 PHP 傳入的搜索 email
    if (!email) {
        email = '<?php echo htmlspecialchars($search_email ?? "", ENT_QUOTES, "UTF-8"); ?>';
    }
    
    if (email) {
        // 構建新的 URL
        const baseUrl = window.location.pathname;
        window.location.href = baseUrl + '?action=search&email=' + encodeURIComponent(email) + '&application_id=' + applicationId;
    } else {
        alert('請先輸入 Email 進行搜尋');
        if (emailInput) {
            emailInput.focus();
        }
    }
}

// 學校搜尋功能
function performSchoolSearch() {
    const keyword = document.getElementById('school_name').value.trim();
    const resultsDiv = document.getElementById('schoolResults');
    const clearBtn = document.getElementById('clearSchoolSearch');

    // 顯示/隱藏清除按鈕
    if (keyword.length > 0) {
        clearBtn.style.display = 'block';
    } else {
        clearBtn.style.display = 'none';
        resultsDiv.classList.remove('show');
        // 當搜尋結果隱藏時，清除錯誤提示
        clearSchoolError();
        return;
    }

    if (keyword.length < 2) {
        resultsDiv.innerHTML = '<div class="search-result-item">請輸入至少2個字元</div>';
        resultsDiv.classList.add('show');
        // 當下拉選單顯示時，清除錯誤提示（用戶還在輸入中）
        clearSchoolError();
        return;
    }

    // 顯示載入中
    resultsDiv.innerHTML = '<div class="search-result-item"><i class="fas fa-spinner fa-spin"></i> 搜尋中...</div>';
    resultsDiv.classList.add('show');
    // 當下拉選單顯示時，清除錯誤提示（用戶還在選擇中）
    clearSchoolError();

    // 從API獲取搜尋結果
    fetch(`api/school_data_api.php?action=search&keyword=${encodeURIComponent(keyword)}&v=20241014-4`)
        .then(response => response.json())
        .then(data => {
            console.log('搜尋結果:', data);
            console.log('調試資訊:', data.debug);
            if (data.schools && data.schools.length > 0) {
                resultsDiv.innerHTML = data.schools.map((school, index) => {
                    let displayName = school.name;
                    let additionalInfo = '';
                    
                    if (school.all_names && school.all_names.length > 1) {
                        additionalInfo = `<div class="school-alternative-names">其他名稱: ${school.all_names.join(', ')}</div>`;
                    }
                    
                    // 確保 school_code 存在，如果沒有則使用空字串
                    const schoolCode = school.school_code || '';
                    // 使用 data 屬性儲存學校資訊，避免 JavaScript 字串轉義問題
                    // 顯示地址資訊，幫助用戶區分相同名稱的學校
                    const schoolAddress = school.address || '';
                    console.log('處理學校搜尋結果:', {
                        schoolCode: schoolCode,
                        schoolName: school.name,
                        address: schoolAddress,
                        addressLength: schoolAddress.length
                    });
                    
                    const addressDisplay = schoolAddress ? `<div class="school-address" style="font-size: 12px; color: #666; margin-top: 4px;">
                        <i class="fas fa-map-marker-alt"></i> ${schoolAddress}
                    </div>` : '';
                    
                    // 轉義地址中的特殊字元，確保正確儲存到 data 屬性
                    const escapedAddress = schoolAddress
                        .replace(/&/g, '&amp;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');
                    
                    return `<div class="search-result-item" 
                                data-school-code="${schoolCode}"
                                data-school-name="${(school.name || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;')}"
                                data-school-city="${(school.city || '').replace(/"/g, '&quot;')}"
                                data-school-district="${(school.district || '').replace(/"/g, '&quot;')}"
                                data-school-address="${escapedAddress}"
                                onclick="selectSchoolFromData(this)">
                            <i class="fas fa-school"></i>
                            <div class="school-info">
                                <span class="school-name">${displayName}</span>
                                <span class="school-location">${school.city || ''} ${school.district || ''}</span>
                                ${addressDisplay}
                                ${additionalInfo}
                            </div>
                        </div>`;
                }).join('');

                if (data.total > 20) {
                    resultsDiv.innerHTML += `<div class="search-result-item more-results">還有 ${data.total - 20} 個結果...</div>`;
                }
                // 當下拉選單顯示時，清除錯誤提示
                clearSchoolError();
            } else {
                resultsDiv.innerHTML = '<div class="search-result-item">找不到匹配的學校</div>';
                // 即使找不到結果，下拉選單仍然顯示，所以清除錯誤提示
                clearSchoolError();
            }
        })
        .catch(error => {
            console.error('搜尋錯誤:', error);
            resultsDiv.innerHTML = '<div class="search-result-item">搜尋失敗，請稍後再試</div>';
            // 即使搜尋失敗，下拉選單仍然顯示，所以清除錯誤提示
            clearSchoolError();
        });
}

// 清除學校輸入錯誤提示
function clearSchoolError() {
    const errorDiv = document.getElementById('school_name_error');
    const input = document.getElementById('school_name');
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
    if (input) {
        input.style.borderColor = '';
        input.style.borderWidth = '';
        input.style.boxShadow = '';
    }
}

// 顯示學校輸入錯誤提示
function showSchoolError(message) {
    const errorDiv = document.getElementById('school_name_error');
    const errorText = document.getElementById('school_name_error_text');
    const input = document.getElementById('school_name');
    
    if (errorDiv && errorText) {
        errorText.textContent = message || '請從系統提供的選項中選擇學校，不能自行輸入';
        errorDiv.style.display = 'block';
        // 添加動畫效果
        errorDiv.style.animation = 'none';
        setTimeout(() => {
            errorDiv.style.animation = 'slideDown 0.3s ease';
        }, 10);
    }
    
    if (input) {
        input.style.borderColor = '#d32f2f';
        input.style.borderWidth = '2px';
        input.style.boxShadow = '0 0 0 3px rgba(211, 47, 47, 0.1)';
    }
}

// 驗證學校輸入格式
function validateSchoolInput() {
    const input = document.getElementById('school_name');
    if (!input) return;
    
    const value = input.value.trim();
    const resultsDiv = document.getElementById('schoolResults');
    
    // 如果為空，不顯示錯誤（由required屬性處理）
    if (!value) {
        clearSchoolError();
        return;
    }
    
    // 如果下拉選單正在顯示，表示用戶還在選擇中，不顯示錯誤
    if (resultsDiv && resultsDiv.classList.contains('show')) {
        clearSchoolError();
        return;
    }
    
    // 檢查格式是否為：學校名稱 (縣市區)
    const schoolFormatPattern = /^.+ \(.+\)$/;
    if (!schoolFormatPattern.test(value)) {
        // 只有在下拉選單隱藏時才顯示錯誤
        showSchoolError('請從系統提供的選項中選擇學校，不能自行輸入');
    } else {
        clearSchoolError();
    }
}

// 立即驗證（不延遲）- 用於失去焦點時
function validateSchoolInputImmediate() {
    validateSchoolInput();
}

// 清除搜尋
function clearSchoolSearch() {
    const schoolCodeInput = document.getElementById('school_code');
    const schoolNameInput = document.getElementById('school_name');
    if (schoolCodeInput) schoolCodeInput.value = '';
    if (schoolNameInput) schoolNameInput.value = '';
    document.getElementById('schoolResults').classList.remove('show');
    document.getElementById('clearSchoolSearch').style.display = 'none';
    clearSchoolError();
    clearCityMismatchError();
    clearDistrictMismatchError();
}

// 台灣縣市和區/鄉鎮市對應資料
const cityDistrictsMap = {
    '台北市': ['中正區', '大同區', '中山區', '松山區', '大安區', '萬華區', '信義區', '士林區', '北投區', '內湖區', '南港區', '文山區'],
    '新北市': ['板橋區', '三重區', '中和區', '永和區', '新莊區', '新店區', '樹林區', '鶯歌區', '三峽區', '淡水區', '汐止區', '瑞芳區', '土城區', '蘆洲區', '五股區', '泰山區', '林口區', '深坑區', '石碇區', '坪林區', '三芝區', '石門區', '八里區', '平溪區', '雙溪區', '貢寮區', '金山區', '萬里區', '烏來區'],
    '桃園市': ['桃園區', '中壢區', '大溪區', '楊梅區', '蘆竹區', '大園區', '龜山區', '八德區', '龍潭區', '平鎮區', '新屋區', '觀音區', '復興區'],
    '台中市': ['中區', '東區', '南區', '西區', '北區', '西屯區', '南屯區', '北屯區', '豐原區', '東勢區', '大甲區', '清水區', '沙鹿區', '梧棲區', '后里區', '神岡區', '潭子區', '大雅區', '新社區', '石岡區', '外埔區', '大安區', '烏日區', '大肚區', '龍井區', '霧峰區', '太平區', '大里區', '和平區'],
    '台南市': ['中西區', '東區', '南區', '北區', '安平區', '安南區', '永康區', '歸仁區', '新化區', '左鎮區', '玉井區', '楠西區', '南化區', '仁德區', '關廟區', '龍崎區', '官田區', '麻豆區', '佳里區', '西港區', '七股區', '將軍區', '學甲區', '北門區', '新營區', '後壁區', '白河區', '東山區', '六甲區', '下營區', '柳營區', '鹽水區', '善化區', '大內區', '山上區', '新市區', '安定區'],
    '高雄市': ['新興區', '前金區', '苓雅區', '鹽埕區', '鼓山區', '旗津區', '前鎮區', '三民區', '左營區', '楠梓區', '小港區', '仁武區', '大社區', '岡山區', '路竹區', '阿蓮區', '田寮區', '燕巢區', '橋頭區', '梓官區', '彌陀區', '永安區', '湖內區', '鳳山區', '大寮區', '林園區', '鳥松區', '大樹區', '旗山區', '美濃區', '六龜區', '內門區', '杉林區', '甲仙區', '桃源區', '那瑪夏區', '茂林區', '茄萣區'],
    '基隆市': ['仁愛區', '信義區', '中正區', '中山區', '安樂區', '暖暖區', '七堵區'],
    '新竹市': ['東區', '北區', '香山區'],
    '嘉義市': ['東區', '西區'],
    '新竹縣': ['竹北市', '湖口鄉', '新豐鄉', '新埔鎮', '關西鎮', '芎林鄉', '寶山鄉', '竹東鎮', '五峰鄉', '橫山鄉', '尖石鄉', '北埔鄉', '峨眉鄉'],
    '苗栗縣': ['苗栗市', '苑裡鎮', '通霄鎮', '竹南鎮', '頭份市', '後龍鎮', '卓蘭鎮', '大湖鄉', '公館鄉', '銅鑼鄉', '南庄鄉', '頭屋鄉', '三義鄉', '西湖鄉', '造橋鄉', '三灣鄉', '獅潭鄉', '泰安鄉'],
    '彰化縣': ['彰化市', '鹿港鎮', '和美鎮', '線西鄉', '伸港鄉', '福興鄉', '秀水鄉', '花壇鄉', '芬園鄉', '員林市', '溪湖鎮', '田中鎮', '大村鄉', '埔鹽鄉', '埔心鄉', '永靖鄉', '社頭鄉', '二水鄉', '北斗鎮', '二林鎮', '田尾鄉', '埤頭鄉', '芳苑鄉', '大城鄉', '竹塘鄉', '溪州鄉'],
    '南投縣': ['南投市', '埔里鎮', '草屯鎮', '竹山鎮', '集集鎮', '名間鄉', '鹿谷鄉', '中寮鄉', '魚池鄉', '國姓鄉', '水里鄉', '信義鄉', '仁愛鄉'],
    '雲林縣': ['斗六市', '斗南鎮', '虎尾鎮', '西螺鎮', '土庫鎮', '北港鎮', '古坑鄉', '大埤鄉', '莿桐鄉', '林內鄉', '二崙鄉', '崙背鄉', '麥寮鄉', '東勢鄉', '褒忠鄉', '台西鄉', '元長鄉', '四湖鄉', '口湖鄉', '水林鄉'],
    '嘉義縣': ['太保市', '朴子市', '布袋鎮', '大林鎮', '民雄鄉', '溪口鄉', '新港鄉', '六腳鄉', '東石鄉', '義竹鄉', '鹿草鄉', '水上鄉', '中埔鄉', '竹崎鄉', '梅山鄉', '番路鄉', '大埔鄉', '阿里山鄉'],
    '屏東縣': ['屏東市', '潮州鎮', '東港鎮', '恆春鎮', '萬丹鄉', '長治鄉', '麟洛鄉', '九如鄉', '里港鄉', '鹽埔鄉', '高樹鄉', '萬巒鄉', '內埔鄉', '竹田鄉', '新埤鄉', '枋寮鄉', '新園鄉', '崁頂鄉', '林邊鄉', '南州鄉', '佳冬鄉', '琉球鄉', '車城鄉', '滿州鄉', '枋山鄉', '三地門鄉', '霧台鄉', '瑪家鄉', '泰武鄉', '來義鄉', '春日鄉', '獅子鄉', '牡丹鄉'],
    '宜蘭縣': ['宜蘭市', '頭城鎮', '礁溪鄉', '壯圍鄉', '員山鄉', '羅東鎮', '三星鄉', '大同鄉', '五結鄉', '冬山鄉', '蘇澳鎮', '南澳鄉'],
    '花蓮縣': ['花蓮市', '鳳林鎮', '玉里鎮', '新城鄉', '吉安鄉', '壽豐鄉', '光復鄉', '豐濱鄉', '瑞穗鄉', '富里鄉', '秀林鄉', '萬榮鄉', '卓溪鄉'],
    '台東縣': ['台東市', '成功鎮', '關山鎮', '卑南鄉', '鹿野鄉', '池上鄉', '東河鄉', '長濱鄉', '太麻里鄉', '大武鄉', '綠島鄉', '海端鄉', '延平鄉', '金峰鄉', '達仁鄉', '蘭嶼鄉'],
    '澎湖縣': ['馬公市', '湖西鄉', '白沙鄉', '西嶼鄉', '望安鄉', '七美鄉'],
    '金門縣': ['金城鎮', '金湖鎮', '金沙鎮', '金寧鄉', '烈嶼鄉', '烏坵鄉'],
    '連江縣': ['南竿鄉', '北竿鄉', '莒光鄉', '東引鄉']
};

// 縣市名稱對應表（處理「臺」vs「台」等變體）
const cityNameMap = {
    '臺北市': '台北市',
    '台北市': '台北市',
    '臺中市': '台中市',
    '台中市': '台中市',
    '臺南市': '台南市',
    '台南市': '台南市',
    '臺東縣': '台東縣',
    '台東縣': '台東縣'
};

// 標準化縣市名稱
function normalizeCityName(city) {
    if (!city) return '';
    if (cityNameMap[city]) {
        return cityNameMap[city];
    }
    return city.replace(/臺/g, '台');
}

// 更新區/鄉鎮市選項
function updateDistricts(preserveValue = false) {
    const citySelect = document.getElementById('citySelect');
    const districtSelect = document.getElementById('districtSelect');
    const schoolAddressInput = document.getElementById('school_address');
    
    if (!citySelect || !districtSelect) return;
    
    const selectedCity = citySelect.value;
    const currentDistrict = preserveValue ? districtSelect.value : '';
    districtSelect.innerHTML = '<option value="">請選擇區/鄉鎮市</option>';
    
    if (selectedCity && cityDistrictsMap[selectedCity]) {
        const districts = cityDistrictsMap[selectedCity];
        districts.forEach(district => {
            const option = document.createElement('option');
            option.value = district;
            option.textContent = district;
            if (preserveValue && district === currentDistrict) {
                option.selected = true;
            }
            districtSelect.appendChild(option);
        });
    }
    
    // 如果沒有保留值，清空學校地址
    if (!preserveValue) {
        schoolAddressInput.value = '';
    } else if (currentDistrict && selectedCity) {
        // 如果保留了值，更新學校地址
        updateSchoolAddress();
    }
}

// 更新學校地址
function updateSchoolAddress() {
    const citySelect = document.getElementById('citySelect');
    const districtSelect = document.getElementById('districtSelect');
    const schoolAddressInput = document.getElementById('school_address');
    
    if (!citySelect || !districtSelect || !schoolAddressInput) return;
    
    const city = citySelect.value;
    const district = districtSelect.value;
    
    if (city && district) {
        schoolAddressInput.value = city + district;
    } else {
        schoolAddressInput.value = '';
    }
}

// 清除縣市不一致錯誤
function clearCityMismatchError() {
    const errorDiv = document.getElementById('city_school_mismatch_error');
    const citySelect = document.getElementById('citySelect');
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
    if (citySelect) {
        citySelect.style.borderColor = '';
        citySelect.style.borderWidth = '';
        citySelect.style.boxShadow = '';
    }
}

// 清除區/鄉鎮市不一致錯誤
function clearDistrictMismatchError() {
    const errorDiv = document.getElementById('district_school_mismatch_error');
    const districtSelect = document.getElementById('districtSelect');
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
    if (districtSelect) {
        districtSelect.style.borderColor = '';
        districtSelect.style.borderWidth = '';
        districtSelect.style.boxShadow = '';
    }
}

// 顯示縣市不一致錯誤
function showCityMismatchError(message) {
    const errorDiv = document.getElementById('city_school_mismatch_error');
    const errorText = document.getElementById('city_school_mismatch_error_text');
    const citySelect = document.getElementById('citySelect');
    
    if (errorDiv && errorText) {
        errorText.textContent = message || '就讀縣市與選擇的學校所在縣市不一致，系統已自動更新為正確的縣市';
        errorDiv.style.display = 'block';
        errorDiv.style.animation = 'none';
        setTimeout(() => {
            errorDiv.style.animation = 'slideDown 0.3s ease';
        }, 10);
    }
    
    if (citySelect) {
        citySelect.style.borderColor = '#d32f2f';
        citySelect.style.borderWidth = '2px';
        citySelect.style.boxShadow = '0 0 0 3px rgba(211, 47, 47, 0.1)';
    }
    
    // 3秒後自動清除錯誤提示和紅色框框
    setTimeout(() => {
        clearCityMismatchError();
    }, 3000);
}

// 顯示區/鄉鎮市不一致錯誤
function showDistrictMismatchError(message) {
    const errorDiv = document.getElementById('district_school_mismatch_error');
    const errorText = document.getElementById('district_school_mismatch_error_text');
    const districtSelect = document.getElementById('districtSelect');
    
    if (errorDiv && errorText) {
        errorText.textContent = message || '區/鄉鎮市與選擇的學校所在區/鄉鎮市不一致，系統已自動更新為正確的區/鄉鎮市';
        errorDiv.style.display = 'block';
        errorDiv.style.animation = 'none';
        setTimeout(() => {
            errorDiv.style.animation = 'slideDown 0.3s ease';
        }, 10);
    }
    
    if (districtSelect) {
        districtSelect.style.borderColor = '#d32f2f';
        districtSelect.style.borderWidth = '2px';
        districtSelect.style.boxShadow = '0 0 0 3px rgba(211, 47, 47, 0.1)';
    }
    
    // 3秒後自動清除錯誤提示和紅色框框
    setTimeout(() => {
        clearDistrictMismatchError();
    }, 3000);
}

// 驗證縣市與學校是否一致
function validateCitySchoolMatch() {
    const citySelect = document.getElementById('citySelect');
    const districtSelect = document.getElementById('districtSelect');
    const schoolCityActualInput = document.getElementById('school_city_actual');
    const schoolDistrictActualInput = document.getElementById('school_district_actual');
    const schoolInput = document.getElementById('school_name');
    
    if (!citySelect || !schoolCityActualInput || !schoolInput) {
        return true;
    }
    
    const selectedCity = citySelect.value;
    const selectedDistrict = districtSelect ? districtSelect.value : '';
    const actualCity = schoolCityActualInput.value;
    const actualDistrict = schoolDistrictActualInput ? schoolDistrictActualInput.value : '';
    const schoolName = schoolInput.value.trim();
    
    if (!schoolName || !actualCity) {
        clearCityMismatchError();
        clearDistrictMismatchError();
        return true;
    }
    
    let hasError = false;
    
    // 驗證縣市
    const normalizedSelected = normalizeCityName(selectedCity);
    const normalizedActual = normalizeCityName(actualCity);
    
    if (normalizedSelected && normalizedActual && normalizedSelected !== normalizedActual) {
        const options = citySelect.options;
        for (let i = 0; i < options.length; i++) {
            const optionValue = normalizeCityName(options[i].value);
            if (optionValue === normalizedActual) {
                citySelect.value = options[i].value;
                updateDistricts();
                showCityMismatchError('就讀縣市與選擇的學校所在縣市不一致，已自動更新為正確的縣市');
                hasError = true;
                break;
            }
        }
        if (!hasError) {
            showCityMismatchError('就讀縣市與選擇的學校所在縣市不一致，請選擇正確的縣市');
            hasError = true;
        }
    } else {
        clearCityMismatchError();
    }
    
    // 驗證區/鄉鎮市
    if (districtSelect && actualDistrict && selectedDistrict && selectedDistrict !== actualDistrict) {
        // 檢查區/鄉鎮市是否在當前縣市的選項中
        const districtOptions = districtSelect.options;
        let districtFound = false;
        for (let i = 0; i < districtOptions.length; i++) {
            if (districtOptions[i].value === actualDistrict) {
                districtFound = true;
                break;
            }
        }
        
        if (districtFound) {
            // 如果區/鄉鎮市在選項中，自動更新
            districtSelect.value = actualDistrict;
            updateSchoolAddress();
            showDistrictMismatchError('區/鄉鎮市與選擇的學校所在區/鄉鎮市不一致，已自動更新為正確的區/鄉鎮市');
            hasError = true;
        } else {
            // 如果區/鄉鎮市不在選項中，手動添加
            const option = document.createElement('option');
            option.value = actualDistrict;
            option.textContent = actualDistrict;
            option.selected = true;
            districtSelect.appendChild(option);
            updateSchoolAddress();
            showDistrictMismatchError('區/鄉鎮市與選擇的學校所在區/鄉鎮市不一致，已自動更新為正確的區/鄉鎮市');
            hasError = true;
        }
    } else {
        clearDistrictMismatchError();
    }
    
    return !hasError;
}

// 從 data 屬性選擇學校（新方法，避免編碼問題）
function selectSchoolFromData(element) {
    const schoolCode = element.getAttribute('data-school-code') || '';
    const schoolName = element.getAttribute('data-school-name') || '';
    const city = element.getAttribute('data-school-city') || '';
    const district = element.getAttribute('data-school-district') || '';
    let fullAddress = element.getAttribute('data-school-address') || '';
    
    // 解碼 HTML 實體（如果有的話）
    if (fullAddress) {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = fullAddress;
        fullAddress = tempDiv.textContent || tempDiv.innerText || fullAddress;
    }
    
    console.log('選擇學校 - 原始資料:', {
        schoolCode,
        schoolName,
        city,
        district,
        fullAddress: fullAddress,
        rawAddress: element.getAttribute('data-school-address')
    });
    
    selectSchool(schoolCode, schoolName, city, district, fullAddress);
}

// 選擇學校（保留舊方法以向後兼容）
function selectSchool(schoolCode, schoolName, city, district, fullAddress) {
    // 驗證 schoolCode 是否存在
    if (!schoolCode || schoolCode.trim() === '') {
        console.error('錯誤：學校代號為空', {schoolCode, schoolName, city, district, fullAddress});
        alert('錯誤：無法取得學校代號，請重新選擇學校');
        return;
    }
    
    const fullSchoolName = `${schoolName} (${city}${district})`;
    const schoolCodeInput = document.getElementById('school_code');
    const schoolNameInput = document.getElementById('school_name');
    
    // 儲存學校代號（隱藏欄位）
    if (schoolCodeInput) {
        schoolCodeInput.value = schoolCode.trim();
        console.log('已設置學校代號:', schoolCode.trim());
    } else {
        console.error('錯誤：找不到 school_code 輸入框');
    }
    
    // 顯示學校名稱（顯示欄位）
    if (schoolNameInput) {
        schoolNameInput.value = fullSchoolName;
    }
    
    // 同時設置隱藏的 school_name_display 欄位，以便表單提交時保留
    const schoolNameDisplayInput = document.getElementById('school_name_display');
    if (schoolNameDisplayInput) {
        schoolNameDisplayInput.value = fullSchoolName;
        console.log('已設置 school_name_display:', fullSchoolName);
    } else {
        console.warn('⚠️ 找不到 school_name_display 隱藏欄位');
    }
    
    // 更新縣市和區/鄉鎮市下拉選單
    const citySelect = document.getElementById('citySelect');
    const districtSelect = document.getElementById('districtSelect');
    const schoolAddressInput = document.getElementById('school_address');
    
    // 優先使用完整地址，如果沒有則使用「縣市+區/鄉鎮市」
    if (schoolAddressInput) {
        console.log('設置地址前的狀態:', {
            fullAddress: fullAddress,
            fullAddressTrimmed: fullAddress ? fullAddress.trim() : '',
            city: city,
            district: district,
            currentValue: schoolAddressInput.value
        });
        
        if (fullAddress && fullAddress.trim() !== '') {
            // 使用完整地址
            const trimmedAddress = fullAddress.trim();
            schoolAddressInput.value = trimmedAddress;
            console.log('✅ 已設置完整學校地址:', trimmedAddress);
            console.log('✅ 驗證設置後的地址值:', schoolAddressInput.value);
        } else if (city && district) {
            // 如果沒有完整地址，使用「縣市+區/鄉鎮市」
            const normalizedCity = normalizeCityName(city);
            const fallbackAddress = normalizedCity + district;
            schoolAddressInput.value = fallbackAddress;
            console.log('✅ 已設置學校地址（縣市+區）:', fallbackAddress);
            console.log('⚠️ 注意：使用備用地址，因為完整地址為空');
        } else {
            schoolAddressInput.value = '';
            console.log('⚠️ 沒有地址資訊，已清空地址欄位');
        }
        
        // 最終驗證
        setTimeout(function() {
            console.log('最終地址值驗證:', schoolAddressInput.value);
        }, 100);
    } else {
        console.error('❌ 找不到學校地址輸入框');
    }
    
    if (citySelect && city) {
        // 標準化縣市名稱（處理「臺」vs「台」等變體）
        const normalizedCity = normalizeCityName(city);
        console.log('選擇學校 - 縣市:', city, '標準化後:', normalizedCity, '區/鄉鎮市:', district);
        
        // 設置縣市下拉選單的值
        citySelect.value = normalizedCity;
        
        // 更新區/鄉鎮市選項（不保留值，因為我們要設置新的值）
        if (typeof updateDistricts === 'function') {
            updateDistricts(false); // 先清空，然後設置新值
        }
        
        // 等待區/鄉鎮市選項更新後再設置區/鄉鎮市值
        setTimeout(function() {
            if (districtSelect && district) {
                districtSelect.value = district;
                console.log('已設置區/鄉鎮市下拉選單:', district);
            }
            
            // 重新設置完整地址（因為 updateDistricts(false) 可能清空了地址）
            if (schoolAddressInput) {
                if (fullAddress && fullAddress.trim() !== '') {
                    // 使用完整地址
                    schoolAddressInput.value = fullAddress.trim();
                    console.log('✅ 重新設置完整學校地址（避免被 updateDistricts 覆蓋）:', schoolAddressInput.value);
                } else if (city && district) {
                    // 如果沒有完整地址，使用「縣市+區/鄉鎮市」
                    const normalizedCity = normalizeCityName(city);
                    schoolAddressInput.value = normalizedCity + district;
                    console.log('✅ 重新設置學校地址（縣市+區）:', schoolAddressInput.value);
                }
            }
        }, 150); // 增加延遲時間，確保 DOM 更新完成
    } else if (citySelect && !city) {
        // 如果沒有縣市資訊，清空下拉選單
        citySelect.value = '';
        if (typeof updateDistricts === 'function') {
            updateDistricts(false);
        }
        // 清空學校地址
        if (schoolAddressInput) {
            schoolAddressInput.value = '';
        }
    }
    
    // 注意：縣市和區/鄉鎮市不再存到資料庫，僅用於顯示（如果有顯示欄位的話）
    const cityInput = document.querySelector('input[name="city"]');
    const districtInput = document.querySelector('input[name="district"]');
    if (cityInput) cityInput.value = city || '';
    if (districtInput) districtInput.value = district || '';
    
    document.getElementById('schoolResults').classList.remove('show');
    document.getElementById('clearSchoolSearch').style.display = 'block';
    
    clearSchoolError();
    clearCityMismatchError();
    clearDistrictMismatchError();
    
    // 驗證縣市和區/鄉鎮市是否一致
    validateCitySchoolMatch();
    
    if (typeof checkRequiredFields === 'function') {
        checkRequiredFields();
    }
}

// 載入申請資料到表單
function loadApplicationData() {
    <?php if ($application_data): ?>
    const data = <?php echo json_encode($application_data); ?>;
    
    // 填入表單資料
    const schoolCodeInput = document.getElementById('school_code');
    const schoolNameInput = document.getElementById('school_name');
    if (schoolCodeInput) {
        schoolCodeInput.value = data.school_code || '';
    }
    if (schoolNameInput) {
        schoolNameInput.value = data.school_name_display || data.school_code || '';
    }
    // 注意：city 和 district 不再存到資料庫，從 school_data 查詢顯示
    // 查詢學校的縣市和區/鄉鎮市用於顯示（但不存到資料庫）
    <?php if ($application_data && !empty($application_data['school_code'])): ?>
    try {
        const schoolCode = '<?php echo htmlspecialchars($application_data['school_code'], ENT_QUOTES, 'UTF-8'); ?>';
        fetch(`api/school_data_api.php?action=get&code=${encodeURIComponent(schoolCode)}`)
            .then(response => response.json())
            .then(schoolData => {
                if (schoolData && schoolData.school) {
                    const cityInput = document.querySelector('input[name="city"]');
                    const districtInput = document.querySelector('input[name="district"]');
                    if (cityInput && schoolData.school.city) cityInput.value = schoolData.school.city;
                    if (districtInput && schoolData.school.district) districtInput.value = schoolData.school.district;
                }
            })
            .catch(err => console.log('查詢學校資訊失敗:', err));
    } catch (e) {
        console.log('無法查詢學校資訊:', e);
    }
    <?php endif; ?>
    
    document.querySelector('input[name="school_address"]').value = data.school_address || '';
    document.querySelector('input[name="contact_name"]').value = data.contact_name || '';
    document.querySelector('input[name="contact_title"]').value = data.contact_title || '';
    document.querySelector('input[name="contact_phone"]').value = data.contact_phone || '';
    document.querySelector('input[name="contact_email"]').value = data.contact_email || '';
    document.querySelector('input[name="preferred_date"]').value = data.preferred_date || '';
    document.querySelector('select[name="preferred_time"]').value = data.preferred_time || '';
    
    // 載入目標年級（複選）
    const targetGradesCheckboxes = document.querySelectorAll('input[name="target_grades[]"]');
    targetGradesCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    <?php if (!empty($selected_target_grades)): ?>
    const selectedGrades = <?php echo json_encode($selected_target_grades); ?>;
    targetGradesCheckboxes.forEach(checkbox => {
        if (selectedGrades.includes(checkbox.value)) {
            checkbox.checked = true;
            checkbox.parentElement.style.background = '#e3f2fd';
            checkbox.parentElement.style.borderColor = '#667eea';
        }
    });
    <?php endif; ?>
    
    document.querySelector('input[name="expected_students"]').value = data.expected_students || '';
    document.querySelector('select[name="venue_type"]').value = data.venue_type || '';
    document.querySelector('textarea[name="special_requirements"]').value = data.special_requirements || '';
    document.querySelector('textarea[name="remarks"]').value = data.remarks || '';
    
    // 如果有學校名稱，顯示清除按鈕
    if (schoolNameInput && schoolNameInput.value) {
        const clearBtn = document.getElementById('clearSchoolSearch');
        if (clearBtn) {
            clearBtn.style.display = 'block';
        }
    }
    
    // 檢查申請狀態
    const applicationStatus = data.status || '';
    const isApproved = (applicationStatus === 'AP' || applicationStatus === 'approved');
    
    // 如果已通過，阻止載入並提示
    if (isApproved) {
        alert('此申請已通過審核，無法修改資料。如需變更，請聯繫管理員。');
        return;
    }
    
    // 設定為更新模式
    document.getElementById('form_action').value = 'update';
    document.getElementById('application_id').value = data.id;
    document.getElementById('submit_btn_text').textContent = '更新申請資料';
    
    // 檢查必填欄位並更新按鈕狀態
    checkRequiredFields();
    
    // 滾動到表單
    document.getElementById('recruitmentForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
    
    // 顯示提示訊息
    alert('申請資料已載入到表單，您可以修改後重新提交。');
    <?php endif; ?>
}

// 驗證碼刷新功能
function refreshCaptcha() {
    const captchaImage = document.getElementById('captchaImage');
    const captchaInput = document.getElementById('captchaInput');
    
    // 清空輸入框
    if (captchaInput) {
        captchaInput.value = '';
        // 清空後檢查必填欄位並更新按鈕狀態
        checkRequiredFields();
    }
    
    // 刷新驗證碼圖片（添加時間戳防止緩存）
    if (captchaImage) {
        captchaImage.src = 'captcha_image.php?t=' + new Date().getTime();
    }
}

</script>
</body>
</html>