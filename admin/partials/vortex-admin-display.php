<?php
/**
 * Provide a admin area view for the plugin
 *
 * @link       https://www.vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <div class="vortex-admin-header">
        <img src="<?php echo VORTEX_AI_MARKETPLACE_PLUGIN_URL . 'admin/images/vortex-logo.png'; ?>" alt="Vortex AI Marketplace" class="vortex-logo" />
        <p class="vortex-description">
            <?php esc_html_e( 'Configure your Vortex AI Marketplace client to connect to the SaaS backend.', 'vortex-ai-marketplace' ); ?>
        </p>
    </div>

    <?php
    // Check for connection to API
    $api_key = get_option( 'vortex_api_key' );
    $api_endpoint = get_option( 'vortex_api_endpoint', 'https://www.vortexartec.com/api/v1' );
    
    if ( ! empty( $api_key ) ) {
        $response = wp_remote_get( 
            $api_endpoint . '/health', 
            array(
                'headers' => array(
                    'X-API-Key' => $api_key,
                ),
                'timeout' => 10,
            ) 
        );

        if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
            echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Successfully connected to the Vortex AI Marketplace API.', 'vortex-ai-marketplace' ) . '</p></div>';
        } else {
            echo '<div class="notice notice-error inline"><p>' . esc_html__( 'Failed to connect to the Vortex AI Marketplace API. Please check your settings.', 'vortex-ai-marketplace' ) . '</p></div>';
        }
    }
    ?>

    <form method="post" action="options.php">
        <?php
        settings_fields( $this->plugin_name );
        do_settings_sections( $this->plugin_name );
        submit_button();
        ?>
    </form>

    <div class="vortex-admin-help">
        <h2><?php esc_html_e( 'Need Help?', 'vortex-ai-marketplace' ); ?></h2>
        <p>
            <?php
            printf(
                /* translators: %s: Documentation URL */
                esc_html__( 'For more information, please visit our %1$sdocumentation%2$s or contact %3$ssupport%4$s.', 'vortex-ai-marketplace' ),
                '<a href="https://www.vortexartec.com/documentation" target="_blank">',
                '</a>',
                '<a href="mailto:support@vortexartec.com">',
                '</a>'
            );
            ?>
        </p>
    </div>
</div> 