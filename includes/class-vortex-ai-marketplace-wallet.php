    /**
     * Send welcome email with wallet information
     *
     * @param int $user_id User ID
     * @param array $wallet_data Wallet data
     */
    private function send_wallet_welcome_email($user_id, $wallet_data) {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }
        
        $subject = sprintf(__('[%s] Your New TOLA Wallet', 'vortex-ai-marketplace'), get_bloginfo('name'));
        
        $message = sprintf(
            __("Hello %s,\n\nWelcome to %s! Your blockchain wallet has been created successfully.\n\nWallet Address: %s\n\nPlease keep this information safe and secure. You will need your wallet address to receive TOLA tokens and interact with our marketplace.\n\nTo start using the marketplace, you need to purchase TOLA tokens. Visit %s to buy tokens.\n\nThank you,\n%s Team", 'vortex-ai-marketplace'),
            $user->display_name,
            get_bloginfo('name'),
            $wallet_data['address'],
            home_url('/purchase-tola/'),
            get_bloginfo('name')
        );
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Check TOLA access restriction
     * 
     * Restricts access to artwork pages if user doesn't have TOLA
     */
    public function check_tola_access_restriction() {
        // Check if restriction is enabled in settings
        $blockchain_settings = get_option('vortex_blockchain_settings', array());
        if (empty($blockchain_settings['require_tola_for_access'])) {
            return;
        }
        
        // Skip for admin, ajax, cron, and login pages
        if (is_admin() || wp_doing_ajax() || wp_doing_cron() || 
            (function_exists('is_login') && is_login())) {
            return;
        }
        
        // Don't restrict the TOLA purchase page or account pages
        if (is_page('purchase-tola') || is_page('my-account') || 
            is_page('login') || is_page('register')) {
            return;
        }
        
        // Check if accessing artwork or marketplace pages
        if (is_singular('vortex_artwork') || 
            has_shortcode(get_post()->post_content, 'vortex_marketplace') ||
            has_shortcode(get_post()->post_content, 'vortex_artwork')) {
            
            // Allow access for admins and shop managers
            if (current_user_can('manage_options') || current_user_can('manage_woocommerce')) {
                return;
            }
            
            // Check if user is logged in
            if (!is_user_logged_in()) {
                // Redirect to login page
                wp_redirect(add_query_arg('redirect_to', urlencode(get_permalink()), wp_login_url()));
                exit;
            }
            
            // Check if user has TOLA tokens
            $user_id = get_current_user_id();
            $tola_balance = floatval(get_user_meta($user_id, 'vortex_tola_balance', true));
            
            if ($tola_balance <= 0) {
                // Redirect to purchase TOLA page
                wp_redirect(home_url('/purchase-tola/?access=restricted'));
                exit;
            }
        }
    }
    
    /**
     * Add wallet information to user profile
     *
     * @param WP_User $user User object
     */
    public function add_wallet_profile_fields($user) {
        if (!current_user_can('edit_user', $user->ID) && get_current_user_id() !== $user->ID) {
            return;
        }
        
        $wallet_address = get_user_meta($user->ID, 'vortex_wallet_address', true);
        $tola_balance = get_user_meta($user->ID, 'vortex_tola_balance', true);
        $wallet_created = get_user_meta($user->ID, 'vortex_wallet_created', true);
        
        // If no wallet address exists, we haven't created a wallet yet
        if (empty($wallet_address)) {
            $has_wallet = false;
        } else {
            $has_wallet = true;
        }
        
        // Get blockchain settings
        $blockchain_settings = get_option('vortex_blockchain_settings', array());
        ?>
        <h2><?php _e('TOLA Wallet Information', 'vortex-ai-marketplace'); ?></h2>
        <table class="form-table">
            <tr>
                <th><label><?php _e('Has Wallet', 'vortex-ai-marketplace'); ?></label></th>
                <td>
                    <?php if ($has_wallet): ?>
                        <span class="dashicons dashicons-yes" style="color: green;"></span>
                        <?php _e('Wallet Created', 'vortex-ai-marketplace'); ?>
                        <?php if (!empty($wallet_created)): ?>
                            <?php printf(__(' on %s', 'vortex-ai-marketplace'), 
                                date_i18n(get_option('date_format'), strtotime($wallet_created))); ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="dashicons dashicons-no" style="color: red;"></span>
                        <?php _e('No Wallet', 'vortex-ai-marketplace'); ?>
                        <?php if (current_user_can('edit_users')): ?>
                            <button type="button" class="button" id="vortex-create-wallet" 
                                    data-user-id="<?php echo esc_attr($user->ID); ?>"
                                    data-nonce="<?php echo wp_create_nonce('vortex_create_wallet_' . $user->ID); ?>">
                                <?php _e('Create Wallet', 'vortex-ai-marketplace'); ?>
                            </button>
                            <span class="spinner" id="vortex-wallet-spinner" style="float: none; visibility: hidden;"></span>
                            <span id="vortex-wallet-message"></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ($has_wallet): ?>
            <tr>
                <th><label for="vortex_wallet_address"><?php _e('Wallet Address', 'vortex-ai-marketplace'); ?></label></th>
                <td>
                    <input type="text" class="regular-text" id="vortex_wallet_address" 
                           value="<?php echo esc_attr($wallet_address); ?>" readonly />
                    <button type="button" class="button copy-to-clipboard" 
                            data-clipboard-target="#vortex_wallet_address">
                        <span class="dashicons dashicons-clipboard"></span>
                    </button>
                </td>
            </tr>
            <tr>
                <th><label for="vortex_tola_balance"><?php _e('TOLA Balance', 'vortex-ai-marketplace'); ?></label></th>
                <td>
                    <?php if (current_user_can('edit_users')): ?>
                        <input type="number" step="0.01" min="0" class="regular-text" 
                               id="vortex_tola_balance" name="vortex_tola_balance" 
                               value="<?php echo esc_attr($tola_balance); ?>" />
                        <p class="description">
                            <?php _e('As an admin, you can adjust the user\'s TOLA balance.', 'vortex-ai-marketplace'); ?>
                        </p>
                    <?php else: ?>
                        <input type="text" class="regular-text" value="<?php echo esc_attr($tola_balance); ?>" readonly />
                        <a href="<?php echo esc_url(home_url('/purchase-tola/')); ?>" class="button">
                            <?php _e('Buy TOLA', 'vortex-ai-marketplace'); ?>
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        
        <?php if (current_user_can('edit_users') && $has_wallet): ?>
        <h3><?php _e('Wallet Transactions', 'vortex-ai-marketplace'); ?></h3>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Date', 'vortex-ai-marketplace'); ?></th>
                    <th><?php _e('Type', 'vortex-ai-marketplace'); ?></th>
                    <th><?php _e('Amount', 'vortex-ai-marketplace'); ?></th>
                    <th><?php _e('Status', 'vortex-ai-marketplace'); ?></th>
                    <th><?php _e('Transaction Hash', 'vortex-ai-marketplace'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Get transaction history
                $transactions = $this->get_user_transactions($user->ID);
                
                if (empty($transactions)): ?>
                    <tr>
                        <td colspan="5"><?php _e('No transactions found.', 'vortex-ai-marketplace'); ?></td>
                    </tr>
                <?php else:
                    foreach ($transactions as $tx): ?>
                        <tr>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($tx['date']))); ?></td>
                            <td><?php echo esc_html($tx['type']); ?></td>
                            <td><?php echo isset($tx['amount']) ? esc_html($tx['amount']) : '-'; ?></td>
                            <td><?php echo esc_html($tx['status']); ?></td>
                            <td>
                                <?php if (!empty($tx['hash'])): ?>
                                    <a href="<?php echo esc_url('https://explorer.example.com/tx/' . $tx['hash']); ?>" 
                                       target="_blank" rel="noopener noreferrer">
                                        <?php echo esc_html(substr($tx['hash'], 0, 10) . '...'); ?>
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach;
                endif; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#vortex-create-wallet').on('click', function() {
                var button = $(this);
                var spinner = $('#vortex-wallet-spinner');
                var message = $('#vortex-wallet-message');
                
                button.prop('disabled', true);
                spinner.css('visibility', 'visible');
                message.html('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_create_wallet',
                        user_id: button.data('user-id'),
                        nonce: button.data('nonce')
                    },
                    success: function(response) {
                        if (response.success) {
                            message.html('<span style="color: green;">' + response.data + '</span>');
                            setTimeout(function() {
                                window.location.reload();
                            }, 2000);
                        } else {
                            message.html('<span style="color: red;">' + response.data + '</span>');
                            button.prop('disabled', false);
                        }
                    },
                    error: function() {
                        message.html('<span style="color: red;"><?php _e('An error occurred.', 'vortex-ai-marketplace'); ?></span>');
                        button.prop('disabled', false);
                    },
                    complete: function() {
                        spinner.css('visibility', 'hidden');
                    }
                });
            });
            
            $('.copy-to-clipboard').on('click', function() {
                var target = $($(this).data('clipboard-target'));
                
                // Create a temporary input to copy from
                var tempInput = $('<input>');
                $('body').append(tempInput);
                tempInput.val(target.val()).select();
                document.execCommand('copy');
                tempInput.remove();
                
                // Show feedback
                var originalText = $(this).html();
                $(this).html('<span class="dashicons dashicons-yes"></span>');
                setTimeout(function() {
                    $('.copy-to-clipboard').html(originalText);
                }, 1500);
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save wallet profile fields
     *
     * @param int $user_id User ID
     */
    public function save_wallet_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }
        
        // Only admins can adjust TOLA balance
        if (current_user_can('edit_users') && isset($_POST['vortex_tola_balance'])) {
            $old_balance = get_user_meta($user_id, 'vortex_tola_balance', true);
            $new_balance = floatval($_POST['vortex_tola_balance']);
            
            // Only update if balance changed
            if ($old_balance != $new_balance) {
                update_user_meta($user_id, 'vortex_tola_balance', $new_balance);
                
                // Log the balance adjustment
                $this->log_wallet_event($user_id, 'admin_adjustment', array(
                    'old_balance' => $old_balance,
                    'new_balance' => $new_balance,
                    'admin_id' => get_current_user_id()
                ));
            }
        }
    }
    
    /**
     * Get user transaction history
     *
     * @param int $user_id User ID
     * @return array Array of transactions
     */
    private function get_user_transactions($user_id) {
        // In a real implementation, fetch from database or blockchain
        // For demo, return sample data
        return array(
            array(
                'date' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'type' => 'purchase',
                'amount' => '50 TOLA',
                'status' => 'completed',
                'hash' => '0x' . bin2hex(random_bytes(16))
            ),
            array(
                'date' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'type' => 'artwork_purchase',
                'amount' => '25 TOLA',
                'status' => 'completed',
                'hash' => '0x' . bin2hex(random_bytes(16))
            ),
            array(
                'date' => date('Y-m-d H:i:s', strtotime('-7 days')),
                'type' => 'wallet_creation',
                'amount' => '',
                'status' => 'completed',
                'hash' => ''
            )
        );
    }
    
    /**
     * Log wallet event
     *
     * @param int $user_id User ID
     * @param string $event_type Event type
     * @param array $data Event data
     */
    private function log_wallet_event($user_id, $event_type, $data = array()) {
        // In a real implementation, log to database
        // For demo, just log to error log if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'Wallet event for user #%d: %s. Data: %s',
                $user_id,
                $event_type,
                json_encode($data)
            ));
        }
    }
    
    /**
     * Wallet connect shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Rendered HTML
     */
    public function wallet_connect_shortcode($atts) {
        $atts = shortcode_atts(array(
            'text' => __('Connect Wallet', 'vortex-ai-marketplace'),
            'class' => '',
            'redirect' => ''
        ), $atts, 'vortex_wallet_connect');
        
        ob_start();
        include(plugin_dir_path(dirname(__FILE__)) . 'public/partials/shortcodes/wallet-connect.php');
        return ob_get_clean();
    }
    
    /**
     * TOLA purchase shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Rendered HTML
     */
    public function tola_purchase_shortcode($atts) {
        $atts = shortcode_atts(array(
            'packages' => 'true',
            'custom' => 'true',
            'min' => 10,
            'max' => 1000
        ), $atts, 'vortex_tola_purchase');
        
        // Convert string booleans to actual booleans
        $show_packages = filter_var($atts['packages'], FILTER_VALIDATE_BOOLEAN);
        $show_custom = filter_var($atts['custom'], FILTER_VALIDATE_BOOLEAN);
        $min_amount = intval($atts['min']);
        $max_amount = intval($atts['max']);
        
        ob_start();
        include(plugin_dir_path(dirname(__FILE__)) . 'public/partials/shortcodes/tola-purchase.php');
        return ob_get_clean();
    }
    
    /**
     * Wallet status shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Rendered HTML
     */
    public function wallet_status_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_balance' => 'true',
            'show_transactions' => 'true',
            'show_nfts' => 'true',
            'limit' => 5
        ), $atts, 'vortex_wallet_status');
        
        // Convert string booleans to actual booleans
        $show_balance = filter_var($atts['show_balance'], FILTER_VALIDATE_BOOLEAN);
        $show_transactions = filter_var($atts['show_transactions'], FILTER_VALIDATE_BOOLEAN);
        $show_nfts = filter_var($atts['show_nfts'], FILTER_VALIDATE_BOOLEAN);
        $limit = intval($atts['limit']);
        
        ob_start();
        include(plugin_dir_path(dirname(__FILE__)) . 'public/partials/shortcodes/wallet-status.php');
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for wallet verification
     */
    public function ajax_verify_wallet() {
        check_ajax_referer('vortex_wallet_verify', 'nonce');
        
        $wallet_address = isset($_POST['wallet_address']) ? sanitize_text_field($_POST['wallet_address']) : '';
        $signature = isset($_POST['signature']) ? sanitize_text_field($_POST['signature']) : '';
        
        if (empty($wallet_address) || empty($signature)) {
            wp_send_json_error(__('Missing required parameters.', 'vortex-ai-marketplace'));
        }
        
        // In a real implementation, verify signature cryptographically
        // For demo, assume success if wallet address starts with 0x
        $is_valid = substr($wallet_address, 0, 2) === '0x';
        
        if ($is_valid) {
            // Get current user
            $user_id = get_current_user_id();
            
            // Update user meta with verified wallet
            update_user_meta($user_id, 'vortex_wallet_address', $wallet_address);
            update_user_meta($user_id, 'vortex_wallet_verified', '1');
            update_user_meta($user_id, 'vortex_wallet_verified_date', current_time('mysql'));
            
            // Log the event
            $this->log_wallet_event($user_id, 'wallet_verified', array(
                'address' => $wallet_address,
                'method' => 'signature'
            ));
            
            wp_send_json_success(__('Wallet verified successfully!', 'vortex-ai-marketplace'));
        } else {
            wp_send_json_error(__('Invalid wallet signature.', 'vortex-ai-marketplace'));
        }
    }
    
    /**
     * AJAX handler for TOLA purchase
     */
    public function ajax_purchase_tola() {
        check_ajax_referer('vortex_purchase_tola', 'nonce');
        
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';
        
        if ($amount <= 0) {
            wp_send_json_error(__('Invalid amount.', 'vortex-ai-marketplace'));
        }
        
        if (empty($payment_method)) {
            wp_send_json_error(__('Payment method is required.', 'vortex-ai-marketplace'));
        }
        
        // Get current user
        $user_id = get_current_user_id();
        
        // In a real implementation, process payment through gateway
        // For demo, simulate success
        $success = true;
        $transaction_id = 'TX' . time() . rand(1000, 9999);
        
        if ($success) {
            // Get current balance
            $current_balance = floatval(get_user_meta($user_id, 'vortex_tola_balance', true));
            
            // Add purchased amount
            $new_balance = $current_balance + $amount;
            update_user_meta($user_id, 'vortex_tola_balance', $new_balance);
            
            // Log transaction
            $this->log_wallet_event($user_id, 'tola_purchase', array(
                'amount' => $amount,
                'payment_method' => $payment_method,
                'transaction_id' => $transaction_id,
                'old_balance' => $current_balance,
                'new_balance' => $new_balance
            ));
            
            wp_send_json_success(array(
                'message' => sprintf(__('Successfully purchased %s TOLA tokens!', 'vortex-ai-marketplace'), $amount),
                'transaction_id' => $transaction_id,
                'new_balance' => $new_balance
            ));
        } else {
            wp_send_json_error(__('Payment processing failed.', 'vortex-ai-marketplace'));
        }
    }

    /**
     * Generate a new wallet
     *
     * @return array Wallet data
     */
    private function generate_new_wallet() {
        // In a real implementation, this would use blockchain libraries
        // For demo purposes, generate a simple wallet address
        
        // Generate a random address (this should use proper crypto libraries in production)
        $address = '0x' . bin2hex(random_bytes(20));
        
        // Generate a private key (this should use proper crypto libraries in production)
        $private_key = bin2hex(random_bytes(32));
        
        // Return wallet data
        return array(
            'address' => $address,
            'private_key' => $private_key,
            'created' => current_time('mysql')
        );
    }

    /**
     * Encrypt private key before storing
     *
     * @param string $private_key Private key
     * @return string Encrypted private key
     */
    private function encrypt_private_key($private_key) {
        // In a real implementation, this would use strong encryption
        // For demo purposes, we're just adding a simple prefix
        // NOTE: In production, use proper encryption methods!
        return 'enc_' . $private_key;
    }

    /**
     * Create wallet for new user
     *
     * @param int $user_id User ID
     * @return bool True if wallet created, false otherwise
     */
    public function create_user_wallet($user_id) {
        // Check if user already has a wallet
        $wallet_address = get_user_meta($user_id, 'vortex_wallet_address', true);
        if (!empty($wallet_address)) {
            return false;
        }
        
        // Generate new wallet
        $wallet_data = $this->generate_new_wallet();
        
        // Encrypt private key before storing
        $encrypted_private_key = $this->encrypt_private_key($wallet_data['private_key']);
        
        // Store wallet data in user meta
        update_user_meta($user_id, 'vortex_wallet_address', $wallet_data['address']);
        update_user_meta($user_id, 'vortex_wallet_private_key', $encrypted_private_key);
        update_user_meta($user_id, 'vortex_wallet_created', $wallet_data['created']);
        update_user_meta($user_id, 'vortex_tola_balance', 0); // Initialize TOLA balance to 0
        
        // Log wallet creation
        $this->log_wallet_event($user_id, 'wallet_created', array(
            'address' => $wallet_data['address'],
            'date' => $wallet_data['created']
        ));
        
        // Send welcome email
        $this->send_wallet_welcome_email($user_id, $wallet_data);
        
        return true;
    }

    /**
     * Initialize the class
     */
    public function __construct() {
        // Hook into user registration to create wallet
        add_action('user_register', array($this, 'create_user_wallet'), 10, 1);
        
        // Add wallet data to user profile
        add_action('show_user_profile', array($this, 'add_wallet_profile_fields'));
        add_action('edit_user_profile', array($this, 'add_wallet_profile_fields'));
        
        // Save wallet data on profile update
        add_action('personal_options_update', array($this, 'save_wallet_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_wallet_profile_fields'));
        
        // Hook into template_redirect to check access restrictions
        add_action('template_redirect', array($this, 'check_tola_access_restriction'));
        
        // Register shortcodes (these are registered in the shortcodes class)
        
        // AJAX handlers for wallet operations
        add_action('wp_ajax_vortex_verify_wallet', array($this, 'ajax_verify_wallet'));
        add_action('wp_ajax_vortex_purchase_tola', array($this, 'ajax_purchase_tola'));
        
        // Filter content for access restriction
        add_filter('the_content', array($this, 'filter_restricted_content'), 99);
    }

    /**
     * Filter content to restrict access based on TOLA
     *
     * @param string $content Post content
     * @return string Filtered content
     */
    public function filter_restricted_content($content) {
        // Check if restriction is enabled in settings
        $blockchain_settings = get_option('vortex_blockchain_settings', array());
        if (empty($blockchain_settings['require_tola_for_access'])) {
            return $content;
        }
        
        // Don't restrict content for admins
        if (current_user_can('manage_options')) {
            return $content;
        }
        
        // Check if this is a restricted post type or has restricted shortcodes
        $restricted_types = array('vortex_artwork');
        $post_type = get_post_type();
        
        $has_restricted_shortcode = false;
        $restricted_shortcodes = array('vortex_artwork', 'vortex_marketplace');
        
        foreach ($restricted_shortcodes as $shortcode) {
            if (has_shortcode($content, $shortcode)) {
                $has_restricted_shortcode = true;
                break;
            }
        }
        
        if (!in_array($post_type, $restricted_types) && !$has_restricted_shortcode) {
            return $content;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return $this->get_login_required_message();
        }
        
        // Check if user has TOLA tokens
        $user_id = get_current_user_id();
        $tola_balance = floatval(get_user_meta($user_id, 'vortex_tola_balance', true));
        $min_tola = !empty($blockchain_settings['min_tola_required']) ? intval($blockchain_settings['min_tola_required']) : 1;
        
        if ($tola_balance < $min_tola) {
            return $this->get_tola_required_message($min_tola);
        }
        
        return $content;
    }

    /**
     * Get login required message
     *
     * @return string HTML message
     */
    private function get_login_required_message() {
        ob_start();
        ?>
        <div class="vortex-login-required">
            <h3><?php esc_html_e('Login Required', 'vortex-ai-marketplace'); ?></h3>
            <p><?php esc_html_e('You need to login to access this content.', 'vortex-ai-marketplace'); ?></p>
            <p>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="vortex-button">
                    <?php esc_html_e('Log In', 'vortex-ai-marketplace'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/register/')); ?>" class="vortex-button vortex-button-secondary">
                    <?php esc_html_e('Register', 'vortex-ai-marketplace'); ?>
                </a>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get TOLA required message
     *
     * @param int $min_tola Minimum TOLA required
     * @return string HTML message
     */
    private function get_tola_required_message($min_tola) {
        ob_start();
        ?>
        <div class="vortex-tola-required">
            <h3><?php esc_html_e('TOLA Tokens Required', 'vortex-ai-marketplace'); ?></h3>
            <p>
                <?php 
                printf(
                    esc_html__('You need at least %d TOLA tokens to access this content.', 'vortex-ai-marketplace'),
                    $min_tola
                ); 
                ?>
            </p>
            <p>
                <a href="<?php echo esc_url(home_url('/purchase-tola/')); ?>" class="vortex-button">
                    <?php esc_html_e('Purchase TOLA', 'vortex-ai-marketplace'); ?>
                </a>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Check if user has access to LLM API calls
     * 
     * @param int $user_id User ID
     * @return bool True if access is allowed, false otherwise
     */
    public function check_llm_api_access($user_id) {
        // Check if restriction is enabled in settings
        $blockchain_settings = get_option('vortex_blockchain_settings', array());
        if (empty($blockchain_settings['require_tola_for_access'])) {
            return true;
        }
        
        // Skip for admins
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        // Get minimum TOLA required
        $min_tola = intval($blockchain_settings['min_tola_required'] ?? 1);
        
        // Get user's TOLA balance
        $tola_balance = floatval(get_user_meta($user_id, 'vortex_tola_balance', true));
        
        // Allow access if user has at least min_tola
        return $tola_balance >= $min_tola;
    }
} 