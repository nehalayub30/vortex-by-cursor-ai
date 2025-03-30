-- VORTEX AI Marketplace - Orders Schema
-- This table stores order information for marketplace purchases

CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}vortex_orders` (
  `order_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `currency` varchar(10) DEFAULT 'TOLA',
  `subtotal` decimal(30,18) DEFAULT 0.000000000000000000,
  `discount` decimal(30,18) DEFAULT 0.000000000000000000,
  `tax` decimal(30,18) DEFAULT 0.000000000000000000,
  `total` decimal(30,18) DEFAULT 0.000000000000000000,
  `coupon_code` varchar(50) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 