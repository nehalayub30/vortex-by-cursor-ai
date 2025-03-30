-- VORTEX AI Marketplace - Artists Schema
-- This table stores artist information that links to WordPress users
-- while providing additional wallet and verification details

CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}vortex_artists` (
  `artist_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `wallet_address` varchar(255) DEFAULT NULL,
  `wallet_network` varchar(50) DEFAULT 'solana',
  `is_verified` tinyint(1) UNSIGNED DEFAULT 0,
  `verification_date` datetime DEFAULT NULL,
  `verification_hash` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `featured_artwork_id` bigint(20) UNSIGNED DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT NULL,
  `social_twitter` varchar(255) DEFAULT NULL,
  `social_instagram` varchar(255) DEFAULT NULL,
  `social_website` varchar(255) DEFAULT NULL,
  `tola_balance` decimal(30,18) DEFAULT 0.000000000000000000,
  `status` varchar(50) DEFAULT 'pending',
  `creation_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_date` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`artist_id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `wallet_address` (`wallet_address`, `wallet_network`),
  KEY `status` (`status`),
  KEY `is_verified` (`is_verified`),
  KEY `featured_artwork_id`
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 