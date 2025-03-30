<?php
/**
 * Template for the project proposals interface
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Get project categories
$categories = get_terms(array(
    'taxonomy' => 'project_category',
    'hide_empty' => false
));

// Show filters if enabled
$show_filters = isset($atts['show_filters']) && $atts['show_filters'] === 'yes';
?>

<div class="vortex-project-proposals">
    <div class="vortex-project-header">
        <h2><?php _e('Project Proposals', 'vortex-ai-marketplace'); ?></h2>
        <p><?php _e('Discover projects to collaborate on or create your own proposal.', 'vortex-ai-marketplace'); ?></p>
        
        <div class="vortex-project-actions">
            <button class="vortex-button vortex-button-primary" id="vortex-create-project-btn">
                <?php _e('Create New Project', 'vortex-ai-marketplace'); ?>
            </button>
        </div>
    </div>
    
    <?php if ($show_filters) : ?>
        <div class="vortex-project-filters">
            <div class="vortex-filter-group">
                <label for="project-category-filter"><?php _e('Category:', 'vortex-ai-marketplace'); ?></label>
                <select id="project-category-filter" class="vortex-filter">
                    <option value=""><?php _e('All Categories', 'vortex-ai-marketplace'); ?></option>
                    <?php foreach ($categories as $category) : ?>
                        <option value="<?php echo esc_attr($category->slug); ?>">
                            <?php echo esc_html($category->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="vortex-filter-group">
                <label for="project-skill-filter"><?php _e('Skills Required:', 'vortex-ai-marketplace'); ?></label>
                <select id="project-skill-filter" class="vortex-filter">
                    <option value=""><?php _e('All Skills', 'vortex-ai-marketplace'); ?></option>
                    <option value="digital-art"><?php _e('Digital Art', 'vortex-ai-marketplace'); ?></option>
                    <option value="traditional-art"><?php _e('Traditional Art', 'vortex-ai-marketplace'); ?></option>
                    <option value="ai-art"><?php _e('AI Art', 'vortex-ai-marketplace'); ?></option>
                    <option value="3d-modeling"><?php _e('3D Modeling', 'vortex-ai-marketplace'); ?></option>
                    <option value="animation"><?php _e('Animation', 'vortex-ai-marketplace'); ?></option>
                    <option value="character-design"><?php _e('Character Design', 'vortex-ai-marketplace'); ?></option>
                    <option value="concept-art"><?php _e('Concept Art', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
            
            <div class="vortex-filter-group">
                <label for="project-budget-filter"><?php _e('Budget (TOLA):', 'vortex-ai-marketplace'); ?></label>
                <select id="project-budget-filter" class="vortex-filter">
                    <option value=""><?php _e('Any Budget', 'vortex-ai-marketplace'); ?></option>
                    <option value="0-100"><?php _e('0-100', 'vortex-ai-marketplace'); ?></option>
                    <option value="100-500"><?php _e('100-500', 'vortex-ai-marketplace'); ?></option>
                    <option value="500-1000"><?php _e('500-1000', 'vortex-ai-marketplace'); ?></option>
                    <option value="1000+"><?php _e('1000+', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="vortex-projects-list">
        <?php if (empty($projects)) : ?>
            <div class="vortex-no-projects">
                <p><?php _e('No projects found. Be the first to create a project proposal!', 'vortex-ai-marketplace'); ?></p>
            </div>
        <?php else : ?>
            <?php foreach ($projects as $project) : 
                $project_id = $project->ID;
                $project_url = get_permalink($project_id);
                $timeline = get_post_meta($project_id, 'vortex_project_timeline', true);
                $budget = get_post_meta($project_id, 'vortex_project_budget', true);
                $skills = get_post_meta($project_id, 'vortex_skills_required', true);
                $status = get_post_meta($project_id, 'vortex_project_status', true);
                
                // Get project category
                $project_categories = wp_get_post_terms($project_id, 'project_category', array('fields' => 'names'));
                $category_name = !empty($project_categories) ? implode(', ', $project_categories) : __('Uncategorized', 'vortex-ai-marketplace');
                
                // Get author
                $author_id = $project->post_author;
                $author_name = get_the_author_meta('display_name', $author_id);
                $author_url = get_author_posts_url($author_id);
            ?>
                <div class="vortex-project-item" data-category="<?php echo esc_attr(implode(',', wp_get_post_terms($project_id, 'project_category', array('fields' => 'slugs')))); ?>" data-skills="<?php echo esc_attr(implode(',', (array)$skills)); ?>" data-budget="<?php echo esc_attr($budget); ?>">
                    <div class="vortex-project-header">
                        <h3 class="vortex-project-title">
                            <a href="<?php echo esc_url($project_url); ?>"><?php echo esc_html($project->post_title); ?></a>
                        </h3>
                        <span class="vortex-project-status vortex-status-<?php echo esc_attr($status); ?>">
                            <?php echo esc_html(ucfirst($status)); ?>
                        </span>
                    </div>
                    
                    <div class="vortex-project-meta">
                        <div class="vortex-meta-item">
                            <span class="vortex-meta-label"><?php _e('Category:', 'vortex-ai-marketplace'); ?></span>
                            <span class="vortex-meta-value"><?php echo esc_html($category_name); ?></span>
                        </div>
                        
                        <div class="vortex-meta-item">
                            <span class="vortex-meta-label"><?php _e('Posted by:', 'vortex-ai-marketplace'); ?></span>
                            <span class="vortex-meta-value">
                                <a href="<?php echo esc_url($author_url); ?>"><?php echo esc_html($author_name); ?></a>
                            </span>
                        </div>
                        
                        <div class="vortex-meta-item">
                            <span class="vortex-meta-label"><?php _e('Timeline:', 'vortex-ai-marketplace'); ?></span>
                            <span class="vortex-meta-value"><?php echo esc_html($timeline); ?></span>
                        </div>
                        
                        <div class="vortex-meta-item">
                            <span class="vortex-meta-label"><?php _e('Budget:', 'vortex-ai-marketplace'); ?></span>
                            <span class="vortex-meta-value"><?php echo esc_html($budget); ?> TOLA</span>
                        </div>
                    </div>
                    
                    <div class="vortex-project-excerpt">
                        <?php echo wp_trim_words($project->post_content, 30, '...'); ?>
                    </div>
                    
                    <?php if (!empty($skills)) : ?>
                        <div class="vortex-project-skills">
                            <span class="vortex-skills-label"><?php _e('Skills Required:', 'vortex-ai-marketplace'); ?></span>
                            <div class="vortex-skills-list">
                                <?php foreach ((array)$skills as $skill) : ?>
                                    <span class="vortex-skill-tag"><?php echo esc_html($skill); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="vortex-project-actions">
                        <a href="<?php echo esc_url($project_url); ?>" class="vortex-button">
                            <?php _e('View Details', 'vortex-ai-marketplace'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Project Creation Modal -->
    <div id="vortex-project-modal" class="vortex-modal">
        <div class="vortex-modal-content">
            <span class="vortex-modal-close">&times;</span>
            <h3><?php _e('Create Project Proposal', 'vortex-ai-marketplace'); ?></h3>
            
            <form id="vortex-project-form" class="vortex-form">
                <?php wp_nonce_field('vortex_career_project_nonce', 'project_nonce'); ?>
                
                <div class="vortex-form-group">
                    <label for="project-title"><?php _e('Project Title', 'vortex-ai-marketplace'); ?></label>
                    <input type="text" id="project-title" name="project_title" required>
                </div>
                
                <div class="vortex-form-group">
                    <label for="project-category"><?php _e('Project Category', 'vortex-ai-marketplace'); ?></label>
                    <select id="project-category" name="project_category" required>
                        <option value=""><?php _e('Select Category', 'vortex-ai-marketplace'); ?></option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo esc_attr($category->slug); ?>">
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="vortex-form-group">
                    <label for="project-description"><?php _e('Project Description', 'vortex-ai-marketplace'); ?></label>
                    <textarea id="project-description" name="project_description" rows="5" required></textarea>
                </div>
                
                <div class="vortex-form-group">
                    <label for="project-timeline"><?php _e('Project Timeline', 'vortex-ai-marketplace'); ?></label>
                    <select id="project-timeline" name="project_timeline" required>
                        <option value=""><?php _e('Select Timeline', 'vortex-ai-marketplace'); ?></option>
                        <option value="Less than 1 week"><?php _e('Less than 1 week', 'vortex-ai-marketplace'); ?></option>
                        <option value="1-2 weeks"><?php _e('1-2 weeks', 'vortex-ai-marketplace'); ?></option>
                        <option value="2-4 weeks"><?php _e('2-4 weeks', 'vortex-ai-marketplace'); ?></option>
                        <option value="1-2 months"><?php _e('1-2 months', 'vortex-ai-marketplace'); ?></option>
                        <option value="2-3 months"><?php _e('2-3 months', 'vortex-ai-marketplace'); ?></option>
                        <option value="3+ months"><?php _e('3+ months', 'vortex-ai-marketplace'); ?></option>
                    </select>
                </div>
                
                <div class="vortex-form-group">
                    <label for="project-budget"><?php _e('Project Budget (TOLA)', 'vortex-ai-marketplace'); ?></label>
                    <input type="number" id="project-budget" name="project_budget" min="0" step="1" required>
                </div>
                
                <div class="vortex-form-group">
                    <label><?php _e('Skills Required', 'vortex-ai-marketplace'); ?></label>
                    <div class="vortex-skills-selector">
                        <label class="vortex-skill-checkbox">
                            <input type="checkbox" name="skills_required[]" value="digital-art">
                            <?php _e('Digital Art', 'vortex-ai-marketplace'); ?>
                        </label>
                        <label class="vortex-skill-checkbox">
                            <input type="checkbox" name="skills_required[]" value="traditional-art">
                            <?php _e('Traditional Art', 'vortex-ai-marketplace'); ?>
                        </label>
                        <label class="vortex-skill-checkbox">
                            <input type="checkbox" name="skills_required[]" value="ai-art">
                            <?php _e('AI Art', 'vortex-ai-marketplace'); ?>
                        </label>
                        <label class="vortex-skill-checkbox">
                            <input type="checkbox" name="skills_required[]" value="3d-modeling">
                            <?php _e('3D Modeling', 'vortex-ai-marketplace'); ?>
                        </label>
                        <label class="vortex-skill-checkbox">
                            <input type="checkbox" name="skills_required[]" value="animation">
                            <?php _e('Animation', 'vortex-ai-marketplace'); ?>
                        </label>
                        <label class="vortex-skill-checkbox">
                            <input type="checkbox" name="skills_required[]" value="character-design">
                            <?php _e('Character Design', 'vortex-ai-marketplace'); ?>
                        </label>
                        <label class="vortex-skill-checkbox">
                            <input type="checkbox" name="skills_required[]" value="concept-art">
                            <?php _e('Concept Art', 'vortex-ai-marketplace'); ?>
                        </label>
                    </div>
                </div>
                
                <div class="vortex-form-group">
                    <label for="project-files"><?php _e('Project Files (Optional)', 'vortex-ai-marketplace'); ?></label>
                    <input type="file" id="project-files" name="project_files[]" multiple>
                    <p class="vortex-help-text"><?php _e('Upload reference images or documents (max 5MB per file).', 'vortex-ai-marketplace'); ?></p>
                </div>
                
                <div class="vortex-form-actions">
                    <button type="submit" class="vortex-button vortex-button-primary">
                        <?php _e('Create Project', 'vortex-ai-marketplace'); ?>
                    </button>
                </div>
                
                <div id="vortex-project-message" class="vortex-message" style="display: none;"></div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Modal functionality
    var modal = $('#vortex-project-modal');
    var modalBtn = $('#vortex-create-project-btn');
    var closeBtn = $('.vortex-modal-close');
    
    modalBtn.on('click', function() {
        modal.css('display', 'block');
    });
    
    closeBtn.on('click', function() {
        modal.css('display', 'none');
    });
    
    $(window).on('click', function(e) {
        if ($(e.target).is(modal)) {
            modal.css('display', 'none');
        }
    });
    
    // Filter functionality
    $('.vortex-filter').on('change', function() {
        var category = $('#project-category-filter').val();
        var skill = $('#project-skill-filter').val();
        var budget = $('#project-budget-filter').val();
        
        $('.vortex-project-item').each(function() {
            var $item = $(this);
            var itemCategories = $item.data('category').split(',');
            var itemSkills = $item.data('skills').split(',');
            var itemBudget = parseFloat($item.data('budget'));
            
            var categoryMatch = !category || itemCategories.indexOf(category) !== -1;
            var skillMatch = !skill || itemSkills.indexOf(skill) !== -1;
            var budgetMatch = true;
            
            if (budget) {
                if (budget === '0-100') {
                    budgetMatch = itemBudget >= 0 && itemBudget <= 100;
                } else if (budget === '100-500') {
                    budgetMatch = itemBudget > 100 && itemBudget <= 500;
                } else if (budget === '500-1000') {
                    budgetMatch = itemBudget > 500 && itemBudget <= 1000;
                } else if (budget === '1000+') {
                    budgetMatch = itemBudget > 1000;
                }
            }
            
            if (categoryMatch && skillMatch && budgetMatch) {
                $item.show();
            } else {
                $item.hide();
            }
        });
    });
    
    // Project form submission
    $('#vortex-project-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitButton = $form.find('button[type="submit"]');
        var $message = $('#vortex-project-message');
        
        // Check if at least one skill is selected
        if ($form.find('input[name="skills_required[]"]:checked').length === 0) {
            $message.removeClass('vortex-message-success')
                    .addClass('vortex-message-error')
                    .text('<?php _e('Please select at least one required skill.', 'vortex-ai-marketplace'); ?>')
                    .show();
            return;
        }
        
        // Disable submit button and show loading state
        $submitButton.prop('disabled', true).text('<?php _e('Creating...', 'vortex-ai-marketplace'); ?>');
        $message.removeClass('vortex-message-error vortex-message-success')
                .addClass('vortex-message-info')
                .text('<?php _e('Creating your project...', 'vortex-ai-marketplace'); ?>')
                .show();
        
        // Prepare form data
        var formData = new FormData(this);
        formData.append('action', 'vortex_submit_project_proposal');
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
                    $message.removeClass('vortex-message-info vortex-message-error')
                            .addClass('vortex-message-success')
                            .text(response.data.message);
                    
                    // Redirect to project page
                    setTimeout(function() {
                        window.location.href = '/project/' + response.data.project_id;
                    }, 1500);
                } else {
                    $message.removeClass('vortex-message-info vortex-message-success')
                            .addClass('vortex-message-error')
                            .text(response.data.message);
                }
            },
            error: function() {
                $message.removeClass('vortex-message-info vortex-message-success')
                        .addClass('vortex-message-error')
                        .text(vortex_career.i18n.error);
            },
            complete: function() {
                // Re-enable submit button
                $submitButton.prop('disabled', false).text('<?php _e('Create Project', 'vortex-ai-marketplace'); ?>');
            }
        });
    });
});
</script>

<style>
.vortex-project-proposals {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

.vortex-project-header {
    margin-bottom: 20px;
}

.vortex-project-actions {
    margin-top: 15px;
}

.vortex-button {
    display: inline-block;
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    background: #f0f0f0;
    color: #333;
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

.vortex-project-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
}

.vortex-filter-group {
    display: flex;
    align-items: center;
}

.vortex-filter-group label {
    margin-right: 5px;
    font-weight: 600;
}

.vortex-filter {
    padding: 5px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.vortex-projects-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.vortex-project-item {
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 15px;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.vortex-project-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.vortex-project-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.vortex-project-title {
    margin: 0;
    font-size: 18px;
}

.vortex-project-title a {
    color: #333;
    text-decoration: none;
}

.vortex-project-status {
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.vortex-status-open {
    background: #d4edda;
    color: #155724;
}

.vortex-status-closed {
    background: #f8d7da;
    color: #721c24;
}

.vortex-project-meta {
    margin-bottom: 15px;
    font-size: 14px;
    color: #666;
}

.vortex-meta-item {
    margin-bottom: 5px;
}

.vortex-meta-label {
    font-weight: 600;
    margin-right: 5px;
}

.vortex-project-excerpt {
    margin-bottom: 15px;
    font-size: 14px;
    line-height: 1.5;
    color: #444;
}

.vortex-project-skills {
    margin-bottom: 15px;
}

.vortex-skills-label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    font-size: 14px;
}

.vortex-skills-list {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.vortex-skill-tag {
    padding: 4px 8px;
    background: #f0f0f0;
    border-radius: 4px;
    font-size: 12px;
    color: #333;
}

.vortex-no-projects {
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
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
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

.vortex-form-group input[type="text"],
.vortex-form-group input[type="number"],
.vortex-form-group textarea,
.vortex-form-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.vortex-skills-selector {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 5px;
}

.vortex-skill-checkbox {
    display: flex;
    align-items: center;
    padding: 5px 10px;
    background: #f0f0f0;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
}

.vortex-skill-checkbox:hover {
    background: #e0e0e0;
}

.vortex-skill-checkbox input {
    margin-right: 5px;
}

.vortex-help-text {
    margin-top: 5px;
    font-size: 12px;
    color: #666;
}

.vortex-form-actions {
    margin-top: 20px;
}

.vortex-message {
    margin-top: 15px;
    padding: 10px;
    border-radius: 4px;
}

.vortex-message-info {
    background-color: #d1ecf1;
    border: 1px solid #bee5eb;
    color: #0c5460;
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