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
