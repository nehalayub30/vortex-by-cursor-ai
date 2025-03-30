<?php
/**
 * Thorius Agent Shortcode
 * 
 * Renders standalone interface for specific AI agent
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Sanitize attributes
$agent = sanitize_text_field($atts['agent']);
$theme = sanitize_text_field($atts['theme']);

// Map agent names to titles and descriptions
$agent_data = array(
    'huraii' => array(
        'title' => 'HURAII AI Studio',
        'description' => 'Advanced AI image generation and transformation',
        'icon_class' => 'huraii-icon',
    ),
    'cloe' => array(
        'title' => 'CLOE Art Discovery',
        'description' => 'Art discovery and curation assistant',
        'icon_class' => 'cloe-icon',
    ),
    'strategist' => array(
        'title' => 'Business Strategist',
        'description' => 'Market insights and trend analysis',
        'icon_class' => 'strategist-icon',
    ),
);

// Get agent data or use default
$current_agent = isset($agent_data[$agent]) ? $agent_data[$agent] : array(
    'title' => 'AI Agent',
    'description' => 'Powered by Thorius',
    'icon_class' => 'thorius-icon',
);
?>

<div class="vortex-thorius-agent-embed vortex-thorius-<?php echo esc_attr($theme); ?>">
    <div class="vortex-thorius-agent-header">
        <div class="vortex-thorius-agent-title">
            <div class="vortex-thorius-agent-icon <?php echo esc_attr($current_agent['icon_class']); ?>"></div>
            <h4><?php echo esc_html($current_agent['title']); ?></h4>
        </div>
        <div class="vortex-thorius-agent-actions">
            <button type="button" class="vortex-thorius-theme-toggle-btn" aria-label="<?php esc_attr_e('Toggle theme', 'vortex-ai-marketplace'); ?>">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path class="sun-icon" d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0zm0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13zm8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zM3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8zm10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0zm-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zm9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707zM4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708z"/>
                    <path class="moon-icon" d="M6 .278a.768.768 0 0 1 .08.858 7.208 7.208 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277.527 0 1.04-.055 1.533-.16a.787.787 0 0 1 .81.316.733.733 0 0 1-.031.893A8.349 8.349 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.752.752 0 0 1 6 .278z"/>
                </svg>
            </button>
        </div>
    </div>
    
    <div class="vortex-thorius-agent-content">
        <p class="vortex-thorius-agent-description"><?php echo esc_html($current_agent['description']); ?></p>
        
        <?php if ($agent === 'huraii'): ?>
            <!-- HURAII Agent Interface -->
            <div class="vortex-huraii-interface">
                <div class="vortex-huraii-input">
                    <textarea id="vortex-huraii-prompt" class="vortex-agent-input" placeholder="<?php esc_attr_e('Describe the image you want to create...', 'vortex-ai-marketplace'); ?>"></textarea>
                    <div class="vortex-huraii-options">
                        <select id="vortex-huraii-style" class="vortex-agent-select">
                            <option value="realistic"><?php esc_html_e('Realistic', 'vortex-ai-marketplace'); ?></option>
                            <option value="abstract"><?php esc_html_e('Abstract', 'vortex-ai-marketplace'); ?></option>
                            <option value="digital-art"><?php esc_html_e('Digital Art', 'vortex-ai-marketplace'); ?></option>
                            <option value="watercolor"><?php esc_html_e('Watercolor', 'vortex-ai-marketplace'); ?></option>
                            <option value="cartoon"><?php esc_html_e('Cartoon', 'vortex-ai-marketplace'); ?></option>
                        </select>
                        <select id="vortex-huraii-size" class="vortex-agent-select">
                            <option value="1024x1024"><?php esc_html_e('1024×1024', 'vortex-ai-marketplace'); ?></option>
                            <option value="1024x1792"><?php esc_html_e('1024×1792', 'vortex-ai-marketplace'); ?></option>
                            <option value="1792x1024"><?php esc_html_e('1792×1024', 'vortex-ai-marketplace'); ?></option>
                        </select>
                    </div>
                    <button id="vortex-huraii-generate" class="vortex-agent-button"><?php esc_html_e('Generate Image', 'vortex-ai-marketplace'); ?></button>
                </div>
                <div class="vortex-huraii-result">
                    <div class="vortex-huraii-loading" style="display:none;"><?php esc_html_e('Creating your masterpiece...', 'vortex-ai-marketplace'); ?></div>
                    <div id="vortex-huraii-output" class="vortex-huraii-output"></div>
                </div>
            </div>
        <?php elseif ($agent === 'cloe'): ?>
            <!-- CLOE Agent Interface -->
            <div class="vortex-cloe-interface">
                <div class="vortex-cloe-preferences">
                    <div class="vortex-cloe-prompt">
                        <textarea id="vortex-cloe-prompt" class="vortex-agent-input" placeholder="<?php esc_attr_e('Describe your art preferences...', 'vortex-ai-marketplace'); ?>"></textarea>
                    </div>
                    <div class="vortex-cloe-options">
                        <div class="vortex-cloe-budget">
                            <label for="vortex-cloe-budget-min"><?php esc_html_e('Budget Range:', 'vortex-ai-marketplace'); ?></label>
                            <input type="number" id="vortex-cloe-budget-min" min="0" value="100" class="vortex-agent-input-small">
                            <span><?php esc_html_e('to', 'vortex-ai-marketplace'); ?></span>
                            <input type="number" id="vortex-cloe-budget-max" min="0" value="5000" class="vortex-agent-input-small">
                        </div>
                        <div class="vortex-cloe-styles">
                            <label><?php esc_html_e('Art Styles:', 'vortex-ai-marketplace'); ?></label>
                            <div class="vortex-cloe-checkbox-group">
                                <label><input type="checkbox" value="abstract"> <?php esc_html_e('Abstract', 'vortex-ai-marketplace'); ?></label>
                                <label><input type="checkbox" value="contemporary"> <?php esc_html_e('Contemporary', 'vortex-ai-marketplace'); ?></label>
                                <label><input type="checkbox" value="digital"> <?php esc_html_e('Digital', 'vortex-ai-marketplace'); ?></label>
                                <label><input type="checkbox" value="photography"> <?php esc_html_e('Photography', 'vortex-ai-marketplace'); ?></label>
                            </div>
                        </div>
                    </div>
                    <button id="vortex-cloe-discover" class="vortex-agent-button"><?php esc_html_e('Discover Art', 'vortex-ai-marketplace'); ?></button>
                </div>
                <div class="vortex-cloe-result">
                    <div class="vortex-cloe-loading" style="display:none;"><?php esc_html_e('Searching for perfect matches...', 'vortex-ai-marketplace'); ?></div>
                    <div id="vortex-cloe-output" class="vortex-cloe-output"></div>
                </div>
            </div>
        <?php elseif ($agent === 'strategist'): ?>
            <!-- Business Strategist Interface -->
            <div class="vortex-strategist-interface">
                <div class="vortex-strategist-input">
                    <div class="vortex-strategist-market-select">
                        <label for="vortex-strategist-market"><?php esc_html_e('Market:', 'vortex-ai-marketplace'); ?></label>
                        <select id="vortex-strategist-market" class="vortex-agent-select">
                            <option value="nft"><?php esc_html_e('NFT Market', 'vortex-ai-marketplace'); ?></option>
                            <option value="digital-art"><?php esc_html_e('Digital Art', 'vortex-ai-marketplace'); ?></option>
                            <option value="traditional-art"><?php esc_html_e('Traditional Art', 'vortex-ai-marketplace'); ?></option>
                            <option value="collectibles"><?php esc_html_e('Collectibles', 'vortex-ai-marketplace'); ?></option>
                        </select>
                    </div>
                    <div class="vortex-strategist-timeframe">
                        <label for="vortex-strategist-timeframe"><?php esc_html_e('Timeframe:', 'vortex-ai-marketplace'); ?></label>
                        <select id="vortex-strategist-timeframe" class="vortex-agent-select">
                            <option value="7days"><?php esc_html_e('7 Days', 'vortex-ai-marketplace'); ?></option>
                            <option value="30days" selected><?php esc_html_e('30 Days', 'vortex-ai-marketplace'); ?></option>
                            <option value="90days"><?php esc_html_e('90 Days', 'vortex-ai-marketplace'); ?></option>
                            <option value="1year"><?php esc_html_e('1 Year', 'vortex-ai-marketplace'); ?></option>
                        </select>
                    </div>
                    <div class="vortex-strategist-analysis-type">
                        <label for="vortex-strategist-analysis"><?php esc_html_e('Analysis Type:', 'vortex-ai-marketplace'); ?></label>
                        <select id="vortex-strategist-analysis" class="vortex-agent-select">
                            <option value="market_analysis"><?php esc_html_e('Market Analysis', 'vortex-ai-marketplace'); ?></option>
                            <option value="price_optimization"><?php esc_html_e('Price Optimization', 'vortex-ai-marketplace'); ?></option>
                            <option value="trend_prediction"><?php esc_html_e('Trend Prediction', 'vortex-ai-marketplace'); ?></option>
                        </select>
                    </div>
                    <button id="vortex-strategist-analyze" class="vortex-agent-button"><?php esc_html_e('Analyze', 'vortex-ai-marketplace'); ?></button>
                </div>
                <div class="vortex-strategist-result">
                    <div class="vortex-strategist-loading" style="display:none;"><?php esc_html_e('Analyzing market data...', 'vortex-ai-marketplace'); ?></div>
                    <div id="vortex-strategist-output" class="vortex-strategist-output"></div>
                </div>
            </div>
        <?php else: ?>
            <!-- Generic Agent Interface -->
            <div class="vortex-thorius-generic-interface">
                <p><?php esc_html_e('This agent is not available or properly configured.', 'vortex-ai-marketplace'); ?></p>
                <p><a href="#" class="switch-to-thorius"><?php esc_html_e('Try Thorius AI Concierge instead', 'vortex-ai-marketplace'); ?></a></p>
            </div>
        <?php endif; ?>
    </div>
</div> 