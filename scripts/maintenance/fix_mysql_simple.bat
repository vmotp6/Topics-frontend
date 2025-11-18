@echo off
chcp 65001 >nul
echo ========================================
echo MySQL 簡單修復工具
echo ========================================
echo.

:: 檢查管理員權限
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [錯誤] 請以管理員身份運行此腳本！
    pause
    exit /b 1
)

set XAMPP_PATH=C:\xampp
set CONFIG_FILE=%XAMPP_PATH%\mysql\bin\my.ini

echo [1/4] 停止 MySQL 進程...
taskkill /F /IM mysqld.exe >nul 2>&1
timeout /t 2 /nobreak >nul
echo 完成
echo.

echo [2/4] 備份配置文件...
if exist "%CONFIG_FILE%" (
    set BACKUP_FILE=%CONFIG_FILE%.backup
    copy "%CONFIG_FILE%" "%BACKUP_FILE%" >nul 2>&1
    echo 已備份
) else (
    echo [錯誤] 未找到配置文件
    pause
    exit /b 1
)
echo.

echo [3/4] 檢查 bind-address 配置...
findstr /i /c:"bind-address" "%CONFIG_FILE%" | findstr /v /c:"#" >nul 2>&1
if %errorLevel% neq 0 (
    echo 未找到 bind-address，需要手動添加
    echo.
    echo 請按照以下步驟操作：
    echo 1. 打開文件: %CONFIG_FILE%
    echo 2. 找到 [mysqld] 區段
    echo 3. 在該區段中添加: bind-address = 127.0.0.1
    echo 4. 保存文件
    echo.
    echo 按任意鍵打開配置文件...
    pause >nul
    notepad "%CONFIG_FILE%"
    echo.
    echo 已保存配置文件？(Y/N)
    set /p SAVED=
    if /i not "%SAVED%"=="Y" (
        echo 取消修復
        pause
        exit /b 1
    )
) else (
    echo 已找到 bind-address 配置
    findstr /i /c:"bind-address" "%CONFIG_FILE%" | findstr /v /c:"#"
)
echo.

echo [4/4] 啟動 MySQL...
cd /d "%XAMPP_PATH%\mysql\bin"
start /B mysqld.exe --defaults-file="%CONFIG_FILE%"
timeout /t 5 /nobreak >nul

netstat -ano | findstr :3306 >nul 2>&1
if %errorLevel% equ 0 (
    echo.
    echo [成功] MySQL 已啟動！
    echo 端口 3306 正在監聽
    echo.
    echo 請在 XAMPP Control Panel 中確認 MySQL 狀態
) else (
    echo.
    echo [失敗] MySQL 啟動失敗
    echo.
    echo 請查看錯誤日誌: %XAMPP_PATH%\mysql\data\mysql_error.log
    echo.
    echo 建議：
    echo 1. 檢查配置文件語法
    echo 2. 在 XAMPP Control Panel 中手動啟動 MySQL
    echo 3. 查看 Logs 按鈕中的錯誤訊息
)

echo.
pause



