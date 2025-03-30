<?php
/**
 * Plugin Name: VORTEX AI AGENTS Marketplace
 * Plugin URI: https://github.com/MarianneNems/VORTEX-AI-AGENTS
 * Description: AI-powered marketplace plugin with HURAII system integration
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Marianne Nems
 * License: GPL v2 or later
 */

defined('ABSPATH') || exit;

// Autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Core functions
require_once __DIR__ . '/includes/core/functions.php';

// Initialize plugin
add_action('plugins_loaded', 'vortex_ai_init');
