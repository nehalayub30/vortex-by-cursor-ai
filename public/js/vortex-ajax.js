jQuery(document).ready(function($) {
    // Handle offer response submission
    $('#vortex-offer-response-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitButton = $form.find('button[type="submit"]');
        var $messageContainer = $form.find('.vortex-message');
        
        // Disable submit button
        $submitButton.prop('disabled', true);
        
        // Get form data
        var formData = new FormData(this);
        formData.append('action', 'vortex_respond_to_offer');
        formData.append('nonce', vortex_ajax.nonce);
        
        // Send AJAX request
        $.ajax({
            url: vortex_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showMessage($messageContainer, response.data.message, 'success');
                    // Redirect if URL is provided
                    if (response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    }
                } else {
                    showMessage($messageContainer, response.data.message, 'error');
                }
            },
            error: function() {
                showMessage($messageContainer, 'An error occurred. Please try again.', 'error');
            },
            complete: function() {
                // Re-enable submit button
                $submitButton.prop('disabled', false);
            }
        });
    });
    
    // Handle collaboration join request submission
    $('#vortex-collaboration-join-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitButton = $form.find('button[type="submit"]');
        var $messageContainer = $form.find('.vortex-message');
        
        // Disable submit button
        $submitButton.prop('disabled', true);
        
        // Get form data
        var formData = new FormData(this);
        formData.append('action', 'vortex_join_collaboration');
        formData.append('nonce', vortex_ajax.nonce);
        
        // Send AJAX request
        $.ajax({
            url: vortex_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showMessage($messageContainer, response.data.message, 'success');
                    // Redirect if URL is provided
                    if (response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    }
                } else {
                    showMessage($messageContainer, response.data.message, 'error');
                }
            },
            error: function() {
                showMessage($messageContainer, 'An error occurred. Please try again.', 'error');
            },
            complete: function() {
                // Re-enable submit button
                $submitButton.prop('disabled', false);
            }
        });
    });
    
    // Handle collaboration leave request
    $('.vortex-leave-collaboration').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $messageContainer = $button.closest('.vortex-collaboration').find('.vortex-message');
        var collaborationId = $button.data('collaboration-id');
        
        // Disable button
        $button.prop('disabled', true);
        
        // Confirm action
        if (!confirm('Are you sure you want to leave this collaboration?')) {
            $button.prop('disabled', false);
            return;
        }
        
        // Prepare data
        var data = {
            action: 'vortex_leave_collaboration',
            nonce: vortex_ajax.nonce,
            collaboration_id: collaborationId
        };
        
        // Send AJAX request
        $.ajax({
            url: vortex_ajax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    showMessage($messageContainer, response.data.message, 'success');
                    // Reload page after successful leave
                    window.location.reload();
                } else {
                    showMessage($messageContainer, response.data.message, 'error');
                }
            },
            error: function() {
                showMessage($messageContainer, 'An error occurred. Please try again.', 'error');
            },
            complete: function() {
                // Re-enable button
                $button.prop('disabled', false);
            }
        });
    });
    
    // Helper function to show messages
    function showMessage($container, message, type) {
        $container.removeClass('vortex-message-success vortex-message-error')
            .addClass('vortex-message-' + type)
            .html(message)
            .show();
        
        // Hide message after 5 seconds
        setTimeout(function() {
            $container.fadeOut();
        }, 5000);
    }
}); 