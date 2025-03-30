<?php
/**
 * Thorius AI Concierge Shortcode
 * 
 * Enhanced version with tab interface, voice recognition,
 * analytics, and multi-agent connectivity.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius AI Concierge shortcode function
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function vortex_thorius_concierge_shortcode($atts) {
    // Define defaults and sanitize attributes
    $atts = shortcode_atts(array(
        'theme' => 'light',
        'position' => 'right',
        'welcome_message' => true,
        'show_tabs' => true,
        'enable_voice' => true,
        'enable_location' => true,
        'default_tab' => 'chat',
        'available_tabs' => 'chat,artwork,tola,web3,ai,events,analytics,marketplace'
    ), $atts);
    
    $theme = sanitize_text_field($atts['theme']);
    $position = sanitize_text_field($atts['position']);
    $welcome_message = filter_var($atts['welcome_message'], FILTER_VALIDATE_BOOLEAN);
    $show_tabs = filter_var($atts['show_tabs'], FILTER_VALIDATE_BOOLEAN);
    $enable_voice = filter_var($atts['enable_voice'], FILTER_VALIDATE_BOOLEAN);
    $enable_location = filter_var($atts['enable_location'], FILTER_VALIDATE_BOOLEAN);
    $default_tab = sanitize_text_field($atts['default_tab']);
    $available_tabs = array_map('trim', explode(',', sanitize_text_field($atts['available_tabs'])));
    
    // Initialize thorius instance for global access
    $thorius = Vortex_Thorius::get_instance();
    
    // Get user language preference or detect from browser
    $language = $thorius->detect_user_language();
    
    // Get user location if enabled
    $location = array();
    if ($enable_location) {
        require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/class-vortex-thorius-location.php';
        $location_handler = new Vortex_Thorius_Location();
        $location = $location_handler->get_user_location();
    }
    
    // Get welcome message
    $welcome = $welcome_message ? $thorius->get_welcome_message($language, $location) : '';
    
    // Get user ID for personalization
    $user_id = get_current_user_id();
    
    // Connect to all AI agents for real-time insights
    $ai_insights = array();
    if (class_exists('VORTEX_HURAII')) {
        $huraii = VORTEX_HURAII::get_instance();
        $ai_insights['HURAII'] = $huraii->get_user_insights($user_id);
    }
    
    if (class_exists('VORTEX_CLOE')) {
        $cloe = VORTEX_CLOE::get_instance();
        $ai_insights['CLOE'] = $cloe->get_user_insights($user_id);
    }
    
    if (class_exists('VORTEX_BusinessStrategist')) {
        $business_strategist = VORTEX_BusinessStrategist::get_instance();
        $ai_insights['BusinessStrategist'] = $business_strategist->get_market_insights($user_id);
    }
    
    // Initialize analytics engine
    require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/analytics/class-vortex-thorius-analytics.php';
    $analytics = new Vortex_Thorius_Analytics();
    
    // Start output buffering to collect HTML
    ob_start();
    
    // Get tab configurations
    $tabs = vortex_thorius_get_tabs($available_tabs, $default_tab);
    
    // Set class based on position and theme
    $theme_class = 'vortex-thorius-' . $theme;
    $position_class = 'vortex-thorius-' . $position;
    
    // Include necessary CSS and JS
    wp_enqueue_style('vortex-thorius-css');
    wp_enqueue_script('vortex-thorius-js');
    
    if ($enable_voice) {
        wp_enqueue_script('vortex-thorius-voice-js');
    }
    ?>
    
    <div id="vortex-thorius-concierge" class="vortex-thorius-container <?php echo esc_attr($theme_class); ?> <?php echo esc_attr($position_class); ?>">
        <!-- Thorius Header -->
        <div class="vortex-thorius-header">
            <div class="vortex-thorius-logo">
                <img src="<?php echo esc_url(VORTEX_PLUGIN_URL . 'assets/img/thorius-icon.png'); ?>" alt="Thorius AI">
            </div>
            <div class="vortex-thorius-title">
                <h3><?php esc_html_e('Thorius AI Concierge', 'vortex-ai-marketplace'); ?></h3>
            </div>
            <div class="vortex-thorius-controls">
                <?php if ($enable_voice): ?>
                <button type="button" class="vortex-thorius-voice-btn">
                    <span class="vortex-thorius-voice-icon"></span>
                </button>
                <?php endif; ?>
                <button type="button" class="vortex-thorius-minimize-btn">
                    <span class="vortex-thorius-minimize-icon"></span>
                </button>
            </div>
        </div>
        
        <!-- Thorius Tabs -->
        <?php if ($show_tabs): ?>
        <div class="vortex-thorius-tabs">
            <div class="vortex-thorius-tabs-inner">
                <?php foreach ($tabs as $tab_id => $tab): ?>
                    <?php if (in_array($tab_id, $available_tabs)): ?>
                    <button type="button" class="vortex-thorius-tab <?php echo $tab_id === $default_tab ? 'active' : ''; ?>" data-tab="<?php echo esc_attr($tab_id); ?>">
                        <span class="vortex-thorius-tab-icon <?php echo esc_attr($tab['icon_class']); ?>"></span>
                        <span class="vortex-thorius-tab-label"><?php echo esc_html($tab['label']); ?></span>
                    </button>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Thorius Content -->
        <div class="vortex-thorius-content">
            <?php foreach ($tabs as $tab_id => $tab): ?>
                <?php if (in_array($tab_id, $available_tabs)): ?>
                <div class="vortex-thorius-tab-content <?php echo $tab_id === $default_tab ? 'active' : ''; ?>" data-tab-content="<?php echo esc_attr($tab_id); ?>">
                    <?php 
                    // Include tab content template
                    include plugin_dir_path(__FILE__) . "../partials/thorius-tabs/{$tab_id}-tab.php"; 
                    ?>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <!-- Thorius Input Area -->
        <div class="vortex-thorius-input">
            <div class="vortex-thorius-input-container">
                <textarea id="vortex-thorius-query" placeholder="<?php esc_attr_e('Ask Thorius anything...', 'vortex-ai-marketplace'); ?>"></textarea>
                <button type="button" id="vortex-thorius-send">
                    <span class="vortex-thorius-send-icon"></span>
                </button>
            </div>
            <?php if ($enable_voice): ?>
            <div class="vortex-thorius-voice-input">
                <button type="button" id="vortex-thorius-voice-input">
                    <span class="vortex-thorius-mic-icon"></span>
                </button>
                <div class="vortex-thorius-voice-status">
                    <?php esc_html_e('Click to speak', 'vortex-ai-marketplace'); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Language selection -->
        <div class="vortex-thorius-footer">
            <select id="vortex-thorius-language">
                <?php foreach ($thorius->get_supported_languages() as $code => $name): ?>
                <option value="<?php echo esc_attr($code); ?>" <?php selected($language, $code); ?>><?php echo esc_html($name); ?></option>
                <?php endforeach; ?>
            </select>
            
            <?php if ($enable_location): ?>
            <button type="button" id="vortex-thorius-location" title="<?php esc_attr_e('Update location', 'vortex-ai-marketplace'); ?>">
                <span class="vortex-thorius-location-icon"></span>
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <script type="text/javascript">
        (function($) {
            $(document).ready(function() {
                const thorius = $('#vortex-thorius-concierge');
                const tabs = $('.vortex-thorius-tab');
                const tabContents = $('.vortex-thorius-tab-content');
                const minimizeBtn = $('.vortex-thorius-minimize-btn');
                const queryInput = $('#vortex-thorius-query');
                const sendBtn = $('#vortex-thorius-send');
                const voiceBtn = $('#vortex-thorius-voice-input');
                const languageSelect = $('#vortex-thorius-language');
                const locationBtn = $('#vortex-thorius-location');
                
                // Handle tab switching
                tabs.on('click', function() {
                    const tabId = $(this).data('tab');
                    tabs.removeClass('active');
                    $(this).addClass('active');
                    
                    tabContents.removeClass('active');
                    $(`.vortex-thorius-tab-content[data-tab-content="${tabId}"]`).addClass('active');
                    
                    // Track tab switch for analytics
                    $.post(vortex_ajax.ajax_url, {
                        action: 'vortex_thorius_track',
                        nonce: vortex_ajax.thorius_nonce,
                        track_type: 'tab_switch',
                        tab: tabId
                    });
                });
                
                // Handle minimize/maximize
                minimizeBtn.on('click', function() {
                    thorius.toggleClass('minimized');
                });
                
                // Handle sending queries
                function sendQuery() {
                    const query = queryInput.val().trim();
                    
                    if (!query) return;
                    
                    // Show loading state
                    const chatContent = $('.vortex-thorius-chat-content');
                    
                    // Add user message to chat
                    chatContent.append(`
                        <div class="vortex-thorius-message user-message">
                            <div class="vortex-thorius-message-content">${query}</div>
                        </div>
                    `);
                    
                    // Add loading message
                    chatContent.append(`
                        <div class="vortex-thorius-message thorius-message loading">
                            <div class="vortex-thorius-message-avatar">
                                <img src="${vortex_ajax.plugin_url}assets/img/thorius-avatar.png" alt="Thorius">
                            </div>
                            <div class="vortex-thorius-message-content">
                                <div class="vortex-thorius-typing-indicator">
                                    <span></span><span></span><span></span>
                                </div>
                            </div>
                        </div>
                    `);
                    
                    // Scroll to bottom
                    chatContent.scrollTop(chatContent[0].scrollHeight);
                    
                    // Clear input
                    queryInput.val('');
                    
                    // Get active tab
                    const activeTab = $('.vortex-thorius-tab.active').data('tab');
                    
                    // Get language
                    const language = languageSelect.val();
                    
                    // Get location data
                    let locationData = {};
                    if (window.thoriumLocationData) {
                        locationData = window.thoriumLocationData;
                    }
                    
                    // Make AJAX request
                    $.ajax({
                        url: vortex_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'vortex_thorius_query',
                            nonce: vortex_ajax.thorius_nonce,
                            query: query,
                            tab: activeTab,
                            language: language,
                            location: locationData,
                            is_voice: false
                        },
                        success: function(response) {
                            // Remove loading message
                            $('.vortex-thorius-message.loading').remove();
                            
                            if (response.success) {
                                // Add Thorius response
                                chatContent.append(`
                                    <div class="vortex-thorius-message thorius-message">
                                        <div class="vortex-thorius-message-avatar">
                                            <img src="${vortex_ajax.plugin_url}assets/img/thorius-avatar.png" alt="Thorius">
                                        </div>
                                        <div class="vortex-thorius-message-content">${response.data.response}</div>
                                    </div>
                                `);
                                
                                // Add follow-up suggestions if available
                                if (response.data.followup_suggestions && response.data.followup_suggestions.length > 0) {
                                    let suggestionsHtml = '<div class="vortex-thorius-suggestions">';
                                    
                                    response.data.followup_suggestions.forEach(function(suggestion) {
                                        suggestionsHtml += `<button type="button" class="vortex-thorius-suggestion">${suggestion}</button>`;
                                    });
                                    
                                    suggestionsHtml += '</div>';
                                    chatContent.append(suggestionsHtml);
                                }
                                
                                // Handle any actions in the response
                                if (response.data.actions) {
                                    handleThorusActions(response.data.actions);
                                }
                                
                                // Update visualization data if in analytics tab
                                if (activeTab === 'analytics' && response.data.visualization_data) {
                                    updateAnalyticsVisualization(response.data.visualization_data);
                                }
                            } else {
                                // Show error message
                                chatContent.append(`
                                    <div class="vortex-thorius-message thorius-message error">
                                        <div class="vortex-thorius-message-avatar">
                                            <img src="${vortex_ajax.plugin_url}assets/img/thorius-avatar.png" alt="Thorius">
                                        </div>
                                        <div class="vortex-thorius-message-content">${response.data.message || 'Sorry, I encountered an error.'}</div>
                                    </div>
                                `);
                            }
                            
                            // Scroll to bottom
                            chatContent.scrollTop(chatContent[0].scrollHeight);
                        },
                        error: function() {
                            // Remove loading message
                            $('.vortex-thorius-message.loading').remove();
                            
                            // Show error message
                            chatContent.append(`
                                <div class="vortex-thorius-message thorius-message error">
                                    <div class="vortex-thorius-message-avatar">
                                        <img src="${vortex_ajax.plugin_url}assets/img/thorius-avatar.png" alt="Thorius">
                                    </div>
                                    <div class="vortex-thorius-message-content">${vortex_ajax.error_message || 'Sorry, I encountered an error.'}</div>
                                </div>
                            `);
                            
                            // Scroll to bottom
                            chatContent.scrollTop(chatContent[0].scrollHeight);
                        }
                    });
                }
                
                // Send query on button click
                sendBtn.on('click', function() {
                    sendQuery();
                });
                
                // Send query on Enter key (but allow Shift+Enter for new lines)
                queryInput.on('keydown', function(e) {
                    if (e.keyCode === 13 && !e.shiftKey) {
                        e.preventDefault();
                        sendQuery();
                    }
                });
                
                // Voice input handling
                if (voiceBtn.length) {
                    let recognition;
                    
                    if ('webkitSpeechRecognition' in window) {
                        recognition = new webkitSpeechRecognition();
                        recognition.continuous = false;
                        recognition.interimResults = true;
                        
                        // Set language based on selection
                        recognition.lang = languageCodeToLocale(languageSelect.val());
                        
                        // Update language when selection changes
                        languageSelect.on('change', function() {
                            recognition.lang = languageCodeToLocale($(this).val());
                        });
                        
                        let finalTranscript = '';
                        
                        recognition.onstart = function() {
                            $('.vortex-thorius-voice-status').text('<?php esc_html_e('Listening...', 'vortex-ai-marketplace'); ?>');
                            voiceBtn.addClass('listening');
                        };
                        
                        recognition.onresult = function(event) {
                            let interimTranscript = '';
                            
                            for (let i = event.resultIndex; i < event.results.length; ++i) {
                                if (event.results[i].isFinal) {
                                    finalTranscript += event.results[i][0].transcript;
                                } else {
                                    interimTranscript += event.results[i][0].transcript;
                                }
                            }
                            
                            // Show interim results
                            $('.vortex-thorius-voice-status').text(interimTranscript || finalTranscript || '<?php esc_html_e('Listening...', 'vortex-ai-marketplace'); ?>');
                        };
                        
                        recognition.onerror = function(event) {
                            $('.vortex-thorius-voice-status').text('<?php esc_html_e('Error: ' . esc_html(event.error), 'vortex-ai-marketplace'); ?>');
                            voiceBtn.removeClass('listening');
                        };
                        
                        recognition.onend = function() {
                            voiceBtn.removeClass('listening');
                            
                            if (finalTranscript) {
                                queryInput.val(finalTranscript);
                                $('.vortex-thorius-voice-status').text('<?php esc_html_e('Click to speak', 'vortex-ai-marketplace'); ?>');
                                
                                // Auto-send the voice query
                                sendQuery();
                            } else {
                                $('.vortex-thorius-voice-status').text('<?php esc_html_e('Click to speak', 'vortex-ai-marketplace'); ?>');
                            }
                            
                            finalTranscript = '';
                        };
                        
                        voiceBtn.on('click', function() {
                            if (voiceBtn.hasClass('listening')) {
                                recognition.stop();
                            } else {
                                finalTranscript = '';
                                recognition.start();
                            }
                        });
                    } else {
                        // Speech recognition not supported
                        $('.vortex-thorius-voice-input').addClass('not-supported');
                        $('.vortex-thorius-voice-status').text('<?php esc_html_e('Voice input not supported in this browser', 'vortex-ai-marketplace'); ?>');
                    }
                }
                
                // Location handling
                if (locationBtn.length) {
                    locationBtn.on('click', function() {
                        if (navigator.geolocation) {
                            locationBtn.addClass('loading');
                            
                            navigator.geolocation.getCurrentPosition(
                                function(position) {
                                    // Store location data globally
                                    window.thoriumLocationData = {
                                        latitude: position.coords.latitude,
                                        longitude: position.coords.longitude
                                    };
                                    
                                    // Update UI
                                    locationBtn.removeClass('loading').addClass('active');
                                    
                                    // Notify user
                                    const chatContent = $('.vortex-thorius-chat-content');
                                    chatContent.append(`
                                        <div class="vortex-thorius-message system-message">
                                            <div class="vortex-thorius-message-content">
                                                <?php esc_html_e('Location updated. I can now provide location-specific information.', 'vortex-ai-marketplace'); ?>
                                            </div>
                                        </div>
                                    `);
                                    
                                    // Save location to server
                                    $.post(vortex_ajax.ajax_url, {
                                        action: 'vortex_thorius_update_location',
                                        nonce: vortex_ajax.thorius_nonce,
                                        latitude: position.coords.latitude,
                                        longitude: position.coords.longitude
                                    });
                                    
                                    // Scroll to bottom
                                    chatContent.scrollTop(chatContent[0].scrollHeight);
                                },
                                function(error) {
                                    locationBtn.removeClass('loading');
                                    
                                    // Show error message
                                    const chatContent = $('.vortex-thorius-chat-content');
                                    
                                    let errorMsg = '<?php esc_html_e('Unable to determine your location.', 'vortex-ai-marketplace'); ?>';
                                    
                                    switch(error.code) {
                                        case error.PERMISSION_DENIED:
                                            errorMsg = '<?php esc_html_e('Location permission denied.', 'vortex-ai-marketplace'); ?>';
                                            break;
                                        case error.POSITION_UNAVAILABLE:
                                            errorMsg = '<?php esc_html_e('Location information unavailable.', 'vortex-ai-marketplace'); ?>';
                                            break;
                                        case error.TIMEOUT:
                                            errorMsg = '<?php esc_html_e('Location request timed out.', 'vortex-ai-marketplace'); ?>';
                                            break;
                                    }
                                    
                                    chatContent.append(`
                                        <div class="vortex-thorius-message system-message error">
                                            <div class="vortex-thorius-message-content">${errorMsg}</div>
                                        </div>
                                    `);
                                    
                                    // Scroll to bottom
                                    chatContent.scrollTop(chatContent[0].scrollHeight);
                                }
                            );
                        } else {
                            // Geolocation not supported
                            const chatContent = $('.vortex-thorius-chat-content');
                            chatContent.append(`
                                <div class="vortex-thorius-message system-message error">
                                    <div class="vortex-thorius-message-content">
                                        <?php esc_html_e('Geolocation is not supported by this browser.', 'vortex-ai-marketplace'); ?>
                                    </div>
                                </div>
                            `);
                            
                            // Scroll to bottom
                            chatContent.scrollTop(chatContent[0].scrollHeight);
                        }
                    });
                }
                
                // Handle suggestion clicks
                $(document).on('click', '.vortex-thorius-suggestion', function() {
                    const suggestion = $(this).text();
                    queryInput.val(suggestion);
                    sendQuery();
                });
                
                // Function to handle Thorius actions
                function handleThorusActions(actions) {
                    if (!actions || typeof actions !== 'object') return;
                    
                    // Handle different types of actions
                    if (actions.navigate_to) {
                        setTimeout(function() {
                            window.location.href = actions.navigate_to;
                        }, 1000);
                    }
                    
                    if (actions.switch_tab) {
                        $(`.vortex-thorius-tab[data-tab="${actions.switch_tab}"]`).trigger('click');
                    }
                    
                    if (actions.open_modal) {
                        // Implementation depends on your modal system
                        if (typeof openVortexModal === 'function') {
                            openVortexModal(actions.open_modal);
                        }
                    }
                    
                    // More action handlers can be added here
                }
                
                // Function to update analytics visualizations
                function updateAnalyticsVisualization(data) {
                    const container = $('.vortex-thorius-analytics-visualization');
                    
                    if (!container.length || !data) return;
                    
                    // Clear existing charts
                    container.empty();
                    
                    // Create charts based on data type
                    if (data.chart_type === 'bar') {
                        // Implementation depends on your charting library
                        createBarChart(container, data);
                    } else if (data.chart_type === 'line') {
                        createLineChart(container, data);
                    } else if (data.chart_type === 'pie') {
                        createPieChart(container, data);
                    }
                }
                
                // Helper function to convert language code to locale for speech recognition
                function languageCodeToLocale(code) {
                    const localeMap = {
                        'en': 'en-US',
                        'es': 'es-ES',
                        'fr': 'fr-FR',
                        'de': 'de-DE',
                        'it': 'it-IT',
                        'pt': 'pt-PT',
                        'ja': 'ja-JP',
                        'zh': 'zh-CN',
                        'ar': 'ar-SA',
                        'ru': 'ru-RU'
                    };
                    
                    return localeMap[code] || 'en-US';
                }
                
                // Initialize with welcome message if provided
                <?php if ($welcome): ?>
                setTimeout(function() {
                    $('.vortex-thorius-chat-content').append(`
                        <div class="vortex-thorius-message thorius-message">
                            <div class="vortex-thorius-message-avatar">
                                <img src="${vortex_ajax.plugin_url}assets/img/thorius-avatar.png" alt="Thorius">
                            </div>
                            <div class="vortex-thorius-message-content"><?php echo esc_js($welcome); ?></div>
                        </div>
                    `);
                }, 500);
                <?php endif; ?>
            });
        })(jQuery);
    </script>
    <?php
    
    // Return the content
    return ob_get_clean();
}

/**
 * Get tab configurations
 *
 * @param array $available_tabs List of available tabs
 * @param string $default_tab Default active tab
 * @return array Tab configurations
 */
function vortex_thorius_get_tabs($available_tabs, $default_tab) {
    $all_tabs = array(
        'chat' => array(
            'label' => __('Chat', 'vortex-ai-marketplace'),
            'icon_class' => 'chat-icon',
            'description' => __('General conversation with Thorius.', 'vortex-ai-marketplace')
        ),
        'artwork' => array(
            'label' => __('Artwork', 'vortex-ai-marketplace'),
            'icon_class' => 'artwork-icon',
            'description' => __('Discover and explore digital artwork.', 'vortex-ai-marketplace')
        ),
        'tola' => array(
            'label' => __('TOLA', 'vortex-ai-marketplace'),
            'icon_class' => 'tola-icon',
            'description' => __('Time-limited Ownership License Agreement assistance.', 'vortex-ai-marketplace')
        ),
        'web3' => array(
            'label' => __('Web3', 'vortex-ai-marketplace'),
            'icon_class' => 'web3-icon',
            'description' => __('Blockchain and Web3 information.', 'vortex-ai-marketplace')
        ),
        'ai' => array(
            'label' => __('AI Tools', 'vortex-ai-marketplace'),
            'icon_class' => 'ai-icon',
            'description' => __('AI tools and capabilities available.', 'vortex-ai-marketplace')
        ),
        'events' => array(
            'label' => __('Events', 'vortex-ai-marketplace'),
            'icon_class' => 'events-icon',
            'description' => __('Upcoming events and exhibitions.', 'vortex-ai-marketplace')
        ),
        'analytics' => array(
            'label' => __('Analytics', 'vortex-ai-marketplace'),
            'icon_class' => 'analytics-icon',
            'description' => __('Market trends and platform analytics.', 'vortex-ai-marketplace')
        ),
        'marketplace' => array(
            'label' => __('Marketplace', 'vortex-ai-marketplace'),
            'icon_class' => 'marketplace-icon',
            'description' => __('Explore marketplace listings and opportunities.', 'vortex-ai-marketplace')
        )
    );
    
    $tabs = array();
    
    foreach ($available_tabs as $tab_id) {
        if (isset($all_tabs[$tab_id])) {
            $tabs[$tab_id] = $all_tabs[$tab_id];
        }
    }
    
    // Ensure default tab is included
    if (!isset($tabs[$default_tab]) && !empty($tabs)) {
        // If default tab is not available, use the first available tab
        $default_tab = array_key_first($tabs);
    }
    
    return $tabs;
}

// Register shortcode
add_shortcode('vortex_thorius', 'vortex_thorius_concierge_shortcode'); 