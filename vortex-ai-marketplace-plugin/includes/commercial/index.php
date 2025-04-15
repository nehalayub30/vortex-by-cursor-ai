<?php
/**
 * VORTEX AI Marketplace Commercial Components
 *
 * This directory contains files and classes related to the commercial aspects of the VORTEX AI Marketplace,
 * including licensing, subscription management, and premium features.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/commercial
 */

// Prevent direct access
if (!defined('WPINC')) {
    exit; // Exit if accessed directly
}

/**
 * Commercial Components
 * 
 * The commercial components include:
 * 
 * - Licensing: License management, activation/deactivation, and validation
 * - Subscription: SaaS subscription management and feature access control
 * - Updates: Secure plugin update delivery system
 */

// Load components
require_once dirname(__FILE__) . '/licensing/class-vortex-license-manager.php';
require_once dirname(__FILE__) . '/licensing/class-vortex-license-api.php';
require_once dirname(__FILE__) . '/licensing/class-vortex-update-manager.php';
require_once dirname(__FILE__) . '/licensing/class-vortex-subscription-manager.php'; 