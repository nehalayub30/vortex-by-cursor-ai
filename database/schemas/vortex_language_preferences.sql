-- VORTEX AI Marketplace - Language Preferences Schema
-- This table stores user language preferences

CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}vortex_language_preferences` (
  `preference_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `language_code` varchar(10) NOT NULL DEFAULT 'en',
  `is_auto_detected` tinyint(1) UNSIGNED DEFAULT 0,
  `interface_language` varchar(10) DEFAULT NULL,
  `content_language` varchar(10) DEFAULT NULL,
  `translation_enabled` tinyint(1) UNSIGNED DEFAULT 1,
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`preference_id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `language_code` (`language_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 