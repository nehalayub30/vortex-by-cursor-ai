<?php
/**
 * Image-to-Image Transformation Interface
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage AI_Interfaces
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display the image-to-image transformation interface
 * 
 * @param array $args Optional parameters
 * @return string HTML for the interface
 */
function vortex_img2img_interface($args = array()) {
    // Parse arguments
    $defaults = array(
        'canvas_width' => 768,
        'canvas_height' => 768,
        'show_advanced_options' => current_user_can('edit_posts'),
        'enable_ai_learning' => true,
        'initial_image_url' => '',
        'initial_prompt' => '',
        'container_id' => 'vortex-img2img-interface',
        'modes' => array('style_transfer', 'inpainting', 'upscaling', 'enhancement'),
        'agent_context' => 'image_transformation'
    );
    
    $args = wp_parse_args($args, $defaults);
    
    // Initialize AI agents to track this interface session
    do_action('vortex_ai_agent_init', $args['agent_context'], array('HURAII', 'CLOE', 'BusinessStrategist'), 'active', array(
        'user_id' => get_current_user_id(),
        'interface' => 'img2img',
        'modes_available' => $args['modes'],
        'session_id' => uniqid('img2img_')
    ));
    
    // Get current user's transformation history
    $user_id = get_current_user_id();
    $transformation_history = get_user_meta($user_id, 'vortex_img2img_history', true);
    if (!is_array($transformation_history)) {
        $transformation_history = array();
    }
    
    // Load available models from Img2Img system
    $img2img = class_exists('VORTEX_Img2Img') ? VORTEX_Img2Img::get_instance() : null;
    $available_models = $img2img ? $img2img->get_available_models() : array();
    
    // Get style suggestions from CLOE
    $style_suggestions = array();
    if (class_exists('VORTEX_CLOE')) {
        $cloe = VORTEX_CLOE::get_instance();
        $style_suggestions = $cloe->get_style_suggestions($user_id, 5);
    }
    
    // Get market insights from BusinessStrategist
    $market_insights = array();
    if (class_exists('VORTEX_BusinessStrategist')) {
        $business_strategist = VORTEX_BusinessStrategist::get_instance();
        $market_insights = $business_strategist->get_art_market_insights('transformation');
    }
    
    // Enqueue required assets
    wp_enqueue_style('vortex-img2img-interface-css', VORTEX_PLUGIN_URL . 'assets/css/img2img-interface.css', array(), VORTEX_VERSION);
    wp_enqueue_script('vortex-img2img-interface-js', VORTEX_PLUGIN_URL . 'assets/js/img2img-interface.js', array('jquery'), VORTEX_VERSION, true);
    
    // Pass data to JavaScript
    wp_localize_script('vortex-img2img-interface-js', 'vortexImg2Img', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('vortex_huraii_nonce'),
        'userId' => $user_id,
        'canvasWidth' => $args['canvas_width'],
        'canvasHeight' => $args['canvas_height'],
        'models' => $available_models,
        'interfaceId' => $args['container_id'],
        'learningEnabled' => $args['enable_ai_learning'],
        'initialImageUrl' => $args['initial_image_url'],
        'modes' => $args['modes'],
        'defaultMode' => 'style_transfer',
        'styleSuggestions' => $style_suggestions,
        'marketInsights' => $market_insights,
        'i18n' => array(
            'uploading' => __('Uploading image...', 'vortex-ai-marketplace'),
            'transforming' => __('Transforming image...', 'vortex-ai-marketplace'),
            'uploadError' => __('Error uploading image. Please try again.', 'vortex-ai-marketplace'),
            'transformError' => __('Error transforming image. Please try again.', 'vortex-ai-marketplace'),
            'noImageSelected' => __('Please select an image first.', 'vortex-ai-marketplace'),
            'noPrompt' => __('Please enter a prompt to guide the transformation.', 'vortex-ai-marketplace'),
            'processingComplete' => __('Transformation complete!', 'vortex-ai-marketplace'),
            'saving' => __('Saving...', 'vortex-ai-marketplace'),
            'saved' => __('Saved!', 'vortex-ai-marketplace'),
            'saveToLibrary' => __('Save to Library', 'vortex-ai-marketplace'),
            'savedSuccess' => __('Image saved to your library!', 'vortex-ai-marketplace'),
            'saveFailed' => __('Failed to save image. Please try again.', 'vortex-ai-marketplace'),
            'noImageToSave' => __('No image to save. Please transform an image first.', 'vortex-ai-marketplace'),
            'saveFirst' => __('Please save the image to your library first.', 'vortex-ai-marketplace'),
            'createNftTitle' => __('Create NFT', 'vortex-ai-marketplace'),
            'nftName' => __('NFT Name', 'vortex-ai-marketplace'),
            'nftDescription' => __('Description', 'vortex-ai-marketplace'),
            'royaltyPercentage' => __('Royalty Percentage', 'vortex-ai-marketplace'),
            'nftPrice' => __('Price', 'vortex-ai-marketplace'),
            'cancel' => __('Cancel', 'vortex-ai-marketplace'),
            'createNft' => __('Create NFT', 'vortex-ai-marketplace'),
            'processing' => __('Processing...', 'vortex-ai-marketplace'),
            'nftFailed' => __('Failed to create NFT. Please try again.', 'vortex-ai-marketplace'),
            'serverError' => __('Server error. Please try again.', 'vortex-ai-marketplace'),
            'nftInitiated' => __('NFT Creation Initiated', 'vortex-ai-marketplace'),
            'blockchainRegistering' => __('is being registered on the blockchain. This process may take a few minutes.', 'vortex-ai-marketplace'),
            'statusPending' => __('Status: Pending', 'vortex-ai-marketplace'),
            'statusConfirmed' => __('Status: Confirmed', 'vortex-ai-marketplace'),
            'notificationComplete' => __('You will receive a notification when the process is complete.', 'vortex-ai-marketplace'),
            'viewNftDetails' => __('View NFT Details', 'vortex-ai-marketplace')
        )
    ));
    
    // Get transformation tips from HURAII
    $huraii_tips = array();
    if (class_exists('VORTEX_HURAII')) {
        $huraii = VORTEX_HURAII::get_instance();
        $huraii_tips = $huraii->get_transformation_tips();
    }
    
    // Start output buffering for the interface
    ob_start();
    ?>
    <div id="<?php echo esc_attr($args['container_id']); ?>" class="vortex-img2img-interface">
        <div class="vortex-img2img-header">
            <h2><?php esc_html_e('AI Image Transformation', 'vortex'); ?></h2>
            <div class="vortex-img2img-info">
                <p><?php esc_html_e('Transform your images using HURAII AI technology', 'vortex'); ?></p>
            </div>
            
            <?php if (!empty($market_insights)): ?>
            <div class="vortex-market-insights">
                <h4><?php esc_html_e('Style Insights', 'vortex'); ?></h4>
                <div class="vortex-insight-badge">
                    <?php echo esc_html(sprintf(
                        __('Trending: %s', 'vortex'), 
                        $market_insights['trending_transformation']
                    )); ?>
                </div>
                <div class="vortex-insight-badge">
                    <?php echo esc_html(sprintf(
                        __('Most Valued: %s', 'vortex'), 
                        $market_insights['valuable_style']
                    )); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="vortex-img2img-container">
            <div class="vortex-img2img-sidebar">
                <div class="vortex-mode-selector">
                    <h4><?php esc_html_e('Transformation Mode', 'vortex'); ?></h4>
                    <div class="vortex-mode-buttons">
                        <?php foreach ($args['modes'] as $mode): 
                            $mode_title = '';
                            $mode_icon = '';
                            switch ($mode) {
                                case 'style_transfer':
                                    $mode_title = __('Style Transfer', 'vortex');
                                    $mode_icon = 'style-icon';
                                    break;
                                case 'inpainting':
                                    $mode_title = __('Inpainting', 'vortex');
                                    $mode_icon = 'inpaint-icon';
                                    break;
                                case 'upscaling':
                                    $mode_title = __('Upscaling', 'vortex');
                                    $mode_icon = 'upscale-icon';
                                    break;
                                case 'enhancement':
                                    $mode_title = __('Enhancement', 'vortex');
                                    $mode_icon = 'enhance-icon';
                                    break;
                            }
                        ?>
                        <button type="button" class="vortex-mode-btn <?php echo $mode === 'style_transfer' ? 'active' : ''; ?>" data-mode="<?php echo esc_attr($mode); ?>">
                            <span class="vortex-mode-icon <?php echo esc_attr($mode_icon); ?>"></span>
                            <span class="vortex-mode-label"><?php echo esc_html($mode_title); ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="vortex-img2img-form">
                    <div class="vortex-form-group vortex-source-image-group">
                        <label><?php esc_html_e('Source Image', 'vortex'); ?></label>
                        <div class="vortex-image-upload-container">
                            <div id="vortex-image-preview" class="vortex-image-preview <?php echo !empty($args['initial_image_url']) ? 'has-image' : ''; ?>">
                                <?php if (!empty($args['initial_image_url'])): ?>
                                <img src="<?php echo esc_url($args['initial_image_url']); ?>" alt="Source image">
                                <?php else: ?>
                                <div class="vortex-upload-placeholder">
                                    <svg width="48" height="48" viewBox="0 0 24 24">
                                        <path fill="currentColor" d="M18 15v3H6v-3H4v3c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2v-3h-2zM7 9l1.41 1.41L11 7.83V16h2V7.83l2.59 2.58L17 9l-5-5-5 5z"/>
                                    </svg>
                                    <p><?php esc_html_e('Upload an image', 'vortex'); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <input type="file" id="vortex-source-image" name="source_image" accept="image/*" class="vortex-file-input">
                            <button type="button" class="vortex-upload-btn"><?php esc_html_e('Upload Image', 'vortex'); ?></button>
                        </div>
                    </div>
                    
                    <!-- Style Transfer Form -->
                    <div class="vortex-mode-form vortex-style-transfer-form">
                        <div class="vortex-form-group">
                            <label for="vortex-style-prompt"><?php esc_html_e('Style Description', 'vortex'); ?></label>
                            <textarea id="vortex-style-prompt" name="style_prompt" rows="3" placeholder="<?php esc_attr_e('Describe the style to apply...', 'vortex'); ?>"><?php echo esc_textarea($args['initial_prompt']); ?></textarea>
                        </div>
                        
                        <div class="vortex-form-group">
                            <label for="vortex-style-strength"><?php esc_html_e('Style Strength', 'vortex'); ?></label>
                            <div class="vortex-slider-container">
                                <input type="range" id="vortex-style-strength" name="style_strength" min="0.1" max="0.9" step="0.1" value="0.5">
                                <div class="vortex-slider-labels">
                                    <span><?php esc_html_e('Subtle', 'vortex'); ?></span>
                                    <span><?php esc_html_e('Strong', 'vortex'); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($style_suggestions)): ?>
                        <div class="vortex-style-suggestions">
                            <h4><?php esc_html_e('CLOE Style Suggestions', 'vortex'); ?></h4>
                            <div class="vortex-style-chips">
                                <?php foreach ($style_suggestions as $style): ?>
                                <button type="button" class="vortex-style-chip" data-style="<?php echo esc_attr($style['style']); ?>">
                                    <?php echo esc_html($style['style']); ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Inpainting Form -->
                    <div class="vortex-mode-form vortex-inpainting-form" style="display: none;">
                        <div class="vortex-form-group">
                            <label for="vortex-inpaint-prompt"><?php esc_html_e('Inpainting Prompt', 'vortex'); ?></label>
                            <textarea id="vortex-inpaint-prompt" name="inpaint_prompt" rows="3" placeholder="<?php esc_attr_e('Describe what to add in the masked area...', 'vortex'); ?>"></textarea>
                        </div>
                        
                        <div class="vortex-form-group">
                            <label><?php esc_html_e('Mask Tools', 'vortex'); ?></label>
                            <div class="vortex-inpaint-tools">
                                <button type="button" class="vortex-inpaint-tool" data-tool="brush"><span class="tool-icon brush-icon"></span> <?php esc_html_e('Brush', 'vortex'); ?></button>
                                <button type="button" class="vortex-inpaint-tool" data-tool="eraser"><span class="tool-icon eraser-icon"></span> <?php esc_html_e('Eraser', 'vortex'); ?></button>
                                <button type="button" class="vortex-inpaint-tool" data-tool="clear"><span class="tool-icon clear-icon"></span> <?php esc_html_e('Clear', 'vortex'); ?></button>
                            </div>
                        </div>
                        
                        <div class="vortex-form-group">
                            <label for="vortex-brush-size"><?php esc_html_e('Brush Size', 'vortex'); ?></label>
                            <div class="vortex-slider-container">
                                <input type="range" id="vortex-brush-size" name="brush_size" min="5" max="50" step="1" value="20">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Upscaling Form -->
                    <div class="vortex-mode-form vortex-upscaling-form" style="display: none;">
                        <div class="vortex-form-group">
                            <label for="vortex-scale-factor"><?php esc_html_e('Scale Factor', 'vortex'); ?></label>
                            <select id="vortex-scale-factor" name="scale_factor">
                                <option value="2">2x</option>
                                <option value="4">4x</option>
                            </select>
                        </div>
                        
                        <div class="vortex-form-group">
                            <label for="vortex-preserve-details"><?php esc_html_e('Preserve Details', 'vortex'); ?></label>
                            <div class="vortex-slider-container">
                                <input type="range" id="vortex-preserve-details" name="preserve_details" min="0" max="1" step="0.1" value="0.5">
                                <div class="vortex-slider-labels">
                                    <span><?php esc_html_e('Smooth', 'vortex'); ?></span>
                                    <span><?php esc_html_e('Detailed', 'vortex'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Enhancement Form -->
                    <div class="vortex-mode-form vortex-enhancement-form" style="display: none;">
                        <div class="vortex-enhancement-options">
                            <div class="vortex-form-group">
                                <div class="vortex-checkbox-control">
                                    <input type="checkbox" id="vortex-enhance-color" name="enhance_color" checked>
                                    <label for="vortex-enhance-color"><?php esc_html_e('Color Enhancement', 'vortex'); ?></label>
                                </div>
                            </div>
                            
                            <div class="vortex-form-group">
                                <div class="vortex-checkbox-control">
                                    <input type="checkbox" id="vortex-enhance-clarity" name="enhance_clarity" checked>
                                    <label for="vortex-enhance-clarity"><?php esc_html_e('Clarity Enhancement', 'vortex'); ?></label>
                                </div>
                            </div>
                            
                            <div class="vortex-form-group">
                                <div class="vortex-checkbox-control">
                                    <input type="checkbox" id="vortex-enhance-details" name="enhance_details" checked>
                                    <label for="vortex-enhance-details"><?php esc_html_e('Detail Enhancement', 'vortex'); ?></label>
                                </div>
                            </div>
                            
                            <div class="vortex-form-group">
                                <div class="vortex-checkbox-control">
                                    <input type="checkbox" id="vortex-remove-noise" name="remove_noise" checked>
                                    <label for="vortex-remove-noise"><?php esc_html_e('Noise Reduction', 'vortex'); ?></label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="vortex-form-group">
                            <label for="vortex-enhancement-strength"><?php esc_html_e('Enhancement Strength', 'vortex'); ?></label>
                            <div class="vortex-slider-container">
                                <input type="range" id="vortex-enhancement-strength" name="enhancement_strength" min="0.1" max="1" step="0.1" value="0.5">
                                <div class="vortex-slider-labels">
                                    <span><?php esc_html_e('Subtle', 'vortex'); ?></span>
                                    <span><?php esc_html_e('Dramatic', 'vortex'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="vortex-form-actions">
                        <button type="button" id="vortex-transform-btn" class="vortex-primary-button" disabled><?php esc_html_e('Transform Image', 'vortex'); ?></button>
                        <div class="vortex-transformation-progress" style="display: none;">
                            <div class="vortex-progress-bar"></div>
                            <div class="vortex-progress-text"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="vortex-img2img-canvas-area">
                <div class="vortex-canvas-container">
                    <div id="vortex-source-canvas-container" class="vortex-source-canvas-container">
                        <!-- Source image will be displayed here -->
                    </div>
                    <div id="vortex-result-canvas-container" class="vortex-result-canvas-container" style="display: none;">
                        <!-- Result image will be displayed here -->
                    </div>
                </div>
                
                <div class="vortex-canvas-actions">
                    <button type="button" class="vortex-action-btn vortex-save-result">
                        <?php esc_html_e('Save to Library', 'vortex-ai-marketplace'); ?>
                    </button>
                    
                    <button type="button" class="vortex-action-btn vortex-create-nft">
                        <?php esc_html_e('Create NFT', 'vortex-ai-marketplace'); ?>
                    </button>
                    
                    <button type="button" class="vortex-action-btn vortex-use-as-source">
                        <?php esc_html_e('Use as Source', 'vortex-ai-marketplace'); ?>
                    </button>
                    
                    <button type="button" class="vortex-action-btn vortex-reset">
                        <?php esc_html_e('Reset', 'vortex-ai-marketplace'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="vortex-img2img-history">
            <h3><?php esc_html_e('Recent Transformations', 'vortex'); ?></h3>
            <div class="vortex-history-grid">
                <?php if (empty($transformation_history)): ?>
                <div class="vortex-empty-history">
                    <?php esc_html_e('Your transformation history will appear here', 'vortex'); ?>
                </div>
                <?php else: ?>
                <?php foreach (array_slice($transformation_history, 0, 8) as $item): ?>
                <div class="vortex-history-item" data-id="<?php echo esc_attr($item['id']); ?>">
                    <div class="vortex-history-image">
                        <img src="<?php echo esc_url($item['thumbnail_url']); ?>" alt="<?php echo esc_attr($item['description']); ?>">
                    </div>
                    <div class="vortex-history-details">
                        <div class="vortex-history-mode"><?php echo esc_html($item['mode']); ?></div>
                        <div class="vortex-history-meta">
                            <?php echo esc_html(human_time_diff(strtotime($item['date']), current_time('timestamp'))); ?> <?php esc_html_e('ago', 'vortex'); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if ($args['enable_ai_learning']): ?>
    <script type="text/javascript">
        (function($) {
            $(document).ready(function() {
                // Track interface view
                $.post(vortexImg2Img.ajaxUrl, {
                    action: 'vortex_track_ai_interaction',
                    nonce: vortexImg2Img.nonce,
                    interaction: 'interface_view',
                    interface: 'img2img',
                    context: '<?php echo esc_js($args['agent_context']); ?>'
                });
                
                // Track mode selection
                $('.vortex-mode-btn').on('click', function() {
                    $.post(vortexImg2Img.ajaxUrl, {
                        action: 'vortex_track_ai_interaction',
                        nonce: vortexImg2Img.nonce,
                        interaction: 'mode_selection',
                        mode: $(this).data('mode'),
                        interface: 'img2img'
                    });
                });
                
                // Track source image upload
                $('#vortex-source-image').on('change', function() {
                    $.post(vortexImg2Img.ajaxUrl, {
                        action: 'vortex_track_ai_interaction',
                        nonce: vortexImg2Img.nonce,
                        interaction: 'image_upload',
                        interface: 'img2img'
                    });
                });
            });
        })(jQuery);
    </script>
    <?php endif;
    
    return ob_get_clean();
}

/**
 * Shortcode for image-to-image interface
 * 
 * @param array $atts Shortcode attributes
 * @return string Interface HTML
 */
function vortex_img2img_interface_shortcode($atts) {
    $atts = shortcode_atts(array(
        'width' => 768,
        'height' => 768,
        'advanced' => 'false',
        'modes' => 'style_transfer,inpainting,upscaling,enhancement',
        'image' => '',
        'prompt' => ''
    ), $atts);
    
    return vortex_img2img_interface(array(
        'canvas_width' => intval($atts['width']),
        'canvas_height' => intval($atts['height']),
        'show_advanced_options' => $atts['advanced'] === 'true' || current_user_can('edit_posts'),
        'initial_image_url' => esc_url($atts['image']),
        'initial_prompt' => sanitize_textarea_field($atts['prompt']),
        'modes' => explode(',', $atts['modes'])
    ));
}
add_shortcode('vortex_img2img', 'vortex_img2img_interface_shortcode'); 