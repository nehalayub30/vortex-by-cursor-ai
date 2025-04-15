<?php
/**
 * VORTEX SaaS Backend - Market Analytics
 * 
 * Provides marketplace analytics, trends, and performance metrics.
 *
 * @package VORTEX_SaaS_Backend
 * @subpackage Marketplace
 */

class VORTEX_MarketAnalytics {
    /**
     * Database connection
     * 
     * @var PDO
     */
    private $db;
    
    /**
     * Cache instance
     * 
     * @var object
     */
    private $cache;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Get database connection
        $this->db = getDbConnection();
        
        // Initialize cache (using a simple file-based cache for now)
        $this->cache = new SimpleCache(__DIR__ . '/../../cache');
    }
    
    /**
     * Get market overview data
     * 
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @return array Market overview data
     */
    public function getMarketOverview($startDate, $endDate) {
        // Cache key using date range
        $cacheKey = "market_overview_{$startDate}_{$endDate}";
        
        // Check if data is in cache
        if (VORTEX_CACHE_ENABLED && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }
        
        // Get sales metrics
        $salesMetrics = $this->getSalesMetrics($startDate, $endDate);
        
        // Get artist metrics
        $artistMetrics = $this->getArtistMetrics($startDate, $endDate);
        
        // Get artwork metrics
        $artworkMetrics = $this->getArtworkMetrics($startDate, $endDate);
        
        // Get blockchain metrics
        $blockchainMetrics = $this->getBlockchainMetrics($startDate, $endDate);
        
        // Compile market overview
        $overview = [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'summary' => [
                'total_sales' => $salesMetrics['total_sales'],
                'total_value' => $salesMetrics['total_value'],
                'average_price' => $salesMetrics['average_price'],
                'growth_rate' => $this->calculateGrowthRate($startDate, $endDate),
                'new_artists' => $artistMetrics['new_artists'],
                'new_collectors' => $artistMetrics['new_collectors'],
                'total_artworks' => $artworkMetrics['total_artworks'],
                'tokenized_artworks' => $blockchainMetrics['tokenized_artworks']
            ],
            'sales_metrics' => $salesMetrics,
            'artist_metrics' => $artistMetrics,
            'artwork_metrics' => $artworkMetrics,
            'blockchain_metrics' => $blockchainMetrics,
            'top_categories' => $this->getTopCategories($startDate, $endDate),
            'price_ranges' => $this->getPriceRangeDistribution($startDate, $endDate)
        ];
        
        // Cache the result
        if (VORTEX_CACHE_ENABLED) {
            $this->cache->set($cacheKey, $overview, VORTEX_CACHE_LIFETIME);
        }
        
        return $overview;
    }
    
    /**
     * Get trend analysis data
     * 
     * @param string $metric Metric to analyze (sales, price, volume, etc.)
     * @param string $period Period for grouping (daily, weekly, monthly)
     * @param string|null $segment Optional segment to filter by (category, style, etc.)
     * @return array Trend analysis data
     */
    public function getTrendAnalysis($metric = 'sales', $period = 'monthly', $segment = null) {
        // Validate inputs
        $validMetrics = ['sales', 'price', 'volume', 'artists', 'collectors'];
        if (!in_array($metric, $validMetrics)) {
            throw new Exception("Invalid metric: $metric");
        }
        
        $validPeriods = ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'];
        if (!in_array($period, $validPeriods)) {
            throw new Exception("Invalid period: $period");
        }
        
        // Cache key using parameters
        $cacheKey = "trend_analysis_{$metric}_{$period}" . ($segment ? "_{$segment}" : "");
        
        // Check if data is in cache
        if (VORTEX_CACHE_ENABLED && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }
        
        // Calculate appropriate lookback period based on requested period
        $lookbackPeriods = [
            'daily' => '-90 days',
            'weekly' => '-1 year',
            'monthly' => '-2 years',
            'quarterly' => '-3 years',
            'yearly' => '-5 years'
        ];
        
        $startDate = date('Y-m-d', strtotime($lookbackPeriods[$period]));
        $endDate = date('Y-m-d');
        
        // Get trend data based on the metric
        $trendData = $this->fetchTrendData($metric, $period, $startDate, $endDate, $segment);
        
        // Analyze trends to identify patterns and forecasts
        $analysis = [
            'metric' => $metric,
            'period' => $period,
            'segment' => $segment,
            'data_points' => $trendData,
            'trends' => $this->identifyTrends($trendData),
            'forecast' => $this->generateForecast($trendData, $period),
            'seasonality' => $this->detectSeasonality($trendData, $period),
        ];
        
        // Cache the result
        if (VORTEX_CACHE_ENABLED) {
            $this->cache->set($cacheKey, $analysis, VORTEX_CACHE_LIFETIME);
        }
        
        return $analysis;
    }
    
    /**
     * Fetch trend data for a specific metric and period
     * 
     * @param string $metric Metric to analyze
     * @param string $period Period for grouping (daily, weekly, monthly)
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @param string|null $segment Optional segment to filter by
     * @return array Array of data points
     */
    private function fetchTrendData($metric, $period, $startDate, $endDate, $segment = null) {
        // Different SQL grouping functions based on period
        $groupByFunctions = [
            'daily' => "DATE(created_at)",
            'weekly' => "YEARWEEK(created_at)",
            'monthly' => "DATE_FORMAT(created_at, '%Y-%m')",
            'quarterly' => "CONCAT(YEAR(created_at), '-Q', QUARTER(created_at))",
            'yearly' => "YEAR(created_at)"
        ];
        
        $groupBy = $groupByFunctions[$period];
        
        // Different metrics require different calculations
        $metricCalculations = [
            'sales' => "COUNT(id) as value",
            'price' => "AVG(price) as value",
            'volume' => "SUM(price) as value",
            'artists' => "COUNT(DISTINCT artist_id) as value",
            'collectors' => "COUNT(DISTINCT buyer_id) as value"
        ];
        
        $metricCalc = $metricCalculations[$metric];
        
        // Build SQL query
        $sql = "SELECT $groupBy as date_group, $metricCalc 
                FROM vortex_sales 
                WHERE created_at BETWEEN :start_date AND :end_date";
        
        // Add segment filter if provided
        if ($segment) {
            $sql .= " AND category = :segment";
        }
        
        $sql .= " GROUP BY date_group ORDER BY date_group";
        
        // Execute query
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        
        if ($segment) {
            $stmt->bindParam(':segment', $segment);
        }
        
        $stmt->execute();
        
        // Format results
        $results = [];
        while ($row = $stmt->fetch()) {
            $displayDate = $this->formatPeriodDate($row['date_group'], $period);
            
            $results[] = [
                'date' => $displayDate,
                'value' => (float) $row['value']
            ];
        }
        
        return $results;
    }
    
    /**
     * Format period date for display
     * 
     * @param string $dateGroup The date group from SQL query
     * @param string $period The period type
     * @return string Formatted date
     */
    private function formatPeriodDate($dateGroup, $period) {
        switch ($period) {
            case 'daily':
                return $dateGroup; // Already in Y-m-d format
                
            case 'weekly':
                // Convert YEARWEEK format to a date string
                $year = substr($dateGroup, 0, 4);
                $week = substr($dateGroup, 4);
                return "Week {$week}, {$year}";
                
            case 'monthly':
                return date('F Y', strtotime($dateGroup . '-01'));
                
            case 'quarterly':
                return $dateGroup; // Already in YYYY-QN format
                
            case 'yearly':
                return $dateGroup; // Already in YYYY format
                
            default:
                return $dateGroup;
        }
    }
    
    /**
     * Identify trends in the data
     * 
     * @param array $dataPoints Array of data points
     * @return array Identified trends
     */
    private function identifyTrends($dataPoints) {
        // In a real implementation, this would use statistical methods
        // to identify significant trends in the data
        
        // Calculate growth rate
        $totalPoints = count($dataPoints);
        if ($totalPoints < 2) {
            return [
                'trend' => 'insufficient_data',
                'growth_rate' => 0
            ];
        }
        
        // Simple linear regression to identify trend
        $xValues = array_keys($dataPoints);
        $yValues = array_column($dataPoints, 'value');
        
        $slope = $this->calculateLinearRegressionSlope($xValues, $yValues);
        $averageValue = array_sum($yValues) / count($yValues);
        $growthRate = ($slope * count($yValues)) / $averageValue;
        
        // Determine trend direction and strength
        $trendDirection = $slope > 0 ? 'upward' : 'downward';
        $trendStrength = abs($growthRate) < 0.05 ? 'weak' : (abs($growthRate) < 0.15 ? 'moderate' : 'strong');
        
        return [
            'direction' => $trendDirection,
            'strength' => $trendStrength,
            'growth_rate' => $growthRate,
            'slope' => $slope
        ];
    }
    
    /**
     * Calculate the slope of a linear regression line
     * 
     * @param array $xValues X values
     * @param array $yValues Y values
     * @return float Slope
     */
    private function calculateLinearRegressionSlope($xValues, $yValues) {
        $n = count($xValues);
        $sumX = array_sum($xValues);
        $sumY = array_sum($yValues);
        
        $sumXY = 0;
        $sumXX = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += ($xValues[$i] * $yValues[$i]);
            $sumXX += ($xValues[$i] * $xValues[$i]);
        }
        
        $slope = (($n * $sumXY) - ($sumX * $sumY)) / (($n * $sumXX) - ($sumX * $sumX));
        return $slope;
    }
    
    /**
     * Generate forecast based on historical data
     * 
     * @param array $dataPoints Historical data points
     * @param string $period Period type
     * @return array Forecast data points
     */
    private function generateForecast($dataPoints, $period) {
        // In a real implementation, this would use time series forecasting methods
        // For now, generate a simple forecast based on trend
        
        $trends = $this->identifyTrends($dataPoints);
        $lastValue = end($dataPoints)['value'];
        
        // Number of periods to forecast
        $forecastPeriods = [
            'daily' => 30,
            'weekly' => 12,
            'monthly' => 6,
            'quarterly' => 4,
            'yearly' => 2
        ];
        
        $numPeriods = $forecastPeriods[$period];
        $forecast = [];
        
        // Generate forecast points
        for ($i = 1; $i <= $numPeriods; $i++) {
            // Calculate forecasted value with some randomness for realism
            $forecastValue = $lastValue * (1 + ($trends['growth_rate'] * $i / $numPeriods));
            $randomFactor = 0.95 + (mt_rand() / mt_getrandmax() * 0.1); // ±5% random variation
            $forecastValue *= $randomFactor;
            
            // Calculate forecast date
            $forecastDate = $this->calculateForecastDate(end($dataPoints)['date'], $period, $i);
            
            $forecast[] = [
                'date' => $forecastDate,
                'value' => round($forecastValue, 2),
                'is_forecast' => true
            ];
        }
        
        return $forecast;
    }
    
    /**
     * Calculate forecast date based on period
     * 
     * @param string $lastDate Last date in data set
     * @param string $period Period type
     * @param int $periodsAhead Number of periods ahead
     * @return string Forecast date
     */
    private function calculateForecastDate($lastDate, $period, $periodsAhead) {
        switch ($period) {
            case 'daily':
                return date('Y-m-d', strtotime("$lastDate +$periodsAhead days"));
                
            case 'weekly':
                return "Week " . (date('W', strtotime("$lastDate +$periodsAhead weeks")) + $periodsAhead) . 
                       ", " . date('Y', strtotime("$lastDate +$periodsAhead weeks"));
                
            case 'monthly':
                return date('F Y', strtotime("$lastDate +$periodsAhead months"));
                
            case 'quarterly':
                // Parse quarter from format like "2023-Q1"
                preg_match('/(\d{4})-Q(\d)/', $lastDate, $matches);
                $year = (int)$matches[1];
                $quarter = (int)$matches[2];
                
                for ($i = 0; $i < $periodsAhead; $i++) {
                    $quarter++;
                    if ($quarter > 4) {
                        $quarter = 1;
                        $year++;
                    }
                }
                
                return "$year-Q$quarter";
                
            case 'yearly':
                return (int)$lastDate + $periodsAhead;
                
            default:
                return $lastDate;
        }
    }
    
    /**
     * Detect seasonality in the data
     * 
     * @param array $dataPoints Data points
     * @param string $period Period type
     * @return array Seasonality information
     */
    private function detectSeasonality($dataPoints, $period) {
        // In a real implementation, this would use statistical methods
        // to identify seasonal patterns in the data
        
        // For now, return mock seasonality data
        switch ($period) {
            case 'monthly':
                return [
                    'pattern' => 'quarterly',
                    'peak_periods' => ['Q4', 'Q2'],
                    'trough_periods' => ['Q1'],
                    'seasonality_strength' => 'moderate'
                ];
                
            case 'quarterly':
                return [
                    'pattern' => 'annual',
                    'peak_periods' => ['Q4'],
                    'trough_periods' => ['Q1'],
                    'seasonality_strength' => 'strong'
                ];
                
            case 'yearly':
                return [
                    'pattern' => 'multi-year',
                    'peak_periods' => ['even years'],
                    'trough_periods' => ['odd years'],
                    'seasonality_strength' => 'weak'
                ];
                
            default:
                return [
                    'pattern' => 'none',
                    'seasonality_strength' => 'none'
                ];
        }
    }
    
    /**
     * Get sales metrics
     * 
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Sales metrics
     */
    private function getSalesMetrics($startDate, $endDate) {
        // In a real implementation, this would query the database
        // For now, return realistic mock data
        return [
            'total_sales' => mt_rand(500, 1500),
            'total_value' => mt_rand(50000, 500000),
            'average_price' => mt_rand(1000, 5000),
            'median_price' => mt_rand(800, 3000),
            'highest_sale' => mt_rand(10000, 50000),
            'sales_by_day' => $this->generateDailyMetrics($startDate, $endDate, 'sales'),
            'value_by_day' => $this->generateDailyMetrics($startDate, $endDate, 'value')
        ];
    }
    
    /**
     * Get artist metrics
     * 
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Artist metrics
     */
    private function getArtistMetrics($startDate, $endDate) {
        // In a real implementation, this would query the database
        // For now, return realistic mock data
        return [
            'active_artists' => mt_rand(100, 500),
            'new_artists' => mt_rand(20, 100),
            'top_artists' => $this->generateTopArtists(5),
            'new_collectors' => mt_rand(50, 200),
            'returning_collectors' => mt_rand(100, 300)
        ];
    }
    
    /**
     * Get artwork metrics
     * 
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Artwork metrics
     */
    private function getArtworkMetrics($startDate, $endDate) {
        // In a real implementation, this would query the database
        // For now, return realistic mock data
        return [
            'total_artworks' => mt_rand(1000, 5000),
            'new_artworks' => mt_rand(100, 500),
            'sold_artworks' => mt_rand(50, 200),
            'average_time_to_sell' => mt_rand(10, 60),
            'top_artworks' => $this->generateTopArtworks(5)
        ];
    }
    
    /**
     * Get blockchain metrics
     * 
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Blockchain metrics
     */
    private function getBlockchainMetrics($startDate, $endDate) {
        // In a real implementation, this would query the database and blockchain
        // For now, return realistic mock data
        return [
            'tokenized_artworks' => mt_rand(50, 500),
            'total_tokens_issued' => mt_rand(10000, 100000),
            'total_transactions' => mt_rand(500, 2000),
            'unique_wallets' => mt_rand(100, 500),
            'transaction_volume' => mt_rand(50000, 500000)
        ];
    }
    
    /**
     * Calculate growth rate between two dates
     * 
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return float Growth rate percentage
     */
    private function calculateGrowthRate($startDate, $endDate) {
        // In a real implementation, this would calculate growth rate
        // from the database
        
        // For now, generate a realistic growth rate
        return (mt_rand(-10, 30) / 100);
    }
    
    /**
     * Get top categories by sales
     * 
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Top categories
     */
    private function getTopCategories($startDate, $endDate) {
        // In a real implementation, this would query the database
        
        // Mock data for top categories
        $categories = [
            ['name' => 'Digital Art', 'sales' => mt_rand(100, 500), 'value' => mt_rand(50000, 200000)],
            ['name' => 'Painting', 'sales' => mt_rand(100, 400), 'value' => mt_rand(40000, 150000)],
            ['name' => 'Photography', 'sales' => mt_rand(80, 300), 'value' => mt_rand(30000, 100000)],
            ['name' => 'Sculpture', 'sales' => mt_rand(50, 200), 'value' => mt_rand(25000, 80000)],
            ['name' => 'Mixed Media', 'sales' => mt_rand(40, 150), 'value' => mt_rand(20000, 70000)]
        ];
        
        // Sort by sales
        usort($categories, function($a, $b) {
            return $b['sales'] - $a['sales'];
        });
        
        return $categories;
    }
    
    /**
     * Get price range distribution
     * 
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Price range distribution
     */
    private function getPriceRangeDistribution($startDate, $endDate) {
        // In a real implementation, this would query the database
        
        // Mock data for price ranges
        return [
            ['range' => 'Under $100', 'count' => mt_rand(100, 300), 'percentage' => mt_rand(5, 15)],
            ['range' => '$100 - $500', 'count' => mt_rand(200, 500), 'percentage' => mt_rand(15, 30)],
            ['range' => '$500 - $1,000', 'count' => mt_rand(150, 350), 'percentage' => mt_rand(10, 25)],
            ['range' => '$1,000 - $5,000', 'count' => mt_rand(100, 300), 'percentage' => mt_rand(10, 20)],
            ['range' => '$5,000 - $10,000', 'count' => mt_rand(50, 150), 'percentage' => mt_rand(5, 15)],
            ['range' => 'Over $10,000', 'count' => mt_rand(20, 100), 'percentage' => mt_rand(1, 10)]
        ];
    }
    
    /**
     * Generate daily metrics
     * 
     * @param string $startDate Start date
     * @param string $endDate End date
     * @param string $metricType Type of metric (sales, value)
     * @return array Daily metrics
     */
    private function generateDailyMetrics($startDate, $endDate, $metricType) {
        $startTs = strtotime($startDate);
        $endTs = strtotime($endDate);
        $dailyMetrics = [];
        
        for ($timestamp = $startTs; $timestamp <= $endTs; $timestamp += 86400) {
            $date = date('Y-m-d', $timestamp);
            
            if ($metricType === 'sales') {
                $value = mt_rand(5, 50);
            } else { // value
                $value = mt_rand(5000, 50000);
            }
            
            $dailyMetrics[] = [
                'date' => $date,
                'value' => $value
            ];
        }
        
        return $dailyMetrics;
    }
    
    /**
     * Generate top artists
     * 
     * @param int $count Number of artists to generate
     * @return array Top artists
     */
    private function generateTopArtists($count) {
        $artists = [];
        
        for ($i = 1; $i <= $count; $i++) {
            $artists[] = [
                'id' => $i,
                'name' => "Artist $i",
                'sales' => mt_rand(10, 50),
                'value' => mt_rand(10000, 100000),
                'growth' => (mt_rand(-20, 50) / 100)
            ];
        }
        
        // Sort by value
        usort($artists, function($a, $b) {
            return $b['value'] - $a['value'];
        });
        
        return $artists;
    }
    
    /**
     * Generate top artworks
     * 
     * @param int $count Number of artworks to generate
     * @return array Top artworks
     */
    private function generateTopArtworks($count) {
        $artworks = [];
        
        for ($i = 1; $i <= $count; $i++) {
            $artworks[] = [
                'id' => $i,
                'title' => "Artwork $i",
                'artist' => "Artist " . ($i % 3 + 1),
                'price' => mt_rand(5000, 50000),
                'views' => mt_rand(1000, 10000),
                'favorited' => mt_rand(100, 1000)
            ];
        }
        
        // Sort by price
        usort($artworks, function($a, $b) {
            return $b['price'] - $a['price'];
        });
        
        return $artworks;
    }
}

/**
 * Simple file-based cache implementation
 */
class SimpleCache {
    private $cacheDir;
    
    public function __construct($cacheDir) {
        $this->cacheDir = $cacheDir;
        
        // Create cache directory if it doesn't exist
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public function has($key) {
        $filename = $this->getFilename($key);
        
        if (!file_exists($filename)) {
            return false;
        }
        
        $data = $this->read($filename);
        
        if ($data['expires'] < time()) {
            // Cache expired
            unlink($filename);
            return false;
        }
        
        return true;
    }
    
    public function get($key) {
        $filename = $this->getFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $data = $this->read($filename);
        
        if ($data['expires'] < time()) {
            // Cache expired
            unlink($filename);
            return null;
        }
        
        return $data['value'];
    }
    
    public function set($key, $value, $lifetime = 3600) {
        $filename = $this->getFilename($key);
        
        $data = [
            'key' => $key,
            'value' => $value,
            'expires' => time() + $lifetime
        ];
        
        file_put_contents($filename, serialize($data));
    }
    
    private function getFilename($key) {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
    
    private function read($filename) {
        return unserialize(file_get_contents($filename));
    }
} 