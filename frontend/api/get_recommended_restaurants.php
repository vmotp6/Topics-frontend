<?php
/**
 * 獲取推薦餐廳 API
 * 從 senior_messages 表中獲取所有推薦餐廳類型的留言
 */

header('Content-Type: application/json; charset=utf-8');

// 載入 Google Maps API Key
require_once __DIR__ . '/../config.php';

/**
 * 從 Google Places API 獲取餐廳資訊
 */
function getRestaurantFromGoogle($restaurantName, $apiKey) {
    if (empty($restaurantName) || empty($apiKey)) {
        return null;
    }
    
    // 使用 Text Search API 搜尋餐廳
    $query = urlencode($restaurantName . ' 台北');
    $url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query={$query}&key={$apiKey}&language=zh-TW&region=TW";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        error_log("Google Places API 請求失敗: HTTP {$httpCode}");
        return null;
    }
    
    $data = json_decode($response, true);
    
    if ($data && isset($data['status']) && $data['status'] === 'OK' && !empty($data['results'])) {
        // 返回第一個結果
        $place = $data['results'][0];
        return [
            'place_id' => $place['place_id'] ?? null,
            'name' => $place['name'] ?? $restaurantName,
            'formatted_address' => $place['formatted_address'] ?? null,
            'vicinity' => $place['vicinity'] ?? $place['formatted_address'] ?? null,
            'lat' => isset($place['geometry']['location']['lat']) ? floatval($place['geometry']['location']['lat']) : null,
            'lng' => isset($place['geometry']['location']['lng']) ? floatval($place['geometry']['location']['lng']) : null,
            'rating' => isset($place['rating']) ? floatval($place['rating']) : null,
            'user_ratings_total' => isset($place['user_ratings_total']) ? intval($place['user_ratings_total']) : 0,
            'price_level' => isset($place['price_level']) ? intval($place['price_level']) : 0
        ];
    }
    
    return null;
}

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
        // 獲取該餐廳的評價數量和平均評分
        $reviewCount = 0;
        $avgRating = null;
        $avgDeliveryRating = null;
        try {
            $reviewStmt = $pdo->prepare("SELECT COUNT(*) as count, AVG(rating) as avg_rating, AVG(delivery_rating) as avg_delivery_rating FROM restaurant_reviews WHERE message_id = ? AND is_published = 1");
            $reviewStmt->execute([$restaurant['id']]);
            $reviewResult = $reviewStmt->fetch(PDO::FETCH_ASSOC);
            if ($reviewResult) {
                $reviewCount = intval($reviewResult['count'] ?? 0);
                $avgRating = $reviewResult['avg_rating'] ? floatval($reviewResult['avg_rating']) : null;
                $avgDeliveryRating = $reviewResult['avg_delivery_rating'] ? floatval($reviewResult['avg_delivery_rating']) : null;
            }
        } catch(PDOException $e) {
            $reviewCount = 0;
            $avgRating = null;
            $avgDeliveryRating = null;
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
        
        // 先初始化評分（稍後會從資料庫或 Google 獲取）
        $restaurantRating = null;
        if (!empty($restaurant['restaurant_rating']) && $restaurant['restaurant_rating'] != '0') {
            $rawRating = $restaurant['restaurant_rating'];
            $val = is_numeric($rawRating) ? floatval($rawRating) : 0;
            if ($val >= 1 && $val <= 5) {
                $restaurantRating = $val;
            }
        }
        if ($restaurantRating === null && $avgRating !== null && $avgRating >= 1 && $avgRating <= 5) {
            $restaurantRating = round($avgRating, 1);
        }
        if ($restaurantRating === null) {
            $restaurantRating = 0;
        }
        
        // 如果沒有地址或座標，嘗試從 Google Places API 獲取
        $googlePlaceId = $restaurant['restaurant_place_id'] ?? null;
        if (($lat == 0 && $lng == 0) && empty($restaurantAddress)) {
            $apiKey = defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : '';
            if (!empty($apiKey)) {
                error_log('嘗試從 Google 獲取餐廳資訊: ' . $restaurantName);
                $googleInfo = getRestaurantFromGoogle($restaurantName, $apiKey);
                if ($googleInfo) {
                    // 使用從 Google 獲取的資訊
                    if (empty($restaurantAddress) && !empty($googleInfo['formatted_address'])) {
                        $restaurantAddress = $googleInfo['formatted_address'];
                    }
                    if ($lat == 0 && $lng == 0 && $googleInfo['lat'] !== null && $googleInfo['lng'] !== null) {
                        $lat = $googleInfo['lat'];
                        $lng = $googleInfo['lng'];
                    }
                    if (empty($googlePlaceId) && !empty($googleInfo['place_id'])) {
                        $googlePlaceId = $googleInfo['place_id'];
                    }
                    // 如果沒有評分，使用 Google 的評分
                    if ($restaurantRating == 0 && $googleInfo['rating'] !== null) {
                        $restaurantRating = $googleInfo['rating'];
                    }
                    // 如果沒有評價數量，使用 Google 的評價數量
                    if ($reviewCount == 0 && $googleInfo['user_ratings_total'] > 0) {
                        $reviewCount = $googleInfo['user_ratings_total'];
                    }
                    // 如果沒有價格等級，使用 Google 的價格等級
                    if (empty($restaurant['price_level']) && $googleInfo['price_level'] > 0) {
                        $restaurant['price_level'] = $googleInfo['price_level'];
                    }
                    error_log('成功從 Google 獲取餐廳資訊: ' . $restaurantName . ', 地址: ' . $restaurantAddress);
                } else {
                    error_log('無法從 Google 獲取餐廳資訊: ' . $restaurantName);
                }
            }
        }
        
        // 如果沒有座標但有地址，標記為需要地理編碼
        $needsGeocoding = ($lat == 0 && $lng == 0) && !empty($restaurantAddress);
        
        // 如果還是沒有座標也沒有地址，設置為 null
        if ($lat == 0 && $lng == 0 && empty($restaurantAddress)) {
            error_log('推薦餐廳無座標且無地址: ' . $restaurantName);
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
        
        
        // 確定外送評分（優先順序：delivery_rating > restaurant_reviews 平均外送評分）
        $deliveryRating = null;
        if (!empty($restaurant['delivery_rating']) && $restaurant['delivery_rating'] != '0') {
            $rawDeliveryRating = $restaurant['delivery_rating'];
            $val = is_numeric($rawDeliveryRating) ? intval($rawDeliveryRating) : 0;
            if ($val >= 1 && $val <= 5) {
                $deliveryRating = $val;
            }
        }
        
        // 如果沒有從 delivery_rating 獲取到評分，使用 restaurant_reviews 的平均外送評分
        if ($deliveryRating === null && $avgDeliveryRating !== null && $avgDeliveryRating >= 1 && $avgDeliveryRating <= 5) {
            $deliveryRating = round($avgDeliveryRating);
        }
        
        $formattedRestaurants[] = [
            'place_id' => $googlePlaceId ?? ($restaurant['restaurant_place_id'] ?? 'recommended_' . $restaurant['id']),
            'name' => $restaurantName,
            'vicinity' => $restaurantAddress ?: $restaurantName,
            'formatted_address' => $restaurantAddress ?: $restaurantName,
            'geometry' => $geometry,
            'needs_geocoding' => $needsGeocoding, // 標記是否需要地理編碼
            'rating' => $restaurantRating,
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
            'delivery_rating' => $deliveryRating,
            'hasDelivery' => $deliveryRating !== null,
            'deliveryRating' => $deliveryRating ? [
                'deliveryRating' => number_format($deliveryRating, 1)
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

