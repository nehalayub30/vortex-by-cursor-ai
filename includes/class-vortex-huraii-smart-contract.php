<?php
/**
 * HURAII Smart Contract Integration
 *
 * Handles the creation and management of smart contracts for HURAII-generated artwork,
 * including origin tracking and royalty distribution.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_HURAII_Smart_Contract {
    /**
     * The single instance of this class
     */
    private static $instance = null;
    
    /**
     * Primary creator details (always included)
     */
    private $primary_creator = array(
        'name' => 'Marianne Nems',
        'alias' => 'Mariana Villard',
        'royalty_percentage' => 5,
        'signature' => 'HURAII: From the heart to the heart in Token of Love And Appreciation - TOLA',
        'role' => 'creator',
        'locked' => true // Cannot be modified or removed
    );
    
    /**
     * Maximum total royalty percentage allowed
     */
    private $max_total_royalty = 20;
    
    /**
     * Maximum artist royalty percentage allowed
     */
    private $max_artist_royalty = 15;
    
    /**
     * Blockchain platform
     */
    private $blockchain = 'solana';
    
    /**
     * Get the singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Create a smart contract for a new artwork
     *
     * @param int $artwork_id The artwork ID
     * @param int $artist_id The artist user ID
     * @param array $seed_artworks IDs of seed artworks used
     * @param array $style_fingerprint The style fingerprint used
     * @param array $additional_royalties Additional royalty recipients [optional]
     * @return array|WP_Error Smart contract data or error
     */
    public function create_artwork_contract($artwork_id, $artist_id, $seed_artworks, $style_fingerprint, $additional_royalties = array()) {
        global $wpdb;
        
        // Validate additional royalties
        $royalties_result = $this->validate_royalties($additional_royalties);
        if (is_wp_error($royalties_result)) {
            return $royalties_result;
        }
        
        // Get artist information
        $artist = get_userdata($artist_id);
        if (!$artist) {
            return new WP_Error('invalid_artist', __('Invalid artist user ID', 'vortex-ai-marketplace'));
        }
        
        // Start with primary creator
        $royalty_recipients = array($this->primary_creator);
        
        // Add validated additional royalties
        foreach ($additional_royalties as $royalty) {
            $royalty_recipients[] = array(
                'name' => sanitize_text_field($royalty['name']),
                'royalty_percentage' => floatval($royalty['percentage']),
                'wallet_address' => sanitize_text_field($royalty['wallet_address']),
                'role' => isset($royalty['role']) ? sanitize_text_field($royalty['role']) : 'contributor',
                'locked' => false
            );
        }
        
        // Create contract data
        $contract_data = array(
            'artwork_id' => $artwork_id,
            'artist_id' => $artist_id,
            'artist_name' => $artist->display_name,
            'creation_time' => current_time('mysql'),
            'royalty_recipients' => $royalty_recipients,
            'total_royalty_percentage' => $this->calculate_total_royalty($royalty_recipients),
            'seed_artwork_ids' => $seed_artworks,
            'style_fingerprint' => $style_fingerprint,
            'blockchain' => $this->blockchain,
            'blockchain_record' => $this->generate_blockchain_record($artwork_id, $artist_id, $royalty_recipients)
        );
        
        // Generate contract hash
        $contract_data['contract_hash'] = $this->generate_contract_hash($contract_data);
        
        // Store in database
        $contracts_table = $wpdb->prefix . 'vortex_artwork_contracts';
        
        $wpdb->insert(
            $contracts_table,
            array(
                'artwork_id' => $artwork_id,
                'artist_id' => $artist_id,
                'contract_hash' => $contract_data['contract_hash'],
                'contract_data' => json_encode($contract_data),
                'creation_date' => current_time('mysql'),
                'total_royalty_percentage' => $contract_data['total_royalty_percentage'],
                'primary_royalty_percentage' => $this->primary_creator['royalty_percentage'],
                'primary_royalty_recipient' => $this->primary_creator['name'],
                'additional_royalties' => json_encode($additional_royalties),
                'signature' => $this->primary_creator['signature'],
                'blockchain' => $this->blockchain
            ),
            array('%d', '%d', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s')
        );
        
        // Return the contract data
        return $contract_data;
    }
    
    /**
     * Validate royalty recipients and percentages
     *
     * @param array $royalties Array of royalty recipients
     * @return true|WP_Error True if valid, WP_Error if not
     */
    private function validate_royalties($royalties) {
        if (empty($royalties)) {
            return true; // No additional royalties is valid
        }
        
        $total_percentage = 0;
        $seen_wallets = array();
        
        foreach ($royalties as $royalty) {
            // Check required fields
            if (!isset($royalty['name']) || !isset($royalty['percentage']) || !isset($royalty['wallet_address'])) {
                return new WP_Error(
                    'invalid_royalty_data', 
                    __('Each royalty recipient must have a name, percentage, and wallet address', 'vortex-ai-marketplace')
                );
            }
            
            // Validate percentage (must be positive number)
            $percentage = floatval($royalty['percentage']);
            if ($percentage <= 0) {
                return new WP_Error(
                    'invalid_royalty_percentage', 
                    __('Royalty percentage must be greater than zero', 'vortex-ai-marketplace')
                );
            }
            
            // Track total
            $total_percentage += $percentage;
            
            // Validate wallet address (basic Solana address format check)
            $wallet = trim($royalty['wallet_address']);
            if (!preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $wallet)) {
                return new WP_Error(
                    'invalid_wallet_address', 
                    __('Invalid Solana wallet address format', 'vortex-ai-marketplace')
                );
            }
            
            // Check for duplicate wallet addresses
            if (in_array($wallet, $seen_wallets)) {
                return new WP_Error(
                    'duplicate_wallet', 
                    __('Duplicate wallet addresses are not allowed', 'vortex-ai-marketplace')
                );
            }
            $seen_wallets[] = $wallet;
        }
        
        // Check if total exceeds maximum allowed artist royalty
        if ($total_percentage > $this->max_artist_royalty) {
            return new WP_Error(
                'royalty_exceeds_maximum', 
                sprintf(
                    __('Total artist royalty percentage (%s%%) exceeds maximum allowed (%s%%)', 'vortex-ai-marketplace'),
                    number_format($total_percentage, 2),
                    $this->max_artist_royalty
                )
            );
        }
        
        return true;
    }
    
    /**
     * Calculate total royalty percentage from all recipients
     */
    private function calculate_total_royalty($recipients) {
        $total = 0;
        foreach ($recipients as $recipient) {
            $total += floatval($recipient['royalty_percentage']);
        }
        return $total;
    }
    
    /**
     * Generate a secure hash for the contract
     */
    private function generate_contract_hash($contract_data) {
        // Remove the hash field if it exists (to avoid circular reference)
        $data_for_hash = $contract_data;
        unset($data_for_hash['contract_hash']);
        
        // Generate a unique hash
        return hash('sha256', json_encode($data_for_hash) . time() . mt_rand(1000000, 9999999));
    }
    
    /**
     * Generate blockchain record data for Solana
     */
    private function generate_blockchain_record($artwork_id, $artist_id, $royalty_recipients) {
        // In a real implementation, this would connect to the Solana blockchain
        // and create the actual smart contract for royalty distribution
        
        // Format royalty data for Solana contract
        $royalty_data = array();
        foreach ($royalty_recipients as $recipient) {
            $royalty_data[] = array(
                'address' => isset($recipient['wallet_address']) ? $recipient['wallet_address'] : $this->get_default_wallet_for_recipient($recipient['name']),
                'share' => $recipient['royalty_percentage'] * 100 // Convert to basis points (1% = 100 basis points)
            );
        }
        
        // Generate Solana-compatible record
        $record = array(
            'transaction_id' => hash('sha256', $artwork_id . $artist_id . time()),
            'contract_address' => $this->generate_solana_address($artwork_id),
            'blockchain' => 'solana',
            'network' => 'mainnet',
            'protocol' => 'TOLA',
            'royalty_data' => $royalty_data,
            'timestamp' => time(),
            'verification_url' => site_url('/verify-artwork/' . $artwork_id),
            'metadata_uri' => site_url('/artwork-metadata/' . $artwork_id . '.json'),
            'perpetual' => true
        );
        
        return $record;
    }
    
    /**
     * Generate a Solana-compatible address for the artwork
     */
    private function generate_solana_address($artwork_id) {
        // In a real implementation, this would be an actual Solana address
        // This is a placeholder that generates a format similar to Solana addresses
        $base58chars = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $address = '';
        
        // Generate a 44-character base58 string
        $seed = 'tola_' . $artwork_id . '_' . time();
        $hash = hash('sha256', $seed);
        
        for ($i = 0; $i < 44; $i++) {
            $address .= $base58chars[hexdec($hash[$i % strlen($hash)]) % strlen($base58chars)];
        }
        
        return $address;
    }
    
    /**
     * Get default wallet for a known recipient
     */
    private function get_default_wallet_for_recipient($name) {
        // For the primary creator, we have a predefined wallet
        if ($name === $this->primary_creator['name'] || $name === $this->primary_creator['alias']) {
            return 'MNems5VRnbTH8VsJp7MYU3bWxkwqZQECaZJ12YBxVpP';
        }
        
        // For others, return a placeholder (in production, this would need to be provided)
        return 'PLACEHOLDER_REQUIRES_REAL_WALLET_ADDRESS';
    }
    
    /**
     * Get contract for an artwork
     */
    public function get_artwork_contract($artwork_id) {
        global $wpdb;
        
        $contracts_table = $wpdb->prefix . 'vortex_artwork_contracts';
        
        $contract = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $contracts_table WHERE artwork_id = %d",
            $artwork_id
        ));
        
        if (!$contract) {
            return false;
        }
        
        // Parse the contract data
        $contract->contract_data = json_decode($contract->contract_data, true);
        $contract->additional_royalties = json_decode($contract->additional_royalties, true);
        
        return $contract;
    }
    
    /**
     * Generate a restricted URL for the artwork that includes contract verification
     */
    public function generate_restricted_url($artwork_id) {
        $contract = $this->get_artwork_contract($artwork_id);
        
        if (!$contract) {
            return new WP_Error('no_contract', __('No contract found for this artwork', 'vortex-ai-marketplace'));
        }
        
        // Generate a signed URL that includes contract verification
        $base_url = site_url('/artwork/' . $artwork_id);
        $hash = substr($contract->contract_hash, 0, 10); // Use part of the hash
        
        $url = add_query_arg(array(
            'origin' => 'huraii',
            'verify' => $hash,
            'contract' => 'solana:' . $contract->contract_data['blockchain_record']['contract_address']
        ), $base_url);
        
        return $url;
    }
    
    /**
     * Record a royalty payment distribution to all recipients
     */
    public function record_royalty_payment($artwork_id, $amount, $transaction_type) {
        global $wpdb;
        
        $contract = $this->get_artwork_contract($artwork_id);
        
        if (!$contract) {
            return new WP_Error('no_contract', __('No contract found for this artwork', 'vortex-ai-marketplace'));
        }
        
        $payments_table = $wpdb->prefix . 'vortex_royalty_payments';
        $recipients = $contract->contract_data['royalty_recipients'];
        $total_royalty = $contract->total_royalty_percentage;
        
        // Record payment for each recipient
        foreach ($recipients as $recipient) {
            // Calculate recipient's share
            $recipient_percentage = $recipient['royalty_percentage'];
            $recipient_amount = ($amount * $recipient_percentage) / 100;
            
            // Record the payment
            $wpdb->insert(
                $payments_table,
                array(
                    'contract_id' => $contract->id,
                    'artwork_id' => $artwork_id,
                    'recipient_name' => $recipient['name'],
                    'recipient_role' => $recipient['role'],
                    'amount' => $recipient_amount,
                    'percentage' => $recipient_percentage,
                    'transaction_type' => $transaction_type,
                    'payment_date' => current_time('mysql'),
                    'blockchain' => $this->blockchain,
                    'tx_hash' => $this->generate_transaction_hash($artwork_id, $recipient['name'], $recipient_amount)
                ),
                array('%d', '%d', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s')
            );
        }
        
        return true;
    }
    
    /**
     * Generate a transaction hash for royalty payment
     * (Placeholder - would be an actual blockchain transaction in production)
     */
    private function generate_transaction_hash($artwork_id, $recipient_name, $amount) {
        return hash('sha256', $artwork_id . $recipient_name . $amount . time());
    }
    
    /**
     * Export contract to metadata JSON for NFT standards
     */
    public function export_contract_metadata($artwork_id) {
        $contract = $this->get_artwork_contract($artwork_id);
        
        if (!$contract) {
            return new WP_Error('no_contract', __('No contract found for this artwork', 'vortex-ai-marketplace'));
        }
        
        // Get artwork details
        global $wpdb;
        $artwork_table = $wpdb->prefix . 'vortex_artworks';
        
        $artwork = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $artwork_table WHERE id = %d",
            $artwork_id
        ));
        
        if (!$artwork) {
            return new WP_Error('artwork_not_found', __('Artwork not found', 'vortex-ai-marketplace'));
        }
        
        // Format metadata according to Solana NFT standards (Metaplex)
        $metadata = array(
            'name' => $artwork->title,
            'description' => $artwork->description,
            'image' => site_url($artwork->file_path),
            'external_url' => site_url('/artwork/' . $artwork_id),
            'attributes' => array(
                array(
                    'trait_type' => 'Generator',
                    'value' => 'HURAII'
                ),
                array(
                    'trait_type' => 'Artist',
                    'value' => get_user_meta($artwork->user_id, 'display_name', true)
                ),
                array(
                    'trait_type' => 'Creation Date',
                    'value' => date('Y-m-d', strtotime($artwork->creation_date))
                )
            ),
            'properties' => array(
                'files' => array(
                    array(
                        'uri' => site_url($artwork->file_path),
                        'type' => 'image/png'
                    )
                ),
                'category' => 'image',
                'creators' => array()
            ),
            'seller_fee_basis_points' => intval($contract->total_royalty_percentage * 100),
            'collection' => array(
                'name' => 'HURAII Generated Artwork',
                'family' => 'TOLA'
            )
        );
        
        // Add creators with shares
        foreach ($contract->contract_data['royalty_recipients'] as $recipient) {
            $metadata['properties']['creators'][] = array(
                'address' => isset($recipient['wallet_address']) 
                    ? $recipient['wallet_address'] 
                    : $this->get_default_wallet_for_recipient($recipient['name']),
                'share' => intval(($recipient['royalty_percentage'] / $contract->total_royalty_percentage) * 100)
            );
        }
        
        return $metadata;
    }
} 