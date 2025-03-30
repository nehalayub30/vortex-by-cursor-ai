# VORTEX AI AGENTS: Implementation Examples

This document provides practical code examples for integrating VORTEX AI AGENTS into WordPress websites, enabling you to leverage the full power of the AI agents and their features.

## Table of Contents
- [Shortcodes](#shortcodes)
- [Block Integration](#block-integration)
- [Theme Integration](#theme-integration)
- [WooCommerce Integration](#woocommerce-integration)
- [Advanced Customization](#advanced-customization)
- [API Usage](#api-usage)

## Shortcodes

VORTEX AI AGENTS provides easy-to-use shortcodes for quick integration into any WordPress post or page.

### Artwork Analysis Dashboard

Display a comprehensive analysis dashboard for an artwork:

```
[vortex_artwork_analysis id="123" display="full"]
```

Parameters:
- `id`: Artwork ID
- `display`: Display mode (`full`, `compact`, `market`, `style`, `audience`)
- `show_recommendations`: Whether to show recommendations (`true`, `false`)

### Artist Market Position

Display an artist's market position and trend analysis:

```
[vortex_artist_market artist_id="456" timeframe="1_year" metrics="price,demand,visibility"]
```

Parameters:
- `artist_id`: Artist ID
- `timeframe`: Analysis timeframe (`3_months`, `6_months`, `1_year`, `5_years`)
- `metrics`: Comma-separated list of metrics to display

### HURAII Style Generation

Allow users to generate artwork previews based on parameters:

```
[vortex_huraii_generation style="abstract" influences="kandinsky,pollock" prompt="vibrant cityscape at sunset"]
```

Parameters:
- `style`: Art style
- `influences`: Comma-separated list of artistic influences
- `prompt`: Text prompt describing the desired artwork
- `allow_download`: Whether to allow users to download the generated image

### Market Trends Widget

Display current art market trends:

```
[vortex_market_trends category="contemporary" display_format="chart" limit="5"]
```

Parameters:
- `category`: Art category
- `display_format`: Display format (`chart`, `list`, `grid`)
- `limit`: Number of trends to display

## Block Integration

VORTEX AI AGENTS is fully compatible with the WordPress block editor (Gutenberg).

### Art Market Analysis Block

```javascript
// Register the block (in your plugin's JavaScript)
registerBlockType('vortex-ai-agents/market-analysis', {
    title: __('Art Market Analysis', 'vortex-ai-agents'),
    icon: 'chart-line',
    category: 'vortex-blocks',
    attributes: {
        artworkId: {
            type: 'number',
        },
        displayOptions: {
            type: 'object',
            default: {
                showPriceAnalysis: true,
                showMarketFit: true,
                showAudience: true,
                showTrends: true
            }
        }
    },
    edit: function(props) {
        // Block editor UI
        return (
            <div className="vortex-block-editor">
                <InspectorControls>
                    {/* Controls for the block */}
                </InspectorControls>
                <div className="vortex-preview">
                    <ServerSideRender
                        block="vortex-ai-agents/market-analysis"
                        attributes={props.attributes}
                    />
                </div>
            </div>
        );
    },
    save: function() {
        // Rendered by PHP
        return null;
    }
});
```

```php
// Handle the server-side rendering (in your plugin's PHP)
function vortex_market_analysis_render_callback($attributes) {
    $artwork_id = isset($attributes['artworkId']) ? intval($attributes['artworkId']) : 0;
    $display_options = isset($attributes['displayOptions']) ? $attributes['displayOptions'] : [];
    
    // Initialize the market analysis service
    $analysis_service = new VortexAIAgents\Services\Art_Market_Analytics();
    $analysis = $analysis_service->analyze_artwork_potential($artwork_id);
    
    // Render the analysis display
    ob_start();
    include VORTEX_AI_AGENTS_PATH . 'templates/market-analysis.php';
    return ob_get_clean();
}

register_block_type('vortex-ai-agents/market-analysis', [
    'attributes' => [
        'artworkId' => [
            'type' => 'number',
        ],
        'displayOptions' => [
            'type' => 'object',
            'default' => [
                'showPriceAnalysis' => true,
                'showMarketFit' => true,
                'showAudience' => true,
                'showTrends' => true
            ]
        ]
    ],
    'render_callback' => 'vortex_market_analysis_render_callback'
]);
```

### HURAII Artwork Generator Block

```javascript
// Register HURAII generator block
registerBlockType('vortex-ai-agents/huraii-generator', {
    title: __('HURAII Artwork Generator', 'vortex-ai-agents'),
    icon: 'art',
    category: 'vortex-blocks',
    attributes: {
        style: {
            type: 'string',
            default: 'abstract'
        },
        influences: {
            type: 'array',
            default: []
        },
        prompt: {
            type: 'string',
            default: ''
        },
        generatedImageUrl: {
            type: 'string'
        }
    },
    edit: function(props) {
        const { attributes, setAttributes } = props;
        
        const generateArtwork = () => {
            // API call to generate artwork
            apiFetch({
                path: '/vortex-ai/v1/huraii/generate',
                method: 'POST',
                data: {
                    style: attributes.style,
                    influences: attributes.influences,
                    prompt: attributes.prompt
                }
            }).then(response => {
                setAttributes({ generatedImageUrl: response.image_url });
            });
        };
        
        return (
            <div className="huraii-generator">
                <InspectorControls>
                    {/* Controls */}
                </InspectorControls>
                
                <TextControl
                    label={__('Prompt', 'vortex-ai-agents')}
                    value={attributes.prompt}
                    onChange={(prompt) => setAttributes({ prompt })}
                />
                
                <SelectControl
                    label={__('Style', 'vortex-ai-agents')}
                    value={attributes.style}
                    options={[
                        { label: 'Abstract', value: 'abstract' },
                        { label: 'Impressionist', value: 'impressionist' },
                        { label: 'Pop Art', value: 'pop-art' }
                        // More options
                    ]}
                    onChange={(style) => setAttributes({ style })}
                />
                
                <Button isPrimary onClick={generateArtwork}>
                    {__('Generate Artwork', 'vortex-ai-agents')}
                </Button>
                
                {attributes.generatedImageUrl && (
                    <div className="generated-artwork">
                        <img src={attributes.generatedImageUrl} alt="Generated artwork" />
                    </div>
                )}
            </div>
        );
    },
    save: function(props) {
        const { attributes } = props;
        
        return (
            <div className="huraii-generated-artwork">
                {attributes.generatedImageUrl && (
                    <img src={attributes.generatedImageUrl} alt="AI-generated artwork" />
                )}
            </div>
        );
    }
});
```

## Theme Integration

For deeper integration with WordPress themes, you can use the following code examples.

### Functions.php Integration

```php
/**
 * Add VORTEX AI functionality to the theme
 */
function theme_vortex_ai_integration() {
    // Only load if the plugin is active
    if (!class_exists('VortexAIAgents\\Plugin')) {
        return;
    }
    
    // Add theme support for VORTEX AI features
    add_theme_support('vortex-ai-agents');
    
    // Add custom style for VORTEX AI components
    wp_enqueue_style(
        'theme-vortex-styles', 
        get_template_directory_uri() . '/assets/css/vortex-integration.css',
        ['vortex-ai-public-styles']
    );
}
add_action('after_setup_theme', 'theme_vortex_ai_integration');

/**
 * Display artwork analytics in single artwork template
 */
function theme_display_artwork_analytics() {
    if (!is_singular('artwork')) {
        return;
    }
    
    $artwork_id = get_the_ID();
    
    // Initialize analytics display
    $artwork_analytics = new VortexAIAgents\Templates\Artwork_Analytics_Display();
    $artwork_analytics->render([
        'artwork_id' => $artwork_id,
        'layout' => 'theme-integrated',
        'show_sections' => ['market_fit', 'price_analysis', 'audience_match']
    ]);
}
add_action('theme_after_artwork_content', 'theme_display_artwork_analytics');
```

### Artist Profile Template

```php
<?php
/**
 * Template Name: Artist Profile with VORTEX AI
 */

get_header();

$artist_id = get_the_author_meta('ID');

// Get market analysis for the artist
$strategist = new VortexAIAgents\Agents\BusinessStrategist();
$market_position = $strategist->analyze_artist_position($artist_id);

// Get career recommendations
$career_guidance = $strategist->get_career_guidance($artist_id);
?>

<div class="artist-profile">
    <div class="artist-info">
        <!-- Standard artist information -->
    </div>
    
    <div class="market-position">
        <h2><?php _e('Market Position', 'theme-domain'); ?></h2>
        
        <div class="market-metrics">
            <div class="metric">
                <span class="label"><?php _e('Market Fit', 'theme-domain'); ?></span>
                <div class="score"><?php echo esc_html($market_position['market_fit_score'] * 100); ?>%</div>
            </div>
            
            <div class="metric">
                <span class="label"><?php _e('Growth Trend', 'theme-domain'); ?></span>
                <div class="trend <?php echo $market_position['growth_trend'] > 0 ? 'positive' : 'negative'; ?>">
                    <?php echo esc_html($market_position['growth_trend'] * 100); ?>%
                </div>
            </div>
            
            <!-- More metrics -->
        </div>
        
        <div class="career-guidance">
            <h3><?php _e('Strategic Recommendations', 'theme-domain'); ?></h3>
            <ul>
                <?php foreach ($career_guidance['recommendations'] as $recommendation) : ?>
                    <li>
                        <strong><?php echo esc_html($recommendation['title']); ?></strong>
                        <p><?php echo esc_html($recommendation['description']); ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    
    <div class="style-evolution">
        <h2><?php _e('Artistic Style Evolution', 'theme-domain'); ?></h2>
        <?php
        // Get style analysis from HURAII
        $huraii = new VortexAIAgents\Agents\HURAII();
        $style_analysis = $huraii->analyze_artist_style_evolution($artist_id);
        
        // Display style evolution chart
        $style_chart = new VortexAIAgents\Templates\Style_Evolution_Chart();
        $style_chart->render([
            'style_data' => $style_analysis,
            'chart_height' => 400,
            'show_legend' => true
        ]);
        ?>
    </div>
</div>

<?php get_footer(); ?>
```

## WooCommerce Integration

For sites using WooCommerce to sell artwork, these integrations enhance the shopping experience with AI insights.

### Product Price Recommendation

```php
/**
 * Add AI price recommendation to WooCommerce product editor
 */
function vortex_add_price_recommendation_field() {
    global $post;
    
    // Only for artwork product type
    if (!has_term('artwork', 'product_cat', $post)) {
        return;
    }
    
    // Get price recommendation from Cloe
    $cloe = new VortexAIAgents\Agents\Cloe();
    $price_data = $cloe->optimize_price($post->ID);
    
    if (!$price_data) {
        return;
    }
    
    ?>
    <div class="options_group vortex-price-recommendations">
        <p class="form-field">
            <label><?php _e('AI Price Recommendation', 'vortex-ai-agents'); ?></label>
            <span class="price-range">
                <?php echo wc_price($price_data['min_price']); ?> - <?php echo wc_price($price_data['max_price']); ?>
            </span>
            <span class="optimal-price">
                <?php _e('Optimal', 'vortex-ai-agents'); ?>: <?php echo wc_price($price_data['optimal_price']); ?>
            </span>
            <span class="description"><?php echo esc_html($price_data['justification']); ?></span>
        </p>
        <p class="form-field">
            <button type="button" class="button apply-recommended-price" data-price="<?php echo esc_attr($price_data['optimal_price']); ?>">
                <?php _e('Apply Recommended Price', 'vortex-ai-agents'); ?>
            </button>
        </p>
    </div>
    <?php
}
add_action('woocommerce_product_options_pricing', 'vortex_add_price_recommendation_field');

/**
 * Add market analytics tab to product page
 */
function vortex_add_market_analysis_tab($tabs) {
    global $post;
    
    // Only for artwork product type
    if (!has_term('artwork', 'product_cat', $post)) {
        return $tabs;
    }
    
    $tabs['vortex_market_analysis'] = [
        'title'    => __('Market Analysis', 'vortex-ai-agents'),
        'priority' => 25,
        'callback' => 'vortex_market_analysis_tab_content'
    ];
    
    return $tabs;
}
add_filter('woocommerce_product_tabs', 'vortex_add_market_analysis_tab');

/**
 * Display market analysis tab content
 */
function vortex_market_analysis_tab_content() {
    global $post;
    
    $analysis_service = new VortexAIAgents\Services\Art_Market_Analytics();
    $analysis = $analysis_service->analyze_artwork_potential($post->ID);
    
    // Render market analysis template
    include VORTEX_AI_AGENTS_PATH . 'templates/woocommerce/market-analysis-tab.php';
}
```

### Similar Artwork Recommendations

```php
/**
 * Add AI-powered related products based on artistic style
 */
function vortex_ai_related_products() {
    global $post;
    
    // Only for artwork products
    if (!has_term('artwork', 'product_cat', $post)) {
        return;
    }
    
    // Remove default related products
    remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);
    
    // Get style-based recommendations
    $huraii = new VortexAIAgents\Agents\HURAII();
    $related_artworks = $huraii->find_style_similar_artworks($post->ID, [
        'limit' => 4,
        'min_similarity' => 0.7
    ]);
    
    if (empty($related_artworks)) {
        return;
    }
    
    // Format for WooCommerce
    $related_ids = wp_list_pluck($related_artworks, 'id');
    
    // Display related products
    woocommerce_related_products([
        'posts_per_page' => 4,
        'columns'        => 4,
        'orderby'        => 'none',
        'post__in'       => $related_ids
    ]);
}
add_action('woocommerce_after_single_product_summary', 'vortex_ai_related_products', 20);
```

## Advanced Customization

For developers who want to extend or customize the plugin's functionality.

### Creating a Custom Agent Integration

```php
/**
 * Custom integration with VORTEX AI Agents
 */
class My_Custom_Integration {
    private $huraii;
    private $cloe;
    private $strategist;
    
    public function __construct() {
        // Initialize agents
        $this->huraii = new VortexAIAgents\Agents\HURAII();
        $this->cloe = new VortexAIAgents\Agents\Cloe();
        $this->strategist = new VortexAIAgents\Agents\BusinessStrategist();
        
        // Add hooks
        add_action('init', [$this, 'register_custom_post_type']);
        add_filter('the_content', [$this, 'enhance_content_with_ai']);
    }
    
    /**
     * Register custom exhibition post type
     */
    public function register_custom_post_type() {
        register_post_type('exhibition', [
            'public' => true,
            'label' => 'Exhibitions',
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'menu_icon' => 'dashicons-format-gallery'
        ]);
    }
    
    /**
     * Enhance exhibition content with AI insights
     */
    public function enhance_content_with_ai($content) {
        if (!is_singular('exhibition')) {
            return $content;
        }
        
        $exhibition_id = get_the_ID();
        $artworks = $this->get_exhibition_artworks($exhibition_id);
        
        if (empty($artworks)) {
            return $content;
        }
        
        // Get exhibition analysis from Business Strategist
        $exhibition_analysis = $this->strategist->analyze_exhibition_potential([
            'exhibition_id' => $exhibition_id,
            'artworks' => $artworks
        ]);
        
        // Get audience match from Cloe
        $audience_match = $this->cloe->analyze_exhibition_audience($exhibition_id);
        
        // Get curatorial feedback from HURAII
        $curatorial_feedback = $this->huraii->analyze_exhibition_curation($exhibition_id);
        
        // Add AI insights to the content
        $ai_insights = $this->render_exhibition_insights([
            'exhibition_analysis' => $exhibition_analysis,
            'audience_match' => $audience_match,
            'curatorial_feedback' => $curatorial_feedback
        ]);
        
        return $content . $ai_insights;
    }
    
    /**
     * Get artworks in an exhibition
     */
    private function get_exhibition_artworks($exhibition_id) {
        // Implementation depends on how artworks are associated with exhibitions
        $artwork_ids = get_post_meta($exhibition_id, 'exhibition_artworks', true);
        return is_array($artwork_ids) ? $artwork_ids : [];
    }
    
    /**
     * Render exhibition insights HTML
     */
    private function render_exhibition_insights($data) {
        ob_start();
        include plugin_dir_path(__FILE__) . 'templates/exhibition-insights.php';
        return ob_get_clean();
    }
}

// Initialize the custom integration
new My_Custom_Integration();
```

### Custom HURAII Artwork Generation with User Inputs

```php
/**
 * HURAII artwork generation form and processing
 */
class HURAII_Artwork_Generator_Form {
    public function __construct() {
        add_shortcode('huraii_generator_form', [$this, 'render_form']);
        add_action('wp_ajax_generate_huraii_artwork', [$this, 'generate_artwork']);
        add_action('wp_ajax_nopriv_generate_huraii_artwork', [$this, 'generate_artwork']);
    }
    
    /**
     * Render generator form shortcode
     */
    public function render_form($atts) {
        $attributes = shortcode_atts([
            'styles' => 'abstract,impressionist,cubist,surrealist,pop-art',
            'max_influences' => 3,
            'default_prompt' => '',
            'show_advanced' => 'true'
        ], $atts);
        
        // Enqueue necessary scripts
        wp_enqueue_script(
            'huraii-generator',
            plugin_dir_url(__FILE__) . 'assets/js/huraii-generator.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_localize_script('huraii-generator', 'huraii_generator', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('huraii_generator_nonce')
        ]);
        
        // Render form
        ob_start();
        include plugin_dir_path(__FILE__) . 'templates/huraii-generator-form.php';
        return ob_get_clean();
    }
    
    /**
     * Handle AJAX request to generate artwork
     */
    public function generate_artwork() {
        check_ajax_referer('huraii_generator_nonce', 'nonce');
        
        $style = isset($_POST['style']) ? sanitize_text_field($_POST['style']) : 'abstract';
        $influences = isset($_POST['influences']) ? explode(',', sanitize_text_field($_POST['influences'])) : [];
        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
        $color_palette = isset($_POST['color_palette']) ? sanitize_text_field($_POST['color_palette']) : '';
        $dimensions = isset($_POST['dimensions']) ? sanitize_text_field($_POST['dimensions']) : '1024x1024';
        
        // Validate inputs
        if (empty($prompt)) {
            wp_send_json_error(['message' => __('Please provide a prompt', 'vortex-ai-agents')]);
            return;
        }
        
        // Initialize HURAII
        $huraii = new VortexAIAgents\Agents\HURAII();
        
        // Generate artwork
        try {
            $result = $huraii->generate_artwork([
                'style' => $style,
                'influences' => $influences,
                'prompt' => $prompt,
                'color_palette' => $color_palette,
                'dimensions' => $dimensions
            ]);
            
            if (!$result || !isset($result['image_url'])) {
                throw new Exception(__('Failed to generate artwork', 'vortex-ai-agents'));
            }
            
            wp_send_json_success([
                'image_url' => $result['image_url'],
                'generation_id' => $result['generation_id'],
                'style_analysis' => $result['style_analysis']
            ]);
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}

// Initialize the generator form
new HURAII_Artwork_Generator_Form();
```

## API Usage

Examples of using the VORTEX AI AGENTS REST API endpoints.

### JavaScript API Client (Frontend)

```javascript
/**
 * VORTEX AI AGENTS API Client
 */
class VortexAiClient {
    constructor() {
        this.baseUrl = '/wp-json/vortex-ai/v1';
        this.nonce = vortexAiSettings.nonce; // Passed from wp_localize_script
    }
    
    /**
     * Get artwork analytics
     */
    async getArtworkAnalytics(artworkId) {
        try {
            const response = await fetch(`${this.baseUrl}/artwork-analytics/${artworkId}`, {
                headers: {
                    'X-WP-Nonce': this.nonce
                }
            });
            
            if (!response.ok) {
                throw new Error('API request failed');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error fetching artwork analytics:', error);
            throw error;
        }
    }
    
    /**
     * Generate artwork with HURAII
     */
    async generateArtwork(parameters) {
        try {
            const response = await fetch(`${this.baseUrl}/huraii/generate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.nonce
                },
                body: JSON.stringify(parameters)
            });
            
            if (!response.ok) {
                throw new Error('Artwork generation failed');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error generating artwork:', error);
            throw error;
        }
    }
    
    /**
     * Get market trends from Cloe
     */
    async getMarketTrends(parameters) {
        try {
            const queryParams = new URLSearchParams(parameters).toString();
            const response = await fetch(`${this.baseUrl}/market-trends?${queryParams}`, {
                headers: {
                    'X-WP-Nonce': this.nonce
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to fetch market trends');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error fetching market trends:', error);
            throw error;
        }
    }
    
    /**
     * Get investment analysis from Business Strategist
     */
    async getInvestmentAnalysis(artworkId) {
        try {
            const response = await fetch(`${this.baseUrl}/investment-analysis/${artworkId}`, {
                headers: {
                    'X-WP-Nonce': this.nonce
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to fetch investment analysis');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error fetching investment analysis:', error);
            throw error;
        }
    }
}

// Usage example
document.addEventListener('DOMContentLoaded', () => {
    const vortexClient = new VortexAiClient();
    
    const analyzeArtwork = async (artworkId) => {
        try {
            const analytics = await vortexClient.getArtworkAnalytics(artworkId);
            
            // Update UI with analytics data
            document.querySelector('.market-fit-score').textContent = 
                `${(analytics.market_fit.overall_score * 100).toFixed(0)}%`;
                
            // More UI updates...
        } catch (error) {
            console.error('Analysis failed:', error);
        }
    };
    
    // Attach to UI elements
    const artworkElements = document.querySelectorAll('.artwork-item');
    artworkElements.forEach(element => {
        const artworkId = element.dataset.artworkId;
        element.querySelector('.analyze-button').addEventListener('click', () => {
            analyzeArtwork(artworkId);
        });
    });
});
```

### PHP API Client (Backend)

```php
/**
 * VORTEX AI AGENTS API Client for PHP
 */
class Vortex_AI_API_Client {
    private $api_base;
    
    public function __construct() {
        $this->api_base = rest_url('vortex-ai/v1');
    }
    
    /**
     * Get artwork analytics
     */
    public function get_artwork_analytics($artwork_id) {
        $response = wp_remote_get(
            $this->api_base . '/artwork-analytics/' . $artwork_id,
            [
                'headers' => [
                    'X-WP-Nonce' => wp_create_nonce('wp_rest')
                ]
            ]
        );
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data;
    }
    
    /**
     * Generate artwork with HURAII
     */
    public function generate_artwork($parameters) {
        $response = wp_remote_post(
            $this->api_base . '/huraii/generate',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-WP-Nonce' => wp_create_nonce('wp_rest')
                ],
                'body' => wp_json_encode($parameters)
            ]
        );
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data;
    }
    
    /**
     * Get career strategy from Business Strategist
     */
    public function get_career_strategy($artist_id, $parameters = []) {
        $query_args = http_build_query($parameters);
        $url = $this->api_base . '/career-strategy/' . $artist_id;
        
        if (!empty($query_args)) {
            $url .= '?' . $query_args;
        }
        
        $response = wp_remote_get(
            $url,
            [
                'headers' => [
                    'X-WP-Nonce' => wp_create_nonce('wp_rest')
                ]
            ]
        );
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data;
    }
}

// Usage example
function my_theme_display_artwork_recommendation($artwork_id) {
    $api_client = new Vortex_AI_API_Client();
    $analytics = $api_client->get_artwork_analytics($artwork_id);
    
    if (is_wp_error($analytics)) {
        echo '<p class="error">' . esc_html__('Unable to retrieve analytics', 'my-theme') . '</p>';
        return;
    }
    
    ?>
    <div class="artwork-analytics">
        <h3><?php esc_html_e('AI Market Analysis', 'my-theme'); ?></h3>
        
        <div class="analytics-summary">
            <div class="market-fit">
                <h4><?php esc_html_e('Market Fit', 'my-theme'); ?></h4>
                <div class="score-display">
                    <?php echo esc_html(($analytics['market_fit']['overall_score'] * 100) . '%'); ?>
                </div>
                <p class="description">
                    <?php echo esc_html($analytics['market_fit']['description']); ?>
                </p>
            </div>
            
            <!-- More analytics display... -->
        </div>
    </div>
    <?php
}
```

These implementation examples demonstrate how to integrate VORTEX AI AGENTS into various aspects of a WordPress website, from simple shortcodes to advanced theme customization and API usage. 