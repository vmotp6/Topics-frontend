<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 資料庫連接
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $message_id = isset($_GET['message_id']) ? intval($_GET['message_id']) : 0;
    
    if ($message_id <= 0) {
        echo json_encode(['success' => false, 'error' => '無效的留言ID']);
        exit;
    }
    
    // 查詢留言（假設表已存在）
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.content,
            c.created_at,
            u.name as author_name,
            u.profile_picture as author_avatar
        FROM senior_message_comments c
        LEFT JOIN user u ON c.user_id = u.id
        WHERE c.message_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$message_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 處理時間格式和頭像路徑
    foreach ($comments as &$comment) {
        $comment['created_at'] = date('Y-m-d H:i', strtotime($comment['created_at']));
        
        // 處理頭像路徑
        if (!empty($comment['author_avatar'])) {
            if (strpos($comment['author_avatar'], 'http://') === 0 || strpos($comment['author_avatar'], 'https://') === 0) {
                // 完整 URL，直接使用
            } elseif (strpos($comment['author_avatar'], 'uploads/') === 0) {
                $comment['author_avatar'] = '/Topics-frontend/frontend/' . $comment['author_avatar'];
            } else {
                $comment['author_avatar'] = '/Topics-frontend/frontend/share/' . $comment['author_avatar'];
            }
        } else {
            $comment['author_avatar'] = '/Topics-frontend/frontend/share/EIdROxGXsAE_LSs.jpg';
        }
    }
    
    echo json_encode([
        'success' => true,
        'comments' => $comments
    ]);
    
} catch(PDOException $e) {
    // 如果表不存在，返回空陣列而不是錯誤
    if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "Table") !== false) {
        echo json_encode([
            'success' => true,
            'comments' => []
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => '載入留言失敗: ' . $e->getMessage()
        ]);
    }
}
?>

