# Git 提交訊息修正腳本
# 用於修正已損壞的提交訊息

# 設定 PowerShell 編碼為 UTF-8
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Git 提交訊息修正工具" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 檢查 Git 編碼設定
Write-Host "[步驟 1] 檢查 Git 編碼設定..." -ForegroundColor Yellow
$commitEncoding = git config --get i18n.commitencoding
$logEncoding = git config --get i18n.logoutputencoding

if ([string]::IsNullOrEmpty($commitEncoding)) {
    Write-Host "  ⚠️  提交編碼未設定，正在設定為 UTF-8..." -ForegroundColor Yellow
    git config --global i18n.commitencoding utf-8
} else {
    Write-Host "  ✅ 提交編碼: $commitEncoding" -ForegroundColor Green
}

if ([string]::IsNullOrEmpty($logEncoding)) {
    Write-Host "  ⚠️  日誌編碼未設定，正在設定為 UTF-8..." -ForegroundColor Yellow
    git config --global i18n.logoutputencoding utf-8
} else {
    Write-Host "  ✅ 日誌編碼: $logEncoding" -ForegroundColor Green
}

git config --global core.quotepath false
Write-Host "  ✅ 路徑引用已設定為 false" -ForegroundColor Green

Write-Host ""
Write-Host "[步驟 2] 顯示最近的提交..." -ForegroundColor Yellow
git log --oneline -3

Write-Host ""
Write-Host "[步驟 3] 修正最後一次提交訊息" -ForegroundColor Yellow
Write-Host "請輸入正確的提交訊息（或按 Enter 使用預設訊息）：" -ForegroundColor Cyan
$newMessage = Read-Host

if ([string]::IsNullOrWhiteSpace($newMessage)) {
    $newMessage = "解決合併衝突：整合錯誤處理和用戶資料功能"
    Write-Host "使用預設訊息: $newMessage" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "正在修正提交訊息..." -ForegroundColor Yellow
git commit --amend -m $newMessage

Write-Host ""
Write-Host "[步驟 4] 驗證修正結果..." -ForegroundColor Yellow
$latestCommit = git log -1 --pretty=format:"%s"
Write-Host "最新的提交訊息: $latestCommit" -ForegroundColor Green

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "修正完成！" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "注意事項：" -ForegroundColor Yellow
Write-Host "1. 如果已經推送到遠端，需要使用強制推送：" -ForegroundColor White
Write-Host "   git push --force" -ForegroundColor Gray
Write-Host ""
Write-Host "2. 強制推送前請確認沒有其他人在使用該分支" -ForegroundColor White
Write-Host ""

