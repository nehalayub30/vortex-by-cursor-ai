jQuery(document).ready(function($) {
    'use strict';

    const registrationForm = $('#vortex-registration-form');
    
    if (registrationForm.length) {
        registrationForm.on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            $.ajax({
                url: vortexData.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Show business quiz popup
                        showBusinessQuizModal();
                    } else {
                        showError(response.data.message);
                    }
                },
                error: function() {
                    showError(vortexData.i18n.generalError);
                }
            });
        });
    }
    
    function showBusinessQuizModal() {
        $.ajax({
            url: vortexData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_load_business_quiz',
                nonce: vortexData.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Create modal
                    const modal = $('<div class="vortex-modal"></div>');
                    const modalContent = $('<div class="vortex-modal-content"></div>');
                    const modalHeader = $('<div class="vortex-modal-header"></div>');
                    const modalTitle = $('<h2 class="vortex-modal-title"></h2>').text(vortexData.i18n.quiz_title);
                    const closeButton = $('<button class="vortex-modal-close">&times;</button>');
                    
                    modalHeader.append(modalTitle, closeButton);
                    modalContent.append(modalHeader, response.data.html);
                    modal.append(modalContent);
                    $('body').append(modal);

                    // Handle close button
                    closeButton.on('click', function() {
                        modal.remove();
                    });

                    // Handle quiz form submission
                    $('#vortex-business-quiz-form').on('submit', function(e) {
                        e.preventDefault();
                        submitBusinessQuiz($(this), modal);
                    });
                } else {
                    console.error('Failed to load quiz:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', error);
            }
        });
    }
    
    function submitBusinessQuiz($form, $modal) {
        const $submit = $form.find('button[type="submit"]');
        const $loading = $form.find('.quiz-loading');
        const $error = $form.find('.quiz-error');
        const $success = $form.find('.quiz-success');

        // Show loading state
        $submit.prop('disabled', true);
        $loading.show();
        $error.hide();
        $success.hide();

        // Collect form data
        const formData = new FormData($form[0]);
        formData.append('action', 'vortex_process_business_quiz');
        formData.append('nonce', vortexData.nonce);

        $.ajax({
            url: vortexData.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $success.html(response.data.message).show();
                    setTimeout(function() {
                        $modal.remove();
                        window.location.href = response.data.redirect_url;
                    }, 2000);
                } else {
                    $error.html(response.data.message).show();
                    $submit.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                $error.html(vortexData.i18n.error_message).show();
                $submit.prop('disabled', false);
                console.error('Ajax error:', error);
            },
            complete: function() {
                $loading.hide();
            }
        });
    }
    
    function showError(message) {
        const error = $('<div/>', {
            class: 'vortex-error',
            text: message
        });
        
        error.insertAfter(registrationForm);
        
        setTimeout(function() {
            error.fadeOut(function() {
                error.remove();
            });
        }, 5000);
    }
    
    function showSuccess(message) {
        const success = $('<div/>', {
            class: 'vortex-success',
            text: message
        });
        
        success.insertAfter(registrationForm);
        
        setTimeout(function() {
            success.fadeOut(function() {
                success.remove();
            });
        }, 5000);
    }
}); 