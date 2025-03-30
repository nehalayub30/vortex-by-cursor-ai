-- VORTEX AI Marketplace - Order Items Schema
-- This table stores individual order items for marketplace purchases

CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}vortex_order_items` (
  `item_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `artwork_id` bigint(20) UNSIGNED NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int(11) UNSIGNED DEFAULT 1,
  `price` decimal(30,18) DEFAULT 0.000000000000000000,
  `subtotal` decimal(30,18) DEFAULT 0.000000000000000000,
  `tax` decimal(30,18) DEFAULT 0.000000000000000000,
  `total` decimal(30,18) DEFAULT 0.000000000000000000,
  `artist_id` bigint(20) UNSIGNED DEFAULT NULL,
  `artist_commission` decimal(30,18) DEFAULT 0.000000000000000000,
  `marketplace_fee` decimal(30,18) DEFAULT 0.000000000000000000,
  `edition_number` int(11) UNSIGNED DEFAULT NULL,
  `includes_nft` tinyint(1) UNSIGNED DEFAULT 0,
  `token_id` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `sale_id` bigint(20) UNSIGNED DEFAULT NULL,
  `license_type` varchar(50) DEFAULT 'standard',
  `download_count` int(11) UNSIGNED DEFAULT 0,
  `download_limit` int(11) UNSIGNED DEFAULT NULL,
  `is_digital` tinyint(1) UNSIGNED DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`item_id`),
  KEY `order_id` (`order_id`),
  KEY `artwork_id` (`artwork_id`),
  KEY `artist_id` (`artist_id`),
  KEY `status` (`status`),
  KEY `sale_id` (`sale_id`),
  KEY `license_type` (`license_type`),
  KEY `includes_nft` (`includes_nft`),
  KEY `token_id` (`token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comments to the table
ALTER TABLE `{$wpdb->prefix}vortex_order_items` COMMENT 'Stores order items for VORTEX AI Marketplace'; 