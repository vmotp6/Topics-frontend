@echo off
chcp 65001 >nul
echo ========================================
echo MySQL 快速修復工具
echo ========================================
echo.
echo 根據診斷結果，發現以下問題：
echo 1. 配置文件可能有語法錯誤
echo 2. MySQL 可能只監聽 IPv6
echo 3. 需要檢查並修復配置
echo.

:: 檢查管理員權限
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ❌ 請以管理員身份運行此腳本！
    pause
    exit /b 1
)

set XAMPP_PATH=C:\xampp
set CONFIG_FILE=%XAMPP_PATH%\mysql\bin\my.ini

echo [步驟 1/3] 停止所有 MySQL 進程...
taskkill /F /IM mysqld.exe >nul 2>&1
timeout /t 2 /nobreak >nul
echo ✅ 已停止 MySQL 進程
echo.

echo [步驟 2/3] 檢查並修復配置文件...
echo.

if not exist "%CONFIG_FILE%" (
    echo ❌ 未找到配置文件
    pause
    exit /b 1
)

:: 備份配置文件
set BACKUP_FILE=%CONFIG_FILE%.backup_%date:~0,4%%date:~5,2%%date:~8,2%
copy "%CONFIG_FILE%" "%BACKUP_FILE%" >nul 2>&1
echo ✅ 配置文件已備份

:: 檢查是否有問題字符
findstr /i /c:"```" "%CONFIG_FILE%" >nul 2>&1
if %errorLevel% equ 0 (
    echo ⚠️  發現配置文件中有問題字符
    echo 建議手動檢查配置文件: %CONFIG_FILE%
    echo.
    echo 請打開配置文件，查找並移除包含 ``` 的行
    echo.
)

:: 檢查 bind-address
findstr /i "bind-address" "%CONFIG_FILE%" >nul 2>&1
if %errorLevel% neq 0 (
    echo ⚠️  未找到 bind-address 配置
    echo MySQL 可能只監聽 IPv6，導致無法連接
    echo.
    echo 建議在 [mysqld] 區段添加：
    echo bind-address = 127.0.0.1
    echo.
) else (
    echo ✅ 找到 bind-address 配置
    findstr /i "bind-address" "%CONFIG_FILE%"
)
echo.

echo [步驟 3/3] 嘗試啟動 MySQL...
echo.

cd /d "%XAMPP_PATH%\mysql\bin"

:: 嘗試啟動 MySQL 並查看輸出
echo 正在啟動 MySQL（請查看是否有錯誤訊息）...
echo.

start /WAIT /MIN "" mysqld.exe --defaults-file="%CONFIG_FILE%" --console

timeout /t 3 /nobreak >nul

:: 檢查是否啟動成功
netstat -ano | findstr :3306 >nul 2>&1
if %errorLevel% equ 0 (
    echo.
    echo ✅ MySQL 啟動成功！
    echo 端口 3306 正在監聽
    echo.
    echo 請在 XAMPP Control Panel 中檢查 MySQL 狀態
) else (
    echo.
    echo ❌ MySQL 啟動失敗
    echo.
    echo 請查看錯誤日誌: %XAMPP_PATH%\mysql\data\mysql_error.log
    echo.
    echo 常見問題解決方法：
    echo 1. 檢查配置文件語法錯誤
    echo 2. 確保數據目錄權限正確
    echo 3. 檢查端口是否被占用
    echo 4. 查看詳細錯誤日誌
)

echo.
echo ========================================
echo 修復完成
echo ========================================
echo.
pause





