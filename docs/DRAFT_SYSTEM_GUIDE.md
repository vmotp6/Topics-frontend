# 表單草稿系統使用指南

## 概述

通用表單草稿系統可以自動儲存、載入和清除表單資料，適用於所有表單頁面。

## 功能特點

1. **自動儲存**：表單欄位變化時自動儲存草稿（1秒防抖延遲）
2. **自動載入**：頁面載入時自動載入上次的草稿
3. **手動管理**：提供載入和清除草稿的按鈕
4. **狀態提示**：顯示草稿儲存、載入和清除的狀態訊息

## 已應用的表單

- ✅ `records.php` - 活動紀錄填報表單（已有完整草稿系統）
- ✅ `senior_message_form.php` - 學長姊留言表單
- ✅ `cooperation_upload.php` - 就讀意願登錄表單

## 如何應用到新表單

### 步驟 1：引入草稿系統腳本

在 HTML 的 `<head>` 部分添加：

```html
<script src="assets/js/draft-system.js"></script>
```

### 步驟 2：初始化草稿系統

在表單頁面的 JavaScript 部分添加：

```javascript
let draftSystem;
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form'); // 或使用更具體的選擇器
    
    draftSystem = new DraftSystem({
        storageKey: 'your_form_draft', // 唯一的儲存鍵名
        formSelector: form, // 表單元素或選擇器
        excludeFields: ['captcha', 'password'], // 要排除的欄位名稱
        autoLoad: true, // 是否自動載入草稿
        showStatus: true // 是否顯示狀態訊息
    });
    
    // 可選：添加草稿管理按鈕
    const draftActions = document.createElement('div');
    draftActions.style.cssText = 'margin-bottom: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 10px; display: flex; gap: 10px; justify-content: flex-end;';
    draftActions.innerHTML = `
        <button type="button" onclick="draftSystem.loadDraft(true)" style="background: #17a2b8; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer;">
            <i class="fas fa-download"></i> 載入草稿
        </button>
        <button type="button" onclick="if(confirm('確定要清除草稿嗎？')) { draftSystem.clearDraft(); form.reset(); }" style="background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer;">
            <i class="fas fa-trash"></i> 清除草稿
        </button>
    `;
    form.insertBefore(draftActions, form.firstChild);
});
```

### 步驟 3：表單提交時清除草稿

在表單提交處理中添加：

```javascript
form.addEventListener('submit', function(e) {
    // ... 表單驗證和提交邏輯 ...
    
    // 提交成功後清除草稿
    if (draftSystem) {
        draftSystem.clearDraft();
    }
});
```

## 配置選項

### DraftSystem 建構函數參數

| 參數 | 類型 | 預設值 | 說明 |
|------|------|--------|------|
| `storageKey` | string | `'form_draft'` | localStorage 的鍵名，每個表單應該使用不同的鍵名 |
| `formSelector` | string/Element | `'form'` | 表單元素或選擇器 |
| `autoSaveDelay` | number | `1000` | 自動儲存的防抖延遲時間（毫秒） |
| `autoLoad` | boolean | `true` | 是否在頁面載入時自動載入草稿 |
| `showStatus` | boolean | `true` | 是否顯示狀態訊息 |
| `excludeFields` | array | `['captcha', 'password']` | 要排除的欄位名稱（不會儲存） |
| `includeFields` | array/null | `null` | 如果指定，只包含這些欄位（null 表示包含所有） |

## 使用範例

### 範例 1：基本使用

```javascript
const draftSystem = new DraftSystem({
    storageKey: 'contact_form_draft',
    formSelector: '#contactForm'
});
```

### 範例 2：自訂配置

```javascript
const draftSystem = new DraftSystem({
    storageKey: 'registration_draft',
    formSelector: document.getElementById('registrationForm'),
    excludeFields: ['captcha', 'password', 'password_confirm', 'agree_terms'],
    autoSaveDelay: 2000, // 2秒後儲存
    autoLoad: true,
    showStatus: true
});
```

### 範例 3：只包含特定欄位

```javascript
const draftSystem = new DraftSystem({
    storageKey: 'survey_draft',
    formSelector: '#surveyForm',
    includeFields: ['name', 'email', 'phone', 'message'], // 只儲存這些欄位
    excludeFields: []
});
```

## API 方法

### `saveDraft()`

手動儲存草稿：

```javascript
draftSystem.saveDraft();
```

### `loadDraft(showMessage)`

載入草稿：

```javascript
// 靜默載入（不顯示提示）
draftSystem.loadDraft(false);

// 顯示提示訊息
draftSystem.loadDraft(true);
```

### `clearDraft()`

清除草稿：

```javascript
draftSystem.clearDraft();
```

## 注意事項

1. **儲存鍵名**：每個表單應該使用不同的 `storageKey`，避免資料衝突
2. **敏感資料**：密碼、驗證碼等敏感欄位會自動排除
3. **檔案上傳**：檔案無法儲存，但會記錄檔案名稱
4. **瀏覽器相容性**：需要支援 localStorage 的現代瀏覽器
5. **資料格式**：草稿資料以 JSON 格式儲存在 localStorage 中

## 故障排除

### 草稿無法載入

1. 檢查 `storageKey` 是否正確
2. 檢查瀏覽器是否支援 localStorage
3. 檢查表單選擇器是否正確

### 某些欄位沒有儲存

1. 檢查欄位是否在 `excludeFields` 中
2. 檢查欄位是否有 `name` 屬性
3. 檢查欄位是否在表單內

### 草稿資料衝突

確保每個表單使用不同的 `storageKey`。

## 技術細節

- 使用 localStorage 儲存草稿資料
- 使用防抖（debounce）技術避免頻繁儲存
- 支援文字輸入、選擇框、checkbox、radio 等所有表單元素
- 自動處理 checkbox 群組（陣列格式）
- 提交成功後自動清除草稿














