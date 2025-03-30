-- VORTEX AI Marketplace - Artist Statistics Schema
-- This table stores aggregated statistics and metrics for each artist

CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}vortex_artist_stats` (
  `stat_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `artist_id` bigint(20) UNSIGNED NOT NULL,
  `total_artworks` bigint(20) UNSIGNED DEFAULT 0,
  `total_views` bigint(20) UNSIGNED DEFAULT 0,
  `total_likes` bigint(20) UNSIGNED DEFAULT 0,
  `total_downloads` bigint(20) UNSIGNED DEFAULT 0,
  `total_shares` bigint(20) UNSIGNED DEFAULT 0,
  `total_comments` bigint(20) UNSIGNED DEFAULT 0,
  `total_sales` bigint(20) UNSIGNED DEFAULT 0,
  `total_revenue` decimal(30,18) DEFAULT 0.000000000000000000,
  `total_tola_revenue` decimal(30,18) DEFAULT 0.000000000000000000,
  `average_artwork_rating` decimal(3,2) DEFAULT 0.00,
  `follower_count` bigint(20) UNSIGNED DEFAULT 0,
  `trending_score` decimal(10,4) DEFAULT 0.0000,
  `popularity_rank` bigint(20) UNSIGNED DEFAULT NULL,
  `artist_rating` decimal(3,2) DEFAULT 0.00,
  `last_sale_date` datetime DEFAULT NULL,
  `daily_stats_json` text DEFAULT NULL,
  `weekly_stats_json` text DEFAULT NULL,
  `monthly_stats_json` text DEFAULT NULL,
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`stat_id`),
  UNIQUE KEY `artist_id` (`artist_id`),
  KEY `trending_score` (`trending_score`),
  KEY `popularity_rank` (`popularity_rank`),
  KEY `total_views` (`total_views`),
  KEY `total_sales` (`total_sales`),
  KEY `total_revenue` (`total_revenue`),
  KEY `average_artwork_rating` (`average_artwork_rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comments to the table
ALTER TABLE `{$wpdb->prefix}vortex_artist_stats` COMMENT 'Stores artist statistics and metrics for VORTEX AI Marketplace'; 