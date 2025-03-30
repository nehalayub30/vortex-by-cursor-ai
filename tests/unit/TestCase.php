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
