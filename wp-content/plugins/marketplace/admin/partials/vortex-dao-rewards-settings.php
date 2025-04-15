                <div class="vortex-settings-section">
                    <h3>Governance & Engagement Rewards</h3>
                    <div class="vortex-settings-grid">
                        <div class="vortex-setting-item">
                            <label for="proposal_creation_reward">Proposal Creation</label>
                            <div class="vortex-input-group">
                                <input type="number" id="proposal_creation_reward" name="proposal_creation_reward" value="<?php echo esc_attr($proposal_creation_reward); ?>" step="1" min="0">
                                <span class="vortex-input-suffix">TOLA</span>
                            </div>
                        </div>
                        
                        <div class="vortex-setting-item">
                            <label for="vote_reward">Voting on Proposals</label>
                            <div class="vortex-input-group">
                                <input type="number" id="vote_reward" name="vote_reward" value="<?php echo esc_attr($vote_reward); ?>" step="0.1" min="0">
                                <span class="vortex-input-suffix">TOLA</span>
                            </div>
                        </div>
                        
                        <div class="vortex-setting-item">
                            <label for="comment_reward">Comment Reward</label>
                            <div class="vortex-input-group">
                                <input type="number" id="comment_reward" name="comment_reward" value="<?php echo esc_attr($comment_reward); ?>" step="0.01" min="0">
                                <span class="vortex-input-suffix">TOLA</span>
                            </div>
                        </div>
                        
                        <div class="vortex-setting-item">
                            <label for="like_reward">Like Reward</label>
                            <div class="vortex-input-group">
                                <input type="number" id="like_reward" name="like_reward" value="<?php echo esc_attr($like_reward); ?>" step="0.01" min="0">
                                <span class="vortex-input-suffix">TOLA</span>
                            </div>
                        </div>
                        
                        <div class="vortex-setting-item">
                            <label for="max_daily_engagement_reward">Max Daily Engagement Reward</label>
                            <div class="vortex-input-group">
                                <input type="number" id="max_daily_engagement_reward" name="max_daily_engagement_reward" value="<?php echo esc_attr($max_daily_engagement_reward); ?>" step="1" min="0">
                                <span class="vortex-input-suffix">TOLA</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="vortex-settings-section">
                    <h3>Listing & Referral Rewards</h3>
                    <div class="vortex-settings-grid">
                        <div class="vortex-setting-item">
                            <label for="listing_reward">New Artwork Listing</label>
                            <div class="vortex-input-group">
                                <input type="number" id="listing_reward" name="listing_reward" value="<?php echo esc_attr($listing_reward); ?>" step="0.1" min="0">
                                <span class="vortex-input-suffix">TOLA</span>
                            </div>
                        </div>
                        
                        <div class="vortex-setting-item">
                            <label for="max_daily_listing_reward">Max Daily Listing Reward</label>
                            <div class="vortex-input-group">
                                <input type="number" id="max_daily_listing_reward" name="max_daily_listing_reward" value="<?php echo esc_attr($max_daily_listing_reward); ?>" step="1" min="0">
                                <span class="vortex-input-suffix">TOLA</span>
                            </div>
                        </div>
                        
                        <div class="vortex-setting-item">
                            <label for="referral_reward">Successful Referral</label>
                            <div class="vortex-input-group">
                                <input type="number" id="referral_reward" name="referral_reward" value="<?php echo esc_attr($referral_reward); ?>" step="1" min="0">
                                <span class="vortex-input-suffix">TOLA</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="vortex-admin-analytics">
                    <h3>Rewards Analytics</h3>
                    
                    <div class="vortex-analytics-grid">
                        <div class="vortex-analytics-card">
                            <h4>Total Rewards Distributed</h4>
                            <div class="vortex-analytics-value">
                                <?php 
                                    global $wpdb;
                                    $total_claimed = $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}vortex_dao_rewards WHERE status = 'claimed'");
                                    echo number_format($total_claimed ?? 0, 2) . ' TOLA';
                                ?>
                            </div>
                        </div>
                        
                        <div class="vortex-analytics-card">
                            <h4>Pending Rewards</h4>
                            <div class="vortex-analytics-value">
                                <?php 
                                    $total_pending = $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}vortex_dao_rewards WHERE status = 'pending'");
                                    echo number_format($total_pending ?? 0, 2) . ' TOLA';
                                ?>
                            </div>
                        </div>
                        
                        <div class="vortex-analytics-card">
                            <h4>Unique Reward Recipients</h4>
                            <div class="vortex-analytics-value">
                                <?php 
                                    $unique_recipients = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}vortex_dao_rewards");
                                    echo number_format($unique_recipients ?? 0);
                                ?>
                            </div>
                        </div>
                        
                        <div class="vortex-analytics-card">
                            <h4>Top Reward Type</h4>
                            <div class="vortex-analytics-value">
                                <?php 
                                    $top_reward_type = $wpdb->get_var("
                                        SELECT reward_type 
                                        FROM {$wpdb->prefix}vortex_dao_rewards 
                                        GROUP BY reward_type 
                                        ORDER BY SUM(amount) DESC 
                                        LIMIT 1
                                    ");
                                    
                                    $reward_type_labels = array(
                                        'sale' => 'Artwork Sales',
                                        'purchase' => 'Artwork Purchases',
                                        'promotion' => 'Tier Promotions',
                                        'governance' => 'Governance',
                                        'referral' => 'Referrals',
                                        'engagement' => 'Engagement',
                                        'listing' => 'Artwork Listings',
                                        'bonus' => 'Bonuses'
                                    );
                                    
                                    echo $reward_type_labels[$top_reward_type] ?? ucfirst($top_reward_type ?? 'None');
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="vortex-reward-distribution-chart">
                        <h4>Reward Distribution by Type</h4>
                        <div id="rewardDistributionChart" style="height: 300px;"></div>
                    </div>
                </div>
                
                <div class="vortex-settings-actions">
                    <button type="submit" name="vortex_save_reward_settings" class="vortex-primary-button">
                        Save Reward Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="vortex-admin-card">
        <div class="vortex-admin-card-header">
            <h2>Recent Reward Claims</h2>
            <p>View the most recent reward claims from users.</p>
        </div>
        
        <div class="vortex-admin-card-body">
            <div class="vortex-recent-claims-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Amount</th>
                            <th>Reward Type</th>
                            <th>Claimed At</th>
                            <th>Wallet</th>
                            <th>Transaction</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_claims = $wpdb->get_results("
                            SELECT r.*, u.display_name 
                            FROM {$wpdb->prefix}vortex_dao_rewards r
                            JOIN {$wpdb->users} u ON r.user_id = u.ID
                            WHERE r.status = 'claimed' 
                            ORDER BY r.claimed_at DESC 
                            LIMIT 10
                        ");
                        
                        if (empty($recent_claims)) {
                            echo '<tr><td colspan="6">No reward claims found.</td></tr>';
                        } else {
                            foreach ($recent_claims as $claim) {
                                ?>
                                <tr>
                                    <td><?php echo esc_html($claim->display_name); ?></td>
                                    <td><?php echo number_format($claim->amount, 2) . ' TOLA'; ?></td>
                                    <td><?php 
                                        $labels = array(
                                            'sale' => 'Artwork Sale',
                                            'purchase' => 'Artwork Purchase',
                                            'promotion' => 'Tier Promotion',
                                            'governance' => 'Governance',
                                            'referral' => 'Referral',
                                            'engagement' => 'Engagement',
                                            'listing' => 'Artwork Listing',
                                            'bonus' => 'Bonus'
                                        );
                                        echo esc_html($labels[$claim->reward_type] ?? ucfirst($claim->reward_type));
                                    ?></td>
                                    <td><?php echo date('M j, Y g:i a', strtotime($claim->claimed_at)); ?></td>
                                    <td><?php 
                                        $shortened_wallet = substr($claim->wallet_address, 0, 6) . '...' . substr($claim->wallet_address, -4);
                                        echo esc_html($shortened_wallet); 
                                    ?></td>
                                    <td>
                                        <?php if ($claim->transaction_signature): ?>
                                            <a href="https://solscan.io/tx/<?php echo esc_attr($claim->transaction_signature); ?>" target="_blank" class="transaction-link">
                                                <?php echo substr($claim->transaction_signature, 0, 10) . '...'; ?>
                                                <span class="dashicons dashicons-external"></span>
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <div class="vortex-view-all-link">
                <a href="<?php echo admin_url('admin.php?page=vortex-dao-rewards-log'); ?>" class="button">View All Reward Claims</a>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize charts if the Chart.js library is loaded
    if (typeof Chart !== 'undefined') {
        // Fetch reward distribution data
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_get_reward_distribution',
                nonce: '<?php echo wp_create_nonce('vortex_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    createRewardDistributionChart(response.data);
                }
            }
        });
        
        function createRewardDistributionChart(data) {
            const ctx = document.getElementById('rewardDistributionChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.values,
                        backgroundColor: [
                            '#4e54c8', '#6a5acd', '#8a2be2', '#9370db', 
                            '#ba55d3', '#da70d6', '#ee82ee', '#ff00ff'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 20,
                            boxWidth: 10
                        }
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltip, data) {
                                const label = data.labels[tooltip.index];
                                const value = data.datasets[0].data[tooltip.index];
                                const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value.toFixed(2)} TOLA (${percentage}%)`;
                            }
                        }
                    }
                }
            });
        }
    }
});
</script>

<style type="text/css">
/* Admin UI Styles */
.vortex-admin-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
    overflow: hidden;
}

.vortex-admin-card-header {
    padding: 20px;
    border-bottom: 1px solid #f0f0f0;
}

.vortex-admin-card-header h2 {
    margin: 0 0 10px;
    color: #23282d;
}

.vortex-admin-card-header p {
    margin: 0;
    color: #757575;
}

.vortex-admin-card-body {
    padding: 20px;
}

.vortex-settings-section {
    margin-bottom: 30px;
}

.vortex-settings-section h3 {
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 10px;
    margin-bottom: 15px;
    color: #23282d;
}

.vortex-settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.vortex-setting-item {
    margin-bottom: 15px;
}

.vortex-setting-item label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
}

.vortex-input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.vortex-input-group input {
    flex: 1;
    height: 36px;
    padding-right: 45px;
}

.vortex-input-suffix {
    position: absolute;
    right: 10px;
    color: #757575;
    font-weight: 500;
}

.vortex-admin-analytics {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #f0f0f0;
}

.vortex-analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.vortex-analytics-card {
    background: #f9fafb;
    border-radius: 6px;
    padding: 15px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
}

.vortex-analytics-card h4 {
    margin: 0 0 10px;
    font-size: 14px;
    color: #757575;
}

.vortex-analytics-value {
    font-size: 20px;
    font-weight: 600;
    color: #23282d;
}

.vortex-reward-distribution-chart {
    margin-top: 20px;
    border: 1px solid #f0f0f0;
    border-radius: 6px;
    padding: 15px;
}

.vortex-primary-button {
    background: linear-gradient(135deg, #4e54c8, #8f94fb);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
}

.vortex-primary-button:hover {
    background: linear-gradient(135deg, #3a3f9c, #7a7fe0);
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(78, 84, 200, 0.3);
}

.vortex-view-all-link {
    margin-top: 15px;
    text-align: right;
}

.transaction-link {
    color: #4e54c8;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}

.transaction-link .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    margin-left: 2px;
}

@media screen and (max-width: 782px) {
    .vortex-settings-grid,
    .vortex-analytics-grid {
        grid-template-columns: 1fr;
    }
}
</style> 