<?php
class VORTEX_HURAII_Core {
    private $seed_art_processor;
    private $automated_sequence;
    private $prompt_handler;
    private $evolution_tracker;
    private $smart_contract_handler;
    
    public function __construct() {
        $this->init_components();
        $this->register_hooks();
        $this->init_smart_contracts();
    }
    
    private function init_components() {
        $this->seed_art_processor = new VORTEX_HURAII_SeedArt();
        $this->automated_sequence = new VORTEX_HURAII_Sequence();
        $this->prompt_handler = new VORTEX_HURAII_Prompts();
        $this->evolution_tracker = new VORTEX_HURAII_Evolution();
    }

    private function init_smart_contracts() {
        $this->smart_contract_handler = new VORTEX_HURAII_Smart_Contract();
        add_action('huraii_artwork_generated', array($this, 'handle_artwork_tokenization'));
    }

    public function handle_artwork_tokenization($artwork_data) {
        try {
            // Add royalty verification
            $this->verify_inventor_royalty_structure();
            
            // Process artwork tokenization
            $contract_result = $this->smart_contract_handler->auto_tokenize_artwork($artwork_data);

            // Verify royalty structure after deployment
            $this->smart_contract_handler->verify_royalty_structure(
                $contract_result['contract_address']
            );

            // Trigger post-tokenization actions
            do_action('huraii_artwork_tokenized', [
                'artwork_data' => $artwork_data,
                'contract_data' => $contract_result,
                'royalty_verified' => true
            ]);

        } catch (Exception $e) {
            error_log("HURAII Tokenization Error: " . $e->getMessage());
            // Handle error appropriately
        }
    }

    private function verify_inventor_royalty_structure() {
        if (!defined('VORTEX_INVENTOR_ROYALTY') || VORTEX_INVENTOR_ROYALTY !== 5) {
            throw new Exception('Invalid inventor royalty configuration');
        }
    }
} 