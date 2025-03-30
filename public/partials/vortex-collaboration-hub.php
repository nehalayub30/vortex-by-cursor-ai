<?php
/**
 * Template for the collaboration hub interface
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

$view = isset($atts['view']) ? $atts['view'] : 'grid';
?>

<div class="vortex-collaboration-hub">
    <div class="vortex-hub-header">
        <h2><?php _e('Collaboration Hub', 'vortex-ai-marketplace'); ?></h2>
        <p><?php _e('Connect with other artists and creatives to collaborate on projects.', 'vortex-ai-marketplace'); ?></p>
        
        <div class="vortex-hub-actions">
            <button class="vortex-view-toggle" data-view="grid" <?php echo $view === 'grid' ? 'aria-selected="true"' : ''; ?>>
                <span class="dashicons dashicons-grid-view"></span>
            </button>
            <button class="vortex-view-toggle" data-view="list" <?php echo $view === 'list' ? 'aria-selected="true"' : ''; ?>>
                <span class="dashicons dashicons-list-view"></span>
            </button>
            <button class="vortex-button vortex-button-primary" id="vortex-start-collaboration">
                <?php _e('Start Collaboration', 'vortex-ai-marketplace'); ?>
            </button>
        </div>
    </div>
    
    <!-- User's Active Collaborations Section -->
    <?php if (!empty($user_collaborations)) : ?>
        <div class="vortex-user-collaborations">
            <h3><?php _e('Your Active Collaborations', 'vortex-ai-marketplace'); ?></h3>
            
            <div class="vortex-collaborations-list">
                <?php foreach ($user_collaborations as $collab) : ?>
                    <div class="vortex-collaboration-card">
                        <h4>
                            <a href="<?php echo esc_url($collab['permalink']); ?>">
                                <?php echo esc_html($collab['title']); ?>
                            </a>
                        </h4>
                        <div class="vortex-role-badge">
                            <?php echo esc_html(ucfirst($collab['role'])); ?>
                        </div>
                        <div class="vortex-card-actions">
                            <a href="<?php echo esc_url($collab['permalink']); ?>" class="vortex-button">
                                <?php _e('Open', 'vortex-ai-marketplace'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Collaborations Browser -->
    <div class="vortex-collaborations-browser">
        <h3><?php _e('Discover Collaborations', 'vortex-ai-marketplace'); ?></h3>
        
        <div class="vortex-collaborations-grid <?php echo $view === 'list' ? 'vortex-view-list' : ''; ?>">
            <?php if (empty($collaborations)) : ?>
                <div class="vortex-no-collaborations">
                    <p><?php _e('No active collaborations found. Start a new collaboration to connect with other artists!', 'vortex-ai-marketplace'); ?></p>
                </div>
            <?php else : ?>
                <?php foreach ($collaborations as $collaboration) : 
                    $collaboration_id = $collaboration->ID;
                    $collaboration_url = get_permalink($collaboration_id);
                    $collaboration_type = get_post_meta($collaboration_id, 'collaboration_type', true);
                    $collaboration_budget = get_post_meta($collaboration_id, 'collaboration_budget', true);
                    $collaboration_deadline = get_post_meta($collaboration_id, 'collaboration_deadline', true);
                    $collaboration_roles = get_post_meta($collaboration_id, 'collaboration_roles', true);
                    
                    // Get author
                    $author_id = $collaboration->post_author;
                    $author_name = get_the_author_meta('display_name', $author_id);
                    $author_url = get_author_posts_url($author_id);
                    
                    // Check if user is already a member
                    $is_member = Vortex_AJAX_Handlers::is_user_collaboration_member($collaboration_id, $user_id);
                    
                    // Check if user has a pending request
                    $has_pending_request = Vortex_AJAX_Handlers::has_user_pending_collaboration_request($collaboration_id, $user_id);
                ?>
                    <div class="vortex-collaboration-item">
                        <div class="vortex-collab-header">
                            <h4 class="vortex-collab-title">
                                <a href="<?php echo esc_url($collaboration_url); ?>">
                                    <?php echo esc_html($collaboration->post_title); ?>
                                </a>
                            </h4>
                            <span class="vortex-collab-type"><?php echo esc_html($collaboration_type); ?></span>
                        </div>
                        
                        <div class="vortex-collab-meta">
                            <div class="vortex-meta-item">
                                <span class="vortex-meta-label"><?php _e('Creator:', 'vortex-ai-marketplace'); ?></span>
                                <span class="vortex-meta-value">
                                    <a href="<?php echo esc_url($author_url); ?>"><?php echo esc_html($author_name); ?></a>
                                </span>
                            </div>
                            
                            <div class="vortex-meta-item">
                                <span class="vortex-meta-label"><?php _e('Budget:', 'vortex-ai-marketplace'); ?></span>
                                <span class="vortex-meta-value"><?php echo esc_html($collaboration_budget); ?> TOLA</span>
                            </div>
                            
                            <div class="vortex-meta-item">
                                <span class="vortex-meta-label"><?php _e('Deadline:', 'vortex-ai-marketplace'); ?></span>
                                <span class="vortex-meta-value">
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($collaboration_deadline))); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="vortex-collab-excerpt">
                            <?php echo wp_trim_words($collaboration->post_content, 20, '...'); ?>
                        </div>
                        
                        <?php if (!empty($collaboration_roles)) : ?>
                            <div class="vortex-collab-roles">
                                <span class="vortex-roles-label"><?php _e('Roles Needed:', 'vortex-ai-marketplace'); ?></span>
                                <div class="vortex-roles-list">
                                    <?php foreach ((array)$collaboration_roles as $role) : ?>
                                        <span class="vortex-role-tag"><?php echo esc_html($role); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="vortex-collab-actions">
                            <a href="<?php echo esc_url($collaboration_url); ?>" class="vortex-button">
                                <?php _e('View Details', 'vortex-ai-marketplace'); ?>
                            </a>
                            
                            <?php if ($is_member) : ?>
                                <span class="vortex-member-badge">
                                    <?php _e('Member', 'vortex-ai-marketplace'); ?>
                                </span>
                            <?php elseif ($has_pending_request) : ?>
                                <span class="vortex-pending-badge">
                                    <?php _e('Request Pending', 'vortex-ai-marketplace'); ?>
                                </span>
                            <?php else : ?>
                                <button class="vortex-button vortex-join-button" data-id="<?php echo esc_attr($collaboration_id); ?>">
                                    <?php _e('Join', 'vortex-ai-marketplace'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Join Collaboration Modal -->
    <div id="vortex-join-modal" class="vortex-modal">
        <div class="vortex-modal-content">
            <span class="vortex-modal-close">&times;</span>
            <h3><?php _e('Join Collaboration', 'vortex-ai-marketplace'); ?></h3>
            
            <form id="vortex-join-form" class="vortex-form">
                <input type="hidden" name="collaboration_id" id="join-collaboration-id">
                <?php wp_nonce_field('vortex_career_project_nonce', 'join_nonce'); ?>
                
                <div class="vortex-form-group">
                    <label for="requested-role"><?php _e('What role would you like to fill?', 'vortex-ai-marketplace'); ?></label>
                    <select id="requested-role" name="requested_role" required>
                        <option value=""><?php _e('Select Role', 'vortex-ai-marketplace'); ?></option>
                        <option value="artist"><?php _e('Artist', 'vortex-ai-marketplace'); ?></option>
                        <option value="designer"><?php _e('Designer', 'vortex-ai-marketplace'); ?></option>
                        <option value="developer"><?php _e('Developer', 'vortex-ai-marketplace'); ?></option>
                        <option value="curator"><?php _e('Curator', 'vortex-ai-marketplace'); ?></option>
                    </select>
                </div>
                
                <div class="vortex-form-group">
                    <label for="request-message"><?php _e('Why would you like to join?', 'vortex-ai-marketplace'); ?></label>
                    <textarea id="request-message" name="request_message" rows="4" required></textarea>
                </div>
                
                <div class="vortex-form-actions">
                    <button type="submit" class="vortex-button vortex-button-primary">
                        <?php _e('Submit Request', 'vortex-ai-marketplace'); ?>
                    </button>
                </div>
                
                <div id="vortex-join-message" class="vortex-message" style="display: none;"></div>
            </form>
        </div>
    </div>
    
    <!-- Create Collaboration Modal -->
    <div id="vortex-create-modal" class="vortex-modal">
        <div class="vortex-modal-content">
            <span class="vortex-modal-close">&times;</span>
            <h3><?php _e('Start New Collaboration', 'vortex-ai-marketplace'); ?></h3>
            
            <form id="vortex-collaboration-form" class="vortex-form">
                <?php wp_nonce_field('vortex_career_project_nonce', 'collaboration_nonce'); ?>
                
                <div class="vortex-form-group">
                    <label for="collaboration-title"><?php _e('Collaboration Title', 'vortex-ai-marketplace'); ?></label>
                    <input type="text" id="collaboration-title" name="collaboration_title" required>
                </div>
                
                <div class="vortex-form-group">
                    <label for="collaboration-type"><?php _e('Collaboration Type', 'vortex-ai-marketplace'); ?></label>
                    <select id="collaboration-type" name="collaboration_type" required>
                        <option value=""><?php _e('Select Type', 'vortex-ai-marketplace'); ?></option>
                        <option value="Artwork Creation"><?php _e('Artwork Creation', 'vortex-ai-marketplace'); ?></option>
                        <option value="Exhibition"><?php _e('Exhibition', 'vortex-ai-marketplace'); ?></option>
                        <option value="Project"><?php _e('Project', 'vortex-ai-marketplace'); ?></option>
                    </select>
                </div>
                
                <div class="vortex-form-group">
                    <label for="collaboration-description"><?php _e('Description', 'vortex-ai-marketplace'); ?></label>
                    <textarea id="collaboration-description" name="collaboration_description" rows="4" required></textarea>
                </div>
                
                <div class="vortex-form-group">
                    <label for="collaboration-budget"><?php _e('Budget (TOLA)', 'vortex-ai-marketplace'); ?></label>
                    <input type="number" id="collaboration-budget" name="collaboration_budget" min="0" step="1" required>
                </div>
                
                <div class="vortex-form-group">
                    <label for="collaboration-deadline"><?php _e('Deadline', 'vortex-ai-marketplace'); ?></label>
                    <input type="date" id="collaboration-deadline" name="collaboration_deadline" required>
                </div>
                
                <div class="vortex-form-actions">
                    <button type="submit" class="vortex-button vortex-button-primary">
                        <?php _e('Create Collaboration', 'vortex-ai-marketplace'); ?>
                    </button>
                </div>
                
                <div id="vortex-collaboration-message" class="vortex-message" style="display: none;"></div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
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
    var joinModal = $('#vortex-join-modal');
    $('.vortex-join-button').on('click', function() {
        var collaborationId = $(this).data('id');
        $('#join-collaboration-id').val(collaborationId);
        joinModal.css('display', 'block');
    });
    
    // Create collaboration modal
    var createModal = $('#vortex-create-modal');
    $('#vortex-start-collaboration').on('click', function() {
        createModal.css('display', 'block');
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
        $submitButton.prop('disabled', true).text('<?php _e('Submitting...', 'vortex-ai-marketplace'); ?>');
        
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
                $submitButton.prop('disabled', false).text('<?php _e('Submit Request', 'vortex-ai-marketplace'); ?>');
            }
        });
    });
});
</script>

<style>
.vortex-collaboration-hub {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

.vortex-hub-header {
    margin-bottom: 20px;
}

.vortex-hub-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 15px;
}

.vortex-view-toggle {
    border: 1px solid #ddd;
    background: #f0f0f0;
    border-radius: 4px;
    padding: 5px;
    cursor: pointer;
}

.vortex-view-toggle[aria-selected="true"] {
    background: #0073aa;
    color: #fff;
    border-color: #0073aa;
}

.vortex-user-collaborations {
    margin-bottom: 30px;
}

.vortex-collaborations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.vortex-collaborations-grid.vortex-view-list {
    grid-template-columns: 1fr;
}

.vortex-view-list .vortex-collaboration-item {
    display: grid;
    grid-template-columns: 1fr auto;
    grid-template-rows: auto auto auto;
    gap: 10px;
}

.vortex-view-list .vortex-collab-meta {
    display: flex;
    gap: 20px;
}

.vortex-collaboration-item {
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 15px;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.vortex-collaboration-card {
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 15px;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    display: flex;
    flex-direction: column;
    position: relative;
}

.vortex-collab-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.vortex-collab-title {
    margin: 0;
}

.vortex-collab-type {
    font-size: 12px;
    padding: 3px 8px;
    background: #f0f0f0;
    border-radius: 4px;
}

.vortex-collab-meta {
    font-size: 14px;
    color: #666;
    margin-bottom: 10px;
}

.vortex-meta-label {
    font-weight: 600;
}

.vortex-collab-excerpt {
    margin-bottom: 15px;
}

.vortex-collab-roles {
    margin-bottom: 15px;
}

.vortex-roles-list {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 5px;
}

.vortex-role-tag {
    background: #f0f0f0;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}

.vortex-collab-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.vortex-button {
    display: inline-block;
    padding: 8px 16px;
    background: #f0f0f0;
    color: #333;
    border: none;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    cursor: pointer;
}

.vortex-button-primary {
    background: #0073aa;
    color: #fff;
}

.vortex-button:hover {
    opacity: 0.9;
}

.vortex-member-badge,
.vortex-pending-badge,
.vortex-role-badge {
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 4px;
}

.vortex-member-badge {
    background: #d4edda;
    color: #155724;
}

.vortex-pending-badge {
    background: #fff3cd;
    color: #856404;
}

.vortex-role-badge {
    background: #d1ecf1;
    color: #0c5460;
    position: absolute;
    top: 15px;
    right: 15px;
}

.vortex-no-collaborations {
    grid-column: 1 / -1;
    padding: 40px;
    text-align: center;
    background: #f8f9fa;
    border-radius: 8px;
    color: #666;
}

/* Modal Styles */
.vortex-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.7);
}

.vortex-modal-content {
    background-color: #fff;
    margin: 50px auto;
    padding: 20px;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
}

.vortex-modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.vortex-modal-close:hover {
    color: #000;
}

.vortex-form-group {
    margin-bottom: 15px;
}

.vortex-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.vortex-form-group input,
.vortex-form-group select,
.vortex-form-group textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.vortex-message {
    margin-top: 15px;
    padding: 10px;
    border-radius: 4px;
}

.vortex-message-success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.vortex-message-error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}
</style> 