@echo off
chcp 65001 >nul
echo ========================================
echo XAMPP 載入問題快速修復工具
echo ========================================
echo.

:: 檢查是否以管理員身份運行
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ⚠️  警告：建議以管理員身份運行此腳本以獲得完整功能
    echo.
    pause
)

echo [步驟 1/6] 檢查端口占用情況...
echo.

echo 檢查端口 80 (HTTP)...
netstat -ano | findstr :80
if %errorLevel% equ 0 (
    echo ⚠️  端口 80 被占用
    echo 正在查找占用端口的程序...
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr :80 ^| findstr LISTENING') do (
        echo 找到 PID: %%a
        tasklist /FI "PID eq %%a" /FO LIST | findstr "Image Name"
    )
) else (
    echo ✅ 端口 80 未被占用
)

echo.
echo 檢查端口 3306 (MySQL)...
netstat -ano | findstr :3306
if %errorLevel% equ 0 (
    echo ✅ 端口 3306 正在使用（MySQL 可能正在運行）
) else (
    echo ⚠️  端口 3306 未被占用（MySQL 可能沒有運行）
)

echo.
echo [步驟 2/6] 檢查常見的端口占用程序...
echo.

:: 檢查 IIS
sc query W3SVC >nul 2>&1
if %errorLevel% equ 0 (
    sc query W3SVC | findstr "STATE" | findstr "RUNNING" >nul
    if %errorLevel% equ 0 (
        echo ⚠️  發現 IIS 正在運行（這會占用端口 80）
        echo 建議停止 IIS 服務
        echo 執行命令: net stop W3SVC
    )
)

:: 檢查 Skype
tasklist | findstr /i skype.exe >nul
if %errorLevel% equ 0 (
    echo ⚠️  發現 Skype 正在運行（可能占用端口 80）
    echo 建議關閉 Skype 或修改其端口設定
)

echo.
echo [步驟 3/6] 檢查 XAMPP 服務狀態...
echo.

:: 檢查 XAMPP 常見路徑
set XAMPP_PATH=
if exist "C:\xampp" (
    set XAMPP_PATH=C:\xampp
    echo ✅ 找到 XAMPP: C:\xampp
) else if exist "D:\xampp" (
    set XAMPP_PATH=D:\xampp
    echo ✅ 找到 XAMPP: D:\xampp
) else (
    echo ⚠️  未找到 XAMPP 安裝路徑
    echo 請手動輸入 XAMPP 路徑，或按 Enter 跳過此步驟
    set /p XAMPP_PATH="XAMPP 路徑: "
)

if defined XAMPP_PATH (
    echo.
    echo 檢查 Apache 配置文件...
    if exist "%XAMPP_PATH%\apache\conf\httpd.conf" (
        echo ✅ 找到 httpd.conf
        echo 檢查監聽端口...
        findstr /i "Listen" "%XAMPP_PATH%\apache\conf\httpd.conf" | findstr /v "^#"
    ) else (
        echo ⚠️  未找到 httpd.conf
    )
    
    echo.
    echo 檢查 MySQL 配置文件...
    if exist "%XAMPP_PATH%\mysql\bin\my.ini" (
        echo ✅ 找到 my.ini
    ) else if exist "%XAMPP_PATH%\mysql\my.ini" (
        echo ✅ 找到 my.ini
    ) else (
        echo ⚠️  未找到 my.ini
    )
)

echo.
echo [步驟 4/6] 檢查錯誤日誌...
echo.

if defined XAMPP_PATH (
    echo 檢查 Apache 錯誤日誌...
    if exist "%XAMPP_PATH%\apache\logs\error.log" (
        echo ✅ 找到錯誤日誌
        echo 顯示最近的錯誤（最後 10 行）...
        echo ----------------------------------------
        powershell -Command "Get-Content '%XAMPP_PATH%\apache\logs\error.log' -Tail 10"
        echo ----------------------------------------
    ) else (
        echo ⚠️  未找到錯誤日誌
    )
    
    echo.
    echo 檢查 MySQL 錯誤日誌...
    for %%f in ("%XAMPP_PATH%\mysql\data\*.err") do (
        echo ✅ 找到錯誤日誌: %%f
        echo 顯示最近的錯誤（最後 10 行）...
        echo ----------------------------------------
        powershell -Command "Get-Content '%%f' -Tail 10"
        echo ----------------------------------------
        goto :found_mysql_log
    )
    echo ⚠️  未找到 MySQL 錯誤日誌
    :found_mysql_log
)

echo.
echo [步驟 5/6] 提供修復建議...
echo.

echo ========================================
echo 修復建議
echo ========================================
echo.
echo 方法 1: 完全重新啟動 XAMPP
echo   1. 在 XAMPP Control Panel 中停止所有服務
echo   2. 等待 10 秒
echo   3. 重新啟動 Apache 和 MySQL
echo   4. 查看日誌確認啟動成功
echo.
echo 方法 2: 修改 Apache 端口（如果端口 80 被占用）
echo   1. 編輯: %XAMPP_PATH%\apache\conf\httpd.conf
echo   2. 找到 "Listen 80" 改為 "Listen 8080"
echo   3. 找到 "ServerName localhost:80" 改為 "ServerName localhost:8080"
echo   4. 保存並重新啟動 Apache
echo   5. 訪問: http://localhost:8080/phpmyadmin/
echo.
echo 方法 3: 停止占用端口的程序
echo   如果端口 80 被占用，執行以下命令停止 IIS:
echo   net stop W3SVC
echo.
echo 方法 4: 檢查防火牆
echo   1. 打開 Windows 防火牆設定
echo   2. 確保 Apache 和 MySQL 被允許通過防火牆
echo.
echo 方法 5: 清除瀏覽器快取
echo   1. 按 Ctrl+Shift+Delete
echo   2. 清除快取和 Cookie
echo   3. 重新載入頁面
echo.

echo [步驟 6/6] 快速測試連接...
echo.

echo 測試 HTTP 連接...
powershell -Command "$response = Invoke-WebRequest -Uri 'http://localhost' -TimeoutSec 3 -UseBasicParsing -ErrorAction SilentlyContinue; if ($response) { Write-Host '✅ HTTP 連接成功' -ForegroundColor Green } else { Write-Host '❌ HTTP 連接失敗' -ForegroundColor Red }" 2>nul
if %errorLevel% neq 0 (
    echo ❌ HTTP 連接失敗或超時
)

echo.
echo 測試 MySQL 連接...
powershell -Command "$connection = Test-NetConnection -ComputerName localhost -Port 3306 -WarningAction SilentlyContinue; if ($connection.TcpTestSucceeded) { Write-Host '✅ MySQL 端口可連接' -ForegroundColor Green } else { Write-Host '❌ MySQL 端口無法連接' -ForegroundColor Red }" 2>nul
if %errorLevel% neq 0 (
    echo ❌ MySQL 端口測試失敗
)

echo.
echo ========================================
echo 診斷完成
echo ========================================
echo.
echo 如果問題仍然存在，請：
echo 1. 查看上面的錯誤日誌內容
echo 2. 檢查 XAMPP Control Panel 中的日誌
echo 3. 嘗試以管理員身份運行 XAMPP Control Panel
echo 4. 檢查 Windows 事件檢視器中的錯誤
echo.
pause

