<?php

namespace App\Security;

class SecurityMiddleware
{
    /**
     * Validate and sanitize input
     */
    public static function validateInput($input, $type = 'string', $maxLength = 255)
    {
        if ($input === null || $input === '') {
            return null;
        }

        switch ($type) {
            case 'int':
                $value = filter_var($input, FILTER_VALIDATE_INT);
                return $value !== false ? $value : null;
            
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL);
            
            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL);
            
            case 'string':
            default:
                $value = htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
                return strlen($value) <= $maxLength ? $value : null;
        }
    }

    /**
     * Validate CSRF token
     */
    public static function validateCSRF()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Check for AJAX request header
            if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
                return false;
            }
            
            // Additional CSRF token validation can be added here
            // if (isset($_POST['csrf_token']) && !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            //     return false;
            // }
        }
        return true;
    }

    /**
     * Set security headers
     */
    public static function setSecurityHeaders()
    {
        // Prevent XSS attacks
        header('X-Content-Type-Options: nosniff');
        
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Strict transport security (HTTPS only)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /**
     * Validate session security
     */
    public static function validateSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration']) || 
            time() - $_SESSION['last_regeneration'] > 300) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }

    /**
     * Sanitize output
     */
    public static function sanitizeOutput($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeOutput'], $data);
        }
        
        if (is_string($data)) {
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        
        return $data;
    }

    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $allowedTypes = [], $maxSize = 5242880)
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        if ($file['size'] > $maxSize) {
            return false;
        }
        
        if (!empty($allowedTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Generate secure random token
     */
    public static function generateSecureToken($length = 32)
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Rate limiting check
     */
    public static function checkRateLimit($key, $maxRequests = 100, $timeWindow = 3600)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rateLimitKey = "rate_limit_{$key}_{$ip}";
        
        if (!isset($_SESSION[$rateLimitKey])) {
            $_SESSION[$rateLimitKey] = ['count' => 0, 'reset_time' => time() + $timeWindow];
        }
        
        if (time() > $_SESSION[$rateLimitKey]['reset_time']) {
            $_SESSION[$rateLimitKey] = ['count' => 0, 'reset_time' => time() + $timeWindow];
        }
        
        $_SESSION[$rateLimitKey]['count']++;
        
        return $_SESSION[$rateLimitKey]['count'] <= $maxRequests;
    }
} 