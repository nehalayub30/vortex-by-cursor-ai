                        ${rewardsBreakdownHtml || '<div class="no-rewards">No rewards earned yet</div>'}
                    </div>
                </div>
                
                <div class="rewards-actions">
                    <h3>Available Actions</h3>
                    <div class="action-buttons">
                        <button class="vortex-btn stake-tokens-btn">Stake TOLA Tokens</button>
                        <button class="vortex-btn claim-rewards-btn">Claim Pending Rewards</button>
                    </div>
                    <p class="rewards-note">Staking TOLA tokens increases your voting power in governance proposals.</p>
                </div>
            `;
            
            $rewardsEl.html(html);
            
            // Add event listeners for reward actions
            $('.stake-tokens-btn').on('click', this.showStakingInterface.bind(this));
            $('.claim-rewards-btn').on('click', this.claimPendingRewards.bind(this));
        },
        
        /**
         * Show staking interface
         */
        showStakingInterface: function() {
            // In a real implementation, this would show a staking modal
            this.showNotification('Staking functionality is not yet implemented', 'info');
        },
        
        /**
         * Claim pending rewards
         */
        claimPendingRewards: function() {
            // In a real implementation, this would interact with the blockchain
            this.showNotification('Claim functionality is not yet implemented', 'info');
        },
        
        /**
         * Show a notification message
         */
        showNotification: function(message, type = 'info') {
            // Check if notification container exists
            let $container = $('.vortex-notifications');
            
            if (!$container.length) {
                $container = $('<div class="vortex-notifications"></div>');
                $('body').append($container);
            }
            
            // Create notification element
            const $notification = $(`
                <div class="vortex-notification ${type}">
                    <div class="notification-message">${message}</div>
                    <button class="notification-close">&times;</button>
                </div>
            `);
            
            // Add to container
            $container.append($notification);
            
            // Animate in
            setTimeout(() => {
                $notification.addClass('show');
            }, 10);
            
            // Set auto-dismiss
            setTimeout(() => {
                $notification.removeClass('show');
                setTimeout(() => {
                    $notification.remove();
                }, 300);
            }, 5000);
            
            // Add close button handler
            $notification.find('.notification-close').on('click', function() {
                $notification.removeClass('show');
                setTimeout(() => {
                    $notification.remove();
                }, 300);
            });
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        VortexDAO.init();
    });
    
})(jQuery); 