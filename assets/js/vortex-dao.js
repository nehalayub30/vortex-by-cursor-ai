/**
 * VORTEX DAO Governance JavaScript
 */
(function($) {
    'use strict';

    // DOM ready
    $(document).ready(function() {
        initDAO();
    });

    /**
     * Initialize DAO functionality
     */
    function initDAO() {
        initProposalForm();
        initVoting();
        initTabs();
    }

    /**
     * Initialize proposal form modal
     */
    function initProposalForm() {
        // Show modal
        $('.vortex-create-proposal-btn').on('click', function(e) {
            e.preventDefault();
            $('.vortex-proposal-form-modal').fadeIn(300);
        });

        // Close modal
        $('.close-modal').on('click', function() {
            $('.vortex-proposal-form-modal').fadeOut(300);
        });

        // Close modal on outside click
        $('.vortex-proposal-form-modal').on('click', function(e) {
            if ($(e.target).hasClass('vortex-proposal-form-modal')) {
                $(this).fadeOut(300);
            }
        });

        // Handle proposal type change
        $('#proposal-type').on('change', function() {
            const type = $(this).val();
            $('.parameter-fields').hide();
            
            if (type === 'parameter_change') {
                $('#parameter-change-fields').show();
            } else if (type === 'feature_request') {
                $('#feature-request-fields').show();
            } else if (type === 'fund_allocation') {
                $('#fund-allocation-fields').show();
            } else if (type === 'membership') {
                $('#membership-fields').show();
            }
        });

        // Autocomplete for username
        if (typeof $.ui !== 'undefined' && $('#membership-user').length > 0) {
            $('#membership-user').autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: vortexDAOData.ajaxUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'vortex_search_users',
                            term: request.term,
                            nonce: vortexDAOData.nonce
                        },
                        success: function(data) {
                            response(data);
                        }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    $('#membership-user-id').val(ui.item.id);
                }
            });
        }

        // Form submission
        $('#vortex-proposal-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('.submit-proposal-btn');
            const $status = $form.find('.form-status');
            
            // Validate form
            if (!validateProposalForm($form)) {
                return;
            }
            
            // Disable button and show loading
            $submitBtn.prop('disabled', true).text('Submitting...');
            $status.removeClass('error success').empty();
            
            // Prepare form data
            const formData = new FormData($form[0]);
            formData.append('action', 'vortex_create_proposal');
            formData.append('nonce', vortexDAOData.nonce);
            
            // Submit proposal
            $.ajax({
                url: vortexDAOData.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $submitBtn.prop('disabled', false).text('Submit Proposal');
                    
                    if (response.success) {
                        $status.addClass('success').text('Proposal created successfully!');
                        
                        // Reset form
                        $form[0].reset();
                        $('.parameter-fields').hide();
                        
                        // Redirect after delay
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 1500);
                    } else {
                        $status.addClass('error').text(response.data.message);
                    }
                },
                error: function() {
                    $submitBtn.prop('disabled', false).text('Submit Proposal');
                    $status.addClass('error').text('An error occurred. Please try again.');
                }
            });
        });
    }

    /**
     * Validate proposal form
     */
    function validateProposalForm($form) {
        const type = $form.find('#proposal-type').val();
        let valid = true;
        
        // Reset validation errors
        $form.find('.validation-error').remove();
        
        // Check required fields
        $form.find('[required]').each(function() {
            if (!$(this).val()) {
                $(this).after('<span class="validation-error" style="color: #e53e3e; font-size: 12px; display: block; margin-top: 5px;">This field is required</span>');
                valid = false;
            }
        });
        
        // Type-specific validation
        if (type === 'parameter_change') {
            if (!$form.find('#parameter-value').val()) {
                $form.find('#parameter-value').after('<span class="validation-error" style="color: #e53e3e; font-size: 12px; display: block; margin-top: 5px;">This field is required</span>');
                valid = false;
            }
        } else if (type === 'feature_request') {
            if (!$form.find('#feature-name').val()) {
                $form.find('#feature-name').after('<span class="validation-error" style="color: #e53e3e; font-size: 12px; display: block; margin-top: 5px;">This field is required</span>');
                valid = false;
            }
        } else if (type === 'fund_allocation') {
            if (!$form.find('#fund-recipient').val() || !$form.find('#fund-amount').val() || !$form.find('#fund-purpose').val()) {
                if (!$form.find('#fund-recipient').val()) {
                    $form.find('#fund-recipient').after('<span class="validation-error" style="color: #e53e3e; font-size: 12px; display: block; margin-top: 5px;">This field is required</span>');
                }
                if (!$form.find('#fund-amount').val()) {
                    $form.find('#fund-amount').after('<span class="validation-error" style="color: #e53e3e; font-size: 12px; display: block; margin-top: 5px;">This field is required</span>');
                }
                if (!$form.find('#fund-purpose').val()) {
                    $form.find('#fund-purpose').after('<span class="validation-error" style="color: #e53e3e; font-size: 12px; display: block; margin-top: 5px;">This field is required</span>');
                }
                valid = false;
            }
        } else if (type === 'membership') {
            if (!$form.find('#membership-user').val() || !$form.find('#membership-user-id').val()) {
                $form.find('#membership-user').after('<span class="validation-error" style="color: #e53e3e; font-size: 12px; display: block; margin-top: 5px;">Please select a valid user</span>');
                valid = false;
            }
        }
        
        return valid;
    }

    /**
     * Initialize voting functionality
     */
    function initVoting() {
        if (vortexDAOData.isLoggedIn !== 'yes') {
            return;
        }
        
        $('.voting-actions').on('click', '.vote-btn', function() {
            const $btn = $(this);
            const $actions = $btn.closest('.voting-actions');
            const proposalId = $actions.data('proposal-id');
            let vote = '';
            
            if ($btn.hasClass('vote-yes')) {
                vote = 'yes';
            } else if ($btn.hasClass('vote-no')) {
                vote = 'no';
            } else if ($btn.hasClass('vote-abstain')) {
                vote = 'abstain';
            }
            
            if (!vote || !proposalId) {
                return;
            }
            
            // Disable buttons
            $actions.find('.vote-btn').prop('disabled', true);
            
            // Send vote
            $.ajax({
                url: vortexDAOData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_vote_on_proposal',
                    proposal_id: proposalId,
                    vote: vote,
                    nonce: vortexDAOData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update UI
                        const $votingResults = $actions.prev('.voting-results');
                        
                        // Update vote counts
                        const yesVotes = parseFloat(response.data.yes_votes);
                        const noVotes = parseFloat(response.data.no_votes);
                        const abstainVotes = parseFloat(response.data.abstain_votes);
                        const totalVotes = yesVotes + noVotes + abstainVotes;
                        
                        const yesPercentage = totalVotes > 0 ? (yesVotes / totalVotes) * 100 : 0;
                        const noPercentage = totalVotes > 0 ? (noVotes / totalVotes) * 100 : 0;
                        const abstainPercentage = totalVotes > 0 ? (abstainVotes / totalVotes) * 100 : 0;
                        
                        $votingResults.find('.yes-value').text(yesPercentage.toFixed(1) + '%');
                        $votingResults.find('.no-value').text(noPercentage.toFixed(1) + '%');
                        $votingResults.find('.progress-yes').css('width', yesPercentage + '%');
                        $votingResults.find('.progress-no').css('width', noPercentage + '%');
                        $votingResults.find('.progress-abstain').css('width', abstainPercentage + '%');
                        $votingResults.find('.vote-counts').text('Total votes: ' + totalVotes.toLocaleString());
                        
                        // Replace voting buttons with confirmation
                        $actions.replaceWith('<div class="user-voted">You voted: ' + vote.charAt(0).toUpperCase() + vote.slice(1) + '</div>');
                    } else {
                        alert(response.data.message);
                        $actions.find('.vote-btn').prop('disabled', false);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $actions.find('.vote-btn').prop('disabled', false);
                }
            });
        });
    }

    /**
     * Initialize tab navigation
     */
    function initTabs() {
        $('.vortex-tab-link').on('click', function(e) {
            e.preventDefault();
            
            const tabId = $(this).attr('href');
            
            $('.vortex-tab-link').removeClass('active');
            $(this).addClass('active');
            
            $('.vortex-tab-content').removeClass('active').hide();
            $(tabId).addClass('active').fadeIn();
        });
    }

})(jQuery); 