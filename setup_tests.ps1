# Testing Environment Setup
$ErrorActionPreference = "Stop"
$pluginRoot = Get-Location

Write-Host "Setting up testing environment for VORTEX AI AGENTS..." -ForegroundColor Yellow

try {
    # 1. Create test structure
    $testFiles = @{
        "tests/bootstrap.php" = @"
<?php
/**
 * PHPUnit bootstrap file
 */

require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';

// WordPress test environment
require_once getenv('WP_TESTS_DIR') . '/includes/functions.php';
require_once getenv('WP_TESTS_DIR') . '/includes/bootstrap.php';
"@

        "tests/unit/TestCase.php" = @"
<?php
namespace VortexAI\Tests\Unit;

use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Add common setup for all tests
    }

    protected function tearDown(): void
    {
        // Add common cleanup for all tests
        parent::tearDown();
    }
}
"@

        "tests/unit/Core/PluginTest.php" = @"
<?php
namespace VortexAI\Tests\Unit\Core;

use VortexAI\Tests\Unit\TestCase;
use VortexAI\Core\Plugin;

class PluginTest extends TestCase
{
    public function testPluginInitialization()
    {
        \$plugin = Plugin::getInstance();
        \$this->assertInstanceOf(Plugin::class, \$plugin);
    }

    public function testHooksAreRegistered()
    {
        \$plugin = Plugin::getInstance();
        \$this->assertTrue(has_action('plugins_loaded', [\$plugin, 'loadTextdomain']));
        \$this->assertTrue(has_action('admin_enqueue_scripts', [\$plugin, 'adminAssets']));
        \$this->assertTrue(has_action('wp_enqueue_scripts', [\$plugin, 'frontendAssets']));
    }
}
"@

        "tests/unit/AI/ManagerTest.php" = @"
<?php
namespace VortexAI\Tests\Unit\AI;

use VortexAI\Tests\Unit\TestCase;
use VortexAI\AI\Manager;

class ManagerTest extends TestCase
{
    public function testManagerInitialization()
    {
        \$manager = Manager::getInstance();
        \$this->assertInstanceOf(Manager::class, \$manager);
    }

    public function testAIInitialization()
    {
        \$manager = Manager::getInstance();
        \$this->assertTrue(method_exists(\$manager, 'initializeAI'));
    }
}
"@

        "phpunit.xml" = @"
<?xml version="1.0" encoding="UTF-8"?>
<phpunit
    bootstrap="tests/bootstrap.php"
    colors="true"
    verbose="true"
    stopOnFailure="false">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/integration</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">src</directory>
            <directory suffix=".php">includes</directory>
        </include>
    </coverage>
    <php>
        <env name="WP_ENV" value="testing"/>
    </php>
</phpunit>
"@

        "tests/integration/TestCase.php" = @"
<?php
namespace VortexAI\Tests\Integration;

use WP_UnitTestCase;

class TestCase extends WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Add common setup for integration tests
    }

    protected function tearDown(): void
    {
        // Add common cleanup for integration tests
        parent::tearDown();
    }
}
"@
    }

    # Create test files
    foreach ($file in $testFiles.Keys) {
        $filePath = Join-Path $pluginRoot $file
        $fileDir = Split-Path $filePath -Parent
        
        if (-not (Test-Path $fileDir)) {
            New-Item -ItemType Directory -Path $fileDir -Force | Out-Null
        }
        
        $testFiles[$file] | Out-File -FilePath $filePath -Force -Encoding UTF8
        Write-Host "Created test file: $file" -ForegroundColor Green
    }

    # Update composer.json properly
    $composerJsonPath = Join-Path $pluginRoot "composer.json"
    $composerContent = Get-Content $composerJsonPath -Raw | ConvertFrom-Json
    
    # Create require-dev if it doesn't exist
    if (-not $composerContent.PSObject.Properties['require-dev']) {
        $composerContent | Add-Member -Type NoteProperty -Name 'require-dev' -Value @{}
    }

    # Add test dependencies
    $composerContent.'require-dev' = @{
        "phpunit/phpunit" = "^9.0"
        "brain/monkey" = "^2.6"
        "yoast/phpunit-polyfills" = "^1.0"
    }

    # Save updated composer.json
    $composerContent | ConvertTo-Json -Depth 10 | Set-Content $composerJsonPath -Encoding UTF8

    Write-Host "`nUpdating Composer dependencies..." -ForegroundColor Cyan
    composer update

    Write-Host "`nTesting environment setup complete!" -ForegroundColor Green
    Write-Host "`nTest Structure:" -ForegroundColor Yellow
    Write-Host "├── tests/" -ForegroundColor Cyan
    Write-Host "│   ├── bootstrap.php     # Test initialization" -ForegroundColor Cyan
    Write-Host "│   ├── unit/            # Unit tests" -ForegroundColor Cyan
    Write-Host "│   └── integration/     # Integration tests" -ForegroundColor Cyan
    Write-Host "└── phpunit.xml          # PHPUnit configuration" -ForegroundColor Cyan

    Write-Host "`nTo run tests:" -ForegroundColor Yellow
    Write-Host "1. Run all tests:           vendor/bin/phpunit" -ForegroundColor Cyan
    Write-Host "2. Run unit tests:          vendor/bin/phpunit --testsuite Unit" -ForegroundColor Cyan
    Write-Host "3. Run integration tests:   vendor/bin/phpunit --testsuite Integration" -ForegroundColor Cyan
    Write-Host "4. Run with coverage:       vendor/bin/phpunit --coverage-html coverage" -ForegroundColor Cyan

} catch {
    Write-Host "Error: $_" -ForegroundColor Red
    Write-Host "Stack Trace: $($_.ScriptStackTrace)" -ForegroundColor Red
    exit 1
} 