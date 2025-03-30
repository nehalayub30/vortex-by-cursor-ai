jQuery(document).ready(function($) {
    'use strict';

    // Handle subscription button clicks
    $('.vortex-subscribe-button').on('click', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const planType = $button.data('plan-type');
        const planTier = $button.data('plan-tier');
        
        // Check if user is logged in
        if (!vortexData.isLoggedIn) {
            window.location.href = vortexData.loginUrl;
            return;
        }
        
        // Check TOLA balance
        checkTolaBalance(planType, planTier);
    });
    
    // Check TOLA balance before proceeding with subscription
    function checkTolaBalance(planType, planTier) {
        $.ajax({
            url: vortexData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_check_tola_balance',
                plan_type: planType,
                plan_tier: planTier,
                nonce: vortexData.nonce
            },
            beforeSend: function() {
                showLoadingState();
            },
            success: function(response) {
                if (response.success) {
                    processSubscription(planType, planTier);
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                showError(vortexData.i18n.generalError);
            },
            complete: function() {
                hideLoadingState();
            }
        });
    }
    
    // Process subscription payment
    function processSubscription(planType, planTier) {
        $.ajax({
            url: vortexData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_process_subscription',
                plan_type: planType,
                plan_tier: planTier,
                nonce: vortexData.nonce
            },
            beforeSend: function() {
                showLoadingState();
            },
            success: function(response) {
                if (response.success) {
                    showSuccess(response.data.message);
                    // Redirect to dashboard after successful subscription
                    setTimeout(function() {
                        window.location.href = vortexData.dashboardUrl;
                    }, 2000);
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                showError(vortexData.i18n.generalError);
            },
            complete: function() {
                hideLoadingState();
            }
        });
    }
    
    // UI Helper Functions
    function showLoadingState() {
        $('.vortex-subscribe-button').addClass('loading').prop('disabled', true);
    }
    
    function hideLoadingState() {
        $('.vortex-subscribe-button').removeClass('loading').prop('disabled', false);
    }
    
    function showSuccess(message) {
        const $notice = $('<div class="vortex-subscription-success"></div>')
            .text(message)
            .insertAfter('.vortex-subscription-notice');
            
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    function showError(message) {
        const $notice = $('<div class="vortex-subscription-error"></div>')
            .text(message)
            .insertAfter('.vortex-subscription-notice');
            
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
}); 