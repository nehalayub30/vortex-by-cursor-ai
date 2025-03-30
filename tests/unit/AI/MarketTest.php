<?php
namespace Vortex\AI\Tests\Unit\AI;

use Vortex\AI\Tests\Unit\TestCase;
use Vortex\AI\Market\Market;

class MarketTest extends TestCase {
    private $market;

    public function setUp(): void {
        parent::setUp();
        $this->market = new Market();
    }

    public function test_analyze_trends() {
        $parameters = [
            'timeframe' => '7d',
            'category' => 'digital_art'
        ];
        
        $result = $this->market->analyze_trends($parameters);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('trend_data', $result);
        $this->assertArrayHasKey('market_sentiment', $result);
    }

    public function test_predict_price() {
        $nft_id = 'test-nft-123';
        $prediction = $this->market->predict_price($nft_id);
        
        $this->assertIsArray($prediction);
        $this->assertArrayHasKey('predicted_price', $prediction);
        $this->assertArrayHasKey('confidence_score', $prediction);
    }

    public function test_get_opportunities() {
        $opportunities = $this->market->get_opportunities();
        
        $this->assertIsArray($opportunities);
        $this->assertNotEmpty($opportunities);
    }
} 