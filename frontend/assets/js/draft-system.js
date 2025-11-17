/**
 * 通用表單草稿系統
 * 適用於所有表單的自動儲存、載入和清除草稿功能
 */

class DraftSystem {
    constructor(options = {}) {
        // 配置選項
        this.storageKey = options.storageKey || 'form_draft';
        this.formSelector = options.formSelector || 'form';
        this.autoSaveDelay = options.autoSaveDelay || 1000; // 1秒防抖延遲
        this.autoLoad = options.autoLoad !== false; // 預設自動載入
        this.showStatus = options.showStatus !== false; // 預設顯示狀態
        this.excludeFields = options.excludeFields || ['captcha', 'password', 'password_confirm']; // 排除的欄位
        this.includeFields = options.includeFields || null; // 如果指定，只包含這些欄位
        
        // 狀態變數
        this.isSubmitting = false;
        this.saveTimeout = null;
        this.form = null;
        
        // 初始化
        this.init();
    }
    
    init() {
        // 等待 DOM 載入完成
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }
    
    setup() {
        // 找到表單
        if (typeof this.formSelector === 'string') {
            this.form = document.querySelector(this.formSelector);
        } else {
            this.form = this.formSelector;
        }
        
        if (!this.form) {
            console.warn('DraftSystem: 找不到表單元素');
            return;
        }
        
        // 設置自動儲存
        this.setupAutoSave();
        
        // 設置自動載入
        if (this.autoLoad) {
            setTimeout(() => this.loadDraft(false), 500);
        }
        
        // 監聽表單提交
        this.form.addEventListener('submit', () => {
            this.isSubmitting = true;
            // 提交成功後清除草稿
            setTimeout(() => {
                this.clearDraft();
            }, 1000);
        });
        
        console.log('✅ DraftSystem 初始化完成');
    }
    
    setupAutoSave() {
        // 使用事件委派監聽表單變化
        const handleChange = (e) => {
            const target = e.target;
            
            // 確保目標在表單內
            if (!this.form.contains(target)) {
                return;
            }
            
            // 跳過排除的欄位
            if (target.name && this.excludeFields.includes(target.name)) {
                return;
            }
            
            // 防抖儲存
            this.debouncedSave();
        };
        
        // 監聽各種事件
        this.form.addEventListener('input', handleChange);
        this.form.addEventListener('change', handleChange);
        this.form.addEventListener('keyup', handleChange);
    }
    
    debouncedSave() {
        if (this.isSubmitting) {
            return;
        }
        
        clearTimeout(this.saveTimeout);
        this.saveTimeout = setTimeout(() => {
            this.saveDraft();
        }, this.autoSaveDelay);
    }
    
    saveDraft() {
        if (this.isSubmitting) {
            return;
        }
        
        if (!this.form) {
            return;
        }
        
        const draftData = {};
        let fieldCount = 0;
        
        // 收集所有輸入欄位
        const inputs = this.form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            if (!input.name || !this.form.contains(input)) {
                return;
            }
            
            // 跳過排除的欄位
            if (this.excludeFields.includes(input.name)) {
                return;
            }
            
            // 如果指定了包含欄位，只處理這些欄位
            if (this.includeFields && !this.includeFields.includes(input.name) && 
                !input.name.match(/\[\]$/)) { // 允許 checkbox 陣列
                return;
            }
            
            // 處理不同類型的輸入
            if (input.type === 'checkbox') {
                if (input.checked) {
                    const name = input.name.replace('[]', '');
                    if (!draftData[name]) {
                        draftData[name] = [];
                    }
                    draftData[name].push(input.value);
                    fieldCount++;
                }
            } else if (input.type === 'radio') {
                if (input.checked) {
                    draftData[input.name] = input.value;
                    fieldCount++;
                }
            } else if (input.type === 'file') {
                // 檔案無法儲存，但可以記錄檔案名稱
                if (input.files && input.files.length > 0) {
                    const fileNames = Array.from(input.files).map(f => f.name);
                    if (fileNames.length > 0) {
                        draftData[input.name + '_names'] = fileNames;
                        fieldCount += fileNames.length;
                    }
                }
            } else {
                // 文字輸入框、選擇框等
                const value = input.value.trim();
                if (value) {
                    draftData[input.name] = value;
                    fieldCount++;
                }
            }
        });
        
        // 記錄儲存時間
        draftData._saved_at = new Date().toISOString();
        draftData._form_id = this.form.id || this.form.action || 'default';
        
        // 儲存到 localStorage
        try {
            localStorage.setItem(this.storageKey, JSON.stringify(draftData));
            if (fieldCount > 0) {
                console.log(`✅ 草稿已自動儲存，包含 ${fieldCount} 個欄位`);
            }
        } catch (e) {
            console.error('❌ 儲存草稿失敗:', e);
        }
    }
    
    loadDraft(showMessage = false) {
        try {
            const draftData = localStorage.getItem(this.storageKey);
            if (!draftData) {
                if (showMessage) {
                    alert('沒有找到儲存的草稿');
                }
                return false;
            }
            
            const data = JSON.parse(draftData);
            
            // 檢查表單 ID 是否匹配（如果有的話）
            if (data._form_id && this.form.id && data._form_id !== this.form.id) {
                if (showMessage) {
                    alert('草稿資料不屬於當前表單');
                }
                return false;
            }
            
            if (!this.form) {
                return false;
            }
            
            let loadedCount = 0;
            
            // 載入資料
            Object.keys(data).forEach(key => {
                // 跳過特殊欄位
                if (key.startsWith('_')) {
                    return;
                }
                
                // 處理檔案名稱（無法載入檔案本身）
                if (key.endsWith('_names')) {
                    console.log(`⚠️ 檔案欄位 ${key} 無法自動載入，請重新選擇檔案`);
                    return;
                }
                
                // 處理陣列欄位（checkbox 群組）
                if (Array.isArray(data[key])) {
                    data[key].forEach(value => {
                        const checkbox = this.form.querySelector(`input[name="${key}[]"][value="${CSS.escape(value)}"]`);
                        if (checkbox) {
                            checkbox.checked = true;
                            loadedCount++;
                        }
                    });
                    return;
                }
                
                // 處理一般欄位
                const input = this.form.querySelector(`[name="${key}"]`);
                if (input) {
                    if (input.type === 'file') {
                        console.log(`⚠️ 檔案欄位 ${key} 無法自動載入`);
                        return;
                    } else if (input.type === 'checkbox') {
                        if (input.value === data[key]) {
                            input.checked = true;
                            loadedCount++;
                        }
                    } else if (input.type === 'radio') {
                        if (input.value === data[key]) {
                            input.checked = true;
                            loadedCount++;
                        }
                    } else {
                        input.value = data[key] || '';
                        if (data[key]) {
                            loadedCount++;
                        }
                    }
                }
            });
            
            if (loadedCount > 0) {
                if (this.showStatus) {
                    this.showStatusMessage('草稿已載入', 'success');
                }
                console.log(`✅ 草稿已載入，共載入 ${loadedCount} 個欄位`);
                
                // 觸發 change 事件以更新相關 UI
                this.form.querySelectorAll('input, select, textarea').forEach(input => {
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });
                
                return true;
            } else {
                if (showMessage) {
                    alert('草稿資料格式錯誤或無法載入');
                }
                return false;
            }
        } catch (e) {
            console.error('載入草稿失敗:', e);
            if (showMessage) {
                alert('載入草稿時發生錯誤: ' + e.message);
            }
            return false;
        }
    }
    
    clearDraft() {
        try {
            localStorage.removeItem(this.storageKey);
            if (this.showStatus) {
                this.showStatusMessage('草稿已清除', 'info');
            }
            console.log('✅ 草稿已清除');
            return true;
        } catch (e) {
            console.error('清除草稿失敗:', e);
            return false;
        }
    }
    
    showStatusMessage(message, type = 'info') {
        // 創建或更新狀態欄
        let statusBar = document.getElementById('draft-status-bar');
        if (!statusBar) {
            statusBar = document.createElement('div');
            statusBar.id = 'draft-status-bar';
            statusBar.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 12px 20px;
                border-radius: 25px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                z-index: 1000;
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 14px;
                transition: all 0.3s ease;
                opacity: 0;
                transform: translateY(20px);
            `;
            document.body.appendChild(statusBar);
        }
        
        const icons = {
            success: '✅',
            info: 'ℹ️',
            error: '❌'
        };
        
        statusBar.textContent = `${icons[type] || icons.info} ${message}`;
        statusBar.style.opacity = '1';
        statusBar.style.transform = 'translateY(0)';
        
        if (type === 'success') {
            setTimeout(() => {
                statusBar.style.opacity = '0';
                statusBar.style.transform = 'translateY(20px)';
            }, 2000);
        }
    }
}

// 導出供其他腳本使用
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DraftSystem;
}






