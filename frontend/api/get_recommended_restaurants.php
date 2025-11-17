<?php
/**
 * 獲取推薦餐廳 API
 * 從 senior_messages 表中獲取所有推薦餐廳類型的留言
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
    
    // 獲取所有推薦餐廳類型的留言
    $stmt = $pdo->prepare("
        SELECT 
            id,
            title,
            content,
            restaurant_name,
            restaurant_address,
            restaurant_lat,
            restaurant_lng,
            restaurant_place_id,
            restaurant_rating,
            delivery_rating,
            price_level,
            author_name,
            created_at,
            view_count,
            like_count
        FROM senior_messages 
        WHERE message_type = '推薦餐廳' 
        AND is_published = 1 
        AND restaurant_name IS NOT NULL 
        AND restaurant_name != ''
        ORDER BY created_at DESC
    ");
    
    $stmt->execute();
    $recommendedRestaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 轉換為與 Google Places API 類似的格式
    $formattedRestaurants = [];
    foreach ($recommendedRestaurants as $restaurant) {
        // 獲取該餐廳的評價數量
        try {
            $reviewStmt = $pdo->prepare("SELECT COUNT(*) as count FROM restaurant_reviews WHERE message_id = ? AND is_published = 1");
            $reviewStmt->execute([$restaurant['id']]);
            $reviewCount = $reviewStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        } catch(PDOException $e) {
            $reviewCount = 0;
        }
        
        $formattedRestaurants[] = [
            'place_id' => $restaurant['restaurant_place_id'] ?? 'recommended_' . $restaurant['id'],
            'name' => $restaurant['restaurant_name'],
            'vicinity' => $restaurant['restaurant_address'],
            'formatted_address' => $restaurant['restaurant_address'],
            'geometry' => [
                'location' => [
                    'lat' => floatval($restaurant['restaurant_lat'] ?? 0),
                    'lng' => floatval($restaurant['restaurant_lng'] ?? 0)
                ]
            ],
            'rating' => floatval($restaurant['restaurant_rating'] ?? 0),
            'user_ratings_total' => $reviewCount,
            'price_level' => intval($restaurant['price_level'] ?? 0),
            'types' => ['restaurant', 'food', 'point_of_interest', 'establishment'],
            'is_recommended' => true, // 標記為推薦餐廳
            'recommendation_id' => $restaurant['id'],
            'recommendation_title' => $restaurant['title'],
            'recommendation_content' => $restaurant['content'],
            'recommendation_author' => $restaurant['author_name'],
            'recommendation_created_at' => $restaurant['created_at'],
            'recommendation_view_count' => intval($restaurant['view_count'] ?? 0),
            'recommendation_like_count' => intval($restaurant['like_count'] ?? 0),
            'delivery_rating' => $restaurant['delivery_rating'] ? intval($restaurant['delivery_rating']) : null,
            'hasDelivery' => $restaurant['delivery_rating'] !== null,
            'deliveryRating' => $restaurant['delivery_rating'] ? [
                'deliveryRating' => number_format($restaurant['delivery_rating'], 1)
            ] : null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'restaurants' => $formattedRestaurants,
        'count' => count($formattedRestaurants)
    ], JSON_UNESCAPED_UNICODE);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => '資料庫錯誤: ' . $e->getMessage(),
        'restaurants' => []
    ], JSON_UNESCAPED_UNICODE);
}

