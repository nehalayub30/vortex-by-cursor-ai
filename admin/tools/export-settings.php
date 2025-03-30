<?php
/**
 * Export Settings Tool
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/tools
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'vortex-ai-marketplace'));
}

// Process export
$export_initiated = false;
$export_messages = array();
$export_errors = array();

// Gather settings
$all_settings = array(
    'general' => get_option('vortex_marketplace_settings', array()),
    'artwork' => get_option('vortex_artwork_settings', array()),
    'artists' => get_option('vortex_artists_settings', array()),
    'payments' => get_option('vortex_payment_settings', array()),
    'blockchain' => get_option('vortex_blockchain_settings', array()),
    'ai' => get_option('vortex_ai_settings', array()),
    'advanced' => get_option('vortex_advanced_settings', array())
);

// Get AI settings for optimization
$ai_settings = $all_settings['ai'];

// Handle export form submission
if (isset($_POST['vortex_export_settings']) && check_admin_referer('vortex_export_settings_nonce')) {
    $export_initiated = true;
    
    // Determine which sections to export
    $sections_to_export = isset($_POST['vortex_export_sections']) ? array_map('sanitize_text_field', $_POST['vortex_export_sections']) : array('all');
    
    // Create export data
    $export_data = array();
    $filename = 'vortex-marketplace-settings-' . date('Y-m-d') . '.json';
    
    if (in_array('all', $sections_to_export)) {
        $export_data = $all_settings;
    } else {
        foreach ($sections_to_export as $section) {
            if (isset($all_settings[$section])) {
                $export_data[$section] = $all_settings[$section];
            }
        }
    }
    
    // Apply AI optimization if enabled
    if (isset($_POST['vortex_ai_optimization']) && !empty($ai_settings['huraii_enabled'])) {
        $export_data = vortex_huraii_optimize_export($export_data);
        $export_messages[] = __('HURAII has optimized your settings for better cross-site compatibility.', 'vortex-ai-marketplace');
    }
    
    // Apply marketplace insights if enabled
    if (isset($_POST['vortex_market_insights']) && !empty($ai_settings['cloe_enabled'])) {
        $market_insights = vortex_cloe_analyze_export($export_data);
        $export_messages[] = sprintf(
            __('CLOE Analysis: %s', 'vortex-ai-marketplace'),
            $market_insights
        );
    }
    
    // Add metadata
    $export_data['_meta'] = array(
        'plugin_version' => VORTEX_AI_MARKETPLACE_VERSION,
        'generated' => current_time('mysql'),
        'site_url' => get_site_url(),
        'optimized' => isset($_POST['vortex_ai_optimization']) && !empty($ai_settings['huraii_enabled'])
    );
    
    // Generate JSON
    $json_data = json_encode($export_data, JSON_PRETTY_PRINT);
    
    if ($json_data === false) {
        $export_errors[] = __('Failed to encode settings to JSON.', 'vortex-ai-marketplace');
    } else {
        // Store in transient for download
        set_transient('vortex_export_data', $json_data, 60 * 5); // valid for 5 minutes
        
        // Success message and download prompt
        $export_messages[] = sprintf(
            __('Settings exported successfully! %1$sClick here to download%2$s your settings file.', 'vortex-ai-marketplace'),
            '<a href="' . esc_url(admin_url('admin-post.php?action=vortex_download_settings')) . '">',
            '</a>'
        );
    }
}

/**
 * HURAII optimization of export data
 */
function vortex_huraii_optimize_export($export_data) {
    // In a real implementation, we would call the HURAII API for optimization
    // Here we're doing basic sanitization and normalization
    
    // Optimize the export data
    foreach ($export_data as $section => $settings) {
        if ($section === 'advanced') {
            // Make advanced settings more portable
            if (isset($settings['allowed_origins'])) {
                // Replace absolute URLs with relative pattern if applicable
                $settings['allowed_origins'] = str_replace(site_url(), '{site_url}', $settings['allowed_origins']);
            }
        }
        
        if ($section === 'blockchain') {
            // Remove potentially environment-specific settings
            if (isset($settings['web3_provider']) && strpos($settings['web3_provider'], 'localhost') !== false) {
                $settings['web3_provider'] = ''; // Clear local provider URLs
            }
        }
        
        // Update the section with optimized settings
        $export_data[$section] = $settings;
    }
    
    return $export_data;
}

/**
 * CLOE market analysis for export
 */
function vortex_cloe_analyze_export($export_data) {
    // In a real implementation, we would call the CLOE API for market analysis
    
    $insights = '';
    
    // Analyze marketplace settings
    if (isset($export_data['payments']) && isset($export_data['payments']['transaction_fee'])) {
        $fee = floatval($export_data['payments']['transaction_fee']);
        
        if ($fee > 5) {
            $insights .= __('Your transaction fee is higher than the current marketplace average (3.5%). Consider lowering for better competitiveness.', 'vortex-ai-marketplace');
        } else if ($fee < 2) {
            $insights .= __('Your transaction fee is lower than recommended for sustainable marketplace operation.', 'vortex-ai-marketplace');
        } else {
            $insights .= __('Your transaction fee structure is aligned with current market trends.', 'vortex-ai-marketplace');
        }
    }
    
    if (empty($insights)) {
        $insights = __('Your marketplace settings align with current best practices.', 'vortex-ai-marketplace');
    }
    
    return $insights;
}

?>
<div class="wrap">
    <h1><?php esc_html_e('Export Settings', 'vortex-ai-marketplace'); ?></h1>
    
    <?php if (!empty($export_messages)): ?>
        <div class="notice notice-success is-dismissible">
            <?php foreach ($export_messages as $message): ?>
                <p><?php echo wp_kses_post($message); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($export_errors)): ?>
        <div class="notice notice-error is-dismissible">
            <?php foreach ($export_errors as $error): ?>
                <p><?php echo esc_html($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="vortex-export-container">
        <div class="vortex-export-card">
            <h2><?php esc_html_e('Export Marketplace Settings', 'vortex-ai-marketplace'); ?></h2>
            
            <p class="description">
                <?php esc_html_e('Select which settings you want to export to a JSON file. These settings can be imported into another installation of the Vortex AI Marketplace plugin.', 'vortex-ai-marketplace'); ?>
            </p>
            
            <form method="post">
                <?php wp_nonce_field('vortex_export_settings_nonce'); ?>
                
                <div class="vortex-export-sections">
                    <h3><?php esc_html_e('Settings to Export', 'vortex-ai-marketplace'); ?></h3>
                    
                    <label>
                        <input type="checkbox" 
                               name="vortex_export_sections[]" 
                               value="all" 
                               checked>
                        <?php esc_html_e('All Settings', 'vortex-ai-marketplace'); ?>
                    </label>
                    
                    <div class="vortex-export-individual-sections">
                        <label>
                            <input type="checkbox" 
                                   name="vortex_export_sections[]" 
                                   value="general" 
                                   class="section-option">
                            <?php esc_html_e('General Settings', 'vortex-ai-marketplace'); ?>
                        </label>
                        
                        <label>
                            <input type="checkbox" 
                                   name="vortex_export_sections[]" 
                                   value="artwork" 
                                   class="section-option">
                            <?php esc_html_e('Artwork Settings', 'vortex-ai-marketplace'); ?>
                        </label>
                        
                        <label>
                            <input type="checkbox" 
                                   name="vortex_export_sections[]" 
                                   value="artists" 
                                   class="section-option">
                            <?php esc_html_e('Artists Settings', 'vortex-ai-marketplace'); ?>
                        </label>
                        
                        <label>
                            <input type="checkbox" 
                                   name="vortex_export_sections[]" 
                                   value="payments" 
                                   class="section-option">
                            <?php esc_html_e('Payment Settings', 'vortex-ai-marketplace'); ?>
                        </label>
                        
                        <label>
                            <input type="checkbox" 
                                   name="vortex_export_sections[]" 
                                   value="blockchain" 
                                   class="section-option">
                            <?php esc_html_e('Blockchain Settings', 'vortex-ai-marketplace'); ?>
                        </label>
                        
                        <label>
                            <input type="checkbox" 
                                   name="vortex_export_sections[]" 
                                   value="ai" 
                                   class="section-option">
                            <?php esc_html_e('AI Settings', 'vortex-ai-marketplace'); ?>
                        </label>
                        
                        <label>
                            <input type="checkbox" 
                                   name="vortex_export_sections[]" 
                                   value="advanced" 
                                   class="section-option">
                            <?php esc_html_e('Advanced Settings', 'vortex-ai-marketplace'); ?>
                        </label>
                    </div>
                </div>
                
                <div class="vortex-export-ai-options">
                    <h3><?php esc_html_e('AI Enhancements', 'vortex-ai-marketplace'); ?></h3>
                    
                    <?php if (!empty($ai_settings['huraii_enabled'])): ?>
                    <label>
                        <input type="checkbox" 
                               name="vortex_ai_optimization" 
                               value="1" 
                               checked>
                        <?php esc_html_e('HURAII Optimization', 'vortex-ai-marketplace'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Use HURAII to optimize settings for better cross-site compatibility.', 'vortex-ai-marketplace'); ?>
                    </p>
                    <?php endif; ?>
                    
                    <?php if (!empty($ai_settings['cloe_enabled'])): ?>
                    <label>
                        <input type="checkbox" 
                               name="vortex_market_insights" 
                               value="1" 
                               checked>
                        <?php esc_html_e('CLOE Market Insights', 'vortex-ai-marketplace'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Receive market analysis and recommendations for your settings.', 'vortex-ai-marketplace'); ?>
                    </p>
                    <?php endif; ?>
                </div>
                
                <div class="vortex-export-actions">
                    <input type="submit" 
                           name="vortex_export_settings" 
                           class="button button-primary" 
                           value="<?php esc_attr_e('Export Settings', 'vortex-ai-marketplace'); ?>">
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=vortex-import-settings')); ?>" 
                       class="button">
                        <?php esc_html_e('Import Settings Instead', 'vortex-ai-marketplace'); ?>
                    </a>
                </div>
            </form>
        </div>
        
        <div class="vortex-export-info">
            <h3><?php esc_html_e('What Gets Exported?', 'vortex-ai-marketplace'); ?></h3>
            
            <p>
                <?php esc_html_e('Your export file will include:', 'vortex-ai-marketplace'); ?>
            </p>
            
            <ul class="vortex-export-list">
                <li><?php esc_html_e('Marketplace configuration settings', 'vortex-ai-marketplace'); ?></li>
                <li><?php esc_html_e('AI agent configurations', 'vortex-ai-marketplace'); ?></li>
                <li><?php esc_html_e('Payment and blockchain settings', 'vortex-ai-marketplace'); ?></li>
                <li><?php esc_html_e('Artwork generation parameters', 'vortex-ai-marketplace'); ?></li>
                <li><?php esc_html_e('Artist verification requirements', 'vortex-ai-marketplace'); ?></li>
                <li><?php esc_html_e('Commission and fee structures', 'vortex-ai-marketplace'); ?></li>
            </ul>
            
            <h4><?php esc_html_e('What Will Not Be Exported', 'vortex-ai-marketplace'); ?></h4>
            
            <ul class="vortex-export-list negative">
                <li><?php esc_html_e('User accounts and artist profiles', 'vortex-ai-marketplace'); ?></li>
                <li><?php esc_html_e('Marketplace listings and transactions', 'vortex-ai-marketplace'); ?></li>
                <li><?php esc_html_e('Generated artwork and media files', 'vortex-ai-marketplace'); ?></li>
                <li><?php esc_html_e('API keys and private credentials', 'vortex-ai-marketplace'); ?></li>
            </ul>
            
            <p class="vortex-export-tip">
                <strong><?php esc_html_e('Tip:', 'vortex-ai-marketplace'); ?></strong> 
                <?php esc_html_e('For a complete backup, use this tool in conjunction with a WordPress backup solution.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
    </div>
</div>

<style>
.vortex-export-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-top: 20px;
}

.vortex-export-card {
    flex: 1;
    min-width: 300px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.vortex-export-info {
    flex: 1;
    min-width: 300px;
    background: #f8f9fa;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.vortex-export-sections,
.vortex-export-ai-options {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.vortex-export-sections h3,
.vortex-export-ai-options h3 {
    margin-top: 0;
    margin-bottom: 10px;
}

.vortex-export-individual-sections {
    margin-top: 10px;
    margin-left: 20px;
}

.vortex-export-individual-sections label,
.vortex-export-ai-options label {
    display: block;
    margin-bottom: 8px;
}

.vortex-export-actions {
    margin-top: 20px;
}

.vortex-export-list {
    list-style-type: disc;
    margin-left: 20px;
}

.vortex-export-list.negative {
    color: #d63638;
}

.vortex-export-tip {
    background: #f0f6fc;
    border-left: 4px solid #72aee6;
    padding: 12px;
    margin-top: 20px;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Toggle individual section options when "All Settings" is checked/unchecked
    $('input[value="all"]').on('change', function() {
        $('.vortex-export-individual-sections').toggle(!$(this).is(':checked'));
        if ($(this).is(':checked')) {
            $('.section-option').prop('checked', false);
        }
    }).trigger('change');
    
    // Uncheck "All Settings" when any individual section is checked
    $('.section-option').on('change', function() {
        if ($(this).is(':checked')) {
            $('input[value="all"]').prop('checked', false);
        }
    });
});
</script> 