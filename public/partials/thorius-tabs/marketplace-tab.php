<div class="vortex-thorius-marketplace-content">
    <div class="vortex-thorius-marketplace-info">
        <h4><?php esc_html_e('Marketplace Assistance', 'vortex-ai-marketplace'); ?></h4>
        <p><?php esc_html_e('Ask Thorius about marketplace listings, buying, selling, and pricing.', 'vortex-ai-marketplace'); ?></p>
        <div class="vortex-thorius-marketplace-prompts">
            <button type="button" class="vortex-thorius-prompt-btn"><?php esc_html_e('Show top selling artworks', 'vortex-ai-marketplace'); ?></button>
            <button type="button" class="vortex-thorius-prompt-btn"><?php esc_html_e('Help me price my artwork', 'vortex-ai-marketplace'); ?></button>
            <button type="button" class="vortex-thorius-prompt-btn"><?php esc_html_e('Find undervalued NFTs', 'vortex-ai-marketplace'); ?></button>
        </div>
    </div>
    
    <div class="vortex-thorius-marketplace-trending">
        <h4><?php esc_html_e('Trending Now', 'vortex-ai-marketplace'); ?></h4>
        <div class="vortex-thorius-marketplace-grid">
            <!-- Trending marketplace items will be inserted here dynamically -->
            <div class="vortex-thorius-loading"><?php esc_html_e('Loading trending items...', 'vortex-ai-marketplace'); ?></div>
        </div>
    </div>
    
    <?php if (is_user_logged_in()): ?>
    <div class="vortex-thorius-marketplace-actions">
        <h4><?php esc_html_e('Marketplace Actions', 'vortex-ai-marketplace'); ?></h4>
        <div class="vortex-thorius-marketplace-buttons">
            <a href="<?php echo esc_url(home_url('/create-listing/')); ?>" class="vortex-thorius-button"><?php esc_html_e('Create Listing', 'vortex-ai-marketplace'); ?></a>
            <a href="<?php echo esc_url(home_url('/my-listings/')); ?>" class="vortex-thorius-button"><?php esc_html_e('My Listings', 'vortex-ai-marketplace'); ?></a>
            <a href="<?php echo esc_url(home_url('/my-offers/')); ?>" class="vortex-thorius-button"><?php esc_html_e('My Offers', 'vortex-ai-marketplace'); ?></a>
            <button type="button" class="vortex-thorius-button vortex-thorius-listing-wizard"><?php esc_html_e('Listing Wizard', 'vortex-ai-marketplace'); ?></button>
        </div>
    </div>
    <?php else: ?>
    <div class="vortex-thorius-login-prompt">
        <p><?php esc_html_e('Please log in to access marketplace actions.', 'vortex-ai-marketplace'); ?></p>
        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="vortex-thorius-login-btn"><?php esc_html_e('Log In', 'vortex-ai-marketplace'); ?></a>
    </div>
    <?php endif; ?>
</div> 