<?php
/**
 * Task Automation Template
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get user's existing tasks
$task_automation = Vortex_AI_Marketplace::get_instance()->task_automation;
$user_tasks = $task_automation->get_user_tasks($user_id, $limit);
?>

<div class="vortex-task-automation <?php echo esc_attr($atts['class']); ?>">
    <h3 class="vortex-section-title"><?php _e('AI Task Automation', 'vortex-ai-marketplace'); ?></h3>
    
    <?php if ($show_create): ?>
    <div class="vortex-create-task-section">
        <h4><?php _e('Create Automated Task', 'vortex-ai-marketplace'); ?></h4>
        
        <form class="vortex-create-task-form">
            <div class="vortex-form-row">
                <label for="task_name"><?php _e('Task Name', 'vortex-ai-marketplace'); ?></label>
                <input type="text" id="task_name" name="task_name" required placeholder="<?php esc_attr_e('My Weekly Market Analysis', 'vortex-ai-marketplace'); ?>" />
            </div>
            
            <div class="vortex-form-row">
                <label for="task_type"><?php _e('Task Type', 'vortex-ai-marketplace'); ?></label>
                <select id="task_type" name="task_type" required>
                    <option value="artwork_generation"><?php _e('Artwork Generation (HURAII)', 'vortex-ai-marketplace'); ?></option>
                    <option value="market_analysis"><?php _e('Market Analysis (CLOE)', 'vortex-ai-marketplace'); ?></option>
                    <option value="strategy_recommendation"><?php _e('Strategy Recommendation (Strategist)', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
            
            <div class="vortex-form-row vortex-task-params" id="params-artwork_generation" style="display:none;">
                <label for="artwork_prompt"><?php _e('Artwork Prompt', 'vortex-ai-marketplace'); ?></label>
                <textarea id="artwork_prompt" name="artwork_prompt" placeholder="<?php esc_attr_e('A beautiful mountain landscape at sunset', 'vortex-ai-marketplace'); ?>"></textarea>
                
                <label for="artwork_style"><?php _e('Style', 'vortex-ai-marketplace'); ?></label>
                <select id="artwork_style" name="artwork_style">
                    <option value="realistic"><?php _e('Realistic', 'vortex-ai-marketplace'); ?></option>
                    <option value="anime"><?php _e('Anime', 'vortex-ai-marketplace'); ?></option>
                    <option value="digital"><?php _e('Digital Art', 'vortex-ai-marketplace'); ?></option>
                    <option value="oil-painting"><?php _e('Oil Painting', 'vortex-ai-marketplace'); ?></option>
                    <option value="watercolor"><?php _e('Watercolor', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
            
            <div class="vortex-form-row vortex-task-params" id="params-market_analysis" style="display:none;">
                <label for="market_type"><?php _e('Market', 'vortex-ai-marketplace'); ?></label>
                <select id="market_type" name="market_type">
                    <option value="crypto"><?php _e('Cryptocurrency', 'vortex-ai-marketplace'); ?></option>
                    <option value="stocks"><?php _e('Stocks', 'vortex-ai-marketplace'); ?></option>
                    <option value="commodities"><?php _e('Commodities', 'vortex-ai-marketplace'); ?></option>
                    <option value="forex"><?php _e('Forex', 'vortex-ai-marketplace'); ?></option>
                </select>
                
                <label for="market_timeframe"><?php _e('Timeframe', 'vortex-ai-marketplace'); ?></label>
                <select id="market_timeframe" name="market_timeframe">
                    <option value="short-term"><?php _e('Short-term', 'vortex-ai-marketplace'); ?></option>
                    <option value="medium-term"><?php _e('Medium-term', 'vortex-ai-marketplace'); ?></option>
                    <option value="long-term"><?php _e('Long-term', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
            
            <div class="vortex-form-row vortex-task-params" id="params-strategy_recommendation" style="display:none;">
                <label for="strategy_industry"><?php _e('Industry', 'vortex-ai-marketplace'); ?></label>
                <select id="strategy_industry" name="strategy_industry">
                    <option value="technology"><?php _e('Technology', 'vortex-ai-marketplace'); ?></option>
                    <option value="retail"><?php _e('Retail', 'vortex-ai-marketplace'); ?></option>
                    <option value="finance"><?php _e('Finance', 'vortex-ai-marketplace'); ?></option>
                    <option value="healthcare"><?php _e('Healthcare', 'vortex-ai-marketplace'); ?></option>
                    <option value="education"><?php _e('Education', 'vortex-ai-marketplace'); ?></option>
                    <option value="manufacturing"><?php _e('Manufacturing', 'vortex-ai-marketplace'); ?></option>
                </select>
                
                <label for="strategy_focus"><?php _e('Focus Area', 'vortex-ai-marketplace'); ?></label>
                <select id="strategy_focus" name="strategy_focus">
                    <option value="growth"><?php _e('Growth', 'vortex-ai-marketplace'); ?></option>
                    <option value="efficiency"><?php _e('Efficiency', 'vortex-ai-marketplace'); ?></option>
                    <option value="innovation"><?php _e('Innovation', 'vortex-ai-marketplace'); ?></option>
                    <option value="market-entry"><?php _e('Market Entry', 'vortex-ai-marketplace'); ?></option>
                    <option value="risk-management"><?php _e('Risk Management', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
            
            <div class="vortex-form-row">
                <label for="task_frequency"><?php _e('Frequency', 'vortex-ai-marketplace'); ?></label>
                <select id="task_frequency" name="task_frequency">
                    <option value="daily"><?php _e('Daily', 'vortex-ai-marketplace'); ?></option>
                    <option value="weekly"><?php _e('Weekly', 'vortex-ai-marketplace'); ?></option>
                    <option value="monthly"><?php _e('Monthly', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
            
            <div class="vortex-form-message"></div>
            
            <div class="vortex-form-row">
                <button type="submit" class="vortex-button-primary"><?php _e('Create Automated Task', 'vortex-ai-marketplace'); ?></button>
            </div>
            
            <?php wp_nonce_field('vortex_create_automation_task', 'automation_nonce'); ?>
        </form>
    </div>
    <?php endif; ?>
    
    <?php if ($show_existing): ?>
    <div class="vortex-existing-tasks-section">
        <h4><?php _e('Your Automated Tasks', 'vortex-ai-marketplace'); ?></h4>
        
        <?php if (empty($user_tasks)): ?>
        <p class="vortex-no-tasks-message"><?php _e('You don\'t have any automated tasks yet. Create one above to get started.', 'vortex-ai-marketplace'); ?></p>
        <?php else: ?>
        <div class="vortex-tasks-list">
            <?php foreach ($user_tasks as $task): ?>
            <div class="vortex-task-item" data-id="<?php echo esc_attr($task['id']); ?>">
                <div class="vortex-task-header">
                    <h5 class="vortex-task-name"><?php echo esc_html($task['task_name']); ?></h5>
                    <span class="vortex-task-status <?php echo $task['active'] ? 'vortex-active' : 'vortex-inactive'; ?>">
                        <?php echo $task['active'] ? esc_html__('Active', 'vortex-ai-marketplace') : esc_html__('Inactive', 'vortex-ai-marketplace'); ?>
                    </span>
                </div>
                
                <div class="vortex-task-details">
                    <p class="vortex-task-type">
                        <strong><?php _e('Type:', 'vortex-ai-marketplace'); ?></strong> 
                        <?php echo esc_html($task_automation->get_task_type_label($task['task_type'])); ?>
                    </p>
                    
                    <p class="vortex-task-frequency">
                        <strong><?php _e('Frequency:', 'vortex-ai-marketplace'); ?></strong> 
                        <?php echo esc_html(ucfirst($task['frequency'])); ?>
                    </p>
                    
                    <?php if ($task['last_run']): ?>
                    <p class="vortex-task-last-run">
                        <strong><?php _e('Last Run:', 'vortex-ai-marketplace'); ?></strong> 
                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($task['last_run']))); ?>
                    </p>
                    <?php endif; ?>
                    
                    <p class="vortex-task-next-run">
                        <strong><?php _e('Next Run:', 'vortex-ai-marketplace'); ?></strong> 
                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($task['next_run']))); ?>
                    </p>
                </div>
                
                <div class="vortex-task-actions">
                    <button class="vortex-toggle-task" data-id="<?php echo esc_attr($task['id']); ?>" data-active="<?php echo $task['active'] ? '1' : '0'; ?>">
                        <?php echo $task['active'] ? esc_html__('Deactivate', 'vortex-ai-marketplace') : esc_html__('Activate', 'vortex-ai-marketplace'); ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div> 