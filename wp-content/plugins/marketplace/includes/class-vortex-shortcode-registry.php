class VORTEX_Shortcode_Registry {
    private const SHORTCODE_MAP = [
        // Marketplace Shortcodes
        'vortex_marketplace' => 'marketplace/views/marketplace-display.php',
        'vortex_artist_gallery' => 'marketplace/views/artist-gallery.php',
        'vortex_artwork_details' => 'marketplace/views/artwork-details.php',
        'vortex_artwork_metrics' => 'marketplace/views/artwork-metrics.php',
        
        // AI Agents Shortcodes
        'vortex_huraii_analysis' => 'ai-agents/huraii/views/analysis-display.php',
        'vortex_cloe_insights' => 'ai-agents/cloe/views/insights-display.php',
        'vortex_strategist_advice' => 'ai-agents/business-strategist/views/advice-display.php',
        'vortex_thorius_security' => 'ai-agents/thorius/views/security-display.php',
        
        // Blockchain & TOLA Shortcodes
        'vortex_tola_metrics' => 'blockchain/views/tola-metrics.php',
        'vortex_tola_explorer' => 'blockchain/views/tola-explorer.php',
        'vortex_contract_details' => 'blockchain/views/contract-details.php',
        
        // DAO Shortcodes
        'vortex_dao_governance' => 'dao/views/governance-display.php',
        'vortex_dao_proposals' => 'dao/views/proposals-display.php',
        'vortex_dao_voting' => 'dao/views/voting-display.php',
        
        // Gamification Shortcodes
        'vortex_leaderboard' => 'gamification/views/leaderboard-display.php',
        'vortex_achievements' => 'gamification/views/achievements-display.php',
        'vortex_user_progress' => 'gamification/views/user-progress.php',
        
        // Integration Shortcodes
        'vortex_realtime_dashboard' => 'integrations/views/realtime-dashboard.php',
        'vortex_artist_rankings' => 'integrations/views/artist-rankings.php'
    ];

    public function register_all_shortcodes() {
        foreach (self::SHORTCODE_MAP as $code => $file_path) {
            add_shortcode($code, function($atts, $content = null) use ($code, $file_path) {
                return $this->render_shortcode($code, $file_path, $atts, $content);
            });
        }
    }

    private function render_shortcode($code, $file_path, $atts, $content) {
        if (!file_exists(VORTEX_PLUGIN_DIR . 'public/partials/' . $file_path)) {
            return sprintf('<div class="vortex-error">Shortcode template for [%s] not found.</div>', $code);
        }
        
        ob_start();
        include(VORTEX_PLUGIN_DIR . 'public/partials/' . $file_path);
        return ob_get_clean();
    }
} 