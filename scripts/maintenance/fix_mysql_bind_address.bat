@echo off
chcp 65001 >nul
echo ========================================
echo MySQL 網路綁定修復工具
echo ========================================
echo.
echo 此工具將修復 MySQL 只監聽 IPv6 的問題
echo 啟用 IPv4 綁定以允許 localhost 連接
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

if not exist "%CONFIG_FILE%" (
    echo ❌ 未找到配置文件: %CONFIG_FILE%
    pause
    exit /b 1
)

echo ✅ 找到配置文件: %CONFIG_FILE%
echo.

:: 備份配置文件
echo [步驟 1/3] 備份配置文件...
set BACKUP_FILE=%CONFIG_FILE%.backup_%date:~0,4%%date:~5,2%%date:~8,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set BACKUP_FILE=%BACKUP_FILE: =0%
copy "%CONFIG_FILE%" "%BACKUP_FILE%" >nul 2>&1
echo ✅ 已備份到: %BACKUP_FILE%
echo.

:: 檢查當前的 bind-address 設定
echo [步驟 2/3] 檢查當前配置...
echo.

findstr /i "bind-address" "%CONFIG_FILE%"
echo.

:: 修復 bind-address
echo [步驟 3/3] 修復 bind-address 配置...
echo.

:: 使用 PowerShell 來修復配置文件
powershell -Command ^
"$content = Get-Content '%CONFIG_FILE%' -Raw; " ^
"$inMysqld = $false; " ^
"$bindAdded = $false; " ^
"$lines = $content -split \"`r?`n\"; " ^
"$newLines = @(); " ^
"foreach ($line in $lines) { " ^
"  if ($line -match '^\s*\[mysqld\]') { $inMysqld = $true; $bindAdded = $false; } " ^
"  if ($line -match '^\s*\[.*\]' -and $line -notmatch '^\s*\[mysqld\]') { $inMysqld = $false; } " ^
"  if ($inMysqld -and $line -match 'bind-address' -and $line -notmatch '^\s*#') { " ^
"    $newLines += 'bind-address = 127.0.0.1'; " ^
"    $bindAdded = $true; " ^
"    continue; " ^
"  } " ^
"  if ($inMysqld -and -not $bindAdded -and $line -match '^\s*[^#\[]') { " ^
"    $newLines += 'bind-address = 127.0.0.1'; " ^
"    $bindAdded = $true; " ^
"  } " ^
"  $newLines += $line; " ^
"}; " ^
"$newContent = $newLines -join \"`r`n\"; " ^
"Set-Content '%CONFIG_FILE%' -Value $newContent -Encoding UTF8"

if %errorLevel% equ 0 (
    echo ✅ 已修復 bind-address 配置
    echo.
    echo 新的配置：
    findstr /i "bind-address" "%CONFIG_FILE%" | findstr /v "^#"
) else (
    echo ⚠️  自動修復失敗，請手動編輯配置文件
    echo.
    echo 請在 [mysqld] 區段添加或修改：
    echo bind-address = 127.0.0.1
    echo.
    echo 確保這一行沒有被註釋（前面沒有 #）
)

echo.
echo ========================================
echo 修復完成
echo ========================================
echo.
echo 下一步操作：
echo 1. 在 XAMPP Control Panel 中重新啟動 MySQL
echo 2. 檢查 MySQL 是否正常啟動
echo 3. 如果仍有問題，查看錯誤日誌: %XAMPP_PATH%\mysql\data\mysql_error.log
echo.
pause








