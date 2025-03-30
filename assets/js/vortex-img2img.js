/**
 * VORTEX Image-to-Image JavaScript
 * 
 * Handles client-side interactions for the image-to-image transformation functionality,
 * ensuring continuous AI agent learning and robust error handling.
 * 
 * @package VORTEX_AI_Marketplace
 */

(function($) {
    'use strict';
    
    // Main Img2Img object
    const VortexImg2Img = {
        // Configuration settings
        config: {
            ajaxUrl: vortexImg2ImgData.ajaxUrl || '',
            nonce: vortexImg2ImgData.nonce || '',
            userId: vortexImg2ImgData.userId || 0,
            maxUploadSize: vortexImg2ImgData.maxUploadSize || 10,
            defaultStrength: vortexImg2ImgData.defaultStrength || 0.75,
            maxWidth: vortexImg2ImgData.maxWidth || 1024,
            maxHeight: vortexImg2ImgData.maxHeight || 1024,
            allowedInputFormats: vortexImg2ImgData.allowedInputFormats || ['jpg', 'jpeg', 'png', 'webp'],
            outputFormat: vortexImg2ImgData.outputFormat || 'png',
            enableStyleTransfer: vortexImg2ImgData.enableStyleTransfer || true,
            enableAdvancedTransforms: vortexImg2ImgData.enableAdvancedTransforms || true,
            enableFaceEnhancement: vortexImg2ImgData.enableFaceEnhancement || true,
            enableUpscaling: vortexImg2ImgData.enableUpscaling || true,
            defaultGuidanceScale: vortexImg2ImgData.defaultGuidanceScale || 7.5,
            maxProcessingSteps: vortexImg2ImgData.maxProcessingSteps || 50,
            learningEnabled: vortexImg2ImgData.learningEnabled !== false,
            i18n: vortexImg2ImgData.i18n || {}
        },
        
        // State management
        state: {
            sourceImage: null,
            sourceImageFile: null,
            referenceImage: null,
            referenceImageFile: null,
            isTransforming: false,
            currentMode: 'standard', // standard, inpaint, outpaint, reference
            transformResult: null,
            selectionStart: { x: 0, y: 0 },
            selectionEnd: { x: 0, y: 0 },
            interactionHistory: [],
            processingStart: null,
            transformationHistory: [],
            currentHistoryIndex: -1,
            maskData: null,
            brushSize: 20,
            brushHardness: 0.5,
            isMasking: false,
            preserveColor: false,
            preserveComposition: false,
            suggestedStyles: [],
            enhanceFaces: false,
            upscaleFactor: 1
        },
        
        /**
         * Initialize Img2Img functionality
         */
        init: function() {
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
            
            console.log('VORTEX Image-to-Image initialized');
        },
        
        /**
         * Register all event listeners
         */
        registerEventListeners: function() {
            const self = this;
            
            // Source image upload
            $('#vortex-source-image-upload').on('change', function(e) {
                self.handleSourceImageUpload(e);
            });
            
            // Reference image upload (for reference mode)
            $('#vortex-reference-image-upload').on('change', function(e) {
                self.handleReferenceImageUpload(e);
            });
            
            // Transformation mode buttons
            $('.vortex-transform-mode-btn').on('click', function(e) {
                e.preventDefault();
                self.setTransformMode($(this).data('mode'));
            });
            
            // Transform button
            $('#vortex-transform-btn').on('click', function(e) {
                e.preventDefault();
                self.handleTransform();
            });
            
            // Strength slider
            $('#vortex-strength-slider').on('input', function() {
                const strengthValue = $(this).val();
                $('#vortex-strength-value').text(strengthValue);
                
                self.trackAIInteraction('strength_adjusted', {
                    value: strengthValue,
                    timestamp: new Date().toISOString()
                });
            });
            
            // Guidance scale slider
            $('#vortex-guidance-scale-slider').on('input', function() {
                const guidanceValue = $(this).val();
                $('#vortex-guidance-scale-value').text(guidanceValue);
                
                self.trackAIInteraction('guidance_adjusted', {
                    value: guidanceValue,
                    timestamp: new Date().toISOString()
                });
            });
            
            // Steps slider
            $('#vortex-steps-slider').on('input', function() {
                const stepsValue = $(this).val();
                $('#vortex-steps-value').text(stepsValue);
                
                self.trackAIInteraction('steps_adjusted', {
                    value: stepsValue,
                    timestamp: new Date().toISOString()
                });
            });
            
            // Style description input tracking for AI learning
            $('#vortex-style-description').on('input', _.debounce(function() {
                self.trackAIInteraction('style_input', {
                    text: $(this).val(),
                    timestamp: new Date().toISOString()
                });
                
                // Request style suggestions if enough text
                if ($(this).val().length > 5) {
                    self.requestStyleSuggestions($(this).val());
                }
            }, 1000));
            
            // Toggle for preserve original color
            $('#vortex-preserve-color').on('change', function() {
                self.state.preserveColor = $(this).is(':checked');
                
                self.trackAIInteraction('preserve_color_toggled', {
                    enabled: self.state.preserveColor,
                    timestamp: new Date().toISOString()
                });
            });
            
            // Toggle for preserve composition
            $('#vortex-preserve-composition').on('change', function() {
                self.state.preserveComposition = $(this).is(':checked');
                
                self.trackAIInteraction('preserve_composition_toggled', {
                    enabled: self.state.preserveComposition,
                    timestamp: new Date().toISOString()
                });
            });
            
            // Toggle for face enhancement
            $('#vortex-enhance-faces').on('change', function() {
                self.state.enhanceFaces = $(this).is(':checked');
                
                self.trackAIInteraction('face_enhancement_toggled', {
                    enabled: self.state.enhanceFaces,
                    timestamp: new Date().toISOString()
                });
            });
            
            // Upscale factor select
            $('#vortex-upscale-factor').on('change', function() {
                self.state.upscaleFactor = parseInt($(this).val(), 10);
                
                self.trackAIInteraction('upscale_factor_changed', {
                    factor: self.state.upscaleFactor,
                    timestamp: new Date().toISOString()
                });
            });
            
            // Canvas for inpainting/outpainting
            $('#vortex-canvas').on('mousedown', function(e) {
                if (self.state.currentMode === 'inpaint' || self.state.currentMode === 'outpaint') {
                    self.startMasking(e);
                }
            });
            
            $('#vortex-canvas').on('mousemove', function(e) {
                if (self.state.isMasking) {
                    self.continueMasking(e);
                }
            });
            
            $(document).on('mouseup', function() {
                if (self.state.isMasking) {
                    self.endMasking();
                }
            });
            
            // Brush size slider
            $('#vortex-brush-size-slider').on('input', function() {
                self.state.brushSize = parseInt($(this).val(), 10);
                $('#vortex-brush-size-value').text(self.state.brushSize);
            });
            
            // Brush hardness slider
            $('#vortex-brush-hardness-slider').on('input', function() {
                self.state.brushHardness = parseFloat($(this).val());
                $('#vortex-brush-hardness-value').text(self.state.brushHardness.toFixed(2));
            });
            
            // Clear mask button
            $('#vortex-clear-mask').on('click', function(e) {
                e.preventDefault();
                self.clearMask();
            });
            
            // Save result button
            $('#vortex-save-result').on('click', function(e) {
                e.preventDefault();
                self.handleSaveResult();
            });
            
            // Cancel transformation button
            $('#vortex-cancel-transform').on('click', function(e) {
                e.preventDefault();
                self.handleCancelTransformation();
            });
            
            // Suggested style click
            $(document).on('click', '.vortex-suggested-style', function() {
                const styleText = $(this).text();
                const currentText = $('#vortex-style-description').val();
                
                // Append the style to existing text or replace if empty
                if (currentText) {
                    $('#vortex-style-description').val(currentText + ', ' + styleText);
                } else {
                    $('#vortex-style-description').val(styleText);
                }
                
                self.trackAIInteraction('suggested_style_used', {
                    style: styleText,
                    timestamp: new Date().toISOString()
                });
            });
            
            // History navigation
            $('#vortex-prev-transformation').on('click', function(e) {
                e.preventDefault();
                self.navigateHistory(-1);
            });
            
            $('#vortex-next-transformation').on('click', function(e) {
                e.preventDefault();
                self.navigateHistory(1);
            });
            
            // Window unload event - track session data
            $(window).on('beforeunload', function() {
                self.trackAIInteraction('session_end', {
                    session_duration: (new Date() - self.state.processingStart) / 1000,
                    interaction_count: self.state.interactionHistory.length,
                    transformation_count: self.state.transformationHistory.length,
                    timestamp: new Date().toISOString()
                });
            });
        },
        
        /**
         * Initialize UI components
         */
        initUI: function() {
            // Initialize canvas for inpainting/outpainting
            this.initCanvas();
            
            // Set initial values from configuration
            $('#vortex-strength-slider').val(this.config.defaultStrength);
            $('#vortex-strength-value').text(this.config.defaultStrength);
            
            $('#vortex-guidance-scale-slider').val(this.config.defaultGuidanceScale);
            $('#vortex-guidance-scale-value').text(this.config.defaultGuidanceScale);
            
            $('#vortex-steps-slider').val(this.config.maxProcessingSteps / 2);
            $('#vortex-steps-value').text(this.config.maxProcessingSteps / 2);
            
            // Set initial transformation mode
            this.setTransformMode('standard');
            
            // Track session start timestamp
            this.state.processingStart = new Date();
            
            // Disable transformation button until image is uploaded
            $('#vortex-transform-btn').prop('disabled', true);
            
            // Initialize tooltips
            if ($.fn.tooltip) {
                $('.vortex-tooltip').tooltip();
            }
        },
        
        /**
         * Initialize canvas for inpainting/outpainting
         */
        initCanvas: function() {
            const canvas = document.getElementById('vortex-canvas');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            
            // Set initial canvas size
            canvas.width = this.config.maxWidth;
            canvas.height = this.config.maxHeight;
            
            // Clear canvas
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            // Create mask layer canvas
            this.maskCanvas = document.createElement('canvas');
            this.maskCanvas.width = canvas.width;
            this.maskCanvas.height = canvas.height;
            this.maskCtx = this.maskCanvas.getContext('2d');
            
            // Clear mask canvas
            this.maskCtx.clearRect(0, 0, this.maskCanvas.width, this.maskCanvas.height);
        },
        
        /**
         * Handle source image upload
         * 
         * @param {Event} e Change event
         */
        handleSourceImageUpload: function(e) {
            const file = e.target.files[0];
            if (!file) {
                return;
            }
            
            // Validate file type
            const fileExt = file.name.split('.').pop().toLowerCase();
            if (!this.config.allowedInputFormats.includes(fileExt)) {
                this.showError(this.config.i18n.invalidImageType || 'Invalid image type. Please upload an allowed image format.');
                return;
            }
            
            // Validate file size
            const maxSizeMB = this.config.maxUploadSize;
            const maxSizeBytes = maxSizeMB * 1024 * 1024;
            if (file.size > maxSizeBytes) {
                this.showError(this.config.i18n.imageTooLarge || `Image too large. Maximum size is ${maxSizeMB}MB.`);
                return;
            }
            
            // Store file reference
            this.state.sourceImageFile = file;
            
            // Read file and display preview
            const reader = new FileReader();
            reader.onload = (event) => {
                const img = new Image();
                img.onload = () => {
                    // Store source image
                    this.state.sourceImage = img;
                    
                    // Display in preview area
                    $('#vortex-source-preview').attr('src', event.target.result).show();
                    $('.vortex-source-preview-container').show();
                    
                    // Enable transform button
                    $('#vortex-transform-btn').prop('disabled', false);
                    
                    // If in inpaint or outpaint mode, draw the image on canvas
                    if (this.state.currentMode === 'inpaint' || this.state.currentMode === 'outpaint') {
                        this.drawSourceOnCanvas();
                    }
                    
                    // Request style suggestions from CLOE
                    this.requestImageAnalysis(img);
                    
                    // Track for AI learning
                    this.trackAIInteraction('source_image_uploaded', {
                        file_type: file.type,
                        file_size: file.size,
                        dimensions: `${img.width}x${img.height}`,
                        timestamp: new Date().toISOString()
                    });
                };
                
                img.onerror = () => {
                    this.showError(this.config.i18n.imageLoadError || 'Error loading image');
                };
                
                img.src = event.target.result;
            };
            
            reader.onerror = () => {
                this.showError(this.config.i18n.imageReadError || 'Error reading image file');
            };
            
            reader.readAsDataURL(file);
        },
        
        /**
         * Handle reference image upload (for reference mode)
         * 
         * @param {Event} e Change event
         */
        handleReferenceImageUpload: function(e) {
            const file = e.target.files[0];
            if (!file) {
                return;
            }
            
            // Validate file type
            const fileExt = file.name.split('.').pop().toLowerCase();
            if (!this.config.allowedInputFormats.includes(fileExt)) {
                this.showError(this.config.i18n.invalidImageType || 'Invalid image type for reference.');
                return;
            }
            
            // Validate file size
            const maxSizeMB = this.config.maxUploadSize;
            const maxSizeBytes = maxSizeMB * 1024 * 1024;
            if (file.size > maxSizeBytes) {
                this.showError(this.config.i18n.imageTooLarge || `Reference image too large. Maximum size is ${maxSizeMB}MB.`);
                return;
            }
            
            // Store file reference
            this.state.referenceImageFile = file;
            
            // Read file and display preview
            const reader = new FileReader();
            reader.onload = (event) => {
                const img = new Image();
                img.onload = () => {
                    // Store reference image
                    this.state.referenceImage = img;
                    
                    // Display in preview area
                    $('#vortex-reference-preview').attr('src', event.target.result).show();
                    $('.vortex-reference-preview-container').show();
                    
                    // Track for AI learning
                    this.trackAIInteraction('reference_image_uploaded', {
                        file_type: file.type,
                        file_size: file.size,
                        dimensions: `${img.width}x${img.height}`,
                        timestamp: new Date().toISOString()
                    });
                };
                
                img.onerror = () => {
                    this.showError(this.config.i18n.imageLoadError || 'Error loading reference image');
                };
                
                img.src = event.target.result;
            };
            
            reader.onerror = () => {
                this.showError(this.config.i18n.imageReadError || 'Error reading reference image file');
            };
            
            reader.readAsDataURL(file);
        },
        
        /**
         * Set transformation mode
         * 
         * @param {string} mode The mode to set (standard, inpaint, outpaint, reference)
         */
        setTransformMode: function(mode) {
            if (!['standard', 'inpaint', 'outpaint', 'reference'].includes(mode)) {
                return;
            }
            
            const previousMode = this.state.currentMode;
            this.state.currentMode = mode;
            
            // Update UI based on selected mode
            $('.vortex-transform-mode-btn').removeClass('active');
            $(`.vortex-transform-mode-btn[data-mode="${mode}"]`).addClass('active');
            
            // Hide all mode-specific controls
            $('.vortex-mode-controls').hide();
            
            // Show controls for current mode
            $(`.vortex-mode-controls[data-mode="${mode}"]`).show();
            
            // Additional setup based on mode
            switch (mode) {
                case 'inpaint':
                case 'outpaint':
                    $('.vortex-canvas-container').show();
                    if (this.state.sourceImage) {
                        this.drawSourceOnCanvas();
                    }
                    $('.vortex-brush-controls').show();
                    break;
                case 'reference':
                    $('.vortex-reference-upload-container').show();
                    $('.vortex-canvas-container').hide();
                    $('.vortex-brush-controls').hide();
                    break;
                default: // standard
                    $('.vortex-canvas-container').hide();
                    $('.vortex-reference-upload-container').hide();
                    $('.vortex-brush-controls').hide();
                    break;
            }
            
            // Track mode change for AI learning
            this.trackAIInteraction('mode_changed', {
                previous_mode: previousMode,
                new_mode: mode,
                timestamp: new Date().toISOString()
            });
        },
        
        /**
         * Draw source image on canvas
         */
        drawSourceOnCanvas: function() {
            if (!this.state.sourceImage) {
                return;
            }
            
            const canvas = document.getElementById('vortex-canvas');
            if (!canvas) {
                return;
            }
            
            const ctx = canvas.getContext('2d');
            
            // Clear canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Calculate aspect ratio to fit the image within the canvas
            const hRatio = canvas.width / this.state.sourceImage.width;
            const vRatio = canvas.height / this.state.sourceImage.height;
            const ratio = Math.min(hRatio, vRatio);
            
            // Calculate centered position
            const centerX = (canvas.width - this.state.sourceImage.width * ratio) / 2;
            const centerY = (canvas.height - this.state.sourceImage.height * ratio) / 2;
            
            // Draw image
            ctx.drawImage(
                this.state.sourceImage,
                0, 0, this.state.sourceImage.width, this.state.sourceImage.height,
                centerX, centerY, this.state.sourceImage.width * ratio, this.state.sourceImage.height * ratio
            );
            
            // Clear mask
            this.clearMask();
        },
        
        /**
         * Start masking (for inpaint/outpaint)
         * 
         * @param {Event} e Mouse event
         */
        startMasking: function(e) {
            this.state.isMasking = true;
            
            const canvas = document.getElementById('vortex-canvas');
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            this.maskCtx.beginPath();
            this.maskCtx.moveTo(x, y);
            
            // Set brush properties
            this.maskCtx.lineWidth = this.state.brushSize;
            this.maskCtx.lineCap = 'round';
            this.maskCtx.lineJoin = 'round';
            
            // Different color for inpaint vs outpaint
            if (this.state.currentMode === 'inpaint') {
                this.maskCtx.strokeStyle = `rgba(255, 0, 0, ${this.state.brushHardness})`;
            } else { // outpaint
                this.maskCtx.strokeStyle = `rgba(0, 255, 0, ${this.state.brushHardness})`;
            }
            
            // Draw the initial point
            this.maskCtx.arc(x, y, this.state.brushSize / 2, 0, Math.PI * 2);
            this.maskCtx.fill();
            
            // Render the mask on top of the image
            this.renderMask();
        },
        
        /**
         * Continue masking (for inpaint/outpaint)
         * 
         * @param {Event} e Mouse event
         */
        continueMasking: function(e) {
            if (!this.state.isMasking) {
                return;
            }
            
            const canvas = document.getElementById('vortex-canvas');
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            this.maskCtx.lineTo(x, y);
            this.maskCtx.stroke();
            
            // Render the mask on top of the image
            this.renderMask();
        },
        
        /**
         * End masking (for inpaint/outpaint)
         */
        endMasking: function() {
            if (!this.state.isMasking) {
                return;
            }
            
            this.state.isMasking = false;
            
            // Generate mask data
            this.generateMaskData();
            
            // Track for AI learning
            this.trackAIInteraction('mask_created', {
                mode: this.state.currentMode,
                mask_coverage: this.calculateMaskCoverage(),
                timestamp: new Date().toISOString()
            });
        },
        
        /**
         * Render mask on top of image
         */
        renderMask: function() {
            const canvas = document.getElementById('vortex-canvas');
            const ctx = canvas.getContext('2d');
            
            // First redraw the source image
            this.drawSourceOnCanvas();
            
            // Then draw the mask on top
            ctx.drawImage(this.maskCanvas, 0, 0);
        },
        
        /**
         * Generate mask data from mask canvas
         */
        generateMaskData: function() {
            // Get mask image data
            const imageData = this.maskCtx.getImageData(0, 0, this.maskCanvas.width, this.maskCanvas.height);
            this.state.maskData = imageData;
        },
        
        /**
         * Calculate percentage of canvas covered by mask
         * 
         * @returns {number} Percentage of coverage (0-100)
         */
        calculateMaskCoverage: function() {
            if (!this.state.maskData) {
                return 0;
            }
            
            const data = this.state.maskData.data;
            let totalPixels = this.maskCanvas.width * this.maskCanvas.height;
            let maskedPixels = 0;
            
            // Check every 4th element (alpha channel) for non-zero values
            for (let i = 3; i < data.length; i += 4) {
                if (data[i] > 0) {
                    maskedPixels++;
                }
            }
            
            return Math.round((maskedPixels / totalPixels) * 100);
        },
        
        /**
         * Clear mask
         */
        clearMask: function() {
            this.maskCtx.clearRect(0, 0, this.maskCanvas.width, this.maskCanvas.height);
            this.state.maskData = null;
            
            // Redraw just the source image
            this.drawSourceOnCanvas();
            
            // Track for AI learning
            this.trackAIInteraction('mask_cleared', {
                mode: this.state.currentMode,
                timestamp: new Date().toISOString()
            });
        },
        
        /**
         * Handle transform button click
         */
        handleTransform: function() {
            // Prevent multiple simultaneous transformations
            if (this.state.isTransforming) {
                this.showMessage(this.config.i18n.alreadyTransforming || 'Transformation already in progress');
                return;
            }
            
            // Validate inputs
            if (!this.state.sourceImage) {
                this.showError(this.config.i18n.noSourceImage || 'Please upload a source image');
                return;
            }
            
            if (this.state.currentMode === 'reference' && !this.state.referenceImage) {
                this.showError(this.config.i18n.noReferenceImage || 'Please upload a reference image');
                return;
            }
            
            // Get transformation parameters
            const params = this.collectTransformationParameters();
            
            // Show loading state
            this.showTransformingState();
            
            // Track transformation start time for performance metrics
            const startTime = new Date();
            
            // Send transformation request
            this.transformImage(params)
                .then(result => {
                    // Track transformation duration
                    const duration = (new Date() - startTime) / 1000;
                    
                    // Process and display results
                    this.handleTransformationResult(result, duration);
                    
                    // Add to transformation history
                    this.addToTransformationHistory(result);
                })
                .catch(error => {
                    this.showError(error.message);
                })
                .finally(() => {
                    this.hideTransformingState();
                });
        },
        
        /**
         * Collect all transformation parameters from the form
         * 
         * @returns {Object} Parameters for transformation
         */
        collectTransformationParameters: function() {
            const params = {
                mode: this.state.currentMode,
                style_description: $('#vortex-style-description').val().trim(),
                strength: parseFloat($('#vortex-strength-slider').val()),
                guidance_scale: parseFloat($('#vortex-guidance-scale-slider').val()),
                steps: parseInt($('#vortex-steps-slider').val(), 10),
                preserve_color: this.state.preserveColor,
                preserve_composition: this.state.preserveComposition,
                enhance_faces: this.state.enhanceFaces,
                upscale_factor: this.state.upscaleFactor,
                output_format: this.config.outputFormat
            };
            
            // Add source image data
            if (this.state.sourceImageFile) {
                params.source_image = true; // Will be sent as FormData
            }
            
            // Add reference image for reference mode
            if (this.state.currentMode === 'reference' && this.state.referenceImageFile) {
                params.reference_image = true; // Will be sent as FormData
            }
            
            // Add mask data for inpaint/outpaint modes
            if ((this.state.currentMode === 'inpaint' || this.state.currentMode === 'outpaint') && this.state.maskData) {
                params.has_mask = true;
                params.mask_mode = this.state.currentMode;
            }
            
            // Track parameters for AI learning
            this.trackAIInteraction('transformation_requested', {
                parameters: params,
                timestamp: new Date().toISOString()
            });
            
            return params;
        },
        
        /**
         * Send transformation request to the server
         * 
         * @param {Object} params Transformation parameters
         * @returns {Promise} Promise resolving to transformation result
         */
        transformImage: function(params) {
            this.state.isTransforming = true;
            
            return new Promise((resolve, reject) => {
                // Create FormData to handle file uploads
                const formData = new FormData();
                
                // Add parameters
                formData.append('action', 'vortex_img2img_transform');
                formData.append('nonce', this.config.nonce);
                formData.append('params', JSON.stringify(params));
                
                // Add learning context for AI agents
                formData.append('learning_context', JSON.stringify({
                    interaction_history: this.state.interactionHistory.slice(-10), // Last 10 interactions
                    browser: navigator.userAgent,
                    screen_size: `${window.innerWidth}x${window.innerHeight}`,
                    session_start: this.state.processingStart.toISOString()
                }));
                
                // Add source image file
                if (this.state.sourceImageFile) {
                    formData.append('source_image', this.state.sourceImageFile);
                }
                
                // Add reference image if in reference mode
                if (params.mode === 'reference' && this.state.referenceImageFile) {
                    formData.append('reference_image', this.state.referenceImageFile);
                }
                
                // Add mask data if in inpaint/outpaint mode
                if ((params.mode === 'inpaint' || params.mode === 'outpaint') && this.state.maskData) {
                    // Convert mask canvas to blob and add to form data
                    this.maskCanvas.toBlob((blob) => {
                        formData.append('mask_image', blob);
                        
                        // Send request with all data
                        this.sendTransformRequest(formData, resolve, reject);
                    }, 'image/png');
                } else {
                    // Send request without mask data
                    this.sendTransformRequest(formData, resolve, reject);
                }
            });
        },
        
        /**
         * Send the transformation request to the server
         * 
         * @param {FormData} formData The form data to send
         * @param {Function} resolve Promise resolve function
         * @param {Function} reject Promise reject function
         */
        sendTransformRequest: function(formData, resolve, reject) {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: () => {
                    const xhr = new window.XMLHttpRequest();
                    
                    // Add progress event listener
                    xhr.upload.addEventListener('progress', (evt) => {
                        if (evt.lengthComputable) {
                            const percentComplete = (evt.loaded / evt.total) * 100;
                            $('.vortex-upload-progress').val(percentComplete).show();
                        }
                    }, false);
                    
                    return xhr;
                },
                success: response => {
                    this.state.isTransforming = false;
                    
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        reject(new Error(response.data.message || this.config.i18n.transformationError));
                        
                        // Track error for AI learning
                        this.trackAIInteraction('transformation_error', {
                            error_message: response.data.message,
                            timestamp: new Date().toISOString()
                        });
                    }
                },
                error: (xhr, status, error) => {
                    this.state.isTransforming = false;
                    
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
        },
        
        /**
         * Handle successful transformation result
         * 
         * @param {Object} result Transformation result from server
         * @param {number} duration Transformation duration in seconds
         */
        handleTransformationResult: function(result, duration) {
            // Store the result
            this.state.transformResult = result;
            
            // Display the result image
            $('#vortex-result-image').attr('src', result.url).show();
            $('.vortex-result-container').show();
            
            // Show transformation time
            $('#vortex-transformation-time').text(duration.toFixed(2));
            $('.vortex-transformation-stats').show();
            
            // Enable save button
            $('#vortex-save-result').prop('disabled', false).show();
            
            // Track successful transformation for AI learning
            this.trackAIInteraction('transformation_completed', {
                result_id: result.id,
                mode: this.state.currentMode,
                duration: duration,
                timestamp: new Date().toISOString()
            });
            
            // Display CLOE analysis if available
            if (result.cloe_analysis) {
                this.displayCloeAnalysis(result.cloe_analysis);
            }
            
            // Display business insights if available
            if (result.business_insights) {
                this.displayBusinessInsights(result.business_insights);
            }
            
            // Show success message
            this.showMessage(this.config.i18n.transformationSuccess || 'Image transformed successfully');
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
            
            // Add style transformation score if available
            if (analysis.style_score) {
                $('<div>', {
                    class: 'cloe-style-score',
                    html: `<span>${this.config.i18n.styleScore || 'Style Transfer Score'}:</span> ${analysis.style_score.toFixed(1)}/10`
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
         * Add result to transformation history
         * 
         * @param {Object} result Transformation result
         */
        addToTransformationHistory: function(result) {
            this.state.transformationHistory.push({
                result: result,
                style_description: $('#vortex-style-description').val(),
                mode: this.state.currentMode,
                strength: parseFloat($('#vortex-strength-slider').val()),
                timestamp: new Date().toISOString()
            });
            
            // Update current index to point to the new result
            this.state.currentHistoryIndex = this.state.transformationHistory.length - 1;
            
            // Update history navigation buttons
            this.updateHistoryNavigation();
        },
        
        /**
         * Navigate through transformation history
         * 
         * @param {number} direction Direction (-1 for prev, 1 for next)
         */
        navigateHistory: function(direction) {
            const newIndex = this.state.currentHistoryIndex + direction;
            
            // Check bounds
            if (newIndex < 0 || newIndex >= this.state.transformationHistory.length) {
                return;
            }
            
            // Update current index
            this.state.currentHistoryIndex = newIndex;
            
            // Get historical transformation
            const historyItem = this.state.transformationHistory[newIndex];
            
            // Display the historical result
            $('#vortex-result-image').attr('src', historyItem.result.url);
            
            // Update UI to reflect historical settings
            $('#vortex-style-description').val(historyItem.style_description);
            $('#vortex-strength-slider').val(historyItem.strength);
            $('#vortex-strength-value').text(historyItem.strength);
            
            // Update history navigation buttons
            this.updateHistoryNavigation();
            
            // Track for AI learning
            this.trackAIInteraction('history_navigated', {
                direction: direction,
                current_index: this.state.currentHistoryIndex,
                total_items: this.state.transformationHistory.length,
                timestamp: new Date().toISOString()
            });
        },
        
        /**
         * Update history navigation buttons state
         */
        updateHistoryNavigation: function() {
            // Disable prev button if at the beginning
            $('#vortex-prev-transformation').prop('disabled', this.state.currentHistoryIndex <= 0);
            
            // Disable next button if at the end
            $('#vortex-next-transformation').prop('disabled', 
                this.state.currentHistoryIndex >= this.state.transformationHistory.length - 1);
            
            // Show history navigation if we have more than one item
            if (this.state.transformationHistory.length > 1) {
                $('.vortex-history-navigation').show();
            }
        },
        
        /**
         * Request style suggestions based on user input
         * 
         * @param {string} text User input text
         */
        requestStyleSuggestions: function(text) {
            // Skip if text is too short
            if (!text || text.length < 5) {
                return;
            }
            
            // Debounce to avoid too many requests
            clearTimeout(this._styleTimeout);
            
            this._styleTimeout = setTimeout(() => {
                $.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'vortex_get_style_suggestions',
                        text: text,
                        nonce: this.config.nonce
                    },
                    success: response => {
                        if (response.success && response.data.suggestions) {
                            this.displayStyleSuggestions(response.data.suggestions);
                            
                            // Store suggestions
                            this.state.suggestedStyles = response.data.suggestions;
                            
                            // Track for AI learning
                            this.trackAIInteraction('style_suggestions_received', {
                                input_text: text,
                                suggestion_count: response.data.suggestions.length,
                                timestamp: new Date().toISOString()
                            });
                        }
                    }
                });
            }, 500);
        },
        
        /**
         * Display style suggestions
         * 
         * @param {Array} suggestions Array of style suggestions
         */
        displayStyleSuggestions: function(suggestions) {
            const $suggestionsContainer = $('.vortex-style-suggestions');
            if (!$suggestionsContainer.length) {
                return;
            }
            
            // Clear existing suggestions
            $suggestionsContainer.empty();
            
            // Add suggestions
            if (suggestions && suggestions.length) {
                $.each(suggestions, function(i, suggestion) {
                    $('<span>', {
                        class: 'vortex-suggested-style',
                        text: suggestion
                    }).appendTo($suggestionsContainer);
                });
                
                $suggestionsContainer.show();
            } else {
                $suggestionsContainer.hide();
            }
        },
        
        /**
         * Request image analysis from CLOE
         * 
         * @param {Image} img The image to analyze
         */
        requestImageAnalysis: function(img) {
            // Create a temporary canvas to get image data
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // Set canvas dimensions (downsample for faster transfer)
            const maxDim = 300;
            const scale = Math.min(maxDim / img.width, maxDim / img.height);
            canvas.width = img.width * scale;
            canvas.height = img.height * scale;
            
            // Draw the image
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            
            // Get image data URL
            const dataUrl = canvas.toDataURL('image/jpeg', 0.7);
            
            // Send to server for CLOE analysis
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_analyze_image',
                    image_data: dataUrl,
                    nonce: this.config.nonce
                },
                success: response => {
                    if (response.success && response.data.analysis) {
                        // Get style suggestions from analysis
                        if (response.data.analysis.suggested_styles) {
                            this.displayStyleSuggestions(response.data.analysis.suggested_styles);
                            
                            // Store suggestions
                            this.state.suggestedStyles = response.data.analysis.suggested_styles;
                        }
                        
                        // Track for AI learning
                        this.trackAIInteraction('image_analysis_received', {
                            has_suggestions: !!response.data.analysis.suggested_styles,
                            timestamp: new Date().toISOString()
                        });
                    }
                }
            });
        },
        
        /**
         * Handle save result button click
         */
        handleSaveResult: function() {
            if (!this.state.transformResult) {
                this.showError(this.config.i18n.noResultToSave || 'No transformation result to save');
                return;
            }
            
            // Get metadata for saving
            const metadata = {
                title: $('#vortex-style-description').val().trim().substring(0, 100) || 'Transformed Image',
                description: $('#vortex-style-description').val().trim(),
                mode: this.state.currentMode,
                strength: parseFloat($('#vortex-strength-slider').val()),
                guidance_scale: parseFloat($('#vortex-guidance-scale-slider').val()),
                steps: parseInt($('#vortex-steps-slider').val(), 10),
                preserve_color: this.state.preserveColor,
                preserve_composition: this.state.preserveComposition,
                enhance_faces: this.state.enhanceFaces,
                upscale_factor: this.state.upscaleFactor
            };
            
            // Show saving state
            $('#vortex-save-result').prop('disabled', true).text(this.config.i18n.saving || 'Saving...');
            
            // Send save request
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_save_img2img_result',
                    result: this.state.transformResult,
                    metadata: metadata,
                    nonce: this.config.nonce
                },
                success: response => {
                    if (response.success) {
                        // Show success message
                        this.showMessage(this.config.i18n.resultSaved || 'Transformation result saved successfully');
                        
                        // Offer to view/edit the saved result
                        if (response.data.edit_url) {
                            setTimeout(() => {
                                if (confirm(this.config.i18n.viewSavedResult || 'View your saved artwork?')) {
                                    window.location.href = response.data.edit_url;
                                }
                            }, 500);
                        }
                        
                        // Track for AI learning
                        this.trackAIInteraction('result_saved', {
                            artwork_id: response.data.artwork_id,
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
                    $('#vortex-save-result').prop('disabled', false).text(this.config.i18n.saveResult || 'Save Result');
                }
            });
        },
        
        /**
         * Handle cancel transformation button click
         */
        handleCancelTransformation: function() {
            if (!this.state.isTransforming) {
                return;
            }
            
            // Send cancel request
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_cancel_img2img_transformation',
                    nonce: this.config.nonce
                },
                success: response => {
                    if (response.success) {
                        this.state.isTransforming = false;
                        this.hideTransformingState();
                        this.showMessage(this.config.i18n.transformationCancelled || 'Transformation cancelled');
                        
                        // Track for AI learning
                        this.trackAIInteraction('transformation_cancelled', {
                            timestamp: new Date().toISOString()
                        });
                    }
                }
            });
        },
        
        /**
         * Show transforming state in UI
         */
        showTransformingState: function() {
            // Show loading indicator
            $('.vortex-loading-indicator').show();
            
            // Show progress bar initialized to 0
            $('.vortex-upload-progress').val(0).show();
            
            // Disable transform button
            $('#vortex-transform-btn').prop('disabled', true).text(this.config.i18n.transforming || 'Transforming...');
            
            // Show cancel button
            $('#vortex-cancel-transform').show();
            
            // Hide previous results
            $('.vortex-result-container, .vortex-transformation-stats, .vortex-cloe-analysis, .vortex-business-insights').hide();
            
            // Track transformation start for AI learning
            this.trackAIInteraction('transformation_started', {
                mode: this.state.currentMode,
                timestamp: new Date().toISOString()
            });
        },
        
        /**
         * Hide transforming state in UI
         */
        hideTransformingState: function() {
            // Hide loading indicator
            $('.vortex-loading-indicator').hide();
            
            // Hide progress bar
            $('.vortex-upload-progress').hide();
            
            // Reset transform button
            $('#vortex-transform-btn').prop('disabled', false).text(this.config.i18n.transform || 'Transform');
            
            // Hide cancel button
            $('#vortex-cancel-transform').hide();
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
                'transformation_requested',
                'transformation_completed',
                'transformation_error',
                'result_saved',
                'mask_created'
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
                    action: 'vortex_img2img_learn',
                    interactions: interactions,
                    user_id: this.config.userId,
                    session_id: this.generateSessionId(),
                    nonce: this.config.nonce
                },
                success: response => {
                    if (response.success && response.data.adjustments) {
                        // Apply any AI adjustments returned from server
                        this.applyAIAdjustments(response.data.adjustments);
                    }
                },
                // Use minimal error handling to avoid impacting user experience
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
            if (adjustments.style_suggestions && adjustments.style_suggestions.length) {
                this.displayStyleSuggestions(adjustments.style_suggestions);
            }
            
            if (adjustments.recommended_settings) {
                this.suggestSettings(adjustments.recommended_settings);
            }
            
            if (adjustments.ui_adjustments) {
                this.applyUIAdjustments(adjustments.ui_adjustments);
            }
        },
        
        /**
         * Suggest settings based on AI learning
         * 
         * @param {Object} settings Recommended settings
         */
        suggestSettings: function(settings) {
            const $settingsSuggestion = $('.vortex-settings-suggestion');
            if (!$settingsSuggestion.length) {
                return;
            }
            
            $settingsSuggestion.empty();
            
            // Create suggestion message
            let message = this.config.i18n.recommendedSettings || 'Recommended settings:';
            
            if (settings.reason) {
                message += ' ' + settings.reason;
            }
            
            $('<p>', {
                html: message
            }).appendTo($settingsSuggestion);
            
            // Create settings list
            const $settingsList = $('<ul>').appendTo($settingsSuggestion);
            
            // Add each setting suggestion
            if (settings.strength) {
                $('<li>', {
                    html: `<strong>${this.config.i18n.strength || 'Strength'}:</strong> ${settings.strength}`
                }).appendTo($settingsList);
            }
            
            if (settings.guidance_scale) {
                $('<li>', {
                    html: `<strong>${this.config.i18n.guidanceScale || 'Guidance Scale'}:</strong> ${settings.guidance_scale}`
                }).appendTo($settingsList);
            }
            
            if (settings.steps) {
                $('<li>', {
                    html: `<strong>${this.config.i18n.steps || 'Steps'}:</strong> ${settings.steps}`
                }).appendTo($settingsList);
            }
            
            // Add apply button
            $('<button>', {
                class: 'button vortex-apply-suggested-settings',
                text: this.config.i18n.applySettings || 'Apply These Settings',
                click: () => {
                    if (settings.strength) {
                        $('#vortex-strength-slider').val(settings.strength);
                        $('#vortex-strength-value').text(settings.strength);
                    }
                    
                    if (settings.guidance_scale) {
                        $('#vortex-guidance-scale-slider').val(settings.guidance_scale);
                        $('#vortex-guidance-scale-value').text(settings.guidance_scale);
                    }
                    
                    if (settings.steps) {
                        $('#vortex-steps-slider').val(settings.steps);
                        $('#vortex-steps-value').text(settings.steps);
                    }
                    
                    $settingsSuggestion.fadeOut();
                    
                    // Track for AI learning
                    this.trackAIInteraction('suggested_settings_applied', {
                        settings: settings,
                        timestamp: new Date().toISOString()
                    });
                }
            }).appendTo($settingsSuggestion);
            
            $settingsSuggestion.fadeIn();
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
            this._sessionId = `img2img_${timestamp}_${randomPart}`;
            
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
        if ($('.vortex-img2img-container').length) {
            VortexImg2Img.init();
        }
    });
    
    // Expose to global scope for external access
    window.VortexImg2Img = VortexImg2Img;
    
})(jQuery); 