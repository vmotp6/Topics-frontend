@echo off
chcp 65001 >nul
echo ========================================
echo MySQL 啟動修復腳本
echo ========================================
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

:: 嘗試查找 MySQL 服務名稱
echo 正在查找 MySQL 服務...
echo.

:: 常見的 MySQL 服務名稱
set MYSQL_SERVICE=

:: 檢查 MySQL80
sc query MySQL80 >nul 2>&1
if %errorLevel% equ 0 (
    set MYSQL_SERVICE=MySQL80
    echo ✅ 找到服務: MySQL80
    goto :start_service
)

:: 檢查 MySQL
sc query MySQL >nul 2>&1
if %errorLevel% equ 0 (
    set MYSQL_SERVICE=MySQL
    echo ✅ 找到服務: MySQL
    goto :start_service
)

:: 檢查 MySQL57
sc query MySQL57 >nul 2>&1
if %errorLevel% equ 0 (
    set MYSQL_SERVICE=MySQL57
    echo ✅ 找到服務: MySQL57
    goto :start_service
)

echo ❌ 未找到 MySQL 服務
echo.
echo 請手動檢查服務名稱：
echo 1. 按 Win + R，輸入 services.msc
echo 2. 查找名稱包含 "MySQL" 的服務
echo 3. 記下服務名稱，然後手動執行：
echo    net start [服務名稱]
echo.
pause
exit /b 1

:start_service
echo.
echo 正在啟動 MySQL 服務: %MYSQL_SERVICE%
echo.

net start %MYSQL_SERVICE%

if %errorLevel% equ 0 (
    echo.
    echo ✅ MySQL 服務啟動成功！
    echo.
    echo 正在驗證連接...
    timeout /t 3 /nobreak >nul
    
    :: 簡單測試連接（需要 PHP 或 MySQL 客戶端）
    echo 請運行 check_mysql_status.php 來驗證連接
) else (
    echo.
    echo ❌ MySQL 服務啟動失敗
    echo.
    echo 可能的原因：
    echo 1. 服務配置錯誤
    echo 2. 端口被占用
    echo 3. 數據目錄損壞
    echo 4. 配置文件錯誤
    echo.
    echo 請檢查 MySQL 錯誤日誌：
    echo - C:\ProgramData\MySQL\MySQL Server 8.0\Data\*.err
    echo - 或您的 MySQL 安裝目錄下的 data 資料夾
    echo.
)

echo.
pause









