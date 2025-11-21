# Git UTF-8 編碼問題解決指南

## 問題描述

在 Windows 系統上使用 Git 時，提交訊息和日誌可能顯示為亂碼，特別是包含中文的訊息。

## 原因

1. **Git 編碼設定未配置**：Git 預設可能使用系統編碼（Windows 通常是 Big5/CP950）
2. **終端機編碼不匹配**：PowerShell 或命令提示字元的編碼與 Git 不一致
3. **提交訊息編碼問題**：提交時使用的編碼與顯示時不同

## 解決方案

### 方法 1: 設定 Git 全域編碼（推薦）

在命令提示字元或 PowerShell 中執行：

```bash
git config --global i18n.commitencoding utf-8
git config --global i18n.logoutputencoding utf-8
git config --global core.quotepath false
```

### 方法 2: 設定 PowerShell 編碼

在 PowerShell 中執行：

```powershell
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
```

或將此命令加入 PowerShell 設定檔（`$PROFILE`）使其永久生效。

### 方法 3: 設定命令提示字元編碼

在命令提示字元中執行：

```cmd
chcp 65001
```

這會將命令提示字元切換到 UTF-8 編碼（代碼頁 65001）。

### 方法 4: 使用自動修復腳本

執行專案中的修復腳本：

```cmd
scripts\maintenance\fix_git_encoding.bat
```

## 修正已亂碼的提交訊息

如果提交訊息已經亂碼，可以使用以下命令修正：

```bash
# 修正最後一次提交訊息
git commit --amend -m "正確的提交訊息"

# 如果需要強制推送（請謹慎使用）
git push --force
```

**注意**：如果已經推送到遠端，使用 `--force` 前請確認沒有其他人基於該提交工作。

## 驗證設定

檢查 Git 編碼設定：

```bash
git config --get i18n.commitencoding
git config --get i18n.logoutputencoding
git config --get core.quotepath
```

預期輸出：
- `i18n.commitencoding`: `utf-8`
- `i18n.logoutputencoding`: `utf-8`
- `core.quotepath`: `false`

## 預防措施

1. **在提交前檢查編碼**：
   ```bash
   git commit -m "測試中文訊息"
   git log -1
   ```

2. **使用 Git GUI 工具**：
   - 某些 Git GUI 工具（如 GitKraken、SourceTree）可能自動處理編碼問題

3. **設定編輯器編碼**：
   如果使用 `git commit`（不帶 `-m`）進入編輯器，確保編輯器使用 UTF-8 編碼

## 常見問題

### Q: 設定後仍然顯示亂碼？

**A**: 可能需要：
1. 重新開啟終端機
2. 確認終端機編碼已切換到 UTF-8
3. 檢查 Git 版本（建議使用較新版本）

### Q: 如何永久設定 PowerShell 編碼？

**A**: 將以下內容加入 PowerShell 設定檔：

```powershell
# 檢查設定檔是否存在
Test-Path $PROFILE

# 如果不存在，建立設定檔
New-Item -Path $PROFILE -Type File -Force

# 編輯設定檔，加入以下內容
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
```

### Q: 團隊其他成員也需要設定嗎？

**A**: 是的，每個開發者都需要在自己的機器上設定。可以將此文件加入專案文檔，或在團隊中分享設定步驟。

## 相關資源

- [Git 官方文檔 - 編碼設定](https://git-scm.com/book/zh-tw/v2/Customizing-Git-Git-Configuration)
- [Windows 編碼頁面說明](https://docs.microsoft.com/zh-tw/windows/win32/intl/code-page-identifiers)

