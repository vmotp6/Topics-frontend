<?php
/**
 * 快速初始化學校資料
 * 直接插入一些基本的國民中學資料
 */

// 載入 session 配置
require_once 'session_config.php';

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("資料庫連接失敗: " . $e->getMessage());
}

echo "<h1>🚀 快速初始化學校資料</h1>";

// 檢查資料表是否存在
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'school_data'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color: red;'>❌ 資料表不存在，正在創建...</p>";
        
        // 創建資料表
        $createTableSQL = "
        CREATE TABLE school_data (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL COMMENT '學校名稱',
            city VARCHAR(20) NOT NULL COMMENT '縣市',
            district VARCHAR(20) NOT NULL COMMENT '區/鄉鎮市',
            type VARCHAR(20) NOT NULL COMMENT '學校類型',
            school_code VARCHAR(20) DEFAULT NULL COMMENT '學校代碼',
            is_active TINYINT(1) DEFAULT 1 COMMENT '是否營運中',
            data_source VARCHAR(100) DEFAULT '快速初始化' COMMENT '資料來源',
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_name (name),
            INDEX idx_city (city),
            INDEX idx_type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $pdo->exec($createTableSQL);
        echo "<p style='color: green;'>✅ 資料表創建成功</p>";
    } else {
        echo "<p style='color: green;'>✅ 資料表已存在</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ 創建資料表失敗: " . $e->getMessage() . "</p>";
    exit;
}

// 清空現有資料
try {
    $pdo->exec("DELETE FROM school_data WHERE type = '國民中學'");
    echo "<p>🗑️ 清空現有國民中學資料</p>";
} catch (PDOException $e) {
    echo "<p style='color: orange;'>⚠️ 清空資料失敗: " . $e->getMessage() . "</p>";
}

// 準備學校資料
$schools = [
    // 台北市
    ['name' => '中正國中', 'city' => '台北市', 'district' => '中正區', 'type' => '國民中學', 'school_code' => 'TP001'],
    ['name' => '西松國中', 'city' => '台北市', 'district' => '松山區', 'type' => '國民中學', 'school_code' => 'TP002'],
    ['name' => '永吉國中', 'city' => '台北市', 'district' => '信義區', 'type' => '國民中學', 'school_code' => 'TP003'],
    ['name' => '中崙國中', 'city' => '台北市', 'district' => '松山區', 'type' => '國民中學', 'school_code' => 'TP004'],
    ['name' => '信義國中', 'city' => '台北市', 'district' => '信義區', 'type' => '國民中學', 'school_code' => 'TP005'],
    ['name' => '松山國中', 'city' => '台北市', 'district' => '松山區', 'type' => '國民中學', 'school_code' => 'TP006'],
    ['name' => '敦化國中', 'city' => '台北市', 'district' => '松山區', 'type' => '國民中學', 'school_code' => 'TP007'],
    ['name' => '介壽國中', 'city' => '台北市', 'district' => '松山區', 'type' => '國民中學', 'school_code' => 'TP008'],
    ['name' => '南港國中', 'city' => '台北市', 'district' => '南港區', 'type' => '國民中學', 'school_code' => 'TP009'],
    ['name' => '內湖國中', 'city' => '台北市', 'district' => '內湖區', 'type' => '國民中學', 'school_code' => 'TP010'],
    ['name' => '麗山國中', 'city' => '台北市', 'district' => '內湖區', 'type' => '國民中學', 'school_code' => 'TP011'],
    ['name' => '大直國中', 'city' => '台北市', 'district' => '中山區', 'type' => '國民中學', 'school_code' => 'TP012'],
    ['name' => '百齡國中', 'city' => '台北市', 'district' => '士林區', 'type' => '國民中學', 'school_code' => 'TP013'],
    ['name' => '陽明國中', 'city' => '台北市', 'district' => '士林區', 'type' => '國民中學', 'school_code' => 'TP014'],
    ['name' => '萬華國中', 'city' => '台北市', 'district' => '萬華區', 'type' => '國民中學', 'school_code' => 'TP015'],
    ['name' => '大理國中', 'city' => '台北市', 'district' => '萬華區', 'type' => '國民中學', 'school_code' => 'TP016'],
    ['name' => '華江國中', 'city' => '台北市', 'district' => '萬華區', 'type' => '國民中學', 'school_code' => 'TP017'],
    ['name' => '成淵國中', 'city' => '台北市', 'district' => '大同區', 'type' => '國民中學', 'school_code' => 'TP018'],
    ['name' => '雙園國中', 'city' => '台北市', 'district' => '萬華區', 'type' => '國民中學', 'school_code' => 'TP019'],
    ['name' => '龍山國中', 'city' => '台北市', 'district' => '萬華區', 'type' => '國民中學', 'school_code' => 'TP020'],
    ['name' => '螢橋國中', 'city' => '台北市', 'district' => '中正區', 'type' => '國民中學', 'school_code' => 'TP021'],
    ['name' => '古亭國中', 'city' => '台北市', 'district' => '中正區', 'type' => '國民中學', 'school_code' => 'TP022'],
    ['name' => '景美國中', 'city' => '台北市', 'district' => '文山區', 'type' => '國民中學', 'school_code' => 'TP023'],
    ['name' => '木柵國中', 'city' => '台北市', 'district' => '文山區', 'type' => '國民中學', 'school_code' => 'TP024'],
    ['name' => '實踐國中', 'city' => '台北市', 'district' => '文山區', 'type' => '國民中學', 'school_code' => 'TP025'],
    ['name' => '興福國中', 'city' => '台北市', 'district' => '文山區', 'type' => '國民中學', 'school_code' => 'TP026'],
    ['name' => '文山國中', 'city' => '台北市', 'district' => '文山區', 'type' => '國民中學', 'school_code' => 'TP027'],
    ['name' => '北政國中', 'city' => '台北市', 'district' => '文山區', 'type' => '國民中學', 'school_code' => 'TP028'],
    ['name' => '景興國中', 'city' => '台北市', 'district' => '文山區', 'type' => '國民中學', 'school_code' => 'TP029'],
    ['name' => '萬芳國中', 'city' => '台北市', 'district' => '文山區', 'type' => '國民中學', 'school_code' => 'TP030'],
    
    // 新北市
    ['name' => '板橋國中', 'city' => '新北市', 'district' => '板橋區', 'type' => '國民中學', 'school_code' => 'NT001'],
    ['name' => '海山國中', 'city' => '新北市', 'district' => '板橋區', 'type' => '國民中學', 'school_code' => 'NT002'],
    ['name' => '新莊國中', 'city' => '新北市', 'district' => '新莊區', 'type' => '國民中學', 'school_code' => 'NT003'],
    ['name' => '丹鳳國中', 'city' => '新北市', 'district' => '新莊區', 'type' => '國民中學', 'school_code' => 'NT004'],
    ['name' => '泰山國中', 'city' => '新北市', 'district' => '泰山區', 'type' => '國民中學', 'school_code' => 'NT005'],
    ['name' => '林口國中', 'city' => '新北市', 'district' => '林口區', 'type' => '國民中學', 'school_code' => 'NT006'],
    ['name' => '五股國中', 'city' => '新北市', 'district' => '五股區', 'type' => '國民中學', 'school_code' => 'NT007'],
    ['name' => '蘆洲國中', 'city' => '新北市', 'district' => '蘆洲區', 'type' => '國民中學', 'school_code' => 'NT008'],
    ['name' => '三重國中', 'city' => '新北市', 'district' => '三重區', 'type' => '國民中學', 'school_code' => 'NT009'],
    ['name' => '永和國中', 'city' => '新北市', 'district' => '永和區', 'type' => '國民中學', 'school_code' => 'NT010'],
    ['name' => '永平國中', 'city' => '新北市', 'district' => '永和區', 'type' => '國民中學', 'school_code' => 'NT011'],
    ['name' => '中和國中', 'city' => '新北市', 'district' => '中和區', 'type' => '國民中學', 'school_code' => 'NT012'],
    ['name' => '錦和國中', 'city' => '新北市', 'district' => '中和區', 'type' => '國民中學', 'school_code' => 'NT013'],
    ['name' => '新店國中', 'city' => '新北市', 'district' => '新店區', 'type' => '國民中學', 'school_code' => 'NT014'],
    ['name' => '安康國中', 'city' => '新北市', 'district' => '新店區', 'type' => '國民中學', 'school_code' => 'NT015'],
    ['name' => '石碇國中', 'city' => '新北市', 'district' => '石碇區', 'type' => '國民中學', 'school_code' => 'NT016'],
    ['name' => '深坑國中', 'city' => '新北市', 'district' => '深坑區', 'type' => '國民中學', 'school_code' => 'NT017'],
    ['name' => '坪林國中', 'city' => '新北市', 'district' => '坪林區', 'type' => '國民中學', 'school_code' => 'NT018'],
    ['name' => '烏來國中', 'city' => '新北市', 'district' => '烏來區', 'type' => '國民中學', 'school_code' => 'NT019'],
    ['name' => '三峽國中', 'city' => '新北市', 'district' => '三峽區', 'type' => '國民中學', 'school_code' => 'NT020'],
    ['name' => '明德國中', 'city' => '新北市', 'district' => '三峽區', 'type' => '國民中學', 'school_code' => 'NT021'],
    ['name' => '樹林國中', 'city' => '新北市', 'district' => '樹林區', 'type' => '國民中學', 'school_code' => 'NT022'],
    ['name' => '鶯歌國中', 'city' => '新北市', 'district' => '鶯歌區', 'type' => '國民中學', 'school_code' => 'NT023'],
    ['name' => '三芝國中', 'city' => '新北市', 'district' => '三芝區', 'type' => '國民中學', 'school_code' => 'NT024'],
    ['name' => '石門國中', 'city' => '新北市', 'district' => '石門區', 'type' => '國民中學', 'school_code' => 'NT025'],
    ['name' => '金山國中', 'city' => '新北市', 'district' => '金山區', 'type' => '國民中學', 'school_code' => 'NT026'],
    ['name' => '萬里國中', 'city' => '新北市', 'district' => '萬里區', 'type' => '國民中學', 'school_code' => 'NT027'],
    ['name' => '瑞芳國中', 'city' => '新北市', 'district' => '瑞芳區', 'type' => '國民中學', 'school_code' => 'NT028'],
    ['name' => '雙溪國中', 'city' => '新北市', 'district' => '雙溪區', 'type' => '國民中學', 'school_code' => 'NT029'],
    ['name' => '貢寮國中', 'city' => '新北市', 'district' => '貢寮區', 'type' => '國民中學', 'school_code' => 'NT030'],
    ['name' => '平溪國中', 'city' => '新北市', 'district' => '平溪區', 'type' => '國民中學', 'school_code' => 'NT031'],
    ['name' => '汐止國中', 'city' => '新北市', 'district' => '汐止區', 'type' => '國民中學', 'school_code' => 'NT032'],
    ['name' => '秀峰國中', 'city' => '新北市', 'district' => '汐止區', 'type' => '國民中學', 'school_code' => 'NT033'],
    ['name' => '淡水國中', 'city' => '新北市', 'district' => '淡水區', 'type' => '國民中學', 'school_code' => 'NT034'],
    ['name' => '淡江國中', 'city' => '新北市', 'district' => '淡水區', 'type' => '國民中學', 'school_code' => 'NT035'],
    ['name' => '竹圍國中', 'city' => '新北市', 'district' => '淡水區', 'type' => '國民中學', 'school_code' => 'NT036'],
    
    // 桃園市
    ['name' => '桃園國中', 'city' => '桃園市', 'district' => '桃園區', 'type' => '國民中學', 'school_code' => 'TY001'],
    ['name' => '中壢國中', 'city' => '桃園市', 'district' => '中壢區', 'type' => '國民中學', 'school_code' => 'TY002'],
    ['name' => '大園國中', 'city' => '桃園市', 'district' => '大園區', 'type' => '國民中學', 'school_code' => 'TY003'],
    ['name' => '蘆竹國中', 'city' => '桃園市', 'district' => '蘆竹區', 'type' => '國民中學', 'school_code' => 'TY004'],
    ['name' => '南崁國中', 'city' => '桃園市', 'district' => '蘆竹區', 'type' => '國民中學', 'school_code' => 'TY005'],
    ['name' => '龜山國中', 'city' => '桃園市', 'district' => '龜山區', 'type' => '國民中學', 'school_code' => 'TY006'],
    ['name' => '八德國中', 'city' => '桃園市', 'district' => '八德區', 'type' => '國民中學', 'school_code' => 'TY007'],
    ['name' => '大溪國中', 'city' => '桃園市', 'district' => '大溪區', 'type' => '國民中學', 'school_code' => 'TY008'],
    ['name' => '復興國中', 'city' => '桃園市', 'district' => '復興區', 'type' => '國民中學', 'school_code' => 'TY009'],
    ['name' => '龍潭國中', 'city' => '桃園市', 'district' => '龍潭區', 'type' => '國民中學', 'school_code' => 'TY010'],
    ['name' => '新屋國中', 'city' => '桃園市', 'district' => '新屋區', 'type' => '國民中學', 'school_code' => 'TY011'],
    ['name' => '觀音國中', 'city' => '桃園市', 'district' => '觀音區', 'type' => '國民中學', 'school_code' => 'TY012'],
    ['name' => '草漯國中', 'city' => '桃園市', 'district' => '觀音區', 'type' => '國民中學', 'school_code' => 'TY013'],
    
    // 基隆市
    ['name' => '基隆國中', 'city' => '基隆市', 'district' => '中正區', 'type' => '國民中學', 'school_code' => 'KL001'],
    ['name' => '安樂國中', 'city' => '基隆市', 'district' => '安樂區', 'type' => '國民中學', 'school_code' => 'KL002'],
    ['name' => '八斗國中', 'city' => '基隆市', 'district' => '中正區', 'type' => '國民中學', 'school_code' => 'KL003'],
    ['name' => '正濱國中', 'city' => '基隆市', 'district' => '中正區', 'type' => '國民中學', 'school_code' => 'KL004'],
    ['name' => '信義國中', 'city' => '基隆市', 'district' => '信義區', 'type' => '國民中學', 'school_code' => 'KL005'],
    ['name' => '暖暖國中', 'city' => '基隆市', 'district' => '暖暖區', 'type' => '國民中學', 'school_code' => 'KL006'],
    ['name' => '碇內國中', 'city' => '基隆市', 'district' => '暖暖區', 'type' => '國民中學', 'school_code' => 'KL007'],
    ['name' => '七堵國中', 'city' => '基隆市', 'district' => '七堵區', 'type' => '國民中學', 'school_code' => 'KL008'],
    ['name' => '百福國中', 'city' => '基隆市', 'district' => '七堵區', 'type' => '國民中學', 'school_code' => 'KL009'],
    
    // 新竹市
    ['name' => '新竹國中', 'city' => '新竹市', 'district' => '東區', 'type' => '國民中學', 'school_code' => 'HSC001'],
    ['name' => '光復國中', 'city' => '新竹市', 'district' => '東區', 'type' => '國民中學', 'school_code' => 'HSC002'],
    ['name' => '香山國中', 'city' => '新竹市', 'district' => '香山區', 'type' => '國民中學', 'school_code' => 'HSC003'],
    ['name' => '成德國中', 'city' => '新竹市', 'district' => '北區', 'type' => '國民中學', 'school_code' => 'HSC004'],
    ['name' => '建功國中', 'city' => '新竹市', 'district' => '東區', 'type' => '國民中學', 'school_code' => 'HSC005'],
    
    // 新竹縣
    ['name' => '竹北國中', 'city' => '新竹縣', 'district' => '竹北市', 'type' => '國民中學', 'school_code' => 'HSH001'],
    ['name' => '六家國中', 'city' => '新竹縣', 'district' => '竹北市', 'type' => '國民中學', 'school_code' => 'HSH002'],
    ['name' => '湖口國中', 'city' => '新竹縣', 'district' => '湖口鄉', 'type' => '國民中學', 'school_code' => 'HSH003'],
    ['name' => '新湖國中', 'city' => '新竹縣', 'district' => '湖口鄉', 'type' => '國民中學', 'school_code' => 'HSH004'],
    ['name' => '新豐國中', 'city' => '新竹縣', 'district' => '新豐鄉', 'type' => '國民中學', 'school_code' => 'HSH005'],
    ['name' => '關西國中', 'city' => '新竹縣', 'district' => '關西鎮', 'type' => '國民中學', 'school_code' => 'HSH006'],
    ['name' => '芎林國中', 'city' => '新竹縣', 'district' => '芎林鄉', 'type' => '國民中學', 'school_code' => 'HSH007'],
    ['name' => '橫山國中', 'city' => '新竹縣', 'district' => '橫山鄉', 'type' => '國民中學', 'school_code' => 'HSH008'],
    ['name' => '北埔國中', 'city' => '新竹縣', 'district' => '北埔鄉', 'type' => '國民中學', 'school_code' => 'HSH009'],
    ['name' => '峨眉國中', 'city' => '新竹縣', 'district' => '峨眉鄉', 'type' => '國民中學', 'school_code' => 'HSH010'],
    ['name' => '寶山國中', 'city' => '新竹縣', 'district' => '寶山鄉', 'type' => '國民中學', 'school_code' => 'HSH011'],
    ['name' => '竹東國中', 'city' => '新竹縣', 'district' => '竹東鎮', 'type' => '國民中學', 'school_code' => 'HSH012'],
    ['name' => '五峰國中', 'city' => '新竹縣', 'district' => '五峰鄉', 'type' => '國民中學', 'school_code' => 'HSH013'],
    ['name' => '尖石國中', 'city' => '新竹縣', 'district' => '尖石鄉', 'type' => '國民中學', 'school_code' => 'HSH014'],
    
    // 台中市
    ['name' => '台中一中', 'city' => '台中市', 'district' => '北區', 'type' => '國民中學', 'school_code' => 'TC001'],
    ['name' => '台中女中', 'city' => '台中市', 'district' => '西區', 'type' => '國民中學', 'school_code' => 'TC002'],
    ['name' => '文華國中', 'city' => '台中市', 'district' => '西屯區', 'type' => '國民中學', 'school_code' => 'TC003'],
    ['name' => '大業國中', 'city' => '台中市', 'district' => '南屯區', 'type' => '國民中學', 'school_code' => 'TC004'],
    ['name' => '惠文國中', 'city' => '台中市', 'district' => '南屯區', 'type' => '國民中學', 'school_code' => 'TC005'],
    ['name' => '崇德國中', 'city' => '台中市', 'district' => '北屯區', 'type' => '國民中學', 'school_code' => 'TC006'],
    ['name' => '四育國中', 'city' => '台中市', 'district' => '南區', 'type' => '國民中學', 'school_code' => 'TC007'],
    ['name' => '居仁國中', 'city' => '台中市', 'district' => '中區', 'type' => '國民中學', 'school_code' => 'TC008'],
    ['name' => '雙十國中', 'city' => '台中市', 'district' => '北區', 'type' => '國民中學', 'school_code' => 'TC009'],
    ['name' => '光明國中', 'city' => '台中市', 'district' => '東區', 'type' => '國民中學', 'school_code' => 'TC010'],
    
    // 台南市
    ['name' => '台南一中', 'city' => '台南市', 'district' => '東區', 'type' => '國民中學', 'school_code' => 'TN001'],
    ['name' => '台南女中', 'city' => '台南市', 'district' => '中西區', 'type' => '國民中學', 'school_code' => 'TN002'],
    ['name' => '建興國中', 'city' => '台南市', 'district' => '中西區', 'type' => '國民中學', 'school_code' => 'TN003'],
    ['name' => '復興國中', 'city' => '台南市', 'district' => '東區', 'type' => '國民中學', 'school_code' => 'TN004'],
    ['name' => '大成國中', 'city' => '台南市', 'district' => '南區', 'type' => '國民中學', 'school_code' => 'TN005'],
    ['name' => '安平國中', 'city' => '台南市', 'district' => '安平區', 'type' => '國民中學', 'school_code' => 'TN006'],
    ['name' => '安南國中', 'city' => '台南市', 'district' => '安南區', 'type' => '國民中學', 'school_code' => 'TN007'],
    ['name' => '永康國中', 'city' => '台南市', 'district' => '永康區', 'type' => '國民中學', 'school_code' => 'TN008'],
    ['name' => '新化國中', 'city' => '台南市', 'district' => '新化區', 'type' => '國民中學', 'school_code' => 'TN009'],
    ['name' => '善化國中', 'city' => '台南市', 'district' => '善化區', 'type' => '國民中學', 'school_code' => 'TN010'],
    
    // 高雄市
    ['name' => '高雄中學', 'city' => '高雄市', 'district' => '三民區', 'type' => '國民中學', 'school_code' => 'KS001'],
    ['name' => '高雄女中', 'city' => '高雄市', 'district' => '前金區', 'type' => '國民中學', 'school_code' => 'KS002'],
    ['name' => '鳳山國中', 'city' => '高雄市', 'district' => '鳳山區', 'type' => '國民中學', 'school_code' => 'KS003'],
    ['name' => '左營國中', 'city' => '高雄市', 'district' => '左營區', 'type' => '國民中學', 'school_code' => 'KS004'],
    ['name' => '楠梓國中', 'city' => '高雄市', 'district' => '楠梓區', 'type' => '國民中學', 'school_code' => 'KS005'],
    ['name' => '鼓山國中', 'city' => '高雄市', 'district' => '鼓山區', 'type' => '國民中學', 'school_code' => 'KS006'],
    ['name' => '旗津國中', 'city' => '高雄市', 'district' => '旗津區', 'type' => '國民中學', 'school_code' => 'KS007'],
    ['name' => '前鎮國中', 'city' => '高雄市', 'district' => '前鎮區', 'type' => '國民中學', 'school_code' => 'KS008'],
    ['name' => '小港國中', 'city' => '高雄市', 'district' => '小港區', 'type' => '國民中學', 'school_code' => 'KS009'],
    ['name' => '林園國中', 'city' => '高雄市', 'district' => '林園區', 'type' => '國民中學', 'school_code' => 'KS010'],
    
    // 其他縣市
    ['name' => '宜蘭國中', 'city' => '宜蘭縣', 'district' => '宜蘭市', 'type' => '國民中學', 'school_code' => 'IL001'],
    ['name' => '羅東國中', 'city' => '宜蘭縣', 'district' => '羅東鎮', 'type' => '國民中學', 'school_code' => 'IL002'],
    ['name' => '花蓮國中', 'city' => '花蓮縣', 'district' => '花蓮市', 'type' => '國民中學', 'school_code' => 'HL001'],
    ['name' => '台東國中', 'city' => '台東縣', 'district' => '台東市', 'type' => '國民中學', 'school_code' => 'TT001'],
    ['name' => '澎湖國中', 'city' => '澎湖縣', 'district' => '馬公市', 'type' => '國民中學', 'school_code' => 'PH001'],
    ['name' => '金門國中', 'city' => '金門縣', 'district' => '金城鎮', 'type' => '國民中學', 'school_code' => 'KM001'],
    ['name' => '連江國中', 'city' => '連江縣', 'district' => '南竿鄉', 'type' => '國民中學', 'school_code' => 'LC001']
];

// 插入學校資料
try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("INSERT INTO school_data (name, city, district, type, school_code, is_active, data_source) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $inserted_count = 0;
    foreach ($schools as $school) {
        $stmt->execute([
            $school['name'],
            $school['city'],
            $school['district'],
            $school['type'],
            $school['school_code'],
            1, // is_active
            '快速初始化'
        ]);
        $inserted_count++;
    }
    
    $pdo->commit();
    echo "<p style='color: green;'>✅ 成功插入 $inserted_count 所國民中學</p>";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<p style='color: red;'>❌ 插入資料失敗: " . $e->getMessage() . "</p>";
    exit;
}

// 驗證資料
echo "<h2>📊 驗證結果</h2>";
try {
    // 總數統計
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM school_data WHERE type = '國民中學'");
    $total = $stmt->fetch()['total'];
    echo "<p>國民中學總數：<strong>$total</strong> 所</p>";
    
    // 按城市統計
    $stmt = $pdo->query("SELECT city, COUNT(*) as count FROM school_data WHERE type = '國民中學' GROUP BY city ORDER BY count DESC");
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>按城市統計：</p>";
    echo "<ul>";
    foreach ($cities as $city) {
        echo "<li>{$city['city']}: {$city['count']} 所</li>";
    }
    echo "</ul>";
    
    // 測試特定學校
    $testSchools = ['中崙國中', '西松國中', '永吉國中', '板橋國中', '桃園國中'];
    echo "<p>測試學校檢查：</p>";
    echo "<ul>";
    foreach ($testSchools as $schoolName) {
        $stmt = $pdo->prepare("SELECT name, city, district FROM school_data WHERE name = ? AND type = '國民中學'");
        $stmt->execute([$schoolName]);
        $school = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($school) {
            echo "<li style='color: green;'>✅ {$school['name']} ({$school['city']} {$school['district']})</li>";
        } else {
            echo "<li style='color: red;'>❌ {$schoolName} - 未找到</li>";
        }
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>驗證失敗: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>🎉 初始化完成！</h2>";
echo "<p>現在您可以：</p>";
echo "<ul>";
echo "<li><a href='test_school_api.php'>測試API功能</a> - 搜尋「中崙」會找到中崙國中</li>";
echo "<li><a href='city_schools.php'>城市學校瀏覽</a> - 按城市查看學校</li>";
echo "<li><a href='cooperation_upload.php'>使用就讀意願登錄</a> - 實際使用搜尋功能</li>";
echo "</ul>";

echo "<h3>🔍 測試建議：</h3>";
echo "<p>在測試頁面中嘗試：</p>";
echo "<ul>";
echo "<li>選擇「台北市」查看所有台北市的國民中學</li>";
echo "<li>搜尋「中崙」找到中崙國中</li>";
echo "<li>搜尋「西松」找到西松國中</li>";
echo "<li>搜尋「板橋」找到板橋國中、海山國中等</li>";
echo "</ul>";
?>
