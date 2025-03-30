            $safe_output = '<div class="vortex-system-info">
                <h3>' . esc_html($this->get_safe_title_for_shortcode($shortcode)) . '</h3>
                <p>' . esc_html($this->get_safe_description_for_shortcode($shortcode)) . '</p>
                <div class="vortex-admin-notice">For detailed information about how the VORTEX AI system works internally, please contact an administrator.</div>
            </div>';
            
            return $safe_output;
        }
        
        return $output;
    }
    
    /**
     * Get safe title for shortcode
     */
    private function get_safe_title_for_shortcode($shortcode) {
        $titles = array(
            'vortex_system_info' => 'VORTEX AI Marketplace',
            'vortex_ai_status' => 'AI Agent Status',
            'vortex_agent_details' => 'AI Agent Information',
            'vortex_algorithm_info' => 'System Overview'
        );
        
        return isset($titles[$shortcode]) ? $titles[$shortcode] : 'VORTEX Information';
    }
    
    /**
     * Get safe description for shortcode
     */
    private function get_safe_description_for_shortcode($shortcode) {
        $descriptions = array(
            'vortex_system_info' => 'The VORTEX AI Marketplace combines advanced technology to create a revolutionary marketplace experience for digital art.',
            'vortex_ai_status' => 'All AI agents are actively working to optimize your marketplace experience.',
            'vortex_agent_details' => 'VORTEX utilizes multiple specialized AI agents to enhance different aspects of the digital art marketplace.',
            'vortex_algorithm_info' => 'VORTEX employs advanced technology to provide superior marketplace functionality.'
        );
        
        return isset($descriptions[$shortcode]) ? $descriptions[$shortcode] : 'VORTEX AI Marketplace provides advanced features for digital art creators and collectors.';
    }
}

// Initialize the Public Responses Manager
add_action('plugins_loaded', function() {
    VORTEX_Public_Responses::get_instance();
}, 10); 