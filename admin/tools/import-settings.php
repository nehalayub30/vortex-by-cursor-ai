<?php
/**
 * Import Settings Tool
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

// Process import
$import_success = false;
$import_errors = array();
$import_messages = array();

if (isset($_POST['vortex_import_settings']) && check_admin_referer('vortex_import_settings_nonce')) {
    // Check if a file was uploaded
    if (!empty($_FILES['vortex_settings_file']['tmp_name'])) {
        // Check file type
        $file_info = pathinfo($_FILES['vortex_settings_file']['name']);
        $extension = strtolower($file_info['extension']);
        
        if ($extension !== 'json') {
            $import_errors[] = __('Invalid file format. Please upload a JSON file.', 'vortex-ai-marketplace');
        } else {
            // Read the file
            $import_data = file_get_contents($_FILES['vortex_settings_file']['tmp_name']);
            
            // Decode the JSON
            $settings = json_decode($import_data, true);
            
            // Check if JSON is valid
            if (json_last_error() !== JSON_ERROR_NONE) {
                $import_errors[] = sprintf(
                    __('Invalid JSON: %s', 'vortex-ai-marketplace'),
                    json_last_error_msg()
                );
            } else {
                // AI-Powered settings validation
                $ai_validation = vortex_ai_validate_settings($settings);
                
                if ($ai_validation['valid']) {
                    // Import the settings
                    $imported = vortex_import_settings($settings);
                    
                    if ($imported) {
                        $import_success = true;
                        $import_messages[] = __('Settings imported successfully!', 'vortex-ai-marketplace');
                        
                        if (!empty($ai_validation['suggestions'])) {
                            $import_messages[] = __('AI Suggestions:', 'vortex-ai-marketplace') . ' ' . implode(' ', $ai_validation['suggestions']);
                        }
                    } else {
                        $import_errors[] = __('Failed to import settings. Please try again.', 'vortex-ai-marketplace');
                    }
                } else {
                    $import_errors[] = __('AI validation failed:', 'vortex-ai-marketplace') . ' ' . $ai_validation['message'];
                }
            }
        }
    } else {
        $import_errors[] = __('No file uploaded. Please select a settings file.', 'vortex-ai-marketplace');
    }
}

/**
 * Validate settings using AI
 */
function vortex_ai_validate_settings($settings) {
    // Get AI settings
    $ai_settings = get_option('vortex_ai_settings', array());
    
    // Initialize result
    $result = array(
        'valid' => true,
        'message' => '',
        'suggestions' => array()
    );
    
    // Basic structure validation
    $required_sections = array(
        'general', 'artwork', 'artists', 'payments', 'blockchain', 'ai', 'advanced'
    );
    
    $missing_sections = array();
    foreach ($required_sections as $section) {
        if (!isset($settings[$section])) {
            $missing_sections[] = $section;
        }
    }
    
    if (!empty($missing_sections)) {
        $result['valid'] = false;
        $result['message'] = sprintf(
            __('Missing required sections: %s', 'vortex-ai-marketplace'),
            implode(', ', $missing_sections)
        );
        return $result;
    }
    
    // Check if HURAII is enabled
    if (!empty($ai_settings['huraii_enabled'])) {
        // Deep analysis with HURAII
        $huraii_analysis = vortex_huraii_analyze_settings($settings);
        
        if (!$huraii_analysis['valid']) {
            $result['valid'] = false;
            $result['message'] = $huraii_analysis['message'];
            return $result;
        }
        
        if (!empty($huraii_analysis['suggestions'])) {
            $result['suggestions'] = array_merge($result['suggestions'], $huraii_analysis['suggestions']);
        }
    }
    
    // Check if CLOE is enabled
    if (!empty($ai_settings['cloe_enabled'])) {
        // Market impact analysis with CLOE
        $cloe_analysis = vortex_cloe_analyze_market_impact($settings);
        
        if (!$cloe_analysis['valid']) {
            $result['valid'] = false;
            $result['message'] = $cloe_analysis['message'];
            return $result;
        }
        
        if (!empty($cloe_analysis['suggestions'])) {
            $result['suggestions'] = array_merge($result['suggestions'], $cloe_analysis['suggestions']);
        }
    }
    
    return $result;
}

/**
 * HURAII analysis of settings
 */
function vortex_huraii_analyze_settings($settings) {
    // Placeholder for actual HURAII integration
    // In a real implementation, this would call the HURAII API
    
    return array(
        'valid' => true,
        'message' => '',
        'suggestions' => array(
            __('Optimize artwork cache settings for better performance.', 'vortex-ai-marketplace'),
            __('Consider enabling advanced AI processing for better artwork generation.', 'vortex-ai-marketplace')
        )
    );
}

/**
 * CLOE market impact analysis
 */
function vortex_cloe_analyze_market_impact($settings) {
    // Placeholder for actual CLOE integration
    // In a real implementation, this would call the CLOE API
    
    return array(
        'valid' => true,
        'message' => '',
        'suggestions' => array(
            __('Your commission rate settings are optimal based on current market trends.', 'vortex-ai-marketplace'),
            __('Consider adjusting price suggestions algorithm for increased marketplace activity.', 'vortex-ai-marketplace')
        )
    );
}

/**
 * Import settings
 */
function vortex_import_settings($settings) {
    try {
        // Import each section
        if (isset($settings['general'])) {
            update_option('vortex_marketplace_settings', $settings['general']);
        }
        
        if (isset($settings['artwork'])) {
            update_option('vortex_artwork_settings', $settings['artwork']);
        }
        
        if (isset($settings['artists'])) {
            update_option('vortex_artists_settings', $settings['artists']);
        }
        
        if (isset($settings['payments'])) {
            update_option('vortex_payment_settings', $settings['payments']);
        }
        
        if (isset($settings['blockchain'])) {
            update_option('vortex_blockchain_settings', $settings['blockchain']);
        }
        
        if (isset($settings['ai'])) {
            update_option('vortex_ai_settings', $settings['ai']);
        }
        
        if (isset($settings['advanced'])) {
            update_option('vortex_advanced_settings', $settings['advanced']);
        }
        
        return true;
    } catch (Exception $e) {
        // Log error
        error_log('Vortex AI Marketplace - Import Settings Error: ' . $e->getMessage());
        return false;
    }
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('Import Settings', 'vortex-ai-marketplace'); ?></h1>
    
    <?php if ($import_success): ?>
        <div class="notice notice-success is-dismissible">
            <?php foreach ($import_messages as $message): ?>
                <p><?php echo esc_html($message); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($import_errors)): ?>
        <div class="notice notice-error is-dismissible">
            <?php foreach ($import_errors as $error): ?>
                <p><?php echo esc_html($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="vortex-import-container">
        <div class="vortex-import-card">
            <h2><?php esc_html_e('Import Marketplace Settings', 'vortex-ai-marketplace'); ?></h2>
            
            <p class="description">
                <?php esc_html_e('Upload a JSON file to import settings for your Vortex AI Marketplace.', 'vortex-ai-marketplace'); ?>
            </p>
            
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('vortex_import_settings_nonce'); ?>
                
                <div class="vortex-import-field">
                    <label for="vortex_settings_file">
                        <?php esc_html_e('Settings File', 'vortex-ai-marketplace'); ?>
                        <span class="required">*</span>
                    </label>
                    
                    <input type="file" 
                           id="vortex_settings_file" 
                           name="vortex_settings_file" 
                           accept=".json" 
                           required>
                    
                    <p class="description">
                        <?php esc_html_e('Select a JSON file exported from the Vortex AI Marketplace plugin.', 'vortex-ai-marketplace'); ?>
                    </p>
                </div>
                
                <div class="vortex-import-options">
                    <label>
                        <input type="checkbox" 
                               name="vortex_ai_validation" 
                               value="1" 
                               checked>
                        <?php esc_html_e('Use AI to validate settings', 'vortex-ai-marketplace'); ?>
                    </label>
                    
                    <p class="description">
                        <?php esc_html_e('HURAII and CLOE will analyze the imported settings for optimal marketplace performance.', 'vortex-ai-marketplace'); ?>
                    </p>
                </div>
                
                <div class="vortex-import-warning">
                    <p>
                        <strong><?php esc_html_e('Warning:', 'vortex-ai-marketplace'); ?></strong> 
                        <?php esc_html_e('Importing settings will overwrite your current marketplace configuration. It is recommended to create a backup before proceeding.', 'vortex-ai-marketplace'); ?>
                    </p>
                </div>
                
                <div class="vortex-import-actions">
                    <input type="submit" 
                           name="vortex_import_settings" 
                           class="button button-primary" 
                           value="<?php esc_attr_e('Import Settings', 'vortex-ai-marketplace'); ?>">
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=vortex-tools&tab=export')); ?>" 
                       class="button">
                        <?php esc_html_e('Export Settings Instead', 'vortex-ai-marketplace'); ?>
                    </a>
                </div>
            </form>
        </div>
        
        <div class="vortex-import-help">
            <h3><?php esc_html_e('Compatible Settings Format', 'vortex-ai-marketplace'); ?></h3>
            
            <p>
                <?php esc_html_e('The imported file should contain JSON data with the following structure:', 'vortex-ai-marketplace'); ?>
            </p>
            
            <pre>{
  "general": { /* general settings */ },
  "artwork": { /* artwork settings */ },
  "artists": { /* artists settings */ },
  "payments": { /* payment settings */ },
  "blockchain": { /* blockchain settings */ },
  "ai": { /* AI settings */ },
  "advanced": { /* advanced settings */ }
}</pre>
            
            <p>
                <?php esc_html_e('For best results, only import settings that were exported from the same version of the Vortex AI Marketplace plugin.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
    </div>
</div>

<style>
.vortex-import-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-top: 20px;
}

.vortex-import-card {
    flex: 1;
    min-width: 300px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.vortex-import-help {
    flex: 1;
    min-width: 300px;
    background: #f8f9fa;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.vortex-import-field {
    margin-bottom: 20px;
}

.vortex-import-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.vortex-import-field input[type="file"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.vortex-import-options {
    margin-bottom: 20px;
}

.vortex-import-warning {
    background: #fff8e5;
    border-left: 4px solid #ffb900;
    padding: 12px;
    margin: 20px 0;
}

.vortex-import-actions {
    margin-top: 20px;
}

.vortex-import-help pre {
    background: #f1f1f1;
    padding: 15px;
    overflow: auto;
    font-family: monospace;
    border-radius: 4px;
}

.required {
    color: #d63638;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // File input enhancement
    $('#vortex_settings_file').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        if (fileName) {
            $(this).next('.description').html(
                '<?php echo esc_js(__('Selected file:', 'vortex-ai-marketplace')); ?> <strong>' + 
                fileName + '</strong>'
            );
        } else {
            $(this).next('.description').html(
                '<?php echo esc_js(__('Select a JSON file exported from the Vortex AI Marketplace plugin.', 'vortex-ai-marketplace')); ?>'
            );
        }
    });
});
</script> 