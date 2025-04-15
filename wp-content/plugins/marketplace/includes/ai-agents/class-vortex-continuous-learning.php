class VORTEX_Continuous_Learning {
    private $agents = [
        'huraii' => null,
        'cloe' => null,
        'business_strategist' => null,
        'thorius' => null
    ];
    
    private $learning_active = false;
    
    public function __construct() {
        add_action('init', [$this, 'initialize_agents']);
        add_action('vortex_hourly_learning', [$this, 'trigger_incremental_learning']);
        add_action('vortex_daily_learning', [$this, 'trigger_daily_learning']);
        add_action('vortex_weekly_learning', [$this, 'trigger_deep_learning']);
        
        // Register real-time learning hooks
        add_action('vortex_artwork_created', [$this, 'learn_from_new_artwork']);
        add_action('vortex_blockchain_transaction', [$this, 'learn_from_transaction']);
        add_action('vortex_user_interaction', [$this, 'learn_from_user_interaction']);
    }
    
    public function initialize_agents() {
        foreach ($this->agents as $agent_name => &$agent) {
            $class_name = 'VORTEX_' . ucfirst($agent_name);
            if (class_exists($class_name)) {
                $agent = new $class_name();
                
                // Enable continuous learning mode
                if (method_exists($agent, 'enable_continuous_learning')) {
                    $agent->enable_continuous_learning([
                        'incremental_threshold' => 10,
                        'real_time_analysis' => true,
                        'cross_learning' => true
                    ]);
                }
            }
        }
    }
    
    public function trigger_incremental_learning() {
        if ($this->learning_active) {
            return;
        }
        
        $this->learning_active = true;
        
        try {
            $learning_data = $this->gather_incremental_data();
            
            foreach ($this->agents as $agent_name => $agent) {
                if ($agent && method_exists($agent, 'process_incremental_learning')) {
                    $agent->process_incremental_learning($learning_data[$agent_name] ?? []);
                }
            }
            
            // Generate cross-agent insights
            $this->generate_cross_agent_insights('incremental');
            
            // Update learning metrics
            $this->update_learning_metrics([
                'type' => 'incremental',
                'timestamp' => current_time('mysql')
            ]);
            
        } catch (Exception $e) {
            error_log('VORTEX Learning Error: ' . $e->getMessage());
        }
        
        $this->learning_active = false;
    }
    
    private function generate_cross_agent_insights($learning_type) {
        $insights = [];
        
        foreach ($this->agents as $agent_name => $agent) {
            if ($agent && method_exists($agent, 'get_latest_insights')) {
                $insights[$agent_name] = $agent->get_latest_insights();
            }
        }
        
        // If we have insights from multiple agents, process them collaboratively
        if (count(array_filter($insights)) >= 2) {
            do_action('vortex_cross_agent_insights', $insights, $learning_type);
        }
    }
} 