param(
    [ValidateSet('Test', 'BrowserUp', 'BrowserDown')]
    [string] $Mode = 'Test',

    [ValidateRange(1024, 65535)]
    [int] $Port = 8081,

    [switch] $StopInfrastructure
)

$ErrorActionPreference = 'Stop'
$ProjectRoot = Split-Path -Parent $PSScriptRoot
$BrowserContainer = 'sgd-chorombo-fases1-2-qa-browser'
$AmbiguousTest = 'test_hu01_convivencia_no_accede_a_correspondencia_general'

function Invoke-Docker {
    param([string[]] $Arguments)

    & docker @Arguments

    if ($LASTEXITCODE -ne 0) {
        throw "Docker command failed with exit code ${LASTEXITCODE}: docker $($Arguments -join ' ')"
    }
}

function Get-QaEnvironment {
    param([bool] $Browser)

    $environment = @(
        '-e', 'APP_ENV=testing',
        '-e', 'APP_MAINTENANCE_DRIVER=file',
        '-e', 'BCRYPT_ROUNDS=4',
        '-e', 'BROADCAST_CONNECTION=null',
        '-e', 'DB_CONNECTION=mysql',
        '-e', 'DB_HOST=mysql',
        '-e', 'DB_PORT=3306',
        '-e', 'DB_DATABASE=testing',
        '-e', 'DB_URL=',
        '-e', 'QUEUE_CONNECTION=sync',
        '-e', 'PULSE_ENABLED=false',
        '-e', 'TELESCOPE_ENABLED=false',
        '-e', 'NIGHTWATCH_ENABLED=false'
    )

    if ($Browser) {
        return $environment + @(
            '-e', 'QA_BROWSER_PASSWORD',
            '-e', 'CACHE_STORE=file',
            '-e', 'MAIL_MAILER=log',
            '-e', 'SESSION_DRIVER=database',
            '-e', 'SUPERVISOR_PHP_COMMAND=/usr/bin/php -d variables_order=EGPCS /var/www/html/artisan serve --host=0.0.0.0 --port=80 --no-reload',
            '-e', "APP_URL=http://127.0.0.1:$Port"
        )
    }

    return $environment + @(
        '-e', 'CACHE_STORE=array',
        '-e', 'MAIL_MAILER=array',
        '-e', 'SESSION_DRIVER=array'
    )
}

function Get-MysqlIdentity {
    $containerId = (& docker compose ps -q mysql).Trim()

    if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($containerId)) {
        throw 'The Compose mysql container is not running.'
    }

    for ($attempt = 0; $attempt -lt 60; $attempt++) {
        $inspection = (& docker inspect $containerId | ConvertFrom-Json)[0]
        $health = $inspection.State.Health.Status

        if ($health -eq 'healthy') {
            $project = $inspection.Config.Labels.'com.docker.compose.project'
            $service = $inspection.Config.Labels.'com.docker.compose.service'

            if ($service -ne 'mysql') {
                throw "Expected Compose service mysql, received $service."
            }

            return @{
                Hostname = $inspection.Config.Hostname
                Project = $project
                Service = $service
            }
        }

        Start-Sleep -Seconds 1
    }

    throw 'The Compose mysql container did not become healthy within 60 seconds.'
}

function Invoke-QaPreflight {
    param(
        [string[]] $Environment,
        [hashtable] $MysqlIdentity
    )

    $arguments = @('compose', 'run', '--rm', '-T') + $Environment + @(
        '-e', "QA_EXPECTED_SERVER_HOSTNAME=$($MysqlIdentity.Hostname)",
        '-e', "QA_EXPECTED_COMPOSE_PROJECT=$($MysqlIdentity.Project)",
        '-e', "QA_EXPECTED_COMPOSE_SERVICE=$($MysqlIdentity.Service)",
        'laravel.test',
        'php',
        'tests/preflight.fases1-2-qa.php'
    )

    Invoke-Docker -Arguments $arguments
}

function Stop-QaBrowser {
    $containerId = [string] (& docker ps -a --filter "name=^/$BrowserContainer$" --format '{{.ID}}')

    if ([string]::IsNullOrWhiteSpace($containerId)) {
        Write-Output 'QA_BROWSER_ALREADY_STOPPED'
        return
    }

    $inspection = (& docker inspect $containerId | ConvertFrom-Json)[0]
    $isTesting = $inspection.Config.Env -contains 'APP_ENV=testing'
    $service = $inspection.Config.Labels.'com.docker.compose.service'

    if (-not $isTesting -or $service -ne 'laravel.test') {
        throw "Refusing to remove container $BrowserContainer because it is not the isolated testing service."
    }

    Invoke-Docker -Arguments @('rm', '-f', $BrowserContainer)
    Write-Output 'QA_BROWSER_STOPPED'
}

Push-Location $ProjectRoot

try {
    if ($Mode -eq 'BrowserDown') {
        Stop-QaBrowser

        if ($StopInfrastructure) {
            Invoke-Docker -Arguments @('compose', 'stop', 'mysql', 'mailpit')
        }

        exit 0
    }

    if ($Mode -eq 'BrowserUp' -and [string]::IsNullOrWhiteSpace([Environment]::GetEnvironmentVariable('QA_BROWSER_PASSWORD'))) {
        throw 'QA_BROWSER_PASSWORD must be supplied through the process environment for BrowserUp.'
    }

    Invoke-Docker -Arguments @('compose', 'up', '-d', 'mysql', 'mailpit')
    $mysqlIdentity = Get-MysqlIdentity

    if ($Mode -eq 'Test') {
        $environment = Get-QaEnvironment -Browser $false
        Invoke-QaPreflight -Environment $environment -MysqlIdentity $mysqlIdentity
        $testingDisks = Join-Path $ProjectRoot 'storage/framework/testing/disks'
        Remove-Item -Path $testingDisks -Recurse -Force -ErrorAction SilentlyContinue
        New-Item -Path $testingDisks -ItemType Directory -Force | Out-Null
        Write-Output "QA_TEST_EXCLUDED_AMBIGUOUS=$AmbiguousTest"

        $arguments = @('compose', 'run', '--rm', '-T') + $environment + @(
            'laravel.test',
            'php',
            'vendor/bin/phpunit',
            '--configuration', 'tests/phpunit.fases1-2-qa.xml',
            '--testsuite', 'fases1-2-qa',
            '--exclude-filter', $AmbiguousTest,
            '--testdox',
            '--no-progress',
            '--do-not-cache-result',
            '--colors=never',
            '--fail-on-risky'
        )

        Invoke-Docker -Arguments $arguments
        exit 0
    }


    $browserEnvironment = Get-QaEnvironment -Browser $true
    Invoke-QaPreflight -Environment $browserEnvironment -MysqlIdentity $mysqlIdentity

    $existingBrowser = [string] (& docker ps -a --filter "name=^/$BrowserContainer$" --format '{{.ID}}')

    if (-not [string]::IsNullOrWhiteSpace($existingBrowser)) {
        throw "QA browser container already exists. Run this script with -Mode BrowserDown first."
    }

    Invoke-Docker -Arguments (@('compose', 'run', '--rm', '-T') + $browserEnvironment + @(
        'laravel.test',
        'php',
        'artisan',
        'migrate:fresh',
        '--force',
        '--no-interaction'
    ))

    Invoke-Docker -Arguments (@('compose', 'run', '--rm', '-T') + $browserEnvironment + @(
        'laravel.test',
        'php',
        'tests/seed.fases1-2-qa.php'
    ))

    Invoke-Docker -Arguments (@('compose', 'run', '--rm', '-d', '--name', $BrowserContainer, '-p', "127.0.0.1:$Port`:80") + $browserEnvironment + @(
        'laravel.test'
    ))

    $loginUrl = "http://127.0.0.1:$Port/login"

    for ($attempt = 0; $attempt -lt 20; $attempt++) {
        & curl.exe --max-time 30 --silent --fail --output NUL $loginUrl

        if ($LASTEXITCODE -eq 0) {
            Write-Output "QA_BROWSER_READY=$loginUrl"
            Write-Output 'QA_BROWSER_PASSWORD_SOURCE=environment'
            Write-Output 'QA_BROWSER_ACCOUNTS=administracion.qa@example.test,directora.qa@example.test,secretaria.qa@example.test,utp.qa@example.test,convivencia.qa@example.test'
            exit 0
        }

        Start-Sleep -Seconds 1
    }

    throw "The isolated QA browser environment did not become ready at $loginUrl."
} finally {
    Pop-Location
}
