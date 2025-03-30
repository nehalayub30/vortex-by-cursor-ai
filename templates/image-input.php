<div class="thorius-image-input-container">
    <div class="thorius-image-drop-zone">
        <div class="thorius-image-icon"></div>
        <p><?php esc_html_e('Drag & drop an image or click to browse', 'vortex-ai-marketplace'); ?></p>
    </div>
    <input type="file" class="thorius-image-file-input" accept="image/*" style="display:none;">
    <div class="thorius-image-preview" style="display:none;">
        <img src="" alt="<?php esc_attr_e('Preview', 'vortex-ai-marketplace'); ?>" class="thorius-image-preview-img">
        <div class="thorius-image-preview-controls">
            <button class="thorius-image-remove button"><?php esc_html_e('Remove', 'vortex-ai-marketplace'); ?></button>
            <button class="thorius-image-submit button button-primary"><?php esc_html_e('Send', 'vortex-ai-marketplace'); ?></button>
        </div>
    </div>
    <div class="thorius-image-caption-container" style="display:none;">
        <label for="thorius-image-caption"><?php esc_html_e('Add a caption (optional):', 'vortex-ai-marketplace'); ?></label>
        <textarea id="thorius-image-caption" class="thorius-image-caption" rows="2"></textarea>
    </div>
</div> 