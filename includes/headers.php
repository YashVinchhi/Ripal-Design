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

        $nonce = base64_encode(random_bytes(16));
        $_REQUEST['csp_nonce'] = $nonce;

        if (!defined('SECURITY_ENABLE_CSP') || SECURITY_ENABLE_CSP) {
            $csp = defined('SECURITY_CSP_POLICY') ? (string)SECURITY_CSP_POLICY : "default-src 'self'; script-src 'self' 'nonce-{$nonce}'; style-src 'self' 'unsafe-inline'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'";
            header('Content-Security-Policy: ' . $csp);
        }

        // HSTS when behind HTTPS (controlled by SECURITY_ENABLE_HSTS constant)
        if (function_exists('app_is_https') && app_is_https() && (!defined('SECURITY_ENABLE_HSTS') || SECURITY_ENABLE_HSTS)) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}

return true;