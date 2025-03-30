<?php
/**
 * TOLA Token API
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/api
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * TOLA Token API Class
 *
 * Provides methods for interacting with the TOLA token contract.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/api
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Tola_API extends Vortex_API {

    /**
     * The blockchain API instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var
 */ 