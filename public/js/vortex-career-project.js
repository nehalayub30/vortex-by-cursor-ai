/**
 * JavaScript for Vortex Career Path, Project Proposals, and Collaboration Hub
 *
 * Handles form submissions, modal interactions, and view toggles.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/js
 */

(function($) {
    'use strict';

    /**
     * Career Path Form Handler
     */
    function initCareerPathForm() {
        $('#vortex-career-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitButton = $form.find('button[type="submit"]');
            var $message = $('#vortex-career-message');
            
            // Disable submit button and show loading state
            $submitButton.prop('disabled', true).text(vortex_career.i18n.submitting);
            $message.removeClass('vortex-message-error vortex-message-success')
                    .addClass('vortex-message-info')
                    .text(vortex_career.i18n.generating)
                    .show();
            
            // Prepare form data
            var formData = new FormData(this);
            formData.append('action', 'vortex_submit_career_path');
            formData.append('nonce', vortex_career.nonce);
            
            // Send AJAX request
            $.ajax({
                url: vortex_career.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $message.removeClass('vortex-message-info vortex-message-error')
                                .addClass('vortex-message-success')
                                .text(response.data.message);
                        
                        // Reload page to show new recommendations
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        $message.removeClass('vortex-message-info vortex-message-success')
                                .addClass('vortex-message-error')
                                .text(response.data.message);
                    }
                },
                error: function() {
                    $message.removeClass('vortex-message-info vortex-message-success')
                            .addClass('vortex-message-error')
                            .text(vortex_career.i18n.error);
                },
                complete: function() {
                    // Re-enable submit button
                    $submitButton.prop('disabled', false).text(vortex_career.i18n.update_career);
                }
            });
        });
    }

    /**
     * Project Proposals Handlers
     */
    function initProjectProposals() {
        // Modal functionality
        var $modal = $('#vortex-project-modal');
        var $modalBtn = $('#vortex-create-project-btn');
        var $closeBtn = $('.vortex-modal-close');
        
        $modalBtn.on('click', function() {
            $modal.css('display', 'block');
        });
        
        $closeBtn.on('click', function() {
            $modal.css('display', 'none');
        });
        
        $(window).on('click', function(e) {
            if ($(e.target).is($modal)) {
                $modal.css('display', 'none');
            }
        });
        
        // Filter functionality
        $('.vortex-filter').on('change', function() {
            var category = $('#project-category-filter').val();
            var skill = $('#project-skill-filter').val();
            var budget = $('#project-budget-filter').val();
            
            $('.vortex-project-item').each(function() {
                var $item = $(this);
                var itemCategories = $item.data('category').split(',');
                var itemSkills = $item.data('skills').split(',');
                var itemBudget = parseFloat($item.data('budget'));
                
                var categoryMatch = !category || itemCategories.indexOf(category) !== -1;
                var skillMatch = !skill || itemSkills.indexOf(skill) !== -1;
                var budgetMatch = true;
                
                if (budget) {
                    if (budget === '0-100') {
                        budgetMatch = itemBudget >= 0 && itemBudget <= 100;
                    } else if (budget === '100-500') {
                        budgetMatch = itemBudget > 100 && itemBudget <= 500;
                    } else if (budget === '500-1000') {
                        budgetMatch = itemBudget > 500 && itemBudget <= 1000;
                    } else if (budget === '1000+') {
                        budgetMatch = itemBudget > 1000;
                    }
                }
                
                if (categoryMatch && skillMatch && budgetMatch) {
                    $item.show();
                } else {
                    $item.hide();
                }
            });
        });
        
        // Project form submission
        $('#vortex-project-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitButton = $form.find('button[type="submit"]');
            var $message = $('#vortex-project-message');
            
            // Check if at least one skill is selected
            if ($form.find('input[name="skills_required[]"]:checked').length === 0) {
                $message.removeClass('vortex-message-success')
                        .addClass('vortex-message-error')
                        .text(vortex_career.i18n.required_skill)
                        .show();
                return;
            }
            
            // Disable submit button and show loading state
            $submitButton.prop('disabled', true).text(vortex_career.i18n.creating);
            $message.removeClass('vortex-message-error vortex-message-success')
                    .addClass('vortex-message-info')
                    .text(vortex_career.i18n.creating_project)
                    .show();
            
            // Prepare form data
            var formData = new FormData(this);
            formData.append('action', 'vortex_submit_project_proposal');
            formData.append('nonce', vortex_career.nonce);
            
            // Send AJAX request
            $.ajax({
                url: vortex_career.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $message.removeClass('vortex-message-info vortex-message-error')
                                .addClass('vortex-message-success')
                                .text(response.data.message);
                        
                        // Redirect to project page
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url || window.location.href;
                        }, 1500);
                    } else {
                        $message.removeClass('vortex-message-info vortex-message-success')
                                .addClass('vortex-message-error')
                                .text(response.data.message);
                    }
                },
                error: function() {
                    $message.removeClass('vortex-message-info vortex-message-success')
                            .addClass('vortex-message-error')
                            .text(vortex_career.i18n.error);
                },
                complete: function() {
                    // Re-enable submit button
                    $submitButton.prop('disabled', false).text(vortex_career.i18n.create_project);
                }
            });
        });
    }

    /**
     * Collaboration Hub Handlers
     */
    function initCollaborationHub() {
        // View toggle
        $('.vortex-view-toggle').on('click', function() {
            var view = $(this).data('view');
            $('.vortex-view-toggle').attr('aria-selected', 'false');
            $(this).attr('aria-selected', 'true');
            
            if (view === 'list') {
                $('.vortex-collaborations-grid').addClass('vortex-view-list');
            } else {
                $('.vortex-collaborations-grid').removeClass('vortex-view-list');
            }
        });
        
        // Join modal
        var $joinModal = $('#vortex-join-modal');
        $('.vortex-join-button').on('click', function() {
            var collaborationId = $(this).data('id');
            $('#join-collaboration-id').val(collaborationId);
            $joinModal.css('display', 'block');
        });
        
        // Create collaboration modal
        var $createModal = $('#vortex-create-modal');
        $('#vortex-start-collaboration').on('click', function() {
            $createModal.css('display', 'block');
        });
        
        // Close modals
        $('.vortex-modal-close').on('click', function() {
            $('.vortex-modal').css('display', 'none');
        });
        
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('vortex-modal')) {
                $('.vortex-modal').css('display', 'none');
            }
        });
        
        // Handle join request submission
        $('#vortex-join-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitButton = $form.find('button[type="submit"]');
            var $message = $('#vortex-join-message');
            
            // Disable submit button
            $submitButton.prop('disabled', true).text(vortex_career.i18n.submitting);
            
            // Prepare form data
            var formData = new FormData(this);
            formData.append('action', 'vortex_join_collaboration');
            formData.append('nonce', vortex_career.nonce);
            
            // Send AJAX request
            $.ajax({
                url: vortex_career.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $message.removeClass('vortex-message-error')
                                .addClass('vortex-message-success')
                                .text(response.data.message)
                                .show();
                        
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        $message.removeClass('vortex-message-success')
                                .addClass('vortex-message-error')
                                .text(response.data.message)
                                .show();
                    }
                },
                error: function() {
                    $message.removeClass('vortex-message-success')
                            .addClass('vortex-message-error')
                            .text(vortex_career.i18n.error)
                            .show();
                },
                complete: function() {
                    $submitButton.prop('disabled', false).text(vortex_career.i18n.submit_request);
                }
            });
        });
        
        // Handle collaboration creation form
        $('#vortex-collaboration-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitButton = $form.find('button[type="submit"]');
            var $message = $('#vortex-collaboration-message');
            
            // Disable submit button
            $submitButton.prop('disabled', true).text(vortex_career.i18n.creating);
            
            // Prepare form data
            var formData = new FormData(this);
            formData.append('action', 'vortex_create_collaboration');
            formData.append('nonce', vortex_career.nonce);
            
            // Send AJAX request
            $.ajax({
                url: vortex_career.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $message.removeClass('vortex-message-error')
                                .addClass('vortex-message-success')
                                .text(response.data.message)
                                .show();
                        
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url || window.location.href;
                        }, 1500);
                    } else {
                        $message.removeClass('vortex-message-success')
                                .addClass('vortex-message-error')
                                .text(response.data.message)
                                .show();
                    }
                },
                error: function() {
                    $message.removeClass('vortex-message-success')
                            .addClass('vortex-message-error')
                            .text(vortex_career.i18n.error)
                            .show();
                },
                complete: function() {
                    $submitButton.prop('disabled', false).text(vortex_career.i18n.create_collaboration);
                }
            });
        });
    }

    // Initialize on document ready
    $(document).ready(function() {
        if ($('#vortex-career-form').length) {
            initCareerPathForm();
        }
        
        if ($('.vortex-project-proposals').length) {
            initProjectProposals();
        }
        
        if ($('.vortex-collaboration-hub').length) {
            initCollaborationHub();
        }
    });

})(jQuery); 