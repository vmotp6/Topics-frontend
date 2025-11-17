@echo off
chcp 65001 >nul
title XAMPP 快速修復工具
color 0A

echo.
echo ========================================
echo    XAMPP 載入問題 - 快速修復工具
echo ========================================
echo.
echo 此工具將嘗試修復常見的 XAMPP 載入問題
echo.

:: 檢查管理員權限
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [警告] 建議以管理員身份運行以獲得完整功能
    echo 按任意鍵繼續，或關閉視窗後右鍵選擇「以系統管理員身份執行」
    pause >nul
)

echo [1/5] 停止可能衝突的服務...
echo.

:: 停止 IIS（如果正在運行）
sc query W3SVC >nul 2>&1
if %errorLevel% equ 0 (
    sc query W3SVC | findstr "RUNNING" >nul
    if %errorLevel% equ 0 (
        echo 發現 IIS 正在運行，正在停止...
        net stop W3SVC /y >nul 2>&1
        if %errorLevel% equ 0 (
            echo ✅ IIS 已停止
        ) else (
            echo ⚠️  無法停止 IIS（可能需要管理員權限）
        )
    )
)

echo.
echo [2/5] 檢查端口占用...
echo.

:: 檢查端口 80
echo 檢查端口 80...
for /f "tokens=5" %%a in ('netstat -ano ^| findstr :80 ^| findstr LISTENING') do (
    echo ⚠️  端口 80 被 PID %%a 占用
    tasklist /FI "PID eq %%a" /FO CSV | findstr /v "PID" >nul
    if %errorLevel% equ 0 (
        for /f "tokens=1 delims=," %%b in ('tasklist /FI "PID eq %%a" /FO CSV ^| findstr /v "PID"') do (
            echo    程序: %%b
        )
    )
)

:: 檢查端口 3306
echo 檢查端口 3306...
netstat -ano | findstr :3306 | findstr LISTENING >nul
if %errorLevel% equ 0 (
    echo ✅ 端口 3306 正在使用（MySQL 可能正在運行）
) else (
    echo ⚠️  端口 3306 未被占用
)

echo.
echo [3/5] 查找 XAMPP 安裝路徑...
echo.

set XAMPP_PATH=
if exist "C:\xampp" (
    set XAMPP_PATH=C:\xampp
    echo ✅ 找到 XAMPP: C:\xampp
) else if exist "D:\xampp" (
    set XAMPP_PATH=D:\xampp
    echo ✅ 找到 XAMPP: D:\xampp
) else (
    echo ⚠️  未自動找到 XAMPP
    echo 請手動輸入 XAMPP 安裝路徑（例如: C:\xampp）
    set /p XAMPP_PATH="XAMPP 路徑: "
    if not exist "!XAMPP_PATH!" (
        echo ❌ 路徑不存在，跳過後續步驟
        goto :end
    )
)

echo.
echo [4/5] 檢查並顯示錯誤日誌...
echo.

if defined XAMPP_PATH (
    echo --- Apache 錯誤日誌（最後 5 行）---
    if exist "%XAMPP_PATH%\apache\logs\error.log" (
        powershell -Command "Get-Content '%XAMPP_PATH%\apache\logs\error.log' -Tail 5 -ErrorAction SilentlyContinue"
    ) else (
        echo ⚠️  未找到 Apache 錯誤日誌
    )
    
    echo.
    echo --- MySQL 錯誤日誌（最後 5 行）---
    for %%f in ("%XAMPP_PATH%\mysql\data\*.err") do (
        powershell -Command "Get-Content '%%f' -Tail 5 -ErrorAction SilentlyContinue"
        goto :found_mysql_log
    )
    echo ⚠️  未找到 MySQL 錯誤日誌
    :found_mysql_log
)

echo.
echo [5/5] 提供解決方案...
echo.
echo ========================================
echo 立即嘗試的解決方法：
echo ========================================
echo.
echo 【方法 1】完全重啟 XAMPP（最有效）
echo   1. 關閉 XAMPP Control Panel
echo   2. 打開工作管理員（Ctrl+Shift+Esc）
echo   3. 結束所有 httpd.exe 和 mysqld.exe 進程
echo   4. 重新打開 XAMPP Control Panel
echo   5. 以管理員身份運行 XAMPP Control Panel
echo   6. 啟動 Apache 和 MySQL
echo   7. 等待至少 10 秒讓服務完全啟動
echo.
echo 【方法 2】修改 Apache 端口（如果端口 80 被占用）
if defined XAMPP_PATH (
    echo   1. 編輯文件: %XAMPP_PATH%\apache\conf\httpd.conf
    echo   2. 找到這一行: Listen 80
    echo   3. 改為: Listen 8080
    echo   4. 找到這一行: ServerName localhost:80
    echo   5. 改為: ServerName localhost:8080
    echo   6. 保存文件
    echo   7. 重新啟動 Apache
    echo   8. 訪問: http://localhost:8080/phpmyadmin/
) else (
    echo   1. 編輯 Apache 配置文件 httpd.conf
    echo   2. 將 Listen 80 改為 Listen 8080
    echo   3. 將 ServerName localhost:80 改為 ServerName localhost:8080
    echo   4. 保存並重啟 Apache
    echo   5. 訪問: http://localhost:8080/phpmyadmin/
)
echo.
echo 【方法 3】清除瀏覽器快取
echo   1. 按 Ctrl+Shift+Delete
echo   2. 選擇「快取圖片和檔案」
echo   3. 選擇「Cookie 和其他網站資料」
echo   4. 點擊「清除資料」
echo   5. 關閉並重新打開瀏覽器
echo   6. 嘗試訪問: http://localhost/phpmyadmin/
echo.
echo 【方法 4】檢查防火牆
echo   1. 打開 Windows 設定 → 更新與安全性 → Windows 安全性
echo   2. 點擊「防火牆與網路保護」
echo   3. 點擊「允許應用程式通過防火牆」
echo   4. 確保 Apache 和 MySQL 被允許
echo.
echo 【方法 5】使用命令列測試
echo   打開新的命令提示字元，執行：
echo   curl http://localhost
echo   或
echo   powershell -Command "Invoke-WebRequest -Uri http://localhost"
echo.

:end
echo ========================================
echo 診斷完成
echo ========================================
echo.
echo 如果問題仍然存在，請：
echo 1. 查看上面的錯誤日誌內容
echo 2. 在 XAMPP Control Panel 中點擊 Logs 按鈕查看詳細日誌
echo 3. 嘗試以管理員身份運行 XAMPP Control Panel
echo 4. 檢查 Windows 事件檢視器（eventvwr.msc）中的錯誤
echo.
pause

