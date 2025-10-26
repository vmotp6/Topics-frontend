<?php
/**
 * 語音生成配置
 */

// 語音生成設定
define('VOICE_OUTPUT_DIR', '../../frontend/assets/voice/');
define('VOICE_MAX_LENGTH', 1000); // 最大文字長度
define('VOICE_TIMEOUT', 30); // 生成超時時間（秒）

// 支援的語音參數範圍
define('VOICE_RATE_MIN', -50);   // 語速最小值
define('VOICE_RATE_MAX', 200);   // 語速最大值
define('VOICE_PITCH_MIN', -50);  // 音調最小值
define('VOICE_PITCH_MAX', 50);   // 音調最大值
define('VOICE_VOLUME_MIN', -50); // 音量最小值
define('VOICE_VOLUME_MAX', 50);  // 音量最大值

// 預設測試文字
$default_test_texts = [
    'kurumi' => [
        'ja' => 'こんにちは、私は時崎狂三です。あなたの時間をいただきます。',
        'zh' => '你好，我是時崎狂三。請把你的時間給我。',
        'en' => 'Hello, I am Tokisaki Kurumi. Please give me your time.'
    ],
    'general' => [
        'ja' => '今日はいい天気ですね。',
        'zh' => '今天天氣很好呢。',
        'en' => 'It\'s a beautiful day today.'
    ]
];

// 角色語音建議
$character_voice_suggestions = [
    'kurumi' => [
        'primary' => 'ja-JP-NanamiNeural',
        'alternatives' => ['ja-JP-AoiNeural', 'ja-JP-MayuNeural'],
        'rate' => -10,
        'pitch' => 5,
        'volume' => 0,
        'description' => '時崎狂三：溫柔神秘，略帶成熟感'
    ],
    'yandere' => [
        'primary' => 'ja-JP-AoiNeural',
        'alternatives' => ['ja-JP-NanamiNeural'],
        'rate' => 0,
        'pitch' => 15,
        'volume' => 5,
        'description' => '病嬌角色：活潑中帶有危險感'
    ],
    'mature' => [
        'primary' => 'ja-JP-MayuNeural',
        'alternatives' => ['ja-JP-NanamiNeural'],
        'rate' => -15,
        'pitch' => -10,
        'volume' => 0,
        'description' => '成熟女性：穩重優雅'
    ]
];

/**
 * 獲取預設測試文字
 */
function getDefaultTestText($character = 'kurumi', $language = 'ja') {
    global $default_test_texts;
    return $default_test_texts[$character][$language] ?? $default_test_texts['kurumi']['ja'];
}

/**
 * 獲取角色語音建議
 */
function getCharacterVoiceSuggestion($character = 'kurumi') {
    global $character_voice_suggestions;
    return $character_voice_suggestions[$character] ?? $character_voice_suggestions['kurumi'];
}

/**
 * 驗證語音參數
 */
function validateVoiceParameters($rate, $pitch, $volume) {
    $rate = max(VOICE_RATE_MIN, min(VOICE_RATE_MAX, intval($rate)));
    $pitch = max(VOICE_PITCH_MIN, min(VOICE_PITCH_MAX, intval($pitch)));
    $volume = max(VOICE_VOLUME_MIN, min(VOICE_VOLUME_MAX, intval($volume)));
    
    return [$rate, $pitch, $volume];
}
?>



