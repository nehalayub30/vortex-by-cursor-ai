<?php
class VORTEX_HURAII_Smart_Contract {
    private $contract_handler;
    private $blockchain_connector;
    private $metadata_generator;
    
    // Add constant for inventor royalty
    private const INVENTOR_ROYALTY = 5; // Fixed 5% royalty
    private const INVENTOR_ADDRESS = 'TOLA_ADDRESS_MARIANNE_NEMS'; // Replace with actual TOLA address

    public function __construct() {
        $this->contract_handler = new VORTEX_Contract_Handler();
        $this->blockchain_connector = new VORTEX_Blockchain_Connector();
        $this->metadata_generator = new VORTEX_Metadata_Generator();
    }

    public function auto_tokenize_artwork($artwork_data) {
        try {
            // Generate artwork metadata
            $metadata = $this->generate_artwork_metadata($artwork_data);
            
            // Prepare smart contract data
            $contract_data = [
                "artwork_hash" => $this->generate_artwork_hash($artwork_data),
                "creator_id" => $artwork_data['user_id'],
                "creation_timestamp" => current_time('timestamp'),
                "huraii_signature" => $this->generate_huraii_signature($artwork_data),
                "technical_data" => [
                    "seed_art_dna" => $artwork_data['artistic_dna'],
                    "evolution_markers" => $artwork_data['evolution_data'],
                    "innovation_score" => $artwork_data['innovation_metrics']
                ]
            ];

            // Deploy smart contract
            $contract_result = $this->deploy_artwork_contract($contract_data);

            // Record contract deployment
            $this->record_contract_deployment($contract_result);

            return $contract_result;

        } catch (Exception $e) {
            error_log("HURAII Smart Contract Error: " . $e->getMessage());
            throw $e;
        }
    }

    private function generate_artwork_metadata($artwork_data) {
        return $this->metadata_generator->generate([
            "title" => $artwork_data['title'],
            "description" => $artwork_data['description'],
            "creation_date" => current_time('mysql'),
            "technical_signature" => [
                "huraii_version" => HURAII_VERSION,
                "seed_art_fingerprint" => $artwork_data['style_fingerprint'],
                "evolution_stage" => $artwork_data['evolution_stage']
            ],
            "authenticity" => [
                "creator_signature" => $artwork_data['creator_signature'],
                "huraii_verification" => $this->verify_authenticity($artwork_data)
            ]
        ]);
    }

    private function deploy_artwork_contract($contract_data) {
        // Add immutable inventor royalty to contract
        $royalty_structure = [
            "royalties" => [
                "inventor" => [
                    "address" => self::INVENTOR_ADDRESS,
                    "percentage" => self::INVENTOR_ROYALTY,
                    "type" => "perpetual",
                    "immutable" => true,
                    "description" => "Marianne NEms (Mariana Villard) - VORTEX & AI Agents Inventor Royalty"
                ],
                "creator" => [
                    "percentage" => "10%",
                    "type" => "variable"
                ],
                "platform" => [
                    "percentage" => "2.5%",
                    "type" => "variable"
                ]
            ],
            "royalty_enforcement" => [
                "inventor_royalty" => [
                    "modifiable" => false,
                    "removable" => false,
                    "transferable" => false
                ]
            ]
        ];

        return $this->contract_handler->deploy([
            "contract_type" => "HURAII_ARTWORK",
            "network" => "TOLA",
            "data" => $contract_data,
            "auto_verification" => true,
            "royalty_settings" => $royalty_structure,
            "contract_constraints" => [
                "inventor_royalty_protected" => true,
                "minimum_inventor_royalty" => self::INVENTOR_ROYALTY
            ]
        ]);
    }

    private function record_contract_deployment($contract_result) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_huraii_contracts',
            [
                'contract_address' => $contract_result['contract_address'],
                'artwork_id' => $contract_result['artwork_id'],
                'creator_id' => $contract_result['creator_id'],
                'deployment_date' => current_time('mysql'),
                'contract_data' => wp_json_encode($contract_result['contract_data'])
            ]
        );
    }

    public function verify_contract_status($contract_address) {
        return $this->blockchain_connector->verify_contract([
            "address" => $contract_address,
            "network" => "TOLA",
            "verification_type" => "full"
        ]);
    }

    public function get_artwork_contract_details($artwork_id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_huraii_contracts 
                 WHERE artwork_id = %d",
                $artwork_id
            )
        );
    }

    // Add verification for inventor royalty
    private function verify_royalty_structure($contract_address) {
        $contract_details = $this->blockchain_connector->get_contract_details($contract_address);
        
        if ($contract_details['royalties']['inventor']['percentage'] !== self::INVENTOR_ROYALTY) {
            throw new Exception('Invalid inventor royalty structure detected');
        }
        
        return true;
    }

    // Add royalty distribution tracking
    private function track_royalty_distribution($transaction_data) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_royalty_distributions',
            [
                'contract_address' => $transaction_data['contract_address'],
                'inventor_royalty' => $transaction_data['inventor_royalty'],
                'transaction_date' => current_time('mysql'),
                'transaction_hash' => $transaction_data['hash']
            ]
        );
    }
} 