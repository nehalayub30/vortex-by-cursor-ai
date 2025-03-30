-- VORTEX AI Marketplace - Rankings Schema
-- This table stores calculated rankings for artworks and artists

CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}vortex_rankings` (
  `ranking_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` bigint(20) UNSIGNED NOT NULL,
  `ranking_type` varchar(50) NOT NULL,
  `ranking_period` varchar(50) DEFAULT 'alltime',
  `rank` int(11) UNSIGNED NOT NULL,
  `score` decimal(30,18) DEFAULT 0.000000000000000000,
  `previous_rank` int(11) UNSIGNED DEFAULT NULL,
  `rank_change` int(11) DEFAULT 0,
  `category` varchar(100) DEFAULT NULL,
  `calculated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `valid_until` datetime DEFAULT NULL,
  PRIMARY KEY (`ranking_id`),
  UNIQUE KEY `entity_ranking` (`entity_type`, `entity_id`, `ranking_type`, `ranking_period`, `category`),
  KEY `rank` (`rank`),
  KEY `score` (`score`),
  KEY `ranking_type` (`ranking_type`),
  KEY `ranking_period` (`ranking_period`),
  KEY `entity_type_id` (`entity_type`, `entity_id`),
  KEY `category` (`category`),
  KEY `calculated_at` (`calculated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comments to the table
ALTER TABLE `{$wpdb->prefix}vortex_rankings` COMMENT 'Stores calculated rankings for VORTEX AI Marketplace'; 