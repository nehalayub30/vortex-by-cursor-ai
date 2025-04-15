class VORTEX_Integration_Registry {
    private $registered_integrations = [];

    public function register_integration($component, $callbacks) {
        $this->registered_integrations[$component] = [
            'callbacks' => $callbacks,
            'status' => $this->verify_integration($component, $callbacks),
            'last_check' => current_time('mysql'),
            'health_score' => $this->calculate_health_score($component)
        ];
    }

    public function verify_all_integrations() {
        return array_map(function($integration) {
            return $this->verify_integration_health($integration);
        }, $this->registered_integrations);
    }
} 