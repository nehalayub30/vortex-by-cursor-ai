<?php
namespace VortexAI\Tests\Unit\Core;

use VortexAI\Tests\Unit\TestCase;
use VortexAI\Core\Plugin;

class PluginTest extends TestCase
{
    public function testPluginInitialization()
    {
        \ = Plugin::getInstance();
        \->assertInstanceOf(Plugin::class, \);
    }

    public function testHooksAreRegistered()
    {
        \ = Plugin::getInstance();
        \->assertTrue(has_action('plugins_loaded', [\, 'loadTextdomain']));
        \->assertTrue(has_action('admin_enqueue_scripts', [\, 'adminAssets']));
        \->assertTrue(has_action('wp_enqueue_scripts', [\, 'frontendAssets']));
    }
}
