@echo off
chcp 65001 >nul
echo ========================================
echo MySQL 完整修復工具
echo ========================================
echo.
echo 此工具將進行完整的診斷和修復
echo.

:: 檢查是否以管理員身份運行
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ❌ 請以管理員身份運行此腳本！
    echo 右鍵點擊此文件，選擇「以系統管理員身份執行」
    pause
    exit /b 1
)

echo ✅ 已確認管理員權限
echo.

set XAMPP_PATH=C:\xampp
if not exist "%XAMPP_PATH%\mysql\bin\mysqld.exe" (
    echo ❌ 未找到 XAMPP MySQL 安裝
    pause
    exit /b 1
)

echo ✅ 找到 XAMPP: %XAMPP_PATH%
echo.

:: 步驟 1: 停止所有 MySQL 進程
echo [步驟 1/8] 停止所有 MySQL 進程...
tasklist | findstr /i "mysqld.exe" >nul 2>&1
if %errorLevel% equ 0 (
    echo 正在停止 MySQL 進程...
    taskkill /F /IM mysqld.exe >nul 2>&1
    timeout /t 3 /nobreak >nul
    echo ✅ MySQL 進程已停止
) else (
    echo ✅ 沒有運行的 MySQL 進程
)
echo.

:: 步驟 2: 檢查端口占用
echo [步驟 2/8] 檢查端口 3306...
netstat -ano | findstr :3306 >nul 2>&1
if %errorLevel% equ 0 (
    echo ⚠️  端口 3306 被占用！
    echo.
    echo 占用端口的進程：
    netstat -ano | findstr :3306
    echo.
    echo 正在嘗試停止占用端口的進程...
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr :3306') do (
        echo 停止進程 PID: %%a
        taskkill /F /PID %%a >nul 2>&1
    )
    timeout /t 2 /nobreak >nul
    echo ✅ 已嘗試停止占用端口的進程
) else (
    echo ✅ 端口 3306 未被占用
)
echo.

:: 步驟 3: 檢查數據目錄
echo [步驟 3/8] 檢查數據目錄...
set DATA_DIR=%XAMPP_PATH%\mysql\data
if exist "%DATA_DIR%" (
    echo ✅ 數據目錄存在: %DATA_DIR%
    
    :: 檢查必要的文件
    if exist "%DATA_DIR%\ibdata1" (
        echo ✅ 找到 ibdata1 文件
    ) else (
        echo ⚠️  未找到 ibdata1 文件（可能需要初始化）
    )
    
    if exist "%DATA_DIR%\mysql" (
        echo ✅ 找到 mysql 系統資料庫目錄
    ) else (
        echo ⚠️  未找到 mysql 系統資料庫（可能需要初始化）
    )
) else (
    echo ❌ 數據目錄不存在！
    echo 正在創建數據目錄...
    mkdir "%DATA_DIR%" >nul 2>&1
    if exist "%DATA_DIR%" (
        echo ✅ 已創建數據目錄
    ) else (
        echo ❌ 無法創建數據目錄，請檢查權限
    )
)
echo.

:: 步驟 4: 檢查並清理臨時文件
echo [步驟 4/8] 清理臨時文件...
if exist "%DATA_DIR%\*.tmp" (
    echo 正在清理臨時文件...
    del /F /Q "%DATA_DIR%\*.tmp" >nul 2>&1
    echo ✅ 臨時文件已清理
) else (
    echo ✅ 沒有臨時文件需要清理
)
echo.

:: 步驟 5: 檢查配置文件
echo [步驟 5/8] 檢查配置文件...
set CONFIG_FILE=%XAMPP_PATH%\mysql\bin\my.ini
if exist "%CONFIG_FILE%" (
    echo ✅ 找到配置文件: %CONFIG_FILE%
    
    :: 檢查數據目錄配置
    findstr /i "datadir" "%CONFIG_FILE%" | findstr /v "^#" | findstr /v "^;" >nul 2>&1
    if %errorLevel% neq 0 (
        echo ⚠️  配置文件中未找到 datadir 設定
    )
) else (
    echo ⚠️  未找到配置文件 my.ini
)
echo.

:: 步驟 6: 嘗試手動啟動 MySQL 並捕獲錯誤
echo [步驟 6/8] 嘗試啟動 MySQL 並查看錯誤...
echo.

cd /d "%XAMPP_PATH%\mysql\bin"

:: 創建臨時錯誤日誌文件
set ERROR_LOG=%TEMP%\mysql_startup_error.log
echo 正在嘗試啟動 MySQL...
echo 錯誤日誌將保存到: %ERROR_LOG%
echo.

:: 嘗試啟動 MySQL（在背景運行並捕獲錯誤）
start /B /MIN "" mysqld.exe --defaults-file="%CONFIG_FILE%" --console > "%ERROR_LOG%" 2>&1

:: 等待 MySQL 啟動
echo 等待 MySQL 啟動（最多 10 秒）...
timeout /t 10 /nobreak >nul

:: 檢查是否啟動成功
netstat -ano | findstr :3306 >nul 2>&1
if %errorLevel% equ 0 (
    echo ✅ MySQL 啟動成功！
    echo.
    echo 端口 3306 正在監聽
    echo.
    echo 請在 XAMPP Control Panel 中檢查 MySQL 狀態
) else (
    echo ❌ MySQL 啟動失敗
    echo.
    echo 正在查看錯誤訊息...
    echo.
    if exist "%ERROR_LOG%" (
        echo ========================================
        echo 錯誤日誌內容：
        echo ========================================
        type "%ERROR_LOG%"
        echo.
        echo ========================================
    ) else (
        echo ⚠️  未找到錯誤日誌文件
    )
    
    :: 檢查數據目錄中的錯誤日誌
    echo.
    echo 檢查數據目錄中的錯誤日誌...
    if exist "%DATA_DIR%\*.err" (
        echo 找到錯誤日誌文件：
        for %%f in ("%DATA_DIR%\*.err") do (
            echo.
            echo 文件: %%~nxf
            echo 最後 30 行：
            powershell -Command "Get-Content '%%f' -Tail 30"
        )
    ) else (
        echo ⚠️  數據目錄中沒有錯誤日誌文件
        echo.
        echo 這可能表示：
        echo 1. MySQL 無法寫入數據目錄
        echo 2. 數據目錄權限問題
        echo 3. MySQL 初始化失敗
    )
)
echo.

:: 步驟 7: 檢查數據目錄權限
echo [步驟 7/8] 檢查數據目錄權限...
echo.
echo 正在檢查數據目錄是否可寫入...
echo test > "%DATA_DIR%\test_write.tmp" 2>nul
if exist "%DATA_DIR%\test_write.tmp" (
    del "%DATA_DIR%\test_write.tmp" >nul 2>&1
    echo ✅ 數據目錄可寫入
) else (
    echo ❌ 數據目錄無法寫入！
    echo.
    echo 請手動檢查數據目錄權限：
    echo 1. 右鍵點擊: %DATA_DIR%
    echo 2. 選擇「內容」→「安全性」
    echo 3. 確保「Users」或「Everyone」有「完全控制」權限
)
echo.

:: 步驟 8: 提供修復建議
echo [步驟 8/8] 修復建議
echo.
echo ========================================
echo 如果 MySQL 仍未啟動，請嘗試以下方法：
echo ========================================
echo.
echo 方法 1: 在 XAMPP Control Panel 中啟動
echo   1. 打開 XAMPP Control Panel（以管理員身份）
echo   2. 點擊 MySQL 的 Start 按鈕
echo   3. 點擊 Logs 按鈕查看錯誤訊息
echo.
echo 方法 2: 檢查並修復數據目錄
echo   如果數據目錄損壞或缺少文件，可能需要：
echo   1. 備份現有數據: %DATA_DIR%
echo   2. 重新初始化 MySQL 數據目錄
echo.
echo 方法 3: 檢查其他 MySQL 服務
echo   執行以下命令檢查是否有其他 MySQL 服務：
echo   sc query type= service state= all ^| findstr /i mysql
echo.
echo 方法 4: 查看 Windows 事件檢視器
echo   按 Win + R，輸入 eventvwr.msc
echo   查看「應用程式」和「系統」日誌
echo.
echo ========================================
echo 修復完成
echo ========================================
echo.

if exist "%ERROR_LOG%" (
    echo 臨時錯誤日誌已保存到: %ERROR_LOG%
    echo 您可以查看此文件以獲取更多資訊
    echo.
)

pause





