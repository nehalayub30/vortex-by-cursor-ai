-- VORTEX AI Marketplace - Sales Schema
-- This table stores sales data for artworks

CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}vortex_sales` (
  `sale_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `artwork_id` bigint(20) UNSIGNED NOT NULL,
  `buyer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `seller_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sale_price` decimal(30,18) DEFAULT 0.000000000000000000,
  `currency` varchar(10) DEFAULT 'TOLA',
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_hash` varchar(255) DEFAULT NULL,
  `blockchain` varchar(50) DEFAULT NULL,
  `edition_number` int(11) UNSIGNED DEFAULT NULL,
  `royalty_percentage` decimal(5,2) DEFAULT 0.00,
  `royalty_amount` decimal(30,18) DEFAULT 0.000000000000000000,
  `marketplace_fee` decimal(30,18) DEFAULT 0.000000000000000000,
  `sale_type` varchar(50) DEFAULT 'primary',
  `status` varchar(50) DEFAULT 'completed',
  `includes_nft` tinyint(1) UNSIGNED DEFAULT 0,
  `nft_token_id` varchar(255) DEFAULT NULL,
  `nft_contract` varchar(255) DEFAULT NULL,
  `sale_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`sale_id`),
  KEY `artwork_id` (`artwork_id`),
  KEY `buyer_id` (`buyer_id`),
  KEY `seller_id` (`seller_id`),
  KEY `order_id` (`order_id`),
  KEY `transaction_hash` (`transaction_hash`),
  KEY `sale_date` (`sale_date`),
  KEY `sale_type` (`sale_type`),
  KEY `status` (`status`),
  KEY `includes_nft` (`includes_nft`),
  KEY `nft_token_id` (`nft_token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comments to the table
ALTER TABLE `{$wpdb->prefix}vortex_sales` COMMENT 'Stores sales data for artworks in VORTEX AI Marketplace'; 