/**
 * VORTEX HURAII Sharing and Download Functionality
 */
(function($) {
    'use strict';
    
    // HURAII Sharing Object
    var HURAIISharing = {
        
        /**
         * Initialize sharing functionality
         */
        init: function() {
            this.bindEvents();
            this.initClipboard();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // Download button click
            $(document).on('click', '.huraii-download-btn', this.handleDownload);
            
            // Upscale button click
            $(document).on('click', '.huraii-upscale-btn', this.handleUpscale);
            
            // Share button click
            $(document).on('click', '.huraii-share-btn', this.handleShare);
            
            // Copy link button click
            $(document).on('click', '.huraii-copy-link-btn', this.handleCopyLink);
            
            // Download format selection
            $(document).on('change', '.huraii-download-format-select', this.updateDownloadButton);
            
            // Scale factor selection
            $(document).on('change', '.huraii-scale-factor-select', this.updateUpscaleButton);
        },
        
        /**
         * Initialize clipboard functionality
         */
        initClipboard: function() {
            if (typeof ClipboardJS !== 'undefined') {
                var clipboard = new ClipboardJS('.huraii-copy-link-btn');
                
                clipboard.on('success', function(e) {
                    HURAIISharing.showNotification(VortexHURAIISharing.i18n.copy_success, 'success');
                    e.clearSelection();
                });
                
                clipboard.on('error', function() {
                    HURAIISharing.showNotification(VortexHURAIISharing.i18n.copy_failed, 'error');
                });
            }
        },
        
        /**
         * Handle download button click
         */
        handleDownload: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var filePath = $button.data('file-path');
            var variant = $button.data('variant') || 'original';
            
            $button.addClass('downloading').prop('disabled', true);
            
            $.ajax({
                url: VortexHURAIISharing.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_download_huraii_content',
                    nonce: VortexHURAIISharing.nonce,
                    file_path: filePath,
                    variant: variant
                },
                success: function(response) {
                    if (response.success) {
                        HURAIISharing.showNotification(VortexHURAIISharing.i18n.download_started, 'success');
                        
                        // Trigger download
                        var link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = response.data.filename;
                        link.style.display = 'none';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        HURAIISharing.showNotification(response.data.message || VortexHURAIISharing.i18n.download_failed, 'error');
                    }
                },
                error: function() {
                    HURAIISharing.showNotification(VortexHURAIISharing.i18n.download_failed, 'error');
                },
                complete: function() {
                    $button.removeClass('downloading').prop('disabled', false);
                }
            });
        },
        
        /**
         * Handle upscale button click
         */
        handleUpscale: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var filePath = $button.data('file-path');
            var scaleFactor = parseFloat($button.data('scale-factor') || 2);
            var settings = $button.data('settings') || {};
            
            $button.addClass('upscaling').prop('disabled', true);
            HURAIISharing.showNotification(VortexHURAIISharing.i18n.upscaling_started, 'info');
            
            $.ajax({
                url: VortexHURAIISharing.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_upscale_huraii_content',
                    nonce: VortexHURAIISharing.nonce,
                    file_path: filePath,
                    scale_factor: scaleFactor,
                    settings: settings
                },
                success: function(response) {
                    if (response.success) {
                        HURAIISharing.showNotification(VortexHURAIISharing.i18n.upscaling_complete, 'success');
                        
                        // Update preview image if applicable
                        if ($('.huraii-result-preview').length) {
                            $('.huraii-result-preview').attr('src', response.data.upscaled_url + '?t=' + new Date().getTime());
                        }
                        
                        // Update download button to point to upscaled version
                        $('.huraii-download-btn').data('file-path', response.data.upscaled_file);
                        
                        // Update UI to show upscaled dimensions
                        if ($('.huraii-dimensions-info').length) {
                            $('.huraii-dimensions-info').text(response.data.new_dimensions);
                        }
                    } else {
                        HURAIISharing.showNotification(response.data.message || VortexHURAIISharing.i18n.upscaling_failed, 'error');
                    }
                },
                error: function() {
                    HURAIISharing.showNotification(VortexHURAIISharing.i18n.upscaling_failed, 'error');
                },
                complete: function() {
                    $button.removeClass('upscaling').prop('disabled', false);
                }
            });
        },
        
        /**
         * Handle share button click
         */
        handleShare: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var filePath = $button.data('file-path');
            var platform = $button.data('platform');
            
            $.ajax({
                url: VortexHURAIISharing.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_share_huraii_content',
                    nonce: VortexHURAIISharing.nonce,
                    file_path: filePath,
                    platform: platform
                },
                success: function(response) {
                    if (response.success) {
                        // Open sharing URL in new window
                        window.open(response.data.share_url, '_blank');
                        HURAIISharing.showNotification(VortexHURAIISharing.i18n.share_success, 'success');
                    } else {
                        HURAIISharing.showNotification(response.data.message || VortexHURAIISharing.i18n.share_failed, 'error');
                    }
                },
                error: function() {
                    HURAIISharing.showNotification(VortexHURAIISharing.i18n.share_failed, 'error');
                }
            });
        },
        
        /**
         * Handle copy link button click
         */
        handleCopyLink: function(e) {
            // This is handled by ClipboardJS, but we still need to prevent default
            e.preventDefault();
        },
        
        /**
         * Update download button based on format selection
         */
        updateDownloadButton: function() {
            var $select = $(this);
            var $button = $select.closest('.huraii-download-container').find('.huraii-download-btn');
            
            $button.data('variant', $select.val());
        },
        
        /**
         * Update upscale button based on scale factor selection
         */
        updateUpscaleButton: function() {
            var $select = $(this);
            var $button = $select.closest('.huraii-upscale-container').find('.huraii-upscale-btn');
            
            $button.data('scale-factor', $select.val());
        },
        
        /**
         * Show notification
         */
        showNotification: function(message, type) {
            if (typeof message !== 'string' || !message) return;
            
            var $notification = $('<div class="huraii-notification huraii-notification-' + type + '">' + message + '</div>');
            
            $('body').append($notification);
            
            setTimeout(function() {
                $notification.addClass('show');
                
                setTimeout(function() {
                    $notification.removeClass('show');
                    
                    setTimeout(function() {
                        $notification.remove();
                    }, 300);
                }, 3000);
            }, 10);
        },
        
        /**
         * Detect user's screen resolution
         */
        detectScreenResolution: function() {
            var screenWidth = window.screen.width;
            var screenHeight = window.screen.height;
            
            // Store in the object for later use
            this.screenResolution = {
                width: screenWidth,
                height: screenHeight,
                aspectRatio: screenWidth / screenHeight
            };
            
            // Update any screen-optimized elements
            this.updateScreenSizeElements();
            
            return this.screenResolution;
        },
        
        /**
         * Update screen size elements
         */
        updateScreenSizeElements: function() {
            // Implementation of updateScreenSizeElements method
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        HURAIISharing.init();
    });
    
})(jQuery); 