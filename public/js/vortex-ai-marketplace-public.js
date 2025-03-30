/**
 * Handle TOLA purchase form submission
 */
$('.vortex-tola-purchase-form').on('submit', function(e) {
    e.preventDefault();
    
    var form = $(this);
    var messageContainer = form.find('.vortex-form-message');
    var submitButton = form.find('button[type="submit"]');
    
    // Disable button and show loading state
    submitButton.prop('disabled', true).addClass('vortex-loading');
    messageContainer.html('').hide();
    
    // Get form data
    var formData = {
        action: 'vortex_complete_tola_purchase',
        amount: form.find('input[name="amount"]').val(),
        nonce: form.find('#tola_purchase_nonce').val()
    };
    
    // Send AJAX request
    $.ajax({
        url: vortex_ajax.ajax_url,
        type: 'POST',
        data: formData,
        success: function(response) {
            submitButton.prop('disabled', false).removeClass('vortex-loading');
            
            if (response.success) {
                // Show success message
                messageContainer.html('<div class="vortex-notice vortex-notice-success">' + response.data.message + '</div>').show();
                
                // Update wallet balance display if it exists
                if ($('.vortex-wallet-balance').length) {
                    $('.vortex-wallet-balance-amount').text(response.data.new_balance);
                }
                
                // Show agreement if needed
                if (response.data.show_agreement) {
                    // This will trigger the agreement popup to display
                    // The agreement popup is already rendered in the footer and will be shown via JS
                    $('.vortex-show-agreement').trigger('click');
                }
                
                // Reset form
                form[0].reset();
            } else {
                // Show error message
                messageContainer.html('<div class="vortex-notice vortex-notice-error">' + response.data.message + '</div>').show();
            }
        },
        error: function() {
            submitButton.prop('disabled', false).removeClass('vortex-loading');
            messageContainer.html('<div class="vortex-notice vortex-notice-error">An error occurred during the purchase. Please try again.</div>').show();
        }
    });
});

/**
 * Handle agreement popup display
 */
$(document).on('click', '.vortex-show-agreement', function(e) {
    e.preventDefault();
    $('#vortex-agreement-modal').addClass('vortex-modal-visible');
});

/**
 * Handle agreement form submission
 */
$('#vortex-agreement-form').on('submit', function(e) {
    e.preventDefault();
    
    var form = $(this);
    var requiredCheckbox = form.find('#vortex-agreement-required-checkbox');
    var submitButton = form.find('.vortex-button-primary');
    
    // Validate required checkbox
    if (!requiredCheckbox.is(':checked')) {
        alert('You must accept the required terms to continue.');
        return;
    }
    
    // Disable button and show loading state
    submitButton.prop('disabled', true).addClass('vortex-loading');
    
    // Get form data
    var formData = {
        action: 'vortex_store_agreement',
        agreed: 'true',
        data_collection: form.find('#vortex-agreement-optional-checkbox').is(':checked') ? 'true' : 'false',
        nonce: form.find('#agreement_nonce').val()
    };
    
    // Send AJAX request
    $.ajax({
        url: vortex_ajax.ajax_url,
        type: 'POST',
        data: formData,
        success: function(response) {
            submitButton.prop('disabled', false).removeClass('vortex-loading');
            
            if (response.success) {
                // Hide modal
                $('#vortex-agreement-modal').removeClass('vortex-modal-visible');
                
                // Show success message (optional)
                alert(response.data.message);
                
                // Reload page to update access
                location.reload();
            } else {
                // Show error message
                alert(response.data.message || 'An error occurred. Please try again.');
            }
        },
        error: function() {
            submitButton.prop('disabled', false).removeClass('vortex-loading');
            alert('An error occurred. Please try again.');
        }
    });
});

/**
 * Close agreement modal
 */
$('.vortex-modal-close, .vortex-button-secondary').on('click', function() {
    $('#vortex-agreement-modal').removeClass('vortex-modal-visible');
});

/**
 * Toggle submit button based on agreement checkbox
 */
$('#vortex-agreement-required-checkbox').on('change', function() {
    $('.vortex-button-primary').prop('disabled', !$(this).is(':checked'));
}); 