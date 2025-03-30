<div class="vortex-thorius-tola-content">
    <div class="vortex-thorius-tola-info">
        <h4><?php esc_html_e('Time-limited Ownership License Agreement', 'vortex-ai-marketplace'); ?></h4>
        <p><?php esc_html_e('Ask Thorius about TOLA contracts, ownership rights, and licensing terms.', 'vortex-ai-marketplace'); ?></p>
        <div class="vortex-thorius-tola-prompts">
            <button type="button" class="vortex-thorius-prompt-btn"><?php esc_html_e('Explain TOLA in simple terms', 'vortex-ai-marketplace'); ?></button>
            <button type="button" class="vortex-thorius-prompt-btn"><?php esc_html_e('What rights do I get as a TOLA holder?', 'vortex-ai-marketplace'); ?></button>
            <button type="button" class="vortex-thorius-prompt-btn"><?php esc_html_e('How do TOLA contracts differ from standard NFTs?', 'vortex-ai-marketplace'); ?></button>
        </div>
    </div>
    
    <div class="vortex-thorius-tola-contracts">
        <?php if (is_user_logged_in()): ?>
            <h4><?php esc_html_e('Your TOLA Contracts', 'vortex-ai-marketplace'); ?></h4>
            <div class="vortex-thorius-tola-list">
                <!-- User's TOLA contracts will be inserted here dynamically -->
                <div class="vortex-thorius-loading"><?php esc_html_e('Loading your contracts...', 'vortex-ai-marketplace'); ?></div>
            </div>
        <?php else: ?>
            <div class="vortex-thorius-login-prompt">
                <p><?php esc_html_e('Please log in to view your TOLA contracts.', 'vortex-ai-marketplace'); ?></p>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="vortex-thorius-login-btn"><?php esc_html_e('Log In', 'vortex-ai-marketplace'); ?></a>
            </div>
        <?php endif; ?>
    </div>
</div> 