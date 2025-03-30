/**
 * Business Idea Form Functionality
 */
jQuery(document).ready(function($) {
    // Character counter for textarea
    $('#business_idea').on('input', function() {
        $('#character-count').text($(this).val().length);
    });
    
    // Tab navigation
    $('.vortex-tab-header').click(function() {
        const tabId = $(this).data('tab');
        
        // Switch active tab header
        $('.vortex-tab-header').removeClass('active');
        $(this).addClass('active');
        
        // Switch active tab content
        $('.vortex-tab-content').removeClass('active');
        $('.vortex-tab-content[data-tab="' + tabId + '"]').addClass('active');
    });
    
    // Next tab button
    $('.vortex-next-tab').click(function() {
        const nextTabId = $(this).data('next');
        $('.vortex-tab-header[data-tab="' + nextTabId + '"]').click();
    });
    
    // Previous tab button
    $('.vortex-prev-tab').click(function() {
        const prevTabId = $(this).data('prev');
        $('.vortex-tab-header[data-tab="' + prevTabId + '"]').click();
    });
    
    // Form submission
    $('#vortex-business-idea-form').submit(function(e) {
        e.preventDefault();
        
        // Add hidden nonce field
        if (!$('#business_idea_nonce').length) {
            $(this).append('<input type="hidden" name="business_idea_nonce" id="business_idea_nonce" value="' + vortex_ajax.form_nonce + '">');
            $(this).append('<input type="hidden" name="action" value="vortex_process_business_idea">');
        }
        
        // Validate business idea not empty
        if ($('#business_idea').val().trim() === '') {
            alert('Please describe your business idea');
            $('.vortex-tab-header[data-tab="vision"]').click();
            return false;
        }
        
        // Show loading state
        $('#vortex-business-idea-form').hide();
        $('#vortex-business-plan-result').show();
        $('.vortex-loading-spinner').show();
        $('.vortex-business-plan-content').hide();
        
        // Submit form data
        $.ajax({
            url: vortex_ajax.ajax_url,
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                $('.vortex-loading-spinner').hide();
                
                if (response.success) {
                    $('.vortex-business-plan-content').html(response.data.html).show();
                    
                    // Show notification prompt after 2 seconds
                    setTimeout(function() {
                        $('#vortex-notification-prompt').fadeIn();
                    }, 2000);
                } else {
                    $('.vortex-business-plan-content').html('<div class="vortex-error">' + response.data.message + '</div>').show();
                }
            },
            error: function() {
                $('.vortex-loading-spinner').hide();
                $('.vortex-business-plan-content').html('<div class="vortex-error">An error occurred. Please try again.</div>').show();
            }
        });
    });
    
    // Handle notification buttons
    $('#vortex-enable-notifications').click(function() {
        // Request notification permission
        if ('Notification' in window) {
            Notification.requestPermission().then(function(permission) {
                if (permission === 'granted') {
                    // Save user preference
                    $.ajax({
                        url: vortex_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'vortex_enable_notifications',
                            nonce: vortex_ajax.notifications_nonce
                        }
                    });
                    
                    // Show success message
                    $('#vortex-notification-prompt').html('<div class="vortex-success-message"><p>Notifications enabled! We\'ll send you daily insights and motivation.</p></div>');
                    
                    // Hide after 3 seconds
                    setTimeout(function() {
                        $('#vortex-notification-prompt').fadeOut();
                    }, 3000);
                }
            });
        }
    });
    
    $('#vortex-skip-notifications').click(function() {
        $('#vortex-notification-prompt').fadeOut();
    });
}); 