<div class="vortex-thorius-events-content">
    <div class="vortex-thorius-events-info">
        <h4><?php esc_html_e('Events & Exhibitions', 'vortex-ai-marketplace'); ?></h4>
        <p><?php esc_html_e('Ask Thorius about upcoming events, exhibitions, and auctions.', 'vortex-ai-marketplace'); ?></p>
        <div class="vortex-thorius-events-prompts">
            <button type="button" class="vortex-thorius-prompt-btn"><?php esc_html_e('Show upcoming exhibitions', 'vortex-ai-marketplace'); ?></button>
            <button type="button" class="vortex-thorius-prompt-btn"><?php esc_html_e('When is the next NFT auction?', 'vortex-ai-marketplace'); ?></button>
            <button type="button" class="vortex-thorius-prompt-btn"><?php esc_html_e('Find events near me', 'vortex-ai-marketplace'); ?></button>
        </div>
    </div>
    
    <div class="vortex-thorius-events-upcoming">
        <h4><?php esc_html_e('Upcoming Events', 'vortex-ai-marketplace'); ?></h4>
        <div class="vortex-thorius-events-list">
            <!-- Upcoming events will be inserted here dynamically -->
            <div class="vortex-thorius-loading"><?php esc_html_e('Loading upcoming events...', 'vortex-ai-marketplace'); ?></div>
        </div>
    </div>
    
    <?php if (is_user_logged_in()): ?>
    <div class="vortex-thorius-events-create">
        <h4><?php esc_html_e('Create Event', 'vortex-ai-marketplace'); ?></h4>
        <p><?php esc_html_e('Want to host your own event or exhibition?', 'vortex-ai-marketplace'); ?></p>
        <div class="vortex-thorius-events-actions">
            <a href="<?php echo esc_url(home_url('/create-exhibition/')); ?>" class="vortex-thorius-button"><?php esc_html_e('Create Exhibition', 'vortex-ai-marketplace'); ?></a>
            <a href="<?php echo esc_url(home_url('/create-auction/')); ?>" class="vortex-thorius-button"><?php esc_html_e('Schedule Auction', 'vortex-ai-marketplace'); ?></a>
            <button type="button" class="vortex-thorius-button vortex-thorius-event-wizard"><?php esc_html_e('Event Wizard', 'vortex-ai-marketplace'); ?></button>
        </div>
    </div>
    <?php endif; ?>
</div> 