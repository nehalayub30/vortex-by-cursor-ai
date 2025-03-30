<?php
/**
 * VORTEX Metrics System
 * Handles marketplace metrics with AI agent integration
 *
 * @package VORTEX
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Vortex_Metrics {
    private $ai_manager;
    private $db;
    private $cache_group = 'vortex_metrics';
    private $cache_expiry = 1800; // 30 minutes

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->init();
    }

    /**
     * Initialize metrics system
     */
    private function init() {
        try {
            $this->ai_manager = VORTEX_AI_Manager::get_instance();
            $this->setup_hooks();
            $this->initialize_metrics_tables();
        } catch (Exception $e) {
            $this->log_error('Initialization failed', $e);
        }
    }

    /**
     * Setup hooks
     */
    private function setup_hooks() {
        add_action('vortex_hourly_metrics_update', array($this, 'update_metrics'));
        add_action('vortex_daily_metrics_analysis', array($this, 'analyze_metrics'));
        add_action('vortex_artwork_sold', array($this, 'track_sale_metrics'), 10, 2);
        add_filter('vortex_dashboard_metrics', array($this, 'get_dashboard_metrics'));
    }

    /**
     * Get marketplace metrics
     */
    public function get_metrics($type = 'all', $period = '30days') {
        try {
            $cache_key = "metrics_{$type}_{$period}";
            $metrics = wp_cache_get($cache_key, $this->cache_group);

            if (false === $metrics) {
                // Get base metrics
                $metrics = $this->calculate_metrics($type, $period);

                // Get AI-enhanced insights
                $ai_insights = $this->get_ai_insights($metrics, $type);

                // Merge insights with metrics
                $metrics = array_merge($metrics, $ai_insights);

                wp_cache_set($cache_key, $metrics, $this->cache_group, $this->cache_expiry);
            }

            return $metrics;

        } catch (Exception $e) {
            $this->log_error('Metrics retrieval failed', $e);
            return array();
        }
    }

    /**
     * Calculate specific metrics
     */
    private function calculate_metrics($type, $period) {
        $start_date = $this->get_period_start_date($period);
        $metrics = array();

        switch ($type) {
            case 'sales':
                $metrics = $this->calculate_sales_metrics($start_date);
                break;
            case 'artists':
                $metrics = $this->calculate_artist_metrics($start_date);
                break;
            case 'engagement':
                $metrics = $this->calculate_engagement_metrics($start_date);
                break;
            case 'all':
                $metrics = array(
                    'sales' => $this->calculate_sales_metrics($start_date),
                    'artists' => $this->calculate_artist_metrics($start_date),
                    'engagement' => $this->calculate_engagement_metrics($start_date)
                );
                break;
        }

        return $metrics;
    }

    /**
     * Get AI-enhanced insights
     */
    private function get_ai_insights($metrics, $type) {
        try {
            // Get insights from each AI agent
            $huraii_insights = $this->ai_manager->get_agent('huraii')
                ->analyze_metrics($metrics, $type);

            $cloe_insights = $this->ai_manager->get_agent('cloe')
                ->analyze_metrics($metrics, $type);

            $business_insights = $this->ai_manager->get_agent('business_strategist')
                ->analyze_metrics($metrics, $type);

            return array(
                'ai_insights' => array(
                    'style_trends' => $huraii_insights,
                    'user_behavior' => $cloe_insights,
                    'market_trends' => $business_insights
                )
            );

        } catch (Exception $e) {
            $this->log_error('AI insights retrieval failed', $e);
            return array();
        }
    }

    /**
     * Track sale metrics
     */
    public function track_sale_metrics($artwork_id, $sale_data) {
        try {
            // Validate sale data
            $this->validate_sale_data($sale_data);

            // Record sale metrics
            $this->record_sale($artwork_id, $sale_data);

            // Get AI analysis of sale
            $ai_analysis = $this->ai_manager->analyze_sale($artwork_id, $sale_data);

            // Update market trends
            $this->update_market_trends($ai_analysis);

            // Track for AI learning
            $this->track_metric_event('sale', array(
                'artwork_id' => $artwork_id,
                'sale_data' => $sale_data,
                'analysis' => $ai_analysis
            ));

        } catch (Exception $e) {
            $this->log_error('Sale tracking failed', $e);
        }
    }

    /**
     * Analyze metrics with AI
     */
    public function analyze_metrics() {
        try {
            // Get current metrics
            $metrics = $this->get_metrics('all', '30days');

            // Get AI analysis
            $analysis = array(
                'huraii' => $this->ai_manager->get_agent('huraii')
                    ->analyze_market_trends($metrics),
                'cloe' => $this->ai_manager->get_agent('cloe')
                    ->analyze_user_behavior($metrics),
                'business' => $this->ai_manager->get_agent('business_strategist')
                    ->analyze_market_performance($metrics)
            );

            // Update insights
            $this->update_market_insights($analysis);

            // Track for AI learning
            $this->track_metric_event('analysis', $analysis);

        } catch (Exception $e) {
            $this->log_error('Metrics analysis failed', $e);
        }
    }

    /**
     * Get dashboard metrics
     */
    public function get_dashboard_metrics($user_id = 0) {
        try {
            $cache_key = "dashboard_metrics_" . ($user_id ?: 'global');
            $metrics = wp_cache_get($cache_key, $this->cache_group);

            if (false === $metrics) {
                // Get base metrics
                $metrics = $this->calculate_dashboard_metrics($user_id);

                // Get AI recommendations
                $recommendations = $this->get_ai_recommendations($user_id, $metrics);

                // Merge recommendations with metrics
                $metrics['recommendations'] = $recommendations;

                wp_cache_set($cache_key, $metrics, $this->cache_group, $this->cache_expiry);
            }

            return $metrics;

        } catch (Exception $e) {
            $this->log_error('Dashboard metrics retrieval failed', $e);
            return array();
        }
    }

    /**
     * Track metric event for AI learning
     */
    private function track_metric_event($type, $data) {
        try {
            $this->ai_manager->track_event('metric_event', array(
                'type' => $type,
                'data' => $data,
                'timestamp' => current_time('timestamp')
            ));
        } catch (Exception $e) {
            $this->log_error('Event tracking failed', $e);
        }
    }

    /**
     * Utility functions
     */
    private function get_period_start_date($period) {
        switch ($period) {
            case '7days':
                return strtotime('-7 days');
            case '30days':
                return strtotime('-30 days');
            case '90days':
                return strtotime('-90 days');
            case '1year':
                return strtotime('-1 year');
            default:
                return strtotime('-30 days');
        }
    }

    /**
     * Validation
     */
    private function validate_sale_data($data) {
        if (empty($data['price']) || !is_numeric($data['price'])) {
            throw new Exception(__('Invalid sale price', 'vortex'));
        }

        if (empty($data['buyer_id'])) {
            throw new Exception(__('Invalid buyer information', 'vortex'));
        }

        return true;
    }

    /**
     * Error logging
     */
    private function log_error($message, $error) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[VORTEX Metrics] %s: %s',
                $message,
                $error->getMessage()
            ));
        }
    }
}

// Initialize the metrics system
new Vortex_Metrics(); 