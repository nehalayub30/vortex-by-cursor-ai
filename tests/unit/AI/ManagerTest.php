<?php
namespace VortexAI\Tests\Unit\AI;

use VortexAI\Tests\Unit\TestCase;
use VortexAI\AI\Manager;

class ManagerTest extends TestCase
{
    public function testManagerInitialization()
    {
        \ = Manager::getInstance();
        \->assertInstanceOf(Manager::class, \);
    }

    public function testAIInitialization()
    {
        \ = Manager::getInstance();
        \->assertTrue(method_exists(\, 'initializeAI'));
    }
}
