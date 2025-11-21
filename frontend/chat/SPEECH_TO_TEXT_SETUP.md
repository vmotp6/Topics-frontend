# 語音轉文字功能設定指南

## 概述

本功能整合了 Google Cloud Speech-to-Text API，讓用戶可以在私訊聊天中直接使用語音輸入，系統會自動將語音轉換為文字。

## 功能特色

### 🎤 語音錄製
- 一鍵開始/停止錄製
- 即時錄製狀態顯示
- 錄製時間計時器
- 最大錄製時間限制（60秒）

### 🗣️ 語音轉文字
- 支援多種語言（繁體中文、簡體中文、英文、日文、韓文等）
- 高準確度語音識別
- 自動標點符號
- 即時轉換結果

### 🎨 用戶介面
- 直觀的語音按鈕
- 錄製狀態動畫
- 處理中指示器
- 成功/錯誤通知

## 設定步驟

### 1. Google Cloud 設定

#### 1.1 啟用 Speech-to-Text API
1. 前往 [Google Cloud Console](https://console.cloud.google.com/)
2. 選擇您的專案或創建新專案
3. 在左側選單選擇「APIs & Services」→「Library」
4. 搜尋「Speech-to-Text API」
5. 點擊「Enable」啟用 API

#### 1.2 創建 API Key
1. 在左側選單選擇「APIs & Services」→「Credentials」
2. 點擊「Create Credentials」→「API Key」
3. 複製生成的 API Key
4. （可選）限制 API Key 的使用範圍

### 2. 系統配置

#### 2.1 設定 API Key
在 `frontend/chat/speech_config.php` 中設定：

```php
// 方法1: 直接設定
$speech_config['api_key'] = 'your-actual-api-key-here';

// 方法2: 使用環境變數
// 在 .env 檔案中設定
GOOGLE_CLOUD_API_KEY=your-actual-api-key-here
```

#### 2.2 配置語言設定
```php
// 預設語言
$speech_config['default_language'] = 'zh-TW'; // 繁體中文

// 支援的語言
$speech_config['supported_languages'] = [
    'zh-TW' => '繁體中文',
    'zh-CN' => '簡體中文',
    'en-US' => 'English (US)',
    'ja-JP' => '日本語',
    'ko-KR' => '한국어'
];
```

### 3. 檔案權限設定

確保以下檔案有適當的權限：
```bash
chmod 644 frontend/chat/speech_to_text_api.php
chmod 644 frontend/chat/speech_config.php
chmod 644 frontend/chat/voice_recorder.js
chmod 644 frontend/chat/voice_styles.css
```

### 4. 測試功能

#### 4.1 檢查 API 連接
訪問：`http://your-domain/frontend/chat/speech_to_text_api.php?action=get_languages`

應該返回支援的語言列表。

#### 4.2 測試語音錄製
1. 開啟聊天頁面
2. 選擇一位聯絡人
3. 點擊「🎤 語音輸入」按鈕
4. 允許麥克風權限
5. 開始說話
6. 點擊「⏹️ 停止錄音」
7. 等待轉換結果

## 使用方式

### 基本操作
1. **開始錄製**：點擊「🎤 語音輸入」按鈕
2. **停止錄製**：再次點擊按鈕（會顯示「⏹️ 停止錄音」）
3. **查看結果**：轉換完成後，文字會自動填入輸入框
4. **發送訊息**：點擊「發送」按鈕

### 進階功能
- **語言切換**：可在配置中設定預設語言
- **準確度設定**：調整 `min_confidence` 參數
- **錄製時間**：調整 `max_recording_time` 參數

## 故障排除

### 常見問題

#### 1. 麥克風權限被拒絕
**解決方案：**
- 檢查瀏覽器設定
- 確保使用 HTTPS 連線
- 重新授權麥克風權限

#### 2. API 請求失敗
**檢查項目：**
- API Key 是否正確設定
- Speech-to-Text API 是否已啟用
- 網路連線是否正常
- API 配額是否足夠

#### 3. 語音識別準確度低
**改善方法：**
- 確保在安靜環境中錄製
- 說話清晰，語速適中
- 檢查語言設定是否正確
- 調整 `min_confidence` 參數

#### 4. 錄製時間過短
**解決方案：**
- 確保錄製時間超過1秒
- 檢查麥克風是否正常工作
- 調整 `max_recording_time` 設定

### 除錯模式

啟用除錯模式以獲取詳細日誌：
```php
$speech_config['debug_mode'] = true;
```

日誌會記錄：
- 錄製時間和檔案大小
- API 請求參數
- 轉換結果和準確度
- 錯誤訊息

## 效能優化

### 1. 音頻品質設定
```php
// 高品質設定
$speech_config['default_sample_rate'] = 48000;
$speech_config['default_encoding'] = 'WEBM_OPUS';

// 平衡設定
$speech_config['default_sample_rate'] = 16000;
$speech_config['default_encoding'] = 'LINEAR16';
```

### 2. 模型選擇
```php
// 長音頻（>60秒）
$speech_config['model'] = 'latest_long';

// 短音頻（<60秒）
$speech_config['model'] = 'latest_short';
```

### 3. 快取設定
- 瀏覽器會快取語音錄製功能
- 首次載入可能需要較長時間
- 建議使用 CDN 加速靜態資源

## 安全考量

### 1. API Key 保護
- 不要將 API Key 提交到版本控制
- 使用環境變數存儲敏感資訊
- 定期輪換 API Key

### 2. 檔案上傳限制
- 限制音頻檔案大小（預設10MB）
- 限制錄製時間（預設60秒）
- 驗證檔案類型

### 3. 隱私保護
- 音頻檔案不會永久存儲
- 轉換完成後立即刪除
- 可選擇記錄轉換日誌

## 成本估算

### Google Cloud Speech-to-Text 定價
- **免費額度**：每月前60分鐘免費
- **付費定價**：$0.006/15秒（約$0.024/分鐘）
- **建議**：小型應用通常在免費額度內

### 優化建議
- 限制錄製時間
- 使用適當的音頻品質
- 監控使用量
- 設定預算警報

## 技術規格

### 支援的瀏覽器
- Chrome 47+
- Firefox 44+
- Safari 11+
- Edge 79+

### 支援的音頻格式
- WebM (Opus)
- WAV (Linear PCM)
- FLAC
- MP3
- OGG

### 支援的語言
- 繁體中文 (zh-TW)
- 簡體中文 (zh-CN)
- 英文 (en-US)
- 日文 (ja-JP)
- 韓文 (ko-KR)
- 泰文 (th-TH)
- 越南文 (vi-VN)

## 更新日誌

### v1.0.0 (2024-01-XX)
- 初始版本發布
- 基本語音轉文字功能
- 多語言支援
- 響應式設計

## 支援與回饋

如有問題或建議，請聯繫系統管理員或提交 Issue。



















