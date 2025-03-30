/**
 * HURAII Interface Functionality
 */
jQuery(document).ready(function($) {
    // Global variables
    let currentLibraryPage = 1;
    let totalLibraryPages = 1;
    let currentLibraryType = 'seed';
    let userTola = 0;
    let seedArtworks = [];
    
    // Initialize HURAII interface
    function initHURAII() {
        // Check user access
        $.ajax({
            url: vortex_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_check_huraii_access',
                nonce: vortex_ajax.huraii_nonce
            },
            dataType: 'json',
            success: function(response) {
                $('#vortex-huraii-loading').hide();
                
                if (response.success) {
                    // User has access
                    const data = response.data;
                    
                    // Check if seed uploads are required
                    if (data.seed_uploads_required > 0) {
                        // Show seed upload required screen
                        $('#vortex-huraii-seed-required').show();
                        $('.vortex-huraii-seed-message').html(
                            data.seed_uploads_required === 1 ?
                            'You need to upload 1 more seed artwork this week.' :
                            `You need to upload ${data.seed_uploads_required} more seed artworks this week.`
                        );
                    } else {
                        // Show main interface
                        showMainInterface(data);
                    }
                } else {
                    // User doesn't have access
                    if (response.data && response.data.code === 'not_artist') {
                        $('#vortex-huraii-access-denied').show();
                    } else {
                        // Show generic error
                        showError('Unable to access HURAII. ' + (response.data ? response.data.message : ''));
                    }
                }
            },
            error: function() {
                $('#vortex-huraii-loading').hide();
                showError('Unable to connect to HURAII. Please try again later.');
            }
        });
    }
    
    // Show main HURAII interface
    function showMainInterface(data) {
        // Set greeting
        $('#vortex-huraii-greeting-text').text(data.greeting);
        
        // Set optional sub-greeting
        if (data.stats && data.stats.last_generation) {
            const lastGenMessage = `Your last artwork was created ${data.stats.last_generation}`;
            $('#vortex-huraii-subgreeting-text').text(lastGenMessage);
        }
        
        // Set Tola balance
        if (data.stats && data.stats.tola_balance) {
            userTola = data.stats.tola_balance;
            $('#vortex-tola-balance').text(data.stats.tola_balance);
        }
        
        // Load seed artworks
        loadArtworkLibrary('seed');
        
        // Populate artwork selects
        populateArtworkSelects();
        
        // Show main interface
        $('#vortex-huraii-main').show();
    }
    
    // Show error message
    function showError(message) {
        alert(message); // For simplicity, using alert. Could be replaced with a modal.
    }
    
    // Tab navigation
    $('.vortex-tab-button').click(function() {
        const tabId = $(this).data('tab');
        
        // Update active tab button
        $('.vortex-tab-button').removeClass('active');
        $(this).addClass('active');
        
        // Update active tab content
        $('.vortex-tab-content').removeClass('active');
        $(`.vortex-tab-content[data-tab="${tabId}"]`).addClass('active');
        
        // If switching to library tab, refresh the library
        if (tabId === 'artwork-library') {
            loadArtworkLibrary(currentLibraryType);
        }
    });
    
    // Upload seed artwork button
    $('#vortex-upload-seed-button').click(function() {
        $('#vortex-huraii-seed-required').hide();
        $('#vortex-seed-upload-form').show();
    });
    
    // Cancel seed upload button
    $('#vortex-seed-upload-cancel').click(function() {
        $('#vortex-seed-upload-form').hide();
        
        // Check if we were in the seed required screen or main interface
        if ($('#vortex-huraii-main').is(':visible')) {
            $('#vortex-huraii-main').show();
        } else {
            $('#vortex-huraii-seed-required').show();
        }
    });
    
    // Preview seed artwork before upload
    $('#seed_artwork_file').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#seed-artwork-preview').attr('src', e.target.result).show();
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Handle seed artwork form submission
    $('#vortex-seed-artwork-form').submit(function(e) {
        e.preventDefault();
        
        // Create FormData object to handle file uploads
        const formData = new FormData(this);
        
        // Add action and nonce
        formData.append('action', 'vortex_upload_seed_artwork');
        formData.append('nonce', vortex_ajax.huraii_nonce);
        formData.append('is_seed', '1');
        
        // Disable form during upload
        $('#vortex-seed-artwork-form button').prop('disabled', true);
        $('#vortex-seed-artwork-form').append('<div class="vortex-upload-progress"><div class="vortex-spinner"></div><p>Uploading...</p></div>');
        
        // Submit form
        $.ajax({
            url: vortex_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                // Re-enable form
                $('#vortex-seed-artwork-form button').prop('disabled', false);
                $('.vortex-upload-progress').remove();
                
                if (response.success) {
                    // Reset form
                    $('#vortex-seed-artwork-form')[0].reset();
                    $('#seed-artwork-preview').hide();
                    
                    // Show main interface
                    $('#vortex-seed-upload-form').hide();
                    
                    // Check if we need more seed uploads
                    if (response.data.seed_uploads_required > 0) {
                        // Still need more uploads
                        $('#vortex-huraii-seed-required').show();
                        $('.vortex-huraii-seed-message').html(
                            response.data.seed_uploads_required === 1 ?
                            'Great job! You need to upload 1 more seed artwork this week.' :
                            `Great job! You need to upload ${response.data.seed_uploads_required} more seed artworks this week.`
                        );
                    } else {
                        // No more uploads needed, show main interface
                        showMainInterface(response.data);
                    }
                } else {
                    // Show error message
                    showError(response.data ? response.data.message : 'Failed to upload artwork.');
                }
            },
            error: function() {
                // Re-enable form
                $('#vortex-seed-artwork-form button').prop('disabled', false);
                $('.vortex-upload-progress').remove();
                
                showError('Connection error. Please try again.');
            }
        });
    });
    
    // Load artwork library
    function loadArtworkLibrary(libraryType) {
        currentLibraryType = libraryType;
        
        // Update library type dropdown
        $('#vortex-library-type').val(libraryType);
        
        // Show loading indicator
        $('#vortex-artwork-grid').html('<div class="vortex-library-loading"><div class="vortex-spinner"></div><p>Loading your artwork...</p></div>');
        
        // Fetch artworks from server
        $.ajax({
            url: vortex_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_get_user_artworks',
                nonce: vortex_ajax.huraii_nonce,
                library_type: libraryType,
                page: currentLibraryPage
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // Update pagination info
                    totalLibraryPages = data.pages;
                    $('#vortex-current-page').text(data.current_page);
                    $('#vortex-total-pages').text(data.pages);
                    
                    // Toggle pagination buttons
                    $('#vortex-prev-page').prop('disabled', data.current_page <= 1);
                    $('#vortex-next-page').prop('disabled', data.current_page >= data.pages);
                    
                    // Render artwork grid
                    renderArtworkGrid(data.artworks);
                    
                    // Store seed artworks for generation select
                    if (libraryType === 'seed') {
                        seedArtworks = data.artworks;
                        populateArtworkSelects();
                    }
                } else {
                    $('#vortex-artwork-grid').html('<div class="vortex-library-error"><p>Failed to load artworks: ' + (response.data ? response.data.message : 'Unknown error') + '</p></div>');
                }
            },
            error: function() {
                $('#vortex-artwork-grid').html('<div class="vortex-library-error"><p>Connection error. Please try again.</p></div>');
            }
        });
    }
    
    // Render artwork grid
    function renderArtworkGrid(artworks) {
        if (!artworks || artworks.length === 0) {
            $('#vortex-artwork-grid').html('<div class="vortex-library-empty"><p>No artworks found. ' + (currentLibraryType === 'seed' ? 'Upload some seed artwork to get started!' : 'Switch to a different library or upload new artwork.') + '</p></div>');
            return;
        }
        
        let gridHtml = '';
        
        artworks.forEach(function(artwork) {
            gridHtml += `
                <div class="vortex-artwork-item" data-id="${artwork.id}">
                    <div class="vortex-artwork-image">
                        <img src="${artwork.image_url}" alt="${artwork.title}">
                        ${artwork.is_seed ? '<span class="vortex-artwork-badge seed">Seed</span>' : ''}
                        ${artwork.ai_generated ? '<span class="vortex-artwork-badge ai">AI Generated</span>' : ''}
                    </div>
                    <div class="vortex-artwork-info">
                        <h4>${artwork.title}</h4>
                        <p class="vortex-artwork-date">${formatDate(artwork.upload_date)}</p>
                    </div>
                    <div class="vortex-artwork-actions">
                        <button class="vortex-button vortex-button-small vortex-view-artwork" data-id="${artwork.id}">View</button>
                        <button class="vortex-button vortex-button-small vortex-analyze-artwork" data-id="${artwork.id}">Analyze</button>
                    </div>
                </div>
            `;
        });
        
        $('#vortex-artwork-grid').html(gridHtml);
        
        // Bind click events to view buttons
        $('.vortex-view-artwork').click(function() {
            const artworkId = $(this).data('id');
            viewArtworkDetails(artworkId);
        });
        
        // Bind click events to analyze buttons
        $('.vortex-analyze-artwork').click(function() {
            const artworkId = $(this).data('id');
            
            // Switch to analyze tab
            $('.vortex-tab-button[data-tab="artwork-analysis"]').click();
            
            // Select the artwork in the dropdown
            $('#analysis_artwork_select').val(artworkId);
        });
    }
    
    // Format date for display
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }
    
    // Populate artwork select dropdowns
    function populateArtworkSelects() {
        // Get artworks for dropdowns
        $.ajax({
            url: vortex_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_get_user_artworks',
                nonce: vortex_ajax.huraii_nonce,
                library_type: 'all',
                page: 1,
                per_page: 100 // Get more artworks for select
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data.artworks) {
                    const artworks = response.data.artworks;
                    
                    // Clear existing options, keeping the placeholder
                    $('#analysis_artwork_select option:not(:first-child)').remove();
                    $('#generation_seed_artwork option:not(:first-child)').remove();
                    
                    // Add options for analysis dropdown
                    artworks.forEach(function(artwork) {
                        $('#analysis_artwork_select').append(`<option value="${artwork.id}">${artwork.title}</option>`);
                    });
                    
                    // Add options for generation dropdown (seed artwork only)
                    const seedArtworks = artworks.filter(a => a.is_seed);
                    seedArtworks.forEach(function(artwork) {
                        $('#generation_seed_artwork').append(`<option value="${artwork.id}">${artwork.title}</option>`);
                    });
                }
            }
        });
    }
    
    // Handle artwork analysis form submission
    $('#vortex-artwork-analysis-form').submit(function(e) {
        e.preventDefault();
        
        const artworkId = $('#analysis_artwork_select').val();
        if (!artworkId) {
            showError('Please select an artwork to analyze.');
            return;
        }
        
        const focus = $('#analysis_focus').val();
        
        // Show analysis loading
        $('#vortex-artwork-analysis-form').hide();
        $('#vortex-analysis-results').show();
        $('.vortex-analysis-loading').show();
        $('.vortex-analysis-content').hide();
        
        // Submit for analysis
        $.ajax({
            url: vortex_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_analyze_artwork',
                artwork_id: artworkId,
                focus: focus,
                nonce: vortex_ajax.huraii_nonce
            },
            dataType: 'json',
            success: function(response) {
                $('.vortex-analysis-loading').hide();
                
                if (response.success) {
                    const data = response.data;
                    
                    // Set image and title
                    $('#vortex-analyzed-image').attr('src', data.artwork.image_url);
                    $('#vortex-analysis-title').text(data.analysis.title);
                    
                    // Build analysis HTML with Seed-Art principles highlighted
                    let analysisHtml = '<div class="vortex-analysis-text">';
                    analysisHtml += data.analysis.text;
                    analysisHtml += '</div>';
                    
                    // Add suggestions section
                    if (data.analysis.suggestions) {
                        analysisHtml += '<div class="vortex-analysis-suggestions">';
                        analysisHtml += '<h4>Enhancement Suggestions</h4>';
                        analysisHtml += data.analysis.suggestions;
                        analysisHtml += '</div>';
                    }
                    
                    // Add Seed-Art principles section
                    analysisHtml += buildSeedArtPrinciplesSection(data.analysis.elements);
                    
                    $('#vortex-analysis-text').html(analysisHtml);
                    $('.vortex-analysis-content').show();
                    
                    // Scroll to results
                    $('html, body').animate({
                        scrollTop: $('#vortex-analysis-results').offset().top - 50
                    }, 500);
                } else {
                    showError('Analysis failed: ' + (response.data ? response.data.message : 'Unknown error'));
                    $('#vortex-artwork-analysis-form').show();
                    $('#vortex-analysis-results').hide();
                }
            },
            error: function() {
                $('.vortex-analysis-loading').hide();
                showError('Server connection error. Please try again later.');
                $('#vortex-artwork-analysis-form').show();
                $('#vortex-analysis-results').hide();
            }
        });
    });
    
    /**
     * Build HTML for Seed-Art principles visualization
     */
    function buildSeedArtPrinciplesSection(elements) {
        if (!elements || Object.keys(elements).length === 0) {
            return '';
        }
        
        let html = '<div class="vortex-seed-art-principles">';
        html += '<h4>Seed-Art Principles Analysis</h4>';
        html += '<div class="vortex-principles-grid">';
        
        // Sacred Geometry
        html += '<div class="vortex-principle">';
        html += '<div class="vortex-principle-icon sacred-geometry"></div>';
        html += '<h5>Sacred Geometry</h5>';
        if (elements.sacred_geometry && elements.sacred_geometry.length > 0) {
            html += '<ul>';
            for (const item of elements.sacred_geometry) {
                html += `<li>${item}</li>`;
            }
            html += '</ul>';
        } else {
            html += '<p>No specific geometric patterns detected.</p>';
        }
        html += '</div>';
        
        // Color Weight
        html += '<div class="vortex-principle">';
        html += '<div class="vortex-principle-icon color-weight"></div>';
        html += '<h5>Color Weight</h5>';
        if (elements.color_weight && Object.keys(elements.color_weight).length > 0) {
            html += '<div class="vortex-color-palette">';
            for (const [color, weight] of Object.entries(elements.color_weight)) {
                html += `<div class="vortex-color-chip" style="background-color:${color};flex-grow:${weight}" title="${color}"></div>`;
            }
            html += '</div>';
        } else {
            html += '<p>Color analysis not available.</p>';
        }
        html += '</div>';
        
        // Continue with other principles...
        
        html += '</div>'; // End principles grid
        html += '</div>'; // End seed-art principles
        
        return html;
    }
    
    // Artwork generation form submission
    $('#vortex-artwork-generation-form').submit(function(e) {
        e.preventDefault();
        
        // Check if seed artwork is selected
        const seedArtworkId = $('#generation_seed_artwork').val();
        if (!seedArtworkId) {
            showError('Please select a seed artwork to base your generation on.');
            return;
        }
        
        // Check if user has enough Tola
        const generationSize = $('#generation_size').val();
        let tokensRequired = 10; // Default medium size
        
        if (generationSize === 'small') {
            tokensRequired = 5;
        } else if (generationSize === 'large') {
            tokensRequired = 20;
        }
        
        if (userTola < tokensRequired) {
            showError(`You need at least ${tokensRequired} Tola tokens for this generation. Your current balance is ${userTola}.`);
            return;
        }
        
        // Create FormData object
        const formData = new FormData(this);
        
        // Add action and nonce
        formData.append('action', 'vortex_generate_artwork');
        formData.append('nonce', vortex_ajax.huraii_nonce);
        
        // Show generation screen
        $('#vortex-artwork-generation-form').hide();
        $('#vortex-generation-results').show();
        $('.vortex-generation-loading').show();
        $('.vortex-generation-success, .vortex-generation-error').hide();
        
        // Submit form
        $.ajax({
            url: vortex_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                $('.vortex-generation-loading').hide();
                
                if (response.success) {
                    // Show success UI
                    const data = response.data;
                    $('#vortex-generated-image').attr('src', data.image_url);
                    $('#vortex-generated-title').text(data.title || 'Untitled Artwork');
                    $('#vortex-generated-description').text(data.description || 'Generated by HURAII based on your seed artwork and artistic direction.');
                    
                    // Update Tola balance
                    if (data.tokens_used) {
                        userTola -= data.tokens_used;
                        $('#vortex-tola-balance').text(userTola);
                    }
                    
                    $('.vortex-generation-success').show();
                } else {
                    // Show error UI
                    $('#vortex-generation-error-message').text(response.data ? response.data.message : 'An unknown error occurred.');
                    $('.vortex-generation-error').show();
                }
            },
            error: function() {
                $('.vortex-generation-loading').hide();
                $('#vortex-generation-error-message').text('Server connection error. Please try again later.');
                $('.vortex-generation-error').show();
            }
        });
    });
    
    // Try again button
    $('#vortex-try-again').click(function() {
        $('#vortex-generation-results').hide();
        $('#vortex-artwork-generation-form').show();
    });
    
    // Create another button
    $('#vortex-create-new').click(function() {
        $('#vortex-generation-results').hide();
        $('#vortex-artwork-generation-form').show();
    });
    
    // Library type filter change
    $('#vortex-library-type').change(function() {
        currentLibraryPage = 1;
        loadArtworkLibrary($(this).val());
    });
    
    // Pagination buttons
    $('#vortex-prev-page').click(function() {
        if (currentLibraryPage > 1) {
            currentLibraryPage--;
            loadArtworkLibrary(currentLibraryType);
        }
    });
    
    $('#vortex-next-page').click(function() {
        if (currentLibraryPage < totalLibraryPages) {
            currentLibraryPage++;
            loadArtworkLibrary(currentLibraryType);
        }
    });
    
    // Upload new artwork button
    $('#vortex-upload-artwork').click(function() {
        // Switch to the seed upload form
        $('#vortex-huraii-main').hide();
        $('#vortex-seed-upload-form').show();
    });
    
    // Download artwork button
    $('#vortex-download-artwork').click(function() {
        const imageUrl = $('#vortex-generated-image').attr('src');
        if (imageUrl) {
            // Create a temporary link and trigger download
            const a = document.createElement('a');
            a.href = imageUrl;
            a.download = 'huraii-artwork.jpg';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    });
    
    // Share artwork button
    $('#vortex-share-artwork').click(function() {
        alert('Sharing functionality will be implemented in a future update.');
    });
    
    // Initialize HURAII on document ready
    initHURAII();
}); 