<?php
/**
 * VORTEX Marketplace Encryption Keys
 *
 * Contains encryption keys and methods for secure data handling in the VORTEX Marketplace plugin.
 * This file should be protected and have restricted access.
 *
 * @package VORTEX_Marketplace
 * @subpackage Security
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Encryption keys and security functionality
 */
class VORTEX_Encryption_Keys {
    /**
     * The encryption key used for sensitive data
     *
     * @var string
     */
    private static $encryption_key = 'Vx9m3$D4o#T0k3n!M4rk3tpl4c3@K3y2023';
    
    /**
     * The initialization vector for encryption
     *
     * @var string
     */
    private static $iv = 'VxD40M4rk3tpl4c3';
    
    /**
     * Salt for password hashing
     *
     * @var string
     */
    private static $salt = 'V0rt3xD40M4rk3tpl4c3$4lt';
    
    /**
     * Encryption method
     *
     * @var string
     */
    private static $cipher = 'AES-256-CBC';
    
    /**
     * JWT secret key for API authentication
     *
     * @var string
     */
    private static $jwt_secret = 'V0rt3xD40JWT$3cr3tK3y2023';
    
    /**
     * Encrypt data using OpenSSL
     *
     * @param string $data The data to encrypt
     * @return string The encrypted data (base64 encoded)
     */
    public static function encrypt($data) {
        if (empty($data)) {
            return '';
        }
        
        // Check if OpenSSL is available
        if (!function_exists('openssl_encrypt')) {
            // Fallback to basic encryption if OpenSSL is not available
            return base64_encode(self::$salt . $data);
        }
        
        // Use OpenSSL for encryption
        $encrypted = openssl_encrypt(
            $data, 
            self::$cipher, 
            self::$encryption_key, 
            0, 
            self::$iv
        );
        
        return $encrypted;
    }
    
    /**
     * Decrypt data using OpenSSL
     *
     * @param string $encrypted_data The encrypted data (base64 encoded)
     * @return string The decrypted data
     */
    public static function decrypt($encrypted_data) {
        if (empty($encrypted_data)) {
            return '';
        }
        
        // Check if OpenSSL is available
        if (!function_exists('openssl_decrypt')) {
            // Fallback to basic decryption if OpenSSL is not available
            $decoded = base64_decode($encrypted_data);
            return substr($decoded, strlen(self::$salt));
        }
        
        // Use OpenSSL for decryption
        $decrypted = openssl_decrypt(
            $encrypted_data, 
            self::$cipher, 
            self::$encryption_key, 
            0, 
            self::$iv
        );
        
        return $decrypted;
    }
    
    /**
     * Hash a password securely
     *
     * @param string $password The password to hash
     * @return string The hashed password
     */
    public static function hash_password($password) {
        if (function_exists('password_hash')) {
            // Use PHP's password_hash function if available (PHP 5.5+)
            return password_hash($password . self::$salt, PASSWORD_BCRYPT, ['cost' => 12]);
        } else {
            // Fallback for older PHP versions
            return hash('sha256', $password . self::$salt);
        }
    }
    
    /**
     * Verify a password against a hash
     *
     * @param string $password The password to verify
     * @param string $hash The hash to check against
     * @return bool True if the password matches the hash
     */
    public static function verify_password($password, $hash) {
        if (function_exists('password_verify')) {
            // Use PHP's password_verify function if available (PHP 5.5+)
            return password_verify($password . self::$salt, $hash);
        } else {
            // Fallback for older PHP versions
            return hash('sha256', $password . self::$salt) === $hash;
        }
    }
    
    /**
     * Generate a secure random token
     *
     * @param int $length The length of the token
     * @return string The generated token
     */
    public static function generate_token($length = 32) {
        if (function_exists('random_bytes')) {
            // Use random_bytes if available (PHP 7+)
            return bin2hex(random_bytes($length / 2));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            // Use OpenSSL if available
            return bin2hex(openssl_random_pseudo_bytes($length / 2));
        } else {
            // Fallback to less secure method
            $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $token = '';
            for ($i = 0; $i < $length; $i++) {
                $token .= $chars[mt_rand(0, strlen($chars) - 1)];
            }
            return $token;
        }
    }
    
    /**
     * Generate a JWT token for API authentication
     *
     * @param array $payload The payload data for the token
     * @param int $expiry Expiry time in seconds (default: 1 hour)
     * @return string The JWT token
     */
    public static function generate_jwt($payload, $expiry = 3600) {
        // Add expiry time to payload
        $payload['exp'] = time() + $expiry;
        $payload['iat'] = time();
        
        // Create JWT parts
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $header_encoded = self::base64url_encode($header);
        $payload_encoded = self::base64url_encode(json_encode($payload));
        
        // Create signature
        $signature = hash_hmac('sha256', $header_encoded . '.' . $payload_encoded, self::$jwt_secret, true);
        $signature_encoded = self::base64url_encode($signature);
        
        // Create JWT token
        return $header_encoded . '.' . $payload_encoded . '.' . $signature_encoded;
    }
    
    /**
     * Verify a JWT token
     *
     * @param string $token The JWT token to verify
     * @return array|false The payload if valid, false otherwise
     */
    public static function verify_jwt($token) {
        // Split token into parts
        $parts = explode('.', $token);
        if (count($parts) != 3) {
            return false;
        }
        
        list($header_encoded, $payload_encoded, $signature_encoded) = $parts;
        
        // Verify signature
        $signature = self::base64url_decode($signature_encoded);
        $expected_signature = hash_hmac('sha256', $header_encoded . '.' . $payload_encoded, self::$jwt_secret, true);
        
        if (!hash_equals($signature, $expected_signature)) {
            return false;
        }
        
        // Decode payload
        $payload = json_decode(self::base64url_decode($payload_encoded), true);
        
        // Check expiry
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Base64URL encode
     *
     * @param string $data The data to encode
     * @return string The encoded data
     */
    private static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64URL decode
     *
     * @param string $data The data to decode
     * @return string The decoded data
     */
    private static function base64url_decode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
} 