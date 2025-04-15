<?php
/**
 * VORTEX Frontend Renderer
 *
 * Handles all front-end rendering in a modular way with output buffering
 * and consistent CSS namespacing to avoid theme conflicts.
 *
 * @package VORTEX
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_Frontend_Renderer {
    
    /**
     * Instance of this class
     * @var VORTEX_Frontend_Renderer
     */
    private static $instance = null;
    
    /**
     * Asset manager instance
     * @var VORTEX_Assets_Manager
     */
    private $assets_manager;
    
    /**
     * Container class for frontend output
     * @var string
     */
    private $container_class = 'marketplace-frontend-wrapper';
    
    /**
     * Version for cache busting
     * @var string
     */
    private $version;
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->assets_manager = vortex_assets_manager();
        $this->version = VORTEX_MARKETPLACE_VERSION;
        
        // Register shortcodes
        add_action('init', array($this, 'register_shortcodes'));
        
        // Register blocks if Gutenberg is available
        add_action('init', array($this, 'register_blocks'));
    }
    
    /**
     * Get class instance
     * @return VORTEX_Frontend_Renderer
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Register all shortcodes
     */
    public function register_shortcodes() {
        // Main marketplace content shortcode
        add_shortcode('marketplace_display', array($this, 'marketplace_display_callback'));
        
        // Featured artwork shortcode
        add_shortcode('marketplace_featured', array($this, 'marketplace_featured_callback'));
        
        // Artist profiles shortcode
        add_shortcode('marketplace_artist_profiles', array($this, 'marketplace_artist_profiles_callback'));
        
        // Wallet connection shortcode
        add_shortcode('marketplace_wallet', array($this, 'marketplace_wallet_callback'));
        
        // DAO metrics shortcode
        add_shortcode('marketplace_dao_metrics', array($this, 'marketplace_dao_metrics_callback'));
    }
    
    /**
     * Register Gutenberg blocks
     */
    public function register_blocks() {
        // Check if Gutenberg is available
        if (!function_exists('register_block_type')) {
            return;
        }
        
        // Register marketplace display block
        register_block_type('vortex/marketplace-display', array(
            'editor_script' => 'vortex-blocks',
            'editor_style' => 'vortex-blocks-editor',
            'render_callback' => array($this, 'marketplace_display_callback'),
            'attributes' => array(
                'title' => array(
                    'type' => 'string',
                    'default' => 'Marketplace'
                ),
                'display' => array(
                    'type' => 'string',
                    'default' => 'grid'
                ),
                'limit' => array(
                    'type' => 'number',
                    'default' => 12
                ),
                'className' => array(
                    'type' => 'string',
                    'default' => ''
                )
            )
        ));
        
        // Register featured artwork block
        register_block_type('vortex/marketplace-featured', array(
            'editor_script' => 'vortex-blocks',
            'editor_style' => 'vortex-blocks-editor',
            'render_callback' => array($this, 'marketplace_featured_callback'),
            'attributes' => array(
                'title' => array(
                    'type' => 'string',
                    'default' => 'Featured Artwork'
                ),
                'limit' => array(
                    'type' => 'number',
                    'default' => 3
                ),
                'className' => array(
                    'type' => 'string',
                    'default' => ''
                )
            )
        ));
    }
    
    /**
     * Main marketplace display callback
     * 
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string HTML output
     */
    public function marketplace_display_callback($atts, $content = null) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'title' => 'Marketplace',
            'display' => 'grid', // grid, list, carousel
            'category' => '',
            'artist' => '',
            'limit' => 12,
            'orderby' => 'date',
            'order' => 'desc',
            'className' => '',
        ), $atts, 'marketplace_display');
        
        // Enqueue necessary assets
        $this->assets_manager->enqueue_style('vortex-dao');
        $this->assets_manager->enqueue_script('vortex-dao');
        
        // Start output buffering
        ob_start();
        
        // Get marketplace content
        $this->render_marketplace_content($atts);
        
        // Get buffered content
        $output = ob_get_clean();
        
        // Wrap in container
        return $this->wrap_content($output, $atts);
    }
    
    /**
     * Featured artwork callback
     * 
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string HTML output
     */
    public function marketplace_featured_callback($atts, $content = null) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'title' => 'Featured Artwork',
            'limit' => 3,
            'className' => '',
        ), $atts, 'marketplace_featured');
        
        // Enqueue necessary assets
        $this->assets_manager->enqueue_style('vortex-dao');
        
        // Start output buffering
        ob_start();
        
        // Get featured artwork
        $this->render_featured_artwork($atts);
        
        // Get buffered content
        $output = ob_get_clean();
        
        // Wrap in container
        return $this->wrap_content($output, $atts);
    }
    
    /**
     * Artist profiles callback
     * 
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string HTML output
     */
    public function marketplace_artist_profiles_callback($atts, $content = null) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'title' => 'Artists',
            'limit' => 8,
            'orderby' => 'name',
            'order' => 'asc',
            'className' => '',
        ), $atts, 'marketplace_artist_profiles');
        
        // Enqueue necessary assets
        $this->assets_manager->enqueue_style('vortex-dao');
        
        // Start output buffering
        ob_start();
        
        // Get artist profiles
        $this->render_artist_profiles($atts);
        
        // Get buffered content
        $output = ob_get_clean();
        
        // Wrap in container
        return $this->wrap_content($output, $atts);
    }
    
    /**
     * Wallet connection callback
     * 
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string HTML output
     */
    public function marketplace_wallet_callback($atts, $content = null) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'title' => 'Connect Wallet',
            'show_balance' => 'yes',
            'className' => '',
        ), $atts, 'marketplace_wallet');
        
        // Enqueue necessary assets
        $this->assets_manager->enqueue_style('vortex-dao');
        $this->assets_manager->enqueue_script('vortex-wallet-connection');
        
        // Start output buffering
        ob_start();
        
        // Get wallet connection
        $this->render_wallet_connection($atts);
        
        // Get buffered content
        $output = ob_get_clean();
        
        // Wrap in container
        return $this->wrap_content($output, $atts);
    }
    
    /**
     * DAO metrics callback
     * 
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string HTML output
     */
    public function marketplace_dao_metrics_callback($atts, $content = null) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'title' => 'DAO Metrics',
            'show_chart' => 'yes',
            'type' => 'all',
            'className' => '',
        ), $atts, 'marketplace_dao_metrics');
        
        // Enqueue necessary assets
        $this->assets_manager->enqueue_blockchain_metrics_assets();
        
        // Start output buffering
        ob_start();
        
        // Get DAO metrics
        $this->render_dao_metrics($atts);
        
        // Get buffered content
        $output = ob_get_clean();
        
        // Wrap in container
        return $this->wrap_content($output, $atts);
    }
    
    /**
     * Wrap content in a container div
     * 
     * @param string $content The content to wrap
     * @param array $atts Attributes containing className if any
     * @return string Wrapped content
     */
    private function wrap_content($content, $atts = array()) {
        $class = $this->container_class;
        
        // Add custom class if provided
        if (!empty($atts['className'])) {
            $class .= ' ' . esc_attr($atts['className']);
        }
        
        // Add title if provided
        $title_html = '';
        if (!empty($atts['title'])) {
            $title_html = '<h2 class="marketplace-frontend-title">' . esc_html($atts['title']) . '</h2>';
        }
        
        return sprintf(
            '<div class="%s">%s<div class="marketplace-frontend-content">%s</div></div>',
            esc_attr($class),
            $title_html,
            $content
        );
    }
    
    /**
     * Render marketplace content
     * 
     * @param array $atts Display attributes
     */
    private function render_marketplace_content($atts) {
        // Check if class exists before instantiating
        if (!class_exists('VORTEX_Artworks')) {
            echo '<p class="marketplace-notice">Marketplace module is not available.</p>';
            return;
        }
        
        // Get marketplace artworks
        $artworks = VORTEX_Artworks::get_instance();
        $items = $artworks->get_artworks(array(
            'limit' => $atts['limit'],
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'category' => $atts['category'],
            'artist' => $atts['artist'],
        ));
        
        if (empty($items)) {
            echo '<p class="marketplace-notice">No artworks found.</p>';
            return;
        }
        
        // Display items based on selected display type
        $display_method = 'render_' . $atts['display'] . '_display';
        
        if (method_exists($this, $display_method)) {
            call_user_func(array($this, $display_method), $items);
        } else {
            // Default to grid display
            $this->render_grid_display($items);
        }
    }
    
    /**
     * Render grid display of artworks
     * 
     * @param array $items Artwork items
     */
    private function render_grid_display($items) {
        ?>
        <div class="marketplace-grid-display">
            <?php foreach ($items as $item) : ?>
                <div class="marketplace-grid-item">
                    <div class="marketplace-artwork-image">
                        <a href="<?php echo esc_url($item['url']); ?>">
                            <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['title']); ?>">
                        </a>
                    </div>
                    <div class="marketplace-artwork-details">
                        <h3 class="marketplace-artwork-title">
                            <a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['title']); ?></a>
                        </h3>
                        <div class="marketplace-artwork-artist">
                            <?php echo esc_html($item['artist']); ?>
                        </div>
                        <div class="marketplace-artwork-price">
                            <?php echo esc_html($item['price']); ?> TOLA
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render list display of artworks
     * 
     * @param array $items Artwork items
     */
    private function render_list_display($items) {
        ?>
        <div class="marketplace-list-display">
            <?php foreach ($items as $item) : ?>
                <div class="marketplace-list-item">
                    <div class="marketplace-list-image">
                        <a href="<?php echo esc_url($item['url']); ?>">
                            <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['title']); ?>">
                        </a>
                    </div>
                    <div class="marketplace-list-details">
                        <h3 class="marketplace-list-title">
                            <a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['title']); ?></a>
                        </h3>
                        <div class="marketplace-list-artist">
                            By <?php echo esc_html($item['artist']); ?>
                        </div>
                        <div class="marketplace-list-description">
                            <?php echo wp_kses_post($item['excerpt']); ?>
                        </div>
                        <div class="marketplace-list-price">
                            <?php echo esc_html($item['price']); ?> TOLA
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render carousel display of artworks
     * 
     * @param array $items Artwork items
     */
    private function render_carousel_display($items) {
        // Enqueue carousel script
        $this->assets_manager->enqueue_script('vortex-carousel');
        ?>
        <div class="marketplace-carousel-display" data-speed="3000" data-auto="true">
            <div class="marketplace-carousel-items">
                <?php foreach ($items as $item) : ?>
                    <div class="marketplace-carousel-item">
                        <div class="marketplace-carousel-image">
                            <a href="<?php echo esc_url($item['url']); ?>">
                                <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['title']); ?>">
                            </a>
                        </div>
                        <div class="marketplace-carousel-details">
                            <h3 class="marketplace-carousel-title">
                                <a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['title']); ?></a>
                            </h3>
                            <div class="marketplace-carousel-artist">
                                <?php echo esc_html($item['artist']); ?>
                            </div>
                            <div class="marketplace-carousel-price">
                                <?php echo esc_html($item['price']); ?> TOLA
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="marketplace-carousel-nav">
                <button class="marketplace-carousel-prev">&laquo;</button>
                <button class="marketplace-carousel-next">&raquo;</button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render featured artwork
     * 
     * @param array $atts Display attributes
     */
    private function render_featured_artwork($atts) {
        // Check if class exists before instantiating
        if (!class_exists('VORTEX_Artworks')) {
            echo '<p class="marketplace-notice">Marketplace module is not available.</p>';
            return;
        }
        
        // Get featured artworks
        $artworks = VORTEX_Artworks::get_instance();
        $items = $artworks->get_featured_artworks($atts['limit']);
        
        if (empty($items)) {
            echo '<p class="marketplace-notice">No featured artworks found.</p>';
            return;
        }
        
        ?>
        <div class="marketplace-featured-display">
            <?php foreach ($items as $item) : ?>
                <div class="marketplace-featured-item">
                    <div class="marketplace-featured-image">
                        <a href="<?php echo esc_url($item['url']); ?>">
                            <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['title']); ?>">
                        </a>
                        <div class="marketplace-featured-badge">Featured</div>
                    </div>
                    <div class="marketplace-featured-details">
                        <h3 class="marketplace-featured-title">
                            <a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['title']); ?></a>
                        </h3>
                        <div class="marketplace-featured-artist">
                            <?php echo esc_html($item['artist']); ?>
                        </div>
                        <div class="marketplace-featured-price">
                            <?php echo esc_html($item['price']); ?> TOLA
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render artist profiles
     * 
     * @param array $atts Display attributes
     */
    private function render_artist_profiles($atts) {
        // Check if class exists before instantiating
        if (!class_exists('VORTEX_Artists')) {
            echo '<p class="marketplace-notice">Artists module is not available.</p>';
            return;
        }
        
        // Get artist profiles
        $artists = VORTEX_Artists::get_instance();
        $items = $artists->get_artists(array(
            'limit' => $atts['limit'],
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
        ));
        
        if (empty($items)) {
            echo '<p class="marketplace-notice">No artists found.</p>';
            return;
        }
        
        ?>
        <div class="marketplace-artists-display">
            <?php foreach ($items as $item) : ?>
                <div class="marketplace-artist-item">
                    <div class="marketplace-artist-avatar">
                        <a href="<?php echo esc_url($item['url']); ?>">
                            <img src="<?php echo esc_url($item['avatar']); ?>" alt="<?php echo esc_attr($item['name']); ?>">
                        </a>
                    </div>
                    <div class="marketplace-artist-details">
                        <h3 class="marketplace-artist-name">
                            <a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['name']); ?></a>
                        </h3>
                        <div class="marketplace-artist-bio">
                            <?php echo wp_kses_post($item['bio']); ?>
                        </div>
                        <div class="marketplace-artist-stats">
                            <span class="marketplace-artist-artworks"><?php echo esc_html($item['artwork_count']); ?> artworks</span>
                            <?php if (!empty($item['sales_count'])) : ?>
                                <span class="marketplace-artist-sales"><?php echo esc_html($item['sales_count']); ?> sales</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render wallet connection
     * 
     * @param array $atts Display attributes
     */
    private function render_wallet_connection($atts) {
        ?>
        <div class="marketplace-wallet-connection">
            <div class="marketplace-wallet-status">
                <div class="marketplace-wallet-status-indicator"></div>
                <div class="marketplace-wallet-status-text">Not connected</div>
            </div>
            <div class="marketplace-wallet-connect">
                <button class="marketplace-connect-wallet-button">Connect Wallet</button>
            </div>
            <?php if ($atts['show_balance'] === 'yes') : ?>
                <div class="marketplace-wallet-balance">
                    <div class="marketplace-wallet-balance-label">TOLA Balance:</div>
                    <div class="marketplace-wallet-balance-amount">0</div>
                </div>
            <?php endif; ?>
            <div class="marketplace-wallet-dropdown" style="display: none;">
                <div class="marketplace-wallet-address"></div>
                <div class="marketplace-wallet-actions">
                    <button class="marketplace-copy-address-button">Copy Address</button>
                    <button class="marketplace-disconnect-wallet-button">Disconnect</button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render DAO metrics
     * 
     * @param array $atts Display attributes
     */
    private function render_dao_metrics($atts) {
        // Check if metrics class exists
        if (!class_exists('VORTEX_DAO_Metrics')) {
            echo '<p class="marketplace-notice">DAO metrics module is not available.</p>';
            return;
        }
        
        // Get DAO metrics
        $metrics = VORTEX_DAO_Metrics::get_instance();
        $data = $metrics->get_metrics();
        
        if (empty($data)) {
            echo '<p class="marketplace-notice">No metrics data available.</p>';
            return;
        }
        
        ?>
        <div class="marketplace-dao-metrics">
            <div class="marketplace-metrics-summary">
                <div class="marketplace-metric-item">
                    <div class="marketplace-metric-label">TOLA Price</div>
                    <div class="marketplace-metric-value">$<?php echo esc_html(number_format($data['token']['price'], 4)); ?></div>
                </div>
                <div class="marketplace-metric-item">
                    <div class="marketplace-metric-label">Market Cap</div>
                    <div class="marketplace-metric-value">$<?php echo esc_html(number_format($data['token']['market_cap'], 0)); ?></div>
                </div>
                <div class="marketplace-metric-item">
                    <div class="marketplace-metric-label">Total Holders</div>
                    <div class="marketplace-metric-value"><?php echo esc_html(number_format($data['holders']['total_holders'], 0)); ?></div>
                </div>
                <div class="marketplace-metric-item">
                    <div class="marketplace-metric-label">Active Proposals</div>
                    <div class="marketplace-metric-value"><?php echo esc_html($data['governance']['proposal_stats']['active']); ?></div>
                </div>
            </div>
            
            <?php if ($atts['show_chart'] === 'yes' && !empty($data['token']['price_history'])) : ?>
                <div class="marketplace-metrics-chart">
                    <canvas id="marketplace-price-chart" width="600" height="300"></canvas>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        var ctx = document.getElementById('marketplace-price-chart').getContext('2d');
                        var priceData = <?php echo json_encode($data['token']['price_history']); ?>;
                        
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: priceData.map(function(item) { return item.date; }),
                                datasets: [{
                                    label: 'TOLA Price (USD)',
                                    data: priceData.map(function(item) { return item.price; }),
                                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                    borderColor: 'rgba(75, 192, 192, 1)',
                                    borderWidth: 2,
                                    tension: 0.4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false
                            }
                        });
                    });
                </script>
            <?php endif; ?>
        </div>
        <?php
    }
}

// Initialize the renderer
function vortex_frontend_renderer() {
    return VORTEX_Frontend_Renderer::get_instance();
}

// Start the renderer
vortex_frontend_renderer(); 