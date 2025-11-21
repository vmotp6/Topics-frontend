@echo off
chcp 65001 >nul
echo ========================================
echo MySQL 完整診斷工具
echo ========================================
echo.

echo [1/5] 檢查常見的 MySQL 服務...
echo.

set FOUND_SERVICE=0

:: 檢查 MySQL80
sc query MySQL80 >nul 2>&1
if %errorLevel% equ 0 (
    echo ✅ 找到服務: MySQL80
    sc query MySQL80 | findstr "STATE"
    set FOUND_SERVICE=1
    set MYSQL_SERVICE=MySQL80
)

:: 檢查 MySQL
sc query MySQL >nul 2>&1
if %errorLevel% equ 0 (
    echo ✅ 找到服務: MySQL
    sc query MySQL | findstr "STATE"
    set FOUND_SERVICE=1
    if not defined MYSQL_SERVICE set MYSQL_SERVICE=MySQL
)

:: 檢查 MySQL57
sc query MySQL57 >nul 2>&1
if %errorLevel% equ 0 (
    echo ✅ 找到服務: MySQL57
    sc query MySQL57 | findstr "STATE"
    set FOUND_SERVICE=1
    if not defined MYSQL_SERVICE set MYSQL_SERVICE=MySQL57
)

if %FOUND_SERVICE% equ 0 (
    echo ❌ 未找到任何 MySQL 服務
    echo.
    echo 這可能表示：
    echo 1. MySQL 尚未安裝
    echo 2. MySQL 服務未正確註冊
    echo 3. 使用了不同的服務名稱
    echo.
    echo 請檢查所有服務：
    sc query type= service | findstr /i mysql
    echo.
) else (
    echo.
    echo 找到的服務名稱: %MYSQL_SERVICE%
)

echo.
echo [2/5] 檢查端口 3306...
netstat -ano | findstr :3306
if %errorLevel% neq 0 (
    echo ⚠️  端口 3306 沒有被占用（MySQL 可能沒有運行）
) else (
    echo ✅ 端口 3306 正在使用中
)

echo.
echo [3/5] 檢查常見的 MySQL 安裝路徑...
if exist "C:\Program Files\MySQL" (
    echo ✅ 找到: C:\Program Files\MySQL
    dir "C:\Program Files\MySQL" /b
) else (
    echo ⚠️  未找到: C:\Program Files\MySQL
)

if exist "C:\xampp\mysql" (
    echo ✅ 找到: C:\xampp\mysql
) else (
    echo ⚠️  未找到: C:\xampp\mysql
)

if exist "C:\wamp64\bin\mysql" (
    echo ✅ 找到: C:\wamp64\bin\mysql
) else (
    echo ⚠️  未找到: C:\wamp64\bin\mysql
)

echo.
echo [4/5] 檢查 MySQL 配置文件...
set CONFIG_FOUND=0

if exist "C:\Program Files\MySQL\MySQL Server 8.0\my.ini" (
    echo ✅ 找到: C:\Program Files\MySQL\MySQL Server 8.0\my.ini
    set CONFIG_FOUND=1
)

if exist "C:\ProgramData\MySQL\MySQL Server 8.0\my.ini" (
    echo ✅ 找到: C:\ProgramData\MySQL\MySQL Server 8.0\my.ini
    set CONFIG_FOUND=1
)

if exist "C:\xampp\mysql\bin\my.ini" (
    echo ✅ 找到: C:\xampp\mysql\bin\my.ini
    set CONFIG_FOUND=1
)

if %CONFIG_FOUND% equ 0 (
    echo ⚠️  未找到常見的配置文件
)

echo.
echo [5/5] 檢查 MySQL 錯誤日誌...
set LOG_FOUND=0

if exist "C:\ProgramData\MySQL\MySQL Server 8.0\Data\*.err" (
    echo ✅ 找到錯誤日誌: C:\ProgramData\MySQL\MySQL Server 8.0\Data\
    set LOG_FOUND=1
)

if exist "C:\xampp\mysql\data\*.err" (
    echo ✅ 找到錯誤日誌: C:\xampp\mysql\data\
    set LOG_FOUND=1
)

if %LOG_FOUND% equ 0 (
    echo ⚠️  未找到錯誤日誌文件
)

echo.
echo ========================================
echo 診斷完成
echo ========================================
echo.

if %FOUND_SERVICE% equ 1 (
    echo 建議操作：
    echo 1. 如果服務狀態不是 RUNNING，請執行：
    echo    net start %MYSQL_SERVICE%
    echo.
    echo 2. 如果服務無法啟動，請檢查錯誤日誌
    echo.
) else (
    echo 建議操作：
    echo 1. 確認 MySQL 是否已安裝
    echo 2. 如果已安裝但服務未註冊，請重新安裝服務
    echo 3. 查看安裝文檔或聯繫系統管理員
    echo.
)

pause







