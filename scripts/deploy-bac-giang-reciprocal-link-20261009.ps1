[CmdletBinding()]
param(
    [switch]$Deploy,
    [string]$RollbackBackup
)

# From PowerShell in this repository:
#   .\scripts\deploy-bac-giang-reciprocal-link-20261009.ps1
#   .\scripts\deploy-bac-giang-reciprocal-link-20261009.ps1 -Deploy
#   .\scripts\deploy-bac-giang-reciprocal-link-20261009.ps1 -RollbackBackup /home/jwhxtzru/backups/bac-giang-reciprocal-link-YYYYMMDD-HHMMSS
# Default mode is read-only preflight apart from uploading the one HTML file to staging.
# Deploy requires the reviewed HTML and this script to be committed first.

$ErrorActionPreference = 'Stop'
if ($Deploy -and $RollbackBackup) {
    throw 'Choose either -Deploy or -RollbackBackup.'
}

$sshAlias = 'mathanoibacninh'
$sourceRelative = 'theme/eyecare-child/content/khu-vuc/bac-giang.html'
$scriptRelative = 'scripts/deploy-bac-giang-reciprocal-link-20261009.ps1'
$oldSha = '6cddd51258ebfbcfa3bce97c999493a2566fc406409d716568ac468d7d603d3d'
# Hash of the reviewed UTF-8 source after normalizing CRLF to LF.
$reviewedNewSha = '215c4a9c9de2a3f26dd8eb5cf84efda36ddcd59bf314ac795bc51a296158264a'
$linkMarkup = 'href="/kien-thuc/benh-vien-mat-uy-tin-tai-bac-giang/"'
$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$mode = if ($RollbackBackup) { 'rollback' } elseif ($Deploy) { 'deploy' } else { 'preflight' }

function Get-BytesSha256([byte[]]$bytes) {
    $hasher = [System.Security.Cryptography.SHA256]::Create()
    try {
        return ([BitConverter]::ToString($hasher.ComputeHash($bytes))).Replace('-', '').ToLowerInvariant()
    } finally {
        $hasher.Dispose()
    }
}

function Assert-NativeSuccess([string]$step) {
    if ($LASTEXITCODE -ne 0) {
        throw "$step failed (exit $LASTEXITCODE)."
    }
}

# Send the remote Bash program as ASCII stdin, preserving Unix line endings.
function Invoke-RemoteProgram([string]$command, [string]$program) {
    $start = New-Object System.Diagnostics.ProcessStartInfo
    $start.FileName = 'ssh'
    $start.Arguments = '-o BatchMode=yes -o ConnectTimeout=10 ' + $sshAlias + ' "' + $command + '"'
    $start.UseShellExecute = $false
    $start.RedirectStandardInput = $true
    $start.RedirectStandardOutput = $true
    $start.RedirectStandardError = $true
    $process = New-Object System.Diagnostics.Process
    $process.StartInfo = $start
    try {
        if (-not $process.Start()) { throw 'Could not start ssh.' }
        $stdoutTask = $process.StandardOutput.ReadToEndAsync()
        $stderrTask = $process.StandardError.ReadToEndAsync()
        $program = $program.Replace(([string][char]13 + [char]10), [string][char]10)
        $process.StandardInput.Write($program + [char]10)
        $process.StandardInput.Close()
        $process.WaitForExit()
        $stdout = $stdoutTask.GetAwaiter().GetResult()
        $stderr = $stderrTask.GetAwaiter().GetResult()
        if ($stdout) { Write-Host $stdout.TrimEnd() }
        if ($stderr) { [Console]::Error.WriteLine($stderr.TrimEnd()) }
        if ($process.ExitCode -ne 0) {
            throw "Remote $mode failed (exit $($process.ExitCode))."
        }
    } finally {
        $process.Dispose()
    }
}

Push-Location $repoRoot
try {
    $stagePath = '-'
    $newSha = ('0' * 64)
    $backupArgument = '-'
    if ($mode -eq 'rollback') {
        if ($RollbackBackup -cnotmatch '^/home/jwhxtzru/backups/bac-giang-reciprocal-link-[0-9]{8}-[0-9]{6}$') {
            throw 'RollbackBackup must be the exact backup directory printed by this script.'
        }
        $backupArgument = $RollbackBackup
    } else {
        $sourcePath = Join-Path $repoRoot $sourceRelative
        if (-not (Test-Path -LiteralPath $sourcePath -PathType Leaf)) {
            throw "Missing source: $sourcePath"
        }
        $bytes = [IO.File]::ReadAllBytes($sourcePath)
        $utf8 = [System.Text.UTF8Encoding]::new($false, $true)
        $sourceText = $utf8.GetString($bytes)
        $normalized = $sourceText.Replace(([string][char]13 + [char]10), [string][char]10)
        if ($normalized.Contains([string][char]13) -or
            (Get-BytesSha256 $utf8.GetBytes($normalized)) -ne $reviewedNewSha) {
            throw 'The source differs from the reviewed HTML (ignoring only CRLF versus LF).'
        }
        if ([regex]::Matches($normalized, [regex]::Escape($linkMarkup)).Count -ne 1) {
            throw 'The contextual link must appear exactly once in the source.'
        }
        $newSha = Get-BytesSha256 $bytes

        if ($mode -eq 'deploy') {
            $dirty = & git status --porcelain -- $sourceRelative $scriptRelative
            Assert-NativeSuccess 'git status'
            if ($dirty) {
                throw 'Commit the reviewed HTML and deployment script before production deployment.'
            }
        }

        $stageDir = '/home/jwhxtzru/staging/bac-giang-reciprocal-link/' + [guid]::NewGuid().ToString('N')
        $stagePath = $stageDir + '/bac-giang.html'
        & ssh -o BatchMode=yes -o ConnectTimeout=10 $sshAlias ("mkdir -p " + $stageDir)
        Assert-NativeSuccess 'create staging directory'
        & scp -q -- $sourceRelative ($sshAlias + ':' + $stagePath)
        Assert-NativeSuccess 'stage the single changed HTML file'
        Write-Host ("STAGED={0}" -f $stagePath)
    }

    $remoteProgram = @'
set -Eeuo pipefail
mode=$1
old_sha=$2
new_sha=$3
stage=$4
backup_arg=$5
root=/home/jwhxtzru/public_html
theme=$root/wp-content/themes/eyecare-child
target=$theme/content/khu-vuc/bac-giang.html
page_url=https://mathanoibacninh.com/khu-vuc/kham-mat-bac-giang/
post_url=https://mathanoibacninh.com/kien-thuc/benh-vien-mat-uy-tin-tai-bac-giang/
lock=/home/jwhxtzru/website-ops-deploy.lock

check_sha() {
    local file=$1 expected=$2 actual
    [[ -s "$file" ]] || { echo "Missing or empty: $file" >&2; exit 1; }
    actual=$(sha256sum "$file" | cut -d' ' -f1)
    [[ "$actual" == "$expected" ]] || {
        echo "SHA-256 mismatch: $file expected $expected got $actual" >&2
        exit 1
    }
}

check_wp_links() {
    cd "$root"
    [[ "$(wp post get 55 --field=post_type)" == page ]]
    [[ "$(wp post get 55 --field=post_status)" == publish ]]
    [[ "$(wp post url 55)" == "$page_url" ]]
    [[ "$(wp post get 1745 --field=post_type)" == post ]]
    [[ "$(wp post get 1745 --field=post_status)" == publish ]]
    [[ "$(wp post url 1745)" == "$post_url" ]]
    [[ "$(wp post get 1745 --field=post_content)" == *"$page_url"* ]] || {
        echo 'Article #1745 no longer links to page #55.' >&2
        exit 1
    }
}

purge_cache() {
    cd "$root"
    wp cache flush --quiet
    wp litespeed-purge all >/dev/null 2>&1 || true
}

check_public() {
    local page_html post_html stamp
    stamp=$(date +%s)
    page_html=$(curl -fsSL --max-time 30 "$page_url?eyecare_link_check=$stamp")
    post_html=$(curl -fsSL --max-time 30 "$post_url?eyecare_link_check=$stamp")
    [[ "$page_html" == *'href="/kien-thuc/benh-vien-mat-uy-tin-tai-bac-giang/"'* ]] || {
        echo 'Page #55 has no contextual link in public HTML.' >&2
        return 1
    }
    [[ "$post_html" == *"$page_url"* ]] || {
        echo 'Article #1745 has no link to page #55 in public HTML.' >&2
        return 1
    }
}

[[ "$mode" == preflight || "$mode" == deploy || "$mode" == rollback ]]
[[ "$old_sha" =~ ^[0-9a-f]{64}$ ]]
exec 9>"$lock"
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }
cd "$root"

if [[ "$mode" == rollback ]]; then
    [[ "$backup_arg" =~ ^/home/jwhxtzru/backups/bac-giang-reciprocal-link-[0-9]{8}-[0-9]{6}$ ]] || {
        echo 'Invalid backup path.' >&2; exit 1;
    }
    check_sha "$backup_arg/bac-giang.html" "$old_sha"
    [[ -s "$backup_arg/database-before.sql" ]] || {
        echo 'Backup database export is missing.' >&2; exit 1;
    }
    deployed_sha=$(cat "$backup_arg/deployed.sha256")
    [[ "$deployed_sha" =~ ^[0-9a-f]{64}$ ]] || {
        echo 'Invalid deployed SHA-256 manifest.' >&2; exit 1;
    }
    check_sha "$target" "$deployed_sha"
    check_wp_links
    cp -p "$target" "$backup_arg/bac-giang.deployed-before-rollback.html"
    cp -p "$backup_arg/bac-giang.html" "$target"
    check_sha "$target" "$old_sha"
    purge_cache
    curl -fsSL --max-time 30 -o /dev/null "$page_url"
    curl -fsSL --max-time 30 -o /dev/null "$post_url"
    printf 'ROLLED_BACK=%s\n' "$backup_arg"
    exit 0
fi

[[ "$new_sha" =~ ^[0-9a-f]{64}$ ]]
[[ "$stage" =~ ^/home/jwhxtzru/staging/bac-giang-reciprocal-link/[0-9a-f]{32}/bac-giang\.html$ ]]
check_sha "$target" "$old_sha"
check_sha "$stage" "$new_sha"
[[ $(grep -oF 'href="/kien-thuc/benh-vien-mat-uy-tin-tai-bac-giang/"' "$stage" | wc -l) == 1 ]] || {
    echo 'Staged file does not contain exactly one contextual link.' >&2; exit 1;
}
check_wp_links
php -l "$theme/footer.php"
php -l "$theme/page-lien-he.php"
curl -fsSL --max-time 30 -o /dev/null "$page_url"
curl -fsSL --max-time 30 -o /dev/null "$post_url"
printf 'PREFLIGHT_OK old=%s new=%s\n' "$old_sha" "$new_sha"
if [[ "$mode" == preflight ]]; then exit 0; fi

umask 077
backup=/home/jwhxtzru/backups/bac-giang-reciprocal-link-$(date +%Y%m%d-%H%M%S)
mkdir "$backup"
cp -p "$target" "$backup/bac-giang.html"
check_sha "$backup/bac-giang.html" "$old_sha"
wp db export "$backup/database-before.sql" --quiet
[[ -s "$backup/database-before.sql" ]]
chmod 600 "$backup/database-before.sql"
printf '%s\n' "$new_sha" > "$backup/deployed.sha256"
printf 'BACKUP=%s\n' "$backup"

applied=0
rollback_on_error() {
    local status=$?
    trap - ERR
    if [[ "$applied" == 1 ]]; then
        cp -p "$backup/bac-giang.html" "$target" || true
        purge_cache || true
        echo "Deployment failed; original HTML restored from $backup" >&2
    fi
    exit "$status"
}
trap rollback_on_error ERR
applied=1
cp -p "$stage" "$target"
check_sha "$target" "$new_sha"
purge_cache
check_wp_links
check_public
php -l "$theme/footer.php"
php -l "$theme/page-lien-he.php"
trap - ERR
printf 'DEPLOYED=%s\n' "$backup"
printf 'ROLLBACK=run this script with -RollbackBackup %s\n' "$backup"
'@
    $command = "bash -s -- $mode $oldSha $newSha $stagePath $backupArgument"
    Invoke-RemoteProgram $command $remoteProgram
} finally {
    Pop-Location
}
