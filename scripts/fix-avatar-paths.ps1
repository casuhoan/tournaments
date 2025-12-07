# Script per fixare i percorsi avatar
Write-Host "=== Fix Avatar Paths ===" -ForegroundColor Cyan

$files = Get-ChildItem -Path "public", "views", "forms", "admin" -Filter "*.php" -File -Recurse -ErrorAction SilentlyContinue

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw
    $modified = $false
    
    # Fix: 'data/avatars/default_avatar.png' -> '/data/avatars/default_avatar.png'
    if ($content -match "'data/avatars/default_avatar\.png'" -and $content -notmatch "'/data/avatars/default_avatar\.png'") {
        $content = $content -replace "'data/avatars/default_avatar\.png'", "'/data/avatars/default_avatar.png'"
        $modified = $true
    }
    
    # Fix: "data/avatars/default_avatar.png" -> "/data/avatars/default_avatar.png"
    if ($content -match '"data/avatars/default_avatar\.png"' -and $content -notmatch '"/data/avatars/default_avatar\.png"') {
        $content = $content -replace '"data/avatars/default_avatar\.png"', '"/data/avatars/default_avatar.png"'
        $modified = $true
    }
    
    if ($modified) {
        $content | Set-Content $file.FullName -NoNewline
        Write-Host "Fixed: $($file.Name)" -ForegroundColor Green
    }
}

Write-Host "`nCompleted!" -ForegroundColor Cyan
