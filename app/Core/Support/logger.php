<?php

if (!function_exists('app_logger')) {
    /**
     * Get shared application logger instance.
     *
     * Uses Monolog when available, otherwise falls back to PHP error_log.
     *
     * @return mixed
     */
    function app_logger()
    {
        static $logger = null;
        if ($logger !== null) {
            return $logger;
        }

        if (class_exists('Monolog\\Logger') && class_exists('Monolog\\Handler\\StreamHandler')) {
            $projectRoot = defined('PROJECT_ROOT') ? rtrim((string)PROJECT_ROOT, '/\\') : rtrim((string)dirname(__DIR__, 3), '/\\');
            $logPath = $projectRoot . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'app.log';
            $logDir = dirname($logPath);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }

            $loggerClass = 'Monolog\\Logger';
            $handlerClass = 'Monolog\\Handler\\StreamHandler';
            $level = class_exists('Monolog\\Level') && defined('Monolog\\Level::Info') ? constant('Monolog\\Level::Info') : 200;

            $logger = new $loggerClass('app');
            $logger->pushHandler(new $handlerClass($logPath, $level));
            return $logger;
        }

        $logger = false;
        return $logger;
    }
}

if (!function_exists('app_log')) {
    /**
     * Application logging helper with Monolog fallback.
     *
     * @param string $level
     * @param string $message
     * @param array<string,mixed> $context
     * @return void
     */
    function app_log(string $level, string $message, array $context = []): void
    {
        $logger = app_logger();
        if (is_object($logger) && method_exists($logger, 'log')) {
            $logger->log(strtolower($level), $message, $context);
            return;
        }

        if (!empty($context)) {
            $encoded = json_encode($context);
            if (is_string($encoded) && $encoded !== '') {
                error_log(strtoupper($level) . ': ' . $message . ' ' . $encoded);
                return;
            }
        }
        error_log(strtoupper($level) . ': ' . $message);
    }
}
