[CmdletBinding()]
param(
    [string]$BaseUrl = 'https://mathanoibacninh.com',
    [switch]$CheckSsh
)

$ErrorActionPreference = 'Stop'
$paths = @(
    '/',
    '/kien-thuc/',
    '/kien-thuc/dau-hieu-can-kham-ngay/',
    '/gioi-thieu/',
    '/doi-ngu-bac-si/',
    '/lien-he/'
)

$failed = @()
foreach ($path in $paths) {
    $url = $BaseUrl.TrimEnd('/') + $path
    try {
        $response = Invoke-WebRequest -Uri $url -UseBasicParsing -MaximumRedirection 5
        $title = ([regex]::Match($response.Content, '<title>(.*?)</title>')).Groups[1].Value
        Write-Host ("{0} {1} {2}" -f $response.StatusCode, $path, $title)
        if ($response.StatusCode -ge 400) { $failed += $path }
    } catch {
        Write-Error ("FAIL {0}: {1}" -f $path, $_.Exception.Message)
        $failed += $path
    }
}

if ($CheckSsh) {
    ssh -o BatchMode=yes -o ConnectTimeout=10 mathanoibacninh "cd ~/public_html && wp core version && wp option get home && wp option get siteurl"
    if ($LASTEXITCODE -ne 0) { $failed += 'ssh' }
}

if ($failed.Count -gt 0) {
    throw ("Smoke test failed: " + ($failed -join ', '))
}

Write-Host 'Smoke test passed.' -ForegroundColor Green
