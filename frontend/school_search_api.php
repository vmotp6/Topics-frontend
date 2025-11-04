<?php
/**
 * 國中學校搜尋API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 台灣主要國中資料
$schools = [
    // 台北市
    ['name' => '台北市立中正國中', 'city' => '台北市', 'district' => '中正區', 'address' => '台北市中正區重慶南路一段139號'],
    ['name' => '台北市立建國中學', 'city' => '台北市', 'district' => '中正區', 'address' => '台北市中正區南海路56號'],
    ['name' => '台北市立成功國中', 'city' => '台北市', 'district' => '中正區', 'address' => '台北市中正區濟南路二段46號'],
    ['name' => '台北市立金華國中', 'city' => '台北市', 'district' => '大安區', 'address' => '台北市大安區新生南路二段32號'],
    ['name' => '台北市立敦化國中', 'city' => '台北市', 'district' => '大安區', 'address' => '台北市大安區敦化南路二段94號'],
    ['name' => '台北市立仁愛國中', 'city' => '台北市', 'district' => '大安區', 'address' => '台北市大安區仁愛路四段130號'],
    ['name' => '台北市立信義國中', 'city' => '台北市', 'district' => '信義區', 'address' => '台北市信義區松仁路158號'],
    ['name' => '台北市立松山國中', 'city' => '台北市', 'district' => '松山區', 'address' => '台北市松山區八德路四段101號'],
    ['name' => '台北市立民生國中', 'city' => '台北市', 'district' => '松山區', 'address' => '台北市松山區民生東路四段133號'],
    ['name' => '台北市立中山國中', 'city' => '台北市', 'district' => '中山區', 'address' => '台北市中山區長安東路二段141號'],
    
    // 新北市
    ['name' => '新北市立板橋國中', 'city' => '新北市', 'district' => '板橋區', 'address' => '新北市板橋區文化路一段188號'],
    ['name' => '新北市立新莊國中', 'city' => '新北市', 'district' => '新莊區', 'address' => '新北市新莊區中正路211號'],
    ['name' => '新北市立三重國中', 'city' => '新北市', 'district' => '三重區', 'address' => '新北市三重區重新路三段1號'],
    ['name' => '新北市立中和國中', 'city' => '新北市', 'district' => '中和區', 'address' => '新北市中和區中正路800號'],
    ['name' => '新北市立永和國中', 'city' => '新北市', 'district' => '永和區', 'address' => '新北市永和區永和路二段100號'],
    ['name' => '新北市立新店國中', 'city' => '新北市', 'district' => '新店區', 'address' => '新北市新店區中正路54號'],
    ['name' => '新北市立樹林國中', 'city' => '新北市', 'district' => '樹林區', 'address' => '新北市樹林區樹新路40號'],
    ['name' => '新北市立鶯歌國中', 'city' => '新北市', 'district' => '鶯歌區', 'address' => '新北市鶯歌區中正一路425號'],
    ['name' => '新北市立三峽國中', 'city' => '新北市', 'district' => '三峽區', 'address' => '新北市三峽區復興路238號'],
    ['name' => '新北市立淡水國中', 'city' => '新北市', 'district' => '淡水區', 'address' => '新北市淡水區中正東路42號'],
    
    // 桃園市
    ['name' => '桃園市立桃園國中', 'city' => '桃園市', 'district' => '桃園區', 'address' => '桃園市桃園區中正路107號'],
    ['name' => '桃園市立中壢國中', 'city' => '桃園市', 'district' => '中壢區', 'address' => '桃園市中壢區中央路二段136號'],
    ['name' => '桃園市立平鎮國中', 'city' => '桃園市', 'district' => '平鎮區', 'address' => '桃園市平鎮區中豐路南勢二段18號'],
    ['name' => '桃園市立八德國中', 'city' => '桃園市', 'district' => '八德區', 'address' => '桃園市八德區介壽路二段361號'],
    ['name' => '桃園市立楊梅國中', 'city' => '桃園市', 'district' => '楊梅區', 'address' => '桃園市楊梅區新農街85號'],
    ['name' => '桃園市立蘆竹國中', 'city' => '桃園市', 'district' => '蘆竹區', 'address' => '桃園市蘆竹區南崁路二段144號'],
    ['name' => '桃園市立大溪國中', 'city' => '桃園市', 'district' => '大溪區', 'address' => '桃園市大溪區康莊路641號'],
    ['name' => '桃園市立大園國中', 'city' => '桃園市', 'district' => '大園區', 'address' => '桃園市大園區中正東路二段147號'],
    ['name' => '桃園市立龜山國中', 'city' => '桃園市', 'district' => '龜山區', 'address' => '桃園市龜山區萬壽路二段920號'],
    ['name' => '桃園市立龍潭國中', 'city' => '桃園市', 'district' => '龍潭區', 'address' => '桃園市龍潭區中正路210號'],
    
    // 台中市
    ['name' => '台中市立台中國中', 'city' => '台中市', 'district' => '中區', 'address' => '台中市中區三民路二段46號'],
    ['name' => '台中市立西區國中', 'city' => '台中市', 'district' => '西區', 'address' => '台中市西區民權路225號'],
    ['name' => '台中市立北區國中', 'city' => '台中市', 'district' => '北區', 'address' => '台中市北區健行路140號'],
    ['name' => '台中市立東區國中', 'city' => '台中市', 'district' => '東區', 'address' => '台中市東區樂業路30號'],
    ['name' => '台中市立南區國中', 'city' => '台中市', 'district' => '南區', 'address' => '台中市南區復興路二段152號'],
    ['name' => '台中市立西屯國中', 'city' => '台中市', 'district' => '西屯區', 'address' => '台中市西屯區台灣大道三段99號'],
    ['name' => '台中市立南屯國中', 'city' => '台中市', 'district' => '南屯區', 'address' => '台中市南屯區大墩路170號'],
    ['name' => '台中市立北屯國中', 'city' => '台中市', 'district' => '北屯區', 'address' => '台中市北屯區崇德路二段227號'],
    ['name' => '台中市立豐原國中', 'city' => '台中市', 'district' => '豐原區', 'address' => '台中市豐原區中正路167號'],
    ['name' => '台中市立東勢國中', 'city' => '台中市', 'district' => '東勢區', 'address' => '台中市東勢區豐勢路389號'],
    
    // 台南市
    ['name' => '台南市立台南國中', 'city' => '台南市', 'district' => '中西區', 'address' => '台南市中西區民族路二段87號'],
    ['name' => '台南市立東區國中', 'city' => '台南市', 'district' => '東區', 'address' => '台南市東區崇學路98號'],
    ['name' => '台南市立南區國中', 'city' => '台南市', 'district' => '南區', 'address' => '台南市南區健康路二段308號'],
    ['name' => '台南市立北區國中', 'city' => '台南市', 'district' => '北區', 'address' => '台南市北區公園路321號'],
    ['name' => '台南市立安平國中', 'city' => '台南市', 'district' => '安平區', 'address' => '台南市安平區安平路232號'],
    ['name' => '台南市立安南國中', 'city' => '台南市', 'district' => '安南區', 'address' => '台南市安南區海佃路一段158號'],
    ['name' => '台南市立永康國中', 'city' => '台南市', 'district' => '永康區', 'address' => '台南市永康區中山南路193號'],
    ['name' => '台南市立歸仁國中', 'city' => '台南市', 'district' => '歸仁區', 'address' => '台南市歸仁區文化街二段138號'],
    ['name' => '台南市立新化國中', 'city' => '台南市', 'district' => '新化區', 'address' => '台南市新化區中正路488號'],
    ['name' => '台南市立左鎮國中', 'city' => '台南市', 'district' => '左鎮區', 'address' => '台南市左鎮區中正里171-5號'],
    
    // 高雄市
    ['name' => '高雄市立高雄國中', 'city' => '高雄市', 'district' => '新興區', 'address' => '高雄市新興區中正三路32號'],
    ['name' => '高雄市立前金國中', 'city' => '高雄市', 'district' => '前金區', 'address' => '高雄市前金區中正四路211號'],
    ['name' => '高雄市立苓雅國中', 'city' => '高雄市', 'district' => '苓雅區', 'address' => '高雄市苓雅區中正一路372號'],
    ['name' => '高雄市立鹽埕國中', 'city' => '高雄市', 'district' => '鹽埕區', 'address' => '高雄市鹽埕區大勇路11號'],
    ['name' => '高雄市立鼓山國中', 'city' => '高雄市', 'district' => '鼓山區', 'address' => '高雄市鼓山區鼓山一路53號'],
    ['name' => '高雄市立旗津國中', 'city' => '高雄市', 'district' => '旗津區', 'address' => '高雄市旗津區中洲三路482號'],
    ['name' => '高雄市立前鎮國中', 'city' => '高雄市', 'district' => '前鎮區', 'address' => '高雄市前鎮區民權二路331號'],
    ['name' => '高雄市立三民國中', 'city' => '高雄市', 'district' => '三民區', 'address' => '高雄市三民區建國三路50號'],
    ['name' => '高雄市立楠梓國中', 'city' => '高雄市', 'district' => '楠梓區', 'address' => '高雄市楠梓區楠梓新路309號'],
    ['name' => '高雄市立小港國中', 'city' => '高雄市', 'district' => '小港區', 'address' => '高雄市小港區小港路160號'],
    
    // 基隆市
    ['name' => '基隆市立基隆國中', 'city' => '基隆市', 'district' => '中正區', 'address' => '基隆市中正區中正路115號'],
    ['name' => '基隆市立信義國中', 'city' => '基隆市', 'district' => '信義區', 'address' => '基隆市信義區信二路179號'],
    ['name' => '基隆市立仁愛國中', 'city' => '基隆市', 'district' => '仁愛區', 'address' => '基隆市仁愛區仁二路135號'],
    ['name' => '基隆市立中山國中', 'city' => '基隆市', 'district' => '中山區', 'address' => '基隆市中山區復興路212號'],
    ['name' => '基隆市立安樂國中', 'city' => '基隆市', 'district' => '安樂區', 'address' => '基隆市安樂區安樂路二段164號'],
    ['name' => '基隆市立暖暖國中', 'city' => '基隆市', 'district' => '暖暖區', 'address' => '基隆市暖暖區暖暖街350號'],
    ['name' => '基隆市立七堵國中', 'city' => '基隆市', 'district' => '七堵區', 'address' => '基隆市七堵區明德一路182號'],
    
    // 新竹市
    ['name' => '新竹市立新竹國中', 'city' => '新竹市', 'district' => '東區', 'address' => '新竹市東區光復路二段153號'],
    ['name' => '新竹市立香山國中', 'city' => '新竹市', 'district' => '香山區', 'address' => '新竹市香山區香北路177號'],
    ['name' => '新竹市立北區國中', 'city' => '新竹市', 'district' => '北區', 'address' => '新竹市北區西大路683號'],
    
    // 嘉義市
    ['name' => '嘉義市立嘉義國中', 'city' => '嘉義市', 'district' => '東區', 'address' => '嘉義市東區民族路420號'],
    ['name' => '嘉義市立西區國中', 'city' => '嘉義市', 'district' => '西區', 'address' => '嘉義市西區新民路580號'],
    
    // 其他縣市主要國中
    ['name' => '新竹縣立竹北國中', 'city' => '新竹縣', 'district' => '竹北市', 'address' => '新竹縣竹北市光明六路10號'],
    ['name' => '苗栗縣立苗栗國中', 'city' => '苗栗縣', 'district' => '苗栗市', 'address' => '苗栗縣苗栗市中正路488號'],
    ['name' => '彰化縣立彰化國中', 'city' => '彰化縣', 'district' => '彰化市', 'address' => '彰化縣彰化市中正路二段117號'],
    ['name' => '南投縣立南投國中', 'city' => '南投縣', 'district' => '南投市', 'address' => '南投縣南投市中興路669號'],
    ['name' => '雲林縣立斗六國中', 'city' => '雲林縣', 'district' => '斗六市', 'address' => '雲林縣斗六市鎮南路59號'],
    ['name' => '嘉義縣立朴子國中', 'city' => '嘉義縣', 'district' => '朴子市', 'address' => '嘉義縣朴子市山通路36號'],
    ['name' => '屏東縣立屏東國中', 'city' => '屏東縣', 'district' => '屏東市', 'address' => '屏東縣屏東市中正路488號'],
    ['name' => '宜蘭縣立宜蘭國中', 'city' => '宜蘭縣', 'district' => '宜蘭市', 'address' => '宜蘭縣宜蘭市復興路二段77號'],
    ['name' => '花蓮縣立花蓮國中', 'city' => '花蓮縣', 'district' => '花蓮市', 'address' => '花蓮縣花蓮市中正路210號'],
    ['name' => '台東縣立台東國中', 'city' => '台東縣', 'district' => '台東市', 'address' => '台東縣台東市中山路276號'],
    ['name' => '澎湖縣立馬公國中', 'city' => '澎湖縣', 'district' => '馬公市', 'address' => '澎湖縣馬公市中華路36號'],
    ['name' => '金門縣立金城國中', 'city' => '金門縣', 'district' => '金城鎮', 'address' => '金門縣金城鎮民生路60號'],
    ['name' => '連江縣立介壽國中', 'city' => '連江縣', 'district' => '南竿鄉', 'address' => '連江縣南竿鄉介壽村76號']
];

// 搜尋功能
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $keyword = $_GET['keyword'] ?? '';
    $city = $_GET['city'] ?? '';
    
    $results = [];
    
    foreach ($schools as $school) {
        $match = false;
        
        // 關鍵字搜尋
        if (!empty($keyword)) {
            if (strpos($school['name'], $keyword) !== false || 
                strpos($school['city'], $keyword) !== false || 
                strpos($school['district'], $keyword) !== false) {
                $match = true;
            }
        }
        
        // 縣市篩選
        if (!empty($city)) {
            if ($school['city'] !== $city) {
                $match = false;
            }
        }
        
        // 如果沒有搜尋條件，顯示所有結果
        if (empty($keyword) && empty($city)) {
            $match = true;
        }
        
        if ($match) {
            $results[] = $school;
        }
    }
    
    // 限制結果數量
    $results = array_slice($results, 0, 50);
    
    echo json_encode([
        'success' => true,
        'count' => count($results),
        'schools' => $results
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => '只支援GET請求'
    ]);
}
?>

























