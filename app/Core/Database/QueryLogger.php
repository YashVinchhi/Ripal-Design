<?php

namespace App\Core\Database;

class QueryLogger
{
    private static array $log = [];
    private static float $threshold = 0.2;
    private static string $slowLogPath = '';

    public static function init(): void
    {
        if (self::$slowLogPath !== '') {
            return;
        }

        $projectRoot = defined('PROJECT_ROOT') ? rtrim((string)PROJECT_ROOT, '/\\') : dirname(__DIR__, 3);
        $logsDir = $projectRoot . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($logsDir)) {
            @mkdir($logsDir, 0755, true);
        }

        self::$slowLogPath = $logsDir . DIRECTORY_SEPARATOR . 'slow-queries.log';
    }

    public static function wrap(\PDO $pdo, string $sql, array $params = []): \PDOStatement|false
    {
        self::init();

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $trace[1] ?? [];
        $start = microtime(true);

        try {
            $stmt = $pdo->prepare($sql);
            if (!$stmt) {
                return false;
            }

            if (!$stmt->execute($params)) {
                return false;
            }

            $elapsed = microtime(true) - $start;
            self::$log[] = [
                'sql' => $sql,
                'ms' => round($elapsed * 1000, 2),
                'file' => (string)($caller['file'] ?? ''),
                'line' => (int)($caller['line'] ?? 0),
            ];

            if ($elapsed > self::$threshold && self::$slowLogPath !== '') {
                if (function_exists('app_log_rotate_file')) {
                    app_log_rotate_file(self::$slowLogPath, 10 * 1024 * 1024);
                }
                $entry = sprintf(
                    "[%s] [%.0fms] %s:%d | %s\n",
                    date('Y-m-d H:i:s'),
                    $elapsed * 1000,
                    basename((string)($caller['file'] ?? '')),
                    (int)($caller['line'] ?? 0),
                    preg_replace('/\s+/', ' ', trim($sql))
                );
                file_put_contents(self::$slowLogPath, $entry, FILE_APPEND | LOCK_EX);
            }

            return $stmt;
        } catch (\PDOException $e) {
            if (function_exists('app_log')) {
                app_log('warning', 'Database query error', ['exception' => $e->getMessage()]);
            }
            return false;
        }
    }

    public static function setThreshold(float $seconds): void
    {
        self::$threshold = $seconds;
    }

    public static function getSummary(): array
    {
        $log = self::$log;
        usort($log, static function (array $left, array $right): int {
            return ($right['ms'] ?? 0) <=> ($left['ms'] ?? 0);
        });

        return $log;
    }

    public static function getTotalTime(): float
    {
        $total = 0.0;
        foreach (self::$log as $entry) {
            $total += (float)($entry['ms'] ?? 0);
        }

        return $total;
    }
}