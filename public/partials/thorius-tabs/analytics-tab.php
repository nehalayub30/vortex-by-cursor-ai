<div class="vortex-thorius-analytics-content">
    <div class="vortex-thorius-analytics-info">
        <h4><?php esc_html_e('Market Analytics', 'vortex-ai-marketplace'); ?></h4>
        <p><?php esc_html_e('Ask Thorius about market trends, platform statistics, and insights.', 'vortex-ai-marketplace'); ?></p>
        <div class="vortex-thorius-analytics-prompts">
            <button type="button" class="vortex-thorius-prompt-btn"><?php esc_html_e('What are the trending art styles?', 'vortex-ai-marketplace'); ?></button>
            <button type="button" class="vortex-thorius-prompt-btn"><?php esc_html_e('Show platform growth statistics', 'vortex-ai-marketplace'); ?></button>
            <button type="button" class="vortex-thorius-prompt-btn"><?php esc_html_e('Compare my performance to market average', 'vortex-ai-marketplace'); ?></button>
        </div>
    </div>
    
    <div class="vortex-thorius-analytics-visualization">
        <!-- Analytics visualizations will be inserted here dynamically -->
        <div class="vortex-thorius-loading"><?php esc_html_e('Loading analytics data...', 'vortex-ai-marketplace'); ?></div>
    </div>
    
    <?php if (is_user_logged_in()): ?>
    <div class="vortex-thorius-analytics-personal">
        <h4><?php esc_html_e('Your Analytics', 'vortex-ai-marketplace'); ?></h4>
        <div class="vortex-thorius-analytics-personal-data">
            <!-- Personal analytics will be inserted here dynamically -->
            <div class="vortex-thorius-loading"><?php esc_html_e('Loading your analytics...', 'vortex-ai-marketplace'); ?></div>
        </div>
    </div>
    <?php else: ?>
    <div class="vortex-thorius-login-prompt">
        <p><?php esc_html_e('Please log in to view your personal analytics.', 'vortex-ai-marketplace'); ?></p>
        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="vortex-thorius-login-btn"><?php esc_html_e('Log In', 'vortex-ai-marketplace'); ?></a>
    </div>
    <?php endif; ?>
</div> 