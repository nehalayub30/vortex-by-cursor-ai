<?php
/**
 * Template for the Greeting Widget
 * Displays personalized greetings and user-specific information
 */

// Get current user data
$user_id = get_current_user_id();
if (!$user_id) {
    return;
}

$user = get_userdata($user_id);
$user_name = $user->display_name;
$user_avatar = get_avatar_url($user_id, array('size' => 48));

// Get greeting data from Cloe
$cloe = VORTEX_Cloe::get_instance();
$greeting_data = $cloe->get_personalized_greeting($user_id);
?>

<div class="vortex-cloe-widget vortex-greeting-widget">
    <div class="vortex-cloe-widget-header">
        <i class="vortex-cloe-widget-icon fas fa-comment-dots"></i>
        <h2 class="vortex-cloe-widget-title"><?php esc_html_e('Personalized Greeting', 'vortex'); ?></h2>
    </div>

    <div class="vortex-greeting-content">
        <div class="vortex-greeting-avatar">
            <img src="<?php echo esc_url($user_avatar); ?>" alt="<?php echo esc_attr($user_name); ?>">
        </div>

        <div class="vortex-greeting-text">
            <div class="vortex-greeting-time">
                <?php
                $hour = current_time('G');
                if ($hour < 12) {
                    esc_html_e('Good morning', 'vortex');
                } elseif ($hour < 18) {
                    esc_html_e('Good afternoon', 'vortex');
                } else {
                    esc_html_e('Good evening', 'vortex');
                }
                ?>, <?php echo esc_html($user_name); ?>!
            </div>

            <div class="vortex-greeting-message">
                <?php echo wp_kses_post($greeting_data['message']); ?>
            </div>

            <?php if (!empty($greeting_data['stats'])) : ?>
                <div class="vortex-greeting-stats">
                    <?php foreach ($greeting_data['stats'] as $stat) : ?>
                        <div class="vortex-greeting-stat">
                            <span class="vortex-greeting-stat-value"><?php echo esc_html($stat['value']); ?></span>
                            <span class="vortex-greeting-stat-label"><?php echo esc_html($stat['label']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <button class="vortex-button vortex-refresh-greeting">
                <i class="fas fa-sync-alt"></i>
                <?php esc_html_e('Refresh Greeting', 'vortex'); ?>
            </button>
        </div>
    </div>

    <?php if (!empty($greeting_data['quick_actions'])) : ?>
        <div class="vortex-greeting-actions">
            <?php foreach ($greeting_data['quick_actions'] as $action) : ?>
                <a href="<?php echo esc_url($action['url']); ?>" class="vortex-button vortex-button-secondary">
                    <i class="<?php echo esc_attr($action['icon']); ?>"></i>
                    <?php echo esc_html($action['label']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.vortex-greeting-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
    margin: 15px 0;
}

.vortex-greeting-stat {
    text-align: center;
    padding: 10px;
    background: var(--vortex-background-alt, #f8f9fa);
    border-radius: 6px;
    transition: all 0.2s ease;
}

.vortex-greeting-stat:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.vortex-greeting-stat-value {
    display: block;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--vortex-primary, #007bff);
    margin-bottom: 5px;
}

.vortex-greeting-stat-label {
    display: block;
    font-size: 0.875rem;
    color: var(--vortex-text-muted, #6c757d);
}

.vortex-greeting-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.vortex-button-secondary {
    background: var(--vortex-background-alt, #f8f9fa);
    color: var(--vortex-text, #212529);
    border: 1px solid var(--vortex-border-color, #e9ecef);
}

.vortex-button-secondary:hover {
    background: var(--vortex-primary-light, #e7f1ff);
    border-color: var(--vortex-primary, #007bff);
    color: var(--vortex-primary, #007bff);
}

@media (max-width: 768px) {
    .vortex-greeting-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .vortex-greeting-actions {
        flex-direction: column;
    }
    
    .vortex-button-secondary {
        width: 100%;
    }
}
</style> 