/**
 * 簡化的語音錄製器
 * 專注於基本的語音識別功能
 */

class SimpleVoiceRecorder {
    constructor() {
        this.isRecording = false;
        this.recognition = null;
        this.finalTranscript = '';
        this.interimTranscript = '';
        this.currentLanguage = 'zh-TW';
        
        console.log('🎤 簡化語音錄製器已初始化');
    }
    
    /**
     * 初始化語音識別
     */
    init() {
        // 檢查瀏覽器支援
        if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
            console.error('❌ 瀏覽器不支援語音識別');
            this.showError('您的瀏覽器不支援語音識別，請使用Chrome或Edge瀏覽器');
            return false;
        }
        
        console.log('✅ 瀏覽器支援語音識別');
        return true;
    }
    
    /**
     * 開始語音識別
     */
    startRecognition() {
        if (this.isRecording) {
            console.log('⚠️ 已在錄製中');
            return;
        }
        
        if (!this.init()) {
            return;
        }
        
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        this.recognition = new SpeechRecognition();
        
        // 基本配置
        this.recognition.continuous = false;
        this.recognition.interimResults = true;
        this.recognition.lang = this.currentLanguage;
        this.recognition.maxAlternatives = 1;
        
        console.log('🔧 語音識別配置:', {
            continuous: this.recognition.continuous,
            interimResults: this.recognition.interimResults,
            lang: this.recognition.lang,
            maxAlternatives: this.recognition.maxAlternatives
        });
        
        // 重置結果
        this.finalTranscript = '';
        this.interimTranscript = '';
        
        // 事件處理
        this.recognition.onstart = () => {
            this.isRecording = true;
            console.log('🎤 語音識別開始');
            this.updateUI(true);
        };
        
        this.recognition.onresult = (event) => {
            console.log('🔍 收到識別結果:', event.results.length, '個結果');
            
            this.interimTranscript = '';
            
            for (let i = event.resultIndex; i < event.results.length; i++) {
                const result = event.results[i];
                const transcript = result[0].transcript;
                const confidence = result[0].confidence;
                
                console.log('📝 識別結果:', {
                    transcript: transcript,
                    confidence: confidence,
                    isFinal: result.isFinal
                });
                
                if (result.isFinal) {
                    this.finalTranscript += transcript;
                    console.log('✅ 最終結果:', this.finalTranscript);
                    
                    // 立即處理結果
                    this.handleResult(this.finalTranscript.trim());
                } else {
                    this.interimTranscript += transcript;
                    console.log('🔄 臨時結果:', this.interimTranscript);
                }
            }
        };
        
        this.recognition.onerror = (event) => {
            console.error('❌ 語音識別錯誤:', event.error);
            this.handleError(event.error);
        };
        
        this.recognition.onend = () => {
            this.isRecording = false;
            console.log('🏁 語音識別結束');
            this.updateUI(false);
            
            if (!this.finalTranscript.trim()) {
                console.log('⚠️ 未識別到語音內容');
                this.showError('未識別到語音內容，請重試');
            }
        };
        
        // 開始識別
        try {
            this.recognition.start();
            console.log('🚀 語音識別已啟動');
        } catch (error) {
            console.error('❌ 啟動失敗:', error);
            this.showError('啟動語音識別失敗: ' + error.message);
        }
    }
    
    /**
     * 停止語音識別
     */
    stopRecognition() {
        if (this.recognition && this.isRecording) {
            this.recognition.stop();
            console.log('⏹️ 停止語音識別');
        }
    }
    
    /**
     * 處理識別結果
     */
    handleResult(transcript) {
        if (transcript) {
            console.log('✅ 處理識別結果:', transcript);
            this.insertText(transcript);
            this.showSuccess('語音識別成功: ' + transcript);
        }
    }
    
    /**
     * 處理錯誤
     */
    handleError(error) {
        let message = '語音識別錯誤: ' + error;
        
        switch (error) {
            case 'not-allowed':
                message = '麥克風權限被拒絕，請允許麥克風存取';
                break;
            case 'no-speech':
                message = '未檢測到語音，請重試';
                break;
            case 'audio-capture':
                message = '音頻捕獲失敗，請檢查麥克風';
                break;
            case 'network':
                message = '網路連接錯誤，請檢查網路';
                break;
        }
        
        this.showError(message);
    }
    
    /**
     * 插入文字到輸入框
     */
    insertText(text) {
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.value = text;
            messageInput.disabled = false;
            messageInput.focus();
            console.log('📝 文字已插入:', text);
        } else {
            console.error('❌ 找不到輸入框');
        }
    }
    
    /**
     * 更新UI狀態
     */
    updateUI(isRecording) {
        const button = document.getElementById('voiceRecordBtn');
        if (button) {
            if (isRecording) {
                button.innerHTML = '⏹️ 停止';
                button.classList.add('recording');
            } else {
                button.innerHTML = '🎤 語音';
                button.classList.remove('recording');
            }
        }
    }
    
    /**
     * 顯示成功訊息
     */
    showSuccess(message) {
        console.log('✅', message);
        // 這裡可以添加UI提示
    }
    
    /**
     * 顯示錯誤訊息
     */
    showError(message) {
        console.error('❌', message);
        // 這裡可以添加UI提示
    }
}

// 創建全局實例
window.simpleVoiceRecorder = new SimpleVoiceRecorder();

// 導出給其他腳本使用
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SimpleVoiceRecorder;
}
