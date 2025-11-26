param(
    [string]$ConfigFile = $args[0]
)

if (-not $ConfigFile -or -not (Test-Path $ConfigFile)) {
    Write-Host "錯誤: 未找到配置文件" -ForegroundColor Red
    exit 1
}

$content = Get-Content $ConfigFile
$newContent = @()
$inMysqld = $false
$bindAdded = $false

foreach ($line in $content) {
    if ($line -match '^\s*\[mysqld\]') {
        $inMysqld = $true
        $bindAdded = $false
    }
    if ($line -match '^\s*\[.*\]' -and $line -notmatch '^\s*\[mysqld\]') {
        $inMysqld = $false
    }
    if ($inMysqld -and -not $bindAdded -and $line -match '^\s*[a-zA-Z]' -and $line -notmatch '^\s*#') {
        $newContent += 'bind-address = 127.0.0.1'
        $bindAdded = $true
    }
    $newContent += $line
}

Set-Content $ConfigFile -Value $newContent -Encoding UTF8

if ($bindAdded) {
    Write-Host "成功: 已添加 bind-address = 127.0.0.1" -ForegroundColor Green
} else {
    Write-Host "提示: bind-address 可能已存在或無法自動添加" -ForegroundColor Yellow
}

exit 0








