<?php
namespace Vortex\AI;

class VortexDeactivator {
    public static function deactivate() {
        // Clear scheduled hooks
        wp_clear_scheduled_hook('vortex_daily_cleanup');
        wp_clear_scheduled_hook('vortex_sync_blockchain');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
