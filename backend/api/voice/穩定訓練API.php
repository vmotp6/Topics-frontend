<?php
/**
 * 穩定訓練 API
 * 修正訓練失敗問題，建立穩定的訓練流程
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

function checkEnvironment() {
    /**
     * 檢查環境和依賴
     */
    $checks = [];
    
    // 檢查 Python
    $python_output = [];
    $python_return = 0;
    exec('python --version 2>&1', $python_output, $python_return);
    $checks['python'] = [
        'available' => $python_return === 0,
        'version' => implode(' ', $python_output),
        'command' => 'python --version'
    ];
    
    // 檢查 FFmpeg
    $ffmpeg_output = [];
    $ffmpeg_return = 0;
    exec('ffmpeg -version 2>&1', $ffmpeg_output, $ffmpeg_return);
    $checks['ffmpeg'] = [
        'available' => $ffmpeg_return === 0,
        'version' => isset($ffmpeg_output[0]) ? $ffmpeg_output[0] : 'Unknown',
        'command' => 'ffmpeg -version'
    ];
    
    // 檢查 Python 套件
    $packages = ['torch', 'numpy', 'librosa'];
    $package_checks = [];
    
    foreach ($packages as $package) {
        $output = [];
        $return = 0;
        exec("python -c \"import $package; print($package.__version__)\" 2>&1", $output, $return);
        
        $package_checks[$package] = [
            'available' => $return === 0,
            'version' => $return === 0 ? $output[0] : 'Not installed',
            'error' => $return !== 0 ? implode(' ', $output) : null
        ];
    }
    
    $checks['packages'] = $package_checks;
    
    // 檢查路徑
    $path_checks = [
        'rvc_path' => is_dir($rvc_path),
        'training_data_path' => is_dir($training_data_path),
        'model_path' => is_dir($model_path),
        'audio_path' => is_dir($audio_path)
    ];
    
    $checks['paths'] = $path_checks;
    
    // 檢查訓練影片
    $video_checks = [
        'Tokisakikurumi.mp4' => file_exists('D:/Downloads/Tokisakikurumi.mp4'),
        'Tokisakikurumi02.mp4' => file_exists('D:/Downloads/Tokisakikurumi02.mp4')
    ];
    
    $checks['videos'] = $video_checks;
    
    return $checks;
}

function installDependencies() {
    /**
     * 安裝必要的依賴
     */
    $install_commands = [
        'torch' => 'pip install torch torchvision torchaudio',
        'numpy' => 'pip install numpy',
        'librosa' => 'pip install librosa',
        'scipy' => 'pip install scipy'
    ];
    
    $results = [];
    
    foreach ($install_commands as $package => $command) {
        $output = [];
        $return = 0;
        exec($command . ' 2>&1', $output, $return);
        
        $results[$package] = [
            'command' => $command,
            'success' => $return === 0,
            'output' => implode("\n", $output)
        ];
    }
    
    return $results;
}

function createStableTrainingScript() {
    /**
     * 創建穩定的訓練腳本
     */
    global $training_data_path;
    
    $script_path = $training_data_path . '/stable_train.py';
    $script_content = generateStableTrainingScript();
    
    $result = file_put_contents($script_path, $script_content);
    
    return [
        'success' => $result !== false,
        'script_path' => $script_path,
        'script_size' => $result
    ];
}

function generateStableTrainingScript() {
    /**
     * 生成穩定的訓練腳本
     */
    return "#!/usr/bin/env python3
# -*- coding: utf-8 -*-
\"\"\"
時穎白穩定訓練腳本
修正訓練失敗問題，建立穩定的訓練流程
\"\"\"

import os
import sys
import json
import warnings
warnings.filterwarnings('ignore')

# 檢查必要的套件
try:
    import torch
    import torch.nn as nn
    import torch.optim as optim
    print('✅ PyTorch 可用')
except ImportError as e:
    print(f'❌ PyTorch 不可用: {e}')
    sys.exit(1)

try:
    import numpy as np
    print('✅ NumPy 可用')
except ImportError as e:
    print(f'❌ NumPy 不可用: {e}')
    sys.exit(1)

try:
    import librosa
    print('✅ Librosa 可用')
except ImportError as e:
    print(f'❌ Librosa 不可用: {e}')
    sys.exit(1)

class StableShiyinbaiTrainer:
    def __init__(self):
        self.training_data_path = '{$training_data_path}'
        self.audio_path = os.path.join(self.training_data_path, 'audio')
        self.model_path = os.path.join(self.training_data_path, 'models')
        
        # 確保目錄存在
        os.makedirs(self.audio_path, exist_ok=True)
        os.makedirs(self.model_path, exist_ok=True)
        
        # 模型參數
        self.sample_rate = 44100
        self.n_mfcc = 13
        
    def load_training_data(self):
        \"\"\"載入訓練資料\"\"\"
        audio_files = []
        for file in os.listdir(self.audio_path):
            if file.endswith('.wav'):
                audio_files.append(os.path.join(self.audio_path, file))
        
        if not audio_files:
            raise ValueError('找不到訓練音頻檔案')
            
        print(f'找到 {len(audio_files)} 個音頻檔案')
        
        training_data = []
        total_duration = 0
        
        for audio_file in audio_files:
            try:
                # 載入音頻
                y, sr = librosa.load(audio_file, sr=self.sample_rate)
                y = librosa.util.normalize(y)
                
                duration = len(y) / self.sample_rate
                total_duration += duration
                
                training_data.append({
                    'audio': y,
                    'duration': duration,
                    'file_path': audio_file
                })
                
                print(f'載入: {os.path.basename(audio_file)} (時長: {duration:.2f}s)')
                
            except Exception as e:
                print(f'載入失敗 {audio_file}: {e}')
                
        print(f'總訓練時長: {total_duration:.2f} 秒')
        return training_data
    
    def extract_simple_features(self, audio):
        \"\"\"提取簡單特徵\"\"\"
        try:
            # 提取 MFCC 特徵
            mfcc = librosa.feature.mfcc(y=audio, sr=self.sample_rate, n_mfcc=self.n_mfcc)
            return mfcc.T  # [time, features]
        except Exception as e:
            print(f'特徵提取失敗: {e}')
            return np.array([])
    
    def create_simple_model(self):
        \"\"\"創建簡單模型\"\"\"
        class SimpleModel(nn.Module):
            def __init__(self, input_dim=13, hidden_dim=128, output_dim=13):
                super().__init__()
                self.network = nn.Sequential(
                    nn.Linear(input_dim, hidden_dim),
                    nn.ReLU(),
                    nn.Dropout(0.1),
                    nn.Linear(hidden_dim, hidden_dim),
                    nn.ReLU(),
                    nn.Dropout(0.1),
                    nn.Linear(hidden_dim, output_dim)
                )
                
            def forward(self, x):
                return self.network(x)
                
        return SimpleModel()
    
    def prepare_training_data(self, training_data):
        \"\"\"準備訓練資料\"\"\"
        X = []
        y = []
        
        for data in training_data:
            audio = data['audio']
            features = self.extract_simple_features(audio)
            
            if len(features) == 0:
                continue
                
            # 創建滑動窗口
            window_size = 5
            step_size = 2
            
            for i in range(0, len(features) - window_size, step_size):
                window = features[i:i+window_size]
                if len(window) == window_size:
                    X.append(window.flatten())
                    y.append(features[i+window_size//2])
        
        if len(X) == 0:
            raise ValueError('沒有可用的訓練資料')
            
        X = np.array(X, dtype=np.float32)
        y = np.array(y, dtype=np.float32)
        
        print(f'準備訓練資料: X={X.shape}, y={y.shape}')
        return X, y
    
    def train_model(self, X, y):
        \"\"\"訓練模型\"\"\"
        print('開始訓練模型...')
        
        # 轉換為 PyTorch 張量
        X_tensor = torch.tensor(X, dtype=torch.float32)
        y_tensor = torch.tensor(y, dtype=torch.float32)
        
        # 創建模型
        model = self.create_simple_model()
        optimizer = optim.Adam(model.parameters(), lr=0.001)
        criterion = nn.MSELoss()
        
        # 訓練參數
        batch_size = 16
        num_epochs = 50
        
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
            
            if epoch % 10 == 0:
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
                }, os.path.join(self.model_path, '時穎白.pth'))
        
        print(f'訓練完成! 最佳損失: {best_loss:.6f}')
        return os.path.join(self.model_path, '時穎白.pth'), best_loss
    
    def run_training(self):
        \"\"\"執行訓練\"\"\"
        try:
            print('🚀 開始時穎白穩定訓練')
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
    trainer = StableShiyinbaiTrainer()
    result = trainer.run_training()
    
    # 輸出結果
    print('\\n' + '=' * 50)
    print('訓練結果:')
    print(json.dumps(result, ensure_ascii=False, indent=2))
";
}

function runStableTraining() {
    /**
     * 執行穩定訓練
     */
    global $training_data_path;
    
    try {
        // 創建訓練腳本
        $script_result = createStableTrainingScript();
        if (!$script_result['success']) {
            throw new Exception('無法創建訓練腳本');
        }
        
        // 執行訓練
        $cmd = sprintf('cd "%s" && python stable_train.py 2>&1', $training_data_path);
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
        $action = $input['action'] ?? 'stable_training';
        
        switch ($action) {
            case 'check_environment':
                $result = checkEnvironment();
                break;
                
            case 'install_dependencies':
                $result = installDependencies();
                break;
                
            case 'create_script':
                $result = createStableTrainingScript();
                break;
                
            case 'stable_training':
            default:
                $result = runStableTraining();
                break;
        }
        
        ob_clean();
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } elseif ($method === 'GET') {
        $response = [
            'success' => true,
            'system_info' => [
                'name' => '穩定訓練系統',
                'version' => '1.0',
                'description' => '修正訓練失敗問題的穩定訓練流程'
            ],
            'available_actions' => [
                'check_environment' => '檢查環境',
                'install_dependencies' => '安裝依賴',
                'create_script' => '創建腳本',
                'stable_training' => '穩定訓練'
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

