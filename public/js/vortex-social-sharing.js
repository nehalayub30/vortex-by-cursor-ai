/**
 * VORTEX Social Sharing JavaScript
 *
 * Handle social sharing functionality and interactions
 */
(function($) {
    'use strict';

    // Initialize social sharing buttons
    function initSocialSharing() {
        $('.vortex-social-share-button').on('click', function(e) {
            e.preventDefault();
            
            const platform = $(this).data('platform');
            const artworkId = $(this).data('artwork-id');
            const shareMessage = $(this).data('message') || '';
            
            // Log the share in our system
            $.ajax({
                url: vortex_social.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_social_share',
                    nonce: vortex_social.nonce,
                    platform: platform,
                    artwork_id: artworkId,
                    message: shareMessage
                },
                success: function(response) {
                    if (response.success) {
                        // Open sharing dialog
                        openShareWindow(response.data.url, platform);
                        
                        // Show success message
                        showNotification(vortex_social.i18n.share_success, 'success');
                        
                        // Update the share count if the element exists
                        const countElement = $('.vortex-share-count[data-artwork-id="' + artworkId + '"]');
                        if (countElement.length) {
                            const currentCount = parseInt(countElement.text()) || 0;
                            countElement.text(currentCount + 1);
                        }
                    } else {
                        showNotification(response.data.message || vortex_social.i18n.share_error, 'error');
                    }
                },
                error: function() {
                    showNotification(vortex_social.i18n.share_error, 'error');
                }
            });
        });
    }
    
    // Open sharing window/dialog
    function openShareWindow(url, platform) {
        // Email uses different handling
        if (platform === 'email') {
            window.location.href = url;
            return;
        }
        
        // For other platforms, open in a popup
        const width = 600;
        const height = 400;
        const left = (screen.width/2) - (width/2);
        const top = (screen.height/2) - (height/2);
        
        window.open(
            url,
            'vortex_share_' + platform,
            'width=' + width + ',height=' + height + ',top=' + top + ',left=' + left + ',toolbar=0,location=0,menubar=0,scrollbars=1'
        );
    }
    
    // Show notification
    function showNotification(message, type) {
        // Check if notification container exists, create if not
        let notificationContainer = $('.vortex-notifications');
        if (!notificationContainer.length) {
            $('body').append('<div class="vortex-notifications"></div>');
            notificationContainer = $('.vortex-notifications');
        }
        
        // Create notification
        const notification = $('<div class="vortex-notification vortex-notification-' + type + '">' + message + '</div>');
        
        // Add to container
        notificationContainer.append(notification);
        
        // Fade in
        notification.fadeIn();
        
        // Remove after delay
        setTimeout(function() {
            notification.fadeOut(function() {
                notification.remove();
            });
        }, 3000);
    }
    
    // Initialize social share dropdown menus
    function initShareDropdowns() {
        $('.vortex-social-share-dropdown-toggle').on('click', function(e) {
            e.preventDefault();
            $(this).next('.vortex-social-share-dropdown').toggleClass('active');
        });
        
        // Close dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.vortex-social-share-container').length) {
                $('.vortex-social-share-dropdown').removeClass('active');
            }
        });
    }
    
    // Initialize when document is ready
    $(document).ready(function() {
        initSocialSharing();
        initShareDropdowns();
    });

})(jQuery); 