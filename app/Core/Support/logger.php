<?php

if (!class_exists('AppLogger')) {
    class AppLogger
    {
        private $logPath;
        private $maxBytes;

        public function __construct(string $logPath = null, int $maxBytes = 5242880)
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
            if (!is_file($this->logPath)) {
                return;
            }
            clearstatcache(true, $this->logPath);
            $size = @filesize($this->logPath);
            if ($size === false) {
                return;
            }
            if ($size > $this->maxBytes) {
                $rotated = $this->logPath . '.1';
                if (is_file($rotated)) {
                    @unlink($rotated);
                }
                @rename($this->logPath, $rotated);
            }
        }

        private function normalizeContext(array $context): string
        {
            try {
                $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                return is_string($json) ? $json : '';
            } catch (Throwable $e) {
                return '';
            }
        }

        private function formatLine(string $level, string $message, string $file = '', int $line = 0, string $contextJson = ''): string
        {
            $ts = gmdate('Y-m-d\TH:i:s\Z');
            $level = strtoupper((string)$level);
            $parts = [$ts, $level . ':', $message];
            if ($file !== '') {
                $parts[] = 'in ' . $file . ':' . (int)$line;
            }
            if ($contextJson !== '') {
                $parts[] = $contextJson;
            }
            return implode(' ', $parts) . PHP_EOL;
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

            $contextJson = $this->normalizeContext($context);
            $this->rotateIfNeeded();

            $lineStr = $this->formatLine($level, $message, $file, $line, $contextJson);

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

if (!function_exists('app_logger')) {
    function app_logger()
    {
        static $logger = null;
        if ($logger !== null) {
            return $logger;
        }

        // Prefer Monolog (with RotatingFileHandler) when available
        if (class_exists('Monolog\\Logger') && class_exists('Monolog\\Handler\\RotatingFileHandler')) {
            try {
                $projectRoot = defined('PROJECT_ROOT') ? rtrim((string)PROJECT_ROOT, '/\\') : rtrim((string)dirname(__DIR__, 3), '/\\');
                $logPath = $projectRoot . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'app.log';
                $dir = dirname($logPath);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0775, true);
                }
                $monolog = new \Monolog\Logger('app');
                $handler = new \Monolog\Handler\RotatingFileHandler($logPath, 0, \Monolog\Logger::DEBUG);
                $monolog->pushHandler($handler);
                $logger = $monolog;
            } catch (Throwable $e) {
                $logger = new AppLogger(null, 5 * 1024 * 1024);
            }
        } else {
            $projectRoot = defined('PROJECT_ROOT') ? rtrim((string)PROJECT_ROOT, '/\\') : rtrim((string)dirname(__DIR__, 3), '/\\');
            $logPath = $projectRoot . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'app.log';
            $logger = new AppLogger($logPath, 5 * 1024 * 1024);
        }

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
    function app_log(string $level, string $message, array $context = []): void
    {
        $logger = app_logger();
        if (is_object($logger) && method_exists($logger, 'log')) {
            $logger->log($level, $message, $context);
            return;
        }

        if (!empty($context)) {
            $encoded = @json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (is_string($encoded) && $encoded !== '') {
                error_log(strtoupper($level) . ': ' . $message . ' ' . $encoded);
                return;
            }
        }
        error_log(strtoupper($level) . ': ' . $message);
    }
}
