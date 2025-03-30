<?php
/**
 * Template for displaying scheduled events.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="vortex-scheduled-events">
    <h2><?php esc_html_e('Scheduled Events', 'vortex-ai-marketplace'); ?></h2>
    
    <?php if (empty($scheduled_events)) : ?>
        <div class="vortex-notice">
            <?php esc_html_e('You do not have any scheduled events.', 'vortex-ai-marketplace'); ?>
        </div>
    <?php else : ?>
        <div class="vortex-events-list">
            <?php foreach ($scheduled_events as $event) : ?>
                <div class="vortex-event-item" data-event-id="<?php echo esc_attr($event->id); ?>">
                    <div class="event-header">
                        <h3><?php echo esc_html($event->title); ?></h3>
                        <span class="event-type <?php echo esc_attr($event->type); ?>">
                            <?php echo esc_html(ucfirst($event->type)); ?>
                        </span>
                    </div>
                    
                    <div class="event-details">
                        <p class="event-time">
                            <strong><?php esc_html_e('Scheduled for:', 'vortex-ai-marketplace'); ?></strong>
                            <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($event->scheduled_time))); ?>
                        </p>
                        
                        <p class="event-status">
                            <strong><?php esc_html_e('Status:', 'vortex-ai-marketplace'); ?></strong>
                            <span class="status-<?php echo esc_attr($event->status); ?>">
                                <?php echo esc_html(ucfirst($event->status)); ?>
                            </span>
                        </p>
                    </div>
                    
                    <div class="event-actions">
                        <button class="vortex-button vortex-button-secondary cancel-event" 
                                data-event-id="<?php echo esc_attr($event->id); ?>">
                            <?php esc_html_e('Cancel', 'vortex-ai-marketplace'); ?>
                        </button>
                        <button class="vortex-button vortex-button-primary reschedule-event" 
                                data-event-id="<?php echo esc_attr($event->id); ?>"
                                data-event-type="<?php echo esc_attr($event->type); ?>">
                            <?php esc_html_e('Reschedule', 'vortex-ai-marketplace'); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Handle event cancellation
            $('.cancel-event').on('click', function() {
                const eventId = $(this).data('event-id');
                const eventItem = $(this).closest('.vortex-event-item');
                
                if (confirm('<?php esc_html_e('Are you sure you want to cancel this event?', 'vortex-ai-marketplace'); ?>')) {
                    $.ajax({
                        url: vortex_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'vortex_cancel_scheduled_event',
                            nonce: vortex_ajax.nonce,
                            event_id: eventId
                        },
                        success: function(response) {
                            if (response.success) {
                                eventItem.fadeOut(function() {
                                    $(this).remove();
                                });
                            } else {
                                alert(response.data.message);
                            }
                        },
                        error: function() {
                            alert('<?php esc_html_e('Failed to cancel event. Please try again.', 'vortex-ai-marketplace'); ?>');
                        }
                    });
                }
            });
            
            // Handle event rescheduling
            $('.reschedule-event').on('click', function() {
                const eventId = $(this).data('event-id');
                const eventType = $(this).data('event-type');
                
                // Load the appropriate rescheduling form
                $.ajax({
                    url: vortex_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'vortex_get_reschedule_form',
                        nonce: vortex_ajax.nonce,
                        event_id: eventId,
                        event_type: eventType
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show the form in a modal or appropriate container
                            // Implementation depends on your UI/UX preferences
                            console.log('Reschedule form loaded:', response.data);
                        } else {
                            alert(response.data.message);
                        }
                    },
                    error: function() {
                        alert('<?php esc_html_e('Failed to load reschedule form. Please try again.', 'vortex-ai-marketplace'); ?>');
                    }
                });
            });
        });
        </script>
        
        <style>
        .vortex-events-list {
            margin-top: 20px;
        }
        
        .vortex-event-item {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .event-type {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .event-type.nft {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .event-type.exhibition {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .event-type.auction {
            background: #e8f5e9;
            color: #388e3c;
        }
        
        .event-details {
            margin: 10px 0;
        }
        
        .event-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .status-pending {
            color: #f57c00;
        }
        
        .status-processing {
            color: #1976d2;
        }
        
        .status-completed {
            color: #388e3c;
        }
        
        .status-cancelled {
            color: #d32f2f;
        }
        </style>
    <?php endif; ?>
</div> 