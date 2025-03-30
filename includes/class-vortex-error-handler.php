<?php

class Vortex_Error_Handler {
    public function register_error_handlers() {
        // Transaction errors
        add_action('vortex_transaction_error', array($this, 'handle_transaction_error'));
        
        // AI processing errors
        add_action('vortex_ai_error', array($this, 'handle_ai_error'));
        
        // User input errors
        add_action('vortex_input_error', array($this, 'handle_input_error'));
    }
} 