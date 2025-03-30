<?php
/**
 * General settings tab template.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials/settings
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Add this near the top of the file, after the WPINC check
if (isset($_POST['vortex_save_settings']) && check_admin_referer('vortex_settings_nonce')) {
    // Save all marketplace settings
    $fields_to_save = array(
        'vortex_marketplace_title',
        'vortex_marketplace_description',
        'vortex_marketplace_currency',
        'vortex_marketplace_currency_symbol',
        'vortex_marketplace_commission_rate',
        'vortex_marketplace_featured_items',
        'vortex_marketplace_enable_reviews',
        'vortex_marketplace_api_key',
        'vortex_marketplace_advanced_setting',
        'vortex_marketplace_backup_setting',
        'vortex_marketplace_security_setting',
        'vortex_marketplace_analytics_setting',
        'vortex_marketplace_payment_setting',
        'vortex_marketplace_shipping_setting',
        'vortex_marketplace_tax_setting',
        'vortex_marketplace_customer_setting',
        'vortex_marketplace_developer_setting'
    );

    foreach ($fields_to_save as $field) {
        if (isset($_POST[$field])) {
            // Special handling for checkbox values
            if ($field == 'vortex_marketplace_enable_reviews') {
                update_option($field, isset($_POST[$field]) ? '1' : '0');
            } else {
                update_option($field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    // Show success message
    echo '<div class="notice notice-success is-dismissible"><p>' . 
         esc_html__('Settings Saved Successfully', 'vortex-ai-marketplace') . 
         '</p></div>';
}

// Add this right after the opening <div class="wrap">
<?php settings_errors('vortex_messages'); ?>

// Add this right before the first settings section
<form method="post" action="">
    <?php wp_nonce_field('vortex_settings_nonce'); ?>

// ... existing settings sections ...

// Add this after the last settings section and before the closing </div>
    <div class="vortex-submit-section">
        <input type="submit" name="vortex_save_settings" class="button button-primary" 
               value="<?php esc_attr_e('Save All Settings', 'vortex-ai-marketplace'); ?>" />
    </div>
</form>

<style>
    .vortex-submit-section {
        margin-top: 20px;
        padding: 20px 0;
        border-top: 1px solid #ddd;
    }
    
    .vortex-submit-section .button-primary {
        padding: 5px 20px;
        height: auto;
        font-size: 14px;
    }
    
    .settings-error {
        margin: 5px 0 15px;
    }
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Form tracking code
    var formChanged = false;
    
    $('form input, form select, form textarea').on('change', function() {
        formChanged = true;
    });
    
    $('form').on('submit', function() {
        window.onbeforeunload = null;
        return true;
    });
    
    window.onbeforeunload = function() {
        if (formChanged) {
            return '<?php echo esc_js(__('You have unsaved changes. Are you sure you want to leave?', 'vortex-ai-marketplace')); ?>';
        }
    };
});
</script>

// ... existing code ...

// Get current values
$marketplace_title = get_option( 'vortex_marketplace_title', '' );
$marketplace_description = get_option( 'vortex_marketplace_description', '' );
$marketplace_currency = get_option( 'vortex_marketplace_currency', 'USD' );
$marketplace_currency_symbol = get_option( 'vortex_marketplace_currency_symbol', '$' );
$marketplace_commission_rate = get_option( 'vortex_marketplace_commission_rate', '10' );
$marketplace_featured_items = get_option( 'vortex_marketplace_featured_items', '8' );
$marketplace_enable_reviews = get_option( 'vortex_marketplace_enable_reviews', true );

// Get currencies list
$currencies = array(
    'USD' => array( 'name' => __( 'US Dollar', 'vortex-ai-marketplace' ), 'symbol' => '$' ),
    'EUR' => array( 'name' => __( 'Euro', 'vortex-ai-marketplace' ), 'symbol' => '€' ),
    'GBP' => array( 'name' => __( 'British Pound', 'vortex-ai-marketplace' ), 'symbol' => '£' ),
    'JPY' => array( 'name' => __( 'Japanese Yen', 'vortex-ai-marketplace' ), 'symbol' => '¥' ),
    'CAD' => array( 'name' => __( 'Canadian Dollar', 'vortex-ai-marketplace' ), 'symbol' => '$' ),
    'AUD' => array( 'name' => __( 'Australian Dollar', 'vortex-ai-marketplace' ), 'symbol' => '$' ),
    'TOLA' => array( 'name' => __( 'TOLA Token', 'vortex-ai-marketplace' ), 'symbol' => 'TOLA' ),
);

?>
<form method="post" action="">
    <?php wp_nonce_field('vortex_settings_nonce'); ?>
    
    <div class="vortex-section">
        <div class="vortex-section-title">
            <h2><?php esc_html_e( 'Marketplace Identity', 'vortex-ai-marketplace' ); ?></h2>
        </div>
        <p class="vortex-section-description">
            <?php esc_html_e( 'Set the name and description for your marketplace.', 'vortex-ai-marketplace' ); ?>
        </p>
        
        <div class="vortex-field-row">
            <label for="vortex_marketplace_title" class="vortex-field-label">
                <?php esc_html_e( 'Marketplace Title', 'vortex-ai-marketplace' ); ?>
            </label>
            <input type="text" id="vortex_marketplace_title" name="vortex_marketplace_title" 
                   value="<?php echo esc_attr( $marketplace_title ); ?>" class="vortex-text-field" 
                   placeholder="<?php esc_attr_e( 'VORTEX AI Marketplace', 'vortex-ai-marketplace' ); ?>" />
            <p class="vortex-field-description">
                <?php esc_html_e( 'The title of your marketplace shown to visitors.', 'vortex-ai-marketplace' ); ?>
            </p>
        </div>
        
        <div class="vortex-field-row">
            <label for="vortex_marketplace_description" class="vortex-field-label">
                <?php esc_html_e( 'Marketplace Description', 'vortex-ai-marketplace' ); ?>
            </label>
            <textarea id="vortex_marketplace_description" name="vortex_marketplace_description" 
                      class="vortex-textarea-field" 
                      placeholder="<?php esc_attr_e( 'AI-powered digital art marketplace for unique artworks', 'vortex-ai-marketplace' ); ?>"><?php echo esc_textarea( $marketplace_description ); ?></textarea>
            <p class="vortex-field-description">
                <?php esc_html_e( 'A short description of your marketplace. This may be used in SEO and social sharing.', 'vortex-ai-marketplace' ); ?>
            </p>
        </div>
    </div>
    
    <div class="vortex-section">
        <div class="vortex-section-title">
            <h2><?php esc_html_e( 'Currency & Pricing', 'vortex-ai-marketplace' ); ?></h2>
        </div>
        <p class="vortex-section-description">
            <?php esc_html_e( 'Configure your marketplace currency and pricing settings.', 'vortex-ai-marketplace' ); ?>
        </p>
        
        <div class="vortex-field-row">
            <label for="vortex_marketplace_currency" class="vortex-field-label">
                <?php esc_html_e( 'Currency', 'vortex-ai-marketplace' ); ?>
            </label>
            <select id="vortex_marketplace_currency" name="vortex_marketplace_currency" class="vortex-select-field">
                <?php foreach ( $currencies as $code => $details ) : ?>
                    <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $marketplace_currency, $code ); ?>>
                        <?php echo esc_html( $code . ' - ' . $details['name'] ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="vortex-field-description">
                <?php esc_html_e( 'The currency used for transactions in your marketplace.', 'vortex-ai-marketplace' ); ?>
            </p>
        </div>
        
        <div class="vortex-field-row">
            <label for="vortex_marketplace_currency_symbol" class="vortex-field-label">
                <?php esc_html_e( 'Currency Symbol', 'vortex-ai-marketplace' ); ?>
            </label>
            <input type="text" id="vortex_marketplace_currency_symbol" name="vortex_marketplace_currency_symbol" 
                   value="<?php echo esc_attr( $marketplace_currency_symbol ); ?>" class="vortex-text-field" 
                   style="width: 60px;" />
            <p class="vortex-field-description">
                <?php esc_html_e( 'The symbol used to represent your currency (e.g., $, €, £, ¥).', 'vortex-ai-marketplace' ); ?>
            </p>
        </div>
        
        <div class="vortex-field-row">
            <label for="vortex_marketplace_commission_rate" class="vortex-field-label">
                <?php esc_html_e( 'Commission Rate (%)', 'vortex-ai-marketplace' ); ?>
            </label>
            <input type="number" id="vortex_marketplace_commission_rate" name="vortex_marketplace_commission_rate" 
                   value="<?php echo esc_attr( $marketplace_commission_rate ); ?>" class="vortex-number-field" 
                   min="0" max="100" step="0.1" style="width: 80px;" />
            <p class="vortex-field-description">
                <?php esc_html_e( 'The percentage of each sale that the marketplace keeps as commission. The rest goes to the artist.', 'vortex-ai-marketplace' ); ?>
            </p>
        </div>
    </div>
    
    <div class="vortex-section">
        <div class="vortex-section-title">
            <h2><?php esc_html_e( 'Display Settings', 'vortex-ai-marketplace' ); ?></h2>
        </div>
        <p class="vortex-section-description">
            <?php esc_html_e( 'Configure how the marketplace appears to visitors.', 'vortex-ai-marketplace' ); ?>
        </p>
        
        <div class="vortex-field-row">
            <label for="vortex_marketplace_featured_items" class="vortex-field-label">
                <?php esc_html_e( 'Featured Items Count', 'vortex-ai-marketplace' ); ?>
            </label>
            <input type="number" id="vortex_marketplace_featured_items" name="vortex_marketplace_featured_items" 
                   value="<?php echo esc_attr( $marketplace_featured_items ); ?>" class="vortex-number-field" 
                   min="0" max="50" step="1" style="width: 80px;" />
            <p class="vortex-field-description">
                <?php esc_html_e( 'The number of featured items to display on the marketplace homepage.', 'vortex-ai-marketplace' ); ?>
            </p>
        </div>
    </div>
    
    <div class="vortex-section">
        <div class="vortex-section-title">
            <h2><?php esc_html_e( 'Review Settings', 'vortex-ai-marketplace' ); ?></h2>
        </div>
        <p class="vortex-section-description">
            <?php esc_html_e( 'Configure the review settings for your marketplace.', 'vortex-ai-marketplace' ); ?>
        </p>
        
        <div class="vortex-field-row">
            <label for="vortex_marketplace_enable_reviews" class="vortex-field-label">
                <?php esc_html_e( 'Enable Reviews', 'vortex-ai-marketplace' ); ?>
            </label>
            <input type="checkbox" id="vortex_marketplace_enable_reviews" name="vortex_marketplace_enable_reviews" 
                   value="1" <?php checked( $marketplace_enable_reviews, true ); ?> />
            <p class="vortex-field-description">
                <?php esc_html_e( 'Allow customers to leave reviews for artworks.', 'vortex-ai-marketplace' ); ?>
            </p>
        </div>
    </div>
    
    <div class="vortex-section">
        <div class="vortex-section-title">
            <h2><?php esc_html_e( 'Additional Settings', 'vortex-ai-marketplace' ); ?></h2>
        </div>
        <p class="vortex-section-description">
            <?php esc_html_e( 'Configure any additional settings for your marketplace.', 'vortex-ai-marketplace' ); ?>
        </p>
        
        <div class="vortex-field-row">
            <label for="vortex_marketplace_additional_setting" class="vortex-field-label">
                <?php esc_html_e( 'Additional Setting', 'vortex-ai-marketplace' ); ?>
            </label>
            <input type="text" id="vortex_marketplace_additional_setting" name="vortex_marketplace_additional_setting" 
                   value="<?php echo esc_attr( get_option( 'vortex_marketplace_additional_setting', '' ) ); ?>" class="vortex-text-field" 
                   placeholder="<?php esc_attr_e( 'Enter additional setting', 'vortex-ai-marketplace' ); ?>" />
            <p class="vortex-field-description">
                <?php esc_html_e( 'Enter any additional setting for your marketplace.', 'vortex-ai-marketplace' ); ?>
            </p>
        </div>
    </div>
    
    <div class="vortex-section">
        <div class="vortex-section-title">
            <h2><?php esc_html_e( 'API Settings', 'vortex-ai-marketplace' ); ?></h2>
        </div>
        <p class="vortex-section-description">
            <?php esc_html_e( 'Configure the API settings for your marketplace.', 'vortex-ai-marketplace' ); ?>
        </p>
        
        <div class="vortex-field-row">
            <label for="vortex_marketplace_api_key" class="vortex-field-label">
                <?php esc_html_e( 'API Key', 'vortex-ai-marketplace' ); ?>
            </label>
            <input type="text" id="vortex_marketplace_api_key" name="vortex_marketplace_api_key" 
                   value="<?php echo esc_attr( get_option( 'vortex_marketplace_api_key', '' ) ); ?>" class="vortex-text-field" 
                   placeholder="<?php esc_attr_e( 'Enter API key', 'vortex-ai-marketplace' ); ?>" />
            <p class="vortex-field-description">
                <?php esc_html_e( 'Enter the API key for your marketplace.', 'vortex-ai-marketplace' ); ?>
            </p>
        </div>
    </div>
    
    <div class="vortex-section">
        <div class="vortex-section-title">
            <h2><?php esc_html_e( 'Marketing Settings', 'vortex-ai-marketplace' ); ?></h2>
        </div>
        <p class="vortex-section-description">
            <?php esc_html_e( 'Configure the marketing settings for your marketplace.', 'vortex-ai-marketplace' ); ?>
        </p>
        
        <div class="vortex-field-row">
            <label for="vortex_marketplace_marketing_setting" class="vortex-field-label">
                <?php esc_html_e( 'Marketing Setting', 'vortex-ai-marketplace' ); ?>
            </label>
            <input type="text" id="vortex_marketplace_marketing_setting" name="vortex_marketplace_marketing_setting" 
                   value="<?php echo esc_attr( get_option( 'vortex_marketplace_marketing_setting', '' ) ); ?>" class="vortex-text-field" 
                   placeholder="<?php esc_attr_e( 'Enter marketing setting', 'vortex-ai-marketplace' ); ?>" />
            <p class="vortex-field-description">
                <?php esc_html_e( 'Enter any marketing setting for your marketplace.', 'vortex-ai-marketplace' ); ?>
            </p>
        </div>
    </div>
    
    <div class="vortex-section">
        <div class="vortex-section-title">
            <h2><?php esc_html_e( 'Legal Settings', 'vortex-ai-marketplace' ); ?></h2>
        </div>
        <p class="vortex-section-description">
            <?php esc_html_e( 'Configure the legal settings for your marketplace.', 'vortex-ai-marketplace' ); ?>
        </p>
        
        <div class="vortex-field-row">
            <label for="vortex_marketplace_legal_setting" class="vortex-field-label">
                <?php esc_html_e( 'Legal Setting', 'vortex-ai-marketplace' ); ?>
            </label>
            <input type="text" id="vortex_marketplace_legal_setting" name="vortex_marketplace_legal_setting" 
                   value="<?php echo esc_attr( get_option( 'vortex_marketplace_legal_setting', '' ) ); ?>" class="vortex-text-field" 
                   placeholder="<?php esc_attr_e( 'Enter legal setting', 'vortex-ai-marketplace' ); ?>" />
            <p class="vortex-field-description">
                <?php esc_html_e( 'Enter any legal setting for your marketplace.', 'vortex-ai-marketplace' ); ?>
            </p>
        </div>
    </div>
    
    <div class="vortex-section">
        <div class="vortex-section-title">
            <h2><?php esc_html_e( 'Support Settings', 'vortex-ai-marketplace' ); ?></h2>
        </div>
        <p class="vortex-section-description">
            <?php esc_html_e( 'Configure the support settings for your marketplace.', 'vortex-ai-marketplace' ); ?>
        </p>
        
        <div class="vortex-field-row">
            <label for="vortex_marketplace_support_setting" class="vortex-field-label">
                <?php esc_html_e( 'Support Setting', 'vortex-ai-marketplace' ); ?>
            </label>
            <input type="text" id="vortex_marketplace_support_setting" name="vortex_marketplace_support_setting" 
                   value="<?php echo esc_attr( get_option( 'vortex_marketplace_support_setting', '' ) ); ?>" class="vortex-text-field" 
                   placeholder="<?php esc_attr_e( 'Enter support setting', 'vortex-ai-marketplace' ); ?>" />
            <p class="vortex-field-description">
                <?php esc_html_e( 'Enter any support setting for your marketplace.', 'vortex-ai-marketplace' ); ?>
            </p>
        </div>
    </div>
    
    <div class="vortex-section">
        <div class="vortex-section-title">
            <h2><?php esc_html_e( 'Backup Settings', 'vortex-ai-marketplace' ); ?></h2>
        </div>
        <p class="vortex-section-description">
            <?php esc_html_e( 'Configure the backup settings for your marketplace.', 'vortex-ai-marketplace' ); ?>
        </p>
        
        <div class="vortex-field-row">
            <label for="vortex_marketplace_backup_setting" class="vortex-field-label">
                <?php esc_html_e( 'Backup Setting', 'vortex-ai-marketplace' ); ?>
            </label>
            <input type="text" id="vortex_marketplace_backup_setting" name="vortex_marketplace_backup_setting" 
                   value="<?php echo esc_attr( get_option( 'vortex_marketplace_backup_setting', '' ) ); ?>" class="vortex-text-field" 
                   placeholder="<?php esc_attr_e( 'Enter backup setting', 'vortex-ai-marketplace' ); ?>" />
            <p class="vortex-field-description">
                <?php esc_html_e( 'Enter any backup setting for your marketplace.', 'vortex-ai-marketplace' ); ?>
            </p>
        </div>
    </div>
    
    <div class="vortex-section">
        <div class="vortex-section-title">
            <h2><?php esc_html_e( 'Security Settings', 'vortex-ai-marketplace' ); ?></h2>
        </div>
        <p class="vortex-section-description">
            <?php esc_html_e( 'Configure the security settings for your marketplace.', 'vortex-ai-marketplace' ); ?>
        </p>
        
        <div class="vortex-field-row">
            <label for="vortex_marketplace_security_setting" class="vortex-field-label">
                <?php esc_html_e( 'Security Setting', 'vortex-ai-marketplace' ); ?>
            </label>
            <input type="text" id="vortex_marketplace_security_setting" name="vortex_marketplace_security_setting" 
                   value="<?php echo esc_attr( get_option( 'vortex_marketplace_security_setting', '' ) ); ?>" class="vortex-text-field" 
                   placeholder="<?php esc_attr_e( 'Enter security setting', 'vortex-ai-marketplace' ); ?>" />
            <p class="vortex-field-description">
                <?php esc_html_e( 'Enter any security setting for your marketplace.', 'vortex-ai-marketplace' ); ?>
            </p>
        </div>
    </div>
    
    <div class="vortex-section">
        <div class="vortex-section-title">
            <h2><?php esc_html_e( 'Analytics Settings', 'vortex-ai-marketplace' ); ?></h2>
        </div>
        <p class="vortex-section-description">
            <?php esc_html_e( 'Configure the analytics settings for your marketplace.', 'vortex-ai-marketplace' ); ?>
        </p>
        
        <div class="vortex-field-row">
            <label for="vortex_marketplace_analytics_setting" class="vortex-field-label">
                <?php esc_html_e( 'Analytics Setting', 'vortex-ai-marketplace' ); ?>
            </label>
            <input type="text" id="vortex_marketplace_analytics_setting" name="vortex_marketplace_analytics_setting" 
                   value="<?php echo esc_attr( get_option( 'vortex_marketplace_analytics_setting', '' ) ); ?>" class="vortex-text-field" 
                   placeholder="<?php esc_attr_e( 'Enter analytics setting', 'vortex-ai-marketplace' ); ?>" />
            <p class="vortex-field-description">
                <?php esc_html_e( 'Enter any analytics setting for your marketplace.', 'vortex-ai-marketplace' ); ?>
            </p>
        </div>
    </div>
    
    <div class="vortex-section">
        <div class="vortex-section-title">
            <h2><?php esc_html_e( 'Payment Settings', 'vortex-ai-marketplace' ); ?></h2>
        </div>
        <p class="vortex-section-description">
            <?php esc_html_e( 'Configure the payment settings for your marketplace.', 'vortex-ai-marketplace' ); ?>
        </p>
        
        <div class="vortex-field-row">
            <label for="vortex_marketplace_payment_setting" class="vortex-field-label">
                <?php esc_html_e( 'Payment Setting', 'vortex-ai-marketplace' ); ?>
            </label>
            <input type="text" id="vortex_marketplace_payment_setting" name="vortex_marketplace_payment_setting" 
                   value="<?php echo esc_attr( get_option( 'vortex_marketplace_payment_setting', '' ) ); ?>" class="vortex-text-field" 
                   placeholder="<?php esc_attr_e( 'Enter payment setting', 'vortex-ai-marketplace' ); ?>" />
            <p class="vortex-field-description">
                <?php esc_html_e( 'Enter any payment setting for your marketplace.', 'vortex-ai-marketplace' ); ?>
            </p>
        </div>
    </div>
    
    <div class="vortex-section">
        <div class="vortex-section-title">
            <h2><?php esc_html_e( 'Shipping Settings', 'vortex-ai-marketplace' ); ?></h2>
        </div>
        <p class="vortex-section-description">
            <?php esc_html_e( 'Configure the shipping settings for your marketplace.', 'vortex-ai-marketplace' ); ?>
        </p>
        
        <div class="vortex-field-row">
            <label for="vortex_marketplace_shipping_setting" class="vortex-field-label">
                <?php esc_html_e( 'Shipping Setting', 'vortex-ai-marketplace' ); ?>
            </label>
            <input type="text" id="vortex_marketplace_shipping_setting" name="vortex_marketplace_shipping_setting" 
                   value="<?php echo esc_attr( get_option( 'vortex_marketplace_shipping_setting', '' ) ); ?>" class="vortex-text-field" 
                   placeholder="<?php esc_attr_e( 'Enter shipping setting', 'vortex-ai-marketplace' ); ?>" />
            <p class="vortex-field-description">
                <?php esc_html_e( 'Enter any shipping setting for your marketplace.', 'vortex-ai-marketplace' ); ?>
            </p>
        </div>
    </div>
    
    <div class="vortex-section">
        <div class="vortex-section-title">
            <h2><?php esc_html_e( 'Tax Settings', 'vortex-ai-marketplace' ); ?></h2>
        </div>
        <p class="vortex-section-description">
            <?php esc_html_e( 'Configure the tax settings for your marketplace.', 'vortex-ai-marketplace' ); ?>
        </p>
        
        <div class="vortex-field-row">
            <label for="vortex_marketplace_tax_setting" class="vortex-field-label">
                <?php esc_html_e( 'Tax Setting', 'vortex-ai-marketplace' ); ?>
            </label>
            <input type="text" id="vortex_marketplace_tax_setting" name="vortex_marketplace_tax_setting" 
                   value="<?php echo esc_attr( get_option( 'vortex_marketplace_tax_setting', '' ) ); ?>" class="vortex-text-field" 
                   placeholder="<?php esc_attr_e( 'Enter tax setting', 'vortex-ai-marketplace' ); ?>" />
            <p class="vortex-field-description">
                <?php esc_html_e( 'Enter any tax setting for your marketplace.', 'vortex-ai-marketplace' ); ?>
            </p>
        </div>
    </div>
    
    <div class="vortex-section">
        <div class="vortex-section-title">
            <h2><?php esc_html_e( 'Customer Settings', 'vortex-ai-marketplace' ); ?></h2>
        </div>
        <p class="vortex-section-description">
            <?php esc_html_e( 'Configure the customer settings for your marketplace.', 'vortex-ai-marketplace' ); ?>
        </p>
        
        <div class="vortex-field-row">
            <label for="vortex_marketplace_customer_setting" class="vortex-field-label">
                <?php esc_html_e( 'Customer Setting', 'vortex-ai-marketplace' ); ?>
            </label>
            <input type="text" id="vortex_marketplace_customer_setting" name="vortex_marketplace_customer_setting" 
                   value="<?php echo esc_attr( get_option( 'vortex_marketplace_customer_setting', '' ) ); ?>" class="vortex-text-field" 
                   placeholder="<?php esc_attr_e( 'Enter customer setting', 'vortex-ai-marketplace' ); ?>" />
            <p class="vortex-field-description">
                <?php esc_html_e( 'Enter any customer setting for your marketplace.', 'vortex-ai-marketplace' ); ?>
            </p>
        </div>
    </div>
    
    <div class="vortex-section">
        <div class="vortex-section-title">
            <h2><?php esc_html_e( 'Developer Settings', 'vortex-ai-marketplace' ); ?></h2>
        </div>
        <p class="vortex-section-description">
            <?php esc_html_e( 'Configure the developer settings for your marketplace.', 'vortex-ai-marketplace' ); ?>
        </p>
        
        <div class="vortex-field-row">
            <label for="vortex_marketplace_developer_setting" class="vortex-field-label">
                <?php esc_html_e( 'Developer Setting', 'vortex-ai-marketplace' ); ?>
            </label>
            <input type="text" id="vortex_marketplace_developer_setting" name="vortex_marketplace_developer_setting" 
                   value="<?php echo esc_attr( get_option( 'vortex_marketplace_developer_setting', '' ) ); ?>" class="vortex-text-field" 
                   placeholder="<?php esc_attr_e( 'Enter developer setting', 'vortex-ai-marketplace' ); ?>" />
            <p class="vortex-field-description">
                <?php esc_html_e( 'Enter any developer setting for your marketplace.', 'vortex-ai-marketplace' ); ?>
            </p>
        </div>
    </div>
    
    <div class="vortex-section">
        <div class="vortex-section-title">
            <h2><?php esc_html_e( 'Advanced Settings', 'vortex-ai-marketplace' ); ?></h2>
        </div>
        <p class="vortex-section-description">
            <?php esc_html_e( 'Configure the advanced settings for your marketplace.', 'vortex-ai-marketplace' ); ?>
        </p>
        
        <div class="vortex-field-row">
            <label for="vortex_marketplace_advanced_setting" class="vortex-field-label">
                <?php esc_html_e( 'Advanced Setting', 'vortex-ai-marketplace' ); ?>
            </label>
            <input type="text" id="vortex_marketplace_advanced_setting" name="vortex_marketplace_advanced_setting" 
                   value="<?php echo esc_attr( get_option( 'vortex_marketplace_advanced_setting', '' ) ); ?>" class="vortex-text-field" 
                   placeholder="<?php esc_attr_e( 'Enter advanced setting', 'vortex-ai-marketplace' ); ?>" />
            <p class="vortex-field-description">
                <?php esc_html_e( 'Enter any advanced setting for your marketplace.', 'vortex-ai-marketplace' ); ?>
            </p>
        </div>
    </div>
    
    <div class="vortex-submit-section">
        <input type="submit" name="vortex_save_settings" class="button button-primary" 
               value="<?php esc_attr_e('Save All Settings', 'vortex-ai-marketplace'); ?>" />
        </div>
</form>

<style>
    .vortex-submit-section {
        margin-top: 20px;
        padding: 20px 0;
        border-top: 1px solid #ddd;
    }
    
    .vortex-submit-section .button-primary {
        padding: 5px 20px;
        height: auto;
        font-size: 14px;
    }
    
    .settings-error {
        margin: 5px 0 15px;
    }
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Form tracking code
    var formChanged = false;
    
    $('form input, form select, form textarea').on('change', function() {
        formChanged = true;
    });
    
    $('form').on('submit', function() {
        window.onbeforeunload = null;
        return true;
    });
    
    window.onbeforeunload = function() {
        if (formChanged) {
            return '<?php echo esc_js(__('You have unsaved changes. Are you sure you want to leave?', 'vortex-ai-marketplace')); ?>';
        }
    };
});
</script>
 