<?php
// 載入 session 配置
require_once 'session_config.php';
require_once 'senior_message_auth.php';

// 提前載入 header.php 中的路徑函數（但只執行函數定義部分，不輸出 HTML）
// 由於 header.php 會檢查函數是否已定義，我們需要先定義 $config
$config = [
    'base_url' => '/Topics-frontend/frontend/',
    'share_url' => '/Topics-frontend/frontend/share/'
];

// 如果函數未定義，則定義它們（避免與 header.php 衝突）
if (!function_exists('getCorrectPath')) {
    function getCorrectPath($targetFile) {
        global $config;
        return $config['base_url'] . $targetFile;
    }
}

if (!function_exists('getResourcePath')) {
    function getResourcePath($resourceFile) {
        global $config;
        return $config['share_url'] . $resourceFile;
    }
}

// 檢查登入狀態（允許未登入用戶查看，但只有登入用戶才能發布留言）
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

// 資料庫連接 - 使用與現有系統相同的配置
$host = 'localhost';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("資料庫連接失敗: " . $e->getMessage());
}

// 檢查留言權限（只有 @stu.ukn.edu.tw 的 email 可以留言）
$can_post_message = false;
$user_role = $_SESSION['role'] ?? '';
$user_email = null;
$current_user_avatar = getResourcePath('EIdROxGXsAE_LSs.jpg'); // 預設頭像

if ($isLoggedIn && isset($_SESSION['username'])) {
    try {
        // 從資料庫查詢用戶的 email 和頭像
        $stmt = $pdo->prepare("SELECT email, profile_picture FROM user WHERE username = ? LIMIT 1");
        $stmt->execute([$_SESSION['username']]);
        $user_result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_result) {
            // 獲取用戶頭像（優先順序：上傳的頭像 > Google 頭像 > 預設頭像）
            if (!empty($user_result['profile_picture'])) {
                $profile_picture = $user_result['profile_picture'];
                
                // 優先檢查是否為上傳的頭像（uploads/ 開頭）
                if (strpos($profile_picture, 'uploads/') === 0) {
                    // 上傳的頭像，優先使用
                    $current_user_avatar = getCorrectPath($profile_picture);
                } elseif (filter_var($profile_picture, FILTER_VALIDATE_URL)) {
                    // 完整 URL（如 Google 頭像），如果沒有上傳的頭像才使用
                    $current_user_avatar = $profile_picture;
                } else {
                    // share 目錄的檔案，使用 getResourcePath
                    $current_user_avatar = getResourcePath($profile_picture);
                }
            }
            
            // 檢查留言權限
            if (!empty($user_result['email'])) {
                $user_email = $user_result['email'];
                // 只有 @stu.ukn.edu.tw 的 email 可以留言
                if (strpos($user_email, '@stu.ukn.edu.tw') !== false) {
                    $can_post_message = true;
                    $permission_result = ['has_permission' => true, 'message' => '您有留言權限'];
                } else {
                    // email 不是 @stu.ukn.edu.tw，沒有留言權限
                    $can_post_message = false;
                    $permission_result = ['has_permission' => false, 'error' => '只有 @stu.ukn.edu.tw 的學生帳號可以留言'];
                }
            } else {
                // 如果找不到 email，沒有留言權限
                $can_post_message = false;
                $permission_result = ['has_permission' => false, 'error' => '您的帳號沒有設定 email，無法留言'];
            }
        } else {
            // 如果找不到用戶資料
            $can_post_message = false;
            $permission_result = ['has_permission' => false, 'error' => '找不到用戶資料'];
        }
    } catch(PDOException $e) {
        error_log("查詢用戶資料失敗: " . $e->getMessage());
        $can_post_message = false;
        $permission_result = ['has_permission' => false, 'error' => '查詢用戶資料失敗，請稍後再試'];
    }
} else {
    $permission_result = ['has_permission' => false, 'error' => '請先登入'];
}

// 獲取排序參數（預設為由新到舊）
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$order_by = ($sort_order === 'oldest') ? 'ASC' : 'DESC';

// 獲取分類篩選參數（支援中文名稱和代碼）
$filter_type = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// 將中文類別名稱轉換為代碼的映射
$category_mapping = [
    '經驗分享' => 'EXP',
    '學習建議' => 'STD',
    '生活指南' => 'LIFE',
    '就業資訊' => 'CAR',
    '推薦餐廳' => 'REST',
    '其他' => 'OTH'
];

// 將篩選條件轉換為代碼（如果輸入的是中文）
$filter_code = ($filter_type !== 'all' && isset($category_mapping[$filter_type])) 
    ? $category_mapping[$filter_type] 
    : $filter_type;

// 分頁設定
$per_page = 15; // 每頁顯示15則留言
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1; // 當前頁碼，最小為1
$offset = ($current_page - 1) * $per_page; // 計算偏移量

// 獲取留言資料
$messages = [];
$total_messages = 0;
$total_pages = 0;

try {
    // 檢查 is_published 欄位是否存在
    $has_is_published = false;
    try {
        $check_sql = "SHOW COLUMNS FROM senior_messages LIKE 'is_published'";
        $check_stmt = $pdo->query($check_sql);
        $has_is_published = $check_stmt->rowCount() > 0;
    } catch(PDOException $e) {
        $has_is_published = false;
    }
    
    // 檢查餐廳相關欄位是否存在
    $has_restaurant_fields = false;
    try {
        $check_sql = "SHOW COLUMNS FROM senior_messages LIKE 'restaurant_name'";
        $check_stmt = $pdo->query($check_sql);
        $has_restaurant_fields = $check_stmt->rowCount() > 0;
    } catch(PDOException $e) {
        $has_restaurant_fields = false;
    }
    
    // 先計算總留言數（根據篩選條件）
    $count_sql = "SELECT COUNT(*) as total 
                  FROM senior_messages sm";
    
    // 只有在 is_published 欄位存在時才添加過濾條件
    // 移除 is_published = 1 的條件，顯示所有記錄
    $where_conditions = [];
    if ($filter_code !== 'all') {
        $where_conditions[] = "COALESCE(sm.message_type, 'OTH') = :filter_type";
    }
    
    if (!empty($where_conditions)) {
        $count_sql .= " WHERE " . implode(" AND ", $where_conditions);
    }
    
    $stmt = $pdo->prepare($count_sql);
    if ($filter_code !== 'all') {
        $stmt->bindValue(':filter_type', $filter_code, PDO::PARAM_STR);
    }
    $stmt->execute();
    $total_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_messages = $total_result['total'] ?? 0;
    $total_pages = ceil($total_messages / $per_page);
    
    // 確保當前頁碼不超過總頁數
    if ($current_page > $total_pages && $total_pages > 0) {
        $current_page = $total_pages;
        $offset = ($current_page - 1) * $per_page;
    }
    
    // 獲取當前頁的留言資料（使用 JOIN 獲取作者資訊和類別名稱）
    $sql = "SELECT 
                sm.id,
                sm.user_id,
                sm.title,
                sm.content,
                sm.author_contact,
                sm.message_type,
                COALESCE(pc.name, '其他') as message_type_name,
                sm.is_published,
                sm.created_at,
                sm.updated_at,
                sm.view_count,
                sm.like_count,
                u.name as author_name,
                u.email as author_email,
                u.profile_picture as author_profile_picture,
                COALESCE(d.name, s.department) as author_department,
                COALESCE(g.name, s.grade) as author_grade";
    
    // 只有在欄位存在時才查詢餐廳相關欄位
    if ($has_restaurant_fields) {
        $sql .= ",
                sm.restaurant_name,
                sm.restaurant_address,
                sm.restaurant_lat,
                sm.restaurant_lng,
                sm.restaurant_place_id,
                sm.restaurant_rating,
                sm.delivery_rating,
                sm.price_level";
    } else {
        // 如果欄位不存在，設置為 NULL
        $sql .= ",
                NULL as restaurant_name,
                NULL as restaurant_address,
                NULL as restaurant_lat,
                NULL as restaurant_lng,
                NULL as restaurant_place_id,
                NULL as restaurant_rating,
                NULL as delivery_rating,
                NULL as price_level";
    }
    
    $sql .= " FROM senior_messages sm
            LEFT JOIN user u ON sm.user_id = u.id
            LEFT JOIN student s ON sm.user_id = s.user_id
            LEFT JOIN departments d ON s.department = d.code
            LEFT JOIN identity_options g ON s.grade = g.code
            LEFT JOIN post_categories pc ON sm.message_type = pc.code";
    
    // 構建 WHERE 條件（移除 is_published = 1 的過濾，顯示所有記錄）
    $where_conditions = [];
    if ($filter_code !== 'all') {
        $where_conditions[] = "COALESCE(sm.message_type, 'OTH') = :filter_type";
    }
    
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(" AND ", $where_conditions);
    }
    
    $sql .= " ORDER BY sm.created_at $order_by LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    if ($filter_code !== 'all') {
        $stmt->bindValue(':filter_type', $filter_code, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 將 message_type_name 映射回 message_type 以便後續使用
    // 同時處理科系和年級的中文顯示
    $grade_display_mapping = [
        'F1' => '專一', 'F2' => '專二', 'F3' => '專三', 'F4' => '專四', 'F5' => '專五',
        'J1' => '國一', 'J2' => '國二', 'J3' => '國三',
        'H1' => '高一', 'H2' => '高二', 'H3' => '高三'
    ];
    
    foreach ($messages as &$message) {
        $message['message_type'] = $message['message_type_name'] ?? '其他';
        
        // 如果年級還是代碼格式，轉換為中文
        if (!empty($message['author_grade'])) {
            $grade_code = $message['author_grade'];
            // 如果 identity_options 表返回的是代碼而不是名稱，進行轉換
            if (isset($grade_display_mapping[$grade_code])) {
                $message['author_grade'] = $grade_display_mapping[$grade_code];
            }
        }
        
        // 確保科系顯示為中文（如果還是代碼，保持原樣，因為 COALESCE 已經處理了）
        // 如果 author_department 為空或未知，設置為 '未知科系'
        if (empty($message['author_department'])) {
            $message['author_department'] = '未知科系';
        }
        
        // 如果 author_grade 為空或未知，設置為 '未知年級'
        if (empty($message['author_grade'])) {
            $message['author_grade'] = '未知年級';
        }
    }
    unset($message);
    
    // 為每個留言檢查用戶是否已點讚（只有登入用戶才檢查）
    // 獲取用戶ID（從資料庫獲取）
    $user_id_for_likes = null;
    if ($isLoggedIn) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
            $stmt->execute([$_SESSION['username']]);
            $user_result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user_result && !empty($user_result['id'])) {
                $user_id_for_likes = (int)$user_result['id'];
            }
        } catch(PDOException $e) {
            error_log("獲取用戶ID失敗: " . $e->getMessage());
        }
    }
    
    foreach ($messages as &$message) {
        if ($isLoggedIn && $user_id_for_likes !== null) {
            try {
                $stmt = $pdo->prepare("SELECT message_id FROM message_likes WHERE message_id = ? AND user_id = ?");
                $stmt->execute([$message['id'], $user_id_for_likes]);
                $message['user_liked'] = $stmt->fetch() ? true : false;
            } catch(PDOException $e) {
                // 如果 message_likes 表不存在，設為 false
                $message['user_liked'] = false;
            }
        } else {
            $message['user_liked'] = false;
        }
        
        // 獲取作者頭像（優先順序：上傳的頭像 > Google 頭像 > 預設頭像）
        $avatar_src = getResourcePath('EIdROxGXsAE_LSs.jpg'); // 預設頭像
        
        // 優先使用 user_id 查詢用戶頭像（最準確）
        if (!empty($message['user_id'])) {
            try {
                $stmt = $pdo->prepare("SELECT profile_picture FROM user WHERE id = ? LIMIT 1");
                $stmt->execute([$message['user_id']]);
                $user_result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user_result && !empty($user_result['profile_picture'])) {
                    $profile_picture = $user_result['profile_picture'];
                    
                    // 優先檢查是否為上傳的頭像（uploads/ 開頭）
                    if (strpos($profile_picture, 'uploads/') === 0) {
                        // 上傳的頭像，優先使用
                        $avatar_src = getCorrectPath($profile_picture);
                    } elseif (filter_var($profile_picture, FILTER_VALIDATE_URL)) {
                        // 完整 URL（如 Google 頭像），如果沒有上傳的頭像才使用
                        $avatar_src = $profile_picture;
                    } else {
                        // share 目錄的檔案，使用 getResourcePath
                        $avatar_src = getResourcePath($profile_picture);
                    }
                }
            } catch(PDOException $e) {
                error_log("獲取留言作者頭像失敗 (user_id): " . $e->getMessage());
            }
        }
        
        // 如果 user_id 查詢失敗或沒有結果，使用 SQL 查詢中已獲取的 profile_picture
        if ($avatar_src === getResourcePath('EIdROxGXsAE_LSs.jpg') && !empty($message['author_profile_picture'])) {
            $profile_picture = $message['author_profile_picture'];
            
            // 優先檢查是否為上傳的頭像（uploads/ 開頭）
            if (strpos($profile_picture, 'uploads/') === 0) {
                // 上傳的頭像，優先使用
                $avatar_src = getCorrectPath($profile_picture);
            } elseif (filter_var($profile_picture, FILTER_VALIDATE_URL)) {
                // 完整 URL（如 Google 頭像），如果沒有上傳的頭像才使用
                $avatar_src = $profile_picture;
            } else {
                // share 目錄的檔案，使用 getResourcePath
                $avatar_src = getResourcePath($profile_picture);
            }
        }
        
        // 如果還是沒有，使用 email 查詢（備用方案）
        if ($avatar_src === getResourcePath('EIdROxGXsAE_LSs.jpg') && !empty($message['author_email'])) {
            try {
                $stmt = $pdo->prepare("SELECT profile_picture FROM user WHERE email = ? OR username = ? LIMIT 1");
                $stmt->execute([$message['author_email'], $message['author_email']]);
                $user_result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user_result && !empty($user_result['profile_picture'])) {
                    $profile_picture = $user_result['profile_picture'];
                    
                    // 優先檢查是否為上傳的頭像（uploads/ 開頭）
                    if (strpos($profile_picture, 'uploads/') === 0) {
                        // 上傳的頭像，優先使用
                        $avatar_src = getCorrectPath($profile_picture);
                    } elseif (filter_var($profile_picture, FILTER_VALIDATE_URL)) {
                        // 完整 URL（如 Google 頭像），如果沒有上傳的頭像才使用
                        $avatar_src = $profile_picture;
                    } else {
                        // share 目錄的檔案，使用 getResourcePath
                        $avatar_src = getResourcePath($profile_picture);
                    }
                }
            } catch(PDOException $e) {
                error_log("獲取留言作者頭像失敗 (email): " . $e->getMessage());
            }
        }
        
        $message['author_avatar'] = $avatar_src;
    }
} catch(PDOException $e) {
    $error_message = "載入留言失敗: " . $e->getMessage();
}

// 處理愛心切換功能
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_like') {
    // 設定 JSON 回應頭
    header('Content-Type: application/json; charset=utf-8');
    
    $message_id = (int)$_POST['message_id'];
    $is_liked = $_POST['is_liked'] === '1';
    
    // 獲取用戶ID（從資料庫獲取）
    $user_id = null;
    if ($isLoggedIn) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
            $stmt->execute([$_SESSION['username']]);
            $user_result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user_result && !empty($user_result['id'])) {
                $user_id = (int)$user_result['id'];
            }
        } catch(PDOException $e) {
            error_log("獲取用戶ID失敗: " . $e->getMessage());
        }
    }
    
    // 檢查用戶是否登入
    if (!$isLoggedIn || $user_id === null) {
        echo json_encode(['success' => false, 'error' => '請先登入']);
        exit;
    }
    
    try {
        // 檢查 message_likes 表是否存在，如果不存在則創建（根據實際資料庫結構）
        $stmt = $pdo->query("SHOW TABLES LIKE 'message_likes'");
        $table_exists = $stmt->rowCount() > 0;
        
        if (!$table_exists) {
            // 根據實際資料庫結構創建表（使用 message_id 和 user_id 作為複合主鍵）
            $createTableSQL = "CREATE TABLE IF NOT EXISTS message_likes (
                message_id INT(11) NOT NULL COMMENT '被按讚的訊息ID',
                user_id INT(11) NOT NULL COMMENT '按讚的使用者ID',
                liked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '按讚時間',
                PRIMARY KEY (message_id, user_id),
                KEY user_id (user_id),
                CONSTRAINT message_likes_ibfk_1 FOREIGN KEY (message_id) REFERENCES senior_messages(id) ON DELETE CASCADE,
                CONSTRAINT message_likes_ibfk_2 FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($createTableSQL);
        }
        
        // 檢查是否已經點讚過
        $stmt = $pdo->prepare("SELECT message_id FROM message_likes WHERE message_id = ? AND user_id = ?");
        $stmt->execute([$message_id, $user_id]);
        $existing_like = $stmt->fetch();
        
        if ($is_liked) {
            // 取消愛心
            if ($existing_like) {
                // 刪除點讚記錄
                $stmt = $pdo->prepare("DELETE FROM message_likes WHERE message_id = ? AND user_id = ?");
                $stmt->execute([$message_id, $user_id]);
                
                // 減少點讚數（確保不會變成負數）
                $stmt = $pdo->prepare("UPDATE senior_messages SET like_count = GREATEST(0, like_count - 1) WHERE id = ?");
                $stmt->execute([$message_id]);
                
                // 獲取新的點讚數
                $stmt = $pdo->prepare("SELECT like_count FROM senior_messages WHERE id = ?");
                $stmt->execute([$message_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $new_count = $result['like_count'] ?? 0;
                
                echo json_encode(['success' => true, 'action' => 'unliked', 'new_count' => (int)$new_count]);
            } else {
                echo json_encode(['success' => true, 'action' => 'no_change', 'message' => '尚未點讚，無需取消']);
            }
        } else {
            // 添加愛心
            if (!$existing_like) {
                // 添加點讚記錄（使用 liked_at 欄位）
                $stmt = $pdo->prepare("INSERT INTO message_likes (message_id, user_id, liked_at) VALUES (?, ?, NOW())");
                $stmt->execute([$message_id, $user_id]);
                
                // 增加點讚數
                $stmt = $pdo->prepare("UPDATE senior_messages SET like_count = like_count + 1 WHERE id = ?");
                $stmt->execute([$message_id]);
                
                // 獲取新的點讚數
                $stmt = $pdo->prepare("SELECT like_count FROM senior_messages WHERE id = ?");
                $stmt->execute([$message_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $new_count = $result['like_count'] ?? 0;
                
                echo json_encode(['success' => true, 'action' => 'liked', 'new_count' => (int)$new_count]);
            } else {
                echo json_encode(['success' => true, 'action' => 'no_change', 'message' => '已經點讚過了']);
            }
        }
        exit;
    } catch(PDOException $e) {
        error_log("點讚功能錯誤: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => '操作失敗: ' . $e->getMessage()]);
        exit;
    }
}

// 處理瀏覽次數更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'view') {
    $message_id = (int)$_POST['message_id'];
    try {
        $stmt = $pdo->prepare("UPDATE senior_messages SET view_count = view_count + 1 WHERE id = ?");
        $stmt->execute([$message_id]);
        echo json_encode(['success' => true]);
        exit;
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>在校生留言板</title>
    <link rel="stylesheet" href="assets/csp/QA.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --bg-color: #fff;
            --text-color: #000;
            --secondary-text: #536471;
            --border-color: #e1e8ed;
            --hover-bg: #f7f9fa;
            --accent-color: #1d9bf0;
            --card-bg: transparent;
        }
        
        body { 
            padding-top: 100px !important; /* 恢復間距避免被固定 header 遮擋 */
            background: var(--bg-color);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.4;
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* 強制覆蓋 QA.css 的樣式 */
        main h2 {
            padding: 0 0 30px 0 !important; /* 移除頂部 padding */
            margin-top: 0 !important;
        }
        
        .container {
            max-width: 700px;
            margin: 0 auto;
            padding: 20px 20px; /* 減少頂部 padding */
            min-height: calc(100vh - 120px);
        }
        
        .header {
            margin-bottom: 30px; /* 減少底部間距 */
            padding: 20px 20px; /* 減少內部 padding */
            background: linear-gradient(90deg, rgba(122, 201, 199, 0.1) 0%, rgba(149, 109, 189, 0.05) 100%);
            border-radius: 20px;
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 100%;
        }
        
        .header-text {
            flex: 1;
            text-align: left;
        }
        
        .post-button-container {
            margin-top: 0;
            position: relative;
            z-index: 10;
            flex-shrink: 0;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #000000 0%, #0074a9 50%, #000000 100%);
        }
        
        .header h1 {
            color: var(--text-color);
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 800;
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .header p {
            color: var(--secondary-text);
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 20px;
        }
        
        .permission-notice {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            text-align: center;
            box-shadow: 0 4px 15px rgba(29, 155, 240, 0.3);
            border: none;
        }
        
        .post-button-container {
            margin-top: 25px;
            position: relative;
            z-index: 10;
        }
        
        .post-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(29, 155, 240, 0.3);
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .post-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(149,109,189,0.2), transparent);
            transition: left 0.5s;
        }
        
        .post-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(29, 155, 240, 0.4);
            color: white;
        }
        
        .post-btn:hover::before {
            left: 100%;
        }
        
        .post-btn:active {
            transform: translateY(0);
        }
        

        /* 移除不必要的間距設定 */
        .page-top-spacer {
            height: 0; /* 不再需要額外間距 */
        }
        
        .filter-tabs {
            display: flex;
            margin-bottom: 30px;
            background: var(--hover-bg);
            border-radius: 15px;
            padding: 8px;
            border: 1px solid var(--border-color);
            overflow-x: auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .filter-tab {
            padding: 12px 20px;
            background: transparent;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            color: var(--secondary-text);
            font-size: 0.9rem;
            white-space: nowrap;
            border-radius: 10px;
            position: relative;
            flex: 1;
            text-align: center;
            display: block;
            text-decoration: none;
        }
        
        .filter-tab:hover {
            background: rgba(29, 155, 240, 0.1);
            color: var(--accent-color);
            transform: translateY(-1px);
            text-decoration: none;
        }
        
        .filter-tab.active {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(29, 155, 240, 0.3);
            text-decoration: none;
        }
        
        .messages-feed {
            display: flex;
            flex-direction: column;
            gap: 15px;
            width: 100%;
            min-height: 200px;
        }
        
        .message-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 0;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            width: 100%;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }
        
        .message-card[style*="display: none"] {
            display: none !important;
            margin-bottom: 0;
            height: 0;
            padding: 0;
            margin: 0;
            overflow: hidden;
        }
        
        .message-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .message-card:hover {
            background: var(--hover-bg);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-color: var(--accent-color);
        }
        
        .message-card:hover::before {
            opacity: 1;
        }
        
        .message-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            margin-right: 15px;
            flex-shrink: 0;
            box-shadow: 0 3px 10px rgba(29, 155, 240, 0.3);
            overflow: hidden;
            position: relative;
        }
        
        .user-avatar .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        
        .user-info {
            flex: 1;
            min-width: 0;
        }
        
        .user-name {
            font-weight: 700;
            color: var(--text-color);
            font-size: 0.9rem;
            margin-bottom: 2px;
        }
        
        .user-details {
            color: var(--secondary-text);
            font-size: 0.8rem;
            margin-bottom: 4px;
        }
        
        .message-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .message-type {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .message-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 10px;
            line-height: 1.4;
        }
        
        .message-content {
            color: var(--secondary-text);
            line-height: 1.6;
            margin-bottom: 15px;
            font-size: 0.95rem;
            word-wrap: break-word;
        }
        
        .like-btn {
            background: transparent;
            color: var(--secondary-text);
            border: none;
            padding: 10px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
        }
        
        .like-btn:hover {
            background: rgba(249, 24, 128, 0.1);
            color: #f91880;
            transform: scale(1.05);
        }
        
        .like-btn:hover .like-icon {
            transform: scale(1.2);
        }
        
        .like-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .like-btn.liked {
            color: #f91880;
        }
        
        .like-btn.liked:hover {
            background: rgba(249, 24, 128, 0.15);
        }
        
        .like-icon {
            transition: all 0.3s ease;
            font-size: 1.1rem;
            display: inline-block;
        }
        
        /* 空心愛心樣式（未點過） */
        .like-btn:not(.liked) .like-icon {
            filter: grayscale(0);
        }
        
        /* 實心愛心樣式（已點過） */
        .like-btn.liked .like-icon {
            filter: none;
            animation: heartBeat 0.5s ease;
        }
        
        @keyframes heartBeat {
            0%, 100% { transform: scale(1); }
            25% { transform: scale(1.3); }
            50% { transform: scale(1.1); }
        }
        
        .like-count {
            font-weight: 600;
        }
        
        .view-count {
            color: var(--secondary-text);
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }
        
        .no-messages {
            text-align: center;
            padding: 80px 20px;
            color: var(--secondary-text);
            background: var(--hover-bg);
            border-radius: 20px;
            border: 1px solid var(--border-color);
            margin: 40px 0;
            width: 100%;
            box-sizing: border-box;
        }
        
        .no-messages h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--text-color);
            font-weight: 700;
        }
        
        .back-btn {
            display: inline-block;
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
            margin-top: 25px;
            box-shadow: 0 4px 15px rgba(29, 155, 240, 0.3);
            text-align: center;
            width: auto;
            max-width: 200px;
        }
        
        .back-btn:hover {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(29, 155, 240, 0.4);
        }
        
        .back-btn-container {
            text-align: center;
            margin-top: 40px;
            padding: 20px 0;
            border-top: 1px solid var(--border-color);
        }
        
        /* 動畫效果 */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message-card {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .message-card:nth-child(1) { animation-delay: 0.1s; }
        .message-card:nth-child(2) { animation-delay: 0.2s; }
        .message-card:nth-child(3) { animation-delay: 0.3s; }
        .message-card:nth-child(4) { animation-delay: 0.4s; }
        .message-card:nth-child(5) { animation-delay: 0.5s; }
        
        /* 滾動條樣式 */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
        }
        
        /* 響應式設計 */
        @media (max-width: 1200px) {
            .container {
                max-width: 1000px;
            }
        }
        
        @media (max-width: 1200px) {
            .messages-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 12px;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding-top: 120px !important; /* 手機版恢復間距 */
            }
            
            .container {
                padding: 15px;
            }
            
            .header {
                padding: 20px 15px;
                margin-bottom: 30px;
            }
            
            .header-content {
                flex-direction: column;
                align-items: stretch;
                gap: 20px;
            }
            
            .header-text {
                text-align: center;
            }
            
            .header h1 {
                font-size: 2.2rem;
            }
            
            .header p {
                font-size: 1.1rem;
            }
            
            .post-button-container {
                display: flex;
                justify-content: center;
            }
            
            .post-btn {
                padding: 6px 12px;
                font-size: 0.8rem;
                border-radius: 12px;
            }
            
            
            .filter-tabs {
                flex-wrap: wrap;
                gap: 8px;
            }
            
            .filter-tab {
                padding: 8px 12px;
                font-size: 0.9rem;
            }
            
            .sort-controls {
                flex-direction: column;
                align-items: stretch;
                width: 100%;
                margin-top: 15px;
            }
            
            .sort-controls .sort-btn {
                width: 100%;
                text-align: center;
            }
            
            .messages-feed {
                gap: 15px;
            }
            
            .message-card {
                padding: 15px;
            }
            
            .message-title {
                font-size: 1.1rem;
            }
            
            .message-content {
                font-size: 0.95rem;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding-top: 130px !important; /* 更小螢幕恢復間距 */
            }
            
            
            .messages-grid {
                grid-template-columns: 1fr;
                gap: 12px;
                max-width: 100%;
            }
            
            .filter-tabs {
                justify-content: flex-start;
                overflow-x: auto;
                padding: 15px;
                gap: 8px;
            }
            
            .filter-tab {
                padding: 8px 14px;
                font-size: 0.8rem;
                white-space: nowrap;
            }
            
            .sort-controls {
                flex-direction: column;
                align-items: stretch;
                width: 100%;
                margin-top: 15px;
            }
            
            .sort-controls .sort-btn {
                width: 100%;
                text-align: center;
            }
            
            .message-card {
                padding: 15px;
            }
            
            .message-title {
                font-size: 1.1rem;
            }
            
            .author-avatar {
                width: 30px;
                height: 30px;
                font-size: 0.8rem;
            }
        }
        
        /* 分頁器樣式 */
        .pagination-container {
            margin-top: 30px;
            padding: 20px 0;
        }
        
        .pagination-btn {
            padding: 10px 16px;
            background: var(--hover-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-color);
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .pagination-btn:hover:not(.disabled) {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(29, 155, 240, 0.3);
        }
        
        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination-number {
            padding: 8px 12px;
            background: var(--hover-bg);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            text-decoration: none;
            color: var(--text-color);
            font-weight: 500;
            min-width: 40px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .pagination-number:hover {
            background: rgba(29, 155, 240, 0.1);
            border-color: var(--accent-color);
            color: var(--accent-color);
        }
        
        .pagination-number.active {
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            border-color: transparent;
            color: white;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(29, 155, 240, 0.3);
        }
        
        .pagination-info {
            color: var(--secondary-text);
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .pagination-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .pagination-numbers {
                order: -1;
                width: 100%;
                justify-content: center;
            }
            
            .pagination-info {
                width: 100%;
                text-align: center;
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body class="custom-spacing">
    <?php include("share/header.php"); ?>
    <div class="page-top-spacer"></div>
    
    <div class="container">
        <div class="header">
            <div class="header-content">
                <div class="header-text">
                    <h1>在校生留言板</h1>
                    <p>來自學長姐的經驗分享與建議</p>
                </div>
                
                <?php if ($can_post_message): ?>
                    <div class="post-button-container">
                        <a href="senior_message_form.php" class="post-btn">
                            <span>✍️</span>
                            <span>發布留言</span>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="permission-notice">
                        權限提示：只有 @stu.ukn.edu.tw 的學生帳號可以留言
                        <?php if (isset($permission_result['error'])): ?>
                            <br><small><?php echo htmlspecialchars($permission_result['error']); ?></small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 15px; flex-wrap: wrap;">
            <div class="filter-tabs" style="flex: 1; min-width: 0;">
                <a href="?filter=all&sort=<?php echo $sort_order; ?>" class="filter-tab <?php echo $filter_type === 'all' ? 'active' : ''; ?>" data-type="all" style="text-decoration: none; color: inherit;">全部留言</a>
                <a href="?filter=經驗分享&sort=<?php echo $sort_order; ?>" class="filter-tab <?php echo $filter_type === '經驗分享' ? 'active' : ''; ?>" data-type="經驗分享" style="text-decoration: none; color: inherit;">經驗分享</a>
                <a href="?filter=學習建議&sort=<?php echo $sort_order; ?>" class="filter-tab <?php echo $filter_type === '學習建議' ? 'active' : ''; ?>" data-type="學習建議" style="text-decoration: none; color: inherit;">學習建議</a>
                <a href="?filter=生活指南&sort=<?php echo $sort_order; ?>" class="filter-tab <?php echo $filter_type === '生活指南' ? 'active' : ''; ?>" data-type="生活指南" style="text-decoration: none; color: inherit;">生活指南</a>
                <a href="?filter=就業資訊&sort=<?php echo $sort_order; ?>" class="filter-tab <?php echo $filter_type === '就業資訊' ? 'active' : ''; ?>" data-type="就業資訊" style="text-decoration: none; color: inherit;">就業資訊</a>
                <a href="?filter=推薦餐廳&sort=<?php echo $sort_order; ?>" class="filter-tab <?php echo $filter_type === '推薦餐廳' ? 'active' : ''; ?>" data-type="推薦餐廳" style="text-decoration: none; color: inherit;">推薦餐廳</a>
                <a href="?filter=其他&sort=<?php echo $sort_order; ?>" class="filter-tab <?php echo $filter_type === '其他' ? 'active' : ''; ?>" data-type="其他" style="text-decoration: none; color: inherit;">其他</a>
            </div>
            
            <div class="sort-controls" style="display: flex; gap: 10px; align-items: center; flex-shrink: 0;">
                <span style="color: var(--secondary-text); font-size: 0.9rem; font-weight: 500;">排序：</span>
                <a href="?filter=<?php echo urlencode($filter_type); ?>&sort=newest" class="sort-btn <?php echo $sort_order === 'newest' ? 'active' : ''; ?>" style="padding: 8px 16px; background: <?php echo $sort_order === 'newest' ? 'linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%)' : 'var(--hover-bg)'; ?>; color: <?php echo $sort_order === 'newest' ? 'white' : 'var(--secondary-text)'; ?>; border: 1px solid var(--border-color); border-radius: 8px; text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: all 0.3s ease; white-space: nowrap;">
                    由新到舊
                </a>
                <a href="?filter=<?php echo urlencode($filter_type); ?>&sort=oldest" class="sort-btn <?php echo $sort_order === 'oldest' ? 'active' : ''; ?>" style="padding: 8px 16px; background: <?php echo $sort_order === 'oldest' ? 'linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%)' : 'var(--hover-bg)'; ?>; color: <?php echo $sort_order === 'oldest' ? 'white' : 'var(--secondary-text)'; ?>; border: 1px solid var(--border-color); border-radius: 8px; text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: all 0.3s ease; white-space: nowrap;">
                    由舊到新
                </a>
            </div>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($messages)): ?>
            <div class="no-messages">
                <h3>📝 暫無留言</h3>
                <p>目前還沒有學長姐的留言，請稍後再來查看。</p>
            </div>
        <?php else: ?>
            <div class="messages-feed" id="messagesFeed">
                <?php foreach ($messages as $message): ?>
                    <div class="message-card" data-type="<?php echo htmlspecialchars($message['message_type'] ?? '其他'); ?>" data-message-id="<?php echo $message['id']; ?>">
                        <div class="message-header">
                            <div class="user-avatar">
                                <img src="<?php echo htmlspecialchars($message['author_avatar'] ?? getResourcePath('EIdROxGXsAE_LSs.jpg')); ?>" 
                                     alt="<?php echo htmlspecialchars($message['author_name']); ?>" 
                                     class="avatar-img"
                                     onerror="this.onerror=null; this.src='<?php echo htmlspecialchars(getResourcePath('EIdROxGXsAE_LSs.jpg')); ?>'; this.style.display='none'; this.parentElement.innerHTML='<?php echo mb_substr(htmlspecialchars($message['author_name']), 0, 1); ?>';">
                            </div>
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($message['author_name']); ?></div>
                                <div class="user-details"><?php echo htmlspecialchars($message['author_department'] ?? '未知科系'); ?> · <?php echo htmlspecialchars($message['author_grade'] ?? '未知年級'); ?></div>
                                <div class="message-meta">
                                    <span class="message-type"><?php echo htmlspecialchars($message['message_type'] ?? '其他'); ?></span>
                                    <span class="message-date"><?php echo date('M j', strtotime($message['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="message-title"><?php echo htmlspecialchars($message['title']); ?></div>
                        
                        <?php if (($message['message_type'] ?? '') === '推薦餐廳' && !empty($message['restaurant_name'])): ?>
                            <div class="restaurant-info-card" style="background: linear-gradient(90deg, rgba(122, 201, 199, 0.1), rgba(149, 109, 189, 0.05)); border: 1px solid rgba(149, 109, 189, 0.3); border-radius: 12px; padding: 16px; margin-bottom: 15px;">
                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                    <div style="font-size: 24px;">🍽️</div>
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0 0 4px 0; color: var(--text-color); font-size: 16px; font-weight: 600;">
                                            <?php echo htmlspecialchars($message['restaurant_name']); ?>
                                        </h4>
                                        <p style="margin: 0; color: var(--secondary-text); font-size: 13px;">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($message['restaurant_address'] ?? '地址未知'); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
                                    <?php if (!empty($message['restaurant_rating'])): ?>
                                        <div style="display: flex; align-items: center; gap: 4px; padding: 6px 12px; background: rgba(243, 156, 18, 0.2); border-radius: 8px;">
                                            <span style="color: #f39c12;">★</span>
                                            <span style="color: var(--text-color); font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($message['restaurant_rating']); ?>/5</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($message['delivery_rating'])): ?>
                                        <div style="display: flex; align-items: center; gap: 4px; padding: 6px 12px; background: rgba(255, 107, 53, 0.2); border-radius: 8px;">
                                            <span style="color: #ff6b35;">🏍️</span>
                                            <span style="color: var(--text-color); font-weight: 600; font-size: 14px;">外送 <?php echo htmlspecialchars($message['delivery_rating']); ?>/5</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($message['price_level'])): ?>
                                        <div style="padding: 6px 12px; background: rgba(39, 174, 96, 0.2); border-radius: 8px;">
                                            <span style="color: var(--text-color); font-weight: 600; font-size: 14px;"><?php echo str_repeat('$', $message['price_level']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="message-content" id="content-<?php echo $message['id']; ?>">
                            <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                        </div>
                        
                        <?php if (strlen($message['content']) > 200): ?>
                            <span class="read-more" onclick="toggleContent(<?php echo $message['id']; ?>)">展開更多</span>
                        <?php endif; ?>
                        
                        <?php if (($message['message_type'] ?? '') === '推薦餐廳'): ?>
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border-color); display: flex; gap: 10px; flex-wrap: wrap;">
                                <?php if (!empty($message['restaurant_lat']) && !empty($message['restaurant_lng'])): ?>
                                    <button type="button" 
                                            onclick="showRestaurantOnMap('<?php echo htmlspecialchars($message['restaurant_name'] ?? ''); ?>', <?php echo htmlspecialchars($message['restaurant_lat']); ?>, <?php echo htmlspecialchars($message['restaurant_lng']); ?>, '<?php echo htmlspecialchars($message['restaurant_address'] ?? ''); ?>')"
                                            style="padding: 8px 16px; background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; transition: all 0.3s ease; font-weight: 600;">
                                        <i class="fas fa-map-marker-alt"></i> 在地圖上查看
                                    </button>
                                <?php endif; ?>
                                <button type="button" 
                                        onclick="showRestaurantReviews(<?php echo $message['id']; ?>, '<?php echo htmlspecialchars($message['restaurant_name'] ?? ''); ?>')"
                                        style="padding: 8px 16px; background: var(--hover-bg); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-color); cursor: pointer; font-size: 13px; transition: all 0.3s ease;">
                                    <i class="fas fa-comments"></i> 查看評價與留言
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="message-stats">
                            <?php if ($isLoggedIn): ?>
                                <button type="button" class="like-btn <?php echo $message['user_liked'] ? 'liked' : ''; ?>" 
                                        data-message-id="<?php echo $message['id']; ?>"
                                        onclick="toggleLike(<?php echo $message['id']; ?>)">
                                    <span class="like-icon"><?php echo $message['user_liked'] ? '💖' : '🤍'; ?></span>
                                    <span class="like-count"><?php echo $message['like_count'] ?? 0; ?></span>
                                </button>
                            <?php else: ?>
                                <div class="like-btn" style="cursor: default; opacity: 0.7;">
                                    <span class="like-icon">🤍</span>
                                    <span class="like-count"><?php echo $message['like_count'] ?? 0; ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="view-count">
                                👁️ <?php echo $message['view_count'] ?? 0; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- 分頁器 -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination-container" style="margin-top: 30px; display: flex; justify-content: center; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <?php
                    // 構建基礎 URL（保留排序和篩選參數）
                    $base_url = "?filter=" . urlencode($filter_type) . "&sort=" . urlencode($sort_order);
                    
                    // 上一頁按鈕
                    if ($current_page > 1):
                    ?>
                        <a href="<?php echo $base_url; ?>&page=<?php echo $current_page - 1; ?>" class="pagination-btn" style="padding: 10px 16px; background: var(--hover-bg); border: 1px solid var(--border-color); border-radius: 8px; text-decoration: none; color: var(--text-color); font-weight: 600; transition: all 0.3s ease;">
                            ← 上一頁
                        </a>
                    <?php else: ?>
                        <span class="pagination-btn disabled" style="padding: 10px 16px; background: var(--hover-bg); border: 1px solid var(--border-color); border-radius: 8px; color: var(--secondary-text); opacity: 0.5; cursor: not-allowed;">
                            ← 上一頁
                        </span>
                    <?php endif; ?>
                    
                    <!-- 頁碼 -->
                    <div class="pagination-numbers" style="display: flex; gap: 5px; align-items: center;">
                        <?php
                        // 計算顯示的頁碼範圍
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        // 如果當前頁接近開頭，顯示更多後面的頁碼
                        if ($current_page <= 3) {
                            $end_page = min($total_pages, 5);
                        }
                        
                        // 如果當前頁接近結尾，顯示更多前面的頁碼
                        if ($current_page >= $total_pages - 2) {
                            $start_page = max(1, $total_pages - 4);
                        }
                        
                        // 顯示第一頁（如果不在範圍內）
                        if ($start_page > 1):
                        ?>
                            <a href="<?php echo $base_url; ?>&page=1" class="pagination-number" style="padding: 8px 12px; background: var(--hover-bg); border: 1px solid var(--border-color); border-radius: 6px; text-decoration: none; color: var(--text-color); font-weight: 500; min-width: 40px; text-align: center; transition: all 0.3s ease;">
                                1
                            </a>
                            <?php if ($start_page > 2): ?>
                                <span style="color: var(--secondary-text); padding: 0 5px;">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <!-- 顯示頁碼範圍 -->
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <?php if ($i == $current_page): ?>
                                <span class="pagination-number active" style="padding: 8px 12px; background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%); border: 1px solid transparent; border-radius: 6px; color: white; font-weight: 700; min-width: 40px; text-align: center; box-shadow: 0 2px 8px rgba(29, 155, 240, 0.3);">
                                    <?php echo $i; ?>
                                </span>
                            <?php else: ?>
                                <a href="<?php echo $base_url; ?>&page=<?php echo $i; ?>" class="pagination-number" style="padding: 8px 12px; background: var(--hover-bg); border: 1px solid var(--border-color); border-radius: 6px; text-decoration: none; color: var(--text-color); font-weight: 500; min-width: 40px; text-align: center; transition: all 0.3s ease;">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <!-- 顯示最後一頁（如果不在範圍內） -->
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <span style="color: var(--secondary-text); padding: 0 5px;">...</span>
                            <?php endif; ?>
                            <a href="<?php echo $base_url; ?>&page=<?php echo $total_pages; ?>" class="pagination-number" style="padding: 8px 12px; background: var(--hover-bg); border: 1px solid var(--border-color); border-radius: 6px; text-decoration: none; color: var(--text-color); font-weight: 500; min-width: 40px; text-align: center; transition: all 0.3s ease;">
                                <?php echo $total_pages; ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 下一頁按鈕 -->
                    <?php if ($current_page < $total_pages): ?>
                        <a href="<?php echo $base_url; ?>&page=<?php echo $current_page + 1; ?>" class="pagination-btn" style="padding: 10px 16px; background: var(--hover-bg); border: 1px solid var(--border-color); border-radius: 8px; text-decoration: none; color: var(--text-color); font-weight: 600; transition: all 0.3s ease;">
                            下一頁 →
                        </a>
                    <?php else: ?>
                        <span class="pagination-btn disabled" style="padding: 10px 16px; background: var(--hover-bg); border: 1px solid var(--border-color); border-radius: 8px; color: var(--secondary-text); opacity: 0.5; cursor: not-allowed;">
                            下一頁 →
                        </span>
                    <?php endif; ?>
                    
                    <!-- 頁碼資訊 -->
                    <div class="pagination-info" style="color: var(--secondary-text); font-size: 0.9rem; margin-left: 15px;">
                        第 <?php echo $current_page; ?> 頁 / 共 <?php echo $total_pages; ?> 頁
                        <span style="margin-left: 10px;">（共 <?php echo $total_messages; ?> 則留言）</span>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <script>
        // 注意：篩選現在由後端處理，前端不需要再做篩選
        // 分頁功能已整合到後端查詢中
        
        // 展開/收縮內容
        function toggleContent(messageId) {
            const content = document.getElementById('content-' + messageId);
            const readMore = content.nextElementSibling;
            
            if (content.classList.contains('expanded')) {
                content.classList.remove('expanded');
                readMore.textContent = '展開更多';
            } else {
                content.classList.add('expanded');
                readMore.textContent = '收起';
            }
        }
        
        // 愛心按鈕功能 - 切換模式
        function toggleLike(messageId, event) {
            // 檢查是否登入（通過檢查按鈕是否存在且可點擊）
            const likeBtn = document.querySelector(`.like-btn[data-message-id="${messageId}"]`);
            
            if (!likeBtn || likeBtn.disabled || likeBtn.style.cursor === 'default') {
                // 未登入或按鈕不可用
                alert('請先登入才能點讚');
                return;
            }
            
            const likeIcon = likeBtn.querySelector('.like-icon');
            const likeCount = likeBtn.querySelector('.like-count');
            
            if (!likeIcon || !likeCount) {
                console.error('找不到愛心圖標或計數器');
                return;
            }
            
            // 檢查當前狀態 - 實心愛心表示已點過
            const isLiked = likeIcon.textContent === '💖' || likeBtn.classList.contains('liked');
            const currentCount = parseInt(likeCount.textContent) || 0;
            
            // 保存原始狀態以便恢復
            const originalCount = currentCount;
            const originalIcon = likeIcon.textContent;
            const originalLiked = likeBtn.classList.contains('liked');
            
            // 立即更新視覺效果（樂觀更新）
            if (isLiked) {
                // 取消愛心 - 改為空心愛心
                likeCount.textContent = Math.max(0, currentCount - 1);
                likeIcon.textContent = '🤍';
                likeBtn.classList.remove('liked');
            } else {
                // 添加愛心 - 改為實心粉紅愛心
                likeCount.textContent = currentCount + 1;
                likeIcon.textContent = '💖';
                likeBtn.classList.add('liked');
            }
            
            // 暫時禁用按鈕防止重複點擊
            likeBtn.disabled = true;
            likeBtn.style.opacity = '0.7';
            likeBtn.style.cursor = 'wait';
            
            // 發送 AJAX 請求
            fetch('senior_messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=toggle_like&message_id=${messageId}&is_liked=${isLiked ? '1' : '0'}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // 如果後端返回了新的計數，使用它
                    if (data.new_count !== undefined) {
                        likeCount.textContent = data.new_count;
                    }
                    // 確保圖標狀態正確
                    if (data.action === 'liked') {
                        likeIcon.textContent = '💖';
                        likeBtn.classList.add('liked');
                    } else if (data.action === 'unliked') {
                        likeIcon.textContent = '🤍';
                        likeBtn.classList.remove('liked');
                    }
                    console.log(data.action === 'liked' ? '點讚成功' : data.action === 'unliked' ? '取消愛心成功' : data.message || '操作成功');
                } else {
                    // 如果失敗，恢復原狀
                    likeCount.textContent = originalCount;
                    likeIcon.textContent = originalIcon;
                    if (originalLiked) {
                        likeBtn.classList.add('liked');
                    } else {
                        likeBtn.classList.remove('liked');
                    }
                    alert('操作失敗: ' + (data.error || '未知錯誤'));
                }
            })
            .catch(error => {
                console.error('操作失敗:', error);
                // 如果失敗，恢復原狀
                likeCount.textContent = originalCount;
                likeIcon.textContent = originalIcon;
                if (originalLiked) {
                    likeBtn.classList.add('liked');
                    // 確保圖標是實心愛心
                    if (likeIcon.textContent !== '💖') {
                        likeIcon.textContent = '💖';
                    }
                } else {
                    likeBtn.classList.remove('liked');
                    // 確保圖標是空心愛心
                    if (likeIcon.textContent !== '🤍') {
                        likeIcon.textContent = '🤍';
                    }
                }
                alert('操作失敗，請檢查網路連線或稍後再試');
            })
            .finally(() => {
                // 重新啟用按鈕
                likeBtn.disabled = false;
                likeBtn.style.opacity = '1';
                likeBtn.style.cursor = 'pointer';
            });
        }
        
        // 增加瀏覽次數（所有用戶都可以觸發）
        document.querySelectorAll('.message-card').forEach(card => {
            // 嘗試從按鈕獲取 message_id，如果沒有按鈕則從 data 屬性獲取
            let messageId = null;
            const likeBtn = card.querySelector('button[onclick*="toggleLike"]');
            if (likeBtn) {
                const match = likeBtn.onclick.toString().match(/toggleLike\((\d+)\)/);
                if (match) {
                    messageId = match[1];
                }
            }
            
            // 如果還是找不到，嘗試從 data 屬性獲取
            if (!messageId) {
                const cardId = card.id || card.getAttribute('data-message-id');
                if (cardId) {
                    messageId = cardId.replace('message-', '');
                }
            }
            
            if (!messageId) return; // 如果找不到 messageId 就跳過
            
            // 使用 fetch 增加瀏覽次數（不重新載入頁面）
            fetch('senior_messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=view&message_id=' + messageId
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 更新顯示的瀏覽次數
                    const viewCount = card.querySelector('.view-count');
                    if (viewCount) {
                        const currentCount = parseInt(viewCount.textContent.match(/\d+/)?.[0] || 0);
                        viewCount.innerHTML = `👁️ ${currentCount + 1}`;
                    }
                }
            }).catch(error => console.log('瀏覽次數更新失敗:', error));
        });
        
        // 在地圖上顯示餐廳位置
        function showRestaurantOnMap(restaurantName, lat, lng, address) {
            // 構建地圖頁面 URL，帶上餐廳參數
            const mapUrl = `campus_map.php?restaurant=${encodeURIComponent(restaurantName)}&lat=${lat}&lng=${lng}&address=${encodeURIComponent(address)}`;
            // 在同一視窗打開，讓用戶可以直接使用地圖功能
            window.location.href = mapUrl;
        }
        
        // 顯示餐廳評價和留言
        function showRestaurantReviews(messageId, restaurantName) {
            // 打開評價和留言的模態框或新頁面
            window.location.href = `restaurant_reviews.php?message_id=${messageId}&restaurant=${encodeURIComponent(restaurantName)}`;
        }
    </script>
    
    <?php include("share/footer.php"); ?>
    <?php include("share/chat_widget.php"); ?>
    <?php include("share/ai_widget.php"); ?>
</body>
</html>
