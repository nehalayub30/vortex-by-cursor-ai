<?php
class BlockchainTest extends WP_UnitTestCase {
    private $blockchain;

    public function setUp(): void {
        parent::setUp();
        $this->blockchain = new \Vortex\AI\Blockchain\Blockchain();
    }

    public function test_connect_wallet() {
        $wallet = '0x1234567890abcdef';
        $result = $this->blockchain->connect_wallet($wallet);
        $this->assertTrue($result);
    }

    public function test_mint_nft() {
        $metadata = [
            'name' => 'Test NFT',
            'description' => 'Test Description',
            'image' => 'https://example.com/image.jpg'
        ];
        $token_id = $this->blockchain->mint_nft($metadata);
        $this->assertNotEmpty($token_id);
    }
} 