<?php
namespace Vortex\AI\Tests\Integration\AI;

use Vortex\AI\Tests\Integration\TestCase;
use Vortex\AI\Blockchain\Blockchain;

class BlockchainIntegrationTest extends TestCase {
    private $blockchain;

    public function setUp(): void {
        parent::setUp();
        $this->blockchain = new Blockchain();
    }

    public function test_full_nft_creation_flow() {
        // 1. Connect wallet
        $wallet = '0x1234567890abcdef';
        $connected = $this->blockchain->connect_wallet($wallet);
        $this->assertTrue($connected);

        // 2. Mint NFT
        $metadata = [
            'name' => '
} 