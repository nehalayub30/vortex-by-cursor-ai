                        // Decline swap
                        $.ajax({
                            url: vortex_params.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'vortex_decline_swap',
                                nonce: vortex_params.swap_nonce,
                                swap_id: swapId
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Remove swap from list
                                    $button.closest('.swap-item').fadeOut('slow', function() {
                                        $(this).remove();
                                        
                                        // Show empty state if no more swaps
                                        if ($('#received-swaps .swap-item').length === 0) {
                                            $('#received-swaps').html(
                                                '<p class="no-swaps-message">' + vortex_params.no_received_swaps + '</p>'
                                            );
                                        }
                                    });
                                    
                                    // Show success message
                                    $('body').append(
                                        '<div class="vortex-notification success">' +
                                            '<span class="dashicons dashicons-yes-alt"></span>' +
                                            '<p>' + response.data.message + '</p>' +
                                            '<span class="close-notification">&times;</span>' +
                                        '</div>'
                                    );
                                } else {
                                    // Enable buttons
                                    $button.prop('disabled', false).text(vortex_params.decline_swap_text);
                                    $('.accept-swap-button').prop('disabled', false);
                                    
                                    // Show error message
                                    $('body').append(
                                        '<div class="vortex-notification error">' +
                                            '<span class="dashicons dashicons-dismiss"></span>' +
                                            '<p>' + response.data.message + '</p>' +
                                            '<span class="close-notification">&times;</span>' +
                                        '</div>'
                                    );
                                }
                                
                                // Remove notification after 5 seconds
                                setTimeout(function() {
                                    $('.vortex-notification').fadeOut('slow', function() {
                                        $(this).remove();
                                    });
                                }, 5000);
                            },
                            error: function() {
                                // Enable buttons
                                $button.prop('disabled', false).text(vortex_params.decline_swap_text);
                                $('.accept-swap-button').prop('disabled', false);
                                
                                // Show error message
                                $('body').append(
                                    '<div class="vortex-notification error">' +
                                        '<span class="dashicons dashicons-dismiss"></span>' +
                                        '<p>' + vortex_params.error_message + '</p>' +
                                        '<span class="close-notification">&times;</span>' +
                                    '</div>'
                                );
                                
                                // Remove notification after 5 seconds
                                setTimeout(function() {
                                    $('.vortex-notification').fadeOut('slow', function() {
                                        $(this).remove();
                                    });
                                }, 5000);
                            }
                        });
                    }
                });
                
                // Complete swap (execute on blockchain)
                $(document).on('click', '.complete-swap-button', function() {
                    const $button = $(this);
                    const swapId = $button.data('swap-id');
                    
                    if (confirm(vortex_params.complete_confirm)) {
                        // Disable button and show loading state
                        $button.prop('disabled', true).text(vortex_params.processing_text);
                        
                        // Complete swap
                        $.ajax({
                            url: vortex_params.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'vortex_complete_swap',
                                nonce: vortex_params.swap_nonce,
                                swap_id: swapId
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Remove swap from active swaps
                                    $button.closest('.swap-item').fadeOut('slow', function() {
                                        $(this).remove();
                                        
                                        // Show empty state if no more swaps
                                        if ($('#initiated-swaps .swap-item').length === 0) {
                                            $('#initiated-swaps').html(
                                                '<p class="no-swaps-message">' + vortex_params.no_initiated_swaps + '</p>'
                                            );
                                        }
                                    });
                                    
                                    // Show success message with completion details
                                    $('body').append(
                                        '<div class="vortex-notification success swap-completed">' +
                                            '<span class="dashicons dashicons-yes-alt"></span>' +
                                            '<div class="notification-content">' +
                                                '<h4>' + vortex_params.swap_completed_title + '</h4>' +
                                                '<p>' + response.data.message + '</p>' +
                                                '<p class="transaction-info">' + vortex_params.transaction_prefix + ': ' +
                                                    '<a href="' + vortex_params.explorer_url + response.data.transaction_hash + '" target="_blank">' +
                                                        response.data.transaction_hash.substring(0, 10) + '...' + response.data.transaction_hash.substring(response.data.transaction_hash.length - 8) +
                                                    '</a>' +
                                                '</p>' +
                                                '<p class="tola-reward">' + vortex_params.tola_reward_message + '</p>' +
                                            '</div>' +
                                            '<span class="close-notification">&times;</span>' +
                                        '</div>'
                                    );
                                    
                                    // Reload swap history tab to include the new completed swap
                                    loadSwapHistory();
                                } else {
                                    // Enable button
                                    $button.prop('disabled', false).text(vortex_params.complete_swap_text);
                                    
                                    // Show error message
                                    $('body').append(
                                        '<div class="vortex-notification error">' +
                                            '<span class="dashicons dashicons-dismiss"></span>' +
                                            '<p>' + response.data.message + '</p>' +
                                            '<span class="close-notification">&times;</span>' +
                                        '</div>'
                                    );
                                }
                                
                                // Remove notification after 8 seconds for completion (longer display)
                                // or 5 seconds for error
                                setTimeout(function() {
                                    $('.vortex-notification').fadeOut('slow', function() {
                                        $(this).remove();
                                    });
                                }, response.success ? 8000 : 5000);
                            },
                            error: function() {
                                // Enable button
                                $button.prop('disabled', false).text(vortex_params.complete_swap_text);
                                
                                // Show error message
                                $('body').append(
                                    '<div class="vortex-notification error">' +
                                        '<span class="dashicons dashicons-dismiss"></span>' +
                                        '<p>' + vortex_params.error_message + '</p>' +
                                        '<span class="close-notification">&times;</span>' +
                                    '</div>'
                                );
                                
                                // Remove notification after 5 seconds
                                setTimeout(function() {
                                    $('.vortex-notification').fadeOut('slow', function() {
                                        $(this).remove();
                                    });
                                }, 5000);
                            }
                        });
                    }
                });
                
                // Close notification
                $(document).on('click', '.close-notification', function() {
                    $(this).closest('.vortex-notification').fadeOut('slow', function() {
                        $(this).remove();
                    });
                });
    
    // Handle swap history filtering and details
    function setupSwapHistory() {
        // Filter swap history
        $('#history-filter, #history-sort').on('change', function() {
            filterSwapHistory();
        });
        
        // Filter swap history function
        function filterSwapHistory() {
            const filter = $('#history-filter').val();
            const sort = $('#history-sort').val();
            
            // Filter items
            if (filter === 'all') {
                $('.swap-history-item').show();
            } else {
                $('.swap-history-item').hide();
                $('.swap-history-item[data-swap-type="' + filter + '"]').show();
            }
            
            // Sort items
            const $items = $('.swap-history-item:visible').detach();
            if (sort === 'recent') {
                $items.sort(function(a, b) {
                    const dateA = $(a).find('.swap-date').text();
                    const dateB = $(b).find('.swap-date').text();
                    return new Date(dateB) - new Date(dateA);
                });
            } else {
                $items.sort(function(a, b) {
                    const dateA = $(a).find('.swap-date').text();
                    const dateB = $(b).find('.swap-date').text();
                    return new Date(dateA) - new Date(dateB);
                });
            }
            
            // Re-append items
            $('.swap-history-list').append($items);
        }
        
        // View swap details
        $(document).on('click', '.view-details-button', function() {
            const swapId = $(this).data('swap-id');
            
            // Show loading state in modal
            $('#swap-details-modal').show();
            $('.swap-details-content').html('<div class="spinner"></div><p>Loading swap details...</p>');
            
            // Get swap details
            $.ajax({
                url: vortex_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_get_swap_details',
                    nonce: vortex_params.swap_nonce,
                    swap_id: swapId
                },
                success: function(response) {
                    if (response.success) {
                        // Update modal content
                        $('.swap-details-content').html(response.data.html);
                        
                        // Update blockchain details
                        $('.transaction-hash-value').text(response.data.transaction_hash);
                        $('.transaction-date-value').text(response.data.completed_date);
                        $('.transaction-explorer-link').attr('href', vortex_params.explorer_url + response.data.transaction_hash);
                    } else {
                        // Show error message
                        $('.swap-details-content').html('<p class="error">' + response.data.message + '</p>');
                    }
                },
                error: function() {
                    // Show error message
                    $('.swap-details-content').html('<p class="error">' + vortex_params.error_message + '</p>');
                }
            });
        });
    }
    
    // Load active swaps via AJAX
    function loadActiveSwaps() {
        // Show loading state
        $('#initiated-swaps, #received-swaps').html('<div class="spinner"></div><p>Loading swaps...</p>');
        
        // Get active swaps
        $.ajax({
            url: vortex_params.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_get_active_swaps',
                nonce: vortex_params.swap_nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update initiated swaps
                    if (response.data.initiated_swaps) {
                        $('#initiated-swaps').html(response.data.initiated_swaps);
                    } else {
                        $('#initiated-swaps').html('<p class="no-swaps-message">' + vortex_params.no_initiated_swaps + '</p>');
                    }
                    
                    // Update received swaps
                    if (response.data.received_swaps) {
                        $('#received-swaps').html(response.data.received_swaps);
                    } else {
                        $('#received-swaps').html('<p class="no-swaps-message">' + vortex_params.no_received_swaps + '</p>');
                    }
                } else {
                    // Show error message
                    $('#initiated-swaps, #received-swaps').html('<p class="error">' + response.data.message + '</p>');
                }
            },
            error: function() {
                // Show error message
                $('#initiated-swaps, #received-swaps').html('<p class="error">' + vortex_params.error_message + '</p>');
            }
        });
    }
    
    // Load swap history via AJAX
    function loadSwapHistory() {
        // Show loading state
        $('.swap-history-list').html('<div class="spinner"></div><p>Loading swap history...</p>');
        
        // Get swap history
        $.ajax({
            url: vortex_params.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_get_swap_history',
                nonce: vortex_params.swap_nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update swap history
                    if (response.data.html) {
                        $('.swap-history-list').html(response.data.html);
                    } else {
                        $('.swap-history-list').html('<div class="empty-state"><p>' + vortex_params.no_swap_history + '</p></div>');
                    }
                    
                    // Apply current filters
                    filterSwapHistory();
                } else {
                    // Show error message
                    $('.swap-history-list').html('<p class="error">' + response.data.message + '</p>');
                }
            },
            error: function() {
                // Show error message
                $('.swap-history-list').html('<p class="error">' + vortex_params.error_message + '</p>');
            }
        });
    }
    
    // Initialize all functionality
    $(document).ready(function() {
        // Setup tabs
        setupTabs();
        
        // Setup swap tabs
        setupSwapTabs();
        
        // Setup artist verification form
        setupVerificationForm();
        
        // Setup artwork verification
        setupArtworkVerification();
        
        // Setup artist search
        setupArtistSearch();
        
        // Setup artist profile modal
        setupArtistProfileModal();
        
        // Setup swap proposal
        setupSwapProposal();
        
        // Setup active swaps
        setupActiveSwaps();
        
        // Setup swap history
        setupSwapHistory();
    });
    
})(jQuery); 