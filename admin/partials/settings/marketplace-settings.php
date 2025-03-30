<?php
/**
 * Marketplace Settings template.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials/settings
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get settings from the database
$marketplace_name = get_option('vortex_marketplace_name', 'VORTEX AI Marketplace');
$marketplace_tagline = get_option('vortex_marketplace_tagline', 'Discover and Trade AI-Generated Art');
$commission_rate = get_option('vortex_commission_rate', 10);
$default_currency = get_option('vortex_default_currency', 'TOLA');
$marketplace_status = get_option('vortex_marketplace_status', 'active');
$featured_artworks_count = get_option('vortex_featured_artworks_count', 8);
$artist_verification_required = get_option('vortex_artist_verification_required', 1);
$free_credits_new_user = get_option('vortex_free_credits_new_user', 10);
$credits_per_generation = get_option('vortex_credits_per_generation', 1);

// Available currencies
$currencies = array(
    'TOLA' => 'TOLA Token',
    'USD' => 'US Dollar ($)',
    'EUR' => 'Euro (€)',
    'GBP' => 'British Pound (£)',
    'JPY' => 'Japanese Yen (¥)',
    'SOL' => 'Solana'
);

// Marketplace status options
$status_options = array(
    'active' => 'Active (Fully operational)',
    'maintenance' => 'Maintenance Mode (Admin only)',
    'read_only' => 'Read Only (Browsing only, no purchases)',
    'closed' => 'Closed (Site down)'
);
?>

<div class="vortex-settings-section" id="marketplace-general-settings">
    <h2><?php esc_html_e('General Marketplace Settings', 'vortex-ai-marketplace'); ?></h2>
    <p class="description"><?php esc_html_e('Configure the basic settings for your VORTEX AI Marketplace.', 'vortex-ai-marketplace'); ?></p>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="vortex_marketplace_name"><?php esc_html_e('Marketplace Name', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <input type="text" id="vortex_marketplace_name" name="vortex_marketplace_name" 
                       value="<?php echo esc_attr($marketplace_name); ?>" class="regular-text" />
                <p class="description"><?php esc_html_e('The name of your AI art marketplace.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_marketplace_tagline"><?php esc_html_e('Marketplace Tagline', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <input type="text" id="vortex_marketplace_tagline" name="vortex_marketplace_tagline" 
                       value="<?php echo esc_attr($marketplace_tagline); ?>" class="regular-text" />
                <p class="description"><?php esc_html_e('A short description or tagline for your marketplace.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_marketplace_status"><?php esc_html_e('Marketplace Status', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <select id="vortex_marketplace_status" name="vortex_marketplace_status">
                    <?php foreach ($status_options as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($marketplace_status, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e('Current operational status of the marketplace.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
    </table>
</div>

<div class="vortex-settings-section" id="marketplace-financial-settings">
    <h2><?php esc_html_e('Financial Settings', 'vortex-ai-marketplace'); ?></h2>
    <p class="description"><?php esc_html_e('Configure financial settings such as currency and commission rates.', 'vortex-ai-marketplace'); ?></p>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="vortex_default_currency"><?php esc_html_e('Default Currency', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <select id="vortex_default_currency" name="vortex_default_currency">
                    <?php foreach ($currencies as $code => $currency_name) : ?>
                        <option value="<?php echo esc_attr($code); ?>" <?php selected($default_currency, $code); ?>>
                            <?php echo esc_html($currency_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e('The primary currency used for transactions in the marketplace.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_commission_rate"><?php esc_html_e('Commission Rate (%)', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <input type="number" id="vortex_commission_rate" name="vortex_commission_rate" 
                       value="<?php echo esc_attr($commission_rate); ?>" min="0" max="100" step="0.1" class="small-text" />
                <p class="description"><?php esc_html_e('Percentage commission taken by the marketplace on each sale.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
    </table>
</div>

<div class="vortex-settings-section" id="marketplace-content-settings">
    <h2><?php esc_html_e('Content Settings', 'vortex-ai-marketplace'); ?></h2>
    <p class="description"><?php esc_html_e('Configure content display and verification settings.', 'vortex-ai-marketplace'); ?></p>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="vortex_featured_artworks_count"><?php esc_html_e('Featured Artworks Count', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <input type="number" id="vortex_featured_artworks_count" name="vortex_featured_artworks_count" 
                       value="<?php echo esc_attr($featured_artworks_count); ?>" min="0" max="50" class="small-text" />
                <p class="description"><?php esc_html_e('Number of featured artworks to display on the homepage.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_artist_verification_required"><?php esc_html_e('Artist Verification Required', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <label for="vortex_artist_verification_required">
                    <input type="checkbox" id="vortex_artist_verification_required" name="vortex_artist_verification_required" 
                           value="1" <?php checked($artist_verification_required, 1); ?> />
                    <?php esc_html_e('Require manual verification of artists before they can sell artwork', 'vortex-ai-marketplace'); ?>
                </label>
                <p class="description"><?php esc_html_e('When enabled, artists must be manually verified by an admin before they can list artworks for sale.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
    </table>
</div>

<div class="vortex-settings-section" id="marketplace-ai-credits-settings">
    <h2><?php esc_html_e('AI Generation Credits', 'vortex-ai-marketplace'); ?></h2>
    <p class="description"><?php esc_html_e('Configure settings for AI generation credits.', 'vortex-ai-marketplace'); ?></p>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="vortex_free_credits_new_user"><?php esc_html_e('Free Credits for New Users', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <input type="number" id="vortex_free_credits_new_user" name="vortex_free_credits_new_user" 
                       value="<?php echo esc_attr($free_credits_new_user); ?>" min="0" max="1000" class="small-text" />
                <p class="description"><?php esc_html_e('Number of free AI generation credits to give new users upon registration.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_credits_per_generation"><?php esc_html_e('Credits Per Generation', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <input type="number" id="vortex_credits_per_generation" name="vortex_credits_per_generation" 
                       value="<?php echo esc_attr($credits_per_generation); ?>" min="1" max="100" class="small-text" />
                <p class="description"><?php esc_html_e('Number of credits consumed per AI artwork generation.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
    </table>
</div> 