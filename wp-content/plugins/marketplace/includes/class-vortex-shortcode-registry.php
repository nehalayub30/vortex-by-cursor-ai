<?php
/**
 * VORTEX Shortcode Registry
 *
 * Centralized registration and documentation for all shortcodes
 *
 * @package VORTEX_Marketplace
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_Shortcode_Registry {
    /**
     * Instance of this class
     *
     * @var VORTEX_Shortcode_Registry
     */
    private static $instance = null;
    
    /**
     * Array of registered shortcodes with documentation
     *
     * @var array
     */
    private $shortcodes = array();
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize hooks
        add_action('init', array($this, 'register_shortcodes'), 20);
        
        // Add admin page for shortcode documentation
        add_action('admin_menu', array($this, 'add_documentation_page'));
        
        // Add AJAX handler for documentation export
        add_action('wp_ajax_vortex_export_shortcode_docs', array($this, 'ajax_export_documentation'));
    }
    
    /**
     * Get instance
     *
     * @return VORTEX_Shortcode_Registry
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Register a shortcode with documentation
     *
     * @param string $code Shortcode name
     * @param callable $callback Shortcode callback function
     * @param array $documentation Documentation details
     */
    public function register($code, $callback, $documentation = array()) {
        // Default documentation structure
        $default_docs = array(
            'name' => $code,
            'description' => '',
            'usage' => '[' . $code . ']',
            'parameters' => array(),
            'examples' => array(),
            'category' => 'general',
            'since' => '1.0.0',
            'required_capabilities' => array(),
        );
        
        // Merge with provided documentation
        $documentation = wp_parse_args($documentation, $default_docs);
        
        // Register the shortcode
        add_shortcode($code, function($atts, $content = null) use ($code, $callback) {
            // Track shortcode usage for analytics
            $this->track_shortcode_usage($code);
            
            // Call the original callback
            return call_user_func($callback, $atts, $content);
        });
        
        // Store in our registry with documentation
        $this->shortcodes[$code] = array(
            'callback' => $callback,
            'documentation' => $documentation
        );
    }
    
    /**
     * Register all shortcodes
     */
    public function register_shortcodes() {
        // This will be populated from other classes
        do_action('vortex_register_shortcodes', $this);
    }
    
    /**
     * Get a list of all registered shortcodes with documentation
     *
     * @param string $category Filter by category
     * @return array Shortcodes with documentation
     */
    public function get_shortcodes($category = '') {
        if (empty($category)) {
            return $this->shortcodes;
        }
        
        // Filter by category
        $filtered = array();
        foreach ($this->shortcodes as $code => $data) {
            if ($data['documentation']['category'] === $category) {
                $filtered[$code] = $data;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Get categories for organizing shortcodes
     *
     * @return array Categories
     */
    public function get_categories() {
        $categories = array();
        
        foreach ($this->shortcodes as $code => $data) {
            $category = $data['documentation']['category'];
            if (!isset($categories[$category])) {
                $categories[$category] = 0;
            }
            $categories[$category]++;
        }
        
        return $categories;
    }
    
    /**
     * Get documentation for a specific shortcode
     *
     * @param string $code Shortcode code
     * @return array|false Documentation or false if not found
     */
    public function get_documentation($code) {
        if (isset($this->shortcodes[$code])) {
            return $this->shortcodes[$code]['documentation'];
        }
        
        return false;
    }
    
    /**
     * Generate HTML documentation for all shortcodes
     *
     * @param string $category Filter by category
     * @return string HTML documentation
     */
    public function generate_html_documentation($category = '') {
        $shortcodes = $this->get_shortcodes($category);
        
        if (empty($shortcodes)) {
            return '<p>No shortcodes found.</p>';
        }
        
        $html = '';
        
        foreach ($shortcodes as $code => $data) {
            $doc = $data['documentation'];
            
            $html .= '<div class="vortex-shortcode-doc" id="shortcode-' . esc_attr($code) . '">';
            $html .= '<h3 class="shortcode-name">' . esc_html($doc['name']) . '</h3>';
            $html .= '<div class="shortcode-tag"><code>[' . esc_html($code) . ']</code></div>';
            
            if (!empty($doc['description'])) {
                $html .= '<div class="shortcode-description">' . wp_kses_post($doc['description']) . '</div>';
            }
            
            $html .= '<div class="shortcode-usage">';
            $html .= '<h4>Usage</h4>';
            $html .= '<pre><code>' . esc_html($doc['usage']) . '</code></pre>';
            $html .= '</div>';
            
            if (!empty($doc['parameters'])) {
                $html .= '<div class="shortcode-parameters">';
                $html .= '<h4>Parameters</h4>';
                $html .= '<table class="widefat striped">';
                $html .= '<thead><tr><th>Parameter</th><th>Default</th><th>Description</th></tr></thead>';
                $html .= '<tbody>';
                
                foreach ($doc['parameters'] as $param => $param_data) {
                    $default = isset($param_data['default']) ? $param_data['default'] : '';
                    $description = isset($param_data['description']) ? $param_data['description'] : '';
                    
                    $html .= '<tr>';
                    $html .= '<td><code>' . esc_html($param) . '</code></td>';
                    $html .= '<td><code>' . esc_html($default) . '</code></td>';
                    $html .= '<td>' . wp_kses_post($description) . '</td>';
                    $html .= '</tr>';
                }
                
                $html .= '</tbody></table>';
                $html .= '</div>';
            }
            
            if (!empty($doc['examples'])) {
                $html .= '<div class="shortcode-examples">';
                $html .= '<h4>Examples</h4>';
                
                foreach ($doc['examples'] as $example) {
                    $html .= '<div class="example">';
                    $html .= '<h5>' . esc_html($example['title']) . '</h5>';
                    
                    if (!empty($example['description'])) {
                        $html .= '<p>' . wp_kses_post($example['description']) . '</p>';
                    }
                    
                    $html .= '<pre><code>' . esc_html($example['code']) . '</code></pre>';
                    $html .= '</div>';
                }
                
                $html .= '</div>';
            }
            
            $html .= '<div class="shortcode-meta">';
            $html .= '<span class="version">Since: ' . esc_html($doc['since']) . '</span>';
            
            if (!empty($doc['required_capabilities'])) {
                $html .= ' | <span class="capabilities">Required capabilities: ';
                $html .= esc_html(implode(', ', $doc['required_capabilities']));
                $html .= '</span>';
            }
            
            $html .= '</div>';
            
            $html .= '</div>'; // End shortcode-doc
        }
        
        return $html;
    }
    
    /**
     * Add admin page for shortcode documentation
     */
    public function add_documentation_page() {
        add_submenu_page(
            'vortex-marketplace',
            'Shortcode Documentation',
            'Shortcodes',
            'manage_options',
            'vortex-shortcodes',
            array($this, 'render_documentation_page')
        );
    }
    
    /**
     * Render shortcode documentation page
     */
    public function render_documentation_page() {
        // Verify admin access
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'vortex-marketplace'));
        }
        
        // Get categories
        $categories = $this->get_categories();
        
        // Get selected category
        $category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
        
        // Start output buffer
        ob_start();
        ?>
        <div class="wrap">
            <h1>VORTEX Shortcode Documentation</h1>
            
            <div class="notice notice-info">
                <p>Use these shortcodes to add VORTEX marketplace functionality to your pages and posts.</p>
            </div>
            
            <div class="vortex-shortcode-tools">
                <a href="#" class="button export-documentation">Export Documentation</a>
            </div>
            
            <div class="vortex-shortcode-filters">
                <form method="get">
                    <input type="hidden" name="page" value="vortex-shortcodes">
                    <select name="category" id="category-filter">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat => $count): ?>
                            <option value="<?php echo esc_attr($cat); ?>" <?php selected($category, $cat); ?>>
                                <?php echo esc_html(ucfirst($cat)); ?> (<?php echo intval($count); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="button">Filter</button>
                </form>
            </div>
            
            <div class="vortex-shortcodes-list">
                <div class="vortex-shortcode-documentation">
                    <?php echo $this->generate_html_documentation($category); ?>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Handle export button
            $('.export-documentation').on('click', function(e) {
                e.preventDefault();
                
                var category = $('#category-filter').val();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_export_shortcode_docs',
                        nonce: '<?php echo wp_create_nonce('vortex_export_docs'); ?>',
                        category: category
                    },
                    success: function(response) {
                        if (response.success) {
                            // Create download link
                            var blob = new Blob([response.data.content], {type: 'text/html'});
                            var url = window.URL.createObjectURL(blob);
                            var a = document.createElement('a');
                            a.href = url;
                            a.download = response.data.filename;
                            document.body.appendChild(a);
                            a.click();
                            window.URL.revokeObjectURL(url);
                            a.remove();
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred while generating the export.');
                    }
                });
            });
        });
        </script>
        <?php
        
        echo ob_get_clean();
    }
    
    /**
     * AJAX handler for exporting documentation
     */
    public function ajax_export_documentation() {
        // Verify nonce
        check_ajax_referer('vortex_export_docs', 'nonce');
        
        // Verify capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'vortex-marketplace')));
            return;
        }
        
        // Get category filter
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        
        // Generate documentation
        $content = $this->generate_export_html($category);
        
        // Set filename
        $filename = 'vortex-shortcodes';
        if (!empty($category)) {
            $filename .= '-' . $category;
        }
        $filename .= '.html';
        
        wp_send_json_success(array(
            'content' => $content,
            'filename' => $filename
        ));
    }
    
    /**
     * Generate exportable HTML documentation
     *
     * @param string $category Category filter
     * @return string HTML content
     */
    private function generate_export_html($category = '') {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>VORTEX Shortcode Documentation</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                    line-height: 1.5;
                    color: #333;
                    max-width: 900px;
                    margin: 0 auto;
                    padding: 20px;
                }
                h1 {
                    border-bottom: 1px solid #eee;
                    padding-bottom: 10px;
                }
                .vortex-shortcode-doc {
                    margin-bottom: 40px;
                    border: 1px solid #ddd;
                    padding: 20px;
                    border-radius: 4px;
                }
                .shortcode-name {
                    margin-top: 0;
                    color: #0073aa;
                }
                .shortcode-tag {
                    margin-bottom: 15px;
                }
                .shortcode-tag code {
                    background: #f5f5f5;
                    padding: 5px 10px;
                    border-radius: 4px;
                    font-size: 14px;
                }
                pre {
                    background: #f5f5f5;
                    padding: 15px;
                    overflow-x: auto;
                    border-radius: 3px;
                }
                code {
                    font-family: monospace;
                }
                table {
                    border-collapse: collapse;
                    width: 100%;
                    margin: 15px 0;
                }
                th, td {
                    text-align: left;
                    padding: 8px;
                    border: 1px solid #ddd;
                }
                th {
                    background-color: #f5f5f5;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .shortcode-meta {
                    margin-top: 20px;
                    color: #666;
                    font-size: 12px;
                }
                .example {
                    margin-bottom: 20px;
                }
            </style>
        </head>
        <body>
            <h1>VORTEX Shortcode Documentation</h1>
            <p><em>Generated: <?php echo current_time('F j, Y, g:i a'); ?></em></p>
            
            <?php if (!empty($category)): ?>
                <p>Category: <strong><?php echo esc_html(ucfirst($category)); ?></strong></p>
            <?php endif; ?>
            
            <div class="vortex-shortcodes-list">
                <?php echo $this->generate_html_documentation($category); ?>
            </div>
        </body>
        </html>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Track shortcode usage for analytics
     *
     * @param string $code Shortcode code
     */
    private function track_shortcode_usage($code) {
        $option_name = 'vortex_shortcode_usage_stats';
        $stats = get_option($option_name, array());
        
        if (!isset($stats[$code])) {
            $stats[$code] = 0;
        }
        
        $stats[$code]++;
        
        update_option($option_name, $stats, false);
    }
    
    /**
     * Get shortcode usage statistics
     *
     * @return array Usage statistics
     */
    public function get_usage_statistics() {
        return get_option('vortex_shortcode_usage_stats', array());
    }
}

// Initialize shortcode registry
VORTEX_Shortcode_Registry::get_instance(); 