<?php
namespace Vortex\AI\Interfaces;

interface MarketInterface {
    /**
     * Analyze market trends
     * @param array $parameters
     * @return array
     */
    public function analyze_trends($parameters);

    /**
     * Get price prediction
     * @param string $nft_id
     * @return array
     */
    public function predict_price($nft_id);

    /**
     * Get market opportunities
     * @return array
     */
    public function get_opportunities();
} 