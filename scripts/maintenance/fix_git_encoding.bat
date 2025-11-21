@echo off
chcp 65001 >nul
echo ========================================
echo Git UTF-8 編碼修復工具
echo ========================================
echo.

echo [步驟 1] 設定 Git 編碼為 UTF-8...
git config --global i18n.commitencoding utf-8
git config --global i18n.logoutputencoding utf-8
git config --global core.quotepath false

echo.
echo [步驟 2] 檢查當前設定...
echo.
echo 提交編碼: 
git config --get i18n.commitencoding
echo.
echo 日誌輸出編碼:
git config --get i18n.logoutputencoding
echo.
echo 引用路徑:
git config --get core.quotepath
echo.

echo ========================================
echo 設定完成！
echo ========================================
echo.
echo 注意事項：
echo 1. 在 PowerShell 中，建議使用以下命令設定輸出編碼：
echo    [Console]::OutputEncoding = [System.Text.Encoding]::UTF8
echo.
echo 2. 在命令提示字元中，執行 chcp 65001 切換到 UTF-8
echo.
echo 3. 如果提交訊息已經亂碼，可以使用以下命令修正：
echo    git commit --amend -m "新的提交訊息"
echo.
pause

