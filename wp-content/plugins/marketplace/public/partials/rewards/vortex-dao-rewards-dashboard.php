<?php
/**
 * Template for DAO Rewards Dashboard
 *
 * @link       https://vortexmarketplace.io
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Generate nonce
$nonce = wp_create_nonce('vortex_rewards_nonce');

?>
<div class="vortex-dao-rewards-dashboard">
    
    <div class="rewards-header">
        <h2>Your TOLA Rewards</h2>
        <div class="rewards-summary">
            <div class="total-pending">
                <span class="label">Pending Rewards:</span>
                <span class="value"><?php echo number_format($total_pending, 2); ?> TOLA</span>
            </div>
            <?php if ($atts['show_claim'] === 'true' && $total_pending > 0) : ?>
            <div class="claim-rewards">
                <button class="vortex-claim-rewards-btn" data-toggle="modal" data-target="#rewardsClaimModal">Claim Rewards</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (empty($rewards)) : ?>
    <div class="no-rewards">
        <p>You don't have any rewards yet. Participate in the VORTEX marketplace to earn TOLA rewards!</p>
        <ul class="reward-opportunities">
            <li>List your artwork in the marketplace</li>
            <li>Engage with other artists by commenting and liking their work</li>
            <li>Participate in DAO governance by voting on proposals</li>
            <li>Refer new artists to the platform</li>
            <li>Make sales and purchase artwork</li>
        </ul>
    </div>
    <?php else : ?>
    <div class="rewards-table-wrapper">
        <table class="rewards-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Description</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($rewards, 0, $atts['limit']) as $reward) : ?>
                <tr class="reward-row">
                    <td class="reward-type"><?php echo esc_html($reward->type_label); ?></td>
                    <td class="reward-amount"><?php echo esc_html($reward->amount_formatted); ?> TOLA</td>
                    <td class="reward-description"><?php echo esc_html($reward->description); ?></td>
                    <td class="reward-date"><?php echo esc_html($reward->created_at_formatted); ?></td>
                    <td class="reward-status">
                        <span class="status-badge <?php echo esc_attr($reward->status_class); ?>">
                            <?php echo esc_html($reward->status_label); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <?php if (count($rewards) > $atts['limit']) : ?>
    <div class="view-more">
        <a href="<?php echo esc_url(get_permalink(get_option('vortex_rewards_page_id'))); ?>" class="view-all-rewards-btn">View All Rewards</a>
    </div>
    <?php endif; ?>
    
    <?php if ($atts['show_claim'] === 'true' && $total_pending > 0) : ?>
    <!-- Claim Rewards Modal -->
    <div class="modal fade" id="rewardsClaimModal" tabindex="-1" role="dialog" aria-labelledby="rewardsClaimModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rewardsClaimModalLabel">Claim TOLA Rewards</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>You have <strong><?php echo number_format($total_pending, 2); ?> TOLA</strong> in pending rewards.</p>
                    
                    <form id="claimRewardsForm">
                        <div class="form-group">
                            <label for="rewardWalletSelect">Select wallet to receive rewards:</label>
                            <select class="form-control" id="rewardWalletSelect" name="wallet_address" required>
                                <option value="">-- Select Wallet --</option>
                                <?php foreach ($wallets as $wallet) : ?>
                                    <option value="<?php echo esc_attr($wallet->wallet_address); ?>">
                                        <?php echo esc_html(substr($wallet->wallet_address, 0, 6) . '...' . substr($wallet->wallet_address, -4)); ?>
                                        <?php if ($wallet->is_primary) echo ' (Primary)'; ?>
                                        - Balance: <?php echo number_format($wallet->token_balance, 2); ?> TOLA
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($wallets)) : ?>
                                <p class="text-danger mt-2">No verified wallets found. Please <a href="<?php echo esc_url(get_permalink(get_option('vortex_wallet_page_id'))); ?>">connect and verify a wallet</a> first.</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="rewardTypeSelect">Reward type to claim:</label>
                            <select class="form-control" id="rewardTypeSelect" name="reward_type">
                                <option value="all">All Rewards</option>
                                <option value="sale">Artwork Sales</option>
                                <option value="purchase">Artwork Purchases</option>
                                <option value="promotion">Tier Promotions</option>
                                <option value="governance">Governance Participation</option>
                                <option value="referral">Referrals</option>
                                <option value="engagement">Marketplace Engagement</option>
                                <option value="listing">Artwork Listings</option>
                                <option value="bonus">Special Bonuses</option>
                            </select>
                        </div>
                        
                        <input type="hidden" name="action" value="vortex_claim_rewards">
                        <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
                        
                        <div class="alert alert-info">
                            <p><strong>Note:</strong> Claiming rewards will transfer TOLA tokens to your selected wallet on the Solana blockchain. This transaction cannot be reversed.</p>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitClaimRewards" <?php echo empty($wallets) ? 'disabled' : ''; ?>>Claim Rewards</button>
                </div>
                <div class="claim-status" style="display:none;"></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Handle reward claim submission
        $('#submitClaimRewards').on('click', function() {
            var form = $('#claimRewardsForm');
            var statusDiv = $('.claim-status');
            
            // Validate form
            if (!form.find('[name="wallet_address"]').val()) {
                statusDiv.html('<div class="alert alert-danger">Please select a wallet address.</div>').show();
                return;
            }
            
            // Show loading
            $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
            statusDiv.html('<div class="alert alert-info">Processing your claim. Please wait...</div>').show();
            
            // Submit claim
            $.ajax({
                url: vortex_ajax.ajax_url,
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        statusDiv.html('<div class="alert alert-success">' + response.data.message + '</div>');
                        
                        // Show transaction details
                        if (response.data.transaction_signature) {
                            var explorerUrl = 'https://solscan.io/tx/' + response.data.transaction_signature;
                            statusDiv.append('<div class="transaction-details mt-3">' +
                                '<p>Transaction Signature:</p>' +
                                '<a href="' + explorerUrl + '" target="_blank" class="transaction-link">' + 
                                response.data.transaction_signature + ' <i class="fas fa-external-link-alt"></i></a>' +
                            '</div>');
                        }
                        
                        // Update UI after successful claim
                        setTimeout(function() {
                            location.reload();
                        }, 5000);
                    } else {
                        statusDiv.html('<div class="alert alert-danger">' + response.data.message + '</div>');
                        $('#submitClaimRewards').prop('disabled', false).text('Claim Rewards');
                    }
                },
                error: function() {
                    statusDiv.html('<div class="alert alert-danger">An error occurred. Please try again.</div>');
                    $('#submitClaimRewards').prop('disabled', false).text('Claim Rewards');
                }
            });
        });
    });
    </script>
</div> 