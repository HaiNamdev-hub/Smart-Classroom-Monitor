param(
    [string] $K6Path = (Join-Path $PSScriptRoot 'tools/k6/k6.exe'),
    [ValidateSet(1, 5, 10, 20, 30, 50, 75, 100)]
    [int] $MaxVus = 100,
    [switch] $RunConfirmedIsolatedLoad
)

$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
$backend = Join-Path $repoRoot 'be'
$templateDb = Join-Path $backend 'database/performance-test/template.sqlite'
$fixture = Join-Path $PSScriptRoot 'data/students.json'
$verifier = Join-Path $PSScriptRoot 'verify-sqlite.php'
$k6Script = Join-Path $PSScriptRoot 'exam-flow.k6.js'
$runDbDirectory = Join-Path $backend 'database/performance-test/runs'
$resultsDirectory = Join-Path $PSScriptRoot 'results'
$port = 8001
$stages = @(1, 5, 10, 20, 30, 50, 75, 100) | Where-Object { $_ -le $MaxVus }

if (-not $RunConfirmedIsolatedLoad) {
    Write-Output 'DRY RUN: no database clone, server process, or load request will be created.'
    Write-Output "Would verify the marked SQLite template and run VU stages: $($stages -join ', ')."
    Write-Output 'To start this isolated test explicitly, pass -RunConfirmedIsolatedLoad.'
    exit 0
}

if (-not (Test-Path $K6Path)) { throw "k6 binary not found: $K6Path. Install/extract it on F: first." }
if (-not (Test-Path $templateDb) -or -not (Test-Path $fixture)) { throw 'Isolated template database and fixture are not ready. Run Prepare-PerformanceDb.ps1 only after approving isolated DB creation.' }
if (Test-Path (Join-Path $backend 'bootstrap/cache/config.php')) { throw 'Refusing load: Laravel config cache exists and could override isolated process environment.' }
if (Get-NetTCPConnection -State Listen -LocalPort $port -ErrorAction SilentlyContinue) { throw "Port $port is already in use; refusing to target an unknown process." }
if (Get-NetTCPConnection -State Listen -LocalPort 1 -ErrorAction SilentlyContinue) { throw 'Safety guard port 1 is unexpectedly listening; refusing to proceed.' }
if (([IO.Path]::GetFullPath($templateDb)).ToLowerInvariant() -ne ([IO.Path]::GetFullPath((Join-Path $backend 'database/performance-test/template.sqlite'))).ToLowerInvariant()) {
    throw 'Template path failed the exact isolated path check.'
}

$phpCommand = Get-Command php -ErrorAction Stop
$null = & $K6Path version
if ($LASTEXITCODE -ne 0) { throw 'k6 version check failed.' }
& $phpCommand.Source $verifier $templateDb
if ($LASTEXITCODE -ne 0) { throw 'Template dataset marker/count check failed; no server will start.' }

New-Item -ItemType Directory -Path $runDbDirectory -Force | Out-Null
New-Item -ItemType Directory -Path $resultsDirectory -Force | Out-Null
$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$oldEnv = @{}
$envNames = @('APP_ENV','APP_DEBUG','APP_KEY','DB_CONNECTION','DB_DATABASE','DB_URL','DATABASE_URL','DB_HOST','DB_PORT','DB_USERNAME','DB_PASSWORD','SESSION_DRIVER','CACHE_STORE','BROADCAST_CONNECTION','QUEUE_CONNECTION','BCRYPT_ROUNDS','LOG_CHANNEL','PERF_BASE_URL','PERF_USERS_FILE','PERF_VUS','PERF_DRAFT_WRITES','PERF_READ_REPEATS','PERF_ISOLATED_DATASET')
foreach ($name in $envNames) { $oldEnv[$name] = [Environment]::GetEnvironmentVariable($name, 'Process') }

function Set-PerformanceEnvironment([string] $databasePath, [int] $vuCount) {
    $rng = [Security.Cryptography.RandomNumberGenerator]::Create()
    $keyBytes = New-Object byte[] 32
    $rng.GetBytes($keyBytes)
    $rng.Dispose()
    $environment = @{
        APP_ENV = 'performance'; APP_DEBUG = 'false'; APP_KEY = 'base64:' + [Convert]::ToBase64String($keyBytes)
        DB_CONNECTION = 'sqlite'; DB_DATABASE = [IO.Path]::GetFullPath($databasePath); DB_URL = ''; DATABASE_URL = ''
        DB_HOST = '127.0.0.1'; DB_PORT = '1'; DB_USERNAME = 'performance_disabled'; DB_PASSWORD = ''
        SESSION_DRIVER = 'array'; CACHE_STORE = 'array'; BROADCAST_CONNECTION = 'null'; QUEUE_CONNECTION = 'sync'
        BCRYPT_ROUNDS = '4'; LOG_CHANNEL = 'stderr'; PERF_BASE_URL = 'http://127.0.0.1:8001'
        PERF_USERS_FILE = $fixture; PERF_VUS = [string] $vuCount; PERF_DRAFT_WRITES = '3'; PERF_READ_REPEATS = '3'; PERF_ISOLATED_DATASET = 'YES'
    }
    foreach ($name in $environment.Keys) { [Environment]::SetEnvironmentVariable($name, [string] $environment[$name], 'Process') }
}

try {
    $allStagesCompleted = $true
    foreach ($vu in $stages) {
        $runDatabase = Join-Path $runDbDirectory "run-$timestamp-vu-$vu.sqlite"
        if (Test-Path $runDatabase) { throw "Refusing to overwrite prior run database: $runDatabase" }
        Copy-Item -LiteralPath $templateDb -Destination $runDatabase
        & $phpCommand.Source $verifier $runDatabase
        if ($LASTEXITCODE -ne 0) { throw "Run database clone for $vu VUs failed verification." }

        Set-PerformanceEnvironment $runDatabase $vu
        $stageName = "$timestamp-vu-$vu"
        $stdout = Join-Path $resultsDirectory "$stageName-k6.log"
        $stderr = Join-Path $resultsDirectory "$stageName-k6-error.log"
        $summary = Join-Path $resultsDirectory "$stageName-summary.json"
        $samples = Join-Path $resultsDirectory "$stageName-process.csv"
        $serverOut = Join-Path $resultsDirectory "$stageName-php.log"
        $serverErr = Join-Path $resultsDirectory "$stageName-php-error.log"
        $phpServer = $null
        $k6Process = $null
        $rows = [System.Collections.Generic.List[object]]::new()

        try {
            $router = Join-Path $backend 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'
            $publicDir = Join-Path $backend 'public'
            $phpArgs = "-S 127.0.0.1:$port -t . `"$router`""
            $phpServer = Start-Process -FilePath $phpCommand.Source -ArgumentList $phpArgs -WorkingDirectory $publicDir -WindowStyle Hidden -RedirectStandardOutput $serverOut -RedirectStandardError $serverErr -PassThru

            $ready = $false
            for ($attempt = 0; $attempt -lt 30; $attempt++) {
                if ($phpServer.HasExited) { throw "PHP isolated server exited early; inspect $serverErr" }
                try {
                    $health = Invoke-WebRequest -Uri "http://127.0.0.1:$port/api/check-token" -Headers @{ Accept = 'application/json' } -TimeoutSec 2 -UseBasicParsing
                    if ([int] $health.StatusCode -eq 401) { $ready = $true; break }
                } catch {
                    $response = $_.Exception.Response
                    if ($response -and [int] $response.StatusCode -eq 401) { $ready = $true; break }
                }
                Start-Sleep -Seconds 1
            }
            if (-not $ready) { throw 'Isolated Laravel endpoint did not return the expected unauthenticated 401; no k6 load was started.' }

            $k6Args = "run --summary-export=`"$summary`" `"$k6Script`""
            $k6Process = Start-Process -FilePath $K6Path -ArgumentList $k6Args -WorkingDirectory $repoRoot -WindowStyle Hidden -RedirectStandardOutput $stdout -RedirectStandardError $stderr -PassThru
            $previousCpu = $null
            $previousAt = Get-Date
            while (-not $k6Process.HasExited) {
                Start-Sleep -Seconds 1
                $k6Process.Refresh()
                if ($k6Process.HasExited) { break }
                try {
                    $process = Get-Process -Id $phpServer.Id -ErrorAction Stop
                    $sampleAt = Get-Date
                    $cpuPercent = $null
                    if ($null -ne $previousCpu) {
                        $seconds = [Math]::Max(0.001, ($sampleAt - $previousAt).TotalSeconds)
                        $cpuPercent = [Math]::Round((($process.CPU - $previousCpu) / $seconds / [Environment]::ProcessorCount) * 100, 2)
                    }
                    $systemCpu = $null
                    $availableMemoryMb = $null
                    try {
                        $counterSamples = (Get-Counter '\Processor(_Total)\% Processor Time','\Memory\Available MBytes' -ErrorAction Stop).CounterSamples
                        $systemCpu = [Math]::Round(($counterSamples | Where-Object { $_.Path -match 'Processor.*% Processor Time' } | Select-Object -First 1 -ExpandProperty CookedValue), 2)
                        $availableMemoryMb = [Math]::Round(($counterSamples | Where-Object { $_.Path -match 'Memory.*Available MBytes' } | Select-Object -First 1 -ExpandProperty CookedValue), 0)
                    } catch { }
                    $rows.Add([pscustomobject]@{ timestamp = $sampleAt.ToString('o'); php_pid = $process.Id; php_cpu_percent_machine = $cpuPercent; php_working_set_mb = [Math]::Round($process.WorkingSet64 / 1MB, 2); system_cpu_percent = $systemCpu; available_memory_mb = $availableMemoryMb })
                    $previousCpu = $process.CPU
                    $previousAt = $sampleAt
                } catch { }
            }
            $k6Process.WaitForExit()
            $rows | Export-Csv -LiteralPath $samples -NoTypeInformation -Encoding UTF8
            if ($k6Process.ExitCode -ne 0) { throw "k6 VU=$vu failed with exit code $($k6Process.ExitCode); inspect summary/logs before continuing." }
            & $phpCommand.Source $verifier $runDatabase $vu
            if ($LASTEXITCODE -ne 0) { throw "Post-test data integrity verification failed at VU=$vu; higher stages are blocked." }
            Write-Output "PASS VU=$vu summary=$summary process_samples=$samples"
        } finally {
            if ($k6Process -and -not $k6Process.HasExited) { Stop-Process -Id $k6Process.Id -Force -ErrorAction SilentlyContinue }
            if ($phpServer -and -not $phpServer.HasExited) { Stop-Process -Id $phpServer.Id -Force -ErrorAction SilentlyContinue }
        }

        if ($vu -ne $stages[-1]) {
            $approval = Read-Host "Review VU=$vu summary and CPU/RAM samples. Type CONTINUE to authorize the next stage, or press Enter to stop"
            if ($approval -cne 'CONTINUE') {
                $allStagesCompleted = $false
                Write-Output "STOPPED_AFTER_VU=$vu; no higher stage was run."
                break
            }
        }
    }
} finally {
    foreach ($name in $oldEnv.Keys) { [Environment]::SetEnvironmentVariable($name, $oldEnv[$name], 'Process') }
}

if ($allStagesCompleted) {
    Write-Output "ALL_REQUESTED_STAGES_PASS: $($stages -join ', ') VUs; review per-stage artifacts before treating an SLO as proven."
}
