@echo off
chcp 65001 >nul
echo ========================================
echo XAMPP MySQL 意外關閉修復工具
echo ========================================
echo.
echo 此工具將診斷並修復 MySQL 意外關閉的問題
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

:: 步驟 1: 檢查並停止所有 MySQL 進程
echo [步驟 1/7] 檢查並清理 MySQL 進程...
echo.

tasklist | findstr /i "mysqld.exe" >nul 2>&1
if %errorLevel% equ 0 (
    echo ⚠️  發現正在運行的 MySQL 進程，正在停止...
    taskkill /F /IM mysqld.exe >nul 2>&1
    timeout /t 2 /nobreak >nul
    echo ✅ MySQL 進程已停止
) else (
    echo ✅ 沒有發現運行的 MySQL 進程
)
echo.

:: 步驟 2: 檢查端口 3306 是否被占用
echo [步驟 2/7] 檢查端口 3306 占用情況...
echo.

netstat -ano | findstr :3306 >nul 2>&1
if %errorLevel% equ 0 (
    echo ⚠️  端口 3306 被占用！
    echo.
    echo 占用端口的進程：
    netstat -ano | findstr :3306
    echo.
    echo 請手動停止占用端口的程序，或按任意鍵繼續嘗試修復...
    pause >nul
) else (
    echo ✅ 端口 3306 未被占用
)
echo.

:: 步驟 3: 檢查 XAMPP MySQL 目錄
echo [步驟 3/7] 檢查 XAMPP MySQL 安裝...
echo.

set XAMPP_PATH=
if exist "C:\xampp\mysql\bin\mysqld.exe" (
    set XAMPP_PATH=C:\xampp
    echo ✅ 找到 XAMPP 安裝: C:\xampp
) else if exist "D:\xampp\mysql\bin\mysqld.exe" (
    set XAMPP_PATH=D:\xampp
    echo ✅ 找到 XAMPP 安裝: D:\xampp
) else (
    echo ❌ 未找到 XAMPP 安裝
    echo 請確認 XAMPP 安裝路徑
    pause
    exit /b 1
)
echo.

:: 步驟 4: 檢查 MySQL 數據目錄
echo [步驟 4/7] 檢查 MySQL 數據目錄...
echo.

if exist "%XAMPP_PATH%\mysql\data" (
    echo ✅ 找到數據目錄: %XAMPP_PATH%\mysql\data
    
    :: 檢查是否有錯誤日誌
    if exist "%XAMPP_PATH%\mysql\data\*.err" (
        echo ⚠️  發現錯誤日誌文件
        echo 最近的錯誤日誌：
        dir /O-D "%XAMPP_PATH%\mysql\data\*.err" /B 2>nul | findstr /V "Directory" | head -n 1
        echo.
        echo 建議查看錯誤日誌以了解關閉原因
    )
) else (
    echo ❌ 未找到數據目錄
)
echo.

:: 步驟 5: 檢查磁碟空間
echo [步驟 5/7] 檢查磁碟空間...
echo.

for /f "tokens=3" %%a in ('dir /-c "%XAMPP_PATH%" ^| findstr /i "bytes free"') do set FREE_SPACE=%%a
echo 磁碟可用空間檢查完成
echo.

:: 步驟 6: 檢查 MySQL 配置文件
echo [步驟 6/7] 檢查 MySQL 配置文件...
echo.

set CONFIG_FILE=%XAMPP_PATH%\mysql\bin\my.ini
if exist "%CONFIG_FILE%" (
    echo ✅ 找到配置文件: %CONFIG_FILE%
    
    :: 檢查端口配置
    findstr /i "port" "%CONFIG_FILE%" | findstr /v "^#" | findstr /v "^;" >nul 2>&1
    if %errorLevel% equ 0 (
        echo 端口配置：
        findstr /i "port" "%CONFIG_FILE%" | findstr /v "^#" | findstr /v "^;"
    )
) else (
    echo ⚠️  未找到配置文件 my.ini
    echo 可能使用默認配置
)
echo.

:: 步驟 7: 嘗試修復並啟動 MySQL
echo [步驟 7/7] 嘗試修復並啟動 MySQL...
echo.

:: 檢查是否有 ibdata1 文件鎖定問題
if exist "%XAMPP_PATH%\mysql\data\ibdata1" (
    echo 檢查數據文件...
    
    :: 嘗試刪除臨時文件
    if exist "%XAMPP_PATH%\mysql\data\*.tmp" (
        echo 清理臨時文件...
        del /F /Q "%XAMPP_PATH%\mysql\data\*.tmp" >nul 2>&1
    )
)

:: 嘗試啟動 MySQL
echo.
echo 正在嘗試啟動 MySQL...
echo.

cd /d "%XAMPP_PATH%\mysql\bin"

:: 使用 XAMPP 的方式啟動 MySQL（不作為服務）
echo 方法 1: 嘗試直接啟動 MySQL...
start /B mysqld.exe --defaults-file="%XAMPP_PATH%\mysql\bin\my.ini" --standalone --console >nul 2>&1

timeout /t 5 /nobreak >nul

:: 檢查是否啟動成功
netstat -ano | findstr :3306 >nul 2>&1
if %errorLevel% equ 0 (
    echo ✅ MySQL 啟動成功！
    echo.
    echo 請在 XAMPP Control Panel 中檢查 MySQL 狀態
) else (
    echo ❌ MySQL 啟動失敗
    echo.
    echo 可能的解決方法：
    echo 1. 檢查錯誤日誌: %XAMPP_PATH%\mysql\data\*.err
    echo 2. 檢查配置文件: %CONFIG_FILE%
    echo 3. 檢查端口是否被占用
    echo 4. 檢查數據目錄權限
    echo 5. 嘗試在 XAMPP Control Panel 中手動啟動
    echo.
    echo 如果問題持續，請查看詳細的錯誤日誌
)

echo.
echo ========================================
echo 修復完成
echo ========================================
echo.
echo 建議後續操作：
echo 1. 在 XAMPP Control Panel 中檢查 MySQL 狀態
echo 2. 如果仍有問題，查看錯誤日誌: %XAMPP_PATH%\mysql\data\*.err
echo 3. 檢查 Windows 事件檢視器中的錯誤訊息
echo.
pause



