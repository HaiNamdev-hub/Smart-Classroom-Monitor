param(
    [string] $PhpPath = (Get-Command php -ErrorAction Stop).Source,
    [int] $Port = 8001
)

$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
$backend = Join-Path $repoRoot 'be'
$template = Join-Path $backend 'database/performance-test/template.sqlite'
$runDirectory = Join-Path $backend 'database/performance-test/runs'
$fixturePath = Join-Path $PSScriptRoot 'data/students.json'
$verifier = Join-Path $PSScriptRoot 'verify-sqlite.php'
$results = Join-Path $PSScriptRoot 'results'

if (-not (Test-Path $template) -or -not (Test-Path $fixturePath)) {
    throw 'Isolated SQLite template or synthetic student fixture is missing.'
}
if (Test-Path (Join-Path $backend 'bootstrap/cache/config.php')) {
    throw 'Refusing preflight while Laravel config cache could override process-only isolation settings.'
}
if (Get-NetTCPConnection -State Listen -LocalPort $Port -ErrorAction SilentlyContinue) {
    throw "Port $Port is already in use; refusing to contact an unknown service."
}
if (Get-NetTCPConnection -State Listen -LocalPort 1 -ErrorAction SilentlyContinue) {
    throw 'Isolation guard port 1 is unexpectedly listening.'
}

$null = New-Item -ItemType Directory -Path $runDirectory -Force
$null = New-Item -ItemType Directory -Path $results -Force
$runId = 'preflight-' + (Get-Date -Format 'yyyyMMdd-HHmmss-fff')
$runDb = Join-Path $runDirectory "$runId.sqlite"
$null = Copy-Item -LiteralPath $template -Destination $runDb
& $PhpPath $verifier $runDb
if ($LASTEXITCODE -ne 0) { throw 'Fresh clone failed isolated-dataset verification.' }

$students = Get-Content -LiteralPath $fixturePath -Raw | ConvertFrom-Json
if (@($students).Count -lt 1) { throw 'No synthetic student fixture available.' }
$student = $students[0]
$expectedExamId = [int] $student.baiTapId
$names = @('APP_ENV','APP_DEBUG','APP_KEY','DB_CONNECTION','DB_DATABASE','DB_URL','DATABASE_URL','DB_HOST','DB_PORT','DB_USERNAME','DB_PASSWORD','SESSION_DRIVER','CACHE_STORE','BROADCAST_CONNECTION','QUEUE_CONNECTION','LOG_CHANNEL')
$oldEnvironment = @{}
foreach ($name in $names) { $oldEnvironment[$name] = [Environment]::GetEnvironmentVariable($name, 'Process') }

$keyBytes = New-Object byte[] 32
$rng = [Security.Cryptography.RandomNumberGenerator]::Create()
$rng.GetBytes($keyBytes)
$rng.Dispose()
$environment = @{
    APP_ENV = 'performance'; APP_DEBUG = 'false'; APP_KEY = 'base64:' + [Convert]::ToBase64String($keyBytes)
    DB_CONNECTION = 'sqlite'; DB_DATABASE = [IO.Path]::GetFullPath($runDb); DB_URL = ''; DATABASE_URL = ''
    DB_HOST = '127.0.0.1'; DB_PORT = '1'; DB_USERNAME = 'performance_disabled'; DB_PASSWORD = ''
    SESSION_DRIVER = 'array'; CACHE_STORE = 'array'; BROADCAST_CONNECTION = 'null'; QUEUE_CONNECTION = 'sync'; LOG_CHANNEL = 'stderr'
}
foreach ($name in $environment.Keys) { [Environment]::SetEnvironmentVariable($name, [string] $environment[$name], 'Process') }

$server = $null
try {
    $router = Join-Path $backend 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'
    $publicDirectory = Join-Path $backend 'public'
    $stdout = Join-Path $results "$runId-php.log"
    $stderr = Join-Path $results "$runId-php-error.log"
    $server = Start-Process -FilePath $PhpPath `
        -ArgumentList "-S 127.0.0.1:$Port -t . `"$router`"" `
        -WorkingDirectory $publicDirectory -WindowStyle Hidden `
        -RedirectStandardOutput $stdout -RedirectStandardError $stderr -PassThru

    $ready = $false
    for ($i = 0; $i -lt 30; $i++) {
        if ($server.HasExited) { throw "Isolated Laravel server exited. Inspect $stderr" }
        try {
            $null = Invoke-WebRequest -Uri "http://127.0.0.1:$Port/api/check-token" `
                -Headers @{ Accept = 'application/json' } -TimeoutSec 2 -UseBasicParsing
            $status = 200
        } catch {
            $status = if ($_.Exception.Response) { [int] $_.Exception.Response.StatusCode } else { 0 }
        }
        if ($status -eq 401) { $ready = $true; break }
        Start-Sleep -Milliseconds 500
    }
    if (-not $ready) { throw "Laravel preflight health check did not return JSON 401 (last status $status)." }
    Write-Output 'HEALTH_CHECK=PASS HTTP=401 unauthenticated JSON'

    $body = @{ email = $student.email; password = $student.password } | ConvertTo-Json -Compress
    $login = Invoke-RestMethod -Method Post -Uri "http://127.0.0.1:$Port/api/dang-nhap" `
        -ContentType 'application/json' -Headers @{ Accept = 'application/json' } -Body $body -TimeoutSec 10
    $token = [string] $login.access_token
    if ([string]::IsNullOrWhiteSpace($token)) { throw 'Synthetic student login returned no Sanctum token.' }
    $headers = @{ Accept = 'application/json'; Authorization = "Bearer $token" }
    Write-Output 'LOGIN=PASS HTTP=200 (synthetic credential; token not logged)'

    $classes = Invoke-RestMethod -Method Get -Uri "http://127.0.0.1:$Port/api/sinh-vien/lop-hoc" `
        -Headers $headers -TimeoutSec 10
    if (-not $classes.status -or @($classes.data).Count -lt 1) { throw 'Student class list is empty.' }
    Write-Output "CLASS_LIST=PASS count=$(@($classes.data).Count)"

    $assignments = Invoke-RestMethod -Method Get -Uri "http://127.0.0.1:$Port/api/sinh-vien/bai-tap" `
        -Headers $headers -TimeoutSec 10
    $assignmentIds = @($assignments.data | ForEach-Object { [int] $_.id })
    if (-not $assignments.status -or $assignmentIds -notcontains $expectedExamId) {
        throw "ASSIGNMENT_LIST=FAIL expected exam $expectedExamId is not visible; no exam writes were sent."
    }
    Write-Output "ASSIGNMENT_LIST=PASS count=$($assignmentIds.Count) expected_exam=$expectedExamId"

    $examUrl = "http://127.0.0.1:$Port/api/sinh-vien/bai-kiem-tra/$expectedExamId"
    $exam = Invoke-RestMethod -Method Get -Uri $examUrl -Headers $headers -TimeoutSec 10
    if (-not $exam.status -or @($exam.data.questions).Count -lt 1) { throw 'Exam detail has no valid question.' }
    Write-Output "EXAM_DETAIL=PASS questions=$(@($exam.data.questions).Count)"

    $startBody = @{ fullscreen = $true; cameraReady = $true } | ConvertTo-Json -Compress
    $start = Invoke-RestMethod -Method Post -Uri "$examUrl/bat-dau" -Headers $headers `
        -ContentType 'application/json' -Body $startBody -TimeoutSec 10
    if (-not $start.status -or $start.data.trangThaiLamBai -ne 'IN_PROGRESS') { throw 'Exam start did not confirm IN_PROGRESS.' }
    Write-Output "START=PASS state=$($start.data.trangThaiLamBai) duplicate=$($start.data.duplicate)"

    $draftBody = @{
        answers = $student.answers
        baseDraftRevision = $exam.data.draftRevision
    } | ConvertTo-Json -Depth 8 -Compress
    $draft = Invoke-RestMethod -Method Put -Uri "$examUrl/nhap" -Headers $headers `
        -ContentType 'application/json' -Body $draftBody -TimeoutSec 10
    if (-not $draft.status -or $draft.data.draftRevision.Length -ne 64) { throw 'Draft save did not return a revision.' }
    Write-Output 'DRAFT_SAVE=PASS (revision present; answers not logged)'

    $restored = Invoke-RestMethod -Method Get -Uri $examUrl -Headers $headers -TimeoutSec 10
    if ((ConvertTo-Json -InputObject $restored.data.answers -Compress) -ne (ConvertTo-Json -InputObject $student.answers -Compress)) {
        throw 'Draft restore does not match the saved fixture answers.'
    }
    Write-Output 'DRAFT_RESTORE=PASS'

    $submitBody = @{ answers = $student.answers } | ConvertTo-Json -Depth 8 -Compress
    $submitted = Invoke-RestMethod -Method Post -Uri "$examUrl/nop" -Headers $headers `
        -ContentType 'application/json' -Body $submitBody -TimeoutSec 10
    if (-not $submitted.status -or -not $submitted.data.daNop) { throw 'Submit was not confirmed.' }
    Write-Output "SUBMIT=PASS score=$($submitted.data.diem)"

    $finalAssignments = Invoke-RestMethod -Method Get -Uri "http://127.0.0.1:$Port/api/sinh-vien/bai-tap" `
        -Headers $headers -TimeoutSec 10
    $submittedExam = @($finalAssignments.data | Where-Object { [int] $_.id -eq $expectedExamId }) | Select-Object -First 1
    if (-not $submittedExam -or -not $submittedExam.da_nop) { throw 'Assignment list did not show the saved submission.' }
    Write-Output 'SUBMISSION_STATUS=PASS da_nop=true'

    & $PhpPath $verifier $runDb 1
    if ($LASTEXITCODE -ne 0) { throw 'Post-smoke isolated database integrity check failed.' }
    Write-Output "PREFLIGHT=PASS database_clone=$runId.sqlite"
} finally {
    if ($server -and -not $server.HasExited) { Stop-Process -Id $server.Id -Force -ErrorAction SilentlyContinue }
    foreach ($name in $oldEnvironment.Keys) {
        [Environment]::SetEnvironmentVariable($name, $oldEnvironment[$name], 'Process')
    }
}
