<?php
/**
 * Template for the Preferences Widget
 * Allows users to customize their experience preferences
 */

// Get current user data
$user_id = get_current_user_id();
if (!$user_id) {
    return;
}

// Get user preferences from Cloe
$cloe = VORTEX_Cloe::get_instance();
$preferences = $cloe->get_user_preferences($user_id);

// Define preference groups
$preference_groups = array(
    'content' => array(
        'title' => __('Content Preferences', 'vortex'),
        'icon' => 'fas fa-newspaper',
        'options' => array(
            'artwork_types' => array(
                'label' => __('Artwork Types', 'vortex'),
                'options' => array(
                    'digital' => __('Digital Art', 'vortex'),
                    'traditional' => __('Traditional Art', 'vortex'),
                    'photography' => __('Photography', 'vortex'),
                    'sculpture' => __('Sculpture', 'vortex')
                )
            ),
            'content_frequency' => array(
                'label' => __('Content Frequency', 'vortex'),
                'options' => array(
                    'daily' => __('Daily', 'vortex'),
                    'weekly' => __('Weekly', 'vortex'),
                    'monthly' => __('Monthly', 'vortex')
                )
            )
        )
    ),
    'notifications' => array(
        'title' => __('Notification Preferences', 'vortex'),
        'icon' => 'fas fa-bell',
        'options' => array(
            'email_notifications' => array(
                'label' => __('Email Notifications', 'vortex'),
                'options' => array(
                    'all' => __('All Updates', 'vortex'),
                    'important' => __('Important Only', 'vortex'),
                    'none' => __('None', 'vortex')
                )
            ),
            'push_notifications' => array(
                'label' => __('Push Notifications', 'vortex'),
                'options' => array(
                    'enabled' => __('Enabled', 'vortex'),
                    'disabled' => __('Disabled', 'vortex')
                )
            )
        )
    ),
    'privacy' => array(
        'title' => __('Privacy Settings', 'vortex'),
        'icon' => 'fas fa-shield-alt',
        'options' => array(
            'profile_visibility' => array(
                'label' => __('Profile Visibility', 'vortex'),
                'options' => array(
                    'public' => __('Public', 'vortex'),
                    'private' => __('Private', 'vortex'),
                    'friends' => __('Friends Only', 'vortex')
                )
            ),
            'activity_sharing' => array(
                'label' => __('Activity Sharing', 'vortex'),
                'options' => array(
                    'all' => __('Share All', 'vortex'),
                    'selected' => __('Share Selected', 'vortex'),
                    'none' => __('Share None', 'vortex')
                )
            )
        )
    )
);
?>

<div class="vortex-cloe-widget vortex-preferences-widget">
    <div class="vortex-cloe-widget-header">
        <i class="vortex-cloe-widget-icon fas fa-cog"></i>
        <h2 class="vortex-cloe-widget-title"><?php esc_html_e('Your Preferences', 'vortex'); ?></h2>
    </div>

    <div class="vortex-preferences-content">
        <form class="vortex-preferences-form" id="vortex-preferences-form">
            <?php foreach ($preference_groups as $group_id => $group) : ?>
                <div class="vortex-preference-group" data-type="<?php echo esc_attr($group_id); ?>">
                    <h3 class="vortex-preference-group-title">
                        <i class="<?php echo esc_attr($group['icon']); ?>"></i>
                        <?php echo esc_html($group['title']); ?>
                    </h3>

                    <?php foreach ($group['options'] as $option_id => $option) : ?>
                        <div class="vortex-preference-option-group">
                            <label class="vortex-preference-option-label">
                                <?php echo esc_html($option['label']); ?>
                            </label>

                            <div class="vortex-preference-options">
                                <?php foreach ($option['options'] as $value => $label) : ?>
                                    <div class="vortex-preference-option <?php echo isset($preferences[$option_id]) && $preferences[$option_id] === $value ? 'selected' : ''; ?>"
                                         data-value="<?php echo esc_attr($value); ?>">
                                        <i class="fas fa-check"></i>
                                        <?php echo esc_html($label); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <div class="vortex-preferences-actions">
                <button type="submit" class="vortex-button">
                    <i class="fas fa-save"></i>
                    <?php esc_html_e('Save Preferences', 'vortex'); ?>
                </button>
                <button type="button" class="vortex-button vortex-button-secondary vortex-reset-preferences">
                    <i class="fas fa-undo"></i>
                    <?php esc_html_e('Reset to Default', 'vortex'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.vortex-preference-group {
    background: var(--vortex-background, #ffffff);
    border-radius: 8px;
    border: 1px solid var(--vortex-border-color, #e9ecef);
    padding: 20px;
    margin-bottom: 20px;
}

.vortex-preference-group-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    color: var(--vortex-text, #212529);
    font-size: 1.1rem;
}

.vortex-preference-group-title i {
    color: var(--vortex-primary, #007bff);
}

.vortex-preference-option-group {
    margin-bottom: 20px;
}

.vortex-preference-option-group:last-child {
    margin-bottom: 0;
}

.vortex-preference-option-label {
    display: block;
    margin-bottom: 10px;
    color: var(--vortex-text, #212529);
    font-weight: 500;
}

.vortex-preference-options {
    display: grid;
    gap: 10px;
}

.vortex-preference-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    background: var(--vortex-background-alt, #f8f9fa);
    border: 1px solid var(--vortex-border-color, #e9ecef);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.vortex-preference-option i {
    opacity: 0;
    color: #ffffff;
    transition: opacity 0.2s ease;
}

.vortex-preference-option:hover {
    background: var(--vortex-primary-light, #e7f1ff);
    border-color: var(--vortex-primary, #007bff);
}

.vortex-preference-option.selected {
    background: var(--vortex-primary, #007bff);
    border-color: var(--vortex-primary, #007bff);
    color: #ffffff;
}

.vortex-preference-option.selected i {
    opacity: 1;
}

.vortex-preferences-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

@media (max-width: 768px) {
    .vortex-preference-group {
        padding: 15px;
    }
    
    .vortex-preferences-actions {
        flex-direction: column;
    }
    
    .vortex-button {
        width: 100%;
    }
}
</style> 