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
    
    // 檢查餐廳相關欄位是否存在
    $has_restaurant_fields = false;
    try {
        $check_sql = "SHOW COLUMNS FROM senior_messages LIKE 'restaurant_name'";
        $check_stmt = $pdo->query($check_sql);
        $has_restaurant_fields = $check_stmt->rowCount() > 0;
    } catch(PDOException $e) {
        $has_restaurant_fields = false;
    }
    
    // 構建查詢條件：推薦餐廳類型
    // 注意：senior_message_auth.php 會將「推薦餐廳」轉換為代碼 'REST' 存入資料庫
    // 所以主要檢查 message_type = 'REST'
    // 但也檢查中文「推薦餐廳」以防有舊數據
    // 同時檢查 post_categories 表中的對應關係
    $where_conditions = [
        "(sm.message_type = 'REST' OR sm.message_type = '推薦餐廳' OR pc.code = 'REST' OR pc.name = '推薦餐廳')"
    ];
    
    // 如果有餐廳欄位，檢查餐廳名稱或標題（至少要有其中一個）
    if ($has_restaurant_fields) {
        $where_conditions[] = "((sm.restaurant_name IS NOT NULL AND sm.restaurant_name != '') OR (sm.title IS NOT NULL AND sm.title != ''))";
    } else {
        // 如果沒有餐廳欄位，只要有標題就可以（因為推薦餐廳時標題就是餐廳名稱）
        $where_conditions[] = "(sm.title IS NOT NULL AND sm.title != '')";
    }
    
    // 調試：記錄查詢條件
    error_log('查詢條件: ' . implode(' AND ', $where_conditions));
    
    // 構建 SELECT 語句（與 senior_messages.php 保持一致）
    $select_fields = "
        sm.id,
        sm.user_id,
        sm.title,
        sm.content,
        sm.created_at,
        sm.view_count,
        sm.like_count,
        COALESCE(pc.name, sm.message_type, '其他') as message_type_name,
        u.name as author_name
    ";
    
    // 如果有餐廳欄位，添加餐廳相關欄位
    if ($has_restaurant_fields) {
        $select_fields .= ",
            sm.restaurant_name,
            sm.restaurant_address,
            sm.restaurant_lat,
            sm.restaurant_lng,
            sm.restaurant_place_id,
            sm.restaurant_rating,
            sm.delivery_rating,
            sm.price_level
        ";
    } else {
        // 如果沒有餐廳欄位，設置為 NULL
        $select_fields .= ",
            NULL as restaurant_name,
            NULL as restaurant_address,
            NULL as restaurant_lat,
            NULL as restaurant_lng,
            NULL as restaurant_place_id,
            NULL as restaurant_rating,
            NULL as delivery_rating,
            NULL as price_level
        ";
    }
    
    $sql = "
        SELECT 
            $select_fields
        FROM senior_messages sm
        LEFT JOIN user u ON sm.user_id = u.id
        LEFT JOIN post_categories pc ON sm.message_type = pc.code
        WHERE " . implode(' AND ', $where_conditions) . "
        ORDER BY sm.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute();
    $recommendedRestaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log('查詢到推薦餐廳數量: ' . count($recommendedRestaurants));
    error_log('SQL 查詢: ' . $sql);
    if (count($recommendedRestaurants) > 0) {
        error_log('第一條記錄: ' . json_encode($recommendedRestaurants[0], JSON_UNESCAPED_UNICODE));
    }
    
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
        
        // 確定餐廳名稱（優先使用 restaurant_name，如果沒有則使用 title）
        $restaurantName = trim($restaurant['restaurant_name'] ?? '');
        if (empty($restaurantName)) {
            $restaurantName = trim($restaurant['title'] ?? '推薦餐廳');
        }
        
        // 如果餐廳名稱為空，跳過
        if (empty($restaurantName)) {
            error_log('跳過餐廳（無名稱）: ID=' . $restaurant['id']);
            continue;
        }
        
        // 確定地址
        $restaurantAddress = trim($restaurant['restaurant_address'] ?? '');
        
        // 確定座標（如果沒有座標但有地址，可以稍後進行地理編碼）
        $lat = floatval($restaurant['restaurant_lat'] ?? 0);
        $lng = floatval($restaurant['restaurant_lng'] ?? 0);
        
        // 如果沒有座標但有地址，仍然返回（前端可以進行地理編碼）
        // 如果既沒有座標也沒有地址，仍然返回（至少可以顯示在列表中，只是不會在地圖上顯示標記）
        // 移除跳過邏輯，讓所有推薦餐廳都能顯示在列表中
        // if (($lat == 0 && $lng == 0) && empty($restaurantAddress)) {
        //     error_log('跳過餐廳（無座標且無地址）: ' . $restaurantName);
        //     continue;
        // }
        
        // 如果沒有座標但有地址，標記為需要地理編碼
        $needsGeocoding = ($lat == 0 && $lng == 0) && !empty($restaurantAddress);
        
        // 如果沒有座標也沒有地址，仍然返回（顯示在列表中，但不在地圖上顯示標記）
        // 使用 null 作為座標標記，前端會跳過地圖標記但顯示在列表中
        if ($lat == 0 && $lng == 0 && empty($restaurantAddress)) {
            error_log('推薦餐廳無座標且無地址，將顯示在列表中但不顯示地圖標記: ' . $restaurantName);
            $lat = null;
            $lng = null;
            $needsGeocoding = false;
        }
        
        // 構建 geometry 對象
        $geometry = null;
        if ($lat !== null && $lng !== null) {
            $geometry = [
                'location' => [
                    'lat' => $lat,
                    'lng' => $lng
                ]
            ];
        } else {
            // 如果沒有座標，設置為 null，前端會跳過地圖標記
            $geometry = [
                'location' => null
            ];
        }
        
        $formattedRestaurants[] = [
            'place_id' => $restaurant['restaurant_place_id'] ?? 'recommended_' . $restaurant['id'],
            'name' => $restaurantName,
            'vicinity' => $restaurantAddress ?: $restaurantName,
            'formatted_address' => $restaurantAddress ?: $restaurantName,
            'geometry' => $geometry,
            'needs_geocoding' => $needsGeocoding, // 標記是否需要地理編碼
            'rating' => floatval($restaurant['restaurant_rating'] ?? 0),
            'user_ratings_total' => $reviewCount,
            'price_level' => intval($restaurant['price_level'] ?? 0),
            'types' => ['restaurant', 'food', 'point_of_interest', 'establishment'],
            'is_recommended' => true, // 標記為推薦餐廳
            'recommendation_id' => $restaurant['id'],
            'recommendation_title' => $restaurant['title'],
            'recommendation_content' => $restaurant['content'],
            'recommendation_author' => $restaurant['author_name'] ?? '學長姐',
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
    
    // 輸出結果
    $result = [
        'success' => true,
        'restaurants' => $formattedRestaurants,
        'count' => count($formattedRestaurants),
        'debug' => [
            'raw_count' => count($recommendedRestaurants),
            'formatted_count' => count($formattedRestaurants),
            'sql' => $sql
        ]
    ];
    
    error_log('API 返回結果: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch(PDOException $e) {
    error_log('API 錯誤: ' . $e->getMessage());
    error_log('錯誤堆疊: ' . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'error' => '資料庫錯誤: ' . $e->getMessage(),
        'restaurants' => [],
        'debug' => [
            'error_type' => 'PDOException',
            'error_message' => $e->getMessage()
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch(Exception $e) {
    error_log('API 一般錯誤: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => '系統錯誤: ' . $e->getMessage(),
        'restaurants' => [],
        'debug' => [
            'error_type' => 'Exception',
            'error_message' => $e->getMessage()
        ]
    ], JSON_UNESCAPED_UNICODE);
}

