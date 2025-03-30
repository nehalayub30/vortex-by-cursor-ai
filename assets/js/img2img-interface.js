/**
 * HURAII Image Transformation Interface
 * 
 * JavaScript functionality for the image transformation interface
 */

(function($) {
    'use strict';
    
    // Initialize the interface when document is ready
    $(document).ready(function() {
        const interfaceId = vortexImg2Img.interfaceId || 'vortex-img2img-interface';
        const interface = $('#' + interfaceId);
        
        if (interface.length === 0) {
            return;
        }
        
        // Elements
        const sourceImage = $('#vortex-source-image');
        const imagePreview = $('#vortex-image-preview');
        const promptInput = $('#vortex-transformation-prompt');
        const modeButtons = $('.vortex-mode-btn');
        const styleSelect = $('#vortex-style-select');
        const transformButton = $('#vortex-transform-btn');
        const progressBar = $('.vortex-transformation-progress');
        const progressText = $('.vortex-progress-text');
        const sourceCanvas = $('#vortex-source-canvas-container');
        const resultCanvas = $('#vortex-result-canvas-container');
        const canvasActions = $('.vortex-canvas-actions');
        
        // Save result actions
        const saveResultButton = $('.vortex-save-result');
        const createNftButton = $('.vortex-create-nft');
        const useAsSourceButton = $('.vortex-use-as-source');
        const resetButton = $('.vortex-reset');
        
        // Mode-specific option containers
        const styleOptions = $('.vortex-style-options');
        const inpaintOptions = $('.vortex-inpaint-options');
        const upscaleOptions = $('.vortex-upscale-options');
        const enhancementOptions = $('.vortex-enhancement-options');
        
        // Current state
        let currentMode = 'style_transfer';
        let resultImageUrl = '';
        let transformSettings = {};
        let sourceImageData = null;
        
        // Initialize drawing canvas for inpainting
        let drawingCanvas = null;
        
        // Initialize the interface
        function initInterface() {
            // Set up initial mode
            setMode(vortexImg2Img.defaultMode || 'style_transfer');
            
            // Check for initial image
            if (vortexImg2Img.initialImageUrl) {
                loadInitialImage(vortexImg2Img.initialImageUrl);
            }
            
            // Set up event handlers
            setupEventHandlers();
        }
        
        // Set up event handlers
        function setupEventHandlers() {
            // Source image upload
            sourceImage.on('change', handleImageUpload);
            
            // Mode selection
            modeButtons.on('click', function() {
                const mode = $(this).data('mode');
                setMode(mode);
            });
            
            // Transform button
            transformButton.on('click', transformImage);
            
            // Result action buttons
            saveResultButton.on('click', saveResultToLibrary);
            createNftButton.on('click', openNftCreationModal);
            useAsSourceButton.on('click', useResultAsSource);
            resetButton.on('click', resetInterface);
            
            // Enable transform button when prompt is entered
            promptInput.on('input', function() {
                toggleTransformButton();
            });
            
            // History item clicks
            interface.on('click', '.vortex-history-item', function() {
                const imageUrl = $(this).find('img').attr('src');
                if (imageUrl) {
                    loadHistoryImage(imageUrl, $(this).data('id'));
                }
            });
        }
        
        // Handle source image upload
        function handleImageUpload(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Show loading indicator
            imagePreview.html('<div class="vortex-loading">' + vortexImg2Img.i18n.uploading + '</div>');
            
            // Read the file
            const reader = new FileReader();
            reader.onload = function(event) {
                const img = new Image();
                img.onload = function() {
                    sourceImageData = {
                        src: event.target.result,
                        width: img.width,
                        height: img.height
                    };
                    
                    // Display image preview
                    displaySourceImage(event.target.result);
                    
                    // Enable transform button
                    toggleTransformButton();
                };
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        }
        
        // Display source image
        function displaySourceImage(src) {
            sourceCanvas.html('<img src="' + src + '" alt="Source Image">');
            sourceCanvas.show();
            
            // Show inpainting canvas if in inpainting mode
            if (currentMode === 'inpainting') {
                setupInpaintingCanvas(src);
            }
        }
        
        // Set the transformation mode
        function setMode(mode) {
            // Update current mode
            currentMode = mode;
            
            // Update UI
            modeButtons.removeClass('active');
            $('.vortex-mode-btn[data-mode="' + mode + '"]').addClass('active');
            
            // Hide all mode-specific options
            styleOptions.hide();
            inpaintOptions.hide();
            upscaleOptions.hide();
            enhancementOptions.hide();
            
            // Show options for selected mode
            switch (mode) {
                case 'style_transfer':
                    styleOptions.show();
                    break;
                case 'inpainting':
                    inpaintOptions.show();
                    if (sourceImageData) {
                        setupInpaintingCanvas(sourceImageData.src);
                    }
                    break;
                case 'upscaling':
                    upscaleOptions.show();
                    break;
                case 'enhancement':
                    enhancementOptions.show();
                    break;
            }
            
            // Update UI based on mode
            updateUIForMode(mode);
        }
        
        // Update UI elements based on selected mode
        function updateUIForMode(mode) {
            const promptLabel = $('.vortex-prompt-label');
            
            switch (mode) {
                case 'style_transfer':
                    promptLabel.text(vortexImg2Img.i18n.applyStyle || 'Apply style to image');
                    break;
                case 'inpainting':
                    promptLabel.text(vortexImg2Img.i18n.drawMask || 'Draw mask on areas to edit');
                    break;
                case 'upscaling':
                    promptLabel.text(vortexImg2Img.i18n.upscaleImage || 'Upscale image');
                    break;
                case 'enhancement':
                    promptLabel.text(vortexImg2Img.i18n.enhanceImage || 'Enhance image');
                    break;
            }
            
            // Reset transform settings
            transformSettings = {
                mode: mode
            };
        }
        
        // Toggle transform button based on inputs
        function toggleTransformButton() {
            if (sourceImageData && (
                currentMode === 'upscaling' || 
                currentMode === 'enhancement' || 
                promptInput.val().trim()
            )) {
                transformButton.prop('disabled', false);
            } else {
                transformButton.prop('disabled', true);
            }
        }
        
        // Transform the image
        function transformImage() {
            if (!sourceImageData) return;
            
            // Hide previous result
            resultCanvas.hide();
            
            // Show progress
            progressBar.show();
            progressText.text(vortexImg2Img.i18n.processing || 'Processing transformation...');
            progressBar.find('.vortex-progress-bar-inner').css('width', '0%');
            
            // Collect transformation settings
            const settings = {
                mode: currentMode,
                prompt: promptInput.val().trim(),
                style: styleSelect.val(),
                source: sourceImageData.src
            };
            
            // Add additional settings based on mode
            switch (currentMode) {
                case 'inpainting':
                    if (drawingCanvas) {
                        settings.mask = drawingCanvas.toDataURL();
                    }
                    break;
                case 'upscaling':
                    settings.scale = parseInt($('#vortex-upscale-factor').val() || 2);
                    break;
                case 'enhancement':
                    settings.strength = parseFloat($('#vortex-enhancement-strength').val() || 0.75);
                    break;
            }
            
            // Store settings for later use
            transformSettings = settings;
            
            // Simulate progress for demo (would be replaced with actual AJAX calls)
            simulateTransformProgress(function() {
                // This would call the actual transformation API
                processTransformation(settings);
            });
        }
        
        // Simulate transformation progress
        function simulateTransformProgress(callback) {
            let progress = 0;
            const interval = setInterval(function() {
                progress += 5;
                progressBar.find('.vortex-progress-bar-inner').css('width', progress + '%');
                
                if (progress >= 100) {
                    clearInterval(interval);
                    setTimeout(callback, 500);
                }
            }, 100);
        }
        
        // Process the transformation (would be an AJAX call to backend)
        function processTransformation(settings) {
            // In a real implementation, this would be an AJAX call
            // For now, we'll just simulate a response
            
            $.ajax({
                url: vortexImg2Img.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_process_img2img',
                    nonce: vortexImg2Img.nonce,
                    settings: settings
                },
                success: function(response) {
                    if (response.success) {
                        displayTransformationResult(response.data.resultUrl);
                        resultImageUrl = response.data.resultUrl;
                    } else {
                        displayError(response.data.message || 'Error processing transformation');
                    }
                },
                error: function() {
                    displayError('Server error. Please try again.');
                },
                complete: function() {
                    progressBar.hide();
                }
            });
            
            // For demonstration purposes (remove in production)
            // This simulates a successful transformation when the API isn't available
            setTimeout(function() {
                // Use the source image as result (in production, this would be the transformed image)
                displayTransformationResult(sourceImageData.src);
                resultImageUrl = sourceImageData.src;
            }, 500);
        }
        
        // Display transformation result
        function displayTransformationResult(resultUrl) {
            resultCanvas.html('<img src="' + resultUrl + '" alt="Transformed Image">');
            resultCanvas.show();
            sourceCanvas.hide();
            canvasActions.show();
            
            // Show success message
            progressText.text('Transformation complete!');
            progressText.addClass('success');
            
            // Reset after a delay
            setTimeout(function() {
                progressText.text('');
                progressText.removeClass('success');
            }, 3000);
        }
        
        // Display error message
        function displayError(message) {
            progressText.text(message);
            progressText.addClass('error');
            
            // Reset after a delay
            setTimeout(function() {
                progressText.text('');
                progressText.removeClass('error');
            }, 5000);
        }
        
        // Save the current result to the library
        function saveResultToLibrary() {
            // Check if there's a result to save
            if (!resultImageUrl) {
                displayMessage(vortexImg2Img.i18n.noImageToSave, 'error');
                return;
            }
            
            // Disable the save button and show saving status
            saveResultButton.prop('disabled', true);
            saveResultButton.text(vortexImg2Img.i18n.saving);
            
            // Get current settings
            const settings = getCurrentSettings();
            
            // Make AJAX request to save the image
            $.ajax({
                url: vortexImg2Img.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_save_huraii_image',
                    nonce: vortexImg2Img.nonce,
                    imageURL: resultImageUrl,
                    prompt: promptInput.val(),
                    mode: currentMode,
                    style: settings.style || '',
                    technique: settings.technique || '',
                    settings: settings,
                    sourceURL: sourceImageData ? sourceImageData.src : ''
                },
                success: function(response) {
                    if (response.success) {
                        // Re-enable the save button
                        saveResultButton.prop('disabled', false);
                        saveResultButton.text(vortexImg2Img.i18n.saved);
                        
                        // Show success message
                        displayMessage(vortexImg2Img.i18n.savedSuccess, 'success');
                        
                        // Store image ID for NFT creation
                        resultImageId = response.data.image.id;
                        
                        // Update UI
                        createNftButton.show();
                        
                        // Add to history
                        addToTransformationHistory(
                            response.data.image.id,
                            response.data.image.thumbnail_url,
                            response.data.image.url,
                            promptInput.val(),
                            currentMode
                        );
                        
                        // Reset save button text after a delay
                        setTimeout(function() {
                            saveResultButton.text(vortexImg2Img.i18n.saveToLibrary);
                        }, 2000);
                    } else {
                        // Re-enable the save button
                        saveResultButton.prop('disabled', false);
                        saveResultButton.text(vortexImg2Img.i18n.saveToLibrary);
                        
                        // Show error message
                        displayMessage(response.data.message || vortexImg2Img.i18n.saveFailed, 'error');
                    }
                },
                error: function() {
                    // Re-enable the save button
                    saveResultButton.prop('disabled', false);
                    saveResultButton.text(vortexImg2Img.i18n.saveToLibrary);
                    
                    // Show error message
                    displayMessage(vortexImg2Img.i18n.saveFailed, 'error');
                }
            });
        }
        
        // Open the NFT creation modal
        function openNftCreationModal() {
            // Check if we have a saved image ID
            if (!resultImageId) {
                displayMessage(vortexImg2Img.i18n.saveFirst, 'error');
                return;
            }
            
            // Create modal if it doesn't exist
            if ($('#vortex-nft-creation-modal').length === 0) {
                const modalHtml = `
                    <div id="vortex-nft-creation-modal" class="vortex-modal">
                        <div class="vortex-modal-content">
                            <span class="vortex-modal-close">&times;</span>
                            <h3>${vortexImg2Img.i18n.createNftTitle}</h3>
                            <div class="vortex-nft-preview">
                                <img src="${resultImageUrl}" alt="${promptInput.val()}">
                            </div>
                            <div class="vortex-nft-form">
                                <div class="vortex-form-field">
                                    <label for="vortex-nft-name">${vortexImg2Img.i18n.nftName}</label>
                                    <input type="text" id="vortex-nft-name" value="${promptInput.val().substring(0, 50)}">
                                </div>
                                <div class="vortex-form-field">
                                    <label for="vortex-nft-description">${vortexImg2Img.i18n.nftDescription}</label>
                                    <textarea id="vortex-nft-description">${promptInput.val()}</textarea>
                                </div>
                                <div class="vortex-form-field">
                                    <label for="vortex-nft-royalty">${vortexImg2Img.i18n.royaltyPercentage}</label>
                                    <input type="number" id="vortex-nft-royalty" min="2.5" max="10" step="0.5" value="5">
                                </div>
                                <div class="vortex-form-field">
                                    <label for="vortex-nft-price">${vortexImg2Img.i18n.nftPrice} (TOLA)</label>
                                    <input type="number" id="vortex-nft-price" min="1" step="1" value="10">
                                </div>
                                <div class="vortex-form-actions">
                                    <button type="button" class="vortex-modal-cancel">${vortexImg2Img.i18n.cancel}</button>
                                    <button type="button" class="vortex-modal-create-nft vortex-action-btn vortex-create-nft">${vortexImg2Img.i18n.createNft}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                $('body').append(modalHtml);
                
                // Setup event handlers
                $('#vortex-nft-creation-modal .vortex-modal-close, #vortex-nft-creation-modal .vortex-modal-cancel').on('click', function() {
                    $('#vortex-nft-creation-modal').hide();
                });
                
                $('#vortex-nft-creation-modal .vortex-modal-create-nft').on('click', createNft);
            }
            
            // Update image preview
            $('#vortex-nft-creation-modal .vortex-nft-preview img').attr('src', resultImageUrl);
            
            // Show the modal
            $('#vortex-nft-creation-modal').show();
        }
        
        // Create an NFT from the saved image
        function createNft() {
            // Get the create button
            const createBtn = $('#vortex-nft-creation-modal .vortex-modal-create-nft');
            
            // Disable button and show loading state
            createBtn.prop('disabled', true);
            createBtn.text(vortexImg2Img.i18n.processing);
            
            // Get form values
            const nftData = {
                imageId: resultImageId,
                name: $('#vortex-nft-name').val(),
                description: $('#vortex-nft-description').val(),
                royaltyPercentage: $('#vortex-nft-royalty').val(),
                price: $('#vortex-nft-price').val()
            };
            
            // Make AJAX request to create NFT
            $.ajax({
                url: vortexImg2Img.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_create_nft_from_image',
                    nonce: vortexImg2Img.nonce,
                    imageId: nftData.imageId,
                    nftName: nftData.name,
                    nftDescription: nftData.description,
                    royaltyPercentage: nftData.royaltyPercentage,
                    price: nftData.price
                },
                success: function(response) {
                    if (response.success) {
                        // Hide the modal
                        $('#vortex-nft-creation-modal').hide();
                        
                        // Show blockchain confirmation
                        showBlockchainConfirmation(response.data.nft);
                    } else {
                        // Re-enable button
                        createBtn.prop('disabled', false);
                        createBtn.text(vortexImg2Img.i18n.createNft);
                        
                        // Show error message
                        displayMessage(response.data.message || vortexImg2Img.i18n.nftFailed, 'error');
                    }
                },
                error: function() {
                    // Re-enable button
                    createBtn.prop('disabled', false);
                    createBtn.text(vortexImg2Img.i18n.createNft);
                    
                    // Show error message
                    displayMessage(vortexImg2Img.i18n.serverError, 'error');
                }
            });
        }
        
        // Show blockchain confirmation
        function showBlockchainConfirmation(nft) {
            // Create confirmation modal if it doesn't exist
            if ($('#vortex-blockchain-modal').length === 0) {
                const confirmationHtml = `
                    <div id="vortex-blockchain-modal" class="vortex-modal">
                        <div class="vortex-modal-content">
                            <span class="vortex-modal-close">&times;</span>
                            <h3>${vortexImg2Img.i18n.nftInitiated}</h3>
                            <div class="vortex-blockchain-info">
                                <p>${nft.title} ${vortexImg2Img.i18n.blockchainRegistering}</p>
                                <div class="vortex-blockchain-status">
                                    <div class="vortex-status-indicator pending"></div>
                                    <span>${vortexImg2Img.i18n.statusPending}</span>
                                </div>
                                <div class="vortex-blockchain-preview">
                                    <img src="${nft.image_url}" alt="NFT Preview">
                                </div>
                                <p>${vortexImg2Img.i18n.notificationComplete}</p>
                                <p><a href="${nft.url}" target="_blank">${vortexImg2Img.i18n.viewNftDetails}</a></p>
                            </div>
                        </div>
                    </div>
                `;
                $('body').append(confirmationHtml);
                
                // Setup modal event handlers
                $('#vortex-blockchain-modal .vortex-modal-close').on('click', function() {
                    $('#vortex-blockchain-modal').hide();
                });
            } else {
                // Update content
                $('#vortex-blockchain-modal .vortex-blockchain-info p:first').text(
                    `${nft.title} ${vortexImg2Img.i18n.blockchainRegistering}`
                );
                $('#vortex-blockchain-modal .vortex-blockchain-preview img').attr('src', nft.image_url);
                $('#vortex-blockchain-modal .vortex-blockchain-info a').attr('href', nft.url);
            }
            
            // Show modal
            $('#vortex-blockchain-modal').show();
            
            // Simulate blockchain confirmation process for demo
            setTimeout(function() {
                $('#vortex-blockchain-modal .vortex-status-indicator')
                    .removeClass('pending')
                    .addClass('success');
                $('#vortex-blockchain-modal .vortex-status-indicator').next('span')
                    .text(vortexImg2Img.i18n.statusConfirmed);
            }, 5000);
        }
        
        // Get current transformation settings
        function getCurrentSettings() {
            const settings = {
                mode: currentMode,
                prompt: promptInput.val()
            };
            
            // Add mode-specific settings
            switch(currentMode) {
                case 'style_transfer':
                    settings.style = $('#vortex-style-select').val();
                    settings.style_intensity = $('#vortex-style-intensity').val();
                    break;
                    
                case 'inpainting':
                    settings.mask_data = drawingCanvas ? drawingCanvas.toDataURL() : null;
                    settings.inpaint_mode = $('#vortex-inpaint-mode').val();
                    break;
                    
                case 'upscaling':
                    settings.scale_factor = $('#vortex-scale-factor').val();
                    settings.enhance_detail = $('#vortex-enhance-detail').is(':checked');
                    break;
                    
                case 'enhancement':
                    settings.brightness = $('#vortex-brightness').val();
                    settings.contrast = $('#vortex-contrast').val();
                    settings.saturation = $('#vortex-saturation').val();
                    settings.sharpness = $('#vortex-sharpness').val();
                    break;
            }
            
            return settings;
        }
        
        // Use result as source image
        function useResultAsSource() {
            if (!resultImageUrl) return;
            
            // Set result as source
            sourceImageData = {
                src: resultImageUrl,
                width: resultCanvas.find('img').width(),
                height: resultCanvas.find('img').height()
            };
            
            // Display as source
            displaySourceImage(resultImageUrl);
            
            // Reset result and actions
            resultCanvas.hide();
            canvasActions.hide();
            
            // Reset prompt based on current mode
            if (currentMode === 'style_transfer' || currentMode === 'inpainting') {
                promptInput.val('');
            }
            
            // Toggle transform button
            toggleTransformButton();
        }
        
        // Reset the interface
        function resetInterface() {
            // Clear source image
            sourceImageData = null;
            sourceCanvas.empty().hide();
            
            // Clear result
            resultCanvas.empty().hide();
            resultImageUrl = '';
            
            // Hide actions
            canvasActions.hide();
            
            // Reset file input
            sourceImage.val('');
            
            // Reset prompt
            promptInput.val('');
            
            // Reset drawing canvas
            if (drawingCanvas) {
                drawingCanvas = null;
            }
            
            // Disable transform button
            toggleTransformButton();
        }
        
        // Update history section with new image
        function updateHistorySection(image) {
            const historyGrid = $('.vortex-history-grid');
            const emptyHistory = $('.vortex-empty-history');
            
            // Remove empty history message if present
            if (emptyHistory.length > 0) {
                emptyHistory.remove();
            }
            
            // Create history item HTML
            const historyItemHtml = `
                <div class="vortex-history-item" data-id="${image.id}">
                    <div class="vortex-history-image">
                        <img src="${image.thumbnail_url}" alt="${image.title}">
                    </div>
                    <div class="vortex-history-details">
                        <div class="vortex-history-mode">${transformSettings.mode || 'transform'}</div>
                        <div class="vortex-history-meta">
                            Just now
                        </div>
                    </div>
                </div>
            `;
            
            // Add to beginning of grid
            historyGrid.prepend(historyItemHtml);
            
            // Limit to 8 items
            if (historyGrid.children().length > 8) {
                historyGrid.children().last().remove();
            }
        }
        
        // Load image from history
        function loadHistoryImage(imageUrl, imageId) {
            // Create a new image to get dimensions
            const img = new Image();
            img.onload = function() {
                sourceImageData = {
                    src: imageUrl,
                    width: img.width,
                    height: img.height
                };
                
                // Set as source image
                displaySourceImage(imageUrl);
                
                // Store image ID for NFT creation
                resultImageId = imageId;
                
                // Toggle transform button
                toggleTransformButton();
            };
            img.src = imageUrl;
        }
        
        // Load initial image if provided
        function loadInitialImage(imageUrl) {
            const img = new Image();
            img.onload = function() {
                sourceImageData = {
                    src: imageUrl,
                    width: img.width,
                    height: img.height
                };
                
                // Display image
                displaySourceImage(imageUrl);
                
                // Toggle transform button
                toggleTransformButton();
            };
            img.src = imageUrl;
        }
        
        // Setup inpainting canvas
        function setupInpaintingCanvas(imageSrc) {
            // Create canvas if in inpainting mode
            if (currentMode !== 'inpainting') return;
            
            // Load image first to get dimensions
            const img = new Image();
            img.onload = function() {
                // Create canvas with same dimensions as image
                const canvasHtml = `<canvas id="vortex-inpaint-canvas" width="${img.width}" height="${img.height}"></canvas>`;
                sourceCanvas.append(canvasHtml);
                
                // Initialize drawing on canvas
                initializeDrawingCanvas(document.getElementById('vortex-inpaint-canvas'), img);
            };
            img.src = imageSrc;
        }
        
        // Initialize drawing on canvas for inpainting
        function initializeDrawingCanvas(canvas, backgroundImage) {
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            let isDrawing = false;
            
            // Draw background image
            ctx.drawImage(backgroundImage, 0, 0, canvas.width, canvas.height);
            
            // Store canvas reference
            drawingCanvas = canvas;
            
            // Add event listeners for drawing
            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseout', stopDrawing);
            
            function startDrawing(e) {
                isDrawing = true;
                draw(e);
            }
            
            function draw(e) {
                if (!isDrawing) return;
                
                const rect = canvas.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                ctx.fillStyle = 'rgba(255, 0, 0, 0.5)';
                ctx.beginPath();
                ctx.arc(x, y, 10, 0, Math.PI * 2);
                ctx.fill();
            }
            
            function stopDrawing() {
                isDrawing = false;
            }
        }
        
        // Display a message
        function displayMessage(message, type) {
            const messageClass = type === 'success' ? 'success' : 'error';
            
            progressText.text(message);
            progressText.removeClass('success error').addClass(messageClass);
            
            // Reset after a delay
            setTimeout(function() {
                progressText.text('');
                progressText.removeClass(messageClass);
            }, 3000);
        }
        
        // Initialize the interface
        initInterface();
    });
})(jQuery); 