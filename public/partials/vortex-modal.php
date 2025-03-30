<?php
/**
 * Template for the modal container.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="vortex-modal" style="display: none;">
    <div class="vortex-modal-overlay"></div>
    <div class="vortex-modal-container">
        <div class="vortex-modal-content">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Function to show modal
    window.showVortexModal = function(content) {
        $('.vortex-modal-content').html(content);
        $('.vortex-modal').fadeIn();
    };
    
    // Close modal when clicking overlay
    $('.vortex-modal-overlay').on('click', function() {
        $('.vortex-modal').fadeOut();
    });
    
    // Close modal when pressing ESC key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('.vortex-modal').fadeOut();
        }
    });
});
</script>

<style>
.vortex-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 999999;
    display: none;
}

.vortex-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.vortex-modal-container {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.vortex-modal-content {
    padding: 20px;
}

/* Scrollbar styles */
.vortex-modal-container::-webkit-scrollbar {
    width: 8px;
}

.vortex-modal-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.vortex-modal-container::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.vortex-modal-container::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Animation */
@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: translate(-50%, -48%);
    }
    to {
        opacity: 1;
        transform: translate(-50%, -50%);
    }
}

.vortex-modal-container {
    animation: modalFadeIn 0.3s ease-out;
}
</style> 