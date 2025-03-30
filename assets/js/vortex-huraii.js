/**
 * VORTEX HURAII JavaScript
 * 
 * Handles client-side interactions for the HURAII AI agent,
 * ensuring continuous learning and robust error handling.
 * 
 * @package VORTEX_AI_Marketplace
 */

(function($) {
    'use strict';
    
    // Main HURAII object
    const VortexHURAII = {
        // Configuration settings
        config: {
            ajaxUrl: vortexHURAIIData.ajaxUrl || '',
            nonce: vortexHURAIIData.nonce || '',
            userId: vortexHURAIIData.userId || 0,
            defaultModel: vortexHURAIIData.defaultModel || 'sd-v2-1',
            maxWidth: vortexHURAIIData.maxWidth || 1024,
            maxHeight: vortexHURAIIData.maxHeight || 1024,
            supportedFormats: vortexHURAIIData.supportedFormats || ['png', 'jpg', 'webp', 'gif', 'mp4', 'obj', 'glb'],
            enabledFeatures: vortexHURAIIData.enabledFeatures || {
                '2d': true,
                '3d': true,
                'video': true,
                'audio': false
            },
            learningEnabled: vortexHURAIIData.learningEnabled !== false,
            i18n: vortexHURAIIData.i18n || {}
        },
        
        // State management
        state: {
            isGenerating: false,
            currentFormat: '2d',
            currentModel: '',
            seedValue: Math.floor(Math.random() * 1000000),
            interactionHistory: [],
            uploadedImageSrc: null,
            generationHistory: [],
            selectedStylePreset: null,
            processingStart: null
        },
        
        /**
         * Initialize HURAII functionality
         */
        init: function() {
            // Set default model
            this.state.currentModel = this.config.defaultModel;
            
            // Register event listeners
            this.registerEventListeners();
            
            // Initialize UI components
            this.initUI();
            
            // Track initialization for AI learning
            this.trackAIInteraction('initialization', {
                browser: navigator.userAgent,
                screenSize: `${window.innerWidth}x${window.innerHeight}`,
                timestamp: new Date().toISOString()
            });
            
            console.log('VORTEX HURAII initialized');
        },
        
        /**
         * Register all event listeners
         */
        registerEventListeners: function() {
            const self = this;
            
            // Generate button
            $('#vortex-generate-btn').on('click', function(e) {
                e.preventDefault();
                self.handleGenerate();
            });
            
            // Format selection
            $('.vortex-format-selector').on('change', function() {
                self.handleFormatChange($(this).val());
            });
            
            // Model selection
            $('.vortex-model-selector').on('change', function() {
                self.handleModelChange($(this).val());
            });
            
            // Prompt input tracking for AI learning
            $('#vortex-prompt-input').on('input', _.debounce(function() {
                self.trackAIInteraction('prompt_input', {
                    prompt: $(this).val(),
                    timestamp: new Date().toISOString()
                });
            }, 1000));
            
            // Seed value input
            $('#vortex-seed-input').on('change', function() {
                self.state.seedValue = parseInt($(this).val(), 10) || Math.floor(Math.random() * 1000000);
                $(this).val(self.state.seedValue);
            });
            
            // Randomize seed button
            $('#vortex-randomize-seed').on('click', function(e) {
                e.preventDefault();
                self.state.seedValue = Math.floor(Math.random() * 1000000);
                $('#vortex-seed-input').val(self.state.seedValue);
                
                self.trackAIInteraction('seed_randomized', {
                    new_seed: self.state.seedValue,
                    timestamp: new Date().toISOString()
                });
            });
            
            // Image uploader for image-to-image
            $('#vortex-image-upload').on('change', function(e) {
                self.handleImageUpload(e);
            });
            
            // Style preset selection
            $('.vortex-style-preset').on('click', function() {
                self.handleStylePresetSelection($(this).data('style-id'));
                $('.vortex-style-preset').removeClass('active');
                $(this).addClass('active');
            });
            
            // Save generated artwork button
            $('#vortex-save-artwork').on('click', function(e) {
                e.preventDefault();
                self.handleSaveArtwork();
            });
            
            // Cancel generation button
            $('#vortex-cancel-generation').on('click', function(e) {
                e.preventDefault();
                self.handleCancelGeneration();
            });
            
            // Strength slider (for image-to-image)
            $('#vortex-strength-slider').on('input', function() {
                const strengthValue = $(this).val();
                $('#vortex-strength-value').text(strengthValue);
                
                self.trackAIInteraction('strength_adjusted', {
                    value: strengthValue,
                    timestamp: new Date().toISOString()
                });
            });
            
            // Generation history navigation
            $('#vortex-prev-generation').on('click', function(e) {
                e.preventDefault();
                self.navigateGenerationHistory(-1);
            });
            
            $('#vortex-next-generation').on('click', function(e) {
                e.preventDefault();
                self.navigateGenerationHistory(1);
            });
            
            // Window unload event - track session data
            $(window).on('beforeunload', function() {
                self.trackAIInteraction('session_end', {
                    session_duration: (new Date() - self.state.processingStart) / 1000,
                    interaction_count: self.state.interactionHistory.length,
                    generation_count: self.state.generationHistory.length,
                    timestamp: new Date().toISOString()
                });
            });
        },
        
        /**
         * Initialize UI components
         */
        initUI: function() {
            // Initialize the canvas element if it exists
            if ($('#vortex-canvas').length) {
                this.initCanvas();
            }
            
            // Show/hide format-specific controls
            this.updateFormatControls(this.state.currentFormat);
            
            // Populate style presets
            this.populateStylePresets();
            
            // Set the initial seed value
            $('#vortex-seed-input').val(this.state.seedValue);
            
            // Track session start timestamp
            this.state.processingStart = new Date();
            
            // Initialize tooltips
            if ($.fn.tooltip) {
                $('.vortex-tooltip').tooltip();
            }
        },
        
        /**
         * Initialize canvas for drawing interactions
         */
        initCanvas: function() {
            const canvas = document.getElementById('vortex-canvas');
            const ctx = canvas.getContext('2d');
            
            // Set initial canvas size
            canvas.width = this.config.maxWidth;
            canvas.height = this.config.maxHeight;
            
            // Clear canvas
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            // TODO: Implement additional canvas functionality if needed
        },
        
        /**
         * Handle format change
         * 
         * @param {string} format The selected format (2d, 3d, video, audio)
         */
        handleFormatChange: function(format) {
            // Validate format
            if (!format || !this.config.enabledFeatures[format]) {
                this.showError(this.config.i18n.formatNotSupported || 'This format is not supported.');
                return;
            }
            
            this.state.currentFormat = format;
            this.updateFormatControls(format);
            
            // Update available models based on format
            this.updateAvailableModels(format);
            
            // Track for AI learning
            this.trackAIInteraction('format_changed', {
                previous_format: this.state.currentFormat,
                new_format: format,
                timestamp: new Date().toISOString()
            });
        },
        
        /**
         * Update UI controls based on selected format
         * 
         * @param {string} format The selected format
         */
        updateFormatControls: function(format) {
            // Hide all format-specific controls first
            $('.vortex-format-controls').hide();
            
            // Show controls for the selected format
            $(`.vortex-format-controls[data-format="${format}"]`).show();
            
            // Update default dimensions based on format
            switch (format) {
                case '3d':
                    $('#vortex-width').val(512);
                    $('#vortex-height').val(512);
                    break;
                case 'video':
                    $('#vortex-width').val(512);
                    $('#vortex-height').val(512);
                    break;
                case 'audio':
                    // Audio doesn't use width/height
                    $('.vortex-dimension-controls').hide();
                    break;
                default: // 2d
                    $('#vortex-width').val(1024);
                    $('#vortex-height').val(1024);
                    $('.vortex-dimension-controls').show();
            }
        },
        
        /**
         * Update available models based on selected format
         * 
         * @param {string} format The selected format
         */
        updateAvailableModels: function(format) {
            // Clear current options
            const $modelSelector = $('.vortex-model-selector');
            $modelSelector.empty();
            
            // Get models for the selected format
            this.getModelsForFormat(format)
                .then(models => {
                    // Populate model selector
                    $.each(models, function(id, name) {
                        $modelSelector.append($('<option>', {
                            value: id,
                            text: name
                        }));
                    });
                    
                    // Set default model if available
                    if (models[this.config.defaultModel]) {
                        $modelSelector.val(this.config.defaultModel);
                        this.state.currentModel = this.config.defaultModel;
                    } else {
                        // Use first available model
                        this.state.currentModel = Object.keys(models)[0];
                        $modelSelector.val(this.state.currentModel);
                    }
                })
                .catch(error => {
                    this.showError(error.message);
                });
        },
        
        /**
         * Get models for the selected format via AJAX
         * 
         * @param {string} format The selected format
         * @returns {Promise} Promise resolving to model data
         */
        getModelsForFormat: function(format) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'vortex_get_huraii_models',
                        format: format,
                        nonce: this.config.nonce
                    },
                    success: response => {
                        if (response.success) {
                            resolve(response.data.models);
                        } else {
                            reject(new Error(response.data.message || this.config.i18n.serverError));
                        }
                    },
                    error: () => {
                        reject(new Error(this.config.i18n.connectionError || 'Connection error'));
                    }
                });
            });
        },
        
        /**
         * Handle model change
         * 
         * @param {string} modelId The selected model ID
         */
        handleModelChange: function(modelId) {
            if (!modelId) {
                return;
            }
            
            this.state.currentModel = modelId;
            
            // Track for AI learning
            this.trackAIInteraction('model_changed', {
                previous_model: this.state.currentModel,
                new_model: modelId,
                timestamp: new Date().toISOString()
            });
        },
        
        /**
         * Handle generate button click
         */
        handleGenerate: function() {
            // Prevent multiple simultaneous generations
            if (this.state.isGenerating) {
                this.showMessage(this.config.i18n.alreadyGenerating || 'Generation already in progress');
                return;
            }
            
            // Validate inputs
            const prompt = $('#vortex-prompt-input').val().trim();
            if (!prompt) {
                this.showError(this.config.i18n.emptyPrompt || 'Please enter a prompt');
                return;
            }
            
            // Get generation parameters
            const params = this.collectGenerationParameters();
            
            // Show loading state
            this.showGeneratingState();
            
            // Track generation start time for performance metrics
            const startTime = new Date();
            
            // Send generation request
            this.generateArtwork(params)
                .then(result => {
                    // Track generation duration
                    const duration = (new Date() - startTime) / 1000;
                    
                    // Process and display results
                    this.handleGenerationResult(result, duration);
                    
                    // Add to generation history
                    this.addToGenerationHistory(result);
                })
                .catch(error => {
                    this.showError(error.message);
                })
                .finally(() => {
                    this.hideGeneratingState();
                });
        },
        
        /**
         * Collect all generation parameters from the form
         * 
         * @returns {Object} Parameters for generation
         */
        collectGenerationParameters: function() {
            const params = {
                prompt: $('#vortex-prompt-input').val().trim(),
                negative_prompt: $('#vortex-negative-prompt-input').val().trim(),
                model: this.state.currentModel,
                format: this.state.currentFormat,
                seed: this.state.seedValue,
                steps: parseInt($('#vortex-steps').val(), 10) || 30,
                cfg_scale: parseFloat($('#vortex-cfg-scale').val()) || 7.5
            };
            
            // Add dimensions for formats that need them
            if (this.state.currentFormat !== 'audio') {
                params.width = parseInt($('#vortex-width').val(), 10) || this.config.maxWidth;
                params.height = parseInt($('#vortex-height').val(), 10) || this.config.maxHeight;
                
                // Ensure dimensions don't exceed maximums
                params.width = Math.min(params.width, this.config.maxWidth);
                params.height = Math.min(params.height, this.config.maxHeight);
            }
            
            // For image-to-image, add source image and strength
            if (this.state.uploadedImageSrc) {
                params.init_image = this.state.uploadedImageSrc;
                params.strength = parseFloat($('#vortex-strength-slider').val()) || 0.75;
            }
            
            // For video, add duration
            if (this.state.currentFormat === 'video') {
                params.duration = parseFloat($('#vortex-duration').val()) || 3.0;
                params.fps = parseInt($('#vortex-fps').val(), 10) || 24;
            }
            
            // For audio, add duration
            if (this.state.currentFormat === 'audio') {
                params.duration = parseFloat($('#vortex-audio-duration').val()) || 10.0;
            }
            
            // Include selected style preset if any
            if (this.state.selectedStylePreset) {
                params.style_preset = this.state.selectedStylePreset;
            }
            
            // Track parameters for AI learning
            this.trackAIInteraction('generation_requested', {
                parameters: params,
                timestamp: new Date().toISOString()
            });
            
            return params;
        },
        
        /**
         * Send generation request to the server
         * 
         * @param {Object} params Generation parameters
         * @returns {Promise} Promise resolving to generation result
         */
        generateArtwork: function(params) {
            this.state.isGenerating = true;
            
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'vortex_huraii_generate',
                        params: params,
                        nonce: this.config.nonce,
                        learning_context: {
                            interaction_history: this.state.interactionHistory.slice(-10), // Last 10 interactions
                            browser: navigator.userAgent,
                            screen_size: `${window.innerWidth}x${window.innerHeight}`,
                            session_start: this.state.processingStart.toISOString()
                        }
                    },
                    success: response => {
                        this.state.isGenerating = false;
                        
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data.message || this.config.i18n.generationError));
                            
                            // Track error for AI learning
                            this.trackAIInteraction('generation_error', {
                                error_message: response.data.message,
                                parameters: params,
                                timestamp: new Date().toISOString()
                            });
                        }
                    },
                    error: (xhr, status, error) => {
                        this.state.isGenerating = false;
                        
                        reject(new Error(this.config.i18n.connectionError || 'Connection error'));
                        
                        // Track error for AI learning
                        this.trackAIInteraction('connection_error', {
                            status_code: xhr.status,
                            error_type: status,
                            error_message: error,
                            timestamp: new Date().toISOString()
                        });
                    }
                });
            });
        },
        
        /**
         * Handle successful generation result
         * 
         * @param {Object} result Generation result from server
         * @param {number} duration Generation duration in seconds
         */
        handleGenerationResult: function(result, duration) {
            // Display the result
            if (this.state.currentFormat === '2d') {
                this.displayImage(result.url);
            } else if (this.state.currentFormat === '3d') {
                this.display3DModel(result.url, result.thumbnail_url);
            } else if (this.state.currentFormat === 'video') {
                this.displayVideo(result.url, result.thumbnail_url);
            } else if (this.state.currentFormat === 'audio') {
                this.displayAudio(result.url);
            }
            
            // Show seed value used
            $('#vortex-seed-used').text(result.seed || this.state.seedValue);
            
            // Show generation time
            $('#vortex-generation-time').text(duration.toFixed(2));
            
            // Enable save button
            $('#vortex-save-artwork').prop('disabled', false).show();
            
            // Update UI to show completion
            $('.vortex-generation-result').show();
            $('.vortex-generation-stats').show();
            
            // Track successful generation for AI learning
            this.trackAIInteraction('generation_completed', {
                result_id: result.id,
                format: this.state.currentFormat,
                seed: result.seed || this.state.seedValue,
                duration: duration,
                quality_score: result.quality_score,
                timestamp: new Date().toISOString()
            });
            
            // Get CLOE analysis if available
            if (result.cloe_analysis) {
                this.displayCloeAnalysis(result.cloe_analysis);
            }
            
            // Get BusinessStrategist insights if available
            if (result.business_insights) {
                this.displayBusinessInsights(result.business_insights);
            }
            
            // Show success message
            this.showMessage(this.config.i18n.generationSuccess || 'Artwork generated successfully');
        },
        
        /**
         * Display generated image
         * 
         * @param {string} url URL of the generated image
         */
        displayImage: function(url) {
            const $resultContainer = $('.vortex-result-container');
            $resultContainer.empty();
            
            const img = new Image();
            img.onload = function() {
                $resultContainer.append(img);
            };
            img.onerror = () => {
                this.showError(this.config.i18n.imageLoadError || 'Error loading image');
            };
            img.src = url;
            img.className = 'vortex-generated-image';
            
            // Store result for saving
            this.state.currentResult = {
                type: '2d',
                url: url
            };
        },
        
        /**
         * Display generated 3D model
         * 
         * @param {string} modelUrl URL of the 3D model
         * @param {string} thumbnailUrl URL of the thumbnail image
         */
        display3DModel: function(modelUrl, thumbnailUrl) {
            const $resultContainer = $('.vortex-result-container');
            $resultContainer.empty();
            
            if (thumbnailUrl) {
                // Show thumbnail first
                const img = new Image();
                img.src = thumbnailUrl;
                img.className = 'vortex-model-thumbnail';
                $resultContainer.append(img);
            }
            
            // Add viewer button
            const $viewerBtn = $('<button>', {
                class: 'button button-primary vortex-view-model-btn',
                text: this.config.i18n.view3DModel || 'View 3D Model'
            }).on('click', () => {
                this.open3DViewer(modelUrl);
            });
            
            $resultContainer.append($viewerBtn);
            
            // Store result for saving
            this.state.currentResult = {
                type: '3d',
                url: modelUrl,
                thumbnailUrl: thumbnailUrl
            };
        },
        
        /**
         * Display generated video
         * 
         * @param {string} videoUrl URL of the generated video
         * @param {string} thumbnailUrl URL of the thumbnail image
         */
        displayVideo: function(videoUrl, thumbnailUrl) {
            const $resultContainer = $('.vortex-result-container');
            $resultContainer.empty();
            
            // Create video element
            const $video = $('<video>', {
                controls: true,
                autoplay: false,
                loop: true,
                class: 'vortex-generated-video'
            });
            
            // Add source
            $('<source>', {
                src: videoUrl,
                type: 'video/mp4'
            }).appendTo($video);
            
            // Add poster if available
            if (thumbnailUrl) {
                $video.attr('poster', thumbnailUrl);
            }
            
            // Add to container
            $resultContainer.append($video);
            
            // Store result for saving
            this.state.currentResult = {
                type: 'video',
                url: videoUrl,
                thumbnailUrl: thumbnailUrl
            };
        },
        
        /**
         * Display generated audio
         * 
         * @param {string} audioUrl URL of the generated audio
         */
        displayAudio: function(audioUrl) {
            const $resultContainer = $('.vortex-result-container');
            $resultContainer.empty();
            
            // Create audio element
            const $audio = $('<audio>', {
                controls: true,
                class: 'vortex-generated-audio'
            });
            
            // Add source
            $('<source>', {
                src: audioUrl,
                type: 'audio/mp3'
            }).appendTo($audio);
            
            // Add to container
            $resultContainer.append($audio);
            
            // Store result for saving
            this.state.currentResult = {
                type: 'audio',
                url: audioUrl
            };
        },
        
        /**
         * Open 3D model viewer in a modal
         * 
         * @param {string} modelUrl URL of the 3D model
         */
        open3DViewer: function(modelUrl) {
            // Implement 3D viewer modal
            if (!window.vortex3DViewer) {
                // If 3D viewer not loaded, load it now
                this.load3DViewer().then(() => {
                    window.vortex3DViewer.open(modelUrl);
                });
            } else {
                window.vortex3DViewer.open(modelUrl);
            }
        },
        
        /**
         * Load 3D viewer script
         * 
         * @returns {Promise} Promise resolving when viewer is loaded
         */
        load3DViewer: function() {
            return new Promise((resolve, reject) => {
                if (window.vortex3DViewer) {
                    resolve();
                    return;
                }
                
                $.getScript(vortexHURAIIData.viewerUrl)
                    .done(function() {
                        resolve();
                    })
                    .fail(function() {
                        reject(new Error('Failed to load 3D viewer'));
                    });
            });
        },
        
        /**
         * Handle image upload for image-to-image
         * 
         * @param {Event} e Change event
         */
        handleImageUpload: function(e) {
            const file = e.target.files[0];
            if (!file) {
                return;
            }
            
            // Validate file type
            const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                this.showError(this.config.i18n.invalidImageType || 'Invalid image type. Please upload a JPEG, PNG, WebP, or GIF image.');
                return;
            }
            
            // Validate file size (max 10MB)
            const maxSize = 10 * 1024 * 1024; // 10MB
            if (file.size > maxSize) {
                this.showError(this.config.i18n.imageTooLarge || 'Image too large. Maximum size is 10MB.');
                return;
            }
            
            // Read file and display preview
            const reader = new FileReader();
            reader.onload = (event) => {
                this.state.uploadedImageSrc = event.target.result;
                
                // Display preview
                $('#vortex-image-preview').attr('src', event.target.result).show();
                $('.vortex-image-preview-container').show();
                
                // Enable strength slider
                $('#vortex-strength-container').show();
                
                // Track for AI learning
                this.trackAIInteraction('image_uploaded', {
                    file_type: file.type,
                    file_size: file.size,
                    timestamp: new Date().toISOString()
                });
            };
            
            reader.onerror = () => {
                this.showError(this.config.i18n.imageReadError || 'Error reading image file.');
            };
            
            reader.readAsDataURL(file);
        },
        
        /**
         * Handle style preset selection
         * 
         * @param {string} styleId The selected style preset ID
         */
        handleStylePresetSelection: function(styleId) {
            this.state.selectedStylePreset = styleId;
            
            // Get style details
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_get_style_details',
                    style_id: styleId,
                    nonce: this.config.nonce
                },
                success: response => {
                    if (response.success) {
                        // Update prompt with style suggestion if available
                        if (response.data.prompt_suggestion) {
                            const currentPrompt = $('#vortex-prompt-input').val();
                            if (currentPrompt) {
                                $('#vortex-prompt-input').val(currentPrompt + ', ' + response.data.prompt_suggestion);
                            } else {
                                $('#vortex-prompt-input').val(response.data.prompt_suggestion);
                            }
                        }
                        
                        // Update negative prompt if available
                        if (response.data.negative_prompt) {
                            $('#vortex-negative-prompt-input').val(response.data.negative_prompt);
                        }
                        
                        // Display style preview if available
                        if (response.data.preview_url) {
                            $('#vortex-style-preview').attr('src', response.data.preview_url).show();
                        }
                        
                        // Track for AI learning
                        this.trackAIInteraction('style_selected', {
                            style_id: styleId,
                            style_name: response.data.name,
                            timestamp: new Date().toISOString()
                        });
                    }
                }
            });
        },
        
        /**
         * Populate style presets
         */
        populateStylePresets: function() {
            const $presetContainer = $('.vortex-style-presets-container');
            if (!$presetContainer.length) {
                return;
            }
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_get_style_presets',
                    format: this.state.currentFormat,
                    nonce: this.config.nonce
                },
                success: response => {
                    if (response.success && response.data.presets) {
                        $presetContainer.empty();
                        
                        // Create style preset items
                        $.each(response.data.presets, function(i, preset) {
                            const $presetItem = $('<div>', {
                                class: 'vortex-style-preset',
                                'data-style-id': preset.id
                            });
                            
                            if (preset.thumbnail_url) {
                                $('<img>', {
                                    src: preset.thumbnail_url,
                                    alt: preset.name
                                }).appendTo($presetItem);
                            }
                            
                            $('<span>', {
                                class: 'style-name',
                                text: preset.name
                            }).appendTo($presetItem);
                            
                            $presetContainer.append($presetItem);
                        });
                        
                        // Reattach event listeners
                        $('.vortex-style-preset').on('click', function() {
                            VortexHURAII.handleStylePresetSelection($(this).data('style-id'));
                            $('.vortex-style-preset').removeClass('active');
                            $(this).addClass('active');
                        });
                    }
                }
            });
        },
        
        /**
         * Handle save artwork button click
         */
        handleSaveArtwork: function() {
            if (!this.state.currentResult) {
                this.showError(this.config.i18n.noArtworkToSave || 'No artwork to save');
                return;
            }
            
            // Get metadata for saving
            const metadata = {
                title: $('#vortex-prompt-input').val().split(',')[0].trim().substring(0, 100),
                prompt: $('#vortex-prompt-input').val(),
                negative_prompt: $('#vortex-negative-prompt-input').val(),
                model: this.state.currentModel,
                format: this.state.currentFormat,
                seed: this.state.seedValue,
                style_preset: this.state.selectedStylePreset
            };
            
            // Show saving state
            $('#vortex-save-artwork').prop('disabled', true).text(this.config.i18n.saving || 'Saving...');
            
            // Send save request
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_save_huraii_artwork',
                    result: this.state.currentResult,
                    metadata: metadata,
                    nonce: this.config.nonce
                },
                success: response => {
                    if (response.success) {
                        // Show success message
                        this.showMessage(this.config.i18n.artworkSaved || 'Artwork saved successfully');
                        
                        // Offer to view/edit the saved artwork
                        if (response.data.edit_url) {
                            setTimeout(() => {
                                if (confirm(this.config.i18n.viewSavedArtwork || 'View your saved artwork?')) {
                                    window.location.href = response.data.edit_url;
                                }
                            }, 500);
                        }
                        
                        // Track for AI learning
                        this.trackAIInteraction('artwork_saved', {
                            artwork_id: response.data.artwork_id,
                            format: this.state.currentFormat,
                            timestamp: new Date().toISOString()
                        });
                    } else {
                        this.showError(response.data.message || this.config.i18n.saveError);
                    }
                },
                error: () => {
                    this.showError(this.config.i18n.connectionError || 'Connection error');
                },
                complete: () => {
                    // Reset button state
                    $('#vortex-save-artwork').prop('disabled', false).text(this.config.i18n.saveArtwork || 'Save Artwork');
                }
            });
        },
        
        /**
         * Handle cancel generation button click
         */
        handleCancelGeneration: function() {
            if (!this.state.isGenerating) {
                return;
            }
            
            // Send cancel request
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_cancel_huraii_generation',
                    nonce: this.config.nonce
                },
                success: response => {
                    if (response.success) {
                        this.state.isGenerating = false;
                        this.hideGeneratingState();
                        this.showMessage(this.config.i18n.generationCancelled || 'Generation cancelled');
                        
                        // Track for AI learning
                        this.trackAIInteraction('generation_cancelled', {
                            timestamp: new Date().toISOString()
                        });
                    }
                }
            });
        },
        
        /**
         * Add result to generation history
         * 
         * @param {Object} result Generation result
         */
        addToGenerationHistory: function(result) {
            this.state.generationHistory.push({
                result: result,
                prompt: $('#vortex-prompt-input').val(),
                seed: result.seed || this.state.seedValue,
                timestamp: new Date().toISOString()
            });
            
            // Update history navigation buttons
            this.updateHistoryNavigation();
        },
        
        /**
         * Navigate through generation history
         * 
         * @param {number} direction Direction (-1 for prev, 1 for next)
         */
        navigateGenerationHistory: function(direction) {
            // TODO: Implement history navigation
        },
        
        /**
         * Update history navigation buttons state
         */
        updateHistoryNavigation: function() {
            // TODO: Update history navigation buttons
        },
        
        /**
         * Display CLOE analysis
         * 
         * @param {Object} analysis CLOE analysis data
         */
        displayCloeAnalysis: function(analysis) {
            const $analysisContainer = $('.vortex-cloe-analysis');
            if (!$analysisContainer.length) {
                return;
            }
            
            $analysisContainer.empty().show();
            
            // Add analysis header
            $('<h4>', {
                text: this.config.i18n.cloeAnalysis || 'CLOE Analysis'
            }).appendTo($analysisContainer);
            
            // Add analysis content
            if (analysis.message) {
                $('<p>', {
                    class: 'cloe-message',
                    html: analysis.message
                }).appendTo($analysisContainer);
            }
            
            // Add style tags if available
            if (analysis.style_tags && analysis.style_tags.length) {
                const $tagContainer = $('<div>', {
                    class: 'cloe-tags'
                }).appendTo($analysisContainer);
                
                $.each(analysis.style_tags, function(i, tag) {
                    $('<span>', {
                        class: 'cloe-tag',
                        text: tag
                    }).appendTo($tagContainer);
                });
            }
            
            // Add quality score if available
            if (analysis.quality_score) {
                $('<div>', {
                    class: 'cloe-quality-score',
                    html: `<span>${this.config.i18n.qualityScore || 'Quality Score'}:</span> ${analysis.quality_score.toFixed(1)}/10`
                }).appendTo($analysisContainer);
            }
        },
        
        /**
         * Display BusinessStrategist insights
         * 
         * @param {Object} insights BusinessStrategist insights data
         */
        displayBusinessInsights: function(insights) {
            const $insightsContainer = $('.vortex-business-insights');
            if (!$insightsContainer.length) {
                return;
            }
            
            $insightsContainer.empty().show();
            
            // Add insights header
            $('<h4>', {
                text: this.config.i18n.businessInsights || 'Market Insights'
            }).appendTo($insightsContainer);
            
            // Add insights content
            if (insights.message) {
                $('<p>', {
                    class: 'business-message',
                    html: insights.message
                }).appendTo($insightsContainer);
            }
            
            // Add market potential if available
            if (insights.market_potential) {
                $('<div>', {
                    class: 'business-market-potential',
                    html: `<span>${this.config.i18n.marketPotential || 'Market Potential'}:</span> ${insights.market_potential}`
                }).appendTo($insightsContainer);
            }
            
            // Add recommended price if available
            if (insights.recommended_price) {
                $('<div>', {
                    class: 'business-recommended-price',
                    html: `<span>${this.config.i18n.recommendedPrice || 'Recommended Price'}:</span> ${insights.recommended_price} TOLA`
                }).appendTo($insightsContainer);
            }
            
            // Add target audience if available
            if (insights.target_audience && insights.target_audience.length) {
                const $audienceContainer = $('<div>', {
                    class: 'business-target-audience'
                }).appendTo($insightsContainer);
                
                    $('<span>', {
                    text: this.config.i18n.targetAudience || 'Target Audience:'
                }).appendTo($audienceContainer);
                
                const $audienceList = $('<ul>').appendTo($audienceContainer);
                
                $.each(insights.target_audience, function(i, audience) {
                    $('<li>', {
                        text: audience
                    }).appendTo($audienceList);
                });
            }
        },
        
        /**
         * Show generating state in UI
         */
        showGeneratingState: function() {
            // Show loading indicator
            $('.vortex-loading-indicator').show();
            
            // Disable generate button
            $('#vortex-generate-btn').prop('disabled', true).text(this.config.i18n.generating || 'Generating...');
            
            // Show cancel button
            $('#vortex-cancel-generation').show();
            
            // Hide previous results
            $('.vortex-generation-result').hide();
            $('.vortex-generation-stats').hide();
            
            // Track generation start for AI learning
            this.trackAIInteraction('generation_started', {
                format: this.state.currentFormat,
                model: this.state.currentModel,
                timestamp: new Date().toISOString()
            });
        },
        
        /**
         * Hide generating state in UI
         */
        hideGeneratingState: function() {
            // Hide loading indicator
            $('.vortex-loading-indicator').hide();
            
            // Reset generate button
            $('#vortex-generate-btn').prop('disabled', false).text(this.config.i18n.generate || 'Generate');
            
            // Hide cancel button
            $('#vortex-cancel-generation').hide();
        },
        
        /**
         * Show a message to the user
         * 
         * @param {string} message The message to show
         */
        showMessage: function(message) {
            const $messageContainer = $('.vortex-messages');
            
            if (!$messageContainer.length) {
                return;
            }
            
            // Create message element
            const $message = $('<div>', {
                class: 'vortex-message',
                text: message
            });
            
            // Add to container
            $messageContainer.empty().append($message).show();
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                    if ($messageContainer.children().length === 0) {
                        $messageContainer.hide();
                    }
                });
            }, 5000);
        },
        
        /**
         * Show an error message to the user
         * 
         * @param {string} message The error message to show
         */
        showError: function(message) {
            const $messageContainer = $('.vortex-messages');
            
            if (!$messageContainer.length) {
                return;
            }
            
            // Create error message element
            const $error = $('<div>', {
                class: 'vortex-message vortex-error',
                text: message
            });
            
            // Add to container
            $messageContainer.empty().append($error).show();
            
            // Auto-hide after 8 seconds (longer for errors)
            setTimeout(function() {
                $error.fadeOut(function() {
                    $(this).remove();
                    if ($messageContainer.children().length === 0) {
                        $messageContainer.hide();
                    }
                });
            }, 8000);
            
            // Track error for AI learning
            this.trackAIInteraction('ui_error_shown', {
                error_message: message,
                timestamp: new Date().toISOString()
            });
        },
        
        /**
         * Track AI interaction for learning
         * 
         * @param {string} action The action performed
         * @param {Object} data Additional data about the action
         */
        trackAIInteraction: function(action, data) {
            // Skip if learning is disabled
            if (!this.config.learningEnabled) {
                return;
            }
            
            // Add to interaction history
            this.state.interactionHistory.push({
                action: action,
                data: data,
                timestamp: new Date().toISOString()
            });
            
            // Send to server for AI learning if action is significant
            // or if we've accumulated enough interactions
            const significantActions = [
                'generation_requested',
                'generation_completed',
                'generation_error',
                'artwork_saved',
                'style_selected'
            ];
            
            if (significantActions.includes(action) || this.state.interactionHistory.length >= 20) {
                // Create a batch of interactions to send
                const batch = this.state.interactionHistory.slice(-20);
                
                // Clear the history to avoid duplicate sends
                // but keep the last 5 for context
                if (this.state.interactionHistory.length > 5) {
                    this.state.interactionHistory = this.state.interactionHistory.slice(-5);
                }
                
                // Send asynchronously to avoid blocking UI
                setTimeout(() => {
                    this.sendAILearningData(batch);
                }, 100);
            }
        },
        
        /**
         * Send AI learning data to the server
         * 
         * @param {Array} interactions Array of interaction data
         */
        sendAILearningData: function(interactions) {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_huraii_learn',
                    interactions: interactions,
                    user_id: this.config.userId,
                    session_id: this.generateSessionId(),
                    nonce: this.config.nonce
                },
                success: response => {
                    if (response.success) {
                        // Handle any learning adjustments returned from server
                        if (response.data.adjustments) {
                            this.applyAIAdjustments(response.data.adjustments);
                        }
                    }
                },
                // Use minimal error handling here to avoid impacting user experience
                error: () => {
                    console.warn('AI learning data could not be sent');
                }
            });
        },
        
        /**
         * Apply AI adjustments received from server
         * 
         * @param {Object} adjustments Adjustments to apply
         */
        applyAIAdjustments: function(adjustments) {
            // Apply any UI or behavior adjustments recommended by AI
            if (adjustments.prompt_suggestions && adjustments.prompt_suggestions.length) {
                this.updatePromptSuggestions(adjustments.prompt_suggestions);
            }
            
            if (adjustments.recommended_model) {
                this.suggestModelChange(adjustments.recommended_model);
            }
            
            if (adjustments.ui_adjustments) {
                this.applyUIAdjustments(adjustments.ui_adjustments);
            }
        },
        
        /**
         * Update prompt suggestions based on AI learning
         * 
         * @param {Array} suggestions Array of prompt suggestions
         */
        updatePromptSuggestions: function(suggestions) {
            const $suggestionsContainer = $('.vortex-prompt-suggestions');
            if (!$suggestionsContainer.length) {
                return;
            }
            
            $suggestionsContainer.empty();
            
            if (suggestions.length) {
                $('<h4>', {
                    text: this.config.i18n.promptSuggestions || 'Try These Prompts:'
                }).appendTo($suggestionsContainer);
                
                const $suggestionsList = $('<ul>').appendTo($suggestionsContainer);
                
                $.each(suggestions, function(i, suggestion) {
                    const $item = $('<li>').appendTo($suggestionsList);
                    
                    $('<a>', {
                        href: '#',
                        text: suggestion,
                        click: function(e) {
                            e.preventDefault();
                            $('#vortex-prompt-input').val(suggestion);
                            
                            // Track suggestion usage for AI learning
                            VortexHURAII.trackAIInteraction('suggestion_used', {
                                suggestion: suggestion,
                                timestamp: new Date().toISOString()
                            });
                        }
                    }).appendTo($item);
                });
                
                $suggestionsContainer.show();
            }
        },
        
        /**
         * Suggest a model change based on AI learning
         * 
         * @param {Object} modelInfo Information about the recommended model
         */
        suggestModelChange: function(modelInfo) {
            const $modelSuggestion = $('.vortex-model-suggestion');
            if (!$modelSuggestion.length) {
                return;
            }
            
            $modelSuggestion.empty();
            
            $('<p>', {
                html: `<strong>${this.config.i18n.recommendedModel || 'Recommended Model'}:</strong> ${modelInfo.name}`
            }).appendTo($modelSuggestion);
            
            if (modelInfo.reason) {
                $('<p>', {
                    class: 'suggestion-reason',
                    text: modelInfo.reason
                }).appendTo($modelSuggestion);
            }
            
            $('<button>', {
                class: 'button vortex-use-suggested-model',
                text: this.config.i18n.useThisModel || 'Use This Model',
                click: function() {
                    $('.vortex-model-selector').val(modelInfo.id).trigger('change');
                    $modelSuggestion.fadeOut();
                    
                    // Track for AI learning
                    VortexHURAII.trackAIInteraction('suggested_model_used', {
                        model_id: modelInfo.id,
                        timestamp: new Date().toISOString()
                    });
                }
            }).appendTo($modelSuggestion);
            
            $modelSuggestion.fadeIn();
        },
        
        /**
         * Apply UI adjustments based on AI learning
         * 
         * @param {Object} adjustments UI adjustments to apply
         */
        applyUIAdjustments: function(adjustments) {
            // Apply any UI adjustments returned from the server
            if (adjustments.highlight_elements && adjustments.highlight_elements.length) {
                $.each(adjustments.highlight_elements, function(i, selector) {
                    $(selector).addClass('vortex-ai-highlight').delay(5000).queue(function() {
                        $(this).removeClass('vortex-ai-highlight').dequeue();
                    });
                });
            }
            
            if (adjustments.show_elements && adjustments.show_elements.length) {
                $.each(adjustments.show_elements, function(i, selector) {
                    $(selector).fadeIn();
                });
            }
            
            if (adjustments.hide_elements && adjustments.hide_elements.length) {
                $.each(adjustments.hide_elements, function(i, selector) {
                    $(selector).fadeOut();
                });
            }
        },
        
        /**
         * Generate a unique session ID
         * 
         * @returns {string} Session ID
         */
        generateSessionId: function() {
            // Use stored session ID if available
            if (this._sessionId) {
                return this._sessionId;
            }
            
            // Generate a new session ID
            const timestamp = new Date().getTime();
            const randomPart = Math.floor(Math.random() * 1000000);
            this._sessionId = `huraii_${timestamp}_${randomPart}`;
            
            return this._sessionId;
        },
        
        /**
         * Debounce function to limit function calls
         * 
         * @param {Function} func Function to debounce
         * @param {number} wait Wait time in milliseconds
         * @returns {Function} Debounced function
         */
        debounce: function(func, wait) {
            let timeout;
            return function() {
                const context = this;
                const args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        // Initialize only if the necessary elements exist
        if ($('.vortex-huraii-container').length) {
            VortexHURAII.init();
        }
    });
    
    // Expose to global scope for external access
    window.VortexHURAII = VortexHURAII;
    
})(jQuery); 