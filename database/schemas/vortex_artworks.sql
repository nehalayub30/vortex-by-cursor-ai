-- VORTEX AI Marketplace - Artworks Schema
-- This table stores core artwork information that links to WordPress posts
-- while providing additional blockchain and AI-specific metadata

CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}vortex_artworks` (
  `artwork_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) UNSIGNED NOT NULL,
  `artist_id` bigint(20) UNSIGNED NOT NULL,
  `token_id` varchar(255) DEFAULT NULL,
  `contract_address` varchar(255) DEFAULT NULL,
  `blockchain` varchar(50) DEFAULT NULL,
  `mint_date` datetime DEFAULT NULL,
  `mint_hash` varchar(255) DEFAULT NULL,
  `token_uri` text DEFAULT NULL,
  `ai_model` varchar(100) DEFAULT NULL,
  `ai_prompt` text DEFAULT NULL,
  `ai_settings` text DEFAULT NULL,
  `edition_number` int(11) UNSIGNED DEFAULT 1,
  `edition_total` int(11) UNSIGNED DEFAULT 1,
  `is_minted` tinyint(1) UNSIGNED DEFAULT 0,
  `price` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `status` varchar(50) DEFAULT 'draft',
  `creation_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_date` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`artwork_id`),
  UNIQUE KEY `post_id` (`post_id`),
  KEY `artist_id` (`artist_id`),
  KEY `blockchain_token` (`blockchain`, `token_id`, `contract_address`),
  KEY `status` (`status`),
  KEY `ai_model` (`ai_model`),
  KEY `price` (`price`),
  KEY `creation_date` (`creation_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comments to the table and columns
ALTER TABLE `{$wpdb->prefix}vortex_artworks` COMMENT 'Stores artwork metadata related to VORTEX AI Marketplace'; 