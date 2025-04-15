/**
 * VORTEX DAO Rewards JavaScript
 *
 * Handles rewards interactions and UI
 *
 * @link       https://vortexmarketplace.io
 * @since      1.0.0
 */

(function($) {
    'use strict';

    // Initialize rewards functionality
    const VortexDAORewards = {
        
        /**
         * Initialize rewards UI and functionality
         */
        init: function() {
            this.setupEventListeners();
            this.refreshRewardsData();
        },
        
        /**
         * Setup event listeners
         */
        setupEventListeners: function() {
            // Claim rewards button click
            $('#submitClaimRewards').on('click', this.handleClaimRewards);
            
            // Filter rewards by type
            $('#filterRewardType').on('change', this.filterRewards);
            
            // Modal shown event
            $('#rewardsClaimModal').on('shown.bs.modal', function() {
                // Focus the wallet select
                $('#rewardWalletSelect').trigger('focus');
            });
            
            // Reward row click for details
            $('.reward-row').on('click', this.showRewardDetails);
            
            // Connect wallet button for users without wallets
            $('.connect-wallet-btn').on('click', this.redirectToWalletPage);
        },
        
        /**
         * Handle reward claim submission
         */
        handleClaimRewards: function(e) {
            e.preventDefault();
            
            const form = $('#claimRewardsForm');
            const statusDiv = $('.claim-status');
            const submitBtn = $(this);
            
            // Validate form
            if (!form.find('[name="wallet_address"]').val()) {
                VortexDAORewards.showMessage(statusDiv, 'Please select a wallet address.', 'danger');
                return;
            }
            
            // Show loading
            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
            VortexDAORewards.showMessage(statusDiv, 'Processing your claim. Please wait...', 'info');
            
            // Submit claim
            $.ajax({
                url: vortex_ajax.ajax_url,
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        VortexDAORewards.showMessage(statusDiv, response.data.message, 'success');
                        
                        // Show transaction details
                        if (response.data.transaction_signature) {
                            VortexDAORewards.showTransactionDetails(statusDiv, response.data.transaction_signature);
                        }
                        
                        // Update UI after successful claim
                        setTimeout(function() {
                            location.reload();
                        }, 5000);
                    } else {
                        VortexDAORewards.showMessage(statusDiv, response.data.message, 'danger');
                        submitBtn.prop('disabled', false).text('Claim Rewards');
                    }
                },
                error: function() {
                    VortexDAORewards.showMessage(statusDiv, 'An error occurred. Please try again.', 'danger');
                    submitBtn.prop('disabled', false).text('Claim Rewards');
                }
            });
        },
        
        /**
         * Show message in status div
         */
        showMessage: function(statusDiv, message, type) {
            statusDiv.html('<div class="alert alert-' + type + '">' + message + '</div>').show();
        },
        
        /**
         * Show transaction details
         */
        showTransactionDetails: function(statusDiv, signature) {
            const explorerUrl = 'https://solscan.io/tx/' + signature;
            statusDiv.append('<div class="transaction-details mt-3">' +
                '<p>Transaction Signature:</p>' +
                '<a href="' + explorerUrl + '" target="_blank" class="transaction-link">' + 
                signature + ' <i class="fas fa-external-link-alt"></i></a>' +
            '</div>');
        },
        
        /**
         * Filter rewards by type
         */
        filterRewards: function() {
            const selectedType = $(this).val();
            
            if (selectedType === 'all') {
                $('.reward-row').show();
            } else {
                $('.reward-row').hide();
                $('.reward-row[data-type="' + selectedType + '"]').show();
            }
            
            // Update displayed count
            const visibleCount = $('.reward-row:visible').length;
            const totalCount = $('.reward-row').length;
            
            $('#visibleRewardsCount').text(visibleCount);
            $('#totalRewardsCount').text(totalCount);
        },
        
        /**
         * Show reward details in modal
         */
        showRewardDetails: function() {
            const rewardId = $(this).data('id');
            const rewardModal = $('#rewardDetailsModal');
            
            // Show loading in modal
            rewardModal.find('.modal-body').html('<div class="text-center p-4"><span class="spinner-border" role="status"></span><p class="mt-2">Loading reward details...</p></div>');
            
            // Show modal
            rewardModal.modal('show');
            
            // Fetch reward details
            $.ajax({
                url: vortex_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_get_reward_details',
                    nonce: vortex_rewards.nonce,
                    reward_id: rewardId
                },
                success: function(response) {
                    if (response.success) {
                        rewardModal.find('.modal-body').html(response.data.html);
                    } else {
                        rewardModal.find('.modal-body').html('<div class="alert alert-danger">' + response.data.message + '</div>');
                    }
                },
                error: function() {
                    rewardModal.find('.modal-body').html('<div class="alert alert-danger">Failed to load reward details.</div>');
                }
            });
        },
        
        /**
         * Redirect to wallet page
         */
        redirectToWalletPage: function(e) {
            e.preventDefault();
            window.location.href = vortex_rewards.wallet_page_url;
        },
        
        /**
         * Refresh rewards data periodically
         */
        refreshRewardsData: function() {
            // Only refresh if user is viewing the full rewards page
            if ($('.vortex-dao-rewards-dashboard.full-page').length === 0) {
                return;
            }
            
            // Refresh every 2 minutes
            setInterval(function() {
                $.ajax({
                    url: vortex_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'vortex_get_user_rewards',
                        nonce: vortex_rewards.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update total pending amount
                            $('.total-pending .value').text(parseFloat(response.data.total_pending).toFixed(2) + ' TOLA');
                            
                            // Enable or disable claim button based on pending amount
                            if (parseFloat(response.data.total_pending) > 0) {
                                $('.vortex-claim-rewards-btn').prop('disabled', false);
                            } else {
                                $('.vortex-claim-rewards-btn').prop('disabled', true);
                            }
                        }
                    }
                });
            }, 120000); // 2 minutes
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        VortexDAORewards.init();
    });
    
})(jQuery); 