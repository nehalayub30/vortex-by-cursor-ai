<?php
/**
 * Public template for DAO governance dashboard
 *
 * @link       https://vortexmarketplace.io
 * @since      1.0.0
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Get proposal status
$status = isset($status) ? $status : 'active';

// Get proposals
$proposals = array();
if (isset($this) && method_exists($this, 'get_proposals')) {
    $proposals = $this->get_proposals($status, 10, 0);
}

// Get governance parameters
$min_proposal_tokens = get_option('vortex_dao_min_proposal_tokens', 1000);
$quorum_percentage = get_option('vortex_dao_quorum_percentage', 15);
$proposal_threshold = get_option('vortex_dao_proposal_threshold', 51);
$voting_period_days = get_option('vortex_dao_voting_period_days', 7);

// Get current user wallet if logged in
$user_wallet = '';
$can_create_proposal = false;
$user_has_voted = array();

if (is_user_logged_in()) {
    global $wpdb;
    $user_id = get_current_user_id();
    
    // Get user's primary wallet
    $wallet = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vortex_wallet_addresses 
        WHERE user_id = %d AND verified = 1 
        ORDER BY is_primary DESC, id ASC 
        LIMIT 1",
        $user_id
    ));
    
    if ($wallet) {
        $user_wallet = $wallet->wallet_address;
        
        // Check if user has tokens to create proposal
        if (isset($this) && method_exists($this->token, 'has_minimum_tokens')) {
            $can_create_proposal = $this->token->has_minimum_tokens($user_wallet, $min_proposal_tokens);
        }
        
        // Check which proposals user has voted on
        if (!empty($proposals)) {
            $proposal_ids = array_map(function($p) { return $p->id; }, $proposals);
            $proposal_ids_str = implode(',', $proposal_ids);
            
            if (!empty($proposal_ids_str)) {
                $votes = $wpdb->get_results($wpdb->prepare(
                    "SELECT proposal_id, vote FROM {$wpdb->prefix}vortex_dao_votes 
                    WHERE proposal_id IN ($proposal_ids_str) AND wallet_address = %s",
                    $user_wallet
                ));
                
                foreach ($votes as $vote) {
                    $user_has_voted[$vote->proposal_id] = $vote->vote;
                }
            }
        }
    }
}
?>

<div class="vortex-dao-governance">
    <div class="vortex-governance-header">
        <h2>VORTEX DAO Governance</h2>
        <div class="vortex-governance-actions">
            <?php if (is_user_logged_in()): ?>
                <?php if ($user_wallet): ?>
                    <?php if ($can_create_proposal): ?>
                        <a href="?view=create" class="vortex-button vortex-button-primary">Create Proposal</a>
                    <?php else: ?>
                        <div class="vortex-tooltip">
                            <a href="#" class="vortex-button vortex-button-disabled">Create Proposal</a>
                            <span class="vortex-tooltip-text">You need at least <?php echo number_format($min_proposal_tokens); ?> TOLA to create a proposal</span>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="vortex-tooltip">
                        <a href="#" class="vortex-button vortex-button-disabled">Create Proposal</a>
                        <span class="vortex-tooltip-text">Connect your wallet first</span>
                    </div>
                    <?php echo do_shortcode('[vortex_wallet_connect text="Connect Wallet"]'); ?>
                <?php endif; ?>
            <?php else: ?>
                <div class="vortex-tooltip">
                    <a href="#" class="vortex-button vortex-button-disabled">Create Proposal</a>
                    <span class="vortex-tooltip-text">Login to create proposals</span>
                </div>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="vortex-button">Login</a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="vortex-governance-stats">
        <div class="vortex-stats-card">
            <div class="vortex-stats-title">Quorum</div>
            <div class="vortex-stats-value"><?php echo esc_html($quorum_percentage); ?>%</div>
            <div class="vortex-stats-desc">of total supply</div>
        </div>
        <div class="vortex-stats-card">
            <div class="vortex-stats-title">Approval Threshold</div>
            <div class="vortex-stats-value"><?php echo esc_html($proposal_threshold); ?>%</div>
            <div class="vortex-stats-desc">of votes</div>
        </div>
        <div class="vortex-stats-card">
            <div class="vortex-stats-title">Voting Period</div>
            <div class="vortex-stats-value"><?php echo esc_html($voting_period_days); ?></div>
            <div class="vortex-stats-desc">days</div>
        </div>
        <div class="vortex-stats-card">
            <div class="vortex-stats-title">To Propose</div>
            <div class="vortex-stats-value"><?php echo number_format($min_proposal_tokens); ?></div>
            <div class="vortex-stats-desc">TOLA required</div>
        </div>
    </div>
    
    <div class="vortex-governance-filters">
        <div class="vortex-filter-label">Filter by status:</div>
        <div class="vortex-filter-options">
            <a href="?status=active" class="vortex-filter-option<?php echo $status === 'active' ? ' active' : ''; ?>">Active</a>
            <a href="?status=approved" class="vortex-filter-option<?php echo $status === 'approved' ? ' active' : ''; ?>">Approved</a>
            <a href="?status=rejected" class="vortex-filter-option<?php echo $status === 'rejected' ? ' active' : ''; ?>">Rejected</a>
            <a href="?status=vetoed" class="vortex-filter-option<?php echo $status === 'vetoed' ? ' active' : ''; ?>">Vetoed</a>
            <a href="?status=executed" class="vortex-filter-option<?php echo $status === 'executed' ? ' active' : ''; ?>">Executed</a>
            <a href="?status=all" class="vortex-filter-option<?php echo $status === 'all' ? ' active' : ''; ?>">All</a>
        </div>
    </div>
    
    <div class="vortex-proposals-list">
        <?php if (empty($proposals)): ?>
        <div class="vortex-no-proposals">
            <p>No proposals found for the selected status.</p>
            <?php if ($status !== 'active' && $status !== 'all'): ?>
            <p><a href="?status=active">View active proposals</a></p>
            <?php endif; ?>
        </div>
        <?php else: ?>
            <?php foreach ($proposals as $proposal): ?>
                <?php
                $status_class = 'status-' . $proposal->status;
                $status_label = ucfirst($proposal->status);
                
                // Format the voting end date
                $voting_ends = strtotime($proposal->voting_end_date);
                $now = time();
                $time_remaining = $voting_ends - $now;
                
                if ($proposal->status === 'active' && $time_remaining > 0) {
                    $days = floor($time_remaining / (60 * 60 * 24));
                    $hours = floor(($time_remaining % (60 * 60 * 24)) / (60 * 60));
                    
                    if ($days > 0) {
                        $time_left = "$days days, $hours hours left";
                    } else {
                        $time_left = "$hours hours left";
                    }
                } else {
                    $time_left = "Voting ended";
                }
                
                // Calculate vote percentages
                $total_votes = floatval($proposal->total_votes);
                $for_percentage = $total_votes > 0 ? (floatval($proposal->for_votes) / $total_votes) * 100 : 0;
                $against_percentage = $total_votes > 0 ? (floatval($proposal->against_votes) / $total_votes) * 100 : 0;
                $abstain_percentage = $total_votes > 0 ? (floatval($proposal->abstain_votes) / $total_votes) * 100 : 0;
                
                // Get proposal type icon
                $type_icon = 'icon-proposal';
                switch ($proposal->proposal_type) {
                    case 'treasury':
                        $type_icon = 'icon-treasury';
                        break;
                    case 'parameter':
                        $type_icon = 'icon-parameter';
                        break;
                    case 'upgrade':
                        $type_icon = 'icon-upgrade';
                        break;
                    case 'grant':
                        $type_icon = 'icon-grant';
                        break;
                    case 'community':
                        $type_icon = 'icon-community';
                        break;
                }
                
                // Check if user has voted on this proposal
                $user_vote = isset($user_has_voted[$proposal->id]) ? $user_has_voted[$proposal->id] : null;
                ?>
                <div class="vortex-proposal-card <?php echo esc_attr($status_class); ?>">
                    <div class="vortex-proposal-header">
                        <div class="vortex-proposal-type">
                            <i class="vortex-icon <?php echo esc_attr($type_icon); ?>"></i>
                            <span><?php echo esc_html(ucfirst($proposal->proposal_type)); ?></span>
                        </div>
                        <div class="vortex-proposal-status">
                            <span class="vortex-status-indicator"></span>
                            <span class="vortex-status-text"><?php echo esc_html($status_label); ?></span>
                        </div>
                    </div>
                    
                    <div class="vortex-proposal-body">
                        <h3 class="vortex-proposal-title">
                            <a href="?view=details&proposal_id=<?php echo esc_attr($proposal->id); ?>">
                                <?php echo esc_html($proposal->title); ?>
                            </a>
                        </h3>
                        
                        <div class="vortex-proposal-meta">
                            <div class="vortex-proposal-id">ID: <?php echo esc_html($proposal->id); ?></div>
                            <div class="vortex-proposal-date">
                                Created: <?php echo esc_html(date('M j, Y', strtotime($proposal->created_at))); ?>
                            </div>
                            <?php if ($proposal->status === 'active'): ?>
                            <div class="vortex-proposal-time-left"><?php echo esc_html($time_left); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($total_votes > 0): ?>
                        <div class="vortex-proposal-votes">
                            <div class="vortex-votes-bar">
                                <div class="vortex-votes-for" style="width: <?php echo esc_attr($for_percentage); ?>%"></div>
                                <div class="vortex-votes-against" style="width: <?php echo esc_attr($against_percentage); ?>%"></div>
                                <div class="vortex-votes-abstain" style="width: <?php echo esc_attr($abstain_percentage); ?>%"></div>
                            </div>
                            <div class="vortex-votes-legend">
                                <div class="vortex-votes-item">
                                    <span class="vortex-votes-color vortex-for"></span>
                                    <span class="vortex-votes-label">For:</span>
                                    <span class="vortex-votes-value"><?php echo number_format($for_percentage, 1); ?>%</span>
                                </div>
                                <div class="vortex-votes-item">
                                    <span class="vortex-votes-color vortex-against"></span>
                                    <span class="vortex-votes-label">Against:</span>
                                    <span class="vortex-votes-value"><?php echo number_format($against_percentage, 1); ?>%</span>
                                </div>
                                <div class="vortex-votes-item">
                                    <span class="vortex-votes-color vortex-abstain"></span>
                                    <span class="vortex-votes-label">Abstain:</span>
                                    <span class="vortex-votes-value"><?php echo number_format($abstain_percentage, 1); ?>%</span>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="vortex-proposal-no-votes">No votes yet</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="vortex-proposal-footer">
                        <?php if ($proposal->status === 'active'): ?>
                            <?php if (is_user_logged_in() && $user_wallet): ?>
                                <?php if ($user_vote): ?>
                                    <div class="vortex-user-voted">
                                        You voted: <span class="vortex-vote-<?php echo esc_attr($user_vote); ?>"><?php echo esc_html(ucfirst($user_vote)); ?></span>
                                    </div>
                                <?php else: ?>
                                    <a href="?view=details&proposal_id=<?php echo esc_attr($proposal->id); ?>#vote" class="vortex-button">Vote Now</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="vortex-tooltip">
                                    <a href="#" class="vortex-button vortex-button-disabled">Vote</a>
                                    <span class="vortex-tooltip-text"><?php echo is_user_logged_in() ? 'Connect your wallet to vote' : 'Login to vote'; ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <a href="?view=details&proposal_id=<?php echo esc_attr($proposal->id); ?>" class="vortex-button vortex-button-outline">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (count($proposals) >= 10): ?>
            <div class="vortex-proposals-pagination">
                <button id="load-more-proposals" class="vortex-button vortex-button-outline" data-status="<?php echo esc_attr($status); ?>" data-offset="10">Load More</button>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // Load more proposals
        $('#load-more-proposals').on('click', function() {
            var button = $(this);
            var status = button.data('status');
            var offset = button.data('offset');
            
            button.prop('disabled', true).text('Loading...');
            
            $.ajax({
                url: vortexParams.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_get_proposals',
                    nonce: vortexParams.nonce,
                    status: status,
                    offset: offset,
                    limit: 10
                },
                success: function(response) {
                    if (response.success && response.data.proposals.length > 0) {
                        // Append new proposals
                        appendProposals(response.data.proposals);
                        
                        // Update button offset
                        button.data('offset', offset + response.data.proposals.length);
                        
                        // Hide button if no more proposals
                        if (offset + response.data.proposals.length >= response.data.total) {
                            button.hide();
                        }
                    } else {
                        button.hide();
                    }
                },
                error: function() {
                    alert('Error loading more proposals.');
                },
                complete: function() {
                    button.prop('disabled', false).text('Load More');
                }
            });
        });
        
        // Function to append proposals to the list
        function appendProposals(proposals) {
            var $list = $('.vortex-proposals-list');
            
            proposals.forEach(function(proposal) {
                // Calculate vote percentages
                var totalVotes = parseFloat(proposal.total_votes);
                var forPercentage = totalVotes > 0 ? (parseFloat(proposal.for_votes) / totalVotes) * 100 : 0;
                var againstPercentage = totalVotes > 0 ? (parseFloat(proposal.against_votes) / totalVotes) * 100 : 0;
                var abstainPercentage = totalVotes > 0 ? (parseFloat(proposal.abstain_votes) / totalVotes) * 100 : 0;
                
                // Format dates
                var createdDate = new Date(proposal.created_at);
                var formattedDate = createdDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                
                // Calculate time remaining
                var timeLeft = '';
                if (proposal.status === 'active') {
                    var votingEnds = new Date(proposal.voting_end_date);
                    var now = new Date();
                    var timeRemaining = votingEnds - now;
                    
                    if (timeRemaining > 0) {
                        var days = Math.floor(timeRemaining / (1000 * 60 * 60 * 24));
                        var hours = Math.floor((timeRemaining % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        
                        if (days > 0) {
                            timeLeft = days + " days, " + hours + " hours left";
                        } else {
                            timeLeft = hours + " hours left";
                        }
                    } else {
                        timeLeft = "Voting ended";
                    }
                }
                
                // Get proposal type icon
                var typeIcon = 'icon-proposal';
                switch (proposal.proposal_type) {
                    case 'treasury':
                        typeIcon = 'icon-treasury';
                        break;
                    case 'parameter':
                        typeIcon = 'icon-parameter';
                        break;
                    case 'upgrade':
                        typeIcon = 'icon-upgrade';
                        break;
                    case 'grant':
                        typeIcon = 'icon-grant';
                        break;
                    case 'community':
                        typeIcon = 'icon-community';
                        break;
                }
                
                // Build HTML
                var html = `
                    <div class="vortex-proposal-card status-${proposal.status}">
                        <div class="vortex-proposal-header">
                            <div class="vortex-proposal-type">
                                <i class="vortex-icon ${typeIcon}"></i>
                                <span>${proposal.proposal_type.charAt(0).toUpperCase() + proposal.proposal_type.slice(1)}</span>
                            </div>
                            <div class="vortex-proposal-status">
                                <span class="vortex-status-indicator"></span>
                                <span class="vortex-status-text">${proposal.status.charAt(0).toUpperCase() + proposal.status.slice(1)}</span>
                            </div>
                        </div>
                        
                        <div class="vortex-proposal-body">
                            <h3 class="vortex-proposal-title">
                                <a href="?view=details&proposal_id=${proposal.id}">
                                    ${proposal.title}
                                </a>
                            </h3>
                            
                            <div class="vortex-proposal-meta">
                                <div class="vortex-proposal-id">ID: ${proposal.id}</div>
                                <div class="vortex-proposal-date">
                                    Created: ${formattedDate}
                                </div>
                                ${proposal.status === 'active' ? `<div class="vortex-proposal-time-left">${timeLeft}</div>` : ''}
                            </div>
                            
                            ${totalVotes > 0 ? `
                            <div class="vortex-proposal-votes">
                                <div class="vortex-votes-bar">
                                    <div class="vortex-votes-for" style="width: ${forPercentage}%"></div>
                                    <div class="vortex-votes-against" style="width: ${againstPercentage}%"></div>
                                    <div class="vortex-votes-abstain" style="width: ${abstainPercentage}%"></div>
                                </div>
                                <div class="vortex-votes-legend">
                                    <div class="vortex-votes-item">
                                        <span class="vortex-votes-color vortex-for"></span>
                                        <span class="vortex-votes-label">For:</span>
                                        <span class="vortex-votes-value">${forPercentage.toFixed(1)}%</span>
                                    </div>
                                    <div class="vortex-votes-item">
                                        <span class="vortex-votes-color vortex-against"></span>
                                        <span class="vortex-votes-label">Against:</span>
                                        <span class="vortex-votes-value">${againstPercentage.toFixed(1)}%</span>
                                    </div>
                                    <div class="vortex-votes-item">
                                        <span class="vortex-votes-color vortex-abstain"></span>
                                        <span class="vortex-votes-label">Abstain:</span>
                                        <span class="vortex-votes-value">${abstainPercentage.toFixed(1)}%</span>
                                    </div>
                                </div>
                            </div>
                            ` : `<div class="vortex-proposal-no-votes">No votes yet</div>`}
                        </div>
                        
                        <div class="vortex-proposal-footer">
                            ${proposal.status === 'active' ? `
                                <!-- Voting actions will be determined by server-side logic -->
                                <a href="?view=details&proposal_id=${proposal.id}#vote" class="vortex-button">Vote Now</a>
                            ` : ''}
                            <a href="?view=details&proposal_id=${proposal.id}" class="vortex-button vortex-button-outline">View Details</a>
                        </div>
                    </div>
                `;
                
                $list.append(html);
            });
        }
    });
</script> 