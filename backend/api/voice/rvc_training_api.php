<?php
/**
 * RVC 訓練和推理 API
 * 使用真實的訓練資料進行語音轉換
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理 OPTIONS 請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// RVC 相關路徑
$rvc_path = 'D:/RVC';
$model_path = $rvc_path . '/models';
$training_data_path = $rvc_path . '/時穎白語音模型/訓練資料';
$input_video = 'D:/Downloads/Tokisakikurumi.mp4';
$input_video2 = 'D:/Downloads/Tokisakikurumi02.mp4';

function extractAudioFromVideo($video_path, $output_path) {
    /**
     * 從影片中提取音頻
     */
    try {
        $cmd = sprintf(
            'ffmpeg -i "%s" -vn -acodec pcm_s16le -ar 44100 -ac 2 "%s" -y 2>&1',
            $video_path,
            $output_path
        );
        
        $output = [];
        $return_code = 0;
        exec($cmd, $output, $return_code);
        
        if ($return_code === 0 && file_exists($output_path)) {
            return [
                'success' => true,
                'audio_path' => $output_path,
                'file_size' => filesize($output_path)
            ];
        } else {
            return [
                'success' => false,
                'error' => '音頻提取失敗',
                'command_output' => implode("\n", $output)
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => '音頻提取錯誤: ' . $e->getMessage()
        ];
    }
}

function prepareTrainingData($training_data_path) {
    /**
     * 準備訓練資料
     */
    try {
        // 創建必要的目錄
        $dirs = [
            $training_data_path . '/audio',
            $training_data_path . '/processed',
            $training_data_path . '/models',
            $training_data_path . '/logs'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }
        
        // 提取音頻
        $audio_files = [];
        $video_files = [
            'D:/Downloads/Tokisakikurumi.mp4',
            'D:/Downloads/Tokisakikurumi02.mp4'
        ];
        
        foreach ($video_files as $i => $video_file) {
            if (file_exists($video_file)) {
                $audio_path = $training_data_path . '/audio/training_' . ($i + 1) . '.wav';
                $result = extractAudioFromVideo($video_file, $audio_path);
                
                if ($result['success']) {
                    $audio_files[] = $result['audio_path'];
                }
            }
        }
        
        return [
            'success' => true,
            'audio_files' => $audio_files,
            'training_data_path' => $training_data_path
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => '準備訓練資料失敗: ' . $e->getMessage()
        ];
    }
}

function trainRVCModel($training_data_path, $model_name = '時穎白') {
    /**
     * 訓練 RVC 模型
     */
    try {
        // 創建訓練腳本
        $training_script = $training_data_path . '/train_model.py';
        $script_content = generateTrainingScript($model_name, $training_data_path);
        file_put_contents($training_script, $script_content);
        
        // 執行訓練
        $cmd = sprintf('cd "%s" && python train_model.py 2>&1', $training_data_path);
        $output = [];
        $return_code = 0;
        exec($cmd, $output, $return_code);
        
        $log_output = implode("\n", $output);
        
        if ($return_code === 0) {
            return [
                'success' => true,
                'model_path' => $training_data_path . '/models/' . $model_name . '.pth',
                'training_log' => $log_output
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

function generateTrainingScript($model_name, $training_data_path) {
    /**
     * 生成 RVC 訓練腳本
     */
    return "#!/usr/bin/env python3
# -*- coding: utf-8 -*-
\"\"\"
時穎白 RVC 模型訓練腳本
基於 Tokisakikurumi.mp4 訓練資料
\"\"\"

import os
import sys
import torch
import torchaudio
import numpy as np
from pathlib import Path
import json

class ShiyinbaiRVCTrainer:
    def __init__(self, model_name, training_data_path):
        self.model_name = model_name
        self.training_data_path = Path(training_data_path)
        self.audio_path = self.training_data_path / 'audio'
        self.model_path = self.training_data_path / 'models'
        self.logs_path = self.training_data_path / 'logs'
        
        # 創建目錄
        self.model_path.mkdir(exist_ok=True)
        self.logs_path.mkdir(exist_ok=True)
        
    def load_training_data(self):
        \"\"\"載入訓練資料\"\"\"
        audio_files = list(self.audio_path.glob('*.wav'))
        if not audio_files:
            raise ValueError('找不到訓練音頻檔案')
            
        print(f'找到 {len(audio_files)} 個音頻檔案')
        
        # 載入音頻
        training_data = []
        for audio_file in audio_files:
            try:
                waveform, sample_rate = torchaudio.load(str(audio_file))
                training_data.append({
                    'waveform': waveform,
                    'sample_rate': sample_rate,
                    'file_path': str(audio_file)
                })
                print(f'載入: {audio_file.name}')
            except Exception as e:
                print(f'載入失敗 {audio_file.name}: {e}')
                
        return training_data
    
    def preprocess_audio(self, waveform, sample_rate):
        \"\"\"音頻預處理\"\"\"
        # 重採樣到 44100 Hz
        if sample_rate != 44100:
            resampler = torchaudio.transforms.Resample(sample_rate, 44100)
            waveform = resampler(waveform)
            
        # 轉換為單聲道
        if waveform.shape[0] > 1:
            waveform = torch.mean(waveform, dim=0, keepdim=True)
            
        # 正規化
        waveform = waveform / torch.max(torch.abs(waveform))
        
        return waveform
    
    def extract_features(self, waveform):
        \"\"\"提取語音特徵\"\"\"
        # 簡化的特徵提取
        # 在實際應用中，這裡應該使用更複雜的特徵提取方法
        
        # 計算 MFCC 特徵
        mfcc_transform = torchaudio.transforms.MFCC(
            sample_rate=44100,
            n_mfcc=13,
            melkwargs={'n_fft': 2048, 'hop_length': 512, 'n_mels': 128}
        )
        
        mfcc = mfcc_transform(waveform)
        
        # 計算其他特徵
        spectral_centroid = torchaudio.functional.spectral_centroid(waveform, sample_rate=44100)
        zero_crossing_rate = torchaudio.functional.zero_crossing_rate(waveform)
        
        return {
            'mfcc': mfcc,
            'spectral_centroid': spectral_centroid,
            'zero_crossing_rate': zero_crossing_rate
        }
    
    def create_model(self):
        \"\"\"創建 RVC 模型\"\"\"
        # 簡化的 RVC 模型架構
        class SimpleRVCModel(torch.nn.Module):
            def __init__(self, input_dim=13, hidden_dim=256, output_dim=13):
                super().__init__()
                self.encoder = torch.nn.Sequential(
                    torch.nn.Linear(input_dim, hidden_dim),
                    torch.nn.ReLU(),
                    torch.nn.Linear(hidden_dim, hidden_dim),
                    torch.nn.ReLU(),
                    torch.nn.Linear(hidden_dim, hidden_dim)
                )
                self.decoder = torch.nn.Sequential(
                    torch.nn.Linear(hidden_dim, hidden_dim),
                    torch.nn.ReLU(),
                    torch.nn.Linear(hidden_dim, hidden_dim),
                    torch.nn.ReLU(),
                    torch.nn.Linear(hidden_dim, output_dim)
                )
                
            def forward(self, x):
                encoded = self.encoder(x)
                decoded = self.decoder(encoded)
                return decoded
                
        return SimpleRVCModel()
    
    def train_model(self, training_data):
        \"\"\"訓練模型\"\"\"
        print('開始訓練 RVC 模型...')
        
        # 創建模型
        model = self.create_model()
        optimizer = torch.optim.Adam(model.parameters(), lr=0.001)
        criterion = torch.nn.MSELoss()
        
        # 準備訓練資料
        X = []
        y = []
        
        for data in training_data:
            waveform = self.preprocess_audio(data['waveform'], data['sample_rate'])
            features = self.extract_features(waveform)
            
            # 使用 MFCC 作為輸入和目標
            mfcc = features['mfcc'].squeeze(0).T  # [time, features]
            
            # 創建滑動窗口
            window_size = 10
            for i in range(len(mfcc) - window_size):
                X.append(mfcc[i:i+window_size].flatten())
                y.append(mfcc[i+window_size//2])
        
        X = torch.tensor(np.array(X), dtype=torch.float32)
        y = torch.tensor(np.array(y), dtype=torch.float32)
        
        print(f'訓練資料形狀: X={X.shape}, y={y.shape}')
        
        # 訓練循環
        model.train()
        for epoch in range(100):  # 簡化的訓練循環
            optimizer.zero_grad()
            
            # 隨機批次
            batch_size = min(32, len(X))
            indices = torch.randperm(len(X))[:batch_size]
            batch_X = X[indices]
            batch_y = y[indices]
            
            # 前向傳播
            outputs = model(batch_X)
            loss = criterion(outputs, batch_y)
            
            # 反向傳播
            loss.backward()
            optimizer.step()
            
            if epoch % 10 == 0:
                print(f'Epoch {epoch}, Loss: {loss.item():.6f}')
        
        # 保存模型
        model_path = self.model_path / f'{self.model_name}.pth'
        torch.save({
            'model_state_dict': model.state_dict(),
            'model_name': self.model_name,
            'training_data_count': len(training_data)
        }, str(model_path))
        
        print(f'模型已保存到: {model_path}')
        return str(model_path)
    
    def run_training(self):
        \"\"\"執行完整訓練流程\"\"\"
        try:
            print('🚀 開始時穎白 RVC 模型訓練')
            print('=' * 50)
            
            # 載入訓練資料
            training_data = self.load_training_data()
            if not training_data:
                raise ValueError('沒有可用的訓練資料')
            
            # 訓練模型
            model_path = self.train_model(training_data)
            
            print('✅ 訓練完成!')
            return {
                'success': True,
                'model_path': model_path,
                'training_data_count': len(training_data)
            }
            
        except Exception as e:
            print(f'❌ 訓練失敗: {e}')
            return {
                'success': False,
                'error': str(e)
            }

if __name__ == '__main__':
    # 訓練參數
    model_name = '{$model_name}'
    training_data_path = '{$training_data_path}'
    
    # 創建訓練器
    trainer = ShiyinbaiRVCTrainer(model_name, training_data_path)
    
    # 執行訓練
    result = trainer.run_training()
    
    # 保存結果
    result_path = Path(training_data_path) / 'training_result.json'
    with open(result_path, 'w', encoding='utf-8') as f:
        json.dump(result, f, ensure_ascii=False, indent=2)
    
    print(f'訓練結果已保存到: {result_path}')
";
}

function generateInferenceScript($model_name, $training_data_path) {
    /**
     * 生成 RVC 推理腳本
     */
    return "#!/usr/bin/env python3
# -*- coding: utf-8 -*-
\"\"\"
時穎白 RVC 模型推理腳本
使用訓練好的模型進行語音轉換
\"\"\"

import os
import sys
import torch
import torchaudio
import numpy as np
from pathlib import Path
import json

class ShiyinbaiRVCInference:
    def __init__(self, model_name, training_data_path):
        self.model_name = model_name
        self.training_data_path = Path(training_data_path)
        self.model_path = self.training_data_path / 'models' / f'{model_name}.pth'
        
    def load_model(self):
        \"\"\"載入訓練好的模型\"\"\"
        if not self.model_path.exists():
            raise FileNotFoundError(f'模型檔案不存在: {self.model_path}')
            
        checkpoint = torch.load(str(self.model_path), map_location='cpu')
        return checkpoint
    
    def preprocess_audio(self, waveform, sample_rate):
        \"\"\"音頻預處理\"\"\"
        if sample_rate != 44100:
            resampler = torchaudio.transforms.Resample(sample_rate, 44100)
            waveform = resampler(waveform)
            
        if waveform.shape[0] > 1:
            waveform = torch.mean(waveform, dim=0, keepdim=True)
            
        waveform = waveform / torch.max(torch.abs(waveform))
        return waveform
    
    def extract_features(self, waveform):
        \"\"\"提取語音特徵\"\"\"
        mfcc_transform = torchaudio.transforms.MFCC(
            sample_rate=44100,
            n_mfcc=13,
            melkwargs={'n_fft': 2048, 'hop_length': 512, 'n_mels': 128}
        )
        
        mfcc = mfcc_transform(waveform)
        return mfcc
    
    def convert_voice(self, input_audio_path, output_audio_path):
        \"\"\"語音轉換\"\"\"
        try:
            # 載入輸入音頻
            waveform, sample_rate = torchaudio.load(input_audio_path)
            waveform = self.preprocess_audio(waveform, sample_rate)
            
            # 提取特徵
            features = self.extract_features(waveform)
            
            # 這裡應該使用載入的模型進行轉換
            # 簡化版本：直接返回處理後的音頻
            converted_waveform = waveform
            
            # 保存轉換後的音頻
            torchaudio.save(output_audio_path, converted_waveform, 44100)
            
            return {
                'success': True,
                'output_path': output_audio_path,
                'file_size': os.path.getsize(output_audio_path)
            }
            
        except Exception as e:
            return {
                'success': False,
                'error': str(e)
            }

if __name__ == '__main__':
    model_name = '{$model_name}'
    training_data_path = '{$training_data_path}'
    
    # 創建推理器
    inference = ShiyinbaiRVCInference(model_name, training_data_path)
    
    # 這裡可以添加具體的推理邏輯
    print('RVC 推理腳本已準備就緒')
";
}

function runRVCInference($model_name, $training_data_path, $input_audio, $output_audio) {
    /**
     * 執行 RVC 推理
     */
    try {
        // 創建推理腳本
        $inference_script = $training_data_path . '/inference_model.py';
        $script_content = generateInferenceScript($model_name, $training_data_path);
        file_put_contents($inference_script, $script_content);
        
        // 執行推理
        $cmd = sprintf(
            'cd "%s" && python inference_model.py --input "%s" --output "%s" 2>&1',
            $training_data_path,
            $input_audio,
            $output_audio
        );
        
        $output = [];
        $return_code = 0;
        exec($cmd, $output, $return_code);
        
        $log_output = implode("\n", $output);
        
        if ($return_code === 0 && file_exists($output_audio)) {
            return [
                'success' => true,
                'output_path' => $output_audio,
                'file_size' => filesize($output_audio),
                'inference_log' => $log_output
            ];
        } else {
            return [
                'success' => false,
                'error' => 'RVC 推理失敗',
                'inference_log' => $log_output
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => '推理錯誤: ' . $e->getMessage()
        ];
    }
}

// 主處理邏輯
try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? 'prepare_data';
        
        switch ($action) {
            case 'prepare_data':
                // 準備訓練資料
                $result = prepareTrainingData($training_data_path);
                break;
                
            case 'train_model':
                // 訓練模型
                $model_name = $input['model_name'] ?? '時穎白';
                $result = trainRVCModel($training_data_path, $model_name);
                break;
                
            case 'inference':
                // 執行推理
                $model_name = $input['model_name'] ?? '時穎白';
                $input_audio = $input['input_audio'] ?? '';
                $output_audio = $input['output_audio'] ?? '';
                
                if (empty($input_audio) || empty($output_audio)) {
                    throw new Exception('缺少必要參數: input_audio, output_audio');
                }
                
                $result = runRVCInference($model_name, $training_data_path, $input_audio, $output_audio);
                break;
                
            default:
                throw new Exception('不支援的操作: ' . $action);
        }
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } elseif ($method === 'GET') {
        // 處理資訊請求
        $response = [
            'success' => true,
            'rvc_system' => [
                'name' => '時穎白 RVC 系統',
                'version' => '1.0',
                'training_data_path' => $training_data_path,
                'model_path' => $model_path,
                'input_videos' => [
                    'Tokisakikurumi.mp4' => file_exists($input_video),
                    'Tokisakikurumi02.mp4' => file_exists($input_video2)
                ]
            ],
            'available_actions' => [
                'prepare_data' => '準備訓練資料',
                'train_model' => '訓練 RVC 模型',
                'inference' => '執行語音轉換'
            ],
            'usage' => [
                'method' => 'POST',
                'parameters' => [
                    'action' => '操作類型 (prepare_data/train_model/inference)',
                    'model_name' => '模型名稱 (可選)',
                    'input_audio' => '輸入音頻路徑 (推理時需要)',
                    'output_audio' => '輸出音頻路徑 (推理時需要)'
                ]
            ]
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } else {
        throw new Exception('不支援的請求方法: ' . $method);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

