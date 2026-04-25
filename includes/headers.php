<?php
/**
 * Central security headers loader.
 *
 * Put site-wide header policies here. This file is required by the bootstrap
 * if present and defines `apply_security_headers()` which is invoked during
 * initialization.
 */

if (!function_exists('apply_security_headers')) {
    function apply_security_headers(): void {
        if (headers_sent()) {
            return;
        }

        // Prevent clickjacking
        header('X-Frame-Options: DENY');

        // Prevent content type sniffing
        header('X-Content-Type-Options: nosniff');

        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Permissions policy - restrict sensors
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        // Content Security Policy - adjust if your app needs additional sources
        $csp = "default-src 'self' https: data: blob: 'unsafe-inline';";
        $csp .= " script-src 'self' https: 'unsafe-inline' 'unsafe-eval';";
        $csp .= " style-src 'self' https: 'unsafe-inline';";
        $csp .= " img-src 'self' https: data: blob:;";
        $csp .= " font-src 'self' https: data:;";
        $csp .= " connect-src 'self' https: wss:;";
        $csp .= " frame-src 'self' https:;";
        $csp .= " media-src 'self' https: data: blob:;";
        $csp .= " object-src 'none';";
        header('Content-Security-Policy: ' . $csp);

        // HSTS when behind HTTPS (controlled by SECURITY_ENABLE_HSTS constant)
        if (function_exists('app_is_https') && app_is_https() && defined('SECURITY_ENABLE_HSTS') && SECURITY_ENABLE_HSTS) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}

return true;
