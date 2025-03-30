<?php
/**
 * Template for user registration shortcode
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Check if user is already logged in
if (is_user_logged_in()) {
    $user = wp_get_current_user();
    ?>
    <div class="vortex-already-registered">
        <p><?php printf(esc_html__('You are already logged in as %s.', 'vortex-ai-marketplace'), esc_html($user->display_name)); ?></p>
        
        <div class="vortex-user-actions">
            <a href="<?php echo esc_url(home_url('/purchase-tola/')); ?>" class="vortex-button">
                <?php esc_html_e('Purchase TOLA', 'vortex-ai-marketplace'); ?>
            </a>
            
            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="vortex-button vortex-button-secondary">
                <?php esc_html_e('Log Out', 'vortex-ai-marketplace'); ?>
            </a>
        </div>
    </div>
    <?php
    return;
}

// Process registration form submission
$registration_error = '';
$registration_success = false;

if (isset($_POST['vortex_register']) && isset($_POST['vortex_registration_nonce']) && 
    wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vortex_registration_nonce'])), 'vortex_registration')) {
    
    $username = isset($_POST['vortex_username']) ? sanitize_user(wp_unslash($_POST['vortex_username'])) : '';
    $email = isset($_POST['vortex_email']) ? sanitize_email(wp_unslash($_POST['vortex_email'])) : '';
    $password = isset($_POST['vortex_password']) ? $_POST['vortex_password'] : '';
    $confirm_password = isset($_POST['vortex_confirm_password']) ? $_POST['vortex_confirm_password'] : '';
    $terms = isset($_POST['vortex_terms']) ? $_POST['vortex_terms'] : '';
    
    // Validate inputs
    if (empty($username)) {
        $registration_error = __('Username is required.', 'vortex-ai-marketplace');
    } elseif (empty($email)) {
        $registration_error = __('Email address is required.', 'vortex-ai-marketplace');
    } elseif (!is_email($email)) {
        $registration_error = __('Invalid email address.', 'vortex-ai-marketplace');
    } elseif (empty($password)) {
        $registration_error = __('Password is required.', 'vortex-ai-marketplace');
    } elseif ($password !== $confirm_password) {
        $registration_error = __('Passwords do not match.', 'vortex-ai-marketplace');
    } elseif (empty($terms)) {
        $registration_error = __('You must agree to the terms and conditions.', 'vortex-ai-marketplace');
    } else {
        // Create user
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            $registration_error = $user_id->get_error_message();
        } else {
            // Set user role
            $user = new WP_User($user_id);
            $user->set_role('subscriber');
            
            // Check if auto wallet creation is enabled
            $blockchain_settings = get_option('vortex_blockchain_settings', array());
            if (!empty($blockchain_settings['auto_wallet_creation'])) {
                // Get wallet manager
                $wallet_manager = Vortex_AI_Marketplace::get_instance()->wallet;
                
                // Create wallet
                if (method_exists($wallet_manager, 'create_user_wallet')) {
                    $wallet_manager->create_user_wallet($user_id);
                }
                
                // Add initial TOLA balance if configured
                if (!empty($blockchain_settings['initial_tola_balance'])) {
                    $initial_balance = intval($blockchain_settings['initial_tola_balance']);
                    if ($initial_balance > 0) {
                        update_user_meta($user_id, 'vortex_tola_balance', $initial_balance);
                    }
                }
            }
            
            // Auto login
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
            
            $registration_success = true;
            
            // Redirect after registration if specified
            if (!empty($atts['redirect'])) {
                wp_redirect(esc_url($atts['redirect']));
                exit;
            }
        }
    }
}
?>

<div class="vortex-registration-form-container">
    <?php if ($registration_success): ?>
        <div class="vortex-registration-success">
            <h3><?php esc_html_e('Registration Successful!', 'vortex-ai-marketplace'); ?></h3>
            <p><?php esc_html_e('Welcome to Vortex AI Marketplace! You are now registered and logged in.', 'vortex-ai-marketplace'); ?></p>
            
            <div class="vortex-next-steps">
                <p><?php esc_html_e('Next Steps:', 'vortex-ai-marketplace'); ?></p>
                <ol>
                    <li>
                        <a href="<?php echo esc_url(home_url('/purchase-tola/')); ?>">
                            <?php esc_html_e('Purchase TOLA tokens to access the marketplace', 'vortex-ai-marketplace'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(home_url('/marketplace/')); ?>">
                            <?php esc_html_e('Explore the AI Art Marketplace', 'vortex-ai-marketplace'); ?>
                        </a>
                    </li>
                </ol>
            </div>
        </div>
    <?php else: ?>
        <h2><?php esc_html_e('Create Your Account', 'vortex-ai-marketplace'); ?></h2>
        <p class="vortex-registration-intro">
            <?php esc_html_e('Join Vortex AI Marketplace to access AI-generated artwork and NFTs.', 'vortex-ai-marketplace'); ?>
        </p>
        
        <?php if (!empty($registration_error)): ?>
            <div class="vortex-registration-error">
                <?php echo esc_html($registration_error); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" class="vortex-registration-form">
            <?php wp_nonce_field('vortex_registration', 'vortex_registration_nonce'); ?>
            
            <div class="vortex-form-field">
                <label for="vortex_username"><?php esc_html_e('Username', 'vortex-ai-marketplace'); ?> <span class="required">*</span></label>
                <input type="text" 
                       id="vortex_username" 
                       name="vortex_username" 
                       value="<?php echo isset($_POST['vortex_username']) ? esc_attr(wp_unslash($_POST['vortex_username'])) : ''; ?>" 
                       required>
            </div>
            
            <div class="vortex-form-field">
                <label for="vortex_email"><?php esc_html_e('Email Address', 'vortex-ai-marketplace'); ?> <span class="required">*</span></label>
                <input type="email" 
                       id="vortex_email" 
                       name="vortex_email" 
                       value="<?php echo isset($_POST['vortex_email']) ? esc_attr(wp_unslash($_POST['vortex_email'])) : ''; ?>" 
                       required>
            </div>
            
            <div class="vortex-form-field">
                <label for="vortex_password"><?php esc_html_e('Password', 'vortex-ai-marketplace'); ?> <span class="required">*</span></label>
                <input type="password" 
                       id="vortex_password" 
                       name="vortex_password" 
                       required>
            </div>
            
            <div class="vortex-form-field">
                <label for="vortex_confirm_password"><?php esc_html_e('Confirm Password', 'vortex-ai-marketplace'); ?> <span class="required">*</span></label>
                <input type="password" 
                       id="vortex_confirm_password" 
                       name="vortex_confirm_password" 
                       required>
            </div>
            
            <div class="vortex-form-field vortex-terms-field">
                <input type="checkbox" 
                       id="vortex_terms" 
                       name="vortex_terms" 
                       value="1" 
                       required>
                <label for="vortex_terms">
                    <?php 
                    printf(
                        esc_html__('I agree to the %1$sTerms of Service%2$s and %3$sPrivacy Policy%4$s', 'vortex-ai-marketplace'),
                        '<a href="' . esc_url(home_url('/terms-of-service/')) . '" target="_blank">',
                        '</a>',
                        '<a href="' . esc_url(home_url('/privacy-policy/')) . '" target="_blank">',
                        '</a>'
                    ); 
                    ?> <span class="required">*</span>
                </label>
            </div>
            
            <div class="vortex-form-submit">
                <button type="submit" name="vortex_register" class="vortex-button vortex-button-primary">
                    <?php esc_html_e('Create Account', 'vortex-ai-marketplace'); ?>
                </button>
            </div>
        </form>
        
        <?php if ($show_login): ?>
            <div class="vortex-login-link">
                <p>
                    <?php 
                    printf(
                        esc_html__('Already have an account? %sLog In%s', 'vortex-ai-marketplace'),
                        '<a href="' . esc_url(wp_login_url()) . '">',
                        '</a>'
                    ); 
                    ?>
                </p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.vortex-registration-form-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 30px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.vortex-registration-form-container h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
}

.vortex-registration-intro {
    margin-bottom: 25px;
    color: #666;
}

.vortex-registration-error {
    padding: 10px 15px;
    margin-bottom: 20px;
    background-color: #ffebee;
    border-left: 4px solid #f44336;
    color: #d32f2f;
}

.vortex-registration-success {
    padding: 20px;
    margin-bottom: 20px;
    background-color: #e8f5e9;
    border-left: 4px solid #4caf50;
    color: #2e7d32;
}

.vortex-form-field {
    margin-bottom: 20px;
}

.vortex-form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.vortex-form-field input[type="text"],
.vortex-form-field input[type="email"],
.vortex-form-field input[type="password"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.vortex-terms-field {
    display: flex;
    align-items: flex-start;
}

.vortex-terms-field input {
    margin-top: 5px;
    margin-right: 10px;
}

.vortex-button {
    display: inline-block;
    padding: 10px 20px;
    background-color: #2271b1;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    font-weight: 500;
}

.vortex-button:hover {
    background-color: #135e96;
    color: white;
}

.vortex-button-secondary {
    background-color: #f0f0f1;
    color: #2c3338;
}

.vortex-button-secondary:hover {
    background-color: #e5e5e5;
    color: #2c3338;
}

.vortex-login-link {
    margin-top: 20px;
    text-align: center;
}

.required {
    color: #f44336;
}

.vortex-next-steps {
    margin-top: 20px;
}

.vortex-next-steps ol {
    margin-left: 20px;
}

.vortex-next-steps li {
    margin-bottom: 10px;
}

.vortex-user-actions {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}
</style> 