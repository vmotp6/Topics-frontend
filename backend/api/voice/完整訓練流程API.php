<?php
/**
 * 完整訓練流程 API
 * 從影片提取音頻到訓練 RVC 模型的完整流程
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

function createDirectories() {
    /**
     * 創建必要的目錄
     */
    global $training_data_path, $model_path, $audio_path;
    
    $dirs = [
        $training_data_path,
        $model_path,
        $audio_path,
        $training_data_path . '/logs',
        $training_data_path . '/checkpoints'
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
    
    return $results;
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
                $extraction_results[] = [
                    'video' => basename($video_path),
                    'audio' => basename($output_path),
                    'size' => filesize($output_path),
                    'duration' => getAudioDuration($output_path),
                    'status' => 'success'
                ];
                $success_count++;
            } else {
                $extraction_results[] = [
                    'video' => basename($video_path),
                    'status' => 'failed',
                    'error' => implode("\n", $output)
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

function prepareTrainingScript() {
    /**
     * 準備訓練腳本
     */
    global $training_data_path;
    
    $script_path = $training_data_path . '/train_shiyinbai_complete.py';
    $script_content = generateCompleteTrainingScript();
    
    $result = file_put_contents($script_path, $script_content);
    
    return [
        'success' => $result !== false,
        'script_path' => $script_path,
        'script_size' => $result
    ];
}

function generateCompleteTrainingScript() {
    /**
     * 生成完整的訓練腳本
     */
    return "#!/usr/bin/env python3
# -*- coding: utf-8 -*-
\"\"\"
時穎白完整 RVC 訓練腳本
基於 Tokisakikurumi.mp4 訓練資料，目標達到 90% 相似度
\"\"\"

import os
import sys
import torch
import torchaudio
import torch.nn as nn
import torch.optim as optim
import numpy as np
from pathlib import Path
import json
import librosa
from scipy.signal import stft, istft
import warnings
warnings.filterwarnings('ignore')

class CompleteShiyinbaiTrainer:
    def __init__(self):
        self.training_data_path = Path('{$training_data_path}')
        self.audio_path = self.training_data_path / 'audio'
        self.model_path = self.training_data_path / 'models'
        self.logs_path = self.training_data_path / 'logs'
        
        # 創建目錄
        self.model_path.mkdir(exist_ok=True)
        self.logs_path.mkdir(exist_ok=True)
        
        # 模型參數
        self.sample_rate = 44100
        self.hop_length = 512
        self.n_fft = 2048
        self.n_mels = 128
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
                # 使用 librosa 載入音頻
                y, sr = librosa.load(str(audio_file), sr=None)
                
                # 重採樣到目標採樣率
                if sr != self.sample_rate:
                    y = librosa.resample(y, orig_sr=sr, target_sr=self.sample_rate)
                
                # 正規化
                y = librosa.util.normalize(y)
                
                duration = len(y) / self.sample_rate
                total_duration += duration
                
                training_data.append({
                    'audio': y,
                    'sample_rate': self.sample_rate,
                    'file_path': str(audio_file),
                    'duration': duration
                })
                
                print(f'載入: {audio_file.name} (時長: {duration:.2f}s)')
                
            except Exception as e:
                print(f'載入失敗 {audio_file.name}: {e}')
                
        print(f'總訓練時長: {total_duration:.2f} 秒')
        return training_data
    
    def extract_advanced_features(self, audio):
        \"\"\"提取高級語音特徵\"\"\"
        features = {}
        
        # 1. MFCC 特徵
        mfcc = librosa.feature.mfcc(y=audio, sr=self.sample_rate, n_mfcc=self.n_mfcc)
        features['mfcc'] = mfcc
        
        # 2. 梅爾頻譜
        mel_spec = librosa.feature.melspectrogram(y=audio, sr=self.sample_rate, n_mels=self.n_mels)
        features['mel_spec'] = mel_spec
        
        # 3. 頻譜質心
        spectral_centroids = librosa.feature.spectral_centroid(y=audio, sr=self.sample_rate)[0]
        features['spectral_centroid'] = spectral_centroids
        
        # 4. 頻譜滾降
        spectral_rolloff = librosa.feature.spectral_rolloff(y=audio, sr=self.sample_rate)[0]
        features['spectral_rolloff'] = spectral_rolloff
        
        # 5. 過零率
        zcr = librosa.feature.zero_crossing_rate(audio)[0]
        features['zcr'] = zcr
        
        # 6. 色度特徵
        chroma = librosa.feature.chroma_stft(y=audio, sr=self.sample_rate)
        features['chroma'] = chroma
        
        return features
    
    def create_advanced_model(self):
        \"\"\"創建高級 RVC 模型\"\"\"
        class AdvancedRVCModel(nn.Module):
            def __init__(self, input_dim=13, hidden_dim=512, output_dim=13):
                super().__init__()
                
                # 編碼器
                self.encoder = nn.Sequential(
                    nn.Linear(input_dim, hidden_dim),
                    nn.BatchNorm1d(hidden_dim),
                    nn.ReLU(),
                    nn.Dropout(0.2),
                    
                    nn.Linear(hidden_dim, hidden_dim),
                    nn.BatchNorm1d(hidden_dim),
                    nn.ReLU(),
                    nn.Dropout(0.2),
                    
                    nn.Linear(hidden_dim, hidden_dim // 2),
                    nn.BatchNorm1d(hidden_dim // 2),
                    nn.ReLU(),
                    nn.Dropout(0.1)
                )
                
                # 解碼器
                self.decoder = nn.Sequential(
                    nn.Linear(hidden_dim // 2, hidden_dim),
                    nn.BatchNorm1d(hidden_dim),
                    nn.ReLU(),
                    nn.Dropout(0.1),
                    
                    nn.Linear(hidden_dim, hidden_dim),
                    nn.BatchNorm1d(hidden_dim),
                    nn.ReLU(),
                    nn.Dropout(0.2),
                    
                    nn.Linear(hidden_dim, output_dim)
                )
                
                # 注意力機制
                self.attention = nn.MultiheadAttention(
                    embed_dim=hidden_dim // 2,
                    num_heads=8,
                    dropout=0.1
                )
                
            def forward(self, x):
                # 編碼
                encoded = self.encoder(x)
                
                # 注意力機制
                encoded_attended, _ = self.attention(encoded, encoded, encoded)
                
                # 解碼
                decoded = self.decoder(encoded_attended)
                
                return decoded
                
        return AdvancedRVCModel()
    
    def prepare_training_data(self, training_data):
        \"\"\"準備訓練資料\"\"\"
        X = []
        y = []
        
        for data in training_data:
            audio = data['audio']
            features = self.extract_advanced_features(audio)
            
            # 使用 MFCC 作為主要特徵
            mfcc = features['mfcc'].T  # [time, features]
            
            # 創建滑動窗口
            window_size = 20
            step_size = 10
            
            for i in range(0, len(mfcc) - window_size, step_size):
                window = mfcc[i:i+window_size]
                if len(window) == window_size:
                    X.append(window.flatten())
                    y.append(mfcc[i+window_size//2])
        
        X = np.array(X, dtype=np.float32)
        y = np.array(y, dtype=np.float32)
        
        print(f'準備訓練資料: X={X.shape}, y={y.shape}')
        return X, y
    
    def train_model(self, X, y):
        \"\"\"訓練模型\"\"\"
        print('開始訓練高級 RVC 模型...')
        
        # 轉換為 PyTorch 張量
        X_tensor = torch.tensor(X, dtype=torch.float32)
        y_tensor = torch.tensor(y, dtype=torch.float32)
        
        # 創建模型
        model = self.create_advanced_model()
        optimizer = optim.Adam(model.parameters(), lr=0.001, weight_decay=1e-5)
        scheduler = optim.lr_scheduler.ReduceLROnPlateau(optimizer, patience=10, factor=0.5)
        criterion = nn.MSELoss()
        
        # 訓練參數
        batch_size = 64
        num_epochs = 200
        
        model.train()
        best_loss = float('inf')
        patience = 20
        patience_counter = 0
        
        training_log = []
        
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
                
                # 梯度裁剪
                torch.nn.utils.clip_grad_norm_(model.parameters(), max_norm=1.0)
                
                optimizer.step()
                
                epoch_loss += loss.item()
                num_batches += 1
            
            avg_loss = epoch_loss / num_batches
            scheduler.step(avg_loss)
            
            if epoch % 10 == 0:
                print(f'Epoch {epoch:3d}, Loss: {avg_loss:.6f}, LR: {optimizer.param_groups[0][\"lr\"]:.6f}')
            
            training_log.append({
                'epoch': epoch,
                'loss': avg_loss,
                'lr': optimizer.param_groups[0]['lr']
            })
            
            # 早停機制
            if avg_loss < best_loss:
                best_loss = avg_loss
                patience_counter = 0
                
                # 保存最佳模型
                torch.save({
                    'model_state_dict': model.state_dict(),
                    'optimizer_state_dict': optimizer.state_dict(),
                    'epoch': epoch,
                    'loss': avg_loss,
                    'model_name': '時穎白',
                    'training_data_count': len(X),
                    'best_loss': best_loss
                }, str(self.model_path / '時穎白.pth'))
            else:
                patience_counter += 1
                if patience_counter >= patience:
                    print(f'早停於 Epoch {epoch}')
                    break
        
        # 保存訓練日誌
        log_path = self.logs_path / 'training_log.json'
        with open(log_path, 'w', encoding='utf-8') as f:
            json.dump(training_log, f, ensure_ascii=False, indent=2)
        
        print(f'訓練完成! 最佳損失: {best_loss:.6f}')
        return str(self.model_path / '時穎白.pth'), best_loss
    
    def run_complete_training(self):
        \"\"\"執行完整訓練流程\"\"\"
        try:
            print('🚀 開始時穎白完整 RVC 模型訓練')
            print('目標: 達到 90% 相似度')
            print('=' * 60)
            
            # 載入訓練資料
            training_data = self.load_training_data()
            if not training_data:
                raise ValueError('沒有可用的訓練資料')
            
            print(f'總訓練資料: {len(training_data)} 個檔案')
            total_duration = sum(data['duration'] for data in training_data)
            print(f'總時長: {total_duration:.2f} 秒')
            
            # 準備訓練資料
            X, y = self.prepare_training_data(training_data)
            
            # 訓練模型
            model_path, final_loss = self.train_model(X, y)
            
            # 保存訓練結果
            result = {
                'success': True,
                'model_path': model_path,
                'training_data_count': len(training_data),
                'total_duration': total_duration,
                'feature_count': len(X),
                'final_loss': final_loss,
                'model_architecture': 'Advanced RVC with Attention',
                'target_similarity': '90%',
                'training_completed': True
            }
            
            result_path = self.logs_path / 'training_result.json'
            with open(result_path, 'w', encoding='utf-8') as f:
                json.dump(result, f, ensure_ascii=False, indent=2)
            
            print('✅ 訓練完成!')
            print(f'模型已保存到: {model_path}')
            print(f'訓練結果已保存到: {result_path}')
            
            return result
            
        except Exception as e:
            print(f'❌ 訓練失敗: {e}')
            return {
                'success': False,
                'error': str(e)
            }

if __name__ == '__main__':
    trainer = CompleteShiyinbaiTrainer()
    result = trainer.run_complete_training()
    
    # 輸出結果
    print('\\n' + '=' * 60)
    print('訓練結果:')
    print(json.dumps(result, ensure_ascii=False, indent=2))
";
}

function runCompleteTraining() {
    /**
     * 執行完整訓練流程
     */
    global $training_data_path;
    
    try {
        // 準備訓練腳本
        $script_result = prepareTrainingScript();
        if (!$script_result['success']) {
            throw new Exception('無法創建訓練腳本');
        }
        
        // 執行訓練
        $cmd = sprintf('cd "%s" && python train_shiyinbai_complete.py 2>&1', $training_data_path);
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
        $action = $input['action'] ?? 'full_training';
        
        switch ($action) {
            case 'create_directories':
                $result = createDirectories();
                break;
                
            case 'extract_audio':
                $result = extractAudioFromVideos();
                break;
                
            case 'prepare_script':
                $result = prepareTrainingScript();
                break;
                
            case 'full_training':
            default:
                $result = runCompleteTraining();
                break;
        }
        
        ob_clean();
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } elseif ($method === 'GET') {
        $response = [
            'success' => true,
            'system_info' => [
                'name' => '完整訓練流程系統',
                'version' => '1.0',
                'description' => '從影片提取音頻到訓練 RVC 模型的完整流程'
            ],
            'available_actions' => [
                'create_directories' => '創建必要目錄',
                'extract_audio' => '從影片提取音頻',
                'prepare_script' => '準備訓練腳本',
                'full_training' => '執行完整訓練流程'
            ],
            'usage' => [
                'method' => 'POST',
                'parameters' => [
                    'action' => '操作類型 (create_directories/extract_audio/prepare_script/full_training)'
                ]
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

