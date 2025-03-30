<?php
/**
 * User Agreement Handler
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class for handling user agreements
 */
class Vortex_User_Agreement {
    
    /**
     * Initialize the class
     */
    public function __construct() {
        // Add hooks for agreement popup
        add_action('wp_footer', array($this, 'render_agreement_popup'));
        add_action('wp_ajax_vortex_store_agreement', array($this, 'store_agreement'));
        
        // Check agreement before AI access
        add_filter('vortex_check_llm_api_access', array($this, 'check_agreement'), 10, 2);
        
        // Show agreement after TOLA purchase
        add_action('vortex_after_tola_purchase', array($this, 'set_show_agreement_flag'));
    }
    
    /**
     * Set flag to show agreement after TOLA purchase
     * 
     * @param int $user_id User ID
     */
    public function set_show_agreement_flag($user_id) {
        if (!$this->has_user_agreed($user_id)) {
            update_user_meta($user_id, 'vortex_show_agreement', 1);
        }
    }
    
    /**
     * Check if user needs to see agreement
     * 
     * @param int $user_id User ID
     * @return bool Whether to show agreement
     */
    public function should_show_agreement($user_id = 0) {
        if ($user_id === 0) {
            $user_id = get_current_user_id();
        }
        
        if ($user_id === 0) {
            return false; // Not logged in
        }
        
        // Check if user has TOLA tokens but hasn't agreed yet
        $has_tokens = Vortex_AI_Marketplace::get_instance()->wallet->get_tola_balance($user_id) > 0;
        $has_agreed = $this->has_user_agreed($user_id);
        $show_flag = get_user_meta($user_id, 'vortex_show_agreement', true);
        
        return $has_tokens && !$has_agreed && $show_flag;
    }
    
    /**
     * Check if user has agreed to terms
     * 
     * @param int $user_id User ID
     * @return bool Whether user has agreed
     */
    public function has_user_agreed($user_id = 0) {
        if ($user_id === 0) {
            $user_id = get_current_user_id();
        }
        
        if ($user_id === 0) {
            return false; // Not logged in
        }
        
        return (bool) get_user_meta($user_id, 'vortex_ai_agreement_accepted', true);
    }
    
    /**
     * Store user agreement
     */
    public function store_agreement() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_agreement_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security verification failed', 'vortex-ai-marketplace')
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to accept the agreement', 'vortex-ai-marketplace')
            ));
        }
        
        $user_id = get_current_user_id();
        $agreed = isset($_POST['agreed']) && $_POST['agreed'] === 'true';
        
        if ($agreed) {
            // Store agreement acceptance
            update_user_meta($user_id, 'vortex_ai_agreement_accepted', 1);
            update_user_meta($user_id, 'vortex_ai_agreement_date', current_time('mysql'));
            delete_user_meta($user_id, 'vortex_show_agreement');
            
            // Store learning consent if provided
            if (isset($_POST['learning_consent']) && $_POST['learning_consent'] === 'true') {
                $ai_learning = Vortex_AI_Marketplace::get_instance()->ai_learning;
                $ai_learning->store_user_consent($user_id, true);
            }
            
            wp_send_json_success(array(
                'message' => __('Agreement accepted successfully', 'vortex-ai-marketplace')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('You must accept the agreement to use AI features', 'vortex-ai-marketplace')
            ));
        }
    }
    
    /**
     * Check agreement before allowing AI access
     * 
     * @param bool $has_access Current access status
     * @param int $user_id User ID
     * @return bool Updated access status
     */
    public function check_agreement($has_access, $user_id) {
        // If user already doesn't have access, don't change that
        if (!$has_access) {
            return false;
        }
        
        // Check if user has agreed to terms
        return $this->has_user_agreed($user_id);
    }
    
    /**
     * Render agreement popup
     */
    public function render_agreement_popup() {
        if (!$this->should_show_agreement()) {
            return;
        }
        
        // Get current timestamp for versioning
        $version_timestamp = '20231101';
        ?>
        <div id="vortex-agreement-overlay" class="vortex-modal-overlay">
            <div id="vortex-agreement-modal" class="vortex-modal">
                <div class="vortex-modal-header">
                    <h2><?php esc_html_e('Vortex AI Marketplace Agreement', 'vortex-ai-marketplace'); ?></h2>
                </div>
                
                <div class="vortex-modal-content">
                    <div class="vortex-agreement-text">
                        <h3><?php esc_html_e('Terms of Use & Privacy Agreement', 'vortex-ai-marketplace'); ?></h3>
                        <p class="vortex-agreement-version"><?php echo sprintf(esc_html__('Version %s', 'vortex-ai-marketplace'), $version_timestamp); ?></p>
                        
                        <div class="vortex-agreement-section">
                            <h4><?php esc_html_e('1. Introduction', 'vortex-ai-marketplace'); ?></h4>
                            <p><?php esc_html_e('Welcome to the Vortex AI Marketplace. By accessing our AI-powered features (HURAII, CLOE, and Business Strategist), you agree to be bound by these terms.', 'vortex-ai-marketplace'); ?></p>
                        </div>
                        
                        <div class="vortex-agreement-section">
                            <h4><?php esc_html_e('2. AI Services', 'vortex-ai-marketplace'); ?></h4>
                            <p><?php esc_html_e('Our marketplace provides AI-generated content including artwork (HURAII), market analysis (CLOE), and business strategy recommendations (Business Strategist). These services are powered by artificial intelligence and deep learning technologies.', 'vortex-ai-marketplace'); ?></p>
                        </div>
                        
                        <div class="vortex-agreement-section">
                            <h4><?php esc_html_e('3. TOLA Token Economy', 'vortex-ai-marketplace'); ?></h4>
                            <p><?php esc_html_e('Access to our AI services requires TOLA tokens, our platform currency. Tokens are consumed when requesting AI-generated content, with rates varying by service and complexity.', 'vortex-ai-marketplace'); ?></p>
                        </div>
                        
                        <div class="vortex-agreement-section">
                            <h4><?php esc_html_e('4. Data Collection', 'vortex-ai-marketplace'); ?></h4>
                            <p><?php esc_html_e('To provide and improve our services, we collect and process the following types of data:', 'vortex-ai-marketplace'); ?></p>
                            <ul>
                                <li><?php esc_html_e('User account information', 'vortex-ai-marketplace'); ?></li>
                                <li><?php esc_html_e('Prompts and inputs provided to AI systems', 'vortex-ai-marketplace'); ?></li>
                                <li><?php esc_html_e('Generated content and results', 'vortex-ai-marketplace'); ?></li>
                                <li><?php esc_html_e('Usage patterns and interaction data', 'vortex-ai-marketplace'); ?></li>
                                <li><?php esc_html_e('Feedback and ratings provided', 'vortex-ai-marketplace'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="vortex-agreement-section">
                            <h4><?php esc_html_e('5. AI Learning & Improvement', 'vortex-ai-marketplace'); ?></h4>
                            <p><?php esc_html_e('Our AI systems are designed to learn and improve from user interactions. By using our services, you agree that your interactions may be used to:', 'vortex-ai-marketplace'); ?></p>
                            <ul>
                                <li><?php esc_html_e('Train and improve AI models', 'vortex-ai-marketplace'); ?></li>
                                <li><?php esc_html_e('Enhance response quality and relevance', 'vortex-ai-marketplace'); ?></li>
                                <li><?php esc_html_e('Develop new features and capabilities', 'vortex-ai-marketplace'); ?></li>
                                <li><?php esc_html_e('Optimize user experience', 'vortex-ai-marketplace'); ?></li>
                            </ul>
                            <p><?php esc_html_e('You can opt out of contributing to AI learning while still using our services by unchecking the optional consent below.', 'vortex-ai-marketplace'); ?></p>
                        </div>
                        
                        <div class="vortex-agreement-section">
                            <h4><?php esc_html_e('6. Content Ownership', 'vortex-ai-marketplace'); ?></h4>
                            <p><?php esc_html_e('You retain ownership of the content you create using our AI services. However, we may use anonymized versions of generated content to improve our systems as outlined above.', 'vortex-ai-marketplace'); ?></p>
                        </div>
                        
                        <div class="vortex-agreement-section">
                            <h4><?php esc_html_e('7. Prohibited Uses', 'vortex-ai-marketplace'); ?></h4>
                            <p><?php esc_html_e('You agree not to use our AI services to:', 'vortex-ai-marketplace'); ?></p>
                            <ul>
                                <li><?php esc_html_e('Generate illegal, harmful, or unethical content', 'vortex-ai-marketplace'); ?></li>
                                <li><?php esc_html_e('Impersonate others or spread misinformation', 'vortex-ai-marketplace'); ?></li>
                                <li><?php esc_html_e('Violate intellectual property rights', 'vortex-ai-marketplace'); ?></li>
                                <li><?php esc_html_e('Attempt to manipulate or exploit the AI systems', 'vortex-ai-marketplace'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="vortex-agreement-section">
                            <h4><?php esc_html_e('8. Limitation of Liability', 'vortex-ai-marketplace'); ?></h4>
                            <p><?php esc_html_e('AI-generated content is provided "as is" without warranties. We are not responsible for decisions made based on AI recommendations or for any inaccuracies in generated content.', 'vortex-ai-marketplace'); ?></p>
                        </div>
                        
                        <div class="vortex-agreement-section">
                            <h4><?php esc_html_e('9. Agreement Changes', 'vortex-ai-marketplace'); ?></h4>
                            <p><?php esc_html_e('We may update these terms at any time. Continued use of our services after changes constitutes acceptance of the updated terms.', 'vortex-ai-marketplace'); ?></p>
                        </div>
                        
                        <div class="vortex-agreement-section">
                            <h4><?php esc_html_e('10. Contact Information', 'vortex-ai-marketplace'); ?></h4>
                            <p><?php esc_html_e('For questions about these terms or our AI services, please contact support@vortexai.com', 'vortex-ai-marketplace'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="vortex-modal-footer">
                    <div class="vortex-agreement-options">
                        <div class="vortex-agreement-required">
                            <label>
                                <input type="checkbox" id="vortex-agreement-checkbox">
                                <span><?php esc_html_e('I accept the Terms of Use & Privacy Agreement', 'vortex-ai-marketplace'); ?></span>
                            </label>
                            <p class="vortex-required-note"><?php esc_html_e('(Required to use AI features)', 'vortex-ai-marketplace'); ?></p>
                        </div>
                        
                        <div class="vortex-agreement-optional">
                            <label>
                                <input type="checkbox" id="vortex-learning-checkbox" checked>
                                <span><?php esc_html_e('I allow my interactions to be used for AI learning and improvement', 'vortex-ai-marketplace'); ?></span>
                            </label>
                            <p class="vortex-optional-note"><?php esc_html_e('(Optional - helps improve our AI)', 'vortex-ai-marketplace'); ?></p>
                        </div>
                    </div>
                    
                    <div class="vortex-agreement-actions">
                        <button id="vortex-decline-button" class="vortex-button vortex-button-secondary"><?php esc_html_e('Decline', 'vortex-ai-marketplace'); ?></button>
                        <button id="vortex-accept-button" class="vortex-button vortex-button-primary" disabled><?php esc_html_e('Accept & Continue', 'vortex-ai-marketplace'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Enable/disable accept button based on required checkbox
            $('#vortex-agreement-checkbox').on('change', function() {
                $('#vortex-accept-button').prop('disabled', !$(this).is(':checked'));
            });
            
            // Handle accept button click
            $('#vortex-accept-button').on('click', function() {
                const agreed = $('#vortex-agreement-checkbox').is(':checked');
                const learningConsent = $('#vortex-learning-checkbox').is(':checked');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'vortex_store_agreement',
                        agreed: agreed ? 'true' : 'false',
                        learning_consent: learningConsent ? 'true' : 'false',
                        nonce: '<?php echo wp_create_nonce('vortex_agreement_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#vortex-agreement-overlay').fadeOut();
                        } else {
                            alert(response.data.message);
                        }
                    }
                });
            });
            
            // Handle decline button click
            $('#vortex-decline-button').on('click', function() {
                $('#vortex-agreement-overlay').fadeOut();
            });
        });
        </script>
        
        <style>
        .vortex-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .vortex-modal {
            background-color: #fff;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .vortex-modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }
        
        .vortex-modal-header h2 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        
        .vortex-modal-content {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
        }
        
        .vortex-agreement-text {
            font-size: 14px;
            line-height: 1.6;
            color: #333;
        }
        
        .vortex-agreement-version {
            color: #777;
            font-style: italic;
            margin-bottom: 20px;
        }
        
        .vortex-agreement-section {
            margin-bottom: 20px;
        }
        
        .vortex-agreement-section h4 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .vortex-modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            background-color: #f9f9f9;
        }
        
        .vortex-agreement-options {
            margin-bottom: 15px;
        }
        
        .vortex-agreement-required,
        .vortex-agreement-optional {
            margin-bottom: 10px;
        }
        
        .vortex-required-note,
        .vortex-optional-note {
            margin: 5px 0 0 25px;
            font-size: 12px;
            color: #777;
            font-style: italic;
        }
        
        .vortex-agreement-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .vortex-button {
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }
        
        .vortex-button-primary {
            background-color: #2271b1;
            color: #fff;
        }
        
        .vortex-button-primary:hover {
            background-color: #135e96;
        }
        
        .vortex-button-primary:disabled {
            background-color: #a7aaad;
            cursor: not-allowed;
        }
        
        .vortex-button-secondary {
            background-color: #f6f7f7;
            color: #2c3338;
            border: 1px solid #2c3338;
        }
        
        .vortex-button-secondary:hover {
            background-color: #f0f0f1;
        }
        </style>
        <?php
    }
} 