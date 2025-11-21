<?php
/**
 * 獲取餐廳評論 API
 * 整合學長姐留言板的評論和 Google Maps 評論
 */

header('Content-Type: application/json; charset=utf-8');

// 資料庫連接
$host = 'localhost';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 獲取參數
    $place_id = $_GET['place_id'] ?? '';
    $restaurant_name = $_GET['restaurant_name'] ?? '';
    $message_id = $_GET['message_id'] ?? null;
    
    $allReviews = [];
    
    // 1. 獲取學長姐留言板的評論（如果有 message_id）
    if ($message_id) {
        try {
            // 獲取該餐廳推薦的所有評價
            $stmt = $pdo->prepare("
                SELECT 
                    id,
                    message_id,
                    author_name,
                    rating,
                    delivery_rating,
                    review_content,
                    created_at
                FROM restaurant_reviews 
                WHERE message_id = ? 
                AND is_published = 1 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$message_id]);
            $seniorReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($seniorReviews as $review) {
                $allReviews[] = [
                    'source' => 'senior',
                    'author_name' => $review['author_name'],
                    'rating' => intval($review['rating']),
                    'delivery_rating' => $review['delivery_rating'] ? intval($review['delivery_rating']) : null,
                    'text' => $review['review_content'],
                    'time' => strtotime($review['created_at']),
                    'isDelivery' => $review['delivery_rating'] !== null,
                    'review_id' => $review['id']
                ];
            }
        } catch(PDOException $e) {
            // 如果表不存在，忽略錯誤
        }
    }
    
    // 2. 如果有 place_id，也嘗試從學長姐留言板獲取相同餐廳的其他推薦
    if ($place_id && !$message_id) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    sm.id as message_id,
                    sm.restaurant_name,
                    sm.restaurant_place_id,
                    sm.title,
                    sm.content,
                    sm.author_name,
                    sm.restaurant_rating,
                    sm.delivery_rating,
                    sm.created_at
                FROM senior_messages sm
                WHERE sm.message_type = '推薦餐廳' 
                AND sm.is_published = 1 
                AND sm.restaurant_place_id = ?
                ORDER BY sm.created_at DESC
            ");
            $stmt->execute([$place_id]);
            $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($recommendations as $rec) {
                // 將推薦內容作為評論
                $allReviews[] = [
                    'source' => 'senior',
                    'author_name' => $rec['author_name'],
                    'rating' => intval($rec['restaurant_rating'] ?? 0),
                    'delivery_rating' => $rec['delivery_rating'] ? intval($rec['delivery_rating']) : null,
                    'text' => $rec['content'],
                    'time' => strtotime($rec['created_at']),
                    'isDelivery' => $rec['delivery_rating'] !== null,
                    'title' => $rec['title'],
                    'message_id' => $rec['message_id']
                ];
                
                // 獲取該推薦的評價
                try {
                    $reviewStmt = $pdo->prepare("
                        SELECT 
                            id,
                            author_name,
                            rating,
                            delivery_rating,
                            review_content,
                            created_at
                        FROM restaurant_reviews 
                        WHERE message_id = ? 
                        AND is_published = 1 
                        ORDER BY created_at DESC
                    ");
                    $reviewStmt->execute([$rec['message_id']]);
                    $recReviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($recReviews as $review) {
                        $allReviews[] = [
                            'source' => 'senior',
                            'author_name' => $review['author_name'],
                            'rating' => intval($review['rating']),
                            'delivery_rating' => $review['delivery_rating'] ? intval($review['delivery_rating']) : null,
                            'text' => $review['review_content'],
                            'time' => strtotime($review['created_at']),
                            'isDelivery' => $review['delivery_rating'] !== null,
                            'review_id' => $review['id']
                        ];
                    }
                } catch(PDOException $e) {
                    // 忽略錯誤
                }
            }
        } catch(PDOException $e) {
            // 忽略錯誤
        }
    }
    
    // 按時間排序（最新的在前）
    usort($allReviews, function($a, $b) {
        return $b['time'] - $a['time'];
    });
    
    echo json_encode([
        'success' => true,
        'reviews' => $allReviews,
        'count' => count($allReviews),
        'sources' => [
            'senior' => count(array_filter($allReviews, function($r) { return $r['source'] === 'senior'; })),
            'google' => count(array_filter($allReviews, function($r) { return $r['source'] === 'google'; }))
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => '資料庫錯誤: ' . $e->getMessage(),
        'reviews' => []
    ], JSON_UNESCAPED_UNICODE);
}

