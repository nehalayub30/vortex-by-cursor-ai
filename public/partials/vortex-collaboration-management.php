<?php
/**
 * Template for the collaboration management interface
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get collaboration data
$collaboration_id = get_the_ID();
$collaboration = get_post($collaboration_id);
$creator_id = $collaboration->post_author;
$current_user_id = get_current_user_id();
$is_creator = ($current_user_id == $creator_id);

// Get collaboration meta
$collaboration_type = get_post_meta($collaboration_id, 'collaboration_type', true);
$collaboration_budget = get_post_meta($collaboration_id, 'collaboration_budget', true);
$collaboration_deadline = get_post_meta($collaboration_id, 'collaboration_deadline', true);
$collaboration_requirements = get_post_meta($collaboration_id, 'collaboration_requirements', true);
$collaboration_roles = get_post_meta($collaboration_id, 'collaboration_roles', true);

// Get members
global $wpdb;
$members = $wpdb->get_results($wpdb->prepare(
    "SELECT m.*, u.display_name, u.user_email 
    FROM {$wpdb->prefix}vortex_collaboration_members m 
    JOIN {$wpdb->users} u ON m.user_id = u.ID 
    WHERE m.collaboration_id = %d AND m.status = 'active'",
    $collaboration_id
));

// Get pending requests
$pending_requests = $wpdb->get_results($wpdb->prepare(
    "SELECT r.*, u.display_name, u.user_email 
    FROM {$wpdb->prefix}vortex_collaboration_requests r 
    JOIN {$wpdb->users} u ON r.user_id = u.ID 
    WHERE r.collaboration_id = %d AND r.request_status = 'pending'",
    $collaboration_id
));
?>

<div class="vortex-collaboration-management">
    <div class="vortex-collaboration-header">
        <h2><?php echo esc_html($collaboration->post_title); ?></h2>
        <div class="vortex-collaboration-meta">
            <span class="vortex-meta-item">
                <strong><?php _e('Type:', 'vortex-ai-marketplace'); ?></strong>
                <?php echo esc_html($collaboration_type); ?>
            </span>
            <span class="vortex-meta-item">
                <strong><?php _e('Budget:', 'vortex-ai-marketplace'); ?></strong>
                <?php echo esc_html($collaboration_budget); ?> TOLA
            </span>
            <span class="vortex-meta-item">
                <strong><?php _e('Deadline:', 'vortex-ai-marketplace'); ?></strong>
                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($collaboration_deadline))); ?>
            </span>
        </div>
    </div>
    
    <div class="vortex-collaboration-content">
        <div class="vortex-collaboration-description">
            <?php echo wp_kses_post($collaboration->post_content); ?>
        </div>
        
        <div class="vortex-collaboration-requirements">
            <h3><?php _e('Requirements', 'vortex-ai-marketplace'); ?></h3>
            <?php echo wp_kses_post($collaboration_requirements); ?>
        </div>
        
        <div class="vortex-collaboration-roles">
            <h3><?php _e('Required Roles', 'vortex-ai-marketplace'); ?></h3>
            <ul>
                <?php foreach ($collaboration_roles as $role) : ?>
                    <li><?php echo esc_html($role); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    
    <?php if ($is_creator) : ?>
        <div class="vortex-collaboration-members">
            <h3><?php _e('Members', 'vortex-ai-marketplace'); ?></h3>
            <?php if (!empty($members)) : ?>
                <ul class="vortex-members-list">
                    <?php foreach ($members as $member) : ?>
                        <li>
                            <span class="vortex-member-name"><?php echo esc_html($member->display_name); ?></span>
                            <span class="vortex-member-role"><?php echo esc_html($member->role); ?></span>
                            <span class="vortex-member-email"><?php echo esc_html($member->user_email); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php _e('No members yet.', 'vortex-ai-marketplace'); ?></p>
            <?php endif; ?>
            
            <?php if (!empty($pending_requests)) : ?>
                <h3><?php _e('Pending Requests', 'vortex-ai-marketplace'); ?></h3>
                <ul class="vortex-requests-list">
                    <?php foreach ($pending_requests as $request) : ?>
                        <li>
                            <span class="vortex-request-name"><?php echo esc_html($request->display_name); ?></span>
                            <span class="vortex-request-role"><?php echo esc_html($request->requested_role); ?></span>
                            <span class="vortex-request-message"><?php echo esc_html($request->request_message); ?></span>
                            <div class="vortex-request-actions">
                                <button class="vortex-button vortex-button-success vortex-approve-request" 
                                        data-request-id="<?php echo esc_attr($request->id); ?>">
                                    <?php _e('Approve', 'vortex-ai-marketplace'); ?>
                                </button>
                                <button class="vortex-button vortex-button-danger vortex-reject-request" 
                                        data-request-id="<?php echo esc_attr($request->id); ?>">
                                    <?php _e('Reject', 'vortex-ai-marketplace'); ?>
                                </button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <?php if (vortex_is_user_collaboration_member($collaboration_id, $current_user_id)) : ?>
            <div class="vortex-collaboration-member-actions">
                <button class="vortex-button vortex-button-danger vortex-leave-collaboration" 
                        data-collaboration-id="<?php echo esc_attr($collaboration_id); ?>">
                    <?php _e('Leave Collaboration', 'vortex-ai-marketplace'); ?>
                </button>
            </div>
        <?php elseif (!vortex_has_user_pending_collaboration_request($collaboration_id, $current_user_id)) : ?>
            <div class="vortex-collaboration-join">
                <h3><?php _e('Join this Collaboration', 'vortex-ai-marketplace'); ?></h3>
                <form id="vortex-collaboration-join-form" class="vortex-form">
                    <input type="hidden" name="collaboration_id" value="<?php echo esc_attr($collaboration_id); ?>">
                    
                    <div class="vortex-form-group">
                        <label for="requested_role"><?php _e('Requested Role', 'vortex-ai-marketplace'); ?></label>
                        <select name="requested_role" id="requested_role" required>
                            <option value=""><?php _e('Select a role', 'vortex-ai-marketplace'); ?></option>
                            <?php foreach ($collaboration_roles as $role) : ?>
                                <option value="<?php echo esc_attr($role); ?>"><?php echo esc_html($role); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="vortex-form-group">
                        <label for="request_message"><?php _e('Message', 'vortex-ai-marketplace'); ?></label>
                        <textarea name="request_message" id="request_message" rows="4" required></textarea>
                    </div>
                    
                    <div class="vortex-form-group">
                        <button type="submit" class="vortex-button">
                            <?php _e('Submit Request', 'vortex-ai-marketplace'); ?>
                        </button>
                    </div>
                    
                    <div class="vortex-message" style="display: none;"></div>
                </form>
            </div>
        <?php else : ?>
            <div class="vortex-collaboration-pending">
                <p><?php _e('Your request to join this collaboration is pending approval.', 'vortex-ai-marketplace'); ?></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.vortex-collaboration-management {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.vortex-collaboration-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.vortex-collaboration-meta {
    display: flex;
    gap: 20px;
    margin-top: 10px;
    color: #666;
}

.vortex-meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.vortex-collaboration-content {
    margin-bottom: 30px;
}

.vortex-collaboration-description {
    margin-bottom: 20px;
    line-height: 1.6;
}

.vortex-collaboration-requirements,
.vortex-collaboration-roles {
    margin-bottom: 20px;
}

.vortex-collaboration-roles ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.vortex-collaboration-roles li {
    background: #f0f0f0;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 14px;
}

.vortex-members-list,
.vortex-requests-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.vortex-members-list li,
.vortex-requests-list li {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.vortex-member-name,
.vortex-request-name {
    flex: 1;
    font-weight: 600;
}

.vortex-member-role,
.vortex-request-role {
    flex: 0 0 150px;
    color: #666;
}

.vortex-member-email,
.vortex-request-message {
    flex: 0 0 200px;
    color: #666;
}

.vortex-request-actions {
    display: flex;
    gap: 10px;
}

.vortex-button {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
}

.vortex-button-success {
    background: #28a745;
    color: #fff;
}

.vortex-button-danger {
    background: #dc3545;
    color: #fff;
}

.vortex-button:hover {
    opacity: 0.9;
}

.vortex-form-group {
    margin-bottom: 20px;
}

.vortex-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.vortex-form-group select,
.vortex-form-group textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.vortex-message {
    margin-top: 15px;
    padding: 10px;
    border-radius: 4px;
    display: none;
}

.vortex-message-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.vortex-message-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.vortex-collaboration-pending {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 4px;
    color: #666;
}
</style> 