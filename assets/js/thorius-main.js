/**
 * Thorius AI Concierge Main JavaScript
 * Handles UI interactions and tab switching
 */
(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        initializeThorius();
    });
    
    /**
     * Initialize all Thorius functionality
     */
    function initializeThorius() {
        // Initialize tabs
        initializeTabs();
        
        // Initialize theme switching
        initializeThemeToggle();
        
        // Initialize artwork functionality
        initializeArtworkTab();
        
        // Initialize NFT functionality
        initializeNFTTab();
        
        // Initialize strategy functionality
        initializeStrategyTab();
        
        // Initialize chat suggestions
        initializeChatSuggestions();
        
        // Initialize concierge minimizing/maximizing
        initializeConciergeToggle();
    }
    
    /**
     * Initialize tab switching
     */
    function initializeTabs() {
        $('.vortex-thorius-tab').on('click', function() {
            const tabId = $(this).data('tab');
            
            // Update active tab
            $('.vortex-thorius-tab').removeClass('active');
            $(this).addClass('active');
            
            // Show tab content
            $('.vortex-thorius-tab-content').removeClass('active');
            $(`#vortex-thorius-${tabId}-tab`).addClass('active');
            
            // Track tab switch if analytics is available
            if (typeof vortex_thorius_params !== 'undefined') {
                $.post(vortex_thorius_params.ajax_url, {
                    action: 'vortex_thorius_track',
                    nonce: vortex_thorius_params.nonce,
                    track_action: 'tab_switch',
                    tab: tabId
                });
            }
        });
    }
    
    /**
     * Initialize theme toggling
     */
    function initializeThemeToggle() {
        $('.vortex-thorius-theme-toggle-btn').on('click', function() {
            const $container = $(this).closest('.vortex-thorius-container');
            
            if ($container.hasClass('vortex-thorius-light')) {
                $container.removeClass('vortex-thorius-light').addClass('vortex-thorius-dark');
                localStorage.setItem('vortex_thorius_theme', 'dark');
            } else {
                $container.removeClass('vortex-thorius-dark').addClass('vortex-thorius-light');
                localStorage.setItem('vortex_thorius_theme', 'light');
            }
        });
        
        // Check for saved theme preference
        const savedTheme = localStorage.getItem('vortex_thorius_theme');
        if (savedTheme) {
            $('.vortex-thorius-container')
                .removeClass('vortex-thorius-light vortex-thorius-dark')
                .addClass(`vortex-thorius-${savedTheme}`);
        }
    }
    
    /**
     * Initialize artwork tab functionality
     */
    function initializeArtworkTab() {
        // Handle artwork generation
        $('#vortex-thorius-artwork-generate').on('click', function() {
            const prompt = $('#vortex-thorius-artwork-prompt').val().trim();
            const style = $('#vortex-thorius-artwork-style').val();
            const size = $('#vortex-thorius-artwork-size').val();
            
            if (!prompt) {
                return; // No prompt entered
            }
            
            // Show loading state
            $('#vortex-thorius-artwork-loading').show();
            $('#vortex-thorius-artwork-output').empty();
            
            // Make AJAX request to generate artwork
            $.ajax({
                url: vortex_thorius_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_thorius_generate_artwork',
                    nonce: vortex_thorius_params.nonce,
                    prompt: prompt,
                    style: style,
                    size: size
                },
                success: function(response) {
                    $('#vortex-thorius-artwork-loading').hide();
                    
                    if (response.success) {
                        displayArtwork(response.data);
                    } else {
                        $('#vortex-thorius-artwork-output').html(
                            `<div class="vortex-thorius-error">${response.data.message || 'Error generating artwork'}</div>`
                        );
                    }
                },
                error: function() {
                    $('#vortex-thorius-artwork-loading').hide();
                    $('#vortex-thorius-artwork-output').html(
                        `<div class="vortex-thorius-error">Server error occurred. Please try again.</div>`
                    );
                }
            });
        });
    }
    
    /**
     * Display generated artwork
     */
    function displayArtwork(artworkData) {
        const $output = $('#vortex-thorius-artwork-output');
        const $actions = $('.vortex-thorius-artwork-actions');
        
        $output.html(`
            <div class="vortex-thorius-artwork-item" data-id="${artworkData.id}">
                <img src="${artworkData.image_url}" alt="${artworkData.prompt}" />
                <div class="vortex-thorius-artwork-info">
                    <div class="vortex-thorius-artwork-prompt">${artworkData.prompt}</div>
                    <div class="vortex-thorius-artwork-style">Style: ${artworkData.style}</div>
                </div>
            </div>
        `);
        
        // Show actions
        $actions.show();
    }
    
    /**
     * Initialize NFT tab functionality
     */
    function initializeNFTTab() {
        // Switch between NFT creation options
        $('.vortex-thorius-nft-option').on('click', function() {
            const targetId = $(this).attr('id');
            const panelId = targetId.replace('select-', '').replace('create-', '').replace('upload-', '');
            
            // Update active button
            $('.vortex-thorius-nft-option').removeClass('active');
            $(this).addClass('active');
            
            // Show corresponding panel
            $('.vortex-thorius-nft-option-panel').removeClass('active');
            $(`#vortex-thorius-nft-${panelId}`).addClass('active');
        });
        
        // Handle collection dropdown
        $('#vortex-thorius-nft-collection').on('change', function() {
            if ($(this).val() === 'new') {
                $('#vortex-thorius-nft-new-collection').show();
            } else {
                $('#vortex-thorius-nft-new-collection').hide();
            }
        });
        
        // Enable mint button when artwork is selected
        $('.vortex-thorius-nft-gallery').on('click', '.vortex-thorius-gallery-item', function() {
            $('.vortex-thorius-gallery-item').removeClass('selected');
            $(this).addClass('selected');
            $('#vortex-thorius-nft-mint').prop('disabled', false);
        });
    }
    
    /**
     * Initialize strategy tab functionality
     */
    function initializeStrategyTab() {
        // Update custom fields based on analysis type
        $('#vortex-thorius-strategy-type').on('change', function() {
            const analysisType = $(this).val();
            const $customFields = $('#vortex-thorius-strategy-customFields');
            
            // Clear existing fields
            $customFields.empty();
            
            // Add type-specific fields
            switch (analysisType) {
                case 'market_analysis':
                    $customFields.html(`
                        <div class="vortex-thorius-strategy-customField">
                            <label for="vortex-thorius-strategy-depth">${vortexThorius.i18n.depth_of_analysis}:</label>
                            <select id="vortex-thorius-strategy-depth">
                                <option value="basic">${vortexThorius.i18n.basic}</option>
                                <option value="detailed" selected>${vortexThorius.i18n.detailed}</option>
                                <option value="comprehensive">${vortexThorius.i18n.comprehensive}</option>
                            </select>
                        </div>
                    `);
                    break;
                    
                case 'price_optimization':
                    $customFields.html(`
                        <div class="vortex-thorius-strategy-customField">
                            <label for="vortex-thorius-strategy-current-price">${vortexThorius.i18n.current_price}:</label>
                            <input type="number" id="vortex-thorius-strategy-current-price" min="0" step="0.01" value="0.1">
                            <select id="vortex-thorius-strategy-currency">
                                <option value="eth">ETH</option>
                                <option value="matic">MATIC</option>
                                <option value="usd">USD</option>
                            </select>
                        </div>
                        <div class="vortex-thorius-strategy-customField">
                            <label for="vortex-thorius-strategy-competitor-count">${vortexThorius.i18n.competitor_count}:</label>
                            <input type="number" id="vortex-thorius-strategy-competitor-count" min="1" max="10" value="3">
                        </div>
                    `);
                    break;
                    
                case 'trend_prediction':
                    $customFields.html(`
                        <div class="vortex-thorius-strategy-customField">
                            <label for="vortex-thorius-strategy-forecast">${vortexThorius.i18n.forecast_period}:</label>
                            <select id="vortex-thorius-strategy-forecast">
                                <option value="1month">${vortexThorius.i18n.one_month}</option>
                                <option value="3months" selected>${vortexThorius.i18n.three_months}</option>
                                <option value="6months">${vortexThorius.i18n.six_months}</option>
                                <option value="1year">${vortexThorius.i18n.one_year}</option>
                            </select>
                        </div>
                    `);
                    break;
            }
        });
        
        // Trigger change to initialize default fields
        $('#vortex-thorius-strategy-type').trigger('change');
        
        // Handle analysis submission
        $('#vortex-thorius-strategy-analyze').on('click', function() {
            const market = $('#vortex-thorius-strategy-market').val();
            const timeframe = $('#vortex-thorius-strategy-time').val();
            const analysisType = $('#vortex-thorius-strategy-type').val();
            
            // Show loading state
            $('#vortex-thorius-strategy-loading').show();
            $('#vortex-thorius-strategy-output').empty();
            
            // Get custom fields based on analysis type
            let customData = {};
            
            switch (analysisType) {
                case 'market_analysis':
                    customData.depth = $('#vortex-thorius-strategy-depth').val();
                    break;
                    
                case 'price_optimization':
                    customData.current_price = $('#vortex-thorius-strategy-current-price').val();
                    customData.currency = $('#vortex-thorius-strategy-currency').val();
                    customData.competitor_count = $('#vortex-thorius-strategy-competitor-count').val();
                    break;
                    
                case 'trend_prediction':
                    customData.forecast_period = $('#vortex-thorius-strategy-forecast').val();
                    break;
            }
            
            // Make AJAX request for analysis
            $.ajax({
                url: vortex_thorius_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_thorius_analyze_strategy',
                    nonce: vortex_thorius_params.nonce,
                    market: market,
                    timeframe: timeframe,
                    analysis_type: analysisType,
                    custom_data: customData
                },
                success: function(response) {
                    $('#vortex-thorius-strategy-loading').hide();
                    
                    if (response.success) {
                        displayAnalysis(response.data);
                    } else {
                        $('#vortex-thorius-strategy-output').html(
                            `<div class="vortex-thorius-error">${response.data.message || 'Error generating analysis'}</div>`
                        );
                    }
                },
                error: function() {
                    $('#vortex-thorius-strategy-loading').hide();
                    $('#vortex-thorius-strategy-output').html(
                        `<div class="vortex-thorius-error">Server error occurred. Please try again.</div>`
                    );
                }
            });
        });
    }
    
    /**
     * Initialize chat suggestions
     */
    function initializeChatSuggestions() {
        $('.vortex-thorius-chat-suggestion').on('click', function() {
            const suggestionText = $(this).text();
            
            // Set suggestion as input value
            $('#vortex-thorius-message-input').val(suggestionText);
            
            // Submit the form
            $('#vortex-thorius-message-form').submit();
        });
    }
    
    /**
     * Initialize concierge minimizing/maximizing
     */
    function initializeConciergeToggle() {
        // Minimize concierge
        $('.vortex-thorius-minimize-btn').on('click', function() {
            const $container = $(this).closest('.vortex-thorius-container');
            $container.addClass('vortex-thorius-minimized');
            
            // Show maximize button
            $('<button>', {
                class: 'vortex-thorius-maximize-btn',
                html: '<svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>',
                'aria-label': 'Maximize'
            }).appendTo('body').on('click', function() {
                $container.removeClass('vortex-thorius-minimized');
                $(this).remove();
            });
        });
    }
    
})(jQuery); 