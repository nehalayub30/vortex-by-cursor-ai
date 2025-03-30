<?php
namespace Vortex\AI\Interfaces;

interface BlockchainInterface {
    /**
     * Connect wallet to the platform
     * @param string $wallet_address
     * @return bool
     */
    public function connect_wallet($wallet_address);

    /**
     * Mint new NFT
     * @param array $metadata
     * @return string NFT token ID
     */
    public function mint_nft($metadata);

    /**
     * Get transaction status
     * @param string $tx_hash
     * @return array
     */
    public function get_transaction_status($tx_hash);
} 