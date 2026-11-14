param(
    [switch] $InitializeIsolatedDatabase,
    [switch] $ConfirmDisposableDatabaseCreation
)

$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
$backend = Join-Path $repoRoot 'be'
$databaseDirectory = Join-Path $backend 'database/performance-test'
$databaseFile = Join-Path $databaseDirectory 'template.sqlite'
$fixtureFile = Join-Path $PSScriptRoot 'data/students.json'

if (-not $InitializeIsolatedDatabase -or -not $ConfirmDisposableDatabaseCreation) {
    Write-Output 'DRY RUN: no directory, database, migration, credential fixture, or server will be created.'
    Write-Output "Would create only: $databaseFile"
    Write-Output "Would run Laravel migrations with DB_CONNECTION=sqlite and that exact file, then PerformanceTestSeeder."
    Write-Output "Would create test-only credentials at: $fixtureFile"
    Write-Output 'To authorize those local test-only operations, run with -InitializeIsolatedDatabase -ConfirmDisposableDatabaseCreation.'
    exit 0
}

if (Test-Path (Join-Path $backend 'bootstrap/cache/config.php')) {
    throw 'Refusing setup: Laravel config cache exists. Do not clear it automatically; use an isolated app copy or review first.'
}
if (Test-Path $databaseFile) { throw "Refusing to overwrite existing test DB: $databaseFile" }
if (Test-Path $fixtureFile) { throw "Refusing to overwrite existing test credentials: $fixtureFile" }
if (Get-NetTCPConnection -State Listen -LocalPort 8001 -ErrorAction SilentlyContinue) {
    throw 'Refusing setup while any process is listening on port 8001.'
}
if (Get-NetTCPConnection -State Listen -LocalPort 1 -ErrorAction SilentlyContinue) {
    throw 'Safety guard port 1 is unexpectedly listening; refusing to proceed.'
}

$phpCommand = Get-Command php -ErrorAction Stop
if (-not ((& $phpCommand.Source -m) -contains 'pdo_sqlite')) {
    throw 'PHP pdo_sqlite is not enabled.'
}

$expectedDatabase = [IO.Path]::GetFullPath($databaseFile)
$oldEnv = @{}
$values = @{
    APP_ENV = 'performance'
    APP_DEBUG = 'false'
    APP_KEY = 'base64:' + [Convert]::ToBase64String([byte[]](1..32))
    DB_CONNECTION = 'sqlite'
    DB_DATABASE = $expectedDatabase
    DB_URL = ''
    DATABASE_URL = ''
    DB_HOST = '127.0.0.1'
    DB_PORT = '1'
    DB_USERNAME = 'performance_disabled'
    DB_PASSWORD = ''
    SESSION_DRIVER = 'array'
    CACHE_STORE = 'array'
    BROADCAST_CONNECTION = 'null'
    QUEUE_CONNECTION = 'sync'
    BCRYPT_ROUNDS = '4'
    LOG_CHANNEL = 'stderr'
}
foreach ($key in $values.Keys) {
    $oldEnv[$key] = [Environment]::GetEnvironmentVariable($key, 'Process')
    [Environment]::SetEnvironmentVariable($key, [string] $values[$key], 'Process')
}

try {
    New-Item -ItemType Directory -Path $databaseDirectory -Force | Out-Null
    New-Item -ItemType File -Path $databaseFile | Out-Null
    New-Item -ItemType Directory -Path (Split-Path -Parent $fixtureFile) -Force | Out-Null

    Push-Location $backend
    try {
        & $phpCommand.Source artisan migrate --env=performance --force
        if ($LASTEXITCODE -ne 0) { throw "Isolated migration failed with exit code $LASTEXITCODE. Keep the partial disposable database for inspection." }
        & $phpCommand.Source artisan db:seed --env=performance --class='Database\Seeders\PerformanceTestSeeder' --force
        if ($LASTEXITCODE -ne 0) { throw "Performance fixture seeding failed with exit code $LASTEXITCODE. Keep the disposable DB for inspection." }
    } finally { Pop-Location }

    & $phpCommand.Source (Join-Path $PSScriptRoot 'verify-sqlite.php') $databaseFile
    if ($LASTEXITCODE -ne 0) { throw 'Seeded SQLite dataset did not pass the read-only isolation check.' }
    Write-Output 'SETUP=PASS. No real MySQL database was targeted.'
} finally {
    foreach ($key in $oldEnv.Keys) {
        [Environment]::SetEnvironmentVariable($key, $oldEnv[$key], 'Process')
    }
}
