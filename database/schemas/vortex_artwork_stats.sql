-- VORTEX AI Marketplace - Artwork Statistics Schema
-- This table stores statistics and metrics for each artwork

CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}vortex_artwork_stats` (
  `stat_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `artwork_id` bigint(20) UNSIGNED NOT NULL,
  `views` bigint(20) UNSIGNED DEFAULT 0,
  `likes` bigint(20) UNSIGNED DEFAULT 0,
  `downloads` bigint(20) UNSIGNED DEFAULT 0,
  `shares` bigint(20) UNSIGNED DEFAULT 0,
  `comments` bigint(20) UNSIGNED DEFAULT 0,
  `sales_count` bigint(20) UNSIGNED DEFAULT 0,
  `total_revenue` decimal(30,18) DEFAULT 0.000000000000000000,
  `tola_revenue` decimal(30,18) DEFAULT 0.000000000000000000,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `rating_count` bigint(20) UNSIGNED DEFAULT 0,
  `trending_score` decimal(10,4) DEFAULT 0.0000,
  `popularity_rank` bigint(20) UNSIGNED DEFAULT NULL,
  `last_sale_date` datetime DEFAULT NULL,
  `daily_stats_json` text DEFAULT NULL,
  `weekly_stats_json` text DEFAULT NULL,
  `monthly_stats_json` text DEFAULT NULL,
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`stat_id`),
  UNIQUE KEY `artwork_id` (`artwork_id`),
  KEY `trending_score` (`trending_score`),
  KEY `popularity_rank` (`popularity_rank`),
  KEY `views` (`views`),
  KEY `sales_count` (`sales_count`),
  KEY `total_revenue` (`total_revenue`),
  KEY `average_rating` (`average_rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comments to the table
ALTER TABLE `{$wpdb->prefix}vortex_artwork_stats` COMMENT 'Stores artwork statistics and metrics for VORTEX AI Marketplace'; 