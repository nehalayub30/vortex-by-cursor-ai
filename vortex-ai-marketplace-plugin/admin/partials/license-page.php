<?php
/**
 * License page template
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get license key and status
$license_key = get_option('vortex_ai_marketplace_license_key');
$license_status = get_option('vortex_ai_marketplace_license_status');
$expiry = get_option('vortex_ai_marketplace_license_key_expires');
?>

<div class="wrap vortex-license-wrap">
    <h1><?php _e('VORTEX AI Marketplace License', 'vortex-ai-marketplace'); ?></h1>
    
    <div class="vortex-license-box">
        <h2><?php _e('License Management', 'vortex-ai-marketplace'); ?></h2>
        
        <?php if ($license_status === 'valid') : ?>
            <div class="vortex-license-status vortex-license-active">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('License Active', 'vortex-ai-marketplace'); ?>
                
                <?php if (!empty($expiry)) : ?>
                    <p class="vortex-license-expiry">
                        <?php echo sprintf(__('Your license expires on %s', 'vortex-ai-marketplace'), date_i18n(get_option('date_format'), strtotime($expiry))); ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="vortex-license-status vortex-license-inactive">
                <span class="dashicons dashicons-warning"></span>
                <?php 
                switch ($license_status) {
                    case 'expired':
                        _e('License Expired', 'vortex-ai-marketplace');
                        break;
                    case 'disabled':
                        _e('License Disabled', 'vortex-ai-marketplace');
                        break;
                    case 'invalid':
                        _e('License Invalid', 'vortex-ai-marketplace');
                        break;
                    default:
                        _e('License Inactive', 'vortex-ai-marketplace');
                }
                ?>
            </div>
        <?php endif; ?>
        
        <form id="vortex-license-form" method="post" action="">
            <?php wp_nonce_field('vortex_license_nonce', 'vortex_license_nonce'); ?>
            
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="vortex_license_key"><?php _e('License Key', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="vortex_license_key" name="vortex_license_key" class="regular-text" value="<?php echo esc_attr($license_key); ?>" <?php echo ($license_status === 'valid') ? 'disabled' : ''; ?> />
                            <p class="description">
                                <?php _e('Enter your license key to activate premium features.', 'vortex-ai-marketplace'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div class="vortex-license-actions">
                <?php if ($license_status === 'valid') : ?>
                    <button type="button" id="vortex-deactivate-license" class="button button-secondary">
                        <?php _e('Deactivate License', 'vortex-ai-marketplace'); ?>
                    </button>
                <?php else : ?>
                    <button type="button" id="vortex-activate-license" class="button button-primary">
                        <?php _e('Activate License', 'vortex-ai-marketplace'); ?>
                    </button>
                <?php endif; ?>
            </div>
            
            <div id="vortex-license-message" class="vortex-license-message" style="display: none;"></div>
        </form>
    </div>
    
    <div class="vortex-license-features">
        <h2><?php _e('Premium Features', 'vortex-ai-marketplace'); ?></h2>
        
        <?php 
        $features = get_option('vortex_ai_marketplace_features', array(
            'ai_agents' => true,
            'blockchain_integration' => false,
            'advanced_analytics' => false,
            'premium_templates' => false,
            'api_access' => false
        ));
        
        $feature_descriptions = array(
            'ai_agents' => __('Basic AI Agents - Create intelligent agents for your marketplace', 'vortex-ai-marketplace'),
            'blockchain_integration' => __('Blockchain Integration - Secure transactions with blockchain technology', 'vortex-ai-marketplace'),
            'advanced_analytics' => __('Advanced Analytics - Deep insights into your marketplace performance', 'vortex-ai-marketplace'),
            'premium_templates' => __('Premium Templates - Access to exclusive marketplace templates', 'vortex-ai-marketplace'),
            'api_access' => __('API Access - Connect your marketplace to external applications', 'vortex-ai-marketplace')
        );
        ?>
        
        <table class="widefat vortex-features-table">
            <thead>
                <tr>
                    <th><?php _e('Feature', 'vortex-ai-marketplace'); ?></th>
                    <th><?php _e('Status', 'vortex-ai-marketplace'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($features as $feature => $enabled) : ?>
                    <tr>
                        <td>
                            <?php echo isset($feature_descriptions[$feature]) ? $feature_descriptions[$feature] : $feature; ?>
                        </td>
                        <td>
                            <?php if ($enabled) : ?>
                                <span class="vortex-feature-active">
                                    <span class="dashicons dashicons-yes"></span>
                                    <?php _e('Active', 'vortex-ai-marketplace'); ?>
                                </span>
                            <?php else : ?>
                                <span class="vortex-feature-inactive">
                                    <span class="dashicons dashicons-no"></span>
                                    <?php _e('Inactive', 'vortex-ai-marketplace'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (!$license_status === 'valid') : ?>
            <div class="vortex-upsell">
                <p>
                    <?php _e('Unlock all premium features by purchasing a license. Visit our website for more information.', 'vortex-ai-marketplace'); ?>
                </p>
                <a href="https://vortexmarketplace.io/pricing" target="_blank" class="button button-primary">
                    <?php _e('View Pricing', 'vortex-ai-marketplace'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Activate license
    $('#vortex-activate-license').on('click', function() {
        var license_key = $('#vortex_license_key').val();
        
        if (license_key === '') {
            showMessage('error', '<?php echo esc_js(__('Please enter a license key.', 'vortex-ai-marketplace')); ?>');
            return;
        }
        
        $(this).prop('disabled', true).text('<?php echo esc_js(__('Activating...', 'vortex-ai-marketplace')); ?>');
        
        activateLicense(license_key);
    });
    
    // Deactivate license
    $('#vortex-deactivate-license').on('click', function() {
        $(this).prop('disabled', true).text('<?php echo esc_js(__('Deactivating...', 'vortex-ai-marketplace')); ?>');
        
        deactivateLicense();
    });
    
    function activateLicense(license_key) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_activate_license',
                nonce: '<?php echo wp_create_nonce('vortex_license_nonce'); ?>',
                license_key: license_key
            },
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data);
                    
                    // Reload page after successful activation
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showMessage('error', response.data);
                    $('#vortex-activate-license').prop('disabled', false).text('<?php echo esc_js(__('Activate License', 'vortex-ai-marketplace')); ?>');
                }
            },
            error: function() {
                showMessage('error', '<?php echo esc_js(__('An error occurred. Please try again.', 'vortex-ai-marketplace')); ?>');
                $('#vortex-activate-license').prop('disabled', false).text('<?php echo esc_js(__('Activate License', 'vortex-ai-marketplace')); ?>');
            }
        });
    }
    
    function deactivateLicense() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_deactivate_license',
                nonce: '<?php echo wp_create_nonce('vortex_license_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data);
                    
                    // Reload page after successful deactivation
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showMessage('error', response.data);
                    $('#vortex-deactivate-license').prop('disabled', false).text('<?php echo esc_js(__('Deactivate License', 'vortex-ai-marketplace')); ?>');
                }
            },
            error: function() {
                showMessage('error', '<?php echo esc_js(__('An error occurred. Please try again.', 'vortex-ai-marketplace')); ?>');
                $('#vortex-deactivate-license').prop('disabled', false).text('<?php echo esc_js(__('Deactivate License', 'vortex-ai-marketplace')); ?>');
            }
        });
    }
    
    function showMessage(type, message) {
        var $message = $('#vortex-license-message');
        
        $message.removeClass('notice-success notice-error')
                .addClass(type === 'success' ? 'notice-success' : 'notice-error')
                .html('<p>' + message + '</p>')
                .show();
    }
});
</script>

<style>
.vortex-license-wrap {
    max-width: 900px;
}

.vortex-license-box {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin: 20px 0;
    border-radius: 4px;
}

.vortex-license-status {
    margin-bottom: 20px;
    padding: 15px;
    border-radius: 4px;
    font-weight: bold;
    font-size: 16px;
}

.vortex-license-active {
    background: #d4f6dd;
    color: #0a7326;
}

.vortex-license-inactive {
    background: #f6d4d4;
    color: #730a0a;
}

.vortex-license-expiry {
    font-weight: normal;
    margin-top: 5px;
    font-size: 14px;
}

.vortex-license-actions {
    margin: 20px 0;
}

.vortex-license-message {
    margin: 20px 0;
}

.vortex-features-table {
    margin: 20px 0;
}

.vortex-feature-active {
    color: #0a7326;
}

.vortex-feature-inactive {
    color: #730a0a;
}

.vortex-upsell {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 4px;
    margin: 20px 0;
    text-align: center;
}
</style> 