<?php
/**
 * Transaction Processing Enhancement for TOLA Enforcement
 */

// Add this to the existing VORTEX_Transaction class

/**
 * Validate currency type to enforce TOLA
 *
 * @since 1.0.0
 * @param array $data Transaction data
 * @return array|WP_Error Validated data or error
 */
private function validate_currency($data) {
    // If currency type is not set, default to TOLA
    if (!isset($data['currency_type'])) {
        $data['currency_type'] = 'tola_credit';
    }
    
    // Apply filter to enforce TOLA (will be enforced by validator)
    $data['currency_type'] = apply_filters('vortex_transaction_currency', $data['currency_type'], $data);
    
    // If it's not TOLA, reject the transaction
    if ($data['currency_type'] !== 'tola_credit') {
        return new WP_Error(
            'invalid_currency',
            __('Only TOLA tokens can be used for transactions in the VORTEX marketplace', 'vortex')
        );
    }
    
    return $data;
}

/**
 * Create a new transaction
 *
 * @since 1.0.0
 * @param array $transaction_data Transaction data
 * @return int|WP_Error Transaction ID or error
 */
public function create($transaction_data) {
    // Validate transaction data
    $validation = $this->validate_transaction_data($transaction_data);
    if (is_wp_error($validation)) {
        return $validation;
    }
    
    // Validate and enforce TOLA as currency
    $currency_validation = $this->validate_currency($transaction_data);
    if (is_wp_error($currency_validation)) {
        return $currency_validation;
    }
    $transaction_data = $currency_validation;
    
    // Run the transaction through the validator
    $valid = apply_filters('vortex_pre_process_transaction', true, $transaction_data);
    if (is_wp_error($valid)) {
        return $valid;
    }
    
    // Proceed with transaction creation as before...
    // [Existing transaction creation code]
} 