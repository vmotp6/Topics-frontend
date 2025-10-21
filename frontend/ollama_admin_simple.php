<?php
// 簡化版 Ollama 管理介面 - 無需登入
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ollama 管理 - 康寧大學 (簡化版)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <style>
        .status-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .status-healthy {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        .status-unhealthy {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
            color: white;
        }
        .model-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .chat-container {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            background-color: #f8f9fa;
        }
        .message {
            margin-bottom: 10px;
            padding: 8px 12px;
            border-radius: 8px;
        }
        .user-message {
            background-color: #007bff;
            color: white;
            margin-left: 20%;
        }
        .bot-message {
            background-color: #e9ecef;
            color: #333;
            margin-right: 20%;
        }
        .ai-message {
            background-color: #28a745;
            color: white;
            margin-right: 20%;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2><i class="fas fa-robot"></i> Ollama AI 模型管理 (簡化版)</h2>
                <p class="text-muted">管理本地Ollama AI模型和訓練資料</p>
            </div>
        </div>

        <!-- 服務狀態 -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="status-card" id="ollama-status">
                    <h4><i class="fas fa-heartbeat"></i> Ollama 服務狀態</h4>
                    <div id="status-content">檢查中...</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="status-card bg-info text-white">
                    <h4><i class="fas fa-server"></i> 服務資訊</h4>
                    <p>URL: http://localhost:11434</p>
                    <p>預設模型: tinyllama</p>
                </div>
            </div>
        </div>

        <!-- 模型管理 -->
        <div class="row mb-4">
            <div class="col-12">
                <h3><i class="fas fa-cogs"></i> 模型管理</h3>
                <div class="card">
                    <div class="card-body">
                        <button class="btn btn-primary mb-3" id="load-models">
                            <i class="fas fa-sync"></i> 載入模型列表
                        </button>
                        <div id="models-container">
                            <p class="text-muted">點擊上方按鈕載入可用模型</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 訓練資料管理 -->
        <div class="row mb-4">
            <div class="col-12">
                <h3><i class="fas fa-database"></i> 訓練資料管理</h3>
                <div class="card">
                    <div class="card-body">
                        <!-- 輸入方式選擇 -->
                        <div class="mb-3">
                            <label class="form-label">選擇輸入方式:</label>
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="input-method" id="text-input" value="text" checked>
                                <label class="btn btn-outline-primary" for="text-input">
                                    <i class="fas fa-keyboard"></i> 文字輸入
                                </label>
                                
                                <input type="radio" class="btn-check" name="input-method" id="file-input" value="file">
                                <label class="btn btn-outline-primary" for="file-input">
                                    <i class="fas fa-file-upload"></i> 檔案上傳
                                </label>
                                
                                <input type="radio" class="btn-check" name="input-method" id="qa-input" value="qa">
                                <label class="btn btn-outline-primary" for="qa-input">
                                    <i class="fas fa-question-circle"></i> 問答對
                                </label>
                            </div>
                        </div>

                        <!-- 文字輸入區域 -->
                        <div id="text-input-area" class="input-area">
                            <div class="mb-3">
                                <label for="training-text-content" class="form-label">輸入文字內容:</label>
                                <textarea class="form-control" id="training-text-content" rows="8" 
                                    placeholder="請輸入任何文字內容，系統會自動處理格式...&#10;&#10;例如：&#10;康寧大學是一所位於台北市的私立大學。學校設有資訊管理系、數位媒體設計系、護理系等多個科系。學校致力於培養學生的專業技能和實務能力。"></textarea>
                                <div class="form-text">
                                    <strong>支援格式：</strong> 純文字、段落、列表等任何文字內容
                                </div>
                            </div>
                        </div>

                        <!-- 檔案上傳區域 -->
                        <div id="file-input-area" class="input-area" style="display: none;">
                            <div class="mb-3">
                                <label for="training-file" class="form-label">選擇檔案:</label>
                                <input type="file" class="form-control" id="training-file" accept=".pdf,.txt,.doc,.docx,.md">
                                <div class="form-text">
                                    <strong>支援格式：</strong> PDF、TXT、DOC、DOCX、Markdown 檔案
                                </div>
                            </div>
                            <div id="file-preview" class="mt-3" style="display: none;">
                                <div class="alert alert-info">
                                    <strong>檔案預覽：</strong>
                                    <div id="file-content-preview"></div>
                                </div>
                            </div>
                        </div>

                        <!-- 問答對輸入區域 -->
                        <div id="qa-input-area" class="input-area" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="question-input" class="form-label">問題:</label>
                                        <input type="text" class="form-control" id="question-input" 
                                            placeholder="例如：康寧大學有哪些科系？">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="answer-input" class="form-label">答案:</label>
                                        <textarea class="form-control" id="answer-input" rows="3" 
                                            placeholder="例如：康寧大學設有資訊管理系、數位媒體設計系、護理系等科系。"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 資料標題和描述 -->
                        <div class="mb-3">
                            <label for="data-title" class="form-label">資料標題 (可選):</label>
                            <input type="text" class="form-control" id="data-title" 
                                placeholder="例如：康寧大學基本資訊">
                        </div>
                        <div class="mb-3">
                            <label for="data-description" class="form-label">資料描述 (可選):</label>
                            <textarea class="form-control" id="data-description" rows="2" 
                                placeholder="簡短描述這份資料的內容或用途..."></textarea>
                        </div>

                        <!-- 操作按鈕 -->
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" id="add-training-data">
                                <i class="fas fa-plus"></i> 添加訓練資料
                            </button>
                            <button class="btn btn-info" id="load-training-data">
                                <i class="fas fa-list"></i> 查看已添加的資料
                            </button>
                            <button class="btn btn-secondary" id="clear-form">
                                <i class="fas fa-eraser"></i> 清除表單
                            </button>
                        </div>
                        
                        <div id="training-response" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 問答測試 -->
        <div class="row mb-4">
            <div class="col-12">
                <h3><i class="fas fa-comments"></i> 問答測試</h3>
                <div class="card">
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="test-question" class="form-label">輸入問題:</label>
                            <input type="text" class="form-control" id="test-question" 
                                   placeholder="例如：康寧大學有哪些科系？" 
                                   value="康寧大學有哪些科系？">
                        </div>
                        <div class="mb-3">
                            <label for="model-select" class="form-label">選擇模型:</label>
                            <select class="form-select" id="model-select">
                                <option value="tinyllama">tinyllama</option>
                            </select>
                        </div>
                        <button class="btn btn-success" id="send-question">
                            <i class="fas fa-paper-plane"></i> 發送問題
                        </button>
                        <button class="btn btn-secondary ms-2" id="clear-chat">
                            <i class="fas fa-trash"></i> 清除對話
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 對話記錄 -->
        <div class="row">
            <div class="col-12">
                <h3><i class="fas fa-history"></i> 對話記錄</h3>
                <div class="chat-container" id="chat-container">
                    <div class="message bot-message">
                        <strong>AI 助手:</strong> 您好！我是康寧大學的智能問答助手。請輸入您的問題，我會盡力為您解答。
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // 檢查 Ollama 健康狀態
            checkOllamaHealth();
            
            // 載入模型列表
            $('#load-models').click(function() {
                loadModels();
            });
            
            // 發送問題
            $('#send-question').click(function() {
                sendQuestion();
            });
            
            // 清除對話
            $('#clear-chat').click(function() {
                $('#chat-container').html(`
                    <div class="message bot-message">
                        <strong>AI 助手:</strong> 您好！我是康寧大學的智能問答助手。請輸入您的問題，我會盡力為您解答。
                    </div>
                `);
            });
            
            // 按 Enter 發送問題
            $('#test-question').keypress(function(e) {
                if (e.which === 13) {
                    sendQuestion();
                }
            });

            // 輸入方式切換
            $('input[name="input-method"]').change(function() {
                const method = $(this).val();
                $('.input-area').hide();
                $(`#${method}-input-area`).show();
            });

            // 檔案上傳處理
            $('#training-file').change(function() {
                const file = this.files[0];
                if (file) {
                    $('#file-preview').show();
                    $('#file-content-preview').html(`<i class="fas fa-file"></i> ${file.name} (${(file.size / 1024).toFixed(1)} KB)`);
                } else {
                    $('#file-preview').hide();
                }
            });

            // 清除表單
            $('#clear-form').click(function() {
                $('#training-text-content').val('');
                $('#question-input').val('');
                $('#answer-input').val('');
                $('#data-title').val('');
                $('#data-description').val('');
                $('#training-file').val('');
                $('#file-preview').hide();
                $('#training-response').empty();
            });

            // 訓練資料管理功能
            $('#add-training-data').click(function() {
                const inputMethod = $('input[name="input-method"]:checked').val();
                let contentData = {};
                let contentType = 'text';
                
                // 根據輸入方式處理資料
                switch(inputMethod) {
                    case 'text':
                        const textContent = $('#training-text-content').val().trim();
                        if (!textContent) {
                            alert('請輸入文字內容！');
                            return;
                        }
                        contentData = {
                            type: 'text',
                            content: textContent,
                            title: $('#data-title').val().trim(),
                            description: $('#data-description').val().trim()
                        };
                        contentType = 'text';
                        break;
                        
                    case 'file':
                        const file = $('#training-file')[0].files[0];
                        if (!file) {
                            alert('請選擇要上傳的檔案！');
                            return;
                        }
                        // 創建 FormData 用於檔案上傳
                        const formData = new FormData();
                        formData.append('action', 'add_training_data');
                        formData.append('content_type', 'file');
                        formData.append('file', file);
                        formData.append('title', $('#data-title').val().trim());
                        formData.append('description', $('#data-description').val().trim());
                        
                        $('#add-training-data').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 上傳中...');
                        $('#training-response').html('<div class="alert alert-info">正在上傳檔案並處理內容...</div>');
                        
                        $.ajax({
                            url: '../backend/api/ollama/ollama_api.php',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                if (response.success) {
                                    $('#training-response').html(`<div class="alert alert-success"><strong>✅ 成功:</strong> ${response.message}</div>`);
                                    $('#clear-form').click(); // 清除表單
                                } else {
                                    $('#training-response').html(`<div class="alert alert-danger"><strong>❌ 錯誤:</strong> ${response.message}</div>`);
                                }
                            },
                            error: function(xhr, status, error) {
                                $('#training-response').html(`<div class="alert alert-danger"><strong>上傳失敗:</strong> ${status} - ${error}</div>`);
                            },
                            complete: function() {
                                $('#add-training-data').prop('disabled', false).html('<i class="fas fa-plus"></i> 添加訓練資料');
                            }
                        });
                        return; // 提前返回，避免執行後面的代碼
                        
                    case 'qa':
                        const question = $('#question-input').val().trim();
                        const answer = $('#answer-input').val().trim();
                        if (!question || !answer) {
                            alert('請輸入問題和答案！');
                            return;
                        }
                        contentData = {
                            type: 'qa',
                            question: question,
                            answer: answer,
                            title: $('#data-title').val().trim(),
                            description: $('#data-description').val().trim()
                        };
                        contentType = 'qa';
                        break;
                }
                
                $('#add-training-data').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 添加中...');
                $('#training-response').html('<div class="alert alert-info">正在添加訓練資料...</div>');
                
                $.post('../backend/api/ollama/ollama_api.php', {
                    action: 'add_training_data',
                    content_type: contentType,
                    content_data: JSON.stringify(contentData)
                })
                .done(function(response) {
                    if (response.success) {
                        $('#training-response').html(`<div class="alert alert-success"><strong>✅ 成功:</strong> ${response.message}</div>`);
                        $('#clear-form').click(); // 清除表單
                    } else {
                        $('#training-response').html(`<div class="alert alert-danger"><strong>❌ 錯誤:</strong> ${response.message}</div>`);
                    }
                })
                .fail(function(xhr, status, error) {
                    $('#training-response').html(`<div class="alert alert-danger"><strong>API 請求失敗:</strong> ${status} - ${error}</div>`);
                })
                .always(function() {
                    $('#add-training-data').prop('disabled', false).html('<i class="fas fa-plus"></i> 添加訓練資料');
                });
            });

            $('#load-training-data').click(function() {
                $('#load-training-data').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 載入中...');
                $('#training-response').html('<div class="alert alert-info">正在載入訓練資料...</div>');
                
                $.get('../backend/api/ollama/ollama_api.php?action=get_training_data')
                .done(function(response) {
                    if (response.success) {
                        let html = '<div class="alert alert-success"><strong>已添加的訓練資料:</strong></div>';
                        if (response.data && response.data.length > 0) {
                            html += '<div class="table-responsive"><table class="table table-striped">';
                            html += '<thead><tr><th>類型</th><th>內容</th><th>添加時間</th></tr></thead><tbody>';
                            response.data.forEach(function(item) {
                                html += `<tr>
                                    <td>${item.content_type}</td>
                                    <td><small>${item.content_data.substring(0, 100)}${item.content_data.length > 100 ? '...' : ''}</small></td>
                                    <td>${item.created_at}</td>
                                </tr>`;
                            });
                            html += '</tbody></table></div>';
                        } else {
                            html += '<p class="text-muted">目前沒有訓練資料。</p>';
                        }
                        $('#training-response').html(html);
                    } else {
                        $('#training-response').html(`<div class="alert alert-danger"><strong>載入失敗:</strong> ${response.message}</div>`);
                    }
                })
                .fail(function(xhr, status, error) {
                    $('#training-response').html(`<div class="alert alert-danger"><strong>載入失敗:</strong> ${status} - ${error}</div>`);
                })
                .always(function() {
                    $('#load-training-data').prop('disabled', false).html('<i class="fas fa-list"></i> 查看已添加的資料');
                });
            });
        });

        function checkOllamaHealth() {
            $.get('../backend/api/ollama/ollama_api.php?action=check_health')
                .done(function(response) {
                    if (response.success) {
                        $('#ollama-status').removeClass('status-unhealthy').addClass('status-healthy');
                        $('#status-content').html(`
                            <h5><i class="fas fa-check-circle"></i> 服務正常</h5>
                            <p>已載入 ${response.models.length} 個模型</p>
                        `);
                        
                        // 更新模型選擇器
                        updateModelSelector(response.models);
                    } else {
                        $('#ollama-status').removeClass('status-healthy').addClass('status-unhealthy');
                        $('#status-content').html(`
                            <h5><i class="fas fa-exclamation-triangle"></i> 服務異常</h5>
                            <p>${response.message}</p>
                        `);
                    }
                })
                .fail(function(xhr, status, error) {
                    $('#ollama-status').removeClass('status-healthy').addClass('status-unhealthy');
                    $('#status-content').html(`
                        <h5><i class="fas fa-times-circle"></i> 連接失敗</h5>
                        <p>無法連接到Ollama服務</p>
                        <p>錯誤: ${error}</p>
                    `);
                });
        }

        function loadModels() {
            $('#load-models').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 載入中...');
            
            $.get('../backend/api/ollama/ollama_api.php?action=get_models')
                .done(function(response) {
                    if (response.success) {
                        displayModels(response.models);
                        updateModelSelector(response.models);
                    } else {
                        $('#models-container').html(`<div class="alert alert-danger">載入模型失敗: ${response.message}</div>`);
                    }
                })
                .fail(function(xhr, status, error) {
                    $('#models-container').html(`<div class="alert alert-danger">API 請求失敗: ${error}</div>`);
                })
                .always(function() {
                    $('#load-models').prop('disabled', false).html('<i class="fas fa-sync"></i> 載入模型列表');
                });
        }

        function displayModels(models) {
            if (models.length === 0) {
                $('#models-container').html('<div class="alert alert-warning">沒有找到可用的模型</div>');
                return;
            }
            
            let html = '<h5>可用模型:</h5>';
            models.forEach(function(model) {
                html += `
                    <div class="model-card">
                        <h6><i class="fas fa-brain"></i> ${model.name}</h6>
                        <p class="mb-1"><strong>大小:</strong> ${formatBytes(model.size)}</p>
                        <p class="mb-0"><strong>修改時間:</strong> ${new Date(model.modified_at).toLocaleString()}</p>
                    </div>
                `;
            });
            
            $('#models-container').html(html);
        }

        function updateModelSelector(models) {
            const selector = $('#model-select');
            selector.empty();
            
            models.forEach(function(model) {
                selector.append(`<option value="${model.name}">${model.name}</option>`);
            });
        }

        function sendQuestion() {
            const question = $('#test-question').val().trim();
            const model = $('#model-select').val();
            
            if (!question) {
                alert('請輸入問題！');
                return;
            }
            
            // 添加用戶問題到對話
            addMessage(question, 'user');
            
            // 禁用發送按鈕
            $('#send-question').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 思考中...');
            
            // 發送問題到 API
            $.post('../backend/api/ollama/ollama_api.php', {
                action: 'ask_question',
                question: question,
                model: model
            })
            .done(function(response) {
                if (response.success) {
                    addMessage(response.answer, 'ai', {
                        model: response.model,
                        source: response.source,
                        responseTime: response.response_time_ms
                    });
                } else {
                    addMessage(`錯誤: ${response.message}`, 'bot');
                }
            })
            .fail(function(xhr, status, error) {
                addMessage(`API 請求失敗: ${error}`, 'bot');
            })
            .always(function() {
                $('#send-question').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> 發送問題');
                // 清空輸入框
                $('#test-question').val('');
            });
        }

        function addMessage(content, type, metadata = {}) {
            const timestamp = new Date().toLocaleTimeString();
            let messageClass = 'message ';
            let icon = '';
            let prefix = '';
            
            switch(type) {
                case 'user':
                    messageClass += 'user-message';
                    icon = '<i class="fas fa-user"></i>';
                    prefix = '您';
                    break;
                case 'ai':
                    messageClass += 'ai-message';
                    icon = '<i class="fas fa-robot"></i>';
                    prefix = 'AI 助手';
                    break;
                default:
                    messageClass += 'bot-message';
                    icon = '<i class="fas fa-info-circle"></i>';
                    prefix = '系統';
            }
            
            let html = `
                <div class="${messageClass}">
                    <strong>${icon} ${prefix}:</strong> ${content}
                    <small class="d-block mt-1 opacity-75">${timestamp}</small>
            `;
            
            if (metadata.model) {
                html += `<small class="d-block mt-1 opacity-75">模型: ${metadata.model}</small>`;
            }
            if (metadata.source) {
                html += `<small class="d-block mt-1 opacity-75">來源: ${metadata.source}</small>`;
            }
            if (metadata.responseTime) {
                html += `<small class="d-block mt-1 opacity-75">回應時間: ${metadata.responseTime}ms</small>`;
            }
            
            html += '</div>';
            
            $('#chat-container').append(html);
            $('#chat-container').scrollTop($('#chat-container')[0].scrollHeight);
        }

        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>
</body>
</html>
