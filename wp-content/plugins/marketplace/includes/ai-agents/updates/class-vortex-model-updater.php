class VORTEX_Model_Updater {
    private $model_registry;
    private $version_checker;
    
    public function check_for_updates() {
        $models = $this->model_registry->get_all_models();
        
        foreach ($models as $model) {
            $latest_version = $this->version_checker->get_latest_version($model['id']);
            
            if ($this->needs_update($model, $latest_version)) {
                $this->schedule_update($model, $latest_version);
            }
        }
    }
    
    private function schedule_update($model, $version) {
        wp_schedule_single_event(
            time() + 60,
            'vortex_update_model',
            array($model['id'], $version)
        );
    }
} 