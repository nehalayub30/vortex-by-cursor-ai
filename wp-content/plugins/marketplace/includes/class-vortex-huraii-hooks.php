<?php
class VORTEX_HURAII_Hooks {
    public function register_hooks() {
        add_action('vortex_analyze_artwork', array($this, 'handle_artwork_analysis'));
        add_action('vortex_generate_art', array($this, 'handle_art_generation'));
        add_action('vortex_track_evolution', array($this, 'handle_evolution_tracking'));
    }
} 