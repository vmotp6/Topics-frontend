<?php
/**
 * 學長姐留言權限控制系統
 * 所有 @stu.ukn.edu.tw 的在校生（1-5年級）都可以留言
 */

class SeniorMessageAuth {
    private $pdo;
    
    public function __construct() {
        // 資料庫連接 - 使用與現有系統相同的配置
        $host = 'localhost';
        $dbname = 'topics_good';
        $username = 'root';
        $password = '';
        
        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            throw new Exception("資料庫連接失敗: " . $e->getMessage());
        }
    }
    
    /**
     * 檢查用戶是否有留言權限
     * @param string $email 用戶email
     * @return array 權限檢查結果
     */
    public function checkPermission($email) {
        // 檢查email格式
        if (!$this->isValidStudentEmail($email)) {
            return [
                'has_permission' => false,
                'error' => '只有 @stu.ukn.edu.tw 的學生帳號可以留言',
                'error_code' => 'INVALID_EMAIL'
            ];
        }
        
        // 從email提取年級資訊
        $grade_year = $this->extractGradeYear($email);
        if (!$grade_year) {
            return [
                'has_permission' => false,
                'error' => '無法從email中識別年級資訊',
                'error_code' => 'INVALID_GRADE'
            ];
        }
        
        // 計算學生當前年級
        $current_year = (int)date('Y');
        $current_roc_year = $current_year - 1911; // 民國年
        $current_grade = $current_roc_year - $grade_year + 1;
        
        // 檢查是否為在校生（1-5年級）
        if ($current_grade >= 1 && $current_grade <= 5) {
            return [
                'has_permission' => true,
                'grade_year' => $grade_year,
                'current_grade' => $current_grade,
                'message' => "您有留言權限（目前為{$current_grade}年級）"
            ];
        } else {
            return [
                'has_permission' => false,
                'error' => "只有在校生可以留言（您目前為{$current_grade}年級，需要1-5年級）",
                'error_code' => 'NOT_CURRENT_STUDENT',
                'grade_year' => $grade_year,
                'current_grade' => $current_grade
            ];
        }
    }
    
    /**
     * 檢查email是否為有效的學生email
     * @param string $email
     * @return bool
     */
    private function isValidStudentEmail($email) {
        // 檢查是否為標準email格式
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && 
            strpos($email, '@stu.ukn.edu.tw') !== false) {
            return true;
        }
        
        // 檢查是否為純學號格式（如：110534225）
        if (preg_match('/^\d{7,9}$/', $email)) {
            return true;
        }
        
        // 檢查是否為姓名+學號格式（如：尤世全110534225）
        if (preg_match('/^[\x{4e00}-\x{9fff}]+\d{7,9}$/u', $email)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 從email中提取年級年份
     * 支援格式：學號@stu.ukn.edu.tw、純學號、姓名+學號
     * @param string $email
     * @return int|null
     */
    private function extractGradeYear($email) {
        // 如果是標準email格式，提取@前的部分
        if (strpos($email, '@') !== false) {
            $username = explode('@', $email)[0];
        } else {
            // 如果是純學號格式，直接使用
            $username = $email;
        }
        
        // 如果是姓名+學號格式，提取學號部分
        if (preg_match('/^[\x{4e00}-\x{9fff}]+(\d{7,9})$/u', $username, $matches)) {
            $username = $matches[1]; // 提取學號部分
        }
        
        // 提取學號前3位作為年級
        if (preg_match('/^(\d{3})/', $username, $matches)) {
            return (int)$matches[1];
        }
        
        return null;
    }
    
    /**
     * 獲取用戶資訊
     * @param string $email
     * @return array|null
     */
    public function getUserInfo($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM user WHERE username = ?");
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return null;
        }
    }
    
    /**
     * 將中文類別名稱轉換為 post_categories 代碼
     * @param string $chineseName 中文類別名稱
     * @return string 類別代碼
     */
    private function convertMessageTypeToCode($chineseName) {
        $mapping = [
            '經驗分享' => 'EXP',
            '學習建議' => 'STD',
            '生活指南' => 'LIFE',
            '就業資訊' => 'CAR',
            '推薦餐廳' => 'REST',
            '其他' => 'OTH'
        ];
        
        return $mapping[$chineseName] ?? 'OTH';
    }
    
    /**
     * 獲取用戶ID（根據email或username）
     * @param string $emailOrUsername
     * @return int|null
     */
    private function getUserId($emailOrUsername) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM user WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$emailOrUsername, $emailOrUsername]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['id'] : null;
        } catch(PDOException $e) {
            error_log("獲取用戶ID失敗: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 創建留言
     * @param array $messageData
     * @return array
     */
    public function createMessage($messageData) {
        try {
            // 獲取用戶ID
            $user_id = $this->getUserId($messageData['author_email'] ?? '');
            if (!$user_id) {
                return [
                    'success' => false,
                    'error' => '無法找到用戶資料，請確認您已正確登入'
                ];
            }
            
            // 將中文類別名稱轉換為代碼
            $message_type_code = $this->convertMessageTypeToCode($messageData['message_type'] ?? '其他');
            
            // 檢查是否需要添加餐廳相關欄位
            $hasRestaurantData = isset($messageData['restaurant_name']) && !empty($messageData['restaurant_name']);
            
            // 檢查表是否有餐廳相關欄位
            $hasRestaurantColumns = $this->checkRestaurantColumns();
            
            if ($hasRestaurantData && $hasRestaurantColumns) {
                // 如果有餐廳資料且欄位存在，插入餐廳相關欄位
                $sql = "INSERT INTO senior_messages (user_id, title, content, author_contact, message_type, restaurant_name, restaurant_address, restaurant_lat, restaurant_lng, restaurant_place_id, restaurant_rating, delivery_rating, price_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                
                $result = $stmt->execute([
                    $user_id,
                    $messageData['title'],
                    $messageData['content'],
                    $messageData['author_contact'] ?? null,
                    $message_type_code,
                    $messageData['restaurant_name'] ?? null,
                    $messageData['restaurant_address'] ?? null,
                    $messageData['restaurant_lat'] ?? null,
                    $messageData['restaurant_lng'] ?? null,
                    $messageData['restaurant_place_id'] ?? null,
                    $messageData['restaurant_rating'] ?? null,
                    $messageData['delivery_rating'] ?? null,
                    $messageData['price_level'] ?? null
                ]);
            } else {
                // 如果沒有餐廳資料或欄位不存在，只插入基本欄位
                $sql = "INSERT INTO senior_messages (user_id, title, content, author_contact, message_type) VALUES (?, ?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                
                $result = $stmt->execute([
                    $user_id,
                    $messageData['title'],
                    $messageData['content'],
                    $messageData['author_contact'] ?? null,
                    $message_type_code
                ]);
            }
            
            if ($result) {
                return [
                    'success' => true,
                    'message_id' => $this->pdo->lastInsertId(),
                    'message' => '留言發布成功！'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => '留言發布失敗'
                ];
            }
        } catch(PDOException $e) {
            return [
                'success' => false,
                'error' => '資料庫錯誤: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 檢查表中是否有餐廳相關欄位
     * @return bool
     */
    private function checkRestaurantColumns() {
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM senior_messages LIKE 'restaurant_name'");
            return $stmt->rowCount() > 0;
        } catch(PDOException $e) {
            error_log("檢查餐廳欄位時發生錯誤: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 確保表中有餐廳相關欄位（如果需要可以調用此方法添加）
     */
    private function ensureRestaurantColumns() {
        try {
            // 檢查欄位是否存在
            if (!$this->checkRestaurantColumns()) {
                // 添加餐廳相關欄位
                $alterSql = "ALTER TABLE senior_messages 
                    ADD COLUMN restaurant_name VARCHAR(255) DEFAULT NULL COMMENT '餐廳名稱（僅用於推薦餐廳類型）',
                    ADD COLUMN restaurant_address VARCHAR(500) DEFAULT NULL COMMENT '餐廳地址',
                    ADD COLUMN restaurant_lat DECIMAL(10, 8) DEFAULT NULL COMMENT '餐廳緯度',
                    ADD COLUMN restaurant_lng DECIMAL(11, 8) DEFAULT NULL COMMENT '餐廳經度',
                    ADD COLUMN restaurant_place_id VARCHAR(255) DEFAULT NULL COMMENT 'Google Places ID',
                    ADD COLUMN restaurant_rating INT DEFAULT NULL COMMENT '餐廳評分 (1-5)',
                    ADD COLUMN delivery_rating INT DEFAULT NULL COMMENT '外送評分 (1-5)',
                    ADD COLUMN price_level INT DEFAULT NULL COMMENT '價格等級 (1-4)'";
                $this->pdo->exec($alterSql);
            }
        } catch(PDOException $e) {
            // 如果欄位已存在或其他錯誤，忽略
            error_log("添加餐廳欄位時發生錯誤: " . $e->getMessage());
        }
    }
    
    /**
     * 獲取用戶的年級顯示文字
     * @param int $grade_year
     * @return string
     */
    public function getGradeDisplay($grade_year) {
        $current_year = date('Y') - 1911; // 民國年
        $grade_level = $current_year - $grade_year + 1;
        
        if ($grade_level <= 0) {
            return '已畢業';
        } elseif ($grade_level > 5) {
            return '新生';
        } else {
            return $grade_level . '年級';
        }
    }
}

// 如果直接訪問此文件，返回權限檢查結果
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_permission') {
    header('Content-Type: application/json');
    
    $email = $_POST['email'] ?? '';
    $auth = new SeniorMessageAuth();
    $result = $auth->checkPermission($email);
    
    echo json_encode($result);
    exit;
}
?>
