<?php
/**
 * Artists Settings Template
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials/settings
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Add nonce verification and settings save handler
if (isset($_POST['vortex_artists_save_settings']) && check_admin_referer('vortex_artists_settings_nonce')) {
    // Sanitize and save settings
    $artists_settings = array(
        'ai_assistance_level' => sanitize_text_field($_POST['vortex_artists_ai_level'] ?? 'balanced'),
        'huraii_integration' => isset($_POST['vortex_artists_huraii_enabled']),
        'cloe_integration' => isset($_POST['vortex_artists_cloe_enabled']),
        'auto_curation' => isset($_POST['vortex_artists_auto_curation']),
        'commission_rate' => floatval($_POST['vortex_artists_commission'] ?? 10),
        'verification_required' => isset($_POST['vortex_artists_verification']),
        'portfolio_limit' => intval($_POST['vortex_artists_portfolio_limit'] ?? 50),
        'style_categories' => sanitize_text_field($_POST['vortex_artists_style_categories'] ?? '')
    );
    
    update_option('vortex_artists_settings', $artists_settings);
    add_settings_error('vortex_messages', 'vortex_artists_message', 
        __('Artists Settings Saved Successfully', 'vortex-ai-marketplace'), 'updated');
}

// Get current settings
$artists_settings = get_option('vortex_artists_settings', array(
    'ai_assistance_level' => 'balanced',
    'huraii_integration' => true,
    'cloe_integration' => true,
    'auto_curation' => true,
    'commission_rate' => 10,
    'verification_required' => true,
    'portfolio_limit' => 50,
    'style_categories' => 'digital,abstract,realistic,traditional'
));

?>

<div class="wrap">
    <h2><?php echo esc_html__('Artists Management Settings', 'vortex-ai-marketplace'); ?></h2>
    <?php settings_errors('vortex_messages'); ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('vortex_artists_settings_nonce'); ?>

        <div class="vortex-section">
            <h3><?php esc_html_e('AI Integration Settings', 'vortex-ai-marketplace'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="vortex_artists_ai_level">
                            <?php esc_html_e('AI Assistance Level', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <select id="vortex_artists_ai_level" name="vortex_artists_ai_level">
                            <option value="minimal" <?php selected($artists_settings['ai_assistance_level'], 'minimal'); ?>>
                                <?php esc_html_e('Minimal', 'vortex-ai-marketplace'); ?>
                            </option>
                            <option value="balanced" <?php selected($artists_settings['ai_assistance_level'], 'balanced'); ?>>
                                <?php esc_html_e('Balanced', 'vortex-ai-marketplace'); ?>
                            </option>
                            <option value="enhanced" <?php selected($artists_settings['ai_assistance_level'], 'enhanced'); ?>>
                                <?php esc_html_e('Enhanced', 'vortex-ai-marketplace'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Controls how much AI assistance is provided to artists', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('AI Agents', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_artists_huraii_enabled" 
                                   value="1" 
                                   <?php checked($artists_settings['huraii_integration']); ?>>
                            <?php esc_html_e('Enable HURAII Integration', 'vortex-ai-marketplace'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_artists_cloe_enabled" 
                                   value="1" 
                                   <?php checked($artists_settings['cloe_integration']); ?>>
                            <?php esc_html_e('Enable CLOE Integration', 'vortex-ai-marketplace'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_artists_auto_curation" 
                                   value="1" 
                                   <?php checked($artists_settings['auto_curation']); ?>>
                            <?php esc_html_e('Enable AI Auto-Curation', 'vortex-ai-marketplace'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <div class="vortex-section">
            <h3><?php esc_html_e('Artist Management', 'vortex-ai-marketplace'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="vortex_artists_commission">
                            <?php esc_html_e('Commission Rate (%)', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="vortex_artists_commission" 
                               name="vortex_artists_commission" 
                               value="<?php echo esc_attr($artists_settings['commission_rate']); ?>"
                               min="0" 
                               max="100" 
                               step="0.1">
                        <p class="description">
                            <?php esc_html_e('Platform commission percentage on artist sales', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="vortex_artists_verification">
                            <?php esc_html_e('Artist Verification', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="vortex_artists_verification" 
                                   name="vortex_artists_verification" 
                                   value="1" 
                                   <?php checked($artists_settings['verification_required']); ?>>
                            <?php esc_html_e('Require Artist Verification', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Artists must be verified before listing artwork', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="vortex_artists_portfolio_limit">
                            <?php esc_html_e('Portfolio Limit', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="vortex_artists_portfolio_limit" 
                               name="vortex_artists_portfolio_limit" 
                               value="<?php echo esc_attr($artists_settings['portfolio_limit']); ?>"
                               min="1" 
                               max="1000">
                        <p class="description">
                            <?php esc_html_e('Maximum number of artworks per artist portfolio', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="vortex_artists_style_categories">
                            <?php esc_html_e('Style Categories', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" 
                               id="vortex_artists_style_categories" 
                               name="vortex_artists_style_categories" 
                               value="<?php echo esc_attr($artists_settings['style_categories']); ?>"
                               class="large-text">
                        <p class="description">
                            <?php esc_html_e('Comma-separated list of available art style categories', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="vortex-shortcode-reference">
            <h3><?php esc_html_e('Artist Shortcodes Reference', 'vortex-ai-marketplace'); ?></h3>
            <table class="vortex-shortcode-list">
                <tr>
                    <th><?php esc_html_e('Shortcode', 'vortex-ai-marketplace'); ?></th>
                    <th><?php esc_html_e('Description', 'vortex-ai-marketplace'); ?></th>
                    <th><?php esc_html_e('Parameters', 'vortex-ai-marketplace'); ?></th>
                </tr>
                <tr>
                    <td><code>[vortex_artist_profile]</code></td>
                    <td><?php esc_html_e('Displays artist profile', 'vortex-ai-marketplace'); ?></td>
                    <td><code>id</code>, <code>style</code></td>
                </tr>
                <tr>
                    <td><code>[vortex_artist_portfolio]</code></td>
                    <td><?php esc_html_e('Shows artist portfolio gallery', 'vortex-ai-marketplace'); ?></td>
                    <td><code>id</code>, <code>limit</code>, <code>category</code></td>
                </tr>
                <tr>
                    <td><code>[vortex_artist_stats]</code></td>
                    <td><?php esc_html_e('Displays artist statistics', 'vortex-ai-marketplace'); ?></td>
                    <td><code>id</code>, <code>metrics</code></td>
                </tr>
            </table>
        </div>

        <div class="vortex-submit-section">
            <input type="submit" 
                   name="vortex_artists_save_settings" 
                   class="button button-primary" 
                   value="<?php esc_attr_e('Save Artists Settings', 'vortex-ai-marketplace'); ?>">
        </div>
    </form>
</div>

<style>
.vortex-section {
    margin: 20px 0;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.vortex-section h3 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.vortex-shortcode-list {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.vortex-shortcode-list th,
.vortex-shortcode-list td {
    padding: 8px;
    text-align: left;
    border: 1px solid #ddd;
}

.vortex-shortcode-list th {
    background-color: #f8f9fa;
}

.vortex-submit-section {
    margin-top: 20px;
    padding: 20px 0;
    border-top: 1px solid #ddd;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Form change tracking
    var formChanged = false;
    
    $('form input, form select, form textarea').on('change', function() {
        formChanged = true;
    });
    
    $(window).on('beforeunload', function() {
        if (formChanged) {
            return '<?php echo esc_js(__('You have unsaved changes. Are you sure you want to leave?', 'vortex-ai-marketplace')); ?>';
        }
    });
    
    $('form').on('submit', function() {
        formChanged = false;
    });

    // Initialize tag-style input for style categories
    $('#vortex_artists_style_categories').on('keyup', function(e) {
        if (e.key === ',') {
            var value = $(this).val().replace(/\s*,\s*/g, ',');
            $(this).val(value);
        }
    });
});
</script> 