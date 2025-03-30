/**
 * Vortex AI Marketplace - Artwork Swap Dashboard
 */
(function($) {
    'use strict';
    
    // Tab functionality
    function initTabs() {
        $('.vortex-swap-tab-button').on('click', function(e) {
            e.preventDefault();
            
            // Get the tab to show
            var tabId = $(this).data('tab');
            
            // Update active states
            $('.vortex-swap-tab-button').removeClass('active');
            $(this).addClass('active');
            
            // Show the selected tab content
            $('.vortex-swap-tab-content').removeClass('active').hide();
            $('#' + tabId + '-tab').addClass('active').show();
        });
    }
    
    // Modal functionality
    function initModals() {
        // Open modal
        $('body').on('click', '.offer-swap-button', function() {
            const artworkId = $(this).data('artwork');
            $('#offered-artwork-id').val(artworkId);
            
            // Populate selected artwork preview
            const artworkCard = $(this).closest('.vortex-artwork-card');
            const thumbnailStyle = artworkCard.find('.artwork-thumbnail').attr('style');
            const title = artworkCard.find('.artwork-title').text();
            
            $('#selected-artwork-display').html(
                '<div class="preview-artwork">' +
                '<div class="artwork-thumbnail" ' + thumbnailStyle + '></div>' +
                '<div class="artwork-title">' + title + '</div>' +
                '</div>'
            );
            
            $('#offer-swap-modal').show();
        });
        
        // Open respond modal
        $('body').on('click', '.respond-offer-button', function() {
            const offerId = $(this).data('offer');
            $('#swap-offer-id').val(offerId);
            
            // Load the offered artwork preview
            const offerCard = $(this).closest('.vortex-offer-card');
            const thumbnailStyle = offerCard.find('.artwork-thumbnail').attr('style');
            const title = offerCard.find('.artwork-title').text();
            
            $('#offered-artwork-display').html(
                '<div class="preview-artwork">' +
                '<div class="artwork-thumbnail" ' + thumbnailStyle + '></div>' +
                '<div class="artwork-title">' + title + '</div>' +
                '</div>'
            );
            
            // Load user's artworks for selection
            loadUserArtworks();
            
            $('#respond-offer-modal').show();
        });
        
        // Close modal
        $('.vortex-modal-close').on('click', function() {
            $(this).closest('.vortex-modal').hide();
        });
        
        // Close modal when clicking outside
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('vortex-modal')) {
                $('.vortex-modal').hide();
            }
        });
    }
    
    // Load user's artworks for selection in respond modal
    function loadUserArtworks() {
        $.ajax({
            url: vortex_swap_data.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_get_user_artworks',
                nonce: vortex_swap_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayUserArtworks(response.data);
                } else {
                    $('#user-artworks-selection').html('<p class="error">' + response.data.message + '</p>');
                }
            },
            error: function() {
                $('#user-artworks-selection').html('<p class="error">Error loading your artworks.</p>');
            }
        });
    }
    
    // Display user's artworks for selection
    function displayUserArtworks(artworks) {
        if (artworks.length === 0) {
            $('#user-artworks-selection').html('<p>You don\'t have any eligible artworks for swapping.</p>');
            return;
        }
        
        let html = '';
        
        artworks.forEach(function(artwork) {
            html += '<div class="artwork-selection-item" data-id="' + artwork.id + '">';
            if (artwork.thumbnail) {
                html += '<div class="artwork-thumbnail" style="background-image: url(' + artwork.thumbnail + ');"></div>';
            } else {
                html += '<div class="artwork-thumbnail no-image"></div>';
            }
            html += '<div class="artwork-title">' + artwork.title + '</div>';
            html += '</div>';
        });
        
        $('#user-artworks-selection').html(html);
        
        // Add selection functionality
        $('.artwork-selection-item').on('click', function() {
            $('.artwork-selection-item').removeClass('selected');
            $(this).addClass('selected');
            $('#counter-artwork-id').val($(this).data('id'));
        });
    }
    
    // Form submission handlers
    function initFormHandlers() {
        // Offer swap form
        $('#offer-swap-form').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const submitButton = form.find('button[type="submit"]');
            const originalText = submitButton.text();
            
            submitButton.text('Sending...').prop('disabled', true);
            
            $.ajax({
                url: vortex_swap_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_initiate_swap',
                    nonce: vortex_swap_data.nonce,
                    artwork_id: $('#offered-artwork-id').val(),
                    target_artist: $('#target-artist').val(),
                    message: $('#swap-message').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        form.html('<div class="success-message">' + response.data.message + '</div>');
                        
                        // Close modal after delay
                        setTimeout(function() {
                            $('#offer-swap-modal').hide();
                            // Reload page to show updated state
                            location.reload();
                        }, 2000);
                    } else {
                        // Show error
                        form.prepend('<div class="error-message">' + response.data.message + '</div>');
                        submitButton.text(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    form.prepend('<div class="error-message">Server error. Please try again.</div>');
                    submitButton.text(originalText).prop('disabled', false);
                }
            });
        });
        
        // Respond to swap form
        $('#respond-offer-form').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const submitButton = form.find('button[type="submit"]');
            const originalText = submitButton.text();
            
            // Validate artwork selection
            if (!$('#counter-artwork-id').val()) {
                form.prepend('<div class="error-message">Please select an artwork to swap.</div>');
                return;
            }
            
            submitButton.text('Submitting...').prop('disabled', true);
            
            $.ajax({
                url: vortex_swap_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_respond_to_swap',
                    nonce: vortex_swap_data.nonce,
                    swap_id: $('#swap-offer-id').val(),
                    artwork_id: $('#counter-artwork-id').val(),
                    message: $('#response-message').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        form.html('<div class="success-message">' + response.data.message + '</div>');
                        
                        // Close modal after delay
                        setTimeout(function() {
                            $('#respond-offer-modal').hide();
                            // Reload page to show updated state
                            location.reload();
                        }, 2000);
                    } else {
                        // Show error
                        form.prepend('<div class="error-message">' + response.data.message + '</div>');
                        submitButton.text(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    form.prepend('<div class="error-message">Server error. Please try again.</div>');
                    submitButton.text(originalText).prop('disabled', false);
                }
            });
        });
        
        // Cancel offer
        $('body').on('click', '.cancel-offer-button', function() {
            const offerId = $(this).data('offer');
            
            // Set up confirmation modal
            $('#confirm-action-title').text('Cancel Swap Offer');
            $('#confirm-action-message').text('Are you sure you want to cancel this swap offer? This action cannot be undone.');
            
            // Set up confirm button
            $('#confirm-action-confirm').off('click').on('click', function() {
                // Hide modal
                $('#confirm-action-modal').hide();
                
                // Show loading indicator
                showLoadingOverlay();
                
                // Submit cancellation
                $.ajax({
                    url: vortex_swap_data.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'vortex_cancel_swap',
                        nonce: vortex_swap_data.nonce,
                        swap_id: offerId
                    },
                    success: function(response) {
                        hideLoadingOverlay();
                        
                        if (response.success) {
                            // Show success message
                            showNotification(response.data.message, 'success');
                            
                            // Reload page after delay
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            // Show error
                            showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        hideLoadingOverlay();
                        showNotification('Server error. Please try again.', 'error');
                    }
                });
            });
            
            // Set up cancel button
            $('#confirm-action-cancel').off('click').on('click', function() {
                $('#confirm-action-modal').hide();
            });
            
            // Show modal
            $('#confirm-action-modal').show();
        });
        
        // Complete swap
        $('body').on('click', '.complete-swap-button', function() {
            const offerId = $(this).data('offer');
            
            // Set up confirmation modal
            $('#confirm-action-title').text('Complete Artwork Swap');
            $('#confirm-action-message').text('Are you sure you want to complete this swap? This will transfer ownership of both artworks on the blockchain.');
            
            // Set up confirm button
            $('#confirm-action-confirm').off('click').on('click', function() {
                // Hide modal
                $('#confirm-action-modal').hide();
                
                // Show loading indicator
                showLoadingOverlay();
                
                // Submit completion
                $.ajax({
                    url: vortex_swap_data.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'vortex_complete_swap',
                        nonce: vortex_swap_data.nonce,
                        swap_id: offerId
                    },
                    success: function(response) {
                        hideLoadingOverlay();
                        
                        if (response.success) {
                            // Show success message
                            showNotification(response.data.message, 'success');
                            
                            // Reload page after delay
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            // Show error
                            showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        hideLoadingOverlay();
                        showNotification('Server error. Please try again.', 'error');
                    }
                });
            });
            
            // Set up cancel button
            $('#confirm-action-cancel').off('click').on('click', function() {
                $('#confirm-action-modal').hide();
            });
            
            // Show modal
            $('#confirm-action-modal').show();
        });
    }
    
    // AI recommendation functions
    function initAIRecommendations() {
        // View artist button
        $('body').on('click', '.view-artist-button', function() {
            const artistId = $(this).data('artist');
            
            // Show loading in modal
            $('#artist-modal-title').text('Loading...');
            $('#artist-artworks-container').html('<div class="loading-spinner"></div>');
            $('#view-artist-modal').show();
            
            // Load artist artworks
            $.ajax({
                url: vortex_swap_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_get_artist_artworks',
                    nonce: vortex_swap_data.nonce,
                    artist_id: artistId
                },
                success: function(response) {
                    if (response.success) {
                        displayArtistArtworks(response.data.artist, response.data.artworks);
                    } else {
                        $('#artist-artworks-container').html('<p class="error">' + response.data.message + '</p>');
                    }
                },
                error: function() {
                    $('#artist-artworks-container').html('<p class="error">Error loading artist artworks.</p>');
                }
            });
        });
        
        // Direct swap button
        $('body').on('click', '.direct-swap-button', function() {
            const yourArtwork = $(this).data('your-artwork');
            const theirArtwork = $(this).data('their-artwork');
            const artistId = $(this).data('artist');
            
            // Load artwork previews
            $.ajax({
                url: vortex_swap_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_get_artwork_details',
                    nonce: vortex_swap_data.nonce,
                    your_artwork: yourArtwork,
                    their_artwork: theirArtwork
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        
                        // Populate direct swap modal
                        $('#direct-your-artwork').html(
                            '<div class="swap-preview-title">Your Artwork:</div>' +
                            '<div class="artwork-thumbnail" style="background-image: url(' + data.your_artwork.thumbnail + ');"></div>' +
                            '<div class="artwork-title">' + data.your_artwork.title + '</div>'
                        );
                        
                        $('#direct-their-artwork').html(
                            '<div class="swap-preview-title">Their Artwork:</div>' +
                            '<div class="artwork-thumbnail" style="background-image: url(' + data.their_artwork.thumbnail + ');"></div>' +
                            '<div class="artwork-title">' + data.their_artwork.title + '</div>' +
                            '<div class="artwork-artist">by ' + data.their_artwork.artist + '</div>'
                        );
                        
                        // Set form values
                        $('#direct-your-artwork').val(yourArtwork);
                        $('#direct-target-artist').val(artistId);
                        
                        // Generate AI message suggestion
                        generateMessageSuggestion(yourArtwork, theirArtwork);
                        
                        // Show modal
                        $('#direct-swap-modal').show();
                    } else {
                        showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    showNotification('Server error. Please try again.', 'error');
                }
            });
        });
        
        // Use AI suggestion button
        $('#use-ai-suggestion').on('click', function() {
            const suggestion = $('#ai-suggested-message').text();
            $('#direct-swap-message').val(suggestion);
        });
        
        // Refresh recommendations button
        $('#refresh-recommendations').on('click', function() {
            const button = $(this);
            const originalText = button.text();
            
            button.text('Generating...').prop('disabled', true);
            
            $.ajax({
                url: vortex_swap_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_refresh_recommendations',
                    nonce: vortex_swap_data.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Reload the page to show new recommendations
                        location.reload();
                    } else {
                        showNotification(response.data.message, 'error');
                        button.text(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    showNotification('Server error. Please try again.', 'error');
                    button.text(originalText).prop('disabled', false);
                }
            });
        });
    }
    
    // Helper functions
    function displayArtistArtworks(artist, artworks) {
        $('#artist-modal-title').text(artist.name + '\'s Artworks');
        
        if (artworks.length === 0) {
            $('#artist-artworks-container').html('<p>This artist has no available artworks for swapping.</p>');
            return;
        }
        
        let html = '';
        
        artworks.forEach(function(artwork) {
            html += '<div class="artist-artwork-card">';
            if (artwork.thumbnail) {
                html += '<div class="artwork-thumbnail" style="background-image: url(' + artwork.thumbnail + ');"></div>';
            } else {
                html += '<div class="artwork-thumbnail no-image"></div>';
            }
            html += '<div class="artwork-details">';
            html += '<div class="artwork-title">' + artwork.title + '</div>';
            html += '<button class="vortex-button offer-direct-swap" data-artwork="' + artwork.id + '" data-artist="' + artist.id + '">Offer Swap</button>';
            html += '</div>';
            html += '</div>';
        });
        
        $('#artist-artworks-container').html(html);
    }
    
    function generateMessageSuggestion(yourArtwork, theirArtwork) {
        $.ajax({
            url: vortex_swap_data.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_generate_swap_message',
                nonce: vortex_swap_data.nonce,
                your_artwork: yourArtwork,
                their_artwork: theirArtwork
            },
            success: function(response) {
                if (response.success) {
                    $('#ai-suggested-message').text(response.data.message);
                    $('.ai-suggestion').show();
                } else {
                    $('.ai-suggestion').hide();
                }
            },
            error: function() {
                $('.ai-suggestion').hide();
            }
        });
    }
    
    function showLoadingOverlay() {
        if ($('#vortex-loading-overlay').length === 0) {
            $('body').append('<div id="vortex-loading-overlay"><div class="loading-spinner"></div></div>');
        }
        $('#vortex-loading-overlay').show();
    }
    
    function hideLoadingOverlay() {
        $('#vortex-loading-overlay').hide();
    }
    
    function showNotification(message, type) {
        const notification = $('<div class="vortex-notification ' + type + '">' + message + '</div>');
        $('body').append(notification);
        
        setTimeout(function() {
            notification.addClass('show');
        }, 10);
        
        setTimeout(function() {
            notification.removeClass('show');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 3000);
    }
    
    // Initialize everything when document is ready
    $(document).ready(function() {
        initTabs();
        initModals();
        initFormHandlers();
        initAIRecommendations();
    });
    
})(jQuery); 