<?php
/**
 * 快速訓練 API
 * 專門處理 Tokisakikurumi.mp4 和 Tokisakikurumi02.mp4 的訓練流程
 */

// 關閉錯誤顯示
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 路徑設定
$rvc_path = 'D:/RVC';
$training_data_path = $rvc_path . '/時穎白語音模型/訓練資料';
$model_path = $training_data_path . '/models';
$audio_path = $training_data_path . '/audio';

function setupEnvironment() {
    /**
     * 設置訓練環境
     */
    global $training_data_path, $model_path, $audio_path;
    
    $dirs = [
        $training_data_path,
        $model_path,
        $audio_path,
        $training_data_path . '/logs'
    ];
    
    $results = [];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            $created = mkdir($dir, 0777, true);
            $results[$dir] = $created ? 'created' : 'failed';
        } else {
            $results[$dir] = 'exists';
        }
    }
    
    return [
        'success' => true,
        'directories' => $results,
        'message' => '環境設置完成'
    ];
}

function extractAudioFromVideos() {
    /**
     * 從影片中提取音頻
     */
    global $audio_path;
    
    $training_videos = [
        'D:/Downloads/Tokisakikurumi.mp4',
        'D:/Downloads/Tokisakikurumi02.mp4'
    ];
    
    $extraction_results = [];
    $success_count = 0;
    $total_duration = 0;
    
    foreach ($training_videos as $i => $video_path) {
        if (file_exists($video_path)) {
            $output_path = $audio_path . '/training_' . ($i + 1) . '.wav';
            
            // 使用 FFmpeg 提取音頻
            $cmd = sprintf(
                'ffmpeg -i "%s" -vn -acodec pcm_s16le -ar 44100 -ac 1 "%s" -y 2>&1',
                $video_path,
                $output_path
            );
            
            $output = [];
            $return_code = 0;
            exec($cmd, $output, $return_code);
            
            if ($return_code === 0 && file_exists($output_path)) {
                $file_size = filesize($output_path);
                $duration = getAudioDuration($output_path);
                $total_duration += $duration;
                
                $extraction_results[] = [
                    'video' => basename($video_path),
                    'audio' => basename($output_path),
                    'size' => $file_size,
                    'duration' => $duration,
                    'status' => 'success'
                ];
                $success_count++;
            } else {
                $extraction_results[] = [
                    'video' => basename($video_path),
                    'status' => 'failed',
                    'error' => implode("\n", array_slice($output, 0, 5))
                ];
            }
        } else {
            $extraction_results[] = [
                'video' => basename($video_path),
                'status' => 'not_found',
                'error' => '影片檔案不存在'
            ];
        }
    }
    
    return [
        'success' => $success_count > 0,
        'extracted_count' => $success_count,
        'total_duration' => $total_duration,
        'results' => $extraction_results
    ];
}

function getAudioDuration($audio_path) {
    /**
     * 獲取音頻時長
     */
    $cmd = sprintf('ffprobe -v quiet -show_entries format=duration -of csv="p=0" "%s" 2>&1', $audio_path);
    $output = [];
    exec($cmd, $output);
    return isset($output[0]) ? floatval($output[0]) : 0;
}

function createQuickTrainingScript() {
    /**
     * 創建快速訓練腳本
     */
    global $training_data_path;
    
    $script_path = $training_data_path . '/quick_train.py';
    $script_content = generateQuickTrainingScript();
    
    $result = file_put_contents($script_path, $script_content);
    
    return [
        'success' => $result !== false,
        'script_path' => $script_path,
        'script_size' => $result
    ];
}

function generateQuickTrainingScript() {
    /**
     * 生成快速訓練腳本
     */
    return "#!/usr/bin/env python3
# -*- coding: utf-8 -*-
\"\"\"
時穎白快速訓練腳本
基於 Tokisakikurumi.mp4 訓練資料
\"\"\"

import os
import sys
import torch
import torch.nn as nn
import torch.optim as optim
import numpy as np
from pathlib import Path
import json
import librosa
import warnings
warnings.filterwarnings('ignore')

class QuickShiyinbaiTrainer:
    def __init__(self):
        self.training_data_path = Path('{$training_data_path}')
        self.audio_path = self.training_data_path / 'audio'
        self.model_path = self.training_data_path / 'models'
        
        # 模型參數
        self.sample_rate = 44100
        self.n_mfcc = 13
        
    def load_training_data(self):
        \"\"\"載入訓練資料\"\"\"
        audio_files = list(self.audio_path.glob('*.wav'))
        if not audio_files:
            raise ValueError('找不到訓練音頻檔案')
            
        print(f'找到 {len(audio_files)} 個音頻檔案')
        
        training_data = []
        total_duration = 0
        
        for audio_file in audio_files:
            try:
                # 載入音頻
                y, sr = librosa.load(str(audio_file), sr=self.sample_rate)
                y = librosa.util.normalize(y)
                
                duration = len(y) / self.sample_rate
                total_duration += duration
                
                training_data.append({
                    'audio': y,
                    'duration': duration,
                    'file_path': str(audio_file)
                })
                
                print(f'載入: {audio_file.name} (時長: {duration:.2f}s)')
                
            except Exception as e:
                print(f'載入失敗 {audio_file.name}: {e}')
                
        print(f'總訓練時長: {total_duration:.2f} 秒')
        return training_data
    
    def extract_features(self, audio):
        \"\"\"提取語音特徵\"\"\"
        # 提取 MFCC 特徵
        mfcc = librosa.feature.mfcc(y=audio, sr=self.sample_rate, n_mfcc=self.n_mfcc)
        return mfcc.T  # [time, features]
    
    def create_simple_model(self):
        \"\"\"創建簡化模型\"\"\"
        class SimpleRVCModel(nn.Module):
            def __init__(self, input_dim=13, hidden_dim=256, output_dim=13):
                super().__init__()
                self.encoder = nn.Sequential(
                    nn.Linear(input_dim, hidden_dim),
                    nn.ReLU(),
                    nn.Dropout(0.2),
                    nn.Linear(hidden_dim, hidden_dim),
                    nn.ReLU(),
                    nn.Dropout(0.2),
                    nn.Linear(hidden_dim, hidden_dim // 2)
                )
                
                self.decoder = nn.Sequential(
                    nn.Linear(hidden_dim // 2, hidden_dim),
                    nn.ReLU(),
                    nn.Dropout(0.2),
                    nn.Linear(hidden_dim, hidden_dim),
                    nn.ReLU(),
                    nn.Dropout(0.2),
                    nn.Linear(hidden_dim, output_dim)
                )
                
            def forward(self, x):
                encoded = self.encoder(x)
                decoded = self.decoder(encoded)
                return decoded
                
        return SimpleRVCModel()
    
    def prepare_training_data(self, training_data):
        \"\"\"準備訓練資料\"\"\"
        X = []
        y = []
        
        for data in training_data:
            audio = data['audio']
            features = self.extract_features(audio)
            
            # 創建滑動窗口
            window_size = 10
            step_size = 5
            
            for i in range(0, len(features) - window_size, step_size):
                window = features[i:i+window_size]
                if len(window) == window_size:
                    X.append(window.flatten())
                    y.append(features[i+window_size//2])
        
        X = np.array(X, dtype=np.float32)
        y = np.array(y, dtype=np.float32)
        
        print(f'準備訓練資料: X={X.shape}, y={y.shape}')
        return X, y
    
    def train_model(self, X, y):
        \"\"\"訓練模型\"\"\"
        print('開始訓練 RVC 模型...')
        
        # 轉換為 PyTorch 張量
        X_tensor = torch.tensor(X, dtype=torch.float32)
        y_tensor = torch.tensor(y, dtype=torch.float32)
        
        # 創建模型
        model = self.create_simple_model()
        optimizer = optim.Adam(model.parameters(), lr=0.001)
        criterion = nn.MSELoss()
        
        # 訓練參數
        batch_size = 32
        num_epochs = 100
        
        model.train()
        best_loss = float('inf')
        
        for epoch in range(num_epochs):
            epoch_loss = 0
            num_batches = 0
            
            # 隨機批次
            indices = torch.randperm(len(X_tensor))
            for i in range(0, len(X_tensor), batch_size):
                batch_indices = indices[i:i+batch_size]
                batch_X = X_tensor[batch_indices]
                batch_y = y_tensor[batch_indices]
                
                optimizer.zero_grad()
                outputs = model(batch_X)
                loss = criterion(outputs, batch_y)
                loss.backward()
                optimizer.step()
                
                epoch_loss += loss.item()
                num_batches += 1
            
            avg_loss = epoch_loss / num_batches
            
            if epoch % 20 == 0:
                print(f'Epoch {epoch:3d}, Loss: {avg_loss:.6f}')
            
            # 保存最佳模型
            if avg_loss < best_loss:
                best_loss = avg_loss
                torch.save({
                    'model_state_dict': model.state_dict(),
                    'optimizer_state_dict': optimizer.state_dict(),
                    'epoch': epoch,
                    'loss': avg_loss,
                    'model_name': '時穎白',
                    'training_data_count': len(X)
                }, str(self.model_path / '時穎白.pth'))
        
        print(f'訓練完成! 最佳損失: {best_loss:.6f}')
        return str(self.model_path / '時穎白.pth'), best_loss
    
    def run_training(self):
        \"\"\"執行訓練\"\"\"
        try:
            print('🚀 開始時穎白快速訓練')
            print('=' * 50)
            
            # 載入訓練資料
            training_data = self.load_training_data()
            if not training_data:
                raise ValueError('沒有可用的訓練資料')
            
            # 準備訓練資料
            X, y = self.prepare_training_data(training_data)
            
            # 訓練模型
            model_path, final_loss = self.train_model(X, y)
            
            result = {
                'success': True,
                'model_path': model_path,
                'training_data_count': len(training_data),
                'feature_count': len(X),
                'final_loss': final_loss,
                'model_name': '時穎白',
                'training_completed': True
            }
            
            print('✅ 訓練完成!')
            return result
            
        except Exception as e:
            print(f'❌ 訓練失敗: {e}')
            return {
                'success': False,
                'error': str(e)
            }

if __name__ == '__main__':
    trainer = QuickShiyinbaiTrainer()
    result = trainer.run_training()
    
    # 輸出結果
    print('\\n' + '=' * 50)
    print('訓練結果:')
    print(json.dumps(result, ensure_ascii=False, indent=2))
";
}

function runQuickTraining() {
    /**
     * 執行快速訓練
     */
    global $training_data_path;
    
    try {
        // 創建訓練腳本
        $script_result = createQuickTrainingScript();
        if (!$script_result['success']) {
            throw new Exception('無法創建訓練腳本');
        }
        
        // 執行訓練
        $cmd = sprintf('cd "%s" && python quick_train.py 2>&1', $training_data_path);
        $output = [];
        $return_code = 0;
        exec($cmd, $output, $return_code);
        
        $log_output = implode("\n", $output);
        
        if ($return_code === 0) {
            return [
                'success' => true,
                'model_path' => $training_data_path . '/models/時穎白.pth',
                'training_log' => $log_output,
                'script_path' => $script_result['script_path']
            ];
        } else {
            return [
                'success' => false,
                'error' => '模型訓練失敗',
                'training_log' => $log_output
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => '訓練錯誤: ' . $e->getMessage()
        ];
    }
}

// 主處理邏輯
try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? 'quick_training';
        
        switch ($action) {
            case 'setup_environment':
                $result = setupEnvironment();
                break;
                
            case 'extract_audio':
                $result = extractAudioFromVideos();
                break;
                
            case 'create_script':
                $result = createQuickTrainingScript();
                break;
                
            case 'quick_training':
            default:
                $result = runQuickTraining();
                break;
        }
        
        ob_clean();
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } elseif ($method === 'GET') {
        $response = [
            'success' => true,
            'system_info' => [
                'name' => '快速訓練系統',
                'version' => '1.0',
                'description' => '專門處理 Tokisakikurumi.mp4 的快速訓練流程'
            ],
            'available_actions' => [
                'setup_environment' => '設置環境',
                'extract_audio' => '提取音頻',
                'create_script' => '創建腳本',
                'quick_training' => '快速訓練'
            ]
        ];
        
        ob_clean();
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } else {
        throw new Exception('不支援的請求方法: ' . $method);
    }
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

