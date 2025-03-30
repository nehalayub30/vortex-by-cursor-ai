<div class="vortex-thorius-ai-content">
    <div class="vortex-thorius-ai-info">
        <h4><?php esc_html_e('AI Tools', 'vortex-ai-marketplace'); ?></h4>
        <p><?php esc_html_e('Ask Thorius about AI tools available on VORTEX, including HURAII, CLOE, and more.', 'vortex-ai-marketplace'); ?></p>
        <div class="vortex-thorius-ai-prompts">
            <button type="button" class="vortex-thorius-prompt-btn"><?php esc_html_e('What can HURAII do?', 'vortex-ai-marketplace'); ?></button>
            <button type="button" class="vortex-thorius-prompt-btn"><?php esc_html_e('How does CLOE help discover art?', 'vortex-ai-marketplace'); ?></button>
            <button type="button" class="vortex-thorius-prompt-btn"><?php esc_html_e('Tell me about Business Strategist', 'vortex-ai-marketplace'); ?></button>
        </div>
    </div>
    
    <div class="vortex-thorius-ai-tools">
        <h4><?php esc_html_e('Available AI Tools', 'vortex-ai-marketplace'); ?></h4>
        
        <div class="vortex-thorius-ai-grid">
            <div class="vortex-thorius-ai-card">
                <div class="vortex-thorius-ai-card-icon huraii-icon"></div>
                <h5><?php esc_html_e('HURAII', 'vortex-ai-marketplace'); ?></h5>
                <p><?php esc_html_e('Advanced AI image generation and transformation', 'vortex-ai-marketplace'); ?></p>
                <a href="<?php echo esc_url(home_url('/huraii-studio/')); ?>" class="vortex-thorius-ai-action"><?php esc_html_e('Use HURAII', 'vortex-ai-marketplace'); ?></a>
            </div>
            
            <div class="vortex-thorius-ai-card">
                <div class="vortex-thorius-ai-card-icon cloe-icon"></div>
                <h5><?php esc_html_e('CLOE', 'vortex-ai-marketplace'); ?></h5>
                <p><?php esc_html_e('Art discovery and curation assistant', 'vortex-ai-marketplace'); ?></p>
                <a href="<?php echo esc_url(home_url('/art-discovery/')); ?>" class="vortex-thorius-ai-action"><?php esc_html_e('Explore with CLOE', 'vortex-ai-marketplace'); ?></a>
            </div>
            
            <div class="vortex-thorius-ai-card">
                <div class="vortex-thorius-ai-card-icon strategist-icon"></div>
                <h5><?php esc_html_e('Business Strategist', 'vortex-ai-marketplace'); ?></h5>
                <p><?php esc_html_e('Market insights and trend analysis', 'vortex-ai-marketplace'); ?></p>
                <a href="<?php echo esc_url(home_url('/market-insights/')); ?>" class="vortex-thorius-ai-action"><?php esc_html_e('View Insights', 'vortex-ai-marketplace'); ?></a>
            </div>
            
            <div class="vortex-thorius-ai-card">
                <div class="vortex-thorius-ai-card-icon thorius-icon"></div>
                <h5><?php esc_html_e('Thorius', 'vortex-ai-marketplace'); ?></h5>
                <p><?php esc_html_e('AI concierge with multi-agent capabilities', 'vortex-ai-marketplace'); ?></p>
                <button type="button" class="vortex-thorius-ai-action switch-to-chat"><?php esc_html_e('Chat with Thorius', 'vortex-ai-marketplace'); ?></button>
            </div>
        </div>
    </div>
    
    <div class="vortex-thorius-ai-usage">
        <h4><?php esc_html_e('Your AI Activity', 'vortex-ai-marketplace'); ?></h4>
        <?php if (is_user_logged_in()): ?>
            <div class="vortex-thorius-ai-stats">
                <!-- User's AI activity stats will be inserted here dynamically -->
                <div class="vortex-thorius-loading"><?php esc_html_e('Loading your AI activity...', 'vortex-ai-marketplace'); ?></div>
            </div>
        <?php else: ?>
            <div class="vortex-thorius-login-prompt">
                <p><?php esc_html_e('Please log in to view your AI activity.', 'vortex-ai-marketplace'); ?></p>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="vortex-thorius-login-btn"><?php esc_html_e('Log In', 'vortex-ai-marketplace'); ?></a>
            </div>
        <?php endif; ?>
    </div>
</div> 