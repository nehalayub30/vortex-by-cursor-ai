<?php
/**
 * Thorius Consent Manager
 * 
 * Handles user consent for AI processing and data collection
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Consent Manager
 */
class Vortex_Thorius_Consent {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register consent checkbox in comments form if enabled
        if (get_option('vortex_thorius_comments_consent', false)) {
            add_filter('comment_form_submit_field', array($this, 'add_comments_consent_checkbox'), 10, 2);
            add_action('comment_post', array($this, 'save_comment_consent'), 10, 2);
        }
        
        // Add consent notice to shortcodes if enabled
        if (get_option('vortex_thorius_display_consent_notice', true)) {
            add_filter('vortex_thorius_concierge_output', array($this, 'add_consent_notice'));
            add_filter('vortex_thorius_chat_output', array($this, 'add_consent_notice'));
            add_filter('vortex_thorius_agent_output', array($this, 'add_consent_notice'));
        }
        
        // Register AJAX handler for saving consent
        add_action('wp_ajax_vortex_thorius_save_consent', array($this, 'ajax_save_consent'));
        add_action('wp_ajax_nopriv_vortex_thorius_save_consent', array($this, 'ajax_save_consent'));
    }
    
    /**
     * Add consent checkbox to comments form
     * 
     * @param string $submit_field Submit field HTML
     * @param array $args Form arguments
     * @return string Modified submit field HTML
     */
    public function add_comments_consent_checkbox($submit_field, $args) {
        $consent_field = '<p class="comment-form-consent">';
        $consent_field .= '<input id="thorius-consent" name="thorius-consent" type="checkbox" value="yes" />';
        $consent_field .= ' <label for="thorius-consent">' . $this->get_consent_text() . '</label>';
        $consent_field .= '</p>';
        
        return $consent_field . $submit_field;
    }
    
    /**
     * Save comment consent
     * 
     * @param int $comment_id Comment ID
     * @param int $comment_approved Comment approval status
     */
    public function save_comment_consent($comment_id, $comment_approved) {
        if (isset($_POST['thorius-consent']) && $_POST['thorius-consent'] === 'yes') {
            add_comment_meta($comment_id, 'thorius_ai_consent', 'yes', true);
        }
    }
    
    /**
     * Add consent notice to shortcode output
     * 
     * @param string $output Shortcode output
     * @return string Modified output with consent notice
     */
    public function add_consent_notice($output) {
        // Generate unique ID for this consent notice
        $consent_id = 'thorius-consent-' . uniqid();
        
        // Check if user has already given consent
        $user_id = get_current_user_id();
        $has_consent = $user_id ? get_user_meta($user_id, 'thorius_ai_consent', true) === 'yes' : false;
        
        // Don't show notice if user has already consented
        if ($has_consent) {
            return $output;
        }
        
        // Create consent notice
        $notice = '<div id="' . esc_attr($consent_id) . '" class="thorius-consent-notice">';
        $notice .= '<div class="thorius-consent-message">' . $this->get_consent_text() . '</div>';
        $notice .= '<div class="thorius-consent-actions">';
        $notice .= '<button class="thorius-accept-consent" data-id="' . esc_attr($consent_id) . '">' . __('Accept', 'vortex-ai-marketplace') . '</button>';
        $notice .= '<button class="thorius-decline-consent" data-id="' . esc_attr($consent_id) . '">' . __('Decline', 'vortex-ai-marketplace') . '</button>';
        $notice .= '</div>';
        $notice .= '</div>';
        
        // Add JavaScript for handling consent
        $notice .= '<script>
            document.addEventListener("DOMContentLoaded", function() {
                var consentNotice = document.getElementById("' . $consent_id . '");
                var thorius = document.querySelector(".thorius-container");
                
                if (consentNotice && thorius) {
                    thorius.style.display = "none";
                    
                    document.querySelector("#' . $consent_id . ' .thorius-accept-consent").addEventListener("click", function() {
                        saveConsent(true);
                        consentNotice.style.display = "none";
                        thorius.style.display = "block";
                    });
                    
                    document.querySelector("#' . $consent_id . ' .thorius-decline-consent").addEventListener("click", function() {
                        saveConsent(false);
                        consentNotice.style.display = "none";
                    });
                }
                
                function saveConsent(consent) {
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "' . esc_url(admin_url('admin-ajax.php')) . '", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.send(
                        "action=vortex_thorius_save_consent" + 
                        "&consent=" + (consent ? "yes" : "no") + 
                        "&nonce=' . esc_js(wp_create_nonce('vortex_thorius_consent_nonce')) . '"
                    );
                }
            });
        </script>';
        
        return $notice . $output;
    }
    
    /**
     * AJAX handler for saving user consent
     */
    public function ajax_save_consent() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_thorius_consent_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Get consent value
        $consent = isset($_POST['consent']) && $_POST['consent'] === 'yes';
        
        // Save consent for logged-in users
        $user_id = get_current_user_id();
        if ($user_id) {
            update_user_meta($user_id, 'thorius_ai_consent', $consent ? 'yes' : 'no');
        }
        
        // Set cookie for non-logged in users (30 days)
        if ($consent) {
            setcookie('thorius_ai_consent', 'yes', time() + (DAY_IN_SECONDS * 30), COOKIEPATH, COOKIE_DOMAIN);
        } else {
            setcookie('thorius_ai_consent', 'no', time() + (DAY_IN_SECONDS * 30), COOKIEPATH, COOKIE_DOMAIN);
        }
        
        // Track consent in analytics if enabled
        if (class_exists('Vortex_Thorius_Analytics')) {
            $analytics = new Vortex_Thorius_Analytics();
            $analytics->track_event('privacy_consent', array(
                'consent' => $consent ? 'granted' : 'declined'
            ));
        }
        
        wp_send_json_success(array('consent' => $consent));
        exit;
    }
    
    /**
     * Get user consent status
     * 
     * @param int $user_id User ID (optional)
     * @return bool Whether user has given consent
     */
    public function has_user_consent($user_id = 0) {
        // If no user ID provided, get current user
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Check user meta for logged-in users
        if ($user_id) {
            $consent = get_user_meta($user_id, 'thorius_ai_consent', true);
            if (!empty($consent)) {
                return $consent === 'yes';
            }
        }
        
        // Check cookie for non-logged in users
        if (isset($_COOKIE['thorius_ai_consent'])) {
            return $_COOKIE['thorius_ai_consent'] === 'yes';
        }
        
        // Default behavior based on site settings
        return get_option('vortex_thorius_default_consent', false);
    }
    
    /**
     * Get consent text
     * 
     * @return string Consent text
     */
    private function get_consent_text() {
        $custom_text = get_option('vortex_thorius_consent_text', '');
        
        if (!empty($custom_text)) {
            return $custom_text;
        }
        
        return __('I consent to having my conversation data processed by AI to improve the service. The data may be stored on third-party servers and used for training AI models.', 'vortex-ai-marketplace');
    }
} 