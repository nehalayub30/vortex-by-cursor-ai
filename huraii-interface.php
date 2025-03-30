<?php
/**
 * HURAII AI Art Generation Interface
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage AI_Interfaces
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display the HURAII AI art generation interface
 * 
 * @param array $args Optional parameters
 * @return void
 */
function vortex_huraii_interface($args = array()) {
    // Parse arguments
    $defaults = array(
        'canvas_width' => 768,
        'canvas_height' => 768,
        'show_seed_art_tools' => true,
        'show_advanced_options' => current_user_can('edit_posts'),
        'initial_prompt' => '',
        'enable_ai_learning' => true,
        'container_id' => 'vortex-huraii-interface',
        'template_id' => 0,
        'agent_context' => 'art_generation'
    );
    
    $args = wp_parse_args($args, $defaults);
    
    // Initialize AI agents to track this interface session
    do_action('vortex_ai_agent_init', $args['agent_context'], array('HURAII', 'CLOE', 'BusinessStrategist'), 'active', array(
        'user_id' => get_current_user_id(),
        'interface' => 'huraii',
        'seed_art_enabled' => $args['show_seed_art_tools'],
        'session_id' => uniqid('huraii_')
    ));
    
    // Get current user's generation history
    $user_id = get_current_user_id();
    $generation_history = get_user_meta($user_id, 'vortex_huraii_history', true);
    if (!is_array($generation_history)) {
        $generation_history = array();
    }
    
    // Load available models from HURAII system
    $huraii = VORTEX_HURAII::get_instance();
    $available_models = $huraii->get_available_models();
    
    // Get recommended prompts from CLOE
    $recommended_prompts = array();
    if (class_exists('VORTEX_CLOE')) {
        $cloe = VORTEX_CLOE::get_instance();
        $recommended_prompts = $cloe->get_personalized_prompts($user_id, 'art_generation', 5);
    }
    
    // Get pricing and market data from BusinessStrategist
    $market_insights = array();
    if (class_exists('VORTEX_BusinessStrategist')) {
        $business_strategist = VORTEX_BusinessStrategist::get_instance();
        $market_insights = $business_strategist->get_art_market_insights('generation');
    }
    
    // Enqueue required assets
    wp_enqueue_style('vortex-huraii-interface-css', VORTEX_PLUGIN_URL . 'assets/css/huraii-interface.css', array(), VORTEX_VERSION);
    wp_enqueue_script('vortex-huraii-interface-js', VORTEX_PLUGIN_URL . 'assets/js/huraii-interface.js', array('jquery'), VORTEX_VERSION, true);
    
    // Pass data to JavaScript
    wp_localize_script('vortex-huraii-interface-js', 'vortexHURAII', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('vortex_huraii_nonce'),
        'userId' => $user_id,
        'canvasWidth' => $args['canvas_width'],
        'canvasHeight' => $args['canvas_height'],
        'seedArtEnabled' => $args['show_seed_art_tools'],
        'models' => $available_models,
        'defaultModel' => $huraii->get_default_model(),
        'interfaceId' => $args['container_id'],
        'learningEnabled' => $args['enable_ai_learning'],
        'templateId' => $args['template_id'],
        'i18n' => array(
            'generating' => __('Generating artwork...', 'vortex'),
            'analyzePrompt' => __('Analyze Prompt', 'vortex'),
            'seedArtAnalysis' => __('Seed Art Analysis', 'vortex'),
            'layers' => __('Layers', 'vortex'),
            'movementDetection' => __('Movement Detection', 'vortex'),
            'efficiencyScore' => __('Efficiency Score', 'vortex')
        )
    ));
    
    // Get random welcome message from HURAII
    $welcome_message = $huraii->get_random_welcome_message($user_id);
    
    // Start output buffering for the interface
    ob_start();
    ?>
    <div id="<?php echo esc_attr($args['container_id']); ?>" class="vortex-huraii-interface">
        <div class="vortex-huraii-header">
            <h2><?php esc_html_e('HURAII AI Art Generation', 'vortex'); ?></h2>
            <div class="vortex-huraii-welcome-message">
                <?php echo esc_html($welcome_message); ?>
            </div>
            
            <?php if (!empty($market_insights)): ?>
            <div class="vortex-market-insights">
                <h4><?php esc_html_e('Market Insights', 'vortex'); ?></h4>
                <div class="vortex-insight-badge">
                    <?php echo esc_html(sprintf(
                        __('Trending: %s', 'vortex'), 
                        $market_insights['trending_style']
                    )); ?>
                </div>
                <div class="vortex-insight-badge">
                    <?php echo esc_html(sprintf(
                        __('Avg. Value: %s', 'vortex'), 
                        '$' . number_format($market_insights['average_value'], 2)
                    )); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="vortex-huraii-container">
            <div class="vortex-huraii-sidebar">
                <div class="vortex-huraii-form">
                    <div class="vortex-form-group">
                        <label for="vortex-prompt"><?php esc_html_e('Prompt', 'vortex'); ?></label>
                        <textarea id="vortex-prompt" name="prompt" rows="4" placeholder="<?php esc_attr_e('Describe the artwork you want to create...', 'vortex'); ?>"><?php echo esc_textarea($args['initial_prompt']); ?></textarea>
                        <button type="button" class="vortex-analyze-prompt"><?php esc_html_e('Analyze Prompt', 'vortex'); ?></button>
                    </div>
                    
                    <div class="vortex-form-group">
                        <label for="vortex-negative-prompt"><?php esc_html_e('Negative Prompt', 'vortex'); ?></label>
                        <textarea id="vortex-negative-prompt" name="negative_prompt" rows="2" placeholder="<?php esc_attr_e('Elements to avoid in the artwork...', 'vortex'); ?>"></textarea>
                    </div>
                    
                    <div class="vortex-form-group">
                        <label for="vortex-ai-model"><?php esc_html_e('AI Model', 'vortex'); ?></label>
                        <select id="vortex-ai-model" name="model">
                            <?php foreach ($available_models as $model_id => $model): ?>
                                <option value="<?php echo esc_attr($model_id); ?>" <?php selected($model_id, $huraii->get_default_model()); ?>>
                                    <?php echo esc_html($model['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if ($args['show_seed_art_tools']): ?>
                    <div class="vortex-form-group vortex-seed-art-controls">
                        <div class="vortex-checkbox-control">
                            <input type="checkbox" id="vortex-enable-seed-art" name="enable_seed_art" value="1" checked>
                            <label for="vortex-enable-seed-art"><?php esc_html_e('Enable Seed Art technique', 'vortex'); ?></label>
                        </div>
                        
                        <div class="vortex-seed-art-components">
                            <h4><?php esc_html_e('Seed Art Components', 'vortex'); ?></h4>
                            <div class="vortex-component-sliders">
                                <div class="vortex-component-slider">
                                    <label><?php esc_html_e('Sacred Geometry', 'vortex'); ?></label>
                                    <input type="range" name="sacred_geometry" min="0" max="100" value="75">
                                </div>
                                <div class="vortex-component-slider">
                                    <label><?php esc_html_e('Texture Layering', 'vortex'); ?></label>
                                    <input type="range" name="texture_layering" min="0" max="100" value="60">
                                </div>
                                <div class="vortex-component-slider">
                                    <label><?php esc_html_e('Color Harmony', 'vortex'); ?></label>
                                    <input type="range" name="color_harmony" min="0" max="100" value="80">
                                </div>
                                <div class="vortex-component-slider">
                                    <label><?php esc_html_e('Symbolic Elements', 'vortex'); ?></label>
                                    <input type="range" name="symbolic_elements" min="0" max="100" value="50">
                                </div>
                                <div class="vortex-component-slider">
                                    <label><?php esc_html_e('Composition Flow', 'vortex'); ?></label>
                                    <input type="range" name="composition_flow" min="0" max="100" value="70">
                                </div>
                                <div class="vortex-component-slider">
                                    <label><?php esc_html_e('Intentional Balance', 'vortex'); ?></label>
                                    <input type="range" name="intentional_balance" min="0" max="100" value="65">
                                </div>
                                <div class="vortex-component-slider">
                                    <label><?php esc_html_e('Movement & Layering', 'vortex'); ?></label>
                                    <input type="range" name="movement_layering" min="0" max="100" value="55">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($args['show_advanced_options']): ?>
                    <div class="vortex-advanced-options">
                        <h4><?php esc_html_e('Advanced Options', 'vortex'); ?></h4>
                        <div class="vortex-form-row">
                            <div class="vortex-form-group">
                                <label for="vortex-width"><?php esc_html_e('Width', 'vortex'); ?></label>
                                <input type="number" id="vortex-width" name="width" value="<?php echo esc_attr($args['canvas_width']); ?>" min="256" max="1024" step="64">
                            </div>
                            <div class="vortex-form-group">
                                <label for="vortex-height"><?php esc_html_e('Height', 'vortex'); ?></label>
                                <input type="number" id="vortex-height" name="height" value="<?php echo esc_attr($args['canvas_height']); ?>" min="256" max="1024" step="64">
                            </div>
                        </div>
                        
                        <div class="vortex-form-row">
                            <div class="vortex-form-group">
                                <label for="vortex-cfg-scale"><?php esc_html_e('CFG Scale', 'vortex'); ?></label>
                                <input type="number" id="vortex-cfg-scale" name="cfg_scale" value="7.5" min="1" max="20" step="0.5">
                            </div>
                            <div class="vortex-form-group">
                                <label for="vortex-steps"><?php esc_html_e('Steps', 'vortex'); ?></label>
                                <input type="number" id="vortex-steps" name="steps" value="30" min="10" max="150" step="1">
                            </div>
                        </div>
                        
                        <div class="vortex-form-group">
                            <label for="vortex-seed"><?php esc_html_e('Seed', 'vortex'); ?></label>
                            <div class="vortex-seed-control">
                                <input type="number" id="vortex-seed" name="seed" value="-1" min="-1" max="2147483647">
                                <button type="button" class="vortex-random-seed"><?php esc_html_e('Random', 'vortex'); ?></button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="vortex-form-actions">
                        <button type="button" id="vortex-generate-btn" class="vortex-primary-button"><?php esc_html_e('Generate Artwork', 'vortex'); ?></button>
                        <div class="vortex-generation-progress">
                            <div class="vortex-progress-bar"></div>
                            <div class="vortex-progress-text"></div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($recommended_prompts)): ?>
                <div class="vortex-recommended-prompts">
                    <h4><?php esc_html_e('CLOE Recommends', 'vortex'); ?></h4>
                    <ul class="vortex-prompt-suggestions">
                        <?php foreach ($recommended_prompts as $prompt): ?>
                        <li class="vortex-prompt-suggestion">
                            <button type="button" class="vortex-use-prompt" data-prompt="<?php echo esc_attr($prompt['prompt']); ?>">
                                <?php echo esc_html($prompt['title']); ?>
                            </button>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="vortex-huraii-canvas-area">
                <div class="vortex-canvas-container">
                    <div id="vortex-canvas-placeholder" class="vortex-canvas-placeholder">
                        <div class="vortex-placeholder-content">
                            <div class="vortex-placeholder-icon">
                                <svg width="64" height="64" viewBox="0 0 24 24">
                                    <path fill="currentColor" d="M18 13v5h-5v-5h5m0-1h-5c-.55 0-1 .45-1 1v5c0 .55.45 1 1 1h5c.55 0 1-.45 1-1v-5c0-.55-.45-1-1-1zm-1-9H7c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V7l-4-4zm2 16H7V5h8v3h4v11z"/>
                                </svg>
                            </div>
                            <div class="vortex-placeholder-text">
                                <?php esc_html_e('Enter a prompt and click "Generate Artwork" to create your masterpiece', 'vortex'); ?>
                            </div>
                        </div>
                    </div>
                    <div id="vortex-generation-result" class="vortex-generation-result" style="display: none;">
                        <img id="vortex-generated-image" src="" alt="Generated artwork">
                    </div>
                    
                    <div id="vortex-seed-art-analysis" class="vortex-seed-art-analysis" style="display: none;">
                        <h3><?php esc_html_e('Seed Art Analysis', 'vortex'); ?></h3>
                        <div class="vortex-analysis-content">
                            <div class="vortex-component-analysis">
                                <!-- Will be populated by JavaScript -->
                            </div>
                            <div class="vortex-efficiency-analysis">
                                <h4><?php esc_html_e('Efficiency Analysis', 'vortex'); ?></h4>
                                <div class="vortex-efficiency-score">
                                    <!-- Will be populated by JavaScript -->
                                </div>
                                <div class="vortex-efficiency-recommendations">
                                    <!-- Will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="vortex-canvas-actions" style="display: none;">
                    <button type="button" class="vortex-action-btn vortex-save-artwork">
                        <?php esc_html_e('Save to Gallery', 'vortex'); ?>
                    </button>
                    <button type="button" class="vortex-action-btn vortex-modify-artwork">
                        <?php esc_html_e('Edit with Img2Img', 'vortex'); ?>
                    </button>
                    <button type="button" class="vortex-action-btn vortex-analyze-artwork">
                        <?php esc_html_e('Analyze Artwork', 'vortex'); ?>
                    </button>
                    <button type="button" class="vortex-action-btn vortex-create-nft">
                        <?php esc_html_e('Create NFT', 'vortex'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="vortex-huraii-history">
            <h3><?php esc_html_e('Recent Generations', 'vortex'); ?></h3>
            <div class="vortex-history-grid">
                <?php if (empty($generation_history)): ?>
                <div class="vortex-empty-history">
                    <?php esc_html_e('Your generation history will appear here', 'vortex'); ?>
                </div>
                <?php else: ?>
                <?php foreach (array_slice($generation_history, 0, 8) as $item): ?>
                <div class="vortex-history-item" data-id="<?php echo esc_attr($item['id']); ?>">
                    <div class="vortex-history-image">
                        <img src="<?php echo esc_url($item['thumbnail_url']); ?>" alt="<?php echo esc_attr($item['prompt']); ?>">
                    </div>
                    <div class="vortex-history-details">
                        <div class="vortex-history-prompt"><?php echo esc_html($item['prompt']); ?></div>
                        <div class="vortex-history-meta">
                            <?php echo esc_html(human_time_diff(strtotime($item['date']), current_time('timestamp'))); ?> <?php esc_html_e('ago', 'vortex'); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Templates for JavaScript use -->
        <script type="text/template" id="vortex-component-analysis-template">
            <div class="vortex-component-score">
                <div class="vortex-component-name">{component_name}</div>
                <div class="vortex-score-bar">
                    <div class="vortex-score-fill" style="width: {score_percentage}%"></div>
                </div>
                <div class="vortex-score-value">{score_value}/100</div>
            </div>
        </script>
        
        <script type="text/template" id="vortex-efficiency-score-template">
            <div class="vortex-score-circle {efficiency_class}" data-score="{efficiency_score}">
                <svg viewBox="0 0 36 36">
                    <path class="vortex-score-circle-bg"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <path class="vortex-score-circle-fill"
                        stroke-dasharray="{score_percentage}, 100"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <text x="18" y="20.35" class="vortex-score-text">{efficiency_score}%</text>
                </svg>
            </div>
            <div class="vortex-score-label">{efficiency_label}</div>
        </script>
    </div>
    <?php
    
    // If AI learning is enabled, output the tracking script
    if ($args['enable_ai_learning']): ?>
    <script type="text/javascript">
        (function($) {
            $(document).ready(function() {
                // Track interface view
                $.post(vortexHURAII.ajaxUrl, {
                    action: 'vortex_track_ai_interaction',
                    nonce: vortexHURAII.nonce,
                    interaction: 'interface_view',
                    interface: 'huraii',
                    context: <?php echo json_encode($args['agent_context']); ?>,
                    template_id: <?php echo intval($args['template_id']); ?>
                });
                
                // Track prompt input
                let promptTypingTimer;
                $('#vortex-prompt').on('keyup', function() {
                    clearTimeout(promptTypingTimer);
                    promptTypingTimer = setTimeout(function() {
                        $.post(vortexHURAII.ajaxUrl, {
                            action: 'vortex_track_ai_interaction',
                            nonce: vortexHURAII.nonce,
                            interaction: 'prompt_input',
                            prompt_length: $('#vortex-prompt').val().length,
                            interface: 'huraii'
                        });
                    }, 1000);
                });
                
                // Track component adjustments for deep learning
                $('.vortex-component-slider input').on('change', function() {
                    $.post(vortexHURAII.ajaxUrl, {
                        action: 'vortex_track_ai_interaction',
                        nonce: vortexHURAII.nonce,
                        interaction: 'component_adjustment',
                        component: $(this).attr('name'),
                        value: $(this).val(),
                        interface: 'huraii'
                    });
                });
            });
        })(jQuery);
    </script>
    <?php endif;
    
    return ob_get_clean();
}

/**
 * Shortcode for HURAII interface
 * 
 * @param array $atts Shortcode attributes
 * @return string Interface HTML
 */
function vortex_huraii_interface_shortcode($atts) {
    $atts = shortcode_atts(array(
        'width' => 768,
        'height' => 768,
        'seed_art' => 'true',
        'advanced' => 'false',
        'prompt' => '',
        'template' => 0
    ), $atts);
    
    return vortex_huraii_interface(array(
        'canvas_width' => intval($atts['width']),
        'canvas_height' => intval($atts['height']),
        'show_seed_art_tools' => $atts['seed_art'] === 'true',
        'show_advanced_options' => $atts['advanced'] === 'true' || current_user_can('edit_posts'),
        'initial_prompt' => sanitize_textarea_field($atts['prompt']),
        'template_id' => intval($atts['template'])
    ));
}
add_shortcode('vortex_huraii', 'vortex_huraii_interface_shortcode');

/**
 * Add format selection UI to HURAII interface
 */
function vortex_huraii_format_selector() {
    ?>
    <div class="vortex-huraii-format-selector">
        <h3><?php _e('Select Generation Format', 'vortex-marketplace'); ?></h3>
        
        <div class="format-options">
            <div class="format-option active" data-format="2d">
                <i class="dashicons dashicons-format-image"></i>
                <span><?php _e('2D Image', 'vortex-marketplace'); ?></span>
            </div>
            
            <div class="format-option" data-format="3d">
                <i class="dashicons dashicons-layout"></i>
                <span><?php _e('3D Model', 'vortex-marketplace'); ?></span>
            </div>
            
            <div class="format-option" data-format="video">
                <i class="dashicons dashicons-format-video"></i>
                <span><?php _e('Video', 'vortex-marketplace'); ?></span>
            </div>
            
            <div class="format-option" data-format="audio">
                <i class="dashicons dashicons-format-audio"></i>
                <span><?php _e('Audio', 'vortex-marketplace'); ?></span>
            </div>
            
            <div class="format-option" data-format="interactive">
                <i class="dashicons dashicons-admin-customizer"></i>
                <span><?php _e('Interactive', 'vortex-marketplace'); ?></span>
            </div>
            
            <div class="format-option" data-format="4d">
                <i class="dashicons dashicons-superhero"></i>
                <span><?php _e('4D Content', 'vortex-marketplace'); ?></span>
            </div>
        </div>
        
        <!-- Format-specific settings will be shown here -->
        <div class="format-settings-container">
            <!-- 2D Settings (default) -->
            <div class="format-settings" id="settings-2d">
                <div class="setting-row">
                    <label for="2d-format"><?php _e('Image Format', 'vortex-marketplace'); ?></label>
                    <select id="2d-format" name="settings[format]">
                        <option value="png">PNG</option>
                        <option value="jpg">JPG</option>
                        <option value="webp">WebP</option>
                    </select>
                </div>
                <div class="setting-row">
                    <label for="2d-width"><?php _e('Width', 'vortex-marketplace'); ?></label>
                    <input type="number" id="2d-width" name="settings[width]" value="512" min="64" max="2048" step="64">
                </div>
                <div class="setting-row">
                    <label for="2d-height"><?php _e('Height', 'vortex-marketplace'); ?></label>
                    <input type="number" id="2d-height" name="settings[height]" value="512" min="64" max="2048" step="64">
                </div>
            </div>
        </div>
    </div>
    <?php
} 