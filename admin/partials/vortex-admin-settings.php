<?php
/**
 * Settings admin page template.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define available tabs
$tabs = array(
    'general' => __( 'General', 'vortex-ai-marketplace' ),
    'artwork' => __( 'Artwork', 'vortex-ai-marketplace' ),
    'artists' => __( 'Artists', 'vortex-ai-marketplace' ),
    'payments' => __( 'Payments', 'vortex-ai-marketplace' ),
    'blockchain' => __( 'Blockchain', 'vortex-ai-marketplace' ),
    'ai' => __( 'AI Models', 'vortex-ai-marketplace' ),
    'advanced' => __( 'Advanced', 'vortex-ai-marketplace' ),
);

?>
<div class="wrap vortex-admin-wrap">
    <div class="vortex-admin-header">
        <div class="vortex-admin-logo">
            <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ) . 'images/vortex-logo.png'; ?>" alt="VORTEX AI Marketplace">
        </div>
        <div class="vortex-admin-header-content">
            <h1><?php esc_html_e( 'Marketplace Settings', 'vortex-ai-marketplace' ); ?></h1>
            <p><?php esc_html_e( 'Configure your AI-powered digital art marketplace', 'vortex-ai-marketplace' ); ?></p>
        </div>
    </div>

    <div class="vortex-tabs" id="vortex-settings-tabs">
        <ul class="vortex-tabs-nav">
            <?php foreach ( $tabs as $tab_id => $tab_name ) : ?>
                <li<?php echo $current_tab === $tab_id ? ' class="ui-tabs-active"' : ''; ?> data-tab="<?php echo esc_attr( $tab_id ); ?>">
                    <a href="#tab-<?php echo esc_attr( $tab_id ); ?>"><?php echo esc_html( $tab_name ); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Tab Content -->
        <?php foreach ( $tabs as $tab_id => $tab_name ) : ?>
            <div id="tab-<?php echo esc_attr( $tab_id ); ?>" class="vortex-tab-content<?php echo $current_tab === $tab_id ? ' ui-tabs-active' : ''; ?>">
                <?php 
                // Include the appropriate tab template
                $tab_template = 'settings/' . $tab_id . '-settings.php';
                if ( file_exists( plugin_dir_path( __FILE__ ) . $tab_template ) ) {
                    include( $tab_template );
                } else {
                    echo '<p>' . esc_html__( 'Tab content not found.', 'vortex-ai-marketplace' ) . '</p>';
                }
                ?>
            </div>
        <?php endforeach; ?>
    </div>
</div> 