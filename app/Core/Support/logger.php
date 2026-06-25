<?php

if (!class_exists('AppLogger')) {
    class AppLogger
    {
        private $logPath;
        private $maxBytes;

        public function __construct(?string $logPath = null, int $maxBytes = 10485760)
        {
            $projectRoot = defined('PROJECT_ROOT') ? rtrim((string)PROJECT_ROOT, '/\\') : rtrim((string)dirname(__DIR__, 3), '/\\');
            $this->logPath = $logPath ?? ($projectRoot . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'app.log');
            $this->maxBytes = $maxBytes;

            $logDir = dirname($this->logPath);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }
        }

        private function rotateIfNeeded(): void
        {
            app_log_rotate_file($this->logPath, $this->maxBytes);
        }

        private function normalizeContext(array $context): array
        {
            return $context;
        }

        private function formatLine(string $level, string $message, array $context = [], string $file = '', int $line = 0): string
        {
            $payload = [
                'ts' => gmdate('c'),
                'level' => (string)$level,
                'request_id' => function_exists('request_id') ? request_id() : (string)($_SERVER['X_REQUEST_ID'] ?? 'no-id'),
                'message' => $message,
                'context' => (object)$context,
                'file' => $file,
                'line' => $line,
            ];

            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return is_string($json) ? $json . PHP_EOL : '';
        }

        public function log(string $level, string $message, array $context = [], string $file = '', int $line = 0): void
        {
            if ($file === '' || $line === 0) {
                $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6);
                foreach ($bt as $frame) {
                    if (isset($frame['file']) && strpos($frame['file'], __FILE__) === false) {
                        $file = $frame['file'];
                        $line = isset($frame['line']) ? (int)$frame['line'] : 0;
                        break;
                    }
                }
            }

            $this->rotateIfNeeded();

            $lineStr = $this->formatLine($level, $message, $this->normalizeContext($context), $file, $line);
            if ($lineStr === '') {
                return;
            }

            $fp = @fopen($this->logPath, 'a');
            if ($fp !== false) {
                @flock($fp, LOCK_EX);
                @fwrite($fp, $lineStr);
                @flock($fp, LOCK_UN);
                @fclose($fp);
                return;
            }

            // Fallback to PHP error_log when file isn't writable
            @error_log($lineStr);
        }

        public function info(string $message, array $context = []): void
        {
            $this->log('info', $message, $context);
        }

        public function warning(string $message, array $context = []): void
        {
            $this->log('warning', $message, $context);
        }

        public function error(string $message, array $context = []): void
        {
            $this->log('error', $message, $context);
        }

        public function debug(string $message, array $context = []): void
        {
            $this->log('debug', $message, $context);
        }
    }
}

if (!function_exists('app_log_rotate_file')) {
    function app_log_rotate_file(string $logPath, int $maxBytes = 10485760): void
    {
        if ($logPath === '' || !is_file($logPath)) {
            return;
        }

        clearstatcache(true, $logPath);
        $size = @filesize($logPath);
        if ($size === false || $size <= $maxBytes) {
            return;
        }

        $dir = dirname($logPath);
        $base = pathinfo($logPath, PATHINFO_FILENAME);
        $ext = pathinfo($logPath, PATHINFO_EXTENSION);
        $rotated = $dir . DIRECTORY_SEPARATOR . $base . '-' . date('Y-m-d-H') . ($ext !== '' ? '.' . $ext : '');
        $suffix = 1;
        while (file_exists($rotated)) {
            $rotated = $dir . DIRECTORY_SEPARATOR . $base . '-' . date('Y-m-d-H') . '-' . $suffix . ($ext !== '' ? '.' . $ext : '');
            $suffix++;
        }

        @rename($logPath, $rotated);
    }
}

if (!function_exists('app_logger')) {
    function app_logger()
    {
        static $logger = null;
        if ($logger !== null) {
            return $logger;
        }

        $projectRoot = defined('PROJECT_ROOT') ? rtrim((string)PROJECT_ROOT, '/\\') : rtrim((string)dirname(__DIR__, 3), '/\\');
        $logPath = $projectRoot . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'app.log';
        $logger = new AppLogger($logPath, 10 * 1024 * 1024);

        // Register PHP error/exception handlers once
        if (!defined('APP_LOGGER_HANDLERS_REGISTERED')) {
            define('APP_LOGGER_HANDLERS_REGISTERED', 1);

            set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$logger) {
                // Respect @ operator
                if (!(error_reporting() & $errno)) {
                    return false;
                }

                $level = 'error';
                if (in_array($errno, [E_WARNING, E_USER_WARNING, E_COMPILE_WARNING, E_RECOVERABLE_ERROR], true)) {
                    $level = 'warning';
                } elseif (in_array($errno, [E_NOTICE, E_USER_NOTICE], true)) {
                    $level = 'info';
                } elseif (in_array($errno, [E_DEPRECATED, E_USER_DEPRECATED, E_STRICT], true)) {
                    $level = 'debug';
                }

                $context = ['errno' => $errno, 'errstr' => $errstr];
                if (is_object($logger) && method_exists($logger, 'log')) {
                    $logger->log($level, $errstr, $context, $errfile, $errline);
                } else {
                    error_log(strtoupper($level) . ': ' . $errstr . ' in ' . $errfile . ':' . $errline);
                }

                // Let PHP internal handler also run
                return false;
            });

            set_exception_handler(function ($e) use (&$logger) {
                $msg = $e instanceof Throwable ? $e->getMessage() : (string)$e;
                $file = method_exists($e, 'getFile') ? $e->getFile() : '';
                $line = method_exists($e, 'getLine') ? $e->getLine() : 0;
                $context = ['exception' => is_object($e) ? get_class($e) : '', 'trace' => method_exists($e, 'getTraceAsString') ? $e->getTraceAsString() : ''];
                if (is_object($logger) && method_exists($logger, 'log')) {
                    $logger->log('error', $msg, $context, $file, $line);
                } else {
                    error_log('ERROR: ' . $msg . ' in ' . $file . ':' . $line);
                }

                // Generic response for web requests - never reveal internals
                if (PHP_SAPI !== 'cli') {
                    if (!headers_sent()) {
                        http_response_code(500);
                        header('Content-Type: text/plain; charset=utf-8');
                    }
                    echo 'An internal server error occurred.';
                }
            });

            register_shutdown_function(function () use (&$logger) {
                $err = error_get_last();
                if ($err && isset($err['type']) && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                    $context = ['errno' => $err['type'], 'errstr' => $err['message'] ?? ''];
                    if (is_object($logger) && method_exists($logger, 'log')) {
                        $logger->log('error', $err['message'] ?? '', $context, $err['file'] ?? '', $err['line'] ?? 0);
                    } else {
                        error_log('FATAL: ' . ($err['message'] ?? ''));
                    }
                }
            });
        }

        return $logger;
    }
}

if (!function_exists('app_log')) {
    function app_log($level, $message = null, array $context = []): void
    {
        $logger = app_logger();
        if ($message === null) {
            $message = (string)$level;
            $level = 'info';
        }

        if (is_object($logger) && method_exists($logger, 'log')) {
            $logger->log((string)$level, (string)$message, $context);
            return;
        }

        $payload = [
            'ts' => gmdate('c'),
            'level' => (string)$level,
            'request_id' => function_exists('request_id') ? request_id() : (string)($_SERVER['X_REQUEST_ID'] ?? 'no-id'),
            'message' => (string)$message,
            'context' => (object)$context,
            'file' => '',
            'line' => 0,
        ];
        $encoded = @json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        error_log(is_string($encoded) ? $encoded : (string)$message);
    }
}
