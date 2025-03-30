<div id="<?php echo esc_attr($agent_id); ?>" class="thorius-agent-container thorius-agent-<?php echo esc_attr($agent); ?>">
    <div class="thorius-agent-header">
        <div class="thorius-agent-header-title"><?php echo strtoupper($agent); ?></div>
    </div>
    
    <div class="thorius-agent-messages">
        <div class="thorius-message thorius-message-bot">
            <div class="thorius-avatar thorius-avatar-<?php echo esc_attr($agent); ?>"></div>
            <div class="thorius-message-content"><?php echo esc_html($welcome_message); ?></div>
        </div>
    </div>
    
    <div class="thorius-agent-input">
        <textarea placeholder="<?php echo esc_attr($placeholder); ?>" rows="1"></textarea>
        <?php if ($voice): ?>
        <div class="thorius-voice-button"><span class="thorius-voice-icon"></span></div>
        <?php endif; ?>
        <div class="thorius-send-button"><span class="thorius-send-icon"></span></div>
    </div>
</div> 