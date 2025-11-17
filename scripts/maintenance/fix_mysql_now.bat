@echo off
chcp 65001 >nul
echo ========================================
echo MySQL 立即修復工具
echo ========================================
echo.
echo 根據診斷結果，將執行以下修復：
echo 1. 停止所有 MySQL 進程
echo 2. 修復 bind-address 配置（啟用 IPv4）
echo 3. 重新啟動 MySQL
echo.

:: 檢查管理員權限
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ❌ 請以管理員身份運行此腳本！
    echo 右鍵點擊此文件，選擇「以系統管理員身份執行」
    pause
    exit /b 1
)

set XAMPP_PATH=C:\xampp
set CONFIG_FILE=%XAMPP_PATH%\mysql\bin\my.ini

:: 步驟 1: 停止 MySQL
echo [步驟 1/4] 停止所有 MySQL 進程...
taskkill /F /IM mysqld.exe >nul 2>&1
timeout /t 2 /nobreak >nul
echo ✅ MySQL 進程已停止
echo.

:: 步驟 2: 備份配置文件
echo [步驟 2/4] 備份配置文件...
if exist "%CONFIG_FILE%" (
    set BACKUP_FILE=%CONFIG_FILE%.backup_%date:~0,4%%date:~5,2%%date:~8,2%
    copy "%CONFIG_FILE%" "%BACKUP_FILE%" >nul 2>&1
    echo ✅ 已備份到: %BACKUP_FILE%
) else (
    echo ❌ 未找到配置文件
    pause
    exit /b 1
)
echo.

:: 步驟 3: 修復 bind-address
echo [步驟 3/4] 修復 bind-address 配置...
echo.

:: 檢查是否已有 bind-address（未註釋的）
findstr /i /v "^#" "%CONFIG_FILE%" | findstr /i "bind-address" >nul 2>&1
if %errorLevel% equ 0 (
    echo ✅ 已找到 bind-address 配置
    findstr /i /v "^#" "%CONFIG_FILE%" | findstr /i "bind-address"
    echo.
    echo 是否要修改為 127.0.0.1？(Y/N)
    set /p MODIFY_BIND=
    if /i not "%MODIFY_BIND%"=="Y" (
        echo 跳過 bind-address 修復
        goto :start_mysql
    )
)

:: 使用 PowerShell 添加 bind-address
echo 正在添加 bind-address = 127.0.0.1 到 [mysqld] 區段...
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0fix_bind_address.ps1" "%CONFIG_FILE%"

if %errorLevel% equ 0 (
    echo ✅ bind-address 配置已添加
    echo.
    echo 驗證配置：
    findstr /i "bind-address" "%CONFIG_FILE%" | findstr /v "^#"
) else (
    echo ⚠️  自動修復失敗
    echo.
    echo 請手動編輯配置文件: %CONFIG_FILE%
    echo 在 [mysqld] 區段添加：bind-address = 127.0.0.1
    echo.
    pause
)
echo.

:start_mysql
:: 步驟 4: 啟動 MySQL
echo [步驟 4/4] 啟動 MySQL...
echo.

cd /d "%XAMPP_PATH%\mysql\bin"

echo 正在啟動 MySQL...
start /B mysqld.exe --defaults-file="%CONFIG_FILE%"

timeout /t 5 /nobreak >nul

:: 檢查是否啟動成功
netstat -ano | findstr :3306 >nul 2>&1
if %errorLevel% equ 0 (
    echo.
    echo ✅✅✅ MySQL 啟動成功！✅✅✅
    echo.
    echo 端口 3306 正在監聽
    echo.
    echo 請在 XAMPP Control Panel 中確認 MySQL 狀態為綠色
) else (
    echo.
    echo ❌ MySQL 啟動失敗
    echo.
    echo 請查看錯誤日誌: %XAMPP_PATH%\mysql\data\mysql_error.log
    echo.
    echo 最後 20 行錯誤：
    powershell -Command "if (Test-Path '%XAMPP_PATH%\mysql\data\mysql_error.log') { Get-Content '%XAMPP_PATH%\mysql\data\mysql_error.log' -Tail 20 }"
)

echo.
echo ========================================
echo 修復完成
echo ========================================
echo.
pause

