-- VORTEX AI Marketplace - Metrics Schema
-- This table stores time-based metrics for the entire marketplace

CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}vortex_metrics` (
  `metric_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `metric_date` date NOT NULL,
  `metric_type` varchar(50) NOT NULL,
  `metric_scope` varchar(50) DEFAULT 'marketplace',
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `numeric_value` decimal(30,18) DEFAULT 0.000000000000000000,
  `string_value` text DEFAULT NULL,
  `count_value` bigint(20) UNSIGNED DEFAULT 0,
  `data_json` longtext DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`metric_id`),
  UNIQUE KEY `metric_unique` (`metric_date`, `metric_type`, `metric_scope`, `entity_id`),
  KEY `metric_type` (`metric_type`),
  KEY `metric_scope` (`metric_scope`),
  KEY `entity_id` (`entity_id`),
  KEY `metric_date` (`metric_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comments to the table
ALTER TABLE `{$wpdb->prefix}vortex_metrics` COMMENT 'Stores time-based metrics for VORTEX AI Marketplace'; 