# 學校搜尋API系統使用指南

## 📋 系統概述

本系統整合了台灣教育部開放資料，提供即時更新的學校搜尋功能，主要用於康寧大學就讀意願登錄頁面的學校搜尋。

## 🏗️ 系統架構

### 檔案結構
```
frontend/
├── api/
│   └── school_data_api.php          # 學校資料API端點
├── assets/csp/
│   └── cooperation_upload.css       # 搜尋功能樣式
├── cooperation_upload.php           # 主要表單頁面
├── admin_school_data.php            # 管理介面
└── test_school_api.php              # API測試頁面

scripts/
├── database/
│   └── create_school_data_table.sql # 資料庫表結構
└── update_school_data.php           # 資料更新腳本
```

### 資料庫表結構
```sql
CREATE TABLE school_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,           -- 學校名稱
    city VARCHAR(20) NOT NULL,            -- 縣市
    district VARCHAR(20) NOT NULL,        -- 區/鄉鎮市
    type VARCHAR(20) NOT NULL,            -- 學校類型
    school_code VARCHAR(20),              -- 學校代碼
    address VARCHAR(200),                 -- 學校地址
    phone VARCHAR(20),                    -- 聯絡電話
    website VARCHAR(200),                 -- 學校網站
    principal VARCHAR(50),                -- 校長姓名
    student_count INT DEFAULT 0,          -- 學生人數
    teacher_count INT DEFAULT 0,          -- 教師人數
    established_year YEAR,                -- 創校年份
    is_active TINYINT(1) DEFAULT 1,       -- 是否營運中
    data_source VARCHAR(100),             -- 資料來源
    last_updated TIMESTAMP,               -- 最後更新時間
    created_at TIMESTAMP                  -- 建立時間
);
```

## 🚀 功能特色

### 1. 即時搜尋
- ✅ **即時響應**：輸入時立即顯示搜尋結果
- ✅ **模糊匹配**：支援部分關鍵字搜尋
- ✅ **大小寫不敏感**：自動處理大小寫差異

### 2. 豐富的學校資訊
- ✅ **完整資料**：學校名稱、城市、區域
- ✅ **視覺化顯示**：圖標、分層資訊展示
- ✅ **載入動畫**：搜尋過程的視覺反饋

### 3. 資料管理
- ✅ **自動更新**：定時從教育部API獲取最新資料
- ✅ **管理介面**：統計資料、手動更新功能
- ✅ **錯誤處理**：完善的錯誤處理機制

## 📡 API端點

### 搜尋學校
```
GET api/school_data_api.php?action=search&keyword={關鍵字}&city={城市}
```

**參數：**
- `action`: 固定值 "search"
- `keyword`: 搜尋關鍵字（至少2個字元）
- `city`: 可選，指定城市過濾

**回應範例：**
```json
{
    "schools": [
        {
            "name": "中正國中",
            "city": "台北市",
            "district": "中正區",
            "type": "國民中學"
        }
    ],
    "total": 1,
    "keyword": "中正"
}
```

### 獲取城市列表
```
GET api/school_data_api.php?action=cities
```

**回應範例：**
```json
{
    "cities": ["台北市", "新北市", "桃園市", "基隆市", "新竹市", "新竹縣"]
}
```

### 更新學校資料
```
GET api/school_data_api.php?action=update
```

## 🛠️ 使用方式

### 1. 基本搜尋功能
在 `cooperation_upload.php` 中，學校搜尋功能已經整合完成：

```javascript
// 搜尋功能會自動觸發
function performSearch() {
    const keyword = document.getElementById('junior_high').value.trim();
    // ... 搜尋邏輯
}
```

### 2. 管理介面
訪問 `admin_school_data.php` 來管理學校資料：
- 查看統計資料
- 手動更新學校資料
- 監控資料更新狀態

### 3. API測試
訪問 `test_school_api.php` 來測試API功能：
- 測試搜尋功能
- 驗證回應格式
- 檢查錯誤處理

## 🔧 設定與維護

### 1. 資料庫初始化
```bash
# 執行SQL腳本創建資料表
mysql -u root -p topics_good < scripts/database/create_school_data_table.sql
```

### 2. 定時更新設定
建議設定定時任務來定期更新學校資料：

```bash
# 每日凌晨2點更新
0 2 * * * /usr/bin/php /path/to/scripts/update_school_data.php
```

### 3. 日誌監控
更新腳本會產生日誌檔案：
```
logs/school_data_update.log
```

## 📊 資料來源

### 台灣教育部開放資料
- **國民中學資料**：https://data.gov.tw/dataset/12071
- **高級中學資料**：https://data.gov.tw/dataset/12072
- **國民小學資料**：https://data.gov.tw/dataset/12070

### 資料更新頻率
- **建議更新頻率**：每週一次
- **緊急更新**：可透過管理介面手動觸發
- **資料驗證**：每次更新後會記錄統計資訊

## 🎨 自訂樣式

### CSS類別
```css
.modern-search-container     /* 搜尋容器 */
.search-input-wrapper        /* 輸入框包裝器 */
.search-result-item          /* 搜尋結果項目 */
.school-info                 /* 學校資訊 */
.school-name                 /* 學校名稱 */
.school-location             /* 學校位置 */
```

### 自訂樣式範例
```css
.cooperation-page-wrapper .school-name {
    color: #your-color;
    font-size: 1.1rem;
}
```

## 🐛 故障排除

### 常見問題

1. **搜尋無結果**
   - 檢查API端點是否正常
   - 確認資料庫中有資料
   - 檢查關鍵字長度（至少2個字元）

2. **更新失敗**
   - 檢查網路連線
   - 確認教育部API可訪問
   - 檢查資料庫權限

3. **樣式問題**
   - 確認CSS檔案路徑正確
   - 檢查瀏覽器快取
   - 驗證CSS語法

### 除錯模式
在URL中加入 `?debug=1` 來啟用除錯模式：
```
http://localhost/Topics-frontend/frontend/test_school_api.php?debug=1
```

## 📈 效能優化

### 建議設定
1. **資料庫索引**：已為常用欄位建立索引
2. **快取機制**：可考慮加入Redis快取
3. **CDN加速**：靜態資源使用CDN
4. **壓縮回應**：啟用Gzip壓縮

### 監控指標
- API回應時間
- 搜尋成功率
- 資料更新頻率
- 錯誤率統計

## 🔒 安全性

### 已實作的安全措施
- ✅ **SQL注入防護**：使用PDO預處理語句
- ✅ **XSS防護**：輸出時進行HTML轉義
- ✅ **CSRF防護**：表單包含CSRF token
- ✅ **權限控制**：管理功能需要管理員權限

### 建議加強
- 設定API速率限制
- 加入IP白名單
- 定期安全掃描
- 備份重要資料

## 📞 技術支援

如有技術問題，請檢查：
1. 系統日誌檔案
2. 瀏覽器開發者工具
3. 資料庫連線狀態
4. API端點回應

---

**最後更新：** 2024年1月
**版本：** 1.0.0
**維護者：** 康寧大學資訊系統團隊
