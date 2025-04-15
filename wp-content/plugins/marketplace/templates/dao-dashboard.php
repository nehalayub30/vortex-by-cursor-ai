                    <li class="benefit-item"><?php esc_attr_e('Voting power multiplier', 'vortex'); ?>: <strong>x${data.level * 0.5}</strong></li>
                    <li class="benefit-item"><?php esc_attr_e('Reward bonus', 'vortex'); ?>: <strong>+${(data.level - 1) * 10}%</strong></li>
                    <li class="benefit-item"><?php esc_attr_e('Proposal creation rights', 'vortex'); ?>: <strong>${data.level >= 3 ? '<?php esc_attr_e("Enabled", "vortex"); ?>' : '<?php esc_attr_e("Requires Level 3", "vortex"); ?>'}</strong></li>
                    <li class="benefit-item"><?php esc_attr_e('Treasury allocation proposals', 'vortex'); ?>: <strong>${data.level >= 5 ? '<?php esc_attr_e("Enabled", "vortex"); ?>' : '<?php esc_attr_e("Requires Level 5", "vortex"); ?>'}</strong></li>
                </ul>
            </div>
        `;
        
        $('.vortex-reputation-dashboard').html(html);
    }
    
    function renderGovernanceInterface(data) {
        let html = `
            <div class="governance-header">
                <h2><?php esc_attr_e('VORTEX DAO Governance', 'vortex'); ?></h2>
                <p>${data.active && data.active.length > 0 ? 
                    '<?php esc_attr_e('There are', 'vortex'); ?> ' + data.active.length + ' <?php esc_attr_e('active proposals requiring your vote', 'vortex'); ?>' : 
                    '<?php esc_attr_e('No active proposals require your vote at this time', 'vortex'); ?>'}</p>
            </div>
            
            <div class="governance-stats">
                <div class="stat-card total-voting-power">
                    <div class="stat-value">${window.vortexDashboardData.reputation ? window.vortexDashboardData.reputation.level * 10 : 0}%</div>
                    <div class="stat-label"><?php esc_attr_e('Your Voting Power', 'vortex'); ?></div>
                </div>
                <div class="stat-card proposals-voted">
                    <div class="stat-value">${getRandomInt(1, 15)}</div>
                    <div class="stat-label"><?php esc_attr_e('Proposals Voted', 'vortex'); ?></div>
                </div>
                <div class="stat-card proposals-created">
                    <div class="stat-value">${getRandomInt(0, 5)}</div>
                    <div class="stat-label"><?php esc_attr_e('Proposals Created', 'vortex'); ?></div>
                </div>
            </div>
            
            <div class="governance-create-proposal">
                <h3><?php esc_attr_e('Create New Proposal', 'vortex'); ?></h3>
                <div class="proposal-form">
                    <div class="form-group">
                        <label for="proposal-type"><?php esc_attr_e('Proposal Type', 'vortex'); ?></label>
                        <select id="proposal-type" class="proposal-type-select">
                            <option value="feature"><?php esc_attr_e('Feature Suggestion', 'vortex'); ?></option>
                            <option value="treasury"><?php esc_attr_e('Treasury Allocation', 'vortex'); ?></option>
                            <option value="parameter"><?php esc_attr_e('Parameter Change', 'vortex'); ?></option>
                            <option value="curation"><?php esc_attr_e('Content Curation', 'vortex'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="proposal-title"><?php esc_attr_e('Title', 'vortex'); ?></label>
                        <input type="text" id="proposal-title" placeholder="<?php esc_attr_e('Enter proposal title', 'vortex'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="proposal-description"><?php esc_attr_e('Description', 'vortex'); ?></label>
                        <textarea id="proposal-description" placeholder="<?php esc_attr_e('Describe your proposal', 'vortex'); ?>"></textarea>
                    </div>
                    
                    <div class="treasury-fields" style="display: none;">
                        <div class="form-group">
                            <label for="treasury-amount"><?php esc_attr_e('Amount (TOLA)', 'vortex'); ?></label>
                            <input type="number" id="treasury-amount" placeholder="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="treasury-recipient"><?php esc_attr_e('Recipient Address', 'vortex'); ?></label>
                            <input type="text" id="treasury-recipient" placeholder="0x...">
                        </div>
                    </div>
                    
                    <button id="vortex-create-proposal-btn" class="vortex-btn"><?php esc_attr_e('Create Proposal', 'vortex'); ?></button>
                </div>
            </div>
            
            <div class="governance-proposals">
                <h3><?php esc_attr_e('Active Proposals', 'vortex'); ?></h3>
                <div class="proposal-list active-proposals">
                    ${renderProposalList(data.active || [])}
                </div>
                
                <h3><?php esc_attr_e('Pending Proposals', 'vortex'); ?></h3>
                <div class="proposal-list pending-proposals">
                    ${renderProposalList(data.pending || [])}
                </div>
                
                <h3><?php esc_attr_e('Recently Executed', 'vortex'); ?></h3>
                <div class="proposal-list executed-proposals">
                    ${renderProposalList(data.executed || [])}
                </div>
            </div>
        `;
        
        $('.vortex-governance-tab').html(html);
        
        // Add event listeners
        $('.proposal-type-select').on('change', function() {
            const proposalType = $(this).val();
            
            if (proposalType === 'treasury') {
                $('.treasury-fields').show();
            } else {
                $('.treasury-fields').hide();
            }
        });
        
        $('#vortex-create-proposal-btn').on('click', createProposal);
    }
    
    function renderProposalList(proposals) {
        if (!proposals || !proposals.length) {
            return '<div class="no-proposals"><?php esc_attr_e("No proposals to display", "vortex"); ?></div>';
        }
        
        let html = '';
        
        proposals.forEach((proposal) => {
            html += `
                <div class="proposal-card" data-id="${proposal.id}">
                    <div class="proposal-header">
                        <h4>${proposal.title}</h4>
                        <span class="proposal-type proposal-type-${proposal.type.toLowerCase()}">${proposal.type}</span>
                    </div>
                    <div class="proposal-body">
                        <p>${proposal.description}</p>
                    </div>
                    <div class="proposal-footer">
                        <div class="proposal-stats">
                            <div class="proposal-votes-bar">
                                <div class="votes-for" style="width: ${proposal.votes_for_percent}%"></div>
                                <div class="votes-against" style="width: ${proposal.votes_against_percent}%"></div>
                            </div>
                            <div class="proposal-votes-text">
                                <span class="votes-for-text">${proposal.votes_for} <?php esc_attr_e("For", "vortex"); ?> (${proposal.votes_for_percent}%)</span>
                                <span class="votes-against-text">${proposal.votes_against} <?php esc_attr_e("Against", "vortex"); ?> (${proposal.votes_against_percent}%)</span>
                            </div>
                        </div>
                        <div class="proposal-actions">
                            <button class="vote-btn vote-for" data-vote="1" data-id="${proposal.id}"><?php esc_attr_e("Vote For", "vortex"); ?></button>
                            <button class="vote-btn vote-against" data-vote="0" data-id="${proposal.id}"><?php esc_attr_e("Vote Against", "vortex"); ?></button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        return html;
    }
    
    function renderRewardHistory(data) {
        // Format reward types for display
        const rewardLabels = {
            contribution_based: '<?php esc_attr_e("Contribution Rewards", "vortex"); ?>',
            achievement_based: '<?php esc_attr_e("Achievement Rewards", "vortex"); ?>',
            daily_activity: '<?php esc_attr_e("Daily Activity", "vortex"); ?>',
            content_creation: '<?php esc_attr_e("Content Creation", "vortex"); ?>',
            marketplace_activity: '<?php esc_attr_e("Marketplace Activity", "vortex"); ?>',
            governance_activity: '<?php esc_attr_e("Governance Participation", "vortex"); ?>',
            ai_collaboration: '<?php esc_attr_e("AI Collaboration", "vortex"); ?>',
            custom_challenge: '<?php esc_attr_e("Custom Challenges", "vortex"); ?>'
        };
        
        let rewardsBreakdownHtml = '';
        
        for (const [typeName, amount] of Object.entries(data.rewards_by_type)) {
            if (amount > 0) {
                const label = rewardLabels[typeName] || typeName;
                rewardsBreakdownHtml += `
                    <div class="reward-type">
                        <span class="reward-label">${label}</span>
                        <span class="reward-amount">${amount} TOLA</span>
                    </div>
                `;
            }
        }
        
        // Render rewards dashboard
        let html = `
            <div class="rewards-header">
                <div class="total-rewards">
                    <h3><?php esc_attr_e("Total Rewards Earned", "vortex"); ?></h3>
                    <div class="reward-amount-large">${data.total_rewards} TOLA</div>
                </div>
            </div>
            
            <div class="rewards-breakdown">
                <h3><?php esc_attr_e("Rewards Breakdown", "vortex"); ?></h3>
                <div class="rewards-by-type">
                    ${rewardsBreakdownHtml || '<div class="no-rewards"><?php esc_attr_e("No rewards earned yet", "vortex"); ?></div>'}
                </div>
            </div>
            
            <div class="rewards-claim-section">
                <h3><?php esc_attr_e("Claim Rewards", "vortex"); ?></h3>
                <div class="claim-card">
                    <div class="claim-info">
                        <div class="pending-rewards">
                            <span class="pending-amount">25</span> TOLA
                        </div>
                        <div class="pending-label"><?php esc_attr_e("Pending Rewards", "vortex"); ?></div>
                    </div>
                    <button class="vortex-btn claim-rewards-btn"><?php esc_attr_e("Claim Rewards", "vortex"); ?></button>
                </div>
            </div>
            
            <div class="rewards-actions">
                <h3><?php esc_attr_e("Available Actions", "vortex"); ?></h3>
                <div class="action-buttons">
                    <button class="vortex-btn stake-tokens-btn"><?php esc_attr_e("Stake TOLA Tokens", "vortex"); ?></button>
                    <button class="vortex-btn boost-rewards-btn"><?php esc_attr_e("Boost Rewards", "vortex"); ?></button>
                </div>
                <p class="rewards-note"><?php esc_attr_e("Staking TOLA tokens increases your voting power in governance proposals and boosts your reward earning rate.", "vortex"); ?></p>
            </div>
            
            <div class="rewards-history">
                <h3><?php esc_attr_e("Recent Reward History", "vortex"); ?></h3>
                <table class="rewards-table">
                    <thead>
                        <tr>
                            <th><?php esc_attr_e("Date", "vortex"); ?></th>
                            <th><?php esc_attr_e("Activity", "vortex"); ?></th>
                            <th><?php esc_attr_e("Amount", "vortex"); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>2023-06-15</td>
                            <td><?php esc_attr_e("Content Creation", "vortex"); ?></td>
                            <td>10 TOLA</td>
                        </tr>
                        <tr>
                            <td>2023-06-10</td>
                            <td><?php esc_attr_e("Achievement Reward", "vortex"); ?></td>
                            <td>25 TOLA</td>
                        </tr>
                        <tr>
                            <td>2023-06-05</td>
                            <td><?php esc_attr_e("Governance Participation", "vortex"); ?></td>
                            <td>5 TOLA</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        `;
        
        $('.vortex-rewards-tab').html(html);
        
        // Add event listeners
        $('.stake-tokens-btn').on('click', showStakingInterface);
        $('.claim-rewards-btn').on('click', claimPendingRewards);
        $('.boost-rewards-btn').on('click', showBoostInterface);
    }
    
    function showAchievementDetail(achievementId) {
        const achievement = window.vortexDashboardData.achievements.find(a => a.id == achievementId);
        
        if (!achievement) {
            return;
        }
        
        const html = `
            <div class="achievement-detail">
                <div class="achievement-detail-image">
                    <img src="${achievement.image}" alt="${achievement.name}">
                </div>
                <div class="achievement-detail-info">
                    <h2>${achievement.name}</h2>
                    <p class="achievement-description">${achievement.description}</p>
                    <div class="achievement-meta">
                        <div class="meta-item">
                            <span class="meta-label"><?php esc_attr_e("Earned", "vortex"); ?></span>
                            <span class="meta-value">${achievement.earned_at || '<?php esc_attr_e("Unknown", "vortex"); ?>'}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label"><?php esc_attr_e("Type", "vortex"); ?></span>
                            <span class="meta-value">${getAchievementTypeName(achievement.type_id)}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label"><?php esc_attr_e("Rarity", "vortex"); ?></span>
                            <span class="meta-value">${achievement.rarity || '<?php esc_attr_e("Common", "vortex"); ?>'}</span>
                        </div>
                    </div>
                    <div class="achievement-benefits">
                        <h3><?php esc_attr_e("Benefits", "vortex"); ?></h3>
                        <ul>
                            <li><?php esc_attr_e("Reputation Points", "vortex"); ?>: +${achievement.points || 100}</li>
                            <li><?php esc_attr_e("Voting Power Boost", "vortex"); ?>: +${achievement.voting_boost || 5}%</li>
                            ${achievement.special_access ? `<li><?php esc_attr_e("Special Access", "vortex"); ?>: ${achievement.special_access}</li>` : ''}
                        </ul>
                    </div>
                    <div class="achievement-actions">
                        <button class="vortex-btn share-achievement-btn" data-id="${achievement.id}"><?php esc_attr_e("Share Achievement", "vortex"); ?></button>
                    </div>
                </div>
            </div>
        `;
        
        $('.achievement-detail-content').html(html);
        $('#achievement-detail-modal').addClass('show');
        
        // Add share button handler
        $('.share-achievement-btn').on('click', function() {
            const achievementId = $(this).data('id');
            shareAchievement(achievementId);
        });
    }
    
    function getAchievementTypeName(typeId) {
        const types = {
            0: '<?php esc_attr_e("General", "vortex"); ?>',
            1: '<?php esc_attr_e("Creation", "vortex"); ?>',
            2: '<?php esc_attr_e("Curation", "vortex"); ?>',
            3: '<?php esc_attr_e("Community", "vortex"); ?>',
            4: '<?php esc_attr_e("Governance", "vortex"); ?>',
            5: '<?php esc_attr_e("Marketplace", "vortex"); ?>',
            6: '<?php esc_attr_e("AI Collaboration", "vortex"); ?>',
            7: '<?php esc_attr_e("Security", "vortex"); ?>'
        };
        
        return types[typeId] || '<?php esc_attr_e("Unknown", "vortex"); ?>';
    }
    
    function shareAchievement(achievementId) {
        const achievement = window.vortexDashboardData.achievements.find(a => a.id == achievementId);
        
        if (!achievement) {
            return;
        }
        
        // Create share data
        const shareData = {
            title: '<?php esc_attr_e("I earned an achievement on VORTEX!", "vortex"); ?>',
            text: '<?php esc_attr_e("I just earned the", "vortex"); ?> "' + achievement.name + '" <?php esc_attr_e("achievement on VORTEX AI Marketplace!", "vortex"); ?>',
            url: window.location.href
        };
        
        // Use Web Share API if available
        if (navigator.share) {
            navigator.share(shareData)
                .then(() => showNotification('<?php esc_attr_e("Achievement shared successfully!", "vortex"); ?>', 'success'))
                .catch(error => console.log('Error sharing:', error));
        } else {
            // Fallback for browsers that don't support Web Share API
            const shareUrl = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(shareData.text + ' ' + shareData.url);
            window.open(shareUrl, '_blank');
        }
    }
    
    function createProposal() {
        const proposalType = $('#proposal-type').val();
        const title = $('#proposal-title').val().trim();
        const description = $('#proposal-description').val().trim();
        
        if (!title || !description) {
            showNotification('<?php esc_attr_e("Please fill in all required fields", "vortex"); ?>', 'error');
            return;
        }
        
        // Additional data for treasury proposals
        let extraData = {};
        if (proposalType === 'treasury') {
            const amount = $('#treasury-amount').val();
            const recipient = $('#treasury-recipient').val().trim();
            
            if (!amount || !recipient) {
                showNotification('<?php esc_attr_e("Please fill in all treasury fields", "vortex"); ?>', 'error');
                return;
            }
            
            extraData = { amount, recipient };
        }
        
        // In a real implementation, this would communicate with the blockchain
        // For now, show a success message
        showNotification('<?php esc_attr_e("Your proposal has been submitted for review", "vortex"); ?>', 'success');
        
        // Reset form
        $('#proposal-title').val('');
        $('#proposal-description').val('');
        $('#treasury-amount').val('');
        $('#treasury-recipient').val('');
    }
    
    function showStakingInterface() {
        // In a real implementation, this would show a staking modal
        showNotification('<?php esc_attr_e("Staking functionality coming soon!", "vortex"); ?>', 'info');
    }
    
    function showBoostInterface() {
        // In a real implementation, this would show a rewards boost modal
        showNotification('<?php esc_attr_e("Rewards boost functionality coming soon!", "vortex"); ?>', 'info');
    }
    
    function claimPendingRewards() {
        // In a real implementation, this would interact with the blockchain
        showNotification('<?php esc_attr_e("Processing your reward claim...", "vortex"); ?>', 'info');
        
        // Simulate a delay
        setTimeout(() => {
            showNotification('<?php esc_attr_e("Successfully claimed 25 TOLA tokens!", "vortex"); ?>', 'success');
            $('.pending-amount').text('0');
        }, 2000);
    }
    
    function showNotification(message, type = 'info') {
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
    
    // Helper functions
    function getRandomInt(min, max) {
        min = Math.ceil(min);
        max = Math.floor(max);
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }
});
</script>

<?php get_footer(); ?> 