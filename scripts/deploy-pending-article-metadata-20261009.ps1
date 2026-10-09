[CmdletBinding()]
param(
    [switch]$Deploy,
    [string]$RollbackBackup
)

# .\scripts\deploy-pending-article-metadata-20261009.ps1
# .\scripts\deploy-pending-article-metadata-20261009.ps1 -Deploy
# .\scripts\deploy-pending-article-metadata-20261009.ps1 -RollbackBackup /home/jwhxtzru/backups/pending-article-metadata-YYYYMMDD-HHMMSS
# Default mode stages only the reviewed PHP file and checks production without changing it.
# Commit this script and the reviewed PHP file before running -Deploy.

$ErrorActionPreference = 'Stop'
if ($Deploy -and $RollbackBackup) {
    throw 'Choose either -Deploy or -RollbackBackup.'
}

$sshAlias = 'mathanoibacninh'
$sourceRelative = 'theme/eyecare-child/inc/tac-gia-bac-si.php'
$scriptRelative = 'scripts/deploy-pending-article-metadata-20261009.ps1'
$oldSha = '6ff8cfe8d935fb26608e119924c01b93e4cc983a89b72b04def1070d9c9d9ced'
# SHA-256 of the reviewed UTF-8 PHP source after normalizing CRLF to LF.
$reviewedNewSha = 'ce7ee795e57573bbff82a6afd38dff4f8dd6aca0f9a54cdb3ac692a6a97ca34e'
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

# Send Bash through stdin so its quoting and line endings are not changed by PowerShell.
function Invoke-RemoteProgram([string]$command, [string]$program) {
    $start = New-Object System.Diagnostics.ProcessStartInfo
    $start.FileName = 'ssh'
    $start.Arguments = '-o BatchMode=yes -o ConnectTimeout=10 ' + $sshAlias + ' "' + $command + '"'
    $start.UseShellExecute = $false
    $start.RedirectStandardInput = $true
    $start.StandardInputEncoding = [System.Text.UTF8Encoding]::new($false)
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
    $newSha = '0' * 64
    $backupArgument = '-'
    if ($mode -eq 'rollback') {
        if ($RollbackBackup -cnotmatch '^/home/jwhxtzru/backups/pending-article-metadata-[0-9]{8}-[0-9]{6}$') {
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
            throw 'The PHP source differs from the reviewed file (ignoring only CRLF versus LF).'
        }
        $newSha = Get-BytesSha256 $bytes

        if ($mode -eq 'deploy') {
            $dirty = & git status --porcelain -- $sourceRelative $scriptRelative
            Assert-NativeSuccess 'git status'
            if ($dirty) {
                throw 'Commit the reviewed PHP file and deployment script before production deployment.'
            }
        }

        $stageDir = '/home/jwhxtzru/staging/pending-article-metadata/' + [guid]::NewGuid().ToString('N')
        $stagePath = $stageDir + '/tac-gia-bac-si.php'
        & ssh -o BatchMode=yes -o ConnectTimeout=10 $sshAlias ("mkdir -p " + $stageDir)
        Assert-NativeSuccess 'create staging directory'
        & scp -q -- $sourceRelative ($sshAlias + ':' + $stagePath)
        Assert-NativeSuccess 'stage the single changed PHP file'
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
target=$root/wp-content/themes/eyecare-child/inc/tac-gia-bac-si.php
post_url=https://mathanoibacninh.com/kien-thuc/benh-vien-mat-uy-tin-tai-bac-giang/
expected_date='2026-10-09 08:34:41'
lock=/home/jwhxtzru/website-ops-deploy.lock

check_sha() {
    local file=$1 expected=$2 actual
    [[ -s "$file" ]] || { echo "Missing or empty: $file" >&2; return 1; }
    actual=$(sha256sum "$file" | cut -d' ' -f1)
    [[ "$actual" == "$expected" ]] || {
        echo "SHA-256 mismatch: $file expected $expected got $actual" >&2
        return 1
    }
}

check_post() {
    cd "$root"
    [[ "$(wp post get 1745 --field=post_type)" == post ]]
    [[ "$(wp post get 1745 --field=post_status)" == publish ]]
    [[ "$(wp post get 1745 --field=post_date)" == "$expected_date" ]]
    [[ "$(wp post get 1745 --field=post_modified)" == "$expected_date" ]]
    [[ "$(wp post meta get 1745 _eyecare_content_review_status)" == pending-author-and-medical-review ]]
    [[ "$(wp post url 1745)" == "$post_url" ]]
}

purge_cache() {
    cd "$root"
    wp cache flush --quiet
    wp litespeed-purge all >/dev/null 2>&1 || true
}

smoke_urls() {
    local url
    for url in https://mathanoibacninh.com/ https://mathanoibacninh.com/kien-thuc/ \
        https://mathanoibacninh.com/gioi-thieu/ https://mathanoibacninh.com/lien-he/ \
        https://mathanoibacninh.com/wp-content/themes/eyecare-child/style.css "$post_url"; do
        curl -fsSL --max-time 30 -o /dev/null "$url"
    done
}

check_public_metadata() {
    local html_path=$1
    python3 - "$html_path" "$post_url" <<'PY'
import json
import re
import sys
from html.parser import HTMLParser

html_path, post_url = sys.argv[1:]
expected_iso = '2026-10-09T08:34:41+07:00'

class HeadScan(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.in_head = False
        self.in_body = False
        self.in_raw_body_tag = False
        self.json_script = False
        self.json_chunks = []
        self.json_blocks = []
        self.meta = []
        self.ai_blocks = []
        self.body_text = []

    def handle_starttag(self, tag, attrs):
        attributes = dict(attrs)
        if tag == 'head':
            self.in_head = True
        elif tag == 'body':
            self.in_body = True
        elif tag == 'meta' and self.in_head:
            self.meta.append(attributes)
        elif tag == 'script' and self.in_head and attributes.get('type') == 'application/ld+json':
            self.json_script = True
            self.json_chunks = []
        if self.in_body and tag in {'script', 'style'}:
            self.in_raw_body_tag = True
        if not self.in_head:
            classes = (attributes.get('class') or '').split()
            for name in classes:
                if name in {'obs-ai-bridge', 'obs-aicb-eeat', 'obs-trust-note'} or name.startswith('obs-author-bio'):
                    self.ai_blocks.append(name)

    def handle_data(self, data):
        if self.json_script:
            self.json_chunks.append(data)
        elif self.in_body and not self.in_raw_body_tag:
            self.body_text.append(data)

    def handle_endtag(self, tag):
        if tag == 'script' and self.json_script:
            self.json_blocks.append(''.join(self.json_chunks))
            self.json_script = False
        if tag in {'script', 'style'} and self.in_body:
            self.in_raw_body_tag = False
        elif tag == 'head':
            self.in_head = False
        elif tag == 'body':
            self.in_body = False

with open(html_path, encoding='utf-8') as source:
    scan = HeadScan()
    scan.feed(source.read())

def fail(message):
    raise SystemExit(message)

def nodes(value):
    if isinstance(value, list):
        for item in value:
            yield from nodes(item)
    elif isinstance(value, dict):
        if '@graph' in value:
            yield from nodes(value['@graph'])
        else:
            yield value

articles = []
for block in scan.json_blocks:
    for node in nodes(json.loads(block)):
        types = node.get('@type', [])
        if isinstance(types, str):
            types = [types]
        if 'Article' in types or 'BlogPosting' in types:
            articles.append(node)

if len(articles) != 1:
    fail(f'Expected exactly one Article schema, found {len(articles)}')
article = articles[0]
types = article.get('@type', [])
if article.get('@id') != post_url + '#bai-viet' or not {'Article', 'MedicalWebPage'}.issubset(set(types)):
    fail('Article is not the child-theme medical schema')
if article.get('datePublished') != expected_iso or article.get('dateModified') != expected_iso:
    fail('Article dates do not match the local publication and modification times')
if any(field in article for field in ('author', 'reviewedBy', 'lastReviewed')):
    fail('Article invents an author or medical reviewer')
if scan.ai_blocks:
    fail('OBS AI/author attribution block remains: ' + ', '.join(scan.ai_blocks))
body_text = ' '.join(' '.join(scan.body_text).split())
credit_label = 'Bi\u00ean so\u1ea1n b\u1edfi:'
doctor_name = '\u0110\u1eb7ng C\u00f4ng H\u1ea3i'
if re.search(re.escape(credit_label) + r'\s*(?:BSCKI\.?\s*)?' + re.escape(doctor_name), body_text, re.IGNORECASE):
    fail('False doctor authorship remains in the public article')

def expect_meta(key, expected, attribute='property'):
    values = [item.get('content') for item in scan.meta if item.get(attribute) == key]
    if values != [expected]:
        fail(f'{key}: expected exactly [{expected!r}], got {values!r}')

expect_meta('og:type', 'article')
expect_meta('og:locale', 'vi_VN')
expect_meta('og:url', post_url)
expect_meta('article:published_time', expected_iso)
expect_meta('article:modified_time', expected_iso)
for key, attribute in (('og:title', 'property'), ('og:description', 'property'),
                       ('og:image', 'property'), ('twitter:card', 'name'),
                       ('twitter:title', 'name'), ('twitter:description', 'name'),
                       ('twitter:image', 'name')):
    values = [item.get('content') for item in scan.meta if item.get(attribute) == key]
    if len(values) != 1 or not values[0]:
        fail(f'{key}: expected one nonempty tag, got {values!r}')

print('PUBLIC_METADATA_OK: one child Article, local dates, vi_VN OG/Twitter, no OBS AI credit')
PY
}

[[ "$mode" == preflight || "$mode" == deploy || "$mode" == rollback ]]
[[ "$old_sha" =~ ^[0-9a-f]{64}$ ]]
exec 9>"$lock"
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }
cd "$root"

if [[ "$mode" == rollback ]]; then
    [[ "$backup_arg" =~ ^/home/jwhxtzru/backups/pending-article-metadata-[0-9]{8}-[0-9]{6}$ ]] || {
        echo 'Invalid backup path.' >&2; exit 1;
    }
    check_sha "$backup_arg/tac-gia-bac-si.php" "$old_sha"
    [[ -s "$backup_arg/database-before.sql" ]] || { echo 'Database backup is missing.' >&2; exit 1; }
    deployed_sha=$(cat "$backup_arg/deployed.sha256")
    [[ "$deployed_sha" =~ ^[0-9a-f]{64}$ ]]
    check_sha "$target" "$deployed_sha"
    cp -p "$target" "$backup_arg/tac-gia-bac-si.deployed-before-rollback.php"
    cp -p "$backup_arg/tac-gia-bac-si.php" "$target"
    check_sha "$target" "$old_sha"
    php -l "$target"
    purge_cache
    smoke_urls
    printf 'ROLLED_BACK=%s\n' "$backup_arg"
    exit 0
fi

[[ "$new_sha" =~ ^[0-9a-f]{64}$ ]]
[[ "$stage" =~ ^/home/jwhxtzru/staging/pending-article-metadata/[0-9a-f]{32}/tac-gia-bac-si\.php$ ]]
check_sha "$target" "$old_sha"
check_sha "$stage" "$new_sha"
php -l "$stage"
php -l "$target"
php -l "$root/wp-content/themes/eyecare-child/footer.php"
php -l "$root/wp-content/themes/eyecare-child/page-lien-he.php"
check_post
smoke_urls
printf 'PREFLIGHT_OK old=%s staged=%s\n' "$old_sha" "$new_sha"
if [[ "$mode" == preflight ]]; then exit 0; fi

umask 077
backup=/home/jwhxtzru/backups/pending-article-metadata-$(date +%Y%m%d-%H%M%S)
mkdir "$backup"
cp -p "$target" "$backup/tac-gia-bac-si.php"
check_sha "$backup/tac-gia-bac-si.php" "$old_sha"
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
        cp -p "$backup/tac-gia-bac-si.php" "$target" || true
        php -l "$target" || true
        purge_cache || true
        echo "Deployment failed; original PHP restored from $backup" >&2
    fi
    exit "$status"
}
trap rollback_on_error ERR
applied=1
cp -p "$stage" "$target"
check_sha "$target" "$new_sha"
php -l "$target"
purge_cache
check_post
stamp=$(date +%s)
curl -fsSL --max-time 30 "$post_url?eyecare_metadata_check=$stamp" -o "$backup/public-after.html"
check_public_metadata "$backup/public-after.html"
smoke_urls
trap - ERR
printf 'DEPLOYED=%s\n' "$backup"
printf 'ROLLBACK=run this script with -RollbackBackup %s\n' "$backup"
'@
    $command = "bash -s -- $mode $oldSha $newSha $stagePath $backupArgument"
    Invoke-RemoteProgram $command $remoteProgram
} finally {
    Pop-Location
}
