<?php

// ... existing code ...

// Line 98 references VORTEX_Business_Strategist which wasn't defined
// Ensure the file is included
require_once plugin_dir_path( __FILE__ ) . 'class-vortex-business-strategist.php';

// Make sure HURAII class is loaded before trying to use its methods
require_once plugin_dir_path(__FILE__) . 'class-vortex-huraii.php';

// Include necessary files at the top of the file
require_once plugin_dir_path(__FILE__) . 'class-vortex-cloe.php';

/**
 * Initialize AI agents and configure them
 */
private function initialize_ai_agents() {
    // Initialize HURAII
    $this->huraii = new VORTEX_HURAII();
    if ($this->ai_settings['huraii_deep_learning']) {
        $this->huraii->enable_deep_learning(true);
        $this->huraii->set_learning_rate(0.001);
        $this->huraii->enable_continuous_learning();
        $this->huraii->set_context_window(1000);
        $this->huraii->enable_cross_learning(true);
    }
    
    // Initialize CLOE
    $this->cloe = new VORTEX_CLOE();
    if (method_exists($this->cloe, 'enable_deep_learning')) {
        $this->cloe->enable_deep_learning();
        $this->cloe->set_learning_rate(0.001);
        $this->cloe->enable_continuous_learning();
        $this->cloe->set_context_window(1000);
        $this->cloe->enable_cross_learning();
    } else {
        error_log('CLOE methods not found - check class implementation');
    }
    
    // Initialize Business Strategist
    $this->business_strategist = new VORTEX_Business_Strategist();
    if ($this->ai_settings['business_strategist_deep_learning']) {
        $this->business_strategist->enable_deep_learning();
        $this->business_strategist->set_learning_rate(0.001);
        $this->business_strategist->enable_continuous_learning();
        $this->business_strategist->set_context_window(1000);
        $this->business_strategist->enable_cross_learning();
    }
    
    // Initialize Thorius
    $this->thorius = new VORTEX_Thorius();
    if ($this->ai_settings['thorius_deep_learning']) {
        $this->thorius->enable_deep_learning();
        $this->thorius->set_learning_rate(0.001);
        $this->thorius->enable_continuous_learning();
        $this->thorius->set_context_window(1000);
        $this->thorius->enable_cross_learning();
    }
}

// ... existing code ... 

// When initializing the HURAII instance, ensure proper error handling
private function initialize_huraii() {
    try {
        $this->huraii = new VORTEX_HURAII();
        
        // Configure the HURAII instance
        if ($this->huraii) {
            $this->huraii->enable_deep_learning(true);
            $this->huraii->set_learning_rate(0.001);
            $this->huraii->enable_continuous_learning();
            $this->huraii->set_context_window(1000);
            $this->huraii->enable_cross_learning(true);
        } else {
            error_log('Failed to initialize HURAII instance');
        }
    } catch (Exception $e) {
        error_log('Error initializing HURAII: ' . $e->getMessage());
    }
}

// ... existing code ... 