-- VORTEX AI Marketplace - Token Transactions Schema
-- This table stores TOLA token transactions for the marketplace

CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}vortex_token_transactions` (
  `transaction_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `artwork_id` bigint(20) UNSIGNED DEFAULT NULL,
  `artist_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `transaction_type` varchar(50) NOT NULL,
  `amount` decimal(30,18) NOT NULL DEFAULT 0.000000000000000000,
  `source_wallet` varchar(255) DEFAULT NULL,
  `destination_wallet` varchar(255) DEFAULT NULL,
  `blockchain` varchar(50) DEFAULT NULL,
  `transaction_hash` varchar(255) DEFAULT NULL,
  `block_number` bigint(20) UNSIGNED DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `details` text DEFAULT NULL,
  `fee` decimal(30,18) DEFAULT 0.000000000000000000,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`transaction_id`),
  KEY `user_id` (`user_id`),
  KEY `artwork_id` (`artwork_id`),
  KEY `artist_id` (`artist_id`),
  KEY `order_id` (`order_id`),
  KEY `transaction_type` (`transaction_type`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  KEY `transaction_hash` (`transaction_hash`),
  KEY `blockchain` (`blockchain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comments to the table
ALTER TABLE `{$wpdb->prefix}vortex_token_transactions` COMMENT 'Stores TOLA token transactions for VORTEX AI Marketplace'; 