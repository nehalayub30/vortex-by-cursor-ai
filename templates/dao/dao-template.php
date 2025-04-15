<?php
/**
 * Template for displaying DAO governance interface
 *
 * This template can be overridden by copying it to yourtheme/vortex/dao/dao-template.php
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Enqueue necessary scripts and styles
wp_enqueue_style('vortex-dao-style', VORTEX_PLUGIN_URL . 'assets/css/vortex-dao.css', array(), VORTEX_VERSION);
wp_enqueue_script('vortex-dao-script', VORTEX_PLUGIN_URL . 'assets/js/vortex-dao.js', array('jquery'), VORTEX_VERSION, true);

// Prepare data for JavaScript
wp_localize_script('vortex-dao-script', 'vortexDAOData', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('vortex_dao_nonce'),
    'isLoggedIn' => is_user_logged_in() ? 'yes' : 'no'
));

// Check if user is logged in
$is_logged_in = is_user_logged_in();
$user_id = get_current_user_id();

// Check if user can create proposals
$can_create_proposals = false;
if ($is_logged_in) {
    $can_create_proposals = $dao_manager->is_user_eligible_to_propose($user_id);
}

// Check if user can vote
$can_vote = false;
if ($is_logged_in) {
    $can_vote = $dao_manager->is_user_eligible_to_vote($user_id);
}

// Get proposals based on status filter
$status = isset($atts['status']) ? $atts['status'] : 'active';
$proposal_args = array(
    'post_type' => 'vortex_proposal',
    'posts_per_page' => 10,
    'paged' => get_query_var('paged') ? get_query_var('paged') : 1
);

switch ($status) {
    case 'active':
        $proposal_args['post_status'] = 'publish';
        $status_title = __('Active Proposals', 'vortex-marketplace');
        break;
        
    case 'approved':
        $proposal_args['post_status'] = 'approved';
        $status_title = __('Approved Proposals', 'vortex-marketplace');
        break;
        
    case 'rejected':
        $proposal_args['post_status'] = 'rejected';
        $status_title = __('Rejected Proposals', 'vortex-marketplace');
        break;
        
    default:
        $proposal_args['post_status'] = array('publish', 'approved', 'rejected');
        $status_title = __('All Proposals', 'vortex-marketplace');
        break;
}

$proposals_query = new WP_Query($proposal_args);
?>

<div class="vortex-dao-container">
    <div class="vortex-dao-header">
        <h2><?php _e('Decentralized Governance', 'vortex-marketplace'); ?></h2>
        <div class="vortex-dao-header-actions">
            <?php if ($can_create_proposals): ?>
                <button class="vortex-create-proposal-btn"><?php _e('Create Proposal', 'vortex-marketplace'); ?></button>
            <?php elseif ($is_logged_in): ?>
                <div class="vortex-dao-notice">
                    <?php _e('You are not eligible to create proposals.', 'vortex-marketplace'); ?>
                </div>
            <?php else: ?>
                <div class="vortex-dao-notice">
                    <?php _e('Please login to participate in governance.', 'vortex-marketplace'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="vortex-dao-tabs">
        <div class="vortex-dao-tab-links">
            <a href="<?php echo add_query_arg('status', 'active'); ?>" class="<?php echo $status === 'active' ? 'active' : ''; ?>">
                <?php _e('Active', 'vortex-marketplace'); ?>
            </a>
            <a href="<?php echo add_query_arg('status', 'approved'); ?>" class="<?php echo $status === 'approved' ? 'active' : ''; ?>">
                <?php _e('Approved', 'vortex-marketplace'); ?>
            </a>
            <a href="<?php echo add_query_arg('status', 'rejected'); ?>" class="<?php echo $status === 'rejected' ? 'active' : ''; ?>">
                <?php _e('Rejected', 'vortex-marketplace'); ?>
            </a>
            <a href="<?php echo add_query_arg('status', 'all'); ?>" class="<?php echo $status === 'all' ? 'active' : ''; ?>">
                <?php _e('All', 'vortex-marketplace'); ?>
            </a>
        </div>
        
        <div class="vortex-dao-tab-content">
            <h3><?php echo $status_title; ?></h3>
            
            <?php if ($proposals_query->have_posts()): ?>
                <div class="vortex-proposals-list">
                    <?php while ($proposals_query->have_posts()): $proposals_query->the_post(); 
                        $proposal_id = get_the_ID();
                        $end_date = get_post_meta($proposal_id, 'vortex_proposal_end_date', true);
                        $proposal_type = get_post_meta($proposal_id, 'vortex_proposal_type', true);
                        $yes_votes = get_post_meta($proposal_id, 'vortex_proposal_yes_votes', true);
                        $no_votes = get_post_meta($proposal_id, 'vortex_proposal_no_votes', true);
                        $abstain_votes = get_post_meta($proposal_id, 'vortex_proposal_abstain_votes', true);
                        $total_votes = get_post_meta($proposal_id, 'vortex_proposal_total_votes', true);
                        
                        // Calculate progress
                        $yes_percentage = $total_votes > 0 ? ($yes_votes / $total_votes) * 100 : 0;
                        $no_percentage = $total_votes > 0 ? ($no_votes / $total_votes) * 100 : 0;
                        $abstain_percentage = $total_votes > 0 ? ($abstain_votes / $total_votes) * 100 : 0;
                        
                        // Check if voting is still open
                        $voting_open = strtotime($end_date) > current_time('timestamp');
                        
                        // Check if user has already voted
                        $user_vote = $is_logged_in ? $dao_manager->get_user_vote($user_id, $proposal_id) : null;
                        
                        // Format proposal type for display
                        switch ($proposal_type) {
                            case 'parameter_change':
                                $type_label = __('Parameter Change', 'vortex-marketplace');
                                break;
                            case 'feature_request':
                                $type_label = __('Feature Request', 'vortex-marketplace');
                                break;
                            case 'fund_allocation':
                                $type_label = __('Fund Allocation', 'vortex-marketplace');
                                break;
                            case 'membership':
                                $type_label = __('Membership Change', 'vortex-marketplace');
                                break;
                            case 'custom':
                                $type_label = __('Custom Proposal', 'vortex-marketplace');
                                break;
                            default:
                                $type_label = __('General Proposal', 'vortex-marketplace');
                        }
                    ?>
                    <div class="vortex-proposal-card">
                        <div class="proposal-header">
                            <h4 class="proposal-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h4>
                            <span class="proposal-type"><?php echo $type_label; ?></span>
                        </div>
                        
                        <div class="proposal-meta">
                            <div class="proposal-author">
                                <?php printf(__('Proposed by: %s', 'vortex-marketplace'), get_the_author()); ?>
                            </div>
                            <div class="proposal-date">
                                <?php printf(__('Created: %s', 'vortex-marketplace'), get_the_date()); ?>
                            </div>
                            <?php if (get_post_status() === 'publish'): ?>
                                <div class="proposal-deadline">
                                    <?php printf(__('Voting ends: %s', 'vortex-marketplace'), 
                                        date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($end_date))); ?>
                                </div>
                            <?php else: 
                                $result = get_post_meta($proposal_id, 'vortex_proposal_final_result', true);
                                $result_date = get_post_meta($proposal_id, 'vortex_proposal_finalized_date', true);
                            ?>
                                <div class="proposal-result <?php echo $result; ?>">
                                    <?php if ($result === 'approved'): ?>
                                        <?php _e('Approved', 'vortex-marketplace'); ?>
                                    <?php else: ?>
                                        <?php _e('Rejected', 'vortex-marketplace'); ?>
                                    <?php endif; ?>
                                    
                                    <?php if ($result_date): ?>
                                        <?php printf(__(' on %s', 'vortex-marketplace'), 
                                            date_i18n(get_option('date_format'), strtotime($result_date))); ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="proposal-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                        
                        <div class="proposal-voting">
                            <div class="voting-results">
                                <div class="progress-container">
                                    <div class="progress-label">
                                        <span class="yes-label"><?php _e('Yes', 'vortex-marketplace'); ?></span>
                                        <span class="yes-value"><?php echo round($yes_percentage, 1); ?>%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-yes" style="width: <?php echo $yes_percentage; ?>%"></div>
                                        <div class="progress-no" style="width: <?php echo $no_percentage; ?>%"></div>
                                        <div class="progress-abstain" style="width: <?php echo $abstain_percentage; ?>%"></div>
                                    </div>
                                    <div class="progress-label">
                                        <span class="no-label"><?php _e('No', 'vortex-marketplace'); ?></span>
                                        <span class="no-value"><?php echo round($no_percentage, 1); ?>%</span>
                                    </div>
                                </div>
                                <div class="vote-counts">
                                    <?php printf(__('Total votes: %s', 'vortex-marketplace'), number_format($total_votes)); ?>
                                </div>
                            </div>
                            
                            <?php if ($voting_open && get_post_status() === 'publish'): ?>
                                <?php if ($can_vote && !$user_vote): ?>
                                    <div class="voting-actions" data-proposal-id="<?php echo $proposal_id; ?>">
                                        <button class="vote-btn vote-yes"><?php _e('Vote Yes', 'vortex-marketplace'); ?></button>
                                        <button class="vote-btn vote-no"><?php _e('Vote No', 'vortex-marketplace'); ?></button>
                                        <button class="vote-btn vote-abstain"><?php _e('Abstain', 'vortex-marketplace'); ?></button>
                                    </div>
                                <?php elseif ($user_vote): ?>
                                    <div class="user-voted">
                                        <?php printf(__('You voted: %s', 'vortex-marketplace'), 
                                            ucfirst($user_vote->vote)); ?>
                                    </div>
                                <?php elseif ($is_logged_in): ?>
                                    <div class="user-ineligible">
                                        <?php _e('You are not eligible to vote.', 'vortex-marketplace'); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="user-login-notice">
                                        <?php _e('Please login to vote.', 'vortex-marketplace'); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="proposal-footer">
                            <a href="<?php the_permalink(); ?>" class="proposal-link"><?php _e('View Details', 'vortex-marketplace'); ?></a>
                            
                            <?php if (get_post_status() === 'approved'): 
                                $result_reason = get_post_meta($proposal_id, 'vortex_proposal_result_reason', true);
                            ?>
                                <div class="implementation-status">
                                    <?php _e('Implementation Status:', 'vortex-marketplace'); ?>
                                    <span class="status-implemented"><?php _e('Implemented', 'vortex-marketplace'); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
                
                <div class="vortex-dao-pagination">
                    <?php
                    echo paginate_links(array(
                        'total' => $proposals_query->max_num_pages,
                        'current' => max(1, get_query_var('paged')),
                        'prev_text' => __('&laquo; Previous', 'vortex-marketplace'),
                        'next_text' => __('Next &raquo;', 'vortex-marketplace'),
                    ));
                    ?>
                </div>
            <?php else: ?>
                <div class="vortex-no-proposals">
                    <p><?php _e('No proposals found.', 'vortex-marketplace'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($can_create_proposals): ?>
    <div class="vortex-proposal-form-modal" style="display: none;">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3><?php _e('Create New Proposal', 'vortex-marketplace'); ?></h3>
            
            <form id="vortex-proposal-form">
                <div class="form-group">
                    <label for="proposal-title"><?php _e('Title', 'vortex-marketplace'); ?></label>
                    <input type="text" id="proposal-title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="proposal-type"><?php _e('Proposal Type', 'vortex-marketplace'); ?></label>
                    <select id="proposal-type" name="type" required>
                        <option value=""><?php _e('Select proposal type', 'vortex-marketplace'); ?></option>
                        <option value="parameter_change"><?php _e('Parameter Change', 'vortex-marketplace'); ?></option>
                        <option value="feature_request"><?php _e('Feature Request', 'vortex-marketplace'); ?></option>
                        <option value="fund_allocation"><?php _e('Fund Allocation', 'vortex-marketplace'); ?></option>
                        <option value="membership"><?php _e('Membership Change', 'vortex-marketplace'); ?></option>
                        <option value="custom"><?php _e('Custom Proposal', 'vortex-marketplace'); ?></option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="proposal-description"><?php _e('Description', 'vortex-marketplace'); ?></label>
                    <textarea id="proposal-description" name="description" rows="8" required></textarea>
                </div>
                
                <!-- Dynamic Parameter Fields (shown/hidden based on proposal type) -->
                <div id="parameter-change-fields" class="parameter-fields" style="display: none;">
                    <h4><?php _e('Parameter Details', 'vortex-marketplace'); ?></h4>
                    <div class="form-group">
                        <label for="parameter-key"><?php _e('Parameter', 'vortex-marketplace'); ?></label>
                        <select id="parameter-key" name="parameters[key]">
                            <option value="vortex_dao_voting_period"><?php _e('Voting Period (days)', 'vortex-marketplace'); ?></option>
                            <option value="vortex_dao_min_quorum"><?php _e('Minimum Quorum', 'vortex-marketplace'); ?></option>
                            <option value="vortex_marketplace_fee"><?php _e('Marketplace Fee (%)', 'vortex-marketplace'); ?></option>
                            <option value="vortex_artist_royalty_default"><?php _e('Default Artist Royalty (%)', 'vortex-marketplace'); ?></option>
                            <option value="vortex_history_retention_days"><?php _e('History Retention (days)', 'vortex-marketplace'); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="parameter-value"><?php _e('New Value', 'vortex-marketplace'); ?></label>
                        <input type="number" id="parameter-value" name="parameters[value]" min="1" step="0.1">
                    </div>
                </div>
                
                <div id="feature-request-fields" class="parameter-fields" style="display: none;">
                    <h4><?php _e('Feature Details', 'vortex-marketplace'); ?></h4>
                    <div class="form-group">
                        <label for="feature-name"><?php _e('Feature Name', 'vortex-marketplace'); ?></label>
                        <input type="text" id="feature-name" name="parameters[feature_name]">
                    </div>
                    <div class="form-group">
                        <label for="feature-description"><?php _e('Feature Description', 'vortex-marketplace'); ?></label>
                        <textarea id="feature-description" name="parameters[description]" rows="4"></textarea>
                    </div>
                </div>
                
                <div id="fund-allocation-fields" class="parameter-fields" style="display: none;">
                    <h4><?php _e('Fund Allocation Details', 'vortex-marketplace'); ?></h4>
                    <div class="form-group">
                        <label for="fund-recipient"><?php _e('Recipient', 'vortex-marketplace'); ?></label>
                        <input type="text" id="fund-recipient" name="parameters[recipient]">
                    </div>
                    <div class="form-group">
                        <label for="fund-amount"><?php _e('Amount (ETH)', 'vortex-marketplace'); ?></label>
                        <input type="number" id="fund-amount" name="parameters[amount]" min="0.01" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="fund-purpose"><?php _e('Purpose', 'vortex-marketplace'); ?></label>
                        <textarea id="fund-purpose" name="parameters[purpose]" rows="4"></textarea>
                    </div>
                </div>
                
                <div id="membership-fields" class="parameter-fields" style="display: none;">
                    <h4><?php _e('Membership Details', 'vortex-marketplace'); ?></h4>
                    <div class="form-group">
                        <label for="membership-action"><?php _e('Action', 'vortex-marketplace'); ?></label>
                        <select id="membership-action" name="parameters[action]">
                            <option value="add_role"><?php _e('Add Role', 'vortex-marketplace'); ?></option>
                            <option value="remove_role"><?php _e('Remove Role', 'vortex-marketplace'); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="membership-user"><?php _e('User', 'vortex-marketplace'); ?></label>
                        <input type="text" id="membership-user" name="parameters[username]" placeholder="<?php _e('Enter username', 'vortex-marketplace'); ?>">
                        <input type="hidden" id="membership-user-id" name="parameters[user_id]">
                    </div>
                    <div class="form-group">
                        <label for="membership-role"><?php _e('Role', 'vortex-marketplace'); ?></label>
                        <select id="membership-role" name="parameters[role]">
                            <option value="vortex_dao_member"><?php _e('DAO Member', 'vortex-marketplace'); ?></option>
                            <option value="vortex_dao_admin"><?php _e('DAO Administrator', 'vortex-marketplace'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="form-footer">
                    <div class="form-status"></div>
                    <button type="submit" class="submit-proposal-btn"><?php _e('Submit Proposal', 'vortex-marketplace'); ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div> 