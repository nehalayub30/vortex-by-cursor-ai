<?php
/**
 * VORTEX Marketplace DAO Commercial Components
 *
 * This directory contains files and classes related to the DAO commercial features
 * of the VORTEX Marketplace, including token management, investment handling, and governance.
 *
 * @package VORTEX_Marketplace
 * @subpackage Commercial/DAO
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Files in this directory:
// - class-vortex-dao-token.php: Manages TOLA token functionality including balances, voting weight, vesting
// - class-vortex-dao-investment.php: Handles investor applications, updates, and dividend distribution
// - class-vortex-dao-manager.php: Manages DAO governance, proposals, and voting functionality 