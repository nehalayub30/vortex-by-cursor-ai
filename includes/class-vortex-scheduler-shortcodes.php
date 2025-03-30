<?php
/**
 * Shortcode handler for the scheduling interface.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Vortex_Scheduler_Shortcodes
 *
 * Handles all shortcodes related to the scheduling interface.
 */
class Vortex_Scheduler_Shortcodes {
    /**
     * The single instance of the class.
     *
     * @var Vortex_Scheduler_Shortcodes
     */
    protected static $instance = null;

    /**
     * The marketplace instance.
     *
     * @var Vortex_AI_Marketplace
     */
    protected $marketplace;

    /**
     * The scheduler instance.
     *
     * @var Vortex_Scheduler
     */
    protected $scheduler;

    /**
     * Main Vortex_Scheduler_Shortcodes Instance.
     *
     * Ensures only one instance of Vortex_Scheduler_Shortcodes exists in memory at any one time.
     *
     * @return Vortex_Scheduler_Shortcodes - Main instance.
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    protected function __construct() {
        $this->marketplace = Vortex_AI_Marketplace::instance();
        $this->scheduler = Vortex_Scheduler::instance();
        
        $this->register_shortcodes();
    }

    /**
     * Register all shortcodes.
     */
    protected function register_shortcodes() {
        add_shortcode('vortex_schedule_nft', array($this, 'render_schedule_nft_form'));
        add_shortcode('vortex_schedule_exhibition', array($this, 'render_schedule_exhibition_form'));
        add_shortcode('vortex_schedule_auction', array($this, 'render_schedule_auction_form'));
        add_shortcode('vortex_scheduled_events', array($this, 'render_scheduled_events'));
    }

    /**
     * Render the NFT scheduling form.
     *
     * @param array $atts Shortcode attributes.
     * @return string The rendered form HTML.
     */
    public function render_schedule_nft_form($atts) {
        if (!is_user_logged_in()) {
            return $this->get_login_message();
        }

        $user_id = get_current_user_id();
        $nfts = $this->get_user_nfts($user_id);

        ob_start();
        include $this->marketplace->get_template_path('vortex-schedule-nft-form.php');
        return ob_get_clean();
    }

    /**
     * Render the exhibition scheduling form.
     *
     * @param array $atts Shortcode attributes.
     * @return string The rendered form HTML.
     */
    public function render_schedule_exhibition_form($atts) {
        if (!is_user_logged_in()) {
            return $this->get_login_message();
        }

        $user_id = get_current_user_id();
        $exhibitions = $this->get_user_exhibitions($user_id);

        ob_start();
        include $this->marketplace->get_template_path('vortex-schedule-exhibition-form.php');
        return ob_get_clean();
    }

    /**
     * Render the auction scheduling form.
     *
     * @param array $atts Shortcode attributes.
     * @return string The rendered form HTML.
     */
    public function render_schedule_auction_form($atts) {
        if (!is_user_logged_in()) {
            return $this->get_login_message();
        }

        $user_id = get_current_user_id();
        $auctions = $this->get_user_auctions($user_id);

        ob_start();
        include $this->marketplace->get_template_path('vortex-schedule-auction-form.php');
        return ob_get_clean();
    }

    /**
     * Render the scheduled events list.
     *
     * @param array $atts Shortcode attributes.
     * @return string The rendered events list HTML.
     */
    public function render_scheduled_events($atts) {
        if (!is_user_logged_in()) {
            return $this->get_login_message();
        }

        $user_id = get_current_user_id();
        $scheduled_events = $this->scheduler->get_user_scheduled_events($user_id);

        ob_start();
        include $this->marketplace->get_template_path('vortex-scheduled-events.php');
        return ob_get_clean();
    }

    /**
     * Get user's NFTs that are available for scheduling.
     *
     * @param int $user_id The user ID.
     * @return array Array of NFT posts.
     */
    protected function get_user_nfts($user_id) {
        return get_posts(array(
            'post_type' => 'vortex_nft',
            'author' => $user_id,
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_vortex_nft_status',
                    'value' => 'draft',
                    'compare' => '='
                )
            )
        ));
    }

    /**
     * Get user's exhibitions that are available for scheduling.
     *
     * @param int $user_id The user ID.
     * @return array Array of exhibition posts.
     */
    protected function get_user_exhibitions($user_id) {
        return get_posts(array(
            'post_type' => 'vortex_exhibition',
            'author' => $user_id,
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_vortex_exhibition_status',
                    'value' => 'draft',
                    'compare' => '='
                )
            )
        ));
    }

    /**
     * Get user's auctions that are available for scheduling.
     *
     * @param int $user_id The user ID.
     * @return array Array of auction posts.
     */
    protected function get_user_auctions($user_id) {
        return get_posts(array(
            'post_type' => 'vortex_auction',
            'author' => $user_id,
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_vortex_auction_status',
                    'value' => 'draft',
                    'compare' => '='
                )
            )
        ));
    }

    /**
     * Get the login message.
     *
     * @return string The login message HTML.
     */
    protected function get_login_message() {
        return sprintf(
            '<div class="vortex-notice">%s <a href="%s">%s</a></div>',
            esc_html__('Please log in to access this feature.', 'vortex-ai-marketplace'),
            esc_url(wp_login_url(get_permalink())),
            esc_html__('Log in', 'vortex-ai-marketplace')
        );
    }
}

// Initialize the shortcodes
Vortex_Scheduler_Shortcodes::instance(); 