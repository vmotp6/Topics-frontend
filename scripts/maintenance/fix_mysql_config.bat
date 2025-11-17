@echo off
chcp 65001 >nul
echo ========================================
echo MySQL 配置文件修復工具
echo ========================================
echo.
echo 此工具將檢查並修復 MySQL 配置文件問題
echo.

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
echo [步驟 1/4] 備份配置文件...
set BACKUP_FILE=%CONFIG_FILE%.backup_%date:~0,4%%date:~5,2%%date:~8,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set BACKUP_FILE=%BACKUP_FILE: =0%
copy "%CONFIG_FILE%" "%BACKUP_FILE%" >nul 2>&1
if %errorLevel% equ 0 (
    echo ✅ 配置文件已備份到: %BACKUP_FILE%
) else (
    echo ⚠️  備份失敗，但繼續執行...
)
echo.

:: 檢查配置文件中的常見錯誤
echo [步驟 2/4] 檢查配置文件錯誤...
echo.

:: 檢查是否有語法錯誤（如 ```ini 等）
findstr /i "```" "%CONFIG_FILE%" >nul 2>&1
if %errorLevel% equ 0 (
    echo ⚠️  發現可能的語法錯誤（包含 ```）
    echo 正在修復...
    
    :: 創建臨時文件
    set TEMP_FILE=%TEMP%\my_ini_fixed.tmp
    
    :: 讀取文件並移除問題行
    (for /f "delims=" %%a in ('type "%CONFIG_FILE%"') do (
        echo %%a | findstr /i "```" >nul
        if errorlevel 1 (
            echo %%a
        ) else (
            echo # 已移除問題行: %%a
        )
    )) > "%TEMP_FILE%"
    
    :: 替換原文件
    move /Y "%TEMP_FILE%" "%CONFIG_FILE%" >nul 2>&1
    echo ✅ 已修復配置文件
) else (
    echo ✅ 未發現明顯的語法錯誤
)
echo.

:: 檢查必要的配置項
echo [步驟 3/4] 檢查必要的配置項...
echo.

set NEEDS_FIX=0

:: 檢查 port 配置
findstr /i "^port" "%CONFIG_FILE%" >nul 2>&1
if %errorLevel% neq 0 (
    findstr /i "port\s*=" "%CONFIG_FILE%" >nul 2>&1
    if %errorLevel% neq 0 (
        echo ⚠️  未找到 port 配置
        set NEEDS_FIX=1
    )
)

:: 檢查 datadir 配置
findstr /i "datadir" "%CONFIG_FILE%" >nul 2>&1
if %errorLevel% neq 0 (
    echo ⚠️  未找到 datadir 配置
    set NEEDS_FIX=1
)

if %NEEDS_FIX% equ 0 (
    echo ✅ 必要的配置項都存在
) else (
    echo ⚠️  發現缺少必要的配置項
    echo 建議手動檢查配置文件
)
echo.

:: 檢查 IPv4 綁定
echo [步驟 4/4] 檢查網路綁定配置...
echo.

findstr /i "bind-address" "%CONFIG_FILE%" >nul 2>&1
if %errorLevel% neq 0 (
    echo ⚠️  未找到 bind-address 配置
    echo MySQL 可能只監聽 IPv6
    echo.
    echo 建議在 [mysqld] 區段添加：
    echo bind-address = 127.0.0.1
    echo.
    echo 是否要自動添加此配置？(Y/N)
    set /p ADD_BIND=
    if /i "%ADD_BIND%"=="Y" (
        echo.
        echo 正在添加 bind-address 配置...
        
        :: 查找 [mysqld] 區段並添加配置
        set TEMP_FILE=%TEMP%\my_ini_bind.tmp
        set IN_MYSQLD=0
        set BIND_ADDED=0
        
        (for /f "delims=" %%a in ('type "%CONFIG_FILE%"') do (
            echo %%a | findstr /i "^\[mysqld\]" >nul
            if !errorlevel! equ 0 (
                set IN_MYSQLD=1
                set BIND_ADDED=0
            )
            if !IN_MYSQLD! equ 1 (
                echo %%a | findstr /i "bind-address" >nul
                if !errorlevel! equ 0 (
                    set BIND_ADDED=1
                )
            )
            echo %%a
            if !IN_MYSQLD! equ 1 if !BIND_ADDED! equ 0 (
                echo %%a | findstr /i "^\[" >nul
                if !errorlevel! neq 0 (
                    echo bind-address = 127.0.0.1
                    set BIND_ADDED=1
                )
            )
        )) > "%TEMP_FILE%"
        
        move /Y "%TEMP_FILE%" "%CONFIG_FILE%" >nul 2>&1
        echo ✅ 已添加 bind-address 配置
    )
) else (
    echo ✅ 找到 bind-address 配置
    findstr /i "bind-address" "%CONFIG_FILE%"
)
echo.

echo ========================================
echo 修復完成
echo ========================================
echo.
echo 建議後續操作：
echo 1. 檢查配置文件: %CONFIG_FILE%
echo 2. 在 XAMPP Control Panel 中重新啟動 MySQL
echo 3. 如果仍有問題，查看錯誤日誌: %XAMPP_PATH%\mysql\data\mysql_error.log
echo.
pause


